/*For mounts and pets*/
UPDATE item_template
SET spellcharges_1 = 0
WHERE description LIKE '%summon this mount%'
  AND spellcharges_1 = -1;

/*For mounts and pets*/
UPDATE item_template
SET spellcharges_1 = 0
WHERE (description LIKE '%summon this companion%'
       OR description LIKE '%summon and dismiss this companion%')
  AND spellcharges_1 = -1;
