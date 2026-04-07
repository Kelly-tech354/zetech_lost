<?php
// 1. Enable error reporting to catch issues immediately
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read when they open this page
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id'");

// Fetch notifications - Assuming you have a 'claim_id' column in this table
$sql = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications | Zetech L&F</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --zetech-blue: #001f3f; 
            --zetech-light-blue: #003366;
            --zetech-gold: #FFD700;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; 
            color: #2d3436; 
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
        }

        .container { 
            width: 100%;
            max-width: 800px; 
            margin-top: 40px;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .header { 
            display: flex; 
            justify-content: space-between;
            align-items: center; 
            margin-bottom: 25px; 
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .back-btn { 
            text-decoration: none; 
            color: var(--zetech-blue); 
            font-weight: 600; 
            font-size: 0.9rem;
            transition: 0.3s;
        }
        
        .back-btn:hover { color: #00509e; }

        .clear-btn {
            text-decoration: none;
            background: #d9534f;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .clear-btn:hover { background: #c9302c; transform: translateY(-1px); }

        /* Modified to act as a link container */
        .notif-link {
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .notif-card { 
            background: white; 
            padding: 18px 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 6px solid var(--zetech-blue);
            transition: transform 0.2s, background 0.2s;
        }

        .notif-card:hover { 
            transform: translateX(5px); 
            background: #f8f9fa;
        }

        .notif-card.unread { 
            border-left-color: var(--zetech-gold); 
            background: #fffef0; 
        }

        .notif-time { 
            font-size: 0.75rem; 
            color: #888; 
            display: block; 
            margin-top: 10px;
            font-weight: 500;
        }

        .no-notif { 
            text-align: center; 
            padding: 60px 20px; 
            color: #7f8c8d; 
        }

        h2 { 
            margin: 0; 
            color: var(--zetech-blue); 
            font-size: 1.7rem; 
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        <h2>Notifications</h2>
        <?php if ($result && $result->num_rows > 0): ?>
            <a href="clear_all_notifs.php" class="clear-btn" onclick="return confirm('Permanently clear all notifications?')">Clear All</a>
        <?php endif; ?>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php 
                // Determine the link: If no claim_id, link to dashboard as fallback
                $target_link = !empty($row['claim_id']) ? "view_claim_details.php?id=" . $row['claim_id'] : "dashboard.php";
            ?>
            <a href="<?php echo $target_link; ?>" class="notif-link">
                <div class="notif-card <?php echo $row['is_read'] == 0 ? 'unread' : ''; ?>">
                    <p style="margin:0; color: #2d3436; line-height: 1.5; font-size: 0.95rem;">
                        <?php echo htmlspecialchars($row['message']); ?>
                    </p>
                    <span class="notif-time">📅 Received: <?php echo date('M d, Y | h:i A', strtotime($row['created_at'])); ?></span>
                </div>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-notif">
            <div style="font-size: 3rem; margin-bottom: 15px;">🔔</div>
            <p style="font-weight: 600; font-size: 1.1rem; margin: 0;">No notifications found</p>
            <p style="font-size: 0.9rem;">You're all caught up! Match alerts will appear here.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>