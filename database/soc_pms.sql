-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2024 at 03:46 AM
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
-- Database: `soc_pms`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_audit_log`
--

CREATE TABLE `tbl_audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- --------------------------------------------------------

--
-- Table structure for table `tbl_permissions`
--

CREATE TABLE `tbl_permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_permissions`
--

INSERT INTO `tbl_permissions` (`permission_id`, `permission_name`, `description`, `created_at`) VALUES
(1, 'manage_users', 'Create, edit, and delete users', '2024-12-18 16:31:14'),
(2, 'manage_roles', 'Manage roles and permissions', '2024-12-18 16:31:14'),
(3, 'approve_submissions', 'Approve paperwork submissions', '2024-12-18 16:31:14'),
(4, 'create_submission', 'Create new paperwork submissions', '2024-12-18 16:31:14'),
(5, 'edit_submission', 'Edit existing submissions', '2024-12-18 16:31:14'),
(6, 'delete_submission', 'Delete submissions', '2024-12-18 16:31:14'),
(7, 'view_submissions', 'View paperwork submissions', '2024-12-18 16:31:14'),
(8, 'generate_reports', 'Generate system reports', '2024-12-18 16:31:14'),
(9, 'manage_departments', 'Manage department settings and view statistics', '2024-12-18 16:31:14'),
(10, 'view_analytics', 'Access analytics dashboard', '2024-12-18 16:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ppw`
--

CREATE TABLE `tbl_ppw` (
  `ppw_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `session` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_date` date NOT NULL,
  `submission_time` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `ref_number` varchar(50) NOT NULL,
  `ppw_type` varchar(50) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `current_stage` varchar(50) DEFAULT 'hod_review',
  `hod_approval` tinyint(1) DEFAULT NULL,
  `hod_note` text DEFAULT NULL,
  `hod_approval_date` datetime DEFAULT NULL,
  `dean_approval` varchar(255) DEFAULT NULL,
  `dean_note` text DEFAULT NULL,
  `dean_approval_date` datetime DEFAULT NULL,
  `user_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `tbl_roles`
--

CREATE TABLE `tbl_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_roles`
--

INSERT INTO `tbl_roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Full system access and control', '2024-12-18 16:31:14'),
(2, 'admin', 'System administration access', '2024-12-18 16:31:14'),
(3, 'dean', 'Dean level access for final approvals', '2024-12-18 16:31:14'),
(4, 'hod', 'Department head access for initial approvals', '2024-12-18 16:31:14'),
(5, 'staff', 'Basic staff access for submissions', '2024-12-18 16:31:14'),
(6, 'viewer', 'Read-only access to approved documents', '2024-12-18 16:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_role_permissions`
--

CREATE TABLE `tbl_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_role_permissions`
--

INSERT INTO `tbl_role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2024-12-19 01:39:51'),
(1, 2, '2024-12-19 01:39:51'),
(1, 3, '2024-12-19 01:39:51'),
(1, 4, '2024-12-19 01:39:51'),
(1, 5, '2024-12-19 01:39:51'),
(1, 6, '2024-12-19 01:39:51'),
(1, 7, '2024-12-19 01:39:51'),
(1, 8, '2024-12-19 01:39:51'),
(1, 9, '2024-12-19 01:39:51'),
(1, 10, '2024-12-19 01:39:51'),
(2, 1, '2024-12-18 16:43:58'),
(2, 3, '2024-12-18 16:43:58'),
(2, 7, '2024-12-18 16:43:58'),
(2, 8, '2024-12-18 16:43:58'),
(2, 9, '2024-12-18 16:43:58'),
(2, 10, '2024-12-18 16:43:58'),
(3, 3, '2024-12-18 16:43:58'),
(3, 7, '2024-12-18 16:43:58'),
(3, 8, '2024-12-18 16:43:58'),
(3, 9, '2024-12-22 02:46:05'),
(3, 10, '2024-12-18 16:43:58'),
(4, 3, '2024-12-18 16:43:58'),
(4, 7, '2024-12-18 16:43:58'),
(4, 8, '2024-12-18 16:43:58'),
(4, 9, '2024-12-22 02:46:05'),
(4, 10, '2024-12-22 02:46:05'),
(5, 4, '2024-12-18 16:43:58'),
(5, 5, '2024-12-18 16:43:58'),
(5, 6, '2024-12-18 16:43:58'),
(5, 7, '2024-12-18 16:43:58'),
(6, 7, '2024-12-18 16:43:58');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'user',
  `register_time` datetime NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `department` varchar(100) DEFAULT NULL,
  `reporting_to` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_roles`
--

CREATE TABLE `tbl_user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_modified_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`),
  ADD KEY `idx_permission_name` (`permission_name`);

--
-- Indexes for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD PRIMARY KEY (`ppw_id`),
  ADD KEY `id` (`id`),
  ADD KEY `idx_user_email` (`user_email`);

--
-- Indexes for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD KEY `idx_role_name` (`role_name`);

--
-- Indexes for table `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`),
  ADD KEY `idx_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_role_permission_combined` (`role_id`,`permission_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- Indexes for table `tbl_user_roles`
--
ALTER TABLE `tbl_user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_user_role` (`user_id`,`role_id`),
  ADD KEY `idx_assigned` (`assigned_by`,`assigned_at`),
  ADD KEY `last_modified_by` (`last_modified_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_permissions`
--
ALTER TABLE `tbl_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  MODIFY `ppw_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_roles`
--
ALTER TABLE `tbl_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_audit_log`
--
ALTER TABLE `tbl_audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_ppw`
--
ALTER TABLE `tbl_ppw`
  ADD CONSTRAINT `fk_user_email` FOREIGN KEY (`user_email`) REFERENCES `tbl_users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ppw_ibfk_1` FOREIGN KEY (`id`) REFERENCES `tbl_users` (`id`);

--
-- Constraints for table `tbl_role_permissions`
--
ALTER TABLE `tbl_role_permissions`
  ADD CONSTRAINT `tbl_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_user_roles`
--
ALTER TABLE `tbl_user_roles`
  ADD CONSTRAINT `tbl_user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_user_roles_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `tbl_users` (`id`),
  ADD CONSTRAINT `tbl_user_roles_ibfk_4` FOREIGN KEY (`last_modified_by`) REFERENCES `tbl_users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- Sample Users
INSERT INTO tbl_users (id, name, email, password, user_type, department) VALUES
(1, 'System Admin', 'admin@example.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'admin', 'IT'),
(2, 'Department Head', 'hod@example.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'hod', 'Computer Science'),
(3, 'Faculty Dean', 'dean@example.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'dean', 'School of Computing'),
(4, 'Staff Member 1', 'staff1@example.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'staff', 'Computer Science'),
(5, 'Staff Member 2', 'staff2@example.com', '$2y$10$VEcdi7ITsNseyr9GCb7vKuKy5v3FOSmGo29dRM08lgmBALeT0UDgi', 'staff', 'Information Technology');

-- Sample User Roles
INSERT INTO tbl_user_roles (user_id, role_id, assigned_by, assigned_at) VALUES
(1, 1, 1, NOW()),
(2, 4, 1, NOW()),
(3, 3, 1, NOW()),
(4, 5, 1, NOW()),
(5, 5, 1, NOW());

-- Sample Paperwork Submissions
INSERT INTO tbl_ppw (id, name, session, project_name, project_date, status, ref_number, ppw_type, document_path, current_stage, user_email) VALUES
(4, 'Staff Member 1', '2023/2024', 'Research Project A', '2024-01-15', 'pending', 'REF001', 'proposal', 'documents/example1.pdf', 'hod_review', 'staff1@example.com'),
(5, 'Staff Member 2', '2023/2024', 'Development Project B', '2024-01-20', 'approved', 'REF002', 'report', 'documents/example2.pdf', 'completed', 'staff2@example.com');

-- Sample Audit Logs
INSERT INTO tbl_audit_log (user_id, action, details, ip_address) VALUES
(1, 'LOGIN', 'User login successful', '127.0.0.1'),
(4, 'SUBMIT', 'New paperwork submission', '127.0.0.1'),
(2, 'APPROVE', 'Approved submission REF001', '127.0.0.1'),
(3, 'REVIEW', 'Reviewed submission REF002', '127.0.0.1');

-- Reset auto-increment counters
ALTER TABLE tbl_users AUTO_INCREMENT = 1000;
ALTER TABLE tbl_ppw AUTO_INCREMENT = 1000;
ALTER TABLE tbl_audit_log AUTO_INCREMENT = 1000;