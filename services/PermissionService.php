<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * Permission service for handling user authorization
 * Single Responsibility: Handle permission checking and role-based access control
 * Open/Closed: Extensible for new permission types and roles
 */
class PermissionService
{
    private mysqli $connection;
    
    // User types
    public const USER_TYPE_REGULAR = 0;
    public const USER_TYPE_ADMIN = 1;
    public const USER_TYPE_SUPER_ADMIN = 2;
    
    // Permission levels
    public const PERMISSION_NONE = 0;
    public const PERMISSION_READ = 1;
    public const PERMISSION_WRITE = 2;
    public const PERMISSION_ADMIN = 3;
    
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_login']);
    }

    /**
     * Get current user info from session
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? '',
            'login' => $_SESSION['user_login'] ?? '',
            'type' => (int)($_SESSION['user_type'] ?? 0),
            'company_id' => $_SESSION['company_id'] ?? null
        ];
    }

    /**
     * Check if user has admin privileges
     */
    public function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['type'] >= self::USER_TYPE_ADMIN;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['type'] >= self::USER_TYPE_SUPER_ADMIN;
    }

    /**
     * Check platform management permissions
     */
    public function canAccessPlatformManagement(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can create/delete entities
     */
    public function canCreateEntities(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can delete entities
     */
    public function canDeleteEntities(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can reset passwords
     */
    public function canResetPasswords(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can change other users' passwords
     */
    public function canChangeUserPasswords(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can access greenhouse data
     */
    public function canAccessGreenhouse(?int $greenhouseId = null): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Super admins can access all greenhouses
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        $user = $this->getCurrentUser();
        
        // Regular admins can access all greenhouses
        if ($this->isAdmin()) {
            return true;
        }
        
        // Regular users can only access greenhouses from their company
        if ($greenhouseId && $user['company_id']) {
            return $this->isGreenhouseInUserCompany($greenhouseId, $user['company_id']);
        }
        
        // If no specific greenhouse ID, allow access (will be filtered by company)
        return true;
    }

    /**
     * Check if user can export data
     */
    public function canExportData(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Check if user can view system statistics
     */
    public function canViewSystemStats(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can perform system operations (backup, etc.)
     */
    public function canPerformSystemOperations(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Check if user can modify system settings
     */
    public function canModifySystemSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Get user's accessible greenhouses
     */
    public function getAccessibleGreenhouses(): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }
        
        $user = $this->getCurrentUser();
        
        // Super admins and admins can access all greenhouses
        if ($this->isAdmin()) {
            $sql = "SELECT Id_greenhouse, Name_greenhouse FROM greenhouse ORDER BY Name_greenhouse";
            $result = $this->connection->query($sql);
        } else {
            // Regular users can only access greenhouses from their company
            $sql = "SELECT Id_greenhouse, Name_greenhouse FROM greenhouse WHERE Id_company = ? ORDER BY Name_greenhouse";
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param("i", $user['company_id']);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        $greenhouses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $greenhouses[] = $row;
            }
        }
        
        return $greenhouses;
    }

    /**
     * Filter entities based on user permissions
     */
    public function filterEntitiesByPermissions(array $entities): array
    {
        if (!$this->isAuthenticated()) {
            return [];
        }
        
        // Super admins can see everything
        if ($this->isSuperAdmin()) {
            return $entities;
        }
        
        $user = $this->getCurrentUser();
        
        // Regular admins can see most things but with some restrictions
        if ($this->isAdmin()) {
            return array_filter($entities, function($entity) {
                // Admins can see all entity types
                return true;
            });
        }
        
        // Regular users have very limited access
        return array_filter($entities, function($entity) use ($user) {
            switch ($entity['type']) {
                case 'greenhouse':
                case 'sensor':
                case 'data':
                    // Can see greenhouses/sensors/data from their company only
                    return true; // Company filtering handled elsewhere
                    
                case 'user':
                case 'company':
                case 'position':
                    // Regular users cannot see user management entities
                    return false;
                    
                default:
                    return false;
            }
        });
    }

    /**
     * Check if greenhouse belongs to user's company
     */
    private function isGreenhouseInUserCompany(int $greenhouseId, int $companyId): bool
    {
        $sql = "SELECT Id_greenhouse FROM greenhouse WHERE Id_greenhouse = ? AND Id_company = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $greenhouseId, $companyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    /**
     * Get permission error message for unauthorized access
     */
    public function getPermissionErrorMessage(string $action): string
    {
        if (!$this->isAuthenticated()) {
            return "You must be logged in to {$action}.";
        }
        
        return "You don't have permission to {$action}. Please contact your administrator.";
    }

    /**
     * Require authentication - throws exception if not authenticated
     */
    public function requireAuthentication(): void
    {
        if (!$this->isAuthenticated()) {
            throw new UnauthorizedException('Authentication required');
        }
    }

    /**
     * Require admin privileges - throws exception if not admin
     */
    public function requireAdmin(): void
    {
        $this->requireAuthentication();
        
        if (!$this->isAdmin()) {
            throw new UnauthorizedException('Administrator privileges required');
        }
    }

    /**
     * Require super admin privileges - throws exception if not super admin
     */
    public function requireSuperAdmin(): void
    {
        $this->requireAuthentication();
        
        if (!$this->isSuperAdmin()) {
            throw new UnauthorizedException('Super administrator privileges required');
        }
    }
}

/**
 * Custom exception for unauthorized access
 */
class UnauthorizedException extends Exception
{
    public function __construct($message = "Unauthorized access", $code = 401, ?Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
} 