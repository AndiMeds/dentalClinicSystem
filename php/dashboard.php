<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../db_connect.php';

// Check if the user is logged in and if the session role is set
if (!isset($_SESSION['login_success']) || !isset($_SESSION['role'])) {
    header("Location: ../loginsignup/login_form.php");
    exit();
}

// For staff dashboard
if ($_SESSION['role'] !== 'staff') {
    // Redirect unauthorized users
    if ($_SESSION['role'] === 'patient') {
        header("Location: ../appointment/appointment_home.php");
    } elseif ($_SESSION['role'] === 'dentist') {
        header("Location: ../dentist/dentist_dashboard.php");
    } else {
        header("Location: ../login_form.php");
    }
    exit();
}
function fetchActivities()
{
    global $conn;

    $sql = "
    SELECT 
        activity_log.timestamp, 
        patient_profiles.full_name AS patient_name,
        appointments.appointment_time,
        appointments.selected_services,  -- Assuming 'selected_services' is in the appointments table
        appointments.status AS appointment_status  -- Assuming 'status' is in the appointments table
    FROM 
        activity_log
    LEFT JOIN 
        patient_profiles ON activity_log.patient_id = patient_profiles.patient_id
    LEFT JOIN 
        appointments ON activity_log.appointment_id = appointments.appointment_id
    ORDER BY 
        activity_log.timestamp DESC;
";


    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        return $activities;
    } else {
        error_log("Query failed: " . $conn->error); // Log any SQL errors
        return "No activities found.";
    }
}

// Fetch activities
$activities = fetchActivities();
$activity_count = count($activities);

// Fetch total number of patients
$sqlTotalPatients = "SELECT COUNT(patient_id) AS totalPatients FROM patient_profiles";
$resultTotalPatients = $conn->query($sqlTotalPatients);
$totalPatients = $resultTotalPatients->fetch_assoc()['totalPatients'];

// Fetch count of today's confirmed appointments
$today = date('Y-m-d');
$sqlAppointmentsTodayConfirmed = "SELECT COUNT(appointment_id) AS totalTodayAppointments 
                                  FROM appointments 
                                  WHERE appointment_date = ? AND status = 'confirmed'";
$stmt = $conn->prepare($sqlAppointmentsTodayConfirmed);
$stmt->bind_param("s", $today);
$stmt->execute();
$resultAppointmentsToday = $stmt->get_result();
$totalTodayAppointments = $resultAppointmentsToday->fetch_assoc()['totalTodayAppointments'];

// Fetch total number of dentists
$sqlTotalDentists = "SELECT COUNT(dentist_id) AS totalDentists FROM dentist_profiles";
$resultTotalDentists = $conn->query($sqlTotalDentists);
$totalDentists = $resultTotalDentists->fetch_assoc()['totalDentists'];

// Fetch confirmed appointments
$sqlConfirmedAppointments = "SELECT COUNT(appointment_id) AS confirmedAppointments FROM appointments WHERE status = 'confirmed'";
$resultConfirmedAppointments = $conn->query($sqlConfirmedAppointments);
$confirmedAppointments = $resultConfirmedAppointments->fetch_assoc()['confirmedAppointments'];

// Fetch pending requests
$sqlPendingRequests = "SELECT COUNT(appointment_id) AS pendingRequests FROM appointments WHERE status = 'pending'";
$resultPendingRequests = $conn->query($sqlPendingRequests);
$pendingRequests = $resultPendingRequests->fetch_assoc()['pendingRequests'];

// Fetch cancelled appointments
$sqlCancelledAppointments = "SELECT COUNT(appointment_id) AS cancelledAppointments FROM appointments WHERE status = 'cancelled'";
$resultCancelledAppointments = $conn->query($sqlCancelledAppointments);
$cancelledAppointments = $resultCancelledAppointments->fetch_assoc()['cancelledAppointments'];

// Fetch patient demographics
$sqlPatientDemographics = "SELECT 
    COUNT(CASE WHEN gender = 'male' THEN 1 END) AS malePatients,
    COUNT(CASE WHEN gender = 'female' THEN 1 END) AS femalePatients
FROM patient_profiles;";
$resultDemographics = $conn->query($sqlPatientDemographics);
$rowDemographics = $resultDemographics->fetch_assoc();
$malePatients = $rowDemographics['malePatients'];
$femalePatients = $rowDemographics['femalePatients'];

// Fetch account creation by day
$sqlAccountsByDay = "SELECT DATE(created_at) AS creation_date, COUNT(patient_id) AS accounts_created 
                     FROM patient_profiles
                     GROUP BY creation_date 
                     ORDER BY creation_date";
$resultAccountsByDay = $conn->query($sqlAccountsByDay);

$creationDates = [];
$accountsCreated = [];

while ($rowAccountsByDay = $resultAccountsByDay->fetch_assoc()) {
    $creationDates[] = $rowAccountsByDay['creation_date'];
    $accountsCreated[] = $rowAccountsByDay['accounts_created'];
}

// Fetch most availed service types
$sqlServiceTypes = "SELECT selected_services, COUNT(selected_services) AS service_count 
                    FROM appointments
                    WHERE status != 'cancelled' 
                    GROUP BY selected_services
                    ORDER BY service_count DESC";
$resultServiceTypes = $conn->query($sqlServiceTypes);

$serviceTypes = [];
$serviceCounts = [];

while ($rowServiceTypes = $resultServiceTypes->fetch_assoc()) {
    $serviceTypes[] = $rowServiceTypes['selected_services'];
    $serviceCounts[] = $rowServiceTypes['service_count'];
}

// Fetch appointments created per day
$sqlAppointmentsByDay = "SELECT DATE(appointment_date) AS appointment_date, COUNT(appointment_id) AS appointments_created 
                         FROM appointments 
                         WHERE status != 'cancelled'
                         GROUP BY appointment_date 
                         ORDER BY appointment_date";
$resultAppointmentsByDay = $conn->query($sqlAppointmentsByDay);

$appointmentDates = [];
$appointmentsCreated = [];

while ($rowAppointmentsByDay = $resultAppointmentsByDay->fetch_assoc()) {
    $appointmentDates[] = $rowAppointmentsByDay['appointment_date'];
    $appointmentsCreated[] = $rowAppointmentsByDay['appointments_created'];
}

$conn->close();