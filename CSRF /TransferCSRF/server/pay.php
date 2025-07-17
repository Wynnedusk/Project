<?php
// Log the current cookie and timestamp for debugging
file_put_contents("debug.txt", "💡 COOKIE = " . ($_COOKIE['auth'] ?? 'NONE') . " ｜ TIME = " . date("H:i:s") . PHP_EOL, FILE_APPEND);

// Step 1: Check if the user is logged in using the 'auth' cookie
if (!isset($_COOKIE['auth']) || $_COOKIE['auth'] !== 'user_token') {
  http_response_code(403);
  echo "Not logged in";
  exit;
}

// Step 2: Get the transfer amount from the POST request
$amount = isset($_POST['money']) ? intval($_POST['money']) : 0;

if ($amount > 0) {
  $balanceFile = "balance.json";
  $balanceData = json_decode(file_get_contents($balanceFile), true);

  // Step 3: Only proceed if the balance is sufficient
  if ($balanceData['balance'] >= $amount) {
    $balanceData['balance'] -= $amount;
    file_put_contents($balanceFile, json_encode($balanceData, JSON_PRETTY_PRINT));

    // Step 4: Record the transaction log as a POST-based CSRF attack
    $recipient = "attacker@evil.com";
    $log = "💣 POST-based CSRF Transfer: {$amount} Euros to {$recipient} ｜Time: " . date("Y-m-d H:i:s") . PHP_EOL;
    file_put_contents("logs.txt", $log, FILE_APPEND);
    
    echo "Transfer successful: {$amount} Euros transferred to {$recipient}";
  } else {
    echo "Insufficient balance, transfer failed";
  }
} else {
  echo "Invalid amount";
}
?>
