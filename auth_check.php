<?php
session_start();

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// 2. Set the timeout duration (15 minutes = 900 seconds)
$timeout_duration = 900;

// 3. Check if the 'last_activity' timestamp exists
if (isset($_SESSION['last_activity'])) {
    // Calculate the session's age
    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time >= $timeout_duration) {
        // Session expired: cleanup and redirect
        session_unset();
        session_destroy();
        
        // Redirect to login with a timeout message
        header("Location: login.html?error=timeout");
        exit();
    }
}

// 4. Update 'last_activity' timestamp to the current time
$_SESSION['last_activity'] = time();
?>