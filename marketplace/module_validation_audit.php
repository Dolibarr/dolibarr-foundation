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
 * \file    marketplace/module_validation_audit.php
 * \ingroup marketplace
 * \brief   Audit of already published marketplace modules against current validation rules.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
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
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array:int');
$k = marketplaceValidationKeys();

// Pagination & sorting
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 'nb_errors';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}
$sortorder = dol_strtoupper($sortorder);
if ($sortorder != 'DESC') {
	$sortorder = 'ASC';
}

$contextpage = 'modulevalidationauditlist';
$canValidate = $user->hasRight('marketplace', 'module', 'validate');

// Search criteria
$search_rowid = trim(GETPOST('search_rowid', 'alpha'));
$search_ref = trim(GETPOST('search_ref', 'alphanohtml'));
$search_label = trim(GETPOST('search_label', 'alphanohtml'));
$search_seller = trim(GETPOST('search_seller', 'alphanohtml'));
$search_version = trim(GETPOST('search_version', 'alphanohtml'));
$search_dolmin = trim(GETPOST('search_dolmin', 'alphanohtml'));
$search_dolmax = trim(GETPOST('search_dolmax', 'alphanohtml'));
$search_rule = trim(GETPOST('search_rule', 'alphanohtml'));
$search_min_errors = GETPOSTINT('search_min_errors');

// SQL columns of the extrafields
$efSub = marketplaceCol('submitted');
$efReason = marketplaceCol('reason');
$efMin = marketplaceCol('dolmin');
$efMax = marketplaceCol('dolmax');
$efDup = marketplaceCol('duplicateof');
$efFork = marketplaceCol('forkof');
$efLaw = marketplaceCol('law');
$efVer = marketplaceCol('version');

// Columns definition
$arrayfields = array(
	'p.rowid' => array('label' => 'TechnicalID', 'checked' => 0, 'position' => 1),
	'p.ref' => array('label' => 'Ref', 'checked' => 1, 'position' => 10),
	'p.label' => array('label' => 'Label', 'checked' => 1, 'position' => 20),
	'seller' => array('label' => 'Seller', 'checked' => 1, 'position' => 30),
	'version' => array('label' => 'ModuleVersion', 'checked' => 1, 'position' => 40),
	'dolmin' => array('label' => 'DolibarrMin', 'checked' => 1, 'position' => 50),
	'dolmax' => array('label' => 'DolibarrMax', 'checked' => 1, 'position' => 51),
	'nb_errors' => array('label' => 'Anomalies', 'checked' => 1, 'position' => 60),
	'rules' => array('label' => 'FailedRules', 'checked' => 1, 'position' => 65),
	'submitted' => array('label' => 'DateRequestToBeOnline', 'checked' => 0, 'position' => 70),
);

// Categories that contain the marketplace modules
$catIds = marketplaceGetCategoryIds($db);
if (empty($catIds)) {
	setEventMessages('MARKETPLACE_ROOT_CATEGORY_ID is not defined', null, 'errors');
	$catIds = array(0);
}

/*
 * Actions
 */

$reasonKey = 'options_'.$k['reason'];
$massreason = trim(GETPOST('massreason', 'alphanohtml'));

if (GETPOST('cancel', 'alpha')) {
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha')) {
	$massaction = '';
}

$object = new Product($db);

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_rowid = '';
		$search_ref = '';
		$search_label = '';
		$search_seller = '';
		$search_version = '';
		$search_dolmin = '';
		$search_dolmax = '';
		$search_rule = '';
		$search_min_errors = 0;
		$toselect = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = '';
	}

	// Mass action: unpublish non-compliant modules (a reason is required)
	if ($massaction == 'confirm_massunpublish' && $canValidate) {
		if (dol_strlen($massreason) < 5) {
			setEventMessages($langs->trans("ReasonRequired"), null, 'errors');
		} else {
			$nbok = 0;
			$productstatic = new Product($db);
			foreach (marketplaceFilterModuleIds($db, $catIds, $toselect) as $toselectid) {
				$result = $productstatic->fetch($toselectid);
				if ($result <= 0) {
					continue;
				}
				$productstatic->array_options[$reasonKey] = dol_trunc($massreason, 255, 'right', 'UTF-8', 1);
				$productstatic->insertExtraFields();
				if ($productstatic->status) {
					$productstatic->setStatut(0, null, 'product', 'PRODUCT_MODIFY', 'tosell');
				}
				marketplaceLogValidationAction($db, $user, $productstatic, 'MKT_UNPUBLISH', 'Unpublish (audit): '.$productstatic->ref.' - '.$massreason);
				$nbok++;
			}
			setEventMessages($langs->trans("ModulesUnpublished", $nbok), null, 'mesgs');
		}
	}
}

/*
 * Audit analysis of all online modules (tosell = 1)
 */

/**
 * Extract failed rule codes and human-readable labels from marketplaceValidationChecks() output.
 *
 * @param  mixed $checks Return value of marketplaceValidationChecks()
 * @return array<string,string> Map of ruleCode => ruleLabel
 */
$extractFailedChecks = function ($checks) {
	$failed = array();
	if (!is_array($checks)) {
		return $failed;
	}
	foreach ($checks as $key => $item) {
		if (is_bool($item)) {
			if (!$item) {
				$code = (string) $key;
				$failed[$code] = $code;
			}
		} elseif (is_string($item) && $item !== '') {
			$code = is_string($key) ? $key : md5($item);
			$failed[$code] = $item;
		} elseif (is_array($item)) {
			$isOk = null;
			foreach (array('ok', 'valid', 'passed', 'success', 'status') as $flag) {
				if (array_key_exists($flag, $item)) {
					$isOk = (bool) $item[$flag];
					break;
				}
			}
			if ($isOk === false) {
				$label = $item['message'] ?? $item['error'] ?? $item['label'] ?? $item['desc'] ?? $item['title'] ?? (is_string($key) ? $key : 'Rule failed');
				$code = is_string($key) ? $key : ($item['code'] ?? $item['key'] ?? md5((string) $label));
				$failed[(string) $code] = (string) $label;
			}
		} elseif (is_object($item)) {
			$isOk = $item->ok ?? $item->valid ?? $item->passed ?? $item->status ?? null;
			if ($isOk === false || $isOk === 0) {
				$label = $item->message ?? $item->error ?? $item->label ?? (is_string($key) ? $key : 'Rule failed');
				$code = is_string($key) ? $key : ($item->code ?? md5((string) $label));
				$failed[(string) $code] = (string) $label;
			}
		}
	}
	return $failed;
};

// Quick counters for pending/refused shortcuts
$from = " FROM ".MAIN_DB_PREFIX."product as p";
$from .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef ON ef.fk_object = p.rowid";
$base = " WHERE p.rowid IN (SELECT cp.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_categorie IN (".implode(',', $catIds)."))";
$condPending = "p.tosell = 0 AND (".$efReason." IS NULL OR ".$efReason." = '') AND ".$efSub." IS NOT NULL";
$condRefused = "p.tosell = 0 AND ".$efReason." IS NOT NULL AND ".$efReason." <> ''";

$kpiPending = 0;
$kpiRefused = 0;
$sqlKpi = "SELECT SUM(CASE WHEN ".$condPending." THEN 1 ELSE 0 END) as pending,";
$sqlKpi .= " SUM(CASE WHEN ".$condRefused." THEN 1 ELSE 0 END) as refused";
$sqlKpi .= $from.$base;
$resqlKpi = $db->query($sqlKpi);
if ($resqlKpi && ($objKpi = $db->fetch_object($resqlKpi))) {
	$kpiPending = (int) $objKpi->pending;
	$kpiRefused = (int) $objKpi->refused;
}

// Load all online modules and run current validation checks
$sql = "SELECT p.rowid, p.ref, p.label, p.tosell, ".$efSub." as submitted,";
$sql .= " ".$efMin." as dolmin, ".$efMax." as dolmax, ".$efVer." as version,";
$sql .= " ".$efFork." as forkof, ".$efDup." as duplicateof, ".$efLaw." as law,";
$sql .= " (SELECT s.rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= "  WHERE pfp.fk_product = p.rowid LIMIT 1) as seller_id,";
$sql .= " (SELECT s.nom FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql .= "  WHERE pfp.fk_product = p.rowid LIMIT 1) as seller";
$sql .= $from.$base." AND p.tosell = 1";
$sql .= " ORDER BY p.ref ASC";

$totalOnline = 0;
$nbCompliant = 0;
$nbNonCompliant = 0;
$totalRuleFailures = 0;
$ruleStats = array();
$impactedSellers = array();
$allNonCompliant = array();

$productCheckObj = new Product($db);
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$totalOnline++;
		$resProd = $productCheckObj->fetch((int) $obj->rowid);
		if ($resProd <= 0) {
			continue;
		}
		$checks = marketplaceValidationChecks($db, $productCheckObj);
		$isValid = marketplaceChecksAreValid($checks);
		$failedRules = $extractFailedChecks($checks);

		if (!$isValid && empty($failedRules)) {
			$failedRules['validation_failed'] = $langs->trans("ValidationCheckFailed");
		}

		if ($isValid && empty($failedRules)) {
			$nbCompliant++;
			continue;
		}

		$nbNonCompliant++;
		$obj->failed_rules = $failedRules;
		$obj->nb_errors = count($failedRules);
		$totalRuleFailures += $obj->nb_errors;

		if (!empty($obj->seller_id)) {
			$impactedSellers[(int) $obj->seller_id] = true;
		}

		foreach ($failedRules as $rCode => $rLabel) {
			if (!isset($ruleStats[$rCode])) {
				$ruleStats[$rCode] = array('code' => $rCode, 'label' => $rLabel, 'count' => 0);
			}
			$ruleStats[$rCode]['count']++;
		}

		$allNonCompliant[] = $obj;
	}
}

// Sort broken rules by frequency descending
uasort($ruleStats, function ($a, $b) {
	return $b['count'] <=> $a['count'];
});

// Apply search filters to the non-compliant modules list
$filteredList = array();
foreach ($allNonCompliant as $o) {
	if ($search_rowid !== '' && (string) $o->rowid !== $search_rowid) {
		continue;
	}
	if ($search_ref !== '' && stripos((string) $o->ref, $search_ref) === false) {
		continue;
	}
	if ($search_label !== '' && stripos((string) $o->label, $search_label) === false) {
		continue;
	}
	if ($search_seller !== '' && stripos((string) $o->seller, $search_seller) === false) {
		continue;
	}
	if ($search_version !== '' && stripos((string) $o->version, $search_version) === false) {
		continue;
	}
	if ($search_dolmin !== '' && stripos((string) $o->dolmin, $search_dolmin) === false) {
		continue;
	}
	if ($search_dolmax !== '' && stripos((string) $o->dolmax, $search_dolmax) === false) {
		continue;
	}
	if ($search_rule !== '' && !isset($o->failed_rules[$search_rule])) {
		continue;
	}
	if ($search_min_errors > 0 && $o->nb_errors < $search_min_errors) {
		continue;
	}
	$filteredList[] = $o;
}

// Sort filtered list
usort($filteredList, function ($a, $b) use ($sortfield, $sortorder) {
	$dir = ($sortorder === 'DESC') ? -1 : 1;
	switch ($sortfield) {
		case 'p.rowid':
			return (((int) $a->rowid) <=> ((int) $b->rowid)) * $dir;
		case 'p.label':
			return strcasecmp((string) $a->label, (string) $b->label) * $dir;
		case 'seller':
			return strcasecmp((string) $a->seller, (string) $b->seller) * $dir;
		case 'version':
			return strcasecmp((string) $a->version, (string) $b->version) * $dir;
		case 'dolmin':
			return strnatcasecmp((string) $a->dolmin, (string) $b->dolmin) * $dir;
		case 'dolmax':
			return strnatcasecmp((string) $a->dolmax, (string) $b->dolmax) * $dir;
		case 'submitted':
			return strcmp((string) $a->submitted, (string) $b->submitted) * $dir;
		case 'nb_errors':
			$cmp = ($a->nb_errors <=> $b->nb_errors) * $dir;
			return $cmp !== 0 ? $cmp : strcasecmp((string) $a->ref, (string) $b->ref);
		case 'p.ref':
		default:
			return strcasecmp((string) $a->ref, (string) $b->ref) * $dir;
	}
});

// Pagination slice (emulating Dolibarr $limit + 1 pattern for print_barre_liste)
$nbtotalofrecords = count($filteredList);
if ($limit > 0 && $offset >= $nbtotalofrecords) {
	$page = 0;
	$offset = 0;
}
if ($limit > 0) {
	$slicePlusOne = array_slice($filteredList, $offset, $limit + 1);
	$num = count($slicePlusOne);
	$list = array_slice($slicePlusOne, 0, $limit);
} else {
	$list = $filteredList;
	$num = count($list);
}

/*
 * View
 */

$help_url = '';
$page_name = "MarketPlaceModuleValidation";
$self = $_SERVER['PHP_SELF'];
$valUrl = dol_buildpath('/marketplace/module_validation.php', 1);

llxHeader('', $langs->trans($page_name).' - '.$langs->trans("AuditPublishedModules"), '', '', 0, 0, '', '', '', 'mod-marketplace page-validation-audit');

print load_fiche_titre($langs->trans($page_name), '', 'fa-puzzle-piece');

// Navigation tabs (Validation / Audit)
$head = array();
$head[0][0] = $valUrl;
$head[0][1] = $langs->trans("Validation");
$head[0][2] = 'validation';

$head[1][0] = $self;
$head[1][1] = $langs->trans("AuditPublishedModules");
$head[1][2] = 'audit';

print dol_get_fiche_head($head, 'audit', '', -1);

$renderInfoBoxLine = function ($label, $value, $url = '', $badgeClass = 'badge badge-info', $warningText = '', $warningTitle = '') {
	$out = '<div class="info-box-line spanoverflow nowrap">';
	if ($url) {
		$out .= '<a href="'.$url.'" class="info-box-text info-box-text-a">';
		$out .= '<div class="marginrightonly inline-block valignmiddle info-box-line-text" title="'.dol_escape_htmltag($label).'">'.dol_escape_htmltag($label).'</div>';
		$out .= '</a>';
	} else {
		$out .= '<div class="marginrightonly inline-block valignmiddle info-box-line-text" title="'.dol_escape_htmltag($label).'">'.dol_escape_htmltag($label).'</div>';
	}
	$out .= '<div class="inline-block nowraponall">';
	if ($url) {
		$out .= '<a href="'.$url.'" class="info-box-text info-box-text-a">';
	}
	$out .= '<span class="'.dol_escape_htmltag($badgeClass).'">'.dol_escape_htmltag((string) $value).'</span>';
	if ($url) {
		$out .= '</a>';
	}
	if ($warningText !== '') {
		$out .= ' <span class="info-box-text info-box-text-a displaycontents">';
		$out .= '<span title="'.dol_escape_htmltag($warningTitle).'" class="classfortooltip badge badge-warning">';
		$out .= dol_escape_htmltag($warningText);
		$out .= '</span></span>';
	}
	$out .= '</div></div>'."\n";
	return $out;
};

$renderInfoBox = function ($title, $bgClass, $iconClass, $linesHtml) {
	$out = '<div class="box-flex-item"><div class="box-flex-item-with-margin">'."\n";
	$out .= '	<div class="info-box info-box-sm">'."\n";
	$out .= '		<span class="info-box-icon '.dol_escape_htmltag($bgClass).'"><i class="fa '.dol_escape_htmltag($iconClass).'"></i></span>'."\n";
	$out .= '		<div class="info-box-content">'."\n";
	$out .= '			<div class="info-box-title" title="'.dolPrintHTMLForAttribute($title).'">'.dol_escape_htmltag($title).'</div>'."\n";
	$out .= '			<div class="info-box-lines">'."\n";
	$out .= $linesHtml;
	$out .= '			</div>'."\n";
	$out .= '		</div>'."\n";
	$out .= '	</div>'."\n";
	$out .= '</div></div>'."\n";
	return $out;
};

$pctCompliant = $totalOnline > 0 ? round($nbCompliant / $totalOnline * 100, 1).'%' : '0%';
$pctNonCompliant = $totalOnline > 0 ? round($nbNonCompliant / $totalOnline * 100, 1).'%' : '0%';

// Native Dolibarr dashboard cards
print '<div class="fichecenter">';
print '<div class="opened-dash-board-wrap"><div class="box-flex-container">';

// Card 1: Catalog compliance overview
$lines = $renderInfoBoxLine($langs->trans("OnlineModulesScanned"), $totalOnline, $self, 'badge badge-info');
$lines .= $renderInfoBoxLine($langs->trans("CompliantModules"), $nbCompliant.' ('.$pctCompliant.')', '', 'badge badge-info');
$lines .= $renderInfoBoxLine(
	$langs->trans("NonCompliantModules"),
	$nbNonCompliant,
	$self,
	$nbNonCompliant > 0 ? 'badge badge-warning' : 'opacitymedium',
	$nbNonCompliant > 0 ? $pctNonCompliant : '',
	$langs->trans("NonCompliantModules")
);
print $renderInfoBox($langs->trans("OnlineCatalogHealth"), 'bg-infobox-commande', 'fa-shield-alt', $lines);

// Card 2: Rule violations summary
$lines = $renderInfoBoxLine($langs->trans("TotalRuleViolations"), $totalRuleFailures, '', $totalRuleFailures > 0 ? 'badge badge-warning' : 'opacitymedium');
$lines .= $renderInfoBoxLine($langs->trans("DistinctRulesViolated"), count($ruleStats), '', count($ruleStats) > 0 ? 'badge badge-info' : 'opacitymedium');
$lines .= $renderInfoBoxLine($langs->trans("ImpactedSellers"), count($impactedSellers), '', count($impactedSellers) > 0 ? 'badge badge-info' : 'opacitymedium');
print $renderInfoBox($langs->trans("AuditAnomalies"), 'bg-infobox-holiday', 'fa-exclamation-triangle', $lines);

// Card 3: Workflow shortcuts
$lines = $renderInfoBoxLine($langs->trans("KpiPending"), $kpiPending, $valUrl.'?mode=pending', $kpiPending > 0 ? 'badge badge-info' : 'opacitymedium');
$lines .= $renderInfoBoxLine($langs->trans("KpiRefused"), $kpiRefused, $valUrl.'?mode=refused', $kpiRefused > 0 ? 'badge badge-info' : 'opacitymedium');
print $renderInfoBox($langs->trans("ValidationWorkflow"), 'bg-infobox-propal', 'fa-tasks', $lines);

for ($f = 1; $f <= 10; $f++) {
	print '<div class="box-flex-item filler"></div>';
}

print '</div></div>';
print '</div>';
print '<div class="clearboth"></div>';

// Breakdown table of violated rules (clickable to filter)
if (!empty($ruleStats)) {
	$maxshown = 5;
	$ruleValues = array_values($ruleStats);
	print '<div style="margin-top:10px;margin-bottom:20px">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("AuditRulesBreakdown").'</th>';
	print '<th class="right">'.$langs->trans("ModulesImpacted").'</th>';
	print '<th class="right">%</th>';
	print '</tr>';

	$renderRuleRow = function ($r, $extra = false) use ($self, $search_rule, $totalOnline) {
		$pct = $totalOnline > 0 ? round($r['count'] / $totalOnline * 100, 1) : 0;
		$isSelected = ($search_rule === $r['code']);
		$url = $isSelected ? $self : $self.'?search_rule='.urlencode($r['code']);
		$out = '<tr class="oddeven'.($extra ? ' auditrulesextra" style="display:none' : '').'">';
		$out .= '<td><a href="'.$url.'">'.($isSelected ? '<strong>' : '').dol_escape_htmltag($r['label']).($isSelected ? '</strong>' : '').'</a></td>';
		$out .= '<td class="right"><span class="badge badge-warning">'.$r['count'].'</span></td>';
		$out .= '<td class="right opacitymedium">'.$pct.'%</td>';
		$out .= '</tr>';
		return $out;
	};

	foreach (array_slice($ruleValues, 0, $maxshown) as $r) {
		print $renderRuleRow($r, false);
	}
	if (count($ruleValues) > $maxshown) {
		print '<tr class="oddeven"><td colspan="3" class="center"><a href="javascript:void(0)" onclick="jQuery(\'tr.auditrulesextra\').show(); jQuery(this).closest(\'tr\').remove(); return false;">'.$langs->trans("More").' ...</a></td></tr>';
		foreach (array_slice($ruleValues, $maxshown) as $r) {
			print $renderRuleRow($r, true);
		}
	}
	print '</table>';
	print '</div>';
}

// List of non-compliant online modules
$form = new Form($db);
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $contextpage, $conf->main_checkbox_left_column);

$arrayofmassactions = array();
if ($canValidate) {
	$arrayofmassactions = array(
		'massunpublish' => img_picto('', 'stop-circle', 'class="pictofixedwidth"').$langs->trans("UnpublishModule"),
	);
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$selectedfields = $htmlofselectarray;
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_rowid !== '') {
	$param .= '&search_rowid='.urlencode($search_rowid);
}
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
if ($search_rule !== '') {
	$param .= '&search_rule='.urlencode($search_rule);
}
if ($search_min_errors > 0) {
	$param .= '&search_min_errors='.((int) $search_min_errors);
}

print '<form id="searchFormList" action="'.$self.'" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';

print_barre_liste($langs->trans("NonCompliantOnlineModules"), $page, $self, $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'service', 0, '', '', $limit, 0, 0, 1);

// Confirmation box for mass unpublish
if ($massaction == 'massunpublish') {
	print '<input type="hidden" name="massaction" value="confirm_massunpublish">';
	print load_fiche_titre($langs->trans("UnpublishModule"), '', '');
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

// Rule dropdown options
$ruleOptions = array();
foreach ($ruleStats as $rCode => $rInfo) {
	$ruleOptions[$rCode] = dol_trunc($rInfo['label'], 60).' ('.$rInfo['count'].')';
}

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">'."\n";

// Filter row
print '<tr class="liste_titre_filter">';
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons('left');
	print '</td>';
}
if (!empty($arrayfields['p.rowid']['checked'])) {
	print '<td class="liste_titre center"><input type="text" class="flat maxwidth50" name="search_rowid" value="'.dol_escape_htmltag($search_rowid).'"></td>';
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
if (!empty($arrayfields['nb_errors']['checked'])) {
	print '<td class="liste_titre center"><input type="text" class="flat maxwidth40" name="search_min_errors" placeholder="&ge;1" value="'.($search_min_errors > 0 ? (int) $search_min_errors : '').'"></td>';
}
if (!empty($arrayfields['rules']['checked'])) {
	print '<td class="liste_titre">'.$form->selectarray('search_rule', $ruleOptions, $search_rule, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth250', 1).'</td>';
}
if (!empty($arrayfields['submitted']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	print $form->showFilterButtons();
	print '</td>';
}
print '</tr>'."\n";

// Title row
print '<tr class="liste_titre">';
if ($conf->main_checkbox_left_column) {
	print_liste_field_titre($selectedfields, $self, "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['p.rowid']['label'], $self, "p.rowid", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $self, "p.ref", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.label']['checked'])) {
	print_liste_field_titre($arrayfields['p.label']['label'], $self, "p.label", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['seller']['checked'])) {
	print_liste_field_titre($arrayfields['seller']['label'], $self, "seller", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['version']['checked'])) {
	print_liste_field_titre($arrayfields['version']['label'], $self, "version", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['dolmin']['checked'])) {
	print_liste_field_titre($arrayfields['dolmin']['label'], $self, "dolmin", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['dolmax']['checked'])) {
	print_liste_field_titre($arrayfields['dolmax']['label'], $self, "dolmax", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['nb_errors']['checked'])) {
	print_liste_field_titre($arrayfields['nb_errors']['label'], $self, "nb_errors", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['rules']['checked'])) {
	print_liste_field_titre($arrayfields['rules']['label'], $self, "", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['submitted']['checked'])) {
	print_liste_field_titre($arrayfields['submitted']['label'], $self, "submitted", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!$conf->main_checkbox_left_column) {
	print_liste_field_titre($selectedfields, $self, "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

$arrayofselected = is_array($toselect) ? $toselect : array();
$moduleseller = new Societe($db);

if (empty($list)) {
	print '<tr class="oddeven"><td colspan="'.$totalarray['nbfield'].'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}
foreach ($list as $o) {
	print '<tr class="oddeven">';
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
	if (!empty($arrayfields['nb_errors']['checked'])) {
		print '<td class="center"><span class="badge badge-warning">'.((int) $o->nb_errors).'</span></td>';
	}
	if (!empty($arrayfields['rules']['checked'])) {
		print '<td>';
		foreach ($o->failed_rules as $rLabel) {
			print '<div class="marginbottomonly"><span class="badge badge-warning"><i class="fa fa-exclamation-triangle"></i> '.dol_escape_htmltag($rLabel).'</span></div>';
		}
		print '</td>';
	}
	if (!empty($arrayfields['submitted']['checked'])) {
		$sub = $db->jdate($o->submitted);
		print '<td>'.($sub ? dol_print_date($sub, 'dayhour') : '&nbsp;').'</td>';
	}
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

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
