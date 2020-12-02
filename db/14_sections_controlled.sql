-- Sections: Discord Role and Controlled submission

ALTER TABLE lsd_section
ADD COLUMN `discord_role` varchar(255) NOT NULL DEFAULT '',
ADD COLUMN `welcome` text NOT NULL,
ADD COLUMN `controlled` tinyint(4) NOT NULL DEFAULT '0'; -- 1 if candidates are controlled


-- Fill data
UPDATE lsd_section SET discord_role='Dual-Universe', controlled=1 WHERE tag='DU';
UPDATE lsd_section SET discord_role='Star Citizen', controlled=1 WHERE tag='SC';

