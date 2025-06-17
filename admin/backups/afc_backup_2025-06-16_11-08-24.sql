-- AFC Website Backup
-- Created: 2025-06-16 11:08:24
-- Database: afc_website

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
('1', '1', 'login', 'user', '1', '{\"message\": \"Admin user logged in\"}', '127.0.0.1', NULL, '2025-06-16 10:22:36'),
('2', '1', 'create_news', 'news', '1', '{\"message\": \"Created news: Welkom bij AFC\"}', '127.0.0.1', NULL, '2025-06-16 10:22:36'),
('3', '1', 'create_team', 'team', '1', '{\"message\": \"Created team: AFC 1\"}', '127.0.0.1', NULL, '2025-06-16 10:22:36'),
('4', NULL, 'login_failed', 'user', NULL, '{\"message\":\"Failed login attempt for username: test\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:23:42'),
('5', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:24:52'),
('6', '1', 'logout', 'user', NULL, '{\"message\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:28:42'),
('7', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:28:52'),
('8', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:49:55'),
('9', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:50:50'),
('10', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:53:52'),
('11', '1', 'logout', 'user', NULL, '{\"message\":\"User logged out\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:59:16'),
('12', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 10:59:20'),
('13', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 11:04:34'),
('14', NULL, 'login_failed', 'user', NULL, '{\"message\":\"Failed login attempt for username: Halil\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 11:05:24'),
('15', NULL, 'login_failed', 'user', NULL, '{\"message\":\"Failed login attempt for username: Halil\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 11:05:25'),
('16', '1', 'login', 'user', NULL, '{\"message\":\"User logged in successfully\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 11:05:53');

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category` enum('training','match','tournament','social','other') NOT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events` (`id`, `title`, `slug`, `description`, `event_date`, `location`, `category`, `status`, `created_at`, `updated_at`) VALUES
('1', 'Seizoensopening 2024', 'seizoensopening-2024', 'De officiële opening van het nieuwe seizoen.', '2025-07-16 10:22:36', 'Sportpark Sloten', 'social', 'upcoming', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('2', 'Jeugdtoernooi', 'jeugdtoernooi-2024', 'Jaarlijks jeugdtoernooi voor alle jeugdteams.', '2025-07-31 10:22:36', 'Sportpark Sloten', 'tournament', 'upcoming', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('3', 'Clubavond', 'clubavond-2024', 'Gezellige avond voor alle leden.', '2025-08-15 10:22:36', 'Clubhuis', 'social', 'upcoming', '2025-06-16 10:22:36', '2025-06-16 10:22:36');

CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `match_date` datetime NOT NULL,
  `competition` varchar(100) DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `home_score` int(11) DEFAULT NULL,
  `away_score` int(11) DEFAULT NULL,
  `status` enum('scheduled','live','completed','cancelled','postponed') NOT NULL DEFAULT 'scheduled',
  `referee` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `home_team_id` (`home_team_id`),
  KEY `away_team_id` (`away_team_id`),
  KEY `idx_match_date` (`match_date`),
  KEY `idx_status` (`status`),
  KEY `idx_competition` (`competition`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `matches` (`id`, `home_team_id`, `away_team_id`, `match_date`, `competition`, `venue`, `home_score`, `away_score`, `status`, `referee`, `notes`, `created_at`, `updated_at`) VALUES
('1', '1', '2', '2025-06-23 10:22:36', 'Competitie', 'Sportpark Sloten', NULL, NULL, 'scheduled', NULL, NULL, '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('2', '3', '4', '2025-06-30 10:22:36', 'Jeugdcompetitie', 'Sportpark Sloten', NULL, NULL, 'scheduled', NULL, NULL, '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('3', '5', '6', '2025-07-07 10:22:36', 'Jeugdcompetitie', 'Sportpark Sloten', NULL, NULL, 'scheduled', NULL, NULL, '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('4', '7', '8', '2025-07-14 10:22:36', 'Damescompetitie', 'Sportpark Sloten', NULL, NULL, 'scheduled', NULL, NULL, '2025-06-16 10:22:36', '2025-06-16 10:22:36');

CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `category` enum('team','club','events','youth') NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_published_at` (`published_at`),
  FULLTEXT KEY `idx_content` (`title`,`content`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `news` (`id`, `title`, `slug`, `content`, `excerpt`, `category`, `image_url`, `author_id`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
('1', 'Welkom bij AFC', 'welkom-bij-afc', 'Welkom op de nieuwe website van Amsterdamsche FC!', NULL, 'club', NULL, '1', 'published', '2025-06-16 10:22:36', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('2', 'Eerste training seizoen 2024', 'eerste-training-seizoen-2024', 'De eerste training van het nieuwe seizoen staat gepland.', NULL, 'team', NULL, '1', 'published', '2025-06-16 10:22:36', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('3', 'Jeugdkamp 2024', 'jeugdkamp-2024', 'Inschrijvingen voor het jaarlijkse jeugdkamp zijn geopend.', NULL, 'events', NULL, '1', 'published', '2025-06-16 10:22:36', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('4', 'Dames 1 wint belangrijke wedstrijd', 'dames-1-wint', 'AFC Dames 1 heeft een belangrijke overwinning geboekt in de competitie.', NULL, 'team', NULL, '1', 'published', '2025-06-16 10:22:36', '2025-06-16 10:22:36', '2025-06-16 10:22:36');

CREATE TABLE `players` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `status` enum('active','inactive','injured') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `players_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json','array') NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_setting_key` (`setting_key`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `is_public`, `created_at`, `updated_at`) VALUES
('1', 'website_name', 'Amsterdamsche FC', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('2', 'club_name', 'Amsterdamsche FC', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('3', 'contact_email', 'info@afc.nl', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('4', 'phone_number', '020-1234567', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('5', 'address', 'Sportpark Sloten, Amsterdam', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('6', 'facebook_url', 'https://facebook.com/afc', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('7', 'twitter_url', 'https://twitter.com/afc', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('8', 'instagram_url', 'https://instagram.com/afc', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('9', 'youtube_url', 'https://youtube.com/afc', 'string', '1', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('10', 'maintenance_mode', 'false', 'boolean', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('11', 'registration_enabled', 'true', 'boolean', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('12', 'default_user_role', 'user', 'string', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('13', 'news_items_per_page', '10', 'integer', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('14', 'matches_items_per_page', '10', 'integer', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('15', 'teams_items_per_page', '12', 'integer', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('16', 'events_items_per_page', '12', 'integer', '0', '2025-06-16 10:22:36', '2025-06-16 10:22:36');

CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `category` enum('senior','junior','women') NOT NULL,
  `division` varchar(50) DEFAULT NULL,
  `coach` varchar(100) DEFAULT NULL,
  `assistant_coach` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `training_times` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teams` (`id`, `name`, `slug`, `category`, `division`, `coach`, `assistant_coach`, `description`, `logo_url`, `training_times`, `status`, `created_at`, `updated_at`) VALUES
('1', 'AFC 1', 'afc-1', 'senior', 'Hoofdklasse', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('2', 'AFC 2', 'afc-2', 'senior', 'Eerste Klasse', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('3', 'AFC 3', 'afc-3', 'senior', 'Tweede Klasse', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('4', 'AFC A1', 'afc-a1', 'junior', 'A1', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('5', 'AFC B1', 'afc-b1', 'junior', 'B1', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('6', 'AFC C1', 'afc-c1', 'junior', 'C1', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('7', 'AFC Dames 1', 'afc-dames-1', 'women', 'Hoofdklasse', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36'),
('8', 'AFC Dames 2', 'afc-dames-2', 'women', 'Eerste Klasse', NULL, NULL, NULL, NULL, NULL, 'active', '2025-06-16 10:22:36', '2025-06-16 10:22:36');

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role` enum('admin','editor','user') NOT NULL DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `role`, `phone`, `last_login`, `login_attempts`, `locked_until`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
('1', 'admin', '$2y$10$ZYQWvO3OuJmeoPoj.Bg2lemn//key.V.nE7H3Ebt5FcRtkQkYbjFe', 'admin@afc.nl', 'Admin', 'User', 'admin', NULL, '2025-06-16 11:05:53', '0', NULL, NULL, NULL, '2025-06-16 10:22:36', '2025-06-16 11:05:53'),
('2', 'Halil', '$2y$10$SJaaJKJYSQ7BPZhDfJUjluTDnAikXIWTkQwUytvpzihaLp4msOsUu', 'halil@gmail.com', NULL, NULL, 'user', NULL, NULL, '0', NULL, NULL, NULL, '2025-06-16 11:05:13', '2025-06-16 11:05:13');

