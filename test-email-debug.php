<?php

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/EmailConfig.php';
require_once __DIR__ . '/services/EmailService.php';

/**
 * Enhanced email testing and debugging script
 * This script helps diagnose SMTP connection issues and provides recommendations
 */

// Load environment configuration
EmailConfig::loadEnvironmentConfig();

// Get SMTP configuration
$smtpConfig = EmailConfig::getSmtpConfig();

echo "=== Enhanced Email Configuration Test ===\n";
echo "SMTP Host: " . $smtpConfig['host'] . "\n";
echo "SMTP Port: " . $smtpConfig['port'] . "\n";
echo "SMTP Encryption: " . $smtpConfig['encryption'] . "\n";
echo "SMTP Username: " . $smtpConfig['username'] . "\n";
echo "From Email: " . $smtpConfig['from_email'] . "\n";
echo "From Name: " . $smtpConfig['from_name'] . "\n";
echo "Timeout: " . ($smtpConfig['timeout'] ?? 30) . " seconds\n";
echo "\n";

// Validate configuration
$issues = [];

if (empty($smtpConfig['username']) || empty($smtpConfig['password'])) {
    $issues[] = "❌ SMTP username or password not configured";
}

if ($smtpConfig['port'] == 25) {
    $issues[] = "⚠️  WARNING: Port 25 is often blocked by ISPs. Consider using port 587 instead.";
}

if ($smtpConfig['port'] == 587 && $smtpConfig['encryption'] !== 'tls') {
    $issues[] = "⚠️  WARNING: Port 587 should use TLS encryption.";
}

if ($smtpConfig['port'] == 465 && $smtpConfig['encryption'] !== 'ssl') {
    $issues[] = "⚠️  WARNING: Port 465 should use SSL encryption.";
}

if (!empty($issues)) {
    echo "=== Configuration Issues ===\n";
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
    echo "\n";
}

// Test network connectivity
echo "=== Network Connectivity Test ===\n";
$host = $smtpConfig['host'];
$port = $smtpConfig['port'];

echo "Testing connection to {$host}:{$port}...\n";

$start = microtime(true);
$connection = @fsockopen($host, $port, $errno, $errstr, 10);
$duration = round((microtime(true) - $start) * 1000, 2);

if ($connection) {
    echo "✅ SUCCESS: Connected to {$host}:{$port} in {$duration}ms\n";
    fclose($connection);
} else {
    echo "❌ FAILED: Cannot connect to {$host}:{$port}\n";
    echo "Error: $errstr (Code: $errno)\n";
    
    // Provide troubleshooting suggestions
    echo "\n🔧 TROUBLESHOOTING SUGGESTIONS:\n";
    
    if ($port == 25) {
        echo "• Port 25 is commonly blocked by ISPs and cloud providers\n";
        echo "• Try using port 587 with TLS encryption instead\n";
        echo "• Update your .env file: SMTP_PORT=587 and SMTP_ENCRYPTION=tls\n";
    }
    
    if (strpos($errstr, 'timeout') !== false) {
        echo "• Connection timeout - check your internet connection\n";
        echo "• Verify the SMTP server address is correct\n";
        echo "• Try increasing the timeout value\n";
    }
    
    if ($errno == 111) {
        echo "• Connection refused - the server may be down or blocking connections\n";
        echo "• Check if you're behind a firewall that blocks SMTP ports\n";
    }
    
    echo "\n";
}

// Test with alternative configurations if current one fails
if (!$connection && $port == 25) {
    echo "=== Testing Alternative Configuration (Port 587) ===\n";
    $altConnection = @fsockopen($host, 587, $altErrno, $altErrstr, 10);
    
    if ($altConnection) {
        echo "✅ SUCCESS: Port 587 is available!\n";
        echo "RECOMMENDATION: Update your configuration to use port 587 with TLS\n";
        fclose($altConnection);
    } else {
        echo "❌ Port 587 also failed: $altErrstr\n";
    }
    echo "\n";
}

// If basic connectivity works, test full email sending
if ($connection || ($smtpConfig['port'] != 25)) {
    try {
        echo "=== Email Service Test ===\n";
        
        // Initialize database and email service
        $database = Database::getInstance();
        $emailService = new EmailService($smtpConfig, $database);
        
        // Test email
        $testEmail = $smtpConfig['username']; // Send to yourself
        $testName = "Test User";
        
        echo "Attempting to send test email to: $testEmail\n";
        echo "This may take a moment...\n\n";
        
        $start = microtime(true);
        $result = $emailService->sendWelcomeEmail($testEmail, $testName);
        $duration = round((microtime(true) - $start), 2);
        
        if ($result) {
            echo "✅ SUCCESS: Test email sent successfully in {$duration} seconds!\n";
            echo "Check your email inbox (and spam folder) for the welcome email.\n";
        } else {
            echo "❌ FAILED: Test email could not be sent\n";
            echo "Check the error logs for more details\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        
        // Provide specific troubleshooting for common errors
        $errorMsg = strtolower($e->getMessage());
        
        if (strpos($errorMsg, 'authentication failed') !== false) {
            echo "\n🔧 AUTHENTICATION TROUBLESHOOTING:\n";
            echo "For Gmail:\n";
            echo "1. Enable 2-Factor Authentication on your Google account\n";
            echo "2. Generate an App Password: https://myaccount.google.com/apppasswords\n";
            echo "3. Use the 16-character App Password in your .env file\n";
            echo "4. OR enable 'Less secure app access': https://myaccount.google.com/lesssecureapps\n";
        }
        
        if (strpos($errorMsg, 'connection') !== false || strpos($errorMsg, 'timeout') !== false) {
            echo "\n🔧 CONNECTION TROUBLESHOOTING:\n";
            echo "• Check your internet connection\n";
            echo "• Verify SMTP server settings\n";
            echo "• Try a different SMTP port (587 instead of 25)\n";
            echo "• Check if your ISP blocks SMTP ports\n";
        }
    }
}

echo "\n=== Recommended SMTP Configurations ===\n";
echo "Gmail (Recommended):\n";
echo "  SMTP_HOST=smtp.gmail.com\n";
echo "  SMTP_PORT=587\n";
echo "  SMTP_ENCRYPTION=tls\n";
echo "\n";

echo "Outlook/Hotmail:\n";
echo "  SMTP_HOST=smtp-mail.outlook.com\n";
echo "  SMTP_PORT=587\n";
echo "  SMTP_ENCRYPTION=tls\n";
echo "\n";

echo "SendGrid (Production Recommended):\n";
echo "  SMTP_HOST=smtp.sendgrid.net\n";
echo "  SMTP_PORT=587\n";
echo "  SMTP_ENCRYPTION=tls\n";
echo "  SMTP_USERNAME=apikey\n";
echo "  SMTP_PASSWORD=your-sendgrid-api-key\n";
echo "\n";

echo "=== Test Complete ===\n"; 