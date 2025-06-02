<?php

/**
 * Email configuration settings
 * Single Responsibility: Centralize email configuration
 * Dependency Inversion: Provide configuration abstraction
 */
class EmailConfig
{
    /**
     * Get SMTP configuration
     * Configure these settings according to your email provider
     */
    public static function getSmtpConfig(): array
    {
        return [
            // SMTP Server Settings - Fixed configuration to avoid port 25 timeouts
            'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
            'port' => (int)($_ENV['SMTP_PORT'] ?? 587), // Use 587 instead of 25 to avoid ISP blocks
            'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls', // Always use TLS for port 587
            'use_smtp' => filter_var($_ENV['USE_SMTP'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'timeout' => (int)($_ENV['SMTP_TIMEOUT'] ?? 30), // Add configurable timeout
            
            // Authentication
            'username' => $_ENV['SMTP_USERNAME'] ?? '',
            'password' => $_ENV['SMTP_PASSWORD'] ?? '',
            
            // From Address
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@yourcompany.com',
            'from_name' => $_ENV['FROM_NAME'] ?? 'Your Company Name',
            
            // Site Settings
            'site_name' => $_ENV['SITE_NAME'] ?? 'Your Application',
            'base_url' => $_ENV['BASE_URL'] ?? self::getBaseUrl(),
        ];
    }

    /**
     * Get Gmail SMTP configuration (helper method)
     */
    public static function getGmailConfig(string $email, string $password): array
    {
        $baseConfig = self::getSmtpConfig();
        
        return array_merge($baseConfig, [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $email,
            'password' => $password,
            'from_email' => $email,
        ]);
    }

    /**
     * Get Outlook/Hotmail SMTP configuration (helper method)
     */
    public static function getOutlookConfig(string $email, string $password): array
    {
        $baseConfig = self::getSmtpConfig();
        
        return array_merge($baseConfig, [
            'host' => 'smtp-mail.outlook.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $email,
            'password' => $password,
            'from_email' => $email,
        ]);
    }

    /**
     * Get SendGrid SMTP configuration (helper method)
     */
    public static function getSendGridConfig(string $apiKey, string $fromEmail): array
    {
        $baseConfig = self::getSmtpConfig();
        
        return array_merge($baseConfig, [
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'apikey',
            'password' => $apiKey,
            'from_email' => $fromEmail,
        ]);
    }

    /**
     * Get Mailgun SMTP configuration (helper method)
     */
    public static function getMailgunConfig(string $username, string $password, string $fromEmail): array
    {
        $baseConfig = self::getSmtpConfig();
        
        return array_merge($baseConfig, [
            'host' => 'smtp.mailgun.org',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $username,
            'password' => $password,
            'from_email' => $fromEmail,
        ]);
    }

    /**
     * Automatically detect base URL
     */
    private static function getBaseUrl(): string
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            return $protocol . $_SERVER['HTTP_HOST'];
        }
        
        return 'http://localhost';
    }

    /**
     * Load configuration from .env file if available
     */
    public static function loadEnvironmentConfig(?string $envPath = null): void
    {
        $envFile = $envPath ?? __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // Skip comments
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
} 