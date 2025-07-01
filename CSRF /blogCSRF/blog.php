<?php
session_start();

// Check if the user is logged in via cookie
$loggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

// Initialize post list in session
if (!isset($_SESSION['posts'])) {
    $_SESSION['posts'] = [];
}

// Handle clear-all-posts request (GET)
if ($loggedIn && isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['posts'] = []; // Clear all stored posts
    header("Location: blog.php"); // Redirect to clean URL
    exit;
}

// Handle post submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($loggedIn && isset($_POST['content'])) {
        $newPost = strip_tags($_POST['content']); // Sanitize input to prevent XSS
        $_SESSION['posts'][] = $newPost; // Add to posts
        header("Location: blog.php");
        exit;
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
    </style>
</head>
<body>
    <h1>My Blog</h1>
    
    <?php if ($loggedIn): ?>
        <!-- Logged-in state -->
        <p><strong>Status:</strong> Logged in ✅</p>

        <!-- Post submission form -->
        <form method="POST" action="">
            <textarea name="content" placeholder="Write something..."></textarea><br>
            <button type="submit">Post</button>
        </form>

        <!-- One-click clear all posts -->
        <form method="GET" action="">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="delete" style="margin-top:10px;">
                Clear All Posts
            </button>
        </form>

    <?php else: ?>
        <!-- Not logged in -->
        <p style="color: red;"><strong>Status:</strong> Not logged in ❌</p>
        <p><a href="login.php">Click here to simulate login</a></p>
    <?php endif; ?>

    <!-- Display all posts -->
    <h2>Posts:</h2>
    <?php foreach (array_reverse($_SESSION['posts']) as $post): ?>
        <div class="post"><?= htmlspecialchars($post) ?></div>
    <?php endforeach; ?>
</body>
</html>
