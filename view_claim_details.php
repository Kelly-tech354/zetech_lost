<?php
session_start();
include('config.php');

// Security: Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Get the claim ID from the URL
if (!isset($_GET['claim_id'])) {
    die("Error: No claim selected.");
}

$claim_id = mysqli_real_escape_string($conn, $_GET['claim_id']);

// Fetch Claim details + Claimer info + Item info
$query = "SELECT claims.*, 
                 items.item_name, items.description as item_desc,
                 users.full_name as claimer_name, users.email as claimer_email, users.phone_number as claimer_phone
          FROM claims
          JOIN items ON claims.item_id = items.id
          JOIN users ON claims.user_id = users.id
          WHERE claims.id = '$claim_id'";

$result = $conn->query($query);

if ($result->num_rows == 0) {
    die("Error: Claim details not found.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Verification Details</title>
    <link rel="stylesheet" href="style.css"> <style>
        .details-container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
        .contact-box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .btn-back { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

<div class="details-container">
    <h2>Verification Details for: <?php echo $data['item_name']; ?></h2>
    <hr>
    
    <p><strong>Claimer Name:</strong> <?php echo $data['claimer_name']; ?></p>
    <p><strong>Proof Provided:</strong></p>
    <blockquote style="background: #fff; padding: 10px; border-left: 5px solid #ccc;">
        <?php echo nl2br($data['proof_details']); ?>
    </blockquote>

    <div class="contact-box">
        <h3>Contact Info for Pickup</h3>
        <p><strong>Email:</strong> <a href="mailto:<?php echo $data['claimer_email']; ?>"><?php echo $data['claimer_email']; ?></a></p>
        <p><strong>Phone:</strong> <a href="tel:<?php echo $data['claimer_phone']; ?>"><?php echo $data['claimer_phone']; ?></a></p>
    </div>

    <a href="dashboard.php" class="btn-back">&larr; Back to Dashboard</a>
</div>

</body>
</html>