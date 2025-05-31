<?php

require_once __DIR__ . '/../interfaces/RepositoryInterface.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * Data repository implementation
 * Single Responsibility: Handle sensor data operations
 * Dependency Inversion: Depends on Database abstraction
 */
class DataRepository implements DataRepositoryInterface
{
    private mysqli $connection;
    
    public function __construct(Database $database)
    {
        $this->connection = $database->getConnection();
    }
    
    public function findBySensorIds(array $sensorIds, ?string $startDate = null, ?string $endDate = null): array
    {
        if (empty($sensorIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($sensorIds), '?'));
        $sql = "SELECT 
                    DATE_FORMAT(Date_data, '%Y-%m-%d %H:%i:00') as timestamp,
                    Id_sensor,
                    AVG(Value_data) as value
                FROM data 
                WHERE Id_sensor IN ($placeholders)";
        
        $params = $sensorIds;
        $types = str_repeat('i', count($sensorIds));
        
        if ($startDate) {
            $sql .= " AND Date_data >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        
        if ($endDate) {
            $sql .= " AND Date_data <= DATE_ADD(?, INTERVAL 1 DAY)";
            $params[] = $endDate;
            $types .= 's';
        }
        
        $sql .= " GROUP BY timestamp, Id_sensor ORDER BY timestamp, Id_sensor";
        
        return $this->executeDataQuery($sql, $types, $params);
    }
    
    public function findHourlyAveragesBySensorIds(array $sensorIds, ?string $startDate = null, ?string $endDate = null): array
    {
        if (empty($sensorIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($sensorIds), '?'));
        $sql = "SELECT 
                    DATE_FORMAT(Date_data, '%Y-%m-%d %H:00:00') as hour_timestamp,
                    Id_sensor,
                    AVG(Value_data) as avg_value
                FROM data 
                WHERE Id_sensor IN ($placeholders)";
        
        $params = $sensorIds;
        $types = str_repeat('i', count($sensorIds));
        
        if ($startDate) {
            $sql .= " AND Date_data >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        
        if ($endDate) {
            $sql .= " AND Date_data <= DATE_ADD(?, INTERVAL 1 DAY)";
            $params[] = $endDate;
            $types .= 's';
        }
        
        $sql .= " GROUP BY hour_timestamp, Id_sensor ORDER BY hour_timestamp, Id_sensor";
        
        return $this->executeDataQuery($sql, $types, $params, 'hour_timestamp', 'avg_value');
    }
    
    private function executeDataQuery(string $sql, string $types, array $params, string $timestampField = 'timestamp', string $valueField = 'value'): array
    {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare data query: " . $this->connection->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $sensorId = (int)$row['Id_sensor'];
            $timestamp = $row[$timestampField];
            $value = (float)$row[$valueField];
            
            if (!isset($data[$sensorId])) {
                $data[$sensorId] = [];
            }
            
            $data[$sensorId][] = [
                'timestamp' => $timestamp,
                'value' => $value
            ];
        }
        
        $stmt->close();
        return $data;
    }
} 