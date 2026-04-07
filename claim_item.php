<?php
// 1. Enable error reporting to catch issues immediately
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('config.php');

// 2. Use the manual PHPMailer files (skipping vendor)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 3. Check if user is logged in
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    
    // Check if form data exists
    if (!isset($_POST['item_id']) || !isset($_POST['proof_details'])) {
        die("<b>Error:</b> Form data missing. Ensure your form uses name='item_id' and name='proof_details'.");
    }

    $item_id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $claimer_id = $_SESSION['user_id'];
    $claimer_name = $_SESSION['full_name'];
    $proof = mysqli_real_escape_string($conn, $_POST['proof_details']);

    // 4. Fetch the Reporter's details (Who found the item)
    $info_query = "SELECT items.item_name, users.id as reporter_id, users.email, users.full_name 
                   FROM items 
                   JOIN users ON items.reported_by = users.id 
                   WHERE items.id = '$item_id'";
    
    $info_result = $conn->query($info_query);
    
    if (!$info_result) {
        die("<b>Database Error (Select):</b> " . $conn->error);
    }

    if ($info_result->num_rows > 0) {
        $info = $info_result->fetch_assoc();
        
        $reporter_id = $info['reporter_id'];
        $reporter_email = $info['email'];
        $item_name = $info['item_name'];

        // 5. Insert the claim record
        $claim_sql = "INSERT INTO claims (item_id, user_id, proof_details) VALUES ('$item_id', '$claimer_id', '$proof')";
        if (!$conn->query($claim_sql)) {
            die("<b>Database Error (Claim):</b> " . $conn->error);
        }

        // --- NEW LOGIC START ---
        // Capture the ID of the claim that was just inserted
        $claim_id = $conn->insert_id; 
        // --- NEW LOGIC END ---

        // 6. Insert System Notification for the reporter
        $msg = "CLAIM ALERT: $claimer_name has claimed the '$item_name' you reported. View claims to verify.";
        $escaped_msg = mysqli_real_escape_string($conn, $msg);
        
        // Updated to include claim_id in the notifications table
        $notif_sql = "INSERT INTO notifications (user_id, message, claim_id, is_read) VALUES ('$reporter_id', '$escaped_msg', '$claim_id', 0)";
        
        if (!$conn->query($notif_sql)) {
            die("<b>Database Error (Notification):</b> " . $conn->error);
        }

        // 7. Send Email via Gmail SMTP (Matched to Signup.php credentials)
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

            // Email Content
            $mail->setFrom('dumbkelly70@gmail.com', 'Zetech Lost & Found');
            $mail->addAddress($reporter_email); // Sends to the person who found the item

            $mail->isHTML(true);
            $mail->Subject = "New Claim for: $item_name";
            $mail->Body    = "
                <h3>Hello {$info['full_name']},</h3>
                <p>A new claim has been submitted for an item you reported as found.</p>
                <p><b>Item:</b> $item_name</p>
                <p><b>Claimed By:</b> $claimer_name</p>
                <p><b>Provided Proof:</b> $proof</p>
                <br>
                <p>Please log in to your dashboard to review this claim and contact the owner.</p>
            ";

            $mail->send();
            
            // Redirect on success
            header("Location: dashboard.php?status=claim_sent");
            exit();

        } catch (Exception $e) {
            // If email fails, the claim is still in the DB/Notifications
            echo "<b>Claim saved, but email failed.</b><br>";
            echo "Mailer Error: " . $mail->ErrorInfo;
            echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
            exit();
        }
    } else {
        die("<b>Error:</b> The item you are trying to claim does not exist.");
    }
} else {
    die("<b>Error:</b> Invalid request or session expired. Please log in again.");
}
?>