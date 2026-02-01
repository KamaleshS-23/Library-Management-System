<?php
// At the VERY TOP of manage_books.php (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'check_auth.php';
verifyOwnerSession();

// Verify owner is logged in
if (!isset($_SESSION['owner_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: owner_login.html');
    exit();
}

// Set default session values if not set
$_SESSION['owner_name'] = $_SESSION['owner_name'] ?? 'Admin';
$_SESSION['role'] = $_SESSION['role'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book'])) {
        // Add new book
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);
        $description = trim($_POST['description']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $isbn, $category, $quantity, $quantity, $description]);
            
            $_SESSION['success'] = "Book added successfully!";
            header("Location: manage_books.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add book: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_book'])) {
        // Update existing book
        $book_id = intval($_POST['book_id']);
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $isbn = trim($_POST['isbn']);
        $category = trim($_POST['category']);
        $quantity = intval($_POST['quantity']);
        $description = trim($_POST['description']);
        
        try {
            // Get current available quantity
            $stmt = $pdo->prepare("SELECT quantity, available_quantity FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate new available quantity
            $diff = $quantity - $current['quantity'];
            $new_available = $current['available_quantity'] + $diff;
            
            $stmt = $pdo->prepare("UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, 
                                  quantity = ?, available_quantity = ?, description = ? 
                                  WHERE id = ?");
            $stmt->execute([$title, $author, $isbn, $category, $quantity, $new_available, $description, $book_id]);
            
            $_SESSION['success'] = "Book updated successfully!";
            header("Location: manage_books.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update book: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_book'])) {
        // Delete book
        $book_id = intval($_POST['book_id']);
        
        try {
            // Check if book is currently borrowed
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = ? AND return_date IS NULL");
            $stmt->execute([$book_id]);
            $borrowed_count = $stmt->fetchColumn();
            
            if ($borrowed_count > 0) {
                $_SESSION['error'] = "Cannot delete book - it is currently borrowed by users.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$book_id]);
                
                $_SESSION['success'] = "Book deleted successfully!";
            }
            
            header("Location: manage_books.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete book: " . $e->getMessage();
        }
    }
}

// Get all books
$books = [];
try {
    $stmt = $pdo->query("SELECT * FROM books ORDER BY title");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load books: " . $e->getMessage();
}

// Get all categories for dropdown
// Get all categories for dropdown
$categories = [];
try {
    // First check if the column exists
    $column_check = $pdo->query("SHOW COLUMNS FROM books LIKE 'category'");
    if ($column_check->rowCount() > 0) {
        $stmt = $pdo->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
} catch (PDOException $e) {
    error_log("Category load error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load categories";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books | Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --gray-color: #95a5a6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        /* Books Table */
        .books-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .books-table th, .books-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .books-table th {
            background-color: var(--dark-color);
            color: white;
            font-weight: 500;
        }
        
        .books-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-add {
            background-color: var(--success-color);
            color: white;
            margin-bottom: 20px;
        }
        
        .btn-add:hover {
            background-color: #27ae60;
        }
        
        /* Book Form Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 600px;
            max-width: 90%;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-color);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Search and Filter */
        .book-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .search-box {
            flex-grow: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-controls {
            display: flex;
            gap: 10px;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .book-controls {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .books-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .action-btns {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Library Admin</h2>
                <p>Owner Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <a href="owner_dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_books.php" class="nav-item active"><i class="fas fa-book"></i> Manage Books</a>
                <a href="manage_users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
                <a href="system_settings.php" class="nav-item"><i class="fas fa-cog"></i> System Settings</a>
                <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Books</h1>
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['owner_name']) ?>&background=random" alt="User Avatar">
                    <span><?= htmlspecialchars($_SESSION['owner_name']) ?></span>
                    <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="book-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search books...">
                </div>
                <div class="filter-controls">
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="availabilityFilter">
                        <option value="">All Availability</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
            
            <button class="btn btn-add" onclick="openAddBookModal()">
                <i class="fas fa-plus"></i> Add New Book
            </button>
            
            <table class="books-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No books found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['title'] ?? '') ?></td>
                            <td><?= htmlspecialchars($book['author'] ?? '') ?></td>
                            <td><?= htmlspecialchars($book['isbn'] ?? '') ?></td>
                            <td><?= htmlspecialchars($book['category'] ?? '') ?></td>
                            <td><?= $book['quantity'] ?? 0 ?></td>
                            <td><?= $book['available_quantity'] ?? 0 ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-edit" onclick="openEditBookModal(<?= $book['id'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-delete" onclick="confirmDelete(<?= $book['id'] ?>, '<?= htmlspecialchars(addslashes($book['title'])) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Book Modal -->
    <div class="modal" id="bookModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Book</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="bookForm" method="POST">
                <input type="hidden" id="book_id" name="book_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="author">Author *</label>
                        <input type="text" id="author" name="author" required>
                    </div>
                    <div class="form-group">
                        <label for="isbn">ISBN *</label>
                        <input type="text" id="isbn" name="isbn" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-edit" name="add_book" id="submitBtn">Add Book</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Open modal for adding new book
        function openAddBookModal() {
            document.getElementById('modalTitle').textContent = 'Add New Book';
            document.getElementById('bookForm').reset();
            document.getElementById('book_id').value = '';
            document.getElementById('submitBtn').name = 'add_book';
            document.getElementById('submitBtn').textContent = 'Add Book';
            document.getElementById('bookModal').style.display = 'flex';
        }
        
        // Open modal for editing book
        function openEditBookModal(bookId) {
            fetch('get_book_details.php?id=' + bookId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Edit Book: ' + data.book.title;
                        document.getElementById('book_id').value = data.book.id;
                        document.getElementById('title').value = data.book.title;
                        document.getElementById('author').value = data.book.author;
                        document.getElementById('isbn').value = data.book.isbn;
                        document.getElementById('category').value = data.book.category;
                        document.getElementById('quantity').value = data.book.quantity;
                        document.getElementById('description').value = data.book.description;
                        
                        document.getElementById('submitBtn').name = 'update_book';
                        document.getElementById('submitBtn').textContent = 'Update Book';
                        document.getElementById('bookModal').style.display = 'flex';
                    } else {
                        alert(data.message || 'Failed to load book details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading book details');
                });
        }
        
        // Confirm before deleting book
        function confirmDelete(bookId, bookTitle) {
            if (confirm(`Are you sure you want to delete "${bookTitle}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_books.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'book_id';
                inputId.value = bookId;
                form.appendChild(inputId);
                
                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_book';
                inputDelete.value = '1';
                form.appendChild(inputDelete);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('bookModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.books-table tbody tr');
            
            rows.forEach(row => {
                const title = row.cells[0].textContent.toLowerCase();
                const author = row.cells[1].textContent.toLowerCase();
                const isbn = row.cells[2].textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm) || isbn.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            filterBooks();
        });
        
        // Availability filter
        document.getElementById('availabilityFilter').addEventListener('change', function() {
            filterBooks();
        });
        
        // Combined filter function
        function filterBooks() {
            const category = document.getElementById('categoryFilter').value.toLowerCase();
            const availability = document.getElementById('availabilityFilter').value;
            const rows = document.querySelectorAll('.books-table tbody tr');
            
            rows.forEach(row => {
                const rowCategory = row.cells[3].textContent.toLowerCase();
                const available = parseInt(row.cells[5].textContent);
                
                let showRow = true;
                
                // Apply category filter
                if (category && rowCategory !== category) {
                    showRow = false;
                }
                
                // Apply availability filter
                if (availability === 'available' && available <= 0) {
                    showRow = false;
                } else if (availability === 'unavailable' && available > 0) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
</body>
</html>