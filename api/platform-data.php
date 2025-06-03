<?php
/**
 * Platform Data API with Server-Side Pagination
 * This endpoint provides paginated data for all entity types with SQL-level pagination
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/PermissionService.php';

try {
    $database = Database::getInstance();
    $connection = $database->getConnection();
    $permissionService = new PermissionService($database);
    
    // Check permissions
    if (!$permissionService->canAccessPlatformManagement()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Get pagination parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10))); // Cap at 100 items per page
    $offset = ($page - 1) * $limit;
    $filterType = trim($_GET['filter_type'] ?? '');
    $search = trim($_GET['search'] ?? '');
    
    $entities = [];
    $totalCount = 0;
    
    // Handle filtered vs unfiltered cases differently
    if (!empty($filterType)) {
        // Single entity type - use SQL-level pagination
        switch ($filterType) {
            case 'greenhouse':
                $result = getGreenhousesWithPagination($connection, $offset, $limit, $search);
                $entities = $result['data'];
                $totalCount = $result['total'];
                break;
            case 'sensor':
                $result = getSensorsWithPagination($connection, $offset, $limit, $search);
                $entities = $result['data'];
                $totalCount = $result['total'];
                break;
            case 'user':
                $result = getUsersWithPagination($connection, $offset, $limit, $search);
                $entities = $result['data'];
                $totalCount = $result['total'];
                break;
            case 'company':
                $result = getCompaniesWithPagination($connection, $offset, $limit, $search);
                $entities = $result['data'];
                $totalCount = $result['total'];
                break;
            case 'position':
                $result = getPositionsWithPagination($connection, $offset, $limit, $search);
                $entities = $result['data'];
                $totalCount = $result['total'];
                break;
        }
    } else {
        // Mixed entity types - get all data without pagination, then paginate in PHP
        $allEntities = [];
        
        // Get all data for each type (without SQL pagination)
        $greenhouseData = getAllGreenhouses($connection, $search);
        $allEntities = array_merge($allEntities, $greenhouseData);
        
        $sensorData = getAllSensors($connection, $search);
        $allEntities = array_merge($allEntities, $sensorData);
        
        $userData = getAllUsers($connection, $search);
        $allEntities = array_merge($allEntities, $userData);
        
        $companyData = getAllCompanies($connection, $search);
        $allEntities = array_merge($allEntities, $companyData);
        
        $positionData = getAllPositions($connection, $search);
        $allEntities = array_merge($allEntities, $positionData);
        
        // Sort all entities
        usort($allEntities, function($a, $b) {
            if ($a['type'] !== $b['type']) {
                return strcmp($a['type'], $b['type']);
            }
            return strcmp($a['name'], $b['name']);
        });
        
        // Apply pagination to the sorted results
        $totalCount = count($allEntities);
        $entities = array_slice($allEntities, $offset, $limit);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $entities,
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalCount / $limit)
    ]);
    
} catch (Exception $e) {
    error_log("Platform data API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Get greenhouses with SQL-level pagination
 */
function getGreenhousesWithPagination($connection, $offset, $limit, $search) {
    $entities = [];
    $total = 0;
    
    try {
        // Build WHERE clause for search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (Name_greenhouse LIKE ? OR Id_company LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM greenhouse " . $whereClause;
        $countStmt = $connection->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Get paginated data
        $dataQuery = "
            SELECT 
                Id_greenhouse as id,
                Name_greenhouse as name,
                Id_company,
                X_max,
                Y_max
            FROM greenhouse 
            " . $whereClause . "
            ORDER BY Name_greenhouse ASC
            LIMIT ? OFFSET ?
        ";
        
        $dataStmt = $connection->prepare($dataQuery);
        $allParams = array_merge($params, [$limit, $offset]);
        $types = str_repeat('s', count($params)) . 'ii';
        $dataStmt->bind_param($types, ...$allParams);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'greenhouse',
                'id' => $row['id'],
                'name' => $row['name'],
                'details' => "Company ID: " . ($row['Id_company'] ?? 'N/A') . " | Size: " . ($row['X_max'] ?? 'N/A') . "x" . ($row['Y_max'] ?? 'N/A'),
                'status' => 'Active',
                'lastUpdated' => date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading greenhouses: " . $e->getMessage());
    }
    
    return ['data' => $entities, 'total' => $total];
}

/**
 * Get sensors with SQL-level pagination
 */
function getSensorsWithPagination($connection, $offset, $limit, $search) {
    $entities = [];
    $total = 0;
    
    try {
        // Build WHERE clause for search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (Name_sensor LIKE ? OR Description LIKE ? OR Id_greenhouse LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM sensor " . $whereClause;
        $countStmt = $connection->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Get paginated data
        $dataQuery = "
            SELECT 
                Id_sensor as id,
                Name_sensor as name,
                Description,
                Id_greenhouse,
                Enabled,
                Last_update
            FROM sensor 
            " . $whereClause . "
            ORDER BY Name_sensor ASC
            LIMIT ? OFFSET ?
        ";
        
        $dataStmt = $connection->prepare($dataQuery);
        $allParams = array_merge($params, [$limit, $offset]);
        $types = str_repeat('s', count($params)) . 'ii';
        $dataStmt->bind_param($types, ...$allParams);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'sensor',
                'id' => $row['id'],
                'name' => $row['name'],
                'details' => "Description: " . ($row['Description'] ?? 'N/A') . " | Greenhouse: " . ($row['Id_greenhouse'] ?? 'N/A'),
                'status' => $row['Enabled'] ? 'Active' : 'Inactive',
                'lastUpdated' => $row['Last_update'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading sensors: " . $e->getMessage());
    }
    
    return ['data' => $entities, 'total' => $total];
}

/**
 * Get users with SQL-level pagination
 */
function getUsersWithPagination($connection, $offset, $limit, $search) {
    $entities = [];
    $total = 0;
    
    try {
        // Build WHERE clause for search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (u.name_user LIKE ? OR u.email_user LIKE ? OR c.name_company LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countQuery = "
            SELECT COUNT(DISTINCT u.id_user) as total 
            FROM users u
            LEFT JOIN companies c ON u.id_company = c.id_company
            " . $whereClause;
        $countStmt = $connection->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Get paginated data
        $dataQuery = "
            SELECT 
                u.id_user,
                u.name_user,
                u.email_user,
                u.enabled,
                u.created_at,
                c.name_company
            FROM users u
            LEFT JOIN companies c ON u.id_company = c.id_company
            " . $whereClause . "
            ORDER BY u.name_user ASC
            LIMIT ? OFFSET ?
        ";
        
        $dataStmt = $connection->prepare($dataQuery);
        $allParams = array_merge($params, [$limit, $offset]);
        $types = str_repeat('s', count($params)) . 'ii';
        $dataStmt->bind_param($types, ...$allParams);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'user',
                'id' => $row['id_user'],
                'name' => $row['name_user'],
                'details' => "Email: " . ($row['email_user'] ?? 'N/A') . " | Company: " . ($row['name_company'] ?? 'N/A'),
                'status' => $row['enabled'] ? 'Active' : 'Inactive',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading users: " . $e->getMessage());
    }
    
    return ['data' => $entities, 'total' => $total];
}

/**
 * Get companies with SQL-level pagination
 */
function getCompaniesWithPagination($connection, $offset, $limit, $search) {
    $entities = [];
    $total = 0;
    
    try {
        // Build WHERE clause for search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND name_company LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM companies " . $whereClause;
        $countStmt = $connection->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Get paginated data with user count
        $dataQuery = "
            SELECT 
                c.id_company,
                c.name_company,
                c.created_at,
                COUNT(u.id_user) as user_count
            FROM companies c
            LEFT JOIN users u ON c.id_company = u.id_company
            " . $whereClause . "
            GROUP BY c.id_company, c.name_company, c.created_at
            ORDER BY c.name_company ASC
            LIMIT ? OFFSET ?
        ";
        
        $dataStmt = $connection->prepare($dataQuery);
        $allParams = array_merge($params, [$limit, $offset]);
        $types = str_repeat('s', count($params)) . 'ii';
        $dataStmt->bind_param($types, ...$allParams);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'company',
                'id' => $row['id_company'],
                'name' => $row['name_company'],
                'details' => "Users: " . $row['user_count'],
                'status' => 'Active',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading companies: " . $e->getMessage());
    }
    
    return ['data' => $entities, 'total' => $total];
}

/**
 * Get positions with SQL-level pagination
 */
function getPositionsWithPagination($connection, $offset, $limit, $search) {
    $entities = [];
    $total = 0;
    
    try {
        // Build WHERE clause for search
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND name_position LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM positions " . $whereClause;
        $countStmt = $connection->prepare($countQuery);
        if (!empty($params)) {
            $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        // Get paginated data with user count
        $dataQuery = "
            SELECT 
                p.id_position,
                p.name_position,
                p.created_at,
                COUNT(u.id_user) as user_count
            FROM positions p
            LEFT JOIN users u ON p.id_position = u.id_position
            " . $whereClause . "
            GROUP BY p.id_position, p.name_position, p.created_at
            ORDER BY p.name_position ASC
            LIMIT ? OFFSET ?
        ";
        
        $dataStmt = $connection->prepare($dataQuery);
        $allParams = array_merge($params, [$limit, $offset]);
        $types = str_repeat('s', count($params)) . 'ii';
        $dataStmt->bind_param($types, ...$allParams);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'position',
                'id' => $row['id_position'],
                'name' => $row['name_position'],
                'details' => "Users: " . $row['user_count'],
                'status' => 'Active',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading positions: " . $e->getMessage());
    }
    
    return ['data' => $entities, 'total' => $total];
}

/**
 * Get all greenhouses without pagination (for mixed-type display)
 */
function getAllGreenhouses($connection, $search) {
    $entities = [];
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (Name_greenhouse LIKE ? OR Id_company LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $query = "
            SELECT 
                Id_greenhouse as id,
                Name_greenhouse as name,
                Id_company,
                X_max,
                Y_max
            FROM greenhouse 
            " . $whereClause . "
            ORDER BY Name_greenhouse ASC
        ";
        
        $stmt = $connection->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'greenhouse',
                'id' => $row['id'],
                'name' => $row['name'],
                'details' => "Company ID: " . ($row['Id_company'] ?? 'N/A') . " | Size: " . ($row['X_max'] ?? 'N/A') . "x" . ($row['Y_max'] ?? 'N/A'),
                'status' => 'Active',
                'lastUpdated' => date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading all greenhouses: " . $e->getMessage());
    }
    
    return $entities;
}

/**
 * Get all sensors without pagination (for mixed-type display)
 */
function getAllSensors($connection, $search) {
    $entities = [];
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (Name_sensor LIKE ? OR Description LIKE ? OR Id_greenhouse LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $query = "
            SELECT 
                Id_sensor as id,
                Name_sensor as name,
                Description,
                Id_greenhouse,
                Enabled,
                Last_update
            FROM sensor 
            " . $whereClause . "
            ORDER BY Name_sensor ASC
        ";
        
        $stmt = $connection->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'sensor',
                'id' => $row['id'],
                'name' => $row['name'],
                'details' => "Description: " . ($row['Description'] ?? 'N/A') . " | Greenhouse: " . ($row['Id_greenhouse'] ?? 'N/A'),
                'status' => $row['Enabled'] ? 'Active' : 'Inactive',
                'lastUpdated' => $row['Last_update'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading all sensors: " . $e->getMessage());
    }
    
    return $entities;
}

/**
 * Get all users without pagination (for mixed-type display)
 */
function getAllUsers($connection, $search) {
    $entities = [];
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND (u.name_user LIKE ? OR u.email_user LIKE ? OR c.name_company LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $query = "
            SELECT 
                u.id_user,
                u.name_user,
                u.email_user,
                u.enabled,
                u.created_at,
                c.name_company
            FROM users u
            LEFT JOIN companies c ON u.id_company = c.id_company
            " . $whereClause . "
            ORDER BY u.name_user ASC
        ";
        
        $stmt = $connection->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'user',
                'id' => $row['id_user'],
                'name' => $row['name_user'],
                'details' => "Email: " . ($row['email_user'] ?? 'N/A') . " | Company: " . ($row['name_company'] ?? 'N/A'),
                'status' => $row['enabled'] ? 'Active' : 'Inactive',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading all users: " . $e->getMessage());
    }
    
    return $entities;
}

/**
 * Get all companies without pagination (for mixed-type display)
 */
function getAllCompanies($connection, $search) {
    $entities = [];
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND name_company LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        $query = "
            SELECT 
                c.id_company,
                c.name_company,
                c.created_at,
                COUNT(u.id_user) as user_count
            FROM companies c
            LEFT JOIN users u ON c.id_company = u.id_company
            " . $whereClause . "
            GROUP BY c.id_company, c.name_company, c.created_at
            ORDER BY c.name_company ASC
        ";
        
        $stmt = $connection->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'company',
                'id' => $row['id_company'],
                'name' => $row['name_company'],
                'details' => "Users: " . $row['user_count'],
                'status' => 'Active',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading all companies: " . $e->getMessage());
    }
    
    return $entities;
}

/**
 * Get all positions without pagination (for mixed-type display)
 */
function getAllPositions($connection, $search) {
    $entities = [];
    
    try {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $whereClause .= " AND name_position LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        $query = "
            SELECT 
                p.id_position,
                p.name_position,
                p.created_at,
                COUNT(u.id_user) as user_count
            FROM positions p
            LEFT JOIN users u ON p.id_position = u.id_position
            " . $whereClause . "
            GROUP BY p.id_position, p.name_position, p.created_at
            ORDER BY p.name_position ASC
        ";
        
        $stmt = $connection->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $entities[] = [
                'type' => 'position',
                'id' => $row['id_position'],
                'name' => $row['name_position'],
                'details' => "Users: " . $row['user_count'],
                'status' => 'Active',
                'lastUpdated' => $row['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error loading all positions: " . $e->getMessage());
    }
    
    return $entities;
}
?> 