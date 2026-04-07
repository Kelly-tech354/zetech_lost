<?php
// db_test.php
// This is a standalone test file to verify your InfinityFree MySQL connection.

$servername = "sql103.infinityfree.com"; 
$username = "if0_41423085"; 
$password = "PASTE_THE_MYSQL_PASSWORD_FROM_VPANEL_HERE"; 
$dbname = "if0_41423085_zetech_lost_found"; 

// Enable error reporting for this test
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    echo "<h1 style='color:green;'>✅ Connection Successful!</h1>";
    echo "<p>Your database <b>$dbname</b> is ready for the Zetech Portal.</p>";
    
    // Test if the 'users' table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p style='color:blue;'>✔️ Table 'users' found.</p>";
    } else {
        echo "<p style='color:red;'>❌ Table 'users' not found. Please import your SQL file.</p>";
    }

} catch (mysqli_sql_exception $e) {
    echo "<h1 style='color:red;'>❌ Connection Failed</h1>";
    echo "<p><b>Error Message:</b> " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<h3>Troubleshooting Checklist:</h3>";
    echo "<ul>
            <li>Check if the <b>MySQL Password</b> in Account Details matches this file.</li>
            <li>Ensure the Database Name starts with <b>if0_...</b></li>
            <li>Make sure you created the database in the 'MySQL Databases' section of vPanel.</li>
          </ul>";
}
?>