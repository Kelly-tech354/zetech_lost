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

// --- 2. FETCH USER DATA ---
// We use 'phone_number' to match your database schema
$query = "SELECT id, full_name, registration_number, email, phone_number FROM users WHERE id = '$user_id'";
$result = $conn->query($query);

if (!$result) {
    die("Database Error: " . $conn->error);
}
$user = $result->fetch_assoc();

// --- 3. NOTIFICATIONS COUNT ---
$unread_count = 0;
$check_notif = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_notif && $check_notif->num_rows > 0) {
    $notif_res = $conn->query("SELECT id FROM notifications WHERE user_id = '$user_id' AND is_read = 0");
    if ($notif_res) { $unread_count = $notif_res->num_rows; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Zetech Lost & Found</title>
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
        .sidebar-info { font-size: 0.85rem; color: #666; line-height: 1.6; margin-top: 10px; }

        .status-banner { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .success-banner { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .content-header { color: white; margin-bottom: 25px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }

        .profile-card { 
            background: var(--glass-bg); padding: 40px; border-radius: 15px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 700px;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.85rem; color: #444; text-transform: uppercase; }
        
        input { 
            width: 100%; padding: 14px; border: 1px solid #d1d8e0; border-radius: 8px; 
            font-size: 1rem; box-sizing: border-box; transition: 0.3s;
        }

        input:disabled { background: #f0f2f5; color: #888; cursor: not-allowed; border-color: #eee; }
        input[name="phone"] { border: 2px solid var(--zetech-blue); background: #fff; }

        .btn-save { 
            background: var(--zetech-blue); color: white; border: none; padding: 16px; 
            width: 100%; border-radius: 8px; font-size: 1rem; font-weight: 700; 
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        .btn-save:hover { background: var(--zetech-dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        .security-note { margin-top: 20px; padding: 15px; background: #fff9db; border-radius: 8px; border-left: 4px solid #f59f00; font-size: 0.8rem; color: #666; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo-area">
        <h2 onclick="location.href='dashboard.php'">ZETECH <span>L&F</span></h2>
    </div>
    
    <nav class="nav-links">
        <a href="dashboard.php" class="nav-link">DASHBOARD</a>
        <a href="my_reports.php" class="nav-link">MY SUBMISSIONS</a>
        <a href="profile.php" class="nav-link active">MY ACCOUNT</a>
        <div class="notif-wrapper" onclick="location.href='notifications.php'" style="cursor:pointer; position:relative;">
            <span style="font-size:1.2rem;">🔔</span>
            <?php if ($unread_count > 0): ?>
                <span style="position:absolute; top:-5px; right:-5px; background:var(--danger); color:white; border-radius:50%; padding:2px 6px; font-size:0.6rem; font-weight:bold;">
                    <?php echo $unread_count; ?>
                </span>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout-btn">SECURE LOGOUT</a>
    </nav>
</header>

<div class="main-layout">
    <aside>
        <div class="sidebar-card">
            <h3>Account Security</h3>
            <div class="sidebar-info">
                Official details like your <strong>Name</strong> and <strong>Registration Number</strong> are synced with the University Registrar and cannot be changed here.
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
            <p style="font-size:0.8rem; color:#888;">Need to change official data? Visit the ICT office.</p>
        </div>
    </aside>

    <main>
        <div class="content-header">
            <h1 style="margin:0;">Profile Management</h1>
            <p style="margin:5px 0 0; opacity:0.9;">Manage your contact information for item recovery</p>
        </div>

        <div class="profile-card">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'updated'): ?>
                <div class="status-banner success-banner">✅ Contact details updated successfully!</div>
            <?php endif; ?>

            <form action="update_profile.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Registration / Student Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['registration_number']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Primary Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Active Phone Number (For Verification)</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="e.g. 0712345678" required>
                </div>

                <button type="submit" class="btn-save">UPDATE CONTACT DETAILS</button>
            </form>

            <div class="security-note">
                <strong>Privacy Policy:</strong> Your phone number is only shared with individuals who have verified your ownership of a lost item.
            </div>
        </div>
    </main>
</div>

<footer style="text-align:center; padding:50px; color:rgba(255,255,255,0.7); font-size: 0.9rem;">
    &copy; <?php echo date('Y'); ?> Zetech University | Official Lost and Found Portal.
</footer>

</body>
</html>