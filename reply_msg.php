<?php
session_start();
include('config.php');

// 1. Security & Admin Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'administrator')) {
    header("Location: login.html?error=unauthorized"); exit();
}

$msg_id = $_GET['id'] ?? null;
if (!$msg_id) { header("Location: admin_management.php"); exit(); }

// 2. Handle Reply Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
    
    // Update the message: add reply, mark as Resolved, and set user notification flag
    $update_sql = "UPDATE support_messages 
                   SET admin_reply = '$admin_reply', 
                       status = 'Resolved', 
                       is_read_by_user = 0 
                   WHERE id = '$msg_id'";
    
    if ($conn->query($update_sql)) {
        header("Location: admin.php?status=replied#messages");
        exit();
    }
}

// 3. Fetch Message Details for the UI
$msg_query = $conn->query("SELECT sm.*, u.full_name, u.email 
                           FROM support_messages sm 
                           LEFT JOIN users u ON sm.user_id = u.id 
                           WHERE sm.id = '$msg_id'");
$msg = $msg_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reply to Support | Zetech Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --zetech-blue: #003366; 
            --zetech-gold: #FFD700; 
            --zetech-dark: #001f3f; 
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            padding: 0;
            /* Same background as your dashboard */
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover;
            display: flex; 
            justify-content: center; 
            align-items: center;
            min-height: 100vh;
        }

        .reply-container { 
            background: var(--glass-bg); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            width: 90%; 
            max-width: 650px; 
            border-top: 6px solid var(--zetech-blue);
            backdrop-filter: blur(10px); /* Adds that modern glass look */
        }

        h2 { 
            color: var(--zetech-blue); 
            margin-top: 0; 
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info { 
            background: rgba(0, 51, 102, 0.05); 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            border-left: 5px solid var(--zetech-gold); 
        }

        .user-info strong { color: var(--zetech-blue); font-size: 0.9rem; }
        
        .original-msg { 
            font-style: italic; 
            color: #444; 
            margin-top: 15px; 
            display: block; 
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px dashed #ccc;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--zetech-blue);
            text-transform: uppercase;
        }

        textarea { 
            width: 100%; 
            height: 200px; 
            padding: 15px; 
            border: 2px solid #e0e0e0; 
            border-radius: 10px; 
            font-family: inherit; 
            font-size: 1rem; 
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: var(--zetech-blue);
        }

        .btn-container { 
            margin-top: 25px; 
            display: flex; 
            align-items: center;
            gap: 20px; 
        }

        .btn-send { 
            background: var(--zetech-blue); 
            color: white; 
            border: none; 
            padding: 14px 30px; 
            border-radius: 8px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.3);
        }

        .btn-send:hover {
            background: var(--zetech-dark);
            transform: translateY(-2px);
        }

        .btn-cancel { 
            text-decoration: none; 
            color: #666; 
            font-weight: 600;
            font-size: 0.9rem; 
            transition: color 0.2s;
        }

        .btn-cancel:hover {
            color: var(--zetech-blue);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="reply-container">
    <h2>✉️ Respond to Student</h2>
    
    <div class="user-info">
        <strong>From:</strong> <?php echo htmlspecialchars($msg['full_name']); ?> (<?php echo htmlspecialchars($msg['email']); ?>)<br>
        <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
        <span class="original-msg">"<?php echo htmlspecialchars($msg['message']); ?>"</span>
    </div>

    <form method="POST">
        <label>Your Admin Response</label>
        <textarea name="admin_reply" placeholder="Write your message to the student here..." required><?php echo $msg['admin_reply'] ?? ''; ?></textarea>
        
        <div class="btn-container">
            <button type="submit" class="btn-send">Send Response</button>
            <a href="admin.php#messages" class="btn-cancel">BACK TO THE DASHBOARD</a>
        </div>
    </form>
</div>

</body>
</html>