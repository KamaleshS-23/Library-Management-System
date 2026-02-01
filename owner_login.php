<?php
require_once 'config.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate input
if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit();
}

$username = trim($input['username']);
$password = trim($input['password']);

try {
    $stmt = $pdo->prepare("SELECT * FROM owners WHERE username = ?");
    $stmt->execute([$username]);
    $owner = $stmt->fetch();

    if ($owner && password_verify($password, $owner['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['owner_id'] = $owner['id'];
        $_SESSION['username'] = $owner['username'];
        $_SESSION['email'] = $owner['email'] ?? '';
        $_SESSION['role'] = 'owner';
        $_SESSION['last_activity'] = time();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        http_response_code(401); // Unauthorized
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>