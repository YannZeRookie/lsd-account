-- Tracking of database migration scripts

DROP TABLE IF EXISTS `lsd_dbpatches`;
CREATE TABLE IF NOT EXISTS `lsd_dbpatches` (
  `id` int(10) NOT NULL auto_increment,
  `filename` varchar(255),
  `applied_at` int(10) NOT NULL default 0,
  `applied_by` int(10) NOT NULL,
  PRIMARY KEY( `id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO lsd_dbpatches SET filename='00_initial_tables.sql', applied_at=unix_timestamp('2019-08-05'), applied_by=2;
INSERT INTO lsd_dbpatches SET filename='01_roles_data.sql', applied_at=unix_timestamp('2019-08-05'), applied_by=2;
INSERT INTO lsd_dbpatches SET filename='02_sample_user03.sql', applied_at=unix_timestamp('2019-03-04'), applied_by=2;
INSERT INTO lsd_dbpatches SET filename='03_roles_extra.sql', applied_at=unix_timestamp('2019-08-05'), applied_by=2;
INSERT INTO lsd_dbpatches SET filename='04_adherent.sql', applied_at=unix_timestamp('2019-08-05'), applied_by=2;
INSERT INTO lsd_dbpatches SET filename='05_adhesions.sql', applied_at=unix_timestamp('2019-08-05'), applied_by=2;
