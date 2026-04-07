<?php
session_start();
include('config.php');

// 1. Basic Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = strtolower($_SESSION['role'] ?? '');

// 2. Get the Item ID
$item_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($item_id > 0) {
    // 3. Advanced Security: Check Ownership or Admin Role
    // This query verifies the user is an Admin OR they reported the item themselves.
    $check_sql = "SELECT reported_by FROM items WHERE id = '$item_id' LIMIT 1";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        if ($item['reported_by'] == $user_id || $user_role === 'admin' || $user_role === 'administrator') {
            // 4. Authorized: Perform the Deletion
            $delete_sql = "DELETE FROM items WHERE id = '$item_id'";
            if ($conn->query($delete_sql)) {
                echo json_encode(['status' => 'success', 'message' => 'Report deleted successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error during deletion.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'You are not authorized to delete this report.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Item ID.']);
}
?>