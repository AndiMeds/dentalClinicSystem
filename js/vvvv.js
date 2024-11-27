let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = "";
let selectedDateElement = null;
let selectedSlotElement = null;

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

    for (let i = 0; i < firstDay; i++) {
        const blankDiv = document.createElement('div');
        blankDiv.classList.add('calendar-date');
        calendarDatesElement.appendChild(blankDiv);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateDiv = document.createElement('div');
        dateDiv.classList.add('calendar-date');
        dateDiv.textContent = day;

        const dateObj = new Date(year, month, day);

        if (dateObj < today.setHours(0, 0, 0, 0) || dateObj.getDay() === 0) {
            dateDiv.classList.add('disabled');
        } else {
            dateDiv.onclick = () => selectDate(dateDiv, year, month + 1, day);
            
            // Select today's date by default
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                selectDate(dateDiv, year, month + 1, day);
            }
        }

        calendarDatesElement.appendChild(dateDiv);
    }
}

function selectDate(dateDiv, year, month, day) {
    if (selectedDateElement) {
        selectedDateElement.classList.remove('selected');
    }
    dateDiv.classList.add('selected');
    selectedDateElement = dateDiv;

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
    xhr.open('POST', '../php/addappointment.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            const slots = JSON.parse(xhr.responseText);
            displaySlots(slots);
            document.getElementById('slotsContainer').style.display = 'block';
        } else {
            console.error('Error loading slots:', xhr.statusText);
        }
    };
    xhr.send('action=fetch_slots&date=' + date);
}

function displaySlots(slots) {
    const slotsContainer = document.getElementById('slotsContainer');
    slotsContainer.innerHTML = '';

    selectedSlotElement = null;
    document.getElementById("selectedTimeSlot").value = '';

    if (slots.appointment_id) {
        document.getElementById("appointment_id").value = slots.appointment_id;
    }

    slots.forEach(slot => {
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

function selectSlot(slot, slotElement) {
    if (selectedSlotElement) {
        selectedSlotElement.classList.remove('selected');
    }
    slotElement.classList.add('selected');
    selectedSlotElement = slotElement;

    document.getElementById("selectedTimeSlot").value = slot;
}

function prevMonth() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    generateCalendar(currentMonth, currentYear);
}

function loadInitialTimeSlots() {
    const today = new Date();
    const formattedDate = `${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`;
    loadSlots(formattedDate);
}

document.addEventListener('DOMContentLoaded', function() {
    generateCalendar(currentMonth, currentYear);
    loadInitialTimeSlots();
});

document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        #slotsContainer {
            display: block;
        }
    `;
    document.head.appendChild(style);

    generateCalendar(currentMonth, currentYear);
    loadInitialTimeSlots();
});

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
                    Swal.fire({
                        icon: response.status,
                        title: response.title,
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed && response.status === 'success') {
                            window.location.href = '../patients/view_appointments.php';
                        }
                    });
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