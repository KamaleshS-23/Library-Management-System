<?php
require_once('config.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$fine_id = $_POST['fine_id'] ?? null;

if (!$fine_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid fine ID']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Update fine status
    $update_stmt = $pdo->prepare("
        UPDATE fines 
        SET status = 'paid', 
            payment_date = NOW() 
        WHERE id = :fine_id AND user_id = :user_id
    ");
    $update_stmt->bindParam(':fine_id', $fine_id);
    $update_stmt->bindParam(':user_id', $user_id);
    $update_stmt->execute();
    
    // Record payment transaction
    $payment_stmt = $pdo->prepare("
        INSERT INTO payments (user_id, fine_id, amount, payment_method, transaction_date)
        VALUES (:user_id, :fine_id, 
               (SELECT amount FROM fines WHERE id = :fine_id), 
               'online', NOW())
    ");
    $payment_stmt->bindParam(':user_id', $user_id);
    $payment_stmt->bindParam(':fine_id', $fine_id);
    $payment_stmt->execute();
    
    // Get payment details for response
    $payment_id = $pdo->lastInsertId();
    $payment_query = $pdo->prepare("
        SELECT p.*, f.reason 
        FROM payments p
        JOIN fines f ON p.fine_id = f.id
        WHERE p.id = :payment_id
    ");
    $payment_query->bindParam(':payment_id', $payment_id);
    $payment_query->execute();
    $payment = $payment_query->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'amount' => $payment['amount'],
        'payment' => $payment
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>