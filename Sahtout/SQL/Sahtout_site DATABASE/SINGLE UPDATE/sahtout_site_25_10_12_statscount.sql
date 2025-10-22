--Dropped custom_item_template not needed
DROP TABLE IF EXISTS `custom_item_template`;

--removed StatsCount
ALTER TABLE `site_items`
DROP COLUMN `StatsCount`;

--recreating TRIGGER without StatsCount
DROP TRIGGER IF EXISTS `before_insert_site_items`;

DELIMITER $$
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