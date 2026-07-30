-- --------------------------------------------------------
-- Hôte:                         192.168.1.24
-- Version du serveur:           8.0.44-0ubuntu0.24.04.2 - (Ubuntu)
-- SE du serveur:                Linux
-- HeidiSQL Version:             12.13.0.7147
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

-- Listage de la structure de procédure acore_characters. SendStoreItem
DELIMITER //
CREATE PROCEDURE `SendStoreItem`(
    IN p_character_guid INT UNSIGNED,
    IN p_item_id INT UNSIGNED
)
BEGIN
    DECLARE v_item_guid INT UNSIGNED;
    DECLARE v_mail_id INT UNSIGNED;
    DECLARE v_charges VARCHAR(50);
    DECLARE v_maxdurability INT;
    DECLARE v_item_name VARCHAR(255);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Validate character_guid
    IF NOT EXISTS (SELECT 1 FROM characters WHERE guid = p_character_guid) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid character GUID';
    END IF;

    -- Get item details from world database
    SELECT 
        CONCAT_WS(' ',
            IFNULL(spellcharges_1, 0),
            IFNULL(spellcharges_2, 0),
            IFNULL(spellcharges_3, 0),
            IFNULL(spellcharges_4, 0),
            IFNULL(spellcharges_5, 0)
        ) as charges,
        IFNULL(MaxDurability, 0) as maxdurability,
        IFNULL(name, 'Unknown Item') as item_name
    INTO v_charges, v_maxdurability, v_item_name
    FROM acore_world.item_template
    WHERE entry = p_item_id;

    -- Validate item exists
    IF v_item_name IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid item ID';
    END IF;

    -- Set default charges if none found
    IF v_charges IS NULL OR v_charges = '' OR v_charges = '0 0 0 0 0' THEN
        -- Check if this is a mount/learning item (spellcharges_1 = -1 typically)
        SELECT 
            CASE 
                WHEN spellcharges_1 = -1 AND spellid_1 > 0 THEN CONCAT(spellid_1, ' 0 0 0 0')
                ELSE '0 0 0 0 0'
            END INTO v_charges
        FROM acore_world.item_template
        WHERE entry = p_item_id;
    END IF;

    START TRANSACTION;

    -- Generate unique item_guid with locking
    SELECT IFNULL(MAX(guid), 0) + 1 INTO v_item_guid 
    FROM item_instance FOR UPDATE;

    -- Insert into item_instance with proper durability
    INSERT INTO item_instance (
        guid, 
        itemEntry, 
        owner_guid, 
        `count`, 
        charges, 
        flags, 
        enchantments, 
        randomPropertyId, 
        durability, 
        playedTime, 
        text
    ) VALUES (
        v_item_guid, 
        p_item_id, 
        p_character_guid, 
        1, 
        v_charges, 
        0,
        '0 0 0 0 0 0 0 0 0 0', 
        0, 
        v_maxdurability,  -- Item starts at max durability
        0, 
        ''
    );

    -- Insert into mail
    INSERT INTO mail (
        messageType, 
        stationery, 
        mailTemplateId, 
        sender, 
        receiver, 
        subject, 
        body, 
        has_items, 
        expire_time, 
        deliver_time, 
        money, 
        cod, 
        checked
    ) VALUES (
        0, 
        41, 
        0, 
        0, 
        p_character_guid,
        CONCAT('Store Purchase: ', v_item_name), 
        CONCAT('Thank you for your purchase of ', v_item_name, '!'),
        1, 
        UNIX_TIMESTAMP() + (30 * 24 * 3600), 
        UNIX_TIMESTAMP(), 
        0, 
        0, 
        0
    );

    SET v_mail_id = LAST_INSERT_ID();

    -- Insert into mail_items
    INSERT INTO mail_items (
        mail_id, 
        item_guid, 
        receiver
    ) VALUES (
        v_mail_id, 
        v_item_guid, 
        p_character_guid
    );

    COMMIT;
END//
DELIMITER ;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
