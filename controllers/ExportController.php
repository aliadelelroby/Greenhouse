<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/DataRepository.php';
require_once __DIR__ . '/../repositories/SensorRepository.php';
require_once __DIR__ . '/../repositories/GreenhouseRepository.php';
require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../services/ResponseService.php';

/**
 * Export controller for handling data export HTTP requests
 * Single Responsibility: Handle export HTTP requests
 * Dependency Inversion: Depends on service interfaces
 */
class ExportController
{
    private ExportService $exportService;
    private ResponseService $responseService;
    
    public function __construct(ExportService $exportService, ResponseService $responseService)
    {
        $this->exportService = $exportService;
        $this->responseService = $responseService;
    }
    
    public function exportData(): void
    {
        try {
            $greenhouseId = isset($_GET['greenhouse_id']) ? (int)$_GET['greenhouse_id'] : null;
            $sensors = $_GET['sensors'] ?? '';
            $type = $_GET['type'] ?? 'quick';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $sensorIds = $this->parseSensorIds($sensors);
            
            if (empty($sensorIds)) {
                $this->responseService->errorResponse("No valid sensor IDs provided", 400);
                return;
            }
            
            $exportData = $this->exportService->exportData(
                $sensorIds,
                $type,
                $greenhouseId,
                $startDate,
                $endDate
            );
            
            $this->responseService->csvDownload(
                $exportData['filename'],
                $exportData['headers'],
                $exportData['data'],
                $exportData['metadata']
            );
            
        } catch (InvalidArgumentException $e) {
            $this->responseService->errorResponse($e->getMessage(), 400);
        } catch (Exception $e) {
            error_log("Export controller error: " . $e->getMessage());
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