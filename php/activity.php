<?php

// Function to ensure database connection
function ensureConnection() {
    global $conn;
    
    // Check if connection is already established or needs to be re-established
    if (!isset($conn) || $conn->connect_errno) {
        require_once __DIR__ . '/../db_connect.php';  // Open the connection if not already open
    }

    // Check if the connection was successful
    if ($conn->connect_errno) {
        error_log("Failed to connect to MySQL: " . $conn->connect_error);
        return null;  // Return null if connection fails
    }
    
    return $conn;
}

// Function to fetch activities from the database
function fetchActivities() {
    $conn = ensureConnection();  // Ensure connection is available
    
    // If no connection is established, return an empty array
    if (!$conn) {
        return [];
    }
    
    // SQL query to fetch activities
    $query = "SELECT action_type, description, timestamp, staff_id, patient_id 
              FROM activity_log 
              ORDER BY timestamp DESC LIMIT 5";
    
    // Execute the query
    $result = $conn->query($query);

    // If the query is successful and returns rows
    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Log error if query failed or no results found
        error_log("No activities found or query failed: " . $conn->error);
        error_log("Query: " . $query);  // Log the query for debugging
        return [];
    }
}

// Function to insert a new activity
function insertActivity($action_type, $description, $staff_id, $patient_id = NULL) {
    $conn = ensureConnection();  // Ensure connection is available
    
    // If no connection is established, return false
    if (!$conn) {
        return false;
    }

    // SQL query to insert activity
    $sql = "INSERT INTO activity_log (action_type, description, staff_id, patient_id) 
            VALUES (?, ?, ?, ?)";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Check if the statement is prepared successfully
    if ($stmt) {
        // Bind parameters and execute the statement
        $stmt->bind_param("ssii", $action_type, $description, $staff_id, $patient_id);
        $success = $stmt->execute();
        $stmt->close();  // Close the prepared statement
        return $success;
    } else {
        // Log error if statement preparation fails
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
}

// Handle POST request for inserting a new activity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch POST data
    $action_type = $_POST['action_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $staff_id = $_POST['staff_id'] ?? 0;
    $patient_id = $_POST['patient_id'] ?? NULL;

    // Insert activity and give feedback
    if (insertActivity($action_type, $description, $staff_id, $patient_id)) {
        echo "New activity recorded successfully!";
    } else {
        echo "Error: Failed to record new activity.";
    }
}

// Fetch activities
$activities = fetchActivities();

// If no activities found or query failed, display appropriate message
if (empty($activities)) {
  
} else {
    // Process and display activities here
    // Example:
    foreach ($activities as $activity) {
        echo "<div>{$activity['timestamp']} - {$activity['action_type']}: {$activity['description']}</div>";
    }
}

// Note: We don't close the connection here to allow other scripts to use it
