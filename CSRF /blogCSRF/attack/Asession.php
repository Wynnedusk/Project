<?php
session_start(); // Required at top of every session-handling file

// Attack module: login state and arrow flags
if (!isset($_SESSION['attack_loggedIn'])) {
    $_SESSION['attack_loggedIn'] = false;
}
if (!isset($_SESSION['attack_loginArrowDrawn'])) {
    $_SESSION['attack_loginArrowDrawn'] = false;
}
if (!isset($_SESSION['attack_csrfArrowDrawn'])) {
    $_SESSION['attack_csrfArrowDrawn'] = false;
}

// Shared post list — persists even when logged out
if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}
