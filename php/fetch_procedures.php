<?php
include '../db_connect.php';

$result = $conn->query("SELECT * FROM procedures");

$procedures = [];
while ($row = $result->fetch_assoc()) {
    $procedures[] = $row;
}

echo json_encode($procedures);

$conn->close();
