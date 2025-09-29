<?php
// Hsession.php  — unify session cookie scope for the whole demo
// Must run before any output (BOM or stray spaces will break Set-Cookie headers)

// Start session only if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
  $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

  // ---- Compatibility: PHP 7.3+ accepts an array for cookie params; older versions use the legacy signature.
  // For older PHP, add SameSite via a path hack so mainstream browsers will recognise it.
  if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
      'lifetime' => 0,       // session cookie
      'path'     => '/',     // share across demo directories
      'domain'   => '',      // default to host
      'secure'   => $secure,
      'httponly' => true,
      'samesite' => 'Lax',   // Lax suits GET-CSRF teaching scenarios
    ]);
  } else {
    // Legacy signature: lifetime, path, domain, secure, httponly
    // Append SameSite via path to support older PHP versions
    $path = '/; samesite=Lax';
    session_set_cookie_params(0, $path, '', $secure, true);
    // Set related ini options as a fallback (harmless if ignored)
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_secure',   $secure ? '1' : '0');
  }

  // Use a fixed session name to avoid collisions with other apps on the server
  session_name('hrefdemo');

  session_start();
}

/* Login state and basic profile defaults */
if (!isset($_SESSION['i_loggedIn']))   $_SESSION['i_loggedIn'] = false;
if (!isset($_SESSION['i_user_email'])) $_SESSION['i_user_email'] = 'student@ucc.ie';
if (!isset($_SESSION['i_user_name']))  $_SESSION['i_user_name']  = 'student';
if (!isset($_SESSION['i_attacked']))   $_SESSION['i_attacked']   = false;

/* Following: prepopulate with 32 placeholder names (display-only) */
if (!isset($_SESSION['i_follows'])) {
  $_SESSION['i_follows'] = [
    'amy','brandon','charlie','diana','eric','fiona','george','hannah',
    'ivan','julia','kevin','lisa','mike','nina','oliver','peter',
    'queen','rachel','sam','tina','ursula','victor','wendy','xavier',
    'yolanda','zack','ben','carl','doris','ed','frank','grace'  // total 32
  ];
}

/* Followers count: fixed example value (display only) */
if (!isset($_SESSION['i_followers_count'])) {
  $_SESSION['i_followers_count'] = 15;
}

/* Utility helpers (kept minimal and consistent with other modules) */
function ensure_csrf_token() {
  if (empty($_SESSION['i_csrf_token'])) $_SESSION['i_csrf_token'] = bin2hex(random_bytes(16));
  return $_SESSION['i_csrf_token'];
}
function json_out($arr) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit;
}
function check_origin_referer() {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $o = $_SERVER['HTTP_ORIGIN']  ?? '';
  $r = $_SERVER['HTTP_REFERER'] ?? '';
  if ($o && stripos($o,$host)!==false) return true;
  if ($r && stripos($r,$host)!==false) return true;
  return false;
}
