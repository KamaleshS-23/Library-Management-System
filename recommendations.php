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
$recommendations = [];
$errors = [];

// Sample book data (in a real app, this would come from a database)
$books_data = [
    [
        "Book Name" => "Data Structures and Algorithms",
        "Genre" => "Computer Science",
        "Published Year" => 2018,
        "Edition" => "3rd",
        "Author" => "Mark Allen Weiss",
        "Book Front Page URL" => "https://example.com/dsa_front.jpg"
    ],
    [
        "Book Name" => "Introduction to Algorithms",
        "Genre" => "Computer Science",
        "Published Year" => 2020,
        "Edition" => "4th",
        "Author" => "Thomas H. Cormen",
        "Book Front Page URL" => "https://example.com/intro_algorithms.jpg"
    ],
    [
        "Book Name" => "Clean Code: A Handbook of Agile Software Craftsmanship",
        "Genre" => "Programming",
        "Published Year" => 2008,
        "Edition" => "1st",
        "Author" => "Robert C. Martin",
        "Book Front Page URL" => "https://example.com/clean_code.jpg"
    ],
    [
        "Book Name" => "Design Patterns: Elements of Reusable Object-Oriented Software",
        "Genre" => "Software Engineering",
        "Published Year" => 1994,
        "Edition" => "1st",
        "Author" => "Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides",
        "Book Front Page URL" => "https://example.com/design_patterns.jpg"
    ],
    [
        "Book Name" => "The Pragmatic Programmer",
        "Genre" => "Programming",
        "Published Year" => 2019,
        "Edition" => "20th Anniversary",
        "Author" => "Andrew Hunt, David Thomas",
        "Book Front Page URL" => "https://example.com/pragmatic_programmer.jpg"
    ]
];

// In a real application, you would generate recommendations based on user preferences
// For this example, we'll just use the sample data
$recommendations = $books_data;

// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $book_id = $_POST['book_id'];
    
    try {
        // Check if wishlist table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($table_check->rowCount() == 0) {
            throw new Exception('Wishlist feature is not available yet.');
        }

        // Check if already in wishlist
        $check_stmt = $pdo->prepare("
            SELECT id FROM wishlist 
            WHERE user_id = :user_id AND book_id = :book_id
        ");
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':book_id', $book_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO wishlist (user_id, book_id, added_date)
                VALUES (:user_id, :book_id, NOW())
            ");
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':book_id', $book_id);
            $insert_stmt->execute();
            
            $_SESSION['wishlist_success'] = 'Book added to your wishlist!';
        } else {
            $_SESSION['wishlist_error'] = 'This book is already in your wishlist.';
        }
        
        header("Location: recommendations.php");
        exit();
        
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
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
  <title>Book Recommendations</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .book-card {
            transition: all 0.2s ease;
        }
        .book-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .genre-tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
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
                <h1 class="text-2xl font-bold">Book Recommendations</h1>
                <p class="text-indigo-100">Personalized book suggestions just for you</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-6xl mx-auto px-4 py-6">
            <!-- Display messages -->
            <?php if (isset($_SESSION['wishlist_success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <?php echo $_SESSION['wishlist_success']; unset($_SESSION['wishlist_success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['wishlist_error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php echo $_SESSION['wishlist_error']; unset($_SESSION['wishlist_error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Recommendations -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="sparkles" class="mr-2"></i>
                        Recommended For You
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Based on your reading history and preferences
                    </p>
                </div>

                <?php if (empty($recommendations)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="book-open" class="mx-auto w-12 h-12"></i>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No recommendations available</h3>
                        <p class="mt-1 text-sm text-gray-500">We need more information about your preferences to generate recommendations.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                        <?php foreach ($recommendations as $book): ?>
                            <div class="book-card bg-white rounded-lg shadow overflow-hidden">
                                <div class="h-48 flex items-center justify-center bg-gray-100">
                                    <img src="<?php echo htmlspecialchars($book['Book Front Page URL'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                         alt="<?php echo htmlspecialchars($book['Book Name']); ?>" 
                                         class="h-full w-full object-contain">
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($book['Book Name']); ?></h3>
                                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($book['Author']); ?></p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="flex items-center">
                                            <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                                            <span class="ml-1 text-xs text-gray-600"><?php echo htmlspecialchars($book['Published Year']); ?></span>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                            <?php echo htmlspecialchars($book['Genre']); ?>
                                        </span>
                                    </div>
                                    <div class="mt-4 flex space-x-2">
                                        <form method="POST" action="wishlist.php" class="flex-1">
                                            <input type="hidden" name="book_id" value="<?php echo htmlspecialchars($book['Book Name']); ?>">
                                            <button type="submit" name="add_to_wishlist" 
                                                    class="w-full py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded transition flex items-center justify-center">
                                                <i data-lucide="heart" class="w-4 h-4 mr-1"></i>
                                                Save
                                            </button>
                                        </form>
                                        <a href="#" class="flex-1">
                                            <button class="w-full py-1 bg-white hover:bg-gray-100 text-gray-800 text-sm rounded transition border border-gray-300 flex items-center justify-center">
                                                <i data-lucide="book-open" class="w-4 h-4 mr-1"></i>
                                                Details
                                            </button>
                                        </a>
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