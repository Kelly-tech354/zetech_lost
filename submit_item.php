<?php
session_start();
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $user_id = $_SESSION['user_id'];
    
    // Default image if none is uploaded
    $file_name = "default_item.png"; 

    // Check if a file was actually uploaded and has no errors
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION));
        $new_file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;

        // Verify it is an image
        $check = getimagesize($_FILES["item_image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
                $file_name = $new_file_name; // Use the uploaded file name
            }
        }
    }

    // Insert into database (will use either the uploaded name or 'default_item.png')
    $sql = "INSERT INTO items (item_name, user_id, item_image) VALUES ('$item_name', '$user_id', '$file_name')";
    
    if ($conn->query($sql)) {
        header("Location: dashboard.php?status=success");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>