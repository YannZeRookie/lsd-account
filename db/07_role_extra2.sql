-- We need an additional field to store the (different) in-game username that a user might have in a Section

ALTER TABLE lsd_roles ADD COLUMN `extra2` varchar(255) default null;
