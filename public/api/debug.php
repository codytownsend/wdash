<?php
/**
 * Debug API endpoint for troubleshooting dashboard issues
 * Save this file as public/api/debug.php
 */
require_once __DIR__ . '/../../src/bootstrap.php';

// Require authentication
if (!$authService->isAuthenticated()) {
    sendJsonResponse(['error' => 'Authentication required'], 401);
}

// Handle only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    // Get basic information
    $debugInfo = [
        'php_version' => phpversion(),
        'timezone' => date_default_timezone_get(),
        'session_active' => session_status() === PHP_SESSION_ACTIVE,
        'user_authenticated' => $authService->isAuthenticated(),
        'stores_configured' => array_keys($config['stores']),
        'date_ranges' => array_keys($config['date_ranges']),
        'bootstrap_loaded' => true,
        'services_loaded' => [
            'authService' => isset($authService),
            'wooCommerceService' => isset($wooCommerceService),
            'dashboardService' => isset($dashboardService),
        ]
    ];
    
    // Test date ranges
    $debugInfo['date_range_test'] = [];
    try {
        $debugInfo['date_range_test']['today'] = array_map(
            function($date) { return $date->format('Y-m-d'); },
            $dashboardService->getDateRange('today')
        );
    } catch (\Exception $e) {
        $debugInfo['date_range_test']['error'] = $e->getMessage();
    }
    
    // Test WooCommerce connection with minimal scope
    $debugInfo['woocommerce_test'] = [];
    try {
        // Use a minimal date range (just today) to reduce load
        list($startDate, $endDate) = $dashboardService->getDateRange('today');
        
        foreach ($config['stores'] as $storeId => $storeConfig) {
            try {
                // Just test connection, don't load full data
                $testOrder = $wooCommerceService->fetchOrders(
                    $storeId, 
                    $startDate, 
                    $endDate, 
                    ['per_page' => 1]  // Just get one order
                );
                
                $debugInfo['woocommerce_test'][$storeId] = [
                    'connection_successful' => true,
                    'order_count' => count($testOrder),
                    'has_orders' => count($testOrder) > 0
                ];
            } catch (\Exception $e) {
                $debugInfo['woocommerce_test'][$storeId] = [
                    'connection_successful' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    } catch (\Exception $e) {
        $debugInfo['woocommerce_test']['general_error'] = $e->getMessage();
    }
    
    // Return debug information
    sendJsonResponse(['debug' => $debugInfo]);
    
} catch (\Exception $e) {
    // Log error
    error_log("Debug API error: " . $e->getMessage());
    
    // Return error response with full details
    sendJsonResponse([
        'error' => 'Debug failed',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 500);
}