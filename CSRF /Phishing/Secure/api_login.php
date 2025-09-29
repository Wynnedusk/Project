<?php
// /Phishing/Secure/api_login.php
// Endpoint for panel "reuse" simulation
// Accepts POST JSON: { email, password, defense }

session_start();
header('Content-Type: application/json; charset=utf-8');

// Demo account (only valid credential set for teaching)


// Read raw POST body and decode as JSON
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true) ?: [];
$email   = $payload['email']    ?? '';
$pwd     = $payload['password'] ?? '';
$defense = !empty($payload['defense']); // In defense mode, always reject reuse attempts

// ---- Defense path ----
// If "defense" flag is set, block the reuse attempt (teaching demo)
if ($defense) {
  echo json_encode(['ok' => false, 'reason' => 'blocked-by-defense']);
  exit;
}

// ---- Normal validation path ----
if ($email === VALID_EMAIL && $pwd === VALID_PASS) {
  // Mark the session as logged in (simulated real login)
  $_SESSION['loggedIn']     = true;
  $_SESSION['email']        = $email;
  $_SESSION['last_login_at'] = date('c');
  echo json_encode(['ok'=>true, 'success'=>true]);
} else {
  echo json_encode(['ok'=>false, 'success'=>false, 'reason'=>'missing-credentials']);
}