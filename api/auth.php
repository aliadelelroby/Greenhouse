<?php
/**
 * Authentication API endpoint
 * Handles login, logout, password management, and session management
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ResponseService.php';

try {
    $database = Database::getInstance();
    $authService = new AuthService($database);
    $responseService = new ResponseService();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            handlePostRequest($authService, $responseService);
            break;
            
        case 'GET':
            handleGetRequest($authService, $responseService);
            break;
            
        case 'PUT':
            handlePutRequest($authService, $responseService);
            break;
            
        case 'DELETE':
            handleLogout($responseService);
            break;
            
        default:
            $responseService->error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Auth API error: " . $e->getMessage());
    $responseService = new ResponseService();
    $responseService->error('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * Handle POST requests (login, forgot password, reset password)
 */
function handlePostRequest(AuthService $authService, ResponseService $responseService): void
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'forgot_password':
                handleForgotPassword($authService, $responseService, $input);
                break;
                
            case 'reset_password':
                handleResetPassword($authService, $responseService, $input);
                break;
                
            default:
                $responseService->error('Invalid action', 400);
        }
    } else {
        // Form data login
        handleLogin($authService, $responseService);
    }
}

/**
 * Handle GET requests (session validation, user info)
 */
function handleGetRequest(AuthService $authService, ResponseService $responseService): void
{
    $action = $_GET['action'] ?? 'check_session';
    
    switch ($action) {
        case 'check_session':
            if ($authService->isAuthenticated()) {
                $user = $authService->getCurrentUser();
                $responseService->success([
                    'authenticated' => true,
                    'user' => $user
                ]);
            } else {
                $responseService->success(['authenticated' => false]);
            }
            break;
            
        case 'user_info':
            if ($authService->isAuthenticated()) {
                $user = $authService->getCurrentUser();
                $responseService->success($user);
            } else {
                $responseService->error('Not authenticated', 401);
            }
            break;
            
        default:
            $responseService->error('Invalid action', 400);
    }
}

/**
 * Handle PUT requests (change password, update profile)
 */
function handlePutRequest(AuthService $authService, ResponseService $responseService): void
{
    if (!$authService->isAuthenticated()) {
        $responseService->error('Not authenticated', 401);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $responseService->error('Invalid JSON input', 400);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            handleChangePassword($authService, $responseService, $input);
            break;
            
        case 'update_profile':
            handleUpdateProfile($authService, $responseService, $input);
            break;
            
        default:
            $responseService->error('Invalid action', 400);
    }
}

/**
 * Handle user login
 */
function handleLogin(AuthService $authService, ResponseService $responseService): void
{
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $responseService->error('Username and password are required', 400);
        return;
    }
    
    try {
        $result = $authService->login($username, $password, $remember);
        
        if ($result['success']) {
            $responseService->success([
                'message' => 'Login successful',
                'user' => $result['user'],
                'redirect' => 'index.php'
            ]);
        } else {
            $responseService->error($result['message'], 401);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $responseService->error('Login failed', 500);
    }
}

/**
 * Handle logout
 */
function handleLogout(ResponseService $responseService): void
{
    session_destroy();
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    $responseService->success(['message' => 'Logged out successfully']);
}

/**
 * Handle forgot password request
 */
function handleForgotPassword(AuthService $authService, ResponseService $responseService, array $input): void
{
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        $responseService->error('Email is required', 400);
        return;
    }
    
    try {
        $result = $authService->forgotPassword($email);
        
        if ($result['success']) {
            $responseService->success([
                'message' => 'Password reset instructions sent to your email'
            ]);
        } else {
            $responseService->error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("Forgot password error: " . $e->getMessage());
        $responseService->error('Failed to process forgot password request', 500);
    }
}

/**
 * Handle password reset
 */
function handleResetPassword(AuthService $authService, ResponseService $responseService, array $input): void
{
    $token = $input['token'] ?? '';
    $newPassword = $input['password'] ?? '';
    
    if (empty($token) || empty($newPassword)) {
        $responseService->error('Token and new password are required', 400);
        return;
    }
    
    try {
        $result = $authService->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $responseService->success([
                'message' => 'Password reset successfully'
            ]);
        } else {
            $responseService->error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        $responseService->error('Failed to reset password', 500);
    }
}

/**
 * Handle password change
 */
function handleChangePassword(AuthService $authService, ResponseService $responseService, array $input): void
{
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        $responseService->error('Current password and new password are required', 400);
        return;
    }
    
    try {
        $result = $authService->changePassword($currentPassword, $newPassword);
        
        if ($result['success']) {
            $responseService->success([
                'message' => 'Password changed successfully'
            ]);
        } else {
            $responseService->error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        $responseService->error('Failed to change password', 500);
    }
}

/**
 * Handle profile update
 */
function handleUpdateProfile(AuthService $authService, ResponseService $responseService, array $input): void
{
    try {
        $result = $authService->updateProfile($input);
        
        if ($result['success']) {
            $responseService->success([
                'message' => 'Profile updated successfully',
                'user' => $result['user']
            ]);
        } else {
            $responseService->error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("Update profile error: " . $e->getMessage());
        $responseService->error('Failed to update profile', 500);
    }
} 