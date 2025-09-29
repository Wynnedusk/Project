<?php
require_once __DIR__ . '/Gsession.php';

/*
 * Only allow logged-in victims to be affected by the vulnerability.
 * The previous $cookie_ok check allowed anonymous users (since demo cookies always exist); this has been removed.
 */
$login_ok = !empty($_SESSION['i_loggedIn']);
if (!$login_ok) {
    http_response_code(403);
    header('Cache-Control: no-store');
    echo "Login required"; 
    exit;
}

// Vulnerability: change email via GET (silently triggered by <img>)
$new = isset($_GET['new']) ? trim($_GET['new']) : '';
if ($new !== '') {
    $_SESSION['i_user_email']   = $new;
    $_SESSION['i_drawRedArrow'] = true;
    $_SESSION['i_csrf_notice']  = true;
    $_SESSION['i_attack_status']= 'attacked'; // Persist attack status for top-level banner
}

// Return a normal image (disguised as an ad)
$imgPath = __DIR__ . '/../assets/image.png';
if (file_exists($imgPath)) {
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    readfile($imgPath); 
    exit;
}
http_response_code(404);
header('Cache-Control: no-store');
echo "Image not found.";
