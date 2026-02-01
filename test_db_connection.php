<?php
// Database connection test script
echo "=== Database Connection Test ===\n\n";

// Test different configurations
$configs = [
    [
        'name' => 'Current Config (Port 3308)',
        'host' => 'localhost',
        'port' => '3308',
        'dbname' => 'library_management',
        'username' => 'root',
        'password' => 'Kamalesh23kk@'
    ],
    [
        'name' => 'Standard MySQL (Port 3306)',
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'library_management',
        'username' => 'root',
        'password' => 'Kamalesh23kk@'
    ],
    [
        'name' => 'Standard MySQL (No Password)',
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'library_management',
        'username' => 'root',
        'password' => ''
    ],
    [
        'name' => 'Port 3308 (No Password)',
        'host' => 'localhost',
        'port' => '3308',
        'dbname' => 'library_management',
        'username' => 'root',
        'password' => ''
    ]
];

$success = false;

foreach ($configs as $config) {
    echo "Testing: {$config['name']}...\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    echo "  Database: {$config['dbname']}\n";
    echo "  Username: {$config['username']}\n";
    echo "  Password: " . (empty($config['password']) ? '(empty)' : '***') . "\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "  ✓ Connection to MySQL server successful!\n";
        
        // Try to select the database
        try {
            $pdo->exec("USE `{$config['dbname']}`");
            echo "  ✓ Database '{$config['dbname']}' exists and accessible!\n";
            echo "\n✅ SUCCESS! Use this configuration:\n";
            echo "Host: {$config['host']}\n";
            echo "Port: {$config['port']}\n";
            echo "Database: {$config['dbname']}\n";
            echo "Username: {$config['username']}\n";
            echo "Password: " . (empty($config['password']) ? '(empty)' : $config['password']) . "\n";
            $success = true;
            break;
        } catch (PDOException $e) {
            echo "  ✗ Database '{$config['dbname']}' error: " . $e->getMessage() . "\n";
            echo "  (Server connection works, but database may not exist)\n";
        }
    } catch (PDOException $e) {
        echo "  ✗ Connection failed: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

if (!$success) {
    echo "\n❌ None of the configurations worked.\n";
    echo "\nPlease check:\n";
    echo "1. Is MySQL/MariaDB running?\n";
    echo "2. What port is MySQL running on? (Check MySQL configuration or try: netstat -an | findstr :3306 or :3308)\n";
    echo "3. What is your MySQL root password?\n";
    echo "4. Does the database 'library_management' exist?\n";
}
?>
