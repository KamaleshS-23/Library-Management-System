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
$borrowed_books = [];
$borrowing_history = [];
$errors = [];

try {
    // Check if borrowings table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'borrowings'");
    if ($table_check->rowCount() == 0) {
        throw new Exception('Borrowings table does not exist. Please contact the administrator.');
    }

    // Get currently borrowed books
    $borrowed_stmt = $pdo->prepare("
        SELECT b.*, br.borrow_date, br.due_date, br.status 
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        WHERE br.user_id = :user_id AND br.status = 'borrowed'
        ORDER BY br.due_date ASC
    ");
    $borrowed_stmt->bindParam(':user_id', $user_id);
    $borrowed_stmt->execute();
    $borrowed_books = $borrowed_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get borrowing history (last 50 returned books)
    $history_stmt = $pdo->prepare("
        SELECT b.*, br.borrow_date, br.due_date, br.return_date 
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        WHERE br.user_id = :user_id AND br.status = 'returned'
        ORDER BY br.return_date DESC
        LIMIT 50
    ");
    $history_stmt->bindParam(':user_id', $user_id);
    $history_stmt->execute();
    $borrowing_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Books & History</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }
        .status-borrowed { background-color: #EFF6FF; color: #1E40AF; }
        .status-overdue { background-color: #FEE2E2; color: #B91C1C; }
        .status-returned { background-color: #D1FAE5; color: #065F46; }
        .book-card:hover {
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
                <h1 class="text-2xl font-bold">My Borrowed Books</h1>
                <p class="text-indigo-100">View your current loans and borrowing history</p>
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

            <!-- Currently Borrowed Books -->
            <div class="bg-white rounded-lg shadow-md mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="book-open" class="mr-2"></i>
                        Currently Borrowed (<?php echo count($borrowed_books); ?>)
                    </h2>
                </div>

                <?php if (empty($borrowed_books)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="book-open" class="mx-auto w-12 h-12"></i>
                        <p class="mt-2">You don't have any books checked out currently.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($borrowed_books as $book): 
                            $due_date = new DateTime($book['due_date']);
                            $today = new DateTime();
                            $is_overdue = $today > $due_date;
                        ?>
                            <div class="book-card p-6 transition">
                                <div class="flex flex-col md:flex-row">
                                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                        <img src="<?php echo htmlspecialchars($book['cover_image'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                             class="h-40 w-28 object-cover rounded border">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-medium"><?php echo htmlspecialchars($book['title']); ?></h3>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($book['author']); ?></p>
                                            </div>
                                            <span class="status-badge <?php echo $is_overdue ? 'status-overdue' : 'status-borrowed'; ?>">
                                                <?php echo $is_overdue ? 'Overdue' : 'Borrowed'; ?>
                                            </span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-500">Borrowed Date</p>
                                                <p><?php echo date('M j, Y', strtotime($book['borrow_date'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Due Date</p>
                                                <p class="<?php echo $is_overdue ? 'text-red-600 font-medium' : ''; ?>">
                                                    <?php echo date('M j, Y', strtotime($book['due_date'])); ?>
                                                    <?php if ($is_overdue): ?>
                                                        <span class="text-xs">(<?php echo $today->diff($due_date)->format('%a days late'); ?>)</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex space-x-3">
                                            <button class="inline-flex items-center px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-50">
                                                <i data-lucide="rotate-ccw" class="w-4 h-4 mr-1"></i> Renew
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Borrowing History -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="clock" class="mr-2"></i>
                        Borrowing History
                    </h2>
                </div>

                <?php if (empty($borrowing_history)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="book" class="mx-auto w-12 h-12"></i>
                        <p class="mt-2">Your borrowing history will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($borrowing_history as $book): 
                            $borrow_date = new DateTime($book['borrow_date']);
                            $return_date = new DateTime($book['return_date']);
                            $loan_duration = $borrow_date->diff($return_date)->format('%a days');
                        ?>
                            <div class="book-card p-6 transition">
                                <div class="flex flex-col md:flex-row">
                                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                        <img src="<?php echo htmlspecialchars($book['cover_image'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                             class="h-40 w-28 object-cover rounded border">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-lg font-medium"><?php echo htmlspecialchars($book['title']); ?></h3>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($book['author']); ?></p>
                                            </div>
                                            <span class="status-badge status-returned">Returned</span>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-500">Borrowed Date</p>
                                                <p><?php echo date('M j, Y', strtotime($book['borrow_date'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Returned Date</p>
                                                <p><?php echo date('M j, Y', strtotime($book['return_date'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Loan Duration</p>
                                                <p><?php echo $loan_duration; ?></p>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <button class="inline-flex items-center px-3 py-1 border border-gray-300 rounded text-sm bg-white hover:bg-gray-50">
                                                <i data-lucide="star" class="w-4 h-4 mr-1"></i> Rate Book
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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