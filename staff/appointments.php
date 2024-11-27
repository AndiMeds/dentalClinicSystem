<?php
include './staff_appointments/staffview_appointments.php';

// Get the limit per page from the URL or default to 10
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;

// Get the current page number from the URL, if not present, set it to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Calculate the starting row for the SQL query
$offset = ($page - 1) * $limit;

// Get the total number of appointments
$totalQuery = "SELECT COUNT(*) AS total FROM appointments";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalAppointments = $totalRow['total'];

// Calculate total pages based on the limit
$totalPages = ceil($totalAppointments / $limit);

// Fetch appointments for the current page with the current limit, ordered by created_at in descending order
$query = "SELECT * FROM appointments ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/css/sidenav.css">
    <link rel="stylesheet" href="/css/appointments.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container">
        <div class="home_content">
            <?php include 'sidenav.html'; ?>
            <div class="content">
                <h1>APPOINTMENTS</h1>
                <hr class="thin-line">

                <div class="search-and-table-container">
                    <div class="search-container">
                        <div class="show-entries">
                            <label for="entriesSelect">SHOW</label>
                            <select id="entriesSelect" aria-label="Entries per page">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <!-- Dropdown for Task Status -->
                        <div class="status-dropdown">
                            <label for="statusFilter">FILTER </label>
                            <select id="statusFilter" onchange="filterAppointments()">
                                <option value="all">All</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>

                        <div class="InputContainer">
                            <input placeholder="SEARCH BY APPOINTMENT ID OR USERNAME" id="searchInput" class="input"
                                name="text" type="text" />
                            <label class="labelforsearch" for="searchInput">
                                <svg class="searchIcon" viewBox="0 0 512 512">
                                    <path
                                        d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z">
                                    </path>
                                </svg>
                            </label>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="appointment-table">
                            <thead>
                                <tr>
                                    <th>Apt. ID</th>
                                    <th>Username</th>
                                    <th>Appointment<br> Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Complain</th>
                                    <th>Follow <br>Up</th>
                                    <th>Preferred<br>Dentist</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Cancellation <br>Reason</th>
                                    <th>Created<br> At</th>
                                    <th>Updated<br> At</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody id="appointment-body">
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $statusClass = strtolower(trim($row['status'])); // Convert status to lowercase and trim
                                
                                        // Format the created_at and updated_at dates
                                        $createdAt = new DateTime($row['created_at']);
                                        $formattedCreatedDate = $createdAt->format('F j, Y'); // Example: October 21, 2024
                                        $formattedCreatedTime = $createdAt->format('g:i A'); // Example: 3:35 PM
                                
                                        $updatedAt = new DateTime($row['updated_at']);
                                        $formattedUpdatedDate = $updatedAt->format('F j, Y'); // Example: October 24, 2024
                                        $formattedUpdatedTime = $updatedAt->format('g:i A'); // Example: 1:16 AM
                                
                                        echo "<tr class='appointment-row $statusClass'>
                                            <td>" . htmlspecialchars($row['appointment_id']) . "</td>
                                            <td>" . htmlspecialchars($row['username']) . "</td>
                                            <td>" . htmlspecialchars($row['appointment_date']) . "</td>
                                            <td>" . htmlspecialchars($row['appointment_time']) . "</td>
                                            <td>" . htmlspecialchars($row['selected_services']) . "</td>
                                            <td>" . htmlspecialchars($row['complaint']) . "</td>
                                            <td>" . htmlspecialchars($row['followup']) . "</td>
                                            <td>" . htmlspecialchars($row['preferred_dentist']) . "</td>
                                            <td>" . htmlspecialchars($row['remarks']) . "</td>
                                            <td><span class='status $statusClass'>" . htmlspecialchars($row['status']) . "</span></td>
                                            <td>" . htmlspecialchars($row['cancellation_reason']) . "</td>
                                            <td>
                                                <div>" . htmlspecialchars($formattedCreatedDate) . "</div>
                                                <div>" . htmlspecialchars($formattedCreatedTime) . "</div>
                                            </td>
                                            <td>
                                                <div>" . htmlspecialchars($formattedUpdatedDate) . "</div>
                                                <div>" . htmlspecialchars($formattedUpdatedTime) . "</div>
                                            </td>
                                            <td>";

                                        if (trim(strtolower($row['status'])) !== 'cancelled') {
                                            echo "<button type='button' onclick='editAppointment(\"" . htmlspecialchars($row['appointment_id']) . "\")'>
                                                    <i class='bx bx-edit'></i> Edit
                                                  </button>";
                                        }
                                        echo "</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='15'>NO APPOINTMENTS FOUND</td></tr>";
                                }


                                ?>
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
        </div>
    </div>
</body>

<script src="./staff_appointments/staffviewappointments.js"></script>

</html>