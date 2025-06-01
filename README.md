# Thermeleon Interface Dashboard - Real Backend Implementation

This project implements a complete real-time greenhouse monitoring system with SOLID architecture principles and real database integration.

## Architecture Overview

The system connects to a real MySQL database (`thermeleondb`) and provides live sensor data, greenhouse management, and platform administration capabilities.

### Real Data Integration

1. **Database Connection**

   - Connects to MySQL database `thermeleondb`
   - Real-time sensor data from `data` table
   - Greenhouse information from `greenhouse` table
   - Sensor configurations from `sensor` table

2. **Live Data Features**

   - Real sensor readings with timestamps
   - Dynamic greenhouse statistics
   - Live temperature alerts
   - Actual system health monitoring

3. **No Dummy Data**
   - All statistics pulled from database
   - Real-time activity logs
   - Actual sensor counts and readings
   - Live export functionality

### SOLID Principles Applied

1. **Single Responsibility Principle (SRP)**

   - Database connections handled by `Database` class
   - Data operations by Repository classes
   - Business logic by Service classes
   - HTTP handling by Controller classes

2. **Open/Closed Principle (OCP)**

   - Easy to extend with new export types in `ExportService`
   - New data repositories can be added without modifying existing code

3. **Liskov Substitution Principle (LSP)**

   - Repository implementations can be substituted without breaking functionality

4. **Interface Segregation Principle (ISP)**

   - Separate interfaces for different repository types
   - Clients only depend on methods they use

5. **Dependency Inversion Principle (DIP)**
   - Controllers depend on interfaces, not concrete implementations
   - Easy to mock for testing

## Directory Structure

```
/
├── api/                          # Real API endpoints
│   ├── data.php                  # Sensor data API
│   ├── export.php                # Data export API
│   ├── sensors.php               # Sensor & greenhouse API
│   └── users.php                 # User management API
├── config/
│   └── Database.php              # Real database connection
├── controllers/
│   ├── DataController.php        # Data HTTP endpoints
│   ├── ExportController.php      # Export HTTP endpoints
│   └── SensorController.php      # Sensor HTTP endpoints
├── interfaces/
│   └── RepositoryInterface.php   # Repository contracts
├── js/
│   └── app.js                    # Frontend with real API calls
├── repositories/
│   ├── DataRepository.php        # Sensor data access
│   ├── GreenhouseRepository.php  # Greenhouse data access
│   ├── SensorRepository.php      # Sensor data access
│   └── UserRepository.php        # User data access
├── services/
│   ├── ExportService.php         # Data export business logic
│   └── ResponseService.php       # HTTP response handling
├── tabs/
│   ├── greenhouse.php            # Real greenhouse data
│   ├── manager.php               # Real management interface
│   ├── platform.php              # Real platform statistics
│   └── presales.php              # Sales tools
├── database_init.sql             # Complete database setup
├── index.php                     # Main dashboard
└── README.md                     # Project documentation
```

## Database Schema

The system connects to these real database tables:

- **greenhouse** - Greenhouse information
- **sensor** - Sensor configurations and metadata
- **data** - Real-time sensor readings
- **sensor_types** - Sensor type definitions

## API Endpoints

All endpoints provide real data from the database:

- **GET** `/api/sensors.php?id={greenhouse_id}` - Get real sensors for greenhouse
- **GET** `/api/sensors.php?greenhouse_list=1` - Get all greenhouses
- **GET** `/api/data.php?sensors={ids}&start_date={date}&end_date={date}` - Get real sensor data
- **GET** `/api/export.php?sensors={ids}&type={quick|detailed}` - Export real data

## Real-Time Features

### Dashboard

- Live sensor data visualization
- Real-time temperature charts
- Actual greenhouse statistics
- Dynamic alert system

### Platform Manager

- Real database statistics
- Live system health monitoring
- Actual data point counts
- Real activity logs from database

### Greenhouse Manager

- Real greenhouse data from database
- Actual sensor counts per greenhouse
- Live temperature alerts
- Real export functionality

### Export System

- Real CSV data export
- Actual sensor readings
- Date range filtering
- Metadata inclusion

## Setup Instructions

1. **Database Configuration**

   Update `config/Database.php` with your database credentials:

   ```php
   private const HOST = 'localhost';
   private const USER = 'root';
   private const PASSWORD = 'As1234*@';
   private const DATABASE = 'thermeleondb';
   ```

2. **Database Initialization**

   Run the SQL scripts to create all tables and sample data:

   ```bash
   # First, create the database structure
   mysql -u root -p < current_sql.sql

   # Then, populate with sample data
   mysql -u root -p thermeleondb < sample_data.sql
   ```

   This creates:

   - All necessary database tables (from current_sql.sql)
   - Sample greenhouses, sensors, and sensor types
   - Sample companies, positions, and users
   - Realistic sensor data for the last 7 days
   - Complete relational data structure

## Key Features

### Real Data Processing

- ✅ Live sensor data from MySQL
- ✅ Real-time temperature monitoring
- ✅ Actual greenhouse statistics
- ✅ Dynamic alert generation
- ✅ Live data export

### Platform Management

- ✅ Real system statistics
- ✅ Database health monitoring
- ✅ Actual data point tracking
- ✅ Live activity logging

### Error Handling

- Comprehensive exception management
- Database connection monitoring
- API endpoint health checks
- Real-time error logging

## Database Schema Compatibility

This implementation is **fully compatible** with the current_sql.sql schema:

- All queries updated to match actual table structure
- Proper field name mapping (Id_greenhouse, Name_greenhouse, etc.)
- Compatible with both `user` and `users` tables
- Supports all relationships defined in the schema
- No references to non-existent tables or fields

## Testing

To verify the setup works correctly:

```bash
# Open in your browser
http://localhost/interface_dashboard/test_connection.php
```

This will test all database connections, repositories, and provide statistics about your data.

## Performance

- Optimized database queries
- Efficient data aggregation
- Real-time response caching
- Minimal data transfer
- Responsive UI updates
