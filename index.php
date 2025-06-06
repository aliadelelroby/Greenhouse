<?php
// Start session and check authentication
session_start();

// Check if user is logged in
$isAuthenticated = false;
$currentUser = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_login'])) {
    $isAuthenticated = true;
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'login' => $_SESSION['user_login'],
        'type' => $_SESSION['user_type'] ?? 0,
        'company_id' => $_SESSION['company_id'] ?? null
    ];
}

// Redirect to login if not authenticated
if (!$isAuthenticated) {
    header('Location: login.php');
    exit();
}

// Initialize permission service for tab visibility
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/services/PermissionService.php';

try {
    $database = Database::getInstance();
    $permissionService = new PermissionService($database);
} catch (Exception $e) {
    error_log("Permission service error: " . $e->getMessage());
    $permissionService = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermeleon Interface Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Luxon (date library) -->
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
    <!-- Chart.js adapter for Luxon -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1"></script>
    
    <!-- Air Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/air-datepicker@3.6.0/air-datepicker.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'thermeleon': {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .pill-close {
            margin-left: 0.5rem;
            cursor: pointer;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
        
        /* Custom Air Datepicker styling to match Tailwind theme */
        .air-datepicker {
            --adp-background-color: #ffffff;
            --adp-border-color: #d1d5db;
            --adp-border-color-inner: #e5e7eb;
            --adp-border-radius: 8px;
            --adp-box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --adp-font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            --adp-font-size: 14px;
            --adp-accent-color: #22c55e;
            --adp-color: #374151;
            --adp-color-secondary: #6b7280;
            --adp-color-other-month: #9ca3af;
            --adp-color-disabled: #d1d5db;
            --adp-color-current-date: #22c55e;
            --adp-day-name-color: #6b7280;
            --adp-day-cell-hover: #f3f4f6;
            --adp-pointer-size: 8px;
            border: 1px solid var(--adp-border-color);
        }
        
        .air-datepicker .air-datepicker--cell.-selected- {
            background: #22c55e;
            color: white;
        }
        
        .air-datepicker .air-datepicker--cell.-selected-:hover {
            background: #16a34a;
        }
        
        .air-datepicker .air-datepicker--button {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            color: #374151;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .air-datepicker .air-datepicker--button:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .air-datepicker .air-datepicker--button[data-action="today"] {
            background: #22c55e;
            color: white;
            border-color: #22c55e;
        }
        
        .air-datepicker .air-datepicker--button[data-action="today"]:hover {
            background: #16a34a;
            border-color: #16a34a;
        }
        
        .air-datepicker .air-datepicker--nav {
            border-bottom: 1px solid #e5e7eb;
            padding: 16px;
        }
        
        .air-datepicker .air-datepicker--nav-title {
            color: #111827;
            font-weight: 600;
        }
        
        .air-datepicker .air-datepicker--nav-action {
            color: #6b7280;
            border-radius: 6px;
        }
        
        .air-datepicker .air-datepicker--nav-action:hover {
            background: #f3f4f6;
            color: #374151;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Thermeleon Dashboard</h1>
                <p class="text-gray-600">Monitor and manage your greenhouse data</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?></span>
                    <?php if ($currentUser['type'] > 0): ?>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                            <?php echo $currentUser['type'] == 1 ? 'Admin' : 'Super Admin'; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <button onclick="logout()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </button>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar Navigation -->
            <nav class="lg:w-1/4 w-full">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Navigation</h2>
                    <ul class="space-y-2">
                        <li>
                            <button onclick="showTab('greenhouse')" 
                                    class="tab-btn w-full text-left px-4 py-3 rounded-lg transition-colors duration-200 bg-thermeleon-500 text-white" 
                                    data-tab="greenhouse">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21l4-4 4 4"></path>
                                    </svg>
                                    My Greenhouse
                                </div>
                            </button>
                        </li>
                        <li>
                            <button onclick="showTab('presales')" 
                                    class="tab-btn w-full text-left px-4 py-3 rounded-lg transition-colors duration-200 text-gray-700 hover:bg-gray-100" 
                                    data-tab="presales">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2V7a2 2 0 012-2h2a2 2 0 002 2v2a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 00-2 2h-2a2 2 0 00-2 2v6a2 2 0 01-2 2H9z"></path>
                                    </svg>
                                    Pre-Sales Tools
                                </div>
                            </button>
                        </li>
                        <li>
                            <button onclick="showTab('manager')" 
                                    class="tab-btn w-full text-left px-4 py-3 rounded-lg transition-colors duration-200 text-gray-700 hover:bg-gray-100" 
                                    data-tab="manager">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Greenhouse Manager
                                </div>
                            </button>
                        </li>
                        <?php if ($permissionService && $permissionService->canAccessPlatformManagement()): ?>
                        <li>
                            <button onclick="showTab('platform')" 
                                    class="tab-btn w-full text-left px-4 py-3 rounded-lg transition-colors duration-200 text-gray-700 hover:bg-gray-100" 
                                    data-tab="platform">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                    </svg>
                                    Platform Manager
                                </div>
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="lg:w-3/4 w-full">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 min-h-[600px]">
                    <!-- Tab Contents -->
                    <div id="greenhouse-content" class="tab-content active p-6">
                        <div class="flex items-center justify-center h-96">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-thermeleon-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-600">Loading greenhouse data...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="presales-content" class="tab-content p-6">
                        <div class="flex items-center justify-center h-96">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-thermeleon-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-600">Loading pre-sales tools...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="manager-content" class="tab-content p-6">
                        <div class="flex items-center justify-center h-96">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-thermeleon-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-600">Loading greenhouse manager...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="platform-content" class="tab-content p-6">
                        <div class="flex items-center justify-center h-96">
                            <div class="text-center">
                                <svg class="animate-spin h-8 w-8 text-thermeleon-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-600">Loading platform manager...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Global Dialog System -->
    <div id="globalDialog" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-[60]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" id="dialogTitle">Confirmation</h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-700" id="dialogMessage">Are you sure?</p>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button id="dialogCancel" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button id="dialogConfirm" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Notification System -->
    <div id="globalNotification" class="fixed top-4 right-4 hidden z-[70]">
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 min-w-80 max-w-md w-auto">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div id="notificationIcon" class="w-6 h-6 rounded-full flex items-center justify-center">
                            <!-- Icon will be inserted here -->
                        </div>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 break-words" id="notificationTitle">Notification</p>
                        <p class="mt-1 text-sm text-gray-500 break-words" id="notificationMessage">Message content</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button id="closeNotification" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global Dialog System
        class DialogSystem {
            constructor() {
                this.dialog = document.getElementById('globalDialog');
                this.title = document.getElementById('dialogTitle');
                this.message = document.getElementById('dialogMessage');
                this.cancelBtn = document.getElementById('dialogCancel');
                this.confirmBtn = document.getElementById('dialogConfirm');
                this.notification = document.getElementById('globalNotification');
                this.notificationTitle = document.getElementById('notificationTitle');
                this.notificationMessage = document.getElementById('notificationMessage');
                this.notificationIcon = document.getElementById('notificationIcon');
                this.closeNotificationBtn = document.getElementById('closeNotification');
                
                this.initializeEventListeners();
            }
            
            initializeEventListeners() {
                this.cancelBtn.addEventListener('click', () => this.hideDialog());
                this.closeNotificationBtn.addEventListener('click', () => this.hideNotification());
                
                // Close dialog when clicking outside
                this.dialog.addEventListener('click', (e) => {
                    if (e.target === this.dialog) {
                        this.hideDialog();
                    }
                });
            }
            
            showDialog(title, message, onConfirm = null, confirmText = 'Confirm', cancelText = 'Cancel') {
                this.title.textContent = title;
                this.message.textContent = message;
                this.confirmBtn.textContent = confirmText;
                this.cancelBtn.textContent = cancelText;
                
                // Remove previous event listeners
                const newConfirmBtn = this.confirmBtn.cloneNode(true);
                this.confirmBtn.parentNode.replaceChild(newConfirmBtn, this.confirmBtn);
                this.confirmBtn = newConfirmBtn;
                
                // Add new event listener
                this.confirmBtn.addEventListener('click', () => {
                    this.hideDialog();
                    if (onConfirm) onConfirm();
                });
                
                this.dialog.classList.remove('hidden');
                return new Promise((resolve) => {
                    const handleConfirm = () => {
                        this.hideDialog();
                        resolve(true);
                    };
                    const handleCancel = () => {
                        this.hideDialog();
                        resolve(false);
                    };
                    
                    this.confirmBtn.onclick = handleConfirm;
                    this.cancelBtn.onclick = handleCancel;
                });
            }
            
            hideDialog() {
                this.dialog.classList.add('hidden');
            }
            
            showNotification(title, message, type = 'info', duration = 5000) {
                this.notificationTitle.textContent = title;
                this.notificationMessage.textContent = message;
                
                // Set icon based on type
                let iconHTML = '';
                let iconClass = '';
                
                switch(type) {
                    case 'success':
                        iconClass = 'bg-green-100';
                        iconHTML = '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                        break;
                    case 'error':
                        iconClass = 'bg-red-100';
                        iconHTML = '<svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>';
                        break;
                    case 'warning':
                        iconClass = 'bg-yellow-100';
                        iconHTML = '<svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
                        break;
                    default:
                        iconClass = 'bg-blue-100';
                        iconHTML = '<svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
                }
                
                this.notificationIcon.className = `w-6 h-6 rounded-full flex items-center justify-center ${iconClass}`;
                this.notificationIcon.innerHTML = iconHTML;
                
                this.notification.classList.remove('hidden');
                
                if (duration > 0) {
                    setTimeout(() => {
                        this.hideNotification();
                    }, duration);
                }
            }
            
            hideNotification() {
                this.notification.classList.add('hidden');
            }
        }
        
        // Initialize global dialog system
        const dialogSystem = new DialogSystem();
        
        // Tab content cache to avoid reloading
        const tabContentCache = {};
        
        // Tab switching functionality with lazy loading
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active state from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-thermeleon-500', 'text-white');
                btn.classList.add('text-gray-700', 'hover:bg-gray-100');
            });
            
            // Show selected tab content
            const selectedContent = document.getElementById(tabName + '-content');
            if (selectedContent) {
                selectedContent.classList.add('active');
                
                // Load content if not already loaded
                if (!tabContentCache[tabName]) {
                    loadTabContent(tabName);
                }
            }
            
            // Activate selected tab button
            const selectedButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (selectedButton) {
                selectedButton.classList.remove('text-gray-700', 'hover:bg-gray-100');
                selectedButton.classList.add('bg-thermeleon-500', 'text-white');
            }
        }
        
        // Function to load tab content via AJAX
        async function loadTabContent(tabName) {
            const tabElement = document.getElementById(tabName + '-content');
            if (!tabElement) return;
            
            try {
                // Show loading spinner (already in place)
                const response = await fetch(`tabs/${tabName}.php`);
                if (!response.ok) {
                    throw new Error(`Failed to load ${tabName} content`);
                }
                
                const content = await response.text();
                tabElement.innerHTML = content;
                tabContentCache[tabName] = true;
                
                // Execute any scripts that might be in the loaded content
                const scripts = tabElement.querySelectorAll('script');
                scripts.forEach(script => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    document.body.appendChild(newScript);
                });
                
                // Trigger any initialization that might be needed for this tab
                if (tabName === 'greenhouse') {
                    // Re-initialize greenhouse functionality after content is loaded
                    setTimeout(() => {
                        if (typeof initializeGreenhouseTab === 'function') {
                            initializeGreenhouseTab();
                        }
                    }, 100);
                } else if (tabName === 'manager') {
                    // Initialize manager tab if needed
                    setTimeout(() => {
                        if (typeof initializeManagerTab === 'function') {
                            initializeManagerTab();
                        }
                    }, 100);
                } else if (tabName === 'platform') {
                    // Initialize platform tab if needed
                    setTimeout(() => {
                        if (typeof initializePlatformTab === 'function') {
                            initializePlatformTab();
                        }
                    }, 100);
                }
                
            } catch (error) {
                console.error(`Error loading ${tabName} content:`, error);
                tabElement.innerHTML = `
                    <div class="flex items-center justify-center h-96">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <p class="text-lg text-red-600 mb-2">Failed to load content</p>
                            <p class="text-gray-600">Please try refreshing the page</p>
                        </div>
                    </div>
                `;
            }
        }

        // Global helper functions for backward compatibility
        function showConfirmDialog(message, onConfirm, title = 'Confirmation') {
            return dialogSystem.showDialog(title, message, onConfirm);
        }
        
        function showNotification(message, type = 'info', title = 'Notification') {
            dialogSystem.showNotification(title, message, type);
        }
        
        function showSuccessNotification(message, title = 'Success') {
            dialogSystem.showNotification(title, message, 'success');
        }
        
        function showErrorNotification(message, title = 'Error') {
            dialogSystem.showNotification(title, message, 'error');
        }
        
        function showWarningNotification(message, title = 'Warning') {
            dialogSystem.showNotification(title, message, 'warning');
        }
        
        // Logout function
        async function logout() {
            try {
                const response = await fetch('api/auth.php', {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'login.php';
                } else {
                    showErrorNotification('Logout failed. Please try again.', 'Logout Error');
                }
            } catch (error) {
                console.error('Logout error:', error);
                // Force redirect even if API call fails
                window.location.href = 'login.php';
            }
        }
        
        // Initialize the default tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load the greenhouse tab by default
            loadTabContent('greenhouse');
        });
    </script>
    
    <!-- Import main JavaScript file -->
    <script src="js/app.js"></script>
</body>
</html> 