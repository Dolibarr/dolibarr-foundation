# Add canadian provinces

UPDATE `ps_country` SET `contains_states`='1' WHERE `id_country`='4';

INSERT INTO `ps_state` (`id_country`, `id_zone`, `name`, `iso_code`, `tax_behavior`, `active`) VALUES 
(4,2,'Ontario','ON',0,1),
(4,2,'Quebec','QC',0,1),
(4,2,'Nova Scotia','NS',0,1),
(4,2,'New Brunswick','NB',0,1),
(4,2,'Manitoba','MB',0,1),
(4,2,'British Columbia','BC',0,1),
(4,2,'Prince Edward Island','PE',0,1),
(4,2,'Saskatchewan','SK',0,1),
(4,2,'Alberta','AB',0,1),
(4,2,'Newfoundland and Labrador','NL',0,1);
