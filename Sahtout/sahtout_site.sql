-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.5 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for sahtout_site
CREATE DATABASE IF NOT EXISTS `sahtout_site` /*!40100 DEFAULT CHARACTER SET utf16 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sahtout_site`;

-- Dumping structure for table sahtout_site.character_teleport_log
CREATE TABLE IF NOT EXISTS `character_teleport_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `character_guid` int unsigned NOT NULL,
  `character_name` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teleport_timestamp` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `character_guid` (`character_guid`),
  CONSTRAINT `character_teleport_log_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE,
  CONSTRAINT `character_teleport_log_ibfk_2` FOREIGN KEY (`character_guid`) REFERENCES `acore_characters`.`characters` (`guid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.character_teleport_log: ~27 rows (approximately)
INSERT INTO `character_teleport_log` (`id`, `account_id`, `character_guid`, `character_name`, `teleport_timestamp`) VALUES
	(10, 61, 34, 'Tras', 1753642061),
	(11, 61, 35, 'Berda', 1753642068),
	(12, 62, 52, 'Start', 1753656220),
	(13, 63, 55, 'Zarl', 1753658085),
	(14, 63, 56, 'Dzass', 1753658304),
	(15, 65, 61, 'Qqre', 1753754678),
	(16, 67, 73, 'Juks', 1753764524),
	(17, 65, 61, 'Qqre', 1753764556),
	(18, 65, 62, 'Styldq', 1753765485),
	(19, 70, 77, 'Dzadas', 1753815732),
	(20, 70, 76, 'Arwa', 1753815814),
	(23, 72, 96, 'Junvod', 1754019094),
	(24, 72, 96, 'Junvod', 1754020036),
	(25, 73, 98, 'Dzadass', 1754107629),
	(26, 73, 99, 'Ward', 1754107634),
	(27, 77, 100, 'Marker', 1754236043),
	(28, 78, 110, 'Songa', 1754517046),
	(29, 85, 112, 'Thedso', 1754544455),
	(30, 85, 112, 'Thedso', 1754546296),
	(31, 85, 112, 'Thedso', 1754546331),
	(32, 85, 112, 'Thedso', 1754546339),
	(33, 85, 112, 'Thedso', 1754546343),
	(34, 85, 112, 'Thedso', 1754546369),
	(35, 85, 112, 'Thedso', 1754546412),
	(36, 85, 112, 'Thedso', 1754546728),
	(37, 85, 112, 'Thedso', 1754546763),
	(38, 85, 112, 'Thedso', 1754547111),
	(39, 86, 113, 'Mas', 1754547616),
	(40, 86, 114, 'Kasqs', 1754547795),
	(41, 85, 112, 'Thedso', 1754549143),
	(42, 99, 115, 'Dsapm', 1754609116);

-- Dumping structure for table sahtout_site.custom_item_template
CREATE TABLE IF NOT EXISTS `custom_item_template` (
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
  `StatsCount` tinyint unsigned NOT NULL DEFAULT '0',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Custom Item System';

-- Dumping data for table sahtout_site.custom_item_template: ~2 rows (approximately)
INSERT INTO `custom_item_template` (`entry`, `class`, `subclass`, `SoundOverrideSubclass`, `name`, `displayid`, `Quality`, `Flags`, `FlagsExtra`, `BuyCount`, `BuyPrice`, `SellPrice`, `InventoryType`, `AllowableClass`, `AllowableRace`, `ItemLevel`, `RequiredLevel`, `RequiredSkill`, `RequiredSkillRank`, `requiredspell`, `requiredhonorrank`, `RequiredCityRank`, `RequiredReputationFaction`, `RequiredReputationRank`, `maxcount`, `stackable`, `ContainerSlots`, `StatsCount`, `stat_type1`, `stat_value1`, `stat_type2`, `stat_value2`, `stat_type3`, `stat_value3`, `stat_type4`, `stat_value4`, `stat_type5`, `stat_value5`, `stat_type6`, `stat_value6`, `stat_type7`, `stat_value7`, `stat_type8`, `stat_value8`, `stat_type9`, `stat_value9`, `stat_type10`, `stat_value10`, `ScalingStatDistribution`, `ScalingStatValue`, `dmg_min1`, `dmg_max1`, `dmg_type1`, `dmg_min2`, `dmg_max2`, `dmg_type2`, `armor`, `holy_res`, `fire_res`, `nature_res`, `frost_res`, `shadow_res`, `arcane_res`, `delay`, `ammo_type`, `RangedModRange`, `spellid_1`, `spelltrigger_1`, `spellcharges_1`, `spellppmRate_1`, `spellcooldown_1`, `spellcategory_1`, `spellcategorycooldown_1`, `spellid_2`, `spelltrigger_2`, `spellcharges_2`, `spellppmRate_2`, `spellcooldown_2`, `spellcategory_2`, `spellcategorycooldown_2`, `spellid_3`, `spelltrigger_3`, `spellcharges_3`, `spellppmRate_3`, `spellcooldown_3`, `spellcategory_3`, `spellcategorycooldown_3`, `spellid_4`, `spelltrigger_4`, `spellcharges_4`, `spellppmRate_4`, `spellcooldown_4`, `spellcategory_4`, `spellcategorycooldown_4`, `spellid_5`, `spelltrigger_5`, `spellcharges_5`, `spellppmRate_5`, `spellcooldown_5`, `spellcategory_5`, `spellcategorycooldown_5`, `bonding`, `description`, `PageText`, `LanguageID`, `PageMaterial`, `startquest`, `lockid`, `Material`, `sheath`, `RandomProperty`, `RandomSuffix`, `block`, `itemset`, `MaxDurability`, `area`, `Map`, `BagFamily`, `TotemCategory`, `socketColor_1`, `socketContent_1`, `socketColor_2`, `socketContent_2`, `socketColor_3`, `socketContent_3`, `socketBonus`, `GemProperties`, `RequiredDisenchantSkill`, `ArmorDamageModifier`, `duration`, `ItemLimitCategory`, `HolidayId`, `ScriptName`, `DisenchantID`, `FoodType`, `minMoneyLoot`, `maxMoneyLoot`, `flagsCustom`, `VerifiedBuild`) VALUES
	(1000, 0, 0, -1, 'Custom Item 1000', 0, 0, 0, 0, 1, 0, 0, 0, -1, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, 0, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 2, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, NULL),
	(19019, 2, 7, -1, 'Thunderfury, Blessed Blade of the Windseeker', 30606, 5, 0, 0, 1, 0, 0, 13, -1, -1, 80, 60, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 3, 5, 7, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 44, 115, 0, 16, 30, 3, 0, 0, 8, 9, 0, 0, 0, 1900, 0, 0, 21992, 2, 0, 4, -1, 0, -1, 0, 2, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, NULL);

-- Dumping structure for table sahtout_site.pending_accounts
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf16;

-- Dumping data for table sahtout_site.pending_accounts: ~0 rows (approximately)

-- Dumping structure for table sahtout_site.profile_avatars
CREATE TABLE IF NOT EXISTS `profile_avatars` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.profile_avatars: ~27 rows (approximately)
INSERT INTO `profile_avatars` (`id`, `filename`, `display_name`, `active`, `created_at`) VALUES
	(1, '1-0.png', 'Avatar 1', 1, '2025-07-27 15:35:31'),
	(2, '1-1.png', 'Avatar 2', 1, '2025-07-27 15:35:31'),
	(3, '2-0.png', 'Avatar 3', 1, '2025-07-27 15:35:31'),
	(4, '2-1.png', 'Avatar 4', 1, '2025-07-27 15:35:31'),
	(5, '3-0.png', 'Avatar 5', 1, '2025-07-27 15:35:31'),
	(6, '3-1.png', 'Avatar 6', 1, '2025-07-27 15:35:31'),
	(7, '4-0.png', 'Avatar 7', 1, '2025-07-27 15:39:58'),
	(8, '4-1.png', 'Avatar 8', 1, '2025-07-27 15:43:18'),
	(9, '5-0.png', 'Avatar 9', 1, '2025-07-27 15:44:07'),
	(10, '5-1.png', 'Avatar 10', 1, '2025-07-27 15:44:17'),
	(11, '6-0.png', 'Avatar 11', 1, '2025-07-27 15:45:20'),
	(12, '6-1.png', 'Avatar 12', 1, '2025-07-27 15:45:30'),
	(13, '7-0.png', 'Avatar 13', 1, '2025-07-27 15:45:49'),
	(14, '7-1.png', 'Avatar 14', 1, '2025-07-27 15:47:55'),
	(15, '8-0.png', 'Avatar 15', 1, '2025-07-27 15:48:09'),
	(16, '8-1.png', 'Avatar 16', 1, '2025-07-27 15:48:20'),
	(17, '10-0.png', 'Avatar 17', 1, '2025-07-27 15:48:48'),
	(18, '10-1.png', 'Avatar 18', 1, '2025-07-27 19:53:56'),
	(19, '11-0.png', 'Avatar 19', 1, '2025-07-27 19:54:04'),
	(20, '11-1.png', 'Avatar 20', 1, '2025-07-27 19:54:14'),
	(21, '28-0.png', 'Avatar 21', 1, '2025-07-27 19:54:24'),
	(22, '28-1.png', 'Avatar 22', 1, '2025-07-27 19:54:36'),
	(23, '32-0.png', 'Avatar 23', 1, '2025-07-27 19:54:48'),
	(24, '32-1.png', 'Avatar 24', 1, '2025-07-27 19:54:57'),
	(25, '37-0.png', 'Avatar 25', 1, '2025-07-27 19:55:09'),
	(26, '70-0.png', 'Avatar 26', 1, '2025-07-27 19:55:18'),
	(27, '70-1.png', 'Avatar 27', 1, '2025-07-27 19:55:29');

-- Dumping structure for table sahtout_site.purchases
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
) ENGINE=InnoDB AUTO_INCREMENT=295 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.purchases: ~149 rows (approximately)
INSERT INTO `purchases` (`purchase_id`, `account_id`, `item_id`, `point_cost`, `token_cost`, `purchase_date`) VALUES
	(9, 65, 4, 0, 0, '2025-07-29 00:44:15'),
	(10, 65, 8, 0, 0, '2025-07-29 00:44:22'),
	(11, 65, 2, 0, 0, '2025-07-29 00:44:26'),
	(12, 65, 9, 0, 0, '2025-07-29 00:55:52'),
	(13, 65, 4, 0, 0, '2025-07-29 01:22:21'),
	(14, 65, 4, 0, 0, '2025-07-29 01:22:27'),
	(15, 65, 4, 0, 0, '2025-07-29 01:22:32'),
	(16, 65, 8, 0, 0, '2025-07-29 01:27:31'),
	(17, 65, 4, 0, 0, '2025-07-29 01:51:15'),
	(42, 65, 4, 0, 0, '2025-07-29 02:56:02'),
	(43, 65, 4, 0, 0, '2025-07-29 02:56:09'),
	(45, 65, 9, 0, 0, '2025-07-29 02:57:12'),
	(46, 65, 8, 0, 0, '2025-07-29 02:57:15'),
	(47, 65, 9, 0, 0, '2025-07-29 02:58:34'),
	(48, 65, 8, 0, 0, '2025-07-29 02:58:50'),
	(49, 65, 8, 0, 0, '2025-07-29 03:00:20'),
	(50, 65, 4, 0, 0, '2025-07-29 03:01:10'),
	(51, 65, 9, 0, 0, '2025-07-29 03:01:18'),
	(52, 65, 8, 0, 0, '2025-07-29 03:05:17'),
	(53, 65, 8, 0, 0, '2025-07-29 03:05:24'),
	(54, 65, 8, 0, 0, '2025-07-29 03:05:28'),
	(55, 65, 9, 0, 0, '2025-07-29 03:06:46'),
	(56, 65, 9, 0, 0, '2025-07-29 03:06:51'),
	(57, 65, 9, 0, 0, '2025-07-29 03:06:54'),
	(59, 65, 9, 0, 0, '2025-07-29 03:10:13'),
	(60, 65, 8, 0, 0, '2025-07-29 03:10:17'),
	(62, 65, 8, 0, 0, '2025-07-29 03:15:07'),
	(64, 65, 4, 0, 0, '2025-07-29 03:15:44'),
	(65, 67, 9, 0, 0, '2025-07-29 03:21:48'),
	(67, 67, 4, 0, 0, '2025-07-29 03:23:23'),
	(68, 65, 9, 0, 0, '2025-07-29 04:15:55'),
	(70, 70, 9, 0, 0, '2025-07-29 19:03:38'),
	(71, 70, 8, 0, 0, '2025-07-29 19:06:36'),
	(72, 70, 4, 0, 0, '2025-07-29 19:06:39'),
	(163, 71, 4, 0, 0, '2025-07-30 04:04:54'),
	(165, 71, 21, 0, 0, '2025-07-30 04:05:03'),
	(166, 71, 1, 0, 0, '2025-07-30 04:05:11'),
	(167, 71, 23, 0, 0, '2025-07-30 04:05:28'),
	(169, 71, 7, 0, 0, '2025-07-30 04:05:40'),
	(170, 71, 2, 0, 0, '2025-07-30 04:08:18'),
	(171, 71, 9, 0, 0, '2025-07-30 04:08:21'),
	(172, 71, 11, 0, 0, '2025-07-30 04:15:06'),
	(173, 71, 10, 0, 0, '2025-07-30 04:29:14'),
	(174, 71, 1, 0, 0, '2025-07-30 04:32:33'),
	(175, 71, 9, 0, 0, '2025-07-30 04:32:36'),
	(176, 71, 10, 0, 0, '2025-07-30 04:32:39'),
	(177, 71, 23, 0, 0, '2025-07-30 04:32:48'),
	(178, 71, 10, 0, 0, '2025-07-30 04:34:31'),
	(179, 71, 10, 0, 0, '2025-07-30 04:36:24'),
	(180, 71, 8, 0, 0, '2025-07-30 04:40:04'),
	(181, 71, 10, 0, 0, '2025-07-30 04:40:07'),
	(182, 71, 1, 0, 0, '2025-07-30 04:40:15'),
	(183, 71, 10, 0, 0, '2025-07-30 04:48:03'),
	(184, 71, 10, 0, 0, '2025-07-30 04:48:30'),
	(185, 71, 8, 0, 0, '2025-07-30 04:48:33'),
	(186, 71, 1, 0, 0, '2025-07-30 04:48:37'),
	(187, 71, 10, 0, 0, '2025-07-30 04:52:21'),
	(188, 71, 10, 0, 0, '2025-07-30 05:00:20'),
	(189, 72, 4, 0, 0, '2025-08-01 03:25:59'),
	(190, 72, 9, 0, 0, '2025-08-01 03:28:50'),
	(191, 72, 4, 0, 0, '2025-08-01 03:28:53'),
	(192, 72, 8, 0, 0, '2025-08-01 03:28:56'),
	(193, 72, 24, 0, 0, '2025-08-01 03:28:58'),
	(194, 72, 6, 0, 0, '2025-08-01 03:29:02'),
	(195, 72, 10, 0, 0, '2025-08-01 03:29:07'),
	(196, 72, 2, 0, 0, '2025-08-01 03:29:10'),
	(197, 72, 3, 0, 0, '2025-08-01 03:29:14'),
	(198, 72, 7, 0, 0, '2025-08-01 03:29:19'),
	(199, 72, 21, 0, 0, '2025-08-01 03:29:24'),
	(200, 72, 5, 0, 0, '2025-08-01 03:29:28'),
	(201, 72, 22, 0, 0, '2025-08-01 03:29:32'),
	(202, 72, 1, 0, 0, '2025-08-01 03:29:36'),
	(203, 72, 23, 0, 0, '2025-08-01 03:29:41'),
	(204, 72, 102, 0, 0, '2025-08-01 03:29:46'),
	(205, 72, 11, 0, 0, '2025-08-01 03:29:53'),
	(206, 72, 104, 0, 0, '2025-08-01 03:29:58'),
	(207, 72, 103, 0, 0, '2025-08-01 03:30:04'),
	(208, 73, 4, 0, 0, '2025-08-02 03:39:30'),
	(209, 73, 4, 0, 0, '2025-08-02 03:39:33'),
	(210, 73, 9, 0, 0, '2025-08-02 03:39:37'),
	(211, 73, 8, 0, 0, '2025-08-02 03:39:40'),
	(212, 73, 4, 0, 0, '2025-08-02 03:53:38'),
	(213, 73, 4, 0, 0, '2025-08-02 03:53:48'),
	(214, 73, 4, 0, 0, '2025-08-02 03:53:55'),
	(215, 73, 4, 0, 0, '2025-08-02 03:53:58'),
	(216, 73, 4, 0, 0, '2025-08-02 03:54:01'),
	(217, 73, 4, 0, 0, '2025-08-02 03:54:39'),
	(218, 73, 4, 0, 0, '2025-08-02 03:54:50'),
	(219, 73, 4, 0, 0, '2025-08-02 03:54:53'),
	(220, 73, 4, 0, 0, '2025-08-02 03:54:56'),
	(221, 73, 9, 0, 0, '2025-08-02 03:55:00'),
	(222, 73, 9, 0, 0, '2025-08-02 03:55:02'),
	(223, 73, 9, 0, 0, '2025-08-02 03:55:05'),
	(224, 73, 4, 0, 0, '2025-08-02 03:59:02'),
	(225, 73, 4, 0, 0, '2025-08-02 03:59:05'),
	(226, 73, 4, 0, 0, '2025-08-02 03:59:08'),
	(227, 73, 9, 0, 0, '2025-08-02 03:59:11'),
	(228, 73, 4, 0, 0, '2025-08-02 03:59:16'),
	(229, 73, 4, 0, 0, '2025-08-02 03:59:19'),
	(230, 73, 4, 0, 0, '2025-08-02 03:59:22'),
	(231, 73, 4, 0, 0, '2025-08-02 04:00:13'),
	(232, 73, 4, 0, 0, '2025-08-02 04:00:20'),
	(233, 73, 4, 0, 0, '2025-08-02 04:00:31'),
	(234, 73, 9, 0, 0, '2025-08-02 04:00:51'),
	(235, 73, 24, 0, 0, '2025-08-02 04:01:07'),
	(236, 73, 6, 0, 0, '2025-08-02 04:01:13'),
	(237, 73, 10, 0, 0, '2025-08-02 04:01:18'),
	(238, 73, 2, 0, 0, '2025-08-02 04:01:24'),
	(239, 73, 7, 0, 0, '2025-08-02 04:01:29'),
	(240, 73, 3, 0, 0, '2025-08-02 04:01:36'),
	(241, 73, 21, 0, 0, '2025-08-02 04:01:42'),
	(242, 73, 1, 0, 0, '2025-08-02 04:01:53'),
	(243, 73, 5, 0, 0, '2025-08-02 04:02:00'),
	(244, 73, 102, 0, 0, '2025-08-02 04:02:38'),
	(245, 73, 11, 0, 0, '2025-08-02 04:02:44'),
	(247, 73, 104, 0, 0, '2025-08-02 04:02:57'),
	(248, 73, 103, 0, 0, '2025-08-02 04:03:03'),
	(249, 73, 105, 0, 0, '2025-08-02 04:03:21'),
	(250, 73, 102, 0, 0, '2025-08-02 04:10:11'),
	(251, 73, 11, 0, 0, '2025-08-02 04:10:17'),
	(252, 73, 1, 0, 0, '2025-08-02 04:10:24'),
	(253, 73, 102, 0, 0, '2025-08-02 04:10:56'),
	(254, 73, 11, 0, 0, '2025-08-02 04:11:02'),
	(255, 73, 105, 0, 0, '2025-08-02 04:11:07'),
	(256, 73, 104, 0, 0, '2025-08-02 04:11:13'),
	(257, 73, 103, 0, 0, '2025-08-02 04:11:18'),
	(258, 73, 6, 0, 0, '2025-08-02 04:11:24'),
	(259, 73, 3, 0, 0, '2025-08-02 04:11:30'),
	(260, 73, 3, 0, 0, '2025-08-02 04:13:27'),
	(261, 73, 3, 0, 0, '2025-08-02 04:14:04'),
	(262, 73, 3, 0, 0, '2025-08-02 04:14:24'),
	(263, 73, 24, 0, 0, '2025-08-02 04:14:41'),
	(264, 73, 6, 0, 0, '2025-08-02 04:14:47'),
	(265, 73, 10, 0, 0, '2025-08-02 04:14:54'),
	(266, 73, 2, 0, 0, '2025-08-02 04:14:59'),
	(267, 73, 105, 0, 0, '2025-08-02 04:15:42'),
	(268, 73, 104, 0, 0, '2025-08-02 04:15:48'),
	(269, 73, 105, 0, 0, '2025-08-02 04:15:53'),
	(270, 73, 103, 0, 0, '2025-08-02 04:15:59'),
	(271, 73, 9, 0, 0, '2025-08-02 04:18:41'),
	(272, 77, 4, 0, 0, '2025-08-03 15:44:31'),
	(273, 77, 10, 0, 0, '2025-08-03 15:44:38'),
	(274, 77, 21, 0, 0, '2025-08-03 15:44:48'),
	(275, 77, 1, 0, 0, '2025-08-03 15:44:54'),
	(276, 77, 11, 0, 0, '2025-08-03 15:45:01'),
	(292, 86, 126, 0, 0, '2025-08-07 06:24:48'),
	(293, 86, 12, 0, 0, '2025-08-07 06:25:56'),
	(294, 86, 103, 0, 0, '2025-08-07 06:26:07');

-- Dumping structure for table sahtout_site.server_news
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
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table sahtout_site.server_news: ~17 rows (approximately)
INSERT INTO `server_news` (`id`, `title`, `slug`, `content`, `posted_by`, `post_date`, `image_url`, `is_important`, `category`) VALUES
	(1, 'Server Patch 3.3.5a Applied', 'server-patch-3.3.5a-applied', 'We have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddlWe have successfully updated the server to the latest patch version. All new content is now available! ok hello test after theis ldodldldlddl', 'Admin', '2025-07-24 17:08:35', 'img/newsimg/news1.jpg', 1, 'update'),
	(2, 'Weekly Arena TournamentEr', 'weekly-arena-tournament', 'Sign up now for this week\'s arena tournament! Prize: 1000 gold to winning team.', 'GameMaster', '2025-07-24 17:08:35', 'img/newsimg/news2.jpg', 1, 'event'),
	(3, 'Scheduled Maintenance', 'scheduled-maintenance', 'Server will be down for maintenance on Friday 2am-4am server time.', 'Admin', '2025-07-24 17:08:35', 'img/newsimg/news3.jpg', 1, 'maintenance'),
	(4, 'New Custom Raid Released', 'new-custom-raid-released', 'Try our new custom raid "The Fallen Citadel" - tuned for 25-man groups!', 'Developer', '2025-07-24 17:08:35', 'img/newsimg/news4.jpg', 1, 'update'),
	(18, 'Server is under Test 5', 'hello', 'ok 5 gg', 'TEST10', '2025-08-04 03:08:18', 'img/newsimg/news1.jpg', 0, 'other'),
	(19, 'test6', 'test6 ddddd', 'azdsadsa 5566', 'TEST10', '2025-08-04 03:08:34', 'img/newsimg/news_6893eec171d57.jpg', 0, 'update'),
	(21, 'tryoit', 'olak', '154ds', 'TEST10', '2025-08-05 02:46:15', NULL, 0, 'update'),
	(22, 'markas', 'msdm', 'dzada', 'TEST10', '2025-08-06 08:27:09', NULL, 0, 'update'),
	(25, 'dadza', 'sass', 'dzada', 'TEST10', '2025-08-06 08:28:47', NULL, 0, 'update'),
	(27, 'dadza', 'sassa', 'dzada', 'TEST10', '2025-08-06 08:29:02', NULL, 0, 'update'),
	(29, 'dza', 'dsqdq', 'dsqdq', 'TEST10', '2025-08-06 08:29:19', NULL, 0, 'update'),
	(30, 'fdsfs', 'sqdqq', 'dsqdqd', 'TEST10', '2025-08-06 08:29:24', NULL, 0, 'update'),
	(31, 'ffefe', 'dfds', 'fdsfs', 'TEST10', '2025-08-06 08:29:28', NULL, 0, 'update'),
	(32, 'fezfzfz', 'fezfzf', 'fzefzfzfz', 'TEST10', '2025-08-06 08:29:33', NULL, 0, 'update'),
	(35, 'dsqdq', 'aaadd', 'dsq', 'TEST10', '2025-08-06 08:29:53', NULL, 0, 'update'),
	(36, 'fdsfs', 'fdsfsfsf', 'fdsfsf', 'TEST10', '2025-08-06 08:30:00', 'img/newsimg/news.png', 0, 'update'),
	(40, 'aaaaa', 'aaaaa', 'azdzada', 'TEST10', '2025-08-07 00:00:10', 'img/newsimg/news.png', 0, 'update');

-- Dumping structure for table sahtout_site.shop_items
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
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.shop_items: ~21 rows (approximately)
INSERT INTO `shop_items` (`item_id`, `category`, `name`, `description`, `image`, `point_cost`, `token_cost`, `stock`, `last_updated`, `entry`, `gold_amount`, `level_boost`, `at_login_flags`, `is_item`) VALUES
	(1, 'Service', 'Level Boost 80', 'Instantly boosts your character to level 80.', 'img/shopimg/services/level_boost_80.jpg', 500, 0, 85, '2025-08-03 15:44:54', NULL, 0, 80, 0, 0),
	(2, 'Mount', 'Swift Zhevra', 'A fast and stylish mount for your adventures.', 'img/shopimg/items/swift_zhevra.jpg', 0, 50, 45, '2025-08-02 04:14:59', 37719, 0, NULL, 0, 1),
	(3, 'Pet', 'Proto drake whelp', 'A cute Proto drake pet to follow you.', 'img/shopimg/items/proto-drake-whelp.jpg', 300, 20, 68, '2025-08-02 04:14:24', 44721, 0, NULL, 0, 1),
	(4, 'Gold', '1000 Gold', 'Add 1000 gold to your in-game wallet.', 'img/shopimg/gold/1000_gold.jpg', 200, 0, NULL, '2025-08-06 23:11:20', NULL, 1000, NULL, 0, 0),
	(5, 'Service', 'Faction Change', 'Change faction (Alliance ↔ Horde)+gender+name', 'img/shopimg/services/faction_change.jpg', 600, 0, 19, '2025-08-02 04:35:21', NULL, 0, NULL, 64, 0),
	(6, 'Mount', 'Invincible', 'A legendary flying mount.', 'img/shopimg/items/invincible.jpg', 0, 100, 20, '2025-08-02 04:33:18', 50818, 0, NULL, 0, 1),
	(7, 'Pet', 'Dun Morogh Cub', 'A cute polarbear pet to follow you.', 'img/shopimg/items/dun-morogh-cub.jpg', 250, 10, 57, '2025-08-02 04:01:29', 44970, 0, NULL, 0, 1),
	(8, 'Gold', '5000 Gold', 'Add 5000 gold you are almost Rich', 'img/shopimg/gold/5000_gold.jpg', 300, 500, 30, '2025-08-02 04:39:03', NULL, 5000, NULL, 0, 0),
	(9, 'Gold', '10000 Gold', 'Add 10000 gold you are so rich', 'img/shopimg/gold/10000_gold.jpg', 200, 850, 26, '2025-08-06 21:34:18', NULL, 10000, NULL, 0, 0),
	(10, 'Mount', 'Swift Spectral Tiger', 'A Beautiful Spectral mount', 'img/shopimg/items/swift_spect_tiger.jpg', 250, 100, 19, '2025-08-03 15:44:38', 49284, 0, NULL, 0, 1),
	(11, 'Stuff', 'Heroic sword LK', 'glorenzelg high blade of the silver hand', 'img/shopimg/items/glorenzelg-high-blade-of-the-silver-hand.jpg', 150, 100, 19, '2025-08-03 15:45:17', 50730, 0, NULL, 0, 1),
	(12, 'Service', 'Level Boost 70', 'Instantly boosts your character to level 70.', 'img/shopimg/services/level_boost_70.avif', 300, 0, 49, '2025-08-07 06:25:56', NULL, 0, 70, 0, 0),
	(21, 'Service', 'Character Rename', 'Rename your character in-game.', 'img/shopimg/services/rename.jpg', 100, 0, NULL, '2025-08-06 23:09:48', NULL, 0, NULL, 1, 0),
	(22, 'Service', 'Gender Change', 'Customize Characters +name', 'img/shopimg/services/gender.jpg', 150, 0, NULL, '2025-07-30 00:41:50', NULL, 0, NULL, 8, 0),
	(23, 'Service', 'Race Change', 'Race Change +gender+name', 'img/shopimg/services/race_change.jpg', 150, 100, NULL, '2025-08-02 04:12:33', NULL, 0, NULL, 128, 0),
	(24, 'Mount', 'Ashes of al\'ar', 'Summons phoenix.  This is a flying mount.', 'img/shopimg/items/phnx.jpg', 150, 20, NULL, '2025-08-01 02:47:45', 32458, 0, NULL, 0, 1),
	(102, 'Stuff', 'Bulwark of Azzinoth Shield', 'Bulwark of Azzinoth shield', 'img/shopimg/items/shield.png', 300, 350, NULL, '2025-08-02 04:12:51', 32375, 0, NULL, 0, 1),
	(103, 'Stuff', 'Warglaive of Azzinoth sword', 'Warglaive of Azzinoth SWORD', 'img/shopimg/items/azzinoth_sword1.jpg', 200, 250, NULL, '2025-08-02 04:12:46', 32837, 0, NULL, 0, 1),
	(104, 'Stuff', 'Thunderfury Sword', 'Thunderfury, Blessed Blade of the Windseeker', 'img/shopimg/items/thunderfury2.png', 200, 200, NULL, '2025-08-02 04:12:43', 19019, 0, NULL, 0, 1),
	(105, 'Stuff', 'Onslaught Battle-Helm', 'Onslaught Battle-Helm', 'img/shopimg/items//warrior_helm.png', 150, 100, NULL, '2025-08-02 04:12:27', 30972, 0, NULL, 0, 1),
	(126, 'Mount', 'Kart', NULL, 'img/shopimg/items/shop_item_689425cd4ad6f.jpg', 0, 0, NULL, '2025-08-07 04:04:29', 50818, 0, NULL, 0, 1);

-- Dumping structure for table sahtout_site.site_items
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
  `StatsCount` tinyint unsigned NOT NULL DEFAULT '0',
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

-- Dumping data for table sahtout_site.site_items: ~12 rows (approximately)
INSERT INTO `site_items` (`entry`, `class`, `subclass`, `SoundOverrideSubclass`, `name`, `displayid`, `Quality`, `Flags`, `FlagsExtra`, `BuyCount`, `BuyPrice`, `SellPrice`, `InventoryType`, `AllowableClass`, `AllowableRace`, `ItemLevel`, `RequiredLevel`, `RequiredSkill`, `RequiredSkillRank`, `requiredspell`, `requiredhonorrank`, `RequiredCityRank`, `RequiredReputationFaction`, `RequiredReputationRank`, `maxcount`, `stackable`, `ContainerSlots`, `StatsCount`, `stat_type1`, `stat_value1`, `stat_type2`, `stat_value2`, `stat_type3`, `stat_value3`, `stat_type4`, `stat_value4`, `stat_type5`, `stat_value5`, `stat_type6`, `stat_value6`, `stat_type7`, `stat_value7`, `stat_type8`, `stat_value8`, `stat_type9`, `stat_value9`, `stat_type10`, `stat_value10`, `ScalingStatDistribution`, `ScalingStatValue`, `dmg_min1`, `dmg_max1`, `dmg_type1`, `dmg_min2`, `dmg_max2`, `dmg_type2`, `armor`, `holy_res`, `fire_res`, `nature_res`, `frost_res`, `shadow_res`, `arcane_res`, `delay`, `ammo_type`, `RangedModRange`, `spellid_1`, `spelltrigger_1`, `spellcharges_1`, `spellppmRate_1`, `spellcooldown_1`, `spellcategory_1`, `spellcategorycooldown_1`, `spellid_2`, `spelltrigger_2`, `spellcharges_2`, `spellppmRate_2`, `spellcooldown_2`, `spellcategory_2`, `spellcategorycooldown_2`, `spellid_3`, `spelltrigger_3`, `spellcharges_3`, `spellppmRate_3`, `spellcooldown_3`, `spellcategory_3`, `spellcategorycooldown_3`, `spellid_4`, `spelltrigger_4`, `spellcharges_4`, `spellppmRate_4`, `spellcooldown_4`, `spellcategory_4`, `spellcategorycooldown_4`, `spellid_5`, `spelltrigger_5`, `spellcharges_5`, `spellppmRate_5`, `spellcooldown_5`, `spellcategory_5`, `spellcategorycooldown_5`, `bonding`, `description`, `PageText`, `LanguageID`, `PageMaterial`, `startquest`, `lockid`, `Material`, `sheath`, `RandomProperty`, `RandomSuffix`, `block`, `itemset`, `MaxDurability`, `area`, `Map`, `BagFamily`, `TotemCategory`, `socketColor_1`, `socketContent_1`, `socketColor_2`, `socketContent_2`, `socketColor_3`, `socketContent_3`, `socketBonus`, `GemProperties`, `RequiredDisenchantSkill`, `ArmorDamageModifier`, `duration`, `ItemLimitCategory`, `HolidayId`, `ScriptName`, `DisenchantID`, `FoodType`, `minMoneyLoot`, `maxMoneyLoot`, `flagsCustom`, `VerifiedBuild`) VALUES
	(19019, 2, 7, -1, 'Thunderfury, Blessed Blade of the Windseeker', 30606, 5, 0, 0, 1, 615704, 123140, 13, -1, -1, 80, 60, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 2, 3, 5, 7, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 44, 115, 0, 16, 30, 3, 0, 0, 8, 9, 0, 0, 0, 1900, 0, 0, 21992, 2, 0, 4, -1, 0, -1, 0, 2, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, -20, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(30969, 4, 4, -1, 'Onslaught Gauntlets', 45659, 4, 4096, 0, 1, 0, 0, 10, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 3, 4, 41, 3, 30, 7, 49, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1141, 0, 0, 0, 0, 0, 0, 0, 0, 0, 42094, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 55, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 2902, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(30972, 4, 4, -1, 'Onslaught Battle-Helm', 49684, 4, 4096, 0, 1, 0, 0, 1, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 3, 4, 54, 3, 41, 7, 54, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1483, 0, 0, 0, 0, 0, 0, 0, 0, 0, 39925, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 100, 0, 0, 0, 0, 1, 0, 2, 0, 0, 0, 2927, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(30975, 4, 4, -1, 'Onslaught Breastplate', 45658, 4, 4096, 0, 1, 0, 0, 5, 1, 32767, 146, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 4, 53, 3, 34, 7, 54, 31, 16, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1825, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40680, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 672, 165, 0, 0, 0, 0, 2, 0, 8, 0, 8, 0, 2952, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(32375, 4, 6, -1, 'Bulwark of Azzinoth', 45653, 4, 0, 0, 1, 472692, 94538, 14, -1, -1, 151, 70, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 3, 7, 40, 12, 26, 4, 29, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6336, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40407, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 6, 4, 0, 0, 174, 0, 120, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 300, 0, 0, 0, 0, '', 67, 0, 0, 0, 0, 12340),
	(32458, 15, 5, -1, 'Ashes of Al\'ar', 44872, 4, 0, 0, 1, 1000000, 0, 0, -1, -1, 70, 70, 762, 300, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, 0, 0, -1, 330, 3000, 40192, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Teaches you how to summon this mount.  Can only be summoned in Outland or Northrend.  This is an extremely fast mount.', 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(32837, 2, 7, -1, 'Warglaive of Azzinoth', 45479, 5, 0, 0, 1, 1215564, 243112, 21, 9, 32767, 156, 70, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 3, 3, 22, 7, 29, 31, 21, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 214, 398, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2800, 0, 0, 15810, 1, 0, 0, -1, 0, -1, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 699, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(32838, 2, 7, -1, 'Warglaive of Azzinoth', 45481, 5, 0, 0, 1, 1219873, 243974, 22, 9, 32767, 156, 70, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 3, 3, 21, 7, 28, 32, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 107, 199, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1400, 0, 0, 15810, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, '', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 699, 125, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(37719, 15, 5, -1, 'Swift Zhevra', 49950, 4, 0, 0, 1, 100000, 0, 0, 262143, -1, 40, 40, 762, 150, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, 0, 0, -1, 330, 3000, 49322, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Teaches you how to summon this mount.  This is a very fast mount.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(44721, 15, 2, 0, 'Proto-Drake Whelp', 57246, 1, 0, 0, 1, 10000, 2500, 0, -1, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, 1000, 0, -1, 61350, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 'Teaches you how to summon and dismiss this companion.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 12340),
	(44970, 15, 2, -1, 'Dun Morogh Cub', 57877, 3, 4160, 0, 1, 0, 0, 0, -1, -1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, -1, 0, -1, 62508, 6, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 'Teaches you how to summon this companion.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 4096, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 9767),
	(49284, 15, 5, -1, 'Reins of the Swift Spectral Tiger', 59462, 4, 0, 0, 1, 100000, 0, 0, 262143, 2147483647, 40, 40, 762, 150, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55884, 0, -1, 0, -1, 330, 3000, 42777, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 'Teaches you how to summon this mount.  This is a very fast mount.', 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 10314),
	(50730, 2, 8, -1, 'Glorenzelg, High-Blade of the Silver Hand', 64397, 4, 8, 0, 1, 1663877, 332775, 17, -1, -1, 284, 80, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 4, 4, 198, 7, 222, 32, 122, 37, 114, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 991, 1487, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3600, 0, 0, 0, 1, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 0, 0, 0, 0, -1, 0, -1, 1, 'Paragon of the Light, lead our armies against the coming darkness.', 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 120, 0, 0, 0, 0, 2, 0, 2, 0, 2, 0, 3312, 0, 375, 0, 0, 0, 0, '', 68, 0, 0, 0, 0, 11159),
	(50818, 15, 5, -1, 'Invincible\'s Reins', 58122, 4, 32768, 0, 1, 0, 0, 0, 262143, 32767, 20, 20, 762, 75, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 483, 0, 0, 0, -1, 330, 3000, 72286, 6, 0, 0, -1, 0, 3000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 'Teaches you how to summon this mount.', 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, -1, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 11159);

-- Dumping structure for table sahtout_site.user_currencies
CREATE TABLE IF NOT EXISTS `user_currencies` (
  `account_id` int unsigned NOT NULL,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` int unsigned NOT NULL DEFAULT '0',
  `tokens` int unsigned NOT NULL DEFAULT '0',
  `role` enum('player','moderator','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'player',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  KEY `idx_username` (`username`),
  KEY `fk_user_currencies_avatar` (`avatar`),
  CONSTRAINT `fk_user_currencies_avatar` FOREIGN KEY (`avatar`) REFERENCES `profile_avatars` (`filename`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `user_currencies_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.user_currencies: ~26 rows (approximately)
INSERT INTO `user_currencies` (`account_id`, `username`, `email`, `avatar`, `points`, `tokens`, `role`, `last_updated`) VALUES
	(59, 'BLODY', 'blodyihebsahtout99@gmail.com', '37-0.png', 0, 0, 'admin', '2025-08-07 04:07:57'),
	(61, 'BLODY1', 'blodyihebsahtoust1@gmail.com', '3-1.png', 200, 330, 'player', '2025-08-05 03:40:41'),
	(62, 'TOPADMIN', '15dz2a@dsq.com', '70-1.png', 0, 0, 'player', '2025-08-05 03:40:41'),
	(63, 'AZERT', '15dza@dsq.com', '8-0.png', 0, 0, 'player', '2025-08-05 03:40:41'),
	(64, 'TRUST', '15dza@dsq.com', '37-0.png', 0, 0, 'player', '2025-08-05 03:40:41'),
	(65, 'TOPADMIN2', '12dpzadpkdsqdqdqdapdkadza@gg.f', '70-0.png', 7100, 4250, 'player', '2025-08-05 03:40:41'),
	(66, 'TEST', '12@gg.f', NULL, 0, 0, 'player', '2025-08-05 03:40:41'),
	(67, 'TEST2', 'yihebsahtout@gmail.com', '28-0.png', 1115, 4765, 'player', '2025-08-05 03:40:41'),
	(70, 'ARWA', '15dza@dsq.com', '10-1.png', 9390, 8865, 'player', '2025-08-05 03:40:41'),
	(71, 'TEST5', '15dza@dsq.com', NULL, 24185, 16340, 'player', '2025-08-05 03:49:19'),
	(72, 'TEST6', '12@gg.f', NULL, 6785, 59791, 'player', '2025-08-05 03:40:41'),
	(73, 'TEST7', '15dza@dsq.com', NULL, 1635, 1869, 'moderator', '2025-08-05 03:40:41'),
	(74, 'TEST8', '15dza@dsq.com', NULL, 5000, 5000, 'admin', '2025-08-05 03:40:41'),
	(75, 'BRO', 'test@test.com', NULL, 0, 0, 'moderator', '2025-08-05 03:40:41'),
	(76, 'TEST9', '12@gg.f', NULL, 0, 0, 'player', '2025-08-05 03:40:41'),
	(77, 'TESTER1', '15dza@dsq.com', NULL, 4935, 5890, 'player', '2025-08-05 03:40:41'),
	(78, 'TEST10', '14sazsaa@gg.fok', '2-0.png', 1670, 1467, 'admin', '2025-08-06 22:28:55'),
	(79, 'TRASH1', 'blodyihebsahtouts@gmail.com', '5-0.png', 0, 0, 'player', '2025-08-05 04:39:59'),
	(80, 'TRASH2', 'sahtout@gmail.com', '6-0.png', 0, 0, 'moderator', '2025-08-05 05:05:49'),
	(81, 'TEST12', 'blodyihebsahtout@gmail.com', NULL, 0, 0, 'player', '2025-08-05 03:50:38'),
	(82, 'TEST13', '1s@gmail.com', NULL, 0, 0, 'player', '2025-08-05 03:51:54'),
	(83, 'TEST15', 'blodyihebsahtout2@gmail.com', NULL, 0, 0, 'player', '2025-08-05 03:56:23'),
	(84, 'START', '15dz4a@dsq.com', NULL, 0, 0, 'player', '2025-08-06 05:29:52'),
	(85, 'IHEB1', 'hshsjqjshshshshshshhssjshd156@gg.com', '70-0.png', 525, 2555, 'moderator', '2025-08-07 06:45:29'),
	(86, 'IHEB2', 'blodyiddhebsahtout@gmail.com', '8-0.png', 2025, 25002, 'admin', '2025-08-07 06:29:07'),
	(87, 'IHEB3', 'blodyijdjjhebsahtout@gmail.com', '2-1.png', 0, 0, 'player', '2025-08-07 07:26:43'),
	(88, 'IHEB5', '1s2345@gmail.com', NULL, 0, 0, 'player', '2025-08-07 21:26:36'),
	(89, 'IHEB6', '123sd45@gmail.com', NULL, 0, 0, 'player', '2025-08-07 21:31:59'),
	(97, 'barka', '14sazsaa@gg.fok', NULL, 0, 0, 'player', '2025-08-07 22:02:00'),
	(99, 'BLODY2', '1239s@gmail.fr', NULL, 0, 0, 'player', '2025-08-07 23:27:49'),
	(100, 'BLODY9', '14sasazsaa@gg.fok', NULL, 0, 0, 'player', '2025-08-07 23:45:05'),
	(101, 'TEST155', '1234qsd5@gmail.com', NULL, 0, 0, 'player', '2025-08-07 23:49:13'),
	(102, 'TEST55', '12sdd3s@gmail.fr', NULL, 0, 0, 'player', '2025-08-08 00:01:02'),
	(103, 'BLODY200', '1235s@frad.com', '8-0.png', 0, 0, 'player', '2025-08-08 00:41:11'),
	(104, 'HAMA', '122DZA@GMAIL.COM', NULL, 0, 0, 'player', '2025-08-08 01:38:55'),
	(120, 'MSADA', 'blodysahtoutdza@gmail.com', NULL, 0, 0, 'player', '2025-08-08 04:01:15'),
	(132, 'SADEM1', 'blodysahtout@gmail.com', NULL, 0, 0, 'player', '2025-08-08 07:09:15');

-- Dumping structure for table sahtout_site.website_activity_log
CREATE TABLE IF NOT EXISTS `website_activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `character_name` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` int unsigned NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `website_activity_log_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `acore_auth`.`account` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=411 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table sahtout_site.website_activity_log: ~282 rows (approximately)
INSERT INTO `website_activity_log` (`id`, `account_id`, `character_name`, `action`, `timestamp`, `details`) VALUES
	(35, 59, NULL, 'Avatar Changed', 1753637145, '0-7-4.gif'),
	(36, 59, NULL, 'Email Changed', 1753637153, 'blodyihebsahtou@gmail.com'),
	(46, 59, NULL, 'Avatar Changed', 1753638390, '0-3-2.gif'),
	(47, 61, NULL, 'Password Changed', 1753641433, NULL),
	(48, 61, NULL, 'Email Changed', 1753641437, 'blodyihebsahtout1@gmail.com'),
	(49, 61, NULL, 'Avatar Changed', 1753641486, '0-1-1.gif'),
	(50, 61, NULL, 'Avatar Changed', 1753641580, '0-2-4.gif'),
	(51, 61, NULL, 'Avatar Changed', 1753641583, '0-3-2.gif'),
	(52, 61, NULL, 'Avatar Changed', 1753641585, '0-10-5.gif'),
	(53, 61, NULL, 'Avatar Changed', 1753641590, '1-8-3.gif'),
	(54, 61, NULL, 'Avatar Changed', 1753641596, '1-7-4.gif'),
	(55, 61, NULL, 'Avatar Changed', 1753641598, '1-7-4.gif'),
	(56, 61, NULL, 'Avatar Changed', 1753641599, '1-7-4.gif'),
	(57, 61, NULL, 'Avatar Changed', 1753641602, '1-7-4.gif'),
	(58, 61, NULL, 'Avatar Changed', 1753641608, '1-7-4.gif'),
	(59, 61, NULL, 'Avatar Changed', 1753641610, '1-7-4.gif'),
	(60, 61, NULL, 'Avatar Changed', 1753641611, '1-7-4.gif'),
	(61, 61, NULL, 'Avatar Changed', 1753641612, '1-7-4.gif'),
	(62, 61, NULL, 'Avatar Changed', 1753641612, '1-7-4.gif'),
	(63, 61, NULL, 'Avatar Changed', 1753641620, '0-5-8.gif'),
	(64, 61, NULL, 'Email Changed', 1753641627, 'blodyihebsahtoust1@gmail.com'),
	(65, 61, 'Tras', 'Teleport', 1753642061, 'To shattrath'),
	(66, 61, 'Berda', 'Teleport', 1753642068, 'To dalaran'),
	(67, 59, NULL, 'Avatar Changed', 1753645363, '0-1-1.png'),
	(68, 59, NULL, 'Avatar Changed', 1753645381, '0-5-8.png'),
	(69, 59, NULL, 'Avatar Changed', 1753645868, '1-11-8.png'),
	(70, 59, NULL, 'Avatar Changed', 1753646207, '37-0.png'),
	(71, 62, NULL, 'Avatar Changed', 1753646793, '70-1.png'),
	(72, 62, 'Start', 'Teleport', 1753656220, 'To dalaran'),
	(73, 62, NULL, 'Email Changed', 1753656247, '15dz2a@dsq.com'),
	(74, 63, 'Zarl', 'Teleport', 1753658085, 'To dalaran'),
	(75, 63, NULL, 'Avatar Changed', 1753658099, '8-0.png'),
	(76, 63, 'Dzass', 'Teleport', 1753658304, 'To shattrath'),
	(77, 64, NULL, 'Avatar Changed', 1753670810, '37-0.png'),
	(78, 64, NULL, 'Avatar Changed', 1753670812, '37-0.png'),
	(79, 65, NULL, 'Avatar Changed', 1753749834, '3-1.png'),
	(80, 65, 'Qqre', 'Teleport', 1753754678, 'To shattrath'),
	(81, 65, 'Qqre', 'Purchase Gold', 1753756329, 'Purchased 10000000 gold for character GUID 61'),
	(82, 65, 'Styldq', 'Purchase Gold', 1753756340, 'Purchased 10000000 gold for character GUID 62'),
	(83, 65, 'Qqre', 'Purchase Gold', 1753756412, 'Purchased 10000000 gold for character GUID 61'),
	(84, 65, 'Styldq', 'Purchase Gold', 1753756414, 'Purchased 10000000 gold for character GUID 62'),
	(85, 65, 'Styldq', 'Purchase Gold', 1753756416, 'Purchased 10000000 gold for character GUID 62'),
	(86, 65, 'Styldq', 'Purchase Gold', 1753756419, 'Purchased 10000000 gold for character GUID 62'),
	(87, 65, 'Styldq', 'Purchase Gold', 1753756675, 'Purchased 10000000 gold for character GUID 62'),
	(88, 65, 'Styldq', 'Purchase Gold', 1753756677, 'Purchased 10000000 gold for character GUID 62'),
	(89, 65, 'Styldq', 'Purchase Gold', 1753756679, 'Purchased 10000000 gold for character GUID 62'),
	(90, 65, 'Qqre', 'Purchase Gold', 1753756680, 'Purchased 10000000 gold for character GUID 61'),
	(91, 65, 'Qqre', 'Purchase Gold', 1753756681, 'Purchased 10000000 gold for character GUID 61'),
	(92, 65, 'Qqre', 'Purchase Gold', 1753756682, 'Purchased 10000000 gold for character GUID 61'),
	(93, 65, 'Qqre', 'Purchase Gold', 1753756683, 'Purchased 10000000 gold for character GUID 61'),
	(94, 65, 'Dsq', 'Purchase Gold', 1753757011, 'Purchased 10000000 gold for character GUID 64'),
	(95, 65, 'Dsq', 'Purchase Gold', 1753757013, 'Purchased 10000000 gold for character GUID 64'),
	(96, 65, 'Qqre', 'Purchase Gold', 1753757762, 'Purchased 1000 gold for character GUID 61'),
	(97, 65, 'Cork', 'Purchase Gold', 1753757769, 'Purchased 1000 gold for character GUID 69'),
	(98, 65, 'Qqre', 'Purchase Gold', 1753757832, 'Purchased 10000 gold for character GUID 61'),
	(99, 65, 'Cork', 'Purchase Gold', 1753757835, 'Purchased 5000 gold for character GUID 69'),
	(100, 65, 'Cork', 'Purchase Gold', 1753757914, 'Purchased 10000 gold for character GUID 69'),
	(101, 65, 'Cork', 'Purchase Gold', 1753757930, 'Purchased 5000 gold for character GUID 69'),
	(102, 65, 'Cork', 'Purchase Gold', 1753758020, 'Purchased 5000 gold for character GUID 69'),
	(103, 65, 'Cork', 'Purchase Gold', 1753758070, 'Purchased 1000 gold for character GUID 69'),
	(104, 65, 'Dsq', 'Purchase Gold', 1753758078, 'Purchased 10000 gold for character GUID 64'),
	(105, 65, 'Cork', 'Purchase Gold', 1753758317, 'Purchased 5000 gold for character GUID 69'),
	(106, 65, 'Cork', 'Purchase Gold', 1753758324, 'Purchased 5000 gold for character GUID 69'),
	(107, 65, 'Cork', 'Purchase Gold', 1753758328, 'Purchased 5000 gold for character GUID 69'),
	(108, 65, 'Cork', 'Purchase Gold', 1753758406, 'Purchased 10000 gold for character GUID 69'),
	(109, 65, 'Cork', 'Purchase Gold', 1753758411, 'Purchased 10000 gold for character GUID 69'),
	(110, 65, 'Cork', 'Purchase Gold', 1753758414, 'Purchased 10000 gold for character GUID 69'),
	(111, 65, 'Stlsd', 'Purchase Gold', 1753758613, 'Purchased 10000 gold for character GUID 66'),
	(112, 65, 'Cork', 'Purchase Gold', 1753758617, 'Purchased 5000 gold for character GUID 69'),
	(113, 65, 'Teazsq', 'Purchase Gold', 1753758907, 'Purchased 5000 gold for character GUID 68'),
	(114, 65, 'Cork', 'Purchase Gold', 1753758944, 'Purchased 1000 gold for character GUID 69'),
	(115, 67, 'Max', 'Purchase Gold', 1753759308, 'Purchased 10000 gold for character GUID 74'),
	(116, 67, 'Nezrort', 'Purchase Gold', 1753759403, 'Purchased 1000 gold for character GUID 75'),
	(117, 65, 'Cork', 'Purchase Gold', 1753762429, 'Purchased 1000 gold for character GUID 69'),
	(118, 65, 'Bars', 'Purchase Gold', 1753762555, 'Purchased 10000 gold for character GUID 67'),
	(119, 65, NULL, 'Avatar Changed', 1753763911, '2-0.png'),
	(120, 65, NULL, 'Avatar Changed', 1753763915, '2-0.png'),
	(121, 67, 'Juks', 'Teleport', 1753764524, 'To shattrath'),
	(122, 65, 'Qqre', 'Teleport', 1753764556, 'To shattrath'),
	(123, 65, NULL, 'Avatar Changed', 1753764593, '70-0.png'),
	(124, 65, NULL, 'Avatar Changed', 1753764597, '70-0.png'),
	(125, 65, NULL, 'Avatar Changed', 1753764602, '70-0.png'),
	(126, 65, NULL, 'Email Changed', 1753764614, '12@gg.f'),
	(127, 65, NULL, 'Email Changed', 1753764756, '12@gg.f'),
	(128, 65, NULL, 'Email Changed', 1753764789, '12dpzadpkapdkadza@gg.f'),
	(129, 65, NULL, 'Email Changed', 1753764802, '12dpzadpkdsqdqdqdapdkadza@gg.f'),
	(135, 65, 'Styldq', 'Teleport', 1753765485, 'To dalaran'),
	(139, 67, NULL, 'Avatar Changed', 1753765875, '28-0.png'),
	(140, 67, NULL, 'Avatar Changed', 1753765879, '28-0.png'),
	(143, 70, NULL, 'Avatar Changed', 1753815623, '10-1.png'),
	(144, 70, 'Dzadas', 'Teleport', 1753815732, 'To shattrath'),
	(145, 70, 'Arwa', 'Teleport', 1753815814, 'To shattrath'),
	(146, 70, 'Arwa', 'Purchase Gold', 1753815818, 'Purchased 10000 gold for character GUID 76'),
	(147, 70, 'Arwa', 'Purchase Gold', 1753815996, 'Purchased 5000 gold for character GUID 76'),
	(148, 70, 'Arwa', 'Purchase Gold', 1753815999, 'Purchased 1000 gold for character GUID 76'),
	(204, 71, 'Marka', 'Purchase Gold', 1753848294, 'Purchased 1000 gold for character GUID 86'),
	(205, 71, 'Marka', 'Purchase Character Customization', 1753848303, 'Applied customization (Rename) for character GUID 86 via item Character Rename (ID: 21)'),
	(206, 71, 'Marka', 'Purchase Level Boost', 1753848311, 'Leveled character GUID 86 to level 80 via item Level Boost 80 (ID: 1)'),
	(207, 71, 'Weldo', 'Purchase Character Customization', 1753848328, 'Applied customization (Race Change) for character GUID 87 via item Race Change (ID: 23)'),
	(208, 71, 'Marka', 'Purchase Item', 1753848340, 'Purchased item Moonkin Hatchling (ID: 7, Entry: 30969) sent via mail to character GUID 86'),
	(209, 71, 'Markas', 'Purchase Item', 1753848498, 'Purchased item Swift Zhevra (ID: 2, Entry: 30972) sent via mail to character GUID 86'),
	(210, 71, 'Markas', 'Purchase Gold', 1753848501, 'Purchased 10000 gold for character GUID 86'),
	(211, 71, 'Weldo', 'Purchase Item', 1753848906, 'Purchased item Test (ID: 11, Entry: 30975) sent via mail to character GUID 87'),
	(212, 71, 'Markas', 'Purchase Item', 1753849754, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 33225) sent via mail to character GUID 86'),
	(213, 71, 'Sahtout', 'Purchase Level Boost', 1753849953, 'Leveled character GUID 88 to level 80 via item Level Boost 80 (ID: 1)'),
	(214, 71, 'Sahtout', 'Purchase Gold', 1753849956, 'Purchased 10000 gold for character GUID 88'),
	(215, 71, 'Sahtout', 'Purchase Item', 1753849959, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 33225) sent via mail to character GUID 88'),
	(216, 71, 'Sahtout', 'Purchase Character Customization', 1753849968, 'Applied customization (Race Change) for character GUID 88 via item Race Change (ID: 23)'),
	(217, 71, 'Sahtout', 'Purchase Item', 1753850071, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 88'),
	(218, 71, 'Sahtout', 'Purchase Item', 1753850184, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 18768) sent via mail to character GUID 88'),
	(219, 71, 'Sahtouta', 'Purchase Gold', 1753850404, 'Purchased 5000 gold for character GUID 89'),
	(220, 71, 'Sahtouta', 'Purchase Item', 1753850407, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 18768) sent via mail to character GUID 89'),
	(221, 71, 'Sahtouta', 'Purchase Level Boost', 1753850415, 'Leveled character GUID 89 to level 80 via item Level Boost 80 (ID: 1)'),
	(222, 71, 'Weldo', 'Purchase Item', 1753850883, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 18768) sent via mail to character GUID 87'),
	(223, 71, 'Hum', 'Purchase Item', 1753850910, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 18768) sent via mail to character GUID 90'),
	(224, 71, 'Hum', 'Purchase Gold', 1753850913, 'Purchased 5000 gold for character GUID 90'),
	(225, 71, 'Hum', 'Purchase Level Boost', 1753850917, 'Leveled character GUID 90 to level 80 via item Level Boost 80 (ID: 1)'),
	(226, 71, 'Zatla', 'Purchase Item', 1753851141, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 18768) sent via mail to character GUID 91'),
	(227, 71, 'Sahtout', 'Purchase Item', 1753851621, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 88'),
	(228, 72, 'Saas', 'Purchase Gold', 1754018759, 'Purchased 1000 gold for character GUID 94'),
	(229, 72, 'Junvod', 'Purchase Gold', 1754018930, 'Purchased 10000 gold for character GUID 96'),
	(230, 72, 'Junvod', 'Purchase Gold', 1754018933, 'Purchased 1000 gold for character GUID 96'),
	(231, 72, 'Junvod', 'Purchase Gold', 1754018936, 'Purchased 5000 gold for character GUID 96'),
	(232, 72, 'Junvod', 'Purchase Item', 1754018938, 'Purchased item Ashes of al\'ar (ID: 24, Entry: 32458) sent via mail to character GUID 96'),
	(233, 72, 'Junvod', 'Purchase Item', 1754018942, 'Purchased item Invincible (ID: 6, Entry: 50818) sent via mail to character GUID 96'),
	(234, 72, 'Junvod', 'Purchase Item', 1754018947, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 96'),
	(235, 72, 'Junvod', 'Purchase Item', 1754018950, 'Purchased item Swift Zhevra (ID: 2, Entry: 37719) sent via mail to character GUID 96'),
	(236, 72, 'Junvod', 'Purchase Item', 1754018954, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 96'),
	(237, 72, 'Junvod', 'Purchase Item', 1754018959, 'Purchased item Dun Morogh Cub (ID: 7, Entry: 44970) sent via mail to character GUID 96'),
	(238, 72, 'Junvod', 'Purchase Character Customization', 1754018964, 'Applied customization (Rename) for character GUID 96 via item Character Rename (ID: 21)'),
	(239, 72, 'Junvod', 'Purchase Character Customization', 1754018968, 'Applied customization (Faction Change) for character GUID 96 via item Faction Change (ID: 5)'),
	(240, 72, 'Junvod', 'Purchase Character Customization', 1754018972, 'Applied customization (Customize) for character GUID 96 via item Gender Change (ID: 22)'),
	(241, 72, 'Junvod', 'Purchase Level Boost', 1754018976, 'Leveled character GUID 96 to level 80 via item Level Boost 80 (ID: 1)'),
	(242, 72, 'Junvod', 'Purchase Character Customization', 1754018981, 'Applied customization (Race Change) for character GUID 96 via item Race Change (ID: 23)'),
	(243, 72, 'Junvod', 'Purchase Item', 1754018986, 'Purchased item Bulwark of Azzinoth Shield (ID: 102, Entry: 32375) sent via mail to character GUID 96'),
	(244, 72, 'Junvod', 'Purchase Item', 1754018993, 'Purchased item Heroic sword LK (ID: 11, Entry: 50730) sent via mail to character GUID 96'),
	(245, 72, 'Junvod', 'Purchase Item', 1754018998, 'Purchased item Thunderfury Sword (ID: 104, Entry: 19019) sent via mail to character GUID 96'),
	(246, 72, 'Junvod', 'Purchase Item', 1754019004, 'Purchased item Warglaive of Azzinoth sword (ID: 103, Entry: 32837) sent via mail to character GUID 96'),
	(247, 72, 'Junvod', 'Teleport', 1754019094, 'To dalaran'),
	(248, 72, 'Junvod', 'Teleport', 1754020036, 'To dalaran'),
	(249, 73, 'Somea', 'Purchase Gold', 1754105970, 'Purchased 1000 gold for character GUID 97'),
	(250, 73, 'Dzadass', 'Purchase Gold', 1754105973, 'Purchased 1000 gold for character GUID 98'),
	(251, 73, 'Somea', 'Purchase Gold', 1754105977, 'Purchased 10000 gold for character GUID 97'),
	(252, 73, 'Somea', 'Purchase Gold', 1754105980, 'Purchased 5000 gold for character GUID 97'),
	(253, 73, 'Somea', 'Purchase Gold', 1754106818, 'Purchased 1000 gold for character GUID 97'),
	(254, 73, 'Dzadass', 'Purchase Gold', 1754106828, 'Purchased 1000 gold for character GUID 98'),
	(255, 73, 'Somea', 'Purchase Gold', 1754106835, 'Purchased 1000 gold for character GUID 97'),
	(256, 73, 'Somea', 'Purchase Gold', 1754106838, 'Purchased 1000 gold for character GUID 97'),
	(257, 73, 'Somea', 'Purchase Gold', 1754106841, 'Purchased 1000 gold for character GUID 97'),
	(258, 73, 'Dzadass', 'Purchase Gold', 1754106879, 'Purchased 1000 gold for character GUID 98'),
	(259, 73, 'Dzadass', 'Purchase Gold', 1754106890, 'Purchased 1000 gold for character GUID 98'),
	(260, 73, 'Dzadass', 'Purchase Gold', 1754106893, 'Purchased 1000 gold for character GUID 98'),
	(261, 73, 'Dzadass', 'Purchase Gold', 1754106896, 'Purchased 1000 gold for character GUID 98'),
	(262, 73, 'Dzadass', 'Purchase Gold', 1754106900, 'Purchased 10000 gold for character GUID 98'),
	(263, 73, 'Dzadass', 'Purchase Gold', 1754106902, 'Purchased 10000 gold for character GUID 98'),
	(264, 73, 'Dzadass', 'Purchase Gold', 1754106905, 'Purchased 10000 gold for character GUID 98'),
	(265, 73, 'Dzadass', 'Purchase Gold', 1754107142, 'Purchased 1000 gold for character GUID 98'),
	(266, 73, 'Dzadass', 'Purchase Gold', 1754107145, 'Purchased 1000 gold for character GUID 98'),
	(267, 73, 'Dzadass', 'Purchase Gold', 1754107148, 'Purchased 1000 gold for character GUID 98'),
	(268, 73, 'Dzadass', 'Purchase Gold', 1754107151, 'Purchased 10000 gold for character GUID 98'),
	(269, 73, 'Dzadass', 'Purchase Gold', 1754107156, 'Purchased 1000 gold for character GUID 98'),
	(270, 73, 'Dzadass', 'Purchase Gold', 1754107159, 'Purchased 1000 gold for character GUID 98'),
	(271, 73, 'Dzadass', 'Purchase Gold', 1754107162, 'Purchased 1000 gold for character GUID 98'),
	(272, 73, 'Dzadass', 'Purchase Gold', 1754107213, 'Purchased 1000 gold for character GUID 98'),
	(273, 73, 'Somea', 'Purchase Gold', 1754107220, 'Purchased 1000 gold for character GUID 97'),
	(274, 73, 'Somea', 'Purchase Gold', 1754107231, 'Purchased 1000 gold for character GUID 97'),
	(275, 73, 'Dzadass', 'Purchase Gold', 1754107251, 'Purchased 10000 gold for character GUID 98'),
	(276, 73, 'Dzadass', 'Purchase Item', 1754107267, 'Purchased item Ashes of al\'ar (ID: 24, Entry: 32458) sent via mail to character GUID 98'),
	(277, 73, 'Dzadass', 'Purchase Item', 1754107273, 'Purchased item Invincible (ID: 6, Entry: 50818) sent via mail to character GUID 98'),
	(278, 73, 'Dzadass', 'Purchase Item', 1754107278, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 98'),
	(279, 73, 'Dzadass', 'Purchase Item', 1754107284, 'Purchased item Swift Zhevra (ID: 2, Entry: 37719) sent via mail to character GUID 98'),
	(280, 73, 'Dzadass', 'Purchase Item', 1754107289, 'Purchased item Dun Morogh Cub (ID: 7, Entry: 44970) sent via mail to character GUID 98'),
	(281, 73, 'Dzadass', 'Purchase Item', 1754107296, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 98'),
	(282, 73, 'Somea', 'Purchase Character Customization', 1754107302, 'Applied customization (Rename) for character GUID 97 via item Character Rename (ID: 21)'),
	(283, 73, 'Dzadass', 'Purchase Level Boost', 1754107313, 'Leveled character GUID 98 to level 80 via item Level Boost 80 (ID: 1)'),
	(284, 73, 'Somea', 'Purchase Character Customization', 1754107320, 'Applied customization (Faction Change) for character GUID 97 via item Faction Change (ID: 5)'),
	(285, 73, 'Ward', 'Purchase Item', 1754107358, 'Purchased item Bulwark of Azzinoth Shield (ID: 102, Entry: 32375) sent via mail to character GUID 99'),
	(286, 73, 'Ward', 'Purchase Item', 1754107364, 'Purchased item Heroic sword LK (ID: 11, Entry: 50730) sent via mail to character GUID 99'),
	(287, 73, 'Ward', 'Purchase Item', 1754107377, 'Purchased item Thunderfury Sword (ID: 104, Entry: 19019) sent via mail to character GUID 99'),
	(288, 73, 'Ward', 'Purchase Item', 1754107383, 'Purchased item Warglaive of Azzinoth sword (ID: 103, Entry: 32837) sent via mail to character GUID 99'),
	(289, 73, 'Ward', 'Purchase Item', 1754107401, 'Purchased item Onslaught Battle-Helm (ID: 105, Entry: 30972) sent via mail to character GUID 99'),
	(290, 73, 'Dzadass', 'Teleport', 1754107629, 'To shattrath'),
	(291, 73, 'Ward', 'Teleport', 1754107634, 'To dalaran'),
	(292, 73, 'Dzadass', 'Purchase Item', 1754107811, 'Purchased item Bulwark of Azzinoth Shield (ID: 102, Entry: 32375) sent via mail to character GUID 98'),
	(293, 73, 'Somea', 'Purchase Item', 1754107817, 'Purchased item Heroic sword LK (ID: 11, Entry: 50730) sent via mail to character GUID 97'),
	(294, 73, 'Ward', 'Purchase Level Boost', 1754107824, 'Leveled character GUID 99 to level 80 via item Level Boost 80 (ID: 1)'),
	(295, 73, 'Dzadass', 'Purchase Item', 1754107856, 'Purchased item Bulwark of Azzinoth Shield (ID: 102, Entry: 32375) sent via mail to character GUID 98'),
	(296, 73, 'Dzadass', 'Purchase Item', 1754107862, 'Purchased item Heroic sword LK (ID: 11, Entry: 50730) sent via mail to character GUID 98'),
	(297, 73, 'Ward', 'Purchase Item', 1754107867, 'Purchased item Onslaught Battle-Helm (ID: 105, Entry: 30972) sent via mail to character GUID 99'),
	(298, 73, 'Ward', 'Purchase Item', 1754107873, 'Purchased item Thunderfury Sword (ID: 104, Entry: 19019) sent via mail to character GUID 99'),
	(299, 73, 'Ward', 'Purchase Item', 1754107878, 'Purchased item Warglaive of Azzinoth sword (ID: 103, Entry: 32837) sent via mail to character GUID 99'),
	(300, 73, 'Ward', 'Purchase Item', 1754107884, 'Purchased item Invincible (ID: 6, Entry: 50818) sent via mail to character GUID 99'),
	(301, 73, 'Ward', 'Purchase Item', 1754107890, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 99'),
	(302, 73, 'Ward', 'Purchase Item', 1754108007, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 99'),
	(303, 73, 'Ward', 'Purchase Item', 1754108044, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 99'),
	(304, 73, 'Dzadass', 'Purchase Item', 1754108064, 'Purchased item Proto drake whelp (ID: 3, Entry: 44721) sent via mail to character GUID 98'),
	(305, 73, 'Dzadass', 'Purchase Item', 1754108081, 'Purchased item Ashes of al\'ar (ID: 24, Entry: 32458) sent via mail to character GUID 98'),
	(306, 73, 'Dzadass', 'Purchase Item', 1754108087, 'Purchased item Invincible (ID: 6, Entry: 50818) sent via mail to character GUID 98'),
	(307, 73, 'Dzadass', 'Purchase Item', 1754108094, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 98'),
	(308, 73, 'Dzadass', 'Purchase Item', 1754108099, 'Purchased item Swift Zhevra (ID: 2, Entry: 37719) sent via mail to character GUID 98'),
	(309, 73, 'Ward', 'Purchase Item', 1754108142, 'Purchased item Onslaught Battle-Helm (ID: 105, Entry: 30972) sent via mail to character GUID 99'),
	(310, 73, 'Dzadass', 'Purchase Item', 1754108148, 'Purchased item Thunderfury Sword (ID: 104, Entry: 19019) sent via mail to character GUID 98'),
	(311, 73, 'Dzadass', 'Purchase Item', 1754108153, 'Purchased item Onslaught Battle-Helm (ID: 105, Entry: 30972) sent via mail to character GUID 98'),
	(312, 73, 'Dzadass', 'Purchase Item', 1754108159, 'Purchased item Warglaive of Azzinoth sword (ID: 103, Entry: 32837) sent via mail to character GUID 98'),
	(313, 73, 'Ward', 'Purchase Gold', 1754108321, 'Purchased 10000 gold for character GUID 99'),
	(314, 75, NULL, 'Email Changed', 1754111510, 'test@test.com'),
	(315, 77, 'Marka', 'Purchase Gold', 1754235871, 'Purchased 1000 gold for character GUID 100'),
	(316, 77, 'Marka', 'Purchase Item', 1754235878, 'Purchased item Swift Spectral Tiger (ID: 10, Entry: 49284) sent via mail to character GUID 100'),
	(317, 77, 'Marka', 'Purchase Character Customization', 1754235888, 'Applied customization (Rename) for character GUID 100 via item Character Rename (ID: 21)'),
	(318, 77, 'Marka', 'Purchase Level Boost', 1754235894, 'Leveled character GUID 100 to level 80 via item Level Boost 80 (ID: 1)'),
	(319, 77, 'Marka', 'Purchase Item', 1754235901, 'Purchased item Heroic sword LK (ID: 11, Entry: 50730) sent via mail to character GUID 100'),
	(320, 77, 'Marker', 'Teleport', 1754236043, 'To shattrath'),
	(321, 78, NULL, 'Avatar Changed', 1754326422, '10-1.png'),
	(322, 78, NULL, 'Avatar Changed', 1754326425, '10-1.png'),
	(323, 78, NULL, 'Email Changed', 1754361880, '12sazsaa@gg.fok'),
	(324, 79, NULL, 'Email Changed', 1754366302, 'blodyihebsahtout@gmail.com'),
	(325, 80, NULL, 'Email Changed', 1754366584, 'sahtout@gmail.com'),
	(326, 79, NULL, 'Email Changed', 1754366618, 'blodyihebsahtout1@gmail.com'),
	(327, 79, NULL, 'Email Changed', 1754366627, 'blodyihebsahtouts1@gmail.com'),
	(328, 79, NULL, 'Email Changed', 1754366802, 'blodyihebsahtouts2@gmail.com'),
	(329, 79, NULL, 'Email Changed', 1754366817, 'blodyihebsahtouts2@gmail.com'),
	(330, 79, NULL, 'Email Changed', 1754366827, 'blodyihebsahtouts2@gmail.com'),
	(331, 79, NULL, 'Email Changed', 1754366834, 'blodyihebsahtouts2@gmail.com'),
	(332, 79, NULL, 'Email Changed', 1754367166, 'blodyihebsahtouts2@gmail.com'),
	(333, 79, NULL, 'Email Changed', 1754367295, 'blodyihebsahtouts2@gmail.com'),
	(334, 79, NULL, 'Email Changed', 1754367522, 'blodyihebsahtouts2@gmail.com'),
	(335, 79, NULL, 'Email Changed', 1754367721, 'blodyihebsahtouts2@gmail.com'),
	(336, 79, NULL, 'Email Changed', 1754367857, 'blodyihebsahtouts2@gmail.com'),
	(337, 79, NULL, 'Email Changed', 1754367862, 'blodyihebsahtouts2@gmail.com'),
	(338, 79, NULL, 'Email Changed', 1754367871, 'blodyihebsahtouts1@gmail.com'),
	(339, 79, NULL, 'Email Changed', 1754367890, 'blodyihebsahtouts2@gmail.com'),
	(340, 79, NULL, 'Email Changed', 1754367892, 'blodyihebsahtouts2@gmail.com'),
	(341, 79, NULL, 'Email Changed', 1754367894, 'blodyihebsahtouts2@gmail.com'),
	(342, 79, NULL, 'Email Changed', 1754368056, 'blodyihebsahtouts2@gmail.com'),
	(343, 79, NULL, 'Email Changed', 1754368086, 'blodyihebsahtouts2@gmail.com'),
	(344, 79, NULL, 'Avatar Changed', 1754368100, 'Default avatar'),
	(345, 79, NULL, 'Avatar Changed', 1754368275, '5-0.png'),
	(346, 79, NULL, 'Email Changed', 1754368286, 'blodyihebsahtouts2@gmail.com'),
	(347, 79, NULL, 'Email Changed', 1754368291, 'blodyihebsahtouts2@gmail.com'),
	(348, 79, NULL, 'Email Changed', 1754368305, 'blodyihebsahtouts23@gmail.com'),
	(349, 79, NULL, 'Email Changed', 1754368780, 'blodyihebsahtouts23@gmail.com'),
	(350, 79, NULL, 'Email Changed', 1754368786, 'blodyihebsahtouts23@gmail.com'),
	(351, 79, NULL, 'Email Changed', 1754368791, 'blodyihebsahtouts3@gmail.com'),
	(352, 79, NULL, 'Email Changed', 1754368799, 'blodyihebsahtouts@gmail.com'),
	(353, 80, NULL, 'Email Changed', 1754368863, 'sahtout@gmail.com'),
	(354, 80, NULL, 'Avatar Changed', 1754368883, '6-0.png'),
	(355, 79, NULL, 'Password Changed', 1754368930, NULL),
	(356, 79, NULL, 'Password Changed', 1754368938, NULL),
	(357, 78, NULL, 'Avatar Changed', 1754369030, '1-1.png'),
	(358, 78, NULL, 'Avatar Changed', 1754466381, '32-0.png'),
	(359, 78, NULL, 'Email Changed', 1754466480, '13sazsaa@gg.fok'),
	(360, 78, NULL, 'Email Changed', 1754466497, '14sazsaa@gg.fok'),
	(361, 78, NULL, 'Password Changed', 1754466536, NULL),
	(362, 78, NULL, 'Password Changed', 1754466543, NULL),
	(363, 78, 'Songa', 'Purchase Gold', 1754516925, 'Purchased 1023 gold for character GUID 110'),
	(364, 78, 'Songa', 'Purchase Item', 1754516961, 'Purchased item mounter (ID: 119, Entry: 44721) sent via mail to character GUID 110'),
	(365, 78, 'Songa', 'Purchase Character Customization', 1754516984, 'Applied customization (First Login) for character GUID 110 via item first login (ID: 123)'),
	(366, 78, 'Songa', 'Purchase Item', 1754516992, 'Purchased item stuffer (ID: 122, Entry: 32837) sent via mail to character GUID 110'),
	(367, 78, 'Songa', 'Teleport', 1754517046, 'To dalaran'),
	(368, 78, 'Songa', 'Purchase Character Customization', 1754517253, 'Applied customization (Faction Change) for character GUID 110 via item first login (ID: 123)'),
	(369, 78, 'Songa', 'Purchase Character Customization', 1754517309, 'Applied customization (Race Change) for character GUID 110 via item first login (ID: 123)'),
	(370, 78, 'Songa', 'Purchase Level Boost', 1754517403, 'Leveled character GUID 110 to level 255 via item level 255 (ID: 124)'),
	(371, 78, 'Songa', 'Purchase Character Customization', 1754517476, 'Applied customization (Reset Spells) for character GUID 110 via item first login (ID: 123)'),
	(372, 78, 'Songa', 'Purchase Character Customization', 1754517529, 'Applied customization (Customize) for character GUID 110 via item first login (ID: 123)'),
	(373, 78, 'Songa', 'Purchase Character Customization', 1754517575, 'Applied customization (Reset Spells) for character GUID 110 via item first login (ID: 123)'),
	(374, 78, 'Songa', 'Purchase Character Customization', 1754517712, 'Applied customization (Customize) for character GUID 110 via item first login (ID: 123)'),
	(375, 78, 'Songa', 'Purchase Character Customization', 1754517781, 'Applied customization (Reset Spells) for character GUID 110 via item first login (ID: 123)'),
	(376, 78, 'Songa', 'Purchase Character Customization', 1754518026, 'Applied customization (Reset Talents) for character GUID 110 via item first login (ID: 123)'),
	(377, 78, 'Songa', 'Purchase Character Customization', 1754518080, 'Applied customization (Rename) for character GUID 110 via item first login (ID: 123)'),
	(378, 78, NULL, 'Avatar Changed', 1754519335, '2-0.png'),
	(379, 85, NULL, 'Email Changed', 1754537701, '156@gg.com'),
	(380, 85, NULL, 'Email Changed', 1754540999, 'hshsjqjshshshshshshhssjshd156@gg.com'),
	(381, 85, NULL, 'Avatar Changed', 1754541547, '5-1.png'),
	(382, 85, 'Thedso', 'Teleport', 1754544455, 'To dalaran'),
	(383, 85, 'Thedso', 'Teleport', 1754546296, 'To shattrath'),
	(384, 85, 'Thedso', 'Teleport', 1754546331, 'To dalaran'),
	(385, 85, 'Thedso', 'Teleport', 1754546339, 'To shattrath'),
	(386, 85, 'Thedso', 'Teleport', 1754546343, 'To shattrath'),
	(387, 85, 'Thedso', 'Teleport', 1754546369, 'To dalaran'),
	(388, 85, 'Thedso', 'Teleport', 1754546412, 'To dalaran'),
	(389, 85, 'Thedso', 'Teleport', 1754546728, 'To shattrath'),
	(390, 85, 'Thedso', 'Teleport', 1754546763, 'To dalaran'),
	(391, 85, 'Thedso', 'Teleport', 1754547111, 'To dalaran'),
	(392, 86, 'Mas', 'Teleport', 1754547616, 'To dalaran'),
	(393, 86, 'Kasqs', 'Teleport', 1754547795, 'To shattrath'),
	(394, 86, 'Kasqs', 'Purchase Item', 1754547888, 'Purchased item Kart (ID: 126, Entry: 50818) sent via mail to character GUID 114'),
	(395, 86, 'Mas', 'Purchase Level Boost', 1754547956, 'Leveled character GUID 113 to level 70 via item Level Boost 70 (ID: 12)'),
	(396, 86, 'Kasqs', 'Purchase Item', 1754547967, 'Purchased item Warglaive of Azzinoth sword (ID: 103, Entry: 32837) sent via mail to character GUID 114'),
	(397, 86, NULL, 'Avatar Changed', 1754548147, '8-0.png'),
	(398, 85, NULL, 'Avatar Changed', 1754549129, '70-0.png'),
	(399, 85, 'Thedso', 'Teleport', 1754549143, 'To dalaran'),
	(400, 87, NULL, 'Avatar Changed', 1754550685, '2-1.png'),
	(401, 88, NULL, 'Email Changed', 1754601996, '1s2345@gmail.com'),
	(402, 99, 'Dsapm', 'Teleport', 1754609116, 'To dalaran'),
	(403, 99, NULL, 'Email Changed', 1754609269, '1239s@gmail.fr'),
	(404, 103, NULL, 'Email Changed', 1754613666, '1235s@frad.com'),
	(405, 103, NULL, 'Avatar Changed', 1754613671, '8-0.png'),
	(406, 103, NULL, 'Password Changed', 1754613700, NULL);

-- Dumping structure for trigger sahtout_site.before_site_items_insert
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
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
        SET NEW.`StatsCount` = (SELECT `StatsCount` FROM `acore_world`.`item_template` WHERE `entry` = NEW.`entry`);
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
