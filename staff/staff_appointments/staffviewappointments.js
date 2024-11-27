// Define functions in the global scope
function editAppointment(appointmentID) {
    if (!appointmentID) {
        console.error('No appointment ID provided');
        return;
    }
    
    // Log the URL we're redirecting to for debugging
    const editUrl = '/staff/staff_appointments/edit_appointment.php?appointment_id=' + encodeURIComponent(appointmentID);
    console.log('Redirecting to:', editUrl);
    
    window.location.href = editUrl;
}
// Function to check if the update was successful and show SweetAlert
function checkUpdateSuccess() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('update_success') === 'true') {
        Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'The appointment has been successfully updated.',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'custom-green-button'
            }
        }).then(() => {
            // Remove the update_success parameter from the URL
            const url = new URL(window.location.href);
            url.searchParams.delete('update_success');
            window.history.replaceState({}, document.title, url.href);
        });
    }
}

// Call this function when the page loads
document.addEventListener('DOMContentLoaded', function () {
    // Check if update success parameter is present in the URL
    checkUpdateSuccess();

    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.appointment-table');
    const rows = table.querySelectorAll('tbody tr');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();

            rows.forEach(row => {
                const appointmentID = row.cells[0].textContent.toLowerCase(); // Using appointment_id now
                const username = row.cells[1].textContent.toLowerCase();

                if (appointmentID.includes(searchTerm) || username.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Moved this block out of the nested DOMContentLoaded
    const entriesSelect = document.getElementById('entriesSelect');

    if (entriesSelect) {
        entriesSelect.addEventListener('change', function () {
            const selectedValue = entriesSelect.value;
            const urlParams = new URLSearchParams(window.location.search);

            urlParams.set('limit', selectedValue); // Update the limit in the URL
            urlParams.set('page', 1); // Always reset to page 1 when limit changes

            const newUrl = '?' + urlParams.toString();
            console.log('Reloading with URL:', newUrl); // Debugging: Check the URL in the console

            // Reload the page with the new limit and page 1
            window.location.search = newUrl;
        });
    }
});

// JavaScript for filtering appointments by status from dropdown
function filterAppointments() {
    const status = document.getElementById('statusFilter').value;
    const appointments = document.querySelectorAll('.appointment-row');

    appointments.forEach(appointment => {
        if (status === 'all') {
            appointment.style.display = 'table-row';
        } else {
            if (appointment.classList.contains(status)) {
                appointment.style.display = 'table-row';
            } else {
                appointment.style.display = 'none';
            }
        }
    });
}
