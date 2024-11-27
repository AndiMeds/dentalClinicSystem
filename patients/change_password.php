<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/css/change_password.css">
    <link rel="stylesheet" href="/css/sidebar.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<?php
session_start(); // Start the session to access session variables

// Database connection details
$servername = "localhost";
$dbname = "dental_db";
$username = "root";
$password = "";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve and trim form inputs
    $currentPassword = trim($_POST['currentPassword']);
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Get the logged-in user's ID from the session
    $patient_id = $_SESSION['patient_id'];

    // Query to get the user's current password from the database
    $query = "SELECT password FROM patient_profiles WHERE patient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->bind_result($dbPassword);
    $stmt->fetch();
    $stmt->close();

    // Validate the current password
    if ($currentPassword === $dbPassword) {

        // Check if new password is the same as current password
        if ($newPassword === $currentPassword) {
            $_SESSION['message'] = "New password cannot be the same as the current password!";
            header("Location: changepassword.php");
            exit;  // Prevent further execution
        }

        // Check if new password is at least 8 characters long
        if (strlen($newPassword) < 8) {
            $_SESSION['message'] = "New password must be at least 8 characters long!";
            header("Location: changepassword.php");
            exit;  // Prevent further execution
        }

        // Check if new password contains at least one number
        if (!preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['message'] = "New password must contain at least one number!";
            header("Location: changepassword.php");
            exit;  // Prevent further execution
        }

        // Check if new password matches confirm password
        else if ($newPassword === $confirmPassword) {

            // Update the password in the database
            $updateQuery = "UPDATE patient_profiles SET password = ? WHERE patient_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $newPassword, $patient_id);
            if ($stmt->execute()) {
                $stmt->close();  // Ensure statement is closed before redirect
                $_SESSION['message'] = "Password updated successfully!";
                header("Location: changepassword.php");
                exit;  // Stop script execution after redirection
            } else {
                $stmt->close();  // Ensure statement is closed before redirect
                $_SESSION['message'] = "Error updating password! Please try again later.";
                header("Location: changepassword.php");
                exit;  // Stop script execution after redirection
            }
        } else {
            $_SESSION['message'] = "New password and confirmation do not match!";
            header("Location: changepassword.php");
            exit;  // Stop script execution after redirection
        }
    } else {
        $_SESSION['message'] = "Current password is incorrect!";
        header("Location: changepassword.php");
        exit;  // Stop script execution after redirection
    }
}

$conn->close(); // Close the connection
?>

<!-- HTML/PHP for displaying the message -->
<?php
if (isset($_SESSION['message'])) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: '" . $_SESSION['message'] . "',
                showConfirmButton: true
            });
        });
    </script>";
    unset($_SESSION['message']); // Clear the message after displaying
}
?>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="container">
        <?php include 'sidebar.html'; ?>
        <div class="main-content">
            <h1> CHANGE PASSWORD</h1>
            <hr class="thin-line">

            <div class="form-container">
                <form class="forgot-password-form" action="" method="POST">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">Current Password</span>
                            <input class="input" type="password" name="currentPassword"
                                placeholder="Enter current password" required>
                        </div>
                        <div class="input-box">
                            <span class="details">New Password</span>
                            <input class="input" type="password" name="newPassword" placeholder="Enter new password"
                                required>
                        </div>
                        <div class="input-box">
                            <span class="details">Confirm Password</span>
                            <input class="input" type="password" name="confirmPassword"
                                placeholder="Confirm your new password" required>
                        </div>
                        <button type="submit" class="change-btn">Change Password</button>
                    </div>
                </form>
            </div>
            </div>
        </div>
        <script src="../script/sidebar.js"></script>
</body>

</html>