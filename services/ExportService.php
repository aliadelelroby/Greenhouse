<?php

require_once __DIR__ . '/../interfaces/RepositoryInterface.php';

/**
 * Export service for handling data export operations
 * Single Responsibility: Handle data export logic
 * Open/Closed Principle: Extensible for new export types
 * Dependency Inversion: Depends on repository interfaces
 */
class ExportService
{
    private DataRepositoryInterface $dataRepository;
    private SensorRepositoryInterface $sensorRepository;
    private GreenhouseRepositoryInterface $greenhouseRepository;
    
    public function __construct(
        DataRepositoryInterface $dataRepository,
        SensorRepositoryInterface $sensorRepository,
        GreenhouseRepositoryInterface $greenhouseRepository
    ) {
        $this->dataRepository = $dataRepository;
        $this->sensorRepository = $sensorRepository;
        $this->greenhouseRepository = $greenhouseRepository;
    }
    
    public function exportData(array $sensorIds, string $type, ?int $greenhouseId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        if (empty($sensorIds)) {
            throw new InvalidArgumentException("No sensor IDs provided");
        }
        
        // Get sensor information for headers
        $sensorInfo = $this->getSensorInfo($sensorIds);
        
        // Get data based on type
        $data = $this->getData($sensorIds, $type, $startDate, $endDate);
        
        // Prepare CSV headers
        $headers = $this->prepareHeaders($sensorIds, $sensorInfo);
        
        // Prepare CSV data
        $csvData = $this->prepareCSVData($data, $sensorIds);
        
        // Prepare filename
        $filename = $this->generateFilename($greenhouseId, $type);
        
        // Prepare metadata
        $metadata = $this->prepareMetadata($greenhouseId, $type, $startDate, $endDate, count($sensorIds));
        
        return [
            'filename' => $filename,
            'headers' => $headers,
            'data' => $csvData,
            'metadata' => $metadata
        ];
    }
    
    private function getSensorInfo(array $sensorIds): array
    {
        $sensorInfo = [];
        foreach ($sensorIds as $sensorId) {
            $sensor = $this->sensorRepository->findById($sensorId);
            if ($sensor) {
                $sensorInfo[$sensorId] = [
                    'name' => $sensor['name_sensor'],
                    'unit' => $sensor['unit_measurement']
                ];
            }
        }
        return $sensorInfo;
    }
    
    private function getData(array $sensorIds, string $type, ?string $startDate, ?string $endDate): array
    {
        if ($type === 'detailed') {
            return $this->dataRepository->findHourlyAveragesBySensorIds($sensorIds, $startDate, $endDate);
        } else {
            return $this->dataRepository->findBySensorIds($sensorIds, $startDate, $endDate);
        }
    }
    
    private function prepareHeaders(array $sensorIds, array $sensorInfo): array
    {
        $headers = ['Timestamp'];
        foreach ($sensorIds as $sensorId) {
            $sensorName = $sensorInfo[$sensorId]['name'] ?? "Sensor_{$sensorId}";
            $sensorUnit = $sensorInfo[$sensorId]['unit'] ?? '°C';
            $headers[] = "{$sensorName} ({$sensorUnit})";
        }
        return $headers;
    }
    
    private function prepareCSVData(array $data, array $sensorIds): array
    {
        // Organize data by timestamp
        $dataByTimestamp = [];
        foreach ($data as $sensorId => $readings) {
            foreach ($readings as $reading) {
                $timestamp = $reading['timestamp'];
                if (!isset($dataByTimestamp[$timestamp])) {
                    $dataByTimestamp[$timestamp] = array_fill_keys($sensorIds, 'N/A');
                }
                $dataByTimestamp[$timestamp][$sensorId] = round($reading['value'], 2);
            }
        }
        
        // Convert to CSV format
        $csvData = [];
        foreach ($dataByTimestamp as $timestamp => $sensorValues) {
            $row = [$timestamp];
            foreach ($sensorIds as $sensorId) {
                $row[] = $sensorValues[$sensorId];
            }
            $csvData[] = $row;
        }
        
        return $csvData;
    }
    
    private function generateFilename(?int $greenhouseId, string $type): string
    {
        $greenhouseName = "greenhouse_{$greenhouseId}";
        if ($greenhouseId) {
            $greenhouse = $this->greenhouseRepository->findById($greenhouseId);
            if ($greenhouse) {
                $greenhouseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $greenhouse['Name_greenhouse']);
            }
        }
        
        $dateSuffix = date('Y-m-d_H-i-s');
        $typeSuffix = ($type === 'detailed') ? 'hourly_avg' : '5min_data';
        
        return "{$greenhouseName}_{$typeSuffix}_{$dateSuffix}.csv";
    }
    
    private function prepareMetadata(?int $greenhouseId, string $type, ?string $startDate, ?string $endDate, int $sensorCount): array
    {
        return [
            'Greenhouse ID' => $greenhouseId ?? 'N/A',
            'Export Type' => ($type === 'detailed') ? 'Hourly Averages' : '5-Minute Intervals',
            'Date Range' => ($startDate ? $startDate : 'All') . ' to ' . ($endDate ? $endDate : 'All'),
            'Export Date' => date('Y-m-d H:i:s'),
            'Total Sensors' => $sensorCount
        ];
    }
} 