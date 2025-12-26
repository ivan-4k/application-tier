<?php
namespace App\Config;

use mysqli;

class Database {
    private static $connection = null;
    
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // INCLUDE Config.php DULU
                require_once __DIR__ . '/Config.php';
                
                if (!class_exists('App\Config\Config')) {
                    throw new \Exception("Config class not found!");
                }
                
                self::$connection = new mysqli(
                    Config::DB_HOST,
                    Config::DB_USER,
                    Config::DB_PASS,
                    Config::DB_NAME
                );
                
                if (self::$connection->connect_error) {
                    throw new \Exception("Database connection failed: " . self::$connection->connect_error);
                }
                
                self::$connection->set_charset("utf8mb4");
                
            } catch (\Exception $e) {
                die("❌ Database Error: " . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    public static function closeConnection() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}
?>