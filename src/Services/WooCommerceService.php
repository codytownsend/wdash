<?php
namespace App\Services;

use Exception;

// Manually include Composer's autoloader to ensure dependencies are loaded
require_once __DIR__ . '/../../vendor/autoload.php';

use Automattic\WooCommerce\Client;

class WooCommerceService {
    private array $stores = [];
    private array $config;
    private \DateTimeZone $timezone;
    private $redis = null;
    private int $cacheDuration = 300;
    
    /**
     * Constructor
     * 
     * @param array $config Application configuration
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->timezone = new \DateTimeZone($config['app']['timezone'] ?? 'UTC');
        
        // Aggressive Predis loading attempt
        $this->loadPredis();
        
        // Check if Predis is available
        if (class_exists('\Predis\Client')) {
            $this->initRedis();
        } else {
            error_log("Predis\Client could not be loaded. Skipping Redis caching.");
        }
        
        $this->initStores();
    }
    
    /**
     * Attempt to load Predis manually
     */
    private function loadPredis(): void {
        $possiblePredisAutoloads = [
            __DIR__ . '/../../vendor/predis/predis/autoload.php',
            __DIR__ . '/../vendor/predis/predis/autoload.php',
            __DIR__ . '/vendor/predis/predis/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/vendor/autoload.php',
        ];
        
        foreach ($possiblePredisAutoloads as $autoloadPath) {
            if (file_exists($autoloadPath)) {
                try {
                    require_once $autoloadPath;
                    error_log("Predis loaded from: " . $autoloadPath);
                    break;
                } catch (Exception $e) {
                    error_log("Failed to load Predis from " . $autoloadPath . ": " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Initialize Redis connection with improved error handling
     */
    private function initRedis(): void {
        // Skip Redis if explicitly disabled in config
        if (isset($this->config['cache']['enabled']) && $this->config['cache']['enabled'] === false) {
            $this->redis = null;
            return;
        }
        
        // Skip if Redis config is missing
        if (!isset($this->config['cache']['redis'])) {
            error_log("Redis config missing, skipping cache initialization");
            $this->redis = null;
            return;
        }
        
        try {
            $this->redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host' => $this->config['cache']['redis']['host'] ?? '127.0.0.1',
                'port' => $this->config['cache']['redis']['port'] ?? 6379,
                'password' => $this->config['cache']['redis']['password'] ?? null,
                'timeout' => 2, // 2 second timeout
                'read_write_timeout' => 2
            ]);
            
            // Test the connection with a ping
            $this->redis->ping();
            
            $this->cacheDuration = $this->config['cache']['duration'] ?? 300;
            error_log("Redis connection successful");
        } catch (Exception $e) {
            // Log the error but continue without Redis
            error_log("Redis connection failed: " . $e->getMessage());
            
            // Set redis to null to indicate no caching
            $this->redis = null;
        }
    }
    
    /**
     * Initialize WooCommerce store connections
     */
    private function initStores(): void {
        foreach ($this->config['stores'] as $storeId => $storeConfig) {
            $this->stores[$storeId] = new Client(
                $storeConfig['url'],
                $storeConfig['consumer_key'],
                $storeConfig['consumer_secret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                    'query_string_auth' => true,
                    'verify_ssl' => false,
                    'timeout' => 30
                ]
            );
        }
    }
    
    /**
     * Create a cache key for a specific request
     */
    private function getCacheKey(string $storeId, string $type, \DateTime $startDate, \DateTime $endDate): string {
        return "wc:{$storeId}:{$type}:{$startDate->format('Ymd')}:{$endDate->format('Ymd')}";
    }
    
    /**
     * Fetch data with caching - improved with better error handling
     */
    private function remember(string $key, callable $callback) {
        // If Redis is not available, just execute the callback without caching
        if (!$this->redis) {
            return $callback();
        }
        
        try {
            // Try to get from cache
            $cached = $this->redis->get($key);
            if ($cached !== null) {
                $decoded = json_decode($cached, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
            
            // If not in cache or decode failed, execute the callback
            $fresh = $callback();
            
            // Store in cache
            $this->redis->setex($key, $this->cacheDuration, json_encode($fresh));
            
            return $fresh;
        } catch (Exception $e) {
            // Log Redis error but continue without caching
            error_log("Redis error in remember method: " . $e->getMessage());
            return $callback();
        }
    }
    
    /**
     * Fetch all orders with pagination
     */
    public function fetchOrders(string $storeId, \DateTime $startDate, \DateTime $endDate, array $additionalParams = []): array {
        if (!isset($this->stores[$storeId])) {
            throw new Exception("Store ID not found: {$storeId}");
        }
        
        $client = $this->stores[$storeId];
        
        // Convert dates to UTC for WooCommerce API
        $startUtc = clone $startDate;
        $endUtc = clone $endDate;
        $startUtc->setTimezone(new \DateTimeZone('UTC'));
        $endUtc->setTimezone(new \DateTimeZone('UTC'));
        
        // Prepare params with optimized fields selection
        $params = array_merge([
            'after' => $startUtc->format('Y-m-d\TH:i:s'),
            'before' => $endUtc->format('Y-m-d\TH:i:s'),
            'status' => ['processing', 'completed', 'on-hold', 'pending'],
            // Only fetch fields we need (optimize payload size)
            '_fields' => 'id,number,status,total,date_created,meta_data'
        ], $additionalParams);
        
        // Get cache key
        $cacheKey = $this->getCacheKey(
            $storeId, 
            'orders', 
            $startDate, 
            $endDate
        );
        
        // Try to get from cache or fetch fresh data
        return $this->remember($cacheKey, function() use ($client, $params) {
            $allOrders = [];
            $page = 1;
            $perPage = 100;
            
            do {
                $params['page'] = $page;
                $params['per_page'] = $perPage;
                
                try {
                    $orders = $client->get('orders', $params);
                    if (empty($orders)) {
                        break;
                    }
                    
                    $allOrders = array_merge($allOrders, $orders);
                    $page++;
                    
                    // Break if we received fewer orders than the page size
                    if (count($orders) < $perPage) {
                        break;
                    }
                } catch (Exception $e) {
                    // Log error but don't expose details
                    error_log("Error fetching orders from {$params['after']} to {$params['before']}: " . $e->getMessage());
                    break;
                }
            } while (true);
            
            return $allOrders;
        });
    }
    
    /**
     * Get metrics for all stores
     */
    public function getMetrics(\DateTime $startDate, \DateTime $endDate): array {
        $startDate = clone $startDate;
        $endDate = clone $endDate;
        
        // Set timezone and time boundaries
        $startDate->setTimezone($this->timezone);
        $startDate->setTime(0, 0, 0);
        
        $endDate->setTimezone($this->timezone);
        $endDate->setTime(23, 59, 59);
        
        // Prepare params for parallel requests
        $metrics = [];
        
        // Process each store (could be parallelized with promises in a production environment)
        foreach ($this->stores as $storeId => $client) {
            try {
                // Get store metrics
                $orders = $this->fetchOrders($storeId, $startDate, $endDate);
                
                $storeMetrics = [
                    'total_revenue' => 0,
                    'order_count' => 0,
                    'completed_orders' => 0,
                    'processing_orders' => 0,
                    'on_hold_orders' => 0,
                    'pending_orders' => 0,
                    'average_order_value' => 0,
                    'currency' => $this->config['stores'][$storeId]['currency'],
                    'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol']
                ];
                
                // Process orders to calculate metrics
                foreach ($orders as $order) {
                    $storeMetrics['total_revenue'] += floatval($order->total);
                    $storeMetrics['order_count']++;
                    
                    switch ($order->status) {
                        case 'completed':
                            $storeMetrics['completed_orders']++;
                            break;
                        case 'processing':
                            $storeMetrics['processing_orders']++;
                            break;
                        case 'on-hold':
                            $storeMetrics['on_hold_orders']++;
                            break;
                        case 'pending':
                            $storeMetrics['pending_orders']++;
                            break;
                    }
                }
                
                if ($storeMetrics['order_count'] > 0) {
                    $storeMetrics['average_order_value'] = 
                        $storeMetrics['total_revenue'] / $storeMetrics['order_count'];
                }
                
                $metrics[$storeId] = $storeMetrics;
                
            } catch (Exception $e) {
                // Log error but return minimal info to client
                error_log("Error getting metrics for {$storeId}: " . $e->getMessage());
                $metrics[$storeId] = [
                    'error' => 'Failed to fetch data: ' . $e->getMessage(),
                    'currency' => $this->config['stores'][$storeId]['currency'],
                    'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol']
                ];
            }
        }
        
        return $metrics;
    }
    
    /**
     * Get order attribution statistics
     */
    public function getAttributionStats(\DateTime $startDate, \DateTime $endDate): array {
        $attribution = [];
        
        foreach ($this->stores as $storeId => $client) {
            try {
                $orders = $this->fetchOrders($storeId, $startDate, $endDate);
                
                $storeStats = [
                    'total_orders' => 0,
                    'sources' => [],
                    'revenue_by_source' => [],
                    'currency' => $this->config['stores'][$storeId]['currency'],
                    'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol']
                ];
                
                foreach ($orders as $order) {
                    $source = $this->getOrderSource($order);
                    
                    // Increment order count
                    $storeStats['sources'][$source] = ($storeStats['sources'][$source] ?? 0) + 1;
                    $storeStats['total_orders']++;
                    
                    // Add revenue
                    $storeStats['revenue_by_source'][$source] = 
                        ($storeStats['revenue_by_source'][$source] ?? 0) + floatval($order->total);
                }
                
                // Calculate percentages
                $storeStats['percentages'] = [];
                if ($storeStats['total_orders'] > 0) {
                    foreach ($storeStats['sources'] as $source => $count) {
                        $storeStats['percentages'][$source] = 
                            round(($count / $storeStats['total_orders']) * 100, 2);
                    }
                }
                
                // Sort sources by order count
                arsort($storeStats['sources']);
                arsort($storeStats['revenue_by_source']);
                
                $attribution[$storeId] = $storeStats;
                
            } catch (Exception $e) {
                error_log("Error getting attribution for {$storeId}: " . $e->getMessage());
                $attribution[$storeId] = [
                    'error' => 'Failed to fetch attribution data: ' . $e->getMessage(),
                    'currency' => $this->config['stores'][$storeId]['currency'],
                    'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol']
                ];
            }
        }
        
        return $attribution;
    }
    
    /**
     * Get order source from meta data
     */
    private function getOrderSource($order): string {
        $source = '(direct)';
        
        if (isset($order->meta_data) && is_array($order->meta_data)) {
            foreach ($order->meta_data as $meta) {
                // Check for various attribution meta keys
                $attributionKeys = [
                    '_wc_order_attribution_utm_source',
                    '_order_attribution_source',
                    '_woosea_attribution',
                    '_billing_wooctm_utm_source',
                    '_utm_source'
                ];
                
                if (in_array($meta->key, $attributionKeys)) {
                    if (is_string($meta->value) && !empty($meta->value)) {
                        $source = $meta->value;
                        break;
                    }
                }
            }
        }
        
        return $source;
    }
    
    /**
     * Get order details for a specific store
     */
    public function getOrderDetails(string $storeId, \DateTime $startDate, \DateTime $endDate): array {
        $orders = $this->fetchOrders($storeId, $startDate, $endDate);
        
        return [
            'orders' => $orders,
            'currency' => $this->config['stores'][$storeId]['currency'],
            'currency_symbol' => $this->config['stores'][$storeId]['currency_symbol']
        ];
    }
    
    /**
     * Get a single order by ID
     */
    public function getOrder(string $storeId, int $orderId): object {
        if (!isset($this->stores[$storeId])) {
            throw new Exception("Store ID not found: {$storeId}");
        }
        
        $client = $this->stores[$storeId];
        
        // Try to get order
        $cacheKey = "wc:{$storeId}:order:{$orderId}";
        
        return $this->remember($cacheKey, function() use ($client, $orderId) {
            return $client->get("orders/{$orderId}");
        });
    }
}