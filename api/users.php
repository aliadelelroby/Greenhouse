<?php
/**
 * Users API endpoint
 * Provides user, company, and position management functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../services/ResponseService.php';

try {
    $database = Database::getInstance();
    $userRepository = new UserRepository($database);
    $responseService = new ResponseService();
    
    // Initialize tables if they don't exist
    $userRepository->initializeTables();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($userRepository, $responseService);
            break;
            
        case 'POST':
            handlePostRequest($userRepository, $responseService);
            break;
            
        case 'PUT':
            handlePutRequest($userRepository, $responseService);
            break;
            
        case 'DELETE':
            handleDeleteRequest($userRepository, $responseService);
            break;
            
        default:
            $responseService->error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Users API error: " . $e->getMessage());
    $responseService = new ResponseService();
    $responseService->error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetRequest(UserRepository $userRepository, ResponseService $responseService): void
{
    $type = $_GET['type'] ?? 'users';
    
    try {
        switch ($type) {
            case 'users':
                $users = $userRepository->findAllUsers();
                $responseService->success($users);
                break;
                
            case 'companies':
                $companies = $userRepository->findAllCompanies();
                $responseService->success($companies);
                break;
                
            case 'positions':
                $positions = $userRepository->findAllPositions();
                $responseService->success($positions);
                break;
                
            case 'statistics':
                $stats = $userRepository->getUserStatistics();
                $responseService->success($stats);
                break;
                
            case 'all':
                $data = [
                    'users' => $userRepository->findAllUsers(),
                    'companies' => $userRepository->findAllCompanies(),
                    'positions' => $userRepository->findAllPositions(),
                    'statistics' => $userRepository->getUserStatistics()
                ];
                $responseService->success($data);
                break;
                
            default:
                $responseService->error('Invalid type parameter', 400);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to fetch data: ' . $e->getMessage(), 500);
    }
}

function handlePostRequest(UserRepository $userRepository, ResponseService $responseService): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $responseService->error('Invalid JSON input', 400);
        return;
    }
    
    $type = $input['type'] ?? '';
    
    try {
        switch ($type) {
            case 'user':
                $name = $input['name'] ?? '';
                $email = $input['email'] ?? '';
                $companyId = !empty($input['company_id']) ? (int)$input['company_id'] : null;
                $positionId = !empty($input['position_id']) ? (int)$input['position_id'] : null;
                
                if (empty($name)) {
                    $responseService->error('Name is required', 400);
                    return;
                }
                
                $userId = $userRepository->createUser($name, $email, $companyId, $positionId);
                $responseService->success(['id' => $userId, 'message' => 'User created successfully']);
                break;
                
            case 'company':
                $name = $input['name'] ?? '';
                
                if (empty($name)) {
                    $responseService->error('Company name is required', 400);
                    return;
                }
                
                $companyId = $userRepository->createCompany($name);
                $responseService->success(['id' => $companyId, 'message' => 'Company created successfully']);
                break;
                
            case 'position':
                $name = $input['name'] ?? '';
                
                if (empty($name)) {
                    $responseService->error('Position name is required', 400);
                    return;
                }
                
                $positionId = $userRepository->createPosition($name);
                $responseService->success(['id' => $positionId, 'message' => 'Position created successfully']);
                break;
                
            default:
                $responseService->error('Invalid type parameter', 400);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to create: ' . $e->getMessage(), 500);
    }
}

function handlePutRequest(UserRepository $userRepository, ResponseService $responseService): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $responseService->error('Invalid JSON input', 400);
        return;
    }
    
    $type = $input['type'] ?? '';
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? '';
    
    if (empty($id) || empty($status)) {
        $responseService->error('ID and status are required', 400);
        return;
    }
    
    try {
        $success = false;
        
        switch ($type) {
            case 'user':
                $success = $userRepository->updateUserStatus((int)$id, $status);
                break;
                
            case 'company':
                $success = $userRepository->updateCompanyStatus((int)$id, $status);
                break;
                
            case 'position':
                $success = $userRepository->updatePositionStatus((int)$id, $status);
                break;
                
            default:
                $responseService->error('Invalid type parameter', 400);
                return;
        }
        
        if ($success) {
            $responseService->success(['message' => ucfirst($type) . ' status updated successfully']);
        } else {
            $responseService->error('Failed to update status', 500);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to update: ' . $e->getMessage(), 500);
    }
}

function handleDeleteRequest(UserRepository $userRepository, ResponseService $responseService): void
{
    $type = $_GET['type'] ?? '';
    $id = $_GET['id'] ?? 0;
    
    if (empty($id)) {
        $responseService->error('ID is required', 400);
        return;
    }
    
    try {
        $success = false;
        
        switch ($type) {
            case 'user':
                $success = $userRepository->deleteUser((int)$id);
                break;
                
            case 'company':
                $success = $userRepository->deleteCompany((int)$id);
                break;
                
            case 'position':
                $success = $userRepository->deletePosition((int)$id);
                break;
                
            default:
                $responseService->error('Invalid type parameter', 400);
                return;
        }
        
        if ($success) {
            $responseService->success(['message' => ucfirst($type) . ' deleted successfully']);
        } else {
            $responseService->error('Failed to delete', 500);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to delete: ' . $e->getMessage(), 500);
    }
} 