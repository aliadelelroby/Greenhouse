<?php

/**
 * Database configuration and connection management
 * Single Responsibility: Handle database connections
 */
class Database
{
    private static $instance = null;
    private $connection;
    
    private function __construct()
    {
        $this->loadEnvironmentConfig();
        $this->connect();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration from .env file if available
     */
    private function loadEnvironmentConfig(?string $envPath = null): void
    {
        $envFile = $envPath ?? __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                if (strpos($line, '=') === false) {
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
    
    /**
     * Get database configuration from environment variables
     */
    private function getDatabaseConfig(): array
    {
        return [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? 'As1234*@',
            'database' => $_ENV['DB_NAME'] ?? 'thermeleondb',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8'
        ];
    }
    
    private function connect(): void
    {
        $config = $this->getDatabaseConfig();
        
        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
        
        if ($this->connection->connect_error) {
            throw new RuntimeException("Database connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset($config['charset']);
    }
    
    public function getConnection(): mysqli
    {
        if (!$this->connection || $this->connection->connect_error) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function close(): void
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
} 