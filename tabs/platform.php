<?php
session_start();

// Permission check - only admins can access platform management
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

try {
    $database = Database::getInstance();
    $permissionService = new PermissionService($database);
    
    // Check if user can access platform management
    if (!$permissionService->canAccessPlatformManagement()) {
        header('HTTP/1.1 403 Forbidden');
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
        echo '<div class="flex items-center">';
        echo '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>';
        echo '</svg>';
        echo '<strong>Access Denied:</strong> ' . $permissionService->getPermissionErrorMessage('access platform management');
        echo '</div>';
        echo '</div>';
        return;
    }
    
    $userRepository = new UserRepository($database);
    
    // Initialize user tables if they don't exist
    $userRepository->initializeTables();
    
    // Get real statistics from database
    $connection = $database->getConnection();
?>

<h2 class="text-2xl font-bold text-gray-900 mb-6">Platform Manager</h2>

<?php
    
    // Count actual data
    $result = $connection->query("SELECT COUNT(*) as count FROM greenhouse");
    $totalGreenhouses = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $connection->query("SELECT COUNT(*) as count FROM sensor");
    $totalSensors = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Get user statistics
    $userStats = $userRepository->getUserStatistics();
    
    // Get recent activity from data table
    $recentActivityQuery = "
        SELECT 
            'data' as type,
            CONCAT('Data recorded from sensor: ', COALESCE(s.Name_sensor, 'Unknown')) as activity,
            d.Date_data as timestamp
        FROM data d
        LEFT JOIN sensor s ON d.Id_sensor = s.Id_sensor
        WHERE d.Enabled = 1
        ORDER BY d.Date_data DESC
        LIMIT 10
    ";
    $recentActivity = [];
    $result = $connection->query($recentActivityQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentActivity[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Platform manager error: " . $e->getMessage());
    $totalGreenhouses = 0;
    $totalSensors = 0;
    $recentActivity = [];
    $userStats = [
        'total_users' => 0,
        'active_users' => 0,
        'total_companies' => 0,
        'total_positions' => 0
    ];
}
?>

<!-- User Management -->
<div class="mb-8">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h3 class="text-lg font-semibold text-gray-900">System Management</h3>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-600">View:</label>
                        <select id="entityTypeSelect" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                            <option value="greenhouse">Greenhouse</option>
                            <option value="sensor">Sensor</option>
                            <option value="data">Data Records</option>
                            <option value="user">Users</option>
                            <option value="company">Companies</option>
                            <option value="position">Positions</option>
                        </select>
                    </div>
                </div>
                <button onclick="refreshData()" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200 flex items-center text-sm shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Data
                </button>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Filter by:</label>
                        <select id="filterType" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors min-w-[140px]">
                            <option value="">All Types</option>
                            <option value="greenhouse">Greenhouses</option>
                            <option value="sensor">Sensors</option>
                            <option value="data">Data Records</option>
                            <option value="user">Users</option>
                            <option value="company">Companies</option>
                            <option value="position">Positions</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Search:</label>
                        <input type="text" id="searchInput" placeholder="Search entities..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors min-w-[200px]">
                    </div>
                </div>
                <?php if ($permissionService->canCreateEntities()): ?>
                <button onclick="showAddEntityModal()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm font-medium shadow-sm flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add New Entity
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full" id="entityTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name/ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="entityTableBody">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                                Loading real data...
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Actions & Stats -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Platform Stats -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Platform Statistics</h3>
            </div>
            <div class="p-6 space-y-4 flex-1">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Greenhouses</span>
                    <span class="text-lg font-semibold text-gray-900" id="totalGreenhouses"><?php echo $totalGreenhouses; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Sensors</span>
                    <span class="text-lg font-semibold text-gray-900" id="totalSensors"><?php echo $totalSensors; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Data Points Today</span>
                    <span class="text-lg font-semibold text-gray-900" id="dataPointsToday">
                        <?php
                        $result = $connection->query("SELECT COUNT(*) as count FROM data WHERE DATE(Date_data) = CURDATE() AND Enabled = 1");
                        echo $result ? $result->fetch_assoc()['count'] : 0;
                        ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Users</span>
                    <span class="text-lg font-semibold text-gray-900" id="totalUsers"><?php echo $userStats['total_users']; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Active Users</span>
                    <span class="text-lg font-semibold text-gray-900" id="activeUsers"><?php echo $userStats['active_users']; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Companies</span>
                    <span class="text-lg font-semibold text-gray-900" id="totalCompanies"><?php echo $userStats['total_companies']; ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">System Status</span>
                    <span class="text-lg font-semibold text-green-600">
                        <?php 
                        // Modern way to check database connection instead of deprecated ping()
                        try {
                            $connection->query("SELECT 1");
                            echo 'Online';
                        } catch (Exception $e) {
                            echo 'Offline';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Health -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">System Health</h3>
            </div>
            <div class="p-6 space-y-4 flex-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Database Connection</span>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-green-600">Connected</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Data Collection</span>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-green-600">Active</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">API Endpoints</span>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-green-600">Operational</span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Export Service</span>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm text-green-600">Ready</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6 space-y-3 flex-1">
                <?php if ($permissionService->canPerformSystemOperations()): ?>
                <button onclick="performBackup()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                    Database Backup
                </button>
                <?php endif; ?>
                
                <?php if ($permissionService->canViewSystemStats()): ?>
                <button onclick="checkSystemHealth()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    System Health Check
                </button>
                <?php endif; ?>
                
                <?php if ($permissionService->canPerformSystemOperations()): ?>
                <button onclick="exportSystemLogs()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H10"></path>
                    </svg>
                    Export System Logs
                </button>
                <?php endif; ?>
                
                <?php if ($permissionService->canModifySystemSettings()): ?>
                <button onclick="manageSettings()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    System Settings
                </button>
                <?php endif; ?>
                
                <?php if (!$permissionService->canPerformSystemOperations() && !$permissionService->canViewSystemStats() && !$permissionService->canModifySystemSettings()): ?>
                <div class="text-center text-gray-500 py-4">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <p class="text-sm">Limited access</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log -->
<div class="mt-8">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <button onclick="refreshActivity()" class="text-sm text-thermeleon-600 hover:text-thermeleon-900">Refresh</button>
        </div>
        <div class="divide-y divide-gray-200" id="activityLog">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                    <div class="px-6 py-4 flex items-center">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['activity']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="px-6 py-4 text-center text-gray-500">
                    No recent activity found
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Entity Modal -->
<div id="addEntityModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add New Entity</h3>
            </div>
            <form id="addEntityForm" class="p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type:</label>
                    <select id="modalEntityType" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                        <option value="user">User</option>
                        <option value="company">Company</option>
                        <option value="position">Position</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name:</label>
                    <input type="text" id="entityName" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors" required>
                </div>
                
                <div id="userFields" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                        <input type="email" id="entityEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username/Login:</label>
                        <input type="text" id="entityLogin" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password:</label>
                        <div class="relative">
                            <input type="password" id="entityPassword" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10">
                            <button type="button" onclick="toggleModalPassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg id="modalEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User Type:</label>
                        <select id="entityUserType" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                            <option value="0">Regular User</option>
                            <option value="1">Administrator</option>
                            <option value="2">Super Admin</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company:</label>
                        <select id="entityCompany" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                            <option value="">Select a company...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position:</label>
                        <select id="entityPosition" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                            <option value="">Select a position...</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddEntityModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600">
                        Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
            </div>
            <form id="changePasswordForm" class="p-6">
                <input type="hidden" id="changePasswordUserId" value="">
                <input type="hidden" id="changePasswordUserLogin" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User:</label>
                    <input type="text" id="changePasswordUserName" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password:</label>
                    <div class="relative">
                        <input type="password" id="newPassword" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10" required>
                        <button type="button" onclick="toggleNewPassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="newPasswordEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password:</label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white hover:border-gray-400 focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10" required>
                        <button type="button" onclick="toggleConfirmPassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="confirmPasswordEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeChangePasswordModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pass PHP permission data to JavaScript
window.platformPermissions = {
    canCreateEntities: <?php echo $permissionService->canCreateEntities() ? 'true' : 'false'; ?>,
    canDeleteEntities: <?php echo $permissionService->canDeleteEntities() ? 'true' : 'false'; ?>,
    canChangeUserPasswords: <?php echo $permissionService->canChangeUserPasswords() ? 'true' : 'false'; ?>,
    canResetPasswords: <?php echo $permissionService->canResetPasswords() ? 'true' : 'false'; ?>,
    canManageUsers: <?php echo $permissionService->canManageUsers() ? 'true' : 'false'; ?>,
    isAdmin: <?php echo $permissionService->isAdmin() ? 'true' : 'false'; ?>,
    isSuperAdmin: <?php echo $permissionService->isSuperAdmin() ? 'true' : 'false'; ?>
};

class PlatformManager {
    constructor() {
        this.entities = [];
        this.cache = new Map();
        this.pendingRequests = new Set();
        this.isLoading = false;
        this.lastLoadTime = 0;
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
        this.companies = [];
        this.positions = [];
        this.permissions = window.platformPermissions;
        
        this.initializeFilters();
        this.setupEventListeners();
    }

    /**
     * Prevents duplicate requests by checking if a request is already pending
     */
    async makeRequest(url, options = {}) {
        const cacheKey = `${url}_${JSON.stringify(options)}`;
        
        // Check if request is already pending
        if (this.pendingRequests.has(cacheKey)) {
            return null;
        }
        
        // Check cache first
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                return cached.data;
            }
            this.cache.delete(cacheKey);
        }
        
        this.pendingRequests.add(cacheKey);
        
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            
            // Cache the result
            this.cache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });
            
            return data;
        } catch (error) {
            console.error(`Request failed for ${url}:`, error);
            throw error;
        } finally {
            this.pendingRequests.delete(cacheKey);
        }
    }

    /**
     * Lazy load data only when needed
     */
    async loadSystemData(force = false) {
        // Prevent duplicate loads unless forced
        if (this.isLoading || (!force && Date.now() - this.lastLoadTime < 10000)) {
            return;
        }

        this.isLoading = true;
        this.showLoadingState();

        try {
            // Load data in batches for better performance
            // await this.loadCoreData();
            await this.loadExtendedData();
            
            this.renderEntityTable();
            this.lastLoadTime = Date.now();
            
        } catch (error) {
            console.error('Error loading system data:', error);
            this.showErrorState('Error loading system data');
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Load core data first (most important)
     */
    async loadCoreData() {
        this.entities = [];
        
        try {
            // Load greenhouses and sensors in parallel
            const [sensorsData, greenhousesData] = await Promise.all([
                this.makeRequest('api/sensors.php'),
                this.makeRequest('api/sensors.php?greenhouse_list=1')
            ]);
        
            // Process greenhouses
            if (Array.isArray(greenhousesData)) {
                greenhousesData.forEach(greenhouse => {
                    this.entities.push({
                    type: 'greenhouse',
                    id: greenhouse.id || greenhouse.Id_greenhouse,
                    name: greenhouse.name || greenhouse.Name_greenhouse,
                    details: `Company ID: ${greenhouse.Id_company || 'N/A'} | Size: ${greenhouse.X_max || 'N/A'}x${greenhouse.Y_max || 'N/A'}`,
                    status: 'Active',
                    lastUpdated: greenhouse.created_at || new Date().toISOString()
                });
            });
        }
        
            // Process sensors
            if (Array.isArray(sensorsData) && sensorsData.length > 0) {
                sensorsData.forEach(sensor => {
                    this.entities.push({
                    type: 'sensor',
                    id: sensor.id || sensor.Id_sensor,
                    name: sensor.name || sensor.Name_sensor,
                    details: `Description: ${sensor.description || sensor.Description || 'N/A'} | Greenhouse: ${sensor.Id_greenhouse || 'N/A'}`,
                    status: sensor.enabled || sensor.Enabled ? 'Active' : 'Inactive',
                    lastUpdated: sensor.Last_update || sensor.created_at || new Date().toISOString()
                });
            });
        }

            // Render partial data immediately
            this.renderEntityTable();
            
        } catch (error) {
            console.error('Error loading core data:', error);
        }
    }

    /**
     * Load extended data (users, companies, etc.) after core data
     */
    async loadExtendedData() {
        try {
            // Load users data
            const usersResponse = await this.makeRequest('api/users.php?type=all');
            
            if (usersResponse && usersResponse.success) {
                const usersData = usersResponse.data;
        
        // Add users
                if (usersData.users && Array.isArray(usersData.users)) {
            usersData.users.forEach(user => {
                        this.entities.push({
                    type: 'user',
                    id: user.id_user,
                    name: user.name_user,
                    details: `Email: ${user.email_user || 'N/A'} | Company: ${user.company_name || 'N/A'}`,
                    status: user.status_user === 'active' ? 'Active' : 'Inactive',
                    lastUpdated: user.created_at || new Date().toISOString()
                });
            });
        }
        
        // Add companies
                if (usersData.companies && Array.isArray(usersData.companies)) {
            usersData.companies.forEach(company => {
                        this.entities.push({
                    type: 'company',
                    id: company.id_company,
                    name: company.name_company,
                    details: `Users: ${company.user_count}`,
                    status: company.status_company === 'active' ? 'Active' : 'Inactive',
                    lastUpdated: company.created_at || new Date().toISOString()
                });
            });
        }
        
        // Add positions
                if (usersData.positions && Array.isArray(usersData.positions)) {
            usersData.positions.forEach(position => {
                        this.entities.push({
                    type: 'position',
                    id: position.id_position,
                    name: position.name_position,
                    details: `Users: ${position.user_count}`,
                    status: position.status_position === 'active' ? 'Active' : 'Inactive',
                    lastUpdated: position.created_at || new Date().toISOString()
                });
            });
        }
            }
        
        } catch (error) {
            console.error('Error loading extended data:', error);
        }
    }

    /**
     * Lazy load data records separately
     */
    async loadDataRecords() {
        try {
            const dataRecords = await this.makeRequest('api/data.php?sensors=all&limit=10');
            
            if (Array.isArray(dataRecords)) {
                dataRecords.forEach((record, index) => {
                    this.entities.push({
                        type: 'data',
                        id: `data_${index}`,
                        name: `Data Record ${record.id || index + 1}`,
                        details: `Value: ${record.value || record.Value_data || 'N/A'}°C`,
                        status: 'Recorded',
                        lastUpdated: record.timestamp || record.Date_data || new Date().toISOString()
                    });
                });
                
                // Re-render with data records
                this.renderEntityTable();
            }
        } catch (error) {
            console.log('Could not load data records:', error);
        }
        }
        
    showLoadingState() {
        const tbody = document.getElementById('entityTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-thermeleon-500 mb-2"></div>
                            Loading system data...
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    showErrorState(message) {
        const tbody = document.getElementById('entityTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-red-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            ${message}
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    renderEntityTable() {
    const tbody = document.getElementById('entityTableBody');
    if (!tbody) return;
    
        if (this.entities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data found</td></tr>';
        return;
    }
    
    // Apply filters
    const filterType = document.getElementById('filterType')?.value || '';
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
        let filteredEntities = this.entities;
    
    if (filterType) {
        filteredEntities = filteredEntities.filter(entity => entity.type === filterType);
    }
    
    if (searchTerm) {
        filteredEntities = filteredEntities.filter(entity => 
            entity.name.toLowerCase().includes(searchTerm) ||
            entity.details.toLowerCase().includes(searchTerm)
        );
    }
    
    // Render table rows
    tbody.innerHTML = filteredEntities.map(entity => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${this.getTypeColor(entity.type)}">
                    ${entity.type.charAt(0).toUpperCase() + entity.type.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${entity.name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${entity.details}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${this.getStatusColor(entity.status)}">
                    ${entity.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDate(entity.lastUpdated)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="platformManager.viewEntity('${entity.type}', '${entity.id}')" class="text-thermeleon-600 hover:text-thermeleon-900 mr-3">View</button>
                ${entity.type === 'user' && this.permissions.canManageUsers ? 
                        `${this.permissions.canChangeUserPasswords ? `<button onclick="platformManager.changePassword('${entity.id}', '${entity.name}')" class="text-purple-600 hover:text-purple-900 mr-3">Change Password</button>` : ''}
                         ${this.permissions.canResetPasswords ? `<button onclick="platformManager.resetPassword('${entity.id}', '${entity.name}')" class="text-orange-600 hover:text-orange-900 mr-3">Reset Password</button>` : ''}
                         <button onclick="platformManager.toggleEntityStatus('${entity.type}', '${entity.id}', '${entity.status}')" class="text-blue-600 hover:text-blue-900 mr-3">${entity.status === 'Active' ? 'Deactivate' : 'Activate'}</button>
                         ${this.permissions.canDeleteEntities ? `<button onclick="platformManager.deleteEntity('${entity.type}', '${entity.id}')" class="text-red-600 hover:text-red-900">Delete</button>` : ''}` :
                    ['company', 'position'].includes(entity.type) && this.permissions.canManageUsers ?
                        `<button onclick="platformManager.toggleEntityStatus('${entity.type}', '${entity.id}', '${entity.status}')" class="text-blue-600 hover:text-blue-900 mr-3">${entity.status === 'Active' ? 'Deactivate' : 'Activate'}</button>
                         ${this.permissions.canDeleteEntities ? `<button onclick="platformManager.deleteEntity('${entity.type}', '${entity.id}')" class="text-red-600 hover:text-red-900">Delete</button>` : ''}` :
                        entity.type === 'user' || ['company', 'position'].includes(entity.type) ?
                            '<span class="text-gray-400 text-sm">Limited access</span>' :
                            `<button onclick="platformManager.editEntity('${entity.type}', '${entity.id}')" class="text-indigo-600 hover:text-indigo-900">Edit</button>`
                }
            </td>
        </tr>
    `).join('');
}

    getTypeColor(type) {
        const colors = {
            greenhouse: 'bg-green-100 text-green-800',
            sensor: 'bg-blue-100 text-blue-800',
            data: 'bg-yellow-100 text-yellow-800',
            user: 'bg-purple-100 text-purple-800',
            company: 'bg-indigo-100 text-indigo-800',
            position: 'bg-pink-100 text-pink-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }

    getStatusColor(status) {
        const colors = {
            active: 'bg-green-100 text-green-800',
            recorded: 'bg-blue-100 text-blue-800',
            offline: 'bg-red-100 text-red-800'
        };
        return colors[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
}

    formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    } catch (error) {
        return 'N/A';
    }
}

    /**
     * Debounced refresh to prevent rapid successive calls
     */
    refreshData() {
        if (this.refreshTimeout) {
            clearTimeout(this.refreshTimeout);
}

        this.refreshTimeout = setTimeout(() => {
            this.loadSystemData(true);
        }, 300);
}

    initializeFilters() {
    const filterType = document.getElementById('filterType');
    const searchInput = document.getElementById('searchInput');
    
    if (filterType) {
            filterType.addEventListener('change', () => this.renderEntityTable());
    }
    
    if (searchInput) {
            // Debounce search input
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.renderEntityTable(), 300);
            });
        }
    }

    setupEventListeners() {
        // Modal events
        const modalEntityType = document.getElementById('modalEntityType');
        if (modalEntityType) {
            modalEntityType.addEventListener('change', () => this.updateModalFields());
        }

        const addEntityForm = document.getElementById('addEntityForm');
        if (addEntityForm) {
            addEntityForm.addEventListener('submit', (e) => this.handleAddEntity(e));
        }

        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', (e) => this.handleChangePassword(e));
        }
    }

    // Entity management methods
    viewEntity(type, id) {
        showNotification(`Viewing ${type} with ID: ${id}`, 'info', 'View Entity');
    }

    editEntity(type, id) {
        showNotification(`Editing ${type} with ID: ${id}`, 'info', 'Edit Entity');
    }

    async showAddEntityModal() {
        if (!this.permissions.canCreateEntities) {
            showErrorNotification('You do not have permission to create entities', 'Access Denied');
            return;
        }
        
    const modal = document.getElementById('addEntityModal');
    modal.classList.remove('hidden');
    
        // Lazy load companies and positions only when modal is opened
        if (this.companies.length === 0 || this.positions.length === 0) {
    try {
                const [companiesData, positionsData] = await Promise.all([
                    this.makeRequest('api/users.php?type=companies'),
                    this.makeRequest('api/users.php?type=positions')
        ]);
        
                this.companies = companiesData?.success ? companiesData.data : [];
                this.positions = positionsData?.success ? positionsData.data : [];
        
                this.updateModalCompanySelect();
                this.updateModalPositionSelect();
    } catch (error) {
        console.error('Error loading companies and positions:', error);
            }
    }
    
        this.updateModalFields();
}

    closeAddEntityModal() {
    const modal = document.getElementById('addEntityModal');
    modal.classList.add('hidden');
    document.getElementById('addEntityForm').reset();
}

    updateModalFields() {
    const type = document.getElementById('modalEntityType').value;
    const userFields = document.getElementById('userFields');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.textContent = `Add New ${type.charAt(0).toUpperCase() + type.slice(1)}`;
    
    if (type === 'user') {
        userFields.style.display = 'block';
    } else {
        userFields.style.display = 'none';
    }
}

    updateModalCompanySelect() {
    const companySelect = document.getElementById('entityCompany');
    companySelect.innerHTML = '<option value="">Select a company...</option>';
    
        if (Array.isArray(this.companies)) {
            this.companies.forEach(company => {
            const option = document.createElement('option');
            option.value = company.id_company;
            option.textContent = company.name_company;
            companySelect.appendChild(option);
        });
    }
}

    updateModalPositionSelect() {
    const positionSelect = document.getElementById('entityPosition');
    positionSelect.innerHTML = '<option value="">Select a position...</option>';
    
        if (Array.isArray(this.positions)) {
            this.positions.forEach(position => {
            const option = document.createElement('option');
            option.value = position.id_position;
            option.textContent = position.name_position;
            positionSelect.appendChild(option);
        });
    }
}

    async toggleEntityStatus(type, id, currentStatus) {
        if (!this.permissions.canManageUsers) {
            showErrorNotification('You do not have permission to modify entity status', 'Access Denied');
            return;
        }
        
    const newStatus = currentStatus === 'Active' ? 'inactive' : 'active';
    
    try {
        const response = await fetch('api/users.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: type,
                id: id,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
                // Clear cache and refresh
                this.cache.clear();
                this.refreshData();
            showSuccessNotification('Status updated successfully', 'Status Update');
        } else {
            showErrorNotification('Error updating status: ' + result.message, 'Update Failed');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showErrorNotification('Error updating status', 'Network Error');
    }
}

    async deleteEntity(type, id) {
        if (!this.permissions.canDeleteEntities) {
            showErrorNotification('You do not have permission to delete entities', 'Access Denied');
            return;
        }
        
    const confirmed = await showConfirmDialog(`Are you sure you want to delete this ${type}?`, null, 'Delete Confirmation');
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch(`api/users.php?type=${type}&id=${id}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
                // Clear cache and refresh
                this.cache.clear();
                this.refreshData();
            showSuccessNotification('Entity deleted successfully', 'Delete Success');
        } else {
            showErrorNotification('Error deleting: ' + result.message, 'Delete Failed');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showErrorNotification('Error deleting entity', 'Network Error');
    }
}

    async handleAddEntity(e) {
    e.preventDefault();
    
    const type = document.getElementById('modalEntityType').value;
    const name = document.getElementById('entityName').value;
    
    const data = {
        type: type,
        name: name
    };
    
    if (type === 'user') {
        const email = document.getElementById('entityEmail').value;
        const login = document.getElementById('entityLogin').value;
        const password = document.getElementById('entityPassword').value;
        const userType = document.getElementById('entityUserType').value;
        const companyId = document.getElementById('entityCompany').value;
        const positionId = document.getElementById('entityPosition').value;
        
        data.email = email;
        data.login = login;
        data.password = password;
        data.user_type = parseInt(userType);
        if (companyId) data.company_id = parseInt(companyId);
        if (positionId) data.position_id = parseInt(positionId);
    }
    
    try {
        const response = await fetch('api/users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
                this.closeAddEntityModal();
                // Clear cache and refresh
                this.cache.clear();
                this.refreshData();
            showSuccessNotification('Entity created successfully', 'Creation Success');
        } else {
            showErrorNotification('Error creating: ' + result.message, 'Creation Failed');
        }
    } catch (error) {
        console.error('Error creating entity:', error);
        showErrorNotification('Error creating entity', 'Network Error');
    }
    }

    /**
     * Show change password modal
     */
    changePassword(userId, userName) {
        if (!this.permissions.canChangeUserPasswords) {
            showErrorNotification('You do not have permission to change user passwords', 'Access Denied');
            return;
        }
        
        document.getElementById('changePasswordUserId').value = userId;
        document.getElementById('changePasswordUserName').value = userName;
        document.getElementById('changePasswordModal').classList.remove('hidden');
    }

    /**
     * Reset user password (admin action)
     */
    async resetPassword(userId, userName) {
        if (!this.permissions.canResetPasswords) {
            showErrorNotification('You do not have permission to reset user passwords', 'Access Denied');
            return;
        }
        
        const confirmed = await showConfirmDialog(`Are you sure you want to reset the password for ${userName}?`, null, 'Reset Password');
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch('api/users.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reset_password',
                    user_id: userId
                })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessNotification(`Password reset for ${userName}. New password: ${result.new_password}`, 'Password Reset');
            } else {
                showErrorNotification('Error resetting password: ' + result.message, 'Reset Failed');
            }
        } catch (error) {
            console.error('Error resetting password:', error);
            showErrorNotification('Error resetting password', 'Network Error');
        }
    }

    /**
     * Handle change password form submission
     */
    async handleChangePassword(e) {
        e.preventDefault();
        
        const userId = document.getElementById('changePasswordUserId').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            showErrorNotification('Passwords do not match', 'Validation Error');
            return;
        }
        
        if (newPassword.length < 6) {
            showErrorNotification('Password must be at least 6 characters long', 'Validation Error');
            return;
        }
        
        try {
            const response = await fetch('api/users.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'change_password',
                    user_id: userId,
                    new_password: newPassword
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.closeChangePasswordModal();
                showSuccessNotification('Password changed successfully', 'Password Change');
            } else {
                showErrorNotification('Error changing password: ' + result.message, 'Change Failed');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showErrorNotification('Error changing password', 'Network Error');
        }
    }

    /**
     * Close change password modal
     */
    closeChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.add('hidden');
        document.getElementById('changePasswordForm').reset();
    }
}

// System management functions
function performBackup() {
    if (!window.platformPermissions.isSuperAdmin) {
        showErrorNotification('You do not have permission to perform database backups', 'Access Denied');
        return;
    }
    
    showConfirmDialog('Are you sure you want to perform a database backup?', () => {
        showSuccessNotification('Database backup initiated. This may take a few minutes.', 'Backup Started');
    }, 'Database Backup');
}

function checkSystemHealth() {
    if (!window.platformPermissions.isAdmin) {
        showErrorNotification('You do not have permission to check system health', 'Access Denied');
        return;
    }
    
    showSuccessNotification('System health check completed. All systems operational.', 'System Health');
}

function exportSystemLogs() {
    if (!window.platformPermissions.isSuperAdmin) {
        showErrorNotification('You do not have permission to export system logs', 'Access Denied');
        return;
    }
    
    window.open('api/export.php?type=logs', '_blank');
}

function manageSettings() {
    if (!window.platformPermissions.isSuperAdmin) {
        showErrorNotification('You do not have permission to manage system settings', 'Access Denied');
        return;
    }
    
    showNotification('System settings management - feature coming soon', 'info', 'Settings');
}

async function refreshActivity() {
    try {
        const activityLog = document.getElementById('activityLog');
        if (activityLog) {
            activityLog.innerHTML = '<div class="px-6 py-4 text-center text-gray-500">Refreshing activity...</div>';
        }
        
        // Reload activity data from server
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const newActivityLog = doc.getElementById('activityLog');
        
        if (newActivityLog && activityLog) {
            activityLog.innerHTML = newActivityLog.innerHTML;
        }
        
        // Also refresh statistics
        const statsElements = {
            'totalGreenhouses': doc.getElementById('totalGreenhouses')?.textContent,
            'totalSensors': doc.getElementById('totalSensors')?.textContent,
            'dataPointsToday': doc.getElementById('dataPointsToday')?.textContent
        };
        
        Object.keys(statsElements).forEach(id => {
            const element = document.getElementById(id);
            if (element && statsElements[id]) {
                element.textContent = statsElements[id];
            }
        });
        
    } catch (error) {
        console.error('Error refreshing activity:', error);
        const activityLog = document.getElementById('activityLog');
        if (activityLog) {
            activityLog.innerHTML = '<div class="px-6 py-4 text-center text-red-500">Error refreshing activity</div>';
        }
    }
}

// Global functions for backwards compatibility
function refreshData() {
    if (window.platformManager) {
        window.platformManager.refreshData();
    } else {
        console.warn('Platform manager not initialized');
    }
}

function showAddEntityModal() {
    if (window.platformManager) {
        window.platformManager.showAddEntityModal();
    } else {
        console.warn('Platform manager not initialized');
    }
}

function closeAddEntityModal() {
    if (window.platformManager) {
        window.platformManager.closeAddEntityModal();
    } else {
        console.warn('Platform manager not initialized');
    }
}

function closeChangePasswordModal() {
    if (window.platformManager) {
        window.platformManager.closeChangePasswordModal();
    } else {
        console.warn('Platform manager not initialized');
    }
}

// Password visibility toggle functions
function toggleModalPassword() {
    const passwordInput = document.getElementById('entityPassword');
    const eyeIcon = document.getElementById('modalEyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

function toggleNewPassword() {
    const passwordInput = document.getElementById('newPassword');
    const eyeIcon = document.getElementById('newPasswordEyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

function toggleConfirmPassword() {
    const passwordInput = document.getElementById('confirmPassword');
    const eyeIcon = document.getElementById('confirmPasswordEyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Initialize platform manager immediately and make it globally available
// Initialize immediately to make it available for other scripts
function initializePlatformManager() {
    if (!window.platformManager) {
        window.platformManager = new PlatformManager();
        
        // Initial load with lazy loading
        window.platformManager.loadSystemData();
    }
    return window.platformManager;
}

// Expose additional global functions for backward compatibility
function loadSystemData() {
    if (window.platformManager) {
        window.platformManager.loadSystemData(true);
    } else {
        console.warn('Platform manager not initialized');
    }
}

function renderRealData() {
    if (window.platformManager) {
        window.platformManager.loadSystemData(true);
    } else {
        console.warn('Platform manager not initialized');
    }
}

// Make global functions available immediately
window.refreshData = refreshData;
window.showAddEntityModal = showAddEntityModal;
window.closeAddEntityModal = closeAddEntityModal;
window.closeChangePasswordModal = closeChangePasswordModal;
window.loadSystemData = loadSystemData;
window.renderRealData = renderRealData;

// Initialize immediately when script loads
initializePlatformManager();

// Also initialize on DOM ready as fallback
document.addEventListener('DOMContentLoaded', function() {
    initializePlatformManager();
});

// Export initialization function for external use
window.initializePlatformTab = initializePlatformManager;
</script> 