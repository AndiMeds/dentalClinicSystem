<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$xray_id = isset($_GET['xray_id']) ? intval($_GET['xray_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

$patient_id = $_SESSION['patient_id'];

// Debug: Check session and URL parameters
echo "<script>console.log('Session Patient ID: $patient_id');</script>";
echo "<script>console.log('Requested X-ray ID: $xray_id');</script>";

// Verify that this X-ray belongs to the logged-in patient
$sql = "SELECT xi.*, CONCAT(up.first_name, ' ', up.last_name) as patient_name 
        FROM xray_images xi 
        JOIN patient_profiles up ON xi.patient_id = up.patient_id 
        WHERE xi.xray_id = ? AND xi.patient_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ii", $xray_id, $patient_id);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Set default values and indicate no record was found
    $xray_name = "N/A";
    $xray_date = "N/A";
    $description = "No description available.";
    $no_record_found = true;
    echo "<script>console.log('No matching X-ray found for X-ray ID $xray_id and Patient ID $patient_id');</script>";
} else {
    // Fetch data and set variables
    $row = $result->fetch_assoc();
    $no_record_found = false;

    $xray_name = $row['xray_name'];
    $xray_date = date('F d, Y', strtotime($row['xray_date']));
    $description = $row['description'];
    $db_image_path = ltrim($row['image_path'], '/');
    $web_path = '/' . $db_image_path;
    $full_server_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $db_image_path;

    // Debug paths
    echo "<script>console.log('Database Image Path: $db_image_path');</script>";
    echo "<script>console.log('Web Path: $web_path');</script>";
    echo "<script>console.log('Full Server Path: $full_server_path');</script>";
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View X-ray - <?php echo htmlspecialchars($xray_name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .xray-container { max-width: 800px; margin: 20px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .xray-header { text-align: center; margin-bottom: 20px; }
        .xray-image { max-width: 100%; height: auto; margin: 20px 0; border: 1px solid #ddd; border-radius: 4px; }
        .xray-details { margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 4px; }
        .debug-info { margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
        .back-button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .back-button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="xray-container">
        <div class="xray-header">
            <h1><?php echo htmlspecialchars($xray_name); ?></h1>
            <p>Date: <?php echo htmlspecialchars($xray_date); ?></p>
        </div>
        
        <?php if ($no_record_found): ?>
            <p style="color: red;">X-ray not found or access denied.</p>
        <?php elseif (file_exists($full_server_path)): ?>
            <img src="<?php echo htmlspecialchars($web_path); ?>" alt="X-ray Image" class="xray-image">
        <?php else: ?>
            <p style="color: red;">Error: Image file not found.</p>
            <div class="debug-info">
                <strong>Debug Information:</strong><br>
                Database Path: <?php echo htmlspecialchars($db_image_path); ?><br>
                Full Server Path: <?php echo htmlspecialchars($full_server_path); ?><br>
                Web Path: <?php echo htmlspecialchars($web_path); ?><br>
                Script Directory: <?php echo htmlspecialchars(__DIR__); ?><br>
                Parent Directory: <?php echo htmlspecialchars(dirname(__DIR__)); ?><br>
                File Exists: <?php echo file_exists($full_server_path) ? 'Yes' : 'No'; ?><br>
                Current Permissions: <?php echo file_exists($full_server_path) ? substr(sprintf('%o', fileperms($full_server_path)), -4) : 'N/A'; ?>
            </div>
        <?php endif; ?>
        
        <div class="xray-details">
            <h3>Description:</h3>
            <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
        </div>
        
        <a href="patient_dashboard.php" class="back-button">Back to Dashboard</a>
    </div>
</body>
</html>
