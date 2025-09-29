<?php
// Load session and initialize CSRF token, login state, etc.
require_once __DIR__ . '/Dsession.php';

// Optional: clear the one-time "post published" flash flag (for debugging or external calls)
if (isset($_GET['clearFlash'])) {
    unset($_SESSION['flash_post_success']);
    http_response_code(204);
    exit;
}

// Check login status
$loggedIn = $_SESSION['loggedIn'] ?? false;
$errorMessage = "";

// Initialize posts and arrow-drawn flag if not already set
if (!isset($_SESSION['global_posts'])) $_SESSION['global_posts'] = [];
if (!isset($_SESSION['secureLoginArrowDrawn'])) $_SESSION['secureLoginArrowDrawn'] = false;

// Clear all posts if requested via GET (only allowed when logged in)
if ($loggedIn && isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['global_posts'] = [];
    header("Location: secure_blog.php");
    exit;
}

// Mark that the login arrow has been drawn (teaching visualization)
if ($loggedIn && $_SESSION['secureLoginArrowDrawn'] === false) {
    $_SESSION['secureLoginArrowDrawn'] = true;
}

// Handle POST requests (attempt to submit a new post)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($loggedIn && isset($_POST['content'])) {
        $user_token    = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'];

        // === CRITICAL CSRF DEFENSE LOGIC ===
        if (!$user_token || $user_token !== $session_token) {
            // CSRF token is missing or invalid — block the attack
            $_SESSION['attackBlocked'] = true;
            http_response_code(403);// Send HTTP 403 Forbidden
            echo "CSRF token invalid or missing.";
            exit;
        } else {
            $newPost = strip_tags(trim($_POST['content']));
            if (!empty($newPost)) {
                $_SESSION['global_posts'][] = $newPost;

                // ✅ Set a one-time "post published" flash flag (consistent with the attack variant)
                $_SESSION['flash_post_success'] = true;

                header("Location: secure_blog.php");
                exit;
            } else {
                $errorMessage = "Please enter content before posting.";
            }
        }
    } else {
        $errorMessage = "Access denied: You must be logged in to post.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🛡️ Secure Blog</title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        textarea { width: 100%; height: 80px; }
        .post { background: #f0f0f0; margin: 10px 0; padding: 10px; border-radius: 5px; }
        .error { color: red; font-weight: bold; margin-top: 10px; }
        .success { color: green; font-weight: bold; }
        .btn-group { display: flex; gap: 20px; margin-bottom: 30px; align-items: center; }
        .btn-group button { padding: 10px 20px; font-size: 16px; }
        #blockIcon { font-size: 24px; display: none; color: red; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 4px; }
        .token-note { background: #e8f8f5; padding: 10px; border-left: 4px solid #17a589; margin-top: 20px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leader-line/1.0.7/leader-line.min.js"></script>
</head>
<body>

<h1>My Blog (Token Protected)</h1>

<div class="btn-group">
    <button id="loginBox">Login</button>
    <button id="blogBox">Blog</button>
    <span id="blockIcon" title="CSRF attack blocked">❌</span>
    <button id="attackerBox">Attacker</button>
</div>

<?php if ($loggedIn): ?>
    <p class="success">✅ Logged in. You may post below.</p>
    <?php if (!empty($errorMessage)): ?>
        <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <textarea id="postBox" name="content" required placeholder="Write something..."></textarea><br>
        <!-- CSRF token hidden field -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <p style="color:#555;font-size:90%;">🔑 Hidden field <code>csrf_token</code> has been added to this form.</p>
        <!-- Anchor element for success toast positioning (kept for parity with attack variant) -->
        <button id="postSubmit" type="submit">Post</button>
    </form>

    <form method="GET" action="">
        <input type="hidden" name="action" value="clear">
        <button type="submit" style="margin-top:10px;">Clear All Posts</button>
    </form>

    <form method="GET" action="Dlogout.php">
        <input type="hidden" name="redirect" value="secure_blog.php?step=1">
        <button type="submit" style="margin-top:10px;">Logout</button>
    </form>
<?php else: ?>
    <p style="color: red;"><strong>Status:</strong> Not logged in</p>
    <p><a id="loginLink" href="Dlogin.php?redirect=secure_blog.php?step=2">Click here to login</a></p>
<?php endif; ?>

<h2>Posts:</h2>
<?php foreach (array_reverse($_SESSION['global_posts']) as $post): ?>
    <div class="post"><?= htmlspecialchars($post) ?></div>
<?php endforeach; ?>

<div class="token-note">
    <h4>Teaching Tips</h4>
    <ul>
        <li>The CSRF Token defense mechanism is enabled on this page.</li>
        <li>Each time the form is loaded, the server generates a random token and puts it into a hidden field.</li>
        <li>When submitting content, the server verifies that the token matches.</li>
        <li>The attacker's page will not be able to post successfully because it cannot get this token, and the attack will be blocked.</li>
    </ul>
</div>

<script>
window.addEventListener("DOMContentLoaded", function () {
    const step = new URLSearchParams(window.location.search).get("step");

    if (step === "1" && !sessionStorage.getItem("secureStep1")) {
        const loginLink = document.getElementById("loginLink");
        if (loginLink) {
            loginLink.setAttribute("data-intro", "Please click here to simulate login");
            introJs().start();
            sessionStorage.setItem("secureStep1", "true");
        }
    }

    if (step === "2" && !sessionStorage.getItem("secureStep2")) {
        const postBox = document.getElementById("postBox");
        if (postBox) {
            postBox.setAttribute("data-intro", "You have successfully logged in. Now try to post something!");
            setTimeout(() => { introJs().start(); }, 300);
            sessionStorage.setItem("secureStep2", "true");
        }
    }

    // Draw a green login arrow (teaching visualization)
    <?php if ($loggedIn): ?>
        <?php if ($_SESSION['secureLoginArrowDrawn'] === false): ?>
        setTimeout(() => {
            new LeaderLine(
                document.getElementById("loginBox"),
                document.getElementById("blogBox"),
                { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
            );
        }, 1000);
        <?php $_SESSION['secureLoginArrowDrawn'] = true; ?>
        <?php else: ?>
        new LeaderLine(
            document.getElementById("loginBox"),
            document.getElementById("blogBox"),
            { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
        );
        <?php endif; ?>
    <?php endif; ?>

    // If a CSRF attack was blocked, show a red marker and a guided explanation
    const blockIcon = document.getElementById("blockIcon");
    <?php if (!empty($_SESSION['attackBlocked'])): ?>
        if (blockIcon) {
            blockIcon.style.display = "inline";
            setTimeout(() => {
                introJs().setOptions({
                    steps: [{
                        element: '#blockIcon',
                        intro: " Attack blocked!\n\nThe attacker attempted to post content without a valid CSRF token (HTTP 403)."
                    }]
                }).start();
            }, 500);
        }
        <?php unset($_SESSION['attackBlocked']); ?>
    <?php endif; ?>
});
</script>

<!-- ✅ Front-end toast on successful publish (same behavior as the attack variant) -->
<?php if (!empty($_SESSION['flash_post_success'])): ?>
<?php unset($_SESSION['flash_post_success']); ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const anchor = document.getElementById('postSubmit') || document.getElementById('blogBox');
  if (!anchor) return;

  const toast = document.createElement('div');
  toast.textContent = '✅ Post published!';
  Object.assign(toast.style, {
    position: 'fixed',
    zIndex: '9999',
    background: '#10b981',
    color: '#fff',
    padding: '8px 12px',
    borderRadius: '10px',
    boxShadow: '0 6px 18px rgba(0,0,0,.15)',
    fontWeight: '600',
    fontSize: '14px',
    opacity: '0',
    transform: 'translateY(-6px)',
    transition: 'opacity .28s ease, transform .28s ease'
  });
  document.body.appendChild(toast);

  function positionToast() {
    const r = anchor.getBoundingClientRect();
    const gap = 10;
    const rightHasRoom = (r.right + 220 < window.innerWidth);
    if (rightHasRoom) {
      toast.style.left = (r.right + gap) + 'px';
      toast.style.top  = (r.top + window.scrollY - 4) + 'px';
    } else {
      toast.style.left = (r.left + window.scrollX + r.width/2 - toast.offsetWidth/2) + 'px';
      toast.style.top  = (r.top + window.scrollY - toast.offsetHeight - gap) + 'px';
    }
  }

  requestAnimationFrame(() => { positionToast(); toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
  ['scroll','resize'].forEach(ev => window.addEventListener(ev, positionToast));

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-6px)';
    setTimeout(() => toast.remove(), 280);
  }, 2200);
});
</script>
<?php endif; ?>

</body>
</html>
