<?php
// Define base path
define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));

// Require autoloader
require_once BASE_PATH . '/app/core/App.php';

// Start the application
use App\Core\App;
new App();
?>