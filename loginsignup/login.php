<?php

// Check if a session is already active before starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection setup
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check for database connection error
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("An error occurred. Please try again later.");
}

// Handle logout request
if (isset($_GET['logout'])) {
    // Destroy the session and redirect to the login page
    session_unset();
    session_destroy();
    header("Location: login_form.php");
    exit();
}

// Handle form submission (login attempt)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF token validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token validation failed");
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: login_form.php");
        exit();
    }

    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Log attempt for debugging purposes
    error_log("Login attempt for user: " . $username);

    // Attempt to find user in patient_profiles table
    $stmt = $conn->prepare("SELECT patient_id, username, password, 'patient' AS role FROM patient_profiles WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // If no patient found, check in staff_profiles table
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("SELECT staff_id, username, password, role FROM staff_profiles WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    // If no staff found, check in dentist_profiles table
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("SELECT dentist_id, username, password, 'dentist' AS role FROM dentist_profiles WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    // If no dentist found, check in admin_profiles table
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("SELECT admin_id, username, password, 'admin' AS role FROM admin_profiles WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    // Process result if user is found
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("User found: " . json_encode($user));
        
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Set session variables based on role
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            switch ($_SESSION['role']) {
                case 'patient':
                    $_SESSION['patient_id'] = $user['patient_id'];
                    break;
                case 'staff':
                    $_SESSION['staff_id'] = $user['staff_id'];
                    break;
                case 'dentist':
                    $_SESSION['dentist_id'] = $user['dentist_id'];
                    break;
                case 'admin':
                    $_SESSION['admin_id'] = $user['admin_id'];
                    break;
            }
            
            $_SESSION['login_success'] = true;

            // Log successful login for debugging
            error_log("Login successful for user: " . $_SESSION['username']);

            // Redirect the user to the respective dashboard
            $redirectUrl = null;
            switch ($_SESSION['role']) {
                case 'patient':
                    $redirectUrl = "../patients/patient_home.php";
                    break;
                case 'staff':
                    $redirectUrl = "../staff/staff_dashboard.php";
                    break;
                case 'dentist':
                    $redirectUrl = "../dentist/dentist_dashboard.php";
                    break;
                case 'admin':
                    $redirectUrl = "../admin/admin_dashboard.php";
                    break;
                default:
                    // If the user's role is not recognized, log the issue and redirect to the login page
                    error_log("Unrecognized user role: " . $_SESSION['role']);
                    $_SESSION['error_message'] = "An error occurred. Please try again later.";
                    $redirectUrl = "login_form.php";
                    break;
            }

            if ($redirectUrl !== null) {
                error_log("Redirecting to: " . $redirectUrl);
                header("Location: $redirectUrl");
                exit();
            } else {
                // If the redirect URL is null, set an error message and redirect to the login page
                error_log("Redirect URL is null");
                $_SESSION['error_message'] = "An error occurred. Please try again later.";
                header("Location: login_form.php");
                exit();
            }
        } else {
            error_log("Password verification failed for user: " . $username);
            $_SESSION['error_message'] = "Invalid username or password.";
            header("Location: login_form.php");
            exit();
        }
    } else {
        // If no user found
        error_log("User not found: " . $username);
        $_SESSION['error_message'] = "Invalid username or password.";
        header("Location: login_form.php");
        exit();
    }
}

$conn->close();

// Set security headers for protection
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");