<?php
require_once __DIR__ . '/Hsession.php';

// Clear session data and restart the session
session_unset();
session_destroy();
session_start();

// Read 'back' (prefer GET, then Referer) and restrict to same-origin
$back = $_GET['back'] ?? '';
if ($back === '') { $back = $_SERVER['HTTP_REFERER'] ?? ''; }

$hostBack = parse_url($back, PHP_URL_HOST);
if ($back !== '' && $hostBack !== null && $hostBack !== $_SERVER['HTTP_HOST']) {
  $back = '';
}

/* ★ Critical fix: default to an existing path /HrefCSRF/Hrefdefense/D_profile.php */
if ($back === '') {
  $back = '/HrefCSRF/Hrefdefense/D_profile.php?step=1&_v=' . time();
}

header('Cache-Control: no-store');
header('Location: ' . $back);
exit;
