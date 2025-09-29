<?php
// D_fakelogin.php --- Fake login page (teaching/demo)
// If the page is opened with ?def=1 (or later instructed by the parent),
// we intercept the form submit to simulate a server-side defense that
// prevents credential exfiltration in the demo.

$defense = isset($_GET['def']) && $_GET['def'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>UCC Single Sign-On (Demo)</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:root{--border:#e5e7eb;--warn:#fef3c7;--ink:#111827;--muted:#64748b;--blue:#2563eb}
*{box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:#f8fafc}
.wrap{max-width:520px;margin:40px auto;padding:0 16px}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:22px}
h1{margin:0 0 10px}
.banner{background:var(--warn);padding:10px 12px;border-radius:10px;border:1px solid #fde68a;color:#92400e;margin:8px 0 14px}
label{display:block;margin:10px 0 6px}
.inp{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fffbeb}
.btn{margin-top:16px;display:block;width:100%;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:12px;cursor:pointer;font-weight:600}
.note{margin-top:12px;color:#475569;font-size:13px;text-align:center}
.blocked{margin-top:12px;color:#b91c1c;font-weight:700;display:none}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>UCC Single Sign-On</h1>
<p>Please sign in with your university email to verify this friend request</p>
<div class="banner">Password Manager Notice: No stored credentials matched this domain (demo)</div>

<form id="f" method="POST" action="D_collect.php" autocomplete="off">
<label>Email</label>
<input class="inp" type="email" name="email" id="email" 
       placeholder="Enter your email" 
       autocomplete="off" 
       autocapitalize="off" 
       autocorrect="off"
       value="" 
       required />

<label>Password</label>
<input class="inp" type="password" name="password" id="password" 
       placeholder="Enter your password" 
       autocomplete="new-password"
       value=""
       required>

<button class="btn" type="submit">Sign in and Verify</button>

<div class="blocked" id="blockedMsg">
Login attempt blocked by server-side defense --- suspicious request rejected, credentials were not accepted.
<br><span style="font-size:13px;color:#64748b"></span>
</div>
</form>
</div>
</div>

<script>
// Whether the defense mode is ON based on the presence of ?def=1 in the URL.
// When true, we prevent the form from submitting and notify the parent frame
// so the panel can log "blocked" in the timeline and demo backend.
const DEFENSE_ON = <?php echo $defense ? 'true' : 'false'; ?>;

// Form & elements
const form = document.getElementById('f');
const emailInput = document.getElementById('email');
const pwdInput = document.getElementById('password');
const blockedMsg = document.getElementById('blockedMsg');

/**
 * Intercept submission in demo defense mode:
 * - Prevent network exfiltration of the password.
 * - Show a red "blocked" banner to the learner.
 * - Notify the parent (D_panel) so it can record a "blocked" event and
 * optionally write a blocked marker to Data/phished.jsonl via D_collect.php.
 */
function blockSubmit(e){
e.preventDefault();
blockedMsg.style.display = 'block';
try{
window.parent?.postMessage({
type: 'PHISH_SUBMIT_BLOCKED',
email: emailInput.value || ''
}, '*');
}catch(err){
// Swallow errors silently --- this is a self-contained demo page.
}
}

// 1) If defense is ON via URL (?def=1), intercept immediately.
if(DEFENSE_ON){
form.addEventListener('submit', blockSubmit);
} else {
// 2) Otherwise, allow the parent panel to toggle defense dynamically.
// When the parent sends {type:'SET_DEFENSE', on:true}, we switch to
// interception mode for the rest of the session.
window.addEventListener('message', (ev)=>{
const d = ev.data || {};
if(d.type === 'SET_DEFENSE' && d.on === true){
form.addEventListener('submit', blockSubmit);
}
});
}
</script>
</body>
</html>