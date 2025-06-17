<?php
// Database configuratie instellingen
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'afc_website');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Foutrapportage voor ontwikkeling (verwijderen in productie)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connectie singleton klasse
class Database {
    private static $instance = null;
    private $conn = null;
    private $inTransaction = false;

    private function __construct() {
        try {
            // Maak DSN (Data Source Name) string
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            // PDO opties instellen
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE
            ];

            // Maak PDO connectie
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database verbinding mislukt: " . $e->getMessage());
            throw new Exception("Database verbinding mislukt. Probeer het later opnieuw.");
        }
    }

    // Singleton pattern - krijg database instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Krijg database connectie
    public function getConnection() {
        return $this->conn;
    }

    // Start database transactie
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->conn->beginTransaction();
            $this->inTransaction = true;
        }
    }

    // Commit database transactie
    public function commit() {
        if ($this->inTransaction) {
            $this->conn->commit();
            $this->inTransaction = false;
        }
    }

    // Rollback database transactie
    public function rollback() {
        if ($this->inTransaction) {
            $this->conn->rollback();
            $this->inTransaction = false;
        }
    }

    // Controleer of er een actieve transactie is
    public function inTransaction() {
        return $this->inTransaction;
    }
}

// Helper functie om database connectie te krijgen
function getDB() {
    return Database::getInstance()->getConnection();
}

// Helper functie om query uit te voeren
function query($sql, $params = []) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query mislukt: " . $e->getMessage());
        throw new Exception("Database query mislukt");
    }
}

// Helper functie om data in te voegen
function insert($table, $data) {
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $conn = getDB();
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        return $conn->lastInsertId();
    } catch(PDOException $e) {
        error_log("Invoegen mislukt: " . $e->getMessage());
        throw new Exception("Gegevens invoegen mislukt");
    }
}

// Helper functie om data bij te werken
function update($table, $data, $where, $whereParams = []) {
    try {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        return query($sql, $params)->rowCount();
    } catch(PDOException $e) {
        error_log("Bijwerken mislukt: " . $e->getMessage());
        throw new Exception("Gegevens bijwerken mislukt");
    }
}

// Helper functie om data te verwijderen
function delete($table, $where, $params = []) {
    try {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return query($sql, $params)->rowCount();
    } catch(PDOException $e) {
        error_log("Verwijderen mislukt: " . $e->getMessage());
        throw new Exception("Gegevens verwijderen mislukt");
    }
}

// Transactie helper functies
function beginTransaction() {
    return Database::getInstance()->beginTransaction();
}

function commit() {
    return Database::getInstance()->commit();
}

function rollback() {
    return Database::getInstance()->rollback();
}

// Controleer of database connectie actief is
function isConnected() {
    try {
        getDB()->query('SELECT 1');
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Initialiseer database tabellen
function initializeDatabase() {
    try {
        $conn = getDB();
        if (!$conn) {
            throw new Exception("Kon geen database verbinding tot stand brengen");
        }

        // Start transactie
        Database::getInstance()->beginTransaction();

        // Maak users tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'editor', 'user') DEFAULT 'user',
            is_active BOOLEAN DEFAULT TRUE,
            login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak teams tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            division VARCHAR(50),
            coach VARCHAR(100),
            description TEXT,
            logo_url VARCHAR(255),
            home_field VARCHAR(255),
            training_days VARCHAR(255),
            training_time VARCHAR(100),
            season VARCHAR(50),
            status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak matches tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            home_team_id INT,
            away_team_id INT,
            home_team_name VARCHAR(255) NOT NULL,
            away_team_name VARCHAR(255) NOT NULL,
            match_date DATE NOT NULL,
            match_time TIME,
            venue VARCHAR(255),
            home_score INT DEFAULT NULL,
            away_score INT DEFAULT NULL,
            status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
            competition VARCHAR(100),
            season VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            INDEX idx_match_date (match_date),
            INDEX idx_status (status),
            INDEX idx_competition (competition)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak news tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            excerpt VARCHAR(500),
            image_url VARCHAR(255),
            author_id INT,
            category VARCHAR(100) DEFAULT 'general',
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            featured BOOLEAN DEFAULT FALSE,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            published_at TIMESTAMP NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak events tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            event_time TIME,
            location VARCHAR(255),
            event_type VARCHAR(100) DEFAULT 'general',
            max_participants INT,
            current_participants INT DEFAULT 0,
            price DECIMAL(10,2) DEFAULT 0.00,
            organizer VARCHAR(255),
            contact_email VARCHAR(255),
            contact_phone VARCHAR(50),
            status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_event_date (event_date),
            INDEX idx_status (status),
            INDEX idx_event_type (event_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak settings tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
            is_public BOOLEAN DEFAULT FALSE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key),
            INDEX idx_is_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Maak activity_log tabel
        $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Voeg standaard admin gebruiker toe als deze nog niet bestaat
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $hashedPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, email, role) 
                VALUES (?, ?, ?, 'admin')
            ");
            $stmt->execute(['admin', $hashedPassword, 'admin@afc.nl']);
        }

        // Voeg standaard instellingen toe als deze nog niet bestaan
        $defaultSettings = [
            ['website_name', 'Amsterdamsche FC', 'string', true],
            ['contact_email', 'info@afc.nl', 'string', true],
            ['phone_number', '020-1234567', 'string', true],
            ['facebook_url', 'https://facebook.com/afc', 'string', true],
            ['twitter_url', 'https://twitter.com/afc', 'string', true],
            ['instagram_url', 'https://instagram.com/afc', 'string', true],
            ['maintenance_mode', 'false', 'boolean', false],
            ['registration_enabled', 'true', 'boolean', false],
            ['default_user_role', 'user', 'string', false],
            ['news_items_per_page', '10', 'integer', false],
            ['matches_items_per_page', '10', 'integer', false],
            ['teams_items_per_page', '12', 'integer', false]
        ];

        $stmt = $conn->prepare("
            INSERT IGNORE INTO settings 
            (setting_key, setting_value, setting_type, is_public) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }

        // Commit transactie
        Database::getInstance()->commit();
        return true;

    } catch(Exception $e) {
        // Rollback transactie bij fout
        Database::getInstance()->rollback();
        error_log("Database initialisatie mislukt: " . $e->getMessage());
        return false;
    }
}

// Helper functies voor veelvoorkomende database operaties
function getUserByUsername($username) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT id, username, password, email, role, 
                   login_attempts, locked_until, last_login
            FROM users 
            WHERE username = ? 
            LIMIT 1
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Fout bij ophalen gebruiker: " . $e->getMessage());
        return null;
    }
}

// Verifieer wachtwoord
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

// Haal nieuwsartikelen op
function getNewsArticles($limit = 10, $offset = 0, $category = null) {
    try {
        $conn = getDB();
        $sql = "SELECT n.*, u.username as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id";
        $params = [];
        
        if ($category) {
            $sql .= " WHERE n.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Fout bij ophalen nieuws: " . $e->getMessage());
        return [];
    }
}

// Haal teams op
function getTeams($limit = 12, $offset = 0, $category = null, $status = 'active') {
    try {
        $conn = getDB();
        $sql = "SELECT * FROM teams WHERE status = ?";
        $params = [$status];
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY category, name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Fout bij ophalen teams: " . $e->getMessage());
        return [];
    }
}

// Haal wedstrijden op
function getMatches($limit = 10, $offset = 0, $status = null, $season = null) {
    try {
        $conn = getDB();
        $sql = "SELECT * FROM matches";
        $params = [];
        $conditions = [];
        
        if ($status) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if ($season) {
            $conditions[] = "season = ?";
            $params[] = $season;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY match_date DESC, match_time DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Fout bij ophalen wedstrijden: " . $e->getMessage());
        return [];
    }
}

// Haal evenementen op
function getEvents($limit = 10, $offset = 0, $status = 'upcoming') {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT * FROM events 
            WHERE status = ? 
            ORDER BY event_date ASC, event_time ASC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$status, $limit, $offset]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Fout bij ophalen evenementen: " . $e->getMessage());
        return [];
    }
}

// Haal instelling op
function getSetting($key, $default = null) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if ($result) {
            switch ($result['setting_type']) {
                case 'boolean':
                    return filter_var($result['setting_value'], FILTER_VALIDATE_BOOLEAN);
                case 'integer':
                    return (int)$result['setting_value'];
                case 'json':
                    return json_decode($result['setting_value'], true);
                default:
                    return $result['setting_value'];
            }
        }
        
        return $default;
    } catch(PDOException $e) {
        error_log("Fout bij ophalen instelling: " . $e->getMessage());
        return $default;
    }
}

// Update instelling
function updateSetting($key, $value) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        return $stmt->execute([$value, $key]);
    } catch(PDOException $e) {
        error_log("Fout bij bijwerken instelling: " . $e->getMessage());
        return false;
    }
}

// Update gebruikers login pogingen
function updateUserLoginAttempts($userId, $success = true) {
    try {
        $conn = getDB();
        if ($success) {
            $stmt = $conn->prepare("
                UPDATE users 
                SET login_attempts = 0, 
                    locked_until = NULL, 
                    last_login = NOW() 
                WHERE id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET login_attempts = login_attempts + 1,
                    locked_until = CASE 
                        WHEN login_attempts + 1 >= 5 
                        THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                        ELSE NULL 
                    END
                WHERE id = ?
            ");
        }
        return $stmt->execute([$userId]);
    } catch(PDOException $e) {
        error_log("Fout bij bijwerken login pogingen: " . $e->getMessage());
        return false;
    }
}

// Log gebruikers activiteit
function logActivity($userId, $action, $details = null) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId, 
            $action, 
            $details, 
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch(PDOException $e) {
        error_log("Fout bij loggen activiteit: " . $e->getMessage());
        return false;
    }
}

// Controleer of gebruiker vergrendeld is
function isUserLocked($userId) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT locked_until 
            FROM users 
            WHERE id = ? AND locked_until IS NOT NULL AND locked_until > NOW()
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() !== false;
    } catch(PDOException $e) {
        error_log("Fout bij controleren gebruiker vergrendeling: " . $e->getMessage());
        return false;
    }
}
?> 