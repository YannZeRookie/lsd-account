-- kill_list table
DROP TABLE IF EXISTS `lsd_kill_list`;
CREATE TABLE IF NOT EXISTS `lsd_kill_list` (
  `id` int(10) NOT NULL auto_increment,
  `section_tag` varchar(16) NOT NULL,
  `enemy_name` varchar(255) NOT NULL,
  `enemy_description` text,
  PRIMARY KEY( `id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
