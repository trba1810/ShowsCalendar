import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import React from 'react';

const CalendarPage: React.FC = () => {
    return (
        <div>
            <h1>My Calendar</h1>
            <FullCalendar
                plugins={[dayGridPlugin, interactionPlugin, timeGridPlugin, listPlugin]}
                initialView="dayGridMonth"
                headerToolbar={{
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth',
                }}
                editable={true}
                selectable={true}
                selectMirror={true}
                dayMaxEvents={true}
            />
        </div>
    );
};

export default CalendarPage;
