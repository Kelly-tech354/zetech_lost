<?php
// config.php
$servername = "sql311.infinityfree.com"; // Check this in your vPanel (it might be sql103 or sql311)
$username = "if0_41424679"; 
$password = "1Y3zUu67cTCL"; 
$dbname = "if0_41424679_zetech_lost_found"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>