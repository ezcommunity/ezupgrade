SET storage_engine=INNODB;

UPDATE ezsite_data SET value='4.0.0' WHERE name='ezpublish-version';
UPDATE ezsite_data SET value='5' WHERE name='ezpublish-release';
DELETE FROM ezuser_setting where user_id not in (SELECT contentobject_id FROM ezuser);
DELETE FROM ezcontentclass_classgroup WHERE NOT EXISTS (SELECT * FROM ezcontentclass c WHERE c.id=contentclass_id AND c.version=contentclass_version);
ALTER TABLE ezcontent_language ADD INDEX ezcontent_language_name(name);
ALTER TABLE ezcontentobject ADD INDEX ezcontentobject_owner(owner_id);
ALTER TABLE ezcontentobject ADD UNIQUE INDEX ezcontentobject_remote_id(remote_id);