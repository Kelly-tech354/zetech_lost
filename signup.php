<?php
// 1. Error Reporting (Keep these enabled to troubleshoot)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('config.php');

// 2. Load PHPMailer
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 3. Sanitize inputs to prevent SQL Injection
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); 
    $reg_number = mysqli_real_escape_string($conn, $_POST['registration_number']); 
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // 4. Robust Role Logic - FIXED FOR CASE SENSITIVITY
    $selected_role = isset($_POST['role']) ? trim($_POST['role']) : ''; 

    // Convert input to lowercase so it catches "admin", "Admin", and "Administrator"
    $check_role = strtolower($selected_role);

    if ($check_role == "admin" || $check_role == "administrator") {
        $role = "admin";
    } else {
        $role = "users"; 
    }

    // 5. Generate verification code
    $v_code = rand(100000, 999999);

    // 6. Check for existing account first
    $check = "SELECT * FROM users WHERE email='$email' OR registration_number='$reg_number' OR username='$username'";
    $result = $conn->query($check);
    
    if ($result && $result->num_rows > 0) {
        header("Location: signup.html?status=exists");
        exit();
    }

    // 7. PRE-INSERT EMAIL ATTEMPT
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
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Account';
        $mail->Body    = "Hello <b>$full_name</b>, <br><br> Your verification code is: <h2>$v_code</h2>";

        // 8. CRITICAL CHANGE: Only save to DB if mail sends successfully
        if($mail->send()) {
            $sql = "INSERT INTO users (full_name, registration_number, email, phone_number, username, password_hash, role, verification_code, is_verified) 
                    VALUES ('$full_name', '$reg_number', '$email', '$phone', '$username', '$password', '$role', '$v_code', 0)";

            if ($conn->query($sql) === TRUE) {
                // Success - Redirect to verify entry page
                header("Location: verify.php?email=" . urlencode($email));
                exit();
            } else {
                echo "<div style='color:red;'>Database Error: " . $conn->error . "</div>";
            }
        }

    } catch (Exception $e) {
        // If mail fails, nothing is saved to the database
        echo "<div style='color:red;'>Verification email could not be sent. Registration cancelled. <br> Mail error: " . $mail->ErrorInfo . "</div>";
    }
}
?>