-- We need additional fields to handle the review process of new users

ALTER TABLE lsd_users
ADD COLUMN `submited_on` int(10) default 0,
ADD COLUMN `reviewed_on` int(10) default 0,
ADD COLUMN `reviewer_id` int(10) default null,
ADD COLUMN `review` mediumtext DEFAULT "";

UPDATE lsd_users SET submited_on=created_on, reviewed_on=created_on;

