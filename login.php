<?php
session_start();
include('config.php');

// Disable default reporting to handle errors cleanly
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capture and Trim Input
    $login_input = trim($_POST['username']); 
    $password = $_POST['password'];
    
    // Capture the role from HTML and force to lowercase for matching
    $selected_role = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : ''; 

    // --- COOKIE LOGIC ---
    if (isset($_POST['remember'])) {
        setcookie("remembered_user", $login_input, time() + (86400 * 30), "/");
    } else {
        setcookie("remembered_user", "", time() - 3600, "/");
    }

    // 2. Search for User
    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role, is_verified, email FROM users WHERE username = ? OR registration_number = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 3. Verify Password against the stored hash
        if (password_verify($password, $user['password_hash'])) {
            
            // --- EMAIL VERIFICATION CHECK ---
            if ($user['is_verified'] == 0) {
                header("Location: verify.php?email=" . urlencode($user['email']) . "&error=notverified");
                exit();
            }
            
            // 4. THE PERMANENT ROLE FIX - MATCHING DROPDOWN VALUES TO DB ROLES
            $role_authorized = false;
            
            // Get the role stored in DB and clean it
            $db_role = strtolower(trim($user['role'])); 

            // Check if selected 'Administrator' matches 'admin' in database
            if (($selected_role === 'admin' || $selected_role === 'administrator') && ($db_role === 'admin' || $db_role === 'administrator')) {
                $role_authorized = true;
                $db_role = 'admin'; // Standardize session role
            } 
            // Check if selected 'Student' or 'Staff' matches 'users' in database
            elseif (($selected_role === 'users' || $selected_role === 'student' || $selected_role === 'staff') && ($db_role === 'users' || $db_role === 'user')) {
                $role_authorized = true;
                $db_role = 'users'; // Standardize session role
            }

            if ($role_authorized) {
                // Set Session Variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $db_role;
                $_SESSION['last_activity'] = time(); 

                // 5. Final Redirect based on verified role
                if ($db_role === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                // Role mismatch error
                header("Location: login.html?error=rolemismatch");
                exit();
            }
        } else {
            // Wrong password
            header("Location: login.html?error=wrongpass");
            exit();
        }
    } else {
        // Username or registration number not found
        header("Location: login.html?error=usernotfound");
        exit();
    }
    $stmt->close();
} else {
    // If accessed without POST
    header("Location: login.html");
    exit();
}
$conn->close();
?>