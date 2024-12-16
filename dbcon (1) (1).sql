-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2024 at 08:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbcon`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL,
  `address_text` text DEFAULT NULL,
  `amphure_id` int(11) NOT NULL,
  `thambon_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `address_text`, `amphure_id`, `thambon_id`) VALUES
(10, 'ฟฟฟ', 7111, 711104),
(11, 'aa', 7111, 711103),
(13, 'aa', 7107, 710705),
(14, 'aaa', 7111, 711102),
(15, 'aa', 7110, 711004),
(19, 'ddd', 7101, 710101),
(29, 'aaaa', 7110, 711002),
(30, 'asd', 7101, 710116),
(31, '56asd46as5da', 7108, 710802),
(33, 'aaa', 7102, 710203),
(35, 'aa', 7111, 711103),
(36, 'ฟฟฟ', 7111, 711101),
(38, 'ddd', 7104, 710404),
(39, 'เเเเ', 7101, 710104),
(41, 'aaa', 7111, 711102),
(42, 'ddd', 7104, 710404),
(43, 'ฟฟฟ', 7101, 710104),
(44, 'ฟฟฟ', 7101, 710104),
(45, 'aa', 7111, 711103),
(46, 'aa', 7107, 710705),
(47, 'aaa', 7111, 711102),
(48, 'ddd', 7104, 710404),
(49, 'เมือง', 7101, 710101),
(51, 'ฟฟฟ', 7101, 710102),
(52, 'ฟหก', 7110, 711001),
(54, 'aaaa', 7112, 711201),
(56, 'ฟฟฟฟ', 7110, 711002),
(59, 'Asd', 7104, 710406);

-- --------------------------------------------------------

--
-- Table structure for table `amphure`
--

CREATE TABLE `amphure` (
  `amphure_id` int(11) NOT NULL,
  `amphure_name` varchar(255) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `amphure`
--

INSERT INTO `amphure` (`amphure_id`, `amphure_name`, `province_id`) VALUES
(7101, 'เมืองกาญจนบุรี', 56),
(7102, 'ไทรโยค', 56),
(7103, 'บ่อพลอย', 56),
(7104, 'ศรีสวัสดิ์', 56),
(7105, 'ท่ามะกา', 56),
(7106, 'ท่าม่วง', 56),
(7107, 'ทองผาภูมิ', 56),
(7108, 'สังขละบุรี', 56),
(7109, 'พนมทวน', 56),
(7110, 'เลาขวัญ', 56),
(7111, 'ด่านมะขามเตี้ย', 56),
(7112, 'หนองปรือ', 56),
(7113, 'ห้วยกระเจา', 56);

-- --------------------------------------------------------

--
-- Table structure for table `bill`
--

CREATE TABLE `bill` (
  `bill_id` int(11) NOT NULL,
  `bill_number` char(20) NOT NULL,
  `bill_type` enum('ประเภท1','ประเภท2','ประเภท3') NOT NULL,
  `bill_create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bill_update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `all_price` float NOT NULL,
  `customer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill`
--

INSERT INTO `bill` (`bill_id`, `bill_number`, `bill_type`, `bill_create_at`, `bill_update_at`, `all_price`, `customer_id`) VALUES
(1, 'BL-20241216080157', 'ประเภท1', '2024-12-16 07:01:57', '2024-12-16 07:01:57', 5050, 3);

-- --------------------------------------------------------

--
-- Table structure for table `bill_group`
--

CREATE TABLE `bill_group` (
  `bill_group_id` int(11) NOT NULL,
  `group_name` char(100) NOT NULL,
  `group_type` enum('1','2') NOT NULL,
  `group_prcice_a` int(11) DEFAULT NULL,
  `group_prcice_b` int(11) DEFAULT NULL,
  `group_price` int(11) DEFAULT NULL,
  `bill_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_group`
--

INSERT INTO `bill_group` (`bill_group_id`, `group_name`, `group_type`, `group_prcice_a`, `group_prcice_b`, `group_price`, `bill_id`) VALUES
(1, 'AAA', '2', NULL, NULL, 50, 1),
(2, 'sadasd', '2', NULL, NULL, 5000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bill_group_info`
--

CREATE TABLE `bill_group_info` (
  `bill_group_info_id` int(11) NOT NULL,
  `group_info_name` char(100) NOT NULL,
  `bill_group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_group_info`
--

INSERT INTO `bill_group_info` (`bill_group_info_id`, `group_info_name`, `bill_group_id`) VALUES
(1, 'asdsad', 1),
(2, 'asdsad', 2);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `customer_name` char(100) NOT NULL,
  `customer_type` enum('เทศบาลเมือง','เทศบาลตำบล','อบจ','อบต','บริษัทเอกชน','โรงพยาบาล','วัด','มูลนิธิ','โรงเรียนประถม','โรงเรียนมัธยม','โรงเรียนเอกชน','วิทยาลัย','มหาวิทยาลัย') NOT NULL,
  `customer_phone` varchar(255) NOT NULL,
  `customer_status` enum('Active','Inactive') NOT NULL,
  `address_id` int(11) NOT NULL,
  `create_at` date NOT NULL,
  `update_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_type`, `customer_phone`, `customer_status`, `address_id`, `create_at`, `update_at`) VALUES
(3, 'การไฟฟ้า', 'เทศบาลเมือง', 'ค.ฝน 034556668', 'Active', 51, '0000-00-00', '2024-12-14'),
(4, 'มูลนิธิกระจกเงา', 'มูลนิธิ', '0123456789', 'Inactive', 45, '0000-00-00', '2024-12-14'),
(6, 'บริษัท สุริยะค้าเหล็ก', 'เทศบาลตำบล', 'aaa', 'Active', 46, '0000-00-00', '2024-12-14'),
(12, 'โรงเรียนกาญจนานุเคราะห์', 'โรงเรียนมัธยม', '0123456789', 'Active', 48, '0000-00-00', '2024-12-14'),
(28, 'โรงเรียนอนุบาลต้นกล้า', 'โรงเรียนประถม', 'ฟฟ 0898081659', 'Active', 59, '2024-12-16', '2024-12-16');

-- --------------------------------------------------------

--
-- Table structure for table `thambon`
--

CREATE TABLE `thambon` (
  `thambon_id` int(11) NOT NULL,
  `thambon_name` varchar(255) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `amphure_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thambon`
--

INSERT INTO `thambon` (`thambon_id`, `thambon_name`, `zip_code`, `amphure_id`) VALUES
(710101, 'บ้านเหนือ', '71000', 7101),
(710102, 'บ้านใต้', '71000', 7101),
(710103, 'ปากแพรก', '71000', 7101),
(710104, 'ท่ามะขาม', '71000', 7101),
(710105, 'แก่งเสี้ยน', '71000', 7101),
(710106, 'หนองบัว', '71190', 7101),
(710107, 'ลาดหญ้า', '71190', 7101),
(710108, 'วังด้ง', '71190', 7101),
(710109, 'ช่องสะเดา', '71190', 7101),
(710110, 'หนองหญ้า', '71000', 7101),
(710111, 'เกาะสำโรง', '71000', 7101),
(710113, 'บ้านเก่า', '71000', 7101),
(710116, 'วังเย็น', '71000', 7101),
(710201, 'ลุ่มสุ่ม', '71150', 7102),
(710202, 'ท่าเสา', '71150', 7102),
(710203, 'สิงห์', '71150', 7102),
(710204, 'ไทรโยค', '71150', 7102),
(710205, 'วังกระแจะ', '71150', 7102),
(710206, 'ศรีมงคล', '71150', 7102),
(710207, 'บ้องตี้', '71150', 7102),
(710301, 'บ่อพลอย', '71160', 7103),
(710302, 'หนองกุ่ม', '71160', 7103),
(710303, 'หนองรี', '71220', 7103),
(710305, 'หลุมรัง', '71160', 7103),
(710308, 'ช่องด่าน', '71160', 7103),
(710309, 'หนองกร่าง', '71220', 7103),
(710401, 'นาสวน', '71250', 7104),
(710402, 'ด่านแม่แฉลบ', '71250', 7104),
(710403, 'หนองเป็ด', '71250', 7104),
(710404, 'ท่ากระดาน', '71250', 7104),
(710405, 'เขาโจด', '71220', 7104),
(710406, 'แม่กระบุง', '71250', 7104),
(710501, 'พงตึก', '71120', 7105),
(710502, 'ยางม่วง', '71120', 7105),
(710503, 'ดอนชะเอม', '71130', 7105),
(710504, 'ท่าไม้', '71120', 7105),
(710505, 'ตะคร้ำเอน', '71130', 7105),
(710506, 'ท่ามะกา', '71120', 7105),
(710507, 'ท่าเรือ', '71130', 7105),
(710508, 'โคกตะบอง', '71120', 7105),
(710509, 'ดอนขมิ้น', '71120', 7105),
(710510, 'อุโลกสี่หมื่น', '71130', 7105),
(710511, 'เขาสามสิบหาบ', '71120', 7105),
(710512, 'พระแท่น', '71130', 7105),
(710513, 'หวายเหนียว', '71120', 7105),
(710514, 'แสนตอ', '71130', 7105),
(710515, 'สนามแย้', '70190', 7105),
(710516, 'ท่าเสา', '71120', 7105),
(710517, 'หนองลาน', '71130', 7105),
(710601, 'ท่าม่วง', '71110', 7106),
(710602, 'วังขนาย', '71110', 7106),
(710603, 'วังศาลา', '71110', 7106),
(710604, 'ท่าล้อ', '71000', 7106),
(710605, 'หนองขาว', '71110', 7106),
(710606, 'ทุ่งทอง', '71110', 7106),
(710607, 'เขาน้อย', '71110', 7106),
(710608, 'ม่วงชุม', '71110', 7106),
(710609, 'บ้านใหม่', '71110', 7106),
(710610, 'พังตรุ', '71110', 7106),
(710611, 'ท่าตะคร้อ', '71130', 7106),
(710612, 'รางสาลี่', '71110', 7106),
(710613, 'หนองตากยา', '71110', 7106),
(710701, 'ท่าขนุน', '71180', 7107),
(710702, 'ปิล๊อก', '71180', 7107),
(710703, 'หินดาด', '71180', 7107),
(710704, 'ลิ่นถิ่น', '71180', 7107),
(710705, 'ชะแล', '71180', 7107),
(710706, 'ห้วยเขย่ง', '71180', 7107),
(710707, 'สหกรณ์นิคม', '71180', 7107),
(710801, 'หนองลู', '71240', 7108),
(710802, 'ปรังเผล', '71240', 7108),
(710803, 'ไล่โว่', '71240', 7108),
(710901, 'พนมทวน', '71140', 7109),
(710902, 'หนองโรง', '71140', 7109),
(710903, 'ทุ่งสมอ', '71140', 7109),
(710904, 'ดอนเจดีย์', '71140', 7109),
(710905, 'พังตรุ', '71140', 7109),
(710906, 'รางหวาย', '71170', 7109),
(710911, 'หนองสาหร่าย', '71140', 7109),
(710912, 'ดอนตาเพชร', '71140', 7109),
(711001, 'เลาขวัญ', '71210', 7110),
(711002, 'หนองโสน', '71210', 7110),
(711003, 'หนองประดู่', '71210', 7110),
(711004, 'หนองปลิง', '71210', 7110),
(711005, 'หนองนกแก้ว', '71210', 7110),
(711006, 'ทุ่งกระบ่ำ', '71210', 7110),
(711007, 'หนองฝ้าย', '71210', 7110),
(711101, 'ด่านมะขามเตี้ย', '71260', 7111),
(711102, 'กลอนโด', '71260', 7111),
(711103, 'จรเข้เผือก', '71260', 7111),
(711104, 'หนองไผ่', '71260', 7111),
(711201, 'หนองปรือ', '71220', 7112),
(711202, 'หนองปลาไหล', '71220', 7112),
(711203, 'สมเด็จเจริญ', '71220', 7112),
(711301, 'ห้วยกระเจา', '71170', 7113),
(711302, 'วังไผ่', '71170', 7113),
(711303, 'ดอนแสลบ', '71170', 7113),
(711304, 'สระลงเรือ', '71170', 7113);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `amphure_id` (`amphure_id`),
  ADD KEY `thambon_id` (`thambon_id`);

--
-- Indexes for table `amphure`
--
ALTER TABLE `amphure`
  ADD PRIMARY KEY (`amphure_id`);

--
-- Indexes for table `bill`
--
ALTER TABLE `bill`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `bill_group`
--
ALTER TABLE `bill_group`
  ADD PRIMARY KEY (`bill_group_id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `bill_group_info`
--
ALTER TABLE `bill_group_info`
  ADD PRIMARY KEY (`bill_group_info_id`),
  ADD KEY `bill_group_id` (`bill_group_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `thambon`
--
ALTER TABLE `thambon`
  ADD PRIMARY KEY (`thambon_id`),
  ADD KEY `amphure_id` (`amphure_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `bill`
--
ALTER TABLE `bill`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bill_group`
--
ALTER TABLE `bill_group`
  MODIFY `bill_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bill_group_info`
--
ALTER TABLE `bill_group_info`
  MODIFY `bill_group_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `address_ibfk_1` FOREIGN KEY (`amphure_id`) REFERENCES `amphure` (`amphure_id`),
  ADD CONSTRAINT `address_ibfk_2` FOREIGN KEY (`thambon_id`) REFERENCES `thambon` (`thambon_id`);

--
-- Constraints for table `bill`
--
ALTER TABLE `bill`
  ADD CONSTRAINT `bill_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `bill_group`
--
ALTER TABLE `bill_group`
  ADD CONSTRAINT `bill_group_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bill` (`bill_id`);

--
-- Constraints for table `bill_group_info`
--
ALTER TABLE `bill_group_info`
  ADD CONSTRAINT `bill_group_info_ibfk_1` FOREIGN KEY (`bill_group_id`) REFERENCES `bill_group` (`bill_group_id`);

--
-- Constraints for table `thambon`
--
ALTER TABLE `thambon`
  ADD CONSTRAINT `thambon_ibfk_1` FOREIGN KEY (`amphure_id`) REFERENCES `amphure` (`amphure_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
