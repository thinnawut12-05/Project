-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 13, 2025 at 07:04 PM
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
  `Password` varchar(15) DEFAULT NULL,
  `Title_name` varchar(6) DEFAULT NULL,
  `First_name` varchar(50) DEFAULT NULL,
  `Last_name` varchar(50) DEFAULT NULL,
  `Gender` varchar(4) DEFAULT NULL,
  `Phone_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_status`
--

CREATE TABLE `booking_status` (
  `Booking_status_Id` varchar(5) NOT NULL,
  `Booking_status_name` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('user1@gmail.com', '$2y$10$HJLHqJuW3v6WIenahA2Hs.gXJ1Gn3Lnuw840VMLMGi.8Q8Swiumn2', 'นาง', 'สมดี', 'สองใจ', 'หญิง', '0981111111'),
('user2@gmail.com', '$2y$10$NXNVzryvM6Pf18f6RL.qIuDrTPY0maPGoZR1wG8eJTAKbVNlFcMtS', 'นาย', 'สมดี', 'สส', 'ชาย', '0982321122');

-- --------------------------------------------------------

--
-- Table structure for table `officer`
--

CREATE TABLE `officer` (
  `Email_Officer` varchar(30) NOT NULL,
  `Password` varchar(15) DEFAULT NULL,
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
  `Region_Id` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Email_member` varchar(30) DEFAULT NULL,
  `Receipt_Id` int(10) DEFAULT NULL,
  `Booking_status_Id` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Room_details` varchar(50) DEFAULT NULL,
  `Email_Officer` varchar(30) DEFAULT NULL,
  `Room_type_Id` varchar(2) DEFAULT NULL,
  `Province_Id` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_type`
--

CREATE TABLE `room_type` (
  `Room_type_Id` varchar(2) NOT NULL,
  `Room_type_name` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `Receipt_Id` (`Receipt_Id`),
  ADD KEY `Booking_status_Id` (`Booking_status_Id`);

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
  ADD CONSTRAINT `reservation_ibfk_3` FOREIGN KEY (`Receipt_Id`) REFERENCES `receipt` (`Receipt_Id`),
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
