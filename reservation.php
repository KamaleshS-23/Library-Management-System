<?php
// Include the config.php file
require_once('config.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
        // AJAX response for unauthorized
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Please login to reserve books',
            'redirect' => 'index.html'
        ]);
        exit();
    } else {
        header('Location: index.html');
        exit();
    }
}

$user_id = $_SESSION['user_id'];

// Handle book reservation (for both AJAX and normal form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    try {
        // Check if book exists and is available
        $book_check = $pdo->prepare("SELECT * FROM books WHERE id = :book_id AND available_copies > 0");
        $book_check->bindParam(':book_id', $book_id);
        $book_check->execute();
        $book = $book_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Book not available for reservation.'
                ]);
                exit();
            } else {
                $_SESSION['reservation_error'] = 'Book not available for reservation.';
                header('Location: search.php');
                exit();
            }
        }
        
        // Check if user already has a reservation for this book
        $existing_reservation = $pdo->prepare("SELECT * FROM reservations 
                                             WHERE user_id = :user_id AND book_id = :book_id 
                                             AND (status = 'pending' OR status = 'active')");
        $existing_reservation->bindParam(':user_id', $user_id);
        $existing_reservation->bindParam(':book_id', $book_id);
        $existing_reservation->execute();
        
        if ($existing_reservation->rowCount() > 0) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'You already have an active reservation for this book.'
                ]);
                exit();
            } else {
                $_SESSION['reservation_error'] = 'You already have an active reservation for this book.';
                header('Location: search.php');
                exit();
            }
        }
        
        // Create reservation
        $reservation_date = date('Y-m-d H:i:s');
        $expiry_date = date('Y-m-d H:i:s', strtotime('+7 days')); // Reservation lasts 7 days
        
        $stmt = $pdo->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, expiry_date, status) 
                              VALUES (:user_id, :book_id, :reservation_date, :expiry_date, 'pending')");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':book_id', $book_id);
        $stmt->bindParam(':reservation_date', $reservation_date);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->execute();
        
        // Update book available copies
        $update_book = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = :book_id");
        $update_book->bindParam(':book_id', $book_id);
        $update_book->execute();
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Book reserved successfully! You have 7 days to collect it.'
            ]);
            exit();
        } else {
            $_SESSION['reservation_success'] = 'Book reserved successfully! You have 7 days to collect it.';
            header('Location: profile.php');
            exit();
        }
        
    } catch (PDOException $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
            exit();
        } else {
            $_SESSION['reservation_error'] = 'Database error: ' . $e->getMessage();
            header('Location: search.php');
            exit();
        }
    }
}

// Get user's active reservations (for non-AJAX requests)
try {
    $reservations_stmt = $pdo->prepare("SELECT r.*, b.title, b.author, b.cover_image 
                                       FROM reservations r
                                       JOIN books b ON r.book_id = b.id
                                       WHERE r.user_id = :user_id
                                       ORDER BY r.reservation_date DESC");
    $reservations_stmt->bindParam(':user_id', $user_id);
    $reservations_stmt->execute();
    $reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservations = [];
    $reservation_error = 'Error loading reservations: ' . $e->getMessage();
}

// If this is an AJAX request but not a POST (shouldn't happen), return error
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// If not an AJAX request, continue with normal page rendering
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Reservations</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .reservation-card {
      transition: all 0.3s ease;
    }
    .reservation-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }
    .status-active {
      background-color: #D1FAE5;
      color: #065F46;
    }
    .status-expired {
      background-color: #FEE2E2;
      color: #92400E;
    }
    .status-completed {
      background-color: #E0E7FF;
      color: #3730A3;
    }
  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
    <div class="min-h-screen">
      <!-- Header -->
    <div class="bg-indigo-700 text-white p-6 rounded-b-2xl shadow-lg">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold">My Book Reservations</h1>
        <p class="text-indigo-100 mt-2">View and manage your reserved books</p>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <?php if (isset($_SESSION['reservation_success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
          <?php echo $_SESSION['reservation_success']; unset($_SESSION['reservation_success']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['reservation_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          <?php echo $_SESSION['reservation_error']; unset($_SESSION['reservation_error']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($reservation_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          <?php echo $reservation_error; ?>
        </div>
      <?php endif; ?>
      
      <!-- Reservations List -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($reservations)): ?>
          <div class="p-8 text-center">
            <i data-lucide="bookmark" class="w-12 h-12 text-gray-400 mx-auto"></i>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No reservations found</h3>
            <p class="mt-1 text-sm text-gray-500">You haven't reserved any books yet.</p>
            <div class="mt-6">
              <a href="search.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">
                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                Browse Books
              </a>
            </div>
          </div>
        <?php else: ?>
          <ul class="divide-y divide-gray-200">
            <?php foreach ($reservations as $reservation): 
              $status_class = '';
              switch ($reservation['status']) {
                case 'pending':
                  $status_class = 'status-pending';
                  break;
                case 'active':
                  $status_class = 'status-active';
                  break;
                case 'expired':
                  $status_class = 'status-expired';
                  break;
                case 'completed':
                  $status_class = 'status-completed';
                  break;
              }
            ?>
              <li class="reservation-card">
                <div class="px-4 py-4 sm:px-6">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-16 w-16 mr-4">
                        <img class="h-full w-full object-contain" 
                             src="<?php echo htmlspecialchars($reservation['cover_image'] ?? 'https://via.placeholder.com/100x150?text=No+Cover'); ?>" 
                             alt="<?php echo htmlspecialchars($reservation['title']); ?>">
                      </div>
                      <div>
                        <h4 class="text-lg font-medium text-gray-900">
                          <?php echo htmlspecialchars($reservation['title']); ?>
                        </h4>
                        <p class="text-sm text-gray-500">
                          <?php echo htmlspecialchars($reservation['author']); ?>
                        </p>
                      </div>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                      <span class="px-2 py-1 text-xs rounded-full <?php echo $status_class; ?>">
                        <?php echo ucfirst($reservation['status']); ?>
                      </span>
                    </div>
                  </div>
                  <div class="mt-4 sm:flex sm:justify-between">
                    <div class="sm:flex">
                      <div class="mr-6 flex items-center text-sm text-gray-500">
                        <i data-lucide="calendar" class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400"></i>
                        Reserved on <?php echo date('M j, Y', strtotime($reservation['reservation_date'])); ?>
                      </div>
                      <div class="mt-2 sm:mt-0 flex items-center text-sm text-gray-500">
                        <i data-lucide="clock" class="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400"></i>
                        Expires on <?php echo date('M j, Y', strtotime($reservation['expiry_date'])); ?>
                      </div>
                    </div>
                    <div class="mt-2 sm:mt-0">
                      <?php if ($reservation['status'] === 'pending' || $reservation['status'] === 'active'): ?>
                        <form method="POST" action="cancel_reservation.php" class="inline">
                          <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                          <button type="submit" class="text-sm text-red-600 hover:text-red-900">
                            <i data-lucide="x-circle" class="w-4 h-4 inline mr-1"></i>
                            Cancel Reservation
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
    </main>
  </div>

  <script>
    // Initialize Lucide icons
    lucide.createIcons();
  </script>
</body>
</html>