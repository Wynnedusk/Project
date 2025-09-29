<?php
// secure-pay.php (defense backend)
session_start();

// 1) User must be logged in
if (!isset($_SESSION['email'])) {
    http_response_code(403);
    exit("Access denied: not logged in.");
}

// 2) Only accept POST and verify CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Nothing changed");
}
$posted_token  = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';
if ($posted_token === '' || $session_token === '' || !hash_equals((string)$session_token, (string)$posted_token)) {
    http_response_code(403);
    exit("Invalid CSRF token.");
}

// 3) Require recipient parameter
$recipient_raw = trim((string)($_POST['recipient'] ?? ''));
if ($recipient_raw === '') {
    http_response_code(400);
    exit("Missing recipient.");
}

// 4) Validate recipient: accept email-like strings (for demo purposes)
//    If using account IDs instead of emails, adjust this validation logic accordingly.
if (!filter_var($recipient_raw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit("Invalid recipient format.");
}

// 5) Get amount
$amount = intval($_POST['money'] ?? 0);
if ($amount <= 0) {
    http_response_code(400);
    exit("Invalid amount");
}

// 6) Read and update balance
$balanceFile = "../server/balance.json";
$balanceData = json_decode(file_get_contents($balanceFile), true);
$balance     = $balanceData["balance"] ?? 0;
if ($balance < $amount) {
    exit("Insufficient balance, transfer failed");
}
$balance -= $amount;
$balanceData["balance"] = $balance;
file_put_contents($balanceFile, json_encode($balanceData, JSON_PRETTY_PRINT));

// 7) Log transfer including recipient (sanitize for logs)
$recipient_safe = htmlspecialchars($recipient_raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$log = "[" . date("Y-m-d H:i:s") . "] User Transfer: -{$amount} to {$recipient_safe} by " . ($_SESSION['email'] ?? 'unknown') . PHP_EOL;
file_put_contents("../server/logs.txt", $log, FILE_APPEND);

// 8) Redirect back to dashboard/home
header("Location: ../server/home.html");
exit();
