<?php

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/EmailConfig.php';
require_once __DIR__ . '/services/EmailService.php';

/**
 * Simple email testing script
 * Copy .env.example to .env and configure your SMTP settings before running
 */

// Load environment configuration
EmailConfig::loadEnvironmentConfig();

// Get SMTP configuration
$smtpConfig = EmailConfig::getSmtpConfig();

echo "=== Email Configuration Test ===\n";
echo "SMTP Host: " . $smtpConfig['host'] . "\n";
echo "SMTP Port: " . $smtpConfig['port'] . "\n";
echo "SMTP Encryption: " . $smtpConfig['encryption'] . "\n";
echo "SMTP Username: " . $smtpConfig['username'] . "\n";
echo "From Email: " . $smtpConfig['from_email'] . "\n";
echo "From Name: " . $smtpConfig['from_name'] . "\n";
echo "\n";

// Check if configuration is complete
if (empty($smtpConfig['username']) || empty($smtpConfig['password'])) {
    echo "❌ ERROR: SMTP username or password not configured\n";
    echo "Please copy env.example to .env and configure your SMTP settings\n";
    exit(1);
}

try {
    // Initialize database and email service
    $database = Database::getInstance();
    $emailService = new EmailService($smtpConfig, $database);
    
    // Test email
    $testEmail = $smtpConfig['username']; // Send to yourself
    $testName = "Test User";
    
    echo "Attempting to send test email to: $testEmail\n";
    echo "This may take a moment...\n\n";
    
    $result = $emailService->sendWelcomeEmail($testEmail, $testName);
    
    if ($result) {
        echo "✅ SUCCESS: Test email sent successfully!\n";
        echo "Check your email inbox (and spam folder) for the welcome email.\n";
    } else {
        echo "❌ FAILED: Test email could not be sent\n";
        echo "Check the error logs for more details\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'authentication failed') !== false) {
        echo "\n🔧 GMAIL TROUBLESHOOTING:\n";
        echo "1. Enable 2-Factor Authentication on your Google account\n";
        echo "2. Generate an App Password: https://myaccount.google.com/apppasswords\n";
        echo "3. Use the 16-character App Password in your .env file\n";
        echo "4. OR enable 'Less secure app access': https://myaccount.google.com/lesssecureapps\n";
    }
}

echo "\n=== Test Complete ===\n"; 