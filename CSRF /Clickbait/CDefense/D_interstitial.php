<?php
header('Content-Type: text/html; charset=UTF-8');
$u = $_GET['u'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Warning</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{--bd:#e5e7eb;--warn-bg:#fee2e2;--warn-bd:#fecaca;--ink:#0f172a;--muted:#64748b;}
    *{box-sizing:border-box}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:22px;color:var(--ink)}
    .note{border:1px solid #a5f3fc;background:#ecfeff;border-radius:10px;padding:10px 12px;margin-bottom:12px;color:#0e7490}
    .card{border:1px solid var(--warn-bd);background:var(--warn-bg);border-radius:12px;padding:14px 16px}
    h3{margin:0 0 10px}
    code{background:#fff;border:1px solid var(--bd);padding:2px 6px;border-radius:6px}
    .row{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap}
    .btn{padding:8px 12px;border:1px solid var(--bd);border-radius:8px;background:#fff;cursor:pointer}
    .muted{color:var(--muted);font-size:13px;margin-top:10px}
  </style>
</head>
<body>
  <div class="note">Warning interstitial is displayed; you may continue in a sandbox.</div>

  <div class="card" role="alert">
    <h3>Potentially risky site</h3>
    <p>The link you clicked is not on the allow-list.</p>
    <p><code><?= htmlspecialchars($u, ENT_QUOTES) ?></code></p>
    <p>You may go back, or continue in a restricted sandbox.</p>
    <div class="row">
      <button class="btn" id="back">Go back</button>
      <button class="btn" id="go">Continue (sandbox)</button>
    </div>
    <p class="muted">Sandbox limits scripts, form posts, downloads, and cross-origin access.</p>
  </div>

  <script>
    const url = <?= json_encode($u) ?>;
    document.getElementById('back').onclick = () => history.back();
    document.getElementById('go').onclick   = () => parent.postMessage({ type:'DEF_CONTINUE_SANDBOX', url }, '*');
  </script>
</body>
</html>
