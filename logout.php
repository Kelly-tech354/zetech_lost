<?php
session_start();
session_unset();
session_destroy();

// Delete the "Remember Me" cookies by setting their expiration to the past
if (isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/");
    setcookie("full_name", "", time() - 3600, "/");
    setcookie("role", "", time() - 3600, "/");
}

header("Location: login.html");
exit();
?>