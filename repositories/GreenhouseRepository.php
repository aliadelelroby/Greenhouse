<?php

require_once __DIR__ . '/../interfaces/RepositoryInterface.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * Greenhouse repository implementation
 * Single Responsibility: Handle greenhouse data operations
 * Dependency Inversion: Depends on Database abstraction
 */
class GreenhouseRepository implements GreenhouseRepositoryInterface
{
    private mysqli $connection;
    
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }
    
    public function findAll(): array
    {
        $sql = "SELECT Id_greenhouse, Name_greenhouse, Id_company, X_max, Y_max FROM greenhouse ORDER BY Name_greenhouse";
        
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute greenhouse query: " . $this->connection->error);
        }
        
        $greenhouses = [];
        while ($row = $result->fetch_assoc()) {
            $greenhouses[] = [
                'Id_greenhouse' => (int)$row['Id_greenhouse'],
                'Name_greenhouse' => $row['Name_greenhouse'],
                'Id_company' => (int)$row['Id_company'],
                'X_max' => $row['X_max'] ? (int)$row['X_max'] : null,
                'Y_max' => $row['Y_max'] ? (int)$row['Y_max'] : null
            ];
        }
        
        return $greenhouses;
    }
    
    public function findById(int $greenhouseId): ?array
    {
        $sql = "SELECT Id_greenhouse, Name_greenhouse, Id_company, X_max, Y_max FROM greenhouse WHERE Id_greenhouse = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare greenhouse query: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $greenhouseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $greenhouse = $result->fetch_assoc();
        $stmt->close();
        
        if (!$greenhouse) {
            return null;
        }
        
        return [
            'Id_greenhouse' => (int)$greenhouse['Id_greenhouse'],
            'Name_greenhouse' => $greenhouse['Name_greenhouse'],
            'Id_company' => (int)$greenhouse['Id_company'],
            'X_max' => $greenhouse['X_max'] ? (int)$greenhouse['X_max'] : null,
            'Y_max' => $greenhouse['Y_max'] ? (int)$greenhouse['Y_max'] : null
        ];
    }
    
    /**
     * Create a new greenhouse
     */
    public function create(string $name, int $companyId, ?int $xMax = null, ?int $yMax = null): int
    {
        $sql = "INSERT INTO greenhouse (Name_greenhouse, Id_company, X_max, Y_max) VALUES (?, ?, ?, ?)";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare greenhouse insert: " . $this->connection->error);
        }
        
        $stmt->bind_param("siii", $name, $companyId, $xMax, $yMax);
        
        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to create greenhouse: " . $stmt->error);
        }
        
        $greenhouseId = $this->connection->insert_id;
        $stmt->close();
        
        return $greenhouseId;
    }
    
    /**
     * Update an existing greenhouse
     */
    public function update(int $greenhouseId, string $name, int $companyId, ?int $xMax = null, ?int $yMax = null): bool
    {
        $sql = "UPDATE greenhouse SET Name_greenhouse = ?, Id_company = ?, X_max = ?, Y_max = ? WHERE Id_greenhouse = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare greenhouse update: " . $this->connection->error);
        }
        
        $stmt->bind_param("siiii", $name, $companyId, $xMax, $yMax, $greenhouseId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete a greenhouse
     */
    public function delete(int $greenhouseId): bool
    {
        // First check if greenhouse has sensors
        $checkSql = "SELECT COUNT(*) as sensor_count FROM sensor WHERE Id_greenhouse = ?";
        $checkStmt = $this->connection->prepare($checkSql);
        $checkStmt->bind_param("i", $greenhouseId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($row['sensor_count'] > 0) {
            throw new RuntimeException("Cannot delete greenhouse: it has associated sensors");
        }
        
        $sql = "DELETE FROM greenhouse WHERE Id_greenhouse = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare greenhouse delete: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $greenhouseId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get greenhouse statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_greenhouses' => 0,
            'total_sensors' => 0,
            'avg_sensors_per_greenhouse' => 0
        ];
        
        // Count total greenhouses
        $result = $this->connection->query("SELECT COUNT(*) as count FROM greenhouse");
        if ($result) {
            $stats['total_greenhouses'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Count total sensors
        $result = $this->connection->query("SELECT COUNT(*) as count FROM sensor");
        if ($result) {
            $stats['total_sensors'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Calculate average
        if ($stats['total_greenhouses'] > 0) {
            $stats['avg_sensors_per_greenhouse'] = round($stats['total_sensors'] / $stats['total_greenhouses'], 2);
        }
        
        return $stats;
    }
} 