<?php
session_start();

// Initialize login state and token, only if not set
if (!isset($_SESSION['loggedIn'])) {
    $_SESSION['loggedIn'] = false;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
//Set up CSRF token defense
if (!isset($_SESSION['global_posts'])) {
    $_SESSION['global_posts'] = [];
}
if (!isset($_SESSION['secureLoginArrowDrawn'])) {
    $_SESSION['secureLoginArrowDrawn'] = false;
}
