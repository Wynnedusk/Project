<?php
session_start();

// Clear login status and visualization flags
unset($_SESSION['attack_loggedIn']);
unset($_SESSION['attack_loginArrowDrawn']);

// Active setting no longer triggers red arrow (insurance clearance)
unset($_SESSION['attack_drawRedArrow']);

$target = $_GET['redirect'] ?? '../attack/blog.php?step=1';
?>
<script>
sessionStorage.removeItem("introStep1Shown");
sessionStorage.removeItem("introStep2Shown");
window.location.href = "<?= htmlspecialchars($target) ?>";
</script>
