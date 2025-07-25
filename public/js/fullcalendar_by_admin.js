document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var events = JSON.parse(calendarEl.getAttribute('data-events'));
    var csrf_token = calendarEl.getAttribute('data-csrf_token');
    var user_id = calendarEl.getAttribute('data-user_id');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        selectable: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: events,
        locale: 'ru',
        firstDay: 1,
        buttonText: {
            today: 'Сегодня',
        },

        dateClick: function(info) {
            $.post('/megatulle/schedule/changeDate', {
                user_id: user_id,
                date: info.dateStr,
                _token: csrf_token
            }, function(response) {
                if(response.deleted){
                    const clickedDate = info.dateStr;

                    // Найти фоновое событие, совпадающее с выбранной датой
                    const matchingEvent = calendar.getEvents().find((event) => (
                        event.display === 'background' &&
                        event.startStr === clickedDate
                    ));

                    if(matchingEvent) {
                        var event = calendar.getEventById(matchingEvent.id)
                        event.remove();
                    }
                } else {
                    calendar.addEvent({
                        id: response.id,
                        start: info.dateStr,
                        end: info.dateStr,
                        display: 'background'
                    });
                }
            });
        },

        select: function(info) {
            calendar.unselect();
        }
    });

    calendar.render();
});
