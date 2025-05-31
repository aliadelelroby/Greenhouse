<?php

/**
 * Database configuration and connection management
 * Single Responsibility: Handle database connections
 */
class Database
{
    private static $instance = null;
    private $connection;
    
    private const HOST = 'localhost';
    private const USER = 'root';
    private const PASSWORD = 'As1234*@';
    private const DATABASE = 'thermeleondb';
    
    private function __construct()
    {
        $this->connect();
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect(): void
    {
        $this->connection = new mysqli(
            self::HOST,
            self::USER,
            self::PASSWORD,
            self::DATABASE
        );
        
        if ($this->connection->connect_error) {
            throw new RuntimeException("Database connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
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