<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API endpoint handler
function handleRequest($conn) {
    header('Content-Type: application/json');
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    switch ($method) {
        case 'GET':
            if ($pathParts[1] === 'tooth') {
                echo json_encode(getToothInfo($conn, $pathParts[2]));
            } elseif ($pathParts[1] === 'treatments') {
                echo json_encode(getTreatments($conn));
            }
            break;
            
        case 'POST':
            if ($pathParts[1] === 'treatment') {
                $data = json_decode(file_get_contents('php://input'), true);
                echo json_encode(addTreatment($conn, $data));
            }
            break;
            
        case 'DELETE':
            if ($pathParts[1] === 'treatment') {
                echo json_encode(deleteTreatment($conn, $pathParts[2]));
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Get tooth information and its treatments
function getToothInfo($conn, $toothNumber) {
    // Sanitize input
    $toothNumber = $conn->real_escape_string($toothNumber);

    // Get tooth details
    $query = "SELECT * FROM teeth WHERE id = '$toothNumber'";
    $result = $conn->query($query);

    if (!$result) {
        return ['error' => 'Database error'];
    }

    $tooth = $result->fetch_assoc();

    if (!$tooth) {
        return ['error' => 'Tooth not found'];
    }

    // Get treatments for this tooth
    $query = "SELECT * FROM treatments WHERE tooth_number = '$toothNumber' ORDER BY treatment_date DESC";
    $result = $conn->query($query);

    if (!$result) {
        return ['error' => 'Database error'];
    }

    $treatments = [];
    while ($row = $result->fetch_assoc()) {
        $treatments[] = $row;
    }

    return [
        'tooth' => $tooth,
        'treatments' => $treatments
    ];
}

// Get all treatments
function getTreatments($conn) {
    $query = "SELECT t.*, th.status as tooth_status 
              FROM treatments t 
              JOIN teeth th ON t.tooth_number = th.id 
              ORDER BY treatment_date DESC";
    
    $result = $conn->query($query);

    if (!$result) {
        return ['error' => 'Database error'];
    }

    $treatments = [];
    while ($row = $result->fetch_assoc()) {
        $treatments[] = $row;
    }

    return $treatments;
}

// Add new treatment
function addTreatment($conn, $data) {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Sanitize inputs
        $toothNumber = $conn->real_escape_string($data['toothNumber']);
        $status = $conn->real_escape_string($data['status']);
        $date = $conn->real_escape_string($data['date']);
        $procedure = $conn->real_escape_string($data['procedure']);
        $notes = $conn->real_escape_string($data['notes']);

        // Update tooth status
        $query = "INSERT INTO teeth (id, status, last_checked) 
                 VALUES ('$toothNumber', '$status', '$date')
                 ON DUPLICATE KEY UPDATE 
                 status = VALUES(status), 
                 last_checked = VALUES(last_checked)";

        if (!$conn->query($query)) {
            throw new Exception($conn->error);
        }

        // Add treatment
        $query = "INSERT INTO treatments 
                 (tooth_number, procedure_name, treatment_date, status, notes) 
                 VALUES ('$toothNumber', '$procedure', '$date', '$status', '$notes')";

        if (!$conn->query($query)) {
            throw new Exception($conn->error);
        }

        $insertId = $conn->insert_id;
        
        $conn->commit();
        return ['success' => true, 'id' => $insertId];
    } catch(Exception $e) {
        $conn->rollback();
        error_log("Error adding treatment: " . $e->getMessage());
        return ['error' => 'Database error'];
    }
}

// Delete treatment
function deleteTreatment($conn, $treatmentId) {
    // Sanitize input
    $treatmentId = $conn->real_escape_string($treatmentId);

    $query = "DELETE FROM treatments WHERE id = '$treatmentId'";
    
    if (!$conn->query($query)) {
        return ['error' => 'Database error'];
    }

    return ['success' => true];
}

// Initialize the system and handle requests

handleRequest($conn);

// Close the connection at the end of the script
$conn->close();
