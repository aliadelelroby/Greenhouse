<?php
/**
 * Sensor API endpoint
 * Provides real sensor information from the database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../controllers/SensorController.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';

try {
    $database = Database::getInstance();
    $sensorRepository = new SensorRepository($database);
    $greenhouseRepository = new GreenhouseRepository($database);
    $responseService = new ResponseService();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($sensorRepository, $greenhouseRepository, $responseService);
            break;
            
        case 'POST':
            handlePostRequest($sensorRepository, $greenhouseRepository, $responseService);
            break;
            
        case 'PUT':
            handlePutRequest($sensorRepository, $greenhouseRepository, $responseService);
            break;
            
        case 'DELETE':
            handleDeleteRequest($sensorRepository, $greenhouseRepository, $responseService);
            break;
            
        default:
            $responseService->errorResponse('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Sensor API error: " . $e->getMessage());
    $responseService = new ResponseService();
    $responseService->errorResponse('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetRequest($sensorRepository, $greenhouseRepository, $responseService): void
{
    // Check if greenhouse list is requested
    if (isset($_GET['greenhouse_list'])) {
        $greenhouses = $greenhouseRepository->findAll();
        $responseService->jsonResponse($greenhouses);
        return;
    }
    
    // Check if greenhouse statistics are requested
    if (isset($_GET['greenhouse_stats'])) {
        $stats = $greenhouseRepository->getStatistics();
        $responseService->jsonResponse($stats);
        return;
    }
    
    // Check if sensor statistics are requested
    if (isset($_GET['sensor_stats'])) {
        $stats = $sensorRepository->getStatistics();
        $responseService->jsonResponse($stats);
        return;
    }
    
    // Check if all sensors are requested
    if (isset($_GET['all'])) {
        $sensors = $sensorRepository->findAll();
        $responseService->jsonResponse($sensors);
        return;
    }
    
    // Check if we have required parameters for sensor operations
    $greenhouseId = $_GET['id'] ?? null;
    $sensorId = $_GET['id_sensor'] ?? null;
    
    if (!$greenhouseId && !$sensorId) {
        // Return empty array instead of error to prevent infinite loops
        $responseService->jsonResponse([]);
        return;
    }
    
    // Create and execute controller
    $controller = new SensorController($sensorRepository, $responseService);
    $controller->getSensors();
}

function handlePostRequest($sensorRepository, $greenhouseRepository, $responseService): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $responseService->errorResponse('Invalid JSON input', 400);
        return;
    }
    
    $type = $input['type'] ?? '';
    
    try {
        switch ($type) {
            case 'sensor':
                $name = $input['name'] ?? '';
                $description = $input['description'] ?? '';
                $greenhouseId = $input['greenhouse_id'] ?? 0;
                $sensorModelId = $input['sensor_model_id'] ?? 1; // Default to 1 if not provided
                
                if (empty($name) || empty($greenhouseId)) {
                    $responseService->errorResponse('Name and greenhouse ID are required', 400);
                    return;
                }
                
                $sensorId = $sensorRepository->create($name, $description, (int)$greenhouseId, (int)$sensorModelId);
                $responseService->jsonResponse(['success' => true, 'id' => $sensorId, 'message' => 'Sensor created successfully']);
                break;
                
            case 'greenhouse':
                $name = $input['name'] ?? '';
                $companyId = $input['company_id'] ?? 0;
                $xMax = !empty($input['x_max']) ? (int)$input['x_max'] : null;
                $yMax = !empty($input['y_max']) ? (int)$input['y_max'] : null;
                
                if (empty($name) || empty($companyId)) {
                    $responseService->errorResponse('Name and company ID are required', 400);
                    return;
                }
                
                $greenhouseId = $greenhouseRepository->create($name, (int)$companyId, $xMax, $yMax);
                $responseService->jsonResponse(['success' => true, 'id' => $greenhouseId, 'message' => 'Greenhouse created successfully']);
                break;
                
            default:
                $responseService->errorResponse('Invalid type parameter', 400);
        }
    } catch (Exception $e) {
        $responseService->errorResponse('Failed to create: ' . $e->getMessage(), 500);
    }
}

function handlePutRequest($sensorRepository, $greenhouseRepository, $responseService): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $responseService->errorResponse('Invalid JSON input', 400);
        return;
    }
    
    $type = $input['type'] ?? '';
    $id = $input['id'] ?? 0;
    
    if (empty($id)) {
        $responseService->errorResponse('ID is required', 400);
        return;
    }
    
    try {
        $success = false;
        
        switch ($type) {
            case 'sensor':
                if (isset($input['toggle_enabled'])) {
                    $success = $sensorRepository->toggleEnabled((int)$id);
                } else {
                    $name = $input['name'] ?? '';
                    $description = $input['description'] ?? '';
                    $greenhouseId = $input['greenhouse_id'] ?? 0;
                    $enabled = $input['enabled'] ?? 1;
                    
                    if (empty($name) || empty($greenhouseId)) {
                        $responseService->errorResponse('Name and greenhouse ID are required', 400);
                        return;
                    }
                    
                    $success = $sensorRepository->update((int)$id, $name, $description, (int)$greenhouseId, (int)$enabled);
                }
                break;
                
            case 'greenhouse':
                $name = $input['name'] ?? '';
                $companyId = $input['company_id'] ?? 0;
                $xMax = !empty($input['x_max']) ? (int)$input['x_max'] : null;
                $yMax = !empty($input['y_max']) ? (int)$input['y_max'] : null;
                
                if (empty($name) || empty($companyId)) {
                    $responseService->errorResponse('Name and company ID are required', 400);
                    return;
                }
                
                $success = $greenhouseRepository->update((int)$id, $name, (int)$companyId, $xMax, $yMax);
                break;
                
            default:
                $responseService->errorResponse('Invalid type parameter', 400);
                return;
        }
        
        if ($success) {
            $responseService->jsonResponse(['success' => true, 'message' => ucfirst($type) . ' updated successfully']);
        } else {
            $responseService->errorResponse('Failed to update', 500);
        }
    } catch (Exception $e) {
        $responseService->errorResponse('Failed to update: ' . $e->getMessage(), 500);
    }
}

function handleDeleteRequest($sensorRepository, $greenhouseRepository, $responseService): void
{
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? 0;
    $hardDelete = isset($_GET['hard_delete']);
    
    if (empty($id)) {
        $responseService->errorResponse('ID is required', 400);
        return;
    }
    
    try {
        $success = false;
        
        switch ($type) {
            case 'sensor':
                if ($hardDelete) {
                    $success = $sensorRepository->hardDelete((int)$id);
                } else {
                    $success = $sensorRepository->delete((int)$id);
                }
                break;
                
            case 'greenhouse':
                $success = $greenhouseRepository->delete((int)$id);
                break;
                
            default:
                $responseService->errorResponse('Invalid type parameter', 400);
                return;
        }
        
        if ($success) {
            $responseService->jsonResponse(['success' => true, 'message' => ucfirst($type) . ' deleted successfully']);
        } else {
            $responseService->errorResponse('Failed to delete', 500);
        }
} catch (Exception $e) {
        $responseService->errorResponse('Failed to delete: ' . $e->getMessage(), 500);
    }
}
?> 