<?php
/**
 * API Orders Endpoint
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

// Get and validate parameters
$storeId = $_GET['store'] ?? null;
$startDateStr = $_GET['start_date'] ?? null;
$endDateStr = $_GET['end_date'] ?? null;
$rangeKey = $_GET['range'] ?? null;

// Validate store ID
if (!$storeId || !isset($config['stores'][$storeId])) {
    sendJsonResponse(['error' => 'Invalid store ID'], 400);
}

$timezone = new DateTimeZone($config['app']['timezone']);

try {
    // If range is provided, use it to calculate dates
    if ($rangeKey && isset($config['date_ranges'][$rangeKey])) {
        list($startDate, $endDate) = $dashboardService->getDateRange($rangeKey);
    } 
    // Otherwise use explicit dates
    else if ($startDateStr && $endDateStr) {
        $startDate = parseDate($startDateStr, $timezone);
        $endDate = parseDate($endDateStr, $timezone);
        
        if (!$startDate || !$endDate) {
            sendJsonResponse(['error' => 'Invalid date format'], 400);
        }
        
        // Ensure end date is not after today
        $today = new DateTime('today', $timezone);
        if ($endDate > $today) {
            $endDate = clone $today;
        }
        
        // Ensure start date is not after end date
        if ($startDate > $endDate) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }
    } 
    // Default to today if no dates provided
    else {
        list($startDate, $endDate) = $dashboardService->getDateRange('today');
    }
    
    // Get store orders
    $data = $dashboardService->getStoreOrders($storeId, $startDate, $endDate);
    
    // Add date range info
    $data['date_range'] = [
        'start' => $startDate->format('Y-m-d'),
        'end' => $endDate->format('Y-m-d')
    ];
    
    // Return JSON response
    sendJsonResponse($data);
    
} catch (Exception $e) {
    // Log error
    error_log("API error: " . $e->getMessage());
    
    // Return error response
    sendJsonResponse([
        'error' => 'Failed to fetch store orders', 
        'message' => $config['app']['debug'] ? $e->getMessage() : null
    ], 500);
}