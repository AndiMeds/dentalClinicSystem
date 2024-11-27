<?php
include '../db_connect.php';

$patientId = $_GET['patient_id'];
$stmt = $conn->prepare("SELECT * FROM teeth WHERE patient_id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();

$teeth = [];
while ($row = $result->fetch_assoc()) {
    $teeth[] = $row;
}

echo json_encode($teeth);

$stmt->close();
$conn->close();
