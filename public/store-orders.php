<?php
/**
 * Store orders page
 */
require_once __DIR__ . '/../src/bootstrap.php';

// Require authentication
$authService->requireAuth();

// Get current user
$user = $authService->getCurrentUser();

// Get and validate parameters
$storeId = $_GET['store'] ?? null;
$startDateStr = $_GET['start_date'] ?? null;
$endDateStr = $_GET['end_date'] ?? null;
$rangeKey = $_GET['range'] ?? null;

// Validate store ID
if (!$storeId || !isset($config['stores'][$storeId])) {
    redirectWithError('/', 'Invalid store ID');
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
            redirectWithError('/', 'Invalid date format');
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
    
    // Get flash messages
    $messages = getFlashMessages();
    
} catch (Exception $e) {
    redirectWithError('/', 'Error processing date range');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($storeId) ?> Orders - WooCommerce Dashboard</title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        body {
            background-color: #111827;
            color: #e5e7eb;
        }
        
        .dark-card {
            background-color: #1f2937;
            border: 1px solid #374151;
        }
        
        .dark-table th {
            background-color: #374151;
        }
        
        .dark-table tr:nth-child(even) {
            background-color: #1f2937;
        }
        
        .dark-table tr:nth-child(odd) {
            background-color: #111827;
        }
        
        .dark-table tr:hover {
            background-color: #374151;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div x-data="ordersPage()" x-init="init()" class="container mx-auto px-4 py-8">
        <!-- Top navigation bar -->
        <nav class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Store Orders</h1>
            
            <div class="flex items-center space-x-4">
                <a href="/" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    Back to Dashboard
                </a>
                <a href="/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">Logout</a>
            </div>
        </nav>
        
        <!-- Flash messages -->
        <?php if (!empty($messages['error'])): ?>
            <div class="bg-red-800 text-white p-4 rounded-md mb-6">
                <?= htmlspecialchars($messages['error']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages['success'])): ?>
            <div class="bg-green-800 text-white p-4 rounded-md mb-6">
                <?= htmlspecialchars($messages['success']) ?>
            </div>
        <?php endif; ?>

        <div class="dark-card rounded-lg shadow-lg p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold"><?= htmlspecialchars($storeId) ?> Orders</h2>
                
                <div>
                    <span class="text-gray-400">
                        <?= $startDate->format('Y-m-d') ?> to <?= $endDate->format('Y-m-d') ?>
                    </span>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="flex justify-center items-center py-12">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg text-gray-400">Loading orders...</span>
            </div>

            <!-- Error State -->
            <div x-show="error" class="bg-red-800 text-white p-4 rounded-lg mb-6">
                <p x-text="error"></p>
            </div>

            <!-- Orders Table -->
            <div x-show="!loading && !error" class="overflow-x-auto">
                <table class="w-full dark-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Order #</th>
                            <th class="px-4 py-2 text-left">Date/Time (<?= $timezone->getName() ?>)</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-right">Total</th>
                            <th class="px-4 py-2 text-left">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="order in orders" :key="order.id">
                            <tr class="border-t border-gray-700">
                                <td class="px-4 py-2">
                                    <span class="text-blue-400" x-text="'#' + order.number"></span>
                                </td>
                                <td class="px-4 py-2" x-text="formatDate(order.date_created)"></td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-sm"
                                          :class="{
                                              'bg-green-800 text-green-100': order.status === 'completed',
                                              'bg-blue-800 text-blue-100': order.status === 'processing',
                                              'bg-yellow-800 text-yellow-100': order.status === 'on-hold',
                                              'bg-gray-800 text-gray-100': order.status === 'pending',
                                              'bg-gray-700 text-gray-100': !['completed', 'processing', 'on-hold', 'pending'].includes(order.status)
                                          }"
                                          x-text="capitalizeFirst(order.status)"></span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <span x-text="currencySymbol + formatNumber(order.total, 2)"></span>
                                </td>
                                <td class="px-4 py-2" x-text="getOrderSource(order)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-400">
                        <span x-text="orders.length"></span> orders found
                    </div>
                    <div class="text-right">
                        <p class="text-lg">
                            Total: <span x-text="currencySymbol + formatNumber(orderTotal, 2)"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function ordersPage() {
            return {
                loading: true,
                error: null,
                orders: [],
                orderTotal: 0,
                currencySymbol: '',
                
                init() {
                    this.fetchOrders();
                },
                
                fetchOrders() {
                    const storeId = '<?= htmlspecialchars($storeId) ?>';
                    const startDate = '<?= $startDate->format('Y-m-d') ?>';
                    const endDate = '<?= $endDate->format('Y-m-d') ?>';
                    const rangeKey = '<?= htmlspecialchars($rangeKey ?? '') ?>';
                    
                    let url = `/api/orders.php?store=${encodeURIComponent(storeId)}`;
                    
                    if (rangeKey) {
                        url += `&range=${rangeKey}`;
                    } else {
                        url += `&start_date=${startDate}&end_date=${endDate}`;
                    }
                    
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch orders');
                            }
                            return response.json();
                        })
                        .then(data => {
                            this.orders = data.orders;
                            this.currencySymbol = data.currency_symbol;
                            this.calculateTotal();
                            this.loading = false;
                        })
                        .catch(error => {
                            this.error = `Error loading orders: ${error.message}`;
                            this.loading = false;
                            console.error('Orders error:', error);
                        });
                },
                
                calculateTotal() {
                    this.orderTotal = this.orders.reduce((total, order) => 
                        total + parseFloat(order.total), 0);
                },
                
                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        timeZoneName: 'short'
                    });
                },
                
                capitalizeFirst(str) {
                    return str.charAt(0).toUpperCase() + str.slice(1);
                },
                
                getOrderSource(order) {
                    if (!order.meta_data) {
                        return '(direct)';
                    }
                    
                    const attributionKeys = [
                        '_wc_order_attribution_utm_source',
                        '_order_attribution_source',
                        '_woosea_attribution',
                        '_billing_wooctm_utm_source',
                        '_utm_source'
                    ];
                    
                    for (const meta of order.meta_data) {
                        if (attributionKeys.includes(meta.key) && meta.value) {
                            return meta.value;
                        }
                    }
                    
                    return '(direct)';
                },
                
                formatNumber(number, decimals = 0) {
                    return parseFloat(number).toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    });
                }
            };
        }
    </script>
</body>
</html>