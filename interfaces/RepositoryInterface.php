<?php

/**
 * Repository interface for dependency inversion
 * Interface Segregation: Specific contracts for data operations
 */
interface SensorRepositoryInterface
{
    public function findByGreenhouseId(int $greenhouseId): array;
    public function findById(int $sensorId): ?array;
}

interface GreenhouseRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $greenhouseId): ?array;
}

interface DataRepositoryInterface
{
    public function findBySensorIds(array $sensorIds, ?string $startDate = null, ?string $endDate = null): array;
    public function findHourlyAveragesBySensorIds(array $sensorIds, ?string $startDate = null, ?string $endDate = null): array;
}

interface UserRepositoryInterface
{
    public function findAllUsers(): array;
    public function findAllCompanies(): array;
    public function findAllPositions(): array;
    public function createUser(string $name, string $email, ?int $companyId = null, ?int $positionId = null): int;
    public function createCompany(string $name): int;
    public function createPosition(string $name): int;
    public function updateUserStatus(int $userId, string $status): bool;
    public function updateCompanyStatus(int $companyId, string $status): bool;
    public function updatePositionStatus(int $positionId, string $status): bool;
    public function deleteUser(int $userId): bool;
    public function deleteCompany(int $companyId): bool;
    public function deletePosition(int $positionId): bool;
    public function getUserStatistics(): array;
    public function initializeTables(): void;
} 