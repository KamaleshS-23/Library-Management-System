<?php
require_once 'config.php';

header('Content-Type: application/json');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate input data
$requiredFields = ['full_name', 'email', 'position', 'reason'];
foreach ($requiredFields as $field) {
    if (empty(trim($data[$field] ?? ''))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit();
    }
}

// Sanitize inputs
$fullName = htmlspecialchars(trim($data['full_name']));
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$position = htmlspecialchars(trim($data['position']));
$reason = htmlspecialchars(trim($data['reason']));

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    // Get user ID if logged in
    $userId = $_SESSION['user_id'] ?? null;
    
    // Check for duplicate pending requests
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_requests 
                               WHERE email = ? AND status = 'pending'");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You already have a pending request']);
        exit();
    }

    // Insert new request
    $stmt = $pdo->prepare("INSERT INTO admin_requests 
                          (user_id, full_name, email, position, reason) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $fullName, $email, $position, $reason]);

    http_response_code(201);
    echo json_encode([
        'success' => true, 
        'message' => 'Admin request submitted successfully',
        'request_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log("Admin request error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>