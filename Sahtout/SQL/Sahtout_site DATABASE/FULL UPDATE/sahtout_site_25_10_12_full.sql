-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.0.43 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Listage de la structure de la base pour sahtout_site
CREATE DATABASE IF NOT EXISTS `sahtout_site` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sahtout_site`;

-- Listage de la structure de table sahtout_site. character_teleport_log
CREATE TABLE IF NOT EXISTS `character_teleport_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `character_guid` int unsigned NOT NULL,
  `character_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teleport_timestamp` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `character_guid` (`character_guid`),
  CONSTRAINT `character_teleport_log_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE,
  CONSTRAINT `character_teleport_log_ibfk_2` FOREIGN KEY (`character_guid`) REFERENCES `acore_characters`.`characters` (`guid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.character_teleport_log : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. failed_logins
CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `attempts` int DEFAULT '0',
  `last_attempt` int NOT NULL,
  `block_until` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf16;

-- Listage des données de la table sahtout_site.failed_logins : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sahtout_site.password_resets : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. pending_accounts
CREATE TABLE IF NOT EXISTS `pending_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `email` varchar(100) NOT NULL,
  `salt` varbinary(32) NOT NULL,
  `verifier` varbinary(32) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `activated` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf16;

-- Listage des données de la table sahtout_site.pending_accounts : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. profile_avatars
CREATE TABLE IF NOT EXISTS `profile_avatars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.profile_avatars : ~2 rows (environ)
INSERT INTO `profile_avatars` (`id`, `filename`, `display_name`, `active`, `created_at`) VALUES
	(1, '1-0.png', 'Avatar 1', 1, '2025-07-27 14:35:31'),
	(2, '1-1.png', 'Avatar 2', 1, '2025-07-27 14:35:31'),
	(3, '2-0.png', 'Avatar 3', 1, '2025-07-27 14:35:31'),
	(4, '2-1.png', 'Avatar 4', 1, '2025-07-27 14:35:31'),
	(5, '3-0.png', 'Avatar 5', 1, '2025-07-27 14:35:31'),
	(6, '3-1.png', 'Avatar 6', 1, '2025-07-27 14:35:31'),
	(7, '4-0.png', 'Avatar 7', 1, '2025-07-27 14:39:58'),
	(8, '4-1.png', 'Avatar 8', 1, '2025-07-27 14:43:18'),
	(9, '5-0.png', 'Avatar 9', 1, '2025-07-27 14:44:07'),
	(10, '5-1.png', 'Avatar 10', 1, '2025-07-27 14:44:17'),
	(11, '6-0.png', 'Avatar 11', 1, '2025-07-27 14:45:20'),
	(12, '6-1.png', 'Avatar 12', 1, '2025-07-27 14:45:30'),
	(13, '7-0.png', 'Avatar 13', 1, '2025-07-27 14:45:49'),
	(14, '7-1.png', 'Avatar 14', 1, '2025-07-27 14:47:55'),
	(15, '8-0.png', 'Avatar 15', 1, '2025-07-27 14:48:09'),
	(16, '8-1.png', 'Avatar 16', 1, '2025-07-27 14:48:20'),
	(17, '10-0.png', 'Avatar 17', 1, '2025-07-27 14:48:48'),
	(18, '10-1.png', 'Avatar 18', 1, '2025-07-27 18:53:56'),
	(19, '11-0.png', 'Avatar 19', 1, '2025-07-27 18:54:04'),
	(20, '11-1.png', 'Avatar 20', 1, '2025-07-27 18:54:14'),
	(21, '28-0.png', 'Avatar 21', 1, '2025-07-27 18:54:24'),
	(22, '28-1.png', 'Avatar 22', 1, '2025-07-27 18:54:36'),
	(23, '32-0.png', 'Avatar 23', 1, '2025-07-27 18:54:48'),
	(24, '32-1.png', 'Avatar 24', 1, '2025-07-27 18:54:57'),
	(25, '37-0.png', 'Avatar 25', 1, '2025-07-27 18:55:09'),
	(26, '70-0.png', 'Avatar 26', 1, '2025-07-27 18:55:18'),
	(27, '70-1.png', 'Avatar 27', 1, '2025-07-27 18:55:29');

-- Listage de la structure de table sahtout_site. purchases
CREATE TABLE IF NOT EXISTS `purchases` (
  `purchase_id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `point_cost` int unsigned NOT NULL DEFAULT '0',
  `token_cost` int unsigned NOT NULL DEFAULT '0',
  `purchase_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`purchase_id`),
  KEY `fk_account_id` (`account_id`),
  KEY `fk_item_id` (`item_id`),
  CONSTRAINT `fk_account_id` FOREIGN KEY (`account_id`) REFERENCES `user_currencies` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_item_id` FOREIGN KEY (`item_id`) REFERENCES `shop_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.purchases : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. reset_attempts
CREATE TABLE IF NOT EXISTS `reset_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf16;

-- Listage des données de la table sahtout_site.reset_attempts : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. server_news
CREATE TABLE IF NOT EXISTS `server_news` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `slug` varchar(120) DEFAULT NULL,
  `content` text NOT NULL,
  `posted_by` varchar(50) NOT NULL COMMENT 'GM/Admin name',
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Optional image for news',
  `is_important` tinyint(1) DEFAULT '0' COMMENT '1 for important/sticky news',
  `category` enum('update','event','maintenance','other') DEFAULT 'update',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sahtout_site.server_news : ~18 rows (environ)
INSERT INTO `server_news` (`id`, `title`, `slug`, `content`, `posted_by`, `post_date`, `image_url`, `is_important`, `category`) VALUES
	(1, 'Server Patch 3.3.5a Applied', 'server-patch-3.3.5a-applied', 'We have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddl', 'Admin', '2025-07-24 17:08:35', 'img/newsimg/news1.jpg', 1, 'update'),
	(2, 'Weekly Arena TournamentEr', 'weekly-arena-tournament', 'Sign up now for this week\'s arena tournament! Prize: 1000 gold to winning team.', 'GameMaster', '2025-07-24 17:08:35', 'img/newsimg/news2.jpg', 1, 'event'),
	(3, 'Scheduled Maintenance', 'scheduled-maintenance', 'Server will be down for maintenance on Friday 2am-4am server time.', 'Admin', '2025-07-24 17:08:35', 'img/newsimg/news3.jpg', 1, 'maintenance'),
	(4, 'New Custom Raid Released', 'new-custom-raid-released', 'Try our new custom raid "The Fallen Citadel" - tuned for 25-man groups! tested', 'Developer', '2025-07-24 17:08:35', 'img/newsimg/news4.jpg', 1, 'update'),
	(18, 'Server is under Test 5', 'hello', 'ok 5 gg', 'TEST10', '2025-08-04 03:08:18', 'img/newsimg/news1.jpg', 0, 'other');

-- Listage de la structure de table sahtout_site. shop_items
CREATE TABLE IF NOT EXISTS `shop_items` (
  `item_id` int unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `point_cost` int unsigned NOT NULL DEFAULT '0',
  `token_cost` int unsigned NOT NULL DEFAULT '0',
  `stock` int unsigned DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entry` int unsigned DEFAULT NULL,
  `gold_amount` int DEFAULT '0',
  `level_boost` smallint unsigned DEFAULT NULL,
  `at_login_flags` tinyint unsigned DEFAULT '0',
  `is_item` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `idx_category` (`category`),
  KEY `idx_entry` (`entry`),
  CONSTRAINT `fk_shop_items_entry` FOREIGN KEY (`entry`) REFERENCES `site_items` (`entry`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_at_login_flags` CHECK ((`at_login_flags` in (0,1,2,4,8,16,32,64,128,3,5,6,7,9,12,15,31,127,255))),
  CONSTRAINT `chk_is_item` CHECK ((`is_item` in (0,1))),
  CONSTRAINT `shop_items_chk_1` CHECK ((`level_boost` between 2 and 255))
) ENGINE=InnoDB AUTO_INCREMENT=505 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.shop_items : ~22 rows (environ)
INSERT INTO `shop_items` (`item_id`, `category`, `name`, `description`, `image`, `point_cost`, `token_cost`, `stock`, `last_updated`, `entry`, `gold_amount`, `level_boost`, `at_login_flags`, `is_item`) VALUES
	(1, 'Service', 'Level Boost 80', 'Instantly boosts your character to level 80.', 'img/shopimg/services/level_boost_80.jpg', 500, 0, 85, '2025-08-03 15:44:54', NULL, 0, 80, 0, 0),
	(2, 'Mount', 'Swift Zhevra', 'A fast and stylish mount for your adventures.', 'img/shopimg/items/swift_zhevra.jpg', 0, 50, 45, '2025-08-02 04:14:59', 37719, 0, NULL, 0, 1),
	(3, 'Pet', 'Proto drake whelp', 'A cute Proto drake pet to follow you.', 'img/shopimg/items/proto-drake-whelp.jpg', 300, 20, 67, '2025-10-05 21:50:34', 44721, 0, NULL, 0, 1),
	(4, 'Gold', '1000 Gold', 'Add 1000 gold to your in-game wallet.', 'img/shopimg/gold/1000_gold.jpg', 200, 0, NULL, '2025-08-06 23:11:20', NULL, 1000, NULL, 0, 0),
	(5, 'Service', 'Faction Change', 'Change faction (Alliance Horde)+gender+name', 'img/shopimg/services/faction_change.jpg', 600, 0, 18, '2025-08-18 08:58:15', NULL, 0, NULL, 64, 0),
	(6, 'Mount', 'Invincible', 'A legendary flying mount.', 'img/shopimg/items/invincible.jpg', 0, 100, 19, '2025-08-18 06:43:52', 50818, 0, NULL, 0, 1),
	(7, 'Pet', 'Dun Morogh Cub', 'A cute polarbear pet to follow you.', 'img/shopimg/items/dun-morogh-cub.jpg', 250, 10, 57, '2025-08-02 04:01:29', 44970, 0, NULL, 0, 1),
	(8, 'Gold', '5000 Gold', 'Add 5000 gold you are almost Rich', 'img/shopimg/gold/5000_gold.jpg', 300, 500, 29, '2025-08-28 21:08:42', NULL, 5000, NULL, 0, 0),
	(9, 'Gold', '10000 Gold', 'Add 10000 gold you are so rich', 'img/shopimg/gold/10000_gold.jpg', 200, 850, 18, '2025-10-07 16:12:38', NULL, 10000, NULL, 0, 0),
	(10, 'Mount', 'Swift Spectral Tiger', 'A Beautiful Spectral mount', 'img/shopimg/items/swift_spect_tiger.jpg', 250, 100, 16, '2025-10-07 16:15:00', 49284, 0, NULL, 0, 1),
	(11, 'Stuff', 'Heroic sword LK', 'glorenzelg high blade of the silver hand', 'img/shopimg/items/glorenzelg-high-bladesilver.jpg', 150, 100, 19, '2025-08-16 14:40:01', 50730, 0, NULL, 0, 1),
	(12, 'Service', 'Level Boost 70', 'Instantly boosts your character to level 70.', 'img/shopimg/services/level_boost_70.avif', 300, 0, 46, '2025-10-05 21:50:41', NULL, 0, 70, 0, 0),
	(21, 'Service', 'Character Rename', 'Rename your character in-game.', 'img/shopimg/services/rename.jpg', 100, 0, NULL, '2025-08-06 23:09:48', NULL, 0, NULL, 1, 0),
	(22, 'Service', 'Gender Change', 'Customize Characters +name', 'img/shopimg/services/gender.jpg', 150, 0, NULL, '2025-07-30 00:41:50', NULL, 0, NULL, 8, 0),
	(23, 'Service', 'Race Change', 'Race Change +gender+name', 'img/shopimg/services/race_change.jpg', 150, 100, NULL, '2025-08-02 04:12:33', NULL, 0, NULL, 128, 0),
	(24, 'Mount', 'Ashes of al\'ar', NULL, 'img/shopimg/items/phnx.jpg', 150, 20, 3, '2025-10-05 21:55:05', 32458, 0, NULL, 0, 1),
	(102, 'Stuff', 'Bulwark of Azzinoth Shield', 'Bulwark of Azzinoth shield', 'img/shopimg/items/shield.png', 300, 350, NULL, '2025-08-02 04:12:51', 32375, 0, NULL, 0, 1),
	(103, 'Stuff', 'Warglaive of Azzinoth sword', NULL, 'img/shopimg/items/azzinoth_sword1.jpg', 199, 299, NULL, '2025-10-05 20:43:12', 32837, 0, NULL, 0, 1),
	(104, 'Stuff', 'Thunderfury Sword', 'Thunderfury, Blessed Blade of the Windseeker', 'img/shopimg/items/thunderfury2.png', 200, 200, NULL, '2025-08-02 04:12:43', 19019, 0, NULL, 0, 1),
	(105, 'Stuff', 'Onslaught Battle-Helm', 'Onslaught Battle-Helm', 'img/shopimg/items//warrior_helm.png', 150, 100, NULL, '2025-08-02 04:12:27', 30972, 0, NULL, 0, 1);

-- Listage de la structure de table sahtout_site. site_items
CREATE TABLE IF NOT EXISTS `site_items` (
  `entry` int unsigned NOT NULL DEFAULT '0',
  `class` tinyint unsigned NOT NULL DEFAULT '0',
  `subclass` tinyint unsigned NOT NULL DEFAULT '0',
  `SoundOverrideSubclass` tinyint NOT NULL DEFAULT '-1',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `displayid` int unsigned NOT NULL DEFAULT '0',
  `Quality` tinyint unsigned NOT NULL DEFAULT '0',
  `Flags` int unsigned NOT NULL DEFAULT '0',
  `FlagsExtra` int unsigned NOT NULL DEFAULT '0',
  `BuyCount` tinyint unsigned NOT NULL DEFAULT '1',
  `BuyPrice` bigint NOT NULL DEFAULT '0',
  `SellPrice` int unsigned NOT NULL DEFAULT '0',
  `InventoryType` tinyint unsigned NOT NULL DEFAULT '0',
  `AllowableClass` int NOT NULL DEFAULT '-1',
  `AllowableRace` int NOT NULL DEFAULT '-1',
  `ItemLevel` smallint unsigned NOT NULL DEFAULT '0',
  `RequiredLevel` tinyint unsigned NOT NULL DEFAULT '0',
  `RequiredSkill` smallint unsigned NOT NULL DEFAULT '0',
  `RequiredSkillRank` smallint unsigned NOT NULL DEFAULT '0',
  `requiredspell` int unsigned NOT NULL DEFAULT '0',
  `requiredhonorrank` int unsigned NOT NULL DEFAULT '0',
  `RequiredCityRank` int unsigned NOT NULL DEFAULT '0',
  `RequiredReputationFaction` smallint unsigned NOT NULL DEFAULT '0',
  `RequiredReputationRank` smallint unsigned NOT NULL DEFAULT '0',
  `maxcount` int NOT NULL DEFAULT '0',
  `stackable` int DEFAULT '1',
  `ContainerSlots` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_type1` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value1` int NOT NULL DEFAULT '0',
  `stat_type2` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value2` int NOT NULL DEFAULT '0',
  `stat_type3` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value3` int NOT NULL DEFAULT '0',
  `stat_type4` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value4` int NOT NULL DEFAULT '0',
  `stat_type5` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value5` int NOT NULL DEFAULT '0',
  `stat_type6` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value6` int NOT NULL DEFAULT '0',
  `stat_type7` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value7` int NOT NULL DEFAULT '0',
  `stat_type8` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value8` int NOT NULL DEFAULT '0',
  `stat_type9` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value9` int NOT NULL DEFAULT '0',
  `stat_type10` tinyint unsigned NOT NULL DEFAULT '0',
  `stat_value10` int NOT NULL DEFAULT '0',
  `ScalingStatDistribution` smallint NOT NULL DEFAULT '0',
  `ScalingStatValue` int unsigned NOT NULL DEFAULT '0',
  `dmg_min1` float NOT NULL DEFAULT '0',
  `dmg_max1` float NOT NULL DEFAULT '0',
  `dmg_type1` tinyint unsigned NOT NULL DEFAULT '0',
  `dmg_min2` float NOT NULL DEFAULT '0',
  `dmg_max2` float NOT NULL DEFAULT '0',
  `dmg_type2` tinyint unsigned NOT NULL DEFAULT '0',
  `armor` int unsigned NOT NULL DEFAULT '0',
  `holy_res` smallint DEFAULT NULL,
  `fire_res` smallint DEFAULT NULL,
  `nature_res` smallint DEFAULT NULL,
  `frost_res` smallint DEFAULT NULL,
  `shadow_res` smallint DEFAULT NULL,
  `arcane_res` smallint DEFAULT NULL,
  `delay` smallint unsigned NOT NULL DEFAULT '1000',
  `ammo_type` tinyint unsigned NOT NULL DEFAULT '0',
  `RangedModRange` float NOT NULL DEFAULT '0',
  `spellid_1` int NOT NULL DEFAULT '0',
  `spelltrigger_1` tinyint unsigned NOT NULL DEFAULT '0',
  `spellcharges_1` smallint NOT NULL DEFAULT '0',
  `spellppmRate_1` float NOT NULL DEFAULT '0',
  `spellcooldown_1` int NOT NULL DEFAULT '-1',
  `spellcategory_1` smallint unsigned NOT NULL DEFAULT '0',
  `spellcategorycooldown_1` int NOT NULL DEFAULT '-1',
  `spellid_2` int NOT NULL DEFAULT '0',
  `spelltrigger_2` tinyint unsigned NOT NULL DEFAULT '0',
  `spellcharges_2` smallint NOT NULL DEFAULT '0',
  `spellppmRate_2` float NOT NULL DEFAULT '0',
  `spellcooldown_2` int NOT NULL DEFAULT '-1',
  `spellcategory_2` smallint unsigned NOT NULL DEFAULT '0',
  `spellcategorycooldown_2` int NOT NULL DEFAULT '-1',
  `spellid_3` int NOT NULL DEFAULT '0',
  `spelltrigger_3` tinyint unsigned NOT NULL DEFAULT '0',
  `spellcharges_3` smallint NOT NULL DEFAULT '0',
  `spellppmRate_3` float NOT NULL DEFAULT '0',
  `spellcooldown_3` int NOT NULL DEFAULT '-1',
  `spellcategory_3` smallint unsigned NOT NULL DEFAULT '0',
  `spellcategorycooldown_3` int NOT NULL DEFAULT '-1',
  `spellid_4` int NOT NULL DEFAULT '0',
  `spelltrigger_4` tinyint unsigned NOT NULL DEFAULT '0',
  `spellcharges_4` smallint NOT NULL DEFAULT '0',
  `spellppmRate_4` float NOT NULL DEFAULT '0',
  `spellcooldown_4` int NOT NULL DEFAULT '-1',
  `spellcategory_4` smallint unsigned NOT NULL DEFAULT '0',
  `spellcategorycooldown_4` int NOT NULL DEFAULT '-1',
  `spellid_5` int NOT NULL DEFAULT '0',
  `spelltrigger_5` tinyint unsigned NOT NULL DEFAULT '0',
  `spellcharges_5` smallint NOT NULL DEFAULT '0',
  `spellppmRate_5` float NOT NULL DEFAULT '0',
  `spellcooldown_5` int NOT NULL DEFAULT '-1',
  `spellcategory_5` smallint unsigned NOT NULL DEFAULT '0',
  `spellcategorycooldown_5` int NOT NULL DEFAULT '-1',
  `bonding` tinyint unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `PageText` int unsigned NOT NULL DEFAULT '0',
  `LanguageID` tinyint unsigned NOT NULL DEFAULT '0',
  `PageMaterial` tinyint unsigned NOT NULL DEFAULT '0',
  `startquest` int unsigned NOT NULL DEFAULT '0',
  `lockid` int unsigned NOT NULL DEFAULT '0',
  `Material` tinyint NOT NULL DEFAULT '0',
  `sheath` tinyint unsigned NOT NULL DEFAULT '0',
  `RandomProperty` int NOT NULL DEFAULT '0',
  `RandomSuffix` int unsigned NOT NULL DEFAULT '0',
  `block` int unsigned NOT NULL DEFAULT '0',
  `itemset` int unsigned NOT NULL DEFAULT '0',
  `MaxDurability` smallint unsigned NOT NULL DEFAULT '0',
  `area` int unsigned NOT NULL DEFAULT '0',
  `Map` smallint NOT NULL DEFAULT '0',
  `BagFamily` int NOT NULL DEFAULT '0',
  `TotemCategory` int NOT NULL DEFAULT '0',
  `socketColor_1` tinyint NOT NULL DEFAULT '0',
  `socketContent_1` int NOT NULL DEFAULT '0',
  `socketColor_2` tinyint NOT NULL DEFAULT '0',
  `socketContent_2` int NOT NULL DEFAULT '0',
  `socketColor_3` tinyint NOT NULL DEFAULT '0',
  `socketContent_3` int NOT NULL DEFAULT '0',
  `socketBonus` int NOT NULL DEFAULT '0',
  `GemProperties` int NOT NULL DEFAULT '0',
  `RequiredDisenchantSkill` smallint NOT NULL DEFAULT '-1',
  `ArmorDamageModifier` float NOT NULL DEFAULT '0',
  `duration` int unsigned NOT NULL DEFAULT '0',
  `ItemLimitCategory` smallint NOT NULL DEFAULT '0',
  `HolidayId` int unsigned NOT NULL DEFAULT '0',
  `ScriptName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `DisenchantID` int unsigned NOT NULL DEFAULT '0',
  `FoodType` tinyint unsigned NOT NULL DEFAULT '0',
  `minMoneyLoot` int unsigned NOT NULL DEFAULT '0',
  `maxMoneyLoot` int unsigned NOT NULL DEFAULT '0',
  `flagsCustom` int unsigned NOT NULL DEFAULT '0',
  `VerifiedBuild` int DEFAULT NULL,
  PRIMARY KEY (`entry`),
  KEY `idx_name` (`name`(250)),
  KEY `items_index` (`class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Item System';

-- Listage des données de la table sahtout_site.site_items : ~15 rows (environ)
INSERT INTO `site_items` (`entry`, `class`, `subclass`, `SoundOverrideSubclass`, `name`, `displayid`, `Quality`, `Flags`, `FlagsExtra`, `BuyCount`, `BuyPrice`, `SellPrice`, `InventoryType`, `AllowableClass`, `AllowableRace`, `ItemLevel`, `RequiredLevel`, `RequiredSkill`, `RequiredSkillRank`, `requiredspell`, `requiredhonorrank`, `RequiredCityRank`, `RequiredReputationFaction`, `RequiredReputationRank`, `maxcount`, `stackable`, `ContainerSlots`, `stat_type1`, `stat_value1`, `stat_type2`, `stat_value2`, `stat_type3`, `stat_value3`, `stat_type4`, `stat_value4`, `stat_type5`, `stat_value5`, `stat_type6`, `stat_value6`, `stat_type7`, `stat_value7`, `stat_type8`, `stat_value8`, `stat_type9`, `stat_value9`, `stat_type10`, `stat_value10`, `ScalingStatDistribution`, `ScalingStatValue`, `dmg_min1`, `dmg_max1`, `dmg_type1`, `dmg_min2`, `dmg_max2`, `dmg_type2`, `armor`, `holy_res`, `fire_res`, `nature_res`, `frost_res`, `shadow_res`, `arcane_res`, `delay`, `ammo_type`, `RangedModRange`, `spellid_1`, `spelltrigger_1`, `spellcharges_1`, `spellppmRate_1`, `spellcooldown_1`, `spellcategory_1`, `spellcategorycooldown_1`, `spellid_2`, `spelltrigger_2`, `spellcharges_2`, `spellppmRate_2`, `spellcooldown_2`, `spellcategory_2`, `spellcategorycooldown_2`, `spellid_3`, `spelltrigger_3`, `spellcharges_3`, `spellppmRate_3`, `spellcooldown_3`, `spellcategory_3`, `spellcategorycooldown_3`, `spellid_4`, `spelltrigger_4`, `spellcharges_4`, `spellppmRate_4`, `spellcooldown_4`, `spellcategory_4`, `spellcategorycooldown_4`, `spellid_5`, `spelltrigger_5`, `spellcharges_5`, `spellppmRate_5`, `spellcooldown_5`, `spellcategory_5`, `spellcategorycooldown_5`, `bonding`, `description`, `PageText`, `LanguageID`, `PageMaterial`, `startquest`, `lockid`, `Material`, `sheath`, `RandomProperty`, `RandomSuffix`, `block`, `itemset`, `MaxDurability`, `area`, `Map`, `BagFamily`, `TotemCategory`, `socketColor_1`, `socketContent_1`, `socketColor_2`, `socketContent_2`, `socketColor_3`, `socketContent_3`, `socketBonus`, `GemProperties`, `RequiredDisenchantSkill`, `ArmorDamageModifier`, `duration`, `ItemLimitCategory`, `HolidayId`, `ScriptName`, `DisenchantID`, `FoodType`, `minMoneyLoot`, `maxMoneyLoot`, `flagsCustom`, `VerifiedBuild`) VALUES
	(19019, 2, 7, -1, 'Thunderfury, Blessed Blade of the Windseeker', 30606, 5, 0, 0, 1, 615704, 123140, 13, -1, -1, 80, 60, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 3, 5, 7, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 44, 115, 0, 16, 30, 3, 0, 0, 8, 9, 0, 0, 0, 1900, 0, 0, 21992, 2, 0, 4, -1, 0, -1, 0, 2, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, -20, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(30969, 4, 4, -1, 'Onslaught Gauntlets', 45659, 4, 4096, 0, 1, 0, 0, 10, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 41, 3, 30, 7, 49, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1141, 0, 0, 0, 0, 0, 0, 0, 0, 0, 42094, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 55, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 2902, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(30972, 4, 4, -1, 'Onslaught Battle-Helm', 49684, 4, 4096, 0, 1, 0, 0, 1, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 54, 3, 41, 7, 54, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1483, 0, 0, 0, 0, 0, 0, 0, 0, 0, 39925, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 100, 0, 0, 0, 0, 1, 0, 2, 0, 0, 0, 2927, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(30975, 4, 4, -1, 'Onslaught Breastplate', 45658, 4, 4096, 0, 1, 0, 0, 5, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 53, 3, 34, 7, 54, 31, 16, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1825, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40680, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 165, 0, 0, 0, 0, 2, 0, 8, 0, 8, 0, 2952, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(32375, 4, 6, -1, 'Bulwark of Azzinoth', 45653, 4, 0, 0, 1, 472692, 94538, 14, -1, -1, 151, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 7, 40, 12, 26, 4, 29, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6336, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40407, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 6, 4, 0, 0, 174, 0, 120, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(32458, 15, 5, -1, 'Ashes of Al\'ar', 44872, 4, 0, 0, 1, 1000000, 0, 0, -1, -1, 70, 70, 762, 300, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, 0, 0, -1, 330, 3000, 40192, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Teaches you how to summon this mount.  Can only be summoned in Outland or Northrend.  This is an extremely fast mount.', 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(32837, 2, 7, -1, 'Warglaive of Azzinoth', 45479, 5, 0, 0, 1, 1215564, 243112, 21, 9, 32767, 156, 70, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 3, 22, 7, 29, 31, 21, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 214, 398, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2800, 0, 0, 15810, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 699, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(32838, 2, 7, -1, 'Warglaive of Azzinoth', 45481, 5, 0, 0, 1, 1219873, 243974, 22, 9, 32767, 156, 70, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 3, 21, 7, 28, 32, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 107, 199, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1400, 0, 0, 15810, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 699, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(37719, 15, 5, -1, 'Swift Zhevra', 49950, 4, 0, 0, 1, 100000, 0, 0, 262143, -1, 40, 40, 762, 150, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, 0, 0, -1, 330, 3000, 49322, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Teaches you how to summon this mount.  This is a very fast mount.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(44721, 15, 2, 0, 'Proto-Drake Whelp', 57246, 1, 0, 0, 1, 10000, 2500, 0, -1, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, 1000, 0, -1, 61350, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 'Teaches you how to summon and dismiss this companion.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(44970, 15, 2, -1, 'Dun Morogh Cub', 57877, 3, 4160, 0, 1, 0, 0, 0, -1, -1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, -1, 0, -1, 62508, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 'Teaches you how to summon this companion.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4096, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 9767),
	(49284, 15, 5, -1, 'Reins of the Swift Spectral Tiger', 59462, 4, 0, 0, 1, 100000, 0, 0, 262143, 2147483647, 40, 40, 762, 150, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, -1, 330, 3000, 42777, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Teaches you how to summon this mount.  This is a very fast mount.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 10314),
	(50730, 2, 8, -1, 'Glorenzelg, High-Blade of the Silver Hand', 64397, 4, 8, 0, 1, 1663877, 332775, 17, -1, -1, 284, 80, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 198, 7, 222, 32, 122, 37, 114, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 991, 1487, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3600, 0, 0, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Paragon of the Light, lead our armies against the coming darkness.', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 120, 0, 0, 0, 0, 2, 0, 2, 0, 2, 0, 3312, 0, 375, 0, 0, 0, 0, '', 68, 0, 0, 0, 0, 11159),
	(50818, 15, 5, -1, 'Invincible\'s Reins', 58122, 4, 32768, 0, 1, 0, 0, 0, 262143, 32767, 20, 20, 762, 75, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 483, 0, 0, 0, -1, 330, 3000, 72286, 6, 0, 0, -1, 0, 3000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 'Teaches you how to summon this mount.', 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 11159);

-- Listage de la structure de table sahtout_site. user_currencies
CREATE TABLE IF NOT EXISTS `user_currencies` (
  `account_id` int unsigned NOT NULL,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` int unsigned NOT NULL DEFAULT '0',
  `tokens` int unsigned NOT NULL DEFAULT '0',
  `role` enum('player','moderator','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'player',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  KEY `idx_username` (`username`),
  KEY `fk_user_currencies_avatar` (`avatar`),
  CONSTRAINT `fk_user_currencies_avatar` FOREIGN KEY (`avatar`) REFERENCES `profile_avatars` (`filename`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `user_currencies_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.user_currencies : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. vote_log
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Listage des données de la table sahtout_site.vote_log : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. vote_log_history
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sahtout_site.vote_log_history : ~0 rows (environ)

-- Listage de la structure de table sahtout_site. vote_sites
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
) ENGINE=InnoDB AUTO_INCREMENT=105181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sahtout_site.vote_sites : ~7 rows (environ)
INSERT INTO `vote_sites` (`id`, `callback_file_name`, `site_name`, `siteid`, `url_format`, `button_image_url`, `cooldown_hours`, `reward_points`, `uses_callback`, `callback_secret`) VALUES
	(105168, 'gtop100', 'GTOP100', '105163', 'https://gtop100.com/wow-private-servers/server-{siteid}?vote=1&pingUsername={username}', 'img/voteimg/gtop100.com.jpg', 12, 100, 1, 'gtop100CODE'),
	(105169, 'topg', 'topg', '675795', 'https://topg.org/wow-private-servers/server-{siteid}-{userid}', 'https://topg.org/topg2.gif', 2, 70, 1, ''),
	(105174, 'arenatop100', 'Arena-Top100', 'blody2', 'https://www.arena-top100.com/index.php?a=in&u={siteid}&id={userid}', 'https://www.arena-top100.com/images/arena-top100.png', 1, 60, 1, 'arena-top100CODE'),
	(105175, 'top100arena', 'Top100arena', '101743', 'https://www.top100arena.com/listing/{siteid}/vote?incentive={userid}', 'https://www.top100arena.com/rankbadge/101743', 1, 90, 1, ''),
	(105176, 'mmohub', 'mmohub', '1110', 'https://www.mmohub.com/site/{siteid}/vote/{userid}', 'https://mmohub.com/vote.jpg', 1, 100, 1, '0'),
	(105177, 'private_server', 'private_serverws', 'blody', 'https://private-server.ws/index.php?a=in&u={siteid}&id={userid}', 'https://private-server.ws/button.php?u=blody&buttontype=static', 12, 100, 1, '0'),
	(105178, 'xtremetop100', 'XtremeTop100', '1132377856', 'https://www.xtremetop100.com/in.php?site={siteid}&postback={userid}', 'https://www.xtremetop100.com/votenew.jpg', 12, 70, 1, '0');

-- Listage de la structure de table sahtout_site. website_activity_log
CREATE TABLE IF NOT EXISTS `website_activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `character_name` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` int unsigned NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `website_activity_log_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table sahtout_site.website_activity_log : ~0 rows (environ)

-- Listage de la structure de déclencheur sahtout_site. before_site_items_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER `before_site_items_insert` BEFORE INSERT ON `site_items` FOR EACH ROW BEGIN
    DECLARE item_exists INT;
    -- Check if the entry exists in item_template
    SELECT COUNT(*) INTO item_exists FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`;
    
    IF item_exists > 0 THEN
        -- Set all columns from item_template
        SET NEW.`class` = (SELECT `class` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`subclass` = (SELECT `subclass` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`SoundOverrideSubclass` = (SELECT `SoundOverrideSubclass` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`name` = (SELECT `name` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`displayid` = (SELECT `displayid` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`Quality` = (SELECT `Quality` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`Flags` = (SELECT `Flags` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`FlagsExtra` = (SELECT `FlagsExtra` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`BuyCount` = (SELECT `BuyCount` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`BuyPrice` = (SELECT `BuyPrice` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`SellPrice` = (SELECT `SellPrice` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`InventoryType` = (SELECT `InventoryType` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`AllowableClass` = (SELECT `AllowableClass` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`AllowableRace` = (SELECT `AllowableRace` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ItemLevel` = (SELECT `ItemLevel` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredLevel` = (SELECT `RequiredLevel` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredSkill` = (SELECT `RequiredSkill` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredSkillRank` = (SELECT `RequiredSkillRank` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`requiredspell` = (SELECT `requiredspell` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`requiredhonorrank` = (SELECT `requiredhonorrank` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredCityRank` = (SELECT `RequiredCityRank` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredReputationFaction` = (SELECT `RequiredReputationFaction` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredReputationRank` = (SELECT `RequiredReputationRank` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`maxcount` = (SELECT `maxcount` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stackable` = (SELECT `stackable` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ContainerSlots` = (SELECT `ContainerSlots` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type1` = (SELECT `stat_type1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value1` = (SELECT `stat_value1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type2` = (SELECT `stat_type2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value2` = (SELECT `stat_value2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type3` = (SELECT `stat_type3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value3` = (SELECT `stat_value3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type4` = (SELECT `stat_type4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value4` = (SELECT `stat_value4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type5` = (SELECT `stat_type5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value5` = (SELECT `stat_value5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type6` = (SELECT `stat_type6` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value6` = (SELECT `stat_value6` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type7` = (SELECT `stat_type7` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value7` = (SELECT `stat_value7` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type8` = (SELECT `stat_type8` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value8` = (SELECT `stat_value8` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type9` = (SELECT `stat_type9` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value9` = (SELECT `stat_value9` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_type10` = (SELECT `stat_type10` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`stat_value10` = (SELECT `stat_value10` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ScalingStatDistribution` = (SELECT `ScalingStatDistribution` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ScalingStatValue` = (SELECT `ScalingStatValue` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_min1` = (SELECT `dmg_min1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_max1` = (SELECT `dmg_max1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_type1` = (SELECT `dmg_type1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_min2` = (SELECT `dmg_min2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_max2` = (SELECT `dmg_max2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`dmg_type2` = (SELECT `dmg_type2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`armor` = (SELECT `armor` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`holy_res` = (SELECT `holy_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`fire_res` = (SELECT `fire_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`nature_res` = (SELECT `nature_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`frost_res` = (SELECT `frost_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`shadow_res` = (SELECT `shadow_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`arcane_res` = (SELECT `arcane_res` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`delay` = (SELECT `delay` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ammo_type` = (SELECT `ammo_type` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RangedModRange` = (SELECT `RangedModRange` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellid_1` = (SELECT `spellid_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spelltrigger_1` = (SELECT `spelltrigger_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcharges_1` = (SELECT `spellcharges_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellppmRate_1` = (SELECT `spellppmRate_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcooldown_1` = (SELECT `spellcooldown_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategory_1` = (SELECT `spellcategory_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategorycooldown_1` = (SELECT `spellcategorycooldown_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellid_2` = (SELECT `spellid_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spelltrigger_2` = (SELECT `spelltrigger_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcharges_2` = (SELECT `spellcharges_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellppmRate_2` = (SELECT `spellppmRate_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcooldown_2` = (SELECT `spellcooldown_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategory_2` = (SELECT `spellcategory_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategorycooldown_2` = (SELECT `spellcategorycooldown_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellid_3` = (SELECT `spellid_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spelltrigger_3` = (SELECT `spelltrigger_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcharges_3` = (SELECT `spellcharges_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellppmRate_3` = (SELECT `spellppmRate_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcooldown_3` = (SELECT `spellcooldown_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategory_3` = (SELECT `spellcategory_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategorycooldown_3` = (SELECT `spellcategorycooldown_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellid_4` = (SELECT `spellid_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spelltrigger_4` = (SELECT `spelltrigger_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcharges_4` = (SELECT `spellcharges_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellppmRate_4` = (SELECT `spellppmRate_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcooldown_4` = (SELECT `spellcooldown_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategory_4` = (SELECT `spellcategory_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategorycooldown_4` = (SELECT `spellcategorycooldown_4` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellid_5` = (SELECT `spellid_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spelltrigger_5` = (SELECT `spelltrigger_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcharges_5` = (SELECT `spellcharges_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellppmRate_5` = (SELECT `spellppmRate_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcooldown_5` = (SELECT `spellcooldown_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategory_5` = (SELECT `spellcategory_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`spellcategorycooldown_5` = (SELECT `spellcategorycooldown_5` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`bonding` = (SELECT `bonding` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`description` = (SELECT `description` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`PageText` = (SELECT `PageText` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`LanguageID` = (SELECT `LanguageID` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`PageMaterial` = (SELECT `PageMaterial` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`startquest` = (SELECT `startquest` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`lockid` = (SELECT `lockid` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`Material` = (SELECT `Material` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`sheath` = (SELECT `sheath` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RandomProperty` = (SELECT `RandomProperty` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RandomSuffix` = (SELECT `RandomSuffix` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`block` = (SELECT `block` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`itemset` = (SELECT `itemset` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`MaxDurability` = (SELECT `MaxDurability` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`area` = (SELECT `area` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`Map` = (SELECT `Map` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`BagFamily` = (SELECT `BagFamily` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`TotemCategory` = (SELECT `TotemCategory` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketColor_1` = (SELECT `socketColor_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketContent_1` = (SELECT `socketContent_1` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketColor_2` = (SELECT `socketColor_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketContent_2` = (SELECT `socketContent_2` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketColor_3` = (SELECT `socketColor_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketContent_3` = (SELECT `socketContent_3` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`socketBonus` = (SELECT `socketBonus` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`GemProperties` = (SELECT `GemProperties` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`RequiredDisenchantSkill` = (SELECT `RequiredDisenchantSkill` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ArmorDamageModifier` = (SELECT `ArmorDamageModifier` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`duration` = (SELECT `duration` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ItemLimitCategory` = (SELECT `ItemLimitCategory` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`HolidayId` = (SELECT `HolidayId` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`ScriptName` = (SELECT `ScriptName` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`DisenchantID` = (SELECT `DisenchantID` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`FoodType` = (SELECT `FoodType` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`minMoneyLoot` = (SELECT `minMoneyLoot` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`maxMoneyLoot` = (SELECT `maxMoneyLoot` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`flagsCustom` = (SELECT `flagsCustom` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
        SET NEW.`VerifiedBuild` = (SELECT `VerifiedBuild` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
    ELSE
        -- Prevent insertion if entry doesn't exist in item_template
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid entry: No matching entry found in item_template';
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
