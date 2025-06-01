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
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `Id_user` int(11) NOT NULL,
  `Id_company` int(11) NOT NULL,
  `Name_user` varchar(128) NOT NULL,
  `Lastname_user` varchar(128) DEFAULT NULL,
  `Email_user` varchar(64) DEFAULT NULL,
  `Login` varchar(64) NOT NULL,
  `Hash_pwd` varchar(1028) NOT NULL,
  `Type_user` tinyint(4) NOT NULL,
  `Enabled` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `name_user` varchar(255) NOT NULL,
  `email_user` varchar(255) DEFAULT NULL,
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
-- Indexes for table `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`Id_data`),
  ADD KEY `idx_data_sensor_date_value` (`Id_sensor`,`Date_data`,`Value_data`);

--
-- Indexes for table `greenhouse`
--
ALTER TABLE `greenhouse`
  ADD PRIMARY KEY (`Id_greenhouse`);

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
-- Indexes for table `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`Id_sensor`);

--
-- Indexes for table `sensor_model`
--
ALTER TABLE `sensor_model`
  ADD PRIMARY KEY (`Id_sensor_model`);

--
-- Indexes for table `sensor_type`
--
ALTER TABLE `sensor_type`
  ADD PRIMARY KEY (`Id_sensor_type`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`Id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email_user` (`email_user`),
  ADD KEY `id_company` (`id_company`),
  ADD KEY `id_position` (`id_position`);

--
-- Indexes for table `user_greenhouse`
--
ALTER TABLE `user_greenhouse`
  ADD PRIMARY KEY (`Id_user`,`Id_greenhouse`);

--
-- Indexes for table `weather`
--
ALTER TABLE `weather`
  ADD PRIMARY KEY (`Id_data`);

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
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `Id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

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

-- Insert sample users (lowercase table)
INSERT INTO `users` (`name_user`, `email_user`, `id_company`, `id_position`, `created_at`) VALUES
('John Smith', 'john.smith@greentech.com', 1, 1, '2024-01-15 10:25:00'),
('Sarah Johnson', 'sarah.johnson@greentech.com', 1, 2, '2024-01-15 10:30:00'),
('Mike Davis', 'mike.davis@ecogardens.com', 2, 1, '2024-02-10 15:00:00'),
('Emma Wilson', 'emma.wilson@smartfarm.com', 3, 3, '2024-03-05 09:30:00');

-- Insert sample users (uppercase table)
INSERT INTO `user` (`Id_company`, `Name_user`, `Lastname_user`, `Email_user`, `Login`, `Hash_pwd`, `Type_user`, `Enabled`) VALUES
(1, 'Admin', 'User', 'admin@thermeleon.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
(2, 'Manager', 'User', 'manager@agritech.com', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1),
(3, 'Operator', 'User', 'operator@ecogrow.com', 'operator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1);

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

-- Insert sample sensors
INSERT INTO `sensor` (`Id_sensor_model`, `Id_greenhouse`, `Name_sensor`, `Description`, `Last_update`, `Enabled`) VALUES
(1, 1, 'North-A1-Temp-01', 'Temperature sensor in north section', '2024-12-01 15:30:00', 1),
(1, 1, 'North-A1-Temp-02', 'Temperature sensor in center section', '2024-12-01 15:30:00', 1),
(2, 1, 'North-A1-Temp-03', 'Backup temperature sensor', '2024-12-01 15:30:00', 1),
(3, 1, 'North-A1-Humid-01', 'Humidity sensor main zone', '2024-12-01 15:30:00', 1),
(1, 2, 'South-B2-Temp-01', 'Temperature sensor zone 1', '2024-12-01 15:30:00', 1),
(1, 2, 'South-B2-Temp-02', 'Temperature sensor zone 2', '2024-12-01 15:30:00', 1),
(3, 2, 'South-B2-Humid-01', 'Humidity monitor central', '2024-12-01 15:30:00', 1),
(1, 3, 'Eco-GH1-Temp-01', 'Main temperature monitor', '2024-12-01 15:30:00', 1),
(4, 3, 'Eco-GH1-Soil-01', 'Soil moisture detector', '2024-12-01 15:30:00', 1),
(1, 4, 'Smart-C-Temp-01', 'Primary temperature sensor', '2024-12-01 15:30:00', 1),
(1, 4, 'Smart-C-Temp-02', 'Secondary temperature sensor', '2024-12-01 15:30:00', 1),
(5, 4, 'Smart-C-Light-01', 'Light intensity meter', '2024-12-01 15:30:00', 1);

-- Insert sample user-greenhouse associations
INSERT INTO `user_greenhouse` (`Id_user`, `Id_greenhouse`, `Enabled`) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 1, 1),
(3, 3, 1);

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

COMMIT;

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
    'Users (uppercase)' as Entity, 
    COUNT(*) as Count 
FROM user
UNION ALL
SELECT 
    'Companies (lowercase)' as Entity, 
    COUNT(*) as Count 
FROM companies
UNION ALL
SELECT 
    'Companies (uppercase)' as Entity, 
    COUNT(*) as Count 
FROM company; 