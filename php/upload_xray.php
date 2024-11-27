<?php
session_start();

// Check if the user is logged in as staff
if (!isset($_SESSION['staff_id'])) {
    header("Location:../loginsignup/login_form.php");
    exit();
}

// Database configuration - consider moving to a separate config file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

// Error handling function
function displayError($message)
{
    return "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}

// Success message function
function displaySuccess($message)
{
    return "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Initialize variables
$upload_message = '';
$patient = null;
$patient_id = 0;

try {
    // Create database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Validate and sanitize patient_id
    if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
        throw new Exception("Invalid or missing patient ID.");
    }

    $patient_id = intval($_GET['patient_id']);

    // Fetch patient information
    $stmt = $conn->prepare("SELECT patient_id, CONCAT(first_name, ' ', last_name) AS name 
                           FROM patient_profiles
                           WHERE patient_id = ?");

    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("i", $patient_id);

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if (!$patient) {
        throw new Exception("Patient not found.");
    }

    $stmt->close();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate form inputs
        if (empty($_POST['xray_name']) || empty($_POST['description']) || empty($_POST['xray_date'])) {
            throw new Exception("All fields are required.");
        }

        if (!isset($_FILES["xray_file"]) || $_FILES["xray_file"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("X-ray file upload failed.");
        }

        $xray_name = trim($_POST['xray_name']);
        $description = trim($_POST['description']);
        $xray_date = $_POST['xray_date'];

        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $xray_date);
        if (!$date || $date->format('Y-m-d') !== $xray_date) {
            throw new Exception("Invalid date format.");
        }

        // Calculate file hash
        $file_contents = file_get_contents($_FILES["xray_file"]["tmp_name"]);
        $file_hash = hash('sha256', $file_contents);

        // Check for duplicate by hash
        $stmt = $conn->prepare("SELECT * FROM xray_images WHERE patient_id = ? AND file_hash = ?");
        $stmt->bind_param("is", $patient_id, $file_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            throw new Exception("Duplicate X-ray file detected. Upload canceled.");
        }

        // Set up upload directory
        $base_path = realpath($_SERVER['DOCUMENT_ROOT']);
        $relative_path = '/dentalClinicSystem/uploads/xrays/' . $patient_id;
        $upload_dir = $base_path . $relative_path;

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create upload directory.");
        }

        // Validate file type and size
        $file_info = getimagesize($_FILES["xray_file"]["tmp_name"]);
        if ($file_info === false) {
            throw new Exception("Invalid image file.");
        }

        if ($_FILES["xray_file"]["size"] > 5000000) { // 5MB limit
            throw new Exception("File size too large (max 5MB).");
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_info['mime'], $allowed_types)) {
            throw new Exception("Invalid file type. Only JPG, PNG & GIF allowed.");
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES["xray_file"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('xray_', true) . '.' . $file_extension;
        $target_file = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;
        $db_file_path = $relative_path . '/' . $new_filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES["xray_file"]["tmp_name"], $target_file)) {
            throw new Exception("Failed to save uploaded file.");
        }

        // Insert into database with hash
        $stmt = $conn->prepare("INSERT INTO xray_images (patient_id, xray_name, image_path, description, xray_date, file_hash) 
                               VALUES (?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            unlink($target_file); // Remove uploaded file if database insert fails
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("isssss", $patient_id, $xray_name, $db_file_path, $description, $xray_date, $file_hash);

        if (!$stmt->execute()) {
            unlink($target_file); // Remove uploaded file if database insert fails
            throw new Exception("Failed to save x-ray information: " . $stmt->error);
        }

        $upload_message = displaySuccess("X-ray uploaded successfully!");
        $stmt->close();
    }

} catch (Exception $e) {
    $upload_message = displayError($e->getMessage());
}
