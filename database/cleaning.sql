-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 09, 2025 at 02:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cleaning`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 8, 'booking_created', 'Booking ID: 1', '::1', '2025-10-31 13:24:25'),
(2, 8, 'booking_cancelled', 'Booking ID: 1', '::1', '2025-10-31 13:24:58'),
(3, 11, 'booking_created', 'Booking ID: 2', '::1', '2025-11-01 08:38:51'),
(4, 11, 'booking_cancelled', 'Booking ID: 2', '::1', '2025-11-01 08:39:18'),
(5, 6, 'booking_created', 'Booking ID: 3', '::1', '2025-11-05 11:32:50'),
(6, 10, 'booking_accepted', 'Booking ID: 3', '::1', '2025-11-05 11:35:35'),
(7, 10, 'booking_completed', 'Booking ID: 3', '::1', '2025-11-05 11:37:03');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$WCfSF6zSuePTOPuY8BE26esg/MHlntsmsCavAgaItrVsEBV6193bi');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cleaner_id` int(11) DEFAULT NULL,
  `service_id` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `date_booked` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `booking_date` date NOT NULL,
  `customer` varchar(100) NOT NULL,
  `cleaner` varchar(100) NOT NULL,
  `inventory_log` tinyint(1) DEFAULT 0,
  `inventory_deducted` tinyint(1) DEFAULT 0,
  `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded','partial_refund') DEFAULT 'unpaid',
  `cancelled_at` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `completed_by_cleaner_at` datetime DEFAULT NULL,
  `completed_by_admin_at` datetime DEFAULT NULL,
  `review` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `cleaner_id`, `service_id`, `price`, `date_booked`, `location`, `completed`, `booking_date`, `customer`, `cleaner`, `inventory_log`, `inventory_deducted`, `status`, `payment_status`, `cancelled_at`, `cancellation_reason`, `completed_by_cleaner_at`, `completed_by_admin_at`, `review`, `rating`) VALUES
(15, 10, 13, 'Regular Cleaning', 500.00, '0000-00-00', '20 education street', 0, '2025-11-10', '', '', 0, 0, 'cancelled', 'unpaid', '2025-11-08 21:57:23', 'I made a wrong order. Thanks', NULL, NULL, NULL, NULL),
(16, 10, 13, 'Deep Cleaning', 1200.00, '0000-00-00', '20 petrusa street', 0, '2025-11-15', '', '', 0, 0, '', 'unpaid', NULL, NULL, '2025-11-08 23:20:35', NULL, NULL, NULL),
(19, 10, 13, 'Regular Cleaning', 500.00, '0000-00-00', '13 education street', 0, '2025-11-12', '', '', 0, 0, '', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 10, NULL, 'Carpet Cleaning', 600.00, '0000-00-00', '21 pertunia street', 0, '2025-11-15', '', '', 0, 0, 'cancelled', 'unpaid', '2025-11-09 15:18:13', 'i booked by mistake. Thanks', NULL, NULL, NULL, NULL),
(21, 10, NULL, 'Carpet Cleaning', 600.00, '0000-00-00', '21 pertunia street', 0, '2025-11-15', '', '', 0, 0, 'cancelled', 'unpaid', '2025-11-09 15:16:07', 'I booked by mistake', NULL, NULL, NULL, NULL),
(22, 10, NULL, 'Carpet Cleaning', 600.00, '0000-00-00', '6 education street', 0, '2025-11-15', '', '', 0, 0, 'pending', 'unpaid', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cancellation_fees`
--

CREATE TABLE `cancellation_fees` (
  `id` int(11) NOT NULL,
  `hours_before_booking` int(11) NOT NULL,
  `fee_percentage` decimal(5,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancellation_fees`
--

INSERT INTO `cancellation_fees` (`id`, `hours_before_booking`, `fee_percentage`, `description`, `created_at`) VALUES
(1, 24, 0.00, 'Free cancellation 24+ hours before', '2025-11-07 23:16:09'),
(2, 12, 25.00, '25% fee for cancellation 12-24 hours before', '2025-11-07 23:16:09'),
(3, 6, 50.00, '50% fee for cancellation 6-12 hours before', '2025-11-07 23:16:09'),
(4, 0, 100.00, '100% fee for cancellation less than 6 hours before', '2025-11-07 23:16:09'),
(5, 24, 0.00, 'Free cancellation 24+ hours before', '2025-11-08 18:22:44'),
(6, 12, 25.00, '25% fee for cancellation 12-24 hours before', '2025-11-08 18:22:44'),
(7, 6, 50.00, '50% fee for cancellation 6-12 hours before', '2025-11-08 18:22:44'),
(8, 0, 100.00, '100% fee for cancellation less than 6 hours before', '2025-11-08 18:22:44');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`) VALUES
(1, 'Keziah Naidu', 'keziahnaidu01@gmail.com', '0649077015', NULL),
(5, 'Eden Kabamba', 'edenkabamba@gmail.com', '', '13 education street, belhar'),
(10, 'Joel Mbuyi', 'joelmbuyi700@gmail.com', '', '6 education street, belhar'),
(11, 'Pertunia Shivuri', 'pertunia@gmail.com', '084 57 41 272', '13 educatio street, belhar');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient_email`, `subject`, `message`, `sent_at`) VALUES
(1, 'joelmbuyi700@gmail.com', 'Booking Confirmation - CleanCare', '\r\n        <html>\r\n        <body style=\'font-family: Arial, sans-serif; line-height: 1.6; color: #333;\'>\r\n            <div style=\'max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;\'>\r\n                <div style=\'text-align: center; margin-bottom: 30px;\'>\r\n                    <h1 style=\'color: #2c5aa0;\'>CleanCare</h1>\r\n                </div>\r\n                \r\n                <h2 style=\'color: #2c5aa0;\'>Booking Confirmation</h2>\r\n                \r\n                <p>Dear <strong>Joel Mbuyi</strong>,</p>\r\n                \r\n                <p>Thank you for choosing CleanCare! Your booking has been <strong>confirmed</strong>.</p>\r\n                \r\n                <div style=\'background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #2c5aa0;\'>Booking Details</h3>\r\n                    <table style=\'width: 100%;\'>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Booking ID:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>#21</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Service:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>Carpet Cleaning</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Date & Time:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>Saturday, November 15, 2025 at 12:00 PM</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Location:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>21 pertunia street</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Price:</strong></td>\r\n                            <td style=\'padding: 8px 0; font-size: 1.2em; color: #2c5aa0;\'><strong>R600.00</strong></td>\r\n                        </tr>\r\n                    </table>\r\n                </div>\r\n                \r\n                <div style=\'background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <p style=\'margin: 0;\'><strong>📋 What\'s Next?</strong></p>\r\n                    <ol style=\'margin: 10px 0 0 0; padding-left: 20px;\'>\r\n                        <li>We\'ll assign a cleaner to your booking</li>\r\n                        <li>You\'ll receive updates via email and in-app notifications</li>\r\n                        <li>Our cleaner will arrive on the scheduled date</li>\r\n                        <li>After completion, you can leave a review</li>\r\n                    </ol>\r\n                </div>\r\n                \r\n                <p>You can view and manage your booking anytime by logging into your account.</p>\r\n                \r\n                <hr style=\'border: none; border-top: 1px solid #ddd; margin: 30px 0;\'>\r\n                \r\n                <p style=\'font-size: 0.9em; color: #666;\'>\r\n                    If you have any questions, please contact us at support@cleancare.com\r\n                </p>\r\n                \r\n                <p style=\'font-size: 0.9em; color: #666;\'>\r\n                    Best regards,<br>\r\n                    <strong>The CleanCare Team</strong>\r\n                </p>\r\n            </div>\r\n        </body>\r\n        </html>\r\n        ', '2025-11-09 11:15:14'),
(2, 'joelmbuyi700@gmail.com', 'Booking Confirmation - CleanCare', '\r\n        <html>\r\n        <body style=\'font-family: Arial, sans-serif; line-height: 1.6; color: #333;\'>\r\n            <div style=\'max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;\'>\r\n                <div style=\'text-align: center; margin-bottom: 30px;\'>\r\n                    <h1 style=\'color: #2c5aa0;\'>CleanCare</h1>\r\n                </div>\r\n                \r\n                <h2 style=\'color: #2c5aa0;\'>Booking Confirmation</h2>\r\n                \r\n                <p>Dear <strong>Joel Mbuyi</strong>,</p>\r\n                \r\n                <p>Thank you for choosing CleanCare! Your booking has been <strong>confirmed</strong>.</p>\r\n                \r\n                <div style=\'background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <h3 style=\'margin-top: 0; color: #2c5aa0;\'>Booking Details</h3>\r\n                    <table style=\'width: 100%;\'>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Booking ID:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>#22</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Service:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>Carpet Cleaning</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Date & Time:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>Saturday, November 15, 2025 at 12:00 PM</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Location:</strong></td>\r\n                            <td style=\'padding: 8px 0;\'>6 education street</td>\r\n                        </tr>\r\n                        <tr>\r\n                            <td style=\'padding: 8px 0;\'><strong>Price:</strong></td>\r\n                            <td style=\'padding: 8px 0; font-size: 1.2em; color: #2c5aa0;\'><strong>R600.00</strong></td>\r\n                        </tr>\r\n                    </table>\r\n                </div>\r\n                \r\n                <div style=\'background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0;\'>\r\n                    <p style=\'margin: 0;\'><strong>📋 What\'s Next?</strong></p>\r\n                    <ol style=\'margin: 10px 0 0 0; padding-left: 20px;\'>\r\n                        <li>We\'ll assign a cleaner to your booking</li>\r\n                        <li>You\'ll receive updates via email and in-app notifications</li>\r\n                        <li>Our cleaner will arrive on the scheduled date</li>\r\n                        <li>After completion, you can leave a review</li>\r\n                    </ol>\r\n                </div>\r\n                \r\n                <p>You can view and manage your booking anytime by logging into your account.</p>\r\n                \r\n                <hr style=\'border: none; border-top: 1px solid #ddd; margin: 30px 0;\'>\r\n                \r\n                <p style=\'font-size: 0.9em; color: #666;\'>\r\n                    If you have any questions, please contact us at support@cleancare.com\r\n                </p>\r\n                \r\n                <p style=\'font-size: 0.9em; color: #666;\'>\r\n                    Best regards,<br>\r\n                    <strong>The CleanCare Team</strong>\r\n                </p>\r\n            </div>\r\n        </body>\r\n        </html>\r\n        ', '2025-11-09 13:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `emp_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `emp_number`, `email`, `phone`, `position`) VALUES
(3, 'Joel', 'EMP3', 'client@example.com', '0649077015', NULL),
(4, 'MARIA', 'EMP4', NULL, NULL, NULL),
(8, 'Sarah Johnson', 'EMP0002', 'sarah@cleancare.com', '0721234567', NULL),
(9, 'Linda Brown', 'EMP0004', 'linda@cleancare.com', '0741234567', NULL),
(13, 'Jemima Mbwembwe', 'EMP0014', 'jemimambwembwe@gmail.com', '084 57 41 272', NULL),
(14, 'Mary Williams', 'EMP0016', 'mary@cleancare.com', '084 57 41 272', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `paid_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `employee_id`, `amount`, `description`, `paid_at`) VALUES
(1, 3, 5000.00, '', '2025-11-02 20:12:43');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `last_purchased` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `quantity`, `price`, `last_purchased`) VALUES
(4, 'Broom', 20, NULL, '2025-11-02 19:00:43'),
(5, 'Apron', 16, NULL, NULL),
(6, 'Gloves', 30, NULL, NULL),
(7, 'Detergents', 12, NULL, NULL),
(8, 'Mop', 0, NULL, NULL),
(9, 'Brush', 0, NULL, NULL),
(10, 'Spray Bottle', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_used` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `inventory_id`, `quantity_used`, `reason`, `booking_id`, `created_at`) VALUES
(1, 4, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(2, 5, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(3, 6, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(4, 7, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(5, 8, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(6, 9, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(7, 10, 1, 'Booking #3 used', NULL, '2025-11-02 20:21:31'),
(8, 4, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(9, 5, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(10, 6, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(11, 7, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(12, 8, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(13, 9, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(14, 10, 1, 'Booking #6 used', NULL, '2025-11-02 20:21:31'),
(15, 4, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(16, 5, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(17, 6, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(18, 7, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(19, 8, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(20, 9, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(21, 10, 1, 'Booking #7 used', NULL, '2025-11-02 20:21:31'),
(22, 4, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(23, 5, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(24, 6, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(25, 7, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(26, 8, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(27, 9, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(28, 10, 1, 'Booking #9 used', NULL, '2025-11-02 20:21:31'),
(29, 4, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(30, 5, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(31, 6, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(32, 7, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(33, 8, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(34, 9, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(35, 10, 1, 'Booking #3 used', NULL, '2025-11-02 20:38:34'),
(36, 4, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(37, 5, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(38, 6, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(39, 7, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(40, 8, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(41, 9, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(42, 10, 1, 'Booking #6 used', NULL, '2025-11-02 20:38:34'),
(43, 4, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(44, 5, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(45, 6, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(46, 7, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(47, 8, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(48, 9, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(49, 10, 1, 'Booking #7 used', NULL, '2025-11-02 20:38:34'),
(50, 4, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(51, 5, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(52, 6, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(53, 7, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(54, 8, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(55, 9, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(56, 10, 1, 'Booking #9 used', NULL, '2025-11-02 20:38:34'),
(57, 4, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(58, 5, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(59, 6, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(60, 7, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(61, 8, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(62, 9, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(63, 10, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:31'),
(64, 4, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(65, 5, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(66, 6, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(67, 7, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(68, 8, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(69, 9, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(70, 10, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:31'),
(71, 4, 1, 'Booking #7 used', NULL, '2025-11-02 20:46:31'),
(72, 4, 1, 'Booking #9 used', NULL, '2025-11-02 20:46:31'),
(73, 4, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:57'),
(74, 5, 1, 'Booking #3 used', NULL, '2025-11-02 20:46:57'),
(75, 5, 1, 'Booking #6 used', NULL, '2025-11-02 20:46:57'),
(76, 5, 1, 'Booking #7 used', NULL, '2025-11-02 20:46:57'),
(77, 5, 1, 'Booking #9 used', NULL, '2025-11-02 20:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_role` enum('admin','cleaner','customer') NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_role`, `user_id`, `message`, `created_at`, `is_read`) VALUES
(1, 'admin', 1, 'Booking #15 cancelled by customer. Reason: I made a wrong order. Thanks', '2025-11-08 19:57:23', 1),
(2, 'admin', 1, 'New booking #21 from Joel Mbuyi - Carpet Cleaning on Nov 15, 2025', '2025-11-09 11:15:14', 1),
(3, 'admin', 1, 'New booking #22 from Joel Mbuyi - Carpet Cleaning on Nov 15, 2025', '2025-11-09 13:17:27', 0);

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `cleaner_id` int(11) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `issued_on` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_history`
--

CREATE TABLE `purchase_history` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `needed_by` date NOT NULL,
  `purchased_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_history`
--

INSERT INTO `purchase_history` (`id`, `inventory_id`, `quantity`, `needed_by`, `purchased_at`) VALUES
(1, 4, 20, '2025-11-25', '2025-11-02 20:50:59'),
(2, 7, 12, '2025-11-19', '2025-11-02 20:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cleaner_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cleaner','customer') NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `address`, `password`, `role`, `name`, `created_at`) VALUES
(1, 'admin@cleancare.com', '0211234567', 'Cape Town, South Africa', '$2y$10$ImsD8PQ3QgRhYbVLHTd6.OAkOoaGqxSYmd7UZh7dTGQOXPxU2nV5S', 'admin', 'System Admin', '2025-10-30 09:47:04'),
(2, 'sarah@cleancare.com', '0721234567', 'Rondebosch, Cape Town', '$2y$10$UYuWkGjNynHMhe6BjZEIJON2vmfE8Q8MRM0xHVRVoQjilvWZybqqu', 'cleaner', 'Sarah Johnson', '2025-10-30 09:47:04'),
(4, 'linda@cleancare.com', '0741234567', 'Wynberg, Cape Town', '$2y$10$UYuWkGjNynHMhe6BjZEIJON2vmfE8Q8MRM0xHVRVoQjilvWZybqqu', 'cleaner', 'Linda Brown', '2025-10-30 09:47:04'),
(5, 'john@example.com', '0761234567', '123 Main St, Cape Town', '$2y$10$jlk4FrysR42v3l4aCmpx2eR32hvMBoC0aT0wTsy/6BMc177z0Yh6q', 'customer', 'John Doe', '2025-10-30 09:47:04'),
(6, 'joelmbuyi700@gmail.com', '084 57 41 272', '13 education street, belhar', '$2y$10$OEzUMl1oK7TbNVSIoEWhfe/74LcvMDW3XRx/FCeHuFwpbdMOYOE8.', 'customer', 'Joel Mbuyi', '2025-10-30 10:22:50'),
(7, 'jemima@gmail.com', '084 57 41 272', '13 education street, belhar', '$2y$10$3x6uvyu6LdTQF.m/3xXzh.vpnLs9Wi37zRgaP3hy0rsM779M6HxiS', 'cleaner', 'jemima', '2025-10-30 15:29:32'),
(8, 'keziah@gmail.com', '084 57 41 272', '13 belhar street', '$2y$10$ZDTzBSsjPGGq9zAfV5w9cOfngiH16KITc9Y7XSbjJAkNi7WKkmdJm', 'customer', 'keziah', '2025-10-30 15:41:46'),
(10, 'marinelle@gmail.com', '084 57 41 272', '21 petunia street', '$2y$10$hV6OiJQgy6tsLIqS1ZFZ8uy4Omrf3cbP24q3o5NkQrjtfzXfUByfS', 'cleaner', 'marinelle', '2025-10-31 13:26:08'),
(11, 'joe@gmail.com', '084 57 41 272', '13 education street', '$2y$10$RJbwc2sa5Pq/m/JMmBGe2eAS45Pze69ssKkMWMVq/9yVFC8fK5Apm', 'customer', 'joe', '2025-11-01 08:37:42'),
(13, 'mado@gmail.com', '0845741272', '13 eduction street belhar', '$2y$10$RlReawIh3cfrF.70Mev.9OuoNWvmwwac9EY6OsEgBbRiyxLbdFEs2', 'cleaner', 'mado kapinga', '2025-11-05 19:21:08'),
(14, 'jemimambwembwe@gmail.com', '084 57 41 272', '13 education street, belhar', '$2y$10$J3XdYNgBWR4MZDZa6.Fm8eOfAHm2KaGVrAXEuaNMZX6MJvuwwQpcG', 'cleaner', 'Jemima Mbwembwe', '2025-11-06 21:19:39'),
(15, 'edenkabamba@gmail.com', '084 57 41 272', '21 petunia street, kuilsriver', '$2y$10$VQzJ5/uXoVRAINFkvC2YauNVBlLJo29FuvAp9HbtzTw3Qy2rXK7Fa', 'customer', 'Eden Kabamba', '2025-11-06 21:24:04'),
(16, 'mary@cleancare.com', '084 57 41 272', '13 education street', '$2y$10$1jZoJy9X1N4i6PHtwF/Q2eNHpoRpoBy8I5KUKXz4Ef.axBjBr8MNO', 'cleaner', 'Mary Williams', '2025-11-07 12:58:38'),
(17, 'pertunia@gmail.com', '084 57 41 272', '13 education street, belhar', '$2y$10$rPJyocfldWVx99fsT6sSS.SQnlERqpK1ZeYuaRPJquJHAZFIvqvz2', 'customer', 'Pertunia Shivuri', '2025-11-07 17:41:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `cleaner_id` (`cleaner_id`);

--
-- Indexes for table `cancellation_fees`
--
ALTER TABLE `cancellation_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cleaner_id` (`cleaner_id`);

--
-- Indexes for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cancellation_fees`
--
ALTER TABLE `cancellation_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`cleaner_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`);

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`cleaner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD CONSTRAINT `purchase_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
