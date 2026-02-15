ALTER TABLE llx_captureserver_captureserver ADD COLUMN registerid VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registeremail VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN previousrowid INTEGER;
ALTER TABLE llx_captureserver_captureserver ADD COLUMN previoussignature VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN lastrowid INTEGER;
ALTER TABLE llx_captureserver_captureserver ADD COLUMN lastsignature VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registername VARCHAR(128);
ALTER TABLE llx_captureserver_captureserver ADD COLUMN registerprofid VARCHAR(128);
