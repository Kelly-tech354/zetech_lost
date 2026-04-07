<?php
session_start();
include('config.php');

// --- 1. AUTO-LOGOUT LOGIC ---
$timeout_duration = 900; 
if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    if ($elapsed_time >= $timeout_duration) {
        session_unset(); session_destroy();
        header("Location: login.html?error=timeout"); exit();
    }
}
$_SESSION['last_activity'] = time();

// --- 2. SECURITY CHECK - FIXED FOR CASE SENSITIVITY ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'admin' && strtolower($_SESSION['role']) !== 'administrator')) {
    header("Location: login.html?error=unauthorized"); exit();
}

// --- 3. ALERT LOGIC ---
$alert_msg = ""; $alert_type = ""; 
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'system_cleared') { $alert_msg = "Database successfully wiped."; $alert_type = "success"; }
    elseif ($_GET['status'] == 'deleted') { $alert_msg = "Report removed successfully."; $alert_type = "success"; }
    elseif ($_GET['status'] == 'replied') { $alert_msg = "Reply sent successfully."; $alert_type = "success"; }
}

// --- 4. FETCH STATISTICS ---
$total_items = $conn->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'] ?? 0;
$lost_items = $conn->query("SELECT COUNT(*) as total FROM items WHERE status='Lost'")->fetch_assoc()['total'] ?? 0;
$found_items = $conn->query("SELECT COUNT(*) as total FROM items WHERE status='Found'")->fetch_assoc()['total'] ?? 0;
$claimed_items = $conn->query("SELECT COUNT(*) as total FROM items WHERE status='Claimed'")->fetch_assoc()['total'] ?? 0;

// --- 5. NEW MESSAGE COUNT FOR NOTIFICATION BADGE ---
$new_msg_count = 0;
$check_msg_count = $conn->query("SELECT COUNT(*) as total FROM support_messages WHERE status='New'");
if ($check_msg_count) {
    $new_msg_count = $check_msg_count->fetch_assoc()['total'];
}

// --- 6. SEARCH & REPORTS ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sql_reports = "SELECT items.*, users.full_name FROM items LEFT JOIN users ON items.reported_by = users.id";
if (!empty($search)) {
    $sql_reports .= " WHERE items.item_name LIKE '%$search%' OR users.full_name LIKE '%$search%' OR items.category LIKE '%$search%'";
}
$sql_reports .= " ORDER BY items.created_at DESC";
$all_reports = $conn->query($sql_reports);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Zetech L&F</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --zetech-blue: #003366;
            --zetech-gold: #FFD700;
            --zetech-dark: #001f3f;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --danger: #d9534f;
            --success: #28a745;
        }

        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; color: #333; min-height: 100vh;
        }

        .navbar { 
            background: var(--zetech-dark); color: white; padding: 0 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            height: 80px; position: sticky; top: 0; z-index: 1000;
            border-bottom: 3px solid var(--zetech-gold);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .logo-area h2 { letter-spacing: 1px; font-weight: 700; margin: 0; }
        .logo-area span { color: var(--zetech-gold); }

        .main-layout { display: grid; grid-template-columns: 280px 1fr; gap: 30px; max-width: 1400px; margin: 30px auto; padding: 0 20px; }

        .sidebar { background: var(--glass-bg); padding: 25px; border-radius: 15px; height: fit-content; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
        .sidebar h3 { font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .side-link { display: flex; justify-content: space-between; align-items: center; padding: 12px; color: var(--zetech-blue); text-decoration: none; font-weight: 600; border-radius: 8px; margin-bottom: 8px; transition: 0.2s; }
        .side-link:hover { background: #f0f2f5; padding-left: 20px; }
        .side-link.active { background: var(--zetech-blue); color: white; }

        .notif-badge { background: var(--danger); color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--glass-bg); padding: 20px; border-radius: 12px; border-bottom: 4px solid var(--zetech-blue); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-card h2 { margin: 0; font-size: 2.2rem; color: var(--zetech-blue); }
        .stat-card p { margin: 5px 0 0; font-size: 0.75rem; font-weight: 700; color: #666; text-transform: uppercase; }

        .content-card { background: var(--glass-bg); padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .content-card h3 { margin-top: 0; color: var(--zetech-blue); font-size: 1.3rem; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }

        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #555; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.9rem; vertical-align: top; }
        tr:hover { background-color: #fafafa; }

        .status-lost { color: var(--danger); font-weight: bold; }
        .status-found { color: var(--success); font-weight: bold; }

        .btn-action { text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; transition: 0.3s; border: none; cursor: pointer; display: inline-block; }
        .btn-delete { color: var(--danger); font-weight: 700; text-decoration: none; }
        .btn-delete:hover { text-decoration: underline; }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .alert-success { background: #d4f8d4; color: #1e7e34; }

        .maintenance-card { background: #fff1f0; border: 2px dashed #ffa39e; }
        
        @media (max-width: 1024px) {
            .main-layout { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo-area">
        <h2>ZETECH <span>ADMIN</span></h2>
    </div>
    <div class="nav-links">
        <span style="font-weight: 600; font-size: 0.85rem; margin-right: 20px;">
            👤 <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?>
        </span>
        <a href="logout.php" style="background: var(--danger); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; font-weight: 700;">LOGOUT</a>
    </div>
</header>

<div class="main-layout">
    <aside class="sidebar">
        <h3>Menu</h3>
        <a href="admin.php" class="side-link active">📊 System Reports</a>
        <a href="support_inbox.php" class="side-link">
            <span>✉️ Support Inbox</span>
            <?php if($new_msg_count > 0): ?>
                <span class="notif-badge"><?php echo $new_msg_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="#maintenance" class="side-link">⚙️ Maintenance</a>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
        <a href="dashboard.php" class="side-link" style="color: #666;">🏠 Student View</a>
    </aside>

    <main>
        <?php if ($alert_msg): ?>
            <div class="alert alert-success"><?php echo $alert_msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card"><h2><?php echo $total_items; ?></h2><p>Total Items</p></div>
            <div class="stat-card" style="border-color: var(--danger);"><h2><?php echo $lost_items; ?></h2><p>Lost Claims</p></div>
            <div class="stat-card" style="border-color: var(--success);"><h2><?php echo $found_items; ?></h2><p>Found Items</p></div>
            <div class="stat-card" style="border-color: #007bff;"><h2><?php echo $claimed_items; ?></h2><p>Claimed</p></div>
        </div>

        <div class="content-card" id="reports">
            <h3>📋 Master Reports Registry</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item Details</th>
                            <th>Category</th>
                            <th>Reporter</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($all_reports && $all_reports->num_rows > 0): ?>
                            <?php while($row = $all_reports->fetch_assoc()): ?>
                            <tr>
                                <td><small><?php echo date('M d, Y', strtotime($row['created_at'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['item_name']); ?></strong><br>
                                    <span class="status-<?php echo strtolower($row['status']); ?>" style="font-size: 0.75rem; text-transform: uppercase;">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name'] ?? 'System User'); ?></td>
                                <td>
                                    <a href="delete_report.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete permanently?')">Remove</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card maintenance-card" id="maintenance">
            <h3 style="color: var(--danger); border-bottom-color: #ffa39e;">⚠️ System Maintenance</h3>
            <p style="font-size: 0.85rem; color: #8c2f2c; margin-bottom: 20px;">Use these tools to manage global data. Be careful, these actions are permanent.</p>
            <div style="display: flex; gap: 15px;">
                <a href="backup_data.php" class="btn-action" style="background: var(--zetech-blue); color: white; padding: 12px 20px;">📥 Download Backup (.sql)</a>
                <form action="clear_data.php" method="POST" onsubmit="return confirm('CRITICAL: Delete all items and messages?')">
                    <button type="submit" name="confirm_clear" class="btn-action" style="background: var(--danger); color: white; padding: 12px 20px;">🗑️ Wipe Activity Logs</button>
                </form>
            </div>
        </div>
    </main>
</div>

<footer style="
    margin-top: 50px;
    padding: 30px 20px;
    background: rgba(0, 31, 63, 0.9);
    color: #ffffff;
    text-align: center;
    border-top: 3px solid #FFD700;
    font-family: 'Inter', sans-serif;
">
    <div style="max-width: 800px; margin: 0 auto;">
        <p style="font-weight: 700; letter-spacing: 1px; margin-bottom: 10px; color: #FFD700;">
            OFFICIAL STUDENT PROJECT DISCLOSURE
        </p>
        <p style="font-size: 0.85rem; line-height: 1.6; opacity: 0.9; margin-bottom: 15px;">
            This website is a <strong>Capstone Project</strong> developed by students at 
            <strong>Zetech University</strong> for the Course: <em>Web Application Development</em>. 
            All data collected is used solely for academic demonstration and testing of database 
            functionalities.
        </p>
        <div style="font-size: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            &copy; <?php echo date('Y'); ?> Zetech University Lost & Found System | 
            <span style="color: #FFD700;">Educational Purpose Only</span> | 
            <a href="https://www.zetech.ac.ke" target="_blank" style="color: #fff; text-decoration: underline;">Institutional Site</a>
        </div>
    </div>
</footer>
</body>
</html>