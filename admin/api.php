<?php
require_once '../config/database.php';
require_once 'auth.php';
require_once 'validation.php';

// Ensure user is authenticated
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

try {
    switch ($action) {
        case 'get_news':
            $limit = isset($_GET['limit']) ? sanitizeInt($_GET['limit']) : 10;
            $offset = isset($_GET['offset']) ? sanitizeInt($_GET['offset']) : 0;
            $category = isset($_GET['category']) ? sanitizeString($_GET['category']) : null;
            
            $news = getNewsArticles($limit, $offset, $category);
            echo json_encode(['success' => true, 'news' => $news]);
            break;

        case 'add_news':
            requireRole(['admin', 'editor']);
            
            // Sanitize input
            $title = sanitizeString($_POST['title']);
            $content = sanitizeString($_POST['content']);
            $category = sanitizeString($_POST['category'] ?? 'club');
            $status = sanitizeString($_POST['status'] ?? 'draft');
            
            // Validate input
            validateNewsInput([
                'title' => $title,
                'content' => $content,
                'category' => $category,
                'status' => $status
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO news (title, content, category, author_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $content,
                $category,
                $_SESSION['user_id'],
                $status
            ]);

            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
            break;

        case 'update_news':
            requireRole(['admin', 'editor']);
            
            // Sanitize input
            $id = sanitizeInt($_POST['id']);
            $title = sanitizeString($_POST['title']);
            $content = sanitizeString($_POST['content']);
            $category = sanitizeString($_POST['category'] ?? 'club');
            $status = sanitizeString($_POST['status'] ?? 'draft');
            
            // Validate input
            validateNewsInput([
                'title' => $title,
                'content' => $content,
                'category' => $category,
                'status' => $status
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, category = ?, status = ? WHERE id = ?");
            $stmt->execute([
                $title,
                $content,
                $category,
                $status,
                $id
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_news':
            requireRole(['admin']);
            
            $id = sanitizeInt($_POST['id']);
            if (!$id) {
                throw new Exception('Missing news ID');
            }

            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;

        case 'get_matches':
            $limit = isset($_GET['limit']) ? sanitizeInt($_GET['limit']) : 10;
            $status = isset($_GET['status']) ? sanitizeString($_GET['status']) : null;
            
            $matches = getMatches($status, $limit);
            echo json_encode(['success' => true, 'matches' => $matches]);
            break;

        case 'add_match':
            requireRole(['admin', 'editor']);
            
            // Sanitize input
            $homeTeamId = sanitizeInt($_POST['home_team_id']);
            $awayTeamId = sanitizeInt($_POST['away_team_id']);
            $matchDate = sanitizeString($_POST['match_date']);
            $competition = sanitizeString($_POST['competition'] ?? null);
            $status = sanitizeString($_POST['status'] ?? 'scheduled');
            
            // Validate input
            validateMatchInput([
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'match_date' => $matchDate,
                'status' => $status
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO matches (home_team_id, away_team_id, match_date, competition, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $homeTeamId,
                $awayTeamId,
                $matchDate,
                $competition,
                $status
            ]);

            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
            break;

        case 'update_match':
            requireRole(['admin', 'editor']);
            
            // Sanitize input
            $id = sanitizeInt($_POST['id']);
            $homeScore = isset($_POST['home_score']) ? sanitizeInt($_POST['home_score']) : null;
            $awayScore = isset($_POST['away_score']) ? sanitizeInt($_POST['away_score']) : null;
            $status = sanitizeString($_POST['status'] ?? 'scheduled');
            
            // Validate input
            validateMatchInput([
                'id' => $id,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'status' => $status
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE matches SET home_score = ?, away_score = ?, status = ? WHERE id = ?");
            $stmt->execute([
                $homeScore,
                $awayScore,
                $status,
                $id
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_match':
            requireRole(['admin']);
            
            $id = sanitizeInt($_POST['id']);
            if (!$id) {
                throw new Exception('Missing match ID');
            }

            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;

        case 'get_teams':
            $category = isset($_GET['category']) ? sanitizeString($_GET['category']) : null;
            
            $teams = getTeams($category);
            echo json_encode(['success' => true, 'teams' => $teams]);
            break;

        case 'add_team':
            requireRole(['admin']);
            
            // Sanitize input
            $name = sanitizeString($_POST['name']);
            $category = sanitizeString($_POST['category']);
            $division = sanitizeString($_POST['division'] ?? null);
            $coach = sanitizeString($_POST['coach'] ?? null);
            
            // Validate input
            validateTeamInput([
                'name' => $name,
                'category' => $category,
                'division' => $division,
                'coach' => $coach
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO teams (name, category, division, coach) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $name,
                $category,
                $division,
                $coach
            ]);

            echo json_encode(['success' => true, 'id' => $conn->lastInsertId()]);
            break;

        case 'update_team':
            requireRole(['admin']);
            
            // Sanitize input
            $id = sanitizeInt($_POST['id']);
            $name = sanitizeString($_POST['name']);
            $category = sanitizeString($_POST['category']);
            $division = sanitizeString($_POST['division'] ?? null);
            $coach = sanitizeString($_POST['coach'] ?? null);
            
            // Validate input
            validateTeamInput([
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'division' => $division,
                'coach' => $coach
            ]);

            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE teams SET name = ?, category = ?, division = ?, coach = ? WHERE id = ?");
            $stmt->execute([
                $name,
                $category,
                $division,
                $coach,
                $id
            ]);

            echo json_encode(['success' => true]);
            break;

        case 'delete_team':
            requireRole(['admin']);
            
            $id = sanitizeInt($_POST['id']);
            if (!$id) {
                throw new Exception('Missing team ID');
            }

            $conn = getDBConnection();
            $stmt = $conn->prepare("DELETE FROM teams WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;

        case 'get_settings':
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode(['success' => true, 'settings' => $settings]);
            break;

        case 'update_settings':
            requireRole(['admin']);
            
            if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
                throw new Exception('Invalid settings data');
            }

            // Sanitize and validate settings
            $settings = [];
            foreach ($_POST['settings'] as $key => $value) {
                $key = sanitizeString($key);
                $value = sanitizeString($value);
                $settings[$key] = $value;
            }

            validateSettingsInput($settings);

            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$value, $key]);
            }

            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 