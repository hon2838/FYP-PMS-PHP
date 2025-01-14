<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection before any usage
include 'dbconnect.php';

// Now include PermissionManager after database connection is established
require_once 'telegram/telegram_handlers.php';
require_once 'includes/PermissionManager.php';

// Strict session validation with notification
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    notifySystemError(
        'Unauthorized Access',
        "Session validation failed in admin account management",
        __FILE__,
        __LINE__
    );
    header('Location: index.php');
    exit;
}

// Validate admin access with notification
if ($_SESSION['user_type'] !== 'admin') {
    notifySystemError(
        'Access Violation',
        "Non-admin user attempted to access admin account management: {$_SESSION['email']}",
        __FILE__,
        __LINE__
    );
    header('Location: index.php');
    exit; 
}

// Initialize PermissionManager after session and database connection are ready
$permManager = new PermissionManager($conn, $_SESSION['user_id']);

// Check if user has permission to manage users
try {
    $permManager->requirePermission('manage_users');
} catch (Exception $e) {
    error_log("Permission denied: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private static $instance = null;
    private $mailer;
    private $rateLimits = [];
    
    private function __construct() {
        $this->initializeMailer();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeMailer() {
        try {
            $this->mailer = new PHPMailer(true);
            $this->mailer->isSMTP();
            $this->mailer->Host = getenv('SMTP_HOST');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = getenv('SMTP_USERNAME');
            $this->mailer->Password = getenv('SMTP_PASSWORD');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = getenv('SMTP_PORT');
            $this->mailer->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME'));
            
            // Set default configurations
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Mailer initialization error: " . $e->getMessage());
            notifySystemError('Email Config Error', $e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

    private function checkRateLimit($email) {
        $now = time();
        $limit = 5; // Max emails per hour
        $window = 3600; // 1 hour
        
        if (!isset($this->rateLimits[$email])) {
            $this->rateLimits[$email] = ['count' => 0, 'window_start' => $now];
        }
        
        if ($now - $this->rateLimits[$email]['window_start'] > $window) {
            $this->rateLimits[$email] = ['count' => 0, 'window_start' => $now];
        }
        
        if ($this->rateLimits[$email]['count'] >= $limit) {
            throw new Exception("Rate limit exceeded for " . $email);
        }
        
        $this->rateLimits[$email]['count']++;
    }

    public function sendEmail($to, $subject, $body, $template = 'default') {
        try {
            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: " . $to);
            }
            
            // Check rate limit
            $this->checkRateLimit($to);
            
            // Reset recipients
            $this->mailer->clearAllRecipients();
            
            // Set email parameters
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            
            // Apply template
            $formattedBody = $this->applyTemplate($body, $template);
            $this->mailer->Body = $formattedBody;
            $this->mailer->AltBody = strip_tags($formattedBody);
            
            // Send email
            $result = $this->mailer->send();
            
            // Log success
            error_log("Email sent successfully to: " . $to);
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            notifySystemError('Email Send Error', $e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

    private function applyTemplate($content, $template) {
        $templates = [
            'default' => "<div>{content}</div>",
            'notification' => "
                <div style='font-family: Arial, sans-serif;'>
                    <h2>Paperwork Management System</h2>
                    {content}
                    <hr>
                    <footer>This is an automated message.</footer>
                </div>
            ",
            'password_reset' => "
                <div style='font-family: Arial, sans-serif;'>
                    <h2>Password Reset Request</h2>
                    {content}
                    <p>If you didn't request this, please ignore this email.</p>
                </div>
            "
        ];
        
        return str_replace('{content}', $content, $templates[$template] ?? $templates['default']);
    }
}

// Helper functions
function sendSubmissionEmail($userEmail, $userName, $paperworkDetails) {
    try {
        $emailService = EmailService::getInstance();
        $content = "<p>Dear {$userName},</p>
                   <p>Your paperwork submission was received:</p>
                   <ul>
                       <li>Reference: {$paperworkDetails['ref_number']}</li>
                       <li>Title: {$paperworkDetails['project_name']}</li>
                   </ul>";
                   
        return $emailService->sendEmail(
            $userEmail,
            'Paperwork Submission Confirmation',
            $content,
            'notification'
        );
    } catch (Exception $e) {
        error_log("Submission email error: " . $e->getMessage());
        return false;
    }
}

function sendHODNotificationEmail($hodEmail, $paperworkDetails) {
    try {
        $emailService = EmailService::getInstance();
        $content = "<p>New paperwork requires your review:</p>
                   <ul>
                       <li>Reference: {$paperworkDetails['ref_number']}</li>
                       <li>Title: {$paperworkDetails['project_name']}</li>
                   </ul>";
                   
        return $emailService->sendEmail(
            $hodEmail,
            'New Paperwork Pending Review',
            $content,
            'notification'
        );
    } catch (Exception $e) {
        error_log("HOD notification error: " . $e->getMessage());
        return false;
    }
}

function sendDeanNotificationEmail($deanEmail, $paperworkDetails, $hodName) {
    try {
        $emailService = EmailService::getInstance();
        $content = "<p>Paperwork endorsed by HOD requires your review:</p>
                   <ul>
                       <li>Reference: {$paperworkDetails['ref_number']}</li>
                       <li>Title: {$paperworkDetails['project_name']}</li>
                       <li>Endorsed by: {$hodName}</li>
                   </ul>";
                   
        return $emailService->sendEmail(
            $deanEmail,
            'Paperwork Endorsed by HOD - Pending Review',
            $content,
            'notification'
        );
    } catch (Exception $e) {
        error_log("Dean notification error: " . $e->getMessage());
        return false;
    }
}

function sendPasswordResetEmail($userEmail, $resetLink) {
    try {
        $emailService = EmailService::getInstance();
        $content = "<p>You have requested to reset your password. Click the link below to proceed:</p>
                   <p><a href='{$resetLink}'>Reset Password</a></p>
                   <p>This link will expire in 1 hour.</p>
                   <p>If you did not request this, please ignore this email.</p>";
                   
        return $emailService->sendEmail(
            $userEmail,
            'Password Reset Request',
            $content,
            'password_reset'
        );
    } catch (Exception $e) {
        error_log("Password reset email error: " . $e->getMessage());
        return false;
    }
}

function sendReturnNotificationEmail($userEmail, $paperworkDetails, $note, $returnedBy) {
    try {
        $emailService = EmailService::getInstance();
        $content = "<p>Your paperwork has been returned for modification:</p>
                   <ul>
                       <li>Reference: {$paperworkDetails['ref_number']}</li>
                       <li>Title: {$paperworkDetails['project_name']}</li>
                       <li>Returned by: {$returnedBy}</li>
                   </ul>
                   <p><strong>Feedback:</strong><br>{$note}</p>
                   <p>Please make the necessary modifications and resubmit.</p>";
                   
        return $emailService->sendEmail(
            $userEmail,
            'Paperwork Returned for Modification',
            $content,
            'notification'
        );
    } catch (Exception $e) {
        error_log("Return notification error: " . $e->getMessage());
        return false;
    }
}