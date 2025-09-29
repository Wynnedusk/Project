<?php
require_once __DIR__ . '/../email_attack/Gsession.php';

/* ---------------- CSRF token bootstrap ---------------- */
if (empty($_SESSION['def_csrf_token'])) {
    $_SESSION['def_csrf_token'] = bin2hex(random_bytes(32));
}

/* ---------------- Fallback defense: POST + same-origin + token ---------------- */
function def_require_csrf_and_origin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
    $scheme = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://');
    $host   = $scheme . $_SERVER['HTTP_HOST'];
    $origin = $_SERVER['HTTP_ORIGIN']  ?? '';
    $refer  = $_SERVER['HTTP_REFERER'] ?? '';
    if ($origin && strpos($origin, $host) !== 0) { http_response_code(403); exit('Bad Origin'); }
    if (!$origin && $refer && strpos($refer, $host) !== 0) { http_response_code(403); exit('Bad Referer'); }
    $site   = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    if ($site && !in_array($site, ['same-origin','same-site'])) { http_response_code(403); exit('Cross-site blocked'); }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!$token || !hash_equals($_SESSION['def_csrf_token'] ?? '', $token)) { http_response_code(403); exit('CSRF failed'); }
}

/* ---------------- JSON state for panel ---------------- */
if (isset($_GET['state'])) {
    header('Content-Type: application/json; no-cache');
    echo json_encode([
        'loggedIn'    => (bool)($_SESSION['i_loggedIn'] ?? false),
        'email'       => $_SESSION['i_user_email'] ?? 'user@example.com',
        'lastBlocked' => !empty($_SESSION['def_last_blocked']),
        'lastReason'  => $_SESSION['def_last_reason'] ?? null,
        'lastMeta'    => $_SESSION['def_last_meta'] ?? null,
    ]);
    exit;
}

/* ---------------- Actions ---------------- */
$action = $_GET['action'] ?? '';
if ($action === 'login') {
    $_SESSION['i_loggedIn']        = true;
    $_SESSION['i_user_email']      = 'student@ucc.ie';
    $_SESSION['i_loginArrowDrawn'] = false;
    $_SESSION['i_csrf_notice']     = false;
    $_SESSION['i_pending_email']   = null;
    $_SESSION['i_email_code']      = null;
    $_SESSION['def_last_blocked']  = false;
    $_SESSION['def_last_reason']   = null;
    $_SESSION['def_last_meta']     = null;
    header('Location: Demail_profile.php?step=2'); exit;
}
if ($action === 'reset') {
    $_SESSION['i_loggedIn']        = false;
    $_SESSION['i_user_email']      = 'user@example.com';
    $_SESSION['i_loginArrowDrawn'] = false;
    $_SESSION['i_csrf_notice']     = false;
    $_SESSION['i_pending_email']   = null;
    $_SESSION['i_email_code']      = null;
    $_SESSION['def_last_blocked']  = false;
    $_SESSION['def_last_reason']   = null;
    $_SESSION['def_last_meta']     = null;
    header('Location: Demail_profile.php'); exit;
}

/* ---------------- Email change flow ---------------- */
$infoMessage = '';
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    def_require_csrf_and_origin();

    if (isset($_POST['request_code'], $_POST['new_email'])) {
        $new = trim($_POST['new_email']);
        if ($new === '') {
            $errorMessage = 'Please enter a new email.';
        } else {
            $_SESSION['i_pending_email'] = $new;
            $_SESSION['i_email_code'] = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $mask = preg_replace('/(.).+(@.+)$/', '$1***$2', $_SESSION['i_user_email']);
            $infoMessage = "Verification code sent to current address: {$mask}";
        }
    }

    if (isset($_POST['confirm_change'], $_POST['code'])) {
        $code = trim($_POST['code']);
        if (!isset($_SESSION['i_email_code'])) {
            $errorMessage = 'No verification requested yet.';
        } elseif ($code !== $_SESSION['i_email_code']) {
            $errorMessage = 'Invalid verification code.';
        } else {
            $new = $_SESSION['i_pending_email'] ?? null;
            if ($new) {
                $_SESSION['i_user_email']  = $new;
                $infoMessage = 'Email updated successfully.';
            }
            $_SESSION['i_pending_email'] = null;
            $_SESSION['i_email_code']    = null;
        }
    }
}

$loggedIn   = (bool)($_SESSION['i_loggedIn'] ?? false);
$email      = $_SESSION['i_user_email'] ?? 'user@example.com';
$blocked    = !empty($_SESSION['def_last_blocked']);
$token      = $_SESSION['def_csrf_token']  ?? '';
$showProtected = ($loggedIn && $blocked);   // Only "logged in AND a block occurred" counts as Protected
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Social Profile (Defense)</title>
  <style>
    :root{--txt:#111;--muted:#666;--ok:#16a34a;--an:#6b7280;--bd:#e5e7eb;--card:#fafafa;--bg:#fcfcfc}
    *{box-sizing:border-box}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:24px 18px;color:var(--txt);background:var(--bg)}
    .title{display:flex;gap:10px;align-items:center;margin:0 0 8px 0}
    .statusRow{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .chipRow{display:flex;gap:10px;align-items:center;margin-left:auto}
    .chip{padding:8px 14px;border:1px solid var(--bd);background:#f3f4f6;border-radius:999px;pointer-events:none}
    .chip.ok{background:#eaffea;border-color:#86efac}
    .chip.prot{background:#dcfce7;border-color:#86efac}
    .badge{padding:4px 8px;border-radius:6px;color:#fff}
    .ok{background:var(--ok)} .an{background:var(--an)}

    .banner{border:1px solid #bbf7d0;background:#dcfce7;color:#166534;border-radius:10px;padding:10px;margin-bottom:12px}
    .warn{border-color:#fecaca;background:#fee2e2;color:#7f1d1d}

    .card{border:1px solid var(--bd);border-radius:12px;background:#fafafa;padding:16px}
    .layout{display:grid;grid-template-columns:360px 1fr;gap:18px}
    .email{font-weight:700}
    .muted{color:var(--muted)}
    .formgrid{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center}
    input[type="email"], input[type="text"]{width:100%;padding:8px 10px;border:1px solid var(--bd);border-radius:8px}
    .list{list-style:none;padding:0;margin:0}
    .list li{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #eee}
    .list li:last-child{border-bottom:none}
    details{border:1px solid var(--bd);background:#fff;border-radius:10px;margin-top:12px}
    details>summary{cursor:pointer;padding:10px 12px;font-weight:600}
    details .box{padding:10px 12px;border-top:1px dashed #eee}
    @media (max-width:980px){ .layout{grid-template-columns:1fr} .chipRow{margin-left:0} }
  </style>
</head>
<body>

<h2 class="title">Social Profile (Defense)</h2>

<div class="statusRow">
  <span>Status:</span>
  <span id="statusBadge" class="badge <?= $loggedIn ? 'ok' : 'an' ?>"><?= $loggedIn ? 'Logged in' : 'Anonymous' ?></span>
  <div class="chipRow">
    <span class="chip <?= $loggedIn ? 'ok':'' ?>">Profile</span>

    <span class="chip <?= $showProtected ? 'prot':'' ?>">Protected</span>
  </div>
</div>

<?php if ($showProtected): ?>
  <div class="banner">Attack attempt blocked — no state change on GET; CSRF/Origin checks enforced.</div>
<?php endif; ?>
<?php if ($infoMessage): ?><div class="banner"><?= htmlspecialchars($infoMessage) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="banner warn"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

<div class="layout">
  <aside>
    <div class="card" id="bindCard">
      <h3>Bound Email</h3>
      <p id="emailLabel" class="email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
      <p class="muted">Tip: This account uses POST + CSRF token for changes. Image GET requests cannot alter the email.</p>
    </div>

  </aside>

  <main>
    <div class="card">
      <h3>Change Email (requires code)</h3>
      <p class="muted">A verification code will be sent to the <b>current bound email</b>.</p>

      <form method="post" style="margin-top:10px">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
        <div class="formgrid">
          <input type="email" name="new_email" placeholder="Enter new email">
          <button class="btn" type="submit" name="request_code" value="1">Request code</button>
        </div>
      </form>

      <form method="post" style="margin-top:10px">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
        <div class="formgrid">
          <input type="text" name="code" placeholder="Enter 6-digit code">
          <button class="btn" type="submit" name="confirm_change" value="1">Confirm change</button>
        </div>
      </form>
    </div>

   

  </main>
</div>
</body>
</html>
