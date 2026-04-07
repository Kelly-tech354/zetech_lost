<?php
session_start();
include('config.php');

// Simple Admin Check (Assumes role 'admin' is stored in session)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Unauthorized access.");
}

// Fetch all messages, joining with users table to get their names
$query = "SELECT sm.*, u.full_name 
          FROM support_messages sm 
          JOIN users u ON sm.user_id = u.id 
          ORDER BY sm.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Support Inbox</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .msg-card { background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 5px solid #ccc; }
        .unread { border-left-color: #d9534f; background: #fff8f8; } /* No reply yet */
        .replied { border-left-color: #28a745; }
        .meta { font-size: 0.8rem; color: #666; }
        .btn-reply { background: #003366; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Support Tickets</h2>
    <?php while($row = $result->fetch_assoc()): 
        $status_class = (empty($row['admin_reply'])) ? 'unread' : 'replied';
    ?>
        <div class="msg-card <?php echo $status_class; ?>">
            <strong>From: <?php echo htmlspecialchars($row['full_name']); ?></strong>
            <p><?php echo htmlspecialchars($row['message']); ?></p>
            <div class="meta">Sent on: <?php echo $row['created_at']; ?></div>
            
            <?php if(!empty($row['admin_reply'])): ?>
                <div style="margin-top:10px; padding: 10px; background: #eee; border-radius: 4px;">
                    <strong>Your Reply:</strong> <?php echo htmlspecialchars($row['admin_reply']); ?>
                </div>
            <?php endif; ?>

            <a href="reply_ticket.php?id=<?php echo $row['id']; ?>" class="btn-reply">
                <?php echo empty($row['admin_reply']) ? 'Reply Now' : 'Edit Reply'; ?>
            </a>
        </div>
    <?php endwhile; ?>
</body>
</html>