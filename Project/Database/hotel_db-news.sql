-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2025 at 10:25 AM
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
('5', 'ยกเลิกการจองเนื่องจากชําระเงินไม่ครบภายใน 24 ชม. ตามกําหนด ยกเลิกโดยผู้ดูแลระบบ');

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
('user7@gmail.com', '$2y$10$VmRA1XEtuHrg35qPmmsE5O/zyge2WXy17rSU4JKS2K5wtQ9Srl0fK', 'นางสาว', 'สมชาย', 'คนดี', 'อื่น', '0951725416');

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
  `Email_Admin` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Booking_time` varchar(10) DEFAULT NULL,
  `Number_of_adults` int(1) DEFAULT NULL,
  `Number_of_children` int(1) DEFAULT NULL,
  `Booking_date` date DEFAULT NULL,
  `Check_out_date` date DEFAULT NULL,
  `Email_Admin` varchar(30) DEFAULT NULL,
  `Province_Id` varchar(2) DEFAULT NULL,
  `Email_member` varchar(30) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `Booking_status_Id` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`Reservation_Id`, `Guest_name`, `Number_of_rooms`, `Booking_time`, `Number_of_adults`, `Number_of_children`, `Booking_date`, `Check_out_date`, `Email_Admin`, `Province_Id`, `Email_member`, `receipt_image`, `Booking_status_Id`) VALUES
('1758791367', 'สมหญิง ใจเกร่ง', 1, '2025-09-25', 1, 0, '2025-09-25', '2025-09-30', 'admin1@example.com', NULL, 'user5@gmail.com', 'receipt_68d506c7eafff4.39818231.jpg', '3'),
('1758791492', 'สมหญิง ใจเกร่ง', 2, '2025-09-25', 4, 0, '2025-09-28', '2025-09-30', 'admin1@example.com', NULL, 'user5@gmail.com', 'receipt_68d507449eda32.91105228.jpg', '3'),
('1758793841', 'สมดี สองใจ', 1, '2025-09-25', 1, 0, '2025-09-28', '2025-09-30', NULL, '1', 'user1@gmail.com', 'receipt_68d510716404c7.24057011.jpg', '2'),
('1758793984', 'สมดี สองใจ', 1, '2025-09-25', 1, 0, '2025-09-28', '2025-09-30', NULL, '1', 'user1@gmail.com', 'receipt_68d51100d5bea5.98156340.jpg', '2'),
('1758902148', 'สมดี ผกดเหกเ', 1, '2025-09-26', 1, 0, '2025-09-27', '2025-09-30', 'admin1@example.com', '1', 'user4@gmail.com', 'receipt_68d6b784f33d15.31566633.jpg', '3');

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
('R00001', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '1'),
('R00002', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '1'),
('R00003', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '2'),
('R00004', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '2'),
('R00005', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '3'),
('R00006', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '3'),
('R00007', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '4'),
('R00008', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '4'),
('R00009', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '5'),
('R00010', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '5'),
('R00011', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '6'),
('R00012', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '6'),
('R00013', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '7'),
('R00014', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '7'),
('R00015', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '8'),
('R00016', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '8'),
('R00017', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '9'),
('R00018', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '9'),
('R00019', 930, '101', 3, 'AVL', 'ห้องพักมาตรฐานเตียงใหญ่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '1', '10'),
('R00020', 930, '201', 3, 'AVL', 'ห้องพักมาตรฐานเตียงคู่ขนาด 17.28 ตร.ม. ไม่มีระเบียงที่สร้างเคียงรบกวนจากถนน ทุกห้องมีสิ่งอำนวยความสะดวกครบครัน อาทิ เตียงนอน, เครื่องปรับอากาศ, ทีวีแอลซีดี, ตู้เย็น, ห้องอาบน้ำพร้อมเครื่องทำน้ำอุ่น และอินเตอร์เน็ต Wi-Fi ทุกห้อง (ฟรี) เข้าพักสูงสุดได้ ผู้ใหญ่ 2 ท่าน, เด็ก 1 ท่าน (อายุต่ำกว่า 12 ปี)', NULL, '2', '10');

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
  `Check_out_time` date DEFAULT NULL,
  `Guest_name` varchar(50) DEFAULT NULL,
  `Room_Id` varchar(6) DEFAULT NULL,
  `Receipt_Id` int(10) DEFAULT NULL,
  `Reservation_Id` varchar(10) DEFAULT NULL,
  `Email_member` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `Receipt_Id` (`receipt_image`),
  ADD KEY `Booking_status_Id` (`Booking_status_Id`),
  ADD KEY `reservation_ibfk_3` (`Province_Id`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`Room_Id`),
  ADD KEY `Email_Officer` (`Email_Officer`),
  ADD KEY `Room_type_Id` (`Room_type_Id`),
  ADD KEY `Province_Id` (`Province_Id`);

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
  ADD CONSTRAINT `reservation_ibfk_4` FOREIGN KEY (`Booking_status_Id`) REFERENCES `booking_status` (`Booking_status_Id`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_1` FOREIGN KEY (`Email_Officer`) REFERENCES `officer` (`Email_Officer`),
  ADD CONSTRAINT `room_ibfk_2` FOREIGN KEY (`Room_type_Id`) REFERENCES `room_type` (`Room_type_Id`),
  ADD CONSTRAINT `room_ibfk_3` FOREIGN KEY (`Province_Id`) REFERENCES `province` (`Province_Id`);

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
