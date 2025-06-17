<?php
// Test database verbinding
require_once '../config/database.php';

echo "<h1>Database Test</h1>";

try {
    $conn = getDB();
    echo "<p>✅ Database verbinding succesvol</p>";

    $stmt = $conn->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "<p>📊 Huidige database: " . $result['db_name'] . "</p>";

    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>📊 Aantal tabellen: " . count($tables) . "</p>";

    if (count($tables) > 0) {
        echo "<h3>Bestaande tabellen:</h3><ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }

    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "<p>✅ Eenvoudige query werkt</p>";
    } else {
        echo "<p>❌ Eenvoudige query werkt niet</p>";
    }

} catch (Exception $e) {
    echo "<p>❌ Database fout: " . $e->getMessage() . "</p>";
}

echo "<h3>Configuratie:</h3>";
echo "<p>Host: " . DB_HOST . "</p>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>Gebruiker: " . DB_USER . "</p>";
?>
