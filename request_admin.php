<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $fullName = trim($data['full_name']);
    $email = trim($data['email']);
    $position = trim($data['position']);
    $reason = trim($data['reason']);

    if (empty($fullName) || empty($email) || empty($position) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }

    try {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $stmt = $pdo->prepare("INSERT INTO admin_requests (user_id, full_name, email, position, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $fullName, $email, $position, $reason]);

        echo json_encode(['success' => true, 'message' => 'Admin request submitted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>