<?php
session_start();

// Mark user as logged in (for attack module only)
$_SESSION['attack_loggedIn'] = true;

// Reset attack module visualization flags
$_SESSION['attack_loginArrowDrawn'] = false;
$_SESSION['attack_csrfArrowDrawn'] = false;

// Initialize shared post list if needed
if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}

// Optional: clear teaching triggers
unset($_SESSION['triggerDrawLoginArrow']);
unset($_SESSION['triggerDrawAttackArrow']);

// Redirect after login
$target = $_GET['redirect'] ?? '../attack/blog.php?step=2';
header("Location: $target");
exit;
