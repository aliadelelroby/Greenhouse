<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * Auth User repository for managing authentication-enabled users
 * Single Responsibility: Handle authenticated user data operations
 * Works with the main 'user' table that has login and password fields
 */
class AuthUserRepository
{
    private mysqli $connection;
    
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    /**
     * Create a new authenticated user
     */
    public function createAuthUser(string $name, string $email, string $login, string $password, int $userType, ?int $companyId = null): int
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name_user, email_user, login, hash_pwd, type_user, id_company, enabled) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare auth user insert: " . $this->connection->error);
        }
        
        $stmt->bind_param("ssssii", $name, $email, $login, $hashedPassword, $userType, $companyId);
        
        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to create auth user: " . $stmt->error);
        }
        
        $userId = $this->connection->insert_id;
        $stmt->close();
        
        return $userId;
    }

    /**
     * Find all authenticated users with company information
     */
    public function findAllAuthUsers(): array
    {
        $sql = "SELECT 
                    u.id_user,
                    u.name_user,
                    u.lastname_user,
                    u.email_user,
                    u.login,
                    u.type_user,
                    u.enabled,
                    u.last_login,
                    c.Name_company
                FROM users u
                LEFT JOIN company c ON u.id_company = c.Id_company
                ORDER BY u.name_user";
        
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute auth users query: " . $this->connection->error);
        }
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id_user' => (int)$row['id_user'],
                'name_user' => $row['name_user'],
                'lastname_user' => $row['lastname_user'] ?? '',
                'email_user' => $row['email_user'],
                'login' => $row['login'],
                'type_user' => (int)$row['type_user'],
                'status_user' => $row['enabled'] ? 'active' : 'inactive',
                'last_login' => $row['last_login'],
                'company_name' => $row['Name_company']
            ];
        }
        
        return $users;
    }

    /**
     * Change user password
     */
    public function changeUserPassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET hash_pwd = ? WHERE id_user = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare password change: " . $this->connection->error);
        }
        
        $stmt->bind_param("si", $hashedPassword, $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Reset user password to a random password
     */
    public function resetUserPassword(int $userId): ?string
    {
        // Generate a random password
        $newPassword = $this->generateRandomPassword();
        
        if ($this->changeUserPassword($userId, $newPassword)) {
            return $newPassword;
        }
        
        return null;
    }

    /**
     * Update user status
     */
    public function updateUserStatus(int $userId, string $status): bool
    {
        $enabled = $status === 'active' ? 1 : 0;
        
        $sql = "UPDATE users SET enabled = ? WHERE id_user = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare user status update: " . $this->connection->error);
        }
        
        $stmt->bind_param("ii", $enabled, $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Delete user
     */
    public function deleteUser(int $userId): bool
    {
        $sql = "DELETE FROM users WHERE id_user = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare user delete: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    /**
     * Get user by ID
     */
    public function findUserById(int $userId): ?array
    {
        $sql = "SELECT 
                    u.id_user,
                    u.name_user,
                    u.lastname_user,
                    u.email_user,
                    u.login,
                    u.type_user,
                    u.enabled,
                    u.last_login,
                    c.Name_company
                FROM users u
                LEFT JOIN company c ON u.id_company = c.Id_company
                WHERE u.id_user = ?";
        
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
                'id_user' => (int)$user['id_user'],
                'name_user' => $user['name_user'],
                'lastname_user' => $user['lastname_user'] ?? '',
                'email_user' => $user['email_user'],
                'login' => $user['login'],
                'type_user' => (int)$user['type_user'],
                'status_user' => $user['enabled'] ? 'active' : 'inactive',
                'last_login' => $user['last_login'],
                'company_name' => $user['Name_company']
            ];
        }
        
        return null;
    }

    /**
     * Check if login exists
     */
    public function loginExists(string $login, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT id_user FROM users WHERE login = ?";
        $params = [$login];
        $types = "s";
        
        if ($excludeUserId) {
            $sql .= " AND id_user != ?";
            $params[] = $excludeUserId;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT id_user FROM users WHERE email_user = ?";
        $params = [$email];
        $types = "s";
        
        if ($excludeUserId) {
            $sql .= " AND id_user != ?";
            $params[] = $excludeUserId;
            $types .= "i";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'admin_users' => 0,
            'recent_logins' => 0
        ];
        
        // Count total users
        $result = $this->connection->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $stats['total_users'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Count active users
        $result = $this->connection->query("SELECT COUNT(*) as count FROM users WHERE enabled = 1");
        if ($result) {
            $stats['active_users'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Count admin users
        $result = $this->connection->query("SELECT COUNT(*) as count FROM users WHERE type_user > 0");
        if ($result) {
            $stats['admin_users'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Count recent logins (last 7 days)
        $result = $this->connection->query("SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        if ($result) {
            $stats['recent_logins'] = (int)$result->fetch_assoc()['count'];
        }
        
        return $stats;
    }

    /**
     * Generate a random password
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
} 