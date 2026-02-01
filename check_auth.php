<?php
require_once 'config.php';

function verifyOwnerSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['owner_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
        header('Location: owner_login.html');
        exit();
    }
}

// Alternative function that won't output JSON (for HTML pages)
function verifyOwnerSessionSilent() {
    if (empty($_SESSION['owner_id']) || empty($_SESSION['username']) || ($_SESSION['role'] ?? null) !== 'owner') {
        header('Location: owner_login.html');
        exit();
    }
}
?>