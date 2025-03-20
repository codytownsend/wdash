<?php
/**
 * Modern Store Orders Page (2025 Design)
 * Save this file as public/store-orders.php
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
    <title><?= htmlspecialchars($storeId) ?> House of Soap - Orders</title>
    
    <!-- Modern fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.7/dayjs.min.js"></script>
    
    <style>
        /* Custom 2025 Design System */
        :root {
            /* Color palette - modern dark theme */
            --color-bg-primary: #0f172a;
            --color-bg-secondary: #1e293b;
            --color-bg-tertiary: #334155;
            --color-bg-accent: #0f766e;
            
            --color-text-primary: #f1f5f9;
            --color-text-secondary: #94a3b8;
            --color-text-muted: #64748b;
            
            --color-border: #2e3b52;
            
            --color-brand-primary: #14b8a6;
            --color-brand-secondary: #0d9488;
            
            --color-success: #22c55e;
            --color-warning: #eab308;
            --color-danger: #ef4444;
            --color-info: #3b82f6;
            
            /* Modern drop shadows */
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15), 0 1px 5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.2), 0 5px 10px rgba(0, 0, 0, 0.1);
            
            /* Modern spacing */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-12: 3rem;
            --space-16: 4rem;
            
            /* Modern radius */
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;
        }
        
        [x-cloak] { display: none !important; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
            line-height: 1.5;
        }
        
        /* Modern card designs with subtle depth */
        .card {
            background-color: var(--color-bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        /* Modern input styles */
        .input-modern {
            background-color: var(--color-bg-tertiary);
            border: 1px solid var(--color-border);
            color: var(--color-text-primary);
            border-radius: var(--radius-md);
            padding: var(--space-2) var(--space-4);
            transition: all 0.2s ease;
        }
        
        .input-modern:focus {
            border-color: var(--color-brand-primary);
            box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.3);
            outline: none;
        }
        
        /* Modern button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            padding: var(--space-2) var(--space-4);
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--color-brand-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--color-brand-secondary);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text-primary);
        }
        
        .btn-outline:hover {
            border-color: var(--color-brand-primary);
            color: var(--color-brand-primary);
        }
        
        /* Modern badge styles */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-full);
            padding: 0.15rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: rgba(34, 197, 94, 0.2);
            color: var(--color-success);
        }
        
        .badge-warning {
            background-color: rgba(234, 179, 8, 0.2);
            color: var(--color-warning);
        }
        
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--color-danger);
        }
        
        .badge-info {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--color-info);
        }
        
        .badge-default {
            background-color: rgba(100, 116, 139, 0.2);
            color: var(--color-text-secondary);
        }
        
        /* Modern animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
        }
        
        /* Modern table styles */
        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-modern th {
            background-color: var(--color-bg-tertiary);
            padding: var(--space-3) var(--space-4);
            text-align: left;
            font-weight: 500;
            color: var(--color-text-secondary);
            border-bottom: 1px solid var(--color-border);
        }
        
        .table-modern td {
            padding: var(--space-3) var(--space-4);
            border-bottom: 1px solid var(--color-border);
        }
        
        .table-modern tr:hover td {
            background-color: rgba(51, 65, 85, 0.4);
        }
        
        /* Glassmorphism top navigation */
        .top-nav {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--color-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Modern scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--color-bg-secondary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--color-bg-tertiary);
            border-radius: var(--radius-full);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-brand-primary);
        }
        
        /* Loading shimmer effect */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        
        .loading-shimmer {
            background: linear-gradient(
                to right,
                var(--color-bg-tertiary) 8%,
                var(--color-bg-secondary) 18%,
                var(--color-bg-tertiary) 33%
            );
            background-size: 1000px 100%;
            animation: shimmer 2s infinite linear;
        }
    </style>
</head>
<body>
    <div x-data="ordersPage()" x-init="init()" class="min-h-screen flex flex-col">
        <!-- Top navigation -->
        <nav class="top-nav py-4 px-6 mb-6">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <div class="mr-2 text-brand-primary">
                        <i class="bi bi-bar-chart-fill text-2xl text-teal-500"></i>
                    </div>
                    <h1 class="text-xl font-semibold">WC Insights</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/" class="btn btn-outline flex items-center">
                        <i class="bi bi-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <a href="/logout.php" class="flex items-center text-red-400 hover:text-red-300">
                        <i class="bi bi-box-arrow-right mr-1"></i>
                        <span class="hidden md:inline">Logout</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <main class="container mx-auto px-6 pb-16 flex-grow">
            <!-- Flash messages -->
            <?php if (!empty($messages['error'])): ?>
                <div class="bg-red-900/40 border border-red-500/30 text-red-300 px-4 py-3 rounded-md mb-6 flex items-start">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mr-3 text-red-500"></i>
                    <span><?= htmlspecialchars($messages['error']) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($messages['success'])): ?>
                <div class="bg-green-900/40 border border-green-500/30 text-green-300 px-4 py-3 rounded-md mb-6 flex items-start">
                    <i class="bi bi-check-circle flex-shrink-0 mr-3 text-green-500"></i>
                    <span><?= htmlspecialchars($messages['success']) ?></span>
                </div>
            <?php endif; ?>

            <div class="card p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-xl font-medium mb-1 flex items-center">
                            <i class="bi bi-shop-window mr-2 text-teal-500"></i>
                            <span><?= htmlspecialchars($storeId) ?> Orders</span>
                        </h2>
                        <p class="text-sm text-gray-400">
                            <?= $startDate->format('Y-m-d') ?> to <?= $endDate->format('Y-m-d') ?>
                            <span class="text-xs">(<?= $timezone->getName() ?>)</span>
                        </p>
                    </div>
                    
                    <div class="mt-4 md:mt-0">
                        <div class="relative">
                            <div class="flex">
                                <input type="text" 
                                       x-model="searchQuery" 
                                       placeholder="Search orders..." 
                                       class="input-modern pr-8">
                                <div class="absolute right-3 top-2 text-gray-400">
                                    <i class="bi bi-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="flex flex-col items-center py-12">
                    <div class="relative w-16 h-16 mb-4">
                        <div class="absolute inset-0 border-t-2 border-r-2 border-teal-500 rounded-full animate-spin"></div>
                    </div>
                    <span class="text-gray-400">Loading orders...</span>
                </div>

                <!-- Error State -->
                <div x-show="error && !loading" class="bg-red-900/40 border border-red-500/30 text-red-300 p-4 rounded-md flex items-start">
                    <i class="bi bi-exclamation-triangle flex-shrink-0 mr-3 text-red-500"></i>
                    <div>
                        <h3 class="font-medium">Error Loading Orders</h3>
                        <p x-text="error" class="text-sm"></p>
                    </div>
                </div>

                <!-- Orders Table -->
                <div x-show="!loading && !error" class="overflow-x-auto">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th class="rounded-tl-md">Order #</th>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                                <th class="rounded-tr-md">Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="filteredOrders.length === 0">
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <i class="bi bi-inbox text-4xl mb-2"></i>
                                            <p>No orders found for this time period</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="order in filteredOrders" :key="order.id">
                                <tr>
                                    <td>
                                        <span class="text-teal-500 font-medium" x-text="'#' + order.number"></span>
                                    </td>
                                    <td x-text="formatDate(order.date_created)"></td>
                                    <td>
                                        <span class="badge"
                                              :class="{
                                                  'badge-success': order.status === 'completed',
                                                  'badge-info': order.status === 'processing',
                                                  'badge-warning': order.status === 'on-hold',
                                                  'badge-default': order.status === 'pending',
                                                  'badge-default': !['completed', 'processing', 'on-hold', 'pending'].includes(order.status)
                                              }">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                                                  :class="{
                                                      'bg-green-400': order.status === 'completed',
                                                      'bg-blue-400': order.status === 'processing',
                                                      'bg-yellow-400': order.status === 'on-hold',
                                                      'bg-gray-400': order.status === 'pending',
                                                      'bg-gray-400': !['completed', 'processing', 'on-hold', 'pending'].includes(order.status)
                                                  }"></span>
                                            <span x-text="capitalizeFirst(order.status)"></span>
                                        </span>
                                    </td>
                                    <td class="text-right font-medium">
                                        <span x-text="currencySymbol + formatNumber(order.total, 2)"></span>
                                    </td>
                                    <td>
                                        <span class="px-2 py-1 bg-gray-800 rounded text-sm" x-text="getOrderSource(order)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    
                    <div x-show="!loading && !error && filteredOrders.length > 0" class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-400">
                            <span x-text="filteredOrders.length"></span> orders found
                            <span x-show="searchQuery && orders.length !== filteredOrders.length">
                                (filtered from <span x-text="orders.length"></span>)
                            </span>
                        </div>
                        <div class="text-right flex items-baseline">
                            <span class="text-gray-400 mr-2">Total:</span>
                            <span class="text-xl font-medium text-teal-400" x-text="currencySymbol + formatNumber(orderTotal, 2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="py-6 px-6 border-t border-gray-800 mt-auto">
            <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
                <div class="text-sm text-gray-500 mb-4 md:mb-0">
                    <p>DOE Tech &copy; <?= date('Y') ?></p>
                </div>
                <div class="flex space-x-4">
                    <span class="text-xs text-gray-600">
                        Server time: <?= date('Y-m-d H:i:s') ?>
                    </span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function ordersPage() {
            return {
                loading: true,
                error: null,
                orders: [],
                searchQuery: '',
                orderTotal: 0,
                currencySymbol: '',
                
                get filteredOrders() {
                    if (!this.searchQuery || this.searchQuery.trim() === '') {
                        return this.orders;
                    }
                    
                    const query = this.searchQuery.toLowerCase().trim();
                    return this.orders.filter(order => {
                        // Search in multiple fields
                        return order.number.toString().includes(query) ||
                               this.formatDate(order.date_created).toLowerCase().includes(query) ||
                               order.status.toLowerCase().includes(query) ||
                               this.getOrderSource(order).toLowerCase().includes(query) ||
                               order.total.toString().includes(query);
                    });
                },
                
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
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
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