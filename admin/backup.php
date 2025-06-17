<?php
// Start de sessie voor authenticatie
session_start();
// Importeer database configuratie
require_once '../config/database.php';

// Controleer of de admin is ingelogd
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

// Maak database connectie
$conn = getDB();
$message = '';
$error = '';

// Verwerk backup creatie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    try {
        // Maak backup directory aan als deze nog niet bestaat
        $backupDir = __DIR__ . '/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Genereer timestamp voor unieke bestandsnaam
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'afc_backup_' . $timestamp . '.sql';
        
        // Haal alle database tabellen op
        $tables = [];
        $stmt = $conn->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        // Begin backup content met header informatie
        $backupContent = "-- AFC Website Backup\n";
        $backupContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Database: " . DB_NAME . "\n\n";
        
        // Loop door alle tabellen
        foreach ($tables as $table) {
            // Haal tabelstructuur op
            $stmt = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $backupContent .= $row[1] . ";\n\n";
            
            // Haal tabeldata op
            $stmt = $conn->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Voeg data toe als er rijen zijn
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $backupContent .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = "(" . implode(', ', $rowValues) . ")";
                }
                
                $backupContent .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // Schrijf backup naar bestand
        file_put_contents($backupFile, $backupContent);
        
        $message = 'Backup succesvol aangemaakt: ' . basename($backupFile);
        
    } catch (Exception $e) {
        $error = 'Fout bij maken backup: ' . $e->getMessage();
    }
}

// Haal bestaande backups op
$backups = [];
$backupDir = __DIR__ . '/backups/';
if (is_dir($backupDir)) {
    $files = glob($backupDir . '*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    // Sorteer op datum (nieuwste eerst)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - AFC Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="../fotos/afc-logo.svg">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #1f2937;
        }

        .admin-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-logo img {
            height: 40px;
            width: auto;
        }

        .admin-logo h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #dc2626;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-nav a {
            color: #6b7280;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .admin-nav a:hover {
            color: #dc2626;
            background: rgba(220, 38, 38, 0.1);
        }

        .admin-content {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 2rem 0;
        }

        .backup-card {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .backup-card h3 {
            color: #1f2937;
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn.success {
            background: #10b981;
        }

        .btn.success:hover {
            background: #059669;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .backup-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: #f8fafc;
        }

        .backup-info {
            flex: 1;
        }

        .backup-name {
            font-weight: 500;
            color: #1f2937;
            margin: 0 0 0.25rem 0;
        }

        .backup-details {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .admin-header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .admin-content {
                padding: 0 1rem;
            }

            .backup-item {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .backup-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">
            <img src="../fotos/afc-logo.svg" alt="AFC Logo">
            <h1>Backup</h1>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../index.html"><i class="fas fa-home"></i> Website</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
        </nav>
    </header>

    <main class="admin-content">
        <h2 class="page-title">Database Backup</h2>

        <?php if ($message): ?>
            <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="backup-card">
            <h3><i class="fas fa-download"></i> Nieuwe Backup Maken</h3>
            <p>Maak een volledige backup van de database. Dit kan enkele minuten duren.</p>
            <form method="POST" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="create_backup">
                <button type="submit" class="btn success">
                    <i class="fas fa-download"></i>
                    Backup Maken
                </button>
            </form>
        </div>

        <div class="backup-card">
            <h3><i class="fas fa-history"></i> Bestaande Backups</h3>
            <?php if (empty($backups)): ?>
                <p>Geen backups gevonden.</p>
            <?php else: ?>
                <ul class="backup-list">
                    <?php foreach ($backups as $backup): ?>
                    <li class="backup-item">
                        <div class="backup-info">
                            <p class="backup-name"><?php echo htmlspecialchars($backup['name']); ?></p>
                            <p class="backup-details">
                                Gemaakt: <?php echo date('d-m-Y H:i:s', $backup['date']); ?> | 
                                Grootte: <?php echo number_format($backup['size'] / 1024, 2); ?> KB
                            </p>
                        </div>
                        <div class="backup-actions">
                            <a href="backups/<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-small" download>
                                <i class="fas fa-download"></i>
                                Download
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 