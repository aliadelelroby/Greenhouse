-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2025 at 05:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thermeleondb`
--

-- Drop database if it exists and recreate
DROP DATABASE IF EXISTS `thermeleondb`;
CREATE DATABASE `thermeleondb` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `thermeleondb`;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id_company` int(11) NOT NULL,
  `name_company` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `Id_company` int(11) NOT NULL,
  `Name_company` varchar(128) NOT NULL,
  `Description` text DEFAULT NULL,
  `Enabled` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `culture_type`
--

CREATE TABLE `culture_type` (
  `Id_culture_type` int(11) NOT NULL,
  `Name_culture_type` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE `data` (
  `Id_data` int(11) NOT NULL,
  `Id_sensor` int(11) NOT NULL,
  `Id_greenhouse` int(11) NOT NULL,
  `Value_data` decimal(10,4) DEFAULT NULL,
  `Unit_data` varchar(32) DEFAULT NULL,
  `Date_data` datetime NOT NULL,
  `X_location` int(11) DEFAULT NULL,
  `Y_location` int(11) DEFAULT NULL,
  `Enabled` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `greenhouse`
--

CREATE TABLE `greenhouse` (
  `Id_greenhouse` int(11) NOT NULL,
  `Id_company` int(11) NOT NULL,
  `Name_greenhouse` varchar(64) NOT NULL,
  `X_max` int(11) DEFAULT NULL,
  `Y_max` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `greenhouse_culture`
--

CREATE TABLE `greenhouse_culture` (
  `Id_greenhouse` int(11) NOT NULL,
  `Id_culture_type` int(11) NOT NULL,
  `Crop_description` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id_position` int(11) NOT NULL,
  `name_position` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor`
--

CREATE TABLE `sensor` (
  `Id_sensor` int(11) NOT NULL,
  `Id_sensor_model` int(11) NOT NULL,
  `Id_greenhouse` int(11) NOT NULL,
  `Name_sensor` varchar(128) NOT NULL,
  `Description` text NOT NULL,
  `Last_update` datetime NOT NULL,
  `Enabled` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_model`
--

CREATE TABLE `sensor_model` (
  `Id_sensor_model` int(11) NOT NULL,
  `Id_sensor_type` int(11) NOT NULL,
  `Brand` varchar(128) DEFAULT NULL,
  `Model` varchar(128) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_type`
--

CREATE TABLE `sensor_type` (
  `Id_sensor_type` int(11) NOT NULL,
  `Name_sensor_type` varchar(128) NOT NULL,
  `Description_sensor_type` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `name_user` varchar(255) NOT NULL,
  `lastname_user` varchar(255) DEFAULT NULL,
  `email_user` varchar(255) DEFAULT NULL,
  `login` varchar(64) NOT NULL,
  `hash_pwd` varchar(1028) NOT NULL,
  `type_user` tinyint(4) NOT NULL DEFAULT 0,
  `enabled` tinyint(4) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `id_company` int(11) DEFAULT NULL,
  `id_position` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_greenhouse`
--

CREATE TABLE `user_greenhouse` (
  `Id_user` int(11) NOT NULL,
  `Id_greenhouse` int(11) NOT NULL,
  `Enabled` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weather`
--

CREATE TABLE `weather` (
  `Id_data` int(11) NOT NULL,
  `Date_data` int(11) NOT NULL,
  `Id_station` int(11) NOT NULL,
  `wind_direction` int(11) NOT NULL,
  `wind_speed` float NOT NULL,
  `fog` int(11) NOT NULL,
  `cloud_cover` int(11) NOT NULL,
  `global_radiation` float NOT NULL,
  `snow` int(11) NOT NULL,
  `sunshine_duration` int(11) NOT NULL,
  `temperature` float NOT NULL,
  `dew_point` float NOT NULL,
  `humidity` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id_company`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`Id_company`);

--
-- Indexes for table `culture_type`
--
ALTER TABLE `culture_type`
  ADD PRIMARY KEY (`Id_culture_type`);

--
-- Indexes for table `data` - OPTIMIZED for 8M+ rows
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`Id_data`),
  ADD KEY `idx_data_sensor_date_value` (`Id_sensor`,`Date_data`,`Value_data`),
  ADD KEY `idx_data_greenhouse_date` (`Id_greenhouse`,`Date_data`),
  ADD KEY `idx_data_location` (`X_location`,`Y_location`),
  ADD KEY `idx_data_enabled_date` (`Enabled`,`Date_data` DESC),
  ADD KEY `idx_data_sensor_enabled` (`Id_sensor`,`Enabled`),
  ADD KEY `idx_data_date_enabled` (`Date_data`,`Enabled`),
  ADD KEY `idx_data_value_enabled` (`Value_data`,`Enabled`),
  ADD KEY `idx_data_enabled_value_date` (`Enabled`,`Value_data`,`Date_data` DESC),
  ADD KEY `idx_data_enabled` (`Enabled`);

--
-- Indexes for table `greenhouse`
--
ALTER TABLE `greenhouse`
  ADD PRIMARY KEY (`Id_greenhouse`),
  ADD KEY `idx_greenhouse_company` (`Id_company`);

--
-- Indexes for table `greenhouse_culture`
--
ALTER TABLE `greenhouse_culture`
  ADD PRIMARY KEY (`Id_greenhouse`,`Id_culture_type`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id_position`);

--
-- Indexes for table `sensor` - OPTIMIZED for JOINs with data table
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`Id_sensor`),
  ADD KEY `idx_sensor_greenhouse` (`Id_greenhouse`),
  ADD KEY `idx_sensor_model` (`Id_sensor_model`),
  ADD KEY `idx_sensor_enabled` (`Enabled`),
  ADD KEY `idx_sensor_greenhouse_enabled` (`Id_greenhouse`,`Enabled`),
  ADD KEY `idx_sensor_enabled_greenhouse` (`Enabled`,`Id_greenhouse`);

--
-- Indexes for table `sensor_model`
--
ALTER TABLE `sensor_model`
  ADD PRIMARY KEY (`Id_sensor_model`),
  ADD KEY `idx_sensor_model_type` (`Id_sensor_type`);

--
-- Indexes for table `sensor_type`
--
ALTER TABLE `sensor_type`
  ADD PRIMARY KEY (`Id_sensor_type`);



--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email_user` (`email_user`),
  ADD KEY `id_company` (`id_company`),
  ADD KEY `id_position` (`id_position`);

--
-- Indexes for table `user_greenhouse`
--
ALTER TABLE `user_greenhouse`
  ADD PRIMARY KEY (`Id_user`,`Id_greenhouse`),
  ADD KEY `idx_user_greenhouse_enabled` (`Enabled`);

--
-- Indexes for table `weather`
--
ALTER TABLE `weather`
  ADD PRIMARY KEY (`Id_data`),
  ADD KEY `idx_weather_date_station` (`Date_data`,`Id_station`),
  ADD KEY `idx_weather_temperature` (`temperature`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_remember_expires` (`expires_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_password_reset_expires` (`expires_at`),
  ADD KEY `idx_password_reset_used` (`used`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id_company` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `Id_company` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `culture_type`
--
ALTER TABLE `culture_type`
  MODIFY `Id_culture_type` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data`
--
ALTER TABLE `data`
  MODIFY `Id_data` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `greenhouse`
--
ALTER TABLE `greenhouse`
  MODIFY `Id_greenhouse` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id_position` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor`
--
ALTER TABLE `sensor`
  MODIFY `Id_sensor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_model`
--
ALTER TABLE `sensor_model`
  MODIFY `Id_sensor_model` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensor_type`
--
ALTER TABLE `sensor_type`
  MODIFY `Id_sensor_type` int(11) NOT NULL AUTO_INCREMENT;



--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weather`
--
ALTER TABLE `weather`
  MODIFY `Id_data` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_company`) REFERENCES `companies` (`id_company`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`id_position`) REFERENCES `positions` (`id_position`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Insert sample data for testing
--

-- Insert sample companies (lowercase table)
INSERT INTO `companies` (`name_company`, `created_at`) VALUES
('GreenTech Solutions', '2024-01-15 10:00:00'),
('Eco Gardens Ltd', '2024-02-10 14:30:00'),
('Smart Farm Co', '2024-03-05 09:15:00');

-- Insert sample companies (uppercase table)
INSERT INTO `company` (`Name_company`, `Description`, `Enabled`) VALUES
('Thermeleon Corp', 'Main technology company for greenhouse solutions', 1),
('AgriTech Industries', 'Agricultural technology and automation', 1),
('EcoGrow Technologies', 'Sustainable farming solutions', 1);

-- Insert sample positions
INSERT INTO `positions` (`name_position`, `created_at`) VALUES
('Farm Manager', '2024-01-15 10:05:00'),
('Greenhouse Technician', '2024-01-15 10:10:00'),
('Data Analyst', '2024-01-15 10:15:00'),
('System Administrator', '2024-01-15 10:20:00');

-- Insert sample users (lowercase table with authentication)
INSERT INTO `users` (`name_user`, `lastname_user`, `email_user`, `login`, `hash_pwd`, `type_user`, `enabled`, `last_login`, `id_company`, `id_position`, `created_at`) VALUES
('John', 'Smith', 'john.smith@greentech.com', 'john.smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2024-01-15 10:25:00', 1, 1, '2024-01-15 10:25:00'),
('Sarah', 'Johnson', 'sarah.johnson@greentech.com', 'sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, '2024-01-15 10:30:00', 1, 2, '2024-01-15 10:30:00'),
('Mike', 'Davis', 'mike.davis@ecogardens.com', 'mike.davis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2024-02-10 15:00:00', 2, 1, '2024-02-10 15:00:00'),
('Emma', 'Wilson', 'emma.wilson@smartfarm.com', 'emma.wilson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, '2024-03-05 09:30:00', 3, 3, '2024-03-05 09:30:00'),
('Admin', 'User', 'admin@thermeleon.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2024-01-01 00:00:00', 1, 4, '2024-01-01 00:00:00');



-- Insert sample culture types
INSERT INTO `culture_type` (`Name_culture_type`) VALUES
('Tomatoes'),
('Lettuce'),
('Peppers'),
('Herbs'),
('Cucumbers');

-- Insert sample greenhouses
INSERT INTO `greenhouse` (`Id_company`, `Name_greenhouse`, `X_max`, `Y_max`) VALUES
(1, 'North Greenhouse A1', 100, 50),
(1, 'South Greenhouse B2', 120, 60),
(2, 'Eco Garden House 1', 80, 40),
(3, 'Smart Farm Facility C', 150, 75);

-- Insert greenhouse cultures
INSERT INTO `greenhouse_culture` (`Id_greenhouse`, `Id_culture_type`, `Crop_description`) VALUES
(1, 1, 'Cherry tomatoes - premium variety'),
(1, 4, 'Basil and oregano herbs'),
(2, 2, 'Organic lettuce varieties'),
(3, 3, 'Bell peppers - multiple colors'),
(4, 5, 'Organic cucumbers');

-- Insert sensor types
INSERT INTO `sensor_type` (`Name_sensor_type`, `Description_sensor_type`) VALUES
('Temperature', 'Measures ambient temperature in Celsius'),
('Humidity', 'Measures relative humidity percentage'),
('Soil Moisture', 'Measures soil moisture content'),
('Light Intensity', 'Measures light intensity in lux'),
('CO2 Level', 'Measures CO2 concentration in ppm');

-- Insert sensor models
INSERT INTO `sensor_model` (`Id_sensor_type`, `Brand`, `Model`) VALUES
(1, 'ThermoTech', 'TT-2000'),
(1, 'SensorPro', 'SP-Temp-101'),
(2, 'HumidityMax', 'HM-350'),
(3, 'SoilSense', 'SS-Moisture-Pro'),
(4, 'LightMeter', 'LM-4000'),
(5, 'CO2Monitor', 'CM-Pro-200');

-- Insert 200 sensors for each greenhouse (800 total sensors)
INSERT INTO `sensor` (`Id_sensor_model`, `Id_greenhouse`, `Name_sensor`, `Description`, `Last_update`, `Enabled`)
SELECT 
    -- Rotate through sensor models 1-6 for variety
    ((seq - 1) % 6) + 1 as sensor_model_id,
    gh.Id_greenhouse,
    CONCAT(
        CASE gh.Id_greenhouse
            WHEN 1 THEN 'North-A1'
            WHEN 2 THEN 'South-B2'
            WHEN 3 THEN 'Eco-GH1'
            WHEN 4 THEN 'Smart-C'
        END,
        '-', 
        CASE ((seq - 1) % 5)
            WHEN 0 THEN 'Temp'
            WHEN 1 THEN 'Humid'
            WHEN 2 THEN 'Soil'
            WHEN 3 THEN 'Light'
            WHEN 4 THEN 'CO2'
        END,
        '-',
        LPAD(seq, 3, '0')
    ) as sensor_name,
    CONCAT(
        CASE ((seq - 1) % 5)
            WHEN 0 THEN 'Temperature sensor'
            WHEN 1 THEN 'Humidity sensor'
            WHEN 2 THEN 'Soil moisture sensor'
            WHEN 3 THEN 'Light intensity sensor'
            WHEN 4 THEN 'CO2 concentration sensor'
        END,
        ' - Zone ', 
        CEIL(seq / 10),
        ' Position ', 
        ((seq - 1) % 10) + 1
    ) as description,
    '2024-12-01 15:30:00' as last_update,
    1 as enabled
FROM greenhouse gh
CROSS JOIN (
    SELECT a.N + b.N * 10 + c.N * 100 + 1 as seq
    FROM 
        (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
    CROSS JOIN 
        (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
    CROSS JOIN 
        (SELECT 0 as N UNION SELECT 1) c
    WHERE a.N + b.N * 10 + c.N * 100 + 1 <= 200
) numbers
ORDER BY gh.Id_greenhouse, seq;

-- Insert sample user-greenhouse associations (using users table IDs)
INSERT INTO `user_greenhouse` (`Id_user`, `Id_greenhouse`, `Enabled`) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 1, 1),
(3, 3, 1),
(5, 1, 1),
(5, 2, 1);

-- Generate sample temperature data for the last 7 days
-- Simple approach using INSERT with SELECT to generate realistic data
INSERT INTO `data` (`Id_sensor`, `Id_greenhouse`, `Value_data`, `Unit_data`, `Date_data`, `X_location`, `Y_location`, `Enabled`)
SELECT 
    s.Id_sensor,
    s.Id_greenhouse,
    ROUND(20 + (RAND() * 15) + (SIN(HOUR(timestamp_val) * PI() / 12) * 5), 2) as temperature,
    '°C' as unit,
    timestamp_val as date_time,
    FLOOR(RAND() * 100) + 1 as x_pos,
    FLOOR(RAND() * 50) + 1 as y_pos,
    1 as enabled
FROM sensor s
CROSS JOIN (
    SELECT DATE_SUB(NOW(), INTERVAL seq * 30 MINUTE) as timestamp_val
    FROM (
        SELECT a.N + b.N * 10 + c.N * 100 as seq
        FROM 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c
        ORDER BY seq
        LIMIT 336 -- 7 days * 24 hours * 2 (every 30 minutes)
    ) numbers
) timestamps
WHERE s.Enabled = 1
ORDER BY s.Id_sensor, timestamp_val;

-- Generate sample weather data for the last 7 days
-- Weather data with realistic patterns
INSERT INTO `weather` (`Id_data`, `Date_data`, `Id_station`, `wind_direction`, `wind_speed`, `fog`, `cloud_cover`, `global_radiation`, `snow`, `sunshine_duration`, `temperature`, `dew_point`, `humidity`)
SELECT 
    (@row_number := @row_number + 1) as id_data,
    UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL seq * 60 MINUTE)) as date_timestamp,
    1 as station_id,
    FLOOR(RAND() * 360) as wind_dir,
    ROUND(2 + (RAND() * 15), 1) as wind_spd,
    IF(RAND() < 0.1, 1, 0) as fog_present,
    FLOOR(RAND() * 100) as clouds,
    ROUND(100 + (RAND() * 900) + (SIN(HOUR(timestamp_val) * PI() / 12) * 400), 1) as radiation,
    0 as snow_present,
    FLOOR(RAND() * 60) as sunshine,
    ROUND(15 + (RAND() * 20) + (SIN(HOUR(timestamp_val) * PI() / 12) * 8), 1) as temp,
    ROUND(10 + (RAND() * 15) + (SIN(HOUR(timestamp_val) * PI() / 12) * 5), 1) as dew,
    ROUND(40 + (RAND() * 50), 1) as humid
FROM (
    SELECT 
        seq,
        DATE_SUB(NOW(), INTERVAL seq * 60 MINUTE) as timestamp_val
    FROM (
        SELECT a.N + b.N * 10 + c.N * 100 as seq
        FROM 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1) c
        ORDER BY seq
        LIMIT 168 -- 7 days * 24 hours (every hour)
    ) numbers
) timestamps
CROSS JOIN (SELECT @row_number := 0) r
ORDER BY seq;

COMMIT;

-- ========================================
-- PERFORMANCE OPTIMIZATION NOTES
-- ========================================
-- 
-- The indexes added above are specifically designed to handle:
-- 1. Large data table (8M+ rows) with frequent ORDER BY Date_data DESC queries
-- 2. Common WHERE clauses filtering by Enabled = 1
-- 3. JOIN operations between data and sensor tables
-- 4. Value-based filtering for alerts (temperature thresholds)
-- 
-- Key index explanations:
-- - idx_data_enabled_date: Optimizes ORDER BY Date_data DESC WHERE Enabled = 1
-- - idx_data_sensor_enabled: Optimizes JOINs and sensor-specific queries
-- - idx_data_enabled_value_date: Optimizes alert queries with value ranges
-- - idx_sensor_greenhouse_enabled: Optimizes greenhouse-sensor relationships
-- 
-- These indexes should significantly improve performance for:
-- - platform.php recent activity queries
-- - manager.php alerts queries  
-- - Data export operations
-- - Real-time dashboard updates
--
-- ========================================

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Show database summary
SELECT 'Database initialized successfully!' as Status;
SELECT 
    'Greenhouses' as Entity, 
    COUNT(*) as Count 
FROM greenhouse
UNION ALL
SELECT 
    'Sensors' as Entity, 
    COUNT(*) as Count 
FROM sensor
UNION ALL
SELECT 
    'Data Points' as Entity, 
    COUNT(*) as Count 
FROM data
UNION ALL
SELECT 
    'Users (lowercase)' as Entity, 
    COUNT(*) as Count 
FROM users

UNION ALL
SELECT 
    'Weather Records' as Entity, 
    COUNT(*) as Count 
FROM weather
UNION ALL
SELECT 
    'Companies (lowercase)' as Entity, 
    COUNT(*) as Count 
FROM companies
UNION ALL
SELECT 
    'Companies (uppercase)' as Entity, 
    COUNT(*) as Count 
FROM company
UNION ALL
SELECT 
    'Remember Tokens' as Entity, 
    COUNT(*) as Count 
FROM remember_tokens
UNION ALL
SELECT 
    'Password Reset Tokens' as Entity, 
    COUNT(*) as Count 
FROM password_reset_tokens; 