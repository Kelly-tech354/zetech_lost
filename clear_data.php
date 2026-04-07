<?php
session_start();
include('config.php');

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access: Please log in first.");
}

// 2. Flexible Security Check: 
// Converts 'Administrator' or 'Admin' to lowercase to match your DB correctly
$user_role = strtolower(trim($_SESSION['role']));

if ($user_role !== 'administrator' && $user_role !== 'admin') {
    die("Unauthorized access. Your current role is: " . $_SESSION['role']);
}

// 3. Process the Wipe
if (isset($_POST['confirm_clear'])) {
    // Disable checks to prevent foreign key errors
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Wipe the tables
    $conn->query("TRUNCATE TABLE notifications");
    $conn->query("TRUNCATE TABLE support_messages");
    $conn->query("TRUNCATE TABLE items");
    
    // Re-enable checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // Redirect back to admin dashboard
    header("Location: admin.php?status=cleared");
    exit();
} else {
    // If someone tries to access this file directly without clicking the button
    die("No confirmation received. Please use the buttons on the Admin Panel.");
}
?>