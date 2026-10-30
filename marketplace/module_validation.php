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
dol_include_once('/marketplace/lib/marketplace.lib.php');

// Translations
$langs->loadLangs(array("marketplace@marketplace", "products"));

// Access control
if (!$user->hasRight('marketplace', 'module', 'read')) {
	accessforbidden();
}

// Parameters
$mode = GETPOST('mode', 'aZ09'); // pending | refused | online (default: pending)
$k = marketplaceValidationKeys();

// SQL columns of the extrafields (alias "ef")
$efSub = marketplaceCol('submitted');
$efReason = marketplaceCol('reason');
$efMin = marketplaceCol('dolmin');
$efDup = marketplaceCol('duplicateof');
$efFork = marketplaceCol('forkof');

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
$sql .= $base." GROUP BY s.rowid, s.nom ORDER BY nb DESC LIMIT 10";
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
$sql = "SELECT p.rowid, p.ref, p.label, p.tosell, COALESCE(".$efSub.", p.datec) as submitted, ".$efMin." as dolmin, ".marketplaceCol('version')." as version,";
$sql .= " (SELECT s.nom FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= "  WHERE pfp.fk_product = p.rowid LIMIT 1) as seller";
$sql .= $from.$base." AND ".$cond." ORDER BY ".$efSub." ASC LIMIT 200";
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

// Statistics tables
print '<div style="display:flex;flex-wrap:wrap;gap:20px;margin-bottom:20px">';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiTopReasons").'</th><th class="right">#</th></tr>';
foreach ($reasons as $r) {
	print '<tr class="oddeven"><td>'.dol_escape_htmltag($r->reason).'</td><td class="right">'.$r->nb.'</td></tr>';
}
print '</table></div>';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiTopSellers").'</th><th class="right">#</th></tr>';
foreach ($sellersTop as $s) {
	print '<tr class="oddeven"><td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.((int) $s->rowid).'">'.dol_escape_htmltag($s->nom).'</a></td><td class="right">'.$s->nb.'</td></tr>';
}
print '</table></div>';
print '<div style="flex:1 1 300px"><table class="noborder centpercent"><tr class="liste_titre"><th>'.$langs->trans("KpiByDolVersion").'</th><th class="right">#</th></tr>';
foreach ($byVersion as $v) {
	print '<tr class="oddeven"><td>'.dol_escape_htmltag($v->v ? $v->v : '-').'</td><td class="right">'.$v->nb.'</td></tr>';
}
print '</table></div></div>';

// List of modules
print load_fiche_titre($langs->trans("ModuleList").' : '.$langs->trans('Filter_'.$listMode), '', '');
print '<table class="noborder centpercent"><tr class="liste_titre">';
foreach (array('Ref', 'Label', 'Seller', 'ModuleVersion', 'DolibarrMin', 'DateRequestToBeOnline', 'Age') as $c) {
	print '<th>'.$langs->trans($c).'</th>';
}
print '</tr>';
if (empty($list)) {
	print '<tr><td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
}
$product = new Product($db);
foreach ($list as $o) {
	$sub = $db->jdate($o->submitted);
	$age = floor((dol_now() - $sub) / 86400);
	$cls = $age > 14 ? 'error' : ($age > 7 ? 'warning' : '');
	print '<tr class="oddeven">';
	print '<td><a href="'.dol_buildpath('/marketplace/module_validation_card.php', 1).'?id='.$o->rowid.'">'.img_picto('', 'service', 'class="pictofixedwidth"').dol_escape_htmltag($o->ref).'</a></td>';
	print '<td class="tdoverflowmax300">'.dol_escape_htmltag($o->label).'</td>';
	print '<td>'.dol_escape_htmltag($o->seller).'</td>';
	print '<td>'.dol_escape_htmltag($o->version).'</td>';
	print '<td>'.dol_escape_htmltag($o->dolmin).'</td>';
	print '<td>'.dol_print_date($sub, 'dayhour').'</td>';
	print '<td class="'.$cls.'">'.$age.' j</td></tr>';
}
print '</table>';

// End of page
llxFooter();
$db->close();
