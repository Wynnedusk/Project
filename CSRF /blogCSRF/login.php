<?php
session_start();
$_SESSION['loggedIn'] = true; // User login state
header("Location: blog.php");
exit();
?>
