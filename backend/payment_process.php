<?php
// backend/payment_process.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'process':
        process_payment();
        break;
    case 'get_history':
        get_payment_history();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Process payment for a booking
 */
function process_payment() {
    global $pdo;
    
    try {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $payment_method = sanitize_input($_POST['payment_method'] ?? '');
        $user_id = $_SESSION['user_id'];
        
        // Validate payment method
        $valid_methods = ['cash', 'card', 'eft', 'paypal'];
        if (!in_array($payment_method, $valid_methods)) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
            return;
        }
        
        // Get booking details
        $stmt = $pdo->prepare("SELECT customer_id, price, status FROM bookings WHERE id = :id");
        $stmt->execute([':id' => $booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            return;
        }
        
        // Check if user owns this booking
        if ($booking['customer_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized action']);
            return;
        }
        
        // Check if booking can be paid
        if ($booking['status'] !== 'confirmed' && $booking['status'] !== 'completed') {
            echo json_encode(['success' => false, 'message' => 'Payment can only be made for confirmed or completed bookings']);
            return;
        }
        
        // Check if payment already exists
        $stmt = $pdo->prepare("SELECT id FROM payslips WHERE cleaner_id = :booking_id");
        $stmt->execute([':booking_id' => $booking_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Payment already processed for this booking']);
            return;
        }
        
        // Record payment in payslips table (using existing structure)
        // Note: Your current schema uses payslips for cleaner payments
        // We'll create a payment reference
        $stmt = $pdo->prepare("INSERT INTO payslips (cleaner_id, month, amount, issued_on) 
                               VALUES (:booking_id, :month, :amount, CURDATE())");
        $stmt->execute([
            ':booking_id' => $booking_id,
            ':month' => date('Y-m'),
            ':amount' => $booking['price']
        ]);
        
        // Log activity
        log_activity($user_id, 'payment_processed', "Booking ID: $booking_id, Amount: R" . $booking['price']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment processed successfully!',
            'amount' => $booking['price']
        ]);
        
    } catch (PDOException $e) {
        error_log("Payment processing error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to process payment. Please try again.']);
    }
}

/**
 * Get payment history for user
 */
function get_payment_history() {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        
        if ($user_role === 'customer') {
            // Get payments for customer's bookings
            $sql = "SELECT p.*, b.service_type, b.date 
                    FROM payslips p
                    JOIN bookings b ON p.cleaner_id = b.id
                    WHERE b.customer_id = :user_id
                    ORDER BY p.issued_on DESC";
        } else {
            // Get payments for cleaner
            $sql = "SELECT p.*, b.service_type, b.date 
                    FROM payslips p
                    JOIN bookings b ON p.cleaner_id = b.id
                    WHERE b.cleaner_id = :user_id
                    ORDER BY p.issued_on DESC";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'payments' => $payments]);
        
    } catch (PDOException $e) {
        error_log("Payment history error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Unable to fetch payment history']);
    }
}
?>