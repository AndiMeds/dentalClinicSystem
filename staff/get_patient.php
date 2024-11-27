<?php
header('Content-Type: application/json');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the patient_id from the query string
$patient_id = str_pad($_GET['patient_id'], 3, '0', STR_PAD_LEFT);

if ($patient_id > 0) {
    // Prepare the SQL statement to fetch the patient's data
    $sql = "SELECT patient_id, full_name, age, occupation, gender, date_of_birth, phone_number, present_address, middle_initial, first_name, last_name, email FROM patient_profiles WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the patient exists
    if ($result->num_rows > 0) {
        $patient_data = $result->fetch_assoc();
        echo json_encode($patient_data); // Return patient data in JSON format
    } else {
        echo json_encode(['error' => 'Patient not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid patient ID']);
}

// Close the database connection
$conn->close();
