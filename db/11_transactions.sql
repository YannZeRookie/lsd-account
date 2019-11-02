-- PayPal (IPN) transactions table

DROP TABLE IF EXISTS `lsd_transactions`;
CREATE TABLE IF NOT EXISTS `lsd_transactions` (
  `id` int(10) NOT NULL auto_increment,
  `adhesion_id` int(10) NULL,
  `ipn_status` varchar(255),  -- VERIFIED or INVALID
  -- IPN fields (selection):
  txn_id varchar(255) default null,
  mc_gross decimal(10,2) default 0,
  mc_currency varchar(8) default null,
  payer_id varchar(255) default null,
  payment_date varchar(255) default null,
  payment_status varchar(255) default null,
  first_name varchar(255) default null,
  last_name varchar(255) default null,
  payer_email varchar(255) default null,
  receiver_email varchar(255) default null,
  verify_sign varchar(255) default null,
  item_name varchar(255) default null,
  residence_country varchar(8) default null,
  ipn_track_id varchar(255) default null,

  PRIMARY KEY( `id` ),
  KEY( `adhesion_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
