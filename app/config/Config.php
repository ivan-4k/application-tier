<?php
namespace App\Config;

class Config {
    // Application
    const APP_NAME = 'Klinik Antrian API';
    const APP_VERSION = '1.0.0';
    const APP_ENV = 'development'; // development, production
    
    // Database
    const DB_HOST = 'localhost';
    const DB_NAME = 'klinik_db';
    const DB_USER = 'root';
    const DB_PASS = '';
    
    // API
    const API_PREFIX = '/api';
    
    // CORS
    const ALLOWED_ORIGINS = ['*'];
    const ALLOWED_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';
    const ALLOWED_HEADERS = 'Content-Type, Authorization';
    
    // Queue
    const QUEUE_NUMBER_FORMAT = 'ymd'; // Format: year-month-day + sequence
    const MAX_QUEUE_PER_DAY = 9999;
}
?>