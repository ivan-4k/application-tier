<?php
require_once '../app/config/Database.php';

use App\Config\Database;

echo "Testing Database Connection...\n";

try {
    $connection = Database::getConnection();
    
    if ($connection->ping()) {
        echo "✅ Database connection successful!\n";
        
        // Test query
        $result = $connection->query("SELECT VERSION() as version");
        $row = $result->fetch_assoc();
        echo "MySQL Version: " . $row['version'] . "\n";
        
        // Check tables
        $result = $connection->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "Tables found: " . implode(', ', $tables) . "\n";
        
    } else {
        echo "❌ Database connection failed!\n";
    }
    
    Database::closeConnection();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>