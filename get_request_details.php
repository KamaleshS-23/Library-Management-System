<?php
require_once 'config.php';

header('Content-Type: application/json');

// Verify owner is logged in
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID not provided']);
    exit();
}

$requestId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT ar.*, u.username 
                          FROM admin_requests ar
                          LEFT JOIN users u ON ar.user_id = u.id
                          WHERE ar.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        echo json_encode(['success' => true, 'request' => $request]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>