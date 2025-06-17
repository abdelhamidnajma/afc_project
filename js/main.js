/*
    AFC Website - Hoofd JavaScript Bestand
    ======================================
    
    Dit bestand bevat alle JavaScript functionaliteit voor de AFC website.
    Inclusief:
    - Kalender functionaliteit voor evenementen
    - Event popup weergave
    - Dynamische content loading
    - API communicatie met backend
    - Event handling en DOM manipulatie
    
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Kalender functionaliteit - initialiseert de kalender met evenementen
function initializeCalendar(year, month, events) {
    // DOM elementen ophalen voor kalender en evenementen
    const calendarDates = document.getElementById('calendar-dates');
    const eventsList = document.getElementById('events-list');
    const popup = document.getElementById('event-popup');
    const closePopup = document.querySelector('.close-popup');

    // Bestaande content leegmaken
    calendarDates.innerHTML = '';
    eventsList.innerHTML = '';

    // Eerste dag van de maand en totaal aantal dagen berekenen
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const totalDays = lastDay.getDate();
    const startingDay = firstDay.getDay() || 7; // Zondag (0) omzetten naar 7

    // Lege cellen toevoegen voor dagen voor de eerste van de maand
    for (let i = 1; i < startingDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'calendar-date empty';
        calendarDates.appendChild(emptyCell);
    }

    // Dagen van de maand toevoegen
    for (let day = 1; day <= totalDays; day++) {
        const dateCell = document.createElement('div');
        dateCell.className = 'calendar-date';
        dateCell.textContent = day;
        dateCell.dataset.date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

        // Controleren of er evenementen zijn op deze dag
        const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEvents = events.filter(event => event.date === currentDate);

        if (dayEvents.length > 0) {
            dateCell.classList.add('has-event');
            const eventDot = document.createElement('div');
            eventDot.className = 'event-dot';
            dateCell.appendChild(eventDot);

            // Click event toevoegen om popup te tonen
            dateCell.addEventListener('click', () => showEventPopup(dayEvents[0]));
        }

        calendarDates.appendChild(dateCell);
    }

    // Aankomende evenementen lijst vullen
    const sortedEvents = [...events].sort((a, b) => new Date(a.date) - new Date(b.date));
    sortedEvents.forEach(event => {
        const eventElement = document.createElement('div');
        eventElement.className = 'event-card';
        eventElement.innerHTML = `
            <div class="event-date">
                <span class="day">${new Date(event.date).getDate()}</span>
                <span class="month">${new Date(event.date).toLocaleString('nl-NL', { month: 'short' })}</span>
            </div>
            <div class="event-info">
                <h3>${event.title}</h3>
                <p><i class="far fa-clock"></i> ${event.time}</p>
                <p><i class="fas fa-map-marker-alt"></i> ${event.location}</p>
            </div>
        `;
        eventElement.addEventListener('click', () => showEventPopup(event));
        eventsList.appendChild(eventElement);
    });

    // Popup sluiten bij klikken op sluitknop
    closePopup.addEventListener('click', () => {
        popup.style.display = 'none';
    });

    // Popup sluiten bij klikken buiten de popup
    window.addEventListener('click', (e) => {
        if (e.target === popup) {
            popup.style.display = 'none';
        }
    });
}

// Evenement popup weergave - toont details van een evenement
function showEventPopup(event) {
    // DOM elementen ophalen voor popup content
    const popup = document.getElementById('event-popup');
    const title = document.getElementById('event-title');
    const date = document.getElementById('event-date');
    const time = document.getElementById('event-time');
    const location = document.getElementById('event-location');
    const description = document.getElementById('event-description');

    // Datum formatteren naar Nederlandse notatie
    const eventDate = new Date(event.date);
    const formattedDate = eventDate.toLocaleDateString('nl-NL', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    // Popup content bijwerken
    title.textContent = event.title;
    date.textContent = formattedDate;
    time.textContent = event.time;
    location.textContent = event.location;
    description.textContent = event.description;

    // Popup weergeven
    popup.style.display = 'flex';
}

// Extra kalender en evenement handling van agenda.html inline script
document.addEventListener('DOMContentLoaded', function() {
    // DOM elementen ophalen voor kalender functionaliteit
    const calendarDates = document.getElementById('calendar-dates');
    const eventPopup = document.getElementById('event-popup');
    const closePopupBtn = document.querySelector('.close-popup');
    const eventTitle = document.getElementById('event-title');
    const eventDate = document.getElementById('event-date');
    const eventTime = document.getElementById('event-time');
    const eventLocation = document.getElementById('event-location');
    const eventDescription = document.getElementById('event-description');
    const eventsList = document.getElementById('events-list');

    // Array voor alle evenementen
    let allEvents = [];

    // Evenementen ophalen van de API
    async function fetchEvents() {
        try {
            const response = await fetch('admin/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_events'
                })
            });

            const data = await response.json();

            if (data.success && data.events) {
                allEvents = data.events;
                initializeCalendar(allEvents);
                displayUpcomingEvents(allEvents);
            } else {
                console.error('Fout bij ophalen evenementen:', data.message);
                // Optioneel een foutmelding op de pagina weergeven
            }
        } catch (error) {
            console.error('Fout bij ophalen evenementen:', error);
            // Optioneel een foutmelding op de pagina weergeven
        }
    }

    // Aankomende evenementen weergeven in de lijst
    function displayUpcomingEvents(events) {
        eventsList.innerHTML = ''; // Huidige evenementen leegmaken
        const now = new Date();

        // Filteren op aankomende evenementen en sorteren op datum
        const upcomingEvents = events.filter(event => new Date(event.date) >= now)
                                     .sort((a, b) => new Date(a.date) - new Date(b.date));

        if (upcomingEvents.length === 0) {
            eventsList.innerHTML = '<p>Geen komende evenementen.</p>';
            return;
        }

        // Evenementen toevoegen aan de lijst
        upcomingEvents.forEach(event => {
            const eventItem = document.createElement('div');
            eventItem.classList.add('event-item');
            eventItem.innerHTML = `
                <div class="event-date">${new Date(event.date).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long' })}</div>
                <div class="event-details">
                    <h3>${event.title}</h3>
                    <p><i class="far fa-clock"></i> ${event.time}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${event.location}</p>
                </div>
            `;
            eventsList.appendChild(eventItem);
        });
    }

    // Evenement popup weergeven bij klikken op kalender datum
    function showEventPopup(e) {
        const events = JSON.parse(e.target.dataset.events);
        const event = events[0]; // Aanname: één evenement per dag voor dit eenvoudige voorbeeld

        eventTitle.textContent = event.title;
        eventDate.textContent = new Date(event.date).toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
        eventTime.textContent = event.time;
        eventLocation.textContent = event.location;
        eventDescription.textContent = event.description;

        eventPopup.style.display = 'block';
    }

    // Event listeners voor popup sluiten
    closePopupBtn.addEventListener('click', function() {
        eventPopup.style.display = 'none';
    });

    // Popup sluiten bij klikken buiten de popup
    window.addEventListener('click', function(e) {
        if (e.target === eventPopup) {
            eventPopup.style.display = 'none';
        }
    });

    // Initial data fetch
    fetchEvents();
});

// Add some CSS classes for styling calendar and events
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
