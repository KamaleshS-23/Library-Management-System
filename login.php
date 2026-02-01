<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $username = trim($data['username']);
    $password = trim($data['password']);
    $userType = $data['userType'] ?? 'user';

    // Basic validation
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_type = ? AND is_approved = TRUE");
        $stmt->execute([$username, $userType]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>