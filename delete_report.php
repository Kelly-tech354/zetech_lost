<?php
session_start();
include('config.php');

// 1. IMPROVED SECURITY CHECK
// This matches the case-insensitive logic used in your main Admin Dashboard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'administrator')) {
    die("Unauthorized access. Admin privileges required.");
}

// 2. VALIDATE THE ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 3. USE PREPARED STATEMENT (More Secure)
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // 4. CORRECT REDIRECT
        // Redirects back to your management page with the success status
        header("Location: admin.php?status=deleted#reports");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
} else {
    // If no ID is provided, just go back
    header("Location: admin.php");
    exit();
}
?>