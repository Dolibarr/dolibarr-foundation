<?php
/* Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    marketplace/module_validation.php
 * \ingroup marketplace
 * \brief   Dashboard of the module validation: KPI, statistics and list of modules
 *          (pending, refused/unpublished or online) submitted to the marketplace.
 *
 * A module is a service (fk_product_type = 1) stored in the marketplace root category
 * (or one of its sub-categories). Its state is deduced from:
 * - llx_product.tosell = 1                               => online
 * - tosell = 0 and "reason disabled" extrafield empty    => pending validation
 * - tosell = 0 and "reason disabled" extrafield filled   => refused or unpublished
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
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
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Societe $mysoc
 */
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/marketplace/lib/marketplace.lib.php');

// Translations
$langs->loadLangs(array("marketplace@marketplace", "products"));

// Access control
if (!$user->hasRight('marketplace', 'module', 'read')) {
	accessforbidden();
}

// Parameters
$mode = GETPOST('mode', 'aZ09'); // pending | refused | online (default: pending)
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array:int');
$k = marketplaceValidationKeys();

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$contextpage = 'modulevalidationlist'; // Identifier of the page, used to store the setup of visible columns

$canValidate = $user->hasRight('marketplace', 'module', 'validate');

// Search criteria
$search_ref = trim(GETPOST('search_ref', 'alphanohtml'));
$search_label = trim(GETPOST('search_label', 'alphanohtml'));
$search_seller = trim(GETPOST('search_seller', 'alphanohtml'));
$search_version = trim(GETPOST('search_version', 'alphanohtml'));
$search_dolmin = trim(GETPOST('search_dolmin', 'alphanohtml'));
$search_dolmax = trim(GETPOST('search_dolmax', 'alphanohtml'));
$search_fork = GETPOST('search_fork', 'aZ09');
$search_duplicate = GETPOST('search_duplicate', 'aZ09');
$search_reason = trim(GETPOST('search_reason', 'alphanohtml'));
$search_submitted_dtstart = dol_mktime(0, 0, 0, GETPOSTINT('search_submitted_dtstartmonth'), GETPOSTINT('search_submitted_dtstartday'), GETPOSTINT('search_submitted_dtstartyear'));
$search_submitted_dtend = dol_mktime(23, 59, 59, GETPOSTINT('search_submitted_dtendmonth'), GETPOSTINT('search_submitted_dtendday'), GETPOSTINT('search_submitted_dtendyear'));

// SQL columns of the extrafields (alias "ef")
$efSub = marketplaceCol('submitted');
$efReason = marketplaceCol('reason');
$efMin = marketplaceCol('dolmin');
$efMax = marketplaceCol('dolmax');
$efDup = marketplaceCol('duplicateof');
$efFork = marketplaceCol('forkof');

// Definition of array of fields for columns
$arrayfields = array(
	'p.rowid' => array('label' => 'TechnicalID', 'checked' => 1, 'position' => 1),
	'p.ref' => array('label' => 'Ref', 'checked' => 1, 'position' => 10),
	'p.label' => array('label' => 'Label', 'checked' => 1, 'position' => 20),
	'seller' => array('label' => 'Seller', 'checked' => 1, 'position' => 30),
	'version' => array('label' => 'ModuleVersion', 'checked' => 1, 'position' => 40),
	'dolmin' => array('label' => 'DolibarrMin', 'checked' => 1, 'position' => 50),
	'dolmax' => array('label' => 'DolibarrMax', 'checked' => 1, 'position' => 51),
	'fork' => array('label' => 'Fork', 'checked' => 1, 'position' => 60),
	'duplicate' => array('label' => 'Duplicate', 'checked' => 1, 'position' => 61),
	'reason' => array('label' => 'Reason', 'checked' => 0, 'position' => 62),
	'submitted' => array('label' => 'DateRequestToBeOnline', 'checked' => 1, 'position' => 70),
	'age' => array('label' => 'Age', 'checked' => 1, 'position' => 80),
	'tosell' => array('label' => 'OnSale', 'checked' => 0, 'position' => 90),
);

// Categories that contain the marketplace modules
$catIds = marketplaceGetCategoryIds($db);
if (empty($catIds)) {
	setEventMessages('MARKETPLACE_ROOT_CATEGORY_ID is not defined', null, 'errors');
	$catIds = array(0);
}

// Common parts of the SQL queries
$from = " FROM ".MAIN_DB_PREFIX."product as p";
$from .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef ON ef.fk_object = p.rowid";
$base = " WHERE p.fk_product_type = 1 AND p.rowid IN (SELECT cp.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_categorie IN (".implode(',', $catIds)."))";
$condPending = "p.tosell = 0 AND (".$efReason." IS NULL OR ".$efReason." = '')";
$condRefused = "p.tosell = 0 AND ".$efReason." IS NOT NULL AND ".$efReason." <> ''";
$condOnline = "p.tosell = 1";

/*
 * Actions
 */

$reasonKey = 'options_'.$k['reason'];
$massreason = trim(GETPOST('massreason', 'alphanohtml'));

if (GETPOST('cancel', 'alpha')) {
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha')) {
	// A mass action is processed only when its confirmation button was pressed
	$massaction = '';
}

$object = new Product($db);

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge of the search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_ref = '';
		$search_label = '';
		$search_seller = '';
		$search_version = '';
		$search_dolmin = '';
		$search_dolmax = '';
		$search_fork = '';
		$search_duplicate = '';
		$search_reason = '';
		$search_submitted_dtstart = '';
		$search_submitted_dtend = '';
		$toselect = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass action: publish the selected modules
	if ($massaction == 'massvalidate' && $canValidate) {
		$nbok = 0;
		$nbko = 0;
		$productstatic = new Product($db);
		foreach (marketplaceFilterModuleIds($db, $catIds, $toselect) as $toselectid) {
			$result = $productstatic->fetch($toselectid);
			if ($result <= 0) {
				$nbko++;
				continue;
			}
			// Publication is refused by the server if a blocking check fails
			$checks = marketplaceValidationChecks($db, $productstatic);
			if (!marketplaceChecksAreValid($checks)) {
				$nbko++;
				continue;
			}
			// Clear the previous reason, then put the service on sale
			$productstatic->array_options[$reasonKey] = '';
			$productstatic->insertExtraFields();
			$productstatic->setStatut(1, null, 'product', 'PRODUCT_MODIFY', 'tosell');
			marketplaceLogValidationAction($db, $user, $productstatic, 'MKT_VALIDATE', 'Module published: '.$productstatic->ref);
			$nbok++;
		}
		setEventMessages($langs->trans("ModulesPublished", $nbok), null, 'mesgs');
		if ($nbko) {
			setEventMessages($langs->trans("ModulesNotPublished", $nbko), null, 'warnings');
		}
	}

	// Mass action: refuse or unpublish the selected modules (a reason is required)
	if (in_array($massaction, array('confirm_massrefuse', 'confirm_massunpublish')) && $canValidate) {
		if (dol_strlen($massreason) < 5) {
			setEventMessages($langs->trans("ReasonRequired"), null, 'errors');
		} else {
			$isrefuse = ($massaction == 'confirm_massrefuse');
			$code = $isrefuse ? 'MKT_REFUSE' : 'MKT_UNPUBLISH';
			$nbok = 0;
			$productstatic = new Product($db);
			foreach (marketplaceFilterModuleIds($db, $catIds, $toselect) as $toselectid) {
				$result = $productstatic->fetch($toselectid);
				if ($result <= 0) {
					continue;
				}
				// The extrafield is limited to 255 characters
				$productstatic->array_options[$reasonKey] = dol_trunc($massreason, 255, 'right', 'UTF-8', 1);
				$productstatic->insertExtraFields();
				if ($productstatic->status) {
					$productstatic->setStatut(0, null, 'product', 'PRODUCT_MODIFY', 'tosell');
				}
				marketplaceLogValidationAction($db, $user, $productstatic, $code, ucfirst($isrefuse ? 'refuse' : 'unpublish').': '.$productstatic->ref.' - '.$massreason);
				$nbok++;
			}
			setEventMessages($langs->trans($isrefuse ? "ModulesRefused" : "ModulesUnpublished", $nbok), null, 'mesgs');
		}
	}
}

/*
 * KPI
 */

// Counters by state, declared duplicates and forks, oldest pending module
$kpi = array('pending' => 0, 'refused' => 0, 'online' => 0, 'dups' => 0, 'forks' => 0, 'oldest' => null);
$sql = "SELECT SUM(CASE WHEN ".$condPending." THEN 1 ELSE 0 END) as pending,";
$sql .= " SUM(CASE WHEN ".$condRefused." THEN 1 ELSE 0 END) as refused,";
$sql .= " SUM(CASE WHEN ".$condOnline." THEN 1 ELSE 0 END) as online,";
$sql .= " SUM(CASE WHEN ".$efDup." IS NOT NULL AND ".$efDup." <> '' THEN 1 ELSE 0 END) as dups,";
$sql .= " SUM(CASE WHEN ".$efFork." IS NOT NULL AND ".$efFork." <> '' THEN 1 ELSE 0 END) as forks,";
$sql .= " MIN(CASE WHEN ".$condPending." THEN COALESCE(".$efSub.", p.datec) ELSE NULL END) as oldest";
$sql .= $from.$base;
$resql = $db->query($sql);
if ($resql && ($obj = $db->fetch_object($resql))) {
	$kpi = array('pending' => (int) $obj->pending, 'refused' => (int) $obj->refused, 'online' => (int) $obj->online,
		'dups' => (int) $obj->dups, 'forks' => (int) $obj->forks, 'oldest' => $obj->oldest ? $db->jdate($obj->oldest) : null);
}

// Tracked actions (last 30 days) and average processing time (submission -> decision)
$since = dol_now() - 30 * 86400;
$counts = array('MKT_VALIDATE' => 0, 'MKT_REFUSE' => 0, 'MKT_UNPUBLISH' => 0);
$delays = array();
$sql = "SELECT a.code, a.datep, ".$efSub." as submitted FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef ON ef.fk_object = a.fk_element";
$sql .= " WHERE a.elementtype = 'product' AND a.code IN ('MKT_VALIDATE','MKT_REFUSE','MKT_UNPUBLISH')";
$sql .= " AND a.datep >= '".$db->idate($since)."'";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$counts[$obj->code]++;
		if ($obj->code != 'MKT_UNPUBLISH' && $obj->submitted) {
			$d = $db->jdate($obj->datep) - $db->jdate($obj->submitted);
			if ($d >= 0) {
				$delays[] = $d;
			}
		}
	}
}
$avgDelayDays = count($delays) ? round(array_sum($delays) / count($delays) / 86400, 1) : null;

// Top reasons of refusal / unpublication
$reasons = array();
$sql = "SELECT ".$efReason." as reason, COUNT(p.rowid) as nb ".$from.$base." AND ".$efReason." <> '' GROUP BY ".$efReason." ORDER BY nb DESC LIMIT 5";
$resql = $db->query($sql);
while ($resql && ($obj = $db->fetch_object($resql))) {
	$reasons[] = $obj;
}

// Top sellers (by number of submitted modules)
$sellersTop = array();
$sql = "SELECT s.rowid, s.nom, COUNT(DISTINCT p.rowid) as nb ".$from;
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pfp.fk_product = p.rowid";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= $base." GROUP BY s.rowid, s.nom ORDER BY nb DESC";
$resql = $db->query($sql);
while ($resql && ($obj = $db->fetch_object($resql))) {
	$sellersTop[] = $obj;
}

// Breakdown by minimum Dolibarr version
$byVersion = array();
$sql = "SELECT ".$efMin." as v, COUNT(p.rowid) as nb ".$from.$base." GROUP BY ".$efMin." ORDER BY nb DESC";
$resql = $db->query($sql);
while ($resql && ($obj = $db->fetch_object($resql))) {
	$byVersion[] = $obj;
}

/*
 * List of modules for the selected state
 */
$listMode = ($mode ? $mode : 'pending');
$cond = array('pending' => $condPending, 'refused' => $condRefused, 'online' => $condOnline)[$listMode] ?? $condPending;

// Add the search criteria to the SQL condition
$sqlWhereFilter = '';
if ($search_ref !== '') {
	$sqlWhereFilter .= natural_search(array('p.ref'), $search_ref);
}
if ($search_label !== '') {
	$sqlWhereFilter .= natural_search(array('p.label'), $search_label);
}
if ($search_seller !== '') {
	$sqlWhereFilter .= " AND EXISTS (SELECT s2.nom FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp2 INNER JOIN ".MAIN_DB_PREFIX."societe as s2 ON s2.rowid = pfp2.fk_soc";
	$sqlWhereFilter .= " WHERE pfp2.fk_product = p.rowid".natural_search(array('s2.nom'), $search_seller).")";
}
if ($search_version !== '') {
	$sqlWhereFilter .= natural_search(array(marketplaceCol('version')), $search_version);
}
if ($search_dolmin !== '') {
	$sqlWhereFilter .= natural_search(array($efMin), $search_dolmin);
}
if ($search_dolmax !== '') {
	$sqlWhereFilter .= natural_search(array($efMax), $search_dolmax);
}
if ($search_fork === '1') {
	$sqlWhereFilter .= " AND (".$efFork." IS NOT NULL AND ".$efFork." <> '')";
} elseif ($search_fork === '0') {
	$sqlWhereFilter .= " AND (".$efFork." IS NULL OR ".$efFork." = '')";
}
if ($search_duplicate === '1') {
	$sqlWhereFilter .= " AND (".$efDup." IS NOT NULL AND ".$efDup." <> '')";
} elseif ($search_duplicate === '0') {
	$sqlWhereFilter .= " AND (".$efDup." IS NULL OR ".$efDup." = '')";
}
if ($search_reason !== '') {
	$sqlWhereFilter .= natural_search(array($efReason), $search_reason);
}
if ($search_submitted_dtstart) {
	$sqlWhereFilter .= " AND COALESCE(".$efSub.", p.datec) >= '".$db->idate($search_submitted_dtstart)."'";
}
if ($search_submitted_dtend) {
	$sqlWhereFilter .= " AND COALESCE(".$efSub.", p.datec) <= '".$db->idate($search_submitted_dtend)."'";
}

// Whitelist of the columns allowed in the ORDER BY clause
$sortmap = array(
	'p.rowid' => 'p.rowid',
	'p.ref' => 'p.ref',
	'p.label' => 'p.label',
	'seller' => 'seller',
	'version' => 'version',
	'dolmin' => 'dolmin',
	'dolmax' => 'dolmax',
	'fork' => 'forkof',
	'duplicate' => 'duplicateof',
	'reason' => 'reason',
	'submitted' => 'submitted',
	'tosell' => 'p.tosell',
);
if (!isset($sortmap[$sortfield])) {
	$sortfield = 'p.ref';
}
if ($sortorder != 'DESC') {
	$sortorder = 'ASC';
}

// Total number of records for the pagination bar
$nbtotalofrecords = 0;
$sql = "SELECT COUNT(p.rowid) as nb".$from.$base." AND ".$cond.$sqlWhereFilter;
$resql = $db->query($sql);
if ($resql && ($obj = $db->fetch_object($resql))) {
	$nbtotalofrecords = (int) $obj->nb;
}

$sql = "SELECT p.rowid, p.ref, p.label, p.tosell, COALESCE(".$efSub.", p.datec) as submitted,";
$sql .= " ".$efMin." as dolmin, ".$efMax." as dolmax, ".marketplaceCol('version')." as version,";
$sql .= " ".$efFork." as forkof, ".$efDup." as duplicateof,";
$sql .= " ".$efReason." as reason,";
$sql .= " (SELECT s.rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= "  WHERE pfp.fk_product = p.rowid LIMIT 1) as seller_id,";
$sql .= " (SELECT s.nom FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= "  WHERE pfp.fk_product = p.rowid LIMIT 1) as seller";
$sql .= $from.$base." AND ".$cond.$sqlWhereFilter;
$sql .= $db->order($sortmap[$sortfield], $sortorder);
$sql .= $db->plimit($limit, $offset);
$list = array();
$resql = $db->query($sql);
while ($resql && ($obj = $db->fetch_object($resql))) {
	$list[] = $obj;
}

/*
 * View
 */

$help_url = '';
$page_name = "MarketPlaceModuleValidation";

llxHeader('', $langs->trans($page_name), '', '', 0, 0, '', '', '', 'mod-marketplace page-validation');

print load_fiche_titre($langs->trans($page_name), '', 'fa-puzzle-piece');

/**
 * Build the HTML of a KPI tile.
 *
 * @param  string $label Label of the KPI
 * @param  mixed  $value Value to display
 * @param  string $url   Optional link on the value
 * @param  string $color Optional color of the left border
 * @return string        HTML of the tile
 */
$tile = function ($label, $value, $url = '', $color = '') {
	$html = '<div style="flex:1 1 170px;border:1px solid #ccc;border-radius:6px;padding:12px;text-align:center;'.($color ? 'border-left:5px solid '.$color.';' : '').'">';
	$html .= '<div class="opacitymedium">'.dol_escape_htmltag($label).'</div>';
	$html .= '<div style="font-size:2em;font-weight:bold">'.($url ? '<a href="'.$url.'">'.$value.'</a>' : $value).'</div></div>';
	return $html;
};
$self = $_SERVER['PHP_SELF'];

// KPI tiles
print '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:15px">';
print $tile($langs->trans("KpiPending"), $kpi['pending'], $self.'?mode=pending', '#e67e22');
print $tile($langs->trans("KpiOldestPending"), $kpi['oldest'] ? floor((dol_now() - $kpi['oldest']) / 86400).' j' : '-');
print $tile($langs->trans("KpiOnline"), $kpi['online'], $self.'?mode=online', '#27ae60');
print $tile($langs->trans("KpiRefused"), $kpi['refused'], $self.'?mode=refused', '#c0392b');
print $tile($langs->trans("KpiValidated30d"), $counts['MKT_VALIDATE']);
print $tile($langs->trans("KpiRefused30d"), $counts['MKT_REFUSE']);
print $tile($langs->trans("KpiUnpublished30d"), $counts['MKT_UNPUBLISH']);
print $tile($langs->trans("KpiAvgDelay"), $avgDelayDays === null ? '-' : $avgDelayDays.' j');
print $tile($langs->trans("KpiDuplicates"), $kpi['dups']);
print $tile($langs->trans("KpiForks"), $kpi['forks']);
print '</div>';

// Statistics tables: only the first rows are shown, the remaining ones
// are revealed with the "More ..." toggle
$maxshown = 10;

print '<div style="display:flex;flex-wrap:wrap;gap:20px;margin-bottom:20px">';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiTopReasons").'</th><th class="right">#</th></tr>';
foreach ($reasons as $r) {
	print '<tr class="oddeven"><td>'.dol_escape_htmltag($r->reason).'</td><td class="right">'.$r->nb.'</td></tr>';
}
print '</table></div>';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiTopSellers").'</th><th class="right">#</th></tr>';
foreach (array_slice($sellersTop, 0, $maxshown) as $s) {
	print '<tr class="oddeven"><td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.((int) $s->rowid).'">'.dol_escape_htmltag($s->nom).'</a></td><td class="right">'.$s->nb.'</td></tr>';
}
if (count($sellersTop) > $maxshown) {
	// The "More ..." line is the last element of the table and disappears once clicked
	print '<tr class="oddeven" id="sellersmorerow"><td colspan="2" class="center"><a href="javascript:void(0)" onclick="jQuery(\'tr.sellersextra\').show(); jQuery(this).closest(\'tr\').remove(); return false;">'.$langs->trans("More").' ...</a></td></tr>';
	foreach (array_slice($sellersTop, $maxshown) as $s) {
		print '<tr class="oddeven sellersextra" style="display:none"><td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.((int) $s->rowid).'">'.dol_escape_htmltag($s->nom).'</a></td><td class="right">'.$s->nb.'</td></tr>';
	}
}
print '</table></div>';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiByDolVersion").'</th><th class="right">#</th></tr>';
foreach (array_slice($byVersion, 0, $maxshown) as $v) {
	print '<tr class="oddeven"><td>'.dol_escape_htmltag($v->v ? $v->v : '-').'</td><td class="right">'.$v->nb.'</td></tr>';
}
if (count($byVersion) > $maxshown) {
	// The "More ..." line is the last element of the table and disappears once clicked
	print '<tr class="oddeven" id="versionsmorerow"><td colspan="2" class="center"><a href="javascript:void(0)" onclick="jQuery(\'tr.versionsextra\').show(); jQuery(this).closest(\'tr\').remove(); return false;">'.$langs->trans("More").' ...</a></td></tr>';
	foreach (array_slice($byVersion, $maxshown) as $v) {
		print '<tr class="oddeven versionsextra" style="display:none"><td>'.dol_escape_htmltag($v->v ? $v->v : '-').'</td><td class="right">'.$v->nb.'</td></tr>';
	}
}
print '</table></div></div>';

// List of modules
$form = new Form($db);

$varpage = $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $conf->main_checkbox_left_column); // This also change content of $arrayfields with user setup

// List of mass actions available
$arrayofmassactions = array();
if ($canValidate) {
	$arrayofmassactions = array(
		'massvalidate' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("PublishModule"),
		'massrefuse' => img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("RefuseModule"),
		'massunpublish' => img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("UnpublishModule"),
	);
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$selectedfields = $htmlofselectarray;
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$param = '&mode='.$listMode;
if ($search_ref !== '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label !== '') {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_seller !== '') {
	$param .= '&search_seller='.urlencode($search_seller);
}
if ($search_version !== '') {
	$param .= '&search_version='.urlencode($search_version);
}
if ($search_dolmin !== '') {
	$param .= '&search_dolmin='.urlencode($search_dolmin);
}
if ($search_dolmax !== '') {
	$param .= '&search_dolmax='.urlencode($search_dolmax);
}
if ($search_fork !== '') {
	$param .= '&search_fork='.urlencode($search_fork);
}
if ($search_duplicate !== '') {
	$param .= '&search_duplicate='.urlencode($search_duplicate);
}
if ($search_reason !== '') {
	$param .= '&search_reason='.urlencode($search_reason);
}
if ($search_submitted_dtstart) {
	$param .= '&search_submitted_dtstartmonth='.GETPOSTINT('search_submitted_dtstartmonth');
	$param .= '&search_submitted_dtstartday='.GETPOSTINT('search_submitted_dtstartday');
	$param .= '&search_submitted_dtstartyear='.GETPOSTINT('search_submitted_dtstartyear');
}
if ($search_submitted_dtend) {
	$param .= '&search_submitted_dtendmonth='.GETPOSTINT('search_submitted_dtendmonth');
	$param .= '&search_submitted_dtendday='.GETPOSTINT('search_submitted_dtendday');
	$param .= '&search_submitted_dtendyear='.GETPOSTINT('search_submitted_dtendyear');
}

print '<form id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="mode" value="'.$listMode.'">';
print '<input type="hidden" name="page_y" value="">';

print_barre_liste($langs->trans("ModuleList").' : '.$langs->trans('Filter_'.$listMode), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, count($list), $nbtotalofrecords, 'service', 0, '', '', $limit, 0, 0, 1);

// Confirmation box for the mass actions that require a reason
if ($massaction == 'massrefuse' || $massaction == 'massunpublish') {
	print '<input type="hidden" name="massaction" value="confirm_'.$massaction.'">';
	print load_fiche_titre($langs->trans($massaction == 'massrefuse' ? "RefuseModule" : "UnpublishModule"), '', '');
	print '<table class="noborder centpercent">';
	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("ReasonQuickFill").'</td><td>';
	print '<textarea class="flat minwidth500 maxwidthonsmartphone" name="massreason" rows="3" placeholder="'.dolPrintHTMLForAttribute($langs->trans("ReasonPlaceholder")).'"></textarea>';
	print '</td></tr>';
	print '<tr class="oddeven"><td></td><td>';
	print '<input type="submit" name="confirmmassaction" class="button" value="'.$langs->trans("Confirm").'">';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
	print '</table>';
}

$totalarray = array('nbfield' => 0);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">'."\n";

// Fields title search
print '<tr class="liste_titre_filter">';
// Action column
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons('left');
	print '</td>';
}
if (!empty($arrayfields['p.rowid']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
}
if (!empty($arrayfields['p.label']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
}
if (!empty($arrayfields['seller']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_seller" value="'.dol_escape_htmltag($search_seller).'"></td>';
}
if (!empty($arrayfields['version']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_version" value="'.dol_escape_htmltag($search_version).'"></td>';
}
if (!empty($arrayfields['dolmin']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_dolmin" value="'.dol_escape_htmltag($search_dolmin).'"></td>';
}
if (!empty($arrayfields['dolmax']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_dolmax" value="'.dol_escape_htmltag($search_dolmax).'"></td>';
}
if (!empty($arrayfields['fork']['checked'])) {
	print '<td class="liste_titre center">'.$form->selectarray('search_fork', array('1' => $langs->trans("Yes"), '0' => $langs->trans("No")), $search_fork, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100', 1).'</td>';
}
if (!empty($arrayfields['duplicate']['checked'])) {
	print '<td class="liste_titre center">'.$form->selectarray('search_duplicate', array('1' => $langs->trans("Yes"), '0' => $langs->trans("No")), $search_duplicate, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100', 1).'</td>';
}
if (!empty($arrayfields['reason']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_reason" value="'.dol_escape_htmltag($search_reason).'"></td>';
}
if (!empty($arrayfields['submitted']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print '<div class="nowrap">';
	print $form->selectDate($search_submitted_dtstart, 'search_submitted_dtstart', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_submitted_dtend, 'search_submitted_dtend', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['age']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['tosell']['checked'])) {
	print '<td class="liste_titre"></td>';
}
// Action column
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons();
	print '</td>';
}
print '</tr>'."\n";

// Fields title label
print '<tr class="liste_titre">';
// Action column
if ($conf->main_checkbox_left_column) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['p.rowid']['label'], $_SERVER["PHP_SELF"], "p.rowid", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.label']['checked'])) {
	print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['seller']['checked'])) {
	print_liste_field_titre($arrayfields['seller']['label'], $_SERVER["PHP_SELF"], "seller", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['version']['checked'])) {
	print_liste_field_titre($arrayfields['version']['label'], $_SERVER["PHP_SELF"], "version", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['dolmin']['checked'])) {
	print_liste_field_titre($arrayfields['dolmin']['label'], $_SERVER["PHP_SELF"], "dolmin", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['dolmax']['checked'])) {
	print_liste_field_titre($arrayfields['dolmax']['label'], $_SERVER["PHP_SELF"], "dolmax", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['fork']['checked'])) {
	print_liste_field_titre($arrayfields['fork']['label'], $_SERVER["PHP_SELF"], "fork", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['duplicate']['checked'])) {
	print_liste_field_titre($arrayfields['duplicate']['label'], $_SERVER["PHP_SELF"], "duplicate", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['reason']['checked'])) {
	print_liste_field_titre($arrayfields['reason']['label'], $_SERVER["PHP_SELF"], "reason", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['submitted']['checked'])) {
	print_liste_field_titre($arrayfields['submitted']['label'], $_SERVER["PHP_SELF"], "submitted", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['age']['checked'])) {
	print_liste_field_titre($arrayfields['age']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['tosell']['checked'])) {
	print_liste_field_titre($arrayfields['tosell']['label'], $_SERVER["PHP_SELF"], "tosell", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Action column
if (!$conf->main_checkbox_left_column) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$arrayofselected = is_array($toselect) ? $toselect : array();

// Single Societe instance reused for all rows to render the seller link
$moduleseller = new Societe($db);

// Single Product instance reused for all rows to render the on-sale status
$moduleproduct = new Product($db);

if (empty($list)) {
	print '<tr class="oddeven"><td colspan="'.$totalarray['nbfield'].'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}
foreach ($list as $o) {
	$sub = $db->jdate($o->submitted);
	$age = $sub ? floor((dol_now() - $sub) / 86400) : '';
	$cls = $age === '' ? '' : ($age > 14 ? 'error' : ($age > 7 ? 'warning' : ''));
	print '<tr class="oddeven">';
	// Action column
	if ($conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {
			$selected = in_array($o->rowid, $arrayofselected);
			print '<input id="cb'.$o->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$o->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	}
	if (!empty($arrayfields['p.rowid']['checked'])) {
		print '<td>'.$o->rowid.'</td>';
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td class="tdoverflowmax250"><a href="'.dol_buildpath('/marketplace/module_validation_card.php', 1).'?id='.$o->rowid.'">'.img_picto('', 'service', 'class="pictofixedwidth"').dol_escape_htmltag($o->ref).'</a></td>';
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		print '<td class="tdoverflowmax300"><div class="twolinesmax-normallineheight minwidth200onall"><span class="spantitle">'.dol_escape_htmltag($o->label).'</span></div></td>';
	}
	if (!empty($arrayfields['seller']['checked'])) {
		// Assign the properties of the reused object instead of instantiating a Societe for each row
		$moduleseller->id = (int) $o->seller_id;
		$moduleseller->nom = $o->seller;
		print '<td class="tdoverflowmax150">'.($o->seller_id ? $moduleseller->getNomUrl(1) : '&nbsp;').'</td>';
	}
	if (!empty($arrayfields['version']['checked'])) {
		print '<td>'.dol_escape_htmltag($o->version).'</td>';
	}
	if (!empty($arrayfields['dolmin']['checked'])) {
		print '<td>'.dol_escape_htmltag($o->dolmin).'</td>';
	}
	if (!empty($arrayfields['dolmax']['checked'])) {
		print '<td>'.dol_escape_htmltag($o->dolmax).'</td>';
	}
	if (!empty($arrayfields['fork']['checked'])) {
		print '<td class="center">'.($o->forkof ? $langs->trans("Yes") : '&nbsp;').'</td>';
	}
	if (!empty($arrayfields['duplicate']['checked'])) {
		print '<td class="center">'.($o->duplicateof ? $langs->trans("Yes") : '&nbsp;').'</td>';
	}
	if (!empty($arrayfields['reason']['checked'])) {
		print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($o->reason).'">'.dol_escape_htmltag($o->reason).'</td>';
	}
	if (!empty($arrayfields['submitted']['checked'])) {
		print '<td>'.dol_print_date($sub, 'dayhour').'</td>';
	}
	if (!empty($arrayfields['age']['checked'])) {
		print '<td class="'.$cls.'">'.($age === '' ? '&nbsp;' : $age.' j').'</td>';
	}
	if (!empty($arrayfields['tosell']['checked'])) {
		print '<td class="center nowrap">'.$moduleproduct->LibStatut($o->tosell, 5, 0).'</td>';
	}
	// Action column
	if (!$conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {
			$selected = in_array($o->rowid, $arrayofselected);
			print '<input id="cb'.$o->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$o->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	}
	print '</tr>';
}
print '</table>'."\n";
print '</div>'."\n";
print '</form>'."\n";

// End of page
llxFooter();
$db->close();
