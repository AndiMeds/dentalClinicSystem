<?php
include '../db_connect.php';

header('Content-Type: application/json'); // Set header for JSON output

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['treatment_id'])) { // Use treatment_id for a specific deletion
        $treatment_id = $_POST['treatment_id'];

        $stmt = $conn->prepare("DELETE FROM treatments WHERE treatment_id = ?"); // Target treatment_id
        if ($stmt) {
            $stmt->bind_param("i", $treatment_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Treatment deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete treatment']);
            }

            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Treatment ID not provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
