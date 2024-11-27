<?php

require_once '../db_connect.php';

// Get the patient_id from the URL parameter
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Query to retrieve patient data
$patient = [];
$medical_history = [];

// Query for patient_profiles
$sql_patient = "SELECT 
                    full_name, first_name, last_name, middle_initial, date_of_birth, 
                    age, gender, present_address, email, occupation, phone_number 
                FROM patient_profiles 
                WHERE patient_id = ?";
$stmt = $conn->prepare($sql_patient);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Check if patient data is found
if (!$patient) {
    echo "No patient data found in patient_profiles.<br>";
}

// Query for medical_history
$sql_medical = "SELECT 
                    q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, q11, q12, 
                    medications, q13a, q13b, q13c
                FROM medical_history
                WHERE patient_id = ?";
$stmt = $conn->prepare($sql_medical);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$medical_history = $result->fetch_assoc();

// Check if medical history data is found
if (!$medical_history) {
    echo "No medical history data found in medical_history.<br>";
}

// Check if at least one of the records is found
if (!$patient && !$medical_history) {
    echo "No data found for the given patient ID.";
} else {
    // Combine data if both are available
    $combined_data = array_merge($patient ?? [], $medical_history ?? []);

}


// Query to retrieve appointment history
$appointments = [];
$sql_appointments = "SELECT appointment_date, complaint, status FROM appointments WHERE patient_id = ? AND status IN ('completed', 'confirmed') ORDER BY appointment_date DESC";
$stmt_appointments = $conn->prepare($sql_appointments);
$stmt_appointments->bind_param("i", $patient_id);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();

// Fetch and format the results
while ($row = $result_appointments->fetch_assoc()) {
    // Create a DateTime object from the appointment_date
    $date = new DateTime($row['appointment_date']);

    // Format the date to 'F j, Y'
    $formatted_date = $date->format('F j, Y');

    // Add the formatted date, complaint, and status to the appointments array
    $appointments[] = [
        'appointment_date' => $formatted_date,
        'complaint' => $row['complaint'],
        'status' => $row['status']
    ];
}
if ($result_appointments->num_rows > 0) {
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Query to retrieve xray-results
$xray_images = [];
$sql_xray_images = "SELECT xray_name, image_path, description, xray_date 
                                 FROM xray_images 
                                 WHERE patient_id = ? 
                                 ORDER BY xray_date DESC";
$stmt_xray_images = $conn->prepare($sql_xray_images);
$stmt_xray_images->bind_param("i", $patient_id);
$stmt_xray_images->execute();
$result_xray_images = $stmt_xray_images->get_result();

if ($result_xray_images->num_rows > 0) {
    while ($row = $result_xray_images->fetch_assoc()) {
        $xray_images[] = $row;
    }
}

$stmt->close();
$stmt_appointments->close();
$conn->close(); // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/patient_details.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Patient Details</title>
</head>
<Style>

</Style>

<body>
    <div class="container">
        <div class="home_content">
            <?php include 'sidenav.html'; ?>
            <div class="label">PATIENT NAME</div>
            <h2><?= htmlspecialchars($patient['first_name'] ?? 'Patient Not Found'); ?></h2>
            <span class="patient-id">PATIENT ID: <?= $patient_id; ?></span>
            <?php if (!empty($patient)): ?>
                <div class="tab-container">
                    <button class="tablink active" data-tab="patient-info" onclick="openTab(event, 'patient-info')">Patient
                        Information</button>
                    <button class="tablink" data-tab="appointmentHistory"
                        onclick="openTab(event, 'appointmentHistory')">Appointment History</button>
                    <button class="tablink" data-tab="treatmentPlanning" onclick="openTab(event, 'treatmentPlanning')">
                        Treatment Planning</button>
                    <button class="tablink" data-tab="xrayResults" onclick="openTab(event, 'xrayResults')">X-ray Results &
                        Treatment Planning</button>
                    <button class="tablink" data-tab="billingHistory" onclick="openTab(event, 'billingHistory')">Billing
                        History</button>
                </div>

                <!-- Tab Content -->
                <div id="patient-info" class="tabcontent">
                    <div id="patient-record">
                        <h1>PATIENT INFORMATION</h1>
                        <hr class="thin-line">

                        <div class="info-grid">
                            <div class="info-field">
                                <div class="label">FULL NAME</div>
                                <div class="value"><?= htmlspecialchars($patient['full_name'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">DATE OF BIRTH</div>
                                <div class="value"><?= htmlspecialchars($patient['date_of_birth'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">AGE</div>
                                <div class="value"><?= htmlspecialchars($patient['age'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">GENDER</div>
                                <div class="value"><?= htmlspecialchars($patient['gender'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">OCCUPATION</div>
                                <div class="value"><?= htmlspecialchars($patient['occupation'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">PHONE NUMBER</div>
                                <div class="value"><?= htmlspecialchars($patient['phone_number'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">EMAIL</div>
                                <div class="value"><?= htmlspecialchars($patient['email'] ?? ''); ?></div>
                            </div>
                            <div class="info-field">
                                <div class="label">ADDRESS</div>
                                <div class="value"><?= htmlspecialchars($patient['present_address'] ?? ''); ?></div>
                            </div>
                        </div> <!-- End of info-grid -->

                        <h1>DENTAL AND MEDICAL HISTORY</h1>
                        <hr class="thin-line">
                        <p>PLEASE CIRCLE APPROPRIATE ANSWER:</p>

                        <div class="medical-question">
                            <!-- Radio buttons for questions 1 to 12 -->
                            <?php
                            $questions = [
                                'q1' => 'ARE YOU HAVING PAIN OR DISCOMFORT AT THIS TIME?',
                                'q2' => 'DO YOU FEEL NERVOUS ABOUT HAVING DENTAL TREATMENT?',
                                'q3' => 'HAVE YOU EVER HAD A BAD EXPERIENCE IN THE DENTAL OFFICE?',
                                'q4' => 'HAVE YOU BEEN UNDER THE CARE OF A MEDICAL DOCTOR DURING THE PAST TWO YEARS?',
                                'q5' => 'HAVE YOU BEEN TAKING MEDICINE OR DRUGS DURING THE PAST TWO YEARS?',
                                'q6' => 'ARE YOU ALLERGIC TO OR MADE SICK BY PENICILLIN, ASPIRIN, OR ANY FOOD, DRUGS, OR MEDICATION?',
                                'q7' => 'HAVE YOU EVER HAD EXCESSIVE BLEEDING REQUIRING SPECIAL TREATMENT?',
                                'q10' => 'WHEN YOU WALK UP STAIRS OR TAKE A WALK, DO YOU EVER STOP BECAUSE OF PAIN IN YOUR CHEST OR SHORTNESS OF BREATH OR BEING TIRED?',
                                'q11' => 'DO YOUR ANKLES SWELL DURING THE DAY?',
                                'q12' => 'DO YOU EVER WAKE UP FROM SLEEP BECAUSE OF SHORTNESS OF BREATH?'
                            ];

                            foreach ($questions as $key => $question) {
                                echo "<label>$question</label><div>
                        <input type='radio' name='$key' value='Yes' " . (isset($$key) && $$key === 'Yes' ? 'checked' : '') . "> YES
                        <input type='radio' name='$key' value='No' " . (isset($$key) && $$key === 'No' ? 'checked' : '') . "> NO
                    </div>";
                            }
                            ?>

                            <label>If YES, please indicate the following medication/s:</label>
                            <textarea name="medications" rows="3"><?= htmlspecialchars($medications ?? ''); ?></textarea>

                            <label>8. CIRCLE ANY OF THE FOLLOWING IN WHICH YOU HAVE HAD OR HAVE AT PRESENT:</label>
                            <div class="checkbox-group">
                                <?php
                                $conditions = [
                                    "Heart Failure",
                                    "Emphysema",
                                    "AIDS",
                                    "Angina Pectoris",
                                    "Heart Disease",
                                    "Hepa-B",
                                    "High Blood Pressure",
                                    "Asthma",
                                    "Tuberculosis",
                                    "Congenital Heart",
                                    "Heart Pacemaker",
                                    "Drug Addiction",
                                    "Epilepsy",
                                    "Heart Surgery",
                                    "Chemotherapy",
                                    "Diabetes",
                                    "Venereal Disease",
                                    "Hemophilia",
                                    "Anemia",
                                    "Rheumatism",
                                    "Cold Sores",
                                    "Stroke",
                                    "Artificial Joint",
                                    "Arthritis",
                                    "Sinus Trouble",
                                    "Fainting",
                                    "Blood Transfusion",
                                    "Artificial Heart Valve"
                                ];
                                foreach ($conditions as $condition) {
                                    $checked = in_array($condition, $q8 ?? []) ? 'checked' : '';
                                    echo "<label><input type='checkbox' name='q8[]' value='$condition' $checked> $condition</label>";
                                }
                                ?>
                            </div>

                            <label>9. DO YOU HAVE ANY DISEASE, CONDITION, OR PROBLEM NOT LISTED?</label>
                            <textarea name="q9" rows="3"
                                placeholder="PLEASE SPECIFY IF ANY..."><?= htmlspecialchars($q9 ?? ''); ?></textarea>

                            <!-- Woman-related Questions -->
                            <p>13. WOMEN:</p>
                            <?php
                            $womanQuestions = [
                                'q13a' => 'ARE YOU PREGNANT NOW?',
                                'q13b' => 'ARE YOU PRACTICING BIRTH CONTROL?',
                                'q13c' => 'DO YOU ANTICIPATE BECOMING PREGNANT?'
                            ];

                            foreach ($womanQuestions as $key => $question) {
                                echo "<label>$question</label><div>
                        <input type='radio' name='$key' value='Yes' " . (isset($$key) && $$key === 'Yes' ? 'checked' : '') . "> YES
                        <input type='radio' name='$key' value='No' " . (isset($$key) && $$key === 'No' ? 'checked' : '') . "> NO
                    </div>";
                            }
                            ?>

                            <!-- Update Profile Button -->
                            <button class="btn-update-profile"
                                onclick="openProfileModal('<?php echo str_pad($patient_id, 3, '0', STR_PAD_LEFT); ?>')">
                                UPDATE PROFILE</button>
                        </div> <!-- End of medical-question -->
                    </div> <!-- End of patient-record -->
                </div> <!-- End of patient-info -->



                <!-- Profile Modal HTML -->
                <div id="profileModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="modal-title">UPDATE INFORMATION</div>
                            <span class="close">&times;</span>
                        </div>
                        <form id="updateProfileForm" method="POST">
                            <input type="hidden" name="patient_id" id="patient_id" value="">
                            <div class="form-group">
                                <label for="last_name">LAST NAME</label>
                                <input name="last_name" id="last_name" type="text" value="<?php echo $last_name; ?>">
                            </div>
                            <div class="form-group">
                                <label for="first_name">FIRST NAME</label>
                                <input name="first_name" id="first_name" type="text" value="<?php echo $first_name; ?>">
                            </div>
                            <div class="form-group">
                                <label for="middle_initial">M.I.</label>
                                <input name="middle_initial" id="middle_initial" type="text"
                                    value="<?php echo $middle_initial; ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth">DATE OF BIRTH</label>
                                <input name="date_of_birth" id="date_of_birth" type="date"
                                    value="<?php echo $date_of_birth; ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">GENDER</label>
                                <select name="gender" id="gender">
                                    <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>Select
                                        Gender</option>
                                    <option value="Male" <?php echo $gender === 'MALE' ? 'selected' : ''; ?>>MALE
                                    </option>
                                    <option value="Female" <?php echo $gender === 'FEMALE' ? 'selected' : ''; ?>>
                                        FEMALE
                                    </option>
                                    <option value="Other" <?php echo $gender === 'OTHER' ? 'selected' : ''; ?>>OTHER
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input name="occupation" id="occupation" type="text" value="<?php echo $occupation; ?>">
                            </div>
                            <div class="form-group">
                                <label for="age">AGE</label>
                                <select name="age" id="age">
                                    <option value="" disabled <?php echo empty($age) ? 'selected' : ''; ?>>Select
                                        Age
                                    </option>
                                    <?php for ($i = 0; $i <= 100; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($age == $i) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">CONTACT</label>
                                <input name="phone_number" id="phone_number" type="tel"
                                    value="<?php echo $phone_number; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">EMAIL ADDRESS</label>
                                <input name="email" id="email" type="email" value="<?php echo $email; ?>">
                            </div>
                            <div class="form-group full-width">
                                <label for="present_address">PRESENT ADDRESS</label>
                                <input name="present_address" id="present_address" type="text"
                                    value="<?php echo $present_address; ?>">
                            </div>
                            <!-- Place the submit button in its own form-group -->
                            <div class="form-group btnn">
                                <button type="submit" class="save-btn">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="appointmentHistory" class="tabcontent">
                    <?php if (!empty($appointments)): ?>
                        <div class="table-container">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Appointment Date</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($appointment['appointment_date']); ?></td>
                                            <td><?= htmlspecialchars($appointment['complaint']); ?></td>
                                            <td>
                                                <span
                                                    class="status-badge <?= strtolower(htmlspecialchars($appointment['status'])); ?>">
                                                    <?= htmlspecialchars($appointment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No past appointments found.</p>
                    <?php endif; ?>
                </div>
                <div id="treatmentPlanning" class="tabcontent">
                    <h3>Treatment Planning</h3>
                    <p class="treatment-planning">Planned treatments for the patient.</p>
                    <a class="view-btn" href="treatment_planning.php?patient_id=<?php echo $patient_id; ?>">View Treatment
                        Planning</a>

                </div>
                <div id="xrayResults" class="tabcontent">
                    <h3>X-ray Results</h3>
                    <div class="xray-grid">
                        <a class="add-image-btn" href="docs/xray_form.php?patient_id=<?php echo $patient_id; ?>">
                            <i class="fa-solid fa-plus"></i> Add image
                        </a>
                        <?php if (!empty($xray_images)): ?>
                            <?php foreach ($xray_images as $index => $image): ?>
                                <div class="xray-card">
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="X-ray Image"
                                        onclick="openLightbox(<?php echo $index; ?>)">
                                    <div class="xray-info">
                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($image['xray_name']); ?></p>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($image['xray_date']); ?></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($image['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-images">No X-ray images available for this patient.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="lightbox" class="lightbox">
                    <button class="nav-btn prev-btn" onclick="navigateImage(-1)">&#10094;</button>
                    <img id="lightboxImage" src="" alt="Enlarged X-ray Image">
                    <button class="nav-btn next-btn" onclick="navigateImage(1)">&#10095;</button>
                    <button class="close-btn" onclick="closeLightbox()">&times;</button>
                </div>
                <div id="billingHistory" class="tabcontent">
                    <h3>Billing History</h3>
                    <p>Record of all billing transactions for the patient.</p>
                </div>
            <?php else: ?>
                <p>No patient data found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="../js/patients_details.js"></script>
    <script>

        let currentImageIndex = 0;
        const images = <?php echo json_encode(array_column($xray_images, 'image_path')); ?>;

        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablink");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

    </script>
</body>

</html>