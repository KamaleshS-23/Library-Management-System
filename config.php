<?php
// Database configuration
// ==========================================
// IMPORTANT: Update these values to match your MySQL setup
// ==========================================
$host = 'localhost';
$port = '3306';  // Common ports: 3306 (standard MySQL), 3308 (XAMPP alternative)
$dbname = 'library_management';
$username = 'root';
$password = 'Kamalesh23kk@';  // Leave empty if your MySQL root has no password

// Try to connect without database first (to check server connection)
try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8";
    $testPdo = new PDO($dsn, $username, $password);
    $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Now try to connect with database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    
    // Provide helpful error messages
    if (strpos($errorMsg, '2002') !== false || strpos($errorMsg, 'refused') !== false) {
        die("
        <!DOCTYPE html>
        <html>
        <head><title>Database Connection Error</title>
        <style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto;}
        .error-box{background:#fee;border:2px solid #f66;padding:20px;border-radius:8px;}
        h1{color:#c00;} code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}
        </style></head>
        <body>
        <div class='error-box'>
        <h1>❌ MySQL Server Not Running</h1>
        <p><strong>Error:</strong> Cannot connect to MySQL server.</p>
        <p><strong>Solution:</strong></p>
        <ol>
        <li>Start MySQL service:
        <ul>
        <li>XAMPP: Open XAMPP Control Panel → Start MySQL</li>
        <li>WAMP: Click WAMP icon → Start MySQL</li>
        <li>Windows Service: Open Services → Start 'MySQL' service</li>
        <li>Command line: <code>net start MySQL</code> (as Administrator)</li>
        </ul>
        </li>
        <li>Check if MySQL is running on port <code>$port</code></li>
        <li>Verify your MySQL installation</li>
        </ol>
        <p><strong>Connection Details:</strong><br>
        Host: $host<br>
        Port: $port<br>
        Username: $username<br>
        Database: $dbname</p>
        </div>
        </body>
        </html>");
    } elseif (strpos($errorMsg, '1045') !== false || strpos($errorMsg, 'Access denied') !== false) {
        die("
        <!DOCTYPE html>
        <html>
        <head><title>Database Authentication Error</title>
        <style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto;}
        .error-box{background:#fee;border:2px solid #f66;padding:20px;border-radius:8px;}
        h1{color:#c00;} code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}
        </style></head>
        <body>
        <div class='error-box'>
        <h1>❌ Database Authentication Failed</h1>
        <p><strong>Error:</strong> Access denied for user '$username'</p>
        <p><strong>Solution:</strong></p>
        <ol>
        <li>Check if the password in <code>config.php</code> is correct</li>
        <li>If your MySQL root has no password, set: <code>\$password = '';</code></li>
        <li>Reset MySQL root password if needed</li>
        <li>Verify the username is correct</li>
        </ol>
        <p><strong>Current Settings:</strong><br>
        Username: $username<br>
        Password: " . (empty($password) ? '(empty)' : '***') . "</p>
        </div>
        </body>
        </html>");
    } elseif (strpos($errorMsg, 'Unknown database') !== false) {
        die("
        <!DOCTYPE html>
        <html>
        <head><title>Database Not Found</title>
        <style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto;}
        .error-box{background:#fff3cd;border:2px solid #ffc107;padding:20px;border-radius:8px;}
        h1{color:#856404;} code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}
        </style></head>
        <body>
        <div class='error-box'>
        <h1>⚠️ Database Not Found</h1>
        <p><strong>Error:</strong> Database '$dbname' does not exist.</p>
        <p><strong>Solution:</strong> Create the database using:</p>
        <pre style='background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;'>
CREATE DATABASE library_management;
USE library_management;</pre>
        <p>Or run this in MySQL command line / phpMyAdmin</p>
        </div>
        </body>
        </html>");
    } else {
        die("Database connection failed: " . htmlspecialchars($errorMsg));
    }
}

// Initialize session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Prevent caching for pages that require authentication
// This prevents back button from showing cached pages
if (!headers_sent()) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

date_default_timezone_set('UTC');
?>