alter table ps_attachment_lang modify column name varchar(128);


alter table ps_product ADD COLUMN module_version varchar(15);
alter table ps_product ADD COLUMN dolibarr_min varchar(6);
alter table ps_product ADD COLUMN dolibarr_min_status int(1);
alter table ps_product ADD COLUMN dolibarr_max varchar(6);
alter table ps_product ADD COLUMN dolibarr_max_status int(1);
alter table ps_product ADD COLUMN dolibarr_core_include int(1);
alter table ps_product ADD COLUMN dolibarr_disable_info varchar(255);


-- Remove the Home category for modules that are lower than a given version
-- To find modules:
SELECT cp.id_category, cp.id_product from ps_product as p, ps_category_product as cp 
WHERE cp.id_category=1 AND p.id_product = cp.id_product AND (p.dolibarr_max IS NULL OR p.dolibarr_max < 18.0);

-- To select 
SELECT * FROM ps_category_product as T WHERE EXISTS (
SELECT 1 from ps_product as p, ps_category_product as cp 
WHERE cp.id_category=1 AND p.id_product = cp.id_product AND (p.dolibarr_max IS NULL OR p.dolibarr_max < 18.0)
AND cp.id_product = T.id_product AND cp.id_category = T.id_category
)

-- To delete
--DELETE FROM ps_category_product as T WHERE EXISTS (
--SELECT 1 from ps_product as p, ps_category_product as cp 
--WHERE cp.id_category=1 AND p.id_product = cp.id_product AND (p.dolibarr_max IS NULL OR p.dolibarr_max < 18.0)
--AND cp.id_product = T.id_product AND cp.id_category = T.id_category
--)
