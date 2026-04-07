<?php
session_start();
include('config.php');

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access: Please log in first.");
}

// 2. Flexible Security Check: 
// Converts the session role to lowercase to ensure it matches 'administrator' or 'admin'
$user_role = strtolower(trim($_SESSION['role']));

if ($user_role !== 'administrator' && $user_role !== 'admin') {
    die("Unauthorized access: Your current role is " . $_SESSION['role'] . ". Only Admins can trigger backups.");
}

// 3. Define tables to backup
$tables = array('users', 'items', 'support_messages', 'notifications');
$content = "-- Zetech Lost and Found Backup\n";
$content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach($tables as $table) {
    $result = $conn->query("SELECT * FROM $table");
    $fields_amount = $result->field_count;

    $content .= "DROP TABLE IF EXISTS $table;";
    $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
    $content .= "\n\n" . $row2[1] . ";\n\n";

    while($row = $result->fetch_row()) {
        $content .= "INSERT INTO $table VALUES(";
        for($j=0; $j<$fields_amount; $j++) {
            if (isset($row[$j])) {
                // Secure the data for SQL insertion
                $row[$j] = str_replace("\n","\\n", addslashes($row[$j]));
                $content .= '"'.$row[$j].'"' ; 
            } else { 
                $content .= 'NULL'; 
            }
            if ($j < ($fields_amount - 1)) { $content .= ','; }
        }
        $content .= ");\n";
    }
    $content .= "\n\n\n";
}

// 4. Set headers to download the file
$backup_name = "Zetech_Backup_" . date('Y-m-d_H-i-s') . ".sql";
header('Content-Type: application/octet-stream');   
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");  

echo $content;
exit;
?>