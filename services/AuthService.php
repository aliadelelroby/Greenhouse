<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/EmailConfig.php';
require_once __DIR__ . '/EmailService.php';

/**
 * Authentication service for user login, session management, and password operations
 * Single Responsibility: Handle authentication-related operations
 * Open/Closed: Extensible for new authentication methods
 */
class AuthService
{
    private mysqli $connection;
    private EmailService $emailService;
    
    public function __construct(Database $database, ?array $emailConfig = null)
    {
        $this->connection = $database->getConnection();
        
        // Load environment configuration if available
        EmailConfig::loadEnvironmentConfig();
        
        // Initialize email service
        $smtpConfig = $emailConfig ?? EmailConfig::getSmtpConfig();
        $this->emailService = new EmailService($smtpConfig, $database);
        
        $this->initializeAuthTables();
    }

    /**
     * Authenticate user with username/email and password
     */
    public function login(string $username, string $password, bool $remember = false): array
    {
        try {
            // Try to find user by login or email
            $sql = "SELECT * FROM users WHERE (login = ? OR email_user = ?) AND enabled = 1";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare login query: " . $this->connection->error);
            }
            
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['hash_pwd'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Create session
            $this->createSession($user, $remember);
            
            // Update last login
            $this->updateLastLogin($user['id_user']);
            
            // Get company info
            $companyInfo = $this->getCompanyInfo($user['id_company']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id_user'],
                    'name' => $user['name_user'],
                    'lastname' => $user['lastname_user'] ?? '',
                    'email' => $user['email_user'],
                    'login' => $user['login'],
                    'type' => $user['type_user'],
                    'company_id' => $user['id_company'],
                    'company_name' => $companyInfo['name'] ?? 'Unknown',
                    'last_login' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed due to system error'
            ];
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        // Check session first
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_login'])) {
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        return $this->getUserById($userId);
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(string $email): array
    {
        try {
            $sql = "SELECT id_user, name_user FROM users WHERE email_user = ? AND enabled = 1";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare forgot password query: " . $this->connection->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                // Don't reveal if email exists
                return [
                    'success' => true,
                    'message' => 'If the email exists, password reset instructions have been sent'
                ];
            }
            
            // Generate reset token
            $token = $this->generateResetToken($user['id_user']);
            
            // Send password reset email
            $emailSent = $this->emailService->sendPasswordResetEmail(
                $email, 
                $user['name_user'], 
                $token
            );
            
            if (!$emailSent) {
                error_log("Failed to send password reset email to: {$email}");
                return [
                    'success' => false,
                    'message' => 'Failed to send password reset email. Please try again later.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Password reset instructions sent to your email'
            ];
            
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process forgot password request'
            ];
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        try {
            $sql = "SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? AND used = 0";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare reset password query: " . $this->connection->error);
            }
            
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $resetToken = $result->fetch_assoc();
            $stmt->close();
            
            if (!$resetToken) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ];
            }
            
            // Check if token is expired
            if (strtotime($resetToken['expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'Reset token has expired'
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET hash_pwd = ? WHERE id_user = ?";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare password update: " . $this->connection->error);
            }
            
            $stmt->bind_param("si", $hashedPassword, $resetToken['user_id']);
            
            if (!$stmt->execute()) {
                throw new RuntimeException("Failed to update password: " . $stmt->error);
            }
            $stmt->close();
            
            // Mark token as used
            $this->markTokenAsUsed($token);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password'
            ];
        }
    }

    /**
     * Change user password
     */
    public function changePassword(string $currentPassword, string $newPassword): array
    {
        if (!$this->isAuthenticated()) {
            return [
                'success' => false,
                'message' => 'Not authenticated'
            ];
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Verify current password
            $sql = "SELECT hash_pwd FROM users WHERE id_user = ?";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare password verification: " . $this->connection->error);
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user || !password_verify($currentPassword, $user['hash_pwd'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET hash_pwd = ? WHERE id_user = ?";
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare password update: " . $this->connection->error);
            }
            
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if (!$stmt->execute()) {
                throw new RuntimeException("Failed to update password: " . $stmt->error);
            }
            $stmt->close();
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to change password'
            ];
        }
    }

    /**
     * Send welcome email to user
     */
    public function sendWelcomeEmail(string $email, string $userName): bool
    {
        try {
            return $this->emailService->sendWelcomeEmail($email, $userName);
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(array $data): array
    {
        if (!$this->isAuthenticated()) {
            return [
                'success' => false,
                'message' => 'Not authenticated'
            ];
        }
        
        try {
            $userId = $_SESSION['user_id'];
            $allowedFields = ['name_user', 'lastname_user', 'email_user'];
            $updateFields = [];
            $updateValues = [];
            $types = '';
            
            foreach ($allowedFields as $field) {
                // Map old field names to new ones for backward compatibility
                $dataKey = match($field) {
                    'name_user' => $data['Name_user'] ?? $data['name_user'] ?? null,
                    'lastname_user' => $data['Lastname_user'] ?? $data['lastname_user'] ?? null,
                    'email_user' => $data['Email_user'] ?? $data['email_user'] ?? null,
                    default => null
                };
                
                if (!empty($dataKey)) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $dataKey;
                    $types .= 's';
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ];
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id_user = ?";
            $updateValues[] = $userId;
            $types .= 'i';
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException("Failed to prepare profile update: " . $this->connection->error);
            }
            
            $stmt->bind_param($types, ...$updateValues);
            
            if (!$stmt->execute()) {
                throw new RuntimeException("Failed to update profile: " . $stmt->error);
            }
            $stmt->close();
            
            // Get updated user info
            $updatedUser = $this->getUserById($userId);
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ];
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update profile'
            ];
        }
    }

    /**
     * Create user session
     */
    private function createSession(array $user, bool $remember = false): void
    {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_name'] = $user['name_user'];
        $_SESSION['user_type'] = $user['type_user'];
        $_SESSION['company_id'] = $user['id_company'];
        
        if ($remember) {
            $this->createRememberToken($user['id_user']);
        }
    }

    /**
     * Create remember me token
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        
        $stmt = $this->connection->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $token, $expiresAt);
            $stmt->execute();
            $stmt->close();
            
            // Set cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
        }
    }

    /**
     * Validate remember token
     */
    private function validateRememberToken(string $token): bool
    {
        $sql = "SELECT rt.user_id FROM remember_tokens rt 
                INNER JOIN users u ON rt.user_id = u.id_user 
                WHERE rt.token = ? AND rt.expires_at > NOW() AND u.enabled = 1";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $tokenData = $result->fetch_assoc();
        $stmt->close();
        
        if ($tokenData) {
            $user = $this->getUserById($tokenData['user_id']);
            if ($user) {
                $this->createSession($user);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate password reset token
     */
    private function generateResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $token, $expiresAt);
            $stmt->execute();
            $stmt->close();
        }
        
        return $token;
    }

    /**
     * Mark reset token as used
     */
    private function markTokenAsUsed(string $token): void
    {
        $sql = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get user by ID
     */
    private function getUserById(int $userId): ?array
    {
        $sql = "SELECT u.*, c.Name_company 
                FROM users u 
                LEFT JOIN company c ON u.id_company = c.Id_company 
                WHERE u.id_user = ? AND u.enabled = 1";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            return [
                'id' => $user['id_user'],
                'name' => $user['name_user'],
                'lastname' => $user['lastname_user'] ?? '',
                'email' => $user['email_user'],
                'login' => $user['login'],
                'type' => $user['type_user'],
                'company_id' => $user['id_company'],
                'company_name' => $user['Name_company'] ?? 'Unknown'
            ];
        }
        
        return null;
    }

    /**
     * Get company information
     */
    private function getCompanyInfo(int $companyId): array
    {
        $sql = "SELECT Name_company FROM company WHERE Id_company = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            return ['name' => 'Unknown'];
        }
        
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'name' => $company ? $company['Name_company'] : 'Unknown'
        ];
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id_user = ?";
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Initialize authentication-related tables
     */
    private function initializeAuthTables(): void
    {
        $tables = [
            'remember_tokens' => "CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE
            )",
            'password_reset_tokens' => "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $tableName => $sql) {
            if (!$this->connection->query($sql)) {
                error_log("Failed to create table $tableName: " . $this->connection->error);
            }
        }
        
        // Add last_login column to users table if it doesn't exist
        $sql = "SHOW COLUMNS FROM users LIKE 'last_login'";
        $result = $this->connection->query($sql);
        
        if ($result && $result->num_rows === 0) {
            $sql = "ALTER TABLE users ADD COLUMN last_login DATETIME NULL";
            if (!$this->connection->query($sql)) {
                error_log("Failed to add last_login column: " . $this->connection->error);
            }
        }
    }
} 