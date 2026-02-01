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
$book_id = $_GET['book_id'] ?? null;
$errors = [];
$success = '';

// Get user details
try {
    $user_stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE id = :user_id");
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error fetching user data: ' . $e->getMessage();
}

// Load books data from book.json if needed
$books_data = [];
if (!$book_id) {
    try {
        $books_json = file_get_contents('book.json');
        $books_data = json_decode($books_json, true);
        
        // Filter out any empty or template entries
        $books_data = array_filter($books_data, function($book) {
            return isset($book['Book Name']) && $book['Book Name'] !== 'Book Name';
        });
    } catch (Exception $e) {
        $errors[] = 'Error loading book data: ' . $e->getMessage();
    }
}

// Get book details if book_id is provided
if ($book_id) {
    try {
        $book_stmt = $pdo->prepare("SELECT * FROM books WHERE id = :book_id");
        $book_stmt->bindParam(':book_id', $book_id);
        $book_stmt->execute();
        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            $errors[] = 'Book not found';
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_review'])) {
        $rating = $_POST['rating'] ?? null;
        $review_text = $_POST['review_text'] ?? '';
        $book_name = $_POST['book_name'] ?? null;
        
        // Validate input
        if ($book_id) {
            // Reviewing a specific book
            if (!$rating || $rating < 1 || $rating > 5) {
                $errors[] = 'Please select a rating between 1 and 5 stars';
            }
            
            if (empty($review_text)) {
                $errors[] = 'Please write your review';
            } elseif (strlen($review_text) > 1000) {
                $errors[] = 'Review must be less than 1000 characters';
            }
        } else {
            // Reviewing from the general reviews page
            if (empty($book_name)) {
                $errors[] = 'Please select a book to review';
            }
            
            if (!$rating || $rating < 1 || $rating > 5) {
                $errors[] = 'Please select a rating between 1 and 5 stars';
            }
            
            if (empty($review_text)) {
                $errors[] = 'Please write your review';
            } elseif (strlen($review_text) > 1000) {
                $errors[] = 'Review must be less than 1000 characters';
            }
        }
        
        if (empty($errors)) {
            try {
                if ($book_id) {
                    // Check if user already reviewed this book
                    $existing_review = $pdo->prepare("SELECT * FROM reviews WHERE user_id = :user_id AND book_id = :book_id");
                    $existing_review->bindParam(':user_id', $user_id);
                    $existing_review->bindParam(':book_id', $book_id);
                    $existing_review->execute();
                    
                    if ($existing_review->rowCount() > 0) {
                        // Update existing review
                        $update_stmt = $pdo->prepare("
                            UPDATE reviews 
                            SET rating = :rating, review_text = :review_text, updated_at = NOW()
                            WHERE user_id = :user_id AND book_id = :book_id
                        ");
                    } else {
                        // Create new review
                        $update_stmt = $pdo->prepare("
                            INSERT INTO reviews (user_id, book_id, rating, review_text)
                            VALUES (:user_id, :book_id, :rating, :review_text)
                        ");
                    }
                    
                    $update_stmt->bindParam(':user_id', $user_id);
                    $update_stmt->bindParam(':book_id', $book_id);
                    $update_stmt->bindParam(':rating', $rating);
                    $update_stmt->bindParam(':review_text', $review_text);
                    $update_stmt->execute();
                    
                    $success = 'Your review has been submitted successfully!';
                } else {
                    // Check if user already reviewed this book
                    $check_stmt = $pdo->prepare("
                        SELECT r.id 
                        FROM reviews r
                        JOIN books b ON r.book_id = b.id
                        WHERE r.user_id = :user_id AND b.title = :book_name
                    ");
                    $check_stmt->bindParam(':user_id', $user_id);
                    $check_stmt->bindParam(':book_name', $book_name);
                    $check_stmt->execute();
                    
                    if ($check_stmt->rowCount() > 0) {
                        $errors[] = 'You have already reviewed this book.';
                    } else {
                        // First, ensure the book exists in the database
                        $book_stmt = $pdo->prepare("
                            SELECT id FROM books WHERE title = :book_name
                        ");
                        $book_stmt->bindParam(':book_name', $book_name);
                        $book_stmt->execute();
                        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$book) {
                            // If book doesn't exist in DB, insert it
                            $selected_book = null;
                            foreach ($books_data as $b) {
                                if ($b['Book Name'] === $book_name) {
                                    $selected_book = $b;
                                    break;
                                }
                            }
                            
                            if ($selected_book) {
                                $insert_book_stmt = $pdo->prepare("
                                    INSERT INTO books (title, author, genre, published_year, edition, cover_image)
                                    VALUES (:title, :author, :genre, :year, :edition, :cover_image)
                                ");
                                $insert_book_stmt->bindParam(':title', $selected_book['Book Name']);
                                $insert_book_stmt->bindParam(':author', $selected_book['Author']);
                                $insert_book_stmt->bindParam(':genre', $selected_book['Genre']);
                                $insert_book_stmt->bindParam(':year', $selected_book['Published Year']);
                                $insert_book_stmt->bindParam(':edition', $selected_book['Edition']);
                                $insert_book_stmt->bindParam(':cover_image', $selected_book['Book Front Page URL']);
                                $insert_book_stmt->execute();
                                
                                $book_id = $pdo->lastInsertId();
                            } else {
                                throw new Exception('Book not found in dataset.');
                            }
                        } else {
                            $book_id = $book['id'];
                        }
                        
                        // Insert new review
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO reviews (user_id, book_id, rating, review_text, created_at)
                            VALUES (:user_id, :book_id, :rating, :review_text, NOW())
                        ");
                        $insert_stmt->bindParam(':user_id', $user_id);
                        $insert_stmt->bindParam(':book_id', $book_id);
                        $insert_stmt->bindParam(':rating', $rating);
                        $insert_stmt->bindParam(':review_text', $review_text);
                        $insert_stmt->execute();
                        
                        $success = 'Your review has been submitted successfully!';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_review'])) {
        $review_id = $_POST['review_id'];
        
        try {
            $delete_stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :review_id AND user_id = :user_id");
            $delete_stmt->bindParam(':review_id', $review_id);
            $delete_stmt->bindParam(':user_id', $user_id);
            $delete_stmt->execute();
            
            $success = 'Review deleted successfully.';
        } catch (PDOException $e) {
            $errors[] = 'Error deleting review: ' . $e->getMessage();
        }
    }
}

// Get existing review if any (for book-specific review)
$existing_review = null;
if ($book_id) {
    try {
        $review_stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = :user_id AND book_id = :book_id");
        $review_stmt->bindParam(':user_id', $user_id);
        $review_stmt->bindParam(':book_id', $book_id);
        $review_stmt->execute();
        $existing_review = $review_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail - we'll just show an empty form
    }
}

// Get user's reviews
try {
    $reviews_stmt = $pdo->prepare("
        SELECT r.*, b.title, b.cover_image 
        FROM reviews r
        JOIN books b ON r.book_id = b.id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");
    $reviews_stmt->bindParam(':user_id', $user_id);
    $reviews_stmt->execute();
    $user_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error fetching reviews: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
  <title><?php echo $book_id ? 'Review Book' : 'My Reviews & Ratings'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .review-card {
            transition: all 0.2s ease;
        }
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .star-rating {
            display: inline-flex;
            direction: rtl;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            cursor: pointer;
            font-size: 1.5rem;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f8d64e;
        }
        .book-search-container {
            position: relative;
        }
        .book-search-results {
            position: absolute;
            z-index: 10;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: none;
        }
        .book-search-result {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .book-search-result:hover {
            background-color: #f7fafc;
        }
        .selected-book {
            display: inline-flex;
            align-items: center;
            background-color: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
        }
        .selected-book button {
            margin-left: 0.5rem;
            color: #6b7280;
        }
        .selected-book button:hover {
            color: #ef4444;
        }
    </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
    <?php if ($book_id): ?>
        <!-- Book-specific review page -->
        <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
                <div class="p-8">
                    <div class="flex items-center">
                        <img class="h-32 w-24 object-cover mr-6" 
                             src="<?php echo htmlspecialchars($book['cover_image'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($book['title']); ?></h1>
                            <p class="text-gray-600"><?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="mt-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="mt-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-6">
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="rating">
                                Your Rating
                            </label>
                            <div class="flex items-center">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                                           class="hidden" <?php echo ($existing_review['rating'] ?? 0) == $i ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $i; ?>" class="text-3xl cursor-pointer">
                                        <i data-lucide="<?php echo ($existing_review['rating'] ?? 0) >= $i ? 'star' : 'star'; ?>" 
                                           class="<?php echo ($existing_review['rating'] ?? 0) >= $i ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'; ?> w-8 h-8"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="review_text">
                                Your Review
                            </label>
                            <textarea id="review_text" name="review_text" rows="5"
                                      class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php 
                                          echo htmlspecialchars($existing_review['review_text'] ?? ''); 
                                      ?></textarea>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <button type="submit" name="submit_review"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Submit Review
                            </button>
                            <a href="search.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Back to Search
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            // Initialize Lucide icons
            lucide.createIcons();
            
            // Handle star rating selection
            document.querySelectorAll('input[name="rating"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const rating = parseInt(this.value);
                    // Update star display
                    for (let i = 1; i <= 5; i++) {
                        const starIcon = document.querySelector(`label[for="star${i}"] i`);
                        if (i <= rating) {
                            starIcon.setAttribute('name', 'star');
                            starIcon.classList.add('text-yellow-400', 'fill-yellow-400');
                            starIcon.classList.remove('text-gray-300');
                        } else {
                            starIcon.setAttribute('name', 'star');
                            starIcon.classList.add('text-gray-300');
                            starIcon.classList.remove('text-yellow-400', 'fill-yellow-400');
                        }
                    }
                });
            });
        </script>
    <?php else: ?>
        <!-- General reviews page -->
        <div class="min-h-screen">
            <!-- Header -->
            <div class="bg-indigo-600 text-white p-6">
                <div class="max-w-6xl mx-auto flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">My Reviews & Ratings</h1>
                        <p class="text-indigo-100">Share your thoughts on books you've read</p>
                    </div>
                    <div class="flex items-center">
                        <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'https://via.placeholder.com/40'); ?>" 
                             alt="Profile" class="w-10 h-10 rounded-full mr-2">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-6xl mx-auto px-4 py-6">
                <!-- Display messages -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                        <p><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Review Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4 flex items-center">
                        <i data-lucide="edit" class="mr-2"></i>
                        Write a New Review
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <div class="book-search-container">
                            <label for="book_search" class="block text-sm font-medium text-gray-700">Search Book</label>
                            <input type="text" id="book_search" placeholder="Start typing a book name..."
                                   class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="hidden" id="book_name" name="book_name" required>
                            <div id="book_search_results" class="book-search-results"></div>
                            <div id="selected_book_display" class="selected-book hidden">
                                <span id="selected_book_name"></span>
                                <button type="button" id="clear_selection" class="flex items-center">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rating</label>
                            <div class="star-rating mt-1">
                                <input type="radio" id="star5" name="rating" value="5" required />
                                <label for="star5" title="5 stars">★</label>
                                <input type="radio" id="star4" name="rating" value="4" />
                                <label for="star4" title="4 stars">★</label>
                                <input type="radio" id="star3" name="rating" value="3" />
                                <label for="star3" title="3 stars">★</label>
                                <input type="radio" id="star2" name="rating" value="2" />
                                <label for="star2" title="2 stars">★</label>
                                <input type="radio" id="star1" name="rating" value="1" />
                                <label for="star1" title="1 star">★</label>
                            </div>
                        </div>
                        
                        <div>
                            <label for="review_text" class="block text-sm font-medium text-gray-700">Your Review</label>
                            <textarea id="review_text" name="review_text" rows="4" required
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="submit_review"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md flex items-center">
                                <i data-lucide="send" class="w-4 h-4 mr-2"></i>
                                Submit Review
                            </button>
                        </div>
                    </form>
                </div>

                <!-- My Reviews -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-xl font-semibold flex items-center">
                            <i data-lucide="list" class="mr-2"></i>
                            My Reviews (<?php echo count($user_reviews); ?>)
                        </h2>
                    </div>

                    <?php if (empty($user_reviews)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <i data-lucide="book-open" class="mx-auto w-12 h-12"></i>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">No reviews yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Write your first review using the form above</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($user_reviews as $review): ?>
                                <div class="review-card p-6">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                                            <img class="h-32 w-24 object-contain rounded border border-gray-200" 
                                                 src="<?php echo htmlspecialchars($review['cover_image'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                                 alt="<?php echo htmlspecialchars($review['title']); ?>">
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($review['title']); ?>
                                                    </h3>
                                                    <div class="flex items-center mt-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i data-lucide="star" 
                                                               class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                        <?php endfor; ?>
                                                        <span class="ml-2 text-sm text-gray-500">
                                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <button type="submit" name="delete_review" 
                                                            class="text-red-500 hover:text-red-700">
                                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <p class="mt-3 text-gray-700">
                                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                            </p>
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
            
            // Star rating interaction
            document.querySelectorAll('.star-rating label').forEach(star => {
                star.addEventListener('click', (e) => {
                    const ratingInput = e.target.previousElementSibling;
                    ratingInput.checked = true;
                });
            });

            // Book search functionality
            const bookSearch = document.getElementById('book_search');
            const bookNameInput = document.getElementById('book_name');
            const searchResults = document.getElementById('book_search_results');
            const selectedBookDisplay = document.getElementById('selected_book_display');
            const selectedBookName = document.getElementById('selected_book_name');
            const clearSelectionBtn = document.getElementById('clear_selection');
            
            // Get all book names from PHP
            const booksData = <?php echo json_encode(array_column($books_data, 'Book Name')); ?>;

            bookSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                searchResults.innerHTML = '';
                
                if (searchTerm.length < 1) {
                    searchResults.style.display = 'none';
                    return;
                }
                
                const matchedBooks = booksData.filter(book => 
                    book.toLowerCase().includes(searchTerm)
                );
                
                if (matchedBooks.length > 0) {
                    matchedBooks.forEach(book => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'book-search-result';
                        resultItem.textContent = book;
                        resultItem.addEventListener('click', function() {
                            bookSearch.value = '';
                            bookNameInput.value = book;
                            selectedBookName.textContent = book;
                            selectedBookDisplay.classList.remove('hidden');
                            searchResults.style.display = 'none';
                        });
                        searchResults.appendChild(resultItem);
                    });
                    searchResults.style.display = 'block';
                } else {
                    const noResults = document.createElement('div');
                    noResults.className = 'book-search-result text-gray-500';
                    noResults.textContent = 'No books found';
                    searchResults.appendChild(noResults);
                    searchResults.style.display = 'block';
                }
            });

            // Clear selection
            clearSelectionBtn.addEventListener('click', function() {
                bookNameInput.value = '';
                selectedBookDisplay.classList.add('hidden');
                bookSearch.focus();
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!bookSearch.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!bookNameInput.value) {
                    e.preventDefault();
                    alert('Please select a book from the search results');
                    bookSearch.focus();
                }
            });
        </script>
    <?php endif; ?>
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