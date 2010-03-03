ALTER TABLE ezorder_item CHANGE vat_value vat_value FLOAT NOT NULL default 0;

CREATE TABLE ezurlalias_ml_incr (
  id int(11) NOT NULL auto_increment,
  PRIMARY KEY  (id)
);