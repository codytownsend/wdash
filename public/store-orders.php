<?php
/**
 * Brand-Aligned Orders Page
 * Tailored to match House of Soap's visual identity
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
    <title><?= htmlspecialchars($storeId) ?> Orders - House of Soap</title>
    
    <!-- Modern fonts - Matching typeface from their site -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.7/dayjs.min.js"></script>
    
    <style>
        :root {
            /* House of Soap Brand Colors */
            --color-white: #FFFFFF;
            --color-cream: #FFFCF2;
            --color-taupe: #D7D2CB;
            --color-olive: #899064;
            --color-charcoal: #333333;
            
            /* Extended palette */
            --color-cream-dark: #F5F2E3;
            --color-taupe-light: #E5E1DB;
            --color-taupe-dark: #AEA89F;
            --color-olive-light: #A8AD7B;
            --color-olive-dark: #6B7247;
            
            /* Status colors - natural/earthy tones */
            --color-success: #71986A; /* Sage green */
            --color-warning: #D2A24C; /* Amber */
            --color-danger: #B86B6B;  /* Terracotta */
            --color-info: #6A8CAD;    /* Dusty blue */
            
            /* Surfaces */
            --surface-primary: var(--color-white);
            --surface-secondary: var(--color-cream);
            --surface-tertiary: var(--color-taupe-light);
            --surface-accent: var(--color-olive-light);
            
            /* Text colors */
            --text-primary: var(--color-charcoal);
            --text-secondary: #5F5F5F;
            --text-tertiary: #8A8A8A;
            --text-light: var(--color-white);
            
            /* Shadows - softer for natural look */
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.08);
            --shadow-focus: 0 0 0 3px rgba(137, 144, 100, 0.2);
            
            /* Border radius - softer corners */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-full: 9999px;
        }
        
        [x-cloak] { display: none !important; }
        
        html, body {
            background-color: var(--color-cream);
            color: var(--text-primary);
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
        }
        
        /* Navbar styling */
        .navbar {
            background-color: var(--surface-primary);
            border-bottom: 1px solid var(--color-taupe);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
        }
        
        /* Logo styling */
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--text-primary);
        }
        
        .logo-icon {
            background-color: var(--color-olive);
            color: white;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary {
            background-color: var(--color-olive);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--color-olive-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--color-taupe);
            color: var(--text-secondary);
        }
        
        .btn-outline:hover {
            border-color: var(--color-olive);
            color: var(--color-olive);
        }
        
        /* Card surfaces */
        .soap-card {
            background: var(--surface-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        /* Search input */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 300px;
        }
        
        .search-input {
            width: 100%;
            background: var(--surface-secondary);
            border: 1px solid var(--color-taupe-light);
            border-radius: var(--radius-md);
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            border-color: var(--color-olive);
            box-shadow: var(--shadow-focus);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            font-size: 1rem;
        }
        
        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-completed {
            background-color: rgba(113, 152, 106, 0.15);
            color: var(--color-success);
        }
        
        .badge-processing {
            background-color: rgba(106, 140, 173, 0.15);
            color: var(--color-info);
        }
        
        .badge-onhold {
            background-color: rgba(210, 162, 76, 0.15);
            color: var(--color-warning);
        }
        
        .badge-pending {
            background-color: rgba(174, 168, 159, 0.15);
            color: var(--text-tertiary);
        }
        
        /* Table styling */
        .table-container {
            background: var(--surface-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .modern-table th {
            background: var(--color-taupe-light);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.875rem 1rem;
            text-align: left;
            position: sticky;
            top: 0;
        }
        
        .modern-table th:first-child {
            border-top-left-radius: var(--radius-sm);
        }
        
        .modern-table th:last-child {
            border-top-right-radius: var(--radius-sm);
        }
        
        .modern-table td {
            padding: 0.875rem 1rem;
            border-top: 1px solid var(--color-taupe-light);
        }
        
        .modern-table tr:hover td {
            background-color: var(--color-cream);
        }
        
        .order-number {
            font-weight: 600;
            color: var(--color-olive);
        }
        
        .order-total {
            font-weight: 600;
            text-align: right;
        }
        
        /* Source tag */
        .source-tag {
            display: inline-flex;
            padding: 0.25rem 0.5rem;
            background: var(--color-taupe-light);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            color: var(--text-tertiary);
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--color-taupe);
        }
        
        /* Footer */
        .footer {
            background-color: var(--surface-primary);
            border-top: 1px solid var(--color-taupe);
            padding: 1.5rem;
            color: var(--text-tertiary);
            font-size: 0.875rem;
        }
        
        /* Loading spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            border: 3px solid rgba(137, 144, 100, 0.1);
            border-top-color: var(--color-olive);
            animation: spin 1s linear infinite;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Layout container */
        .layout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .layout-container {
                padding: 1rem;
            }
            
            .modern-table th, 
            .modern-table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div x-data="ordersPage()" x-init="init()" class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <header class="navbar">
            <div class="navbar-container">
                <div class="logo">
                    <span>House of Soap</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="./index.php" class="btn btn-outline flex items-center">
                        <i class="bi bi-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </header>
        
        <main class="flex-grow">
            <div class="layout-container">
                <!-- Flash messages -->
                <?php if (!empty($messages['error'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6 flex items-start">
                        <i class="bi bi-exclamation-triangle flex-shrink-0 mr-3 text-red-500"></i>
                        <span><?= htmlspecialchars($messages['error']) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($messages['success'])): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 flex items-start">
                        <i class="bi bi-check-circle flex-shrink-0 mr-3 text-green-500"></i>
                        <span><?= htmlspecialchars($messages['success']) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="soap-card p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold mb-2 flex items-center">
                                <span class="text-olive-600 mr-2"><?= htmlspecialchars($storeId) ?></span>
                                <span>Orders</span>
                            </h1>
                            <p class="text-sm text-gray-600">
                                <?= $startDate->format('Y-m-d') ?> to <?= $endDate->format('Y-m-d') ?>
                                <span class="text-gray-400 text-xs ml-1">(<?= $timezone->getName() ?>)</span>
                            </p>
                        </div>
                        
                        <div class="mt-4 md:mt-0">
                            <div class="search-container">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" 
                                       x-model="searchQuery" 
                                       placeholder="Search orders..." 
                                       class="search-input">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div x-show="loading" class="flex flex-col items-center py-16">
                        <div class="loading-spinner mb-4"></div>
                        <span class="text-gray-500">Loading orders...</span>
                    </div>
                    
                    <!-- Error State -->
                    <div x-show="error && !loading" class="bg-red-50 border border-red-200 text-red-700 p-6 rounded-md">
                        <div class="flex items-start">
                            <i class="bi bi-exclamation-triangle flex-shrink-0 mr-3 text-red-500 text-xl"></i>
                            <div>
                                <h3 class="font-semibold text-lg mb-2">Error Loading Orders</h3>
                                <p x-text="error"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders Table -->
                    <div x-show="!loading && !error" class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Date/Time</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="filteredOrders.length === 0">
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox empty-icon"></i>
                                                <p class="text-lg font-medium mb-2">No orders found</p>
                                                <p class="text-sm">There are no orders for this time period.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <template x-for="order in filteredOrders" :key="order.id">
                                    <tr>
                                        <td class="order-number" x-text="'#' + order.number"></td>
                                        <td x-text="formatDate(order.date_created)"></td>
                                        <td>
                                            <span :class="{
                                                'badge badge-completed': order.status === 'completed',
                                                'badge badge-processing': order.status === 'processing',
                                                'badge badge-onhold': order.status === 'on-hold',
                                                'badge badge-pending': order.status === 'pending' || !['completed', 'processing', 'on-hold'].includes(order.status)
                                            }">
                                                <span x-text="capitalizeFirst(order.status)"></span>
                                            </span>
                                        </td>
                                        <td class="order-total">
                                            <span x-text="currencySymbol + formatNumber(order.total, 2)"></span>
                                        </td>
                                        <td>
                                            <div class="source-tag" x-text="getOrderSource(order)"></div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Order Summary -->
                    <div x-show="!loading && !error && filteredOrders.length > 0" class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600 mb-3 sm:mb-0">
                            <span x-text="filteredOrders.length"></span> orders found
                            <span x-show="searchQuery && orders.length !== filteredOrders.length">
                                (filtered from <span x-text="orders.length"></span>)
                            </span>
                        </div>
                        <div class="text-right bg-gray-50 px-4 py-2 rounded-md border border-gray-200">
                            <span class="text-gray-600 mr-2">Total:</span>
                            <span class="text-xl font-bold text-olive-600" x-text="currencySymbol + formatNumber(orderTotal, 2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="footer mt-auto">
            <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>House of Soap Insights &copy; <?= date('Y') ?></p>
                </div>
                <div class="flex space-x-4">
                    <span class="text-sm">
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
                    
                    let url = `./api/orders.php?store=${encodeURIComponent(storeId)}`;
                    
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
                            this.orders = data.orders || [];
                            this.currencySymbol = data.currency_symbol || '$';
                            this.calculateTotal();
                            this.loading = false;
                        })
                        .catch(error => {
                            this.error = `Error loading orders: ${error.message}`;
                            this.loading = false;
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
                        month: 'short',
                        day: 'numeric',
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
                    // Use browser's Intl formatter for better display
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    }).format(number);
                }
            };
        }
    </script>
</body>
</html>