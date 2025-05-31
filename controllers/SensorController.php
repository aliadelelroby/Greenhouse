<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/SensorRepository.php';
require_once __DIR__ . '/../services/ResponseService.php';

/**
 * Sensor controller for handling sensor-related HTTP requests
 * Single Responsibility: Handle sensor HTTP requests
 * Dependency Inversion: Depends on service interfaces
 */
class SensorController
{
    private SensorRepositoryInterface $sensorRepository;
    private ResponseService $responseService;
    
    public function __construct(SensorRepositoryInterface $sensorRepository, ResponseService $responseService)
    {
        $this->sensorRepository = $sensorRepository;
        $this->responseService = $responseService;
    }
    
    public function getSensors(): void
    {
        try {
            $greenhouseId = $_GET['id'] ?? null;
            $sensorId = $_GET['id_sensor'] ?? null;
            
            if ($sensorId) {
                $this->getSensorById((int)$sensorId);
            } elseif ($greenhouseId) {
                $this->getSensorsByGreenhouseId((int)$greenhouseId);
            } else {
                $this->responseService->errorResponse("Parameter id or id_sensor required", 400);
            }
            
        } catch (Exception $e) {
            error_log("Sensor controller error: " . $e->getMessage());
            $this->responseService->errorResponse("Internal server error", 500);
        }
    }
    
    private function getSensorById(int $sensorId): void
    {
        $sensor = $this->sensorRepository->findById($sensorId);
        
        if ($sensor) {
            $this->responseService->jsonResponse($sensor);
        } else {
            $this->responseService->errorResponse("Sensor not found", 404);
        }
    }
    
    private function getSensorsByGreenhouseId(int $greenhouseId): void
    {
        $sensors = $this->sensorRepository->findByGreenhouseId($greenhouseId);
        $this->responseService->jsonResponse($sensors);
    }
} 