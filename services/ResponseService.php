<?php

/**
 * Response service for handling HTTP responses
 * Single Responsibility: Handle all HTTP response formatting
 */
class ResponseService
{
    public function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    public function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['error' => $message], $statusCode);
    }
    
    public function success(array $data, int $statusCode = 200): void
    {
        $this->jsonResponse(['success' => true, 'data' => $data], $statusCode);
    }
    
    public function error(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['success' => false, 'message' => $message], $statusCode);
    }
    
    public function csvDownload(string $filename, array $headers, array $data, array $metadata = []): void
    {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, $headers, ',', '"', '"');
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '"');
        }
        
        // Add metadata if provided
        if (!empty($metadata)) {
            fputcsv($output, [], ',', '"', '"');
            fputcsv($output, ['Export Information:'], ',', '"', '"');
            foreach ($metadata as $key => $value) {
                fputcsv($output, [$key, $value], ',', '"', '"');
            }
        }
        
        fclose($output);
    }
} 