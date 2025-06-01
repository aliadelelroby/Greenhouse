<?php
/**
 * Test script to verify database connection and schema compatibility
 */

require_once 'config/Database.php';
require_once 'repositories/GreenhouseRepository.php';
require_once 'repositories/SensorRepository.php';
require_once 'repositories/DataRepository.php';
require_once 'repositories/UserRepository.php';

echo "<h1>Database Connection Test</h1>\n";

try {
    // Test database connection
    echo "<h2>Testing Database Connection...</h2>\n";
    $database = Database::getInstance();
    $connection = $database->getConnection();
    echo "✅ Database connection successful!\n<br>";
    
    // Test Greenhouse Repository
    echo "<h2>Testing Greenhouse Repository...</h2>\n";
    $greenhouseRepo = new GreenhouseRepository($database);
    $greenhouses = $greenhouseRepo->findAll();
    echo "✅ Found " . count($greenhouses) . " greenhouses\n<br>";
    
    if (!empty($greenhouses)) {
        echo "Sample greenhouse: " . $greenhouses[0]['Name_greenhouse'] . "\n<br>";
    }
    
    // Test Sensor Repository
    echo "<h2>Testing Sensor Repository...</h2>\n";
    $sensorRepo = new SensorRepository($database);
    
    if (!empty($greenhouses)) {
        $sensors = $sensorRepo->findByGreenhouseId($greenhouses[0]['Id_greenhouse']);
        echo "✅ Found " . count($sensors) . " sensors in first greenhouse\n<br>";
        
        if (!empty($sensors)) {
            echo "Sample sensor: " . $sensors[0]['name_sensor'] . "\n<br>";
        }
    }
    
    // Test Data Repository
    echo "<h2>Testing Data Repository...</h2>\n";
    $dataRepo = new DataRepository($database);
    
    if (!empty($sensors)) {
        $sensorIds = array_slice(array_column($sensors, 'id_sensor'), 0, 2); // Test with first 2 sensors
        $data = $dataRepo->findBySensorIds($sensorIds, null, null);
        echo "✅ Found data for " . count($data) . " sensors\n<br>";
        
        $totalDataPoints = 0;
        foreach ($data as $sensorData) {
            $totalDataPoints += count($sensorData);
        }
        echo "Total data points: " . $totalDataPoints . "\n<br>";
    }
    
    // Test User Repository
    echo "<h2>Testing User Repository...</h2>\n";
    $userRepo = new UserRepository($database);
    $userRepo->initializeTables(); // Initialize if needed
    
    $users = $userRepo->findAllUsers();
    $companies = $userRepo->findAllCompanies();
    $positions = $userRepo->findAllPositions();
    
    echo "✅ Found " . count($users) . " users\n<br>";
    echo "✅ Found " . count($companies) . " companies\n<br>";
    echo "✅ Found " . count($positions) . " positions\n<br>";
    
    // Test database statistics
    echo "<h2>Database Statistics...</h2>\n";
    $result = $connection->query("SELECT COUNT(*) as count FROM greenhouse");
    $totalGreenhouses = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $connection->query("SELECT COUNT(*) as count FROM sensor WHERE Enabled = 1");
    $totalSensors = $result ? $result->fetch_assoc()['count'] : 0;
    
    $result = $connection->query("SELECT COUNT(*) as count FROM data");
    $totalDataPoints = $result ? $result->fetch_assoc()['count'] : 0;
    
    echo "📊 Total Greenhouses: " . $totalGreenhouses . "\n<br>";
    echo "📊 Total Active Sensors: " . $totalSensors . "\n<br>";
    echo "📊 Total Data Points: " . $totalDataPoints . "\n<br>";
    
    // Test recent data
    $result = $connection->query("SELECT COUNT(*) as count FROM data WHERE DATE(Date_data) = CURDATE()");
    $todayDataPoints = $result ? $result->fetch_assoc()['count'] : 0;
    echo "📊 Data Points Today: " . $todayDataPoints . "\n<br>";
    
    echo "\n<h2>🎉 All tests passed! The application is ready to use.</h2>\n";
    echo "<a href='index.php'>Go to Dashboard</a>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 