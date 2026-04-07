<?php
include('config.php');
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$message = "";
$verified = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_code = mysqli_real_escape_string($conn, $_POST['code']);

    $query = "SELECT * FROM users WHERE email='$email' AND verification_code='$user_code'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $conn->query("UPDATE users SET is_verified=1, verification_code=NULL WHERE email='$email'");
        $message = "<div class='success-banner'>✅ Account verified! Redirecting to login...</div>";
        $verified = true;
    } else {
        $message = "<div class='error-banner'>❌ Invalid code. Please check your email.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account | Zetech Lost & Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --zetech-blue: #003366;
            --glass-white: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Inter', sans-serif;
            /* Matches your Signup/Login background */
            background: linear-gradient(rgba(0, 31, 63, 0.7), rgba(0, 31, 63, 0.7)), 
                        url('Campus.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .verify-card {
            background: var(--glass-white);
            padding: 35px 25px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        h2 { color: var(--zetech-blue); font-size: 1.6rem; margin: 0; font-weight: 700; }
        h3 { color: #666; font-weight: 400; font-size: 0.9rem; margin: 10px 0 25px 0; }

        .success-banner { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #bbf7d0; }
        .error-banner { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #fecaca; }

        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 0.7rem; color: var(--zetech-blue); font-weight: 700; margin-bottom: 8px; text-transform: uppercase; }

        input[type="text"] {
            width: 100%; padding: 14px; border: 2px solid #e5e7eb; border-radius: 10px;
            box-sizing: border-box; font-size: 1.2rem; text-align: center;
            letter-spacing: 4px; transition: 0.3s; font-weight: 600;
        }
        input:focus { border-color: var(--zetech-blue); outline: none; box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1); }

        .verify-btn {
            width: 100%; padding: 14px; background: var(--zetech-blue); color: white;
            border: none; border-radius: 10px; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: 0.3s;
        }
        .verify-btn:hover { background: #002244; transform: translateY(-2px); }

        .footer-links { margin-top: 25px; font-size: 0.85rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        .footer-links a { color: var(--zetech-blue); text-decoration: none; font-weight: 700; }
    </style>

    <?php if ($verified): ?>
    <script>
        setTimeout(function() {
            window.location.href = "login.html?status=verified";
        }, 3000); // Redirects after 3 seconds
    </script>
    <?php endif; ?>
</head>
<body>

<div class="verify-card">
    <h2>VERIFY EMAIL</h2>
    <h3>Enter the 6-digit code sent to:<br><strong><?php echo $email; ?></strong></h3>

    <?php echo $message; ?>

    <form method="POST">
        <input type="hidden" name="email" value="<?php echo $email; ?>">
        <div class="form-group">
            <label>Verification Code</label>
            <input type="text" name="code" placeholder="000000" maxlength="6" required autocomplete="off">
        </div>
        <button type="submit" class="verify-btn">Verify Account</button>
    </form>

    <div class="footer-links">
        Didn't receive a code? <a href="#">Resend Code</a>
    </div>
</div>

</body>
</html>