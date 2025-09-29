<?php
require_once __DIR__ . '/Hsession.php';

if (empty($_SESSION['i_loggedIn'])) {
  http_response_code(401);
  exit('Not logged in');
}

$user = trim($_GET['user'] ?? '');
if ($user === '') {
  http_response_code(400);
  exit("Missing 'user' parameter.");
}

// [FIX] force container to be an array even if it was accidentally set to a string
if (!isset($_SESSION['i_follows']) || !is_array($_SESSION['i_follows'])) {
  $_SESSION['i_follows'] = [];
}

$_SESSION['i_follows'] = $_SESSION['i_follows'] ?? [];
if (!in_array($user, $_SESSION['i_follows'], true)) {
  $_SESSION['i_follows'][] = $user;
}

// 记录攻击演示状态 & 一次性提示/高亮
$_SESSION['i_attacked']     = true;
$_SESSION['i_flash']        = ['type' => 'follow_ok', 'who' => $user]; // 提示条用
$_SESSION['i_new_follow']   = $user;                                    // 列表页一次性高亮用

// 回到资料页（是否带参数不影响逻辑）
header('Location: H_profile.php?step=2');
exit;
