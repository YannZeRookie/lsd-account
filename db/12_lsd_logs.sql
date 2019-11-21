-- Logs table

DROP TABLE IF EXISTS `lsd_logs`;
CREATE TABLE IF NOT EXISTS `lsd_logs` (
  `id` int(10) NOT NULL auto_increment,
  `created_on` int(10) DEFAULT NULL,
  `user_id` int(10) NOT NULL,
  `target_id` int(10) NOT NULL,
  `action` varchar(32) DEFAULT '',
  `old_values` TEXT DEFAULT '',
  `new_values` TEXT DEFAULT '',

  PRIMARY KEY( `id` ),
  KEY( `user_id` ),
  KEY( `target_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
