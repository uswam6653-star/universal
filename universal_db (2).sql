-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 06:40 AM
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
-- Database: `universal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` varchar(20) DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `user_id`, `subject`, `description`, `assigned_to`, `status`, `created_at`, `priority`) VALUES
(1, 8, 'WORKOUT', 'Gym Training Assignment', 5, 'In Progress', '2026-04-28 16:55:34', 'medium'),
(2, 15, 'WORKOUT', 'Gym Training Assignment', 6, 'In Progress', '2026-04-28 17:02:13', 'medium');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_attendance`
--

CREATE TABLE `gym_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `check_in` time NOT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent') DEFAULT 'Present',
  `method` enum('Manual','QR','Fingerprint') DEFAULT 'Manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_attendance`
--

INSERT INTO `gym_attendance` (`id`, `user_id`, `date`, `check_in`, `check_out`, `status`, `method`, `created_at`) VALUES
(1, 1, '2026-04-28', '05:25:18', NULL, 'Present', 'Fingerprint', '2026-04-28 03:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `total_base_amount` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `payable_amount` decimal(10,2) DEFAULT NULL,
  `balance_due` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `code`) VALUES
(1, 'General Membership', 'GYM-GEN');

-- --------------------------------------------------------

--
-- Table structure for table `role_access`
--

CREATE TABLE `role_access` (
  `role_key` varchar(50) NOT NULL,
  `page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_access`
--

INSERT INTO `role_access` (`role_key`, `page_id`) VALUES
('admin', 3),
('admin', 20),
('admin', 21),
('admin', 22),
('admin', 23),
('admin', 24),
('admin', 25),
('admin', 26),
('admin', 40),
('admin', 41),
('admin', 42),
('admin', 43),
('clerk', 1),
('clerk', 3),
('clerk', 18),
('gym_manager', 3),
('gym_manager', 20),
('gym_manager', 21),
('gym_manager', 22),
('gym_manager', 23),
('gym_manager', 24),
('gym_manager', 25),
('gym_manager', 26),
('gym_manager', 40),
('gym_manager', 41),
('gym_manager', 42),
('gym_manager', 43),
('hod', 1),
('hod', 18),
('hod', 33),
('hod', 34),
('hod', 35),
('hod', 36),
('student', 1),
('student', 18),
('student', 37),
('super_admin', 1),
('super_admin', 2),
('super_admin', 3),
('super_admin', 4),
('super_admin', 5),
('super_admin', 18),
('super_admin', 20),
('super_admin', 21),
('super_admin', 22),
('super_admin', 23),
('super_admin', 24),
('super_admin', 25),
('super_admin', 26),
('super_admin', 31),
('super_admin', 32),
('super_admin', 38),
('super_admin', 40),
('super_admin', 41),
('super_admin', 42),
('super_admin', 43),
('trainer', 33),
('trainer', 34),
('trainer', 35),
('trainer', 36);

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `program_id`, `name`, `number`) VALUES
(1, 1, 'Monthly', 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('footer_text', '© 2026 Universal Systems. All rights reserved.'),
('gym_attendance', '[{\"id\":\"ATT_69b59f1755179\",\"member_id\":\"4\",\"member_name\":\"ali raza (MEM-0010)\",\"date\":\"2026-03-14\",\"check_in\":\"11:00\",\"check_out\":\"14:00\",\"status\":\"Present\"},{\"id\":\"ATT_69b465529e201\",\"member_id\":\"5\",\"member_name\":\"AHMAD (GY001)\",\"date\":\"2026-03-12\",\"check_in\":\"\",\"check_out\":\"\",\"status\":\"Absent\"},{\"id\":\"ATT_69b4627f6dbc1\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo) (MEM-0010)\",\"date\":\"2026-03-12\",\"check_in\":\"18:00\",\"check_out\":\"19:45\",\"status\":\"Present\"},{\"id\":\"ATT_69b4627f6dbae\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo) (MEM-0010)\",\"date\":\"2026-03-13\",\"check_in\":\"17:30\",\"check_out\":\"19:00\",\"status\":\"Present\"}]'),
('gym_membership_plans', '[{\"id\":\"PLAN_69b5a3911e236\",\"plan_id\":\"PLAN-01\",\"name\":\"Silver monthly\",\"duration\":\"30\",\"price\":\"3000\",\"facilities\":[\"Gym Access\",\"Diet Plan\"],\"description\":\"\",\"is_popular\":0,\"status\":\"Active\"},{\"id\":\"PLAN_69b45e9a7be95\",\"plan_id\":\"PLAN-02\",\"name\":\"MONTHLY FITNESS PLAN\",\"duration\":\"30\",\"price\":\"3000\",\"facilities\":[\"Personal Trainer\"],\"description\":\"\",\"is_popular\":0,\"status\":\"Active\"},{\"id\":\"PLAN_69b45c28d5ef8\",\"plan_id\":\"GLD-01\",\"name\":\"Gold Monthly Package\",\"duration\":\"30\",\"price\":\"5000\",\"facilities\":[\"Gym Access\",\"Diet Plan\"],\"description\":\"A complete monthly package for fitness enthusiasts.\",\"is_popular\":0,\"status\":\"Active\"},{\"id\":\"PLAN_69b455b50e77f\",\"plan_id\":\"TEST-01\",\"name\":\"Test Plan\",\"duration\":\"30\",\"price\":\"1000\",\"facilities\":[\"Personal Trainer\"],\"description\":\"Test Desc\",\"is_popular\":0,\"status\":\"Active\"}]'),
('gym_payments', '[{\"id\":\"INV-B09180\",\"member_id\":\"12\",\"member_name\":\"shahan malik\",\"plan_name\":\"Walk-in\",\"amount\":\"5000\",\"payment_date\":\"2026-03-16\",\"method\":\"Cash\",\"status\":\"Pending\"},{\"id\":\"INV-9327CC\",\"member_id\":\"12\",\"member_name\":\"shahan malik\",\"plan_name\":\"Walk-in\",\"amount\":\"5000\",\"payment_date\":\"2026-03-16\",\"method\":\"Cash\",\"status\":\"Pending\"},{\"id\":\"INV-A68225\",\"member_id\":\"8\",\"member_name\":\"abdullah\",\"plan_name\":\"MONTHLY FITNESS PLAN\",\"amount\":\"3000\",\"payment_date\":\"2026-03-14\",\"method\":\"Cash\",\"status\":\"Paid\"},{\"id\":\"INV-D14504\",\"member_id\":\"2\",\"member_name\":\"Test Admin\",\"plan_name\":\"Test Plan\",\"amount\":\"1000\",\"payment_date\":\"2026-03-16\",\"method\":\"Online Transfer\",\"status\":\"Pending\"},{\"id\":\"INV-DEMO2\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo)\",\"plan_name\":\"Gold Monthly Package\",\"amount\":5000,\"payment_date\":\"2026-02-11\",\"method\":\"Card\",\"status\":\"Paid\"},{\"id\":\"INV-DEMO1\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo)\",\"plan_name\":\"Gold Monthly Package\",\"amount\":5000,\"payment_date\":\"2026-03-13\",\"method\":\"Cash\",\"status\":\"Paid\"}]'),
('gym_trainer_assignments', '[{\"id\":\"TR_ASS_69b5a80118403\",\"member_id\":\"8\",\"member_name\":\"abdullah\",\"trainer_id\":\"6\",\"trainer_name\":\"Ahmad Raza\",\"type\":\"Weight Training\",\"start_date\":\"2026-03-14\",\"end_date\":\"2026-04-13\",\"status\":\"Active\"},{\"id\":\"TR_ASS_69b4702c5382c\",\"member_id\":\"5\",\"member_name\":\"AHMAD\",\"trainer_id\":\"7\",\"trainer_name\":\"Sara Khan\",\"type\":\"Yoga\",\"start_date\":\"2026-03-13\",\"end_date\":\"2026-04-20\",\"status\":\"Active\"},{\"id\":\"TR_ASS_69b46d3317b3a\",\"member_id\":\"5\",\"member_name\":\"AHMAD\",\"trainer_id\":\"7\",\"trainer_name\":\"Sara Khan\",\"type\":\"Yoga\",\"start_date\":\"2026-03-13\",\"end_date\":\"2026-04-20\",\"status\":\"Active\"},{\"id\":\"TR_ASS_69b4680c24656\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo)\",\"trainer_id\":\"7\",\"trainer_name\":\"Sara Khan\",\"type\":\"Cardio\",\"start_date\":\"2026-03-13\",\"end_date\":\"2026-04-12\",\"status\":\"Active\"},{\"id\":\"TR_ASS_69b4680c24639\",\"member_id\":4,\"member_name\":\"Ali Raza (Demo)\",\"trainer_id\":\"6\",\"trainer_name\":\"Ahmad Raza\",\"type\":\"Weight Training\",\"start_date\":\"2026-03-13\",\"end_date\":\"2026-04-12\",\"status\":\"Active\"}]'),
('system_logo', 'https://cdn-icons-png.flaticon.com/512/906/906343.png'),
('system_name', 'Universal ERP');

-- --------------------------------------------------------

--
-- Table structure for table `sys_activity_logs`
--

CREATE TABLE `sys_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sys_pages`
--

CREATE TABLE `sys_pages` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) DEFAULT '#',
  `icon_class` varchar(50) DEFAULT 'bi bi-circle',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_pages`
--

INSERT INTO `sys_pages` (`id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(1, 0, 'Gym Dashboard', 'index.php', 'bi bi-speedometer2', 1),
(2, 0, 'Membership Hub', '#', 'bi bi-gear-fill', 98),
(4, 2, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock-fill', 3),
(5, 2, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-gear-wide-connected', 4),
(18, 0, 'Profile', 'profile.php', 'bi bi-graph-up', 99),
(20, 40, 'Gym Membership Plans', 'dashboards/super_admin/manage_plans.php', 'bi bi-speedometer2', 3),
(21, 40, 'Assign Plan', 'dashboards/super_admin/assign_plan.php', 'bi bi-person-plus', 2),
(22, 40, 'Expiry Alerts', 'dashboards/super_admin/expiry_alerts.php', 'bi bi-alarm', 4),
(23, 40, 'Gym Attendance', 'dashboards/super_admin/gym_attendance.php', 'bi bi-calendar2-check', 5),
(24, 41, 'Assign Trainers', 'dashboards/super_admin/assign_trainers.php', 'bi bi-person-video3', 2),
(25, 42, 'Payments & Billing', 'dashboards/super_admin/gym_payments.php', 'bi bi-receipt', 1),
(26, 43, 'Gym Reports', 'dashboards/super_admin/gym_reports.php', 'bi bi-bar-chart-line', 1),
(31, 40, 'Gym Members', 'dashboards/super_admin/manage_members.php', 'bi-people-fill', 1),
(32, 41, 'Gym Staff', 'dashboards/super_admin/manage_staff.php', 'bi-person-badge-fill', 1),
(33, 0, 'Trainer Dashboard', 'dashboards/trainer/dashboard.php', 'bi bi-speedometer2', 2),
(34, 0, 'My Members', 'dashboards/trainer/manage_members.php', 'bi bi-people', 3),
(35, 0, 'Workout Plans', 'dashboards/hod/workout_plans.php', 'bi bi-clipboard2-check', 4),
(36, 0, 'Member Progress', 'dashboards/hod/member_progress.php', 'bi bi-graph-up', 5),
(37, 0, 'My Member Portal', 'dashboards/student/member_dashboard.php', 'bi bi-person-badge', 2),
(38, 41, 'Training Hub', 'dashboards/super_admin/manage_training.php', 'bi bi-person-bounding-box', 3),
(40, 0, 'Members', '#', 'bi bi-people-fill', 15),
(41, 0, 'Trainers', '#', 'bi bi-person-video3', 20),
(42, 0, 'Finance', '#', 'bi bi-currency-dollar', 25),
(43, 0, 'Reports', '#', 'bi bi-bar-chart-line-fill', 30);

-- --------------------------------------------------------

--
-- Table structure for table `sys_roles`
--

CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `is_system_role` tinyint(1) DEFAULT 0 COMMENT '1=Cannot Delete'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sys_roles`
--

INSERT INTO `sys_roles` (`id`, `role_name`, `role_key`, `is_system_role`) VALUES
(1, 'Super Admin', 'super_admin', 1),
(2, 'Administrator', 'admin', 0),
(3, 'Gym Member', 'student', 0),
(4, 'Suspended', 'suspended', 1),
(5, 'Gym Receptionist', 'receptionist', 0),
(6, 'Gym Trainer', 'trainer', 0),
(7, 'Gym Manager', 'gym_manager', 0),
(8, 'Trainer', 'hod', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `identity_no` text DEFAULT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `roll_no` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `identity_no`, `registration_no`, `is_active`, `roll_no`) VALUES
(1, 'Gym Director', 'admin@sys.com', '$2y$10$Fon.ROGD.CNUvFyL1/lCjevJpAT681c.dfxZ2PZu/a/q5Yiikih0W', 'super_admin', NULL, '12345-1234567-1|||||||||', 'ADM-001', 1, 'ADM-001'),
(2, 'Test Admin', 'testadmin@gmail.com', '$2y$10$m6pjqOTC/UDF2tEnjvjAGOfWG5vMvcQc8a0zRZ0PRkz9DeyQ9ewqC', 'student', NULL, '0300-1234567|Male|1995-01-01|2026-03-12|Test Addre||||2026-04-12||', '', 1, ''),
(3, 'shahzad', 'shahzad@123', '$2y$10$kYG53bJ17LXWCgn2YcEXZeLgQFKLpqowW3LSKyI4cCEAActLlyou.', 'admin', NULL, '|||||||||28,29|', NULL, 1, NULL),
(4, 'ali raza', 'ali.test@gym.com', '$2y$10$NgZ4qNKUPrJe0OfYMDeMBe4Q/3INj3tM3MxdJBypRQ4h.n12Yk4zm', 'student', NULL, '0300-1122334|Male|1998-01-01|2026-02-14|Karachi|3 Months||Paid|2026-05-14||', 'MEM-0010', 1, 'MEM-0010'),
(5, 'AHMAD', 'AHMAD@123', '$2y$10$KzIN5qCHaoXL7hBXcOT5J.l9Jb/XJx0TwpowudLd.PAM/BHRg9uli', 'trainer', NULL, '0835634747|Male|2003-12-01|2026-03-13|JEEWAN CITY |Monthly||Unpaid||28,23|', 'GY001', 1, 'GY001'),
(6, 'Ahmad Raza', 'ahmad.trainer@gym.com', '$2y$10$H0FWPL3DgfbmDyxDZzH4N.RR1Wbfc1MASgxAOYhtlzPZSFljnIptG', 'trainer', NULL, '032245677|Male|||saiwal|||||28,29,23,21,24,25,26,22,20,4|', 'TRN-001', 1, 'TRN-001'),
(7, 'Sara Khan', 'sara.trainer@gym.com', '$2y$10$5NDwVhrKX4QaH363hhXCZ.9CtpdRnrldhG0ZFR4aNw8uTUnDxcBWu', 'trainer', NULL, '03865346678|Male|||farid town saiwal||||||', 'TRN-002', 1, 'TRN-002'),
(8, 'abdullah', 'awan024malik@gmail.com', '$2y$10$/.TywjZQqm0Qzy1kJAohXu/PSwXovjZ.zzxfDro934.5wgfXb.ae6', 'student', NULL, '03266422924|Male|2005-03-02|2026-03-14|12C Sm Gard|MONTHLY FITNESS PLAN|AHMAD|Paid|2026-04-13||', 'GYM004', 1, 'GYM004'),
(12, 'shahan malik', 'shahaanmalik46@gmail.com', '$2y$10$0Qamny0aotIZJR.qtPtxFeyf.e7kT7j3KZV/r2IoPETghuO1sZjfi', 'student', NULL, '03359691790|Male|2009-03-26|2026-03-15|house no 157\\P Alharam city |Yearly|member_1773599437_123.jpg|Paid|2027-03-15||', 'GYM005', 1, 'GYM005'),
(14, 'rimsha', 'rimsha@123', '$2y$10$I.z5Bou.1iFnQ5MWgYN1oewBJ.RyB6/GsJKiQW6AOhY8vkn2ZhJUi', 'student', 'uploads/1775048393_yu.jpg', '076543567745|Female|2005-04-09|2026-03-16|al razaq|VIP Pass||Paid|2026-04-16||', 'GYM006', 1, 'GYM006'),
(15, 'Aleeza', 'aleeza@123', '$2y$10$KLTWGSqjDmiK5/tqWYW9ruVbrhcf/0pIxq.6WlFuULyBp4ZEaDutK', 'student', 'uploads/1775048360_zzz.jpg', '030997773736|Female|2005-06-04|2026-03-29||3 Months|Ahmad Raza|Paid|2026-06-29||', 'GYM006', 1, 'GYM006'),
(16, 'azan', 'azan@1234', '$2y$10$6RmFZERFq4Jrzrcd/p3u9.3u5aF71eQxcQQ.mwxxx.iwNkgvnVyL6', 'gym_manager', NULL, '0300987658|Male|2005-03-23|2026-03-29|jeewan city sahiwal|||||', 'StF01', 1, 'StF01'),
(17, 'Gym Admin', 'admin@gym.com', '$2y$10$cAKGqiN9rve7962VKBL7E.Fk5z8PrZwXJorQP3FGpprNA2F.HPp5u', 'super_admin', NULL, NULL, 'ADM-001', 1, NULL),
(18, 'Senior Trainer', 'trainer@gym.com', '$2y$10$nE4jDHL23Pyxtt.Q11uA7.TJfm/pO5k3fFOUz5py7xnz0s9VFOn8S', 'hod', NULL, NULL, 'TRN-001', 1, NULL),
(19, 'Gym Member', 'member@gym.com', '$2y$10$hFJ.Iy6dok4JMFKTZIeJq.iZVwkCAnKQWsHpl752iLGxa5gXJW7R2', 'student', NULL, NULL, 'MEM-001', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_attendance`
--
ALTER TABLE `gym_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_access`
--
ALTER TABLE `role_access`
  ADD PRIMARY KEY (`role_key`,`page_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_pages`
--
ALTER TABLE `sys_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_roles`
--
ALTER TABLE `sys_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_key` (`role_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_attendance`
--
ALTER TABLE `gym_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sys_activity_logs`
--
ALTER TABLE `sys_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sys_pages`
--
ALTER TABLE `sys_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `sys_roles`
--
ALTER TABLE `sys_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gym_attendance`
--
ALTER TABLE `gym_attendance`
  ADD CONSTRAINT `gym_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
