<?php
// Auto-detect whether the app is running locally or on the live host.
$hostName = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocal = in_array($hostName, ['localhost', '127.0.0.1', '::1'], true)
    || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);

if ($isLocal) {
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'zetech_lost_found';
} else {
    $servername = 'sql311.infinityfree.com';
    $username = 'if0_41424679';
    $password = '1Y3zUu67cTCL';
    $dbname = 'if0_41424679_zetech_lost_found';
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>
