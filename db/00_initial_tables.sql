-- Unique short-lived connection key
DROP TABLE IF EXISTS `lsd_login`;
CREATE TABLE IF NOT EXISTS `lsd_login` (
  `id` int(10) NOT NULL auto_increment,
  `login_key` varchar(255),
  `created_on` int(10),
  `discord_id` varchar(255),
  `discord_username` varchar(255),
  `discord_discriminator` varchar(255),
  `discord_avatar` varchar(255),
  PRIMARY KEY( `id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Users database
DROP TABLE IF EXISTS `lsd_users`;
CREATE TABLE IF NOT EXISTS `lsd_users` (
  `id` int(10) NOT NULL auto_increment,
  `created_on` int(10),
  `discord_id` varchar(255),
  `discord_username` varchar(255),
  `discord_discriminator` varchar(255),
  `discord_avatar` varchar(255),
  `vb_id` int(10) NULL,
  `email` varchar(255) NULL,
  `testimony` text,
  `minor` tinyint default 0,
  PRIMARY KEY( `id` ),
  KEY(`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Users Roles
DROP TABLE IF EXISTS `lsd_roles`;
CREATE TABLE IF NOT EXISTS `lsd_roles` (
  `id` int(10) NOT NULL auto_increment,
  `user_id` int(10) NULL,
  `role` varchar(255),        -- Can be 'visiteur', 'invite', 'scorpion', 'membre', 'officier', 'conseiller', 'secretaire', 'tresorier', 'president', 'admin', 'cm', 'adherant'
  `section_id` int(10) NULL,  -- Section (when it makes sense, i.e. for 'membre' and 'officier')
  `sub_year` int(10) NULL,     -- Adherant subscription year
  PRIMARY KEY( `id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- To do: change section_id and sub_year into a generic 'data' field.
--        how do we track the Officer who validated a Scorpion?
