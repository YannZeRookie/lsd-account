-- Change section_id and sub_year into a generic 'data' field
-- When role = 'officier' => data = section tag
-- When role = 'membre'   => data = section tag
-- When role = 'adherent' => data = subscription year
ALTER TABLE lsd_roles
CHANGE COLUMN section_id `data` varchar(255) default NULL,
DROP COLUMN `sub_year`;


-- Section definition
-- This is the way it is currently defined for VBulletin ("VB"). We'll get rid of several columns when we drop VB.
DROP TABLE IF EXISTS `lsd_section`;
CREATE TABLE `lsd_section` (
  `tag` varchar(16) NOT NULL,                 -- Section ID: a short string in the [A-Z0-9]+ format
  `name` varchar(255) NOT NULL,               -- Human readable name
  `officer_group` int(11) NOT NULL,           -- VB Group ID of officers
  `member_group` int(11) NOT NULL,            -- VB Group ID of members
  `archived` tinyint(4) NOT NULL DEFAULT '0', -- 1 if Section was retired
  `category` int(11) NOT NULL,                -- VB ID of the forum category
  `forums` varchar(255) NOT NULL,             -- VB IDs of sub-forums, as a comma-separated list
  `officer_forum` int(11) NOT NULL,           -- VB ID of the officers' forum
  `order` int(11) NOT NULL DEFAULT '0',       -- Sorting integer
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Sections information';

INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('ARMA','Arma 3 - DayZ',23,22,0,6785,'23170,23171,23172,23173,23174',23175,5);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('DU','Dual Universe',46,47,0,68010,'68011,68012,68014,68015',68013,2);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('ELITE','ELITE: Dangerous',33,32,1,27649,'27653,27650,27651',27652,2);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('EvE','EvE Online',16,15,1,83,'95,96,1802,97,1185',98,1);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('FO76','Fallout 76',48,49,0,68241,'68242',68240,1);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('HOTS','Heroes of the Storm',37,38,0,51944,'51945,51949',51950,6);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('JDM','Jeux du moment',45,17,0,23867,'23868,66607,59379,55640',59375,7);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('MMO','Section MMO',43,44,0,66052,'67546',62478,3);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('PS2','PlanetSide 2',12,14,1,15,'21,22,24,102,12349,1682,20',19,2);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('SC','Star Citizen',21,20,0,3302,'3304,3303,59377',3305,4);
INSERT INTO `lsd_section` (`tag`, `name`, `officer_group`, `member_group`, `archived`, `category`, `forums`, `officer_forum`, `order`) VALUES ('WF','Warframe',36,35,1,50982,'50983,50984',59653,2);

