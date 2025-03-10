import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import axios from 'axios';
import React, { useEffect, useState } from 'react';

interface Episode {
    id: number;
    title: string;
    start: string;
    description: string;
    show_id: number;
    episode_number: number;
    season_number: number;
}

const CalendarPage: React.FC = () => {
    const [episodes, setEpisodes] = useState<Episode[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchEpisodes = async () => {
            try {
                const response = await axios.get('/shows/monthly');
                console.log('API Response:', response); // Debug log
                setEpisodes(response.data);
            } catch (error) {
                if (axios.isAxiosError(error)) {
                    console.error('Error details:', error.response || error.message); // Enhanced error logging
                } else {
                    console.error('Error details:', error); // Enhanced error logging
                }
                setLoading(false);
            }
        };

        fetchEpisodes();
    }, []);

    return (
        <div className="calendar-container p-4">
            <h1 className="mb-4 text-2xl font-bold">My Shows Calendar</h1>
            {loading ? (
                <div className="p-4 text-center">Loading episodes...</div>
            ) : (
                <FullCalendar
                    plugins={[dayGridPlugin, interactionPlugin, timeGridPlugin, listPlugin]}
                    initialView="dayGridMonth"
                    headerToolbar={{
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek',
                    }}
                    events={episodes.map((episode) => ({
                        id: episode.id.toString(),
                        title: episode.title,
                        start: episode.start,
                        description: episode.description,
                        extendedProps: {
                            show_id: episode.show_id,
                            episode_number: episode.episode_number,
                            season_number: episode.season_number,
                        },
                    }))}
                    eventContent={(eventInfo) => (
                        <div className="episode-event p-1">
                            <div className="font-semibold">{eventInfo.event.title}</div>
                            {eventInfo.event.extendedProps.description && (
                                <div className="truncate text-sm">{eventInfo.event.extendedProps.description}</div>
                            )}
                        </div>
                    )}
                    eventClick={(info) => {
                        // Optional: Add click handler to show more details
                        console.log('Episode clicked:', info.event);
                    }}
                    height="auto"
                    dayMaxEvents={true}
                />
            )}
        </div>
    );
};

export default CalendarPage;
