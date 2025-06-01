-- Performance testing queries simulating index.php greenhouse dashboard
-- These queries test the most common operations that would cause slowdowns

-- Test 1: Get all sensors for a greenhouse (simulating dropdown/selection)
SELECT 'Test 1: Sensor Selection Query' as test_name;
SET @start_time = NOW(6);
SELECT s.Id_sensor, s.Name_sensor, st.Name_sensor_type, sm.Brand, sm.Model 
FROM sensor s 
JOIN sensor_model sm ON s.Id_sensor_model = sm.Id_sensor_model 
JOIN sensor_type st ON sm.Id_sensor_type = st.Id_sensor_type 
WHERE s.Id_greenhouse = 1 AND s.Enabled = 1 
ORDER BY s.Name_sensor 
LIMIT 10;
SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as 'Query_1_Time_ms';

-- Test 2: Get recent sensor data (last 24 hours) - This is likely the slow query
SELECT 'Test 2: Recent Data Query (24h)' as test_name;
SET @start_time = NOW(6);
SELECT d.Id_sensor, s.Name_sensor, d.Value_data, d.Unit_data, d.Date_data
FROM data d
JOIN sensor s ON d.Id_sensor = s.Id_sensor
WHERE d.Id_greenhouse = 1 
AND d.Date_data >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND d.Enabled = 1
ORDER BY d.Date_data DESC
LIMIT 100;
SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as 'Query_2_Time_ms';

-- Test 3: Aggregated data for charts (hourly averages)
SELECT 'Test 3: Hourly Aggregation Query' as test_name;
SET @start_time = NOW(6);
SELECT 
    DATE_FORMAT(d.Date_data, '%Y-%m-%d %H:00:00') as hour_interval,
    s.Name_sensor,
    AVG(d.Value_data) as avg_value,
    MIN(d.Value_data) as min_value,
    MAX(d.Value_data) as max_value,
    COUNT(*) as data_points
FROM data d
JOIN sensor s ON d.Id_sensor = s.Id_sensor
WHERE d.Id_greenhouse = 1 
AND d.Id_sensor IN (1, 2, 3, 4, 5)  -- First 5 sensors
AND d.Date_data >= DATE_SUB(NOW(), INTERVAL 7 DAY)
AND d.Enabled = 1
GROUP BY hour_interval, d.Id_sensor, s.Name_sensor
ORDER BY hour_interval DESC, s.Name_sensor
LIMIT 100;
SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as 'Query_3_Time_ms';

-- Test 4: Count total records per table
SELECT 'Test 4: Record Counts' as test_name;
SELECT 'sensor' as table_name, COUNT(*) as record_count FROM sensor
UNION ALL
SELECT 'data' as table_name, COUNT(*) as record_count FROM data
UNION ALL
SELECT 'greenhouse' as table_name, COUNT(*) as record_count FROM greenhouse;

-- Test 5: Check index usage on data table
SELECT 'Test 5: Index Analysis' as test_name;
EXPLAIN SELECT d.Id_sensor, d.Value_data, d.Date_data
FROM data d
WHERE d.Id_greenhouse = 1 
AND d.Date_data >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND d.Enabled = 1
ORDER BY d.Date_data DESC
LIMIT 100;

-- Test 6: Check for missing indexes
SELECT 'Test 6: Missing Index Check' as test_name;
SHOW INDEX FROM data; 