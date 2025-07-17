<?php
session_start();

// Clear the login sign and teaching arrows
unset($_SESSION['loggedIn']);
unset($_SESSION['secureLoginArrowDrawn']);
unset($_SESSION['csrf_token']);

// Redirects to a specific page or to the default secure_blog.php
$target = $_GET['redirect'] ?? 'secure_blog.php?step=1';
?>
<script>
sessionStorage.removeItem("secureStep1");
sessionStorage.removeItem("secureStep2");
window.location.href = "<?= htmlspecialchars($target) ?>";
</script>
