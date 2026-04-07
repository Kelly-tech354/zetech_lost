<?php
session_start();
include('config.php');

// Security: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the Item ID from the URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);

// RESTORED: Joining on 'reported_by' and selecting your specific columns
$query = "SELECT items.*, users.full_name, users.email, users.phone_number 
          FROM items 
          JOIN users ON items.reported_by = users.id 
          WHERE items.id = '$item_id'";

$result = $conn->query($query);

if (!$result || $result->num_rows == 0) {
    echo "Item not found.";
    exit();
}

$item = $result->fetch_assoc();

// --- NEW LOGIC: MATCHED USER VERIFICATION ---
// 1. Check if current user is the person who reported/found it
$is_reporter = ($item['reported_by'] == $user_id);

// 2. Check if there is an official match in the matches table for this user and this item
$match_query = "SELECT id FROM matches WHERE item_id = '$item_id' AND user_id = '$user_id'";
$match_result = $conn->query($match_query);
$is_matched_owner = ($match_result && $match_result->num_rows > 0);

// Contact info is ONLY visible if you found it OR you are the confirmed owner who got the email
$can_see_contact = ($is_reporter || $is_matched_owner);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details | Zetech Lost & Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --zetech-blue: #003366;
            --zetech-gold: #FFD700;
            --zetech-dark: #001f3f;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --danger: #d9534f;
            --success: #28a745;
            --warning: #f0ad4e;
        }

        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; color: #2d3436; min-height: 100vh;
        }

        /* --- NAVBAR MATCHING MY_REPORTS --- */
        .navbar { 
            background: var(--zetech-dark); color: white; padding: 0 5%; 
            display: flex; justify-content: space-between; align-items: center; 
            height: 80px; position: sticky; top: 0; z-index: 1000;
            border-bottom: 3px solid var(--zetech-gold);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .logo-area h2 { letter-spacing: 1px; font-weight: 700; margin: 0; cursor: pointer; }
        .logo-area span { color: var(--zetech-gold); }

        .btn-back-nav { 
            color: #d1d8e0; text-decoration: none; font-weight: 600; font-size: 0.9rem; 
            transition: 0.3s; border: 1px solid rgba(255,215,0,0.3); padding: 8px 15px; border-radius: 5px;
        }
        .btn-back-nav:hover { color: var(--zetech-gold); border-color: var(--zetech-gold); }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }

        .detail-card { 
            background: var(--glass-bg); border-radius: 15px; overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .item-header { 
            padding: 35px; background: white; border-bottom: 1px solid #eee; 
            display: flex; justify-content: space-between; align-items: center;
        }

        .status-pill { 
            padding: 6px 15px; border-radius: 20px; font-weight: 800; font-size: 0.75rem; 
            text-transform: uppercase; display: inline-block;
        }
        .pill-lost { background: #ffdada; color: #af1c1c; }
        .pill-found { background: #d4f8d4; color: #1e7e34; }

        .item-image-container { text-align: center; padding: 20px; background: #fff; }
        .item-image-container img { max-width: 100%; max-height: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .info-section { padding: 35px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .info-group h4 { margin: 0 0 8px 0; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        .info-group p { margin: 0; font-size: 1rem; color: var(--zetech-blue); font-weight: 600; }

        .description-box { padding: 0 35px 35px; }
        .description-box h4 { color: #888; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }
        .description-text { background: #f8f9fa; padding: 25px; border-radius: 10px; line-height: 1.6; color: #444; border: 1px solid #eee; }

        /* --- UPGRADE: INTERACTION SECTION --- */
        .interaction-area { padding: 20px 35px; background: #fff; border-top: 1px solid #eee; text-align: center; }
        .btn-action-main { padding: 12px 25px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 0.9rem; }
        .btn-claim-trigger { background: var(--success); color: white; }
        .btn-report-trigger { background: #007bff; color: white; }
        
        .hidden-form-box { 
            display: none; margin-top: 20px; text-align: left; background: #f9f9f9; 
            padding: 20px; border-radius: 10px; border: 1px solid #ddd; animation: fadeIn 0.4s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .form-control { width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #ccc; margin: 10px 0; font-family: inherit; }

        .owner-actions { 
            background: #fff3cd; padding: 20px 35px; border-top: 2px dashed #ffeeba;
            display: flex; justify-content: space-between; align-items: center;
        }
        .action-btns { display: flex; gap: 10px; }
        .btn-edit { background: var(--warning); color: #856404; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; }
        .btn-delete { background: var(--danger); color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; }

        .contact-footer { 
            background: #f0f2f5; padding: 30px 35px; border-top: 1px solid #e1e4e8;
            display: flex; align-items: center; justify-content: space-between;
        }
        .contact-info strong { display: block; color: var(--zetech-blue); margin-bottom: 5px; }
        .contact-info span { font-size: 0.9rem; color: #555; }
        
        .btn-claim { 
            background: var(--zetech-blue); color: white; padding: 12px 28px; 
            border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem;
            transition: 0.3s; border: none; cursor: pointer;
        }
        .btn-claim:hover { background: var(--zetech-dark); transform: translateY(-2px); }

        .restricted-msg { color: var(--danger); font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        footer { text-align:center; padding:50px; color:rgba(255,255,255,0.7); font-size: 0.9rem; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo-area">
        <h2 onclick="location.href='dashboard.php'">ZETECH <span>L&F</span></h2>
    </div>
    <a href="dashboard.php" class="btn-back-nav">← RETURN TO DASHBOARD</a>
</header>

<div class="container">
    <div class="detail-card">
        
        <?php if (!empty($item['item_image'])): ?>
        <div class="item-image-container">
            <img src="uploads/<?php echo htmlspecialchars($item['item_image']); ?>" alt="Item Image">
        </div>
        <?php endif; ?>

        <div class="item-header">
            <div>
                <span class="status-pill <?php echo (strtolower($item['status']) == 'lost') ? 'pill-lost' : 'pill-found'; ?>">
                    <?php echo htmlspecialchars($item['status']); ?>
                </span>
                <h1 style="margin:10px 0 0; color: var(--zetech-blue); font-size: 1.8rem;">
                    <?php echo htmlspecialchars($item['item_name']); ?>
                </h1>
            </div>
            <div style="font-size: 2.5rem; opacity: 0.2;">📁</div>
        </div>

        <?php if ($is_reporter && $item['status'] != 'Claimed'): ?>
        <div class="owner-actions">
            <div>
                <strong style="color: #856404;">Report Management</strong><br>
                <small>You reported this item. You can edit the details or delete the report.</small>
            </div>
            <div class="action-btns">
                <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn-edit">Edit Details</a>
                <a href="javascript:void(0);" class="btn-delete" 
                   onclick="performAjaxDelete(<?php echo $item['id']; ?>)">
                    Delete Report
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="info-section">
            <div class="info-group">
                <h4>Category</h4>
                <p><?php echo htmlspecialchars($item['category']); ?></p>
            </div>
            <div class="info-group">
                <h4>Location Reported</h4>
                <p>📍 <?php echo htmlspecialchars($item['location']); ?></p>
            </div>
            <div class="info-group">
                <h4>Reported Date</h4>
                <p>📅 <?php echo isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'Recently'; ?></p>
            </div>
            <div class="info-group">
                <h4>Official Reporter</h4>
                <p>👤 <?php echo htmlspecialchars($item['full_name']); ?></p>
            </div>
        </div>

        <div class="description-box">
            <h4>Description & Identifying Marks</h4>
            <div class="description-text">
                <?php echo nl2br(htmlspecialchars($item['description'] ?? $item['item_name'])); ?>
            </div>
        </div>

        <?php if (!$is_reporter && $item['status'] != 'Claimed'): ?>
        <div class="interaction-area">
            <?php if (strtolower($item['status']) == 'found'): ?>
                <button class="btn-action-main btn-claim-trigger" onclick="toggleForm('claimForm')">🙋 I am the Owner (Claim Item)</button>
                
                <div id="claimForm" class="hidden-form-box">
                    <h3 style="margin-top:0; color:var(--success);">Claim Verification</h3>
                    <p style="font-size:0.85rem; color:#666;">To confirm ownership, please describe unique details (e.g., serial number, hidden marks, or contents):</p>
                    <form action="claim_item.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <textarea name="proof_details" class="form-control" rows="4" placeholder="Enter proof of ownership here..." required></textarea>
                        <button type="submit" class="btn-claim" style="background:var(--success); width:100%;">Submit Verification Request</button>
                    </form>
                </div>

            <?php else: ?>
                <button class="btn-action-main btn-report-trigger" onclick="toggleForm('reportForm')">📢 I Found This Item</button>
                
                <div id="reportForm" class="hidden-form-box">
                    <h3 style="margin-top:0; color:#007bff;">Instant Report to Owner</h3>
                    <p style="font-size:0.85rem; color:#666;">This message will be sent directly to the person who lost this item:</p>
                    <form action="direct_report.php" method="POST">
                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $item['reported_by']; ?>">
                        <textarea name="message_text" class="form-control" rows="4" placeholder="Where did you find it? How can they get it?" required></textarea>
                        <button type="submit" class="btn-claim" style="background:#007bff; width:100%;">Send Alert to Owner</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="contact-footer">
            <div class="contact-info">
                <?php if ($item['status'] != 'Claimed'): ?>
                    <?php if ($can_see_contact): ?>
                        <strong>Contact Details:</strong>
                        <span>📧 <?php echo htmlspecialchars($item['email']); ?></span> | 
                        <span>📞 <?php echo htmlspecialchars($item['phone_number']); ?></span>
                    <?php else: ?>
                        <div class="restricted-msg">
                            🔒 Contact information is hidden for security.
                            <br><span style="font-weight:400; color:#666;">Verified owners or reporters can see contact info.</span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <strong style="color: var(--success);">✓ CASE CLOSED: Item Recovered</strong>
                <?php endif; ?>
            </div>

            <?php if ($item['reported_by'] == $user_id && $item['status'] != 'Claimed'): ?>
                <a href="finalize_claim.php?id=<?php echo $item['id']; ?>" 
                   class="btn-claim" 
                   style="background: var(--success);" 
                   onclick="return confirm('Are you sure the item has been returned?');">
                     Mark as Claimed
                </a>
            <?php elseif ($item['status'] != 'Claimed' && $can_see_contact): ?>
                <a href="mailto:<?php echo $item['email']; ?>?subject=Claiming Item: <?php echo $item['item_name']; ?>" class="btn-claim">Send Email</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="toastNotif" class="toast-notification">
    <span id="toastIcon"></span>
    <span id="toastMessage"></span>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> Zetech University | Official Lost and Found Registry.
</footer>

<script>
function toggleForm(formId) {
    const claimForm = document.getElementById('claimForm');
    const reportForm = document.getElementById('reportForm');
    
    if (formId === 'claimForm') {
        claimForm.style.display = claimForm.style.display === 'block' ? 'none' : 'block';
        if(reportForm) reportForm.style.display = 'none';
    } else {
        reportForm.style.display = reportForm.style.display === 'block' ? 'none' : 'block';
        if(claimForm) claimForm.style.display = 'none';
    }
}

function performAjaxDelete(itemId) {
    if (!confirm("Are you sure you want to permanently delete this report?")) {
        return;
    }
    const toast = document.getElementById('toastNotif');
    const toastMsg = document.getElementById('toastMessage');
    const formData = new FormData();
    formData.append('id', itemId);

    fetch('ajax_delete_item.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            toastMsg.innerText = "✓ " + data.message;
            toast.classList.add('show', 'toast-success');
            setTimeout(() => { window.location.href = 'dashboard.php?status=deleted#reports'; }, 2000);
        } else {
            toastMsg.innerText = "❌ Error: " + data.message;
            toast.classList.add('show', 'toast-error');
            setTimeout(() => { toast.classList.remove('show', 'toast-error'); }, 4000);
        }
    })
    .catch(error => {
        toastMsg.innerText = "❌ System error occurred.";
        toast.classList.add('show', 'toast-error');
    });
}
</script>

</body>
</html>