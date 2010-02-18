ALTER TABLE ezworkflow_event ADD COLUMN data_text5 LONGTEXT;

ALTER TABLE ezrss_export ADD COLUMN node_id INT NULL;
ALTER TABLE ezrss_export_item ADD COLUMN category VARCHAR( 255 ) NULL;

ALTER TABLE ezbinaryfile MODIFY COLUMN mime_type VARCHAR(255) NOT NULL;

ALTER TABLE ezuservisit ADD COLUMN login_count int(11) NOT NULL default 0;
ALTER TABLE ezuservisit ADD INDEX ezuservisit_co_visit_count( current_visit_timestamp, login_count );

ALTER TABLE ezforgot_password ADD INDEX ezforgot_password_user(user_id);

CREATE TABLE ezcobj_state (
  default_language_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  identifier varchar(45) NOT NULL default '',
  language_mask int(11) NOT NULL default '0',
  priority int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY ezcobj_state_identifier (group_id,identifier),
  KEY ezcobj_state_lmask (language_mask),
  KEY ezcobj_state_priority (priority)
);
CREATE TABLE ezcobj_state_group (
  default_language_id int(11) NOT NULL default '0',
  id int(11) NOT NULL auto_increment,
  identifier varchar(45) NOT NULL default '',
  language_mask int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY ezcobj_state_group_identifier (identifier),
  KEY ezcobj_state_group_lmask (language_mask)
);

CREATE TABLE ezcobj_state_group_language (
  contentobject_state_group_id int(11) NOT NULL default '0',
  description longtext NOT NULL,
  language_id int(11) NOT NULL default '0',
  name varchar(45) NOT NULL default '',
  PRIMARY KEY  (contentobject_state_group_id,language_id)
);

CREATE TABLE ezcobj_state_language (
  contentobject_state_id int(11) NOT NULL default '0',
  description longtext NOT NULL,
  language_id int(11) NOT NULL default '0',
  name varchar(45) NOT NULL default '',
  PRIMARY KEY  (contentobject_state_id,language_id)
);

CREATE TABLE ezcobj_state_link (
  contentobject_id int(11) NOT NULL default '0',
  contentobject_state_id int(11) NOT NULL default '0',
  PRIMARY KEY  (contentobject_id,contentobject_state_id)
);

ALTER TABLE ezsession ADD COLUMN user_hash VARCHAR( 32 ) NOT NULL default '';

ALTER TABLE ezpending_actions ADD COLUMN created int(11) DEFAULT NULL;

ALTER TABLE ezpending_actions ADD INDEX ezpending_actions_created ( created );