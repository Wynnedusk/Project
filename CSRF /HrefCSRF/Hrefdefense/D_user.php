<?php
// D_user.php — defense version (unauth view matches H_user look; tip not boxed)
require_once __DIR__ . '/../Hrefattack/Hsession.php';

/* ───── Unauthenticated: output the same UI as the H version; the Tip stays outside the card ───── */
if (empty($_SESSION['i_loggedIn'])) {
  http_response_code(401);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Following</title>
    <style>
      :root{
        --bd:#e5e7eb; --muted:#64748b; --ink:#0f172a; --card:#fff;
      }
      *{box-sizing:border-box}
      body{
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
        margin:24px; color:var(--ink); background:#fff;
      }
      .wrap{max-width:920px; margin:0 auto}
      .card{
        border:1px solid var(--bd); background:var(--card);
        border-radius:16px; padding:20px; margin:0 0 18px 0;
      }
      h2{margin:0 0 10px 0; font-size:28px; font-weight:800; letter-spacing:.2px}
      .desc{font-size:20px; line-height:1.6; color:var(--muted); margin:0}
      .tip{font-size:18px; line-height:1.6; color:var(--muted)}
      .tip b{color:#0f172a}
    </style>
  </head>
  <body>
    <div class="wrap">
      <!-- Top card: title + description -->
      <div class="card" role="region" aria-label="Login required">
        <h2>Please login</h2>
        <p class="desc">You need to sign in to view profile, following and followers.</p>
      </div>
      <!-- Bottom tip: intentionally not inside a card -->
      <p class="tip">
        Tip: Use the <b>Login</b> button in the top bar first, then click the link on the attacker page.
      </p>
    </div>
  </body></html>
  <?php
  exit;
}
/* ───── Authenticated: Following list (keep your existing logic) ───── */

$view = $_GET['view'] ?? '';
$highlight = isset($_GET['highlight']) ? trim($_GET['highlight']) : '';

/* Utility: move a specific name to the front (to pin attacker or the highlighted name) */
function move_to_front(array $arr, string $name){
  $idx = null; $val = null;
  foreach ($arr as $i => $v) { if (strcasecmp($v,$name)===0){ $idx=$i; $val=$v; break; } }
  if ($idx===null) return $arr;
  array_splice($arr,$idx,1); array_unshift($arr,$val); return $arr;
}

if ($view === 'following') {
  $all = $_SESSION['i_follows'] ?? [];

  // Respect highlight first; otherwise pin "attacker" to the top (consistent with attack version)
  if ($highlight !== '') {
    $all = move_to_front($all, $highlight);
  } elseif (in_array('attacker',$all,true)) {
    $all = move_to_front($all, 'attacker');
  }

  // Natural sort for the rest (keep the pinned item at index 0)
  if (!empty($all)) {
    $top = $all[0];
    $rest = array_slice($all,1);
    if (!empty($rest)) { natcasesort($rest); $all = array_merge([$top], array_values($rest)); }
  }

  $count = count($all);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Following · <?= (int)$count ?></title>
    <style>
      :root{--bd:#e5e7eb;--muted:#64748b;--chip-bg:#fef3c7;--chip-bd:#fde68a;--chip-fg:#92400e}
      body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:#fff;color:#0f172a}
      .wrap{padding:16px}
      .card{border:1px solid var(--bd);border-radius:12px;padding:16px;background:#fff;max-width:560px;margin:0 auto}
      .muted{color:var(--muted)}
      ul{margin:8px 0 0 20px} li{padding:2px 0}
      .chip{display:inline-block;margin-left:6px;padding:2px 6px;border:1px solid var(--chip-bd);background:var(--chip-bg);color:var(--chip-fg);border-radius:8px;font-size:12px;line-height:1}
      a.btn{display:inline-block;margin-bottom:12px;padding:8px 12px;border:1px solid var(--bd);border-radius:8px;text-decoration:none;color:#111;background:#f8fafc}
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="card">
        <a class="btn" href="D_profile.php">← Back to Profile</a>

        <h3 style="margin:0 0 6px 0;">Following (<?= (int)$count ?>)</h3>
        <p class="muted">Names are not clickable. This list includes the newly added account.</p>

        <?php if (empty($all)): ?>
          <p class="muted">No followings yet.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($all as $name): ?>
              <li>
                <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                <?php
                  $isNew = ($highlight !== '' && strcasecmp($name,$highlight)===0)
                        || ($highlight === '' && strcasecmp($name,'attacker')===0);
                  if ($isNew): ?>
                    <span class="chip">new</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;
}

http_response_code(400);
echo 'Bad request.';
