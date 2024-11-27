<?php
// update_treatment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../db_connect.php';

header('Content-Type: application/json'); // Ensure response header is JSON

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn->begin_transaction();

    // Validate required fields - now including progress
    $required_fields = ['treatment_id', 'patient_id', 'toothNumber', 'treatment', 'date', 'status', 'progress'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    $treatment_id = intval($_POST['treatment_id']);
    $patient_id = intval($_POST['patient_id']);
    $toothNumber = intval($_POST['toothNumber']);
    $treatment = $_POST['treatment'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $progress = $_POST['progress'];
    $notes = $_POST['notes'] ?? '';

    // Verify treatment exists and belongs to patient
    $verify_stmt = $conn->prepare("SELECT treatment_id FROM treatments WHERE treatment_id = ? AND patient_id = ?");
    if (!$verify_stmt) {
        throw new Exception("Prepare verify statement failed: " . $conn->error);
    }
    
    $verify_stmt->bind_param("ii", $treatment_id, $patient_id);
    if (!$verify_stmt->execute()) {
        throw new Exception("Error executing verify statement: " . $verify_stmt->error);
    }
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        throw new Exception("Treatment not found or doesn't belong to this patient");
    }
    $verify_stmt->close();

    // Update treatment - now including progress
    $update_stmt = $conn->prepare("
        UPDATE treatments 
        SET tooth_number = ?, 
            treatment = ?, 
            date = ?, 
            status = ?, 
            progress = ?,
            notes = ?
        WHERE treatment_id = ? AND patient_id = ?
    ");

    if (!$update_stmt) {
        throw new Exception("Prepare update statement failed: " . $conn->error);
    }

    $update_stmt->bind_param("isssssii", 
        $toothNumber,
        $treatment,
        $date,
        $status,
        $progress,
        $notes,
        $treatment_id,
        $patient_id
    );

    if (!$update_stmt->execute()) {
        throw new Exception("Error updating treatment: " . $update_stmt->error);
    }

    // Store affected rows before closing the statement
    $affected_rows = $update_stmt->affected_rows;
    $update_stmt->close();

    // Update tooth_data status
    $tooth_stmt = $conn->prepare("
        UPDATE tooth_data 
        SET status = ?,
            last_checked = ?
        WHERE patient_id = ? AND tooth_number = ?
    ");

    if (!$tooth_stmt) {
        throw new Exception("Prepare tooth data statement failed: " . $conn->error);
    }

    $tooth_stmt->bind_param("ssii",
        $status,
        $date,
        $patient_id,
        $toothNumber
    );

    if (!$tooth_stmt->execute()) {
        throw new Exception("Error updating tooth data: " . $tooth_stmt->error);
    }
    $tooth_stmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Treatment updated successfully',
        'affected_rows' => $affected_rows
    ]);
    exit;

} catch (Exception $e) {
    if ($conn->connect_errno) {
        $conn->rollback();
    }
    error_log("Error in update_treatment.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} finally {
    if (isset($conn) && !$conn->connect_errno) {
        $conn->close();
    }
}