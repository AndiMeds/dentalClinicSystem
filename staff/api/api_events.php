<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $event_id = $data['id'] ?? null;
    $title = $data['title']; // This now contains both title and attachment
    $start = $data['start'];

    if ($event_id) {
        // Update existing event
        $stmt = $conn->prepare("UPDATE events SET title = ?, date = DATE(?), time = TIME(?) WHERE event_id = ?");
        $stmt->bind_param("sssi", $title, $start, $start, $event_id);
    } else {
        // Insert new event
        $stmt = $conn->prepare("INSERT INTO events (title, date, time) VALUES (?, DATE(?), TIME(?))");
        $stmt->bind_param("sss", $title, $start, $start);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT event_id, title, DATE_FORMAT(CONCAT(date, ' ', time), '%Y-%m-%dT%H:%i:%s') as start FROM events");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['event_id'],
            'title' => $row['title'],
            'start' => $row['start']
        ];
    }
    
    echo json_encode($events);
    
    $stmt->close();
}

$conn->close();
