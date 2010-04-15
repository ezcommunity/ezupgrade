CREATE TABLE ezurlwildcard (
  id int(11) NOT NULL auto_increment,
  source_url longtext NOT NULL,
  destination_url longtext NOT NULL,
  type int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);
ALTER TABLE ezurlalias_ml ADD alias_redirects int(11) NOT NULL default 1;

ALTER TABLE ezcontent_language ADD INDEX ezcontent_language_name(name);

ALTER TABLE ezcontentobject ADD INDEX ezcontentobject_owner(owner_id);

ALTER TABLE ezcontentobject ADD UNIQUE INDEX ezcontentobject_remote_id(remote_id);

