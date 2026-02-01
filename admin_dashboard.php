<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

if ($_SESSION['user_type'] !== 'admin') {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        <p>This is the admin dashboard.</p>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
</body>
</html>