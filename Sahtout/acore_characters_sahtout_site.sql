-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.5 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.10.0.7000
-- --------------------------------------------------------
ALTER TABLE `mail` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for procedure acore_characters.SendStoreItem
DELIMITER //
CREATE PROCEDURE `SendStoreItem`(
    IN p_character_guid INT UNSIGNED,
    IN p_item_id INT UNSIGNED
)
BEGIN
    DECLARE v_item_guid INT UNSIGNED;
    DECLARE v_mail_id INT UNSIGNED;

    -- Validate character_guid
    IF NOT EXISTS (SELECT guid FROM acore_characters.characters WHERE guid = p_character_guid) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid character GUID';
    END IF;

    -- Validate item_id
    IF NOT EXISTS (SELECT entry FROM acore_world.item_template WHERE entry = p_item_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid item ID';
    END IF;

    -- Start transaction
    START TRANSACTION;

    -- Generate unique item_guid
    SELECT MAX(guid) + 1 INTO v_item_guid FROM acore_characters.item_instance;
    IF v_item_guid IS NULL THEN
        SET v_item_guid = 1; -- Fallback if table is empty
    END IF;

    -- Insert into item_instance
    INSERT INTO acore_characters.item_instance (guid, itemEntry, owner_guid, count, enchantments)
    VALUES (v_item_guid, p_item_id, p_character_guid, 1, '0 0 0 0 0 0 0 0 0 0 0 0 0 0 0');

    -- Insert into mail (omit id for auto-increment)
    INSERT INTO acore_characters.mail (messageType, stationery, mailTemplateId, sender, receiver, subject, body, has_items, expire_time, deliver_time, money, cod, checked)
    VALUES (0, 41, 0, 0, p_character_guid, 'Store Purchase', 'Thank you for your purchase!', 1, UNIX_TIMESTAMP() + 30*24*3600, UNIX_TIMESTAMP(), 0, 0, 0);

    -- Get the mail_id
    SET v_mail_id = LAST_INSERT_ID();

    -- Insert into mail_items
    INSERT INTO acore_characters.mail_items (mail_id, item_guid, receiver)
    VALUES (v_mail_id, v_item_guid, p_character_guid);

    -- Commit transaction
    COMMIT;
END//
DELIMITER ;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
