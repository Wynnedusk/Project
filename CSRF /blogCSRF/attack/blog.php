<?php
// Load session handling for the attack simulation module
require_once __DIR__ . '/Asession.php';

/* ---- Optional: clear the "post published" flash flag ---- */
if (isset($_GET['clearFlash'])) {
    unset($_SESSION['flash_post_success']);
    http_response_code(204);
    exit;
}

// Handle an optional GET request to clear the red arrow flag (triggered after attack visual completes)
if (isset($_GET['clearRed'])) {
    unset($_SESSION['attack_drawRedArrow']);
    http_response_code(204);
    exit;
}

// Check login status for posting functionality
$loggedIn = (bool) ($_SESSION['attack_loggedIn'] ?? false);

// Initialize public post list if not already set (visible to everyone)
if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}

// Initialize arrow tracking state for teaching purposes
if (!isset($_SESSION['attack_loginArrowDrawn'])) {
    $_SESSION['attack_loginArrowDrawn'] = false;
}

$errorMessage = "";

// Only allow logged-in users to clear all posts
if ($loggedIn && isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['posts'] = [];
    header("Location: blog.php");
    exit;
}

// Handle form submission (only logged-in users can post)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($loggedIn && isset($_POST['content'])) {
        $newPost = strip_tags(trim($_POST['content']));
        if (!empty($newPost)) {
            $_SESSION['posts'][] = $newPost;

            /* ---- Set "post published" flash flag (applies to both manual and CSRF posts) ---- */
            $_SESSION['flash_post_success'] = true;

            // If the submitted post matches the CSRF attack pattern, flag it for red-arrow visualization
            if (strpos($newPost, 'Automatically posted by CSRF via session cookie') !== false) {
                $_SESSION['attack_drawRedArrow'] = true;
            }

            header("Location: blog.php");
            exit;
        } else {
            $errorMessage = "Please enter content before posting.";
        }
    } else {
        // Block posting from unauthenticated users
        die("Access denied: You must be logged in to post.");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Blog (Attack Demo)</title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        textarea { width: 100%; height: 80px; }
        .post { background: #f0f0f0; margin: 10px 0; padding: 10px; border-radius: 5px; }
        button.delete { background-color: #d9534f; color: white; border: none; padding: 6px 12px; border-radius: 4px; }
        .error { color: red; font-weight: bold; margin-top: 10px; }
        .btn-group { display: flex; gap: 20px; margin-bottom: 30px; }
        .btn-group button { padding: 10px 20px; font-size: 16px; }
    </style>

    <!-- External libraries for step-by-step teaching and arrow drawings -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
    <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leader-line/1.0.7/leader-line.min.js"></script>
</head>
<body>

<h1>My Blog (Attack Simulation)</h1>

<!-- Control panel -->
<div class="btn-group">
    <button id="loginBox">Login</button>
    <button id="blogBox">Blog</button>
    <button id="attackerBox">Attacker</button>
</div>

<!-- Post submission section: visible only to logged-in users -->
<?php if ($loggedIn): ?>
    <p style="color: green;">Logged in. You may post below.</p>
    <p><strong>Status:</strong> Logged in</p>

    <?php if (!empty($errorMessage)): ?>
        <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <textarea id="postBox" name="content" placeholder="Write something..." required></textarea><br>
        <button id="postSubmit" type="submit">Post</button>
    </form>

    <form method="GET" action="">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="delete" style="margin-top:10px;">Clear All Posts</button>
    </form>

    <form method="GET" action="Alogout.php">
        <input type="hidden" name="redirect" value="blog.php?step=1">
        <button type="submit" class="delete" style="margin-top:10px;">Logout</button>
    </form>
<?php else: ?>
    <!-- Anonymous visitors can view posts but cannot post -->
    <p style="color: red;"><strong>Status:</strong> Not logged in</p>
    <p><a id="loginLink" href="Alogin.php?redirect=blog.php?step=2">Click here to simulate login</a></p>
<?php endif; ?>

<!-- Always visible to all users (read-only list) -->
<h2>Posts:</h2>
<?php foreach (array_reverse($_SESSION['posts']) as $post): ?>
    <div class="post"><?= htmlspecialchars($post) ?></div>
<?php endforeach; ?>

<script>
window.addEventListener("DOMContentLoaded", function () {
    const step = new URLSearchParams(window.location.search).get("step");

    // Teaching step 1: login guidance
    if (step === "1" && !sessionStorage.getItem("introStep1Shown")) {
        const loginLink = document.getElementById("loginLink");
        if (loginLink) {
            loginLink.setAttribute("data-intro", "Click here to simulate login");
            introJs().start();
            sessionStorage.setItem("introStep1Shown", "true");
        }
    }

    // Teaching step 2: posting guidance
    if (step === "2" && !sessionStorage.getItem("introStep2Shown")) {
        const postBox = document.getElementById("postBox");
        if (postBox) {
            postBox.setAttribute("data-intro", "You are now logged in. Try submitting a post.");
            setTimeout(() => { introJs().start(); }, 300);
            sessionStorage.setItem("introStep2Shown", "true");
        }
    }

    // On logout, clear any existing leader-line arrows
    <?php if (!$loggedIn): ?>
    const existingLines = document.querySelectorAll("svg.leader-line");
    existingLines.forEach(line => line.remove());
    <?php endif; ?>
});
</script>
<script>
window.addEventListener("DOMContentLoaded", function () {
    // Draw green login arrow (one-time or persistent)
    <?php if ($loggedIn && $_SESSION['attack_loginArrowDrawn'] === false): ?>
        setTimeout(() => {
            new LeaderLine(
                document.getElementById("loginBox"),
                document.getElementById("blogBox"),
                { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
            );
        }, 1000);
    <?php $_SESSION['attack_loginArrowDrawn'] = true; ?>
    <?php elseif ($loggedIn): ?>
        new LeaderLine(
            document.getElementById("loginBox"),
            document.getElementById("blogBox"),
            { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
        );
    <?php endif; ?>
});
</script>

<?php if (isset($_SESSION['attack_drawRedArrow']) && $_SESSION['attack_drawRedArrow'] === true): ?>
<script>
window.addEventListener("DOMContentLoaded", function () {
    // Draw red arrow from attacker to blog (attack visualization)
    setTimeout(() => {
        const attacker = document.getElementById("attackerBox");
        const blog = document.getElementById("blogBox");
        if (attacker && blog) {
            new LeaderLine(attacker, blog, {
                color: "red", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow"
            });

            // After rendering, clear the server-side flag so it doesn't repeat
            fetch("blog.php?clearRed=1");
        }
    }, 1500);
});
</script>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_post_success'])): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const anchor = document.getElementById('postSubmit') || document.getElementById('blogBox');
  if (!anchor) return;

  // 1) Create and show toast (your original styles and positioning logic kept)
  const toast = document.createElement('div');
  toast.textContent = '✅ Post published!';
  Object.assign(toast.style, {
    position:'fixed', zIndex:'9999', background:'#10b981', color:'#fff',
    padding:'8px 12px', borderRadius:'10px', boxShadow:'0 6px 18px rgba(0,0,0,.15)',
    fontWeight:'600', fontSize:'14px', opacity:'0', transform:'translateY(-6px)',
    transition:'opacity .28s ease, transform .28s ease'
  });
  document.body.appendChild(toast);

  function positionToast(){
    const r = anchor.getBoundingClientRect();
    const gap = 10;
    if (r.right + 220 < window.innerWidth) {
      toast.style.left = (r.right + gap) + 'px';
      toast.style.top  = (r.top + window.scrollY - 4) + 'px';
    } else {
      toast.style.left = (r.left + window.scrollX + r.width/2 - toast.offsetWidth/2) + 'px';
      toast.style.top  = (r.top + window.scrollY - toast.offsetHeight - gap) + 'px';
    }
  }
  requestAnimationFrame(()=>{ positionToast(); toast.style.opacity='1'; toast.style.transform='translateY(0)'; });
  ['scroll','resize'].forEach(ev=>window.addEventListener(ev, positionToast));

  setTimeout(()=>{
    toast.style.opacity='0';
    toast.style.transform='translateY(-6px)';
    setTimeout(()=>toast.remove(), 280);
  }, 2200);

  // 2) Runs only on the visible page, then clears the server-side flash flag
  fetch('blog.php?clearFlash=1', {cache:'no-store'});
});
</script>
<?php endif; ?>



</body>
</html>
