# Was in v1.3.6.0 but missing in v1.4.17

UPDATE `ps_country` SET `contains_states`='1' WHERE `id_country`='44';

INSERT INTO `ps_state` (`id_country`, `id_zone`, `name`, `iso_code`, `tax_behavior`, `active`) VALUES 
(44,2,'Buenos Aires','BA',0,1),
(44,2,'Other','OT',0,1);
