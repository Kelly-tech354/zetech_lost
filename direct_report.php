<?php
// 1. Enable error reporting to catch any hidden issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('config.php');

// 2. Use the manual PHPMailer files that are already working for you
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    // Escape inputs to prevent SQL injection and syntax crashes
    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $receiver_id = mysqli_real_escape_string($conn, $_POST['receiver_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message_text']);
    $sender_name = $_SESSION['full_name'];

    // 1. Get Item and Owner Info
    $owner_query = "SELECT items.item_name, users.email, users.full_name 
                    FROM items 
                    JOIN users ON items.reported_by = users.id 
                    WHERE items.id = '$item_id'";
    
    $owner_res = $conn->query($owner_query);
    
    if ($owner_res && $owner_res->num_rows > 0) {
        $owner = $owner_res->fetch_assoc();
        $item_name = $owner['item_name'];
        $owner_email = $owner['email'];

        // 2. Insert System Notification
        $notif_msg = "🎉 GREAT NEWS: $sender_name found your lost '$item_name'! Message: $message";
        
        // Escape the final message string to handle quotes (e.g., "Student's ID")
        $escaped_notif = mysqli_real_escape_string($conn, $notif_msg);
        
        $notif_sql = "INSERT INTO notifications (user_id, message, is_read) VALUES ('$receiver_id', '$escaped_notif', 0)";
        
        if (!$conn->query($notif_sql)) {
            die("<b>Database Error (Notification):</b> " . $conn->error);
        }

        // 3. Send Email via Gmail SMTP (Using working credentials from signup.php)
        $mail = new PHPMailer(true);
        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dumbkelly70@gmail.com'; 
            $mail->Password   = 'fcyhhulxgdyqeldo'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('dumbkelly70@gmail.com', 'Zetech Lost & Found');
            $mail->addAddress($owner_email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = "Good News! Your $item_name was found";
            $mail->Body    = "<h3>Hi {$owner['full_name']},</h3>
                              <p>Someone at Zetech has found your lost item!</p>
                              <p><b>Item:</b> $item_name</p>
                              <p><b>Message from Finder:</b> $message</p>
                              <p>Login to your dashboard to see their contact info.</p>";
            
            $mail->send();
            
            header("Location: dashboard.php?status=owner_notified");
            exit();
            
        } catch (Exception $e) {
            // If email fails, the notification is still saved in the database
            echo "<b>Notification sent in dashboard, but email failed.</b><br>";
            echo "Mailer Error: " . $mail->ErrorInfo;
            echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
            exit();
        }
    } else {
        die("<b>Error:</b> Item not found.");
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>