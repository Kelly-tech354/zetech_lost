<?php
session_start();
include('config.php');

// Security Check
if (!isset($_SESSION['user_id']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'administrator')) {
    header("Location: login.html?error=unauthorized"); exit();
}

// Fetch Messages
$all_messages = false;
$check_support = $conn->query("SHOW TABLES LIKE 'support_messages'");
if ($check_support && $check_support->num_rows > 0) {
    $sql_msgs = "SELECT support_messages.*, users.full_name, users.email 
                 FROM support_messages 
                 LEFT JOIN users ON support_messages.user_id = users.id 
                 ORDER BY FIELD(support_messages.status, 'New', 'Resolved'), support_messages.created_at DESC";
    $all_messages = $conn->query($sql_msgs);
}

// Get count for badge
$new_msg_count = $conn->query("SELECT COUNT(*) as total FROM support_messages WHERE status='New'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Inbox | Zetech L&F</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing your CSS exactly as requested */
        :root { --zetech-blue: #003366; --zetech-gold: #FFD700; --zetech-dark: #001f3f; --glass-bg: rgba(255, 255, 255, 0.95); --danger: #d9534f; --success: #28a745; }
        body { font-family: 'Inter', sans-serif; margin: 0; background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), url('Campus.jpg') no-repeat center center fixed; background-size: cover; color: #333; min-height: 100vh; }
        .navbar { background: var(--zetech-dark); color: white; padding: 0 5%; display: flex; justify-content: space-between; align-items: center; height: 80px; position: sticky; top: 0; z-index: 1000; border-bottom: 3px solid var(--zetech-gold); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .main-layout { display: grid; grid-template-columns: 280px 1fr; gap: 30px; max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .sidebar { background: var(--glass-bg); padding: 25px; border-radius: 15px; height: fit-content; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .side-link { display: flex; justify-content: space-between; align-items: center; padding: 12px; color: var(--zetech-blue); text-decoration: none; font-weight: 600; border-radius: 8px; margin-bottom: 8px; }
        .side-link.active { background: var(--zetech-blue); color: white; }
        .notif-badge { background: var(--danger); color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; }
        .content-card { background: var(--glass-bg); padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #555; text-transform: uppercase; font-size: 0.8rem; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .bg-new { background: #fff3cd; color: #856404; }
        .bg-resolved { background: #d4edda; color: #155724; }
        .btn-action { text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block; border: 1px solid transparent; }
        .btn-reply { background: var(--zetech-blue); color: white; }
        .btn-resolve { background: #e6ffed; color: #22863a; border-color: #22863a; }
        .admin-reply-box { margin-top: 10px; padding: 10px; background: #f0f4f8; border-radius: 8px; font-size: 0.8rem; border-left: 3px solid var(--zetech-blue); }
    </style>
</head>
<body>

<header class="navbar">
    <div style="font-weight:700; font-size:1.5rem;">ZETECH <span>ADMIN</span></div>
    <div>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
</header>

<div class="main-layout">
    <aside class="sidebar">
        <h3>Menu</h3>
        <a href="admin.php" class="side-link">📊 System Reports</a>
        <a href="support_inbox.php" class="side-link active">
            <span>✉️ Support Inbox</span>
            <?php if($new_msg_count > 0): ?>
                <span class="notif-badge"><?php echo $new_msg_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin.php#maintenance" class="side-link">⚙️ Maintenance</a>
    </aside>

    <main>
        <div class="content-card">
            <h3>✉️ Support & Help Messages</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Message & Response</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($all_messages && $all_messages->num_rows > 0): ?>
                            <?php while($msg = $all_messages->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($msg['email']); ?></small>
                                </td>
                                <td>
                                    <strong style="color:var(--zetech-blue);"><?php echo htmlspecialchars($msg['subject']); ?></strong>
                                    <p><?php echo htmlspecialchars($msg['message']); ?></p>
                                    <?php if(!empty($msg['admin_reply'])): ?>
                                        <div class="admin-reply-box"><strong>Response:</strong> <?php echo htmlspecialchars($msg['admin_reply']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?php echo strtolower($msg['status']); ?>"><?php echo $msg['status']; ?></span></td>
                                <td>
                                    <a href="reply_msg.php?id=<?php echo $msg['id']; ?>" class="btn-action btn-reply">Reply</a>
                                    <?php if($msg['status'] == 'New'): ?>
                                        <a href="resolve_msg.php?id=<?php echo $msg['id']; ?>" class="btn-action btn-resolve">Resolve</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px;">No messages.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>