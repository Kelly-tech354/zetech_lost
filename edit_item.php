<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing data
$sql = "SELECT * FROM items WHERE id = '$item_id' AND reported_by = '$user_id'";
$result = $conn->query($sql);
$item = $result->fetch_assoc();

if (!$item) { echo "Unauthorized or item not found."; exit(); }

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    $update_sql = "UPDATE items SET item_name='$name', category='$cat', location='$loc', description='$desc' WHERE id='$item_id'";
    
    // Optional: Handle new photo upload if provided
    if (!empty($_FILES['item_image']['name'])) {
        $imgName = time() . '_' . $_FILES['item_image']['name'];
        move_uploaded_file($_FILES['item_image']['tmp_name'], "uploads/" . $imgName);
        $update_sql = "UPDATE items SET item_name='$name', category='$cat', location='$loc', description='$desc', item_image='$imgName' WHERE id='$item_id'";
    }

    if ($conn->query($update_sql)) {
        header("Location: view_details.php?id=$item_id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report | Zetech L&F</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; margin: 0; 
            background: linear-gradient(135deg, rgba(0, 31, 63, 0.8), rgba(0, 51, 102, 0.6)), 
                        url('Campus.jpg') no-repeat center center fixed; 
            background-size: cover; min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .form-card { 
            background: rgba(255,255,255,0.95); padding: 40px; border-radius: 15px; 
            width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h2 { color: #003366; margin-top: 0; border-bottom: 2px solid #FFD700; padding-bottom: 10px; }
        label { display: block; margin: 15px 0 5px; font-weight: 600; color: #555; font-size: 0.9rem; }
        input, select, textarea { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; 
        }
        .btn-save { 
            background: #003366; color: white; border: none; padding: 15px; width: 100%; 
            border-radius: 8px; font-weight: 700; margin-top: 25px; cursor: pointer;
        }
        .btn-cancel { 
            display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.85rem; 
        }
    </style>
</head>
<body>

<div class="form-card">
    <h2>Edit Your Report</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Item Name</label>
        <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>

        <label>Category</label>
        <select name="category">
            <option value="Electronics" <?php if($item['category'] == 'Electronics') echo 'selected'; ?>>Electronics</option>
            <option value="Documents" <?php if($item['category'] == 'Documents') echo 'selected'; ?>>Documents</option>
            <option value="Personal Items" <?php if($item['category'] == 'Personal Items') echo 'selected'; ?>>Personal Items</option>
            <option value="Others" <?php if($item['category'] == 'Others') echo 'selected'; ?>>Others</option>
        </select>

        <label>Location Found/Lost</label>
        <input type="text" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>

        <label>Detailed Description</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($item['description']); ?></textarea>

        <label>Update Photo (Optional)</label>
        <input type="file" name="item_image" accept="image/*">

        <button type="submit" class="btn-save">Update Report</button>
        <a href="view_details.php?id=<?php echo $item_id; ?>" class="btn-cancel">Cancel and Go Back</a>
    </form>
</div>

</body>
</html>