<?php
/* Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    marketplace/admin/setupvalidation.php
 * \ingroup marketplace
 * \brief   Marketplace setup page for module validation.
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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/marketplace.lib.php';

// Translations
$langs->loadLangs(array("admin", "marketplace@marketplace"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */
if ($action == 'save' && GETPOST('token') == newToken()) {
	foreach (array('REFUSE', 'UNPUBLISH') as $t) {
		dolibarr_set_const($db, 'MARKETPLACE_VALIDATION_MAIL_'.$t.'_SUBJECT', GETPOST('subject_'.$t, 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MARKETPLACE_VALIDATION_MAIL_'.$t.'_BODY', GETPOST('body_'.$t, 'restricthtml'), 'chaine', 0, '', $conf->entity);
	}
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */
$help_url = '';
$page_name = "MarketplaceSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-marketplace page-adminvalidation');

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = marketplaceAdminPrepareHead();
print dol_get_fiche_head($head, 'setupvalidation', $langs->trans($page_name), -1, "fa-store");

print '<span class="opacitymedium">'.$langs->trans("MarketPlaceValidationMailHelp").'</span><br><br>';
print '<b>__SELLER_NAME__, __MODULE_REF__, __MODULE_LABEL__, __REASON__</b><br><br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

foreach (array('REFUSE' => 'MailTemplateRefuse', 'UNPUBLISH' => 'MailTemplateUnpublish') as $t => $title) {
	$tpl = marketplaceGetMailTemplate(strtolower($t));
	print load_fiche_titre($langs->trans($title), '', '');
	print '<table class="noborder centpercent">';
	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("MailSubject").'</td>';
	print '<td><input type="text" class="minwidth500" name="subject_'.$t.'" value="'.dol_escape_htmltag($tpl['subject']).'"></td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("MailBody").'</td>';
	print '<td><textarea class="quatrevingtpercent" rows="10" name="body_'.$t.'">'.dol_escape_htmltag($tpl['body']).'</textarea></td></tr>';
	print '</table><br>';
}

print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
