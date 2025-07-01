<?php
session_start();

// Step 1: Check if the user is logged in (via session)
if (!isset($_SESSION['email'])) {
    die("Access denied: not logged in.");
}

// Step 2: Handle only POST requests with valid CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify that the CSRF token matches the one stored in the session
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Step 3: Get the transfer amount and validate it
    $amount = intval($_POST['money']);
    if ($amount <= 0) die("Invalid amount");

    // Step 4: Read the current balance
    $balanceFile = "../server/balance.json";
    $balanceData = json_decode(file_get_contents($balanceFile), true);
    $balance = $balanceData["balance"] ?? 0;

    // Step 5: Subtract the amount and save new balance
    $balance -= $amount;
    $balanceData["balance"] = $balance;
    file_put_contents($balanceFile, json_encode($balanceData, JSON_PRETTY_PRINT));

    // Step 6: Record the transaction as a valid user action
    $log = "[" . date("Y-m-d H:i:s") . "] ✅ User Transfer: -" . $amount . " by " . $_SESSION['email'] . PHP_EOL;
    file_put_contents("../server/logs.txt", $log, FILE_APPEND);

    // Step 7: Redirect back to the main dashboard
    header("Location: ../server/home.html");
    exit();
}
?>
