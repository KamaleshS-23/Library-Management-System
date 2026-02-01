<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $fullName = trim($data['full_name']);
    $email = trim($data['email']);
    $username = trim($data['username']);
    $password = trim($data['password']);
    $confirmPassword = trim($data['confirm_password']);

    // Validate inputs
    if (empty($fullName) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }

    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }

    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit();
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password, user_type) VALUES (?, ?, ?, ?, 'user')");
        $stmt->execute([$fullName, $email, $username, $hashedPassword]);

        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>