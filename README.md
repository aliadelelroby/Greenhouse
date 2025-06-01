# Thermeleon Greenhouse Dashboard

A comprehensive greenhouse management and monitoring system with real-time sensor data visualization, weather integration, and complete CRUD operations for greenhouse and sensor management.

## Features

### 🏠 Greenhouse Management

- **View all greenhouses** with detailed information
- **Add new greenhouses** with company association and dimensions
- **Edit existing greenhouses** including size and company details
- **Delete greenhouses** with proper validation
- **Export greenhouse-specific data** in multiple formats

### 📊 Sensor Management

- **Comprehensive sensor overview** across all greenhouses
- **Add new sensors** with description and greenhouse assignment
- **Enable/disable sensors** for maintenance
- **Real-time sensor data collection** from hardware devices
- **Sensor status monitoring** and health checks

### 📈 Data Visualization

- **Interactive charts** using Chart.js with real-time updates
- **Multiple sensor data** on single charts with color coding
- **Weather data integration** with outdoor temperature overlay
- **Customizable date ranges** for historical analysis
- **Responsive design** that works on all devices

### 🌤️ Weather Integration

- **Outside weather data** displayed alongside greenhouse sensors
- **Weather checkbox** to toggle outdoor conditions on charts
- **Historical weather data** for environmental correlation
- **Weather data API** for external integrations

### 👥 User Management

- **Complete user CRUD operations** (Create, Read, Update, Delete)
- **Company management** with user associations
- **Position/role management** for access control
- **User statistics** and activity tracking

### 📤 Data Export

- **Quick export** (5-minute intervals) for rapid analysis
- **Detailed export** (hourly averages) for comprehensive reports
- **All data export** for complete system backup
- **CSV format** with metadata headers
- **Custom date range exports**

### 🎛️ System Management

- **Platform overview** with real-time statistics
- **System health monitoring** with status indicators
- **Database management** tools and backup functionality
- **Activity logging** and audit trails

## Technical Architecture

### Backend (PHP)

- **RESTful API design** with proper HTTP methods
- **Repository pattern** for data access layer
- **Service layer** for business logic
- **Dependency injection** for clean architecture
- **Error handling** with proper logging

### Frontend (JavaScript)

- **Modular JavaScript** with clean separation of concerns
- **Real-time data fetching** with async/await
- **Chart.js integration** for data visualization
- **Responsive UI** using Tailwind CSS
- **Modal management** for CRUD operations

### Database (MySQL)

- **Optimized schema** with proper indexing
- **Foreign key constraints** for data integrity
- **Sample data generation** for testing
- **Weather data integration** with historical records
- **Efficient queries** for large datasets

## Installation

1. **Clone the repository**

```bash
git clone [repository-url]
cd interface_dashboard
```

2. **Database Setup**

```bash
# Import the database schema and sample data
mysql -u root -p < database_init.sql
```

3. **Configuration**

```php
// Update config/Database.php with your database credentials
private string $host = "localhost";
private string $database = "thermeleondb";
private string $username = "your_username";
private string $password = "your_password";
```

4. **Start the development server**

```bash
php -S localhost:8000
```

5. **Test the installation**

```
Visit: http://localhost:8000/test_connection.php
```

## API Endpoints

### Sensor & Greenhouse Management

- `GET /api/sensors.php` - List sensors
- `POST /api/sensors.php` - Create sensor/greenhouse
- `PUT /api/sensors.php` - Update sensor/greenhouse
- `DELETE /api/sensors.php` - Delete sensor/greenhouse

### Data Retrieval

- `GET /api/data.php?sensors=1,2,3` - Get sensor data
- `GET /api/data.php?weather=1` - Get weather data
- `GET /api/export.php` - Export data in CSV format

### User Management

- `GET /api/users.php` - List users/companies/positions
- `POST /api/users.php` - Create user/company/position
- `PUT /api/users.php` - Update user status
- `DELETE /api/users.php` - Delete user/company/position

## Usage Guide

### Dashboard Navigation

1. **Greenhouse Tab** - Monitor and visualize sensor data
2. **Manager Tab** - CRUD operations for greenhouses and sensors
3. **Platform Tab** - System administration and user management
4. **Pre-sales Tab** - Tools for customer presentations

### Adding a New Greenhouse

1. Go to Manager tab
2. Click "Add Greenhouse"
3. Fill in greenhouse details (name, company ID, dimensions)
4. Save to create the greenhouse

### Adding Sensors

1. Go to Manager tab
2. Click "Add Sensor"
3. Enter sensor details and select greenhouse
4. Sensor will be available for data collection

### Viewing Data with Weather

1. Go to Greenhouse tab
2. Select a greenhouse
3. Choose sensors to display
4. Check "Include Weather Data" for outdoor temperature
5. Adjust date range as needed

### Exporting Data

1. Select greenhouse and sensors
2. Choose export type (Quick/Detailed)
3. Data downloads as CSV with metadata

## Database Schema

### Key Tables

- **greenhouse** - Greenhouse definitions and properties
- **sensor** - Sensor configurations and status
- **data** - Time-series sensor readings
- **weather** - External weather station data
- **users/companies/positions** - User management
- **user_greenhouse** - User access permissions

### Sample Data

The system includes comprehensive sample data:

- 4 greenhouses with different configurations
- 800+ sensors across all greenhouses
- 7 days of historical sensor data
- Weather data with realistic patterns
- User accounts and company structures

## Sensor Data Collection

The system is designed to receive automatic data from hardware sensors:

```sql
-- Sensors push data using this format:
INSERT INTO data (Id_sensor, Id_greenhouse, Value_data, Unit_data, Date_data, Enabled)
VALUES (sensor_id, greenhouse_id, temperature_value, '°C', NOW(), 1);
```

### Hardware Integration

- Sensors identify themselves by `Id_sensor`
- Data is automatically timestamped
- Multiple data points per sensor supported
- Real-time updates in dashboard

## Contributing

1. Follow SOLID principles and clean code practices
2. Add JSDoc for TypeScript/JavaScript functions
3. Use proper error handling and logging
4. Test all CRUD operations thoroughly
5. Maintain database integrity with proper constraints

## Support

For issues or questions:

1. Check the test connection script: `/test_connection.php`
2. Review error logs in browser console
3. Verify database connectivity and permissions
4. Ensure all required PHP extensions are installed

## License

This project is proprietary software for Thermeleon greenhouse management systems.
