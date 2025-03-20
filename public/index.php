<?php
/**
 * Main dashboard page
 */
require_once __DIR__ . '/../src/bootstrap_new.php';

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
    <title>WooCommerce Multi-Store Dashboard</title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.11.7/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
        
        .metric-card {
            background-color: #111827;
            border: 1px solid #374151;
        }
        
        .dark-input {
            background-color: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }
        
        .dark-input:focus {
            border-color: #60a5fa;
        }
        
        .dark-dropdown {
            background-color: #1f2937;
            border: 1px solid #374151;
        }
        
        .hover-dark:hover {
            background-color: #374151;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div x-data="dashboard()" x-init="init()" class="container mx-auto px-4 py-8">
        <!-- Top navigation bar -->
        <nav class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">WooCommerce Dashboard</h1>
            
            <div class="flex items-center space-x-4">
                <span class="text-gray-400">Welcome, <?= htmlspecialchars($user['username']) ?></span>
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

        <!-- Header with Date Range Selector -->
        <div class="dark-card rounded-lg shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h2 class="text-2xl font-bold mb-4 md:mb-0">Store Metrics</h2>
                
                <div class="relative inline-block text-left">
                    <button @click="dateDropdownOpen = !dateDropdownOpen" type="button" 
                            class="inline-flex justify-between items-center w-full rounded-md border border-gray-600 px-4 py-2 bg-gray-800 text-sm font-medium text-gray-200 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-gray-900">
                        <span x-text="dateRangeLabel"></span>
                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="dateDropdownOpen" 
                         @click.away="dateDropdownOpen = false"
                         class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg dark-dropdown divide-y divide-gray-700 z-50"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         x-cloak>
                        <div class="p-4">
                            <h3 class="text-lg font-medium text-gray-200 mb-4">Presets</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="(range, key) in dateRanges" :key="key">
                                    <a href="#" 
                                       @click.prevent="setDateRange(key)"
                                       :class="{'bg-blue-900 text-blue-100': selectedRange === key, 'text-gray-300 hover:bg-gray-800': selectedRange !== key}"
                                       class="px-3 py-2 rounded-md text-sm">
                                        <span x-text="range.label"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="text-lg font-medium text-gray-200 mb-4">Custom Range</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300">Start Date</label>
                                    <input type="date" 
                                           x-model="customStartDate"
                                           x-ref="startDatePicker"
                                           class="mt-1 block w-full rounded-md dark-input px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300">End Date</label>
                                    <input type="date" 
                                           x-model="customEndDate"
                                           x-ref="endDatePicker"
                                           class="mt-1 block w-full rounded-md dark-input px-3 py-2">
                                </div>
                                <button @click="applyCustomRange()" 
                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    Apply Range
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loading" class="flex justify-center items-center py-12">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg text-gray-400">Loading dashboard data...</span>
            </div>

            <!-- Overall Metrics -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div class="metric-card rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-400 mb-2">Total Revenue (USD)</h3>
                    <p class="text-4xl font-bold text-blue-400">
                        $<span x-text="formatNumber(totals.revenue_usd)"></span>
                    </p>
                </div>
                <div class="metric-card rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-400 mb-2">Total Orders</h3>
                    <p class="text-4xl font-bold text-blue-400">
                        <span x-text="formatNumber(totals.orders)"></span>
                    </p>
                </div>
                <div class="metric-card rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-400 mb-2">Average Order Value (USD)</h3>
                    <p class="text-4xl font-bold text-blue-400">
                        $<span x-text="formatNumber(totals.average_order_value_usd, 2)"></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div x-show="error" class="bg-red-800 text-white p-4 rounded-lg mb-6">
            <p x-text="error"></p>
        </div>

        <!-- Store Cards -->
        <div x-show="!loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="(data, siteId) in metrics" :key="siteId">
                <div class="dark-card rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-200" x-text="siteId"></h2>
                        <a :href="`store-orders.php?store=${encodeURIComponent(siteId)}&start_date=${startDate}&end_date=${endDate}`" 
                           class="text-sm text-blue-400 hover:text-blue-300">
                            View Orders →
                        </a>
                    </div>

                    <div x-show="data.error" class="text-red-400" x-text="data.error"></div>

                    <div x-show="!data.error" class="grid grid-cols-2 gap-6">
                        <!-- Revenue Section -->
                        <div class="col-span-2 bg-gray-800 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-400 text-sm">Revenue</p>
                                    <p class="text-2xl font-bold text-gray-200">
                                        <span x-text="data.currency_symbol"></span><span x-text="formatNumber(data.total_revenue, 2)"></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        ≈ $<span x-text="formatNumber(data.total_revenue * conversionRates[data.currency], 2)"></span> USD
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-400 text-sm">Orders</p>
                                    <p class="text-2xl font-bold text-gray-200" x-text="data.order_count"></p>
                                    <p class="text-sm text-gray-500">
                                        Avg: <span x-text="data.currency_symbol"></span><span x-text="formatNumber(data.average_order_value, 2)"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status Section -->
                        <div class="col-span-2 grid grid-cols-4 gap-4">
                            <div class="bg-gray-800 rounded-lg p-3">
                                <p class="text-gray-400 text-sm">Completed</p>
                                <p class="text-xl font-bold text-green-400" x-text="data.completed_orders"></p>
                            </div>
                            <div class="bg-gray-800 rounded-lg p-3">
                                <p class="text-gray-400 text-sm">Processing</p>
                                <p class="text-xl font-bold text-blue-400" x-text="data.processing_orders"></p>
                            </div>
                            <div class="bg-gray-800 rounded-lg p-3">
                                <p class="text-gray-400 text-sm">On Hold</p>
                                <p class="text-xl font-bold text-yellow-400" x-text="data.on_hold_orders"></p>
                            </div>
                            <div class="bg-gray-800 rounded-lg p-3">
                                <p class="text-gray-400 text-sm">Pending</p>
                                <p class="text-xl font-bold text-gray-400" x-text="data.pending_orders"></p>
                            </div>
                        </div>

                        <!-- Attribution Chart Section -->
                        <div class="col-span-2" x-show="attribution[siteId] && !attribution[siteId].error">
                            <h3 class="text-lg font-medium text-gray-300 mb-4">Order Attribution</h3>
                            <div class="bg-gray-800 rounded-lg p-4">
                                <div :id="'chart-' + siteId" class="h-48"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Footer -->
        <footer class="mt-12 text-center text-gray-500 text-sm">
            <p>WooCommerce Multi-Store Dashboard &copy; <?= date('Y') ?></p>
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
                
                dateRanges: <?= json_encode($config['date_ranges']) ?>,
                
                conversionRates: <?= json_encode($config['conversion_rates']) ?>,
                
                get dateRangeLabel() {
                    return this.dateRanges[this.selectedRange]?.label || 'Custom Range';
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
                    this.dateDropdownOpen = false;
                    this.fetchData();
                },
                
                fetchData() {
                    this.loading = true;
                    this.error = null;
                    
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
                            this.renderCharts();
                            this.loading = false;
                        })
                        .catch(error => {
                            this.error = `Error loading dashboard: ${error.message}`;
                            this.loading = false;
                            console.error('Dashboard error:', error);
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
                    const options = {
                        series: Object.values(stats.percentages),
                        labels: Object.keys(stats.sources),
                        chart: {
                            type: 'donut',
                            height: 192,
                            background: 'transparent',
                        },
                        theme: {
                            mode: 'dark'
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '70%'
                                }
                            }
                        },
                        legend: {
                            position: 'right',
                            labels: {
                                colors: '#e5e7eb'
                            },
                            fontSize: '12px'
                        },
                        tooltip: {
                            y: {
                                formatter: function(value) {
                                    return value.toFixed(1) + '%'
                                }
                            }
                        },
                        colors: ['#60A5FA', '#34D399', '#F472B6', '#FBBF24', '#A78BFA', '#F87171']
                    };
                    
                    new ApexCharts(element, options).render();
                },
                
                formatNumber(number, decimals = 0) {
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