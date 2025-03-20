<?php
namespace App\Services;

use Exception;

class DashboardService {
    private WooCommerceService $woocommerce;
    private array $config;
    private \DateTimeZone $timezone;
    
    /**
     * Constructor
     * 
     * @param array $config Application configuration
     * @param WooCommerceService $woocommerce WooCommerce service
     */
    public function __construct(array $config, WooCommerceService $woocommerce) {
        $this->config = $config;
        $this->woocommerce = $woocommerce;
        $this->timezone = new \DateTimeZone($config['app']['timezone'] ?? 'UTC');
    }
    
    /**
     * Get dashboard data
     * 
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @return array Dashboard data
     */
    public function getDashboardData(\DateTime $startDate, \DateTime $endDate): array {
        try {
            // Get metrics and attribution data
            $metrics = $this->woocommerce->getMetrics($startDate, $endDate);
            $attribution = $this->woocommerce->getAttributionStats($startDate, $endDate);
            
            // Calculate totals
            $totals = $this->calculateTotals($metrics);
            
            return [
                'metrics' => $metrics,
                'attribution' => $attribution,
                'totals' => $totals,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ];
        } catch (Exception $e) {
            // Log error
            error_log("Dashboard data error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate dashboard totals
     * 
     * @param array $metrics Store metrics
     * @return array Calculated totals
     */
    private function calculateTotals(array $metrics): array {
        $totalUsd = 0;
        $totalOrders = 0;
        
        foreach ($metrics as $storeId => $data) {
            if (!isset($data['error'])) {
                $currency = $data['currency'];
                $conversionRate = $this->config['conversion_rates'][$currency] ?? 1;
                
                $totalUsd += $data['total_revenue'] * $conversionRate;
                $totalOrders += $data['order_count'];
            }
        }
        
        $averageOrderValueUsd = $totalOrders > 0 ? $totalUsd / $totalOrders : 0;
        
        return [
            'revenue_usd' => $totalUsd,
            'orders' => $totalOrders,
            'average_order_value_usd' => $averageOrderValueUsd
        ];
    }
    
    /**
     * Get store orders
     * 
     * @param string $storeId Store ID
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @return array Orders data
     */
    public function getStoreOrders(string $storeId, \DateTime $startDate, \DateTime $endDate): array {
        try {
            return $this->woocommerce->getOrderDetails($storeId, $startDate, $endDate);
        } catch (Exception $e) {
            error_log("Store orders error for {$storeId}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get date range from preset
     * 
     * @param string $rangeKey Range key
     * @return array Start and end dates
     */
    public function getDateRange(string $rangeKey): array {
        $now = new \DateTime('now', $this->timezone);
        $today = new \DateTime('today', $this->timezone);
        
        switch ($rangeKey) {
            case 'today':
                $startDate = clone $today;
                $endDate = clone $today;
                break;
                
            case 'yesterday':
                $startDate = clone $today;
                $startDate->modify('-1 day');
                $endDate = clone $startDate;
                break;
                
            case 'week_to_date':
                $startDate = clone $today;
                $startDate->modify('sunday this week');
                if ($startDate > $now) {
                    $startDate->modify('-1 week');
                }
                $endDate = clone $today;
                break;
                
            case 'last_week':
                $startDate = clone $today;
                $startDate->modify('sunday last week');
                $endDate = clone $startDate;
                $endDate->modify('+6 days');
                break;
                
            case 'month_to_date':
                $startDate = clone $today;
                $startDate->modify('first day of this month');
                $endDate = clone $today;
                break;
                
            case 'last_month':
                $startDate = clone $today;
                $startDate->modify('first day of last month');
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');
                break;
                
            default:
                throw new Exception("Invalid date range");
        }
        
        return [$startDate, $endDate];
    }
}