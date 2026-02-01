<?php
require_once('config.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$wishlist_items = [];
$errors = [];

try {
    // Get user's wishlist items
    $stmt = $pdo->prepare("
        SELECT w.*, b.* 
        FROM wishlist w
        JOIN books b ON w.book_id = b.id
        WHERE w.user_id = :user_id
        ORDER BY w.added_date DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $book_id = $_POST['book_id'];
    
    try {
        $delete_stmt = $pdo->prepare("
            DELETE FROM wishlist 
            WHERE user_id = :user_id AND book_id = :book_id
        ");
        $delete_stmt->bindParam(':user_id', $user_id);
        $delete_stmt->bindParam(':book_id', $book_id);
        $delete_stmt->execute();
        
        // Refresh the page
        header("Location: wishlist.php");
        exit();
        
    } catch (PDOException $e) {
        $errors[] = 'Failed to remove item: ' . $e->getMessage();
    }
}

// Handle move to borrow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_item'])) {
    $book_id = $_POST['book_id'];
    
    try {
        // Check if book is available
        $book_check = $pdo->prepare("SELECT * FROM books WHERE id = :book_id AND available_copies > 0");
        $book_check->bindParam(':book_id', $book_id);
        $book_check->execute();
        $book = $book_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception('Book is not available for borrowing');
        }

        // Check if user already has this book borrowed
        $borrowing_check = $pdo->prepare("
            SELECT * FROM borrowings 
            WHERE user_id = :user_id AND book_id = :book_id AND status = 'borrowed'
        ");
        $borrowing_check->bindParam(':user_id', $user_id);
        $borrowing_check->bindParam(':book_id', $book_id);
        $borrowing_check->execute();
        
        if ($borrowing_check->rowCount() > 0) {
            throw new Exception('You already have this book checked out');
        }

        // Create borrowing record
        $borrow_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+14 days')); // 2 weeks loan period
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status)
            VALUES (:user_id, :book_id, :borrow_date, :due_date, 'borrowed')
        ");
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->bindParam(':book_id', $book_id);
        $insert_stmt->bindParam(':borrow_date', $borrow_date);
        $insert_stmt->bindParam(':due_date', $due_date);
        $insert_stmt->execute();
        
        // Update available copies
        $update_stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = :book_id");
        $update_stmt->bindParam(':book_id', $book_id);
        $update_stmt->execute();
        
        // Remove from wishlist
        $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND book_id = :book_id");
        $delete_stmt->bindParam(':user_id', $user_id);
        $delete_stmt->bindParam(':book_id', $book_id);
        $delete_stmt->execute();
        
        $_SESSION['borrowing_success'] = "Book '{$book['title']}' has been checked out successfully! Due date: " . date('M j, Y', strtotime($due_date));
        header("Location: borrowed.php");
        exit();
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
  <title>My Wishlist</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .wishlist-item {
            transition: all 0.2s ease;
        }
        .wishlist-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
        <div class="bg-indigo-600 text-white p-6">
            <div class="max-w-6xl mx-auto">
                <h1 class="text-2xl font-bold">My Wishlist</h1>
                <p class="text-indigo-100">Books you want to read</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-6xl mx-auto px-4 py-6">
            <!-- Display errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Wishlist Items -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="heart" class="mr-2"></i>
                        Saved Books (<?php echo count($wishlist_items); ?>)
                    </h2>
                </div>

                <?php if (empty($wishlist_items)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="heart-off" class="mx-auto w-12 h-12"></i>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Your wishlist is empty</h3>
                        <p class="mt-1 text-sm text-gray-500">Add books to your wishlist to save them for later</p>
                        <div class="mt-6">
                            <a href="search.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                Browse Books
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($wishlist_items as $item): ?>
                            <div class="wishlist-item p-6">
                                <div class="flex flex-col md:flex-row">
                                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                        <img class="h-40 w-28 object-contain rounded border border-gray-200" 
                                             src="<?php echo htmlspecialchars($item['cover_image'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['title']); ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">
                                                    <?php echo htmlspecialchars($item['author']); ?>
                                                </p>
                                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                                    <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                                                    <?php echo htmlspecialchars($item['published_year']); ?> • 
                                                    <?php echo htmlspecialchars($item['edition']); ?> Edition
                                                </div>
                                                <div class="mt-1">
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                                        <?php echo htmlspecialchars($item['genre']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                                <button type="submit" name="remove_item" 
                                                        class="text-red-500 hover:text-red-700">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <div class="mt-4 flex space-x-3">
                                            <form method="POST">
                                                <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                                <button type="submit" name="borrow_item" 
                                                        class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    <i data-lucide="book-open" class="w-4 h-4 mr-1"></i>
                                                    Borrow Now
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
    </main>
  </div>
  <script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  </script>
</body>
</html>