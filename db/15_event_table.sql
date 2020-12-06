-- Event table
DROP TABLE IF EXISTS `lsd_events`;
CREATE TABLE IF NOT EXISTS `lsd_events` (
  `event_id` int(10) NOT NULL auto_increment,
  `section_tag` varchar(16) NOT NULL,
  `date_time` datetime NOT NULL,
  `author_discord_id` varchar(255) NOT NULL,
  `author_discord_tag` varchar(255) NOT NULL,
  `description` text,
  `participants` text,
  PRIMARY KEY( `event_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
