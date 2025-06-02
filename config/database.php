<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'afc_website');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Error reporting for development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection singleton
class Database {
    private static $instance = null;
    private $conn = null;
    private $inTransaction = false;

    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATE
            ];

            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->conn->beginTransaction();
            $this->inTransaction = true;
        }
    }

    public function commit() {
        if ($this->inTransaction) {
            $this->conn->commit();
            $this->inTransaction = false;
        }
    }

    public function rollback() {
        if ($this->inTransaction) {
            $this->conn->rollBack();
            $this->inTransaction = false;
        }
    }

    public function __destruct() {
        if ($this->inTransaction) {
            $this->rollback();
        }
    }
}

// Get database connection
function getDB() {
    try {
        return Database::getInstance()->getConnection();
    } catch(Exception $e) {
        error_log("Failed to get database connection: " . $e->getMessage());
        return null;
    }
}

// Helper functions for common database operations
function query($sql, $params = []) {
    try {
        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
}

function fetch($sql, $params = []) {
    return query($sql, $params)->fetch();
}

function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

function insert($table, $data) {
    try {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        
        query($sql, array_values($data));
        return getDB()->lastInsertId();
    } catch(PDOException $e) {
        error_log("Insert failed: " . $e->getMessage());
        throw new Exception("Failed to save data");
    }
}

function update($table, $data, $where, $whereParams = []) {
    try {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        return query($sql, $params)->rowCount();
    } catch(PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        throw new Exception("Failed to update data");
    }
}

function delete($table, $where, $params = []) {
    try {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return query($sql, $params)->rowCount();
    } catch(PDOException $e) {
        error_log("Delete failed: " . $e->getMessage());
        throw new Exception("Failed to delete data");
    }
}

// Transaction helpers
function beginTransaction() {
    return Database::getInstance()->beginTransaction();
}

function commit() {
    return Database::getInstance()->commit();
}

function rollback() {
    return Database::getInstance()->rollback();
}

// Connection check
function isConnected() {
    try {
        getDB()->query('SELECT 1');
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Initialize database tables
function initializeDatabase() {
    try {
        $conn = getDB();
        if (!$conn) {
            throw new Exception("Could not establish database connection");
        }

        // Start transaction
        Database::getInstance()->beginTransaction();

        // Users table with improved security
        $conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
            last_login DATETIME,
            login_attempts INT DEFAULT 0,
            locked_until DATETIME,
            reset_token VARCHAR(100),
            reset_token_expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // News table with improved indexing
        $conn->exec("CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            category ENUM('team', 'club', 'events') NOT NULL,
            image_url VARCHAR(255),
            author_id INT,
            status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
            published_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_category (category),
            INDEX idx_status (status),
            INDEX idx_published_at (published_at),
            FULLTEXT INDEX idx_content (title, content)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // Teams table with improved structure
        $conn->exec("CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            category ENUM('senior', 'junior', 'women') NOT NULL,
            division VARCHAR(50),
            coach VARCHAR(100),
            assistant_coach VARCHAR(100),
            description TEXT,
            logo_url VARCHAR(255),
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_category (category),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // Matches table with improved structure
        $conn->exec("CREATE TABLE IF NOT EXISTS matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            home_team_id INT NOT NULL,
            away_team_id INT NOT NULL,
            match_date DATETIME NOT NULL,
            competition VARCHAR(100),
            venue VARCHAR(100),
            home_score INT,
            away_score INT,
            status ENUM('scheduled', 'live', 'completed', 'cancelled', 'postponed') NOT NULL DEFAULT 'scheduled',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
            FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
            INDEX idx_match_date (match_date),
            INDEX idx_status (status),
            INDEX idx_competition (competition)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // Settings table with improved structure
        $conn->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'integer', 'boolean', 'json', 'array') NOT NULL DEFAULT 'string',
            is_public BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key),
            INDEX idx_is_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // Activity log table for audit trail
        $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE);

        // Insert default admin user if not exists
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

        // Insert default settings if not exists
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

        // Commit transaction
        Database::getInstance()->commit();
        return true;

    } catch(Exception $e) {
        // Rollback transaction on error
        Database::getInstance()->rollback();
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Helper functions for common database operations
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
        error_log("Error fetching user: " . $e->getMessage());
        return null;
    }
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

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
        error_log("Error fetching news: " . $e->getMessage());
        return [];
    }
}

function getTeams($category = null) {
    try {
        $conn = getDB();
        $sql = "SELECT * FROM teams";
        $params = [];
        
        if ($category) {
            $sql .= " WHERE category = ?";
            $params[] = $category;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching teams: " . $e->getMessage());
        return [];
    }
}

function getMatches($status = null, $limit = 10) {
    try {
        $conn = getDB();
        $sql = "SELECT m.*, 
                ht.name as home_team_name, 
                at.name as away_team_name 
                FROM matches m 
                JOIN teams ht ON m.home_team_id = ht.id 
                JOIN teams at ON m.away_team_id = at.id";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE m.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY m.match_date DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching matches: " . $e->getMessage());
        return [];
    }
}

function getSetting($key) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : null;
    } catch(PDOException $e) {
        error_log("Error fetching setting: " . $e->getMessage());
        return null;
    }
}

function updateSetting($key, $value) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        return $stmt->execute([$value, $key]);
    } catch(PDOException $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

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
        error_log("Error updating login attempts: " . $e->getMessage());
        return false;
    }
}

function logActivity($userId, $action, $entityType, $entityId = null, $details = null) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            INSERT INTO activity_log 
            (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch(PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// Add new helper functions for improved functionality
function searchNews($query, $limit = 10, $offset = 0) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT n.*, u.username as author_name 
            FROM news n 
            LEFT JOIN users u ON n.author_id = u.id
            WHERE MATCH(n.title, n.content) AGAINST(? IN BOOLEAN MODE)
            ORDER BY n.published_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$query, $limit, $offset]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error searching news: " . $e->getMessage());
        return [];
    }
}

function getUpcomingMatches($limit = 5) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT m.*, 
                   ht.name as home_team_name, 
                   at.name as away_team_name,
                   ht.logo_url as home_team_logo,
                   at.logo_url as away_team_logo
            FROM matches m 
            JOIN teams ht ON m.home_team_id = ht.id 
            JOIN teams at ON m.away_team_id = at.id
            WHERE m.match_date > NOW() 
            AND m.status = 'scheduled'
            ORDER BY m.match_date ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching upcoming matches: " . $e->getMessage());
        return [];
    }
}

function getTeamStats($teamId) {
    try {
        $conn = getDB();
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_matches,
                SUM(CASE WHEN home_team_id = ? AND home_score > away_score THEN 1 
                         WHEN away_team_id = ? AND away_score > home_score THEN 1 
                         ELSE 0 END) as wins,
                SUM(CASE WHEN (home_team_id = ? OR away_team_id = ?) 
                         AND home_score = away_score THEN 1 
                         ELSE 0 END) as draws,
                SUM(CASE WHEN home_team_id = ? AND home_score < away_score THEN 1 
                         WHEN away_team_id = ? AND away_score < home_score THEN 1 
                         ELSE 0 END) as losses
            FROM matches 
            WHERE (home_team_id = ? OR away_team_id = ?)
            AND status = 'completed'
        ");
        $stmt->execute([$teamId, $teamId, $teamId, $teamId, $teamId, $teamId, $teamId, $teamId]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Error fetching team stats: " . $e->getMessage());
        return null;
    }
}
?> 