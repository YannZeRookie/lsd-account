-- Sample user (Totor0, id=3)

DELETE FROM `lsd_users` WHERE id=3;
INSERT INTO `lsd_users` VALUES (3,1553099266,'233116506332856320','TotorO',NULL,NULL,NULL,'Totor0@lsd.com','Coucou ç moi le plus beau',0);

DELETE FROM `lsd_roles` WHERE user_id=3;
INSERT INTO `lsd_roles` VALUES (null,3,'scorpion',NULL);
INSERT INTO `lsd_roles` VALUES (null,3,'conseiller',NULL);
INSERT INTO `lsd_roles` VALUES (null,3,'tresorier',NULL);
INSERT INTO `lsd_roles` VALUES (null,3,'officier','MMO');
INSERT INTO `lsd_roles` VALUES (null,3,'officier','ARMA');
INSERT INTO `lsd_roles` VALUES (null,3,'membre','FO76');
INSERT INTO `lsd_roles` VALUES (null,3,'cm',NULL);
INSERT INTO `lsd_roles` VALUES (null,3,'adherant','2018');
INSERT INTO `lsd_roles` VALUES (null,3,'adherant','2019');

