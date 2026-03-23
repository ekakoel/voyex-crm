-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 23, 2026 at 09:45 AM
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
-- Table structure for table `hotel_packages`
--

CREATE TABLE `hotel_packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hotels_id` bigint(20) UNSIGNED NOT NULL,
  `rooms_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stay_period_start` date NOT NULL,
  `stay_period_end` date NOT NULL,
  `contract_rate` int(11) NOT NULL,
  `markup` int(11) NOT NULL,
  `booking_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` longtext COLLATE utf8mb4_unicode_ci,
  `benefits_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `benefits_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `include` longtext COLLATE utf8mb4_unicode_ci,
  `include_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `include_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_traditional` longtext COLLATE utf8mb4_unicode_ci,
  `additional_info_simplified` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hotel_packages`
--
ALTER TABLE `hotel_packages`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hotel_packages`
--
ALTER TABLE `hotel_packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
