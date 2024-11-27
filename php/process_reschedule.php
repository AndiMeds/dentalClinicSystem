<?php
// Start session at the very beginning
session_start();
session_regenerate_id();

// Turn off error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($data)
{
    echo json_encode($data);
    exit;
}

// Function to check if user is logged in
function isLoggedIn()
{
    return isset($_SESSION['patient_id']) && isset($_SESSION['username']);
}

// Function to log errors
function logError($message)
{
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'error.log');
}

// Database connection
require_once '../db_connect.php';


// Check connection
if ($conn->connect_error) {
    logError("Database connection failed: " . $conn->connect_error);
    sendJsonResponse(["error" => "Connection failed. Please try again later."]);
}

// Function to convert 24-hour time to 12-hour format
function convertTo12HourFormat($time)
{
    return date("h:i A", strtotime($time));
}

// Handle fetching available time slots
if (isset($_POST['action']) && $_POST['action'] == 'fetch_slots' && isset($_POST['appointment_date'])) {
    $selectedDate = $_POST['appointment_date'];

    // Query to fetch booked slots count for the selected date
    $sql = "SELECT appointment_time, COUNT(*) as booked_count FROM appointments WHERE appointment_date = ? GROUP BY appointment_time";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookedSlots = [];
        while ($row = $result->fetch_assoc()) {
            $bookedSlots[$row['appointment_time']] = $row['booked_count'];
        }

        // Define available slots (9 AM to 5 PM)
        $availableSlots = [];
        $startTime = 9;
        $endTime = 18;

        for ($i = $startTime; $i < $endTime; $i++) {
            $slot = sprintf("%02d:00", $i); // Still creates the 24-hour format time
            $displayTime = convertTo12HourFormat($slot); // Convert to 12-hour format with AM/PM
            $$bookedCount = isset($bookedSlots[$displayTime]) ? $bookedSlots[$displayTime] : 0;
            $availableCount = 15 - $bookedCount;

            // Store the 'displayTime' in the output
            $availableSlots[] = [
                'time' => $displayTime,
                'displayTime' => $displayTime,
                'available' => max(0, $availableCount)
            ];
        }

        // Send the response
        sendJsonResponse($availableSlots);
    } else {
        logError("Error preparing statement for fetching time slots: " . $conn->error);
        sendJsonResponse(["error" => "Error preparing statement for fetching time slots"]);
    }
}

// Handle form submission for booking an appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    if (!isLoggedIn()) {
        logError("User not logged in or username not set in session");
        sendJsonResponse([
            "status" => "error",
            "message" => "Error: User not logged in or session expired. Please log in again."
        ]);
    }

    // Retrieve and validate form data
    $selectedTimeSlot = $_POST['time_slot'] ?? '';
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentId = $_SESSION['appointment_id'] ?? null;
    $patient_id = $_SESSION['patient_id'];
    $username = $_SESSION['username'];

    // Validation
    if (empty($selectedTimeSlot) || empty($appointmentDate) || empty($appointmentId)) {
        logError("Form validation failed: " . print_r($_POST, true));
        sendJsonResponse([
            "status" => "error",
            "message" => "Please fill in all required fields."
        ]);
    }

    // Convert selected time slot to 12-hour format
    $selectedDisplayTime = convertTo12HourFormat($selectedTimeSlot);

    // Check if the slot is still available
    $sql = "SELECT COUNT(*) as booked_count FROM appointments WHERE appointment_date = ? AND appointment_time = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $appointmentDate, $selectedDisplayTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $bookedCount = $row['booked_count'];

        // If the booked count is 15 or more, slot is fully booked
        if ($bookedCount >= 15) {
            sendJsonResponse([
                "status" => "error",
                "message" => "Sorry, this slot is no longer available. Please choose another."
            ]);
            exit; // Exit to prevent further processing
        }

        // Proceed with updating the appointment
        // Proceed with updating the appointment
        $sql = "UPDATE appointments 
            SET appointment_time = ?, appointment_date = ? 
            WHERE appointment_id = ? AND patient_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $selectedDisplayTime, $appointmentDate, $appointmentId, $patient_id);

            if ($stmt->execute()) {
                sendJsonResponse([
                    "status" => "success",
                    "title" => "Success",
                    "message" => "Appointment rescheduled successfully."
                ]);
            } else {
                logError("Error executing statement: " . $stmt->error);
                sendJsonResponse([
                    "status" => "error",
                    "title" => "Error",
                    "message" => "Error updating appointment. Please try again."
                ]);
            }
            $stmt->close();
        } else {
            logError("Error preparing statement: " . $conn->error);
            sendJsonResponse([
                "status" => "error",
                "message" => "Error preparing statement. Please try again."
            ]);
        }
    } else {
        logError("Error preparing statement: " . $conn->error);
        sendJsonResponse([
            "status" => "error",
            "message" => "Error preparing statement. Please try again."
        ]);
    }
}
$conn->close();
