<?php
require_once('config.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_POST['reservation_id'])) {
    $_SESSION['reservation_error'] = 'Invalid request.';
    header('Location: reservation.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_POST['reservation_id'];

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get reservation details
    $reservation_stmt = $pdo->prepare("SELECT * FROM reservations 
                                      WHERE id = :reservation_id AND user_id = :user_id
                                      AND (status = 'pending' OR status = 'active')");
    $reservation_stmt->bindParam(':reservation_id', $reservation_id);
    $reservation_stmt->bindParam(':user_id', $user_id);
    $reservation_stmt->execute();
    $reservation = $reservation_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['reservation_error'] = 'Reservation not found or cannot be cancelled.';
        header('Location: reservation.php');
        exit();
    }
    
    // Update reservation status
    $update_stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :reservation_id");
    $update_stmt->bindParam(':reservation_id', $reservation_id);
    $update_stmt->execute();
    
    // Increment available copies
    $book_update = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = :book_id");
    $book_update->bindParam(':book_id', $reservation['book_id']);
    $book_update->execute();
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['reservation_success'] = 'Reservation cancelled successfully.';
    header('Location: reservation.php');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['reservation_error'] = 'Error cancelling reservation: ' . $e->getMessage();
    header('Location: reservation.php');
    exit();
}