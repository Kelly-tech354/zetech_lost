<?php
include('config.php');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $res = $conn->query("SELECT * FROM items WHERE id = $id");
    $item = $res->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['status'];
    $id = $_POST['id'];
    
    $sql = "UPDATE items SET status='$new_status' WHERE id=$id";
    if ($conn->query($sql)) {
        header("Location: admin.php?msg=Updated"); // Changed from admin_manage.php
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item Status</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; }
        .edit-box { background: white; padding: 30px; border: 1px solid #ccc; border-radius: 8px; width: 300px; }
        select, button { width: 100%; padding: 10px; margin-top: 10px; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="edit-box">
        <h3>Update Item: <?php echo $item['item_name']; ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <label>Current Status: <b><?php echo $item['status']; ?></b></label>
            <select name="status">
                <option value="Lost">Lost</option>
                <option value="Found">Found</option>
                <option value="Claimed">Claimed</option>
                <option value="Unclaimed">Unclaimed</option>
            </select>
            <button type="submit">Update Record</button>
            <a href="admin_manage.php" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
        </form>
    </div>
</body>
</html>