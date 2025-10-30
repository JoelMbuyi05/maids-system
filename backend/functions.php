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
                               AND status IN ('pending', 'confirmed') 
                               AND date >= CURDATE()");
        $stmt->execute([':cleaner_id' => $cleaner_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['active_bookings'] < 3 ? 'available' : 'busy';
    } catch (PDOException $e) {
        error_log("Error checking cleaner status: " . $e->getMessage());
        return 'unknown';
    }
}

/**
 * Send email notification (placeholder)
 */
function send_notification($to, $subject, $message) {
    // TODO: Implement email sending with PHPMailer
    error_log("Email notification: To: $to, Subject: $subject");
    return true;
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
                               AND status NOT IN ('cancelled', 'rejected')");
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
?>