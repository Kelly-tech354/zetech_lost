<?php
session_start();
include('config.php');

// Disable default reporting to handle errors cleanly
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Capture the input from the 'phone' name attribute in profile.php
    $new_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if (!empty($new_phone)) {
        // Use a prepared statement to prevent SQL injection and syntax crashes
        $stmt = $conn->prepare("UPDATE users SET phone_number = ? WHERE id = ?");
        $stmt->bind_param("si", $new_phone, $user_id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: profile.php?status=updated");
            exit();
        } else {
            echo "Error updating profile: " . $conn->error;
        }
    } else {
        header("Location: profile.php?error=empty");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
$conn->close();
?>