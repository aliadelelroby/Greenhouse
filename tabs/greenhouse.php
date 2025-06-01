<h2 class="text-2xl font-bold text-gray-900 mb-6">Greenhouse Dashboard</h2>

<?php
// Use refactored architecture for data fetching
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';

try {
    $database = Database::getInstance();
    $greenhouseRepository = new GreenhouseRepository($database);
    $greenhouses = $greenhouseRepository->findAll();
} catch (Exception $e) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">Error loading greenhouses: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $greenhouses = [];
}
?>

<!-- Greenhouse Selection -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div>
        <label for="menuSelect" class="block text-sm font-medium text-gray-700 mb-2">Select a Greenhouse</label>
        <select id="menuSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-transparent">
            <option value="">Choose a greenhouse...</option>
            <?php
            if (!empty($greenhouses)) {
                foreach ($greenhouses as $greenhouse) {
                    echo '<option value="' . htmlspecialchars($greenhouse["Id_greenhouse"]) . '">' . htmlspecialchars($greenhouse["Name_greenhouse"]) . '</option>';
                }
            } else {
                echo '<option disabled>No greenhouses available</option>';
            }
            ?>
        </select>
    </div>

    <!-- Sensor Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Sensors to Display</label>
        <div class="relative">
            <button id="sensorDropdownBtn" 
                    class="w-full px-3 py-2 text-left border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-transparent bg-white">
                <span class="text-gray-500">Choose sensors...</span>
                <svg class="w-5 h-5 float-right mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div id="sensorDropdownMenu" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                <div class="p-3 text-gray-500">Select a greenhouse first...</div>
            </div>
        </div>
    </div>
</div>

<!-- Selected Sensors Pills -->
<div class="mb-6">
    <div class="flex justify-between items-center mb-2">
        <h3 class="text-sm font-medium text-gray-700">Selected Sensors</h3>
        <div class="flex items-center">
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" id="includeWeather" class="mr-2 text-thermeleon-500 focus:ring-thermeleon-500 border-gray-300 rounded">
                <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.004 4.004 0 003 15z"></path>
                </svg>
                Include Weather Data
            </label>
        </div>
    </div>
    <div id="selectedPills" class="flex flex-wrap gap-2">
        <span class="text-gray-500 text-sm">No sensors selected</span>
    </div>
</div>

<!-- Date Range Selection -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div>
        <label for="startDate" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
        <div class="relative">
            <input type="text" id="startDate" placeholder="Select start date" readonly class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-transparent bg-white cursor-pointer">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
    </div>
    <div>
        <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
        <div class="relative">
            <input type="text" id="endDate" placeholder="Select end date" readonly class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-transparent bg-white cursor-pointer">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Sensor Data Chart</h3>
        <div class="flex gap-2">
            <button id="refreshChart" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
    
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div id="chartContainer" class="chart-container">
            <canvas id="temperatureChart"></canvas>
        </div>
        <div id="chartPlaceholder" class="flex items-center justify-center h-96 text-gray-500">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2V7a2 2 0 012-2h2a2 2 0 002 2v2a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 00-2 2h-2a2 2 0 00-2 2v6a2 2 0 01-2 2H9z"></path>
                </svg>
                <p class="text-lg">Select a greenhouse and sensors to view data</p>
            </div>
        </div>
    </div>
</div>

<!-- Export Section -->
<div class="border-t pt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Data</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button id="exportQuick" class="flex items-center justify-center px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200" disabled>
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Quick Data (5min intervals)
        </button>
        
        <button id="exportDetailed" class="flex items-center justify-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200" disabled>
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Detailed Data (hourly averages)
        </button>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md">
        <div class="flex items-center mb-4">
            <svg id="notificationIcon" class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 id="notificationTitle" class="text-lg font-semibold text-gray-900">Notification</h3>
        </div>
        <p id="notificationMessage" class="text-gray-700 mb-6">This is a notification message.</p>
        <div class="flex justify-end">
            <button id="closeNotificationModal" class="px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200">Close</button>
        </div>
    </div>
</div>

<script>
// Initialize chart placeholder visibility and date pickers
document.addEventListener('DOMContentLoaded', function() {
    const chartContainer = document.getElementById('chartContainer');
    const chartPlaceholder = document.getElementById('chartPlaceholder');
    
    // Initially hide chart container and show placeholder
    chartContainer.style.display = 'none';
    chartPlaceholder.style.display = 'flex';
    
    // Setup dropdown functionality
    const dropdownBtn = document.getElementById('sensorDropdownBtn');
    const dropdownMenu = document.getElementById('sensorDropdownMenu');
    
    dropdownBtn.addEventListener('click', function() {
        dropdownMenu.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!dropdownBtn.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.add('hidden');
        }
    });
    
    // Export button functionality
    document.getElementById('exportQuick').addEventListener('click', function() {
        if (!this.disabled) {
            exportData('quick');
        }
    });
    
    document.getElementById('exportDetailed').addEventListener('click', function() {
        if (!this.disabled) {
            exportData('detailed');
        }
    });
    
    document.getElementById('refreshChart').addEventListener('click', function() {
        updateChart();
    });
    
    // Add event listener for notification modal close button
    document.getElementById('closeNotificationModal').addEventListener('click', closeNotificationModal);
});

function exportData(type) {
    const greenhouseId = document.getElementById('menuSelect').value;
    const sensors = Array.from(selectedSensors);
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!greenhouseId || sensors.length === 0) {
        showNotificationModal('Export Error', 'Please select a greenhouse and at least one sensor.', 'error');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('greenhouse_id', greenhouseId);
    params.append('sensors', sensors.join(','));
    params.append('type', type);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    // Use real API endpoint
    const url = `api/export.php?${params.toString()}`;
    window.open(url, '_blank');
}

function updateExportButtons() {
    const exportQuick = document.getElementById('exportQuick');
    const exportDetailed = document.getElementById('exportDetailed');
    const hasData = selectedSensors.size > 0 && document.getElementById('menuSelect').value;
    
    exportQuick.disabled = !hasData;
    exportDetailed.disabled = !hasData;
    
    if (hasData) {
        exportQuick.classList.remove('opacity-50', 'cursor-not-allowed');
        exportDetailed.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        exportQuick.classList.add('opacity-50', 'cursor-not-allowed');
        exportDetailed.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Add notification modal functions
function showNotificationModal(title, message, type = 'info') {
    const modal = document.getElementById('notificationModal');
    const modalTitle = document.getElementById('notificationTitle');
    const modalMessage = document.getElementById('notificationMessage');
    const modalIcon = document.getElementById('notificationIcon');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Update icon and colors based on type
    const iconColors = {
        'error': 'text-red-500',
        'success': 'text-green-500',
        'warning': 'text-yellow-500',
        'info': 'text-blue-500'
    };
    
    const iconPaths = {
        'error': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'success': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'info': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    
    modalIcon.className = `w-6 h-6 ${iconColors[type]}`;
    modalIcon.querySelector('path').setAttribute('d', iconPaths[type]);
    
    modal.classList.remove('hidden');
}

function closeNotificationModal() {
    document.getElementById('notificationModal').classList.add('hidden');
}

// Note: initializeDatePickers() is now handled by the main app.js file
</script> 