<?php
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/categories.lib.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Load translation files required by the page
$langs->loadLangs(array('commande', 'propal', 'bills', 'other', 'products', 'marketplace@marketplace'));

$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$type = GETPOST('type', 'intcomma');
$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : '';
$mode = 'commande';

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
if (!$sortfield) {
	$sortfield = "s.datec";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

restrictedArea($user, 'fournisseur', 0, '', '', '');

// Initialize array of search criteria
$search_id = GETPOST('search_id');
$search_name = GETPOST('search_name');
$search_alias = GETPOST('search_alias');
$search_ref_ext = GETPOST('search_ref_ext');
$search_logins = GETPOST('search_logins');
$search_country = GETPOST('search_country');
$search_numberOfProducts = GETPOST('search_numberOfProducts');
$search_numberOfPaidSells = GETPOST('search_numberOfPaidSells');
$search_qtyRefunds = GETPOST('search_qtyRefunds');
$search_sumRefunds = GETPOST('search_sumRefunds');
$search_totalOfSellsDone = GETPOST('search_totalOfSellsDone');
$search_totalValidatedSells = GETPOST('search_totalValidatedSells');
$search_remainedAmountInOneMonth = GETPOST('search_remainedAmountInOneMonth');
$search_remainedAmountToday = GETPOST('search_remainedAmountToday');
$search_discounts = GETPOST('search_discounts');
$search_totalPaymentsDone = GETPOST('search_totalPaymentsDone');
$search_numberOfSupplierInvoices = GETPOST('search_numberOfSupplierInvoices');

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_id = '';
	$search_name = '';
	$search_alias = '';
	$search_ref_ext = '';
	$search_logins = '';
	$search_country = '';
	$search_numberOfProducts = '';
	$search_numberOfPaidSells = '';
	$search_qtyRefunds = '';
	$search_sumRefunds = '';
	$search_totalOfSellsDone = '';
	$search_totalValidatedSells = '';
	$search_remainedAmountInOneMonth = '';
	$search_remainedAmountToday = '';
	$search_discounts = '';
	$search_totalPaymentsDone = '';
	$search_numberOfSupplierInvoices = '';
}

/*
 * View
 */

$form = new Form($db);
$tmpproduct = new Product($db);

llxHeader("", $langs->trans("MarketplaceArea"), '', '', 0, 0, '', '', '', 'mod-marketplace page-index');

$param = ''; // Initialize $param by default

if ($search_id > 0) {
	$param .= "&search_id=" . ((int) $search_id);
}
if (!empty($search_name)) {
	$param .= "&search_name=" . urlencode($search_name);
}
if (!empty($search_alias)) {
	$param .= "&search_alias=" . urlencode($search_alias);
}
if (!empty($search_ref_ext)) {
	$param .= "&search_ref_ext=" . urlencode($search_ref_ext);
}
if (!empty($search_logins)) {
	$param .= "&search_logins=" . urlencode($search_logins);
}
if (!empty($search_country)) {
	$param .= "&search_country=" . urlencode($search_country);
}
if (!empty($search_numberOfProducts)) {
	$param .= "&search_numberOfProducts=" . ((int) $search_numberOfProducts);
}
if (!empty($search_numberOfPaidSells)) {
	$param .= "&search_numberOfPaidSells=" . ((int) $search_numberOfPaidSells);
}
if (!empty($search_qtyRefunds)) {
	$param .= "&search_qtyRefunds=" . ((float) $search_qtyRefunds);
}
if (!empty($search_sumRefunds)) {
	$param .= "&search_sumRefunds=" . ((float) $search_sumRefunds);
}
if (!empty($search_totalOfSellsDone)) {
	$param .= "&search_totalOfSellsDone=" . ((float) $search_totalOfSellsDone);
}
if (!empty($search_totalValidatedSells)) {
	$param .= "&search_totalValidatedSells=" . ((float) $search_totalValidatedSells);
}
if (!empty($search_remainedAmountInOneMonth)) {
	$param .= "&search_remainedAmountInOneMonth=" . ((float) $search_remainedAmountInOneMonth);
}
if (!empty($search_remainedAmountToday)) {
	$param .= "&search_remainedAmountToday=" . ((float) $search_remainedAmountToday);
}
if (!empty($search_discounts)) {
	$param .= "&search_discounts=" . ((float) $search_discounts);
}
if (!empty($search_totalPaymentsDone)) {
	$param .= "&search_totalPaymentsDone=" . ((float) $search_totalPaymentsDone);
}
if (!empty($search_numberOfSupplierInvoices)) {
	$param .= "&search_numberOfSupplierInvoices=" . ((int) $search_numberOfSupplierInvoices);
}

print load_fiche_titre($langs->trans("MarketplaceArea"), '', 'fa-store');

$title = $langs->trans("statisticsListMarketplace");

$h = 0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/marketplaceindex.php';
$head[$h][1] = $langs->trans("invoicesStatsMarketplace");
$head[$h][2] = 'invoicesStatsMarketplace';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/customerstats.php';
$head[$h][1] = $langs->trans("customersStatsMarketplace");
$head[$h][2] = 'customersStatsMarketplace';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/productstats.php';
$head[$h][1] = $langs->trans("productsStatsMarketplace");
$head[$h][2] = 'productsStatsMarketplace';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/salesstats.php';
$head[$h][1] = $langs->trans("ListOfSalesMarketplace");
$head[$h][2] = 'ListOfSalesMarketplace';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/custom/marketplace/supplierstats.php';
$head[$h][1] = $langs->trans("SupplierStatsMarketplace");
$head[$h][2] = 'SupplierStatsMarketplace';
$h++;

print dol_get_fiche_head($head, 'SupplierStatsMarketplace', '', -1);

if (!empty(GETPOST('datestart')) || !empty(GETPOST('dateend'))) {

    $date_start = GETPOST('datestart');
    $date_end = GETPOST('dateend');

    $date_start_timestamp = dol_mktime(0, 0, 0, GETPOSTINT('datestartmonth'), GETPOSTINT('datestartday'), GETPOSTINT('datestartyear'));
    $date_end_timestamp = dol_mktime(0, 0, 0, GETPOSTINT('dateendmonth'), GETPOSTINT('dateendday'), GETPOSTINT('dateendyear'));

    // If only the end date is provided, set the start date to the earliest possible value
    if (empty($date_start) && !empty($date_end)) {
        $date_start_timestamp = 0; // Unix epoch time for 1970-01-01
    }
    // If only the start date is provided, set the end date to the current date
    elseif (!empty($date_start) && empty($date_end)) {
        $date_end_timestamp = dol_now(); // Current timestamp
    }

    $error = 0;
    $error_messages = array();

    if ($date_end_timestamp < $date_start_timestamp) {
        $error++;
        $error_messages[] = "endDateMustBeGreater";
    }

    $filter = '';
    if ($error == 0 && !empty($date_end_timestamp)) {
        $filter = " AND c.date_commande BETWEEN '".$db->idate($date_start_timestamp)."' AND '".$db->idate($date_end_timestamp)."'";
    }

    $filterOrders = '';
    if ($error == 0 && !empty($date_end_timestamp)) {
        $filterOrders = " AND c.date_commande BETWEEN '".$db->idate($date_start_timestamp)."' AND '".$db->idate($date_end_timestamp)."'";
    }

    $filterInvoices = '';
    if ($error == 0 && !empty($date_end_timestamp)) {
        $filterInvoices = " AND f.datef BETWEEN '".$db->idate($date_start_timestamp)."' AND '".$db->idate($date_end_timestamp)."'";
    }
}


$supplierListSql = "SELECT s.rowid, s.nom as name, s.name_alias, s.ref_ext, s.datec as date_creation, country.label as country_label ";
$supplierListSql .= "FROM llx_societe as s ";
$supplierListSql .= "LEFT JOIN llx_societe as s2 ON s.parent = s2.rowid ";
$supplierListSql .= "LEFT JOIN llx_c_country as country on (country.rowid = s.fk_pays) ";
$supplierListSql .= "LEFT JOIN llx_c_typent as typent on (typent.id = s.fk_typent) ";
$supplierListSql .= "LEFT JOIN llx_c_effectif as staff on (staff.id = s.fk_effectif) ";
$supplierListSql .= "LEFT JOIN llx_c_departements as state on (state.rowid = s.fk_departement) ";
$supplierListSql .= "LEFT JOIN llx_c_regions as region on (region.code_region = state.fk_region) ";
$supplierListSql .= "LEFT JOIN llx_c_stcomm as st ON s.fk_stcomm = st.id ";
$supplierListSql .= "WHERE s.entity IN (1) ";
$supplierListSql .= "AND ( EXISTS (SELECT ck.fk_soc FROM llx_categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (" . getDolGlobalInt("MARKETPLACE_PROSPECTCUSTOMER_ID") . "))) ";
$supplierListSql .= "AND s.fournisseur = 1 ";
$supplierListSql .= "AND (s.status IN (1)) ";

// Apply filters
if (!empty($search_id)) {
	$supplierListSql .= " AND s.rowid = " . $db->escape($search_id);
}
if (!empty($search_name)) {
	$supplierListSql .= " AND s.nom LIKE '%" . $db->escape($search_name) . "%'";
}
if (!empty($search_alias)) {
	$supplierListSql .= " AND s.name_alias LIKE '%" . $db->escape($search_alias) . "%'";
}
if (!empty($search_ref_ext)) {
	$supplierListSql .= " AND s.ref_ext LIKE '%" . $db->escape($search_ref_ext) . "%'";
}
if (!empty($search_country)) {
	$supplierListSql .= " AND country.label LIKE '%" . $db->escape($search_country) . "%'";
}

// Apply sorting
if (in_array($sortfield, ['s.rowid', 's.nom', 's.name_alias', 's.ref_ext', 's.datec', 'country.label'])) {
	$supplierListSql .= " ORDER BY " . $sortfield . " " . $sortorder;
}

//$supplierListSql .= $db->plimit($limit + 1, $offset);

$supplierList = array();
if ($result = $db->query($supplierListSql)) {
	while ($supplier = $db->fetch_object($result)) {
		$supplierList[] = $supplier;
	}
}


$supplier_stats = array();
foreach ($supplierList as $supplier) {
	$customer_id = $supplier->rowid;

	// Get account info
	/*
	$accounts = array();
	$sql = "SELECT rowid, login ";
	$sql .= "FROM ".MAIN_DB_PREFIX."societe_account ";
	$sql .= "WHERE fk_soc = ".((int) $customer_id)." ";
	//$sql .= "AND fk_website = " . getDolGlobalInt("MARKETPLACE_WEBSITE_ID") . " ";
	$sql .= "LIMIT 100";
	$resql = $db->query($sql);
	while ($objsql = $db->fetch_object($resql)) {
		$accounts[] = array('id' => $objsql->rowid, 'login' => $objsql->login);
	}
	$logins = implode('<br>', array_column($accounts, 'login'));
	*/

	// Get liste of products
	$products_ids = array();

	$sql = "SELECT pf.fk_product, MIN(pf.datec) AS first_date ";
	$sql .= "FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pf, ".MAIN_DB_PREFIX."categorie_product AS cp ";
	$sql .= "WHERE pf.fk_product = cp.fk_product";
	if ($customer_id != "all") {
		$sql .= " AND pf.fk_soc = ".((int) $customer_id);
	}
	$sql .= " AND cp.fk_categorie = ".((int) getDolGlobalInt("MARKETPLACE_ROOT_CATEGORY_ID"));
	$sql .= " GROUP BY pf.fk_product ";
	$sql .= " ORDER BY first_date DESC";

	if ($result_products = $db->query($sql)) {
		while ($product_id = $result_products->fetch_object()) {
			$products_ids[] = $product_id->fk_product;
		}
	}
	$products_ids_str = implode(',', $products_ids);

	if (!empty($products_ids_str)) {
		// Number of all paid sells OR for a period
		$all_sells_period_orders = "SELECT SUM(d.qty) AS sells FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande and d.fk_product IN (" . $products_ids_str . ") and d.total_ht != 0 and c.fk_statut IN (1,3) and (c.facture = 1 OR c.ref_ext IS NOT NULL) and c.module_source = 'marketplace' and c.date_commande < '2025-01-01'";
		if (!empty($filterOrders)) {
			$all_sells_period_orders .=  $filterOrders;
		}
		$resql = $db->query($all_sells_period_orders);
		$all_sells_period_orders = $db->fetch_object($resql);

		$all_sells_period_invoices = "SELECT SUM(fd.qty) AS sells FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and fd.total_ht > 0 and f.type = 0 and f.paye = 1 and f.module_source = 'marketplace' and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$all_sells_period_invoices .=  $filterInvoices;
		}
		$resql = $db->query($all_sells_period_invoices);
		$all_sells_period_invoices = $db->fetch_object($resql);

		$all_refunds_period_invoices = "SELECT SUM(fd.qty) AS refunds FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and f.type = 2 and f.paye = 1 and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$all_refunds_period_invoices .=  $filterInvoices;
		}
		$resql = $db->query($all_refunds_period_invoices);
		$all_refunds_period_invoices = $db->fetch_object($resql);

		$all_sells_period = $all_sells_period_orders->sells + $all_sells_period_invoices->sells;

		// Total of all sells done OR for a period
		$sum_all_sells_period_orders = "SELECT SUM(d.total_ht) as total FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande and d.fk_product IN (" . $products_ids_str . ") and c.fk_statut IN (1,3) and (c.facture = 1 || c.ref_ext IS NOT NULL) and c.module_source = 'marketplace' and c.date_commande < '2025-01-01'";
		if (!empty($filterOrders)) {
			$sum_all_sells_period_orders .=  $filterOrders;
		}
		dol_syslog("SQL Query for sum_all_sells_period_orders: " . $sum_all_sells_period_orders, LOG_DEBUG);
		$resql = $db->query($sum_all_sells_period_orders);
		if (!$resql) {
			dol_syslog("Error in sum_all_sells_period_orders query: " . $db->lasterror(), LOG_ERR);
		}
		$sum_all_sells_period_orders = $db->fetch_object($resql);
		dol_syslog("Result for sum_all_sells_period_orders: " . json_encode($sum_all_sells_period_orders), LOG_DEBUG);

		$sum_all_sells_period_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and f.paye = 1 and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$sum_all_sells_period_invoices .=  $filterInvoices;
		}
		dol_syslog("SQL Query for sum_all_sells_period_invoices: " . $sum_all_sells_period_invoices, LOG_DEBUG);
		$resql = $db->query($sum_all_sells_period_invoices);
		if (!$resql) {
			dol_syslog("Error in sum_all_sells_period_invoices query: " . $db->lasterror(), LOG_ERR);
		}
		$sum_all_sells_period_invoices = $db->fetch_object($resql);
		dol_syslog("Result for sum_all_sells_period_invoices: " . json_encode($sum_all_sells_period_invoices), LOG_DEBUG);

		$sum_all_sells_period = $sum_all_sells_period_orders->total + $sum_all_sells_period_invoices->total;
		dol_syslog("Total sum_all_sells_period: " . $sum_all_sells_period, LOG_DEBUG);

		// Total of all validated sells OR for a period
		$sum_all_validated_sells_period_orders = "SELECT SUM(d.total_ht) as total FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande and d.fk_product IN (" . $products_ids_str . ") and (c.facture = 1 || c.ref_ext IS NOT NULL) and c.module_source = 'marketplace' and c.date_commande < '2025-01-01' AND c.date_commande < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		if (!empty($filterOrders)) {
			$sum_all_validated_sells_period_orders .=  $filterOrders;
		}
		$resql = $db->query($sum_all_validated_sells_period_orders);
		$sum_all_validated_sells_period_orders = $db->fetch_object($resql);

		$sum_all_validated_sells_period_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and fd.total_ht > 0 and f.type = 0 and f.paye = 1 and f.datef > '2025-01-01' AND f.datef < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		if (!empty($filterInvoices)) {
			$sum_all_validated_sells_period_invoices .=  $filterInvoices;
		}
		$resql = $db->query($sum_all_validated_sells_period_invoices);
		$sum_all_validated_sells_period_invoices = $db->fetch_object($resql);

		$sum_all_refunds_period_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and f.type = 2 and f.paye = 1 and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$sum_all_refunds_period_invoices .=  $filterInvoices;
		}
		$resql = $db->query($sum_all_refunds_period_invoices);
		$sum_all_refunds_period_invoices = $db->fetch_object($resql);

		$sum_all_validated_sells_period = $sum_all_validated_sells_period_orders->total + $sum_all_validated_sells_period_invoices->total + $sum_all_refunds_period_invoices->total;

		// Remaining amount to claim in one month
		$sum_all_sells_orders = "SELECT SUM(d.total_ht) as total FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande and d.fk_product IN (" . $products_ids_str . ") and c.fk_statut IN (1,3) and (c.facture = 1 || c.ref_ext IS NOT NULL) and c.module_source = 'marketplace' and c.date_commande < '2025-01-01'";
		if (!empty($filterOrders)) {
			$sum_all_sells_orders .=  $filterOrders;
		}
		$resql = $db->query($sum_all_sells_orders);
		$sum_all_sells_orders = $db->fetch_object($resql);

		$sum_all_sells_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and f.paye = 1 and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$sum_all_sells_invoices .=  $filterInvoices;
		}
		$resql = $db->query($sum_all_sells_invoices);
		$sum_all_sells_invoices = $db->fetch_object($resql);

		$sum_all_sells = $sum_all_sells_orders->total + $sum_all_sells_invoices->total;

		// Remaining amount to claim today
		$sum_all_validated_sells_orders = "SELECT SUM(d.total_ht) as total FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande and d.fk_product IN (" . $products_ids_str . ") and (c.facture = 1 || c.ref_ext IS NOT NULL) and c.module_source = 'marketplace' and c.date_commande < '2025-01-01' AND c.date_commande < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		if (!empty($filterOrders)) {
			$sum_all_validated_sells_orders .=  $filterOrders;
		}
		$resql = $db->query($sum_all_validated_sells_orders);
		$sells_done_validated_orders = $db->fetch_object($resql);

		$sum_all_validated_sells_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and fd.total_ht > 0 and f.type = 0 and f.paye = 1 and f.datef > '2025-01-01' AND f.datef < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
		if (!empty($filterInvoices)) {
			$sum_all_validated_sells_invoices .=  $filterInvoices;
		}
		$resql = $db->query($sum_all_validated_sells_invoices);
		$sells_done_validated_invoices = $db->fetch_object($resql);

		$sum_all_refunds_invoices = "SELECT SUM(fd.total_ht) as total FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as fd WHERE f.rowid = fd.fk_facture and fd.fk_product IN (" . $products_ids_str . ") and f.type = 2 and f.paye = 1 and f.datef > '2025-01-01'";
		if (!empty($filterInvoices)) {
			$sum_all_refunds_invoices .=  $filterInvoices;
		}
		$resql = $db->query($sum_all_refunds_invoices);
		$sum_all_refunds_invoices = $db->fetch_object($resql);

		$sells_done_validated = $sells_done_validated_orders->total + $sells_done_validated_invoices->total + $sum_all_refunds_invoices->total;

		// Calculate all discounts for this customer products (before 01/01/2025)
		$TOTAL_DISCOUNTS = 0;
		$like_conditions = [];
		foreach ($products_ids as $product_id) {
			$like_conditions[] = "d.description LIKE '" . $db->escape($product_id) . "# %'";
		}
		$like_clause = implode(' OR ', $like_conditions);

		$sum_all_discounts = "SELECT SUM(d.total_ht) as total FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d WHERE c.rowid = d.fk_commande AND (" . $like_clause . ") AND (c.facture = 1 OR c.ref_ext IS NOT NULL) AND c.module_source = 'marketplace'";
		if (!empty($filterOrders)) {
			$sum_all_discounts .=  $filterOrders;
		}
		$resql = $db->query($sum_all_discounts);
		$sells_discounts = $db->fetch_object($resql);
		$TOTAL_DISCOUNTS = $sells_discounts->total;

		// Payment History
		$payment_history = "SELECT f.rowid, f.ref, f.fk_statut, f.fk_soc, f.datec, f.datef, f.date_closing, f.total_ht, f.total_ttc FROM ".MAIN_DB_PREFIX."facture_fourn as f WHERE f.fk_statut = 2 AND f.paye = 1";
		if ($customer_id != "all") {
			$payment_history .= " AND f.fk_soc = " . ((int) $customer_id);
		}
		if (!empty($filterInvoices)) {
			$payment_history .=  $filterInvoices;
		}
		$payment_history .= " ORDER BY f.datef DESC";

		$alreadyreceived = 0;
		$dolistoreinvoices = array();
		$linesinvoice = array();
		if ($result_payment_history = $db->query($payment_history)) {
			while ($payment = $result_payment_history->fetch_object()) {
				$invoice = new FactureFournisseur($db);
				$result = $invoice->fetch($payment->rowid);

				$linesresp = array();
				foreach ($invoice->lines as $line) {
					$linesresp[] = array(
						'id' => $line->rowid,
						'type' => $line->product_type,
						'desc' => dol_htmlcleanlastbr($line->desc),
						'total_net' => $line->total_ht,
						'total_vat' => $line->total_tva,
						'total' => $line->total_ttc,
						'vat_rate' => $line->tva_tx,
						'qty' => $line->qty,
						'product_ref' => $line->product_ref,
						'product_label' => $line->product_label,
						'product_desc' => $line->product_desc,
					);
				}

				$linesinvoice[] = array(
					'id' => $invoice->id,
					'ref' => $invoice->ref,
					'ref_supplier' => $invoice->ref_supplier,
					'ref_ext' => $invoice->ref_ext,
					'fk_user_author' => $invoice->user_creation_id,
					'fk_user_valid' => $invoice->user_validation_id,
					'fk_thirdparty' => $invoice->socid,
					'type' => $invoice->type,
					'status' => $invoice->status,
					'total_net' => $invoice->total_ht,
					'total_vat' => $invoice->total_tva,
					'total' => $invoice->total_ttc,
					'date_creation' => dol_print_date($invoice->datec, 'dayhourrfc'),
					'date_modification' => dol_print_date($invoice->tms, 'dayhourrfc'),
					'date_invoice' => dol_print_date($invoice->date, 'dayhourrfc'),
					'date_term' => dol_print_date($invoice->date_echeance, 'dayhourrfc'),
					'label' => $invoice->label,
					'paid' => $invoice->paid,
					'note_private' => $invoice->note_private,
					'note_public' => $invoice->note_public,
					'close_code' => $invoice->close_code,
					'close_note' => $invoice->close_note,
					'lines' => $linesresp
				);
			}

			foreach ($linesinvoice as $invoice) {
				$dateinvoice = substr($invoice['date_invoice'], 0, 10);

				$isfordolistore = 0;
				if ((preg_match('/dolistore/i', $invoice['note_private']) || preg_match('/dolistore/i', $invoice['label'])) && !preg_match('/agios/i', $invoice['ref_supplier']) && !preg_match('/frais/i', $invoice['ref_supplier']) && !preg_match('/comDolistore/i', $invoice['ref_supplier'])) {
					$isfordolistore = 1;
				}

				if (!$isfordolistore) {
					foreach ($invoice['lines'] as $line) {
						if (preg_match('/dolistore/i', $line['desc']) && !preg_match('/Remboursement certificat|Remboursement domaine/i', $line['desc']) && !preg_match('/agios/i', $invoice['ref_supplier']) && !preg_match('/frais/i', $invoice['ref_supplier']) && !preg_match('/comDolistore/i', $invoice['ref_supplier'])) {
							$isfordolistore++;
						}
					}
				}

				if ($isfordolistore) {
					$dolistoreinvoices[] = array(
						'dolistore_customer_id' => $customer_id,
						'id' => $invoice['id'],
						'ref' => $invoice['ref'],
						'ref_supplier' => $invoice['ref_supplier'],
						'status' => $invoice['status'],
						'date' => $invoice['date_invoice'],
						'datenohour' => $dateinvoice,
						'amount_ht' => $invoice['total_net'],
						'amount_vat' => $invoice['total_vat'],
						'amount_ttc' => $invoice['total'],
						'fk_thirdparty' => $invoice['fk_thirdparty']
					);
				}
			}
		}

		foreach ($dolistoreinvoices as $item) {
			$tmpdate = preg_replace('/(\s|T)00:00:00Z/', '', $item['date']);
			if (strcmp($tmpdate, '2013-01-01') < 0) {
				$alreadyreceived += $item['amount_ttc'];
			} else {
				$alreadyreceived += $item['amount_ht'];
			}
		}

		$sum_payment_done = $alreadyreceived;

		// Check if there is an amount of coupons for this customer
		$TOTAL_REDUC_OLD_SYSTEM = 0;
		if (getDolGlobalString("MARKETPLACE_TOTAL_REDUC_OLD_SYSTEM_" . $customer_id)) {
			$TOTAL_REDUC_OLD_SYSTEM = getDolGlobalString("MARKETPLACE_TOTAL_REDUC_OLD_SYSTEM_" . $customer_id);
		}
		if ($customer_id == "all") {
			$TOTAL_REDUC_OLD_SYSTEM = getDolGlobalString("MARKETPLACE_TOTAL_REDUC_OLD_SYSTEM");
		}

		$supplier_stats[$customer_id] = array(
			'id' => $customer_id,
			'name' => $supplier->name,
			'alias' => $supplier->name_alias,
			'ref_ext' => $supplier->ref_ext,
			'logins' => $logins,
			'date_creation' => $supplier->date_creation,
			'country' => $supplier->country_label,
			'numberOfProducts' => count($products_ids),
			'numberOfPaidSells' => $all_sells_period,
			'totalOfSellsDone' => (($sum_all_sells_period - $TOTAL_REDUC_OLD_SYSTEM + $TOTAL_DISCOUNTS) * 0.80),
			'totalValidatedSells' => (($sum_all_validated_sells_period - $TOTAL_REDUC_OLD_SYSTEM + $TOTAL_DISCOUNTS) * 0.80),
			'remainedAmountInOneMonth' => ((($sum_all_sells - $TOTAL_REDUC_OLD_SYSTEM + $TOTAL_DISCOUNTS) * 0.80) - $sum_payment_done),
			'remainedAmountToday' => ((($sells_done_validated - $TOTAL_REDUC_OLD_SYSTEM + $TOTAL_DISCOUNTS) * 0.80) - $sum_payment_done),
			'discounts' => $TOTAL_DISCOUNTS,
			'totalPaymentsDone' => $sum_payment_done,
			'numberOfSupplierInvoices' => count($dolistoreinvoices),
			'paymentsHistory' => $dolistoreinvoices
		);

		!empty($all_refunds_period_invoices->refunds) ? $supplier_stats[$customer_id]['qtyRefunds'] = $all_refunds_period_invoices->refunds : $supplier_stats[$customer_id]['qtyRefunds'] = 0;

		!empty($sum_all_refunds_period_invoices->total) ? $supplier_stats[$customer_id]['sumRefunds'] = $sum_all_refunds_period_invoices->total : $supplier_stats[$customer_id]['sumRefunds'] = 0;

	}
}

// Filter the supplier_stats array based on search criteria
if (!empty($search_numberOfProducts)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_numberOfProducts) {
		return $supplier['numberOfProducts'] == (int)$search_numberOfProducts;
	});
}
if (!empty($search_numberOfPaidSells)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_numberOfPaidSells) {
		return $supplier['numberOfPaidSells'] == (int)$search_numberOfPaidSells;
	});
}
if (!empty($search_qtyRefunds)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_qtyRefunds) {
		return $supplier['qtyRefunds'] == (float)$search_qtyRefunds;
	});
}
if (!empty($search_sumRefunds)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_sumRefunds) {
		return $supplier['sumRefunds'] == (float)$search_sumRefunds;
	});
}
if (!empty($search_totalOfSellsDone)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_totalOfSellsDone) {
		return $supplier['totalOfSellsDone'] == (float)$search_totalOfSellsDone;
	});
}
if (!empty($search_totalValidatedSells)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_totalValidatedSells) {
		return $supplier['totalValidatedSells'] == (float)$search_totalValidatedSells;
	});
}
if (!empty($search_remainedAmountInOneMonth)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_remainedAmountInOneMonth) {
		return $supplier['remainedAmountInOneMonth'] == (float)$search_remainedAmountInOneMonth;
	});
}
if (!empty($search_remainedAmountToday)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_remainedAmountToday) {
		return $supplier['remainedAmountToday'] == (float)$search_remainedAmountToday;
	});
}
if (!empty($search_discounts)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_discounts) {
		return $supplier['discounts'] == (float)$search_discounts;
	});
}
if (!empty($search_totalPaymentsDone)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_totalPaymentsDone) {
		return $supplier['totalPaymentsDone'] == (float)$search_totalPaymentsDone;
	});
}
if (!empty($search_numberOfSupplierInvoices)) {
	$supplier_stats = array_filter($supplier_stats, function($supplier) use ($search_numberOfSupplierInvoices) {
		return $supplier['numberOfSupplierInvoices'] == (int)$search_numberOfSupplierInvoices;
	});
}


// Function to sort the supplier_stats array
function sort_supplier_stats(&$supplier_stats, $sortfield, $sortorder) {
	usort($supplier_stats, function($a, $b) use ($sortfield, $sortorder) {
		if ($sortorder == 'asc') {
			return $a[$sortfield] <=> $b[$sortfield];
		} else {
			return $b[$sortfield] <=> $a[$sortfield];
		}
	});
}

// Sort the supplier_stats array based on the sortfield and sortorder

if (in_array($sortfield, [
	'numberOfProducts',
	'numberOfPaidSells',
	'qtyRefunds',
	'numberOfSupplierInvoices',
	'totalOfSellsDone',
	'sumRefunds',
	'discounts',
	'totalValidatedSells',
	'remainedAmountInOneMonth',
	'remainedAmountToday',
	'totalPaymentsDone'
])) {
	sort_supplier_stats($supplier_stats, $sortfield, $sortorder);
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="action" value="add">';
if ($backtopage) {
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
}
if ($backtopageforcancel) {
	print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", count($supplier_stats), count($supplier_stats), '', 0, '', '', 0, 0, 0, 1);

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_id" value="'.dol_escape_htmltag($search_id).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_name" value="'.dol_escape_htmltag($search_name).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_alias" value="'.dol_escape_htmltag($search_alias).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_ref_ext" value="'.dol_escape_htmltag($search_ref_ext).'"></td>';
//print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_logins" value="'.dol_escape_htmltag($search_logins).'"></td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_country" value="'.dol_escape_htmltag($search_country).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_numberOfProducts" value="'.dol_escape_htmltag($search_numberOfProducts).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_numberOfPaidSells" value="'.dol_escape_htmltag($search_numberOfPaidSells).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_qtyRefunds" value="'.dol_escape_htmltag($search_qtyRefunds).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_numberOfSupplierInvoices" value="'.dol_escape_htmltag($search_numberOfSupplierInvoices).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_totalOfSellsDone" value="'.dol_escape_htmltag($search_totalOfSellsDone).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_sumRefunds" value="'.dol_escape_htmltag($search_sumRefunds).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_discounts" value="'.dol_escape_htmltag($search_discounts).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_totalValidatedSells" value="'.dol_escape_htmltag($search_totalValidatedSells).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_remainedAmountInOneMonth" value="'.dol_escape_htmltag($search_remainedAmountInOneMonth).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_remainedAmountToday" value="'.dol_escape_htmltag($search_remainedAmountToday).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat searchstring maxwidth75imp" name="search_totalPaymentsDone" value="'.dol_escape_htmltag($search_totalPaymentsDone).'"></td>';
print '<td class="liste_titre center">';
/*print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', false, 1).'" value="'.$langs->trans("Search").'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"), 'searchclear.png', '', false, 1).'" value="'.$langs->trans("RemoveFilter").'">';*/

print '<div class="nowraponall">';
print '<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x"><span class="fas fa-search"></span></button>';
print '<button type="submit" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x"><span class="fas fa-times"></span></button>';
print '</div>';

print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('TechnicalID'), $_SERVER["PHP_SELF"], 's.rowid', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ThirdPartyName'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('AliasNameShort'), $_SERVER["PHP_SELF"], 's.name_alias', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('RefExt'), $_SERVER["PHP_SELF"], 's.ref_ext', '', $param, '', $sortfield, $sortorder);
//print_liste_field_titre($langs->trans('WebSiteAccounts'), $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('DateCreation'), $_SERVER["PHP_SELF"], 's.datec', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('Country'), $_SERVER["PHP_SELF"], 'country.label', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('numberOfProducts'), $_SERVER["PHP_SELF"], 'numberOfProducts', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('numberOfPaidSells'), $_SERVER["PHP_SELF"], 'numberOfPaidSells', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('qtyRefunds'), $_SERVER["PHP_SELF"], 'qtyRefunds', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('numberOfSupplierInvoices'), $_SERVER["PHP_SELF"], 'numberOfSupplierInvoices', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('totalOfSellsDone'), $_SERVER["PHP_SELF"], 'totalOfSellsDone', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('sumRefunds'), $_SERVER["PHP_SELF"], 'sumRefunds', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('totalDiscounts'), $_SERVER["PHP_SELF"], 'discounts', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('totalValidatedSells'), $_SERVER["PHP_SELF"], 'totalValidatedSells', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('remainedAmountInOneMonth'), $_SERVER["PHP_SELF"], 'remainedAmountInOneMonth', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('remainedAmountToday'), $_SERVER["PHP_SELF"], 'remainedAmountToday', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($langs->trans('totalPaymentsDone'), $_SERVER["PHP_SELF"], 'totalPaymentsDone', '', $param, '', $sortfield, $sortorder, 'right ');
print "<td class=\"liste_titre\">&nbsp;</td>";
print "</tr>\n";

// Static object
$supplierObject = new Societe($db);

$totalNumberOfProducts = 0;
$totalNumberOfPaidSells = 0;
$totalQtyRefunds = 0;
$totalNumberOfSupplierInvoices = 0;
$totalOfSellsDone = 0;
$totalSumRefunds = 0;
$totalDiscounts = 0;
$totalValidatedSells = 0;
$totalRemainedAmountInOneMonth = 0;
$totalRemainedAmountToday = 0;
$totalPaymentsDone = 0;

foreach ($supplier_stats as $supplier_id => $supplier) {
	$supplierObject->fetch($supplier['id']);
	print "<tr>";
	print '<td>'.$supplier['id'].'</td>';
	print '<td class="tdoverflowmax125">'.$supplierObject->getNomUrl(1).'</td>';
	print '<td class="tdoverflowmax125" title="'.dolPrintHtmlForAttribute($supplier['alias']).'">'.$supplier['alias'].'</td>';
	print '<td>'.$supplier['ref_ext'].'</td>';
	//print '<td>'.$supplier['logins'].'</td>';
	print '<td>'.dol_print_date($supplier['date_creation'], 'day').'</td>';
	print '<td>'.$supplier['country'].'</td>';
	print '<td class="right">'.((int) $supplier['numberOfProducts']).'</td>';
	print '<td class="right">'.((int) $supplier['numberOfPaidSells']).'</td>';
	print '<td class="right">'.$supplier['qtyRefunds'].'</td>';
	print '<td class="right">'.((int) $supplier['numberOfSupplierInvoices']).'</td>';
	print '<td class="right">'.price($supplier['totalOfSellsDone'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['sumRefunds'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['discounts'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['totalValidatedSells'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['remainedAmountInOneMonth'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['remainedAmountToday'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right">'.price($supplier['totalPaymentsDone'], 0, '', 1, -1, 2).'</td>';
	print '<td class="right"></td>';
	print "</tr>\n";

	$totalNumberOfProducts += $supplier['numberOfProducts'];
	$totalNumberOfPaidSells += $supplier['numberOfPaidSells'];
	$totalQtyRefunds += $supplier['qtyRefunds'];
	$totalNumberOfSupplierInvoices += $supplier['numberOfSupplierInvoices'];
	$totalOfSellsDone += $supplier['totalOfSellsDone'];
	$totalSumRefunds += $supplier['sumRefunds'];
	$totalDiscounts += $supplier['discounts'];
	$totalValidatedSells += $supplier['totalValidatedSells'];
	$totalRemainedAmountInOneMonth += $supplier['remainedAmountInOneMonth'];
	$totalRemainedAmountToday += $supplier['remainedAmountToday'];
	$totalPaymentsDone += $supplier['totalPaymentsDone'];
}

if (empty($supplier_stats)) {
	print '<tr><td colspan="18"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

// Total row
print '<tr class="liste_total">';
print '<td colspan="6" class="right">'.$langs->trans("Total").'</td>';
print '<td class="right">'.((int) $totalNumberOfProducts).'</td>';
print '<td class="right">'.((int) $totalNumberOfPaidSells).'</td>';
print '<td class="right">'.$totalQtyRefunds.'</td>';
print '<td class="right">'.((int) $totalNumberOfSupplierInvoices).'</td>';
print '<td class="right">'.price($totalOfSellsDone, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalSumRefunds, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalDiscounts, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalValidatedSells, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalRemainedAmountInOneMonth, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalRemainedAmountToday, 0, '', 1, -1, 2).'</td>';
print '<td class="right">'.price($totalPaymentsDone, 0, '', 1, -1, 2).'</td>';
print '<td class="right"></td>';
print '</tr>';

print "</table>";
print '</div>';

print '</form>';

print dol_get_fiche_end();
llxFooter();
$db->close();
