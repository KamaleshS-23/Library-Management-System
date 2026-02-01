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
$errors = [];
$ebooks = [];
$categories = [];

// Get user details
try {
    $user_stmt = $pdo->prepare("SELECT username, membership_level FROM users WHERE id = :user_id");
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Error fetching user data: ' . $e->getMessage();
}

// Get available ebooks
try {
    $ebooks_stmt = $pdo->prepare("
        SELECT e.*, b.title, b.author, b.genre 
        FROM ebooks e
        JOIN books b ON e.book_id = b.id
        WHERE (e.access_level = 'all' OR e.access_level = :membership_level)
        ORDER BY b.title
    ");
    $ebooks_stmt->bindParam(':membership_level', $user['membership_level']);
    $ebooks_stmt->execute();
    $ebooks = $ebooks_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique categories
    $categories_stmt = $pdo->query("SELECT DISTINCT genre FROM books ORDER BY genre");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    $errors[] = 'Error fetching ebooks: ' . $e->getMessage();
}

// Handle ebook download
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_ebook'])) {
    $ebook_id = $_POST['ebook_id'];
    
    try {
        // Verify user can access this ebook
        $check_stmt = $pdo->prepare("
            SELECT e.* 
            FROM ebooks e
            WHERE e.id = :ebook_id 
            AND (e.access_level = 'all' OR e.access_level = :membership_level)
        ");
        $check_stmt->bindParam(':ebook_id', $ebook_id);
        $check_stmt->bindParam(':membership_level', $user['membership_level']);
        $check_stmt->execute();
        $ebook = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ebook) {
            // Record download history
            $history_stmt = $pdo->prepare("
                INSERT INTO download_history (user_id, ebook_id, download_date)
                VALUES (:user_id, :ebook_id, NOW())
            ");
            $history_stmt->bindParam(':user_id', $user_id);
            $history_stmt->bindParam(':ebook_id', $ebook_id);
            $history_stmt->execute();
            
            // Redirect to download (in a real app, this would serve the file)
            header("Location: " . $ebook['file_url']);
            exit();
        } else {
            $errors[] = 'You do not have permission to download this ebook.';
        }
    } catch (PDOException $e) {
        $errors[] = 'Error processing download: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
  <title>eBooks Library</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .ebook-card {
            transition: all 0.2s ease;

        }
        .ebook-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .category-chip {
            transition: all 0.2s ease;
        }
        .category-chip:hover, .category-chip.active {
            background-color: #6366F1;
            color: white;
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
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">eBooks Library</h1>
                    <p class="text-indigo-100">Access your digital books anytime, anywhere</p>
                </div>
                <div class="flex items-center">
                    <span class="bg-indigo-800 text-white px-3 py-1 rounded-full text-sm mr-4">
                        <?php echo htmlspecialchars(ucfirst($user['membership_level'])); ?> Member
                    </span>
                    <img src="https://via.placeholder.com/40" alt="Profile" class="w-10 h-10 rounded-full">
                </div>
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

            <!-- Categories Filter -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3 flex items-center">
                    <i data-lucide="filter" class="mr-2"></i>
                    Filter by Category
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button class="category-chip active px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm">
                        All Categories
                    </button>
                    <?php foreach ($categories as $category): ?>
                        <button class="category-chip px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm">
                            <?php echo htmlspecialchars($category); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Search and Sort -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <div class="relative w-full md:w-64 mb-4 md:mb-0">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i data-lucide="search" class="text-gray-400"></i>
                    </div>
                    <input type="text" placeholder="Search ebooks..." 
                           class="pl-10 w-full border border-gray-300 rounded-md py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-600 mr-2">Sort by:</span>
                    <select class="border border-gray-300 rounded-md py-1 px-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option>Title (A-Z)</option>
                        <option>Title (Z-A)</option>
                        <option>Newest First</option>
                        <option>Most Popular</option>
                    </select>
                </div>
            </div>

            <!-- eBooks Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($ebooks as $ebook): ?>
                    <div class="ebook-card bg-white rounded-lg shadow overflow-hidden">
                        <div class="h-48 flex items-center justify-center bg-gray-100 p-4">
                            <img src="<?php echo htmlspecialchars($ebook['cover_image_url'] ?? 'https://via.placeholder.com/150x200?text=No+Cover'); ?>" 
                                 alt="<?php echo htmlspecialchars($ebook['title']); ?>" 
                                 class="h-full object-contain">
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($ebook['title']); ?></h3>
                            <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($ebook['author']); ?></p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                    <?php echo htmlspecialchars($ebook['genre']); ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?php echo strtoupper($ebook['file_format']); ?>
                                </span>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <div class="flex items-center">
                                    <i data-lucide="download" class="text-gray-500 mr-1"></i>
                                    <span class="text-xs text-gray-500">
                                        <?php echo $ebook['download_count'] ?? 0; ?> downloads
                                    </span>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="ebook_id" value="<?php echo $ebook['id']; ?>">
                                    <button type="submit" name="download_ebook"
                                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm flex items-center">
                                        <i data-lucide="download" class="w-4 h-4 mr-1"></i>
                                        Download
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($ebooks)): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i data-lucide="book-open" class="mx-auto w-12 h-12 text-gray-400"></i>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No eBooks Available</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        There are currently no eBooks matching your membership level.
                        <a href="profile.php" class="text-indigo-600 hover:text-indigo-800">Upgrade your membership</a> 
                        to access more content.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Category filter functionality
        document.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelector('.category-chip.active').classList.remove('active');
                this.classList.add('active');
                
                // In a real app, you would filter the eBooks here
                console.log('Filter by:', this.textContent.trim());
            });
        });
        
        // Search functionality
        const searchInput = document.querySelector('input[type="text"]');
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                // In a real app, you would search the eBooks here
                console.log('Search for:', this.value);
            }
        });
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