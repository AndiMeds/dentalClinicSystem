function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function (fetchInfo, successCallback, failureCallback) {
            showLoading();
            $.ajax({
                url: 'api_appointments.php',
                type: 'GET',
                success: function (response) {
                    console.log('API Response:', response);
                    console.log('All appointments:', response);
                    const events = response.map(event => ({
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        extendedProps: {
                            ...event.extendedProps,
                            time: event.time_block  // Map time_block to time in extendedProps
                        },
                        time: event.time, 
                        backgroundColor: getStatusColor(event.extendedProps.status),
                        borderColor: getStatusColor(event.extendedProps.status)
                    }));
                    console.log('Final formatted events:', events);
                    successCallback(events);
                    hideLoading();
                },
                error: function (error) {
                    console.error('Error fetching events:', error);
                    failureCallback(error);
                    hideLoading();
                    showError('Failed to load appointments');
                }
            });
        },
        eventClick: function (info) {
            openAppointmentModal(info.event);
        },
        eventDidMount: function (info) {
            const event = info.event;
            const tooltip = `
                Patient: ${event.extendedProps.username}
                Service: ${event.extendedProps.service_type}
                Status: ${event.extendedProps.status}
            `;
            info.el.setAttribute('title', tooltip);
        },
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5, 6],
            startTime: '09:00',
            endTime: '18:00',
        },
    });

    calendar.render();
}