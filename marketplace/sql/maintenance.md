# Pour basculer les produits du vendeur dolibarr id=X vers dolibarr id=Y, ID vendeur qui était Z sous dolistore:

X=1707 Y=1287 Z=11070

update llx_product_fournisseur_price set fk_soc = Y where fk_soc = X and ref_fourn like 'cZd%';
update llx_commande set fk_soc = Y where fk_soc = X and module_source = 'marketplace';
update llx_facture set fk_soc = Y where fk_soc = X and module_source = 'marketplace';

update ll_societe_account set fk_soc = Y where fk_soc = X and site = 'dolibarr_website';


# Pour corriger une version min
UPDATE llx_product_extrafields SET marketplace_max_version = 'V23' WHERE marketplace_min_version = 'V23';

