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
    
    // Check if companies list is requested
    if (isset($_GET['companies'])) {
        $companies = getCompanies($responseService);
        $responseService->jsonResponse($companies);
        return;
    }
    
    // Check if sensor models list is requested
    if (isset($_GET['sensor_models'])) {
        $sensorModels = getSensorModels($responseService);
        $responseService->jsonResponse($sensorModels);
        return;
    }
    
    // Check if sensor types list is requested
    if (isset($_GET['sensor_types'])) {
        $sensorTypes = getSensorTypes($responseService);
        $responseService->jsonResponse($sensorTypes);
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
                
            case 'sensor_model':
                $brand = $input['brand'] ?? '';
                $model = $input['model'] ?? '';
                $sensorTypeId = $input['sensor_type_id'] ?? 0;
                
                if (empty($brand) || empty($model) || empty($sensorTypeId)) {
                    $responseService->errorResponse('Brand, model, and sensor type are required', 400);
                    return;
                }
                
                $sensorModelId = createSensorModel($brand, $model, (int)$sensorTypeId, $responseService);
                $responseService->jsonResponse(['success' => true, 'id' => $sensorModelId, 'message' => 'Sensor model created successfully']);
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
                
            case 'sensor_model':
                $brand = $input['brand'] ?? '';
                $model = $input['model'] ?? '';
                $sensorTypeId = $input['sensor_type_id'] ?? 0;
                
                if (empty($brand) || empty($model) || empty($sensorTypeId)) {
                    $responseService->errorResponse('Brand, model, and sensor type are required', 400);
                    return;
                }
                
                $success = updateSensorModel((int)$id, $brand, $model, (int)$sensorTypeId, $responseService);
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
                
            case 'sensor_model':
                $success = deleteSensorModel((int)$id, $responseService);
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

/**
 * Get all companies from the database
 */
function getCompanies($responseService): array
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $sql = "SELECT Id_company, Name_company, Description, Enabled 
                FROM company 
                WHERE Enabled = 1
                ORDER BY Name_company";
        
        $result = $connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute companies query: " . $connection->error);
        }
        
        $companies = [];
        while ($row = $result->fetch_assoc()) {
            $companies[] = [
                'Id_company' => (int)$row['Id_company'],
                'Name_company' => $row['Name_company'],
                'Description' => $row['Description'],
                'Enabled' => (int)$row['Enabled']
            ];
        }
        
        return $companies;
    } catch (Exception $e) {
        error_log("Error fetching companies: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all sensor models from the database  
 */
function getSensorModels($responseService): array
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $sql = "SELECT sm.Id_sensor_model, sm.Brand, sm.Model, sm.Id_sensor_type, st.Name_sensor_type
                FROM sensor_model sm
                LEFT JOIN sensor_type st ON sm.Id_sensor_type = st.Id_sensor_type
                ORDER BY sm.Brand, sm.Model";
        
        $result = $connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute sensor models query: " . $connection->error);
        }
        
        $sensorModels = [];
        while ($row = $result->fetch_assoc()) {
            $sensorModels[] = [
                'Id_sensor_model' => (int)$row['Id_sensor_model'],
                'Name_sensor_model' => $row['Brand'] . ' ' . $row['Model'],
                'Brand' => $row['Brand'],
                'Model' => $row['Model'],
                'Id_sensor_type' => (int)$row['Id_sensor_type'],
                'Name_sensor_type' => $row['Name_sensor_type']
            ];
        }
        
        return $sensorModels;
    } catch (Exception $e) {
        error_log("Error fetching sensor models: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all sensor types from the database
 */
function getSensorTypes($responseService): array
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $sql = "SELECT Id_sensor_type, Name_sensor_type, Description_sensor_type 
                FROM sensor_type 
                ORDER BY Name_sensor_type";
        
        $result = $connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute sensor types query: " . $connection->error);
        }
        
        $sensorTypes = [];
        while ($row = $result->fetch_assoc()) {
            $sensorTypes[] = [
                'Id_sensor_type' => (int)$row['Id_sensor_type'],
                'Name_sensor_type' => $row['Name_sensor_type'],
                'Description_sensor_type' => $row['Description_sensor_type']
            ];
        }
        
        return $sensorTypes;
    } catch (Exception $e) {
        error_log("Error fetching sensor types: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new sensor model
 */
function createSensorModel(string $brand, string $model, int $sensorTypeId, $responseService): int
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $sql = "INSERT INTO sensor_model (Brand, Model, Id_sensor_type) VALUES (?, ?, ?)";
        
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor model insert: " . $connection->error);
        }
        
        $stmt->bind_param("ssi", $brand, $model, $sensorTypeId);
        
        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to create sensor model: " . $stmt->error);
        }
        
        $sensorModelId = $connection->insert_id;
        $stmt->close();
        
        return $sensorModelId;
    } catch (Exception $e) {
        error_log("Error creating sensor model: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Update a sensor model
 */
function updateSensorModel(int $sensorModelId, string $brand, string $model, int $sensorTypeId, $responseService): bool
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        $sql = "UPDATE sensor_model SET Brand = ?, Model = ?, Id_sensor_type = ? WHERE Id_sensor_model = ?";
        
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor model update: " . $connection->error);
        }
        
        $stmt->bind_param("ssii", $brand, $model, $sensorTypeId, $sensorModelId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error updating sensor model: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Delete a sensor model
 */
function deleteSensorModel(int $sensorModelId, $responseService): bool
{
    try {
        $database = Database::getInstance();
        $connection = $database->getConnection();
        
        // First check if sensor model is being used by any sensors
        $checkSql = "SELECT COUNT(*) as sensor_count FROM sensor WHERE Id_sensor_model = ?";
        $checkStmt = $connection->prepare($checkSql);
        $checkStmt->bind_param("i", $sensorModelId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($row['sensor_count'] > 0) {
            throw new RuntimeException("Cannot delete sensor model: it is being used by " . $row['sensor_count'] . " sensor(s)");
        }
        
        $sql = "DELETE FROM sensor_model WHERE Id_sensor_model = ?";
        
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor model delete: " . $connection->error);
        }
        
        $stmt->bind_param("i", $sensorModelId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error deleting sensor model: " . $e->getMessage());
        throw $e;
    }
}
?> 