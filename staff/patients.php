<?php
$last_name = $first_name = $middle_initial = $birthdate = $gender = $occupation = $age = $phone_number = $email = $present_address = $username = $password = "";
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

// Set limit for displaying entries
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10; // Default to 10

// Get the current page number from the URL, if not present, set it to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Calculate the starting row for the SQL query
$offset = ($page - 1) * $limit;

// Get the total number of patients
$totalQuery = "SELECT COUNT(*) AS total FROM patient_profiles";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalPatients = $totalRow['total'];

// Calculate total pages based on the limit
$totalPages = ceil($totalPatients / $limit);

// Query to retrieve patient data, including full name, ordered by last_name ascending, with pagination
$sql = "
    SELECT patient_id, email, phone_number, username, last_name, first_name, CONCAT(first_name, ' ', last_name) AS full_name
    FROM patient_profiles
    ORDER BY last_name ASC 
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset); // Bind the limit and offset parameters to prevent SQL injection
$stmt->execute();
$result = $stmt->get_result();

$patients = []; // Initialize an empty array to store patient data

if ($result && $result->num_rows > 0) {
    // Fetch all the results as an associative array
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row; // Store each row of data into the $patients array
    }
} else {
    $patients = []; // No data found, make sure $patients is still an empty array
}

$stmt->close();
$conn->close(); // Close the database connection

?>

<!DOCTYPE html>
<sty lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Patients</title>
        <link rel="stylesheet" href="/css/patients.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    </head>

    <body>
        <div class="container">
            <div class="home_content">
                <?php include 'sidenav.html'; ?>
                <div class="content">
                    <h1>PATIENTS</h1>
                    <hr class="thin-line">
                    <div class="search-and-table-container">
                        <div class="search-container">
                            <div class="InputContainer">
                                <input placeholder="SEARCH BY NAME OR USERNAME" id="searchInput" class="inputsearch"
                                    name="text" type="text" />
                                <label class="labelforsearch" for="searchInput">
                                    <svg class="searchIcon" viewBox="0 0 512 512">
                                        <path
                                            d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                        </path>
                                    </svg>
                                </label>
                            </div>
                            <!-- Add New Patient Modal -->
                            <div id="addPatientModal" class="modal-overlay">
                                <div class="modal-content">
                                    <!-- Close button within the modal -->
                                    <span class="close-button"
                                        onclick="closeModal(document.getElementById('addPatientModal'))">×</span>
                                    <h2>ADD NEW PATIENT</h2>
                                    <form id="addPatientForm">
                                        <!-- Row 1: Last Name, First Name, Middle Initial -->
                                        <div class="form-group">
                                            <label for="last_name">LAST NAME</label>
                                            <input name="last_name" id="last_name" type="text">
                                        </div>
                                        <div class="form-group">
                                            <label for="first_name">FIRST NAME</label>
                                            <input name="first_name" id="first_name" type="text">
                                        </div>
                                        <div class="form-group">
                                            <label for="middle_initial">MIDDLE INITIAL</label>
                                            <input name="middle_initial" id="middle_initial" type="text">
                                        </div>

                                        <!-- Row 2: Gender, Date of Birth, Age -->
                                        <div class="form-group">
                                            <label for="gender">GENDER</label>
                                            <select name="gender" id="gender" required>
                                                <option value="" disabled <?php echo empty($gender) ? 'selected' : ''; ?>>SELECT GENDER
                                                </option>
                                                <option value="Male" <?php echo $gender === 'MALE' ? 'selected' : ''; ?>>
                                                    MALE</option>
                                                <option value="Female" <?php echo $gender === 'FEMALE' ? 'selected' : ''; ?>>FEMALE</option>
                                                <option value="Other" <?php echo $gender === 'OTHER' ? 'selected' : ''; ?>>OTHER</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="birthdate">DATE OF BIRTH</label>
                                            <input name="birthdate" id="birthdate" type="date">
                                        </div>
                                        <div class="form-group">
                                            <label for="age">Age</label>
                                            <input type="number" name="age" id="age" min="5" max="90" readonly>
                                        </div>

                                        <!-- Row 3: Phone Number, Email Address -->
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="tel" id="phone_number" name="phone_number"
                                                placeholder="+63 900 000 0000">
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input name="email" id="email" type="email">
                                        </div>

                                        <!-- Row 4: Occupation, Present Address -->
                                        <div class="form-group">
                                            <label for="occupation">Occupation</label>
                                            <input name="occupation" id="occupation" type="text">
                                        </div>
                                        <div class="form-group full-width">
                                            <label for="present_address">Present Address</label>
                                            <input name="present_address" id="present_address" type="text">
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="form-group full-width">
                                            <button type="submit">Add Patient</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Button to Open the Modal -->
                            <button onclick="openModal(document.getElementById('addPatientModal'))">Add Patient</button>

                        </div>
                        <div class="table-container">
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone Number</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($patients) > 0): ?>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($patient['patient_id']); ?></td>
                                                <td><?= htmlspecialchars($patient['full_name']); ?></td>
                                                <td><?= htmlspecialchars($patient['email']); ?></td>
                                                <td><?= htmlspecialchars($patient['phone_number']); ?></td>
                                                <td>
                                                    <a class="btn btn-profile"
                                                        href="patient_details.php?patient_id=<?php echo $patient['patient_id']; ?>">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">No patients found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                        </div>
                        <div class="pagination-container">
                        <nav class="pagination" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-button prev" aria-label="Previous page">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <ul class="pagination-list">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li>
                                        <a href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>"
                                            class="pagination-link <?php echo $i == $page ? 'is-current' : ''; ?>"
                                            aria-label="Page <?php echo $i; ?>" <?php echo $i == $page ? 'aria-current="page"' : ''; ?>>
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>"
                                    class="pagination-button next" aria-label="Next page">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    </div>
                    
                </div>
                <script src="/js/patients.js"></script>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        flatpickr("#date_of_birth", {
                            dateFormat: "Y-m-d",
                            maxDate: "today",
                            onChange: function (selectedDates, dateStr, instance) {
                                const dob = dateStr;
                                if (dob) {
                                    const age = calculateAge(dob);
                                    document.getElementById('age').value = age;
                                }
                            }
                        });

                        function calculateAge(dob) {
                            const today = new Date();
                            const birthDate = new Date(dob);
                            let age = today.getFullYear() - birthDate.getFullYear();
                            const monthDiff = today.getMonth() - birthDate.getMonth();
                            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                                age--;
                            }
                            return age;
                        }
                    });
                </script>
    </body>

    </html>