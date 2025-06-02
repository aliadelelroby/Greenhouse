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
require_once __DIR__ . '/../repositories/AuthUserRepository.php';
require_once __DIR__ . '/../services/ResponseService.php';
require_once __DIR__ . '/../services/PermissionService.php';

session_start();

try {
    $database = Database::getInstance();
    $userRepository = new UserRepository($database);
    $authUserRepository = new AuthUserRepository($database);
    $responseService = new ResponseService();
    $permissionService = new PermissionService($database);
    
    // Initialize tables if they don't exist
    $userRepository->initializeTables();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($userRepository, $authUserRepository, $responseService, $permissionService);
            break;
            
        case 'POST':
            handlePostRequest($userRepository, $authUserRepository, $responseService, $permissionService);
            break;
            
        case 'PUT':
            handlePutRequest($userRepository, $authUserRepository, $responseService, $permissionService);
            break;
            
        case 'DELETE':
            handleDeleteRequest($userRepository, $authUserRepository, $responseService, $permissionService);
            break;
            
        default:
            $responseService->error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Users API error: " . $e->getMessage());
    $responseService = new ResponseService();
    $responseService->error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetRequest(UserRepository $userRepository, AuthUserRepository $authUserRepository, ResponseService $responseService, PermissionService $permissionService): void
{
    // Check authentication for all GET requests
    if (!$permissionService->isAuthenticated()) {
        $responseService->error('Authentication required', 401);
        return;
    }
    
    $type = $_GET['type'] ?? 'users';
    
    // Check permissions based on request type
    if (in_array($type, ['users', 'companies', 'positions', 'all']) && !$permissionService->canManageUsers()) {
        $responseService->error($permissionService->getPermissionErrorMessage('access user management data'), 403);
        return;
    }
    
    try {
        switch ($type) {
            case 'users':
                $users = $authUserRepository->findAllAuthUsers();
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
                    'users' => $authUserRepository->findAllAuthUsers(),
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

function handlePostRequest(UserRepository $userRepository, AuthUserRepository $authUserRepository, ResponseService $responseService, PermissionService $permissionService): void
{
    // Check authentication and creation permissions
    if (!$permissionService->isAuthenticated()) {
        $responseService->error('Authentication required', 401);
        return;
    }
    
    if (!$permissionService->canCreateEntities()) {
        $responseService->error($permissionService->getPermissionErrorMessage('create entities'), 403);
        return;
    }
    
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
                $login = $input['login'] ?? '';
                $password = $input['password'] ?? '';
                $userType = $input['user_type'] ?? 0;
                $companyId = !empty($input['company_id']) ? (int)$input['company_id'] : null;
                $positionId = !empty($input['position_id']) ? (int)$input['position_id'] : null;
                
                if (empty($name) || empty($login) || empty($password)) {
                    $responseService->error('Name, login, and password are required', 400);
                    return;
                }
                
                $userId = $authUserRepository->createAuthUser($name, $email, $login, $password, $userType, $companyId);
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

function handlePutRequest(UserRepository $userRepository, AuthUserRepository $authUserRepository, ResponseService $responseService, PermissionService $permissionService): void
{
    // Check authentication
    if (!$permissionService->isAuthenticated()) {
        $responseService->error('Authentication required', 401);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $responseService->error('Invalid JSON input', 400);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    // Handle password-related actions
    if ($action === 'change_password') {
        if (!$permissionService->canChangeUserPasswords()) {
            $responseService->error($permissionService->getPermissionErrorMessage('change user passwords'), 403);
            return;
        }
        handleChangePassword($authUserRepository, $responseService, $input);
        return;
    }
    
    if ($action === 'reset_password') {
        if (!$permissionService->canResetPasswords()) {
            $responseService->error($permissionService->getPermissionErrorMessage('reset user passwords'), 403);
            return;
        }
        handleResetPassword($authUserRepository, $responseService, $input);
        return;
    }
    
    // Check general management permissions for other actions
    if (!$permissionService->canManageUsers()) {
        $responseService->error($permissionService->getPermissionErrorMessage('manage users'), 403);
        return;
    }
    
    // Handle status updates
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
                $success = $authUserRepository->updateUserStatus((int)$id, $status);
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

function handleDeleteRequest(UserRepository $userRepository, AuthUserRepository $authUserRepository, ResponseService $responseService, PermissionService $permissionService): void
{
    // Check authentication and delete permissions
    if (!$permissionService->isAuthenticated()) {
        $responseService->error('Authentication required', 401);
        return;
    }
    
    if (!$permissionService->canDeleteEntities()) {
        $responseService->error($permissionService->getPermissionErrorMessage('delete entities'), 403);
        return;
    }
    
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
                $success = $authUserRepository->deleteUser((int)$id);
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

/**
 * Handle password change
 */
function handleChangePassword(AuthUserRepository $authUserRepository, ResponseService $responseService, array $input): void
{
    $userId = $input['user_id'] ?? 0;
    $newPassword = $input['new_password'] ?? '';
    
    if (empty($userId) || empty($newPassword)) {
        $responseService->error('User ID and new password are required', 400);
        return;
    }
    
    try {
        $success = $authUserRepository->changeUserPassword((int)$userId, $newPassword);
        
        if ($success) {
            $responseService->success(['message' => 'Password changed successfully']);
        } else {
            $responseService->error('Failed to change password', 500);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to change password: ' . $e->getMessage(), 500);
    }
}

/**
 * Handle password reset
 */
function handleResetPassword(AuthUserRepository $authUserRepository, ResponseService $responseService, array $input): void
{
    $userId = $input['user_id'] ?? 0;
    
    if (empty($userId)) {
        $responseService->error('User ID is required', 400);
        return;
    }
    
    try {
        $newPassword = $authUserRepository->resetUserPassword((int)$userId);
        
        if ($newPassword) {
            $responseService->success([
                'message' => 'Password reset successfully',
                'new_password' => $newPassword
            ]);
        } else {
            $responseService->error('Failed to reset password', 500);
        }
    } catch (Exception $e) {
        $responseService->error('Failed to reset password: ' . $e->getMessage(), 500);
    }
} 