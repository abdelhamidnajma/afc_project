// Admin page JavaScript from admin.html inline script

// Add validation functions
const validators = {
    required: (value) => value.trim() !== '' || 'Dit veld is verplicht',
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Ongeldig e-mailadres',
    url: (value) => !value || /^https?:\/\/.+/.test(value) || 'Ongeldige URL',
    phone: (value) => !value || /^[0-9\s\-\(\)]{10,15}$/.test(value) || 'Ongeldig telefoonnummer',
    minLength: (min) => (value) => value.length >= min || `Minimaal ${min} tekens`,
    maxLength: (max) => (value) => value.length <= max || `Maximaal ${max} tekens`,
    pattern: (pattern, message) => (value) => pattern.test(value) || message,
    date: (value) => !value || /^\d{4}-\d{2}-\d{2}$/.test(value) || 'Ongeldige datum (YYYY-MM-DD)',
    datetime: (value) => !value || /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value) || 'Ongeldige datum/tijd (YYYY-MM-DD HH:MM:SS)',
    number: (value) => !value || !isNaN(value) || 'Moet een getal zijn',
    min: (min) => (value) => !value || Number(value) >= min || `Minimaal ${min}`,
    max: (max) => (value) => !value || Number(value) <= max || `Maximaal ${max}`,
    password: (value) => {
        if (!value) return true;
        if (value.length < 8) return 'Wachtwoord moet minimaal 8 tekens lang zijn';
        if (!/[A-Z]/.test(value)) return 'Wachtwoord moet minimaal één hoofdletter bevatten';
        if (!/[a-z]/.test(value)) return 'Wachtwoord moet minimaal één kleine letter bevatten';
        if (!/[0-9]/.test(value)) return 'Wachtwoord moet minimaal één cijfer bevatten';
        return true;
    }
};

// Form validation function
function validateForm(form, rules) {
    const errors = {};
    let isValid = true;

    for (const [fieldName, fieldRules] of Object.entries(rules)) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) continue;

        const value = field.value;
        for (const rule of fieldRules) {
            const result = typeof rule === 'function' ? rule(value) : rule(value);
            if (result !== true) {
                errors[fieldName] = result;
                isValid = false;
                break;
            }
        }
    }

    // Show/hide error messages
    for (const [fieldName, error] of Object.entries(errors)) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        const errorElement = field.parentElement.querySelector('.error-message') || 
            document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = error;
        if (!field.parentElement.querySelector('.error-message')) {
            field.parentElement.appendChild(errorElement);
        }
        field.classList.add('error');
    }

    // Clear error messages for valid fields
    form.querySelectorAll('input, select, textarea').forEach(field => {
        if (!errors[field.name]) {
            field.classList.remove('error');
            const errorElement = field.parentElement.querySelector('.error-message');
            if (errorElement) {
                errorElement.remove();
            }
        }
    });

    return isValid;
}

// Add validation rules for each form
const formRules = {
    login: {
        username: [
            validators.required,
            validators.pattern(/^[a-zA-Z0-9_]{3,20}$/, 'Gebruikersnaam mag alleen letters, cijfers en underscores bevatten (3-20 tekens)')
        ],
        password: [validators.required]
    },
    news: {
        title: [
            validators.required,
            validators.minLength(3),
            validators.maxLength(255)
        ],
        content: [
            validators.required,
            validators.minLength(10),
            validators.maxLength(10000)
        ],
        category: [validators.required]
    },
    match: {
        home_team_id: [validators.required, validators.number],
        away_team_id: [validators.required, validators.number],
        match_date: [validators.required, validators.datetime],
        competition: [validators.maxLength(100)]
    },
    team: {
        name: [
            validators.required,
            validators.minLength(2),
            validators.maxLength(100)
        ],
        category: [validators.required],
        division: [validators.maxLength(50)],
        coach: [validators.maxLength(100)]
    },
    settings: {
        website_name: [
            validators.required,
            validators.minLength(2),
            validators.maxLength(100)
        ],
        contact_email: [
            validators.required,
            validators.email
        ],
        phone_number: [
            validators.required,
            validators.phone
        ],
        facebook_url: [validators.url],
        twitter_url: [validators.url],
        instagram_url: [validators.url]
    }
};

// Admin functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check authentication status
    checkAuth();

    // Login handling
    const loginForm = document.getElementById('login');
    const loginSection = document.getElementById('loginForm');
    const dashboard = document.getElementById('dashboard');

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (!validateForm(this, formRules.login)) return;
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        try {
            const response = await fetch('admin/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'login',
                    username: username,
                    password: password
                })
            });

            const data = await response.json();
            if (data.success) {
                loginSection.style.display = 'none';
                dashboard.style.display = 'flex';
                loadDashboardData();
                updateUserInterface(data.user);
            } else {
                alert(data.message || 'Ongeldige inloggegevens');
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('Er is een fout opgetreden bij het inloggen');
        }
    });

    // Logout handling
    document.getElementById('logoutBtn').addEventListener('click', async function() {
        try {
            const response = await fetch('admin/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'logout'
                })
            });

            const data = await response.json();
            if (data.success) {
                loginSection.style.display = 'block';
                dashboard.style.display = 'none';
                loginForm.reset();
            }
        } catch (error) {
            console.error('Logout error:', error);
        }
    });

    // Check authentication status
    async function checkAuth() {
        try {
            const response = await fetch('admin/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'check_auth'
                })
            });

            const data = await response.json();
            if (data.success) {
                loginSection.style.display = 'none';
                dashboard.style.display = 'flex';
                loadDashboardData();
                updateUserInterface(data.user);
            }
        } catch (error) {
            console.error('Auth check error:', error);
        }
    }

    // Update UI based on user data
    function updateUserInterface(user) {
        const userProfile = document.querySelector('.user-profile span');
        if (userProfile) {
            userProfile.textContent = user.username;
        }

        // Update navigation based on role
        const adminOnlyLinks = document.querySelectorAll('[data-role="admin"]');
        adminOnlyLinks.forEach(link => {
            link.style.display = user.role === 'admin' ? 'block' : 'none';
        });
    }

    // Load dashboard data
    async function loadDashboardData() {
        try {
            // Load news articles
            const newsResponse = await fetch('admin/api.php?action=get_news&limit=5');
            const newsData = await newsResponse.json();
            if (newsData.success) {
                updateNewsList(newsData.news);
            }

            // Load matches
            const matchesResponse = await fetch('admin/api.php?action=get_matches&limit=5');
            const matchesData = await matchesResponse.json();
            if (matchesData.success) {
                updateMatchesList(matchesData.matches);
            }

            // Load teams
            const teamsResponse = await fetch('admin/api.php?action=get_teams');
            const teamsData = await teamsResponse.json();
            if (teamsData.success) {
                updateTeamsList(teamsData.teams);
            }

            // Load settings
            const settingsResponse = await fetch('admin/api.php?action=get_settings');
            const settingsData = await settingsResponse.json();
            if (settingsData.success) {
                updateSettingsForm(settingsData.settings);
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    // Update news list
    function updateNewsList(news) {
        const newsList = document.querySelector('.news-list');
        if (!newsList) return;

        newsList.innerHTML = news.map(article => `
            <div class="news-item">
                <div class="news-content">
                    <h3>${article.title}</h3>
                    <p>${article.content.substring(0, 100)}...</p>
                    <div class="news-meta">
                        <span><i class="far fa-calendar"></i> ${new Date(article.created_at).toLocaleDateString()}</span>
                        <span><i class="far fa-user"></i> ${article.author_name}</span>
                    </div>
                </div>
                <div class="news-actions">
                    <button class="btn-edit" data-id="${article.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" data-id="${article.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Update matches list
    function updateMatchesList(matches) {
        const matchesList = document.querySelector('.matches-list');
        if (!matchesList) return;

        matchesList.innerHTML = matches.map(match => `
            <div class="match-item">
                <div class="match-teams">
                    <span>${match.home_team_name}</span>
                    <span class="vs">vs</span>
                    <span>${match.away_team_name}</span>
                </div>
                <div class="match-info">
                    <span class="match-date">${new Date(match.match_date).toLocaleDateString()}</span>
                    <span class="match-score">${match.home_score !== null ? `${match.home_score} - ${match.away_score}` : 'TBD'}</span>
                    <span class="match-status">${match.status}</span>
                </div>
                <div class="match-actions">
                    <button class="btn-edit" data-id="${match.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" data-id="${match.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Update teams list
    function updateTeamsList(teams) {
        const teamsList = document.querySelector('.teams-list');
        if (!teamsList) return;

        teamsList.innerHTML = teams.map(team => `
            <div class="team-item">
                <div class="team-info">
                    <h3>${team.name}</h3>
                    <p>${team.category} - ${team.division}</p>
                    <p>Coach: ${team.coach}</p>
                </div>
                <div class="team-actions">
                    <button class="btn-edit" data-id="${team.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" data-id="${team.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    // Update settings form
    function updateSettingsForm(settings) {
        Object.entries(settings).forEach(([key, value]) => {
            const input = document.querySelector(`input[name="${key}"]`);
            if (input) {
                input.value = value;
            }
        });
    }

    // Navigation handling
    const navLinks = document.querySelectorAll('.admin-nav a');
    const sections = document.querySelectorAll('.admin-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            
            // Update active state
            navLinks.forEach(a => a.classList.remove('active'));
            this.classList.add('active');
            
            // Show selected section
            sections.forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // Modal handling
    const modals = document.querySelectorAll('.modal');
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const closeButtons = document.querySelectorAll('.close-modal');

    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            document.getElementById(modalId).style.display = 'block';
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // News form validation
    const newsForm = document.querySelector('#addNewsModal form');
    if (newsForm) {
        newsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateForm(this, formRules.news)) return;
            
            // ... existing news submission code ...
        });
    }

    // Match form validation
    const matchForm = document.querySelector('#addMatchModal form');
    if (matchForm) {
        matchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateForm(this, formRules.match)) return;
            
            // ... existing match submission code ...
        });
    }

    // Team form validation
    const teamForm = document.querySelector('#addTeamModal form');
    if (teamForm) {
        teamForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateForm(this, formRules.team)) return;
            
            // ... existing team submission code ...
        });
    }

    // Settings form validation
    const settingsForms = document.querySelectorAll('.settings-form');
    settingsForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!validateForm(this, formRules.settings)) return;
            
            // ... existing settings submission code ...
        });
    });

    // Add real-time validation
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('blur', function() {
            const form = this.closest('form');
            if (!form) return;

            let rules;
            if (form.id === 'login') rules = formRules.login;
            else if (form.closest('#addNewsModal')) rules = formRules.news;
            else if (form.closest('#addMatchModal')) rules = formRules.match;
            else if (form.closest('#addTeamModal')) rules = formRules.team;
            else if (form.closest('.settings-form')) rules = formRules.settings;
            else return;

            if (rules[this.name]) {
                validateForm(form, { [this.name]: rules[this.name] });
            }
        });
    });
});

// Add error message styles
const style = document.createElement('style');
style.textContent = `
    .error-message {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    input.error,
    select.error,
    textarea.error {
        border-color: #dc3545;
    }

    input.error:focus,
    select.error:focus,
    textarea.error:focus {
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
`;
document.head.appendChild(style);
