<?php
// Hrefdefense/Dsession.php
session_start();


$_SESSION['i_loggedIn'] = $_SESSION['i_loggedIn'] ?? false;

// Generate a CSRF token for this session (if it does not exist)
if (empty($_SESSION['i_csrf_token'])) {
    $_SESSION['i_csrf_token'] = bin2hex(random_bytes(16));
}
