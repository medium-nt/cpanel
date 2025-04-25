document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var events = JSON.parse(calendarEl.getAttribute('data-events'));

    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: events,
        locale: 'ru',
        firstDay: 1,
        buttonText: {
            today: 'Сегодня'
        }
    });

    calendar.render();
});
