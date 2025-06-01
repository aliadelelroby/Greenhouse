<?php
/**
 * Simple test script to verify database connection and new functionality
 */

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/repositories/DataRepository.php';
require_once __DIR__ . '/repositories/GreenhouseRepository.php';
require_once __DIR__ . '/repositories/SensorRepository.php';
require_once __DIR__ . '/services/ResponseService.php';

try {
    echo "<h1>Database Connection Test</h1>";
    
    // Test database connection
    $database = Database::getInstance();
    echo "<p>✅ Database connection successful!</p>";
    
    // Test repositories
    $dataRepository = new DataRepository($database);
    $greenhouseRepository = new GreenhouseRepository($database);
    $sensorRepository = new SensorRepository($database);
    
    echo "<h2>Repository Tests</h2>";
    
    // Test greenhouse repository
    $greenhouses = $greenhouseRepository->findAll();
    echo "<p>✅ Found " . count($greenhouses) . " greenhouses</p>";
    
    if (!empty($greenhouses)) {
        echo "<ul>";
        foreach ($greenhouses as $greenhouse) {
            echo "<li>ID: {$greenhouse['Id_greenhouse']}, Name: {$greenhouse['Name_greenhouse']}</li>";
        }
        echo "</ul>";
    }
    
    // Test sensor repository
    $sensors = $sensorRepository->findAll();
    echo "<p>✅ Found " . count($sensors) . " sensors</p>";
    
    // Test sensor data for first greenhouse
    if (!empty($greenhouses)) {
        $firstGreenhouse = $greenhouses[0]['Id_greenhouse'];
        $sensors = $sensorRepository->findByGreenhouseId($firstGreenhouse);
        echo "<p>✅ Greenhouse {$firstGreenhouse} has " . count($sensors) . " sensors</p>";
        
        if (!empty($sensors)) {
            // Test data retrieval
            $sensorIds = array_slice(array_column($sensors, 'id_sensor'), 0, 5); // First 5 sensors
            $data = $dataRepository->findBySensorIds($sensorIds);
            echo "<p>✅ Retrieved data for " . count($data) . " sensors</p>";
        }
    }
    
    // Test weather data
    echo "<h2>Weather Data Test</h2>";
    $weatherData = $dataRepository->findWeatherData();
    echo "<p>✅ Found " . count($weatherData) . " weather records</p>";
    
    if (!empty($weatherData)) {
        $latest = $weatherData[0];
        echo "<p>Latest weather: {$latest['temperature']}°C, {$latest['humidity']}% humidity at {$latest['timestamp']}</p>";
    }
    
    // Test statistics
    echo "<h2>Statistics</h2>";
    $greenhouseStats = $greenhouseRepository->getStatistics();
    $sensorStats = $sensorRepository->getStatistics();
    
    echo "<p>Total Greenhouses: {$greenhouseStats['total_greenhouses']}</p>";
    echo "<p>Total Sensors: {$greenhouseStats['total_sensors']}</p>";
    echo "<p>Enabled Sensors: {$sensorStats['enabled_sensors']}</p>";
    echo "<p>Disabled Sensors: {$sensorStats['disabled_sensors']}</p>";
    
    echo "<h2>✅ All tests passed successfully!</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?> 