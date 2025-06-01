<?php
/**
 * Export API endpoint
 * Provides real data export functionality
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/ExportController.php';

try {
    // Instantiate dependencies
    $database = Database::getInstance();
    $dataRepository = new DataRepository($database);
    $sensorRepository = new SensorRepository($database);
    $greenhouseRepository = new GreenhouseRepository($database);
    $exportService = new ExportService($dataRepository, $sensorRepository, $greenhouseRepository);
    $responseService = new ResponseService();
    
    // Create and execute controller
    $controller = new ExportController($exportService, $responseService, $sensorRepository);
    $controller->exportData();
    
} catch (Exception $e) {
    error_log("Export API error: " . $e->getMessage());
    http_response_code(500);
    echo "Export failed: Internal server error";
}
?> 