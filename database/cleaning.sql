-- CleanCare Database Export
-- Date: 2025-10-30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: `cleaning`

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cleaner','customer') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample users (password: admin123, cleaner123, customer123)
INSERT INTO `users` (`name`, `email`, `phone`, `address`, `password`, `role`) VALUES
('System Admin', 'admin@cleancare.com', '0211234567', 'Cape Town, South Africa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sarah Johnson', 'sarah@cleancare.com', '0721234567', 'Rondebosch, Cape Town', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner'),
('Mary Williams', 'mary@cleancare.com', '0731234567', 'Claremont, Cape Town', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner'),
('Linda Brown', 'linda@cleancare.com', '0741234567', 'Wynberg, Cape Town', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cleaner'),
('John Doe', 'john@example.com', '0761234567', '123 Main St, Cape Town', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- --------------------------------------------------------

-- Table structure for table `bookings`
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `cleaner_id` int(11) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `address` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `cleaner_id` (`cleaner_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`cleaner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `contact_messages`
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `cleaner_applications`
CREATE TABLE `cleaner_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `experience` text NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `payslips`
CREATE TABLE `payslips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cleaner_id` int(11) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cleaner_id` (`cleaner_id`),
  CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`cleaner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Table structure for table `activity_logs`
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;