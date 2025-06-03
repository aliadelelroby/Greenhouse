-- URGENT PERFORMANCE FIX for 8M row data table
-- Run these indexes immediately to fix slow loading

-- 1. Essential indexes for data table
CREATE INDEX IF NOT EXISTS idx_data_enabled_date ON data(Enabled, Date_data DESC);
CREATE INDEX IF NOT EXISTS idx_data_sensor_enabled ON data(Id_sensor, Enabled);
CREATE INDEX IF NOT EXISTS idx_data_date_enabled ON data(Date_data, Enabled);
CREATE INDEX IF NOT EXISTS idx_data_value_enabled ON data(Value_data, Enabled);
CREATE INDEX IF NOT EXISTS idx_data_enabled_value_date ON data(Enabled, Value_data, Date_data DESC);
CREATE INDEX IF NOT EXISTS idx_data_enabled_date_value_high ON data(Enabled, Date_data, Value_data);
CREATE INDEX IF NOT EXISTS idx_data_enabled_date_value_low ON data(Enabled, Date_data, Value_data);

-- 2. Sensor table indexes
CREATE INDEX IF NOT EXISTS idx_sensor_greenhouse ON sensor(Id_greenhouse, Enabled);
CREATE INDEX IF NOT EXISTS idx_sensor_enabled ON sensor(Enabled);

-- 3. Check current indexes
SHOW INDEX FROM data;
SHOW INDEX FROM sensor;

-- 4. Analyze table statistics
ANALYZE TABLE data;
ANALYZE TABLE sensor;
ANALYZE TABLE greenhouse;

-- 5. Test query performance after indexes
EXPLAIN SELECT 
    d.Id_sensor,
    s.Name_sensor,
    d.Value_data,
    d.Date_data
FROM data d
LEFT JOIN sensor s ON d.Id_sensor = s.Id_sensor
WHERE d.Enabled = 1 
AND (d.Value_data > 30 OR d.Value_data < 5)
ORDER BY d.Date_data DESC
LIMIT 5; 