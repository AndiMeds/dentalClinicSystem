<?php
// get_treatment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

$patient_id = intval($_GET['patient_id']);

try {
    $stmt = $conn->prepare("SELECT * FROM treatments WHERE patient_id = ? ORDER BY date DESC");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $treatments = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($treatments);

} catch (Exception $e) {
    error_log("Error in get_treatment.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}