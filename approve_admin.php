<?php
require_once 'config.php';
require_once 'mailer.php';

header('Content-Type: application/json');

// Enhanced error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Validate session and permissions
    if (!isset($_SESSION['owner_id'])) {
        throw new Exception('Unauthorized access', 403);
    }

    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input', 400);
    }

    $requestId = filter_var($data['request_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$requestId) {
        throw new Exception('Invalid request ID', 400);
    }

    // Database transaction
    $pdo->beginTransaction();

    // Get the request with locking
    $stmt = $pdo->prepare("SELECT * FROM admin_requests WHERE id = ? AND status = 'pending' FOR UPDATE");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();

    if (!$request) {
        throw new Exception('Request not found or already processed', 404);
    }

    // Generate credentials
    $username = generateUsername($request['full_name']);
    $tempPassword = generateTemporaryPassword();
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Check for existing user
    $existingUser = getUserByEmail($request['email']);

    if ($existingUser) {
        updateUser($existingUser['id'], [
            'password' => $hashedPassword,
            'role' => 'admin',
            'is_approved' => true
        ]);
    } else {
        createUser([
            'full_name' => $request['full_name'],
            'email' => $request['email'],
            'username' => $username,
            'password' => $hashedPassword,
            'role' => 'admin',
            'is_approved' => true
        ]);
    }

    // Update request status
    $pdo->prepare("UPDATE admin_requests SET status = 'approved', processed_at = NOW() WHERE id = ?")
        ->execute([$requestId]);

    // Send email
    $emailSent = sendAdminApprovalEmail(
        $request['email'],
        $request['full_name'],
        $username,
        $tempPassword
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Admin account created successfully',
        'email_sent' => $emailSent
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log the error
    error_log("Approval Error: " . $e->getMessage());
    
    // Return appropriate HTTP status
    http_response_code($e->getCode() ?: 500);
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while approving the request',
        'debug' => APP_ENV === 'development' ? $e->getMessage() : null
    ]);
}

// Helper functions would be defined here...
?>