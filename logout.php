<?php
require_once 'config.php';

// Check user type before destroying session
$userType = $_SESSION['user_type'] ?? $_SESSION['role'] ?? null;

// Destroy session
$_SESSION = array();
session_destroy();

// Redirect based on user type
if ($userType === 'owner' || isset($_SESSION['owner_id'])) {
    header('Location: owner_login.html');
} else {
    header('Location: index.html');
}
exit();
?>