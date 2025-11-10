<?php
// backend/functions.php
// Reusable helper functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoload PHPMailer
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} else {
    require_once '../vendor/phpmailer/PHPMailer.php';
    require_once '../vendor/phpmailer/SMTP.php';
    require_once '../vendor/phpmailer/Exception.php';
}

require_once 'email_config.php';

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $toName, $subject, $htmlMessage) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'Cleancare');
        $mail->addAddress($to, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlMessage;
        $mail->AltBody = strip_tags($htmlMessage); // Plain text version
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send booking confirmation email to customer
 */
function sendBookingConfirmationEmail($customer_email, $customer_name, $booking_id, $service_name, $booking_date, $location, $price) {
    $subject = "Booking Confirmation - CleanCare #$booking_id";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; }
            .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9rem; }
            .btn { background: #2c5aa0; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>🎉 Booking Confirmed!</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>$customer_name</strong>,</p>
                <p>Thank you for booking with CleanCare! Your booking has been confirmed.</p>
                
                <div class='booking-details'>
                    <h3 style='color: #2c5aa0; margin-top: 0;'>Booking Details</h3>
                    <div class='detail-row'>
                        <span><strong>Booking ID:</strong></span>
                        <span>#$booking_id</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Service:</strong></span>
                        <span>$service_name</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Date:</strong></span>
                        <span>$booking_date</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Location:</strong></span>
                        <span>$location</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Price:</strong></span>
                        <span><strong>R" . number_format($price, 2) . "</strong></span>
                    </div>
                </div>
                
                <p><strong>What's Next?</strong></p>
                <ul>
                    <li>We will assign a cleaner to your booking shortly</li>
                    <li>You will receive a notification once a cleaner is assigned</li>
                    <li>You can track your booking status in your dashboard</li>
                </ul>
            </div>
            <div class='footer'>
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
                <p>Need help? Contact us at " . SMTP_FROM_EMAIL . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($customer_email, $customer_name, $subject, $message);
}

/**
 * Send cleaner assignment notification email
 */
function sendCleanerAssignedNotification($cleaner_email, $cleaner_name, $service_id, $booking_date) {
    $service_names = [
        1 => 'Regular Cleaning',
        2 => 'Deep Cleaning',
        3 => 'Move In/Out Cleaning',
        4 => 'Office Cleaning',
        5 => 'Carpet Cleaning',
        6 => 'Window Cleaning'
    ];
    $service_name = $service_names[$service_id] ?? "Service #$service_id";
    
    $subject = "New Job Assignment - CleanCare";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; }
            .job-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .btn { background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>👷 New Job Assignment</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$cleaner_name</strong>,</p>
                <p>You have been assigned to a new cleaning job!</p>
                
                <div class='job-details'>
                    <h3 style='color: #28a745; margin-top: 0;'>Job Details</h3>
                    <p><strong>Service:</strong> $service_name</p>
                    <p><strong>Date:</strong> $booking_date</p>
                </div>
                
                <p>Please log in to your dashboard to view complete job details including customer contact information and location.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($cleaner_email, $cleaner_name, $subject, $message);
}

/**
 * Send job completion notification to admin
 */
function sendJobCompletionNotification($admin_email, $cleaner_name, $booking_id) {
    $subject = "Job Completed - Awaiting Confirmation #$booking_id";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FFC107; color: #333; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; }
            .alert { background: #fff3cd; border-left: 4px solid #FFC107; padding: 15px; margin: 20px 0; }
            .btn { background: #2c5aa0; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>⏳ Job Awaiting Confirmation</h1>
            </div>
            <div class='content'>
                <p>Hello Admin,</p>
                
                <div class='alert'>
                    <p style='margin: 0;'><strong>$cleaner_name</strong> has marked booking <strong>#$booking_id</strong> as complete.</p>
                </div>
                
                <p>Please review and confirm the completion of this booking.</p>
                
                <p><strong>Action Required:</strong></p>
                <ul>
                    <li>Review the booking details</li>
                    <li>Verify job completion</li>
                    <li>Confirm or follow up as needed</li>
                </ul>
            </div>
            <div class='footer'>
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($admin_email, 'Admin', $subject, $message);
}

/**
 * Send booking completed email to customer
 */
function sendBookingCompletedEmail($customer_email, $customer_name, $booking_id) {
    $subject = "Service Completed - CleanCare #$booking_id";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; }
            .success-box { background: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0; }
            .btn { background: #FFC107; color: #333; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 20px; font-weight: bold; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 0.9rem; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>✅ Service Completed!</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>$customer_name</strong>,</p>
                
                <div class='success-box'>
                    <p style='margin: 0;'>Your booking <strong>#$booking_id</strong> has been completed successfully!</p>
                </div>
                
                <p>We hope you're satisfied with our service. Your feedback is important to us!</p>
                
                <p><strong>📝 Please take a moment to leave a review:</strong></p>
                <ul>
                    <li>Share your experience</li>
                    <li>Rate our cleaner's performance</li>
                    <li>Help us improve our service</li>
                </ul>
                
                <p style='margin-top: 30px;'>Thank you for choosing CleanCare!</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 CleanCare. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($customer_email, $customer_name, $subject, $message);
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (South African format)
 */
function validate_phone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check if it's 10 digits starting with 0
    return preg_match('/^0[0-9]{9}$/', $phone);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function check_role($required_role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    return $_SESSION['user_role'] === $required_role;
}

/**
 * Redirect with message
 */
function redirect_with_message($location, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $location");
    exit;
}

/**
 * Display flash message
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        $class = $type === 'error' ? 'error-message' : 'success-message';
        
        echo "<div class='$class' style='display:block; margin: 20px auto; max-width: 600px; text-align: center; padding: 15px; border-radius: 8px;'>$message</div>";
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

/**
 * Format date for display
 */
function format_date($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format currency (South African Rand)
 */
function format_currency($amount) {
    return 'R' . number_format($amount, 2);
}

/**
 * Get cleaner availability status
 */
function get_cleaner_status($cleaner_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_bookings FROM bookings 
                             WHERE cleaner_id = :cleaner_id 
                             AND status IN ('pending', 'confirmed', 'assigned') 
                             AND date >= CURDATE()");
        $stmt->execute([':cleaner_id' => $cleaner_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Assuming 'assigned' is also a work status
        return $result['active_bookings'] < 3 ? 'available' : 'busy'; 
    } catch (PDOException $e) {
        error_log("Error checking cleaner status: " . $e->getMessage());
        return 'unknown';
    }
}

/**
 * Log system activity
 */
function log_activity($user_id, $action, $details = '') {
    global $pdo;
    
    try {
        // Create activity_logs table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) 
                             VALUES (:user_id, :action, :details, :ip)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':details' => $details,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Generate booking reference number
 */
function generate_booking_ref() {
    return 'BK' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Check booking availability
 */
function check_booking_availability($cleaner_id, $date, $time, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings 
                             WHERE cleaner_id = :cleaner_id 
                             AND date = :date 
                             AND time = :time 
                             AND status NOT IN ('cancelled', 'rejected', 'completed', 'review_pending')");
        $stmt->execute([
            ':cleaner_id' => $cleaner_id,
            ':date' => $date,
            ':time' => $time
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == 0;
    } catch (PDOException $e) {
        error_log("Availability check error: " . $e->getMessage());
        return false;
    }
}

//Save notification to database

function save_notification($user_id, $user_role, $message) {
    global $pdo;
    
    try {
        // Create notifications table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_role VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_role, message, is_read) 
                              VALUES (:user_id, :user_role, :message, 0)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':user_role' => $user_role,
            ':message' => $message
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Alias for compatibility
 */
function save_user_notification($user_id, $user_role, $message) {
    return save_notification($user_id, $user_role, $message);
}

/**
 * Get unread notification count
 */
function get_notification_count($user_id, $user_role) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications 
                              WHERE user_id = :user_id 
                              AND user_role = :user_role 
                              AND is_read = 0");
        $stmt->execute([
            ':user_id' => $user_id,
            ':user_role' => $user_role
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch (PDOException $e) {
        error_log("Get notification count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
        $stmt->execute([':id' => $notification_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}
?>