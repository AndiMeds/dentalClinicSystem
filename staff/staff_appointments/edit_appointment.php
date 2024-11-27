<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dental_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$success = false;

// Function to update appointment details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'];
    $remarks = $_POST['remarks'];
    $status = $_POST['status'];

    $sql = "UPDATE appointments SET 
            remarks = ?, 
            status = ? 
            WHERE appointment_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sss",
        $remarks,
        $status,
        $appointment_id
    );

    if ($stmt->execute()) {
        header("Location: /staff/appointments.php?update_success=true");
        exit();
    } else {
        $message = "Error updating appointment: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch appointment data
$appointment_id = $_GET['appointment_id'] ?? '';

if ($appointment_id) {
    $sql = "SELECT * FROM appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $appointment_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();

        // Convert the appointment_time to HH:MM format
        $appointment['appointment_time'] = date('H:i', strtotime($appointment['appointment_time']));
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment</title>
    <link rel="stylesheet" href="/css/appointments.css">
    <link rel="stylesheet" href="/css/edit_appointment.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        .time-input-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .time-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="home_content">
            <?php include '../sidenav.html'; ?>
            <div class="content">
                <h2>EDIT APPOINTMENT</h2>
                <?php if ($message): ?>
                    <p class="error-message"><?php echo $message; ?></p>
                <?php endif; ?>
                <?php if ($appointment): ?>
                    <form method="POST" id="appointmentForm" onsubmit="return confirmSubmission(event)">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">

                        <label for="appointment_date">APPOINTMENT DATE</label>
                        <input type="date" id="appointment_date" name="appointment_date"
                            value="<?php echo $appointment['appointment_date']; ?>" required disabled>

                        <label for="service_type">SERVICE TYPE</label>
                        <input type="text" id="service_type" name="service_type"
                            value="<?php echo htmlspecialchars($appointment['service_type']); ?>" required disabled>

                        <label for="complaint">COMPLAINT</label>
                        <textarea id="complaint" name="complaint" required
                            disabled><?php echo htmlspecialchars($appointment['complaint']); ?></textarea>

                        <label for="other_details">OTHER DETAILS</label>
                        <textarea id="other_details" name="other_details"
                            disabled><?php echo htmlspecialchars($appointment['other_details']); ?></textarea>

                        <label for="followup">FOLLOW UP</label>
                        <input type="text" id="followup" name="followup"
                            value="<?php echo htmlspecialchars($appointment['followup']); ?>" disabled>

                        <label for="preferred_dentist">PREFERRED DENTIST</label>
                        <input type="text" id="preferred_dentist" name="preferred_dentist"
                            value="<?php echo htmlspecialchars($appointment['preferred_dentist']); ?>" disabled>

                        <label for="appointment_time">TIME BLOCK</label>
                        <input type="time" id="appointment_time" name="appointment_time"
                            value="<?php echo $appointment['appointment_time']; ?>" disabled>

                        <label for="remarks">REMARKS</label>
                        <textarea id="remarks"
                            name="remarks"><?php echo htmlspecialchars($appointment['remarks']); ?></textarea>

                        <label for="status">STATUS</label>
                        <select id="status" name="status" required >
                            <?php
                            $currentStatus = strtolower(trim($appointment['status']));
                            $statusOptions = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
                            foreach ($statusOptions as $status) {
                                $isSelected = (strtolower($status) === $currentStatus) ? 'selected' : '';
                                echo "<option value='$status' $isSelected>$status</option>";
                            }
                            ?>
                        </select>

                        <button type="submit">Update Appointment</button>
                    </form>
                <?php else: ?>
                    <p>Appointment not found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmSubmission(event) {
    event.preventDefault(); // Prevents the form from submitting immediately
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you really want to update this appointment?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById("appointmentForm").submit(); // Manually submits the form
        }
    });
    return false; // Prevents the default form submission
}
    

    document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('update_success') === 'true') {
        Swal.fire({
            title: 'Success!',
            text: 'Appointment updated successfully.',
            icon: 'success',
            confirmButtonText: 'Okay'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'appointments.php';
            }
        });
    }
});
    </script>

</body>

</html>
