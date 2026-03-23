-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 23, 2026 at 09:47 AM
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
-- Table structure for table `hotel_promos`
--

CREATE TABLE `hotel_promos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_type` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quotes` text COLLATE utf8mb4_unicode_ci,
  `hotels_id` bigint(20) UNSIGNED NOT NULL,
  `rooms_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book_periode_start` date NOT NULL,
  `book_periode_end` date NOT NULL,
  `periode_start` date NOT NULL,
  `periode_end` date NOT NULL,
  `contract_rate` int(11) NOT NULL,
  `minimum_stay` int(11) DEFAULT NULL,
  `markup` int(11) NOT NULL,
  `booking_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` longtext COLLATE utf8mb4_unicode_ci,
  `benefits_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `benefits_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `email_status` tinyint(4) NOT NULL DEFAULT '0',
  `send_to_specific_email` tinyint(4) NOT NULL DEFAULT '0',
  `specific_email` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` int(11) DEFAULT NULL,
  `include` longtext COLLATE utf8mb4_unicode_ci,
  `include_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `include_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hotel_promos`
--
ALTER TABLE `hotel_promos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hotels_id` (`hotels_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hotel_promos`
--
ALTER TABLE `hotel_promos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
