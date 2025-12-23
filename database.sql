CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('admin', 'employer', 'jobseeker', 'training_center') DEFAULT NULL,
  `status` ENUM('active', 'pending') DEFAULT 'pending',
  `designation` VARCHAR(100) DEFAULT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `jobs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employer_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `salary` VARCHAR(100) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `category` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending', 'approved') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_requested` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `employer_id` (`employer_id`),
  CONSTRAINT `fk_jobs_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) 


CREATE TABLE `applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `resume` VARCHAR(255) DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `course_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `name` VARCHAR(150),
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `address` VARCHAR(255),
  `resume` VARCHAR(255),
  `photo` VARCHAR(255),
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`course_id`) REFERENCES courses(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
);
CREATE TABLE `courses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `training_center_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `structure` TEXT DEFAULT NULL,
  `cost` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending', 'approved') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `training_center_id` (`training_center_id`),
  CONSTRAINT `fk_courses_training_center` FOREIGN KEY (`training_center_id`) REFERENCES `users` (`id`) ON DELETE CASCADE)

-- Add description column (TEXT type for longer content)
ALTER TABLE `courses` 
ADD COLUMN `description` TEXT DEFAULT NULL AFTER `structure`;

-- Add duration column (VARCHAR for flexible time formats)
ALTER TABLE `courses` 
ADD COLUMN `duration` VARCHAR(100) DEFAULT NULL AFTER `cost`;

-- Add prerequisites column (TEXT type for potentially long lists)
ALTER TABLE `courses` 
ADD COLUMN `prerequisites` TEXT DEFAULT NULL AFTER `duration`;