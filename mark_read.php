<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Option 1: Clear ALL notifications for this user
if (isset($_GET['action']) && $_GET['action'] == 'clear_all') {
    $conn->query("DELETE FROM notifications WHERE user_id = '$user_id'");
    header("Location: notifications.php?status=cleared");
    exit();
}

// Option 2: Clear ONE specific notification
if (isset($_GET['id'])) {
    $notif_id = mysqli_real_escape_string($conn, $_GET['id']);
    // Ensure the notification actually belongs to the logged-in user for security
    $conn->query("DELETE FROM notifications WHERE id = '$notif_id' AND user_id = '$user_id'");
    header("Location: notifications.php?status=deleted");
    exit();
}

header("Location: dashboard.php");
exit();
?>