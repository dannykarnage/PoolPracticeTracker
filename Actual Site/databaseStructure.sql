-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 28, 2025 at 02:03 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u449903691_eooaokt`
--

-- --------------------------------------------------------

--
-- Table structure for table `drills`
--

CREATE TABLE `drills` (
  `published` tinyint(4) NOT NULL DEFAULT 0,
  `drill_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `featured` tinyint(1) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `description_new` text DEFAULT NULL,
  `pass_fail` tinyint(1) NOT NULL DEFAULT 0,
  `score` tinyint(1) NOT NULL DEFAULT 0,
  `out_of` tinyint(1) NOT NULL DEFAULT 0,
  `out_of_num` int(11) DEFAULT NULL,
  `out_of_pass` int(11) DEFAULT NULL,
  `has_table_diagram` tinyint(1) NOT NULL DEFAULT 0,
  `has_video` tinyint(1) NOT NULL DEFAULT 0,
  `youtube_video_code` varchar(30) DEFAULT NULL,
  `has_creator_video` tinyint(1) NOT NULL DEFAULT 0,
  `creator_video_youtube_video_code` varchar(30) DEFAULT NULL,
  `creator_video_description` varchar(1000) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drill_results`
--

CREATE TABLE `drill_results` (
  `id` bigint(20) NOT NULL,
  `user_id` int(20) NOT NULL,
  `drill_id` int(11) NOT NULL,
  `pass` tinyint(4) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `is_admin` tinyint(4) NOT NULL,
  `email_list` tinyint(4) NOT NULL,
  `verified` tinyint(4) NOT NULL DEFAULT 0,
  `vkey` varchar(100) NOT NULL,
  `signup_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_reset_required` tinyint(4) NOT NULL DEFAULT 0,
  `pkey` varchar(100) DEFAULT NULL,
  `password_reset_request_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drills`
--
ALTER TABLE `drills`
  ADD UNIQUE KEY `drill_id` (`drill_id`);

--
-- Indexes for table `drill_results`
--
ALTER TABLE `drill_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pass` (`pass`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`) USING BTREE,
  ADD UNIQUE KEY `email` (`email`) USING BTREE,
  ADD KEY `verified` (`verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drill_results`
--
ALTER TABLE `drill_results`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
