<?php
session_start();
include('config.php');

if (isset($_GET['id']) && $_SESSION['role'] === 'Admin') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $conn->query("UPDATE support_messages SET status = 'Resolved' WHERE id = '$id'");
    header("Location: admin.php?status=resolved");
} else {
    echo "Access denied.";
}
?>