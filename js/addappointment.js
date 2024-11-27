let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = "";
let selectedDateElement = null;
let selectedSlotElement = null;

let holidayDates = [];  // This will store the holidays.

function fetchHolidays() {
    const apiKey = 'AIzaSyDRNKH3lo-FaELDunaz3azWMXgJ31aY48g';
    const calendarId = 'en-gb.philippines%23holiday%40group.v.calendar.google.com'; // Use your correct calendar ID
    const url = `https://www.googleapis.com/calendar/v3/calendars/${calendarId}/events?key=${apiKey}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error fetching holidays: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.items && Array.isArray(data.items)) {
                holidayDates = data.items.map(event => {
                    const eventDate = new Date(event.start.dateTime || event.start.date);
                    return {
                        date: eventDate.toLocaleDateString('en-CA'),  // Store date in 'YYYY-MM-DD' format
                        title: event.summary  // Store the event's title (holiday name)
                    };
                });
                generateCalendar(currentMonth, currentYear);  // Re-generate the calendar after fetching holidays
            } else {
                console.error("No holidays found or data format is incorrect");
            }
        })
        .catch(error => console.error('Error fetching holidays:', error));
}

function generateCalendar(month, year) {
    const monthYearElement = document.getElementById('monthYear');
    const calendarDatesElement = document.getElementById('calendarDates');
    const months = [
        "JANUARY", "FEBRUARY", "MARCH", "APRIL", "MAY", "JUNE",
        "JULY", "AUGUST", "SEPTEMBER", "OCTOBER", "NOVEMBER", "DECEMBER"
    ];

    monthYearElement.textContent = `${months[month]} ${year}`;
    calendarDatesElement.innerHTML = "";

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let nextAvailableDate = null;

    // Add blank divs for the days before the start of the month
    for (let i = 0; i < firstDay; i++) {
        const blankDiv = document.createElement('div');
        blankDiv.classList.add('calendar-date');
        calendarDatesElement.appendChild(blankDiv);
    }

    // Loop through the days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateDiv = document.createElement('div');
        dateDiv.classList.add('calendar-date');
        dateDiv.textContent = day;

        const dateObj = new Date(year, month, day);
        dateObj.setHours(0, 0, 0, 0);
        const formattedDate = dateObj.toLocaleDateString('en-CA');  // Format date as YYYY-MM-DD

        // Check if the current date is a holiday
        const holiday = holidayDates.find(holiday => holiday.date === formattedDate);
        if (holiday) {
            dateDiv.classList.add('holiday');  // Add 'holiday' class to highlight it
            const holidayName = document.createElement('div');
            holidayName.classList.add('holiday-name');
            holidayName.textContent = holiday.title;  // Display the holiday's name
            dateDiv.appendChild(holidayName);  // Append the holiday name inside the date cell
            dateDiv.classList.add('disabled');  // Disable holiday dates
        }

        // Disable past dates, Sundays, and holidays
        if (dateObj <= today || dateObj.getDay() === 0 || dateDiv.classList.contains('disabled')) {
            dateDiv.classList.add('disabled');
        } else {
            dateDiv.onclick = () => selectDate(dateDiv, year, month + 1, day);
            if (!nextAvailableDate) {
                nextAvailableDate = dateObj;
                dateDiv.classList.add('next-available');  // Add class for next available date
            }
        }

        calendarDatesElement.appendChild(dateDiv);
    }

    // Pre-select the next available date if one exists
    if (nextAvailableDate) {
        selectDate(null, nextAvailableDate.getFullYear(), nextAvailableDate.getMonth() + 1, nextAvailableDate.getDate());
    }
}



window.onload = function() {
    fetchHolidays();  // Fetch holidays as soon as the page loads
};




function selectDate(dateDiv, year, month, day) {
    if (selectedDateElement) {
        selectedDateElement.classList.remove('selected');
    }
    if (dateDiv) {
        dateDiv.classList.add('selected');
        selectedDateElement = dateDiv;
    }

    selectedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    document.getElementById("appointmentDate").value = selectedDate;

    const dateObj = new Date(year, month - 1, day);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = dateObj.toLocaleDateString('en-US', options);

    document.getElementById("selectedDate").textContent = formattedDate;

    loadSlots(selectedDate);
}

function loadSlots(date) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "../php/addappointment.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === "error") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#3085d6'
                });
            } else {
                displaySlots(response);
            }
        } else {
            Swal.fire('Error', 'An error occurred while fetching slots.', 'error');
        }
    };
    xhr.send(`action=fetch_slots&date=${date}`);
}

function displaySlots(slots) {
    const slotsContainer = document.getElementById('slotsContainer');
    slotsContainer.innerHTML = '';

    selectedSlotElement = null;
    document.getElementById("selectedTimeSlot").value = '';

    // Handle appointment_id if present
    if (slots.appointment_id) {
        document.getElementById("appointment_id").value = slots.appointment_id;
    }

    // Ensure slots is an array
    let slotsArray = [];
    if (Array.isArray(slots)) {
        slotsArray = slots;
    } else if (slots.slots && Array.isArray(slots.slots)) {
        // If slots is wrapped in an object
        slotsArray = slots.slots;
    } else if (typeof slots === 'object' && !Array.isArray(slots)) {
        // If slots is an object but not an array, convert to array
        slotsArray = Object.values(slots).filter(slot =>
            typeof slot === 'object' && 'time' in slot
        );
    }

    // If we still don't have any valid slots, show an error message
    if (slotsArray.length === 0) {
        const message = document.createElement('p');
        message.textContent = 'No available time slots for this date.';
        message.classList.add('no-slots-message');
        slotsContainer.appendChild(message);
        return;
    }

    // Create buttons for each slot
    slotsArray.forEach(slot => {
        const button = document.createElement('button');
        const timeText = slot.time || 'Undefined';

        button.textContent = `${timeText}`;
        button.innerHTML += `<span class="availability"> (${slot.available} available)</span>`;
        button.onclick = () => selectSlot(slot.time, button);
        button.disabled = slot.available === 0;
        button.type = 'button';
        slotsContainer.appendChild(button);
    });
}

// Helper function to validate slot data
function isValidSlot(slot) {
    return (
        typeof slot === 'object' &&
        slot !== null &&
        'time' in slot &&
        'available' in slot &&
        'pending' in slot
    );
}

function selectSlot(time, button) {
    if (selectedSlotElement) {
        selectedSlotElement.classList.remove('selected');
    }
    button.classList.add('selected');
    selectedSlotElement = button;
    document.getElementById("selectedTimeSlot").value = time;
}

function prevMonth() {
    if (currentMonth === 0) {
        currentMonth = 11;
        currentYear--;
    } else {
        currentMonth--;
    }
    generateCalendar(currentMonth, currentYear);
}

function nextMonth() {
    if (currentMonth === 11) {
        currentMonth = 0;
        currentYear++;
    } else {
        currentMonth++;
    }
    generateCalendar(currentMonth, currentYear);
}

document.getElementById("appointmentForm").addEventListener("submit", function (event) {
    event.preventDefault();

    if (!validateForm()) {
        return;
    }

    const serviceType = document.getElementById('service').value;
    const complain = document.getElementById('complain').value;
    const appointmentDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;

    const [year, month, day] = appointmentDate.split('-');
    const dateObj = new Date(year, parseInt(month) - 1, day);
    const formattedDate = dateObj.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    Swal.fire({
        title: 'Confirm Appointment',
        html:
            `<div style="text-align: justify;">
                <p><strong>SERVICE TYPE: </strong> ${serviceType}</p>
                <p><strong>COMPLAINT: </strong> ${complain}</p>
                <p><strong>DATE: </strong> ${formattedDate}</p>
                <p><strong>TIME SLOT: </strong> ${selectedSlot}</p>
                <p>Are you sure you want to confirm this appointment?</p>
            </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(document.getElementById("appointmentForm"));
            formData.append('action', 'book_appointment');

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "../php/addappointment.php", true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);

                    if (response.status === "error") {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            confirmButtonColor: '#3085d6'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (response.updatedSlots) {
                                    displaySlots(response.updatedSlots);
                                }
                                window.location.href = '../patients/view_appointments.php';
                            }
                        });
                    }
                } else {
                    Swal.fire('Error', 'An error occurred while submitting the form.', 'error');
                }
            };
            xhr.send(formData);
        }
    });
});

function validateForm() {
    const serviceType = document.getElementById('service').value;
    const complain = document.getElementById('complain').value;
    const selectedDate = document.getElementById('appointmentDate').value;
    const selectedSlot = document.getElementById("selectedTimeSlot").value;

    const errorMessages = [];

    if (serviceType === "") {
        errorMessages.push("Please select a service.");
    }
    if (complain.trim() === "") {
        errorMessages.push("Please enter a complaint.");
    }
    if (selectedDate === "") {
        errorMessages.push("Please select a date.");
    }
    if (selectedSlot === "") {
        errorMessages.push("Please select a time slot.");
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const selectedDateObj = new Date(selectedDate);
    if (selectedDateObj <= today) {
        errorMessages.push("Please select a future date for the appointment.");
    }

    if (errorMessages.length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Please fill in all required fields',
            html: errorMessages.join('<br>'),
            confirmButtonColor: '#3085d6'
        });
        return false;
    }

    return true;
}

generateCalendar(currentMonth, currentYear);