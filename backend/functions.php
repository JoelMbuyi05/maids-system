<?php
// backend/functions.php
// Reusable helper functions

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

// ====================================================================
// NEW: Notification & Email Helpers
// ====================================================================

/**
 * NEW: Saves a notification to the database (using your schema)
 */
function save_user_notification($userId, $userRole, $message) {
    global $pdo;
    try {
        $sql = "INSERT INTO notifications (user_id, user_role, message) 
                VALUES (:user_id, :user_role, :message)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_role' => $userRole,
            ':message' => $message
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification saving failed: " . $e->getMessage());
        return false;
    }
}

// ✅ Email notification to customer when booking is confirmed
function sendBookingConfirmation($customerEmail, $customerName, $bookingId, $service, $date) {
    $subject = "Booking Confirmed - CleanCare";
    $message = "
    Hi $customerName,<br><br>
    Your booking <strong>#$bookingId</strong> for <strong>$service</strong> has been <strong>confirmed</strong>.<br>
    Date: $date<br><br>
    We’ll notify you when your cleaner is on the way!<br><br>
    Best,<br>CleanCare Team
    ";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: CleanCare <noreply@cleancare.com>\r\n";
    @mail($customerEmail, $subject, $message, $headers);
    // Log the email attempt
    log_email($customerEmail, $subject, $message);
}

// ✅ Email notification to admin when job is completed
function sendJobCompletionNotification($adminEmail, $cleanerName, $bookingId) {
    $subject = "Job Completed - Booking #$bookingId";
    $message = "
    Hello Admin,<br><br>
    Cleaner <strong>$cleanerName</strong> has marked booking #$bookingId as completed.<br>
    Please verify the job and process payment.<br><br>
    Regards,<br>CleanCare System
    ";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: CleanCare <noreply@cleancare.com>\r\n";
    @mail($adminEmail, $subject, $message, $headers);
    // Log the email attempt
    log_email($adminEmail, $subject, $message);
}

// ✅ Notification when admin assigns a cleaner
function sendCleanerAssignedNotification($cleanerEmail, $cleanerName, $service, $date) {
    $subject = "New Job Assigned - CleanCare";
    $message = "
    Hi $cleanerName,<br><br>
    You’ve been assigned a new cleaning job.<br><br>
    Service: <strong>$service</strong><br>
    Date: <strong>$date</strong><br><br>
    Please check your dashboard for full details.<br><br>
    Regards,<br>CleanCare System
    ";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: CleanCare <noreply@cleancare.com>\r\n";
    @mail($cleanerEmail, $subject, $message, $headers);
    // Log the email attempt
    log_email($cleanerEmail, $subject, $message);
}

// NEW: Function to log emails into your email_logs table
function log_email($recipientEmail, $subject, $message) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO email_logs (recipient_email, subject, message) 
                               VALUES (:email, :subject, :message)");
        $stmt->execute([
            ':email' => $recipientEmail,
            ':subject' => $subject,
            ':message' => $message
        ]);
    } catch (PDOException $e) {
        error_log("Email log error: " . $e->getMessage());
    }
}


// Placeholder functions removed for better file separation:
// Removed: function submitReview(...) -> now in review_api.php
// Removed: function displayNotification(...) -> now handled by the notification table
// Removed: function send_notification (deprecated, use dedicated email functions)

function displayNotification($message, $type = 'info') {
    // Left this here as it was in your file, but recommend removing for a full notification API
    echo "<div class='notification notification-$type'>$message</div>";
}
?>