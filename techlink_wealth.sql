-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2026 at 02:36 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `techlink_wealth`
--

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `amount_requested` decimal(15,2) NOT NULL,
  `purpose` text NOT NULL,
  `loan_status` enum('pending','approved','rejected','disbursed','closed') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `member_id`, `amount_requested`, `purpose`, `loan_status`, `requested_at`) VALUES
(1, 4, 200.00, 'I just need money', 'rejected', '2026-10-25 07:18:00'),
(2, 5, 10000.00, 'to start a business', 'closed', '2026-11-04 09:58:05'),
(3, 7, 200.00, 'hunger is killing me', 'disbursed', '2026-11-10 18:32:11'),
(4, 8, 95000.00, 'pay a hitman', 'disbursed', '2026-11-11 11:50:58'),
(5, 8, 97000.00, 'back up for insurance', 'disbursed', '2026-11-11 11:52:05'),
(6, 7, 1000000.00, 'i need an Atenza.', 'disbursed', '2026-11-13 12:53:59'),
(7, 7, 1200000000.00, 'Go to a vacation in Thailand', 'pending', '2026-11-13 21:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `loan_approvals`
--

CREATE TABLE `loan_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_approvals`
--

INSERT INTO `loan_approvals` (`id`, `loan_id`, `admin_id`, `decision`, `decided_at`) VALUES
(1, 7, 2, 'approved', '2026-11-13 21:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `account_number` varchar(12) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `membership_status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('member','admin') NOT NULL DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `full_name`, `email`, `account_number`, `phone`, `password_hash`, `membership_status`, `created_at`, `role`) VALUES
(2, 'David Njeru', 'gichukidavid54@gmail.com', NULL, '0797439214', '$2y$10$c9nhwAh7CQirQvR3ZDs6Q.2bmfEFjcXkIviokpDIP7/GEYFYzQKaq', 'approved', '2026-10-23 17:33:56', 'admin'),
(3, 'Nancy Njeri', 'nawanje29@gmail.com', 'TW000003', '0712345678', '$2y$10$kiCJZAodkpdcNQXCq8utAe3lcoyh/vkdZFUfAUzOsqcCkIx.GS9iq', 'approved', '2026-10-23 18:36:01', 'member'),
(4, 'wendy Entana', 'wendyentana@gmail.com', 'TW000004', '0787654321', '$2y$10$EbjuMLf2N1zL/iE8fGZ9teel/ravEWRyz23q4giy/lpzRmmMiAi2e', 'approved', '2026-10-24 05:08:16', 'member'),
(5, 'blair', 'blairwachira@proton.me', 'TW000005', '0708707776', '$2y$10$LT9uPbAu3ABNIPxBf7t3n.937SZqCyqkFLU7Ar7tlWlBD1RlHVNmS', 'approved', '2026-11-04 09:43:06', 'member'),
(6, 'mike mwangi', 'mikemwangi621@gmail.com', 'TW000006', '0712889287', '$2y$10$2FsgT0FtPYyKcWL51ZPdlOVJTejk6dd9cthSRn5XCPUpH9UWeQJsa', 'approved', '2026-11-07 09:11:39', 'member'),
(7, 'omondi Timon', 'omondiTimon45@gmail.com', 'TW000007', '0794386601', '$2y$12$ibLMoCKCTXT23.iMgakosO3awSOls7Z7JnHeF3Egsj5Zjz3qMqFZS', 'approved', '2026-11-08 22:20:04', 'member'),
(8, 'waka neke', 'wakaneke@gmail.com', 'TW000008', '0789988998', '$2y$12$0/395SfY4Zbe/l.B1r51BOA.dQDx/sdqK40cwz9U7tCYiDbeota2m', 'approved', '2026-11-11 11:49:38', 'member'),
(9, 'Nicholas Muchiri', 'nicholasmuchiri60@gmail.com', 'TW000009', '0789988998', '$2y$12$5m/3R1dSfsUovl9tacIp2uetotaemPMexcYxlNz6HnBcymGc9NP.S', 'pending', '2026-11-15 13:27:04', 'member');

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savings`
--

INSERT INTO `savings` (`id`, `member_id`, `transaction_id`, `created_at`, `amount`) VALUES
(1, 7, 1, '2026-11-10 18:34:26', 200.00),
(2, 7, 2, '2026-11-11 11:19:46', 980000.00),
(3, 8, 3, '2026-11-11 11:55:15', 93000.00),
(4, 7, 4, '2026-11-13 12:58:33', 23400000.00);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('deposit','withdrawal','loan_disbursement','loan_repayment','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('mpesa','bank','cash','internal') NOT NULL,
  `mpesa_receipt` varchar(100) DEFAULT NULL,
  `transaction_status` enum('pending','confirmed','failed','reversed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `member_id`, `transaction_type`, `amount`, `payment_method`, `mpesa_receipt`, `transaction_status`, `created_at`) VALUES
(1, 7, 'deposit', 200.00, 'mpesa', 'WEFG4FTY6H', 'confirmed', '2026-11-10 18:33:23'),
(2, 7, 'deposit', 980000.00, 'bank', 'ST3DHJ87N', 'confirmed', '2026-11-11 11:18:52'),
(3, 8, 'deposit', 93000.00, 'mpesa', 'MNG123THGW', 'confirmed', '2026-11-11 11:53:58'),
(4, 7, 'deposit', 23400000.00, 'bank', 'ST8DGJ87N', 'confirmed', '2026-11-13 12:51:22'),
(7, 7, 'loan_disbursement', 1000000.00, 'bank', NULL, 'confirmed', '2026-11-13 21:35:40'),
(8, 8, 'loan_disbursement', 97000.00, 'mpesa', NULL, 'confirmed', '2026-11-13 21:35:42'),
(9, 8, 'loan_disbursement', 95000.00, 'mpesa', NULL, 'confirmed', '2026-11-13 21:35:44'),
(10, 7, 'loan_disbursement', 200.00, 'mpesa', NULL, 'confirmed', '2026-11-13 21:35:46'),
(11, 5, 'loan_disbursement', 10000.00, 'mpesa', NULL, 'confirmed', '2026-11-13 21:35:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_loans_member` (`member_id`);

--
-- Indexes for table `loan_approvals`
--
ALTER TABLE `loan_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loan_admin` (`loan_id`,`admin_id`),
  ADD KEY `fk_la_admin` (`admin_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_account_number` (`account_number`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `fk_savings_member` (`member_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mpesa_receipt` (`mpesa_receipt`),
  ADD KEY `fk_transactions_member` (`member_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_approvals`
--
ALTER TABLE `loan_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `loan_approvals`
--
ALTER TABLE `loan_approvals`
  ADD CONSTRAINT `fk_la_admin` FOREIGN KEY (`admin_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_la_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `savings`
--
ALTER TABLE `savings`
  ADD CONSTRAINT `fk_savings_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_savings_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
