<?php
/**
 * Data API endpoint
 * Provides real sensor data from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../controllers/DataController.php';

try {
    // Check if weather data is requested
    if (isset($_GET['weather'])) {
        $database = Database::getInstance();
        $dataRepository = new DataRepository($database);
        $responseService = new ResponseService();
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $weatherData = $dataRepository->findWeatherData($startDate, $endDate);
        $responseService->jsonResponse(['weather' => $weatherData]);
        return;
    }
    
    // Instantiate dependencies for regular sensor data
    $database = Database::getInstance();
    $dataRepository = new DataRepository($database);
    $responseService = new ResponseService();
    
    // Create and execute controller
    $controller = new DataController($dataRepository, $responseService);
    $controller->getData();
    
} catch (Exception $e) {
    error_log("Data API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal server error"]);
}
?> 