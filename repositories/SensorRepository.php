<?php

require_once __DIR__ . '/../interfaces/RepositoryInterface.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * Sensor repository implementation
 * Single Responsibility: Handle sensor data operations
 * Dependency Inversion: Depends on Database abstraction
 */
class SensorRepository implements SensorRepositoryInterface
{
    private mysqli $connection;
    
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }
    
    public function findByGreenhouseId(int $greenhouseId): array
    {
        $sql = "SELECT s.Id_sensor, s.Name_sensor, s.Description, s.Enabled
                FROM sensor s 
                WHERE s.Id_greenhouse = ? AND s.Enabled = 1";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor query: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $greenhouseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sensors = [];
        while ($row = $result->fetch_assoc()) {
            $sensors[] = [
                'id_sensor' => (int)$row['Id_sensor'],
                'name_sensor' => $row['Name_sensor'],
                'description' => $row['Description'],
                'enabled' => (int)$row['Enabled'],
                // Backward compatibility
                'id' => (int)$row['Id_sensor'],
                'name' => $row['Name_sensor']
            ];
        }
        
        $stmt->close();
        return $sensors;
    }
    
    public function findById(int $sensorId): ?array
    {
        $sql = "SELECT s.Id_sensor, s.Name_sensor, s.Description, s.Enabled, s.Id_greenhouse
                FROM sensor s 
                WHERE s.Id_sensor = ? AND s.Enabled = 1";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor query: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $sensorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sensor = $result->fetch_assoc();
        $stmt->close();
        
        if (!$sensor) {
            return null;
        }
        
        return [
            'id_sensor' => (int)$sensor['Id_sensor'],
            'name_sensor' => $sensor['Name_sensor'],
            'description' => $sensor['Description'],
            'enabled' => (int)$sensor['Enabled'],
            'id_greenhouse' => (int)$sensor['Id_greenhouse'],
            // Backward compatibility
            'id' => (int)$sensor['Id_sensor'],
            'name' => $sensor['Name_sensor']
        ];
    }
    
    public function findAll(): array
    {
        $sql = "SELECT s.Id_sensor, s.Name_sensor, s.Description, s.Enabled, s.Id_greenhouse
                FROM sensor s 
                WHERE s.Enabled = 1
                ORDER BY s.Name_sensor";
        
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new RuntimeException("Failed to execute sensor query: " . $this->connection->error);
        }
        
        $sensors = [];
        while ($row = $result->fetch_assoc()) {
            $sensors[] = [
                'id_sensor' => (int)$row['Id_sensor'],
                'name_sensor' => $row['Name_sensor'],
                'description' => $row['Description'],
                'enabled' => (int)$row['Enabled'],
                'id_greenhouse' => (int)$row['Id_greenhouse'],
                // Backward compatibility
                'id' => (int)$row['Id_sensor'],
                'name' => $row['Name_sensor']
            ];
        }
        
        return $sensors;
    }
    
    /**
     * Create a new sensor
     */
    public function create(string $name, string $description, int $greenhouseId, int $sensorModelId = 1, int $enabled = 1): int
    {
        $currentTimestamp = date('Y-m-d H:i:s');
        $sql = "INSERT INTO sensor (Name_sensor, Description, Id_greenhouse, Id_sensor_model, Enabled, Last_update) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor insert: " . $this->connection->error);
        }
        
        $stmt->bind_param("ssiiss", $name, $description, $greenhouseId, $sensorModelId, $enabled, $currentTimestamp);
        
        if (!$stmt->execute()) {
            throw new RuntimeException("Failed to create sensor: " . $stmt->error);
        }
        
        $sensorId = $this->connection->insert_id;
        $stmt->close();
        
        return $sensorId;
    }
    
    /**
     * Update an existing sensor
     */
    public function update(int $sensorId, string $name, string $description, int $greenhouseId, int $enabled = 1): bool
    {
        $sql = "UPDATE sensor SET Name_sensor = ?, Description = ?, Id_greenhouse = ?, Enabled = ? WHERE Id_sensor = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor update: " . $this->connection->error);
        }
        
        $stmt->bind_param("ssiii", $name, $description, $greenhouseId, $enabled, $sensorId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Delete a sensor (soft delete by setting Enabled = 0)
     */
    public function delete(int $sensorId): bool
    {
        $sql = "UPDATE sensor SET Enabled = 0 WHERE Id_sensor = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor delete: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $sensorId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Permanently delete a sensor and its data
     */
    public function hardDelete(int $sensorId): bool
    {
        // Start transaction
        $this->connection->begin_transaction();
        
        try {
            // Delete sensor data first
            $dataStmt = $this->connection->prepare("DELETE FROM data WHERE Id_sensor = ?");
            $dataStmt->bind_param("i", $sensorId);
            $dataStmt->execute();
            $dataStmt->close();
            
            // Delete sensor
            $sensorStmt = $this->connection->prepare("DELETE FROM sensor WHERE Id_sensor = ?");
            $sensorStmt->bind_param("i", $sensorId);
            $result = $sensorStmt->execute();
            $sensorStmt->close();
            
            $this->connection->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->connection->rollback();
            throw new RuntimeException("Failed to permanently delete sensor: " . $e->getMessage());
        }
    }
    
    /**
     * Toggle sensor enabled status
     */
    public function toggleEnabled(int $sensorId): bool
    {
        $sql = "UPDATE sensor SET Enabled = 1 - Enabled WHERE Id_sensor = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare sensor toggle: " . $this->connection->error);
        }
        
        $stmt->bind_param("i", $sensorId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get sensor statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_sensors' => 0,
            'enabled_sensors' => 0,
            'disabled_sensors' => 0,
            'sensors_by_greenhouse' => []
        ];
        
        // Count total sensors
        $result = $this->connection->query("SELECT COUNT(*) as count FROM sensor");
        if ($result) {
            $stats['total_sensors'] = (int)$result->fetch_assoc()['count'];
        }
        
        // Count enabled sensors
        $result = $this->connection->query("SELECT COUNT(*) as count FROM sensor WHERE Enabled = 1");
        if ($result) {
            $stats['enabled_sensors'] = (int)$result->fetch_assoc()['count'];
        }
        
        $stats['disabled_sensors'] = $stats['total_sensors'] - $stats['enabled_sensors'];
        
        // Sensors by greenhouse
        $result = $this->connection->query("
            SELECT 
                g.Name_greenhouse, 
                COUNT(s.Id_sensor) as sensor_count 
            FROM greenhouse g 
            LEFT JOIN sensor s ON g.Id_greenhouse = s.Id_greenhouse 
            GROUP BY g.Id_greenhouse, g.Name_greenhouse
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['sensors_by_greenhouse'][] = [
                    'greenhouse_name' => $row['Name_greenhouse'],
                    'sensor_count' => (int)$row['sensor_count']
                ];
            }
        }
        
        return $stats;
    }
} 