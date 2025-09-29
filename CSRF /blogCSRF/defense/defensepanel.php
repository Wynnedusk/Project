<?php require_once __DIR__ . '/Dsession.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CSRF Defense Panel</title>
  <style>
    :root{
      --blue:#2563eb; --blue-d:#1d4ed8;
      --bg:#f4f6fa; --card:#fff; --border:#e5e7eb; --muted:#64748b; --ink:#0f172a;
      --ok:#16a34a; --bad:#b91c1c;
    }
    *{box-sizing:border-box}
    body{
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
      margin:0; padding:30px; background:var(--bg); color:var(--ink); text-align:center;
    }
    h1{margin:0 0 16px}
    .topbar{display:flex;gap:12px;justify-content:center;align-items:center;margin-bottom:18px}
    .btn{padding:8px 12px;border:1px solid var(--border);background:#f8fafc;border-radius:8px;cursor:pointer}
    .btn:active{transform:translateY(1px)}
    .btn-primary{background:var(--blue);color:#fff;border-color:var(--blue-d);font-weight:600}
    .badge{padding:4px 10px;border:1px solid var(--border);border-radius:999px;background:#fff;font-weight:600}
    .s-ok{color:var(--ok)}
    .s-bad{color:var(--bad)}
    .container{display:flex;gap:30px;justify-content:center;align-items:flex-start}
    .panel{
      border:1px solid var(--border); background:var(--card);
      padding:10px; width:600px; height:600px; border-radius:12px;
      display:flex; flex-direction:column;
    }
    .panel h2{margin:0 0 8px; font-size:18px}
    .frameWrap{flex:1; min-height:0; overflow:auto}
    .frameWrap>iframe{width:100%; height:100%; border:none; border-radius:6px; background:#fff}
    .reload-btn{margin-top:10px}

    /* Teaching prompts */
    .teach{max-width:1230px;margin:20px auto 0;display:flex;flex-direction:column;gap:14px;text-align:left}
    .teachbox{
      border:1px solid var(--border); border-radius:10px; background:#fff; overflow:hidden;
    }
    .teachbox>summary{
      padding:12px 14px; list-style:none; font-weight:700; cursor:pointer; background:#f8fafc;
      border-bottom:1px solid var(--border)
    }
    .teachbox>summary::-webkit-details-marker{display:none}
    .teachbox[open]>summary{background:#eef2ff}
    .teachbox .box{padding:12px 14px}
    code, pre{background:#0f172a; color:#e5e7eb; border-radius:8px; padding:10px; display:block; overflow:auto}
    .muted{color:var(--muted); font-size:13px}
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
  <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
</head>
<body>
  <h1>CSRF Blog Defense Demo</h1>

  <div class="topbar">
    <button class="btn" onclick="doReset()">Reset Demo</button>
    <button class="btn btn-primary" onclick="startGuide()">Start Guide</button>
    <span class="badge">Status: <span id="statusText" class="s-ok">Idle</span></span>
  </div>

  <div class="container">
    <!-- Secure Blog -->
    <div class="panel" id="victimPanel">
      <h2>Secure Blog (Defended)</h2>
      <div class="frameWrap">
        <iframe id="secureFrame" src="secure_blog.php?step=1" title="Secure Blog"></iframe>
      </div>
      <button class="reload-btn btn" onclick="reloadVictim()">Reload Secure Blog</button>
    </div>

    <!-- Attacker -->
    <div class="panel" id="attackerPanel">
      <h2>Attacker Attempt</h2>
      <div class="frameWrap">
        <iframe id="attackerFrame" src="attacker_defense.html" title="Attacker"></iframe>
      </div>
      <button class="reload-btn btn" onclick="reloadAttacker()">Reload Attacker</button>
    </div>
  </div>

  <!-- Unified teaching section -->
  <div class="teach" id="teachSection">
    <!-- What just happened -->
    <details class="teachbox">
      <summary>What just happened?</summary>
      <div class="box">
        <p>
          You clicked an action in the right panel. That page tried to submit a cross-site
          POST using your login, but it did not carry the secret proof that only the real
          blog can provide. The server refused the request, so no new post appeared.
        </p>
        <p class="muted">
          Signal: user click (armed) → request sent (no secret proof) → server refuses → left panel unchanged → status stays Protected.
        </p>
      </div>
    </details>

    <!-- Why defense succeeded (no file names; plain-language) -->
    <details class="teachbox">
      <summary>Why did the defense succeed?</summary>
      <div class="box">
        <ul style="margin:0 0 0 18px;">
          <li><b>A secret ticket</b>: after you sign in, the page puts a one-time “ticket” into its own forms to prove “this click came from me”.</li>
          <li><b>Other sites can’t copy it</b>: the attacker page is on a different site; even with your cookies, it cannot attach the ticket.</li>
          <li><b>Changes use the safe lane</b>: only proper form submissions (POST) can change data; links/images (GET) don’t change anything.</li>
          <li><b>Basic gate first</b>: if you weren’t signed in, the request would be stopped before any change.</li>
        </ul>
        <p class="muted" style="margin-top:10px">
          In short: no valid ticket → no permission to change → nothing on the blog is altered.
        </p>
      </div>
    </details>

    <!-- Check token -->
    <details class="teachbox">
      <summary>Check the current CSRF Token</summary>
      <div class="box">
        <code><?= htmlspecialchars($_SESSION['csrf_token'] ?? '(no token yet)') ?></code>
      </div>
    </details>

    <!-- Key defense code (with simple explanation) -->
    <details class="teachbox">
      <summary>View key defense code (token generation and validation)</summary>
      <div class="box">
      <pre><code>&lt;?php
// Dsession.php (core teaching snippet for display)
session_start();                      // must start session first
if (empty($_SESSION['csrf_token'])) { // generate once per session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$loggedIn = $_SESSION['loggedIn'] ?? false;

// Example: when rendering the genuine form, embed the token as a hidden field
?&gt;
&lt;input type="hidden" name="csrf_token" value="&lt;?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?&gt;"&gt;
&lt;?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn) {
    $session_token = $_SESSION['csrf_token'] ?? '';
    $user_token    = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';

    // check empties first, then use timing-safe compare
    if ($session_token === '' || $user_token === '' || !hash_equals((string)$session_token, $user_token)) {
        // mark blocked so the teaching panel can visualise the block
        $_SESSION['attackBlocked'] = true;
        http_response_code(403);
        exit('CSRF token invalid or missing.');
    }
    // token OK -> proceed with state change
}
?&gt;</code></pre>
        <ul style="margin:10px 0 0 18px;">
          <li><b>Make a secret ticket</b>: when your session starts, we create a random string and keep it on the server.</li>
          <li><b>Put ticket in real forms</b>: the genuine page adds this ticket as a hidden field when you click submit.</li>
          <li><b>Server checks it</b>: on receiving a change request, the server compares “what you sent” with “what it stored”.</li>
          <li><b>Mismatch or missing</b> → the request is refused (Forbidden) and nothing changes.</li>
        </ul>
      </div>
    </details>
</div>
<script>
  const secureFrame   = document.getElementById('secureFrame');
  const attackerFrame = document.getElementById('attackerFrame');
  const statusEl      = document.getElementById('statusText');

  // tri-state: 'idle' | 'attacked' | 'protected'
  function setStatus(state){
    if (state === 'attacked'){
      statusEl.textContent = 'Attacked';
      statusEl.classList.remove('s-ok'); statusEl.classList.add('s-bad');
    } else if (state === 'protected'){
      statusEl.textContent = 'Protected';
      statusEl.classList.remove('s-bad'); statusEl.classList.add('s-ok');
    } else {
      statusEl.textContent = 'Idle';
      statusEl.classList.remove('s-bad'); statusEl.classList.add('s-ok');
    }
  }

  // receive signals from attacker iframe
  window.addEventListener('message', (e) => {
    const d = e.data || {};
    if (d.type === 'ATTACK_ATTEMPT')  setStatus('attacked');
    if (d.type === 'DEFENSE_BLOCKED') setStatus('protected');
  }, false);

  function reloadVictim(){
    const u=new URL(secureFrame.src,location.href);
    u.searchParams.set('_v',Date.now());
    secureFrame.src=u.toString();
  }
  function reloadAttacker(){
    const u=new URL(attackerFrame.src,location.href);
    u.searchParams.set('_v',Date.now());
    attackerFrame.src=u.toString();
  }
  function doReset(){ setStatus('idle'); reloadVictim(); reloadAttacker(); }

  function startGuide(){
    introJs().setOptions({
      nextLabel:'Next', prevLabel:'Back', doneLabel:'Done',
      exitOnOverlayClick:false, scrollToElement:true,
      steps:[
        {element:'#victimPanel',   intro:'Step 1: In the secure blog (left), click Simulate Login to sign in.'},
        {element:'#attackerPanel', intro:'Step 2: In the attacker panel (right), click Like. The request will be sent but blocked without the secret ticket.'},
        {element:'#victimPanel',   intro:'Step 3: Look at the blog: no new post, the request was refused. Status remains Idle.'},
        {element:'#teachSection',  intro:'Step 4: After the demo, check this teaching section for a clear explanatios below.'}
      ]
    }).start();
  }

  setStatus('idle');
</script>
</body>
</html>
