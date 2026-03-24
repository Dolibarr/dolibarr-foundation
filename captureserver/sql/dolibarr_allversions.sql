ALTER TABLE llx_captureserver_captureserver ADD COLUMN versiondolibarr VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN versionblockedlog VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registerid VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registeremail VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN previousrowid INTEGER;
ALTER TABLE llx_captureserver_captureserver ADD COLUMN previoussignature VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN lastrowid INTEGER;
ALTER TABLE llx_captureserver_captureserver ADD COLUMN lastsignature VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registername VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registerprofid VARCHAR(128);

ALTER TABLE llx_captureserver_captureserver ADD COLUMN country_code VARCHAR(8) after versionblockedlog;

ALTER TABLE llx_captureserver_captureserver ADD COLUMN datesys VARCHAR(32);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN previousdatecreation VARCHAR(32) after previoussignature;
ALTER TABLE llx_captureserver_captureserver ADD COLUMN lastdatecreation VARCHAR(32) after lastsignature;

ALTER TABLE llx_captureserver_captureserver ADD INDEX idx_captureserver_caputreserver(registerid);

ALTER TABLE llx_captureserver_captureserver MODIFY COLUMN comment varchar(1024) NULL;
