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
 *	\file       marketplace/module_validation.php
 *	\ingroup    marketplace
 *	\brief      Home page of marketplace top menu
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
dol_include_once('/marketplace/lib/marketplace.lib.php');

$langs->loadLangs(array("marketplace@marketplace", "products", "other"));

if (!$user->hasRight('marketplace', 'module', 'read')) {
	accessforbidden();
}

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$reason = GETPOST('reason', 'alphanohtml');

$object = new Product($db);
if ($object->fetch($id) <= 0 || $object->type != Product::TYPE_SERVICE) {
	accessforbidden('Service not found');
}
$object->fetch_optionals();
if (getDolGlobalInt('MAIN_MULTILANGS') && empty($object->multilangs)) {
	$object->getMultiLangs();
}

$canValidate = $user->hasRight('marketplace', 'module', 'validate');
$reasonKey = 'options_'.marketplaceValidationKeys()['reason'];
$backurl = $_SERVER['PHP_SELF'].'?id='.$object->id;

/* ---------- Actions ---------- */
if (in_array($action, array('validate', 'refuse', 'unpublish')) && $_SERVER['REQUEST_METHOD'] == 'POST') {
	if (!$canValidate) {
		accessforbidden();
	}
	if (GETPOST('token') != newToken()) {
		accessforbidden('Bad token');
	}
	$error = 0;

	if ($action == 'validate') {
		$checks = marketplaceValidationChecks($db, $object);
		if (!marketplaceChecksAreValid($checks)) {
			setEventMessages($langs->trans("ValidationBlockedByErrors"), null, 'errors');
			$error++;
		} else {
			$object->array_options[$reasonKey] = '';
			$object->insertExtraFields();
			$object->setStatut(1, null, 'product', 'PRODUCT_MODIFY', 'tosell');
			marketplaceLogValidationAction($db, $user, $object, 'MKT_VALIDATE', 'Module published: '.$object->ref);
			setEventMessages($langs->trans("ModulePublished"), null, 'mesgs');
		}
	} else {
		if (dol_strlen(trim($reason)) < 5) {
			setEventMessages($langs->trans("ReasonRequired"), null, 'errors');
			$error++;
		} else {
			$object->array_options[$reasonKey] = dol_trunc($reason, 255, 'right', 'UTF-8', 1);
			$object->insertExtraFields();
			if ($object->status) {
				$object->setStatut(0, null, 'product', 'PRODUCT_MODIFY', 'tosell');
			}
			$code = ($action == 'refuse') ? 'MKT_REFUSE' : 'MKT_UNPUBLISH';
			marketplaceLogValidationAction($db, $user, $object, $code, ucfirst($action).': '.$object->ref.' - '.$reason);
			if (GETPOSTINT('sendmail')) {
				$err = marketplaceSendValidationMail($db, $object, $action, $reason);
				if ($err) {
					setEventMessages($langs->trans("MailNotSent").' : '.$err, null, 'warnings');
				} else {
					setEventMessages($langs->trans("MailSent"), null, 'mesgs');
				}
			}
		}
	}
	if (!$error) {
		header("Location: ".dol_buildpath('/marketplace/module_validation.php', 1));
		exit;
	}
	$action = '';
}

/*
 * View
 */
$help_url = '';
$page_name = "ModuleValidation";

llxHeader('', $langs->trans($page_name).' '.$object->ref, '', '', 0, 0, '', '', '', 'mod-marketplace page-validation-card');

// Subheader
$linkback = '<a href="'.dol_buildpath('/marketplace/module_validation.php', 1).'">'.$langs->trans("BackToList").'</a>';

print load_fiche_titre($langs->trans($page_name).' : '.$object->ref, $linkback, 'fa-puzzle-piece');

$checks = marketplaceValidationChecks($db, $object);
$valid = marketplaceChecksAreValid($checks);
$files = marketplaceGetFiles($object);
$sellers = marketplaceGetSellers($db, $object->id);
$e = function ($alias) use ($object) {
	return dol_escape_htmltag(marketplaceExtra($object, $alias));
};

print '<div style="display:flex;flex-wrap:wrap;gap:20px">';

// Colonne gauche : informations
print '<div style="flex:2 1 500px">';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td>'.dol_escape_htmltag($object->label).'</td></tr>';
print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(5, 0).'</td></tr>';
print '<tr><td>'.$langs->trans("Seller").'</td><td>';
foreach ($sellers as $s) {
	print $s->getNomUrl(1, 'supplier').' '.dol_print_email($s->email, 0, $s->id, 1).'<br>';
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("DateRequestToBeOnline").'</td><td>'.dol_print_date(marketplaceExtra($object, 'submitted'), 'dayhour').'</td></tr>';
print '<tr><td>'.$langs->trans("ModuleVersion").'</td><td>'.$e('version').'</td></tr>';
print '<tr><td>Dolibarr min / max</td><td>'.$e('dolmin').' / '.$e('dolmax').'</td></tr>';
print '<tr><td>PHP min / max</td><td>'.$e('phpmin').' / '.$e('phpmax').'</td></tr>';
print '<tr><td>'.$langs->trans("ModuleKeywords").'</td><td>'.$e('keywords').'</td></tr>';
print '<tr><td>'.$langs->trans("HowtoContactSupport").'</td><td>'.$e('support').'</td></tr>';
print '<tr><td>'.$langs->trans("Price").'</td><td>'.price($object->price, 0, $langs, 1, -1, -1, $conf->currency).' '.$e('pricecomment').'</td></tr>';
print '<tr><td>'.$langs->trans("ComplementaryPriceHT").'</td><td>'.$e('compprice').'</td></tr>';
print '<tr><td>'.$langs->trans("MarketplaceValidityDuration").'</td><td>'.$e('duration').'</td></tr>';
print '<tr><td>'.$langs->trans("DisclaimerMessage").'</td><td>'.$e('disclaimer').'</td></tr>';
print '<tr><td>'.$langs->trans("WantToIncludeSourceInCore").'</td><td>'.yn((int) marketplaceExtra($object, 'coresource')).'</td></tr>';
foreach (array('duplicateof' => 'DuplicateOf', 'forkof' => 'ForkOf') as $alias => $lab) {
	$val = marketplaceExtra($object, $alias);
	if ($val !== '' && $val > 0) {
		$other = new Product($db);
		$other->fetch((int) $val);
		print '<tr><td>'.$langs->trans($lab).'</td><td class="warning">'.$other->getNomUrl(1).'</td></tr>';
	}
}
print '<tr><td>'.$langs->trans("LawViolation").'</td><td>'.$e('law').'</td></tr>';
print '<tr><td>'.$langs->trans("LastReasonDisabled").'</td><td>'.$e('reason').'</td></tr>';
print '</table><br>';

// Descriptions par langue
print load_fiche_titre($langs->trans("Description"), '', '');
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">Default</td><td>'.dolPrintHTML($object->description).'</td></tr>';
foreach (array('fr_FR', 'en_US', 'de_DE', 'it_IT', 'es_ES') as $code) {
	$ml = isset($object->multilangs[$code]) ? $object->multilangs[$code] : array();
	$note = $ml['note'] ?? ($ml['other'] ?? '');
	print '<tr><td>'.$code.'</td><td><b>'.dol_escape_htmltag($ml['label'] ?? '').'</b><br>'.dolPrintHTML($ml['description'] ?? '');
	if ($note) {
		print '<br><span class="opacitymedium">Note : '.dolPrintHTML($note).'</span>';
	}
	print '</td></tr>';
}
print '</table>';
print '</div>';

// Colonne droite : controles, fichiers, actions
print '<div style="flex:1 1 320px">';
print load_fiche_titre($langs->trans("ValidationChecks"), '', '');
print '<table class="noborder centpercent">';
$icons = array('ok' => img_picto('', 'tick', 'class="pictofixedwidth"'), 'warning' => img_picto('', 'warning', 'class="pictofixedwidth"'), 'error' => img_picto('', 'error', 'class="pictofixedwidth"'));
$bg = array('ok' => '', 'warning' => 'background:#fff4e0', 'error' => 'background:#fde8e8');
foreach ($checks as $c) {
	print '<tr style="'.$bg[$c['level']].'"><td>'.$icons[$c['level']].dol_escape_htmltag($c['label']).'</td><td class="right opacitymedium">'.dol_escape_htmltag($c['detail']).'</td></tr>';
}
print '</table><br>';

print load_fiche_titre($langs->trans("Files"), '', '');
$urlfile = DOL_URL_ROOT.'/document.php?modulepart=produit&file=';
foreach ($files['zips'] as $f) {
	print img_mime($f['name']).' <a href="'.$urlfile.urlencode(dol_sanitizeFileName($object->ref).'/'.$f['name']).'&entity='.((int) $object->entity).'">'.dol_escape_htmltag($f['name']).'</a> ('.dol_print_size($f['size'], 1, 1).')<br>';
}
print '<br><div style="display:flex;flex-wrap:wrap;gap:8px">';
foreach ($files['images'] as $f) {
	$u = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.((int) $object->entity).'&file='.urlencode(dol_sanitizeFileName($object->ref).'/'.$f['name']);
	print '<a href="'.$u.'" target="_blank"><img src="'.$u.'" style="max-width:150px;max-height:110px;border:1px solid #ddd"></a>';
}
print '</div><br>';

if ($canValidate) {
	print load_fiche_titre($langs->trans("Actions"), '', '');
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<textarea name="reason" class="centpercent" rows="4" placeholder="'.dol_escape_htmltag($langs->trans("ReasonPlaceholder")).'">'.dol_escape_htmltag($reason).'</textarea>';
	print '<br><label><input type="checkbox" name="sendmail" value="1" checked> '.$langs->trans("SendEmailToSeller").'</label><br><br>';
	if (!$object->status) {
		print '<button type="submit" name="action" value="validate" class="butAction"'.($valid ? '' : ' disabled title="'.dol_escape_htmltag($langs->trans("ValidationBlockedByErrors")).'"').'>'.$langs->trans("PublishModule").'</button> ';
		print '<button type="submit" name="action" value="refuse" class="butActionDelete">'.$langs->trans("RefuseModule").'</button>';
	} else {
		print '<button type="submit" name="action" value="unpublish" class="butActionDelete">'.$langs->trans("UnpublishModule").'</button>';
	}
	print '</form>';
}
print '</div></div>';

llxFooter();
$db->close();