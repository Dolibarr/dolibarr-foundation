<?php
/* Copyright (C) 2024 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file    marketplace/admin/setupapi.php
 * \ingroup marketplace
 * \brief   Marketplace setup page for CaptchaGoogle.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/marketplace.lib.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "marketplace@marketplace"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('marketplacesetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);


// Enter here all parameters in your setup page

/*
// Setup conf for selection of an URL
$item = $formSetup->newItem('MARKETPLACE_MYPARAM1');
$item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
$item->cssClass = 'minwidth500';

// Setup conf for selection of a simple string input
$item = $formSetup->newItem('MARKETPLACE_MYPARAM2');
$item->defaultFieldValue = 'default value';

// Setup conf for selection of a simple textarea input but we replace the text of field title
$item = $formSetup->newItem('MARKETPLACE_MYPARAM3');
$item->nameText = $item->getNameText().' more html text ';

// Setup conf for a selection of a thirdparty
$item = $formSetup->newItem('MARKETPLACE_MYPARAM4');
$item->setAsThirdpartyType();

// Setup conf for a selection of a boolean
$formSetup->newItem('MARKETPLACE_MYPARAM5')->setAsYesNo();

// Setup conf for a selection of an email template of type thirdparty
$formSetup->newItem('MARKETPLACE_MYPARAM6')->setAsEmailTemplate('thirdparty');

// Setup conf for a selection of a secured key
//$formSetup->newItem('MARKETPLACE_MYPARAM7')->setAsSecureKey();

// Setup conf for a selection of a product
$formSetup->newItem('MARKETPLACE_MYPARAM8')->setAsProduct();

// Add a title for a new section
$formSetup->newItem('NewSection')->setAsTitle();

$TField = array(
	'test01' => $langs->trans('test01'),
	'test02' => $langs->trans('test02'),
	'test03' => $langs->trans('test03'),
	'test04' => $langs->trans('test04'),
	'test05' => $langs->trans('test05'),
	'test06' => $langs->trans('test06'),
);

// Setup conf for a simple combo list
$formSetup->newItem('MARKETPLACE_MYPARAM9')->setAsSelect($TField);

// Setup conf for a multiselect combo list
$item = $formSetup->newItem('MARKETPLACE_MYPARAM10');
$item->setAsMultiSelect($TField);
$item->helpText = $langs->transnoentities('MARKETPLACE_MYPARAM10');
*/



/*
// Setup conf MARKETPLACE_MYPARAM10
$item = $formSetup->newItem('MARKETPLACE_MYPARAM10');
$item->setAsColor();
$item->defaultFieldValue = '#FF0000';
$item->nameText = $item->getNameText().' more html text ';
$item->fieldInputOverride = '';
$item->helpText = $langs->transnoentities('AnHelpMessage');
//$item->fieldValue = '';
//$item->fieldAttr = array() ; // fields attribute only for compatible fields like input text
//$item->fieldOverride = false; // set this var to override field output will override $fieldInputOverride and $fieldOutputOverride too
//$item->fieldInputOverride = false; // set this var to override field input
//$item->fieldOutputOverride = false; // set this var to override field output
*/
/*$item = $formSetup->newItem('MARKETPLACE_PUBLIC_API_KEY');
$item->helpText = $langs->trans("MARKETPLACE_PUBLIC_API_KEY_HELP");
$item->cssClass = 'minwidth500';


$setupnotempty += count($formSetup->items);
*/

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


$MARKETPLACE_PUBLIC_API_KEY = getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY');

if (GETPOSTISSET('MARKETPLACE_PUBLIC_API_KEY')) {
	$MARKETPLACE_PUBLIC_API_KEY = trim(GETPOST('MARKETPLACE_PUBLIC_API_KEY', 'alpha'));
}

if ($action == "update") {
	$i = 0;

	$db->begin();

	$i += dolibarr_set_const($db, 'MARKETPLACE_PUBLIC_API_KEY', $MARKETPLACE_PUBLIC_API_KEY, 'chaine', 0, '', $conf->entity);

	if ($i >= 1) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("SaveFailed"), null, 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "MarketplaceSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-marketplace page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = marketplaceAdminPrepareHead();
print dol_get_fiche_head($head, 'setupapi', $langs->trans($page_name), -1, "fa-store");

global $dolibarr_main_url_root;
$param = '';

print '<form name="marketplaceapisetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td></td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("MARKETPLACE_PUBLIC_API_KEY")."</td>";
print '<td><input required="required" type="text" class="flat minwidth100 maxwidth300 widthcentpercentminusx" id="MARKETPLACE_PUBLIC_API_KEY" name="MARKETPLACE_PUBLIC_API_KEY" value="'.dol_escape_htmltag($MARKETPLACE_PUBLIC_API_KEY).'">';
if (!empty($conf->use_javascript_ajax)) {
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
}
print '</td>';
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

// Page end
print dol_get_fiche_end();

print "</form>\n";


clearstatcache();


print "<br>";


// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
$getentity = ($conf->entity > 1 ? "&entity=".$conf->entity : "");

// Show message
$message = '';

$urlCategoryApi = '<a href="'.$urlwithroot.'/api/index.php/marketplace/categories?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'" target="_blank" rel="noopener noreferrer">';
$urlCategoryApi .= $urlwithroot.'/api/index.php/marketplace/categories?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : 'KEYNOTDEFINED').'</a>';
$message .= img_picto('', 'link').' '.str_replace('{url}', $urlCategoryApi, '<span class="opacitymedium">'.$langs->trans("CategoryApiInfo", 'vcal', '').'</span>');
$message .= '<div class="urllink">';
$message .= '<input type="text" id="onlinepaymenturl1" class="quatrevingtpercent" spellcheck="false" value="'.$urlwithroot.'/api/index.php/marketplace/categories?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'">';
if (getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) {
	$message .= ' <a href="'.$urlwithroot.'/api/index.php/marketplace/categories?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'" target="new">'.img_picto('', 'globe').'</a>';
}
$message .= '</div>';
$message .= ajax_autoselect('onlinepaymenturl1');
$message .= '<br>';


$urlProductApi = '<a href="'.$urlwithroot.'/api/index.php/marketplace/products?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'" target="_blank" rel="noopener noreferrer">';
$urlProductApi .= $urlwithroot.'/api/index.php/marketplace/products?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : 'KEYNOTDEFINED').'</a>';
$message .= img_picto('', 'link').' '.str_replace('{url}', $urlProductApi, '<span class="opacitymedium">'.$langs->trans("ProductApiInfo", 'vcal', '').'</span>');
$message .= '<div class="urllink">';
$message .= '<input type="text" id="onlinepaymenturl1" class="quatrevingtpercent" spellcheck="false" value="'.$urlwithroot.'/api/index.php/marketplace/products?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'">';
if (getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) {
	$message .= ' <a href="'.$urlwithroot.'/api/index.php/marketplace/products?apikey='.($conf->global->MARKETPLACE_PUBLIC_API_KEY ? urlencode(getDolGlobalString('MARKETPLACE_PUBLIC_API_KEY')) : '...').'" target="new">'.img_picto('', 'globe').'</a>';
}
$message .= '</div>';
$message .= ajax_autoselect('onlinepaymenturl1');
$message .= '<br>';


print $message;

$message = $langs->trans("productApiHelp");

print info_admin($message);

$constname = 'MARKETPLACE_PUBLIC_API_KEY';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword($constname);

llxFooter();
$db->close();
