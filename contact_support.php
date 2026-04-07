<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";

// Handle New Message Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_help'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    $sql = "INSERT INTO support_messages (user_id, subject, message) VALUES ('$user_id', '$subject', '$message')";
    if ($conn->query($sql)) {
        $success_msg = "Message sent successfully! Admin will review it shortly.";
    }
}

// Mark replies as read when user views this page
$conn->query("UPDATE support_messages SET is_read_by_user = 1 WHERE user_id = '$user_id' AND admin_reply IS NOT NULL");

// Fetch User's Message History
$history = $conn->query("SELECT * FROM support_messages WHERE user_id = '$user_id' ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help & Support | Zetech L&F</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --zetech-blue: #003366; --zetech-gold: #FFD700; --glass: rgba(255, 255, 255, 0.95); }
        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), url('Campus.jpg') no-repeat center fixed;
            background-size: cover; min-height: 100vh;
        }
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        
        /* Form Styling */
        .support-card { background: var(--glass); padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: fit-content; }
        input, textarea, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-send { background: var(--zetech-blue); color: white; border: none; padding: 12px; width: 100%; border-radius: 8px; font-weight: 700; cursor: pointer; }
        
        /* History Styling */
        .history-card { background: var(--glass); padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .msg-item { background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 5px solid #ccc; }
        .status-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; font-weight: bold; }
        .status-unread { background: #ffeeba; color: #856404; }
        .status-replied { background: #d4f8d4; color: #1e7e34; border-left-color: #28a745; }
        
        .admin-reply { margin-top: 10px; padding: 10px; background: #f0f7ff; border-radius: 5px; font-size: 0.9rem; color: #333; border-left: 3px solid var(--zetech-blue); }
    </style>
</head>
<body>

<div class="container">
    <div class="support-card">
        <h2 style="color: var(--zetech-blue); margin-top:0;">Need Help?</h2>
        <p style="font-size: 0.85rem; color: #666;">Contact ZETECH Support for issues regarding lost items or account problems.</p>
        
        <?php if($success_msg): ?>
            <p style="color: green; font-weight: bold;"><?php echo $success_msg; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Subject</label>
            <select name="subject" required>
                <option value="Item Recovery Issue">Item Recovery Issue</option>
                <option value="Account/Profile Support">Account/Profile Support</option>
                <option value="Reporting a Bug">Reporting a Bug</option>
                <option value="General Inquiry">General Inquiry</option>
            </select>
            
            <label>Message</label>
            <textarea name="message" rows="5" placeholder="Describe your issue..." required></textarea>
            
            <button type="submit" name="send_help" class="btn-send">Send Message</button>
            <a href="dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Back to Dashboard</a>
        </form>
    </div>

    <div class="history-card">
        <h2 style="color: var(--zetech-blue); margin-top:0;">Your Support History</h2>
        
        <?php if($history->num_rows > 0): ?>
            <?php while($row = $history->fetch_assoc()): ?>
                <div class="msg-item" style="<?php echo $row['admin_reply'] ? 'border-left-color: #28a745;' : ''; ?>">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <strong style="color: var(--zetech-blue);"><?php echo htmlspecialchars($row['subject']); ?></strong>
                        <?php if($row['admin_reply']): ?>
                            <span class="status-badge status-replied">Replied</span>
                        <?php else: ?>
                            <span class="status-badge status-unread">Pending</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size: 0.9rem; margin: 10px 0; color: #555;"><?php echo htmlspecialchars($row['message']); ?></p>
                    <small style="color: #999;"><?php echo date('M d, g:i a', strtotime($row['created_at'])); ?></small>

                    <?php if($row['admin_reply']): ?>
                        <div class="admin-reply">
                            <strong>Admin Reply:</strong><br>
                            <?php echo htmlspecialchars($row['admin_reply']); ?>
                            <br><small style="color: #666; font-size: 0.75rem;">Replied on: <?php echo date('M d, Y', strtotime($row['replied_at'])); ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#888; margin-top:50px;">You haven't sent any support messages yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>