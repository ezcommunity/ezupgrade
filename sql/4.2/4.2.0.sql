SET storage_engine=InnoDB;
UPDATE ezsite_data SET value='4.2.0' WHERE name='ezpublish-version';
UPDATE ezsite_data SET value='1' WHERE name='ezpublish-release';

ALTER TABLE ezpolicy_limitation ADD INDEX policy_id ( policy_id );
ALTER TABLE ezworkflow_event ADD INDEX wid_version_placement ( workflow_id , version , placement );
ALTER TABLE ezuser_accountkey ADD INDEX hash_key ( hash_key );
