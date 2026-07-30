ALTER TABLE `shop_items`
    ADD COLUMN `is_set` tinyint unsigned NOT NULL DEFAULT '0' AFTER `is_item`,
    ADD COLUMN `itemset_id` int unsigned DEFAULT NULL AFTER `is_set`,
    ADD KEY `idx_itemset_id` (`itemset_id`),
    ADD CONSTRAINT `chk_is_set` CHECK ((`is_set` in (0,1))),
    ADD CONSTRAINT `chk_shop_items_category` CHECK ((`category` in ('Mount','Pet','Gold','Service','Stuff','Set')));
