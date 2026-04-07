<?php
// 1. Error Reporting (Kept active to help you debug any final SMTP issues)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('config.php');

// 2. MANUAL PHPMailer Initialization
// We are pointing directly to the folder you created
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// --- LOGIC HANDLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if ($step === 1) {
        $reg_no = mysqli_real_escape_string($conn, $_POST['reg_no']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $check = $conn->query("SELECT * FROM users WHERE registration_number='$reg_no' AND email='$email'");

        if ($check && $check->num_rows > 0) {
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_reg_no'] = $reg_no;
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_expiry'] = time() + 600; 

            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'dumbkelly70@gmail.com'; 
                
                // IMPORTANT: Ensure this is your FRESH 16-digit App Password
                $mail->Password   = 'fcyhhulxgdyqeldo'; 
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('dumbkelly70@gmail.com', 'Zetech Lost & Found');
                $mail->addAddress($email); 

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Recovery Code - Zetech L&F';
                $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 400px; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #003366; text-align: center;'>Verification Code</h2>
                    <p>Hello, use the code below to reset your Zetech Lost and Found password. This code expires in 10 minutes.</p>
                    <div style='background: #f4f7f6; padding: 15px; font-size: 28px; font-weight: bold; text-align: center; color: #003366; letter-spacing: 8px; border-radius: 5px;'>
                        $otp
                    </div>
                </div>";

                $mail->send();
                $step = 2; 
            } catch (Exception $e) {
                // If mail fails, redirect with error status
                header("Location: forget_password.php?status=mail_error");
                exit();
            }
        } else {
            header("Location: forget_password.php?status=fail");
            exit();
        }
    }

    elseif ($step === 2) {
        $user_otp = $_POST['otp'];
        if (isset($_SESSION['reset_otp']) && $user_otp == $_SESSION['reset_otp'] && time() < $_SESSION['otp_expiry']) {
            $step = 3;
        } else {
            header("Location: forget_password.php?status=invalid_otp");
            exit();
        }
    }

    elseif ($step === 3) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        $reg_no = $_SESSION['reset_reg_no'];

        if ($new_pass !== $confirm_pass) {
            header("Location: forget_password.php?status=mismatch");
            exit();
        } else {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->bind_param("ss", $new_password_hashed, $email);
            
            if ($stmt->execute()) {
                session_unset();
                session_destroy();
                header("Location: forget_password.php?status=success");
            } else {
                header("Location: forget_password.php?status=error");
            }
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password | Zetech Lost and Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --zetech-blue: #003366; --glass-white: rgba(255, 255, 255, 0.9); }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(0, 31, 63, 0.7), rgba(0, 31, 63, 0.7)), url('Campus.jpg') no-repeat center center fixed;
            background-size: cover; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px;
        }
        .reset-card { background: var(--glass-white); padding: 30px 25px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); width: 100%; max-width: 380px; text-align: center; backdrop-filter: blur(10px); }
        .reset-card h2 { color: var(--zetech-blue); font-size: 1.5rem; margin: 0; font-weight: 700; }
        .banner { display: block; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 500; }
        .error-banner { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .success-banner { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .info-banner { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-size: 0.75rem; color: var(--zetech-blue); font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
        input { width: 100%; padding: 14px 12px; border: 2px solid #e5e7eb; border-radius: 10px; box-sizing: border-box; font-size: 1rem; transition: 0.3s; background: white; outline: none; }
        input:focus { border-color: var(--zetech-blue); box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1); }
        .action-btn { width: 100%; padding: 14px; background: var(--zetech-blue); color: white; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .action-btn:hover { background: #002244; transform: translateY(-2px); }
        .footer-links { margin-top: 25px; font-size: 0.9rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 20px; }
        .footer-links a { color: var(--zetech-blue); text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>

<div class="reset-card">
    <h2>Reset Password</h2>

    <?php if ($status === 'success'): ?>
        <div class="banner success-banner">✅ Password updated! <a href="login.html" style="color: inherit; text-decoration: underline;">Login Now</a></div>
    <?php elseif ($status === 'fail'): ?>
        <div class="banner error-banner">❌ Records not found. Check your details.</div>
    <?php elseif ($status === 'invalid_otp'): ?>
        <div class="banner error-banner">❌ Invalid or expired code. Try again.</div>
    <?php elseif ($status === 'mismatch'): ?>
        <div class="banner error-banner">❌ Passwords do not match!</div>
    <?php elseif ($status === 'mail_error'): ?>
        <div class="banner error-banner">⚠️ Failed to send email. Please try again later.</div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="step" value="<?php echo $step; ?>">

        <?php if ($step === 1): ?>
            <p>Verify your identity to proceed.</p>
            <div class="form-group">
                <label>Registration Number</label>
                <input type="text" name="reg_no" placeholder="e.g. ZETECH/001" required>
            </div>
            <div class="form-group">
                <label>University Email</label>
                <input type="email" name="email" placeholder="student@zetech.ac.ke" required>
            </div>
            <button type="submit" class="action-btn">Send Recovery Code</button>

        <?php elseif ($step === 2): ?>
            <div class="banner info-banner">📩 Code sent to <?php echo htmlspecialchars($_SESSION['reset_email']); ?></div>
            <div class="form-group">
                <label>Verification Code</label>
                <input type="number" name="otp" placeholder="000000" required autofocus>
            </div>
            <button type="submit" class="action-btn">Verify Code</button>

        <?php elseif ($step === 3): ?>
            <p>Choose a strong new password.</p>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="action-btn">Update Password</button>
        <?php endif; ?>

        <div class="footer-links">
            <a href="login.html">← Back to Login</a>
        </div>
    </form>
</div>

</body>
</html>