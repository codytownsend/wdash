<?php
/**
 * Unified Bootstrap file
 * Replace both bootstrap.php and bootstrap_new.php with this file
 */

// Display all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
if (file_exists(__DIR__ . '/../config/app.php')) {
    $config = require_once __DIR__ . '/../config/app.php';
} else {
    die("Configuration file not found at " . __DIR__ . '/../config/app.php');
}

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

// Include service classes directly (more reliable than autoloader)
require_once __DIR__ . '/Auth/AuthService.php';
require_once __DIR__ . '/Services/WooCommerceService.php';
require_once __DIR__ . '/Services/DashboardService.php';

// Initialize services with robust error handling
try {
    // Initialize auth service
    $authService = new \App\Auth\AuthService($config);
    
    // Initialize WooCommerce service with verbose error handling
    try {
        $wooCommerceService = new \App\Services\WooCommerceService($config);
    } catch (\Throwable $e) {
        error_log("WooCommerce Service Error: " . $e->getMessage());
        // Create a minimal version that returns empty data but doesn't crash
        $wooCommerceService = new class($config) extends \App\Services\WooCommerceService {
            public function __construct(array $config) {
                // Minimal constructor
                error_log("Using fallback WooCommerce service due to initialization error");
                $this->config = $config;
                $this->timezone = new \DateTimeZone($config['app']['timezone'] ?? 'UTC');
            }
            
            // Override methods to return empty data
            public function fetchOrders(string $storeId, \DateTime $startDate, \DateTime $endDate, array $additionalParams = []): array {
                return [];
            }
            
            public function getMetrics(\DateTime $startDate, \DateTime $endDate): array {
                $metrics = [];
                foreach ($this->config['stores'] as $storeId => $storeConfig) {
                    $metrics[$storeId] = [
                        'total_revenue' => 0,
                        'order_count' => 0,
                        'completed_orders' => 0,
                        'processing_orders' => 0,
                        'on_hold_orders' => 0,
                        'pending_orders' => 0,
                        'average_order_value' => 0,
                        'currency' => $this->config['stores'][$storeId]['currency'] ?? 'USD',
                        'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol'] ?? '$',
                        'error' => 'WooCommerce service initialization failed.'
                    ];
                }
                return $metrics;
            }
            
            public function getAttributionStats(\DateTime $startDate, \DateTime $endDate): array {
                $attribution = [];
                foreach ($this->config['stores'] as $storeId => $storeConfig) {
                    $attribution[$storeId] = [
                        'error' => 'WooCommerce service initialization failed.',
                        'currency' => $this->config['stores'][$storeId]['currency'] ?? 'USD',
                        'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol'] ?? '$'
                    ];
                }
                return $attribution;
            }
            
            public function getOrderDetails(string $storeId, \DateTime $startDate, \DateTime $endDate): array {
                return [
                    'orders' => [],
                    'currency' => $this->config['stores'][$storeId]['currency'] ?? 'USD',
                    'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol'] ?? '$',
                    'error' => 'WooCommerce service initialization failed.'
                ];
            }
        };
    }
    
    // Initialize dashboard service
    $dashboardService = new \App\Services\DashboardService($config, $wooCommerceService);
} catch (\Throwable $e) {
    // More detailed error message with file and line
    die("Error initializing services: " . $e->getMessage() . 
        "<br>in file: " . $e->getFile() . 
        "<br>at line: " . $e->getLine() . 
        "<br>Trace: <pre>" . $e->getTraceAsString() . "</pre>");
}

// Utility function to send JSON response
function sendJsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    // Add extra debugging info in development mode
    if (isset($GLOBALS['config']) && $GLOBALS['config']['app']['env'] === 'development') {
        $data['_debug'] = [
            'timestamp' => date('c'),
            'php_version' => phpversion(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB'
        ];
    }
    
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