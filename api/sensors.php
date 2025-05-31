<?php
/**
 * Sensor API endpoint
 * Provides real sensor information from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/SensorController.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';

try {
    // Check if greenhouse list is requested
    if (isset($_GET['greenhouse_list'])) {
        $database = Database::getInstance();
        $greenhouseRepository = new GreenhouseRepository($database);
        $responseService = new ResponseService();
        
        $greenhouses = $greenhouseRepository->findAll();
        $responseService->jsonResponse($greenhouses);
        return;
    }
    
    // Check if we have required parameters for sensor operations
    $greenhouseId = $_GET['id'] ?? null;
    $sensorId = $_GET['id_sensor'] ?? null;
    
    if (!$greenhouseId && !$sensorId) {
        // Return empty array instead of error to prevent infinite loops
        $responseService = new ResponseService();
        $responseService->jsonResponse([]);
        return;
    }
    
    // Instantiate dependencies for sensor operations
    $database = Database::getInstance();
    $sensorRepository = new SensorRepository($database);
    $responseService = new ResponseService();
    
    // Create and execute controller
    $controller = new SensorController($sensorRepository, $responseService);
    $controller->getSensors();
    
} catch (Exception $e) {
    error_log("Sensor API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
?> 