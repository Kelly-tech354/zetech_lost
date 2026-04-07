<?php
session_start();
include('config.php');

// Security: Only Admins should see detailed analytics
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    die("Access Denied. Reports are restricted to Administrators.");
}

// 1. Get Totals
$total_items = $conn->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];
$lost_count = $conn->query("SELECT COUNT(*) as count FROM items WHERE status='Lost'")->fetch_assoc()['count'];
$found_count = $conn->query("SELECT COUNT(*) as count FROM items WHERE status='Found'")->fetch_assoc()['count'];
$claimed_count = $conn->query("SELECT COUNT(*) as count FROM items WHERE status='Claimed'")->fetch_assoc()['count'];

// 2. Calculate Recovery Rate (Percentage of found items that were claimed)
$recovery_rate = ($found_count > 0) ? round(($claimed_count / $found_count) * 100, 2) : 0;

// 3. Get Category Distribution
$categories = $conn->query("SELECT category, COUNT(*) as count FROM items GROUP BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytical Reports - Zetech L&F</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .report-container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #003366; margin-bottom: 30px; padding-bottom: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 40px; }
        .stat-card { padding: 20px; border-radius: 8px; text-align: center; color: white; }
        .bg-blue { background: #003366; }
        .bg-red { background: #d9534f; }
        .bg-green { background: #5cb85c; }
        .bg-purple { background: #6f42c1; }
        
        .stat-card h2 { margin: 0; font-size: 2rem; }
        .stat-card p { margin: 5px 0 0; font-size: 0.9rem; opacity: 0.9; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        
        .recovery-box { background: #e7f3ff; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; }
        .recovery-box h3 { color: #003366; margin: 0; }
        
        @media print { .print-btn { display: none; } } /* Hide button when printing */
        .print-btn { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; float: right; }
    </style>
</head>
<body>

<div class="report-container">
    <button class="print-btn" onclick="window.print()">Print Report</button>
    <div class="header">
        <h1>Zetech University</h1>
        <h3>Lost and Found Analytical Report</h3>
        <p>Generated on: <?php echo date('F d, Y'); ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card bg-blue"><h2><?php echo $total_items; ?></h2><p>Total Items</p></div>
        <div class="stat-card bg-red"><h2><?php echo $lost_count; ?></h2><p>Currently Lost</p></div>
        <div class="stat-card bg-green"><h2><?php echo $found_count; ?></h2><p>Items Found</p></div>
        <div class="stat-card bg-purple"><h2><?php echo $claimed_count; ?></h2><p>Successfully Claimed</p></div>
    </div>

    <div class="recovery-box">
        <h3>System Recovery Rate: <?php echo $recovery_rate; ?>%</h3>
        <p>This represents the percentage of found items that have been returned to their owners.</p>
    </div>

    <h3>Items by Category</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Number of Items</th>
            </tr>
        </thead>
        <tbody>
            <?php while($cat = $categories->fetch_assoc()): ?>
            <tr>
                <td><?php echo $cat['category'] ? $cat['category'] : 'Uncategorized'; ?></td>
                <td><?php echo $cat['count']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; font-size: 0.8rem; color: #888;">
        <p>End of Official Report - Zetech Management System</p>
    </div>
</div>

</body>
</html>