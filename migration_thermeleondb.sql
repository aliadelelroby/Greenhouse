-- Migration file to update thermeleondb structure
-- This migration transforms the current database to match the enhanced structure

USE `thermeleondb`;

-- Start transaction for safe migration
START TRANSACTION;

-- ============================================================================
-- 1. UPDATE USERS TABLE STRUCTURE
-- ============================================================================

-- Add missing columns to users table
ALTER TABLE `users` 
ADD COLUMN `lastname_user` varchar(255) DEFAULT NULL AFTER `name_user`,
ADD COLUMN `login` varchar(64) NOT NULL AFTER `email_user`,
ADD COLUMN `hash_pwd` varchar(1028) NOT NULL AFTER `login`,
ADD COLUMN `type_user` tinyint(4) NOT NULL DEFAULT 0 AFTER `hash_pwd`,
ADD COLUMN `enabled` tinyint(4) NOT NULL DEFAULT 1 AFTER `type_user`,
ADD COLUMN `last_login` datetime DEFAULT NULL AFTER `enabled`;

-- Add unique constraint on login
ALTER TABLE `users` ADD UNIQUE KEY `login` (`login`);

-- ============================================================================
-- 2. DROP OLD USER TABLE (if exists)
-- ============================================================================

-- Drop the old user table with uppercase columns as it's replaced by enhanced users table
DROP TABLE IF EXISTS `user`;

-- ============================================================================
-- 3. ADD MISSING AUTO_INCREMENT SETTINGS
-- ============================================================================

ALTER TABLE `company` MODIFY `Id_company` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `culture_type` MODIFY `Id_culture_type` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `greenhouse` MODIFY `Id_greenhouse` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sensor` MODIFY `Id_sensor` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sensor_model` MODIFY `Id_sensor_model` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sensor_type` MODIFY `Id_sensor_type` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `weather` MODIFY `Id_data` int(11) NOT NULL AUTO_INCREMENT;

-- ============================================================================
-- 4. ADD MISSING INDEXES FOR PERFORMANCE
-- ============================================================================

-- Add performance indexes to existing tables
ALTER TABLE `data` ADD INDEX `idx_data_sensor_date_value` (`Id_sensor`, `Date_data`, `Value_data`);
ALTER TABLE `data` ADD INDEX `idx_data_greenhouse_date` (`Id_greenhouse`, `Date_data`);
ALTER TABLE `data` ADD INDEX `idx_data_location` (`X_location`, `Y_location`);

-- Add indexes for sensor relationships
ALTER TABLE `sensor` ADD INDEX `idx_sensor_greenhouse` (`Id_greenhouse`);
ALTER TABLE `sensor` ADD INDEX `idx_sensor_model` (`Id_sensor_model`);
ALTER TABLE `sensor` ADD INDEX `idx_sensor_enabled` (`Enabled`);

-- Add indexes for greenhouse relationships
ALTER TABLE `greenhouse` ADD INDEX `idx_greenhouse_company` (`Id_company`);

-- Add indexes for sensor model relationships
ALTER TABLE `sensor_model` ADD INDEX `idx_sensor_model_type` (`Id_sensor_type`);

-- Add indexes for user greenhouse relationships
ALTER TABLE `user_greenhouse` ADD INDEX `idx_user_greenhouse_enabled` (`Enabled`);

-- Add indexes for weather data
ALTER TABLE `weather` ADD INDEX `idx_weather_date_station` (`Date_data`, `Id_station`);
ALTER TABLE `weather` ADD INDEX `idx_weather_temperature` (`temperature`);

-- ============================================================================
-- 5. CREATE NEW AUTHENTICATION TABLES
-- ============================================================================

-- Create remember_tokens table for "Remember Me" functionality
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_remember_expires` (`expires_at`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create password_reset_tokens table for password reset functionality
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_password_reset_expires` (`expires_at`),
  KEY `idx_password_reset_used` (`used`),
  CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================================
-- 6. INSERT SAMPLE DATA (if tables are empty)
-- ============================================================================

-- Insert sample companies (only if table is empty)
INSERT INTO `companies` (`name_company`, `created_at`) 
SELECT * FROM (
  SELECT 'GreenTech Solutions' as name_company, '2024-01-15 10:00:00' as created_at
  UNION ALL
  SELECT 'Eco Gardens Ltd', '2024-02-10 14:30:00'
  UNION ALL
  SELECT 'Smart Farm Co', '2024-03-05 09:15:00'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `companies` LIMIT 1);

-- Insert sample companies (uppercase table, only if empty)
INSERT INTO `company` (`Name_company`, `Description`, `Enabled`)
SELECT * FROM (
  SELECT 'Thermeleon Corp' as Name_company, 'Main technology company for greenhouse solutions' as Description, 1 as Enabled
  UNION ALL
  SELECT 'AgriTech Industries', 'Agricultural technology and automation', 1
  UNION ALL
  SELECT 'EcoGrow Technologies', 'Sustainable farming solutions', 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `company` LIMIT 1);

-- Insert sample positions (only if table is empty)
INSERT INTO `positions` (`name_position`, `created_at`)
SELECT * FROM (
  SELECT 'Farm Manager' as name_position, '2024-01-15 10:05:00' as created_at
  UNION ALL
  SELECT 'Greenhouse Technician', '2024-01-15 10:10:00'
  UNION ALL
  SELECT 'Data Analyst', '2024-01-15 10:15:00'
  UNION ALL
  SELECT 'System Administrator', '2024-01-15 10:20:00'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `positions` LIMIT 1);

-- Update existing users with default values for new columns (if any exist)
UPDATE `users` SET 
  `lastname_user` = CASE 
    WHEN `lastname_user` IS NULL THEN 'User' 
    ELSE `lastname_user` 
  END,
  `login` = CASE 
    WHEN `login` = '' OR `login` IS NULL THEN LOWER(REPLACE(`name_user`, ' ', '.'))
    ELSE `login` 
  END,
  `hash_pwd` = CASE 
    WHEN `hash_pwd` = '' OR `hash_pwd` IS NULL THEN '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    ELSE `hash_pwd`
  END,
  `type_user` = CASE 
    WHEN `type_user` IS NULL THEN 1 
    ELSE `type_user` 
  END,
  `enabled` = CASE 
    WHEN `enabled` IS NULL THEN 1 
    ELSE `enabled` 
  END
WHERE EXISTS (SELECT 1 FROM `users` LIMIT 1);

-- Insert sample users with authentication (only if no users exist)
INSERT INTO `users` (`name_user`, `lastname_user`, `email_user`, `login`, `hash_pwd`, `type_user`, `enabled`, `last_login`, `id_company`, `id_position`, `created_at`)
SELECT * FROM (
  SELECT 'John' as name_user, 'Smith' as lastname_user, 'john.smith@greentech.com' as email_user, 'john.smith' as login, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as hash_pwd, 1 as type_user, 1 as enabled, '2024-01-15 10:25:00' as last_login, 1 as id_company, 1 as id_position, '2024-01-15 10:25:00' as created_at
  UNION ALL
  SELECT 'Sarah', 'Johnson', 'sarah.johnson@greentech.com', 'sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, '2024-01-15 10:30:00', 1, 2, '2024-01-15 10:30:00'
  UNION ALL
  SELECT 'Mike', 'Davis', 'mike.davis@ecogardens.com', 'mike.davis', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2024-02-10 15:00:00', 2, 1, '2024-02-10 15:00:00'
  UNION ALL
  SELECT 'Emma', 'Wilson', 'emma.wilson@smartfarm.com', 'emma.wilson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, '2024-03-05 09:30:00', 3, 3, '2024-03-05 09:30:00'
  UNION ALL
  SELECT 'Admin', 'User', 'admin@thermeleon.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2024-01-01 00:00:00', 1, 4, '2024-01-01 00:00:00'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `login` IS NOT NULL AND `login` != '' LIMIT 1);

-- ============================================================================
-- 7. INSERT ADDITIONAL SAMPLE DATA (if tables are empty)
-- ============================================================================

-- Insert culture types
INSERT INTO `culture_type` (`Name_culture_type`)
SELECT * FROM (
  SELECT 'Tomatoes' as Name_culture_type
  UNION ALL SELECT 'Lettuce'
  UNION ALL SELECT 'Peppers'
  UNION ALL SELECT 'Herbs'
  UNION ALL SELECT 'Cucumbers'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `culture_type` LIMIT 1);

-- Insert sample greenhouses
INSERT INTO `greenhouse` (`Id_company`, `Name_greenhouse`, `X_max`, `Y_max`)
SELECT * FROM (
  SELECT 1 as Id_company, 'North Greenhouse A1' as Name_greenhouse, 100 as X_max, 50 as Y_max
  UNION ALL SELECT 1, 'South Greenhouse B2', 120, 60
  UNION ALL SELECT 2, 'Eco Garden House 1', 80, 40
  UNION ALL SELECT 3, 'Smart Farm Facility C', 150, 75
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `greenhouse` LIMIT 1);

-- Insert sensor types
INSERT INTO `sensor_type` (`Name_sensor_type`, `Description_sensor_type`)
SELECT * FROM (
  SELECT 'Temperature' as Name_sensor_type, 'Measures ambient temperature in Celsius' as Description_sensor_type
  UNION ALL SELECT 'Humidity', 'Measures relative humidity percentage'
  UNION ALL SELECT 'Soil Moisture', 'Measures soil moisture content'
  UNION ALL SELECT 'Light Intensity', 'Measures light intensity in lux'
  UNION ALL SELECT 'CO2 Level', 'Measures CO2 concentration in ppm'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `sensor_type` LIMIT 1);

-- Insert sensor models
INSERT INTO `sensor_model` (`Id_sensor_type`, `Brand`, `Model`)
SELECT * FROM (
  SELECT 1 as Id_sensor_type, 'ThermoTech' as Brand, 'TT-2000' as Model
  UNION ALL SELECT 1, 'SensorPro', 'SP-Temp-101'
  UNION ALL SELECT 2, 'HumidityMax', 'HM-350'
  UNION ALL SELECT 3, 'SoilSense', 'SS-Moisture-Pro'
  UNION ALL SELECT 4, 'LightMeter', 'LM-4000'
  UNION ALL SELECT 5, 'CO2Monitor', 'CM-Pro-200'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `sensor_model` LIMIT 1);

-- ============================================================================
-- 8. VERIFY MIGRATION SUCCESS
-- ============================================================================

-- Display migration results
SELECT 'Migration completed successfully!' as Status;

SELECT 'Database Structure Summary' as Info;
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
    'Users' as Entity, 
    COUNT(*) as Count 
FROM users
UNION ALL
SELECT 
    'Positions' as Entity, 
    COUNT(*) as Count 
FROM positions
UNION ALL
SELECT 
    'Greenhouses' as Entity, 
    COUNT(*) as Count 
FROM greenhouse
UNION ALL
SELECT 
    'Sensor Types' as Entity, 
    COUNT(*) as Count 
FROM sensor_type
UNION ALL
SELECT 
    'Sensor Models' as Entity, 
    COUNT(*) as Count 
FROM sensor_model
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

-- Show updated users table structure
DESCRIBE users;

COMMIT;

-- ============================================================================
-- MIGRATION NOTES:
-- ============================================================================
-- 1. Added missing columns to users table: lastname_user, login, hash_pwd, type_user, enabled, last_login
-- 2. Added unique constraint on login column
-- 3. Removed duplicate user table with uppercase columns
-- 4. Added AUTO_INCREMENT to missing tables
-- 5. Added performance indexes for all major tables
-- 6. Created remember_tokens table for authentication
-- 7. Created password_reset_tokens table for password reset functionality
-- 8. Added foreign key constraints for authentication tables
-- 9. Inserted sample data only if tables are empty to avoid conflicts
-- 10. Updated existing users with default values for new columns
-- ============================================================================ 