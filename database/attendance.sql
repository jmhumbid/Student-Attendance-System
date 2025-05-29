-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 09:14 AM
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
-- Database: `attendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `timestamp`, `class_id`) VALUES
(11, '6', '2025-05-18 14:50:18', 2),
(12, '6', '2025-05-18 14:57:27', 2),
(13, '6', '2025-05-18 14:57:31', 2),
(14, '6', '2025-05-18 14:57:37', 2),
(15, '6', '2025-05-18 14:57:50', 2),
(16, '5', '2025-05-18 14:58:22', 2),
(17, '7', '2025-05-18 15:13:43', 3);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `instructor_id`, `class_name`, `subject`, `start_time`, `end_time`) VALUES
(1, 3, 'Section C', 'Integrative Programming 2', '10:00:00', '12:00:00'),
(2, 3, 'Section C', 'Integrative Programming 2 (LAB)', '13:00:00', '16:00:00'),
(3, 8, '1A', 'Purposive Communication', '13:00:00', '16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_students`
--

INSERT INTO `class_students` (`id`, `class_id`, `student_id`) VALUES
(3, 1, 3),
(1, 1, 4),
(7, 1, 5),
(10, 2, 3),
(8, 2, 4),
(9, 2, 5),
(12, 2, 6),
(13, 3, 7);

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `instructor_id` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` ENUM('unverified','verified') DEFAULT 'unverified',
  `verification_token` varchar(255) DEFAULT NULL,
  `suspended` ENUM('no','yes') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `full_name`, `instructor_id`, `department`, `email`, `username`, `password`) VALUES
(3, 'Marc Fritz Aseo', '2022-12345', 'Computer Studies', 'aseo@evsu.edu.ph', 'aseo123', '$2y$10$rN36tCw5UkC6WvOMwyzGreWfvYaZnxUPZ5Y5.l9NEJBkAvKPf1ECm'),
(4, 'Revie Miaga Vasquez', '2022-98765', 'Computer Studies', 'revie@evsu.edu.ph', 'revie11', '$2y$10$FwFdQnJR2EKHAT//E086FuBIjCPhRxZw2xvRnR.qhX80PSvuoGrTu'),
(5, 'Clyde Morallos', '2022-55555', 'Computer Studies', 'clydemorallos@evsu.edu.ph', 'clyde123', '$2y$10$lJm4YJ4NgpPvrYQW/Nt/W.ciUZKHTeYhWueBsNkKf5WNKHDDcC7g.'),
(7, 'Joseph Jaymel Morpos', '12345', 'Computer Studies', 'jjm@evsu.edu.ph', 'jjmorpos123', '$2y$10$wsjsc9qwQTyTHxN9.YAjmOqjGeL9fFrTrE9A3wy9xftWAoGPR6NDe'),
(8, 'Cherry Bertulfo', '015963', 'Computer Studies', 'cherryb@evsu.edu.ph', 'cherry123', 'cherry1');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `year` varchar(20) NOT NULL,
  `course` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `gender`, `year`, `course`) VALUES
(3, '2022-31138', 'Niño Boholst', 'Male', '3rd Year', 'Bachelor of Science in Information Technology'),
(4, '2022-31183', 'Ariel C. Corton', 'Male', '3rd Year', 'Bachelor of Science in Information Technology'),
(5, '2022-25896', 'Loren Capuyan', 'Male', '3rd Year', 'Bachelor of Science in Information Technology'),
(6, '2022-15963', 'Ruelene Labiste', 'Female', '3rd Year', 'Bachelor of Science in Information Technology'),
(7, '2022-32101', 'James Humphry Manilag', 'Male', '3rd Year', 'Bachelor of Science in Information Technology');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attendance_class` (`class_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_student_unique` (`class_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_students`
--
ALTER TABLE `class_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `class_students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
