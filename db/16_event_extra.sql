-- We need an additional field to store the event title in the event table

ALTER TABLE lsd_events ADD COLUMN `title` varchar(255) default null;
