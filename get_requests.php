<?php
require_once 'config.php';
require_once 'check_auth.php';

header('Content-Type: application/json');

// Verify owner session
verifyOwnerSession();

try {
    $stmt = $pdo->prepare("SELECT ar.*, u.username, u.email as user_email 
                          FROM admin_requests ar
                          LEFT JOIN users u ON ar.user_id = u.id
                          WHERE ar.status = 'pending'
                          ORDER BY ar.created_at DESC");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output
    foreach ($requests as &$request) {
        $request['full_name'] = htmlspecialchars($request['full_name']);
        $request['email'] = htmlspecialchars($request['email']);
        $request['position'] = htmlspecialchars($request['position']);
        $request['reason'] = nl2br(htmlspecialchars($request['reason']));
    }

    echo json_encode([
        'success' => true,
        'data' => $requests,
        'count' => count($requests)
    ]);
} catch (PDOException $e) {
    error_log("Get requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load requests']);
}
?>