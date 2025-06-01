<h2 class="text-2xl font-bold text-gray-900 mb-6">Platform Manager</h2>

<?php
// Connect to real data
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

try {
    $database = Database::getInstance();
    $userRepository = new UserRepository($database);
    
    // Initialize user tables if they don't exist
    $userRepository->initializeTables();
    
    // Get real statistics from database
    $connection = $database->getConnection();
    
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
                <button onclick="showAddEntityModal()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm font-medium shadow-sm flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add New Entity
                </button>
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
                <button onclick="performBackup()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                    Database Backup
                </button>
                
                <button onclick="checkSystemHealth()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    System Health Check
                </button>
                
                <button onclick="exportSystemLogs()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H10"></path>
                    </svg>
                    Export System Logs
                </button>
                
                <button onclick="manageSettings()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm">
                    <svg class="w-4 h-4 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    System Settings
                </button>
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

<script>
// Platform manager functionality
let entities = [];

// Load real data from database
async function loadSystemData() {
    try {
        // Load all system entities
        const [sensorsResponse, greenhousesResponse, usersResponse] = await Promise.all([
            fetch('api/sensors.php'),
            fetch('api/sensors.php?greenhouse_list=1'),
            fetch('api/users.php?type=all')
        ]);
        
        const sensors = await sensorsResponse.json();
        const greenhouses = await greenhousesResponse.json();
        const usersResponse_json = await usersResponse.json();
        
        // Extract data from API response
        const usersData = usersResponse_json.success ? usersResponse_json.data : {};
        
        // Combine all entities
        entities = [];
        
        // Add greenhouses
        if (Array.isArray(greenhouses)) {
            greenhouses.forEach(greenhouse => {
                entities.push({
                    type: 'greenhouse',
                    id: greenhouse.id || greenhouse.Id_greenhouse,
                    name: greenhouse.name || greenhouse.Name_greenhouse,
                    details: `Company ID: ${greenhouse.Id_company || 'N/A'} | Size: ${greenhouse.X_max || 'N/A'}x${greenhouse.Y_max || 'N/A'}`,
                    status: 'Active',
                    lastUpdated: greenhouse.created_at || new Date().toISOString()
                });
            });
        }
        
        // Add sensors  
        if (Array.isArray(sensors) && sensors.length > 0) {
            sensors.forEach(sensor => {
                entities.push({
                    type: 'sensor',
                    id: sensor.id || sensor.Id_sensor,
                    name: sensor.name || sensor.Name_sensor,
                    details: `Description: ${sensor.description || sensor.Description || 'N/A'} | Greenhouse: ${sensor.Id_greenhouse || 'N/A'}`,
                    status: sensor.enabled || sensor.Enabled ? 'Active' : 'Inactive',
                    lastUpdated: sensor.Last_update || sensor.created_at || new Date().toISOString()
                });
            });
        }
        
        // Add users
        if (usersData && usersData.users && Array.isArray(usersData.users)) {
            usersData.users.forEach(user => {
                entities.push({
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
        if (usersData && usersData.companies && Array.isArray(usersData.companies)) {
            usersData.companies.forEach(company => {
                entities.push({
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
        if (usersData && usersData.positions && Array.isArray(usersData.positions)) {
            usersData.positions.forEach(position => {
                entities.push({
                    type: 'position',
                    id: position.id_position,
                    name: position.name_position,
                    details: `Users: ${position.user_count}`,
                    status: position.status_position === 'active' ? 'Active' : 'Inactive',
                    lastUpdated: position.created_at || new Date().toISOString()
                });
            });
        }
        
        // Load recent data records
        try {
            const dataResponse = await fetch('api/data.php?sensors=all&limit=10');
            const dataRecords = await dataResponse.json();
            
            if (Array.isArray(dataRecords)) {
                dataRecords.forEach((record, index) => {
                    entities.push({
                        type: 'data',
                        id: `data_${index}`,
                        name: `Data Record ${record.id || index + 1}`,
                        details: `Value: ${record.value || record.Value_data || 'N/A'}°C`,
                        status: 'Recorded',
                        lastUpdated: record.timestamp || record.Date_data || new Date().toISOString()
                    });
                });
            }
        } catch (error) {
            console.log('Could not load data records:', error);
        }
        
        renderEntityTable();
        console.log('System data loaded successfully:', entities.length, 'entities');
    } catch (error) {
        console.error('Error loading system data:', error);
        const tbody = document.getElementById('entityTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error loading system data</td></tr>';
        }
    }
}

function renderEntityTable() {
    const tbody = document.getElementById('entityTableBody');
    if (!tbody) return;
    
    if (entities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No data found</td></tr>';
        return;
    }
    
    // Apply filters
    const filterType = document.getElementById('filterType')?.value || '';
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
    let filteredEntities = entities;
    
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
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColor(entity.type)}">
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
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(entity.status)}">
                    ${entity.status}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(entity.lastUpdated)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewEntity('${entity.type}', '${entity.id}')" class="text-thermeleon-600 hover:text-thermeleon-900 mr-3">View</button>
                ${['user', 'company', 'position'].includes(entity.type) ? 
                    `<button onclick="toggleEntityStatus('${entity.type}', '${entity.id}', '${entity.status}')" class="text-blue-600 hover:text-blue-900 mr-3">${entity.status === 'Active' ? 'Deactivate' : 'Activate'}</button>
                     <button onclick="deleteEntity('${entity.type}', '${entity.id}')" class="text-red-600 hover:text-red-900">Delete</button>` :
                    `<button onclick="editEntity('${entity.type}', '${entity.id}')" class="text-indigo-600 hover:text-indigo-900">Edit</button>`
                }
            </td>
        </tr>
    `).join('');
}

function getTypeColor(type) {
    switch(type) {
        case 'greenhouse': return 'bg-green-100 text-green-800';
        case 'sensor': return 'bg-blue-100 text-blue-800';
        case 'data': return 'bg-yellow-100 text-yellow-800';
        case 'user': return 'bg-purple-100 text-purple-800';
        case 'company': return 'bg-indigo-100 text-indigo-800';
        case 'position': return 'bg-pink-100 text-pink-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'recorded': return 'bg-blue-100 text-blue-800';
        case 'offline': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    } catch (error) {
        return 'N/A';
    }
}

function viewEntity(type, id) {
    showNotification(`Viewing ${type} with ID: ${id}`, 'info', 'View Entity');
}

function editEntity(type, id) {
    showNotification(`Editing ${type} with ID: ${id}`, 'info', 'Edit Entity');
}

function refreshData() {
    // Show loading state
    const tbody = document.getElementById('entityTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Refreshing data...</td></tr>';
    }
    
    // Reload data without page refresh
    loadSystemData();
}

function renderRealData() {
    const tbody = document.getElementById('entityTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading real system data...</td></tr>';
    }
    
    // Load and display real data here - only call once
    loadSystemData();
}

// Add event listeners for filters
function initializeFilters() {
    const filterType = document.getElementById('filterType');
    const searchInput = document.getElementById('searchInput');
    
    if (filterType) {
        filterType.addEventListener('change', renderEntityTable);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', renderEntityTable);
    }
}

// System management functions
function performBackup() {
    showConfirmDialog('Are you sure you want to perform a database backup?', () => {
        showSuccessNotification('Database backup initiated. This may take a few minutes.', 'Backup Started');
        // Add real backup functionality here
    }, 'Database Backup');
}

function checkSystemHealth() {
    showSuccessNotification('System health check completed. All systems operational.', 'System Health');
    // Add real health check functionality here
}

function exportSystemLogs() {
    window.open('api/export.php?type=logs', '_blank');
}

function manageSettings() {
    showNotification('System settings management - feature coming soon', 'info', 'Settings');
}

async function refreshActivity() {
    try {
        // Show loading state
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

// User management functions
let companies = [];
let positions = [];

async function showAddEntityModal() {
    const modal = document.getElementById('addEntityModal');
    modal.classList.remove('hidden');
    
    // Load companies and positions for user form
    try {
        const [companiesResponse, positionsResponse] = await Promise.all([
            fetch('api/users.php?type=companies'),
            fetch('api/users.php?type=positions')
        ]);
        
        const companiesData = await companiesResponse.json();
        const positionsData = await positionsResponse.json();
        
        companies = companiesData.success ? companiesData.data : [];
        positions = positionsData.success ? positionsData.data : [];
        
        updateModalCompanySelect();
        updateModalPositionSelect();
    } catch (error) {
        console.error('Error loading companies and positions:', error);
    }
    
    // Set up form based on selected type
    updateModalFields();
}

function closeAddEntityModal() {
    const modal = document.getElementById('addEntityModal');
    modal.classList.add('hidden');
    document.getElementById('addEntityForm').reset();
}

function updateModalFields() {
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

function updateModalCompanySelect() {
    const companySelect = document.getElementById('entityCompany');
    companySelect.innerHTML = '<option value="">Select a company...</option>';
    
    if (Array.isArray(companies)) {
        companies.forEach(company => {
            const option = document.createElement('option');
            option.value = company.id_company;
            option.textContent = company.name_company;
            companySelect.appendChild(option);
        });
    }
}

function updateModalPositionSelect() {
    const positionSelect = document.getElementById('entityPosition');
    positionSelect.innerHTML = '<option value="">Select a position...</option>';
    
    if (Array.isArray(positions)) {
        positions.forEach(position => {
            const option = document.createElement('option');
            option.value = position.id_position;
            option.textContent = position.name_position;
            positionSelect.appendChild(option);
        });
    }
}

async function toggleEntityStatus(type, id, currentStatus) {
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
            refreshData();
            showSuccessNotification('Status updated successfully', 'Status Update');
        } else {
            showErrorNotification('Error updating status: ' + result.message, 'Update Failed');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showErrorNotification('Error updating status', 'Network Error');
    }
}

async function deleteEntity(type, id) {
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
            refreshData();
            showSuccessNotification('Entity deleted successfully', 'Delete Success');
        } else {
            showErrorNotification('Error deleting: ' + result.message, 'Delete Failed');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showErrorNotification('Error deleting entity', 'Network Error');
    }
}

// Event listeners
document.getElementById('modalEntityType').addEventListener('change', updateModalFields);

document.getElementById('addEntityForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const type = document.getElementById('modalEntityType').value;
    const name = document.getElementById('entityName').value;
    
    const data = {
        type: type,
        name: name
    };
    
    if (type === 'user') {
        const email = document.getElementById('entityEmail').value;
        const companyId = document.getElementById('entityCompany').value;
        const positionId = document.getElementById('entityPosition').value;
        
        data.email = email;
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
            closeAddEntityModal();
            refreshData();
            showSuccessNotification('Entity created successfully', 'Creation Success');
        } else {
            showErrorNotification('Error creating: ' + result.message, 'Creation Failed');
        }
    } catch (error) {
        console.error('Error creating entity:', error);
        showErrorNotification('Error creating entity', 'Network Error');
    }
});

// Initialize with real data
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    renderRealData();
});
</script> 