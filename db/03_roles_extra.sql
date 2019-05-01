-- OK, so it was not a good idea to use 'data' as a field name,
-- it makes Active Record screw up :-(
-- Let's rename it into 'extra'
-- When role = 'officier' => extra = section tag
-- When role = 'membre'   => extra = section tag
-- When role = 'adherant' => extra = subscription year
ALTER TABLE lsd_roles
CHANGE COLUMN data `extra` varchar(255) default NULL;
