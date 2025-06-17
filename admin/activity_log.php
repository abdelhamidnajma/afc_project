<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.html');
    exit;
}

$conn = getDB();
$activities = [];

try {
    $stmt = $conn->prepare("
        SELECT al.*, u.username 
        FROM activity_log al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Fout bij ophalen activiteit: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activiteit Log - AFC Admin</title>
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

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 2rem 0;
        }

        .activity-table {
            background: #ffffff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .activity-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th,
        .activity-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .activity-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }

        .activity-table tr:hover {
            background: #f8fafc;
        }

        .action-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .action-badge.login {
            background: #d1fae5;
            color: #065f46;
        }

        .action-badge.logout {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-badge.register {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-badge.login_failed {
            background: #fef3c7;
            color: #92400e;
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

            .activity-table {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">
            <img src="../fotos/afc-logo.svg" alt="AFC Logo">
            <h1>Activiteit Log</h1>
        </div>
        <nav class="admin-nav">
            <a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../index.html"><i class="fas fa-home"></i> Website</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
        </nav>
    </header>

    <main class="admin-content">
        <h2 class="page-title">Activiteit Log</h2>

        <div class="activity-table">
            <table>
                <thead>
                    <tr>
                        <th>Datum/Tijd</th>
                        <th>Gebruiker</th>
                        <th>Actie</th>
                        <th>IP Adres</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?php echo date('d-m-Y H:i:s', strtotime($activity['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($activity['username'] ?? 'Systeem'); ?></td>
                        <td>
                            <span class="action-badge <?php echo $activity['action']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            if ($activity['details']) {
                                $details = json_decode($activity['details'], true);
                                if (is_array($details)) {
                                    echo htmlspecialchars(implode(', ', array_map(function($key, $value) {
                                        return "$key: $value";
                                    }, array_keys($details), $details)));
                                } else {
                                    echo htmlspecialchars($activity['details']);
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html> 