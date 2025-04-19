-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 08:52 AM
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
-- Database: `social`
--

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `comment_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_moderated` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`comment_id`, `post_id`, `user_id`, `content`, `created_at`, `is_moderated`) VALUES
(1, 1, 2, 'This is a sample comment by User Two on User One\'s post.', '2025-04-04 07:02:14', 0),
(2, 2, 1, 'This is a sample comment by User One on User Two\'s post.', '2025-04-04 07:02:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `friend`
--

CREATE TABLE `friend` (
  `friendship_id` bigint(20) NOT NULL,
  `user_id1` bigint(20) NOT NULL,
  `user_id2` bigint(20) NOT NULL,
  `status` enum('pending','accepted','blocked') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friend`
--

INSERT INTO `friend` (`friendship_id`, `user_id1`, `user_id2`, `status`, `created_at`) VALUES
(1, 1, 2, 'accepted', '2025-04-04 07:02:21'),
(2, 1, 3, 'pending', '2025-04-17 08:05:29');

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `group_id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `creator_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visibility` enum('public','private') DEFAULT 'public'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`group_id`, `name`, `description`, `creator_id`, `created_at`, `visibility`) VALUES
(1, 'Sample Group', 'This is a sample group.', 1, '2025-04-04 07:02:23', 'public'),
(2, 'olivier', 'test group', 1, '2025-04-17 08:08:08', 'public');

-- --------------------------------------------------------

--
-- Table structure for table `groupmembership`
--

CREATE TABLE `groupmembership` (
  `membership_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groupmembership`
--

INSERT INTO `groupmembership` (`membership_id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 1, 'admin', '2025-04-04 07:02:23'),
(2, 1, 2, 'member', '2025-04-04 07:02:23'),
(3, 2, 1, 'admin', '2025-04-17 08:08:08');

-- --------------------------------------------------------

--
-- Table structure for table `like`
--

CREATE TABLE `like` (
  `like_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `like`
--

INSERT INTO `like` (`like_id`, `user_id`, `post_id`, `comment_id`, `created_at`) VALUES
(1, 1, 2, NULL, '2025-04-04 07:02:15'),
(2, 2, 1, NULL, '2025-04-04 07:02:15'),
(3, 1, NULL, 1, '2025-04-04 07:02:15'),
(4, 2, NULL, 2, '2025-04-04 07:02:15'),
(7, 1, 1, NULL, '2025-04-17 08:02:26');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` bigint(20) NOT NULL,
  `sender_id` bigint(20) NOT NULL,
  `receiver_id` bigint(20) NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`message_id`, `sender_id`, `receiver_id`, `content`, `sent_at`, `is_read`) VALUES
(1, 1, 2, 'Hello User Two! This is a sample message from User One.', '2025-04-04 07:02:22', 0),
(2, 2, 1, 'Hi User One! Thanks for your message.', '2025-04-04 07:02:22', 1),
(3, 1, 2, 'hi', '2025-04-17 08:13:47', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `content` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visibility` enum('public','friends','private') DEFAULT 'public',
  `is_moderated` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post`
--

INSERT INTO `post` (`post_id`, `user_id`, `content`, `media_url`, `created_at`, `visibility`, `is_moderated`) VALUES
(1, 1, 'This is a sample post by User One.', NULL, '2025-04-04 07:02:14', 'public', 0),
(2, 2, 'This is another sample post by User Two.', NULL, '2025-04-04 07:02:14', 'friends', 0),
(3, 1, 'morning', NULL, '2025-04-17 09:20:02', 'public', 0);

-- --------------------------------------------------------

--
-- Table structure for table `privacysetting`
--

CREATE TABLE `privacysetting` (
  `setting_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `profile_visibility` enum('public','friends','private') DEFAULT 'public',
  `post_default_visibility` enum('public','friends','private') DEFAULT 'public',
  `message_privacy` enum('anyone','friends','none') DEFAULT 'anyone'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `privacysetting`
--

INSERT INTO `privacysetting` (`setting_id`, `user_id`, `profile_visibility`, `post_default_visibility`, `message_privacy`) VALUES
(1, 1, 'public', 'public', 'anyone'),
(2, 2, 'friends', 'public', 'anyone');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `report_id` bigint(20) NOT NULL,
  `reporter_id` bigint(20) NOT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`report_id`, `reporter_id`, `post_id`, `comment_id`, `reason`, `status`, `created_at`) VALUES
(1, 1, 2, NULL, 'Inappropriate content', 'pending', '2025-04-04 07:02:24'),
(2, 2, NULL, 1, 'Spam', 'reviewed', '2025-04-04 07:02:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `profile_picture`, `bio`, `created_at`, `is_active`) VALUES
(1, 'johndushime88gmailcom', 'johndushime88@gmail.com', '$2y$10$AdnYTH/Cm7pPrqIrGZ63YuBl0qYYNYRvPF4VNsa.It.E4O/UpeA4m', 'Agaba Olivier', NULL, 'First person to use this app', '2025-03-31 09:04:49', 1),
(2, 'user1', 'user1@example.com', '$2y$10$AdnYTH/Cm7pPrqIrGZ63YuBl0qYYNYRvPF4VNsa.It.E4O/UpeA4m', 'User One', NULL, 'Bio of User One', '2025-04-04 07:02:13', 1),
(3, 'user2', 'user2@example.com', '$2y$10$AdnYTH/Cm7pPrqIrGZ63YuBl0qYYNYRvPF4VNsa.It.E4O/UpeA4m', 'User Two', NULL, 'Bio of User Two', '2025-04-04 07:02:13', 1),
(4, 'johndushime8908gmailcom', 'johndushime8908@gmail.com', '$2y$10$EaCLBsZOWaECcc06awUuD.R21V7sKvf.Lo9.cIdzcjYW9TogF8vFC', 'Agaba Olivier', NULL, NULL, '2025-04-16 03:30:48', 1),
(5, 'johndushime878908gmailcom', 'johndushime878908@gmail.com', '$2y$10$MiAlInyxQ.woYI77szG3dumv4MlyXWYNWSrSdvP.vR83K3geA4OPy', 'Agaba Olivier', NULL, NULL, '2025-04-16 03:31:28', 1),
(6, 'johndushime823456788gmailcom', 'johndushime823456788@gmail.com', '$2y$10$WL7IXJr/uTXv7Y/ZTpSdR.2CeVZBof.LPYnG7T83Qb7FhOdgC5Bvq', 'wamala', NULL, NULL, '2025-04-17 06:19:29', 1),
(7, 'johndushim55gmailcom', 'johndushim55@gmail.com', '$2y$10$/42DGPZrBA1nJ78aEEcv3uoPWcNbMbp6DPq0uxmbtTIBlb86HjjZe', 'eden wamala', NULL, NULL, '2025-04-17 06:24:50', 1),
(8, 'johndushime8678908gmailcom', 'johndushime8678908@gmail.com', '$2y$10$eLJPx6gVzWTqkbu9XOWtu.Y25nBLNf6.97Nrz9ah7ti5DCUw1nvby', 'davi', NULL, NULL, '2025-04-17 06:51:56', 1),
(9, 'moseseegmailcom', 'mosesee@gmail.com', '$2y$10$UXOyEuu9PnPdRl10xLO1oOiyanMlMboLP05YE9Q81X8m7IZmhy/gS', 'moses', NULL, NULL, '2025-04-17 08:59:28', 1),
(10, 'moseseeghjgmailcom', 'moseseeghj@gmail.com', '$2y$10$E3BaqaY/pxN2YD89AMyedumlfHB1W52tv4bQLCmNrPLqXee6KZlY2', 'moses moses', NULL, NULL, '2025-04-17 09:00:40', 1),
(11, 'moseseeagabagmailcom', 'moseseeagaba@gmail.com', '$2y$10$BHuAoV4IbhbVOYxdl31Ff.RVg7velzGOOC2IAcutkbjHyAvkZF7ni', 'moses agaba', NULL, NULL, '2025-04-17 09:03:46', 1),
(12, 'deanmangan09gmailcom', 'deanmangan09@gmail.com', '$2y$10$z62Fsr8.ukj4/DmCBGFGn.O4b16OagsKe//aQSdnhj1NoUaFmNM4y', 'dean mangan', NULL, NULL, '2025-04-17 09:22:17', 1),
(13, 'sdfghjkledfghjklsdfghjk', 'sdfghjkl@edfghjkl.sdfghjk', '$2y$10$U8ZlVbQ19eJSvPeM8KvG7unm7VW5GviXMLSJ6p3FpeGO1R4ffhVuO', 'Tumwebaze Travor', NULL, NULL, '2025-04-17 09:36:46', 1),
(14, 'nakitende56gmailcom', 'nakitende56@gmail.com', '$2y$10$NcdLmvc8qwNOua9nSdOr5uYtR4UPNeizWVkkvxZ9V25IsP4pthDUu', 'christine nakitende', NULL, NULL, '2025-04-17 09:44:42', 1),
(15, 'travor56789gmailcom', 'travor56789@gmail.com', '$2y$10$SsasJ8oDwBlJ8rIWmEhfLepNvbDn6VkDxjLZ2uIu/yOSha4YrfrIG', 'Tumwebaze Travor', NULL, NULL, '2025-04-17 12:49:15', 1),
(16, 'johndushime8o9i8gmailcom', 'johndushime8o9i8@gmail.com', '$2y$10$M0E.pNc1dK7hc.XNMoUg/eEZ2lEbRKkKe6Mvu6CRbQ3C5seQh8C2i', 'Agaba Olivier', NULL, NULL, '2025-04-18 05:46:53', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `friend`
--
ALTER TABLE `friend`
  ADD PRIMARY KEY (`friendship_id`),
  ADD KEY `user_id2` (`user_id2`),
  ADD KEY `user_id1` (`user_id1`,`user_id2`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`group_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `groupmembership`
--
ALTER TABLE `groupmembership`
  ADD PRIMARY KEY (`membership_id`),
  ADD UNIQUE KEY `group_id` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `like`
--
ALTER TABLE `like`
  ADD PRIMARY KEY (`like_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `privacysetting`
--
ALTER TABLE `privacysetting`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `comment_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `friend`
--
ALTER TABLE `friend`
  MODIFY `friendship_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `group_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groupmembership`
--
ALTER TABLE `groupmembership`
  MODIFY `membership_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `like`
--
ALTER TABLE `like`
  MODIFY `like_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `post_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `privacysetting`
--
ALTER TABLE `privacysetting`
  MODIFY `setting_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `report_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `friend`
--
ALTER TABLE `friend`
  ADD CONSTRAINT `friend_ibfk_1` FOREIGN KEY (`user_id1`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_ibfk_2` FOREIGN KEY (`user_id2`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `group`
--
ALTER TABLE `group`
  ADD CONSTRAINT `group_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `groupmembership`
--
ALTER TABLE `groupmembership`
  ADD CONSTRAINT `groupmembership_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `group` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groupmembership_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `like`
--
ALTER TABLE `like`
  ADD CONSTRAINT `like_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `like_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `like_ibfk_3` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `privacysetting`
--
ALTER TABLE `privacysetting`
  ADD CONSTRAINT `privacysetting_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_ibfk_3` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`comment_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
