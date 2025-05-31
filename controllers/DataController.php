<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/DataRepository.php';
require_once __DIR__ . '/../services/ResponseService.php';

/**
 * Data controller for handling sensor data HTTP requests
 * Single Responsibility: Handle data HTTP requests
 * Dependency Inversion: Depends on service interfaces
 */
class DataController
{
    private DataRepositoryInterface $dataRepository;
    private ResponseService $responseService;
    
    public function __construct(DataRepositoryInterface $dataRepository, ResponseService $responseService)
    {
        $this->dataRepository = $dataRepository;
        $this->responseService = $responseService;
    }
    
    public function getData(): void
    {
        try {
            $sensors = $_GET['sensors'] ?? '';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $sensorIds = $this->parseSensorIds($sensors);
            
            if (empty($sensorIds)) {
                $this->responseService->jsonResponse([]);
                return;
            }
            
            $data = $this->dataRepository->findBySensorIds($sensorIds, $startDate, $endDate);
            $this->responseService->jsonResponse($data);
            
        } catch (InvalidArgumentException $e) {
            $this->responseService->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            error_log("Data controller error: " . $e->getMessage());
            $this->responseService->errorResponse("Internal server error", 500);
        }
    }
    
    private function parseSensorIds(string $sensors): array
    {
        if (empty($sensors)) {
            return [];
        }
        
        $ids = explode(',', $sensors);
        $ids = array_filter($ids, fn($id) => is_numeric($id));
        
        return array_map('intval', $ids);
    }
} 