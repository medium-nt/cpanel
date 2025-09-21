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
        },

        dayCellContent: function(arg) {
            const pad = n => n.toString().padStart(2, '0');
            const dateStr = `${arg.date.getFullYear()}-${pad(arg.date.getMonth() + 1)}-${pad(arg.date.getDate())}`;

            const shiftEvent = events.find(e => e.start === dateStr);

            let customText = arg.date.getDate(); // номер дня

            if (shiftEvent) {
                if(shiftEvent.shift_start !== '00:00:00') {
                    return {
                        html: `<div title="с ${shiftEvent.shift_start} по ${shiftEvent.shift_end}">
                            <span class="fc-daygrid-day-number">
                            <i class="fas fa-check fa-fw fa-xs" style="color: green;"> </i>${customText}
                            </span>
                        </div>`
                    };
                } else {
                    return {
                        html: `<div title="нет на смене">
                            <span class="fc-daygrid-day-number">
                            <i class="fas fa-times fa-fw fa-xs" style="color: red;"> </i>${customText}
                            </span>
                        </div>`
                    };
                }
            }

            return {
                html: `<div><span class="fc-daygrid-day-number">${customText}</span></div>`
            };
        }
    });

    calendar.render();
});
