<?php
// Start the session
session_start();

// Check for success message
$success = isset($_SESSION['success']) ? $_SESSION['success'] : false;
// If success is true, unset it to prevent it from showing again
if ($success) {
    unset($_SESSION['success']);
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set limit for displaying entries
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10; // Default to 10

// Fetch appointments with limit, ordered by latest created_at first
$sql = "SELECT appointment_id, username, appointment_date, service_type, complaint, other_details, followup, preferred_dentist, appointment_time, remarks, cancellation_reason, created_at, updated_at, status 
        FROM appointments 
        ORDER BY created_at DESC 
        LIMIT $limit";
$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
}
