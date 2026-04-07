<?php
session_start();
include('config.php');

// 1. Security Check: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Clear Notifications Logic
// Instead of DELETING them, it is often better to just mark them as 'read' 
// but if you want them gone from the UI entirely, we use DELETE.
$sql = "DELETE FROM notifications WHERE user_id = '$user_id'";

if ($conn->query($sql) === TRUE) {
    // Redirect back to the dashboard with a success message
    header("Location: dashboard.php?notif_status=cleared");
} else {
    // Redirect back with an error message if something went wrong
    header("Location: dashboard.php?notif_status=error");
}

$conn->close();
?>