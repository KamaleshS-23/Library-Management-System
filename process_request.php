<?php
require_once 'config.php';
require_once 'check_auth.php';

header('Content-Type: application/json');

// Verify owner session
verifyOwnerSession();

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Validate input
$requestId = filter_var($data['request_id'] ?? 0, FILTER_VALIDATE_INT);
$action = in_array($data['action'] ?? '', ['approve', 'reject']) ? $data['action'] : null;
$reason = isset($data['reason']) ? htmlspecialchars(trim($data['reason'])) : null;

if (!$requestId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Update request status
    $stmt = $pdo->prepare("UPDATE admin_requests 
                          SET status = ?, updated_at = NOW()
                          WHERE id = ? AND status = 'pending'");
    $stmt->execute([$action === 'approve' ? 'approved' : 'rejected', $requestId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Request not found or already processed');
    }

    // 2. If approved and has user_id, upgrade to admin
    if ($action === 'approve') {
        $requestStmt = $pdo->prepare("SELECT user_id FROM admin_requests WHERE id = ?");
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if ($request && $request['user_id']) {
            $userStmt = $pdo->prepare("UPDATE users 
                                      SET user_type = 'admin', is_approved = TRUE 
                                      WHERE id = ?");
            $userStmt->execute([$request['user_id']]);
        }

        // Here you could also send an approval email
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Request {$action}d successfully"]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Process request error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>