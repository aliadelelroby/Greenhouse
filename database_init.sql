-- Thermeleon Interface Dashboard Database Initialization
-- This script creates all necessary tables for the greenhouse monitoring system

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS thermeleondb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thermeleondb;

-- Drop tables if they exist (in correct order to handle foreign keys)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS data;
DROP TABLE IF EXISTS sensor;
DROP TABLE IF EXISTS sensor_types;
DROP TABLE IF EXISTS greenhouse;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS positions;
SET FOREIGN_KEY_CHECKS = 1;

-- Create companies table
CREATE TABLE companies (
    id_company INT AUTO_INCREMENT PRIMARY KEY,
    name_company VARCHAR(255) NOT NULL,
    status_company ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create positions table
CREATE TABLE positions (
    id_position INT AUTO_INCREMENT PRIMARY KEY,
    name_position VARCHAR(255) NOT NULL,
    status_position ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    name_user VARCHAR(255) NOT NULL,
    email_user VARCHAR(255) UNIQUE,
    id_company INT,
    id_position INT,
    status_user ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_company) REFERENCES companies(id_company) ON DELETE SET NULL,
    FOREIGN KEY (id_position) REFERENCES positions(id_position) ON DELETE SET NULL,
    INDEX idx_user_email (email_user),
    INDEX idx_user_company (id_company),
    INDEX idx_user_position (id_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create greenhouse table
CREATE TABLE greenhouse (
    Id_greenhouse INT AUTO_INCREMENT PRIMARY KEY,
    Name_greenhouse VARCHAR(255) NOT NULL,
    Location_greenhouse VARCHAR(255),
    Description_greenhouse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_greenhouse_name (Name_greenhouse)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sensor_types table
CREATE TABLE sensor_types (
    id_sensor_type INT AUTO_INCREMENT PRIMARY KEY,
    name_sensor_type VARCHAR(100) NOT NULL,
    unit_measurement VARCHAR(20) DEFAULT '°C',
    description_sensor_type TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sensor table
CREATE TABLE sensor (
    Id_sensor INT AUTO_INCREMENT PRIMARY KEY,
    Name_sensor VARCHAR(255) NOT NULL,
    Type_sensor VARCHAR(100) DEFAULT 'Temperature',
    Id_greenhouse INT NOT NULL,
    id_sensor_type INT DEFAULT 1,
    Location_sensor VARCHAR(255),
    Status_sensor ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Id_greenhouse) REFERENCES greenhouse(Id_greenhouse) ON DELETE CASCADE,
    FOREIGN KEY (id_sensor_type) REFERENCES sensor_types(id_sensor_type) ON DELETE SET NULL,
    INDEX idx_sensor_greenhouse (Id_greenhouse),
    INDEX idx_sensor_type (id_sensor_type),
    INDEX idx_sensor_status (Status_sensor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create data table
CREATE TABLE data (
    Id_data INT AUTO_INCREMENT PRIMARY KEY,
    Id_sensor INT NOT NULL,
    Value_data DECIMAL(10,2) NOT NULL,
    Date_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Quality_flag ENUM('good', 'warning', 'error') DEFAULT 'good',
    Notes TEXT,
    FOREIGN KEY (Id_sensor) REFERENCES sensor(Id_sensor) ON DELETE CASCADE,
    INDEX idx_data_sensor (Id_sensor),
    INDEX idx_data_date (Date_data),
    INDEX idx_data_sensor_date (Id_sensor, Date_data),
    INDEX idx_data_quality (Quality_flag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default sensor types
INSERT INTO sensor_types (name_sensor_type, unit_measurement, description_sensor_type) VALUES
('Temperature', '°C', 'Temperature measurement sensor'),
('Humidity', '%', 'Relative humidity measurement sensor'),
('Soil Moisture', '%', 'Soil moisture content sensor'),
('Light Intensity', 'lux', 'Light intensity measurement sensor'),
('CO2 Level', 'ppm', 'Carbon dioxide concentration sensor'),
('pH Level', 'pH', 'Soil or water pH level sensor'),
('Pressure', 'hPa', 'Atmospheric pressure sensor'),
('Wind Speed', 'm/s', 'Wind speed measurement sensor');

-- Insert sample greenhouse data
INSERT INTO greenhouse (Name_greenhouse, Location_greenhouse, Description_greenhouse) VALUES
('Greenhouse Alpha', 'Section A1', 'Main production greenhouse for tomatoes'),
('Greenhouse Beta', 'Section B2', 'Secondary greenhouse for leafy greens'),
('Greenhouse Gamma', 'Section C3', 'Research and development greenhouse'),
('Greenhouse Delta', 'Section D4', 'Hydroponic cucumber production'),
('Greenhouse Epsilon', 'Section E5', 'Organic herb cultivation greenhouse');

-- Insert sample companies
INSERT INTO companies (name_company) VALUES
('Thermeleon Corp'),
('GreenTech Solutions'),
('AgriTech Industries'),
('Smart Farm Systems'),
('EcoGrow Technologies');

-- Insert sample positions
INSERT INTO positions (name_position) VALUES
('Administrator'),
('Greenhouse Manager'),
('Technician'),
('Data Analyst'),
('System Operator'),
('Agronomist'),
('Quality Control Specialist');

-- Insert sample users
INSERT INTO users (name_user, email_user, id_company, id_position) VALUES
('John Admin', 'admin@thermeleon.com', 1, 1),
('Jane Manager', 'manager@greentech.com', 2, 2),
('Bob Technician', 'tech@agritech.com', 3, 3),
('Alice Analyst', 'analyst@smartfarm.com', 4, 4),
('Mike Operator', 'operator@thermeleon.com', 1, 5),
('Sarah Green', 'sarah@ecoGrow.com', 5, 6),
('David Quality', 'david@thermeleon.com', 1, 7);

-- Insert sample sensors for each greenhouse
INSERT INTO sensor (Name_sensor, Type_sensor, Id_greenhouse, id_sensor_type, Location_sensor) VALUES
-- Greenhouse Alpha sensors
('Alpha-Temp-01', 'Temperature', 1, 1, 'North Wall'),
('Alpha-Temp-02', 'Temperature', 1, 1, 'Center'),
('Alpha-Temp-03', 'Temperature', 1, 1, 'South Wall'),
('Alpha-Humidity-01', 'Humidity', 1, 2, 'Center'),
('Alpha-Soil-01', 'Soil Moisture', 1, 3, 'Plot 1'),
('Alpha-Light-01', 'Light', 1, 4, 'Roof Center'),

-- Greenhouse Beta sensors
('Beta-Temp-01', 'Temperature', 2, 1, 'North Side'),
('Beta-Temp-02', 'Temperature', 2, 1, 'South Side'),
('Beta-Humidity-01', 'Humidity', 2, 2, 'Center'),
('Beta-CO2-01', 'CO2', 2, 5, 'Air Circulation'),
('Beta-pH-01', 'pH', 2, 6, 'Hydroponic System'),

-- Greenhouse Gamma sensors
('Gamma-Temp-01', 'Temperature', 3, 1, 'Research Area'),
('Gamma-Humidity-01', 'Humidity', 3, 2, 'Research Area'),
('Gamma-Light-01', 'Light', 3, 4, 'Growth Chamber'),
('Gamma-Pressure-01', 'Pressure', 3, 7, 'External'),

-- Greenhouse Delta sensors
('Delta-Temp-01', 'Temperature', 4, 1, 'Growing Zone 1'),
('Delta-Temp-02', 'Temperature', 4, 1, 'Growing Zone 2'),
('Delta-Soil-01', 'Soil Moisture', 4, 3, 'Root Zone'),
('Delta-pH-01', 'pH', 4, 6, 'Nutrient Solution'),

-- Greenhouse Epsilon sensors
('Epsilon-Temp-01', 'Temperature', 5, 1, 'Herb Section'),
('Epsilon-Humidity-01', 'Humidity', 5, 2, 'Herb Section'),
('Epsilon-Light-01', 'Light', 5, 4, 'LED Array'),
('Epsilon-Wind-01', 'Wind Speed', 5, 8, 'Ventilation');

-- Insert sample data for the last 7 days
-- This creates realistic temperature data with some variation
INSERT INTO data (Id_sensor, Value_data, Date_data) 
SELECT 
    s.Id_sensor,
    ROUND(18 + (RAND() * 12) + SIN(HOUR(timestamp_val) * PI() / 12) * 3, 2) as temperature,
    timestamp_val
FROM sensor s
CROSS JOIN (
    SELECT DATE_SUB(NOW(), INTERVAL seq HOUR) as timestamp_val
    FROM (
        SELECT a.N + b.N * 10 + c.N * 100 as seq
        FROM 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2) c
        ORDER BY seq
        LIMIT 168 -- 7 days * 24 hours
    ) numbers
) timestamps
WHERE s.id_sensor_type = 1 -- Only temperature sensors
ORDER BY s.Id_sensor, timestamp_val;

-- Insert sample data for humidity sensors (40-80%)
INSERT INTO data (Id_sensor, Value_data, Date_data)
SELECT 
    s.Id_sensor,
    ROUND(40 + (RAND() * 40) + SIN(HOUR(timestamp_val) * PI() / 12) * 5, 2) as humidity,
    timestamp_val
FROM sensor s
CROSS JOIN (
    SELECT DATE_SUB(NOW(), INTERVAL seq HOUR) as timestamp_val
    FROM (
        SELECT a.N + b.N * 10 + c.N * 100 as seq
        FROM 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2) c
        ORDER BY seq
        LIMIT 168 -- 7 days * 24 hours
    ) numbers
) timestamps
WHERE s.id_sensor_type = 2 -- Only humidity sensors
ORDER BY s.Id_sensor, timestamp_val;

-- Insert sample data for other sensor types with appropriate ranges
INSERT INTO data (Id_sensor, Value_data, Date_data)
SELECT 
    s.Id_sensor,
    CASE 
        WHEN s.id_sensor_type = 3 THEN ROUND(30 + (RAND() * 40), 2) -- Soil moisture 30-70%
        WHEN s.id_sensor_type = 4 THEN ROUND(20000 + (RAND() * 30000), 2) -- Light 20k-50k lux
        WHEN s.id_sensor_type = 5 THEN ROUND(350 + (RAND() * 150), 2) -- CO2 350-500 ppm
        WHEN s.id_sensor_type = 6 THEN ROUND(6.0 + (RAND() * 2.0), 2) -- pH 6.0-8.0
        WHEN s.id_sensor_type = 7 THEN ROUND(1013 + (RAND() * 20 - 10), 2) -- Pressure 1003-1023 hPa
        WHEN s.id_sensor_type = 8 THEN ROUND(RAND() * 5, 2) -- Wind speed 0-5 m/s
        ELSE ROUND(20 + (RAND() * 10), 2)
    END as sensor_value,
    timestamp_val
FROM sensor s
CROSS JOIN (
    SELECT DATE_SUB(NOW(), INTERVAL seq * 4 HOUR) as timestamp_val
    FROM (
        SELECT a.N + b.N * 10 as seq
        FROM 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN 
            (SELECT 0 as N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) b
        ORDER BY seq
        LIMIT 42 -- 7 days, every 4 hours
    ) numbers
) timestamps
WHERE s.id_sensor_type > 2 -- All non-temperature, non-humidity sensors
ORDER BY s.Id_sensor, timestamp_val;

-- Create indexes for better performance
CREATE INDEX idx_data_recent ON data (Date_data DESC);
CREATE INDEX idx_data_value_range ON data (Value_data);

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
    'Users' as Entity, 
    COUNT(*) as Count 
FROM users
UNION ALL
SELECT 
    'Companies' as Entity, 
    COUNT(*) as Count 
FROM companies
UNION ALL
SELECT 
    'Positions' as Entity, 
    COUNT(*) as Count 
FROM positions;

-- Show latest data sample
SELECT 
    g.Name_greenhouse,
    s.Name_sensor,
    st.unit_measurement,
    d.Value_data,
    d.Date_data
FROM data d
JOIN sensor s ON d.Id_sensor = s.Id_sensor
JOIN greenhouse g ON s.Id_greenhouse = g.Id_greenhouse
JOIN sensor_types st ON s.id_sensor_type = st.id_sensor_type
ORDER BY d.Date_data DESC
LIMIT 10; 