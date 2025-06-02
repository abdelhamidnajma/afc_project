DROP DATABASE IF EXISTS `afc_website`;

CREATE DATABASE `afc_website` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `afc_website`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `first_name` VARCHAR(50),
    `last_name` VARCHAR(50),
    `role` ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    `phone` VARCHAR(20),
    `last_login` DATETIME,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME,
    `reset_token` VARCHAR(100),
    `reset_token_expires` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `category` ENUM('senior', 'junior', 'women') NOT NULL,
    `division` VARCHAR(50),
    `coach` VARCHAR(100),
    `assistant_coach` VARCHAR(100),
    `description` TEXT,
    `logo_url` VARCHAR(255),
    `training_times` TEXT,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `players` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `number` INT,
    `position` VARCHAR(50),
    `date_of_birth` DATE,
    `joined_date` DATE,
    `status` ENUM('active', 'inactive', 'injured') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    INDEX `idx_team` (`team_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `matches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `home_team_id` INT NOT NULL,
    `away_team_id` INT NOT NULL,
    `match_date` DATETIME NOT NULL,
    `competition` VARCHAR(100),
    `venue` VARCHAR(100),
    `home_score` INT,
    `away_score` INT,
    `status` ENUM('scheduled', 'live', 'completed', 'cancelled', 'postponed') NOT NULL DEFAULT 'scheduled',
    `referee` VARCHAR(100),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`home_team_id`) REFERENCES `teams`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`away_team_id`) REFERENCES `teams`(`id`) ON DELETE RESTRICT,
    INDEX `idx_match_date` (`match_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_competition` (`competition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `content` TEXT NOT NULL,
    `excerpt` TEXT,
    `category` ENUM('team', 'club', 'events', 'youth') NOT NULL,
    `image_url` VARCHAR(255),
    `author_id` INT,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `published_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`),
    INDEX `idx_published_at` (`published_at`),
    FULLTEXT INDEX `idx_content` (`title`, `content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) UNIQUE NOT NULL,
    `description` TEXT,
    `event_date` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `category` ENUM('training', 'match', 'tournament', 'social', 'other') NOT NULL,
    `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_category` (`category`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json', 'array') NOT NULL DEFAULT 'string',
    `is_public` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`),
    INDEX `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT,
    `details` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`username`, `password`, `email`, `first_name`, `last_name`, `role`) 
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: Admin@123
    'admin@afc.nl',
    'Admin',
    'User',
    'admin'
);

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `is_public`) VALUES
('website_name', 'Amsterdamsche FC', 'string', true),
('club_name', 'Amsterdamsche FC', 'string', true),
('contact_email', 'info@afc.nl', 'string', true),
('phone_number', '020-1234567', 'string', true),
('address', 'Sportpark Sloten, Amsterdam', 'string', true),
('facebook_url', 'https://facebook.com/afc', 'string', true),
('twitter_url', 'https://twitter.com/afc', 'string', true),
('instagram_url', 'https://instagram.com/afc', 'string', true),
('youtube_url', 'https://youtube.com/afc', 'string', true),
('maintenance_mode', 'false', 'boolean', false),
('registration_enabled', 'true', 'boolean', false),
('default_user_role', 'user', 'string', false),
('news_items_per_page', '10', 'integer', false),
('matches_items_per_page', '10', 'integer', false),
('teams_items_per_page', '12', 'integer', false),
('events_items_per_page', '12', 'integer', false);

INSERT INTO `teams` (`name`, `slug`, `category`, `division`, `status`) VALUES
('AFC 1', 'afc-1', 'senior', 'Hoofdklasse', 'active'),
('AFC 2', 'afc-2', 'senior', 'Eerste Klasse', 'active'),
('AFC 3', 'afc-3', 'senior', 'Tweede Klasse', 'active'),
('AFC A1', 'afc-a1', 'junior', 'A1', 'active'),
('AFC B1', 'afc-b1', 'junior', 'B1', 'active'),
('AFC C1', 'afc-c1', 'junior', 'C1', 'active'),
('AFC Dames 1', 'afc-dames-1', 'women', 'Hoofdklasse', 'active'),
('AFC Dames 2', 'afc-dames-2', 'women', 'Eerste Klasse', 'active');

INSERT INTO `matches` (`home_team_id`, `away_team_id`, `match_date`, `competition`, `venue`, `status`) VALUES
(1, 2, DATE_ADD(NOW(), INTERVAL 7 DAY), 'Competitie', 'Sportpark Sloten', 'scheduled'),
(3, 4, DATE_ADD(NOW(), INTERVAL 14 DAY), 'Jeugdcompetitie', 'Sportpark Sloten', 'scheduled'),
(5, 6, DATE_ADD(NOW(), INTERVAL 21 DAY), 'Jeugdcompetitie', 'Sportpark Sloten', 'scheduled'),
(7, 8, DATE_ADD(NOW(), INTERVAL 28 DAY), 'Damescompetitie', 'Sportpark Sloten', 'scheduled');

INSERT INTO `news` (`title`, `slug`, `content`, `category`, `author_id`, `status`, `published_at`) VALUES
('Welkom bij AFC', 'welkom-bij-afc', 'Welkom op de nieuwe website van Amsterdamsche FC!', 'club', 1, 'published', NOW()),
('Eerste training seizoen 2024', 'eerste-training-seizoen-2024', 'De eerste training van het nieuwe seizoen staat gepland.', 'team', 1, 'published', NOW()),
('Jeugdkamp 2024', 'jeugdkamp-2024', 'Inschrijvingen voor het jaarlijkse jeugdkamp zijn geopend.', 'events', 1, 'published', NOW()),
('Dames 1 wint belangrijke wedstrijd', 'dames-1-wint', 'AFC Dames 1 heeft een belangrijke overwinning geboekt in de competitie.', 'team', 1, 'published', NOW());

INSERT INTO `events` (`title`, `slug`, `description`, `event_date`, `location`, `category`, `status`) VALUES
('Seizoensopening 2024', 'seizoensopening-2024', 'De officiële opening van het nieuwe seizoen.', DATE_ADD(NOW(), INTERVAL 30 DAY), 'Sportpark Sloten', 'social', 'upcoming'),
('Jeugdtoernooi', 'jeugdtoernooi-2024', 'Jaarlijks jeugdtoernooi voor alle jeugdteams.', DATE_ADD(NOW(), INTERVAL 45 DAY), 'Sportpark Sloten', 'tournament', 'upcoming'),
('Clubavond', 'clubavond-2024', 'Gezellige avond voor alle leden.', DATE_ADD(NOW(), INTERVAL 60 DAY), 'Clubhuis', 'social', 'upcoming');