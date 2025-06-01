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
        $sql = "SELECT s.Id_sensor, s.Name_sensor, s.Description, s.Enabled
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
} 