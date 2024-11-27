<?php
session_start();

// Check if the user is logged in as staff
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

// Error handling function
function displayError($message)
{
    return "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}

// Initialize variables
$patient_id = 0;
$xray_images = [];
$error_message = '';

// Validate and sanitize patient_id
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    $error_message = displayError("Invalid or missing patient ID.");
} else {
    $patient_id = intval($_GET['patient_id']);

    // Fetch X-ray images from the database
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Updated SQL query with ORDER BY clause
        $stmt = $conn->prepare("SELECT xray_name, image_path, description, xray_date 
                                 FROM xray_images 
                                 WHERE patient_id = ? 
                                 ORDER BY xray_date DESC");
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("i", $patient_id);

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $xray_images[] = $row;
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error_message = displayError($e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <title>X-ray Results - Dental Clinic</title>
    <style>
        body {
            font-family: Varela Round;
            background-color: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 1.8rem;
            color: #343a40;
            margin-bottom: 1.5rem;
        }
        .xray-card {
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            background-color: #f9f9f9;
        }
        .xray-card img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .xray-info {
            margin-top: 0.5rem;
        }
        .xray-info p {
            margin: 0.3rem 0;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            color: #dc3545;
            background-color: #fff5f5;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>X-ray Results for Patient ID: <?php echo htmlspecialchars($patient_id); ?></h1>

        <?php echo $error_message; ?>

        <?php if (!empty($xray_images)): ?>
            <?php foreach ($xray_images as $image): ?>
                <div class="xray-card">
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="X-ray Image">
                    <div class="xray-info">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($image['xray_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($image['xray_date']); ?></p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($image['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No X-ray images available for this patient.</p>
        <?php endif; ?>
    </div>
</body>

</html>
