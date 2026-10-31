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
 * \file    marketplace/module_validation_card.php
 * \ingroup marketplace
 * \brief   Validation card of a marketplace module (service): all the information on a
 *          single page, automatic checks, and actions (publish, refuse, unpublish).
 *
 * Parameters:
 * - id   : ID of the service
 * - mode : list used for the previous/next navigation (pending | refused | online)
 *
 * Actions (POST + CSRF token, permission marketplace->module->validate):
 * - validate  : sets the service on sale (blocked if a check is in error)
 * - refuse    : keeps the service off sale and stores the reason
 * - unpublish : sets an online service off sale and stores the reason
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
dol_include_once('/marketplace/lib/marketplace.lib.php');

// Translations
$langs->loadLangs(array("marketplace@marketplace", "products", "other"));

// Access control
if (!$user->hasRight('marketplace', 'module', 'read')) {
	accessforbidden();
}

// Parameters
$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$reason = GETPOST('reason', 'alphanohtml');
$mode = GETPOST('mode', 'aZ09') ? GETPOST('mode', 'aZ09') : 'pending';

// Load the service with its extrafields and translations
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

// Navigation within the current list (previous / next module)
$ids = marketplaceGetListIds($db, $mode);
$pos = array_search((int) $object->id, $ids, true);
$prevId = ($pos !== false && $pos > 0) ? $ids[$pos - 1] : 0;
$nextId = ($pos !== false && isset($ids[$pos + 1])) ? $ids[$pos + 1] : 0;
$urlList = dol_buildpath('/marketplace/module_validation.php', 1).'?mode='.$mode;
$urlCard = dol_buildpath('/marketplace/module_validation_card.php', 1).'?mode='.$mode.'&id=';

/*
 *  Actions
 */
if (in_array($action, array('validate', 'refuse', 'unpublish')) && $_SERVER['REQUEST_METHOD'] == 'POST') {
	if (!$canValidate) {
		accessforbidden();
	}
	if (GETPOST('token') != newToken()) {
		accessforbidden('Bad token');
	}
	$error = 0;

	if ($action == 'validate') {
		// Publication is refused by the server if a blocking check fails
		$checks = marketplaceValidationChecks($db, $object);
		if (!marketplaceChecksAreValid($checks)) {
			setEventMessages($langs->trans("ValidationBlockedByErrors"), null, 'errors');
			$error++;
		} else {
			// Clear the previous reason, then put the service on sale
			$object->array_options[$reasonKey] = '';
			$object->insertExtraFields();
			$object->setStatut(1, null, 'product', 'PRODUCT_MODIFY', 'tosell');
			marketplaceLogValidationAction($db, $user, $object, 'MKT_VALIDATE', 'Module published: '.$object->ref);
			setEventMessages($langs->trans("ModulePublished"), null, 'mesgs');
		}
	} else {
		// Refuse or unpublish: a reason is mandatory
		if (dol_strlen(trim($reason)) < 5) {
			setEventMessages($langs->trans("ReasonRequired"), null, 'errors');
			$error++;
		} else {
			// The extrafield is limited to 255 characters, the email keeps the full text
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
		// Go to the next module of the list (or the previous one, or back to the list)
		$target = $nextId ? $nextId : $prevId;
		header("Location: ".($target ? $urlCard.$target : $urlList));
		exit;
	}
	$action = '';
}

/*
 * View
 */
$help_url = '';
$page_name = "ModuleValidation";

$checks = marketplaceValidationChecks($db, $object);
$valid = marketplaceChecksAreValid($checks);
$files = marketplaceGetFiles($object);
$sellers = marketplaceGetSellers($db, $object->id);

// Counters and texts used by the "set the reason" buttons
$nbErr = 0;
$nbWarn = 0;
$reasonsErr = array();
$reasonsAll = array();
foreach ($checks as $c) {
	if ($c['level'] == 'error') {
		$nbErr++;
	} elseif ($c['level'] == 'warning') {
		$nbWarn++;
	}
	if ($c['level'] != 'ok' && $c['reason'] !== '') {
		$reasonsAll[] = '- '.$c['reason'];
		if ($c['level'] == 'error') {
			$reasonsErr[] = '- '.$c['reason'];
		}
	}
}

/**
 * Get the escaped value of a marketplace extrafield of the current service.
 *
 * @param  string $alias Alias defined in marketplaceValidationKeys()
 * @return string        HTML-escaped value
 */
$e = function ($alias) use ($object) {
	return dol_escape_htmltag(marketplaceExtra($object, $alias));
};

/**
 * Build a row "label / value" of an information table.
 *
 * @param  string $label Label (already escaped)
 * @param  string $value Value (already escaped)
 * @return string        HTML of the row
 */
$row = function ($label, $value) {
	return '<tr class="oddeven"><td>'.$label.'</td><td>'.$value.'</td></tr>';
};

llxHeader('', $langs->trans($page_name).' '.$object->ref, '', '', 0, 0, '', '', '', 'mod-marketplace page-validation-card');

// Subheader
$linkback = '<a href="'.dol_buildpath('/marketplace/module_validation.php', 1).'">'.$langs->trans("BackToList").'</a>';

print load_fiche_titre($langs->trans($page_name).' : '.$object->ref, $linkback, 'fa-puzzle-piece');

print '<style>
#mkt-nav{position:sticky;top:0;z-index:50;display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:6px 10px;margin-bottom:12px;background:var(--colorbacktitle1,#e9eaed);border:1px solid var(--colorbordertitle,#ccc);border-radius:4px}
#mkt-nav .sep{border-left:1px solid #bbb;height:20px}
.mkt-anchor{scroll-margin-top:60px}
.mkt-cols{display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start}
.mkt-cols > div{min-width:0}
.mkt-lvl-error td{background:#fde8e8 !important}
.mkt-lvl-warning td{background:#fff4e0 !important}
.mkt-reasonbtn{margin:2px}
</style>';

// Navigation bar: back to list, previous / next, anchors, counters
print '<div id="mkt-nav">';
print '<a class="button smallpaddingimp" href="'.$urlList.'">'.img_picto('', 'back', 'class="pictofixedwidth"').$langs->trans("BackToList").'</a>';
print '<span class="sep"></span>';
print $prevId ? '<a class="button smallpaddingimp" href="'.$urlCard.$prevId.'">&laquo; '.$langs->trans("Previous").'</a>' : '<span class="button smallpaddingimp opacitymedium">&laquo; '.$langs->trans("Previous").'</span>';
print '<b>'.($pos !== false ? ($pos + 1).' / '.count($ids) : '-').'</b>';
print $nextId ? '<a class="button smallpaddingimp" href="'.$urlCard.$nextId.'">'.$langs->trans("Next").' &raquo;</a>' : '<span class="button smallpaddingimp opacitymedium">'.$langs->trans("Next").' &raquo;</span>';
print '<span class="sep"></span>';
foreach (array('mkt-infos' => 'Infos', 'mkt-desc' => 'Description', 'mkt-files' => 'Files', 'mkt-checks' => 'ValidationChecks', 'mkt-actions' => 'Actions') as $anchor => $lab) {
	print '<a href="#'.$anchor.'">'.$langs->trans($lab).'</a>';
}
print '<span class="sep"></span>';
print '<span><span class="badge badge-status8">'.$nbErr.' '.$langs->trans("Errors").'</span> <span class="badge badge-status1">'.$nbWarn.' '.$langs->trans("Warnings").'</span></span>';
print '<span style="margin-left:auto"><b>'.dol_escape_htmltag($object->ref).'</b> - '.dol_escape_htmltag(dol_trunc($object->label, 40)).'</span>';
print '</div>';

print '<div class="mkt-cols">';

// Left column: information and descriptions
print '<div style="flex:3 1 560px">';

print '<table class="noborder centpercent"><tr class="liste_titre"><th colspan="2" id="mkt-infos" class="mkt-anchor">'.$langs->trans("Infos").'</th></tr>';
print $row($langs->trans("Ref"), $object->getNomUrl(1));
print $row($langs->trans("Label"), dol_escape_htmltag($object->label));
print $row($langs->trans("Status"), $object->getLibStatut(5, 0));
$sellerHtml = '';
foreach ($sellers as $s) {
	$sellerHtml .= $s->getNomUrl(1, 'supplier').' '.dol_print_email($s->email, 0, $s->id, 1).'<br>';
}
print $row($langs->trans("Seller"), $sellerHtml);
print $row($langs->trans("DateRequestToBeOnline"), dol_print_date(marketplaceExtra($object, 'submitted'), 'dayhour'));
print $row($langs->trans("ModuleVersion"), $e('version'));
print $row('Dolibarr min / max', $e('dolmin').' / '.$e('dolmax'));
print $row('PHP min / max', $e('phpmin').' / '.$e('phpmax'));
print $row($langs->trans("ModuleKeywords"), $e('keywords'));
print $row($langs->trans("HowtoContactSupport"), $e('support'));
print $row($langs->trans("Price"), price($object->price, 0, $langs, 1, -1, -1, $conf->currency).' '.$e('pricecomment'));
print $row($langs->trans("ComplementaryPriceHT"), $e('compprice'));
print $row($langs->trans("MarketplaceValidityDuration"), $e('duration'));
print $row($langs->trans("DisclaimerMessage"), $e('disclaimer'));
print $row($langs->trans("WantToIncludeSourceInCore"), yn((int) marketplaceExtra($object, 'coresource')));
// Original module when the service is declared as a copy or a fork
foreach (array('duplicateof' => 'DuplicateOf', 'forkof' => 'ForkOf') as $alias => $lab) {
	$val = marketplaceExtra($object, $alias);
	if ($val !== '' && $val > 0) {
		$other = new Product($db);
		$other->fetch((int) $val);
		print $row($langs->trans($lab), $other->getNomUrl(1));
	}
}
print $row($langs->trans("LawViolation"), $e('law'));
print $row($langs->trans("LastReasonDisabled"), $e('reason'));
print '</table><br>';

// Label, description and note in each language
$urlTrad = DOL_URL_ROOT.'/product/traduction.php?action=edit&token='.newToken().'&id='.$object->id;
print '<table class="noborder centpercent"><tr class="liste_titre"><th id="mkt-desc" class="mkt-anchor">'.$langs->trans("Language").'</th><th>'.$langs->trans("Label").'</th><th>'.$langs->trans("DescriptionAndNote").'</th><th class="right"><a href="'.$urlTrad.'">'.img_picto($langs->trans("Modify"), 'edit').'</a></th></tr>';
print '<tr class="oddeven"><td class="tdtop">Default</td><td class="tdtop">'.dol_escape_htmltag($object->label).'</td><td>'.dolPrintHTML($object->description).(!empty($object->note_public) ? '<hr>'.dolPrintHTML($object->note_public) : '').'</td><td></td></tr>';
foreach (array('fr_FR', 'en_US', 'de_DE', 'it_IT', 'es_ES') as $code) {
	$ml = isset($object->multilangs[$code]) ? $object->multilangs[$code] : array();
	$note = isset($ml['note']) ? $ml['note'] : (isset($ml['other']) ? $ml['other'] : '');
	print '<tr class="oddeven"><td class="tdtop">'.$code.'</td><td class="tdtop">'.dol_escape_htmltag(isset($ml['label']) ? $ml['label'] : '').'</td><td>'.dolPrintHTML(isset($ml['description']) ? $ml['description'] : '').(!empty($note) ? '<hr>'.dolPrintHTML($note) : '').'</td><td></td></tr>';
}
print '</table>';
print '</div>';

// Right column: files, checks and actions
print '<div style="flex:2 1 380px">';

// Actions: reason, quick buttons, publish / refuse / unpublish
if ($canValidate) {
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&mode='.$mode.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><th id="mkt-actions" class="mkt-anchor">'.$langs->trans("Actions").'</th></tr>';

	// Buttons that fill the reason (from the checks or from predefined texts)
	print '<tr class="oddeven"><td>';
	print '<div class="opacitymedium">'.$langs->trans("ReasonQuickFill").'</div>';
	print '<button type="button" class="button smallpaddingimp mkt-fill" data-mode="replace" data-text="'.dol_escape_htmltag(implode("\n", $reasonsErr)).'">'.$langs->trans("FillFromErrors").' ('.count($reasonsErr).')</button> ';
	print '<button type="button" class="button smallpaddingimp mkt-fill" data-mode="replace" data-text="'.dol_escape_htmltag(implode("\n", $reasonsAll)).'">'.$langs->trans("FillFromAllIssues").' ('.count($reasonsAll).')</button> ';
	print '<button type="button" class="button smallpaddingimp mkt-fill" data-mode="replace" data-text="">'.$langs->trans("Clear").'</button><br>';
	foreach (marketplaceGetQuickReasons() as $lab => $txt) {
		print '<button type="button" class="button smallpaddingimp mkt-reasonbtn mkt-fill" data-mode="append" data-text="'.dol_escape_htmltag('- '.$txt).'">+ '.$langs->trans($lab).'</button>';
	}
	print '</td></tr>';

	// Reason (stored in an extrafield limited to 255 characters) and email option
	print '<tr class="oddeven"><td>';
	print '<textarea id="mkt-reason" name="reason" class="centpercent" rows="6" maxlength="255" placeholder="'.dol_escape_htmltag($langs->trans("ReasonPlaceholder")).'">'.dol_escape_htmltag($reason ? $reason : marketplaceExtra($object, 'reason')).'</textarea>';
	print '<div class="opacitymedium right"><span id="mkt-count">0</span>/255</div>';
	print '<label><input type="checkbox" name="sendmail" value="1" checked> '.$langs->trans("SendEmailToSeller").'</label>';
	print '</td></tr>';

	// Submit buttons: publish is disabled while a check is in error
	print '<tr class="oddeven"><td class="center">';
	if (!$object->status) {
		print '<button type="submit" name="action" value="validate" class="butAction"'.($valid ? '' : ' disabled title="'.dol_escape_htmltag($langs->trans("ValidationBlockedByErrors")).'"').'>'.$langs->trans("PublishModule").'</button> ';
		print '<button type="submit" name="action" value="refuse" class="butActionDelete">'.$langs->trans("RefuseModule").'</button>';
	} else {
		print '<button type="submit" name="action" value="unpublish" class="butActionDelete">'.$langs->trans("UnpublishModule").'</button>';
	}
	print '</td></tr></table></form>';

	// Reason helpers: fill / append text and character counter
	print '<script>
(function(){
	var ta = document.getElementById("mkt-reason");
	var cnt = document.getElementById("mkt-count");
	function upd(){ cnt.textContent = ta.value.length; }
	ta.addEventListener("input", upd); upd();
	document.querySelectorAll(".mkt-fill").forEach(function(b){
		b.addEventListener("click", function(){
			var t = b.getAttribute("data-text");
			if (b.getAttribute("data-mode") === "append" && ta.value.trim() !== "") { ta.value = ta.value.replace(/\s+$/, "") + "\n" + t; }
			else { ta.value = t; }
			ta.focus(); upd();
		});
	});
})();
</script>';
} else {
	// The user can see the card but is not allowed to act
	print '<table class="noborder centpercent"><tr class="liste_titre"><th id="mkt-actions" class="mkt-anchor">'.$langs->trans("ModuleActions").'</th></tr>';
	print '<tr class="oddeven"><td class="opacitymedium">'.$langs->trans("NoRightToValidate").'</td></tr></table>';
}

// Validation checks
print '<table class="noborder centpercent"><tr class="liste_titre"><th id="mkt-checks" class="mkt-anchor">'.$langs->trans("ValidationChecks").'</th><th class="right">'.$langs->trans("Detail").'</th></tr>';
$icons = array('ok' => img_picto('', 'tick', 'class="pictofixedwidth"'), 'warning' => img_picto('', 'warning', 'class="pictofixedwidth"'), 'error' => img_picto('', 'error', 'class="pictofixedwidth"'));
foreach ($checks as $c) {
	print '<tr class="oddeven mkt-lvl-'.$c['level'].'"><td>'.$icons[$c['level']].dol_escape_htmltag($c['label']).'</td><td class="right opacitymedium">'.dol_escape_htmltag($c['detail']).'</td></tr>';
}
print '</table>';

// Files attached to the service
print '<table class="noborder centpercent"><tr class="liste_titre"><th colspan="3" id="mkt-files" class="mkt-anchor">'.$langs->trans("Files").'</th></tr>';
$urlDoc = DOL_URL_ROOT.'/document.php?modulepart=produit&entity='.((int) (empty($object->entity) ? 1 : $object->entity)).'&file=';
$urlImg = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.((int) (empty($object->entity) ? 1 : $object->entity)).'&file=';
if (empty($files['zips']) && empty($files['images']) && empty($files['others'])) {
	print '<tr><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
}
foreach ($files['zips'] as $f) {
	print '<tr class="oddeven"><td>ZIP</td><td><a href="'.$urlDoc.urlencode($f['rel']).'">'.img_mime($f['name']).' '.dol_escape_htmltag($f['name']).'</a></td><td class="right">'.dol_print_size($f['size'], 1, 1).'</td></tr>';
}
foreach ($files['images'] as $f) {
	$u = $urlImg.urlencode($f['rel']);
	print '<tr class="oddeven"><td>IMG</td><td>'.dol_escape_htmltag($f['name']).'</td><td class="right"><a href="'.$u.'" target="_blank"><img src="'.$u.'" style="max-width:120px;max-height:80px"></a></td></tr>';
}
foreach ($files['others'] as $f) {
	print '<tr class="oddeven"><td>-</td><td>'.dol_escape_htmltag($f['name']).'</td><td class="right">'.dol_print_size($f['size'], 1, 1).'</td></tr>';
}
print '</table>';

print '</div></div>';

llxFooter();
$db->close();
