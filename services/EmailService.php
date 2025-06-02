<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * Email service for sending emails via SMTP with template support
 * Single Responsibility: Handle email operations including SMTP and templates
 * Open/Closed: Extensible for new email providers and template types
 * Dependency Inversion: Depends on abstractions rather than concrete implementations
 */
class EmailService
{
    private array $smtpConfig;
    private string $templatesPath;
    private mysqli $connection;
    
    /**
     * Initialize email service with SMTP configuration
     */
    public function __construct(array $smtpConfig, Database $database, ?string $templatesPath = null)
    {
        $this->smtpConfig = $this->validateSmtpConfig($smtpConfig);
        $this->connection = $database->getConnection();
        $this->templatesPath = $templatesPath ?? __DIR__ . '/../templates/email/';
        $this->ensureTemplatesDirectory();
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $userName, string $resetToken): bool
    {
        try {
            $resetLink = $this->buildResetLink($resetToken);
            
            $templateData = [
                'user_name' => $userName,
                'reset_link' => $resetLink,
                'site_name' => $this->smtpConfig['site_name'] ?? 'Your Application',
                'expiry_hours' => '1'
            ];

            $subject = 'Password Reset Request';
            $htmlBody = $this->renderTemplate('password-reset.html', $templateData);
            $textBody = $this->renderTemplate('password-reset.txt', $templateData);

            return $this->sendEmail($email, $subject, $htmlBody, $textBody);
            
        } catch (Exception $e) {
            error_log("Failed to send password reset email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail(string $email, string $userName): bool
    {
        try {
            $templateData = [
                'user_name' => $userName,
                'site_name' => $this->smtpConfig['site_name'] ?? 'Your Application',
                'login_link' => $this->smtpConfig['base_url'] ?? ''
            ];

            $subject = 'Welcome to ' . ($this->smtpConfig['site_name'] ?? 'Our Platform');
            $htmlBody = $this->renderTemplate('welcome.html', $templateData);
            $textBody = $this->renderTemplate('welcome.txt', $templateData);

            return $this->sendEmail($email, $subject, $htmlBody, $textBody);
            
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email using SMTP
     */
    private function sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        try {
            $headers = $this->buildEmailHeaders($textBody !== null);
            $boundary = $this->generateBoundary();
            
            if ($textBody !== null) {
                $body = $this->buildMultipartBody($htmlBody, $textBody, $boundary);
                $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            } else {
                $body = $htmlBody;
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            }

            if ($this->smtpConfig['use_smtp']) {
                return $this->sendViaSMTP($to, $subject, $body, $headers);
            } else {
                return $this->sendViaPHPMail($to, $subject, $body, $headers);
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email via SMTP socket connection
     */
    private function sendViaSMTP(string $to, string $subject, string $body, string $headers): bool
    {
        // Add SSL context for secure connections with improved timeout handling
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
            ],
            'socket' => [
                'timeout' => $this->smtpConfig['timeout'] ?? 30
            ]
        ]);
        
        // Determine connection type based on port and encryption
        $connectionString = $this->buildConnectionString();
        
        $socket = stream_socket_client(
            $connectionString,
            $errno,
            $errstr,
            $this->smtpConfig['timeout'] ?? 30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            $errorMessage = "SMTP connection failed: $errstr ($errno)";
            
            // Provide specific guidance for common issues
            if ($this->smtpConfig['port'] == 25) {
                $errorMessage .= "\n\nPort 25 is often blocked by ISPs and cloud providers. Try using port 587 with TLS encryption instead.";
            } elseif (strpos($errstr, 'timeout') !== false || $errno == 110) {
                $errorMessage .= "\n\nConnection timeout. Check your network connection and SMTP server settings.";
            }
            
            throw new RuntimeException($errorMessage);
        }

        try {
            $this->readSMTPResponse($socket, 220);
            
            // EHLO with proper hostname
            $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            fwrite($socket, "EHLO {$hostname}\r\n");
            $this->readSMTPResponse($socket, 250);
            
            // Start TLS if required
            if ($this->smtpConfig['encryption'] === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $this->readSMTPResponse($socket, 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                fwrite($socket, "EHLO {$hostname}\r\n");
                $this->readSMTPResponse($socket, 250);
            }
            
            // Authentication
            if (!empty($this->smtpConfig['username'])) {
                try {
                    fwrite($socket, "AUTH LOGIN\r\n");
                    $this->readSMTPResponse($socket, 334);
                    
                    fwrite($socket, base64_encode($this->smtpConfig['username']) . "\r\n");
                    $this->readSMTPResponse($socket, 334);
                    
                    fwrite($socket, base64_encode($this->smtpConfig['password']) . "\r\n");
                    $this->readSMTPResponse($socket, 235);
                } catch (Exception $authError) {
                    throw new RuntimeException(
                        "SMTP Authentication failed. For Gmail: 1) Enable 2FA and use App Password, or 2) Enable 'Less secure app access'. Error: " . 
                        $authError->getMessage()
                    );
                }
            }
            
            // Send email
            fwrite($socket, "MAIL FROM: <" . $this->smtpConfig['from_email'] . ">\r\n");
            $this->readSMTPResponse($socket, 250);
            
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            $this->readSMTPResponse($socket, 250);
            
            fwrite($socket, "DATA\r\n");
            $this->readSMTPResponse($socket, 354);
            
            $emailData = $headers . "\r\n" . $body . "\r\n.\r\n";
            fwrite($socket, $emailData);
            $this->readSMTPResponse($socket, 250);
            
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            error_log("SMTP Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email via PHP's mail function as fallback
     */
    private function sendViaPHPMail(string $to, string $subject, string $body, string $headers): bool
    {
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Read and validate SMTP response
     */
    private function readSMTPResponse($socket, int $expectedCode): string
    {
        $response = '';
        
        do {
            $line = fgets($socket, 512);
            if ($line === false) {
                throw new RuntimeException("SMTP Error: Failed to read response from server");
            }
            
            $response .= $line;
            $code = (int) substr($line, 0, 3);
            $separator = substr($line, 3, 1);
            
        } while ($separator === '-'); // Continue reading multi-line responses
        
        if ($code !== $expectedCode) {
            throw new RuntimeException("SMTP Error: Expected $expectedCode, got $code - $response");
        }
        
        return trim($response);
    }

    /**
     * Build email headers
     */
    private function buildEmailHeaders(bool $isMultipart = false): string
    {
        $headers = "From: " . $this->smtpConfig['from_name'] . " <" . $this->smtpConfig['from_email'] . ">\r\n";
        $headers .= "Reply-To: " . $this->smtpConfig['from_email'] . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        return $headers;
    }

    /**
     * Generate boundary for multipart emails
     */
    private function generateBoundary(): string
    {
        return '----=_NextPart_' . md5(time() . rand());
    }

    /**
     * Build multipart email body
     */
    private function buildMultipartBody(string $htmlBody, string $textBody, string $boundary): string
    {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        
        $body .= "--{$boundary}--\r\n";
        
        return $body;
    }

    /**
     * Render email template with data
     */
    private function renderTemplate(string $templateName, array $data): string
    {
        $templatePath = $this->templatesPath . $templateName;
        
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Email template not found: $templatePath");
        }
        
        $template = file_get_contents($templatePath);
        
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $template);
        }
        
        return $template;
    }

    /**
     * Build password reset link
     */
    private function buildResetLink(string $token): string
    {
        $baseUrl = $this->smtpConfig['base_url'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        
        return $protocol . $baseUrl . "/reset-password.php?token=" . urlencode($token);
    }

    /**
     * Build connection string based on SMTP configuration
     */
    private function buildConnectionString(): string
    {
        $host = $this->smtpConfig['host'];
        $port = $this->smtpConfig['port'];
        $encryption = $this->smtpConfig['encryption'] ?? '';
        
        // Handle different SMTP configurations to avoid timeouts
        switch ($port) {
            case 465:
                // SSL/TLS implicit encryption (older method)
                return "ssl://{$host}:{$port}";
                
            case 587:
                // STARTTLS explicit encryption (modern standard)
                return "tcp://{$host}:{$port}";
                
            case 25:
                // Plain SMTP (often blocked by ISPs) - fallback to 587
                error_log("Warning: Port 25 detected. Many ISPs block this port. Consider using port 587 instead.");
                return "tcp://{$host}:{$port}";
                
            default:
                // Custom port - use encryption if specified
                $protocol = ($encryption === 'ssl') ? 'ssl' : 'tcp';
                return "{$protocol}://{$host}:{$port}";
        }
    }

    /**
     * Validate SMTP configuration
     */
    private function validateSmtpConfig(array $config): array
    {
        $required = ['host', 'port', 'from_email', 'from_name'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new InvalidArgumentException("SMTP configuration missing required field: $field");
            }
        }
        
        $defaults = [
            'use_smtp' => true,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'site_name' => 'Application',
            'base_url' => $_SERVER['HTTP_HOST'] ?? 'localhost'
        ];
        
        return array_merge($defaults, $config);
    }

    /**
     * Ensure templates directory exists
     */
    private function ensureTemplatesDirectory(): void
    {
        if (!is_dir($this->templatesPath)) {
            if (!mkdir($this->templatesPath, 0755, true)) {
                throw new RuntimeException("Failed to create templates directory: " . $this->templatesPath);
            }
        }
    }
} 