ALTER TABLE ezpolicy_limitation ADD INDEX policy_id ( policy_id );
ALTER TABLE ezworkflow_event ADD INDEX wid_version_placement ( workflow_id , version , placement );
ALTER TABLE ezuser_accountkey ADD INDEX hash_key ( hash_key );