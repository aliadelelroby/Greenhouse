<h2 class="text-2xl font-bold text-gray-900 mb-6">Greenhouse Manager</h2>

<?php
// Connect to real data
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';
require_once __DIR__ . '/../repositories/SensorRepository.php';

try {
    $database = Database::getInstance();
    $greenhouseRepository = new GreenhouseRepository($database);
    $sensorRepository = new SensorRepository($database);
    
    // Get real greenhouse data
    $greenhouses = $greenhouseRepository->findAll();
    
    // Get sensor counts for each greenhouse
    $greenhouseStats = [];
    foreach ($greenhouses as $greenhouse) {
        $sensors = $sensorRepository->findByGreenhouseId($greenhouse['Id_greenhouse']);
        $greenhouseStats[$greenhouse['Id_greenhouse']] = [
            'name' => $greenhouse['Name_greenhouse'],
            'sensor_count' => count($sensors),
            'status' => 'active' // You can add status logic based on your requirements
        ];
    }
    
    // Get overall statistics
    $connection = $database->getConnection();
    $totalGreenhouses = count($greenhouses);
    
    $result = $connection->query("SELECT COUNT(*) as count FROM sensor");
    $totalSensors = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Get recent alerts from data (e.g., temperature thresholds)
    $alertsQuery = "
        SELECT 
            d.Id_sensor,
            s.Name_sensor,
            g.Name_greenhouse,
            d.Value_data,
            d.Date_data
        FROM data d
        LEFT JOIN sensor s ON d.Id_sensor = s.Id_sensor
        LEFT JOIN greenhouse g ON s.Id_greenhouse = g.Id_greenhouse
        WHERE (d.Value_data > 30 OR d.Value_data < 5) 
        AND d.Enabled = 1 
        AND s.Enabled = 1
        ORDER BY d.Date_data DESC
        LIMIT 5
    ";
    $alerts = [];
    $result = $connection->query($alertsQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
    }
    
} catch (Exception $e) {
    error_log("Manager error: " . $e->getMessage());
    $greenhouses = [];
    $greenhouseStats = [];
    $totalGreenhouses = 0;
    $totalSensors = 0;
    $alerts = [];
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Quick Stats -->
    <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21l4-4 4 4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Greenhouses</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalGreenhouses; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2V7a2 2 0 012-2h2a2 2 0 002 2v2a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 00-2 2h-2a2 2 0 00-2 2v6a2 2 0 01-2 2H9z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Sensors</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $totalSensors; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Alerts</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo count($alerts); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Greenhouse List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Greenhouse Overview</h3>
                <button onclick="refreshData()" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Data
                </button>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sensors</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($greenhouses)): ?>
                            <?php foreach ($greenhouses as $greenhouse): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($greenhouse['Name_greenhouse']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">GH-<?php echo str_pad($greenhouse['Id_greenhouse'], 3, '0', STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $greenhouseStats[$greenhouse['Id_greenhouse']]['sensor_count']; ?> sensors</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewGreenhouse(<?php echo $greenhouse['Id_greenhouse']; ?>)" class="text-thermeleon-600 hover:text-thermeleon-900 mr-3">View</button>
                                        <button onclick="exportGreenhouseData(<?php echo $greenhouse['Id_greenhouse']; ?>)" class="text-blue-600 hover:text-blue-900">Export</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No greenhouses found in database
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm h-full flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6 space-y-4 flex-1">
                <button onclick="exportAllData()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900">Export All Data</div>
                        <div class="text-xs text-gray-500">Download complete dataset</div>
                    </div>
                </button>
                
                <button onclick="checkSystemStatus()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900">System Status</div>
                        <div class="text-xs text-gray-500">Check all systems health</div>
                    </div>
                </button>
                
                <button onclick="generateReport()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900">Generate Report</div>
                        <div class="text-xs text-gray-500">Create performance summary</div>
                    </div>
                </button>
                
                <button onclick="manageAlerts()" class="w-full flex items-center px-4 py-3 text-left bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM3 12h12a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <div class="text-sm font-medium text-gray-900">Manage Alerts</div>
                        <div class="text-xs text-gray-500">Configure alert thresholds</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Alerts -->
<div class="mt-8">
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Alerts</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <?php if (!empty($alerts)): ?>
                <?php foreach ($alerts as $alert): ?>
                    <div class="px-6 py-4 flex items-center">
                        <div class="w-2 h-2 <?php echo ($alert['Value_data'] > 30) ? 'bg-red-400' : 'bg-blue-400'; ?> rounded-full mr-3"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">
                                <?php 
                                if ($alert['Value_data'] > 30) {
                                    echo "High temperature detected: {$alert['Value_data']}°C in {$alert['Name_greenhouse']} - {$alert['Name_sensor']}";
                                } else {
                                    echo "Low temperature detected: {$alert['Value_data']}°C in {$alert['Name_greenhouse']} - {$alert['Name_sensor']}";
                                }
                                ?>
                            </p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($alert['Date_data'])); ?></p>
                        </div>
                        <button onclick="acknowledgeAlert(<?php echo $alert['Id_sensor']; ?>)" class="text-sm text-thermeleon-600 hover:text-thermeleon-900">Acknowledge</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="px-6 py-4 text-center text-gray-500">
                    No recent alerts
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Manager functionality with real data

async function refreshData() {
    try {
        // Show loading state
        const loadingHtml = '<div class="text-center py-8"><div class="text-gray-500">Refreshing data...</div></div>';
        
        // Refresh the entire manager content
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        
        // Update greenhouse table
        const newGreenhouseTable = doc.querySelector('#managerContent table tbody');
        const currentGreenhouseTable = document.querySelector('#managerContent table tbody');
        if (newGreenhouseTable && currentGreenhouseTable) {
            currentGreenhouseTable.innerHTML = newGreenhouseTable.innerHTML;
        }
        
        // Update statistics
        const statsToUpdate = [
            'totalGreenhousesManager',
            'totalSensorsManager', 
            'activeAlertsCount',
            'dataPointsTodayManager'
        ];
        
        statsToUpdate.forEach(id => {
            const newElement = doc.getElementById(id);
            const currentElement = document.getElementById(id);
            if (newElement && currentElement) {
                currentElement.textContent = newElement.textContent;
            }
        });
        
        // Update alerts section
        const newAlertsDiv = doc.querySelector('[class*="Recent Alerts"]')?.nextElementSibling?.querySelector('.divide-y');
        const currentAlertsDiv = document.querySelector('.divide-y.divide-gray-200');
        if (newAlertsDiv && currentAlertsDiv) {
            currentAlertsDiv.innerHTML = newAlertsDiv.innerHTML;
        }
        
        console.log('Manager data refreshed successfully');
        
    } catch (error) {
        console.error('Error refreshing manager data:', error);
        showErrorNotification('Error refreshing data. Please try again.', 'Refresh Failed');
    }
}

function viewGreenhouse(greenhouseId) {
    // Switch to greenhouse tab and load specific greenhouse
    if (typeof showTab === 'function') {
        showTab('greenhouse');
        setTimeout(() => {
            const menuSelect = document.getElementById('menuSelect');
            if (menuSelect) {
                menuSelect.value = greenhouseId;
                menuSelect.dispatchEvent(new Event('change'));
            }
        }, 100);
    }
}

function exportGreenhouseData(greenhouseId) {
    // Get all sensors for this greenhouse and export
    fetch(`api/sensors.php?id=${greenhouseId}`)
        .then(response => response.json())
        .then(sensors => {
            if (sensors && sensors.length > 0) {
                const sensorIds = sensors.map(s => s.id_sensor || s.id).join(',');
                const url = `api/export.php?greenhouse_id=${greenhouseId}&sensors=${sensorIds}&type=detailed`;
                window.open(url, '_blank');
            } else {
                showWarningNotification('No sensors found for this greenhouse', 'Export Warning');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorNotification('Error loading sensor data', 'Export Failed');
        });
}

function exportAllData() {
    showConfirmDialog('This will export data from all greenhouses. This may be a large file. Continue?', () => {
        // Get all sensor IDs and export
        fetch('api/sensors.php?all=1')
            .then(response => response.json())
            .then(data => {
                // Handle response and create export URL
                window.open('api/export.php?type=detailed&all=1', '_blank');
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorNotification('Error exporting data', 'Export Failed');
            });
    }, 'Export All Data');
}

function checkSystemStatus() {
    // Check API endpoints
    Promise.all([
        fetch('api/sensors.php'),
        fetch('api/data.php'),
        fetch('api/export.php')
    ]).then(responses => {
        const allOk = responses.every(r => r.ok);
        if (allOk) {
            showSuccessNotification('All systems operational', 'System Status');
        } else {
            showWarningNotification('Some systems may be experiencing issues', 'System Status');
        }
    }).catch(() => {
        showErrorNotification('System health check failed', 'Health Check Failed');
    });
}

function generateReport() {
    const today = new Date().toISOString().split('T')[0];
    const lastWeek = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    window.open(`api/export.php?type=detailed&start_date=${lastWeek}&end_date=${today}&report=1`, '_blank');
}

function manageAlerts() {
    showNotification('Alert management interface - feature coming soon', 'info', 'Alert Management');
}

function acknowledgeAlert(sensorId) {
    // This could update a database field to mark alert as acknowledged
    showSuccessNotification(`Alert for sensor ${sensorId} acknowledged`, 'Alert Acknowledged');
    // In a real implementation, you'd make an API call here
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manager tab loaded with real data');
});
</script> 