<?php
// ImageCSRF/Gsession.php — session bootstrap for Image GET CSRF demo
session_start();

/* base state */
if (!isset($_SESSION['i_loggedIn']))        $_SESSION['i_loggedIn'] = false;
if (!isset($_SESSION['i_user_email']))      $_SESSION['i_user_email'] = 'user@example.com';
if (!isset($_SESSION['i_loginArrowDrawn'])) $_SESSION['i_loginArrowDrawn'] = false;
if (!isset($_SESSION['i_drawRedArrow']))    $_SESSION['i_drawRedArrow'] = false;

/* NEW: durable attack status; only reset by explicit 'reset' action */
if (!isset($_SESSION['i_attack_status']))   $_SESSION['i_attack_status'] = 'idle'; // 'idle' | 'attacked'

/* demo cookie (kept for compatibility; no longer sufficient for attack) */
if (!isset($_COOKIE['iauth'])) {
    setcookie('iauth', 'i_user_token', time() + 3600, '/');
}
