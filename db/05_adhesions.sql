-- Adhesions table

DROP TABLE IF EXISTS `lsd_adhesions`;
CREATE TABLE IF NOT EXISTS `lsd_adhesions` (
  `id` int(10) NOT NULL auto_increment,
  `user_id` int(10) NULL,
  `name` varchar(255),
  `firstname` varchar(255),
  `dob` varchar(32),
  `address` varchar(255),
  `telephone` varchar(32),
  `cotisation` varchar(32),
  `amount` decimal(8,2),
  `created_on` int(10),
  PRIMARY KEY( `id` ),
  KEY(`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
