<?php
require_once '../backend/auth_check.php';
require_once '../backend/db_config.php';

// Ensure only admin can access
if ($user_role !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get export type
$export_type = $_GET['type'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $export_type . '_export_' . date('Y-m-d_His') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

try {
    switch ($export_type) {
        case 'bookings':
            // Add BOM for Excel UTF-8 support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($output, ['ID', 'Customer', 'Email', 'Phone', 'Service', 'Date', 'Location', 'Cleaner', 'Status', 'Price', 'Created At']);
            
            // Data
            $stmt = $pdo->query("SELECT b.*, 
                                c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                                e.name as cleaner_name
                                FROM bookings b
                                LEFT JOIN customers c ON b.customer_id = c.id
                                LEFT JOIN employees e ON b.cleaner_id = e.id
                                ORDER BY b.id DESC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $status = $row['status'] ?? ($row['completed'] ? 'completed' : 'pending');
                
                fputcsv($output, [
                    $row['id'],
                    $row['customer_name'],
                    $row['customer_email'],
                    $row['customer_phone'],
                    $row['service_id'],
                    $row['booking_date'],
                    $row['location'],
                    $row['cleaner_name'] ?? 'Not Assigned',
                    $status,
                    $row['price'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'customers':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Address', 'Total Bookings', 'Total Spent', 'Created At']);
            
            $stmt = $pdo->query("SELECT c.*, 
                                COUNT(b.id) as total_bookings,
                                COALESCE(SUM(b.price), 0) as total_spent
                                FROM customers c
                                LEFT JOIN bookings b ON c.id = b.customer_id
                                GROUP BY c.id
                                ORDER BY c.name ASC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['name'],
                    $row['email'],
                    $row['phone'],
                    $row['address'],
                    $row['total_bookings'],
                    $row['total_spent'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'employees':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ID', 'Name', 'Employee Number', 'Email', 'Phone', 'Total Bookings', 'Total Paid', 'Created At']);
            
            $stmt = $pdo->query("SELECT e.*, 
                                COUNT(DISTINCT b.id) as total_bookings,
                                COALESCE(SUM(ep.amount), 0) as total_paid
                                FROM employees e
                                LEFT JOIN bookings b ON e.id = b.cleaner_id
                                LEFT JOIN employee_payments ep ON e.id = ep.employee_id
                                GROUP BY e.id
                                ORDER BY e.name ASC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['name'],
                    $row['emp_number'],
                    $row['email'],
                    $row['phone'],
                    $row['total_bookings'],
                    $row['total_paid'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'inventory':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['ID', 'Item Name', 'Category', 'Quantity', 'Unit', 'Min Stock', 'Price', 'Last Updated']);
            
            $stmt = $pdo->query("SELECT * FROM inventory ORDER BY item_name ASC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['item_name'],
                    $row['category'],
                    $row['quantity'],
                    $row['unit'],
                    $row['min_stock_level'],
                    $row['price_per_unit'],
                    $row['last_updated']
                ]);
            }
            break;
            
        case 'finance':
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, ['Type', 'Description', 'Amount', 'Date']);
            
            // Revenue
            fputcsv($output, ['=== REVENUE ===', '', '', '']);
            $stmt = $pdo->query("SELECT id, service_id, price, booking_date 
                                FROM bookings 
                                WHERE status = 'completed' OR completed = 1
                                ORDER BY booking_date DESC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    'Revenue',
                    "Booking #{$row['id']} - {$row['service_id']}",
                    $row['price'],
                    $row['booking_date']
                ]);
            }
            
            // Employee Payments
            fputcsv($output, ['', '', '', '']);
            fputcsv($output, ['=== EMPLOYEE PAYMENTS ===', '', '', '']);
            $stmt = $pdo->query("SELECT employee_name, amount, pay_date 
                                FROM employee_payments 
                                ORDER BY pay_date DESC");
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    'Employee Payment',
                    $row['employee_name'],
                    -$row['amount'],
                    $row['pay_date']
                ]);
            }
            
            // Summary
            $stmt = $pdo->query("SELECT 
                                (SELECT COALESCE(SUM(price), 0) FROM bookings WHERE status = 'completed' OR completed = 1) as revenue,
                                (SELECT COALESCE(SUM(amount), 0) FROM employee_payments) as payments,
                                (SELECT COALESCE(SUM(amount), 0) FROM expenses) as expenses");
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            $profit = $summary['revenue'] - ($summary['payments'] + $summary['expenses']);
            
            fputcsv($output, ['', '', '', '']);
            fputcsv($output, ['=== SUMMARY ===', '', '', '']);
            fputcsv($output, ['Total Revenue', '', $summary['revenue'], '']);
            fputcsv($output, ['Total Payments', '', -$summary['payments'], '']);
            fputcsv($output, ['Total Expenses', '', -$summary['expenses'], '']);
            fputcsv($output, ['Net Profit', '', $profit, '']);
            break;
            
        default:
            fputcsv($output, ['Error: Invalid export type']);
            break;
    }
    
} catch (PDOException $e) {
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

fclose($output);
exit;