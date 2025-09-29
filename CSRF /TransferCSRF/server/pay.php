<?php
session_start();

// Optional: write current cookie and time into debug log (for troubleshooting)
file_put_contents("debug.txt",
  "💡 COOKIE = " . ($_COOKIE['auth'] ?? 'NONE') .
  " ｜ TIME = " . date("H:i:s") . PHP_EOL,
  FILE_APPEND
);

// Step 1: Backend enforces "logged-in" (either valid session or correct auth cookie required)
if (
  empty($_SESSION['email']) ||           // no valid session
  !isset($_COOKIE['auth']) ||
  $_COOKIE['auth'] !== 'user_token'      // or invalid auth cookie
) {
  http_response_code(403);
  echo "Not logged in";
  exit;
}

// Step 2: Get transfer amount
$amount = isset($_POST['money']) ? intval($_POST['money']) : 0;

if ($amount > 0) {
  $balanceFile = "balance.json";
  $balanceData = json_decode(file_get_contents($balanceFile), true);

  // Step 3: Deduct only if balance is sufficient
  if ($balanceData['balance'] >= $amount) {
    $balanceData['balance'] -= $amount;
    file_put_contents($balanceFile, json_encode($balanceData, JSON_PRETTY_PRINT));

    // Step 4: Log the "POST CSRF transfer"
    $recipient = "attacker@evil.com";
    $log = "POST-based CSRF Transfer: {$amount} Euros to {$recipient} ｜Time: " . date("Y-m-d H:i:s") . PHP_EOL;
    file_put_contents("logs.txt", $log, FILE_APPEND);

    echo "Transfer successful: {$amount} Euros transferred to {$recipient}";
  } else {
    echo "Insufficient balance, transfer failed";
  }
} else {
  echo "Invalid amount";
}
