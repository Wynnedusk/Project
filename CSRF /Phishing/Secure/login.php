<?php
// /Phishing/Secure/login.php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: login.php');
  exit;
}

// ====== Demo account (to make "correct vs. wrong credentials" realistic) ======
const VALID_EMAIL = 'student@demo.local';
const VALID_PASS  = 'demo123';

// Regular form login (manual login on the real site, left panel)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $pwd   = $_POST['password'] ?? '';
  if ($email === VALID_EMAIL && $pwd === VALID_PASS) {
    $_SESSION['loggedIn'] = true;
    $_SESSION['email']    = $email;
    $_SESSION['last_login_at'] = date('c');
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'Invalid credentials';
  }
}

$isLoggedIn = !empty($_SESSION['loggedIn']);
$me  = $_SESSION['email'] ?? null;
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['logout'])) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Secure Portal — Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:root{--border:#e5e7eb;--ok:#16a34a;--muted:#64748b;--blue:#2563eb;--bg:#f5f7fb}
*{box-sizing:border-box} body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:var(--bg)}
.container{max-width:680px;margin:40px auto;padding:0 16px}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:22px}
.h{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.badge{border:1px solid var(--border);border-radius:999px;padding:4px 8px;color:#065f46;background:#ecfeff}
.label{display:block;margin:10px 0 6px;color:#111827}
.inp{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px}
.btn{display:inline-block;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer}
a.btn-link{display:inline-block;margin-top:10px;color:#1d4ed8;text-decoration:none}
.ok{color:var(--ok);font-weight:700}
.err{color:#b91c1c}
.note{color:#64748b;font-size:13px;margin-top:8px}
.center{display:flex;justify-content:center;margin-top:16px}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="h">
      <h2 style="margin:0">Secure Portal — Login</h2>
      <span class="badge">host: <?=htmlspecialchars($host)?></span>
    </div>

<?php if ($isLoggedIn): ?>
    <p class="ok">Logged in as <b><?=htmlspecialchars($me)?></b></p>
    <p><a class="btn-link" href="dashboard.php">Go to Dashboard</a></p>
    <div class="center"><a class="btn" href="?logout=1">Log out</a></div>

<?php else: ?>
    <?php if ($error): ?><p class="err"><?=htmlspecialchars($error)?></p><?php endif; ?>
    <form method="post" action="login.php" autocomplete="off">
      <label class="label">Email</label>
      <input class="inp" type="email" name="email" 
             placeholder="Enter your email" 
             autocomplete="off" 
             autocapitalize="off"
             autocorrect="off"
             value=""
             required />

      <label class="label">Password</label>
      <input class="inp" type="password" name="password" 
             placeholder="Enter your password" 
             autocomplete="new-password"
             value=""
             required />

      <div class="center" style="margin-top:16px"><button class="btn" type="submit">Login</button></div>
      <p class="note">Demo account: <code><?=VALID_EMAIL?></code> / <code><?=VALID_PASS?></code></p>
      <p class="note">This page accepts <code>postMessage(DEMO_LOGIN)</code> from the panel for teaching purposes.</p>
    </form>
<?php endif; ?>
  </div>
</div>

<script>
/**
 * Real-site login page:
 * - Supports normal human submission (manual login succeeds with the demo account).
 * - Also listens for DEMO_LOGIN from the parent panel (teaching: credential reuse after theft).
 *   Defense version: if the message is marked defense=true, immediately reject and
 *   send the result back to the parent panel.
 */
(function () {
  const form   = document.querySelector('form');
  const emailI = document.querySelector('input[name="email"]');
  const passI  = document.querySelector('input[name="password"]');

  // Clear any autofill on page load
  window.addEventListener('load', function() {
    if (emailI) emailI.value = '';
    if (passI) passI.value = '';
  });

  // Normal user submission (example only; keep existing behavior unchanged)
  form?.addEventListener('submit', async (e) => {
    // Intentionally left blank for the demo
  });

  // Listen for messages from the parent panel: P/D_panel will postMessage({type:'DEMO_LOGIN', ...})
  window.addEventListener('message', async (ev) => {
    const d = ev.data || {};
    if (d.type !== 'DEMO_LOGIN') return;

    // Defense: any reuse attempt flagged as defense=true is rejected
    if (d.defense) {
      try {
        window.parent?.postMessage({
          type: 'REUSE_RESULT',
          ok: false,
          reason: 'blocked_by_defense'
        }, '*');
      } catch (e) {}
      return;
    }

    // Attack demo only: allow auto login attempt (used by the attack panel)
    try {
      const body = new URLSearchParams({
        email: d.email || '',
        password: d.password || '',
        from: 'panel'   // teaching marker
      });

      const res  = await fetch('api_login.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body
      });
      const json = await res.json().catch(()=>({success:false}));

      // Send the result back to the parent panel (both P_panel / D_panel will handle it)
      window.parent?.postMessage({
        type: 'REUSE_RESULT',
        ok: !!json.success,
        reason: json.success ? 'ok' : 'bad_creds'
      }, '*');

      // On success, navigate to dashboard (attack demo path)
      if (json.success) location.href = 'dashboard.php';
    } catch (e) {
      window.parent?.postMessage({
        type: 'REUSE_RESULT',
        ok: false,
        reason: 'network_error'
      }, '*');
    }
  }, false);
})();
</script>

<!-- ===== BEGIN: Teaching tips (defense) — append-only ===== -->
<style>
  .teach-defense details{margin:12px auto;max-width:680px;border:1px solid #e5e7eb;border-radius:8px;background:#fff}
  .teach-defense details>summary{padding:8px 12px;cursor:pointer;font-weight:600;list-style:none}
  .teach-defense details>summary::-webkit-details-marker{display:none}
  .teach-defense .box{padding:8px 12px;border-top:1px solid #e5e7eb;background:#fafafa;
    font-size:14px;line-height:1.55;color:#0f172a;max-height:240px;overflow:auto}
  .teach-defense pre{margin:0;line-height:1.45;white-space:pre-wrap;word-break:break-word}
  .teach-defense .muted{color:#64748b}
</style>



</body>
</html>