-- Invitations table
-- See https://docs.google.com/presentation/d/1D-VxOKeIiG_MG7XovrdiMOfuyfsmu8F4OHSGYbusmWI/edit for the specs

DROP TABLE IF EXISTS `lsd_invitations`;
CREATE TABLE IF NOT EXISTS `lsd_invitations` (
  `id` int(10) NOT NULL auto_increment,
  `created_on` int(10) DEFAULT NULL,
  `expiration` int(10) DEFAULT 7,   -- Number of days until the invitation expires

  `user_id` int(10) DEFAULT NULL,   -- Will be NULL most of the time, unless we find him in the lsd_users table

  `discord_id` varchar(255) DEFAULT NULL,
  `discord_username` varchar(255) DEFAULT NULL,

  -- Invited by:
  `by_discord_id` varchar(255) DEFAULT NULL,
  `by_discord_username` varchar(255) DEFAULT NULL,

  PRIMARY KEY( `id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
