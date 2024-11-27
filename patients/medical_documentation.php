<?php
session_start(); // Start the session at the beginning of the script

// Check if the user is logged in
if (!isset($_SESSION['patient_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Medical Documentation - Dental Clinic</title>
    <link rel="stylesheet" href="/css/sidebar.css">
    <link rel="stylesheet" href="/css/docs.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>

<body>
    <div id="hamburger-menu" class="hamburger">&#9776;</div>
    <div class="layout">
        <?php include 'sidebar.html'; ?>
        <div class="main-content">
            <header>
                <h1>DENTAL DOCUMENTATION</h1>
                <hr class="thin-line">
                <p>View and download your dental records, X-rays, and other medical documents.</p>
            </header>
            <div class="controls">
                <input type="text" id="search" placeholder="Search documents" class="search-input">
                <select id="filter" class="filter-select">
                    <option value="all">All Documents</option>
                    <option value="Dental Records">Dental Records</option>
                    <option value="X-Rays">X-Rays</option>
                    <option value="Treatment Plans">Treatment Plans</option>
                    <option value="Billing Records">Billing Records</option>
                    <option value="Prescriptions">Prescriptions</option>
                </select>
            </div>
            <table id="documentsTable">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Table rows will be populated by JavaScript -->
                </tbody>
            </table>
            <div id="noResults" class="no-results" style="display: none;">
                No documents found matching your search criteria.
            </div>
        </div>
    </div>
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
    // Modified SQL query to get all necessary fields
    $sql = "SELECT 
xray_id as id,
xray_name as name,
'X-Ray' as type,
DATE_FORMAT(xray_date, '%Y-%m-%d') as date,
description,
image_path,
'xray' as source 
FROM xray_images 
WHERE patient_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    $stmt->close();
    $conn->close();
    ?>
    <script>
        // Pass PHP data to JavaScript
        var documents = <?php echo json_encode($records); ?>;
    </script>
    <script src="../js/docs.js"></script>
    <script src="../js/sidebar.js"></script>
</body>

</html>