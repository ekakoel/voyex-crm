-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 23, 2026 at 09:43 AM
-- Server version: 5.7.43-log
-- PHP Version: 8.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u1655344_backend`
--

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_stay` int(11) DEFAULT NULL,
  `max_stay` int(11) DEFAULT NULL,
  `check_in_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `airport_duration` int(11) DEFAULT NULL,
  `airport_distance` int(11) DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `description_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `description_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `facility` longtext COLLATE utf8mb4_unicode_ci,
  `facility_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `facility_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `wedding_info` text COLLATE utf8mb4_unicode_ci,
  `entrance_fee` longtext COLLATE utf8mb4_unicode_ci,
  `wedding_cancellation_policy` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` int(11) NOT NULL,
  `web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `map` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `optional_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_policy` longtext COLLATE utf8mb4_unicode_ci,
  `cancellation_policy_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `cancellation_policy_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotels_code_unique` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
