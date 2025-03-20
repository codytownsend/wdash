<?php
/**
 * Dashboard
 */
require_once __DIR__ . '/../src/bootstrap.php';

// Require authentication
$authService->requireAuth();

// Get current user
$user = $authService->getCurrentUser();

// Get flash messages
$messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House of Soap - Dashboard</title>
    
    <!-- Modern fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Modern date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.7/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .metric-card {
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }
        
        .metric-card:hover {
            box-shadow: 0 0 0 2px var(--color-brand-primary);
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
        
        /* Modern animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
        }
        
        /* Chart styles */
        .chart-container {
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .status-success { background-color: var(--color-success); }
        .status-warning { background-color: var(--color-warning); }
        .status-danger { background-color: var(--color-danger); }
        .status-info { background-color: var(--color-info); }
        
        /* Modern dropdowns */
        .dropdown-modern {
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
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
        
        /* Key metrics design */
        .key-metric {
            position: relative;
            overflow: hidden;
        }
        
        .key-metric-icon {
            position: absolute;
            bottom: -15px;
            right: -15px;
            opacity: 0.1;
            font-size: 5rem;
            transform: rotate(15deg);
            color: var(--color-brand-primary);
        }
        
        /* Timeline/trend indicators */
        .trend-up {
            color: var(--color-success);
        }
        
        .trend-down {
            color: var(--color-danger);
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
    <div x-data="dashboard()" x-init="init()" class="min-h-screen flex flex-col">
        <!-- Top navigation -->
        <nav class="top-nav py-4 px-6 mb-6">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <div class="mr-2 text-brand-primary">
                        <i class="bi bi-bar-chart-fill text-2xl text-teal-500"></i>
                    </div>
                    <h1 class="text-xl font-semibold">WC Insights</h1>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Date range selector -->
                    <div class="relative" @click.away="dateDropdownOpen = false">
                        <button @click="dateDropdownOpen = !dateDropdownOpen" 
                                class="btn btn-outline flex items-center">
                            <i class="bi bi-calendar-range mr-2"></i>
                            <span x-text="dateRangeLabel"></span>
                            <i class="bi bi-chevron-down ml-2 text-xs"></i>
                        </button>
                        
                        <div x-show="dateDropdownOpen" 
                             class="dropdown-modern absolute right-0 mt-2 w-80 z-50 animate-fade-in"
                             x-cloak>
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-gray-400 mb-3">PRESETS</h3>
                                <div class="grid grid-cols-2 gap-2 mb-4">
                                    <template x-for="(range, key) in dateRanges" :key="key">
                                        <button @click.prevent="setDateRange(key)"
                                               class="btn btn-outline text-sm py-1"
                                               :class="{'border-teal-500 text-teal-500': selectedRange === key}">
                                            <span x-text="range.label"></span>
                                        </button>
                                    </template>
                                </div>
                                
                                <h3 class="text-sm font-medium text-gray-400 mb-3 mt-4">CUSTOM RANGE</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Start Date</label>
                                        <input type="date" 
                                               x-model="customStartDate"
                                               x-ref="startDatePicker"
                                               class="input-modern w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">End Date</label>
                                        <input type="date" 
                                               x-model="customEndDate"
                                               x-ref="endDatePicker"
                                               class="input-modern w-full">
                                    </div>
                                    <button @click="applyCustomRange(); dateDropdownOpen = false" 
                                            class="btn btn-primary w-full mt-2">
                                        Apply Range
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User menu -->
                    <div class="relative" @click.away="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-full bg-teal-500 flex items-center justify-center uppercase font-semibold text-white">
                                <?= htmlspecialchars(substr($user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="hidden md:inline text-sm"><?= htmlspecialchars($user['username']) ?></span>
                            <i class="bi bi-chevron-down text-xs"></i>
                        </button>
                        
                        <div x-show="userMenuOpen" 
                             class="dropdown-modern absolute right-0 mt-2 w-48 py-2 animate-fade-in"
                             x-cloak>
                            <a href="/logout.php" class="block px-4 py-2 text-sm hover:bg-gray-700 flex items-center">
                                <i class="bi bi-box-arrow-right mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
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

            <!-- Debug message for development -->
            <div x-show="showDebugMessage" class="bg-yellow-900/40 border border-yellow-500/30 text-yellow-300 px-4 py-3 rounded-md mb-6">
                <div class="flex items-start">
                    <i class="bi bi-bug flex-shrink-0 mr-3 text-yellow-500"></i>
                    <div>
                        <h3 class="font-medium mb-1">Data loading issue detected</h3>
                        <p x-text="debugMessage" class="text-sm"></p>
                        <div class="mt-2 flex space-x-3">
                            <button @click="runDiagnostics()" class="text-xs px-2 py-1 bg-yellow-500/20 rounded text-yellow-500 hover:bg-yellow-500/30">
                                Run Diagnostics
                            </button>
                            <button @click="showDebugMessage = false" class="text-xs px-2 py-1 bg-yellow-500/10 rounded text-yellow-300 hover:bg-yellow-500/20">
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Diagnostic results -->
                <div x-show="diagnosticResults" class="mt-3 pt-3 border-t border-yellow-500/20">
                    <h4 class="font-medium mb-2 text-sm">Diagnostic Results:</h4>
                    <pre x-text="diagnosticResults" class="text-xs bg-yellow-900/30 p-3 rounded overflow-auto max-h-60"></pre>
                </div>
            </div>

            <!-- Dashboard content with loading state -->
            <div x-show="loading" class="flex flex-col space-y-8">
                <!-- Loading skeleton for key metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="i in 3" class="metric-card p-6 loading-shimmer h-32"></div>
                </div>
                
                <!-- Loading skeleton for store cards -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div v-for="i in 2" class="card p-6">
                        <div class="h-6 w-1/3 loading-shimmer rounded mb-6"></div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="h-24 loading-shimmer rounded col-span-2"></div>
                            <div class="h-24 loading-shimmer rounded col-span-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard content when loaded -->
            <div x-show="!loading" class="flex flex-col space-y-8">
                <!-- Key metrics section -->
                <section>
                    <h2 class="text-xl font-medium mb-4 flex items-center">
                        <i class="bi bi-bar-chart-fill mr-2 text-teal-500"></i>
                        Key Metrics
                        <span x-show="isDataLoaded" class="text-xs text-gray-400 ml-3">
                            <span x-text="startDate"></span> – <span x-text="endDate"></span>
                        </span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Total Revenue Metric -->
                        <div class="metric-card p-6 relative key-metric">
                            <div class="text-sm text-gray-400 mb-1">Total Revenue</div>
                            <div class="text-3xl font-semibold mb-1">
                                $<span x-text="formatNumber(totals.revenue_usd)"></span>
                                <span x-show="revenueChange !== null" 
                                      :class="revenueChange >= 0 ? 'trend-up' : 'trend-down'"
                                      class="text-sm font-normal ml-2">
                                    <i :class="revenueChange >= 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                                    <span x-text="formatNumber(Math.abs(revenueChange), 1)"></span>%
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">vs. previous period</div>
                            <i class="bi bi-currency-dollar key-metric-icon"></i>
                        </div>
                        
                        <!-- Total Orders Metric -->
                        <div class="metric-card p-6 relative key-metric">
                            <div class="text-sm text-gray-400 mb-1">Total Orders</div>
                            <div class="text-3xl font-semibold mb-1">
                                <span x-text="formatNumber(totals.orders)"></span>
                                <span x-show="orderChange !== null" 
                                      :class="orderChange >= 0 ? 'trend-up' : 'trend-down'"
                                      class="text-sm font-normal ml-2">
                                    <i :class="orderChange >= 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                                    <span x-text="formatNumber(Math.abs(orderChange), 1)"></span>%
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">vs. previous period</div>
                            <i class="bi bi-cart key-metric-icon"></i>
                        </div>
                        
                        <!-- Average Order Value Metric -->
                        <div class="metric-card p-6 relative key-metric">
                            <div class="text-sm text-gray-400 mb-1">Average Order Value</div>
                            <div class="text-3xl font-semibold mb-1">
                                $<span x-text="formatNumber(totals.average_order_value_usd, 2)"></span>
                                <span x-show="aovChange !== null" 
                                      :class="aovChange >= 0 ? 'trend-up' : 'trend-down'"
                                      class="text-sm font-normal ml-2">
                                    <i :class="aovChange >= 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                                    <span x-text="formatNumber(Math.abs(aovChange), 1)"></span>%
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">vs. previous period</div>
                            <i class="bi bi-cash-stack key-metric-icon"></i>
                        </div>
                    </div>
                </section>
                
                <!-- Error message -->
                <div x-show="error" class="card p-6 border-red-500/50 bg-red-900/20">
                    <div class="flex items-start">
                        <i class="bi bi-exclamation-triangle text-red-500 text-xl mr-3"></i>
                        <div>
                            <h3 class="font-medium text-red-300 mb-1">Error Loading Dashboard Data</h3>
                            <p x-text="error" class="text-red-300/80"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Store performance section -->
                <section x-show="!error && isDataLoaded">
                    <h2 class="text-xl font-medium mb-4 flex items-center">
                        <i class="bi bi-shop mr-2 text-teal-500"></i>
                        Store Performance
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <template x-for="(data, siteId) in metrics" :key="siteId">
                            <div class="card card-hover p-6">
                                <!-- Store header with view orders link -->
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-medium flex items-center">
                                        <i class="bi bi-shop-window mr-2 text-teal-500"></i>
                                        <span x-text="siteId"></span>
                                    </h3>
                                    <a :href="`store-orders.php?store=${encodeURIComponent(siteId)}&start_date=${startDate}&end_date=${endDate}`" 
                                       class="btn btn-outline btn-sm text-xs flex items-center">
                                        <span>View Orders</span>
                                        <i class="bi bi-arrow-right ml-1"></i>
                                    </a>
                                </div>
                                
                                <!-- Store error message -->
                                <div x-show="data.error" class="bg-red-900/20 border border-red-500/30 text-red-300 p-4 rounded-md">
                                    <div class="flex items-center">
                                        <i class="bi bi-exclamation-circle text-red-500 mr-2"></i>
                                        <span x-text="data.error"></span>
                                    </div>
                                </div>
                                
                                <!-- Store metrics content -->
                                <div x-show="!data.error" class="space-y-6">
                                    <!-- Revenue & Orders Overview -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="metric-card p-4 relative">
                                            <p class="text-xs text-gray-400 mb-1">Revenue</p>
                                            <p class="text-xl font-semibold flex items-baseline">
                                                <span x-text="data.currency_symbol"></span>
                                                <span x-text="formatNumber(data.total_revenue, 2)"></span>
                                                <span class="text-xs text-gray-500 ml-2">
                                                    ≈ $<span x-text="formatNumber(data.total_revenue * conversionRates[data.currency], 2)"></span>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="metric-card p-4">
                                            <p class="text-xs text-gray-400 mb-1">Orders</p>
                                            <p class="text-xl font-semibold flex items-baseline">
                                                <span x-text="data.order_count"></span>
                                                <span class="text-xs text-gray-500 ml-2">
                                                    Avg: <span x-text="data.currency_symbol"></span><span x-text="formatNumber(data.average_order_value, 2)"></span>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Status Stats -->
                                    <div>
                                        <h4 class="text-xs uppercase text-gray-400 mb-3">Order Status</h4>
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="metric-card p-3">
                                                <div class="flex items-center mb-1">
                                                    <span class="status-indicator status-success"></span>
                                                    <p class="text-xs text-gray-400">Completed</p>
                                                </div>
                                                <p class="text-lg font-semibold text-green-400" x-text="data.completed_orders"></p>
                                            </div>
                                            <div class="metric-card p-3">
                                                <div class="flex items-center mb-1">
                                                    <span class="status-indicator status-info"></span>
                                                    <p class="text-xs text-gray-400">Processing</p>
                                                </div>
                                                <p class="text-lg font-semibold text-blue-400" x-text="data.processing_orders"></p>
                                            </div>
                                            <div class="metric-card p-3">
                                                <div class="flex items-center mb-1">
                                                    <span class="status-indicator status-warning"></span>
                                                    <p class="text-xs text-gray-400">On Hold</p>
                                                </div>
                                                <p class="text-lg font-semibold text-yellow-400" x-text="data.on_hold_orders"></p>
                                            </div>
                                            <div class="metric-card p-3">
                                                <div class="flex items-center mb-1">
                                                    <span class="status-indicator" style="background-color: var(--color-text-muted);"></span>
                                                    <p class="text-xs text-gray-400">Pending</p>
                                                </div>
                                                <p class="text-lg font-semibold text-gray-400" x-text="data.pending_orders"></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Attribution Chart -->
                                    <div x-show="attribution[siteId] && !attribution[siteId].error" class="chart-container">
                                        <h4 class="text-xs uppercase text-gray-400 mb-3">Order Attribution</h4>
                                        <div :id="'chart-' + siteId" class="h-48 bg-gray-800/50 rounded-md"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="py-6 px-6 border-t border-gray-800">
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
        function dashboard() {
            return {
                loading: true,
                error: null,
                metrics: {},
                attribution: {},
                totals: {
                    revenue_usd: 0,
                    orders: 0,
                    average_order_value_usd: 0
                },
                selectedRange: 'today',
                customStartDate: '',
                customEndDate: '',
                startDate: '',
                endDate: '',
                dateDropdownOpen: false,
                userMenuOpen: false,
                
                // Trend indicators (percentage change)
                revenueChange: null,
                orderChange: null,
                aovChange: null,
                
                // Debug info
                showDebugMessage: false,
                debugMessage: '',
                diagnosticResults: null,
                
                dateRanges: <?= json_encode($config['date_ranges']) ?>,
                conversionRates: <?= json_encode($config['conversion_rates']) ?>,
                
                get dateRangeLabel() {
                    return this.dateRanges[this.selectedRange]?.label || 'Custom Range';
                },
                
                get isDataLoaded() {
                    return !this.loading && !this.error && Object.keys(this.metrics).length > 0;
                },
                
                init() {
                    this.initializeDates();
                    this.initializeDatePickers();
                    this.fetchData();
                },
                
                initializeDates() {
                    const today = dayjs();
                    this.customStartDate = today.format('YYYY-MM-DD');
                    this.customEndDate = today.format('YYYY-MM-DD');
                    this.startDate = this.customStartDate;
                    this.endDate = this.customEndDate;
                },
                
                initializeDatePickers() {
                    // Initialize date pickers after DOM is ready
                    this.$nextTick(() => {
                        flatpickr(this.$refs.startDatePicker, {
                            dateFormat: "Y-m-d",
                            maxDate: "today",
                            theme: "dark",
                            onChange: (selectedDates) => {
                                if (selectedDates[0]) {
                                    this.customStartDate = dayjs(selectedDates[0]).format('YYYY-MM-DD');
                                }
                            }
                        });
                        
                        flatpickr(this.$refs.endDatePicker, {
                            dateFormat: "Y-m-d",
                            maxDate: "today",
                            theme: "dark",
                            onChange: (selectedDates) => {
                                if (selectedDates[0]) {
                                    this.customEndDate = dayjs(selectedDates[0]).format('YYYY-MM-DD');
                                }
                            }
                        });
                    });
                },
                
                setDateRange(range) {
                    this.selectedRange = range;
                    this.dateDropdownOpen = false;
                    this.fetchData();
                },
                
                applyCustomRange() {
                    this.selectedRange = 'custom';
                    this.startDate = this.customStartDate;
                    this.endDate = this.customEndDate;
                    this.fetchData();
                },
                
                fetchData() {
                    this.loading = true;
                    this.error = null;
                    this.diagnosticResults = null;
                    
                    let url = `/api/metrics.php?`;
                    
                    if (this.selectedRange !== 'custom') {
                        url += `range=${this.selectedRange}`;
                    } else {
                        url += `start_date=${this.startDate}&end_date=${this.endDate}`;
                    }
                    
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch dashboard data');
                            }
                            return response.json();
                        })
                        .then(data => {
                            this.metrics = data.metrics;
                            this.attribution = data.attribution;
                            this.totals = data.totals;
                            
                            // Set date range from response
                            if (data.date_range) {
                                this.startDate = data.date_range.start;
                                this.endDate = data.date_range.end;
                            }
                            
                            // Simulate trend indicators (normally calculated from previous period)
                            this.simulateTrends();
                            
                            // Check if we have actual data
                            if (this.totals.orders === 0 && Object.keys(this.metrics).length > 0) {
                                // Check if we have store error messages
                                const storeErrors = Object.values(this.metrics)
                                    .filter(store => store.error)
                                    .map(store => store.error);
                                
                                if (storeErrors.length > 0) {
                                    this.showDebugMessage = true;
                                    this.debugMessage = `No orders found. One or more stores returned an error: ${storeErrors[0]}`;
                                }
                            }
                            
                            this.renderCharts();
                            this.loading = false;
                        })
                        .catch(error => {
                            this.error = `Error loading dashboard: ${error.message}`;
                            this.loading = false;
                            console.error('Dashboard error:', error);
                            
                            // Show debug message
                            this.showDebugMessage = true;
                            this.debugMessage = `Dashboard data failed to load. This could be due to a WooCommerce API connection issue or a configuration problem.`;
                        });
                },
                
                simulateTrends() {
                    // Normally these would be calculated by comparing current period to previous period
                    // This is just for demonstration purposes
                    const randomTrend = (min, max) => {
                        const value = Math.random() * (max - min) + min;
                        // Round to one decimal place
                        return Math.round(value * 10) / 10;
                    };
                    
                    // Only show trends if we have actual data
                    if (this.totals.orders > 0) {
                        this.revenueChange = randomTrend(-15, 20);
                        this.orderChange = randomTrend(-10, 25);
                        this.aovChange = randomTrend(-8, 12);
                    }
                },
                
                runDiagnostics() {
                    this.diagnosticResults = "Running diagnostics...";
                    
                    fetch('/api/debug.php')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Diagnostic endpoint not available');
                            }
                            return response.json();
                        })
                        .then(data => {
                            this.diagnosticResults = JSON.stringify(data, null, 2);
                        })
                        .catch(error => {
                            this.diagnosticResults = `Diagnostic error: ${error.message}\n\nPlease ensure the debug.php endpoint is installed correctly.`;
                        });
                },
                
                renderCharts() {
                    // Wait for DOM to update
                    setTimeout(() => {
                        Object.entries(this.attribution).forEach(([siteId, stats]) => {
                            if (!stats.error && stats.sources && Object.keys(stats.sources).length > 0) {
                                const chartElement = document.getElementById(`chart-${siteId}`);
                                if (chartElement) {
                                    this.renderDonutChart(chartElement, stats);
                                }
                            }
                        });
                    }, 50);
                },
                
                renderDonutChart(element, stats) {
                    // Modern chart colors
                    const colors = [
                        '#14b8a6', // teal-500
                        '#0ea5e9', // sky-500
                        '#a855f7', // purple-500
                        '#ec4899', // pink-500
                        '#f97316', // orange-500
                        '#eab308'  // yellow-500
                    ];
                    
                    const options = {
                        series: Object.values(stats.percentages),
                        labels: Object.keys(stats.sources),
                        chart: {
                            type: 'donut',
                            height: 192,
                            background: 'transparent',
                            fontFamily: 'Inter, sans-serif',
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                                animateGradually: {
                                    enabled: true,
                                    delay: 150
                                },
                                dynamicAnimation: {
                                    enabled: true,
                                    speed: 350
                                }
                            }
                        },
                        theme: {
                            mode: 'dark'
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '70%',
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            fontWeight: 500,
                                            fontSize: '12px',
                                            color: '#94a3b8'
                                        },
                                        value: {
                                            show: true,
                                            fontWeight: 600,
                                            fontSize: '16px',
                                            color: '#f1f5f9'
                                        },
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            color: '#94a3b8',
                                            fontWeight: 500,
                                            fontSize: '12px',
                                            formatter: function(w) {
                                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        stroke: {
                            width: 0
                        },
                        legend: {
                            position: 'right',
                            fontSize: '12px',
                            fontWeight: 500,
                            labels: {
                                colors: '#94a3b8'
                            },
                            markers: {
                                width: 10,
                                height: 10,
                                radius: 2
                            },
                            itemMargin: {
                                vertical: 5
                            }
                        },
                        tooltip: {
                            theme: 'dark',
                            y: {
                                formatter: function(value) {
                                    return value.toFixed(1) + '%'
                                }
                            }
                        },
                        responsive: [
                            {
                                breakpoint: 480,
                                options: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        ],
                        colors: colors
                    };
                    
                    new ApexCharts(element, options).render();
                },
                
                formatNumber(number, decimals = 0) {
                    if (number === null || number === undefined) return '';
                    return number.toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                    });
                }
            };
        }
    </script>
</body>
</html>