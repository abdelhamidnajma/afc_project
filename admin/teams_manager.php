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

// Maak de teams-tabel aan als deze nog niet bestaat
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        age_group VARCHAR(50),
        coach_name VARCHAR(255),
        coach_email VARCHAR(255),
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Tabel bestaat mogelijk al
}

// Verwerk formulier indien verzonden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $age_group = trim($_POST['age_group']);
        $coach_name = trim($_POST['coach_name']);
        $coach_email = trim($_POST['coach_email']);
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        
        // Controleer verplichte velden
        if (empty($name) || empty($category)) {
            $error = 'Team naam en categorie zijn verplicht';
        } else {
            try {
                // Voeg team toe aan database
                $stmt = $conn->prepare("
                    INSERT INTO teams (name, category, age_group, coach_name, coach_email, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $category, $age_group, $coach_name, $coach_email, $description, $status]);
                $message = 'Team succesvol toegevoegd';
            } catch (Exception $e) {
                $error = 'Fout bij toevoegen team: ' . $e->getMessage();
            }
        }
    }
}

// Haal alle teams op
$teams = [];
try {
    $stmt = $conn->query("SELECT * FROM teams ORDER BY category, name");
    $teams = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Fout bij ophalen teams: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Beheer - AFC Admin</title>
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
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

        .teams-table {
            background: #ffffff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .teams-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .teams-table th,
        .teams-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .teams-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .teams-table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background: #fef3c7;
            color: #92400e;
        }

        .add-form {
            background: #ffffff;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .teams-table {
                overflow-x: auto;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">
            <img src="../fotos/afc-logo.svg" alt="AFC Logo">
            <h1>Teams Beheer</h1>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../index.html"><i class="fas fa-home"></i> Website</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
        </nav>
    </header>

    <main class="admin-content">
        <?php if ($message): ?>
            <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2 class="page-title">Teams Beheer</h2>
        </div>

        <div class="add-form">
            <h3>Nieuw Team Toevoegen</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Team Naam</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Categorie</label>
                        <select id="category" name="category" required>
                            <option value="">Selecteer categorie</option>
                            <option value="senioren">Senioren</option>
                            <option value="junioren">Junioren</option>
                            <option value="amateurs">Amateurs</option>
                            <option value="vrouwen">Vrouwen</option>
                            <option value="meisjes">Meisjes</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="age_group">Leeftijdsgroep</label>
                        <input type="text" id="age_group" name="age_group" placeholder="bijv. U18, Senioren, etc.">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active">Actief</option>
                            <option value="inactive">Inactief</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="coach_name">Coach Naam</label>
                        <input type="text" id="coach_name" name="coach_name">
                    </div>
                    <div class="form-group">
                        <label for="coach_email">Coach E-mail</label>
                        <input type="email" id="coach_email" name="coach_email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Beschrijving</label>
                    <textarea id="description" name="description" placeholder="Beschrijving van het team..."></textarea>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-plus"></i>
                    Team Toevoegen
                </button>
            </form>
        </div>

        <div class="teams-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Team Naam</th>
                        <th>Categorie</th>
                        <th>Leeftijdsgroep</th>
                        <th>Coach</th>
                        <th>Status</th>
                        <th>Aangemaakt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?php echo $team['id']; ?></td>
                        <td><?php echo htmlspecialchars($team['name']); ?></td>
                        <td><?php echo ucfirst($team['category']); ?></td>
                        <td><?php echo htmlspecialchars($team['age_group'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($team['coach_name'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $team['status']; ?>">
                                <?php echo ucfirst($team['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($team['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html> 