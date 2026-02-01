<?php
require_once('config.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (for reservation functionality)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle book reservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_book'])) {
    if (!$user_id) {
        header('Location: index.html');
        exit();
    }

    $book_id = $_POST['book_id'];
    
    try {
        // Check if book exists and is available
        $book_stmt = $pdo->prepare("SELECT * FROM books WHERE id = :book_id AND available_copies > 0");
        $book_stmt->bindParam(':book_id', $book_id);
        $book_stmt->execute();
        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception('Book not available for reservation');
        }

        // Check if already reserved
        $reservation_check = $pdo->prepare("
            SELECT * FROM reservations 
            WHERE user_id = :user_id AND book_id = :book_id AND status IN ('pending', 'active')
        ");
        $reservation_check->bindParam(':user_id', $user_id);
        $reservation_check->bindParam(':book_id', $book_id);
        $reservation_check->execute();
        
        if ($reservation_check->rowCount() > 0) {
            throw new Exception('You already have a pending reservation for this book');
        }

        // Create reservation
        $reservation_date = date('Y-m-d H:i:s');
        $expiry_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO reservations (user_id, book_id, reservation_date, expiry_date, status)
            VALUES (:user_id, :book_id, :reservation_date, :expiry_date, 'pending')
        ");
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->bindParam(':book_id', $book_id);
        $insert_stmt->bindParam(':reservation_date', $reservation_date);
        $insert_stmt->bindParam(':expiry_date', $expiry_date);
        $insert_stmt->execute();
        
        // Update available copies
        $update_stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = :book_id");
        $update_stmt->bindParam(':book_id', $book_id);
        $update_stmt->execute();
        
        // Set success message in session
        $_SESSION['reservation_success'] = "Book '{$book['title']}' has been reserved successfully!";
        
        // Redirect back to prevent form resubmission
        header("Location: search.php");
        exit();
        
    } catch (Exception $e) {
        $reservation_error = $e->getMessage();
    }
}

// Handle wishlist addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    if (!$user_id) {
        header('Location: index.html');
        exit();
    }

    $book_id = $_POST['book_id'];
    
    try {
        // Check if book exists
        $book_stmt = $pdo->prepare("SELECT * FROM books WHERE id = :book_id");
        $book_stmt->bindParam(':book_id', $book_id);
        $book_stmt->execute();
        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception('Book not found');
        }

        // Check if already in wishlist
        $wishlist_check = $pdo->prepare("
            SELECT * FROM wishlist 
            WHERE user_id = :user_id AND book_id = :book_id
        ");
        $wishlist_check->bindParam(':user_id', $user_id);
        $wishlist_check->bindParam(':book_id', $book_id);
        $wishlist_check->execute();
        
        if ($wishlist_check->rowCount() > 0) {
            throw new Exception('This book is already in your wishlist');
        }

        // Add to wishlist
        $added_date = date('Y-m-d H:i:s');
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO wishlist (user_id, book_id, added_date)
            VALUES (:user_id, :book_id, :added_date)
        ");
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->bindParam(':book_id', $book_id);
        $insert_stmt->bindParam(':added_date', $added_date);
        $insert_stmt->execute();
        
        // Set success message in session
        $_SESSION['wishlist_success'] = "Book '{$book['title']}' has been added to your wishlist!";
        
        // Redirect back to prevent form resubmission
        header("Location: search.php");
        exit();
        
    } catch (Exception $e) {
        $wishlist_error = $e->getMessage();
    }
}

// Handle book borrowing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    if (!$user_id) {
        header('Location: index.html');
        exit();
    }

    $book_id = $_POST['book_id'];
    
    try {
        // Check if book exists and is available
        $book_stmt = $pdo->prepare("SELECT * FROM books WHERE id = :book_id AND available_copies > 0");
        $book_stmt->bindParam(':book_id', $book_id);
        $book_stmt->execute();
        $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception('Book not available for borrowing');
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
        
        // Set success message in session
        $_SESSION['borrowing_success'] = "Book '{$book['title']}' has been checked out successfully! Due date: " . date('M j, Y', strtotime($due_date));
        
        // Redirect back to prevent form resubmission
        header("Location: search.php");
        exit();
        
    } catch (Exception $e) {
        $borrowing_error = $e->getMessage();
    }
}

// Fetch all books for display
try {
    $books_stmt = $pdo->query("SELECT * FROM books ORDER BY title");
    $all_books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_books = [];
    $books_error = 'Error loading books: ' . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Library Book Search</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    .book-card {
      transition: all 0.3s ease;
      transform: translateY(0);
    }
    .book-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .search-header {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    }
    .filter-btn.active {
      background-color: #4f46e5;
      color: white;
    }
    .skeleton-loader {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 0.6; }
      50% { opacity: 0.3; }
    }
    .book-cover {
      height: 200px;
      object-fit: contain;
      background-color: #f3f4f6;
    }
    .genre-tag {
      transition: all 0.2s ease;
    }
    .genre-tag:hover {
      transform: scale(1.05);
    }
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 1rem;
      background-color: #10b981;
      color: white;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transform: translateY(-100px);
      opacity: 0;
      transition: all 0.3s ease;
    }
    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
    .toast.error {
      background-color: #ef4444;
    }
  </style>
</head>
<body>
  <!-- Toast Notification -->
  <div id="toast" class="toast hidden"></div>
  <div class="dashboard">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
  <!-- Display reservation success message -->
  <?php if (isset($_SESSION['reservation_success'])): ?>
    <div class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50">
      <?php echo $_SESSION['reservation_success']; ?>
      <?php unset($_SESSION['reservation_success']); ?>
    </div>
  <?php endif; ?>

  <!-- Display wishlist success message -->
  <?php if (isset($_SESSION['wishlist_success'])): ?>
    <div class="fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50">
      <?php echo $_SESSION['wishlist_success']; ?>
      <?php unset($_SESSION['wishlist_success']); ?>
    </div>
  <?php endif; ?>

  <div class="min-h-screen">
    <!-- Search Header -->
    <div class="search-header p-6 text-white rounded-b-2xl shadow-lg">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Discover Your Next Read</h1>
        
        <!-- Search Bar -->
        <div class="relative mb-8">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i data-lucide="search" class="w-5 h-5 text-gray-400"></i>
          </div>
          <input type="text" id="search-input" 
                 class="w-full py-4 pl-10 pr-4 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500" 
                 placeholder="Search by title, author, ISBN or keywords...">
          <button id="search-btn" class="absolute right-2 top-2 bg-purple-600 hover:bg-purple-700 text-white py-2 px-6 rounded-md transition">
            Search
          </button>
        </div>
        
        <!-- Quick Filters -->
        <div class="flex flex-wrap gap-3 mb-4">
          <button class="filter-btn active px-4 py-2 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition" data-filter="all">
            All Books
          </button>
          <button class="filter-btn px-4 py-2 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition" data-filter="recent">
            Recent (2020+)
          </button>
          <button class="filter-btn px-4 py-2 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition" data-filter="cs">
            Computer Science
          </button>
          <button class="filter-btn px-4 py-2 rounded-full bg-white bg-opacity-20 hover:bg-opacity-30 transition" data-filter="programming">
            Programming
          </button>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Search Results Info -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">
          <span id="results-count">0</span> Books Found
        </h2>
        <div class="flex items-center">
          <span class="text-sm text-gray-600 mr-2">Sort by:</span>
          <select id="sort-by" class="p-2 border rounded bg-white">
            <option value="relevance">Relevance</option>
            <option value="title-asc">Title (A-Z)</option>
            <option value="title-desc">Title (Z-A)</option>
            <option value="year-desc">Newest First</option>
            <option value="year-asc">Oldest First</option>
          </select>
        </div>
      </div>
      
      <!-- Books Grid -->
      <div id="books-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <!-- Loading skeleton -->
        <div class="book-card skeleton-loader bg-white rounded-lg shadow p-4 h-64"></div>
        <div class="book-card skeleton-loader bg-white rounded-lg shadow p-4 h-64"></div>
        <div class="book-card skeleton-loader bg-white rounded-lg shadow p-4 h-64"></div>
        <div class="book-card skeleton-loader bg-white rounded-lg shadow p-4 h-64"></div>
      </div>
      
      <!-- Pagination -->
      <div class="mt-8 flex justify-center hidden" id="pagination-container">
        <nav class="inline-flex rounded-md shadow">
          <button id="prev-page" class="px-3 py-1 rounded-l-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
            Previous
          </button>
          <div id="page-numbers" class="flex">
            <!-- Page numbers will be added here -->
          </div>
          <button id="next-page" class="px-3 py-1 rounded-r-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>

  <!-- Book Details Modal -->
  <div id="book-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-start">
          <div>
            <h2 class="text-2xl font-bold text-gray-800" id="modal-title">Book Title</h2>
            <p class="text-gray-600" id="modal-author">Author Name</p>
          </div>
          <button id="close-modal" class="text-gray-400 hover:text-gray-600">
            <i data-lucide="x" class="w-6 h-6"></i>
          </button>
        </div>
        
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-1">
            <img id="modal-cover" src="" alt="Book Cover" class="w-full rounded-lg shadow-md book-cover">
            <div class="mt-4 flex items-center justify-between">
              <div class="flex items-center">
                <i data-lucide="star" class="w-5 h-5 text-yellow-400 fill-yellow-400"></i>
                <span class="ml-1 text-gray-700">4.5 (120 reviews)</span>
              </div>
              <span id="availability" class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Available</span>
            </div>
          </div>
          
          <div class="md:col-span-2">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Details</h3>
              <div class="grid grid-cols-2 gap-4 mt-2">
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Published Year</h4>
                  <p class="text-gray-800" id="modal-year">2023</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Edition</h4>
                  <p class="text-gray-800" id="modal-edition">1st</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Genre</h4>
                  <p class="text-gray-800" id="modal-genre">Computer Science</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Pages</h4>
                  <p class="text-gray-800">350</p>
                </div>
              </div>
            </div>
            
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Description</h3>
              <p class="text-gray-600 mt-2" id="modal-description">
                This is a placeholder description. In a real application, this would contain a detailed description of the book.
              </p>
            </div>
            
            <div class="mb-4">
              <h4 class="text-sm font-medium text-gray-500">Genres</h4>
              <div class="flex flex-wrap gap-2 mt-2" id="modal-genres">
                <!-- Genre tags will be added here -->
              </div>
            </div>
            
            <!-- Inside the modal in search.php -->
              <div class="flex flex-wrap gap-3 mt-6">
                  <form method="POST" action="search.php" class="inline">
                      <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                      <button type="submit" name="borrow_book" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition">
                          <i data-lucide="book-open" class="w-4 h-4 mr-2"></i>
                          Borrow Book
                      </button>
                  </form>

                  <form method="POST" action="search.php" class="inline">
                      <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                      <button type="submit" name="add_to_wishlist" class="flex items-center px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-md transition">
                          <i data-lucide="bookmark" class="w-4 h-4 mr-2"></i>
                          Add to Wishlist
                      </button>
                  </form>

                  <button onclick="window.location.href='review.php?book_id=<?php echo $book['id']; ?>'" 
                          class="flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md transition">
                      <i data-lucide="message-square" class="w-4 h-4 mr-2"></i>
                      Write Review
                  </button>

                  <form method="POST" action="search.php" class="inline">
                      <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                      <button type="submit" name="reserve_book" class="flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition">
                          <i data-lucide="bookmark" class="w-4 h-4 mr-2"></i>
                          Reserve
                      </button>
                  </form>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Lucide icons
      lucide.createIcons();
      
      // DOM Elements
      const searchInput = document.getElementById('search-input');
      const searchBtn = document.getElementById('search-btn');
      const filterBtns = document.querySelectorAll('.filter-btn');
      const sortSelect = document.getElementById('sort-by');
      const booksContainer = document.getElementById('books-container');
      const resultsCount = document.getElementById('results-count');
      const paginationContainer = document.getElementById('pagination-container');
      const prevPageBtn = document.getElementById('prev-page');
      const nextPageBtn = document.getElementById('next-page');
      const pageNumbers = document.getElementById('page-numbers');
      const bookModal = document.getElementById('book-modal');
      const closeModal = document.getElementById('close-modal');
      const toast = document.getElementById('toast');
      
      // Book data
      let allBooks = [];
      let filteredBooks = [];
      let currentPage = 1;
      const booksPerPage = 10;
      
      // Fetch book data from JSON file
      fetch('book.json')
        .then(response => response.json())
        .then(data => {
          // Clean the data (remove any empty template objects)
          allBooks = data.filter(book => book['Book Name'] !== 'Book Name');
          filteredBooks = [...allBooks];
          displayBooks();
        })
        .catch(error => {
          console.error('Error loading book data:', error);
          booksContainer.innerHTML = '<p class="text-red-500">Error loading books. Please try again later.</p>';
        });
      
      // Display books in the grid
      function displayBooks(page = 1) {
        currentPage = page;
        const startIndex = (page - 1) * booksPerPage;
        const endIndex = startIndex + booksPerPage;
        const booksToShow = filteredBooks.slice(startIndex, endIndex);
        
        // Update results count
        resultsCount.textContent = filteredBooks.length;
        
        // Clear previous books
        booksContainer.innerHTML = '';
        
        if (booksToShow.length === 0) {
          booksContainer.innerHTML = '<p class="col-span-full text-center text-gray-500 py-8">No books found matching your criteria.</p>';
          paginationContainer.classList.add('hidden');
          return;
        }
        
        // Add books to the grid
        booksToShow.forEach(book => {
          const bookCard = document.createElement('div');
          bookCard.className = 'book-card bg-white rounded-lg shadow overflow-hidden';
          bookCard.innerHTML = `
            <div class="h-48 flex items-center justify-center bg-gray-100">
              <img src="${book['Book Front Page URL'] || 'https://via.placeholder.com/150x200?text=No+Cover'}" 
                   alt="${book['Book Name']}" 
                   class="book-cover w-full h-full object-contain">
            </div>
            <div class="p-4">
              <h3 class="font-semibold text-gray-800 truncate">${book['Book Name']}</h3>
              <p class="text-sm text-gray-600 truncate">${book['Author']}</p>
              <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center">
                  <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                  <span class="ml-1 text-xs text-gray-600">${book['Published Year']}</span>
                </div>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${book['Genre']}</span>
              </div>
              <button class="mt-3 w-full py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition view-details-btn" 
                      data-book-id="${book['Book Name']}">
                View Details
              </button>
            </div>
          `;
          booksContainer.appendChild(bookCard);
        });
        
        // Initialize Lucide icons for new content
        lucide.createIcons();
        
        // Add event listeners to view details buttons
        document.querySelectorAll('.view-details-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            showBookDetails(bookId);
          });
        });
        
        // Update pagination
        updatePagination();
      }
      
      // Show book details in modal
      function showBookDetails(bookName) {
        const book = allBooks.find(b => b['Book Name'] === bookName);
        if (!book) return;
        
        document.getElementById('modal-title').textContent = book['Book Name'];
        document.getElementById('modal-author').textContent = book['Author'];
        document.getElementById('modal-year').textContent = book['Published Year'];
        document.getElementById('modal-edition').textContent = book['Edition'];
        document.getElementById('modal-genre').textContent = book['Genre'];
        
        // Set book ID for forms
        document.getElementById('reserve-book-id').value = book['Book Name'];
        document.getElementById('wishlist-book-id').value = book['Book Name'];
        
        const coverImg = document.getElementById('modal-cover');
        coverImg.src = book['Book Front Page URL'] || 'https://via.placeholder.com/300x450?text=No+Cover';
        coverImg.alt = book['Book Name'];
        
        // Clear previous genres
        const genresContainer = document.getElementById('modal-genres');
        genresContainer.innerHTML = '';
        
        // Add genre tags (could be expanded to include multiple genres)
        const genreTag = document.createElement('span');
        genreTag.className = 'genre-tag px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded';
        genreTag.textContent = book['Genre'];
        genresContainer.appendChild(genreTag);
        
        bookModal.classList.remove('hidden');
      }
      
      // Update pagination controls
      function updatePagination() {
        const totalPages = Math.ceil(filteredBooks.length / booksPerPage);
        
        if (totalPages <= 1) {
          paginationContainer.classList.add('hidden');
          return;
        }
        
        paginationContainer.classList.remove('hidden');
        pageNumbers.innerHTML = '';
        
        // Previous button state
        prevPageBtn.disabled = currentPage === 1;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
          const pageBtn = document.createElement('button');
          pageBtn.className = `px-3 py-1 border-t border-b border-gray-300 ${i === currentPage ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-500 hover:bg-gray-50'}`;
          pageBtn.textContent = i;
          pageBtn.addEventListener('click', () => displayBooks(i));
          pageNumbers.appendChild(pageBtn);
        }
        
        // Next button state
        nextPageBtn.disabled = currentPage === totalPages;
      }
      
      // Filter books based on search and filters
      function filterBooks() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
        const sortValue = sortSelect.value;
        
        filteredBooks = allBooks.filter(book => {
          // Search term matching
          const matchesSearch = 
            book['Book Name'].toLowerCase().includes(searchTerm) ||
            book['Author'].toLowerCase().includes(searchTerm) ||
            book['Genre'].toLowerCase().includes(searchTerm);
          
          // Filter matching
          let matchesFilter = true;
          switch (activeFilter) {
            case 'all':
              matchesFilter = true;
              break;
            case 'recent':
              matchesFilter = book['Published Year'] >= 2020;
              break;
            case 'cs':
              matchesFilter = book['Genre'].toLowerCase().includes('computer science') || book['Genre'].toLowerCase().includes('cs');
              break;
            case 'programming':
              matchesFilter = book['Genre'].toLowerCase().includes('programming');
              break;
          }
          
          return matchesSearch && matchesFilter;
        });
        
        // Sort books
        switch (sortValue) {
          case 'title-asc':
            filteredBooks.sort((a, b) => a['Book Name'].localeCompare(b['Book Name']));
            break;
          case 'title-desc':
            filteredBooks.sort((a, b) => b['Book Name'].localeCompare(a['Book Name']));
            break;
          case 'year-desc':
            filteredBooks.sort((a, b) => b['Published Year'] - a['Published Year']);
            break;
          case 'year-asc':
            filteredBooks.sort((a, b) => a['Published Year'] - b['Published Year']);
            break;
          // Default is relevance (original order)
        }
        
        // Reset to first page and update pagination
        displayBooks(1);
        updatePagination();
      }
      
      // Show toast notification
      function showToast(message, isError = false) {
        toast.textContent = message;
        toast.className = isError ? 'toast error' : 'toast';
        toast.classList.remove('hidden');
        toast.classList.add('show');
        
        setTimeout(() => {
          toast.classList.remove('show');
          setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3000);
      }
      
      // Event listeners
      searchBtn.addEventListener('click', filterBooks);
      searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') filterBooks();
      });
      
      filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          filterBtns.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          filterBooks();
        });
      });
      
      sortSelect.addEventListener('change', filterBooks);
      
      prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) displayBooks(currentPage - 1);
      });
      
      nextPageBtn.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredBooks.length / booksPerPage);
        if (currentPage < totalPages) displayBooks(currentPage + 1);
      });
      
      closeModal.addEventListener('click', () => {
        bookModal.classList.add('hidden');
      });
      
      // Handle form submissions with AJAX
      document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const formData = new FormData(this);
          const action = this.getAttribute('action');
          const method = this.getAttribute('method');
          
          fetch(action, {
            method: method,
            body: formData
          })
          .then(response => {
            if (response.redirected) {
              window.location.href = response.url;
            } else {
              return response.text();
            }
          })
          .then(data => {
            if (data) {
              // Handle JSON response if needed
              try {
                const jsonData = JSON.parse(data);
                if (jsonData.success) {
                  showToast(jsonData.success);
                } else if (jsonData.error) {
                  showToast(jsonData.error, true);
                }
              } catch (e) {
                // Not JSON, do nothing
              }
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', true);
          });
        });
      });
    });
  </script>
    </main>
  </div>
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
  </script>
</body>
</html>