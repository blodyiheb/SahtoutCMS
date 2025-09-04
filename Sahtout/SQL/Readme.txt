sahtout_site: the website database

Run the other SQL files :example 
-----acore_auth_sahtout_site.sql------ 
means thats inside the acore_auth database



⚠️ Important Database Migration Notice ⚠️

OPTION 1 
The new sahtout_site SQL file will recreate the database and all tables.
That means it will delete your old structure.

To avoid losing your data:

1.Export/backup your current sahtout_site database.

2.Run the new SQL file to install the updated database structure.

3.Manually re-insert or import your old data into the new database.

OPTION 2 create this tables
failed_logins Table
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

reset_attempts Table
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
