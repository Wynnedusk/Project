<?php
// /Phishing/Secure/dashboard.php
session_start();
if (empty($_SESSION['loggedIn'])) {
  header('Location: login.php');
  exit;
}
$me   = $_SESSION['email'] ?? 'unknown';
$last = $_SESSION['last_login_at'] ?? date('c');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>Secure Portal — Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
:root{--border:#e5e7eb;--bg:#f5f7fb;--muted:#64748b;--blue:#2563eb}
*{box-sizing:border-box} body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0;background:var(--bg)}
.container{max-width:780px;margin:40px auto;padding:0 16px}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:22px}
.h{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.badge{border:1px solid var(--border);border-radius:999px;padding:4px 8px;color:#065f46;background:#ecfeff}
p{margin:8px 0} .muted{color:var(--muted)}
.btn{display:inline-block;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:10px 14px;cursor:pointer;text-decoration:none}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="h">
      <h2 style="margin:0">Welcome, <?=htmlspecialchars($me)?></h2>
      <span class="badge">host: <?=htmlspecialchars($host)?></span>
    </div>
    <p class="muted">Last login: <?=htmlspecialchars($last)?></p>
    <p class="muted">Login source: panel / form (teaching)</p>
    <div style="margin-top:16px">
      <a class="btn" href="login.php?logout=1">Log out</a>
    </div>
  </div>
</div>
</body>
</html>
