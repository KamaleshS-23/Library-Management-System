<?php
// sidebar.php - Reusable sidebar component
// This file generates the sidebar navigation for all dashboard pages

$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <h2 class="sidebar-title">Library Dashboard</h2>
    <nav class="menu">
        <a href="profile.php" class="menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
            <i data-lucide="user"></i> User Profile
        </a>
        <a href="search.php" class="menu-item <?php echo ($current_page === 'search.php') ? 'active' : ''; ?>">
            <i data-lucide="search"></i> Search & Browse
        </a>
        <a href="borrowed.php" class="menu-item <?php echo ($current_page === 'borrowed.php') ? 'active' : ''; ?>">
            <i data-lucide="archive"></i> Borrowed Books & History
        </a>
        <a href="reservation.php" class="menu-item <?php echo ($current_page === 'reservation.php') ? 'active' : ''; ?>">
            <i data-lucide="bookmark"></i> Book Reservation
        </a>
        <a href="fines.php" class="menu-item <?php echo ($current_page === 'fines.php') ? 'active' : ''; ?>">
            <i data-lucide="dollar-sign"></i> Fines
        </a>
        <a href="notifications.php" class="menu-item <?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>">
            <i data-lucide="bell"></i> Notifications
        </a>
        <a href="wishlist.php" class="menu-item <?php echo ($current_page === 'wishlist.php') ? 'active' : ''; ?>">
            <i data-lucide="heart"></i> Wishlist
        </a>
        <a href="recommendations.php" class="menu-item <?php echo ($current_page === 'recommendations.php') ? 'active' : ''; ?>">
            <i data-lucide="sparkles"></i> Recommendations
        </a>
        <a href="reviews.php" class="menu-item <?php echo ($current_page === 'reviews.php') ? 'active' : ''; ?>">
            <i data-lucide="star"></i> Reviews & Ratings
        </a>
        <a href="ebooks.php" class="menu-item <?php echo ($current_page === 'ebooks.php') ? 'active' : ''; ?>">
            <i data-lucide="download"></i> eBooks / PDFs
        </a>
        <a href="calendar.php" class="menu-item <?php echo ($current_page === 'calendar.php') ? 'active' : ''; ?>">
            <i data-lucide="calendar"></i> Events Calendar
        </a>
        <a href="chat.php" class="menu-item <?php echo ($current_page === 'chat.php') ? 'active' : ''; ?>">
            <i data-lucide="message-circle"></i> Ask Librarian
        </a>
        <a href="feedback.php" class="menu-item <?php echo ($current_page === 'feedback.php') ? 'active' : ''; ?>">
            <i data-lucide="edit"></i> Feedback
        </a>
        <a href="settings.php" class="menu-item <?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
            <i data-lucide="settings"></i> Settings
        </a>
        <a href="logout.php" class="menu-item" style="border-top: 1px solid #374151; margin-top: 15px; padding-top: 15px; color: #ef4444;">
            <i data-lucide="log-out"></i> Logout
        </a>
    </nav>
</aside>
