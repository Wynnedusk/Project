<?php
require_once __DIR__ . '/Hsession.php';

if (empty($_SESSION['i_loggedIn'])) {
  http_response_code(401);
  ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Following</title>
  <style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:24px;color:#334155}
  .card{max-width:560px;border:1px solid #e5e7eb;border-radius:12px;padding:16px;background:#fff}
  .muted{color:#64748b}</style></head>
  <body><div class="card"><h3 style="margin:0 0 8px 0;"> Please login</h3>
  <p class="muted" style="margin:0">You must sign in to view the following list.</p></div></body></html><?php
  exit;
}

$view = $_GET['view'] ?? '';
$ack  = isset($_GET['ack']); // whether arrived via the 'view'/'ack' flow
$highlight = isset($_GET['highlight']) ? trim($_GET['highlight']) : '';

$willClearOnce = false;
// If no explicit highlight is provided, use the one-time i_new_follow and mark it for clearing after use
if ($highlight === '' && !empty($_SESSION['i_new_follow'])) {
  $highlight = (string)$_SESSION['i_new_follow'];
  $willClearOnce = true;
}

if ($view === 'following') {
  $all = $_SESSION['i_follows'] ?? [];

  // Sort the list
  if (!empty($all)) {
    natcasesort($all);
    $all = array_values($all);
  }

  // Move the highlighted name to the front
  if ($highlight !== '') {
    $idx = null; $val = null;
    foreach ($all as $i => $v) {
      if (strcasecmp($v, $highlight) === 0) { $idx = $i; $val = $v; break; }
    }
    if ($idx !== null) { array_splice($all, $idx, 1); array_unshift($all, $val); }
  }

  if ($ack || $willClearOnce) {
    unset($_SESSION['i_flash'], $_SESSION['i_new_follow']);
  }

  $count = count($all);
  ?><!DOCTYPE html>
  <html lang="en"><head>
    <meta charset="UTF-8" />
    <title>Following · <?= $count ?></title>
    <style>
      :root{--bd:#e5e7eb;--muted:#64748b;--chip-bg:#fef3c7;--chip-bd:#fde68a;--chip-fg:#92400e}
      body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:#fff}
      .wrap{padding:16px}
      .card{border:1px solid var(--bd);border-radius:12px;padding:16px;background:#fff;max-width:560px}
      .muted{color:var(--muted)}
      ul{margin:8px 0 0 20px} li{padding:2px 0}
      .chip{display:inline-block;margin-left:6px;padding:2px 6px;border:1px solid var(--chip-bd);background:var(--chip-bg);color:var(--chip-fg);border-radius:8px;font-size:12px;line-height:1}
      a.btn{display:inline-block;margin-bottom:12px;padding:8px 12px;border:1px solid var(--bd);border-radius:8px;text-decoration:none;color:#111;background:#f8fafc}
    </style>
  </head><body>
    <div class="wrap">
      <div class="card">
        <!-- Move 'Back to Profile' to the top -->
        <a class="btn" href="H_profile.php">← Back to Profile</a>

        <h3 style="margin:0 0 6px 0;">Following (<?= $count ?>)</h3>
        <p class="muted">Names are not clickable. This list includes the newly added account.</p>
        <?php if(empty($all)): ?>
          <p class="muted">No followings yet.</p>
        <?php else: ?>
          <ul>
          <?php foreach($all as $name): ?>
            <li>
              <?= htmlspecialchars($name) ?>
              <?php if ($highlight !== '' && strcasecmp($name,$highlight)===0): ?>
                <span class="chip">new</span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </body></html><?php
  exit;
}

http_response_code(400);
echo 'Bad request.';
