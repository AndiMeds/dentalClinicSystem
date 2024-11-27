<?php
require_once '../db_connect.php';

// Get the patient_id from the URL parameter
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Query to retrieve patient data
$patient = [];
$sql = "SELECT * FROM patient_profiles WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
}

// Query to retrieve appointment history
$appointments = [];
$sql_appointments = "SELECT appointment_date, complaint, status FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC LIMIT 5";
$stmt_appointments = $conn->prepare($sql_appointments);
$stmt_appointments->bind_param("i", $patient_id);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();

while ($row = $result_appointments->fetch_assoc()) {
    $appointments[] = $row;
}

// Query to retrieve x-ray results
$xray_images = [];
$sql_xray_images = "SELECT xray_name, image_path, description, xray_date FROM xray_images WHERE patient_id = ? ORDER BY xray_date DESC LIMIT 6";
$stmt_xray_images = $conn->prepare($sql_xray_images);
$stmt_xray_images->bind_param("i", $patient_id);
$stmt_xray_images->execute();
$result_xray_images = $stmt_xray_images->get_result();

while ($row = $result_xray_images->fetch_assoc()) {
    $xray_images[] = $row;
}

$stmt->close();
$stmt_appointments->close();
$stmt_xray_images->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f0f4f8;
            --text-color: #333;
            --light-text-color: #666;
            --border-color: #e0e0e0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .patient-header {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .patient-name {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .patient-id {
            font-size: 14px;
            color: var(--light-text-color);
        }

        .tab-container {
            display: flex;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .tab:hover {
            background-color: var(--secondary-color);
        }

        .tab.active {
            background-color: var(--primary-color);
            color: #fff;
        }

        .content-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            background-color: var(--secondary-color);
            padding: 10px;
            border-radius: 5px;
        }

        .info-label {
            font-size: 12px;
            color: var(--light-text-color);
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--secondary-color);
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background-color: var(--success-color);
            color: #fff;
        }

        .status-scheduled {
            background-color: var(--warning-color);
            color: #000;
        }

        .status-cancelled {
            background-color: var(--danger-color);
            color: #fff;
        }

        .xray-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .xray-item {
            background-color: var(--secondary-color);
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .xray-item:hover {
            transform: scale(1.05);
        }

        .xray-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .xray-info {
            padding: 10px;
        }

        .xray-date {
            font-size: 12px;
            color: var(--light-text-color);
        }

        .xray-name {
            font-size: 14px;
            font-weight: 500;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #357abD;
        }

        @media (max-width: 768px) {
            .tab-container {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="patient-header">
            <h1 class="patient-name"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
            <span class="patient-id">Patient ID: <?php echo str_pad($patient_id, 3, '0', STR_PAD_LEFT); ?></span>
        </div>

        <div class="tab-container">
            <div class="tab active" data-tab="info">Personal Info</div>
            <div class="tab" data-tab="appointments">Appointments</div>
            <div class="tab" data-tab="xrays">X-Rays</div>
            <div class="tab" data-tab="treatment">Treatment</div>
        </div>

        <div id="info" class="content-section">
            <h2 class="section-title">Personal Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['date_of_birth']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['gender']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['phone_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['present_address']); ?></div>
                </div>
            </div>
            <a href="#" class="btn" style="margin-top: 20px;">Update Profile</a>
        </div>

        <div id="appointments" class="content-section" style="display: none;">
            <h2 class="section-title">Appointment History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['complaint']); ?></td>
                        <td><span class="status-badge status-<?php echo strtolower($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="xrays" class="content-section" style="display: none;">
            <h2 class="section-title">X-Ray Results</h2>
            <div class="xray-grid">
                <?php foreach ($xray_images as $xray): ?>
                <div class="xray-item">
                    <img src="<?php echo htmlspecialchars($xray['image_path']); ?>" alt="X-ray" class="xray-image">
                    <div class="xray-info">
                        <div class="xray-date"><?php echo htmlspecialchars($xray['xray_date']); ?></div>
                        <div class="xray-name"><?php echo htmlspecialchars($xray['xray_name']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="#" class="btn" style="margin-top: 20px;">Add New X-Ray</a>
        </div>

        <div id="treatment" class="content-section" style="display: none;">
            <h2 class="section-title">Treatment Planning</h2>
            <p>Treatment planning information will be displayed here.</p>
            <a href="treatment_planning.php?patient_id=<?php echo $patient_id; ?>" class="btn" style="margin-top: 20px;">View Treatment Plan</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const contentSections = document.querySelectorAll('.content-section');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');

                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    contentSections.forEach(section => {
                        section.style.display = section.id === tabId ? 'block' : 'none';
                    });
                });
            });
        });
    </script>
</body>
</html>