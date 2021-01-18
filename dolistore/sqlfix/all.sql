alter table ps_attachment_lang modify column name varchar(128);


alter table ps_product ADD COLUMN module_version varchar(15);
alter table ps_product ADD COLUMN dolibarr_min varchar(6);
alter table ps_product ADD COLUMN dolibarr_min_status int(1);
alter table ps_product ADD COLUMN dolibarr_max varchar(6);
alter table ps_product ADD COLUMN dolibarr_max_status int(1);
alter table ps_product ADD COLUMN dolibarr_core_include int(1);
alter table ps_product ADD COLUMN dolibarr_disable_info varchar(255);
