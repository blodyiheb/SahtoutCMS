
CREATE TABLE IF NOT EXISTS `vote_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `site_id` int NOT NULL,
  `user_id` int unsigned NOT NULL,
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vote_timestamp` int NOT NULL,
  `reward_status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_site_id` (`site_id`),
  KEY `fk_vote_log_user_currencies` (`user_id`),
  CONSTRAINT `fk_vote_log_sites` FOREIGN KEY (`site_id`) REFERENCES `vote_sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_log_user_currencies` FOREIGN KEY (`user_id`) REFERENCES `user_currencies` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;


CREATE TABLE IF NOT EXISTS `vote_log_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `site_id` int NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `vote_timestamp` int NOT NULL,
  `reward_status` tinyint(1) NOT NULL DEFAULT '0',
  `reward_points` int NOT NULL DEFAULT '0',
  `claimed_timestamp` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vote_timestamp` (`vote_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE IF NOT EXISTS `vote_sites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `callback_file_name` varchar(50) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `siteid` varchar(255) NOT NULL,
  `url_format` varchar(255) DEFAULT NULL,
  `button_image_url` varchar(255) DEFAULT NULL,
  `cooldown_hours` int NOT NULL DEFAULT '12',
  `reward_points` int NOT NULL DEFAULT '1',
  `uses_callback` tinyint(1) NOT NULL DEFAULT '0',
  `callback_secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_site_name` (`site_name`),
  UNIQUE KEY `unique_external_site_id` (`callback_file_name`),
  UNIQUE KEY `uk_external_site_id` (`callback_file_name`)
) ENGINE=InnoDB AUTO_INCREMENT=105179 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
