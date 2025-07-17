<?php
session_start();

// Login 
$_SESSION['loggedIn'] = true;

// Teaching Arrow Initialization
$_SESSION['secureLoginArrowDrawn'] = false;

// Clear Attack Blocker Flags
unset($_SESSION['attackBlocked']);

// Initialize post list (first time only)
if (!isset($_SESSION['global_posts'])) {
    $_SESSION['global_posts'] = [];
}

// redirect secure_blog.php
$target = $_GET['redirect'] ?? 'secure_blog.php?step=2';
header("Location: $target");
exit;
