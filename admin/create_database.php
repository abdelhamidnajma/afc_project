<?php
/*
    AFC Website - Database Aanmaken
    ===============================
    
    Dit script maakt de database en tabellen aan als deze niet bestaan.
    
    Gebruik: Ga naar admin/create_database.php in je browser
    Auteur: AFC Development Team
    Laatste update: 2024
*/

// Schakel foutrapportage in voor debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AFC Database Aanmaken</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

try {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'afc_website';
    
    // Verbinding maken zonder database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    echo "<p class='success'>✓ MySQL connectie succesvol</p>";
    
    // Controleer of database bestaat
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='info'>Database '$dbname' bestaat niet. Aanmaken...</p>";
        
        // Maak database aan
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p class='success'>✓ Database '$dbname' aangemaakt</p>";
    } else {
        echo "<p class='success'>✓ Database '$dbname' bestaat al</p>";
    }
    
    // Verbinding maken met de database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    echo "<p class='success'>✓ Verbinding met database succesvol</p>";
    
    // Controleer of users tabel bestaat
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p class='info'>Users tabel bestaat niet. Aanmaken...</p>";
        
        // Maak users tabel aan
        $sql = "CREATE TABLE users (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<p class='success'>✓ Users tabel aangemaakt</p>";
        
        // Maak admin gebruiker aan
        $adminUsername = 'admin';
        $adminEmail = 'admin@afc.nl';
        $adminPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$adminUsername, $adminEmail, $adminPassword]);
        
        echo "<p class='success'>✓ Admin gebruiker aangemaakt</p>";
        echo "<p class='info'>Gebruikersnaam: admin</p>";
        echo "<p class='info'>Wachtwoord: Admin123!</p>";
        
    } else {
        echo "<p class='success'>✓ Users tabel bestaat al</p>";
        
        // Controleer of er gebruikers zijn
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        
        if ($userCount == 0) {
            echo "<p class='info'>Geen gebruikers gevonden. Admin gebruiker aanmaken...</p>";
            
            $adminUsername = 'admin';
            $adminEmail = 'admin@afc.nl';
            $adminPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminUsername, $adminEmail, $adminPassword]);
            
            echo "<p class='success'>✓ Admin gebruiker aangemaakt</p>";
            echo "<p class='info'>Gebruikersnaam: admin</p>";
            echo "<p class='info'>Wachtwoord: Admin123!</p>";
        } else {
            echo "<p class='info'>Aantal gebruikers: {$userCount}</p>";
        }
    }
    
    // Maak andere benodigde tabellen aan
    $tables = [
        'activity_logs' => "CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'news_articles' => "CREATE TABLE news_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            author_id INT,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            published_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'teams' => "CREATE TABLE teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50),
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'events' => "CREATE TABLE events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            event_time TIME,
            location VARCHAR(255),
            status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $tableName => $sql) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($sql);
            echo "<p class='success'>✓ Tabel '$tableName' aangemaakt</p>";
        } else {
            echo "<p class='success'>✓ Tabel '$tableName' bestaat al</p>";
        }
    }
    
    echo "<h2>Database Setup Voltooid!</h2>";
    echo "<p class='success'>Alle tabellen zijn aangemaakt en klaar voor gebruik.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Fout: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='check_database_simple.php'>Controleer Database</a></p>";
echo "<p><a href='login.html'>Ga naar Login Pagina</a></p>";
echo "<p><a href='../index.html'>Ga naar Website</a></p>";
?> 