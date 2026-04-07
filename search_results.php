<?php
session_start();
include('config.php');

// Security Check: Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = "";
$category_filter = "";

// --- 1. HANDLE SEARCH & CATEGORY LOGIC ---
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_query = mysqli_real_escape_string($conn, $_GET['query']);
}

if (isset($_GET['category']) && !empty(trim($_GET['category']))) {
    $category_filter = mysqli_real_escape_string($conn, $_GET['category']);
}

// Build the dynamic query
$sql = "SELECT * FROM items WHERE status != 'Claimed'";

if (!empty($search_query)) {
    $sql .= " AND (item_name LIKE '%$search_query%' 
                 OR description LIKE '%$search_query%' 
                 OR location LIKE '%$search_query%')";
}

if (!empty($category_filter)) {
    $sql .= " AND category = '$category_filter'";
}

// Match the column name used in your database (date_reported or created_at)
$order_col = "id"; // Default fallback
$check_cols = $conn->query("SHOW COLUMNS FROM items LIKE 'created_at'");
if ($check_cols && $check_cols->num_rows > 0) { $order_col = "created_at"; }

$sql .= " ORDER BY $order_col DESC";
$results = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Zetech Lost & Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --zetech-blue: #003366;
            --zetech-gold: #FFD700;
            --zetech-dark: #001f3f;
            --glass-bg: rgba(255, 255, 255, 0.92);
            --danger: #d9534f;
            --success: #28a745;
        }

        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; color: #2d3436; min-height: 100vh;
        }

        /* --- NAVBAR STYLING --- */
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
        .nav-link:hover { color: var(--zetech-gold); }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }

        /* --- HEADER AREA --- */
        .results-header { margin-bottom: 30px; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .results-header h1 { margin: 0; font-size: 2rem; }
        .results-header p { opacity: 0.9; font-size: 1.1rem; }

        /* --- GRID & CARDS --- */
        .results-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 25px; 
        }

        .item-card { 
            background: var(--glass-bg); border-radius: 15px; overflow: hidden; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.1); transition: 0.3s;
            display: flex; flex-direction: column;
        }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(0,0,0,0.2); }

        .item-img { width: 100%; height: 200px; object-fit: cover; background: #eee; }

        .item-info { padding: 20px; flex-grow: 1; }
        
        .status-pill { 
            padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; 
            font-weight: 800; text-transform: uppercase; margin-bottom: 12px; display: inline-block;
        }
        .pill-lost { background: #ffdada; color: #af1c1c; }
        .pill-found { background: #d4f8d4; color: #1e7e34; }

        .item-name { font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: var(--zetech-blue); }
        .item-meta { font-size: 0.85rem; color: #555; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }

        .description-preview { 
            font-size: 0.85rem; color: #666; line-height: 1.5; margin-top: 10px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        .btn-view { 
            display: block; text-align: center; background: var(--zetech-blue); 
            color: white; padding: 12px; text-decoration: none; border-radius: 8px; 
            font-weight: 700; font-size: 0.85rem; margin-top: 20px; transition: 0.3s;
        }
        .btn-view:hover { background: var(--zetech-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }

        /* --- EMPTY STATE --- */
        .no-results {
            background: var(--glass-bg); padding: 60px 20px; border-radius: 15px; 
            text-align: center; grid-column: 1 / -1; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .no-results h3 { color: var(--zetech-blue); font-size: 1.5rem; margin-bottom: 10px; }
        .btn-back { 
            display: inline-block; margin-top: 20px; color: var(--zetech-blue); 
            text-decoration: none; font-weight: 700; border-bottom: 2px solid var(--zetech-blue);
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo-area" onclick="location.href='dashboard.php'">
        <h2>ZETECH <span>L&F</span></h2>
    </div>
    <nav class="nav-links">
        <a href="dashboard.php" class="nav-link">DASHBOARD</a>
        <a href="my_reports.php" class="nav-link">MY SUBMISSIONS</a>
        <a href="logout.php" class="nav-link" style="color: var(--danger);">LOGOUT</a>
    </nav>
</header>

<div class="container">
    <div class="results-header">
        <h1>
            <?php 
                if (!empty($category_filter)) {
                    echo "Category: " . htmlspecialchars($category_filter);
                } else {
                    echo "Search Results";
                }
            ?>
        </h1>
        <p>
            <?php if(!empty($search_query)): ?>
                Found matches for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
            <?php else: ?>
                Viewing all items in this classification.
            <?php endif; ?>
        </p>
    </div>

    <div class="results-grid">
        <?php if ($results && $results->num_rows > 0): ?>
            <?php while($row = $results->fetch_assoc()): 
                $img = !empty($row['item_image']) ? $row['item_image'] : 'default_item.png';
                $pill_class = ($row['status'] == 'Lost') ? 'pill-lost' : 'pill-found';
            ?>
                <div class="item-card">
                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" class="item-img" onerror="this.src='https://via.placeholder.com/400x250?text=No+Photo+Available';">
                    <div class="item-info">
                        <span class="status-pill <?php echo $pill_class; ?>"><?php echo $row['status']; ?></span>
                        <div class="item-name"><?php echo htmlspecialchars($row['item_name']); ?></div>
                        
                        <div class="item-meta">📍 <strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></div>
                        <div class="item-meta">📁 <strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?></div>
                        
                        <div class="description-preview">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </div>
                        
                        <a href="view_details.php?id=<?php echo $row['id']; ?>" class="btn-view">VIEW FULL DETAILS</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results">
                <span style="font-size: 4rem;">🔍</span>
                <h3>No items found</h3>
                <p>We couldn't find anything matching your request in the registry.</p>
                <a href="dashboard.php" class="btn-back">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer style="text-align:center; padding:50px; color:rgba(255,255,255,0.7); font-size: 0.9rem;">
    &copy; <?php echo date('Y'); ?> Zetech University | Official Lost and Found Registry.
</footer>

</body>
</html>