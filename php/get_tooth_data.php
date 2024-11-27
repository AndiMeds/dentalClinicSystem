<?php
// get_tooth_data.php
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
    // Query treatments table directly since that's where we're inserting data
    $stmt = $conn->prepare("
    SELECT 
        t.tooth_number,
        t.status,
        DATE_FORMAT(MAX(t.date), '%Y-%m-%d') as last_checked,
        GROUP_CONCAT(
            DISTINCT t.treatment 
            ORDER BY t.date DESC 
            SEPARATOR ', '
        ) as recent_treatment
    FROM treatments t
    WHERE t.patient_id = ?
    GROUP BY t.tooth_number, t.status
");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $toothData = [];

    // Initialize all teeth with default values
    for ($i = 1; $i <= 32; $i++) {
        $toothData[$i] = [
            'status' => 'Healthy',
            'last_checked' => null,
            'treatment' => []
        ];
    }

    // Update with actual data where it exists
    while ($row = $result->fetch_assoc()) {
        $toothNumber = $row['tooth_number'];
        $toothData[$toothNumber] = [
            'status' => $row['status'],
            'last_checked' => $row['last_checked'],
            'treatment' => $row['recent_treatment'] ? explode(', ', $row['recent_treatment']) : []
        ];
    }

    // Add debugging information
    $debug = [
        'query_ran' => true,
        'rows_found' => $result->num_rows,
        'patient_id_queried' => $patient_id
    ];

    echo json_encode([
        'tooth_data' => $toothData,
        'debug' => $debug
    ]);

} catch (Exception $e) {
    error_log("Error in get_tooth_data.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'debug' => [
            'error_details' => $e->getMessage(),
            'patient_id_attempted' => $patient_id
        ]
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}