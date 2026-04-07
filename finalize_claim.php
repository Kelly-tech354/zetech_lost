<?php
session_start();
include('config.php');

// Security: Redirect if not logged in or ID is missing
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    
    // Sanitize the ID to prevent SQL injection
    $item_id = mysqli_real_escape_string($conn, $_GET['id']);
    $user_id = $_SESSION['user_id'];

    // Security check: Only the person who reported it can mark it as claimed
    // Using 'reported_by' to match your items table schema
    $sql = "UPDATE items SET status = 'claimed' 
            WHERE id = '$item_id' AND reported_by = '$user_id'";

    if ($conn->query($sql) === TRUE) {
        // Double check if a row was actually updated (prevents unauthorized status changes)
        if ($conn->affected_rows > 0) {
            header("Location: my_reports.php?status=success");
        } else {
            // No row updated usually means the item didn't belong to the user
            header("Location: my_reports.php?status=error&message=unauthorized");
        }
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    // If accessed directly without an ID or session, send to dashboard
    header("Location: dashboard.php");
}

exit();
?>