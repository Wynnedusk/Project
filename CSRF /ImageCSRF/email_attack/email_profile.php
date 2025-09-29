<?php
require_once __DIR__ . '/Gsession.php';

/* ---------- JSON state ---------- */
if (isset($_GET['state'])) {
    header('Content-Type: application/json; no-cache');
    echo json_encode([
        'loggedIn' => (bool)($_SESSION['i_loggedIn'] ?? false),
        'email'    => $_SESSION['i_user_email'] ?? 'user@example.com',
        'attacked' => ($_SESSION['i_attack_status'] ?? 'idle') === 'attacked',
        'banner'   => !empty($_SESSION['i_csrf_notice'])
    ]);
    exit;
}

/* ---------- Actions ---------- */
$action = $_GET['action'] ?? '';
if ($action === 'login') {
    $_SESSION['i_loggedIn']        = true;
    $_SESSION['i_user_email']      = 'student@ucc.ie';
    $_SESSION['i_loginArrowDrawn'] = false;
    $_SESSION['i_csrf_notice']     = false;
    $_SESSION['i_pending_email']   = null;
    $_SESSION['i_email_code']      = null;
    header('Location: email_profile.php?step=2'); exit;
}
if ($action === 'logout') {
    $_SESSION['i_loggedIn']        = false;
    $_SESSION['i_loginArrowDrawn'] = false;
    header('Location: email_profile.php?step=1'); exit;
}
if ($action === 'reset') {
    $_SESSION['i_loggedIn']        = false;
    $_SESSION['i_user_email']      = 'user@example.com';
    $_SESSION['i_loginArrowDrawn'] = false;
    $_SESSION['i_drawRedArrow']    = false;
    $_SESSION['i_csrf_notice']     = false;
    $_SESSION['i_pending_email']   = null;
    $_SESSION['i_email_code']      = null;
    $_SESSION['i_attack_status']   = 'idle';
    header('Location: email_profile.php'); exit;
}

/* ---------- Email change flow ---------- */
$infoMessage = '';
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $_SESSION['i_csrf_notice'] = false;
            }
            $_SESSION['i_pending_email'] = null;
            $_SESSION['i_email_code']    = null;
            $infoMessage = 'Email updated successfully.';
        }
    }
}

$loggedIn = (bool)($_SESSION['i_loggedIn'] ?? false);
$email    = $_SESSION['i_user_email'] ?? 'user@example.com';
$attacked = (($_SESSION['i_attack_status'] ?? 'idle') === 'attacked') || !empty($_SESSION['i_csrf_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Social Profile (Victim)</title>
  <style>
    :root{--txt:#111;--muted:#666;--ok:#16a34a;--an:#b91c1c;--bd:#e5e7eb;--card:#fafafa;--bg:#fcfcfc}
    *{box-sizing:border-box}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:24px 18px;color:var(--txt);background:var(--bg)}
    .title{display:flex;gap:10px;align-items:center;margin:0 0 8px 0}
    .statusRow{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .chipRow{display:flex;gap:10px;align-items:center;margin-left:auto}
    .chip{padding:8px 14px;border:1px solid var(--bd);background:#f3f4f6;border-radius:999px;pointer-events:none}
    .chip.ok{background:#eaffea;border-color:#86efac}
    .chip.attacked{background:#fee2e2;border-color:#fca5a5}
    .tinyArrow{display:inline-block;font-weight:700}
    .tinyArrow.red{color:#b91c1c}
    .badge{padding:4px 8px;border-radius:6px;color:#fff}
    .ok{background:var(--ok)} .an{background:var(--an)}
    .btn{padding:8px 12px;border:1px solid var(--bd);background:#fff;border-radius:8px;cursor:pointer;text-decoration:none;color:#111;display:inline-block}
    .layout{display:grid;grid-template-columns:340px 1fr;gap:18px}
    .card{border:1px solid var(--bd);border-radius:12px;background:#fafafa;padding:16px}
    .email{font-weight:700}
    .muted{color:var(--muted)}
    .banner{border:1px solid #fecaca;background:#fee2e2;color:#7f1d1d;border-radius:10px;padding:10px;margin-bottom:12px}
    .success{border-color:#bbf7d0;background:#dcfce7;color:#166534}
    .formgrid{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center}
    input[type="email"], input[type="text"]{width:100%;padding:8px 10px;border:1px solid var(--bd);border-radius:8px}
    .list{list-style:none;padding:0;margin:0}
    .list li{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #eee}
    .list li:last-child{border-bottom:none}
    @media (max-width:980px){ .layout{grid-template-columns:1fr} .chipRow{margin-left:0} }
  </style>
</head>
<body>

<h2 class="title">Social Profile (Victim)</h2>

<div class="statusRow">
  <span>Status:</span>
  <span id="statusBadge" class="badge <?= $loggedIn ? 'ok' : 'an' ?>"><?= $loggedIn ? 'Logged in' : 'Anonymous' ?></span>
  <div class="chipRow">
    <span id="statusProfileChip" class="chip <?= $loggedIn ? 'ok':'' ?>">Profile</span>
    <span id="tinyRedArrow" class="tinyArrow red" style="<?= $attacked ? '' : 'display:none' ?>">←</span>
    <span id="attackChip" class="chip <?= $attacked ? 'attacked':'' ?>"><?= $attacked ? 'Attacked' : 'Attack' ?></span>
  </div>
</div>


<div class="layout">
  <aside>
    <div class="card" id="bindCard">
      <h3>Bound Email</h3>
      
      <p id="emailLabel" class="email"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
      <?php if (!empty($_SESSION['i_csrf_notice'])): ?>
  <div class="banner">Email changed successfully!</div>
<?php endif; ?>
<?php if ($infoMessage): ?><div class="banner success"><?= htmlspecialchars($infoMessage) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="banner"><?= htmlspecialchars($errorMessage) ?></div><?php endif; ?>

      <p class="muted">Your account is currently bound to the email above.</p>
    </div>
  </aside>

  <main>
    <div class="card">
      <h3>Change Email (requires code)</h3>
      <p class="muted">A verification code will be sent to the <b>current bound email</b>. If you have recently changed your e-mail address, please try again in three days.</p>

      <form method="post" style="margin-top:10px">
        <div class="formgrid">
          <input type="email" name="new_email" placeholder="Enter new email">
          <button class="btn" type="submit" name="request_code" value="1">Request code</button>
        </div>
      </form>

      <form method="post" style="margin-top:10px">
        <div class="formgrid">
          <input type="text" name="code" placeholder="Enter 6-digit code">
          <button class="btn" type="submit" name="confirm_change" value="1">Confirm change</button>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
/* Broadcast status only once (prevent reload from parent causing scroll issues) */
(function(){
  const attackedNow = <?= $attacked ? 'true' : 'false' ?>;
  const statusValue = attackedNow ? 'attacked' : 'idle';
  try { window.parent?.postMessage({type:'ATTACK_STATUS', value:statusValue}, '*'); } catch(e){}
})();
</script>
</body>
</html>
