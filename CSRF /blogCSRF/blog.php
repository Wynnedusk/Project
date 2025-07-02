<?php
session_start();

// Determine login status
$loggedIn = (bool) ($_SESSION['loggedIn'] ?? false);

// Initialize session data if not already set
if (!isset($_SESSION['posts'])) $_SESSION['posts'] = [];
if (!isset($_SESSION['loginArrowDrawn'])) $_SESSION['loginArrowDrawn'] = false;
if (!isset($_SESSION['csrfArrowDrawn'])) $_SESSION['csrfArrowDrawn'] = false;

$errorMessage = "";

// Clear all posts if user is logged in and requested clear action
if ($loggedIn && isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['posts'] = [];
    header("Location: blog.php");
    exit;
}

// Handle blog post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($loggedIn && isset($_POST['content'])) {
        $newPost = strip_tags(trim($_POST['content']));
        if (!empty($newPost)) {
            $_SESSION['posts'][] = $newPost;

            // If the post is from CSRF (predefined content), trigger red arrow on next load
            if (strpos($newPost, 'Automatically posted by CSRF via session cookie') !== false) {
                $_SESSION['csrfArrowDrawn'] = false;
            }

            header("Location: blog.php");
            exit;
        } else {
            $errorMessage = "Please enter content before posting.";
        }
    } else {
        die("Access denied: You must be logged in to post.");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple Blog with Login Check</title>
    <style>
        body { font-family: sans-serif; margin: 40px; }
        textarea { width: 100%; height: 80px; }
        .post { background: #f0f0f0; margin: 10px 0; padding: 10px; border-radius: 5px; }
        button.delete { background-color: #d9534f; color: white; border: none; padding: 6px 12px; border-radius: 4px; }
        .error { color: red; font-weight: bold; margin-top: 10px; }
        .btn-group { display: flex; gap: 20px; margin-bottom: 30px; }
        .btn-group button { padding: 10px 20px; font-size: 16px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leader-line/1.0.7/leader-line.min.js"></script>
</head>
<body>

<h1>My Blog</h1>

<!-- Navigation Buttons -->
<div class="btn-group">
    <button id="loginBox" onclick="location.href='login.php'">Login</button>
    <button id="blogBox">Blog</button>
    <button id="attackerBox" onclick="window.open('attacker.html')">Attacker</button>
</div>

<?php if ($loggedIn): ?>
    <p style="color: green;"><strong>Session established:</strong> You can now post.</p>
    <p><strong>Status:</strong> Logged in</p>

    <?php if (!empty($errorMessage)): ?>
        <p class="error"><?= htmlspecialchars($errorMessage) ?></p>
    <?php endif; ?>

    <!-- Form to submit a blog post -->
    <form method="POST" action="">
        <textarea name="content" placeholder="Write something..." required></textarea><br>
        <button type="submit">Post</button>
    </form>

    <!-- Button to clear all posts -->
    <form method="GET" action="">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="delete" style="margin-top:10px;">Clear All Posts</button>
    </form>

    <!-- Logout button -->
    <form method="GET" action="logout.php">
        <button type="submit" class="delete" style="margin-top:10px;">Logout</button>
    </form>
<?php else: ?>
    <p style="color: red;"><strong>Status:</strong> Not logged in</p>
    <p><a href="login.php">Click here to simulate login</a></p>
<?php endif; ?>

<h2>Posts:</h2>
<?php foreach (array_reverse($_SESSION['posts']) as $post): ?>
    <div class="post"><?= htmlspecialchars($post) ?></div>
<?php endforeach; ?>

<!-- Animation Drawing Script -->
<script>
<?php if ($loggedIn): ?>
    <?php if ($_SESSION['loginArrowDrawn'] === false): ?>
        // Draw green login arrow with delay (only once)
        setTimeout(() => {
            new LeaderLine(
                document.getElementById("loginBox"),
                document.getElementById("blogBox"),
                { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
            );
        }, 1000);
        <?php $_SESSION['loginArrowDrawn'] = true; ?>
    <?php else: ?>
        // If already drawn before, draw arrow again instantly without delay
        window.addEventListener("DOMContentLoaded", () => {
            new LeaderLine(
                document.getElementById("loginBox"),
                document.getElementById("blogBox"),
                { color: "green", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
            );
        });
    <?php endif; ?>
<?php endif; ?>

<?php if ($_SESSION['csrfArrowDrawn'] === false): ?>
    // If CSRF post is detected, draw red arrow after delay
    setTimeout(() => {
        const posts = document.querySelectorAll(".post");
        posts.forEach(post => {
            if (post.textContent.includes("Automatically posted by CSRF via session cookie")) {
                new LeaderLine(
                    document.getElementById("attackerBox"),
                    document.getElementById("blogBox"),
                    { color: "red", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
                );
            }
        });
    }, 2000);
    <?php $_SESSION['csrfArrowDrawn'] = true; ?>
<?php elseif ($_SESSION['csrfArrowDrawn'] === true): ?>
    // Redraw red arrow without animation if it was already shown
    window.addEventListener("DOMContentLoaded", () => {
        const posts = document.querySelectorAll(".post");
        posts.forEach(post => {
            if (post.textContent.includes("Automatically posted by CSRF via session cookie")) {
                new LeaderLine(
                    document.getElementById("attackerBox"),
                    document.getElementById("blogBox"),
                    { color: "red", size: 4, path: "straight", startPlug: "disc", endPlug: "arrow" }
                );
            }
        });
    });
<?php endif; ?>
</script>

</body>
</html>
