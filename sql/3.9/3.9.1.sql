UPDATE ezsite_data SET value='3.9.1' WHERE name='ezpublish-version';
UPDATE ezsite_data SET value='7' WHERE name='ezpublish-release';

-- extend length of 'serialized_name_list'
ALTER TABLE ezcontentclass CHANGE COLUMN serialized_name_list serialized_name_list longtext default NULL;
ALTER TABLE ezcontentclass_attribute CHANGE COLUMN serialized_name_list serialized_name_list longtext NOT NULL;