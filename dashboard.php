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

// --- 2. SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 3. DATA FETCHING ---
$safe_uid = $conn->real_escape_string($user_id);

// Fetch Unread Notifications (General)
$unread_count = 0;
$check_notif = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_notif && $check_notif->num_rows > 0) {
    $notif_res = $conn->query("SELECT id FROM notifications WHERE user_id = '$safe_uid' AND is_read = 0");
    if ($notif_res) { $unread_count = $notif_res->num_rows; }
}

// Fetch Unread Support Replies
$support_unread = 0;
$check_support = $conn->query("SHOW TABLES LIKE 'support_messages'");
if ($check_support && $check_support->num_rows > 0) {
    $supp_res = $conn->query("SELECT id FROM support_messages WHERE user_id = '$safe_uid' AND admin_reply IS NOT NULL AND admin_reply != '' AND is_read_by_user = 0");
    if ($supp_res) { $support_unread = $supp_res->num_rows; }
}

// FETCH RECENT NOTIFICATIONS FOR DROPDOWN
$recent_notifs = [];
if ($check_notif && $check_notif->num_rows > 0) {
    // UPDATED: Selecting ID to link to details if it's a claim
    $notif_list_res = $conn->query("SELECT * FROM notifications WHERE user_id = '$safe_uid' ORDER BY created_at DESC LIMIT 5");
    if ($notif_list_res) {
        while($row = $notif_list_res->fetch_assoc()) {
            $recent_notifs[] = $row;
        }
    }
}

// --- FETCH PERSONAL MATCHES ---
$match_items = [];
$check_matches = $conn->query("SHOW TABLES LIKE 'matches'");
if ($check_matches && $check_matches->num_rows > 0) {
    $match_res = $conn->query("SELECT items.* FROM items 
                               JOIN matches ON items.id = matches.item_id 
                               WHERE matches.user_id = '$safe_uid' AND items.status != 'Claimed' AND items.status != 'Resolved'
                               ORDER BY items.id DESC");
    if ($match_res) {
        while($m_row = $match_res->fetch_assoc()) {
            $match_items[] = $m_row;
        }
    }
}

$lost_count = $conn->query("SELECT COUNT(*) as t FROM items WHERE status='Lost'")->fetch_assoc()['t'];
$found_count = $conn->query("SELECT COUNT(*) as t FROM items WHERE status='Found'")->fetch_assoc()['t'];
$match_count = count($match_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Dashboard | Zetech Lost & Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --zetech-blue: #003366;
            --zetech-gold: #FFD700;
            --zetech-dark: #001f3f;
            --glass-bg: rgba(255, 255, 255, 0.92);
            --danger: #d9534f;
            --success: #28a745;
            --warning-orange: #f39c12;
        }

        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; color: #2d3436; min-height: 100vh;
        }

        /* --- TOAST NOTIFICATION --- */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .custom-toast {
            background: var(--success); color: white; padding: 15px 25px; border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 12px;
            font-weight: 600; font-size: 0.9rem; transform: translateX(150%); transition: 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .custom-toast.show { transform: translateX(0); }

        /* --- MODERN NAVBAR --- */
        .navbar { 
            background: var(--zetech-dark); color: white; padding: 0 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            height: 80px; position: sticky; top: 0; z-index: 1000;
            border-bottom: 3px solid var(--zetech-gold);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .logo-area h2 { letter-spacing: 1px; font-weight: 700; margin: 0; cursor: pointer; }
        .logo-area span { color: var(--zetech-gold); }

        .search-bar { flex: 1; max-width: 500px; margin: 0 40px; position: relative; }
        .search-bar input { 
            width: 100%; padding: 12px 20px; border-radius: 8px; border: none; 
            background: rgba(255,255,255,0.15); color: white; outline: none; font-size: 0.9rem;
        }
        .search-bar input::placeholder { color: #ccc; }

        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-link { color: #d1d8e0; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--zetech-gold); }

        /* --- NOTIFICATION DROPDOWN STYLES --- */
        .notif-wrapper { position: relative; display: flex; align-items: center; }
        .notif-badge {
            position: absolute; top: -5px; right: -8px;
            background: var(--danger); color: white;
            font-size: 0.65rem; font-weight: 800;
            padding: 2px 6px; border-radius: 20px;
            border: 2px solid var(--zetech-dark);
        }

        .notif-dropdown {
            display: none; position: absolute; top: 55px; right: -10px;
            width: 340px; background: white; border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); z-index: 2000;
            overflow: hidden; border: 1px solid #eee;
        }

        .notif-dropdown.show { display: block; animation: slideDown 0.2s ease-out; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notif-header { 
            padding: 15px; background: #f8f9fa; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; font-weight: 700; font-size: 0.9rem;
            color: var(--zetech-blue);
        }

        .notif-body { max-height: 320px; overflow-y: auto; }

        .notif-item { 
            padding: 15px; border-bottom: 1px solid #f1f1f1; transition: 0.2s; cursor: pointer;
        }
        .notif-item:hover { background: #f9f9f9; }
        .notif-item.unread { background: #fffdf2; border-left: 4px solid var(--zetech-gold); }

        .notif-item p { margin: 0; font-size: 0.85rem; color: #333; line-height: 1.4; }
        .notif-item small { color: #999; font-size: 0.75rem; display: block; margin-top: 5px; }

        .notif-empty { padding: 40px 20px; text-align: center; color: #999; font-size: 0.9rem; }

        .notif-footer {
            display: block; text-align: center; padding: 12px;
            background: #f8f9fa; color: var(--zetech-blue);
            text-decoration: none; font-weight: 700; font-size: 0.85rem;
            border-top: 1px solid #eee;
        }

        .logout-btn { 
            background: var(--danger); color: white; padding: 10px 22px; 
            border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.8rem;
            transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .logout-btn:hover { background: #c9302c; transform: translateY(-1px); }

        /* --- LAYOUT & CONTENT --- */
        .main-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; max-width: 1400px; margin: 40px auto; padding: 0 20px; }

        .sidebar-card { background: var(--glass-bg); padding: 25px; border-radius: 15px; height: fit-content; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .sidebar-card h3 { margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid #ddd; padding-bottom: 10px; color: var(--zetech-blue); }
        
        .quick-link { 
            display: flex; align-items: center; justify-content: space-between; padding: 12px; text-decoration: none; 
            color: #444; font-weight: 600; border-radius: 8px; margin-bottom: 8px; transition: 0.2s;
        }
        .quick-link:hover { background: #f0f2f5; color: var(--zetech-blue); padding-left: 18px; }

        .support-dot {
            background: var(--danger); color: white; font-size: 0.6rem;
            padding: 2px 6px; border-radius: 10px; font-weight: 800;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(217, 83, 79, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(217, 83, 79, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(217, 83, 79, 0); }
        }

        .welcome-header { margin-bottom: 30px; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .welcome-header h1 { margin: 0; font-size: 2rem; }

        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { 
            background: var(--glass-bg); padding: 25px; border-radius: 15px; 
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1); border-left: 8px solid var(--zetech-blue);
        }
        .stat-box.lost { border-left-color: var(--danger); }
        .stat-box.found { border-left-color: var(--success); }
        .stat-box.matches { border-left-color: var(--warning-orange); }
        .stat-box h2 { margin: 0; font-size: 2.5rem; color: var(--zetech-blue); }
        .stat-box p { margin: 0; color: #666; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; }

        .match-alert-card {
            background: #fff9db; border: 2px solid #fab005; border-radius: 15px;
            padding: 25px; margin-bottom: 30px; animation: glow 2s infinite alternate;
        }
        @keyframes glow {
            from { box-shadow: 0 0 5px rgba(250, 176, 5, 0.2); }
            to { box-shadow: 0 0 20px rgba(250, 176, 5, 0.5); }
        }

        .official-card { background: var(--glass-bg); padding: 35px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 40px; }
        .official-card h2 { margin-top: 0; color: var(--zetech-blue); display: flex; align-items: center; gap: 10px; }
        
        .grid-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }

        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.85rem; color: #444; }
        input, select, textarea { 
            width: 100%; padding: 14px; border: 1px solid #d1d8e0; border-radius: 8px; 
            font-size: 1rem; background: #fff; transition: 0.3s;
        }

        .btn-primary { 
            background: var(--zetech-blue); color: white; border: none; padding: 16px; 
            width: 100%; border-radius: 8px; font-size: 1rem; font-weight: 700; 
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-primary:hover { background: var(--zetech-dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        .section-title { color: white; margin-bottom: 20px; font-size: 1.5rem; }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .item-card { 
            background: white; border-radius: 12px; overflow: hidden; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: 0.3s;
        }
        .item-card img { width: 100%; height: 200px; object-fit: cover; }
        .item-info { padding: 20px; }
        .status-pill { 
            padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; 
            font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: inline-block;
        }
        .pill-lost { background: #ffdada; color: #af1c1c; }
        .pill-found { background: #d4f8d4; color: #1e7e34; }
        .pill-match { background: #fff3bf; color: #e67e22; border: 1px solid #fab005; }

        .btn-action {
            display: block; text-align: center; background: var(--zetech-blue); color: white;
            text-decoration: none; padding: 10px; border-radius: 6px; font-size: 0.8rem;
            font-weight: 700; margin-top: 15px; transition: 0.2s;
        }
        .btn-action:hover { background: var(--zetech-dark); }

        /* Style for Resolve Button */
        .btn-resolve {
            background: var(--success); color: white; margin-top: 5px;
        }
        .btn-resolve:hover { background: #218838; }
    </style>
</head>
<body>

<div class="toast-container" id="toastContainer"></div>

<header class="navbar">
    <div class="logo-area">
        <h2 onclick="location.href='dashboard.php'">ZETECH <span>L&F</span></h2>
    </div>
    
    <div class="search-bar">
        <form action="search_results.php" method="GET">
            <input type="text" name="query" placeholder="Search by Item Name, ID Number, or Location...">
        </form>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php" class="nav-link active">DASHBOARD</a>
        <a href="my_reports.php" class="nav-link">MY SUBMISSIONS</a>
        <a href="profile.php" class="nav-link">MY ACCOUNT</a>
        
        <div class="notif-wrapper" id="notifTrigger">
            <span style="font-size:1.3rem; cursor:pointer; position:relative; top:2px;">🔔</span>
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>

            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Notifications</span>
                    <a href="notifications.php" style="color:var(--zetech-blue); font-size:0.75rem; text-decoration:none;">View All</a>
                </div>
                <div class="notif-body">
                    <?php if (!empty($recent_notifs)): ?>
                        <?php foreach ($recent_notifs as $n): 
                            // LOGIC: If message contains 'CLAIM ALERT', redirect to details instead of general page
                            $target_url = "notifications.php";
                            if (strpos($n['message'], 'CLAIM ALERT') !== false) {
                                // We try to find the claim ID if stored or just go to notifications to see the list
                                $target_url = "notifications.php"; 
                            }
                        ?>
                            <div class="notif-item <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>" onclick="location.href='<?php echo $target_url; ?>'">
                                <p><?php echo htmlspecialchars($n['message']); ?></p>
                                <small>📅 <?php echo date('M d, g:i a', strtotime($n['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">
                            <p style="margin:0; font-size:0.8rem; color:#999;">No new notifications</p>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="notifications.php" class="notif-footer">See all notifications</a>
            </div>
        </div>

        <a href="logout.php" class="logout-btn">SECURE LOGOUT</a>
    </nav>
</header>

<div class="main-layout">
    <aside>
        <div class="sidebar-card">
            <h3>Quick Links</h3>
            <a href="my_reports.php" class="quick-link"><span>📂 View My Reports</span></a>
            <a href="profile.php" class="quick-link"><span>⚙️ Account Settings</span></a>
            
            <a href="contact_support.php" class="quick-link">
                <span>💬 Help & Support</span>
                <?php if ($support_unread > 0): ?>
                    <span class="support-dot"><?php echo $support_unread; ?> NEW</span>
                <?php endif; ?>
            </a>

            <a href="search_results.php?category=Electronics" class="quick-link"><span>📱 Electronics</span></a>
            <a href="search_results.php?category=Documents" class="quick-link"><span>🪪 Documents & IDs</span></a>
        </div>
    </aside>

    <main>
        <div class="welcome-header">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?></h1>
            <p>Official Lost & Found Management Portal</p>
        </div>

        <div class="stats-row">
            <div class="stat-box lost">
                <div><p>Active Lost Claims</p><h2><?php echo $lost_count; ?></h2></div>
                <span style="font-size: 2rem;">🔍</span>
            </div>
            <div class="stat-box matches">
                <div><p>Matched For You</p><h2><?php echo $match_count; ?></h2></div>
                <span style="font-size: 2rem;">🎉</span>
            </div>
            <div class="stat-box found">
                <div><p>Recovered Items</p><h2><?php echo $found_count; ?></h2></div>
                <span style="font-size: 2rem;">📦</span>
            </div>
        </div>

        <?php if (!empty($match_items)): ?>
            <div class="match-alert-card">
                <h2 style="margin:0; color: #e67e22; font-size: 1.4rem;">🎉 Potential Match Found!</h2>
                <p style="margin: 5px 0 20px; color: #666; font-size: 0.9rem;">We found items that match your reports.</p>
                <div class="items-grid">
                    <?php foreach ($match_items as $m): ?>
                        <div class="item-card" style="border: 2px solid #fab005;">
                            <img src="uploads/<?php echo htmlspecialchars($m['item_image']); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=Match+Found';">
                            <div class="item-info">
                                <span class="status-pill pill-match">Match Identified</span>
                                <h3 style="margin:5px 0; font-size: 1rem; color:var(--zetech-blue);"><?php echo htmlspecialchars($m['item_name']); ?></h3>
                                <p style="font-size:0.8rem; color:#777;">📍 Found at: <?php echo htmlspecialchars($m['location']); ?></p>
                                <a href="view_details.php?id=<?php echo $m['id']; ?>" class="btn-action">VIEW & CLAIM ITEM</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="official-card">
            <h2>📢 Official Item Report Form</h2>
            <form action="report_item.php" method="POST" enctype="multipart/form-data">
                <div class="grid-form">
                    <div class="form-group full-width">
                        <label>Item Name / Title</label>
                        <input type="text" name="item_name" placeholder="Enter a clear title" required>
                    </div>
                    <div class="form-group">
                        <label>Classification Category</label>
                        <select name="category" required>
                            <option value="Electronics">Electronics & Gadgets</option>
                            <option value="Documents">University Documents / IDs</option>
                            <option value="Personal Items">Clothing & Personal Accessories</option>
                            <option value="Books">Educational Materials / Books</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Report Status</label>
                        <select name="status" required>
                            <option value="Lost">Report as Lost Item</option>
                            <option value="Found">Report as Found Item</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Last Known Location</label>
                        <input type="text" name="location" placeholder="e.g. Library" required>
                    </div>
                    <div class="form-group">
                        <label>Supporting Evidence (Photo)</label>
                        <input type="file" name="item_image" accept="image/*">
                    </div>
                    <div class="form-group full-width">
                        <label>Detailed Description</label>
                        <textarea name="description" rows="3" placeholder="Identifying marks..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary">SUBMIT OFFICIAL REPORT</button>
            </form>
        </div>

        <h2 class="section-title">Recent Community Activity</h2>
        <div class="items-grid">
            <?php
            // UPDATED QUERY: Exclude Claimed or Resolved items from general feed
            $items_query = $conn->query("SELECT * FROM items WHERE status != 'Claimed' AND status != 'Resolved' ORDER BY id DESC LIMIT 6");
            while ($item = $items_query->fetch_assoc()):
                $img = !empty($item['item_image']) ? $item['item_image'] : 'default_item.png';
                $pill_class = ($item['status'] == 'Lost') ? 'pill-lost' : 'pill-found';
            ?>
                <div class="item-card">
                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=No+Photo';">
                    <div class="item-info">
                        <span class="status-pill <?php echo $pill_class; ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                        <h3 style="margin:5px 0; color:var(--zetech-blue);"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                        <p style="font-size:0.8rem; color:#777;">📍 <?php echo htmlspecialchars($item['location']); ?></p>
                        
                        <a href="view_details.php?id=<?php echo $item['id']; ?>" class="btn-action" style="background:#eee; color:#444;">View Details</a>
                        
                        <?php if($item['reported_by'] == $user_id): ?>
                            <a href="resolve_item.php?id=<?php echo $item['id']; ?>" class="btn-action btn-resolve" onclick="return confirm('Is this item returned to the owner? This will remove it from the list.')">Mark as Resolved</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<footer style="margin-top: 50px; padding: 30px 20px; background: rgba(0, 31, 63, 0.9); color: #ffffff; text-align: center; border-top: 3px solid #FFD700; font-family: 'Inter', sans-serif;">
    <div style="max-width: 800px; margin: 0 auto;">
        <p style="font-weight: 700; letter-spacing: 1px; margin-bottom: 10px; color: #FFD700;">OFFICIAL STUDENT PROJECT DISCLOSURE</p>
        <p style="font-size: 0.85rem; line-height: 1.6; opacity: 0.9; margin-bottom: 15px;">
            This website is a <strong>Capstone Project</strong> developed by students at <strong>Zetech University</strong> for the Course: <em>Web Application Development</em>. All data collected is used solely for academic demonstration and testing of database functionalities.
        </p>
        <div style="font-size: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            &copy; <?php echo date('Y'); ?> Zetech University Lost & Found System | <span style="color: #FFD700;">Educational Purpose Only</span> | <a href="https://www.zetech.ac.ke" target="_blank" style="color: #fff; text-decoration: underline;">Institutional Site</a>
        </div>
    </div>
</footer>

<script>
    // --- DROPDOWN LOGIC ---
    const notifTrigger = document.getElementById('notifTrigger');
    const notifDropdown = document.getElementById('notifDropdown');

    notifTrigger.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
    });

    document.addEventListener('click', function(event) {
        if (!notifTrigger.contains(event.target)) {
            notifDropdown.classList.remove('show');
        }
    });

    // --- SUCCESS TOAST LOGIC ---
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status')) {
            const status = urlParams.get('status');
            let msg = "";
            
            if (status === 'claim_sent' || status === 'owner_notified') {
                msg = "Message sent successfully! Wait for verification.";
            } else if (status === 'resolved') {
                msg = "Item successfully marked as resolved!";
            }

            if (msg !== "") {
                const container = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = 'custom-toast';
                toast.innerHTML = `<span>✅</span> <span>${msg}</span>`;
                container.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 100);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        toast.remove();
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }, 500);
                }, 4000);
            }
        }
    };
</script>

</body>
</html>