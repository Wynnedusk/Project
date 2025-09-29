<?php
require_once __DIR__ . '/../Hrefattack/Hsession.php';

/* Disable caching to avoid browsers reusing stale DOM */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ---- normalize containers ----
if (!isset($_SESSION['i_follows']) || !is_array($_SESSION['i_follows'])) {
  $_SESSION['i_follows'] = [];
}
if (!isset($_SESSION['i_followers']) || !is_array($_SESSION['i_followers'])) {
  $_SESSION['i_followers'] = [];
}

/* Lightweight state endpoint (panel polls token/status) */
if (isset($_GET['state'])) {
  $resp = [
    'loggedIn' => (bool)($_SESSION['i_loggedIn'] ?? false),
    'blocked'  => (bool)($_SESSION['i_blocked']  ?? false),
  ];
  if (!empty($_SESSION['i_loggedIn'])) $resp['token'] = $_SESSION['i_csrf_token'] ?? '';
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($resp, JSON_UNESCAPED_UNICODE);
  exit;
}

/* ★ Reset endpoint: wipe all "attacked" traces (used by the panel Reset) */
if (isset($_GET['reset'])) {
  // 1) Clear status flags
  $_SESSION['i_attacked'] = false;
  $_SESSION['i_blocked']  = false;

  // 2) Remove "attacker" (legacy-compatible closure for PHP 7.3-)
  if (!empty($_SESSION['i_follows']) && is_array($_SESSION['i_follows'])) {
    $_SESSION['i_follows'] = array_values(array_filter(
      $_SESSION['i_follows'],
      function ($v) { return strcasecmp((string)$v, 'attacker') !== 0; }
    ));
  }

  // 3) Clear one-time flashes/highlights and reset CSRF token
  unset($_SESSION['i_flash'], $_SESSION['i_new_follow']);
  $_SESSION['i_csrf_token'] = bin2hex(random_bytes(16));

  // 4) If "redirect" is present, 302 to a clean page (for iframe navigation)
  if (isset($_GET['redirect'])) {
    header('Location: D_profile.php?step=1&_v=' . time(), true, 302);
    exit;
  }
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
  exit;
}

/* Main view */
$logged   = !empty($_SESSION['i_loggedIn']);
$blocked  = !empty($_SESSION['i_blocked']); // ← becomes false after Reset
$token    = $_SESSION['i_csrf_token'] ?? '';

$follows        = $_SESSION['i_follows'] ?? [];
$countFollowing = count($follows);
$countFollowers = (int)($_SESSION['i_followers_count'] ?? 15);

/* One-time flash message (set after a successful secure form submit) */
$flash = $_SESSION['i_flash'] ?? null;
unset($_SESSION['i_flash']);

$view      = $_GET['view'] ?? 'profile';
$highlight = $_GET['highlight'] ?? '';

/* Pin "attacker" to the top of the list (for clearer demonstration) */
if (in_array('attacker', $follows, true)) {
  $follows = array_values(array_unique(array_merge(['attacker'], array_diff($follows, ['attacker']))));
}

/* User display values */
$userName  = htmlspecialchars($_SESSION['i_user_name']  ?? 'student', ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($_SESSION['i_user_email'] ?? 'student@ucc.ie', ENT_QUOTES, 'UTF-8');

/* Login return URL helpers */
function current_url_abs() {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $scheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}
$hereBackAbs   = current_url_abs();
$backAfterLogin= rawurlencode('/HrefCSRF/Hrefdefense/D_profile.php?step=2&_v='.time());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Victim Profile (Secure)</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  :root{
    --bd:#e5e7eb; --muted:#64748b; --ink:#0f172a;
    --ok:#16a34a; --danger:#b91c1c;
    --note-bg:#ecfdf5; --note-bd:#a7f3d0; --note-fg:#065f46;
    --tips-bg:#ecfeff; --tips-bd:#a5f3fc; --tips-fg:#0e7490;
    --pill-bg:#f8fafc; --card:#fff;
  }
  *{box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:#fff;color:var(--ink)}
  .topnav{display:flex;justify-content:flex-end;gap:12px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--bd)}
  .topnav a{color:#111;text-decoration:none;border:1px solid #cbd5e1;border-radius:8px;padding:6px 10px;background:var(--pill-bg)}
  .wrap{padding:14px; max-width:960px; margin:0 auto}
  .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid var(--bd);background:var(--pill-bg)}
  .ok{color:var(--ok)} .danger{color:var(--danger)} .muted{color:var(--muted)}
  h2.title{margin:10px 0 12px 0; font-size:28px; font-weight:800; letter-spacing:.2px}
  h3{margin:10px 0}
  .row{display:flex;gap:8px;align-items:center;margin:8px 0}
  .pill{display:inline-block;padding:2px 8px;border:1px solid var(--bd);border-radius:999px;background:var(--pill-bg);font-size:12px}
  .pill-link{display:inline-block;padding:2px 8px;border:1px solid var(--bd);border-radius:999px;background:var(--pill-bg);font-size:12px;color:#111;text-decoration:none}
  .pill-link:hover{box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .note{display:block;padding:8px 10px;border:1px solid var(--note-bd);background:var(--note-bg);color:var(--note-fg);border-radius:10px;margin:10px 0}
  .tips{background:var(--tips-bg);border:1px solid var(--tips-bd);color:var(--tips-fg);border-radius:10px;padding:12px;margin:12px 0}
  .tips h4{margin:0 0 8px 0}
  .tips ul{margin:6px 16px}
  .formBox{border:1px dashed var(--bd);border-radius:12px;padding:12px;background:#fafafa;margin-top:12px}
  .row-form{display:flex;gap:8px;align-items:center;margin:8px 0}
  input[type=text]{flex:1;border:1px solid var(--bd);border-radius:8px;padding:8px}
  button.primary{background:#2563eb;color:#fff;border:1px solid #1d4ed8;border-radius:8px;padding:8px 12px;cursor:pointer}
  .card{border:1px solid var(--bd);border-radius:16px;padding:20px;background:var(--card);margin:12px 0}
  .hidden{display:none}
  ul.list{margin:8px 0 0 22px}
  .tag-new{display:inline-block;margin-left:6px;padding:0 6px;border-radius:6px;font-size:11px;background:#fef3c7;color:#92400e;border:1px solid #fde68a}
  .back{margin-top:12px}
  .looseTip{font-size:18px; line-height:1.6; color:var(--muted); margin:12px 0 0 0}
</style>
</head>
<body>
<div class="topnav">
  <?php if ($logged): ?>
    <span class="badge ok">Logged in</span>
    <a href="/HrefCSRF/Hrefattack/Hlogout.php?back=<?= rawurlencode($hereBackAbs) ?>">Logout</a>
  <?php else: ?>
    <span class="badge">Guest</span>
    <a href="/HrefCSRF/Hrefattack/Hlogin.php?back=<?= $backAfterLogin ?>">Login</a>
  <?php endif; ?>
</div>

<div class="wrap" aria-live="polite">
  <div style="margin-bottom:10px;">
    Status:
    <span class="badge <?= $logged?'ok':'' ?>"><?= $logged ? 'Logged in' : 'Guest' ?></span>
    &nbsp;|&nbsp; Attack:
    <span class="badge <?= $blocked?'danger':'' ?>" id="attackBadge"><?= $blocked ? 'Blocked' : 'Idle' ?></span>
  </div>

<?php if(!$logged): ?>
  <div class="card" role="region" aria-label="Login notice">
    <h2 class="title" style="margin:0 0 6px 0;">Please login</h2>
    <p class="muted" style="margin:0; font-size:20px; line-height:1.6;">
      You need to sign in to view profile, following and followers.
    </p>
  </div>
  <p class="looseTip">Tip: Use the <b>Login</b> button in the top bar first, then click the link on the attacker page.</p>

<?php else: ?>

<?php if (($view ?? 'profile') === 'following'): ?>
  <h2 class="title">Victim Profile (Secure)</h2>
  <ul class="list">
    <?php foreach ($follows as $name): ?>
      <li>
        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($highlight && $name === $highlight): ?>
          <span class="tag-new">new</span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="back"><a href="D_profile.php">&larr; Back to Profile</a></p>

<?php else: ?>

  <h3>Victim: <?= $userName ?></h3>
  <p>Email: <?= $userEmail ?></p>

  <div class="row" style="margin-top:8px;">
    <strong>Following</strong>
    <a class="pill-link"
       href="D_user.php?view=following<?= in_array('attacker', $follows, true) ? '&highlight=attacker' : '' ?>"
       title="View following list"><?= (int)$countFollowing ?></a>
    &nbsp;&nbsp;<strong>Followers</strong> <span class="pill"><?= (int)$countFollowers ?></span>
  </div>

  <?php if ($flash && ($flash['type'] ?? '') === 'follow_ok'): ?>
    <div class="note">
      Followed +1, <a href="D_profile.php?view=following&highlight=<?= urlencode($flash['who']) ?>">view</a>
    </div>
  <?php endif; ?>

  <div class="tips">
    <h4>Teaching Tips</h4>
    <ul>
      <li>This page enables <b>CSRF Token</b> protection and validates <b>Origin/Referer</b>.</li>
      <li>Each time the form loads, a random token is generated and placed in a hidden field.</li>
      <li>On submit, the server verifies the token and the request origin. Attack pages cannot obtain the token, so the request is rejected.</li>
    </ul>
    <p class="muted" style="margin:8px 0 0 0;">Tip: Use the secure form (shown after a blocked attempt) to follow someone. GET hyperlinks from other origins are rejected.</p>
  </div>

  <!-- Visible only after a block occurs -->
  <div id="blockedNote" class="note <?= $blocked ? '' : 'hidden' ?>">Blocked: a cross-origin <b>GET</b> follow attempt.</div>

  <div id="safeBox" class="formBox <?= $blocked ? '' : 'hidden' ?>" role="region" aria-label="Secure follow form">
    <div class="muted" style="margin-bottom:6px;">Safe method: submit via <b>POST + CSRF Token</b>.</div>
    <form method="POST" action="D_follower.php">
      <div class="row-form">
        <label for="user" class="muted">Account to follow:</label>
        <input id="user" name="user" type="text" value="attacker" />
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <button class="primary" type="submit">Follow safely</button>
      </div>
      <div class="muted" style="font-size:12px">Token: <?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?></div>
    </form>
  </div>

<?php endif; ?>
<?php endif; ?>
</div>

<script>
(function(){
  // Toggle UI parts when a block is reported by the attacker panel
  const note  = document.getElementById('blockedNote');
  const box   = document.getElementById('safeBox');
  const badge = document.getElementById('attackBadge');

  function showBlocked(){
    if (note)  note.classList.remove('hidden');
    if (box)   box.classList.remove('hidden');
    if (badge) { badge.textContent = 'Blocked'; badge.classList.add('danger'); }
  }

  // Listen for a cross-document message to reveal "blocked" state (posted by the demo panel)
  window.addEventListener('message', (e)=>{
    const d = e.data || {};
    if (d.type === 'ATTACK_BLOCKED') showBlocked();
  }, false);
})();
</script>
</body>
</html>
