-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 03:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
-- MySQL database schema for ShopNow e-commerce application
-- To be used with XAMPP

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS shopdb;
USE shopdb;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shopdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address` varchar(128) NOT NULL,
  `city` varchar(128) NOT NULL,
  `state` varchar(128) NOT NULL,
  `is_default` int(1) NOT NULL,
  `user_id` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', 'electronics', 'Electronic devices and gadgets', 'https://images.unsplash.com/photo-1526738549149-8e07eca6c147', NULL, 1, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(2, 'Clothing', 'clothing', 'Fashion clothing and accessories', 'https://images.unsplash.com/photo-1534452203293-494d7ddbf7e0', NULL, 2, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(3, 'Home & Kitchen', 'home-kitchen', 'Home appliances and kitchen equipment', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7', NULL, 3, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(4, 'Books', 'books', 'Books, magazines and literature', 'https://images.unsplash.com/photo-1516979187457-637abb4f9353', NULL, 4, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(5, 'Sports', 'sports-outdoors', 'Sporting goods and outdoor equipment', 'https://images.unsplash.com/photo-1517649763962-0c623066013b', NULL, 5, '2025-05-11 13:53:25', '2025-05-28 19:04:24'),
(6, 'Beauty', 'beauty-personal-care', 'Beauty products and personal care items', 'https://images.unsplash.com/photo-1526047932273-341f2a7631f9', NULL, 6, '2025-05-11 13:53:25', '2025-05-28 19:05:03');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) DEFAULT 0.00,
  `max_usage` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `expiry_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `min_order_value`, `max_usage`, `usage_count`, `active`, `expiry_date`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 50.00, 100, 0, 1, '2025-12-31', '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(2, 'SUMMER25', 'percentage', 25.00, 100.00, 50, 0, 1, '2025-08-31', '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(3, 'SALE15', 'percentage', 15.00, 75.00, 75, 0, 1, '2025-12-31', '2025-05-11 13:53:25', '2025-05-11 13:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `subtotal` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `shipping` text NOT NULL,
  `tax` int(11) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount` int(11) NOT NULL,
  `coupon_code` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `subtotal`, `total`, `shipping`, `tax`, `phone`, `email`, `payment_method`, `transaction_id`, `notes`, `created_at`, `updated_at`, `discount`, `coupon_code`) VALUES
(3, 3, 'cancelled', 250, 274.99, '0', 25, '', '', 'credit_card', NULL, NULL, '2025-05-20 14:34:54', '2025-05-29 07:25:59', 0, NULL),
(4, 3, 'cancelled', 90, 98.99, '0', 9, '', '', 'paypal', NULL, NULL, '2025-05-20 14:44:53', '2025-05-29 07:26:03', 0, NULL),
(5, 3, 'cancelled', 35, 38.49, '0', 3, '', '', 'paypal', NULL, NULL, '2025-05-20 14:48:03', '2025-05-20 16:35:18', 0, NULL),
(6, 3, 'delivered', 880, 967.88, '0', 88, '', '', 'paypal', NULL, NULL, '2025-05-23 07:07:05', '2025-06-03 09:21:03', 0, NULL),
(7, 5, 'delivered', 90, 98.99, '0', 9, '', '', 'bank_transfer', NULL, NULL, '2025-06-03 09:26:38', '2025-06-03 09:28:01', 0, NULL),
(8, 5, 'pending', 70, 76.98, '0', 7, '', '', 'bank_transfer', NULL, NULL, '2025-06-03 13:52:49', '2025-06-03 13:52:49', 0, NULL),
(9, 9, 'pending', 80, 87.99, '0', 8, '', '', 'bank_transfer', NULL, NULL, '2025-06-03 14:20:03', '2025-06-03 14:20:03', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_addresses`
--

CREATE TABLE `order_addresses` (
  `order_id` int(128) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `last_name` varchar(20) NOT NULL,
  `email` varchar(128) NOT NULL,
  `phone` int(20) NOT NULL,
  `address` varchar(128) NOT NULL,
  `address2` varchar(128) NOT NULL,
  `city` varchar(20) NOT NULL,
  `state` varchar(20) NOT NULL,
  `zip_code` int(20) NOT NULL,
  `country` varchar(20) NOT NULL,
  `is_shipping` tinyint(1) NOT NULL,
  `is_billing` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_addresses`
--

INSERT INTO `order_addresses` (`order_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `address2`, `city`, `state`, `zip_code`, `country`, `is_shipping`, `is_billing`) VALUES
(3, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1),
(4, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1),
(5, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 1, 'IN', 1, 1),
(6, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1),
(7, 'a', 'a', 'akshit62832@gmail.com', 1111111111, 'a', 'a', 'a', 'a', 0, 'IN', 1, 1),
(8, 'a', 'a', 'akshit62832@gmail.com', 1111111111, 'a', 'a', 'a1', '1', 11, 'US', 1, 1),
(9, 'test', 'mahajanji', 'test@gmail.com', 628325, 'jandpeer gali', 'khandwala', 'asr', 'punjab', 143001, 'IN', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `created_at`, `name`) VALUES
(1, 3, 1, '', 249.99, 1, 249.99, '2025-05-20 14:34:54', 'Premium Smart Watch'),
(2, 4, 5, '', 89.99, 1, 89.99, '2025-05-20 14:44:53', 'Portable Bluetooth S'),
(3, 5, 6, '', 34.99, 1, 34.99, '2025-05-20 14:48:03', 'Ceramic Coffee Mug S'),
(4, 6, 4, '', 79.99, 11, 879.89, '2025-05-23 07:07:05', 'Stylish Sunglasses'),
(5, 7, 5, '', 89.99, 1, 89.99, '2025-06-03 09:26:38', 'Portable Bluetooth S'),
(6, 8, 6, '', 34.99, 2, 69.98, '2025-06-03 13:52:49', 'Ceramic Coffee Mug S'),
(7, 9, 4, '', 79.99, 1, 79.99, '2025-06-03 14:20:03', 'Stylish Sunglasses');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `in_stock` tinyint(1) NOT NULL DEFAULT 1,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `rating_count` int(11) DEFAULT 0,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `description`, `price`, `stock_quantity`, `in_stock`, `featured`, `category_id`, `image`, `specifications`, `brand`, `rating`, `rating_count`, `tags`, `created_at`, `updated_at`) VALUES
(1, 'Premium Smart Watch', 'WATCH001', 'Stay connected with our premium smart watch that tracks your fitness goals and keeps you updated with notifications.', 249.99, 24, 1, 1, 1, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30', 'Display: 1.3\" AMOLED\r\nBattery Life: Up to 7 days\r\nWater Resistant: 5 ATM\r\nConnectivity: Bluetooth 5.0\r\nCompatibility: iOS 12.0+ / Android 7.0+', 'TechGear', 4.5, 120, 'smart watch, fitness tracker, wearable tech', '2025-05-11 13:53:25', '2025-05-20 14:34:54'),
(2, 'Wireless Bluetooth Earbuds', 'EARBUDS002', 'Premium wireless earbuds with active noise cancellation and superior sound quality. Perfect for music lovers on the go.', 129.99, 40, 1, 1, 1, 'https://images.unsplash.com/photo-1524678606370-a47ad25cb82a', 'Battery Life: 8 hours (24 with case)\r\nActive Noise Cancellation: Yes\r\nWater Resistant: IPX4\r\nBluetooth: 5.1\r\nCharging: USB-C, Wireless', 'AudioPro', 4.7, 85, 'earbuds, wireless, bluetooth, audio', '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(3, 'Premium Leather Wallet', 'WALLET003', 'Handcrafted genuine leather wallet with multiple card slots and RFID protection. Elegant and functional design.', 59.99, 50, 1, 0, 2, 'https://images.unsplash.com/photo-1509695507497-903c140c43b0', 'Material: Genuine Leather\r\nDimensions: 4.5\" x 3.5\" x 0.5\"\r\nCard Slots: 8\r\nRFID Protection: Yes\r\nColor: Brown', 'LeatherCraft', 4.3, 65, 'wallet, leather, accessories, men', '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(4, 'Stylish Sunglasses', 'SUN004', 'Trendy sunglasses with UV protection and polarized lenses. Perfect for beach days and outdoor activities.', 79.99, 23, 1, 1, 2, 'https://images.unsplash.com/photo-1525904097878-94fb15835963', 'Frame Material: Acetate\r\nLens: Polarized\r\nUV Protection: 100%\r\nStyle: Wayfarer\r\nIncludes: Protective Case', 'VisionStyle', 4.2, 42, 'sunglasses, eyewear, accessories, summer', '2025-05-11 13:53:25', '2025-06-03 14:20:03'),
(5, 'Portable Bluetooth Speaker', 'SPEAKER005', 'Compact and powerful bluetooth speaker with 360° sound and 20 hours of battery life. Take your music anywhere.', 89.99, 18, 1, 1, 1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e', 'Power Output: 20W\r\nBattery Life: 20 hours\r\nWaterproof: IPX7\r\nBluetooth Range: 30m\r\nSize: 7\" x 3\" x 3\"', 'SoundWave', 4.6, 110, 'speaker, bluetooth, audio, portable', '2025-05-11 13:53:25', '2025-06-03 09:26:38'),
(6, 'Ceramic Coffee Mug Set', 'MUG006', 'Set of 4 premium ceramic coffee mugs in assorted colors. Microwave and dishwasher safe.', 34.99, 42, 1, 0, 3, 'https://images.unsplash.com/photo-1479064555552-3ef4979f8908', 'Material: Ceramic\r\nCapacity: 12oz\r\nDishwasher Safe: Yes\r\nMicrowave Safe: Yes\r\nColors: Assorted', 'HomeEssentials', 4.4, 78, 'mugs, coffee, kitchenware, ceramic', '2025-05-11 13:53:25', '2025-06-03 13:52:49'),
(7, 'iphone 16 pro', '10', 'Pro camera system\r\n\r\n48MP Fusion | 48MP Ultra Wide | Telephoto\r\n\r\nSuper-high‑resolution photos\r\n(24MP and 48MP)\r\n\r\nNext-generation portraits with Focus and Depth Control\r\n\r\n48MP macro photography\r\n\r\nDolby Vision up to 4K at 120 fps\r\n\r\nSpatial photos and videos\r\n\r\nLatest-generation Photographic Styles\r\n\r\nVisual intelligence, to learn about your surroundings', 100000.00, 0, 0, 1, 1, 'https://m.media-amazon.com/images/I/61qV17Px4NL._AC_UY327_FMwebp_QL65_.jpg', 'Pro camera system\r\n\r\n48MP Fusion | 48MP Ultra Wide | Telephoto\r\n\r\nSuper-high‑resolution photos\r\n(24MP and 48MP)\r\n\r\nNext-generation portraits with Focus and Depth Control\r\n\r\n48MP macro photography\r\n\r\nDolby Vision up to 4K at 120 fps\r\n\r\nSpatial photos and videos\r\n\r\nLatest-generation Photographic Styles\r\n\r\nVisual intelligence, to learn about your surroundings', 'apple', NULL, 0, '', '2025-06-03 09:39:56', '2025-06-03 12:02:44');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_addresses`
--

CREATE TABLE `saved_addresses` (
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(128) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `last_name` varchar(20) NOT NULL,
  `email` varchar(128) NOT NULL,
  `phone` int(20) NOT NULL,
  `address` varchar(128) NOT NULL,
  `address2` varchar(128) NOT NULL,
  `city` varchar(20) NOT NULL,
  `state` varchar(20) NOT NULL,
  `zip_code` int(20) NOT NULL,
  `country` varchar(20) NOT NULL,
  `is_shipping` tinyint(1) NOT NULL,
  `is_billing` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_addresses`
--

INSERT INTO `saved_addresses` (`user_id`, `order_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `address2`, `city`, `state`, `zip_code`, `country`, `is_shipping`, `is_billing`) VALUES
(3, 3, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1),
(3, 4, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1),
(3, 5, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 1, 'IN', 1, 1),
(3, 6, 'akshit', 'mahajan', 'akshit628325@gmail.com', 2147483647, 'jandpeer colony,khandwale, amritsar.', 'jandpeer colony,khandwale, amritsar.', 'AMRITSAR', 'Punjab', 143001, 'IN', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firebase_uid` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firebase_uid`, `email`, `name`, `password`, `is_admin`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'admin123', 'admin@shopnow.com', 'Admin User', '$2y$10$1qAz2wSx3eDc4rFv5tDOsu/4uQEgdZO3.PW.VzF8.CariVV6/2.re', 1, NULL, NULL, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(2, 'user123', 'customer@example.com', 'John Customer', '$2y$10$yCmMb41Vl.e1v9z0HQ.aje77ZK1RG7ECNelQwQL9YfdfoZXkc/MZ2', 0, NULL, NULL, '2025-05-11 13:53:25', '2025-05-11 13:53:25'),
(3, 'HAGKfrnt5Ie6nOIDwve5tDfJRIs1', 'akshit628325@gmail.com', 'Akshit', NULL, 0, NULL, NULL, '2025-05-20 10:58:16', '2025-05-20 10:58:16'),
(4, 'vfW0sSLu03eJh99h9OHPVooFrjk2', 'akshit@gmail.com', 'akshit', NULL, 0, NULL, NULL, '2025-05-29 10:22:54', '2025-05-29 10:22:54'),
(5, 'mJH0tigd75QGPIFgcPBZv8BTE6w1', 'akshit62832@gmail.com', 'Akshit mahajan', NULL, 0, NULL, NULL, '2025-05-31 10:42:09', '2025-05-31 10:42:09'),
(6, 'xP73dBN8G4SOTgGNphdvN0WMs4F3', 'akshit1@gmail.com', 'a', NULL, 0, NULL, NULL, '2025-05-31 11:20:34', '2025-05-31 11:20:34'),
(7, 'Ffqcil4rXDRWjEx9h5XYgN4NiI32', 'akshit2@gmail.com', 'ak', NULL, 0, NULL, NULL, '2025-05-31 11:51:43', '2025-05-31 11:51:43'),
(8, 'LNyrvmwLwZc2fLyb3Z8dUP7ErkP2', 'akshit10@gmail.com', 'billo', NULL, 0, NULL, NULL, '2025-06-03 09:24:21', '2025-06-03 09:24:21'),
(9, 'i0MbfiUvr7fM0Wjpj83oQEsMEZW2', 'test@gmail.com', 'test', '$2y$10$SzC.4qOIKlDB0izb6gCIpucQ06U38d5slnweV2Wi/p4F5BaYwH4aW', 0, '628325', 'jandpeer gali, khandwala, asr, punjab 143001, IN', '2025-06-03 14:18:56', '2025-06-03 14:20:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `firebase_uid` (`firebase_uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
