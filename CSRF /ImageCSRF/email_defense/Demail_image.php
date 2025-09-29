<?php
require_once __DIR__ . '/../email_attack/Gsession.php'; // reuse the same session store
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * Unified helper to "record and force <img> failure"
 * - Records this interception (for display in the left Profile accordion)
 * - Returns a response that will trigger <img>.onerror (non-200/200 as chosen + empty GIF body)
 */
function def_block($code, $reason, array $meta = []) {
    $_SESSION['def_last_blocked'] = true;
    $_SESSION['def_last_reason']  = $reason;
    $_SESSION['def_last_meta']    = array_merge([
        'method'    => $_SERVER['REQUEST_METHOD'] ?? '',
        'origin'    => $_SERVER['HTTP_ORIGIN']    ?? '',
        'referer'   => $_SERVER['HTTP_REFERER']   ?? '',
        'site'      => $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '',
        'has_token' => isset($_POST['csrf_token']) ? 'yes' : 'no',
    ], $meta);

    // Ensure <img>.onerror fires: return a non-success code + empty image payload
    http_response_code($code);
    header('Content-Type: image/gif');
    header('Content-Length: 0');
    header('Cache-Control: no-store');
    exit;
}

// Never cache this endpoint
header('Cache-Control: no-store');

// 1) Require authenticated session
if (empty($_SESSION['i_loggedIn'])) {
    def_block(403, 'not_logged_in');
}

// 2) Only allow POST (image-based GET attempts should fail here)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    def_block(405, 'method');
}

// 3) Verify CSRF token
$posted = $_POST['csrf_token'] ?? '';
$token  = $_SESSION['def_csrf_token'] ?? '';
if (!$posted || !$token || !hash_equals($token, $posted)) {
    def_block(403, 'token');
}

// 4) Optional same-origin checks (Origin / Referer / Sec-Fetch-Site)
$scheme = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://');
$host   = $scheme . ($_SERVER['HTTP_HOST'] ?? '');
$origin = $_SERVER['HTTP_ORIGIN']       ?? '';
$refer  = $_SERVER['HTTP_REFERER']      ?? '';
$site   = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';

if (($origin && strpos($origin, $host) !== 0) ||
    (!$origin && $refer && strpos($refer, $host) !== 0) ||
    ($site && !in_array($site, ['same-origin','same-site'], true))) {
    def_block(403, 'origin');
}

// 5) Apply the actual change (rarely reached in the demo flow)
$newEmail = trim($_POST['new'] ?? '');
if ($newEmail === '') {
    def_block(400, 'empty_new_email');
}

$_SESSION['i_user_email']     = $newEmail;
$_SESSION['def_last_blocked'] = false; // reset the "blocked" flag on successful business flow

// Return a tiny single-pixel GIF (optional). 204 would also be fine, but keep consistency with other flows.
http_response_code(200);
header('Content-Type: image/gif');
echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x4C\x01\x00\x3B";
