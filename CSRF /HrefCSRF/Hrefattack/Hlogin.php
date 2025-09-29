<?php
require_once __DIR__ . '/Hsession.php';

// Simulate login
$_SESSION['i_loggedIn']   = true;
$_SESSION['i_user_email'] = 'student@ucc.ie';
$_SESSION['i_user_name']  = 'student';
ensure_csrf_token();
session_regenerate_id(true);

// Read 'back' parameter
$back = $_GET['back'] ?? '';
if ($back === '') { $back = $_SERVER['HTTP_REFERER'] ?? ''; }

$hostBack = parse_url($back, PHP_URL_HOST);
if ($back !== '' && $hostBack !== null && $hostBack !== $_SERVER['HTTP_HOST']) {
  $back = '';
}

// ★ Default to the defense demo Profile
if ($back === '' ) { 
  $back = '/HrefCSRF/D_profile.php?step=2&_v='.time(); 
}

header('Cache-Control: no-store');
header('Location: ' . $back);
exit;
