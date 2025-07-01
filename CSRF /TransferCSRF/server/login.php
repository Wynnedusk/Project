<?php
session_start();

// Step 1: Check if this is a POST request from the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pwd = $_POST['password'];

    // No real password check in demo – just store the email in the session
    $_SESSION['email'] = $email;

    // Step 2: Generate a CSRF token and store it in the session
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;

    // Step 3: Set cookies for use in the frontend (e.g., JS in secure-home.html)
    setcookie("csrf_token", $token, time() + 3600, "/");   // Used in hidden form field
    setcookie("email", $email, time() + 3600, "/");        // Just for display
    setcookie("auth", "user_token", time() + 3600, "/");   // Indicates user is logged in

    // Step 4: Redirect to the main dashboard page
    header("Location:home.html");
    exit();
}
?>
