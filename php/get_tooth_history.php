<?php
error_log(print_r($toothData, true)); // Add this to your PHP file
header('Content-Type: application/json');
include '../db_connect.php';

if (!isset($_GET['patient_id']) || !isset($_GET['tooth_number'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$patient_id = intval($_GET['patient_id']);
$tooth_number = intval($_GET['tooth_number']);

try {
    // Query to fetch tooth history data
    $stmt = $conn->prepare("SELECT status, last_checked, treatments FROM tooth_data WHERE patient_id = ? AND tooth_number = ?");
    $stmt->bind_param("ii", $patient_id, $tooth_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No history found for this tooth']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching tooth history']);
} finally {
    $conn->close();
}
