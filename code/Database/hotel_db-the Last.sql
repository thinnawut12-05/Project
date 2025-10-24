-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 11:18 AM
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
-- Database: `hotel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Email_Admin` varchar(30) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Title_name` varchar(6) DEFAULT NULL,
  `First_name` varchar(50) DEFAULT NULL,
  `Last_name` varchar(50) DEFAULT NULL,
  `Gender` varchar(4) DEFAULT NULL,
  `Phone_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Email_Admin`, `Password`, `Title_name`, `First_name`, `Last_name`, `Gender`, `Phone_number`) VALUES
('admin1@example.com', '$2a$12$KxAUAO9U8h8T4xyzXgYIpOEmgYghHBp25PdKM7iSWqu0K.xd/03AW', 'Mr.', 'Somchai', 'Sukhum', 'Male', '0812345678');

-- --------------------------------------------------------

--
-- Table structure for table `booking_status`
--

CREATE TABLE `booking_status` (
  `Booking_status_Id` varchar(5) NOT NULL,
  `Booking_status_name` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_status`
--

INSERT INTO `booking_status` (`Booking_status_Id`, `Booking_status_name`) VALUES
('1', 'ยืนยันการจองและรอชำระเงิน'),
('2', 'ชำระเงินสำเร็จรอการตรวจสอบ'),
('3', 'ชำระเงินสำเร็จ'),
('4', 'ยกเลิกการจองเนื่องจากไม่ชําระเงินภายใน 24 ชม. ตามกําหนด  ยกเลิกโดยระบบ'),
('5', 'ยกเลิกการจองเนื่องจากชําระเงินไม่ครบภายใน 24 ชม. ตามกําหนด ยกเลิกโดยผู้ดูแลระบบ'),
('6', 'เช็คอินแล้ว'),
('7', 'เช็คเอ้าท์แล้ว'),
('8', 'เช็คอินล่าข้า/ถูกปรับ');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `Email_member` varchar(30) NOT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Title_name` varchar(6) DEFAULT NULL,
  `First_name` varchar(50) DEFAULT NULL,
  `Last_name` varchar(50) DEFAULT NULL,
  `Gender` varchar(4) DEFAULT NULL,
  `Phone_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`Email_member`, `Password`, `Title_name`, `First_name`, `Last_name`, `Gender`, `Phone_number`) VALUES
('user1@gmail.com', '$2y$10$l4n3G8A3hACOVHLcpcTCh.8qvxa4kIsi4/NeTuyqwulAvdEmHKC.W', 'นาย', 'สมดี', 'สองใจ', 'อื่น', '0981111111'),
('user2@gmail.com', '$2y$10$Sp6EisMcsJu61rdlQ3.KquoYee7lFM6Jxs1qPf1TttOB9h3nZJHMa', 'นาย', 'สมดี', 'สส', 'ชาย', '0982321122'),
('user3@gmail.com', '$2y$10$/HnCorCUXaGNG1AQe11oSew9uK4rZ3wQBxZ3HZxUThugndM95lUGe', 'นาย', 'จงกอน', 'ปาร์ต', 'ชาย', '0981111112'),
('user4@gmail.com', '$2y$10$CYr.bntG7lEzMtdLBg8FH.nvlCdJiX/B/pnnQUlncMA1jDQtNjKxS', 'นาง', 'สมดี', 'ผกดเหกเ', 'หญิง', '0981234567'),
('user5@gmail.com', '$2y$10$Pioef5EG5GXm2OdHAlm1cuehycxLYUrfENaU6DawnjIDE8oUU9ZRy', 'นาย', 'สมหญิง', 'ใจเกร่ง', 'ชาย', '0981112222'),
('user6@gmail.com', '$2y$10$/wB9TOTk/DGhtuFxIsWEMOmwzIfnE3ZkscMijmxPLqwC0HHpcRM5K', 'นาง', 'ฮาอิน', 'ปาร์ค', 'หญิง', '0982317777'),
('user7@gmail.com', '$2y$10$VmRA1XEtuHrg35qPmmsE5O/zyge2WXy17rSU4JKS2K5wtQ9Srl0fK', 'นางสาว', 'สมชาย', 'คนดี', 'อื่น', '0951725416'),
('user8@gmail.com', '$2y$10$v1Bqoy/2h9oNn5MnIIE2IOQUbHRkTiBgsiE6soK6kcLb8ZJchkdTW', 'นาย', 'กิตติพงศ์', 'ศรีสวัสดิ์', 'ชาย', '0984562371'),
('walkin@example.com', '$2y$10$abcdefghijklmnopqrstuvwxyza.hash', NULL, 'ลูกค้า', 'Walk-in', NULL, '0000000000');

-- --------------------------------------------------------

--
-- Table structure for table `officer`
--

CREATE TABLE `officer` (
  `Email_Officer` varchar(30) NOT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Title_name` varchar(6) DEFAULT NULL,
  `First_name` varchar(50) DEFAULT NULL,
  `Last_name` varchar(50) DEFAULT NULL,
  `Gender` varchar(4) DEFAULT NULL,
  `Phone_number` varchar(10) DEFAULT NULL,
  `Email_Admin` varchar(30) DEFAULT NULL,
  `Province_Id` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officer`
--

INSERT INTO `officer` (`Email_Officer`, `Password`, `Title_name`, `First_name`, `Last_name`, `Gender`, `Phone_number`, `Email_Admin`, `Province_Id`) VALUES
('officer10@example.com', '$2a$12$wd6ZuosEbFHZR2NXuSCWg.v35bmQwrBBXJC1gIGGlAuCXTcfWjeN.', 'นางสาว', 'ศิรินทร์', 'รุ่งแสง', 'หญิง', '0810101010', 'admin1@example.com', '10'),
('officer1@example.com', '$2a$12$dKlXGT5xy9vY2aDLI4UbMuuT7Wdoi3jF6HRxSPo3xLYBGAcJd40cm', 'นาย', 'สมชาย', 'ใจดี', 'ชาย', '0811111111', 'admin1@example.com', '1'),
('officer2@example.com', '$2a$12$3YzBLf2JktGECgprpy6wZuC4o93Y/GILOUCMxF6n4dlbKjF0nE2Pe', 'นางสาว', 'สุดา', 'สุขใจ', 'หญิง', '0822222222', 'admin1@example.com', '2'),
('officer3@example.com', '$2a$12$8jMePcMorBfAcLzYsL9PIOrU7or61VGmuyzJAcShoN8nv9lPSFMBu', 'นาย', 'มนตรี', 'รุ่งเรือง', 'ชาย', '0833333333', 'admin1@example.com', '3'),
('officer4@example.com', '$2a$12$RiwzRx5HbNAeaJ4Vj158FOXtI04VAL3WgLaJmWizQBSVFV85ze1gi', 'นางสาว', 'พิมพ์ใจ', 'ทองดี', 'หญิง', '0844444444', 'admin1@example.com', '4'),
('officer5@example.com', '$2a$12$HnhsDwIBmjprsYEH05otmeJQKyK7mrRU3LM/siQwBwv/w5lqgIinO', 'นาย', 'วิชัย', 'ศรีสุข', 'ชาย', '0855555555', 'admin1@example.com', '5'),
('officer6@example.com', '$2a$12$OmM4XGGu36bTzNrIL3OlZeqsVg8UcPWL3QZbfA7egzf47mhhHqV/m', 'นางสาว', 'กานดา', 'ใจงาม', 'หญิง', '0866666666', 'admin1@example.com', '6'),
('officer7@example.com', '$2a$12$wEqF7kFrZjzwrYb09nbQjOFT3vBa5SnVF7e27spPhNSb9Yv.gPaTu', 'นาย', 'อนันต์', 'มีบุญ', 'ชาย', '0877777777', 'admin1@example.com', '7'),
('officer8@example.com', '$2a$12$o2ZZz6Yz57O2C2vMqRQigud98dUQSvuIniBlfWxpss/lkCitzSXru', 'นางสาว', 'ชุติมา', 'เพียรดี', 'หญิง', '0888888888', 'admin1@example.com', '8'),
('officer9@example.com', '$2a$12$ZIJNfon9hBHm2aYjLe2oXOc8D7rZRyh9uEIzNV183etbjpZT6c.KS', 'นาย', 'ธนพล', 'ก้องเกียรติ', 'ชาย', '0899999999', 'admin1@example.com', '9');

-- --------------------------------------------------------

--
-- Table structure for table `province`
--

CREATE TABLE `province` (
  `Province_Id` varchar(2) NOT NULL,
  `Province_name` varchar(50) DEFAULT NULL,
  `Region_Id` varchar(2) DEFAULT NULL,
  `Address` varchar(255) NOT NULL,
  `Phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `province`
--

INSERT INTO `province` (`Province_Id`, `Province_name`, `Region_Id`, `Address`, `Phone`) VALUES
('1', 'พะเยา', '1', '21 ถนนพหลโยธิน ต.เวียง อ.เมือง พะเยา 56000', '054-222-333'),
('10', 'นครศรีธรรมราช', '5', '66 ถนนราชดำเนิน ต.คลัง อ.เมือง นครศรีธรรมราช 80000', '075-555-111'),
('2', 'เชียงใหม่', '1', '45 ถนนนิมมานเหมินทร์ ต.สุเทพ อ.เมือง เชียงใหม่ 50200', '053-555-678'),
('3', 'กรุงเทพ', '2', '123 ถนนสุขุมวิท เขตวัฒนา เขตคลองเตย กรุงเทพฯ 10110', '02-123-4567'),
('4', 'อ่างทอง', '2', '88 ถนนโพธิ์ทอง ต.บางพลี อ.เมือง อ่างทอง 14000', '035-555-888'),
('5', 'ขอนแก่น', '3', '200 ถนนมิตรภาพ ต.ในเมือง อ.เมือง ขอนแก่น 40000', '043-222-444'),
('6', 'นครราชสีมา', '3', '150 ถนนราชดำเนิน ต.ในเมือง อ.เมือง นครราชสีมา 30000', '044-333-777'),
('7', 'กาญจนบุรี', '4', '12 ถนนแสงชูโต ต.บ้านใต้ อ.เมือง กาญจนบุรี 71000', '034-222-666'),
('8', 'เพชรบุรี', '4', '76 ถนนราชวิถี ต.คลองกระแชง อ.เมือง เพชรบุรี 76000', '032-444-555'),
('9', 'สุราษฎร์ธานี', '5', '99 ถนนตลาดใหม่ ต.ตลาด อ.เมือง สุราษฎร์ธานี 84000', '077-123-999');

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `Receipt_Id` int(10) NOT NULL,
  `Guest_name` varchar(100) DEFAULT NULL,
  `Receipt_date` date DEFAULT NULL,
  `Receipt_time` varchar(10) DEFAULT NULL,
  `Phone_number` varchar(10) DEFAULT NULL,
  `Payment_image_file` varchar(50) DEFAULT NULL,
  `Email_Admin` varchar(30) DEFAULT NULL,
  `Status` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`Receipt_Id`, `Guest_name`, `Receipt_date`, `Receipt_time`, `Phone_number`, `Payment_image_file`, `Email_Admin`, `Status`) VALUES
(185277788, 'สมดี สองใจ', '2025-10-15', '13:42:19', '0981111111', 'receipt_68ef424b9c2667.39575719.jpg', NULL, 'Yes'),
(220810188, 'สมดี ผกดเหกเ', '2025-10-04', '15:18:55', '0981234567', 'receipt_68e0d86f6c51c4.70369506.jpg', NULL, 'Yes'),
(244664874, 'กิตติพงศ์ ศรีสวัสดิ์', '2025-10-15', '13:14:12', '0984562371', 'receipt_68ef3bb4409db6.68527662.jpg', NULL, 'Yes'),
(383036472, 'ฮาอิน ปาร์ค', '2025-10-20', '18:19:18', '0982317777', 'receipt_68f61ab63b39e0.98054721.jpg', NULL, 'Yes'),
(460861183, 'สมดี สองใจ', '2025-10-14', '20:18:17', '0981111111', 'receipt_68ee4d99ac6f43.74919511.jpg', NULL, 'Yes'),
(461902576, 'สมหญิง ใจเกร่ง', '2025-10-19', '23:28:22', '0981112222', 'receipt_68f511a68b31b6.33455886.jpg', NULL, 'No'),
(477669803, 'สมดี สองใจ', '2025-10-15', '13:28:43', '0981111111', 'receipt_68ef3f1ba09654.48494566.jpg', NULL, 'Yes'),
(598059857, 'สมห่าง สีดี', '2025-10-10', '15:09:58', '0987654323', 'receipt_68e8bf5648a054.30150543.jpg', 'admin1@example.com', 'Yes'),
(782488248, 'สมดี สองใจ', '2025-10-03', '12:31:53', '0981111111', 'receipt_68dfa619c552a8.80083345.jpg', NULL, 'Yes'),
(835982992, 'สมดี สองใจ', '2025-10-07', '13:56:54', '0981111111', 'receipt_68e4b9b66c9b31.73226137.jpg', NULL, 'Yes'),
(1152201616, 'สมดี สองใจ', '2025-10-06', '01:14:59', '0981111111', 'receipt_68e2b5a3242357.06690481.jpg', NULL, 'Yes'),
(1317375777, 'สมดี สองใจ', '2025-10-15', '11:04:51', '0981111111', 'receipt_68ef1d63365271.71155349.jpg', NULL, 'Yes'),
(1346657723, 'สมดี สองใจ', '2025-10-08', '00:52:58', '0981111111', 'receipt_68e5537a9afec9.74738888.jpg', NULL, 'Yes'),
(1474355690, 'สมดี ผกดเหกเ', '2025-10-04', '15:15:31', '0981234567', 'receipt_68e0d7a3a9c436.16724458.jpg', NULL, 'Yes'),
(1676909116, 'สมดี สองใจ', '2025-10-07', '13:37:09', '0981111111', 'receipt_68e4b515ce7e53.07278391.jpg', NULL, 'Yes'),
(1717557356, 'สมห่าง สีดี', '2025-10-10', '14:44:56', '0987654323', 'receipt_68e8b978807af7.14149403.jpg', NULL, 'Yes'),
(1722183189, 'สมชาย ใจดี', '2025-10-14', '15:30:21', '0981111111', 'receipt_68ee0a1d12b3e9.37835691.jpg', NULL, 'No'),
(1808977654, 'สมดี สองใจ', '2025-10-03', '10:30:54', '0981111111', 'receipt_68df89be400f68.50475446.jpg', NULL, 'No'),
(1878536775, 'สมหญิง ใจเกร่ง', '2025-10-03', '17:30:01', '0981112222', 'receipt_68dfebf956e416.25467523.jpg', NULL, 'Yes'),
(1879926656, 'สมดี สองใจ', '2025-10-07', '13:44:54', '0981111111', 'receipt_68e4b6e65dd5f7.94267575.jpg', NULL, 'Yes'),
(1984275185, 'สมดี สองใจ', '2025-10-15', '23:55:52', '0981111111', 'receipt_68efd218d53ff5.71998143.jpg', NULL, 'Yes'),
(1991303828, 'สมดี สองใจ', '2025-10-07', '14:04:08', '0981111111', 'receipt_68e4bb68789d78.30218000.jpg', NULL, 'Yes'),
(1999543796, 'สมดี สองใจ', '2025-10-03', '10:44:15', '0981111111', 'receipt_68df8cdf104953.19192619.jpg', NULL, 'No'),
(2015333900, 'สมดี ผกดเหกเ', '2025-10-09', '01:20:08', '0981234567', 'receipt_68e6ab58176393.26364441.jpg', NULL, 'Yes'),
(2047192613, 'จงกอน ปาร์ต', '2025-10-07', '19:19:37', '0981111112', 'receipt_68e50559eba840.65821275.jpg', NULL, 'Yes'),
(2058424250, 'สมดี สองใจ', '2025-10-03', '10:56:39', '0981111111', 'receipt_68df8fc7366777.83769799.jpg', NULL, 'No'),
(2076377635, 'สมหญิง ใจเกร่ง', '2025-10-03', '23:01:40', '0981112222', 'receipt_68dff36472ac44.48436465.jpg', NULL, 'Yes'),
(2105034027, 'สมดี ผกดเหกเ', '2025-10-09', '03:02:12', '0981234567', 'receipt_68e6c344b23970.64493171.jpg', NULL, 'Yes'),
(2122076433, 'จงกอน ปาร์ต', '2025-10-07', '19:08:55', '0981111112', 'receipt_68e502d753bbb9.99765460.jpg', NULL, 'Yes'),
(2133375484, 'สมดี สองใจ', '2025-10-03', '10:58:08', '0981111111', 'receipt_68df9020ded8e9.74315338.jpg', NULL, 'No'),
(2142182290, 'สมดี สองใจ', '2025-10-07', '12:45:29', '0981111111', 'receipt_68e4a8f981fb29.11803732.jpg', NULL, 'Yes'),
(2147483647, 'สมดี สองใจ', '2025-10-03', '10:07:58', '0981111111', 'receipt_68df845e09abe7.00307840.jpg', NULL, 'No');

-- --------------------------------------------------------

--
-- Table structure for table `region`
--

CREATE TABLE `region` (
  `Region_Id` varchar(2) NOT NULL,
  `Region_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `region`
--

INSERT INTO `region` (`Region_Id`, `Region_name`) VALUES
('1', 'เหนือ'),
('2', 'กลาง'),
('3', 'ตะวันออกเฉียงเหนือ'),
('4', 'ตะวันตก'),
('5', 'ใต้');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `Reservation_Id` varchar(10) NOT NULL,
  `Guest_name` varchar(100) DEFAULT NULL,
  `Number_of_rooms` int(2) DEFAULT NULL,
  `Booking_time` datetime DEFAULT NULL,
  `Number_of_adults` int(1) DEFAULT NULL,
  `Number_of_children` int(1) DEFAULT NULL,
  `Booking_date` date DEFAULT NULL,
  `Check_out_date` date DEFAULT NULL,
  `Email_Admin` varchar(30) DEFAULT NULL,
  `Province_Id` varchar(2) DEFAULT NULL,
  `Email_member` varchar(30) DEFAULT NULL,
  `Booking_status_Id` varchar(5) DEFAULT NULL,
  `stars` decimal(2,1) DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Receipt_Id` int(10) DEFAULT NULL,
  `Total_price` int(100) NOT NULL,
  `rating_timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`Reservation_Id`, `Guest_name`, `Number_of_rooms`, `Booking_time`, `Number_of_adults`, `Number_of_children`, `Booking_date`, `Check_out_date`, `Email_Admin`, `Province_Id`, `Email_member`, `Booking_status_Id`, `stars`, `comment`, `Receipt_Id`, `Total_price`, `rating_timestamp`) VALUES
('2607053576', 'สมดี สองใจ', 1, '2025-10-15 23:55:47', 1, 0, '2025-10-15', '2025-10-23', NULL, '2', 'user1@gmail.com', '7', 5.0, 'ดีมากๆ', 1984275185, 7440, '2025-10-16 00:33:33'),
('2798138596', 'สมดี สองใจ', 1, '2025-10-08 00:52:54', 1, 0, '2025-10-08', '2025-10-09', NULL, '1', 'user1@gmail.com', '7', 5.0, 'พนักงานตอนรับดีมาก', 1346657723, 930, '2025-10-11 15:23:13'),
('4016008165', 'สมดี สองใจ', 1, '2025-10-15 13:28:38', 1, 0, '2025-10-15', '2025-10-16', NULL, '1', 'user1@gmail.com', '7', 4.0, '5555', 477669803, 930, '2025-10-15 13:29:13'),
('5590606712', 'สมดี สองใจ', 1, '2025-10-07 12:45:21', 1, 0, '2025-10-07', '2025-10-08', NULL, '1', 'user1@gmail.com', '7', 5.0, 'ห้องพักนอนสบายมาก', 2142182290, 930, '2025-10-13 16:46:17'),
('7510422320', 'สมดี สองใจ', 1, '2025-10-15 13:42:13', 1, 0, '2025-11-01', '2025-11-30', NULL, '1', 'user1@gmail.com', '7', 2.0, 'ห้องน้ำไม่สะอาด', 185277788, 26970, '2025-10-17 13:35:35'),
('7640779295', 'ลูกค้า', 1, '2025-10-15 13:34:36', 1, 0, '2025-10-15', '2025-10-16', NULL, '1', 'walkin@example.com', '7', NULL, NULL, NULL, 930, NULL),
('8261976144', 'ลูกค้า', 1, '2025-10-13 15:54:23', 1, 0, '2025-10-13', '2025-10-14', NULL, '1', 'walkin@example.com', '7', NULL, NULL, NULL, 930, NULL),
('8662151554', 'สมดี สองใจ', 1, '2025-10-07 13:37:04', 1, 0, '2025-10-15', '2025-10-18', NULL, '1', 'user1@gmail.com', '7', 5.0, 'ดีๆๆๆๆๆๆๆ', 1676909116, 2790, '2025-10-11 15:19:36'),
('9016202198', 'สมดี สองใจ', 1, '2025-10-06 01:14:54', 1, 0, '2025-10-06', '2025-10-07', NULL, '1', 'user1@gmail.com', '7', 5.0, 'พนักงานเป็นกันเองสุดๆ', 1152201616, 930, '2025-10-13 16:46:26'),
('9904392982', 'จงกอน ปาร์ต', 1, '2025-10-07 19:08:49', 1, 0, '2025-10-14', '2025-10-15', NULL, '1', 'user3@gmail.com', '7', NULL, NULL, 2122076433, 930, NULL),
('9995660847', 'สมดี ผกดเหกเ', 1, '2025-10-09 01:06:59', 1, 0, '2025-10-09', '2025-10-13', NULL, '1', 'user4@gmail.com', '7', NULL, NULL, 2015333900, 3720, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `Room_Id` varchar(6) NOT NULL,
  `Price` float DEFAULT NULL,
  `Room_number` varchar(3) DEFAULT NULL,
  `Number_of_people_staying` int(2) DEFAULT NULL,
  `Status` varchar(3) DEFAULT NULL,
  `Room_details` varchar(500) DEFAULT NULL,
  `Email_Officer` varchar(30) DEFAULT NULL,
  `Room_type_Id` varchar(2) DEFAULT NULL,
  `Province_Id` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`Room_Id`, `Price`, `Room_number`, `Number_of_people_staying`, `Status`, `Room_details`, `Email_Officer`, `Room_type_Id`, `Province_Id`) VALUES
('R00001', 930, '101', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '1', '1'),
('R00002', 930, '201', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '2', '1'),
('R00003', 930, '101', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '1', '2'),
('R00004', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '2'),
('R00005', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '3'),
('R00006', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '3'),
('R00007', 930, '101', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '1', '4'),
('R00008', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '4'),
('R00009', 930, '101', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '1', '5'),
('R00010', 930, '201', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '2', '5'),
('R00011', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '6'),
('R00012', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '6'),
('R00013', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '7'),
('R00014', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '7'),
('R00015', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '8'),
('R00016', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '8'),
('R00017', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '9'),
('R00018', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '9'),
('R00019', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '10'),
('R00020', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '10'),
('R00021', 930, '304', 3, 'AVL', 'ห้องพักปกติ (พร้อมใช้งาน)', NULL, '2', '1');

-- --------------------------------------------------------

--
-- Table structure for table `room_damages`
--

CREATE TABLE `room_damages` (
  `Damage_Id` int(11) NOT NULL,
  `Stay_Id` varchar(10) NOT NULL,
  `Room_Id` varchar(10) NOT NULL,
  `Damage_item` varchar(255) NOT NULL,
  `Damage_description` text DEFAULT NULL,
  `Damage_value` decimal(10,2) NOT NULL,
  `Damage_date` datetime DEFAULT current_timestamp(),
  `Officer_Email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_damages`
--

INSERT INTO `room_damages` (`Damage_Id`, `Stay_Id`, `Room_Id`, `Damage_item`, `Damage_description`, `Damage_value`, `Damage_date`, `Officer_Email`) VALUES
(1, '3859760845', 'R00001', 'ประตู', '0', 400.00, '2025-10-07 00:00:00', 'officer1@example.com'),
(4, '2539655131', 'R00001', 'ประตู', '0', 100.00, '2025-10-07 12:59:12', 'officer1@example.com'),
(5, '2539655131', 'R00001', 'ประตู', '0', 100.00, '2025-10-07 13:03:32', 'officer1@example.com'),
(6, '2539655131', 'R00001', 'ประตู', '0', 1000.00, '2025-10-07 13:25:35', 'officer1@example.com'),
(7, '2539655131', 'R00001', 'ประตู', '0', 1000.00, '2025-10-07 13:28:03', 'officer1@example.com'),
(14, '1004902848', 'R00001', 'ประตู', '0', 450.00, '2025-10-13 16:05:04', 'officer1@example.com'),
(15, '1807078760', 'R00002', 'กระจก', '0', 1500.00, '2025-10-15 13:39:17', 'officer1@example.com');

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

CREATE TABLE `room_type` (
  `Room_type_Id` varchar(2) NOT NULL,
  `Room_type_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_type`
--

INSERT INTO `room_type` (`Room_type_Id`, `Room_type_name`) VALUES
('1', 'ห้องมาตรฐาน เตียงใหญ'),
('2', 'ห้องมาตรฐาน เตียงคู่');

-- --------------------------------------------------------

--
-- Table structure for table `stay`
--

CREATE TABLE `stay` (
  `Stay_Id` varchar(10) NOT NULL,
  `Check_in_date` date DEFAULT NULL,
  `Check_in_time` time DEFAULT NULL,
  `Check_out_date` date DEFAULT NULL,
  `Check_out_time` time DEFAULT NULL,
  `Guest_name` varchar(50) DEFAULT NULL,
  `Room_Id` varchar(6) DEFAULT NULL,
  `Receipt_Id` int(10) DEFAULT NULL,
  `Reservation_Id` varchar(10) DEFAULT NULL,
  `Email_member` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stay`
--

INSERT INTO `stay` (`Stay_Id`, `Check_in_date`, `Check_in_time`, `Check_out_date`, `Check_out_time`, `Guest_name`, `Room_Id`, `Receipt_Id`, `Reservation_Id`, `Email_member`) VALUES
('1004902848', '2025-10-13', '15:54:23', '2025-10-13', '16:07:41', 'ลูกค้า', 'R00001', NULL, '8261976144', 'walkin@example.com'),
('1807078760', '2025-10-15', '13:34:38', '2025-10-15', '13:39:23', 'ลูกค้า', 'R00002', NULL, '7640779295', 'walkin@example.com'),
('1924131534', '2025-10-15', '23:57:24', '2025-10-15', '23:57:25', 'สมดี สองใจ', 'R00004', 1984275185, '2607053576', 'user1@gmail.com'),
('2357910925', '2025-10-08', '00:53:28', '2025-10-08', '20:36:14', 'สมดี สองใจ', 'R00001', 1346657723, '2798138596', 'user1@gmail.com'),
('2539655131', '2025-10-07', '12:45:00', '2025-10-07', '14:04:54', 'สมดี สองใจ', 'R00001', 2142182290, '5590606712', 'user1@gmail.com'),
('3859760845', '2025-10-06', '21:08:00', '2025-10-07', '01:33:41', 'สมดี สองใจ', 'R00001', 1152201616, '9016202198', 'user1@gmail.com'),
('6864642904', '2025-11-01', '12:55:36', '2025-10-17', '12:55:38', 'สมดี สองใจ', 'R00001', 185277788, '7510422320', 'user1@gmail.com'),
('8316819208', '2025-10-15', '13:29:00', '2025-10-15', '13:29:02', 'สมดี สองใจ', 'R00001', 477669803, '4016008165', 'user1@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Email_Admin`);

--
-- Indexes for table `booking_status`
--
ALTER TABLE `booking_status`
  ADD PRIMARY KEY (`Booking_status_Id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`Email_member`);

--
-- Indexes for table `officer`
--
ALTER TABLE `officer`
  ADD PRIMARY KEY (`Email_Officer`),
  ADD KEY `Email_Admin` (`Email_Admin`),
  ADD KEY `Province_Id` (`Province_Id`);

--
-- Indexes for table `province`
--
ALTER TABLE `province`
  ADD PRIMARY KEY (`Province_Id`),
  ADD KEY `Region_Id` (`Region_Id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`Receipt_Id`),
  ADD KEY `Email_Admin` (`Email_Admin`);

--
-- Indexes for table `region`
--
ALTER TABLE `region`
  ADD PRIMARY KEY (`Region_Id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`Reservation_Id`),
  ADD KEY `Email_Admin` (`Email_Admin`),
  ADD KEY `Email_member` (`Email_member`),
  ADD KEY `Booking_status_Id` (`Booking_status_Id`),
  ADD KEY `reservation_ibfk_3` (`Province_Id`),
  ADD KEY `reservation_ibfk_5` (`Receipt_Id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`Room_Id`),
  ADD KEY `Email_Officer` (`Email_Officer`),
  ADD KEY `Room_type_Id` (`Room_type_Id`),
  ADD KEY `Province_Id` (`Province_Id`);

--
-- Indexes for table `room_damages`
--
ALTER TABLE `room_damages`
  ADD PRIMARY KEY (`Damage_Id`),
  ADD KEY `Room_Id` (`Room_Id`),
  ADD KEY `Officer_Email` (`Officer_Email`),
  ADD KEY `fk_room_damages_stay_id` (`Stay_Id`);

--
-- Indexes for table `room_type`
--
ALTER TABLE `room_type`
  ADD PRIMARY KEY (`Room_type_Id`);

--
-- Indexes for table `stay`
--
ALTER TABLE `stay`
  ADD PRIMARY KEY (`Stay_Id`),
  ADD KEY `Room_Id` (`Room_Id`),
  ADD KEY `Receipt_Id` (`Receipt_Id`),
  ADD KEY `Reservation_Id` (`Reservation_Id`),
  ADD KEY `Email_member` (`Email_member`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `room_damages`
--
ALTER TABLE `room_damages`
  MODIFY `Damage_Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `officer`
--
ALTER TABLE `officer`
  ADD CONSTRAINT `officer_ibfk_1` FOREIGN KEY (`Email_Admin`) REFERENCES `admin` (`Email_Admin`),
  ADD CONSTRAINT `officer_ibfk_2` FOREIGN KEY (`Province_Id`) REFERENCES `province` (`Province_Id`);

--
-- Constraints for table `province`
--
ALTER TABLE `province`
  ADD CONSTRAINT `province_ibfk_1` FOREIGN KEY (`Region_Id`) REFERENCES `region` (`Region_Id`);

--
-- Constraints for table `receipt`
--
ALTER TABLE `receipt`
  ADD CONSTRAINT `receipt_ibfk_1` FOREIGN KEY (`Email_Admin`) REFERENCES `admin` (`Email_Admin`);

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`Email_Admin`) REFERENCES `admin` (`Email_Admin`),
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`Email_member`) REFERENCES `member` (`Email_member`),
  ADD CONSTRAINT `reservation_ibfk_3` FOREIGN KEY (`Province_Id`) REFERENCES `province` (`Province_Id`),
  ADD CONSTRAINT `reservation_ibfk_4` FOREIGN KEY (`Booking_status_Id`) REFERENCES `booking_status` (`Booking_status_Id`),
  ADD CONSTRAINT `reservation_ibfk_5` FOREIGN KEY (`Receipt_Id`) REFERENCES `receipt` (`Receipt_Id`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_1` FOREIGN KEY (`Email_Officer`) REFERENCES `officer` (`Email_Officer`),
  ADD CONSTRAINT `room_ibfk_2` FOREIGN KEY (`Room_type_Id`) REFERENCES `room_type` (`Room_type_Id`),
  ADD CONSTRAINT `room_ibfk_3` FOREIGN KEY (`Province_Id`) REFERENCES `province` (`Province_Id`);

--
-- Constraints for table `room_damages`
--
ALTER TABLE `room_damages`
  ADD CONSTRAINT `fk_room_damages_stay_id` FOREIGN KEY (`Stay_Id`) REFERENCES `stay` (`Stay_Id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `room_damages_ibfk_1` FOREIGN KEY (`Stay_Id`) REFERENCES `stay` (`Stay_Id`),
  ADD CONSTRAINT `room_damages_ibfk_2` FOREIGN KEY (`Room_Id`) REFERENCES `room` (`Room_Id`),
  ADD CONSTRAINT `room_damages_ibfk_3` FOREIGN KEY (`Officer_Email`) REFERENCES `officer` (`Email_Officer`);

--
-- Constraints for table `stay`
--
ALTER TABLE `stay`
  ADD CONSTRAINT `stay_ibfk_1` FOREIGN KEY (`Room_Id`) REFERENCES `room` (`Room_Id`),
  ADD CONSTRAINT `stay_ibfk_2` FOREIGN KEY (`Receipt_Id`) REFERENCES `receipt` (`Receipt_Id`),
  ADD CONSTRAINT `stay_ibfk_3` FOREIGN KEY (`Reservation_Id`) REFERENCES `reservation` (`Reservation_Id`),
  ADD CONSTRAINT `stay_ibfk_4` FOREIGN KEY (`Email_member`) REFERENCES `member` (`Email_member`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
