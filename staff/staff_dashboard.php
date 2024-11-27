<?php

require_once '../php/dashboard.php';


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Dashboard</title>
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="top-bar">
        <div class="notification-container">
            <i class="fa-regular fa-bell"></i>
        </div>
    </div>

    <div class="container">
        <div class="home_content">
            <div class="sidenav">
                <?php include "sidenav.html"; ?>
            </div>

            <div class="content">
                <h1 class="dashboard-title">Dashboard</h1>

                <div class="dashboard-container">
                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card bg-blue">
                            <i class="fa-solid fa-users"></i>
                            <div class="stat-content">
                                <h3>Total Patients</h3>
                                <p><?php echo $totalPatients; ?></p>
                            </div>
                        </div>

                        <div class="stat-card bg-green">
                            <i class="fa-solid fa-user-md"></i>
                            <div class="stat-content">
                                <h3>Total Dentists</h3>
                                <p><?php echo $totalDentists; ?></p>
                            </div>
                        </div>

                        <div class="stat-card bg-purple">
                            <i class="fa-solid fa-calendar-check"></i>
                            <div class="stat-content">
                                <h3>Today's Appointments</h3>
                                <p><?php echo $totalTodayAppointments; ?></p>
                            </div>
                        </div>

                        <div class="stat-card bg-teal">
                            <i class="fa-solid fa-check-circle"></i>
                            <div class="stat-content">
                                <h3>Confirmed</h3>
                                <p><?php echo $confirmedAppointments; ?></p>
                            </div>
                        </div>

                        <div class="stat-card bg-orange">
                            <i class="fa-solid fa-clock"></i>
                            <div class="stat-content">
                                <h3>Pending</h3>
                                <p><?php echo $pendingRequests; ?></p>
                            </div>
                        </div>

                        <div class="stat-card bg-red">
                            <i class="fa-solid fa-ban"></i>
                            <div class="stat-content">
                                <h3>Cancelled</h3>
                                <p><?php echo $cancelledAppointments; ?></p>
                            </div>
                        </div>
                    </div>
                
                    <!-- Charts Grid -->
                    <div class="charts-grid">
                        <div class="chart-container services">
                            <h3>Most Availed Services</h3>
                            <canvas id="serviceTypeChart"></canvas>
                        </div>

                        <div class="chart-container demographics">
                            <h3>Patient Demographics</h3>
                            <canvas id="demographicsChart"></canvas>
                        </div>

                        <div class="chart-container accounts">
                            <h3>New Accounts</h3>
                            <canvas id="accountsChart"></canvas>
                        </div>

                        <div class="chart-container appointments">
                            <h3>Appointments Overview</h3>
                            <canvas id="appointmentsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const malePatients = <?php echo $malePatients; ?>;
        const femalePatients = <?php echo $femalePatients; ?>;
        const ctx = document.getElementById('demographicsChart').getContext('2d');

        // Common chart options
        const commonChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            }
        };

        if (malePatients >= 0 && femalePatients >= 0) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{
                        data: [malePatients, femalePatients],
                        backgroundColor: [
                            'rgba(119,169,215)', // Male color
                            'rgba(234,209,220)' // Female color
                        ],
                        borderColor: [
                            'rgba(119,169,215)', // Male border color
                            'rgba(234,209,220)' // Female border color
                        ],
                        borderWidth: 1,
                        hoverBackgroundColor: [
                            'rgba(77,117,154)',
                            'rgba(214,192,203)',
                        ]
                    }]
                },
                options: {
                    ...commonChartOptions,
                    aspectRatio: 1,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }

        // Service Type Data for Bar Chart
        const serviceTypes = <?php echo json_encode($serviceTypes); ?>;
        const serviceCounts = <?php echo json_encode($serviceCounts); ?>;
        const ctxService = document.getElementById('serviceTypeChart').getContext('2d');
        new Chart(ctxService, {
            type: 'bar',
            data: {
                labels: serviceTypes,
                datasets: [{
                    label: 'Availed Services',
                    data: serviceCounts,
                    backgroundColor: 'rgba(171,201,206)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    hoverBackgroundColor: 'rgba(75, 192, 192, 1)',
                    hoverBorderColor: 'rgba(171,201,206)',
                    hoverBorderWidth: 2
                }]
            },
            options: {
                ...commonChartOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        });

        // Bar Chart for Account Creation by Day
        const creationDates = <?php echo json_encode($creationDates); ?>;
        const accountsCreated = <?php echo json_encode($accountsCreated); ?>;
        const ctx2 = document.getElementById('accountsChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: creationDates,
                datasets: [{
                    label: 'Accounts Created per Day',
                    data: accountsCreated,
                    backgroundColor: 'rgba(188,210,200)',
                    borderColor: 'rgba(167,187,178)',
                    borderWidth: 1
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        });

        // Line Chart for Appointments Created per Day
        const appointmentDates = <?php echo json_encode($appointmentDates); ?>;
        const appointmentsCreated = <?php echo json_encode($appointmentsCreated); ?>;
        const ctxAppointments = document.getElementById('appointmentsChart').getContext('2d');
        new Chart(ctxAppointments, {
            type: 'line',
            data: {
                labels: appointmentDates,
                datasets: [{
                    label: 'Appointments Created per Day',
                    data: appointmentsCreated,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                }
            }
        });
    </script>

</body>

</html>