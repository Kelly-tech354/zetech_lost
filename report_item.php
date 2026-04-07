<?php
session_start();
include('config.php');

// --- NEW MANUAL LOADING (Option 1) ---
// We point to the folder you created
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

mysqli_report(MYSQLI_REPORT_OFF);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize basic inputs
    $item_name   = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category    = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status      = mysqli_real_escape_string($conn, $_POST['status']); 
    $location    = mysqli_real_escape_string($conn, $_POST['location']);
    $reported_by = $_SESSION['user_id'];

    // --- Image Upload Logic ---
    $file_name = "default_item.png"; 

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION));
        $unique_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
        $target_file = $target_dir . $unique_name;

        $check = getimagesize($_FILES["item_image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["item_image"]["tmp_name"], $target_file)) {
                $file_name = $unique_name;
            }
        }
    }

    // 1. Insert the newly reported item into the database
    $sql = "INSERT INTO items (item_name, category, description, status, location, reported_by, item_image) 
            VALUES ('$item_name', '$category', '$description', '$status', '$location', '$reported_by', '$file_name')";

    if ($conn->query($sql) === TRUE) {
        $newItemId = $conn->insert_id; // Get the ID of the item just reported
        
        // --- UPDATED AUTOMATIC MATCHING LOGIC (TWO-WAY & CASE INSENSITIVE) ---
        // Determine the opposite status to look for a match
        $searchStatus = ($status == 'Found') ? 'Lost' : 'Found';
        
        // Find matching items regardless of status (Case Insensitive using LOWER)
        $match_sql = "SELECT id, reported_by, item_name, status FROM items 
                      WHERE status = '$searchStatus' 
                      AND category = '$category' 
                      AND (LOWER(item_name) LIKE LOWER('%$item_name%') OR LOWER('$item_name') LIKE CONCAT('%', LOWER(item_name), '%'))";
        
        $match_result = $conn->query($match_sql);

        if ($match_result && $match_result->num_rows > 0) {
            
            // Initialize PHPMailer
            $mail = new PHPMailer(true);
            try {
                // SMTP Server Settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'dumbkelly70@gmail.com'; 
                $mail->Password   = 'fcyhhulxgdyqeldo'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('dumbkelly70@gmail.com', 'Zetech Lost & Found');
                $mail->isHTML(true);

                while ($row = $match_result->fetch_assoc()) {
                    // Logic to identify who the 'Owner' (the person who lost it) is
                    $lost_item_id = ($status == 'Lost') ? $newItemId : $row['id'];
                    $found_item_id = ($status == 'Found') ? $newItemId : $row['id'];
                    $owner_id = ($status == 'Lost') ? $_SESSION['user_id'] : $row['reported_by'];
                    $matching_item_name = $row['item_name'];

                    // --- AUTHORIZE THE MATCH IN DATABASE ---
                    // Note: We use the Found Item ID for the item_id column to allow the loser to see the finder
                    $auth_sql = "INSERT INTO matches (item_id, user_id) VALUES ('$found_item_id', '$owner_id')";
                    $conn->query($auth_sql);
                    
                    // Fetch the email of the person who reported the item LOST
                    $user_query = $conn->query("SELECT email, full_name FROM users WHERE id = '$owner_id'");
                    $user_data = $user_query->fetch_assoc();
                    $to_email = $user_data['email'] ?? null;
                    $owner_name = $user_data['full_name'] ?? 'Student';

                    if ($to_email) {
                        $mail->clearAddresses();
                        $mail->addAddress($to_email);
                        $mail->Subject = "Match Found: Your lost item " . ($status == 'Lost' ? $item_name : $matching_item_name);
                        
                        $mail->Body = "
                            <div style='font-family: Arial, sans-serif; border: 1px solid #003366; border-radius: 10px; overflow: hidden; max-width: 500px;'>
                                <div style='background:#003366; color:white; padding:20px; text-align:center;'>
                                    <h2 style='margin:0;'>Zetech University</h2>
                                    <p style='margin:0;'>Lost & Found System</p>
                                </div>
                                <div style='padding:20px; color: #333;'>
                                    <p>Hello <b>$owner_name</b>,</p>
                                    <p>Great news! A match has been found for your item in the <b>" . htmlspecialchars($category) . "</b> category.</p>
                                    <p>Our system detected a similarity between your report and an entry at <b>" . htmlspecialchars($location) . "</b>.</p>
                                    <p>You can now log in to your dashboard to view the finder's contact information and claim your item.</p>
                                    <div style='text-align:center; margin: 30px 0;'>
                                        <a href='https://zetect-lost-found.infinityfreeapp.com/login.html' 
                                           style='background:#d9534f; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold;'>
                                            View Match Details
                                        </a>
                                    </div>
                                    <p style='font-size: 0.8rem; color: #777;'>Please visit the Lost and Found office with your student ID to verify ownership.</p>
                                </div>
                            </div>";

                        $mail->send();
                    }

                    // Also insert an in-app notification for the person who lost the item
                    $notif_msg = "Match Alert: A match for your item was found in the $category category at $location.";
                    $notif_msg = mysqli_real_escape_string($conn, $notif_msg);
                    $conn->query("INSERT INTO notifications (user_id, message, is_read) VALUES ('$owner_id', '$notif_msg', 0)");
                }
            } catch (Exception $e) {
                // Fail silently
            }
        }

        header("Location: dashboard.php?status=success");
        exit();
    } else {
        header("Location: dashboard.php?status=error");
        exit();
    }
}
$conn->close();
?>