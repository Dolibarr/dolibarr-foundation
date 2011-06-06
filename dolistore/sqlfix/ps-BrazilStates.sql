# Was in v1.3.6.0 but missing in v1.4.17

UPDATE `ps_country` SET `contains_states`='1' WHERE `id_country`='58';

INSERT INTO `ps_state` (`id_country`, `id_zone`, `name`, `iso_code`, `tax_behavior`, `active`) VALUES 
(58,2,'Acre','AC',0,1),
(58,2,'Alagoas','AL',0,1),
(58,2,'Amapá','AP',0,1),
(58,2,'Amazonas','AM',0,1),
(58,2,'Bahia','BA',0,1),
(58,2,'Ceará','CE',0,1),
(58,2,'Distrito Federal','DF',0,1),
(58,2,'Espírito Santo','ES',0,1),
(58,2,'Goiás','GO',0,1),
(58,2,'Maranhão','MA',0,1),
(58,2,'Mato Grosso','MT',0,1),
(58,2,'Mato Grosso do Sul','MS',0,1),
(58,2,'Minas Gerais','MG',0,1),
(58,2,'Pará','PA',0,1),
(58,2,'Paraíba','PB',0,1),
(58,2,'Paraná','PR',0,1),
(58,2,'Pernambuco','PE',0,1),
(58,2,'Piauí','PI',0,1),
(58,2,'Rio de Janeiro','RJ',0,1),
(58,2,'Rio Grande do Norte','RN',0,1),
(58,2,'Rio Grande do Sul','RS',0,1),
(58,2,'Rondônia','RO',0,1),
(58,2,'Roraima','RR',0,1),
(58,2,'Santa Catarina','SC',0,1),
(58,2,'São Paulo','SP',0,1),
(58,2,'Sergipe','SE',0,1),
(58,2,'Tocantins','TO',0,1);

