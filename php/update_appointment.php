<?php
// Database connection
require_once '../db_connect.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Validate input
    if ($appointmentId <= 0 || empty($status)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }

    // Prepare and bind
    $stmt = $conn->prepare("UPDATE appointments SET remarks = ?, status = ? WHERE appointment_id = ?");
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare statement: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssi", $remarks, $status, $appointmentId);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Appointment updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Appointment not found or no changes made']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update appointment: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();