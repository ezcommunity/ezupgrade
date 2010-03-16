SET storage_engine=INNODB;

UPDATE ezsite_data SET value='4.0.1' WHERE name='ezpublish-version';
UPDATE ezsite_data SET value='5' WHERE name='ezpublish-release';
DELETE FROM ezuser_setting where user_id not in (SELECT contentobject_id FROM ezuser);
DELETE FROM ezcontentclass_classgroup WHERE NOT EXISTS (SELECT * FROM ezcontentclass c WHERE c.id=contentclass_id AND c.version=contentclass_version);