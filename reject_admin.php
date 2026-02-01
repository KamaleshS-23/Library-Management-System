<?php
require_once 'config.php';

header('Content-Type: application/json');

// Verify owner is logged in
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID not provided']);
    exit();
}

$requestId = (int)$data['request_id'];
$reason = isset($data['reason']) ? trim($data['reason']) : null;

try {
    $stmt = $pdo->prepare("UPDATE admin_requests 
                          SET status = 'rejected', 
                              processed_at = NOW(),
                              rejection_reason = ?
                          WHERE id = ?");
    $stmt->execute([$reason, $requestId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>