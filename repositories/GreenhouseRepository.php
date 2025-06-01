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
} 