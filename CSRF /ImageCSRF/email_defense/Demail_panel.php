<?php
// Demail_panel.php  (Defense panel with concise teaching section)

require_once __DIR__ . '/../email_attack/Gsession.php';

// Ensure the same CSRF token used by the profile exists,
// so we can display it in the teaching section below.
if (empty($_SESSION['def_csrf_token'])) {
    $_SESSION['def_csrf_token'] = bin2hex(random_bytes(32));
}
$defToken = $_SESSION['def_csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>GET CSRF Demo — Change Email via &lt;img&gt; (Defense)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;padding:30px}
    h1{text-align:center;margin-bottom:18px}

    .topbar{display:flex;justify-content:center;gap:12px;margin-bottom:14px;align-items:center}
    .btn{padding:8px 12px;border:1px solid #ccc;background:#f7f7f7;border-radius:6px;cursor:pointer}
    .btn-guide{
      background:#2563eb;color:#fff;border-color:#1d4ed8;font-weight:600;
      box-shadow:0 0 8px rgba(37,99,235,.6);transition:background .2s,transform .2s;
    }
    .btn-guide:hover{background:#1d4ed8;transform:scale(1.05)}
    .btn:active{transform:translateY(1px)}
    .btn:disabled{opacity:.5;cursor:not-allowed;filter:grayscale(30%)}

    .status{font-size:14px;color:#555}.status b{color:#166534}

    .container{display:flex;gap:30px;justify-content:center}
    .panel{
      border:1px solid #ccc;padding:10px;width:600px;height:600px;border-radius:8px;
      display:flex;flex-direction:column;min-width:0;background:#fff
    }
    .panel h2{margin:0 0 8px 0;font-size:18px}
    .frameWrap{flex:1;min-height:0;overflow:hidden;display:flex}
    .frameWrap>iframe{width:100%;height:100%;border:none;border-radius:6px;background:#fff;touch-action:pan-y}
    .reload-btn{margin-top:10px}

    /* Teaching section */
    .teach{max-width:1230px;margin:22px auto;display:flex;flex-direction:column;gap:14px}
    .teachbox{
      border:1px solid #e5e7eb;border-radius:10px;background:#fff;text-align:left;overflow:hidden;
    }
    .teachbox>summary{
      padding:10px 12px;list-style:none;font-weight:700;cursor:pointer;background:#f8fafc;
      border-bottom:1px solid #e5e7eb;
    }
    .teachbox[open]>summary{background:#eef2ff}
    .teachbox .box{padding:12px}
    pre,code{background:#0f172a;color:#e5e7eb;border-radius:8px;padding:10px;display:block;overflow:auto;margin:10px 0}
    .muted{color:#64748b;font-size:13px}
    .label{font-weight:700;margin-top:6px}
    .bullets{margin:8px 0 12px 18px}
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
  <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
</head>
<body>
  <h1>GET CSRF Demo — Change Email via &lt;img&gt; (Defense)</h1>

  <div class="topbar">
    <button id="btnSimLogin" class="btn" onclick="simulateLogin()">Simulate Login</button>
    <button class="btn" onclick="resetProfile()">Reset Profile</button>
    <button class="btn btn-guide" onclick="startGuide()">Start Guide</button>
    <span class="status">Attack status: <b id="attackStatus">Idle</b></span>
  </div>

  <div class="container">
    <div class="panel" id="victimPanel">
      <h2>Profile (Victim)</h2>
      <div class="frameWrap">
        <iframe id="victimFrame" src="Demail_profile.php?step=1" title="Victim Profile" scrolling="auto"></iframe>
      </div>
      <button class="reload-btn btn" onclick="reloadVictim()">Reload Profile</button>
    </div>

    <div class="panel" id="attackerPanel">
      <h2>Attacker Offer (attempted)</h2>
      <div class="frameWrap">
        <iframe id="attackerFrame" src="Demail_attacker.html" title="Attacker Page" scrolling="auto"></iframe>
      </div>
      <button class="reload-btn btn" onclick="reloadAttacker()">Reload Attacker</button>
    </div>
  </div>

  <!-- Teaching section -->
  <div class="teach" id="teachSection">

    <details class="teachbox">
      <summary>What just happened?</summary>
      <div class="box">
        <p>The attacker page tried to change your email by loading a cross-site image whose URL contained a command.
           The server blocked it, so the profile did not change.</p>
        <p class="muted">Flow: hidden image → browser sends cookie-backed GET → server checks → rejected.</p>
      </div>
    </details>
    <details class="teachbox">
  <summary>Why did the defense succeed?</summary>
  <div class="box">
    <ul class="bullets">
      <li><b>No state change on GET:</b> resource loads such as <code>&lt;img&gt;</code> only issue GET requests; sensitive endpoints enforce POST-only.</li>
      <li><b>Same-origin heuristic:</b> the server checks <code>Referer</code> or <code>Origin</code> headers to detect cross-site loads (when present).</li>
      <li><b>Per-session CSRF token:</b> only requests carrying the session’s secret token are accepted.</li>
    </ul>

    <!-- Attacker attempt -->
    <p class="label" style="margin-top:10px;">Attacker attempt (blocked)</p>
<pre><code>// Cross-site GET via &lt;img&gt; (cannot carry a CSRF token)
const img = new Image();
img.src = 'https://victim.example.com/email_image.php?new=attacker%40evil.com';
</code></pre>
    <p class="muted">An image load triggers a GET and will attach cookies for the victim site, but it cannot include a CSRF token.</p>

    <!-- Defense #1 -->
    <p class="label" style="margin-top:10px;">Defense #1 — enforce POST only</p>
<pre><code>// Reject resource-based GETs before any state change
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit;
}
</code></pre>
    <p class="muted">GET requests (e.g. from images) are rejected upfront.</p>

    <!-- Defense #2 -->
    <p class="label" style="margin-top:10px;">Defense #2 — same-origin heuristic + CSRF token</p>
<pre><code>// Extra checks: token is primary; origin/referrer is auxiliary
session_start();

// Same-origin heuristic (Referer is common for images; Origin may be absent)
$host    = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$originOk = (strpos($origin, $host) === 0) || (strpos($referer, $host) === 0);

// Token from header (AJAX) or POST form
$sessionTok = $_SESSION['def_csrf_token'] ?? '';
$tok        = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');

if (!$originOk || $sessionTok === '' || $tok === '' || !hash_equals((string)$sessionTok, (string)$tok)) {
    http_response_code(403);
    exit('Forbidden: origin or CSRF token invalid.');
}

// proceed with validated state change...
</code></pre>
    <p class="muted">Only same-origin requests that also present the correct per-session token are allowed.</p>

    <!-- Fix summary -->
    <p style="margin-top:10px;">
      <b>Fix summary:</b> never perform state changes on GET. Require POST + CSRF token as the main defense. Use Referer/Origin checks as defense-in-depth, and always validate inputs.
    </p>
    <p class="muted">Tip: SameSite cookies help but do not replace server-side CSRF tokens.</p>
  </div>
</details>



    <details class="teachbox">
      <summary>Check the current CSRF Token</summary>
      <div class="box">
        <p class="muted">This token is embedded in the profile forms and verified by the server on every change.</p>
        <p>Current token:<br><code><?php echo htmlspecialchars($defToken, ENT_QUOTES, 'UTF-8'); ?></code></p>
      </div>
    </details>

  </div>

<script>
const victim   = document.getElementById('victimFrame');
const attacker = document.getElementById('attackerFrame');
const statusEl = document.getElementById('attackStatus');
const btnLogin = document.getElementById('btnSimLogin');

function checkVictimState(){
  return fetch('Demail_profile.php?state=1&v='+Date.now(),{cache:'no-store'})
    .then(r=>r.ok?r.json():Promise.reject())
    .then(s=>{
      btnLogin.disabled = !!s.loggedIn;
      // Only show "Protected" when logged in and at least one real cross-site attempt was blocked
      statusEl.textContent = (s.loggedIn && s.lastBlocked) ? 'Protected' : 'Idle';
    })
    .catch(()=>{});
}

function simulateLogin(){
  if (btnLogin.disabled) return;
  btnLogin.disabled = true;
  victim.src = 'Demail_profile.php?action=login';
  setTimeout(()=>{ victim.src = 'Demail_profile.php?step=2'; checkVictimState(); }, 500);
}

function resetProfile(){
  victim.src = 'Demail_profile.php?action=reset';
  statusEl.textContent = 'Idle';
  btnLogin.disabled = false;
  const u = new URL(attacker.src, location.href);
  u.searchParams.set('_v', Date.now());
  attacker.src = u.toString();
}

function reloadVictim(){
  const u = new URL(victim.src, location.href);
  u.searchParams.set('_v', Date.now());
  victim.src = u.toString();
  setTimeout(checkVictimState, 600);
}

function reloadAttacker(){
  const u = new URL(attacker.src, location.href);
  u.searchParams.set('_v', Date.now());
  attacker.src = u.toString();
}

// After an attacker attempt, reload and re-check state
window.addEventListener('message',(e)=>{
  const d = e.data || {};
  if (d.type === 'DEFENSE_RESULT'){
    reloadVictim();
    setTimeout(checkVictimState, 500);
  }
}, false);

document.addEventListener('DOMContentLoaded',()=>{
  checkVictimState();
  let n=0; const t=setInterval(()=>{ checkVictimState(); if(++n>10) clearInterval(t); },200);
});

function startGuide(){
  introJs().setOptions({
    nextLabel:'Next', prevLabel:'Back', doneLabel:'Done',
    exitOnOverlayClick:false, scrollToElement:true,
    steps:[
      { element:'#btnSimLogin',  intro:'Click <b>Simulate Login</b> to sign in the victim account.', position:'bottom' },
      { element:'#attackerPanel',intro:'Scroll the right panel to the third image. The attacker tries a cross-site GET via &lt;img&gt;.' },
      { element:'#victimPanel',  intro:'The profile does not change. The request is blocked by POST-only plus CSRF/Origin checks.' },
      { element:'#teachSection', intro:'After the demo, check this teaching section for a clear explanatios below.' }
    ]
  }).start();
}
</script>
</body>
</html>
