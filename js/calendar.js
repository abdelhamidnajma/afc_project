// Kalender functionaliteit voor juni 2025 - hoofdscript voor agenda pagina
document.addEventListener('DOMContentLoaded', function() {
    // DOM elementen ophalen voor kalender en evenementen
    const calendar = document.getElementById('calendar');
    const eventsList = document.getElementById('eventsList');
    const eventPopup = document.getElementById('eventPopup');
    const popupContent = document.getElementById('popupContent');
    const closePopup = document.getElementById('closePopup');
    const eventTitle = document.getElementById('event-title');
    const eventDate = document.getElementById('event-date');
    const eventTime = document.getElementById('event-time');
    const eventLocation = document.getElementById('event-location');
    const eventDescription = document.getElementById('event-description');

    // Voorbeeld evenementen voor juni 2025 - statische data voor demonstratie
    const events = [
        {
            id: 1,
            title: 'Training Senioren',
            date: '2025-06-02',
            time: '19:00',
            location: 'Sportpark Goed Genoeg',
            description: 'Reguliere training voor het seniorenteam'
        },
        {
            id: 2,
            title: 'Wedstrijd AFC vs Quick Boys',
            date: '2025-06-07',
            time: '15:00',
            location: 'Sportpark Goed Genoeg',
            description: 'Thuiswedstrijd tegen Quick Boys'
        },
        {
            id: 3,
            title: 'Jeugdtraining',
            date: '2025-06-09',
            time: '16:00',
            location: 'Sportpark Goed Genoeg',
            description: 'Training voor alle jeugdteams'
        },
        {
            id: 4,
            title: 'Uitwedstrijd Jong Almere City',
            date: '2025-06-14',
            time: '14:30',
            location: 'Almere',
            description: 'Uitwedstrijd tegen Jong Almere City'
        },
        {
            id: 5,
            title: 'Algemene Ledenvergadering',
            date: '2025-06-21',
            time: '20:00',
            location: 'Clubhuis',
            description: 'Jaarlijkse algemene ledenvergadering'
        },
        {
            id: 6,
            title: 'Vriendenwedstrijd',
            date: '2025-06-28',
            time: '15:00',
            location: 'Sportpark Goed Genoeg',
            description: 'Vriendschappelijke wedstrijd tegen oude bekenden'
        }
    ];

    // Kalender initialiseren - bouwt de kalender op met evenementen
    function initializeCalendar() {
        // Bestaande content leegmaken
        calendar.innerHTML = '';

        // Juni 2025 begint op zondag (0) - aanpassen voor maandag start
        const currentDate = new Date(2025, 5, 1);
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        const startDate = new Date(firstDay);
        
        // Zondag (0) omzetten naar 6, maandag (1) naar 0, etc.
        const dayOfWeek = firstDay.getDay();
        const adjustedDay = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
        startDate.setDate(startDate.getDate() - adjustedDay);

        // Lege cellen toevoegen voor dagen voor 1 juni
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            
            if (date.getMonth() === currentDate.getMonth()) {
                dayElement.classList.add('current-month');
            }
            
            dayElement.textContent = date.getDate();
            
            const dayEvents = events.filter(event => event.date === formatDate(date));
            if (dayEvents.length > 0) {
                dayElement.classList.add('has-event');
                dayElement.addEventListener('click', () => showEventPopup(dayEvents[0]));
            }
            
            calendar.appendChild(dayElement);
        }

        // Aankomende evenementen lijst vullen
        displayUpcomingEvents();
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Aankomende evenementen weergeven in de lijst
    function displayUpcomingEvents() {
        if (!eventsList) return;
        
        eventsList.innerHTML = '';
        
        // Evenementen sorteren op datum
        const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
        
        sortedEvents.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = 'event-item';
            eventElement.innerHTML = `
                <div class="event-date">
                    <span class="day">${new Date(event.date).getDate()}</span>
                    <span class="month">${new Date(event.date).toLocaleDateString('nl-NL', { month: 'short' })}</span>
                </div>
                <div class="event-details">
                    <h4>${event.title}</h4>
                    <p>${event.time} - ${event.location}</p>
                </div>
            `;
            eventElement.addEventListener('click', () => showEventPopup(event));
            eventsList.appendChild(eventElement);
        });
    }

    // Evenement popup weergeven - toont details van een evenement
    function showEventPopup(event) {
        if (!eventPopup || !eventTitle || !eventDate || !eventTime || !eventLocation || !eventDescription) return;

        // Datum formatteren naar Nederlandse notatie
        const eventDateObj = new Date(event.date);
        const formattedDate = eventDateObj.toLocaleDateString('nl-NL', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        // Popup content bijwerken
        eventTitle.textContent = event.title;
        eventDate.textContent = formattedDate;
        eventTime.textContent = event.time;
        eventLocation.textContent = event.location;
        eventDescription.textContent = event.description;

        // Popup weergeven
        eventPopup.style.display = 'flex';
    }

    // Popup sluiten bij klikken op sluitknop
    if (closePopup) {
        closePopup.addEventListener('click', () => {
            eventPopup.style.display = 'none';
        });
    }

    // Popup sluiten bij klikken buiten de popup
    window.addEventListener('click', (e) => {
        if (e.target === eventPopup) {
            eventPopup.style.display = 'none';
        }
    });

    // Popup sluiten met Escape toets
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && eventPopup.style.display === 'flex') {
            eventPopup.style.display = 'none';
        }
    });

    // Kalender initialiseren
    initializeCalendar();
});

// CSS classes toevoegen voor styling van kalender elementen
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .calendar-date {
            position: relative;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border: 1px solid #eee;
        }

        .calendar-date.has-event {
            background-color: #f8f9fa;
        }

        .event-dot {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #007bff;
            border-radius: 50%;
        }

        .event-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .event-popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }

        .close-popup {
            position: absolute;
            top: 10px;
            right: 10px;
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
        }

        .event-card {
            display: flex;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .event-date {
            text-align: center;
            margin-right: 15px;
            min-width: 60px;
        }

        .event-date .day {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }

        .event-date .month {
            color: #666;
            text-transform: uppercase;
        }

        .event-info h3 {
            margin: 0 0 10px 0;
        }

        .event-info p {
            margin: 5px 0;
            color: #666;
        }

        .event-info i {
            width: 20px;
            color: #007bff;
        }
    </style>
`); 