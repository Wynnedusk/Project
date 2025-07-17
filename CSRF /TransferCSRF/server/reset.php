<?php
// Reset balance to 10000
file_put_contents("balance.json", json_encode(["balance" => 10000], JSON_PRETTY_PRINT));

// Clear logs
file_put_contents("logs.txt", "");

// Return message
echo "Account balance and log records have been reset.";
?>
