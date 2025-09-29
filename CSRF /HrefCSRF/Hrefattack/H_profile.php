<?php
require_once __DIR__ . '/Hsession.php';

// [FIX] normalize session containers to avoid "Array to string conversion"
if (!isset($_SESSION['i_follows']) || !is_array($_SESSION['i_follows'])) {
    $_SESSION['i_follows'] = [];
}
if (!isset($_SESSION['i_followers']) || !is_array($_SESSION['i_followers'])) {
    $_SESSION['i_followers'] = [];
}

/* -------- State endpoint for the panel -------- */
if (isset($_GET['state'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'loggedIn' => (bool)($_SESSION['i_loggedIn'] ?? false),
        'attacked' => (bool)($_SESSION['i_attacked'] ?? false),
        'ts'       => time(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------- Reset endpoint (JSON), keep login session -------- */
if (isset($_GET['reset'])) {
    // 1) flags
    $_SESSION['i_attacked'] = false;
    $_SESSION['i_blocked']  = false;

    // 2) remove "attacker" from following (use old-style anonymous function for older PHP)
    if (!empty($_SESSION['i_follows']) && is_array($_SESSION['i_follows'])) {
        $_SESSION['i_follows'] = array_values(array_filter(
            $_SESSION['i_follows'],
            function ($n) { return strcasecmp((string)$n, 'attacker') !== 0; }
        ));
    }

    // 3) clear one-off notices
    unset($_SESSION['i_flash'], $_SESSION['i_new_follow']);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit;
}

/* -------- dismiss flash once (kept consistent with your link) -------- */
// [FIX] provide handler for ?dismiss_flash=1 used by the page link
if (isset($_GET['dismiss_flash'])) {
    unset($_SESSION['i_flash']);
    header('Location: H_profile.php');
    exit;
}

/* -------- View data prep -------- */
// helper: safe string
function sstr($v, $fallback = '') {
    if (!isset($v)) return $fallback;
    if (is_string($v)) return $v;
    if (is_numeric($v)) return (string)$v;
    // anything else -> fallback to avoid "Array to string conversion"
    return $fallback;
}

$isLoggedIn = !empty($_SESSION['i_loggedIn']);
$follows    = (isset($_SESSION['i_follows']) && is_array($_SESSION['i_follows'])) ? $_SESSION['i_follows'] : [];
$followersC = (int)($_SESSION['i_followers_count'] ?? 0);
$followC    = count($follows);

// sanitize name/email to avoid notices on old server
$userName  = sstr($_SESSION['i_user_name']  ?? null, 'student');
$userEmail = sstr($_SESSION['i_user_email'] ?? null, 'student@ucc.ie');

// params for highlighting
$new   = isset($_GET['new'])   ? trim((string)$_GET['new'])   : '';
$added = isset($_GET['added']) ? (int)$_GET['added']          : 0;

// do not auto-consume flash (keep until user views/closes)
$flash = isset($_SESSION['i_flash']) ? $_SESSION['i_flash'] : null;

// compute auto highlight (keep your rule)
$autoHi = '';
if ($flash && ($flash['type'] ?? '') === 'follow_ok') {
    $autoHi = (string)$flash['who'];
} elseif ($new !== '') {
    $autoHi = $new;
} elseif (!empty($_SESSION['i_attacked']) && in_array('attacker', $follows, true)) {
    $autoHi = 'attacker';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Victim Profile (Vulnerable)</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
<style>
  :root{--bd:#e5e7eb;--muted:#64748b;--ok:#16a34a;--danger:#b91c1c;--note-bg:#ecfdf5;--note-bd:#a7f3d0;--note-fg:#065f46}
  body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:#fff}
  .topnav{display:flex;justify-content:flex-end;gap:12px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--bd)}
  .topnav a{color:#111;text-decoration:none;border:1px solid #cbd5e1;border-radius:8px;padding:6px 10px;background:#f8fafc}
  .wrap{padding:14px}
  .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid var(--bd);background:#f8fafc}
  .ok{color:var(--ok)} .danger{color:var(--danger)} .muted{color:var(--muted)}
  .head{display:flex;align-items:center;gap:8px;margin-top:14px}
  .pill-link{display:inline-block;padding:2px 8px;border:1px solid var(--bd);border-radius:999px;background:#f8fafc;font-size:12px;text-decoration:none;color:#111;margin-left:6px}
  .pill-link:hover{box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .note{display:inline-flex;align-items:center;gap:10px;padding:6px 10px;border:1px solid var(--note-bd);background:#ecfdf5;color:#065f46;border-radius:8px;font-size:14px;margin-left:8px}
  .note a{color:#065f46;text-decoration:underline}
  .note .close{display:inline-block;border:1px solid #a7f3d0;background:#fff;border-radius:6px;padding:0 6px;line-height:20px;text-decoration:none;color:#065f46}
  hr{border:none;border-top:1px solid var(--bd);margin:12px 0}
  .card{border:1px solid var(--bd);border-radius:12px;padding:14px;background:#fff}
</style>
</head>
<body>
<div class="topnav">
  <?php if(!empty($_SESSION['i_loggedIn'])): ?>
    <span class="badge ok">Logged in</span>
    <a href="Hlogout.php?back=<?= urlencode('H_profile.php?step=1') ?>">Logout</a>
  <?php else: ?>
    <span class="badge">Guest</span>
    <a id="loginLink" href="Hlogin.php?back=<?= urlencode('H_profile.php?step=2') ?>">Login</a>
  <?php endif; ?>
</div>

<div class="wrap">
  <div style="margin-bottom:10px;">
    Status:
    <?php if($isLoggedIn): ?><span class="badge ok">Logged in</span><?php else: ?><span class="badge">Guest</span><?php endif; ?>
    &nbsp;|&nbsp; Attack:
    <?php if(!empty($_SESSION['i_attacked'])): ?><span class="badge danger">Attacked</span><?php else: ?><span class="badge">Idle</span><?php endif; ?>
  </div>

  <?php if(!$isLoggedIn): ?>
    <div class="card" role="region" aria-label="Login notice">
      <h2 class="title" style="margin:0 0 6px 0;">Please login</h2>
      <p class="muted" style="margin:0; font-size:20px; line-height:1.6;">
        You need to sign in to view profile, following and followers.
      </p>
    </div>
    <p class="looseTip">
      Tip: Use the <b>Login</b> button in the top bar first, then click the link on the attacker page.
    </p>

  <?php else: ?>
    <h3> Victim: <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h3>
    <p>Email: <?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></p>

    <div class="head">
      <h4 style="margin:0;">Following</h4>
      <a class="pill-link"
         href="H_user.php?view=following<?= $autoHi ? '&highlight='.urlencode($autoHi) : '' ?>&ack=1"
         title="View following list"><?= (int)$followC ?></a>

      <?php
        if ($flash && ($flash['type'] ?? '') === 'follow_ok') {
          $who = htmlspecialchars((string)$flash['who'], ENT_QUOTES, 'UTF-8');
          echo '<span class="note">Followed +1，<a href="H_user.php?view=following&highlight=' . $who . '&ack=1">view</a>'
             . '<a class="close" href="H_profile.php?dismiss_flash=1" title="不再提示">×</a></span>';
        } elseif ($autoHi && $added === 1) {
          echo '<span class="note">新Followed +1，<a href="H_user.php?view=following&highlight='
             . htmlspecialchars($autoHi, ENT_QUOTES, 'UTF-8') . '">view</a></span>';
        }
      ?>
    </div>

    <div class="head">
      <h4 style="margin:0;">Followers</h4>
      <span class="pill-link" style="pointer-events:none;cursor:default"><?= (int)$followersC ?></span>
    </div>

    <hr>
    <p class="muted">Tip: Use the <b>Login</b> button in the top bar first, then click the link on the attacker page.</p>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
<script>
  window.addEventListener('message', function(e){
    if (!e || !e.data) return;
    if (e.data.type === 'IFRAME_GUIDE_STEP1') startStep1();
  });
  function startStep1(){
    var login = document.getElementById('loginLink');
    var badge = document.getElementById('authBadge');
    var target = login || badge;
    var text   = login ? '点击这里登录（Guest 旁边）。' : '你已登录。若为 Guest，这里会出现 “Login” 按钮。';
    if (!target){
      try{ parent.postMessage({type:'IFRAME_STEP1_SKIPPED'}, '*'); }catch(_){}
      return;
    }
    introJs().setOptions({
      nextLabel:'Next', doneLabel:'Next', prevLabel:'Back',
      exitOnOverlayClick:false, scrollToElement:true,
      steps: [{ element: target, intro: text, position: 'left' }]
    })
    .oncomplete(function(){ try { parent.postMessage({type:'IFRAME_STEP1_DONE'}, '*'); } catch(_){}})
    .onexit(function(){ try { parent.postMessage({type:'IFRAME_STEP1_DONE'}, '*'); } catch(_){}})
    .start();
  }
</script>
</body>
</html>
