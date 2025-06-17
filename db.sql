CREATE DATABASE IF NOT EXISTS sportsclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sportsclub;

DROP TABLE IF EXISTS contact_requests;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS login_attempts;

CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    division VARCHAR(50),
    coach VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition VARCHAR(100) NOT NULL,
    team1 VARCHAR(100) NOT NULL,
    team2 VARCHAR(100) NOT NULL,
    score1 INT NOT NULL,
    score2 INT NOT NULL,
    date DATE NOT NULL,
    time TIME,
    location VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME,
    location VARCHAR(200),
    type VARCHAR(50) DEFAULT 'event',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(300),
    image_url VARCHAR(500),
    category VARCHAR(50) DEFAULT 'general',
    author VARCHAR(100) DEFAULT 'Redactie',
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('admin', 'manager', 'editor') DEFAULT 'editor',
    active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    action ENUM('login', 'logout', 'failed') DEFAULT 'login',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

INSERT INTO teams (name, category, division, coach) VALUES
('AFC 1', 'Senioren', 'Hoofdklasse', 'Jan Jansen'),
('AFC 2', 'Senioren', 'Eerste Klasse', 'Piet Pietersen'),
('AFC A1', 'Junioren', 'A-Klasse', 'Klaas Klaassen'),
('AFC B1', 'Junioren', 'B-Klasse', 'Henk Hendriks'),
('AFC C1', 'Junioren', 'C-Klasse', 'Gerard Gerardsen');

INSERT INTO results (competition, team1, team2, score1, score2, date, time, location) VALUES
('Hoofdklasse', 'AFC 1', 'Rivalen FC', 3, 1, '2024-05-15', '15:00:00', 'Sportpark AFC'),
('Eerste Klasse', 'AFC 2', 'Stad FC', 2, 2, '2024-05-12', '14:30:00', 'Sportpark AFC'),
('A-Klasse', 'AFC A1', 'Jeugd FC', 4, 0, '2024-05-10', '16:00:00', 'Sportpark AFC'),
('Hoofdklasse', 'AFC 1', 'Kampioen FC', 1, 0, '2024-05-08', '20:00:00', 'Sportpark AFC'),
('B-Klasse', 'AFC B1', 'Junioren FC', 2, 1, '2024-05-05', '15:30:00', 'Sportpark AFC');

INSERT INTO events (title, description, date, time, location, type) VALUES
('Jaarlijkse Barbecue', 'De traditionele jaarlijkse barbecue voor alle leden en hun families. Live muziek, spelletjes en natuurlijk lekker eten!', '2025-06-15', '16:00:00', 'Sportpark AFC', 'social'),
('Sponsorloop voor Jeugd', 'Sponsorloop georganiseerd door de jeugdafdeling. Alle opbrengsten gaan naar nieuwe trainingsmaterialen.', '2025-06-22', '10:00:00', 'Sportpark AFC', 'fundraiser'),
('Zomer Toernooi', 'Het jaarlijkse zomertoernooi voor alle leeftijdscategorieën. Kom kijken naar spannende wedstrijden!', '2025-06-28', '09:00:00', 'Sportpark AFC', 'tournament');

INSERT INTO news (title, content, excerpt, category, author) VALUES
('Kampioenschap voor AFC 1!', 'Na een zinderend seizoen is AFC 1 kampioen geworden in de hoofdklasse. De ontlading was groot na het laatste fluitsignaal. Het team heeft het hele seizoen door gewerkt aan dit doel en de overwinning is meer dan verdiend.', 'Na een zinderend seizoen is AFC 1 kampioen geworden in de hoofdklasse.', 'team', 'Redactie'),
('Jeugdtoernooi AFC groot succes', 'Meer dan 200 kinderen deden mee aan het jaarlijkse jeugdtoernooi. Het was een dag vol sportiviteit en plezier. De organisatie kijkt terug op een zeer geslaagde dag.', 'Meer dan 200 kinderen deden mee aan het jaarlijkse jeugdtoernooi.', 'jeugd', 'Redactie'),
('Nieuwe kantine AFC geopend', 'De vernieuwde kantine is feestelijk geopend! Kom snel langs voor een hapje en drankje na de wedstrijd. De nieuwe kantine biedt meer ruimte en een modern interieur.', 'De vernieuwde kantine is feestelijk geopend!', 'faciliteiten', 'Redactie');

INSERT INTO admin_users (username, password, email, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@afc.nl', 'Admin', 'User', 'admin'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@afc.nl', 'Manager', 'User', 'manager'),
('editor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'editor@afc.nl', 'Editor', 'User', 'editor');

CREATE INDEX idx_results_date ON results(date);
CREATE INDEX idx_events_date ON events(date);
CREATE INDEX idx_contact_status ON contact_requests(status);
CREATE INDEX idx_news_published ON news(published_at);
CREATE INDEX idx_news_category ON news(category);
CREATE INDEX idx_login_attempts_ip ON login_attempts(ip_address);
CREATE INDEX idx_login_attempts_created ON login_attempts(created_at);
CREATE INDEX idx_login_attempts_success ON login_attempts(success);
