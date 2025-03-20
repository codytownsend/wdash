<?php
// New bootstrap file
// Show all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Skip autoloader completely for debugging
// Include service classes directly
include_once __DIR__ . '/Auth/AuthService.php';
include_once __DIR__ . '/Services/WooCommerceService.php';
include_once __DIR__ . '/Services/DashboardService.php';

// Load configuration 
$config = include __DIR__ . '/../config/app.php';

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

// Initialize services
$authService = new \App\Auth\AuthService($config);
$wooCommerceService = new \App\Services\WooCommerceService($config);
$dashboardService = new \App\Services\DashboardService($config, $wooCommerceService);

// Utility functions
function sendJsonResponse($data, int $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirectWithError($url, $error) {
    $_SESSION['error_message'] = $error;
    header("Location: $url");
    exit;
}

function redirectWithSuccess($url, $message) {
    $_SESSION['success_message'] = $message;
    header("Location: $url");
    exit;
}

function getFlashMessages() {
    $messages = [
        'error' => $_SESSION['error_message'] ?? null,
        'success' => $_SESSION['success_message'] ?? null
    ];
    
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    
    return $messages;
}

function parseDate($dateStr, $timezone) {
    try {
        return new \DateTime($dateStr, $timezone);
    } catch (\Exception $e) {
        return null;
    }
}