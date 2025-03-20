<?php
// Bootstrap file for initializing application components

// Display all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("Composer autoloader not found. Run 'composer install' in the project root.");
}

// Load configuration
if (file_exists(__DIR__ . '/../config/app.php')) {
    $config = require_once __DIR__ . '/../config/app.php';
} else {
    die("Configuration file not found at " . __DIR__ . '/../config/app.php');
}

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

// Explicitly include service classes to avoid autoloading issues
require_once __DIR__ . '/Auth/AuthService.php';
require_once __DIR__ . '/Services/WooCommerceService.php';
require_once __DIR__ . '/Services/DashboardService.php';

// Initialize services with error handling
try {
    // Initialize auth service
    $authService = new \App\Auth\AuthService($config);
    
    // Initialize WooCommerce service
    $wooCommerceService = new \App\Services\WooCommerceService($config);
    
    // Initialize dashboard service
    $dashboardService = new \App\Services\DashboardService($config, $wooCommerceService);
} catch (\Throwable $e) {
    die("Error initializing services: " . $e->getMessage() . 
        "<br>in file: " . $e->getFile() . 
        "<br>at line: " . $e->getLine());
}

// Set error reporting based on environment (now that config is loaded)
if ($config['app']['env'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Utility function to send JSON response
function sendJsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Utility function to redirect with error
function redirectWithError(string $url, string $error): void {
    $_SESSION['error_message'] = $error;
    header("Location: $url");
    exit;
}

// Utility function to redirect with success
function redirectWithSuccess(string $url, string $message): void {
    $_SESSION['success_message'] = $message;
    header("Location: $url");
    exit;
}

// Function to get and clear flash messages
function getFlashMessages(): array {
    $messages = [
        'error' => $_SESSION['error_message'] ?? null,
        'success' => $_SESSION['success_message'] ?? null
    ];
    
    // Clear the messages
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    
    return $messages;
}

// Function to validate and parse date
function parseDate(string $dateStr, \DateTimeZone $timezone): ?\DateTime {
    try {
        return new \DateTime($dateStr, $timezone);
    } catch (\Exception $e) {
        return null;
    }
}