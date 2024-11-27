<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

function sendJsonResponse($data) {
    $jsonData = json_encode($data);
    if ($jsonData === false) {
        logError("JSON encode error: " . json_last_error_msg());
        echo json_encode(["status" => "error", "message" => "Error encoding response"]);
    } else {
        echo $jsonData;
    }
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['patient_id']) && isset($_SESSION['username']);
}

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

require_once '../db_connect.php';

$action = $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'fetch_slots':
            $slots = fetchAvailableSlots($conn);
            sendJsonResponse($slots);
            break;
        case 'book_appointment':
            bookAppointment($conn);
            break;
        case 'cancel_appointment':
            cancelAppointment($conn);
            break;
        default:
            sendJsonResponse(['status' => 'error', 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    logError("Unhandled exception: " . $e->getMessage());
    sendJsonResponse(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}

function fetchAvailableSlots($conn) {
    $selectedDate = $_POST['date'] ?? null;

    if (!$selectedDate) {
        $selectedDate = getNextAvailableDate($conn);
    }

    $sql = "SELECT appointment_time, 
            SUM(CASE WHEN standing = 'active' AND status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN standing = 'active' THEN 1 ELSE 0 END) as total_active_count
            FROM appointments 
            WHERE appointment_date = ?
            GROUP BY appointment_time";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $selectedDate);
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['appointment_time']] = [
            'confirmed' => $row['confirmed_count'],
            'total' => $row['total_active_count']
        ];
    }

    $availableSlots = [];
    $startTime = 9;
    $endTime = 18;

    for ($i = $startTime; $i < $endTime; $i++) {
        $slot = sprintf("%02d:00", $i);
        $displayTime = convertTo12HourFormat($slot);
        $confirmedCount = $bookedSlots[$displayTime]['confirmed'] ?? 0;
        $totalActiveCount = $bookedSlots[$displayTime]['total'] ?? 0;
        $availableCount = 3 - $confirmedCount;

        $availableSlots[] = [
            'time' => $displayTime,
            'available' => max(0, $availableCount),
            'pending' => $totalActiveCount - $confirmedCount
        ];
    }

    return [
        'date' => $selectedDate,
        'slots' => $availableSlots
    ];
}

function getNextAvailableDate($conn) {
    $today = date('Y-m-d');
    $nextDate = date('Y-m-d', strtotime($today . ' +1 day'));

    while (true) {
        if (date('w', strtotime($nextDate)) == 0) {
            $nextDate = date('Y-m-d', strtotime($nextDate . ' +1 day'));
            continue;
        }

        $sql = "SELECT COUNT(*) as slot_count
                FROM appointments 
                WHERE appointment_date = ? AND standing = 'active' AND status = 'confirmed'
                GROUP BY appointment_time
                HAVING slot_count >= 3";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("s", $nextDate);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($result->num_rows < 9) {
            return $nextDate;
        }

        $nextDate = date('Y-m-d', strtotime($nextDate . ' +1 day'));
    }
}

function bookAppointment($conn) {
    if (!isLoggedIn()) {
        throw new Exception("User not logged in.");
    }

    $service_type = strtoupper(trim($_POST['service_type'] ?? ''));
    $complain = strtoupper(trim($_POST['complain'] ?? ''));
    $selectedTimeSlot = $_POST['time_slot'] ?? '';
    $appointmentDate = $_POST['date'] ?? '';
    $patient_id = $_SESSION['patient_id'];
    $username = $_SESSION['username'];

    if (empty($service_type) || empty($complain) || empty($selectedTimeSlot) || empty($appointmentDate)) {
        throw new Exception("Please fill in all required fields.");
    }

    $today = date('Y-m-d');
    if ($appointmentDate <= $today) {
        throw new Exception("Cannot book appointments for today or past dates.");
    }
    
    $conn->begin_transaction();

    try {
        $sql = "SELECT 
                SUM(CASE WHEN standing = 'active' AND status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                SUM(CASE WHEN standing = 'active' THEN 1 ELSE 0 END) as total_active_count
                FROM appointments 
                WHERE appointment_date = ? AND appointment_time = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ss", $appointmentDate, $selectedTimeSlot);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $confirmedCount = $row['confirmed_count'];
        $totalActiveCount = $row['total_active_count'];

        if ($confirmedCount >= 3) {
            throw new Exception("Sorry, this slot is no longer available.");
        }

        $sql = "INSERT INTO appointments (service_type, username, complaint, patient_id, appointment_time, appointment_date, standing, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', 'pending')";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("ssssss", $service_type, $username, $complain, $patient_id, $selectedTimeSlot, $appointmentDate);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }

        $appointment_id = $conn->insert_id;
        $conn->commit();

        $updatedSlots = fetchAvailableSlots($conn);
        sendJsonResponse([
            "status" => "success", 
            "message" => "New appointment added successfully. Status: Pending", 
            "appointment_id" => $appointment_id,
            "updatedSlots" => $updatedSlots
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function cancelAppointment($conn) {
    if (!isLoggedIn()) {
        throw new Exception("User not logged in.");
    }

    $appointment_id = $_POST['appointment_id'] ?? '';
    $patient_id = $_SESSION['patient_id'];

    if (empty($appointment_id)) {
        throw new Exception("Appointment ID is required.");
    }

    // Update both `standing` and `status` to 'cancelled'
    $sql = "UPDATE appointments SET standing = 'cancelled', status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ii", $appointment_id, $patient_id);
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No appointment found or you don't have permission to cancel it.");
    }

    // Commit the transaction to make sure changes are saved
    $conn->commit();
    
    sendJsonResponse(["status" => "success", "message" => "Appointment cancelled successfully."]);
}

function convertTo12HourFormat($time) {
    return date("h:i A", strtotime($time));
}

$conn->close();
