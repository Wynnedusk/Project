<?php
require_once __DIR__ . '/../Hrefattack/Hsession.php';


// Reject if not logged in
if (empty($_SESSION['i_loggedIn'])) {
    http_response_code(401);
    exit('Not logged in');
}

// ---- Ensure the follows container is an array ----
if (!isset($_SESSION['i_follows']) || !is_array($_SESSION['i_follows'])) {
    $_SESSION['i_follows'] = [];
}

// Only allow POST requests; treat GET as an attack and block
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['i_blocked'] = true;
    header('Location: D_attacker.html?blocked=1');
    exit;
}

// Validate CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!$token || $token !== ($_SESSION['i_csrf_token'] ?? '')) {
    http_response_code(403);
    exit('CSRF token invalid or missing.');
}

// Validate request origin (Origin / Referer)
$host   = $_SERVER['HTTP_HOST']    ?? '';
$origin = $_SERVER['HTTP_ORIGIN']  ?? '';
$refer  = $_SERVER['HTTP_REFERER'] ?? '';
$ok = ($origin && stripos($origin, $host) !== false) ||
      ($refer  && stripos($refer,  $host) !== false);
if (!$ok) {
    http_response_code(403);
    exit('Cross-origin request rejected.');
}

// Parameters
$user = trim($_POST['user'] ?? '');
if ($user === '') { $user = 'attacker'; }

// Update follows list
if (!in_array($user, $_SESSION['i_follows'], true)) {
    array_unshift($_SESSION['i_follows'], $user);
}

// Set flash message
$_SESSION['i_flash'] = ['type' => 'follow_ok', 'who' => $user];

// Redirect back to Profile
header('Location: D_profile.php');
exit;
