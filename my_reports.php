<?php
session_start();
include('config.php');

// --- 1. SECURITY & TIMEOUT ---
$timeout_duration = 900; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    session_unset(); session_destroy();
    header("Location: login.html?error=timeout"); exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.html"); exit(); 
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 2. NOTIFICATIONS & STATS ---
$unread_count = 0;
$check_notif = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_notif && $check_notif->num_rows > 0) {
    $notif_res = $conn->query("SELECT id FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
    if ($notif_res) { $unread_count = $notif_res->num_rows; }
}

$my_lost = $conn->query("SELECT COUNT(*) as t FROM items WHERE reported_by = '$user_id' AND status='Lost'")->fetch_assoc()['t'];
$my_found = $conn->query("SELECT COUNT(*) as t FROM items WHERE reported_by = '$user_id' AND status='Found'")->fetch_assoc()['t'];

// --- 3. FETCH USER REPORTS ---
$query = "SELECT * FROM items WHERE reported_by = '$user_id' ORDER BY id DESC";
$my_items = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions | Zetech Lost & Found</title>
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
            background-size: cover; color: #2d3436; min-height: 100vh;
        }

        /* --- SUCCESS POPUP STYLES --- */
        .alert-popup {
            position: fixed; top: 20px; right: 20px;
            background: var(--success); color: white;
            padding: 15px 25px; border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 2000; display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.5s ease-out forwards;
        }

        @keyframes slideIn {
            from { transform: translateX(120%); }
            to { transform: translateX(0); }
        }

        .alert-close { cursor: pointer; font-weight: bold; margin-left: 10px; }

        /* --- NAVBAR & LAYOUT --- */
        .navbar { 
            background: var(--zetech-dark); color: white; padding: 0 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            height: 80px; position: sticky; top: 0; z-index: 1000;
            border-bottom: 3px solid var(--zetech-gold);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .logo-area h2 { letter-spacing: 1px; font-weight: 700; margin: 0; cursor: pointer; }
        .logo-area span { color: var(--zetech-gold); }

        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-link { color: #d1d8e0; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--zetech-gold); }

        .logout-btn { 
            background: var(--danger); color: white; padding: 10px 22px; 
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.8rem;
        }

        .main-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; max-width: 1400px; margin: 40px auto; padding: 0 20px; }

        .sidebar-card { background: var(--glass-bg); padding: 25px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .sidebar-card h3 { margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid #ddd; padding-bottom: 10px; color: var(--zetech-blue); }
        
        .stat-mini { display: flex; justify-content: space-between; padding: 10px 0; font-size: 0.85rem; font-weight: 600; color: #555; }
        .stat-mini span { color: var(--zetech-blue); }

        .content-header { color: white; margin-bottom: 25px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        
        .official-table-card { 
            background: var(--glass-bg); padding: 30px; border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th { 
            text-align: left; padding: 15px; background: #f0f2f5; 
            color: var(--zetech-blue); font-weight: 700; font-size: 0.85rem; 
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        td { padding: 18px 15px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        tr:hover td { background: rgba(0, 51, 102, 0.02); }

        .status-pill { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; 
            font-weight: 800; text-transform: uppercase; display: inline-block;
        }
        .pill-Lost { background: #ffdada; color: #af1c1c; }
        .pill-Found { background: #d4f8d4; color: #1e7e34; }
        .pill-claimed { background: #e2e8f0; color: #475569; }

        .btn-action { 
            background: var(--zetech-blue); color: white; padding: 8px 15px; 
            border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 700;
            transition: 0.3s; display: inline-block;
        }
        .btn-action:hover { background: var(--zetech-dark); transform: translateY(-1px); }
    </style>
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert-popup" id="successAlert">
        <span>✅ Item successfully marked as claimed!</span>
        <span class="alert-close" onclick="document.getElementById('successAlert').style.display='none'">&times;</span>
    </div>
    <script>
        // Automatically hide the alert after 4 seconds
        setTimeout(() => {
            const alert = document.getElementById('successAlert');
            if(alert) alert.style.display = 'none';
        }, 4000);
    </script>
<?php endif; ?>

<header class="navbar">
    <div class="logo-area">
        <h2 onclick="location.href='dashboard.php'">ZETECH <span>L&F</span></h2>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php" class="nav-link">DASHBOARD</a>
        <a href="my_reports.php" class="nav-link active">MY SUBMISSIONS</a>
        <a href="profile.php" class="nav-link">MY ACCOUNT</a>
        <a href="logout.php" class="logout-btn">SECURE LOGOUT</a>
    </nav>
</header>

<div class="main-layout">
    <aside>
        <div class="sidebar-card">
            <h3>Registry Summary</h3>
            <div class="stat-mini">My Lost Reports <span><?php echo $my_lost; ?></span></div>
            <div class="stat-mini">My Found Reports <span><?php echo $my_found; ?></span></div>
        </div>
    </aside>

    <main>
        <div class="content-header">
            <h1 style="margin:0;">Personal Registry</h1>
            <p style="margin:5px 0 0; opacity:0.9;">Tracking all items reported by <?php echo htmlspecialchars($full_name); ?></p>
        </div>

        <div class="official-table-card">
            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Reported Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_items && $my_items->num_rows > 0): ?>
                        <?php while($item = $my_items->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--zetech-blue);"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>
                                    <span class="status-pill pill-<?php echo $item['status']; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td style="color: #666;">📍 <?php echo htmlspecialchars($item['location']); ?></td>
                                <td><a href="view_details.php?id=<?php echo $item['id']; ?>" class="btn-action">View File</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:50px;">No reports found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<footer style="text-align:center; padding:50px; color:rgba(255,255,255,0.7); font-size: 0.9rem;">
    &copy; <?php echo date('Y'); ?> Zetech University Lost & Found.
</footer>

</body>
</html>