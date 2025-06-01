-- Sample data for thermeleondb
-- This script adds sample data to test the interface dashboard

-- Insert sample companies
INSERT INTO companies (name_company, created_at) VALUES
('GreenTech Solutions', '2024-01-15 10:00:00'),
('Eco Gardens Ltd', '2024-02-10 14:30:00'),
('Smart Farm Co', '2024-03-05 09:15:00');

-- Insert sample positions
INSERT INTO positions (name_position, created_at) VALUES
('Farm Manager', '2024-01-15 10:05:00'),
('Greenhouse Technician', '2024-01-15 10:10:00'),
('Data Analyst', '2024-01-15 10:15:00'),
('System Administrator', '2024-01-15 10:20:00');

-- Insert sample users
INSERT INTO users (name_user, email_user, id_company, id_position, created_at) VALUES
('John Smith', 'john.smith@greentech.com', 1, 1, '2024-01-15 10:25:00'),
('Sarah Johnson', 'sarah.johnson@greentech.com', 1, 2, '2024-01-15 10:30:00'),
('Mike Davis', 'mike.davis@ecogardens.com', 2, 1, '2024-02-10 15:00:00'),
('Emma Wilson', 'emma.wilson@smartfarm.com', 3, 3, '2024-03-05 09:30:00');

-- Insert sample culture types
INSERT INTO culture_type (Name_culture_type) VALUES
('Tomatoes'),
('Lettuce'),
('Peppers'),
('Herbs'),
('Cucumbers');

-- Insert sample greenhouses
INSERT INTO greenhouse (Id_company, Name_greenhouse, X_max, Y_max) VALUES
(1, 'North Greenhouse A1', 100, 50),
(1, 'South Greenhouse B2', 120, 60),
(2, 'Eco Garden House 1', 80, 40),
(3, 'Smart Farm Facility C', 150, 75);

-- Insert greenhouse cultures
INSERT INTO greenhouse_culture (Id_greenhouse, Id_culture_type, Crop_description) VALUES
(1, 1, 'Cherry tomatoes - premium variety'),
(1, 4, 'Basil and oregano herbs'),
(2, 2, 'Organic lettuce varieties'),
(3, 3, 'Bell peppers - multiple colors'),
(4, 5, 'Organic cucumbers');

-- Insert sensor types
INSERT INTO sensor_type (Name_sensor_type, Description_sensor_type) VALUES
('Temperature', 'Measures ambient temperature in Celsius'),
('Humidity', 'Measures relative humidity percentage'),
('Soil Moisture', 'Measures soil moisture content'),
('Light Intensity', 'Measures light intensity in lux'),
('CO2 Level', 'Measures CO2 concentration in ppm');

-- Insert sensor models
INSERT INTO sensor_model (Id_sensor_type, Brand, Model) VALUES
(1, 'ThermoTech', 'TT-2000'),
(1, 'SensorPro', 'SP-Temp-101'),
(2, 'HumidityMax', 'HM-350'),
(3, 'SoilSense', 'SS-Moisture-Pro'),
(4, 'LightMeter', 'LM-4000'),
(5, 'CO2Monitor', 'CM-Pro-200');

-- Insert sample sensors
INSERT INTO sensor (Id_sensor_model, Id_greenhouse, Name_sensor, Description, Last_update, Enabled) VALUES
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
INSERT INTO user_greenhouse (Id_user, Id_greenhouse, Enabled) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 1, 1),
(3, 3, 1),
(4, 4, 1);

-- Generate sample temperature data for the last 7 days
-- This will create realistic temperature readings every 30 minutes
DELIMITER $$

CREATE PROCEDURE GenerateSampleData()
BEGIN
    DECLARE start_date DATETIME DEFAULT DATE_SUB(NOW(), INTERVAL 7 DAY);
    DECLARE end_date DATETIME DEFAULT NOW();
    DECLARE current_time DATETIME DEFAULT start_date;
    DECLARE sensor_id INT;
    DECLARE temp_value DECIMAL(10,4);
    DECLARE base_temp DECIMAL(10,4);
    DECLARE hour_offset INT;
    DECLARE random_variation DECIMAL(10,4);
    
    -- Cursor to iterate through sensors
    DECLARE sensor_cursor CURSOR FOR 
        SELECT Id_sensor FROM sensor WHERE Enabled = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET sensor_id = 0;
    
    OPEN sensor_cursor;
    
    sensor_loop: LOOP
        FETCH sensor_cursor INTO sensor_id;
        IF sensor_id = 0 THEN
            LEAVE sensor_loop;
        END IF;
        
        SET current_time = start_date;
        
        -- Set base temperature depending on sensor type
        SET base_temp = 22.0 + (RAND() * 6 - 3); -- Base temp between 19-25°C
        
        time_loop: WHILE current_time <= end_date DO
            -- Calculate temperature variation based on time of day
            SET hour_offset = HOUR(current_time);
            
            -- Simulate daily temperature cycle (warmer during day, cooler at night)
            SET temp_value = base_temp + 
                (SIN((hour_offset - 6) * PI() / 12) * 4) + -- Daily cycle ±4°C
                (RAND() * 2 - 1); -- Random variation ±1°C
            
            -- Ensure temperature stays within realistic greenhouse ranges
            IF temp_value < 10 THEN SET temp_value = 10; END IF;
            IF temp_value > 35 THEN SET temp_value = 35; END IF;
            
            -- Insert the data point
            INSERT INTO data (Id_sensor, Id_greenhouse, Value_data, Unit_data, Date_data, X_location, Y_location, Enabled)
            SELECT 
                sensor_id,
                s.Id_greenhouse,
                ROUND(temp_value, 2),
                '°C',
                current_time,
                FLOOR(RAND() * 100) + 1,  -- Random X position
                FLOOR(RAND() * 50) + 1,   -- Random Y position
                1
            FROM sensor s WHERE s.Id_sensor = sensor_id;
            
            -- Move to next time point (30 minutes)
            SET current_time = DATE_ADD(current_time, INTERVAL 30 MINUTE);
        END WHILE time_loop;
        
    END LOOP sensor_loop;
    
    CLOSE sensor_cursor;
END$$

DELIMITER ;

-- Execute the procedure to generate sample data
CALL GenerateSampleData();

-- Clean up the procedure
DROP PROCEDURE GenerateSampleData;

-- Insert some additional user data in the main user table (if you want to use the legacy user table)
INSERT INTO user (Id_company, Name_user, Lastname_user, Email_user, Login, Hash_pwd, Type_user, Enabled) VALUES
(1, 'Admin', 'User', 'admin@greentech.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
(2, 'Manager', 'User', 'manager@ecogardens.com', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1);

-- Update statistics
UPDATE greenhouse SET X_max = 100, Y_max = 50 WHERE Id_greenhouse = 1;
UPDATE greenhouse SET X_max = 120, Y_max = 60 WHERE Id_greenhouse = 2;
UPDATE greenhouse SET X_max = 80, Y_max = 40 WHERE Id_greenhouse = 3;
UPDATE greenhouse SET X_max = 150, Y_max = 75 WHERE Id_greenhouse = 4;

COMMIT; 