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
 * \file    marketplace/admin/setup.php
 * \ingroup marketplace
 * \brief   Marketplace setup page.
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
if (!$res && file_exists("../../../../../main.inc.php")) {
	$res = @include "../../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * @var DoliDb $db
 * @var Hookmanager $hookmanager
 * @var User $user
 * @var Translate $langs
 */

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
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';


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

$formSetup->newItem('Miscellaneous')->setAsTitle();

// Setup conf for the name of the Market place
$formSetup->newItem('MARKETPLACE_NAME');

// Setup conf for the email of the Market place
$itememail = $formSetup->newItem('MARKETPLACE_EMAIL');
if (method_exists($itememail, 'setAsPrice')) {
	$itememail->setAsEmail();
}

$formSetup->newItem('Products')->setAsTitle();

// Setup conf for root category of proucts to sell
$formSetup->newItem('MARKETPLACE_ROOT_CATEGORY_ID')->setAsCategory('product');

// Setup conf for category Version
$formSetup->newItem('MARKETPLACE_VERSIONS_CATEGORY_ID')->setAsCategory('product');

// Setup conf for category Promotions
$formSetup->newItem('MARKETPLACE_SPECIAL_CATEGORY_ID')->setAsCategory('product');

// Setup conf for minimum price for products
$itemminprice = $formSetup->newItem('MARKETPLACE_MIN_PRODUCT_PRICE');
if (method_exists($itemminprice, 'setAsPrice')) {
	$itemminprice->setAsPrice();
}
$itemminprice->helpText = $langs->trans("IfNotFree");

// Setup conf for category New
$itemdelay = $formSetup->newItem('MARKETPLACE_DELAY_FOR_NEW');
$itemdelay->defaultFieldValue = '30';

$formSetup->newItem('ThirdParties')->setAsTitle();

// Setup conf for category Promotions
$formSetup->newItem('MARKETPLACE_PROSPECTCUSTOMER_ID')->setAsCategory('customer');

// Setup conf for category Promotions
$formSetup->newItem('MARKETPLACE_VENDOR_ID')->setAsCategory('supplier');

$formSetup->newItem('DiscountsPreferedCustomers')->setAsTitle();

// Setup conf for preferred customer
$formSetup->newItem('MARKETPLACE_PROSPECTCUSTOMER_PREFERRED_ID')->setAsCategory('customer');

// Setup conf for value of discount for preferred customer
$dicountValue = $formSetup->newItem('MARKETPLACE_PROSPECTCUSTOMER_PREFERRED_DISCOUNT');
$dicountValue->defaultFieldValue = '20';
$dicountValue->fieldAttr['placeholder'] = '%';
$dicountValue->cssClass = 'width40';

// Setup conf for products category to exclude from discounts
$formSetup->newItem('MARKETPLACE_DISCOUNT_EXCLUDE_PRODUCTS_CATEGORY_ID')->setAsCategory('product');


// Membership
if (isModEnabled("member")) {
	$formSetup->newItem('Members')->setAsTitle();

	// Setup conf for category Promotions
	$formSetup->newItem('MARKETPLACE_MEMBER_CATEGORY_ADMIN_ID')->setAsCategory('member');
}


// Websites
$formSetup->newItem('WebSite')->setAsTitle();

// Setup conf for URL of logo
$itemlogo = $formSetup->newItem('MARKETPLACE_URL_FOR_LOGO');
if (method_exists($itemminprice, 'setAsUrl')) {
	$itemlogo->setAsUrl();
}
$itemlogo->fieldAttr['placeholder'] = 'https://...';
$itemlogo->cssClass = 'minwidth500';

// User with necessary rights to manage website
//$userList = $formSetup->form->select_dolusers(getDolGlobalInt('MARKETPLACE_USER_MANAGE_WEBSITE'), 'MARKETPLACE_USER_MANAGE_WEBSITE', 1, null, 0, '', '', '0', 0, 0, '', 0, '', '', 1, 2);

$userModel = new User($db);
$userListData = $userModel->fetchAll('', '', 0, 0, "(statut:<>:0)");

$userList = ['-1' => ''];
foreach ($userModel->users as $u) {
	$userList[$u->id] = $u->login;
}

$item = $formSetup->newItem('MARKETPLACE_USER_MANAGE_WEBSITE');
$item->setAsSelect($userList);
$item->picto = 'user';
$item->helpText = $langs->transnoentities('MARKETPLACE_USER_MANAGE_WEBSITE_HELP');


// Name of template to use
$website = new Website($db);
$listofwebsites = $website->fetchAll('ASC', 'position'); // List of websites
$TFieldWebsites = array('-1' => '');
foreach ($listofwebsites as $key => $valwebsite) {
	$TFieldWebsites[$valwebsite->id] = $valwebsite->ref;
}
$itemforwebsiteid = $formSetup->newItem('MARKETPLACE_WEBSITE_ID')->setAsSelect($TFieldWebsites);
$itemforwebsiteid->helpText = $langs->trans("MARKETPLACE_WEBSITE_ID_HELP");
$itemforwebsiteid->fieldAttr['placeholder'] = '';


// Setup conf for email templates
$formSetup->newItem('EMailTemplates')->setAsTitle();

$formSetup->newItem('MARKETPLACE_WELCOME_EMAIL_TEMPLATE')->setAsEmailTemplate('thirdparty');

$formSetup->newItem('MARKETPLACE_FORGOT_PASSWORD_EMAIL_TEMPLATE')->setAsEmailTemplate('thirdparty');

$formSetup->newItem('MARKETPLACE_BUYER_ORDER_CONFIRMATION_TEMPLATE')->setAsEmailTemplate('order_send');

$formSetup->newItem('MARKETPLACE_SELLERS_ORDER_CONFIRMATION_TEMPLATE')->setAsEmailTemplate('order_send');

// Setup conf for additional product in cart
$formSetup->newItem('additionalProductInCard')->setAsTitle();

$additionalProductInCard = $formSetup->newItem('MARKETPLACE_ADDITIONAL_PRODUCT_IN_CART_ID')->setAsProduct();

$additionalProductPricePercent = $formSetup->newItem('MARKETPLACE_ADDITIONAL_PRODUCT_TOTAL_PERCENT');
$additionalProductPricePercent->fieldAttr['placeholder'] = '%';

$additionalProductCountries = $formSetup->newItem('MARKETPLACE_ADDITIONAL_PRODUCT_APPLICABLE_COUNTRIES');
$additionalProductCountries->fieldAttr['placeholder'] = 'fr,es,be,it,...';

// Setup configuration for "Product for Shipping Fees"
$formSetup->newItem('MARKETPLACE_SHIPPING_FEES_PRODUCT')->setAsTitle();
$formSetup->newItem('MARKETPLACE_SHIPPING_FEES_PRODUCT_ID')->setAsProduct();

// Setup conf others
$formSetup->newItem('otherSetups')->setAsTitle();
$itemMinimumAmountForPayment = $formSetup->newItem('MARKETPLACE_MINIMUM_PAYOUT_AMOUNT');
if (method_exists($itemminprice, 'setAsPrice')) {
	$itemMinimumAmountForPayment->setAsPrice();
}
$itemMinimumAmountForPayment->defaultFieldValue = '50';

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

$setupnotempty += count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object', 'aZ09');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/marketplace/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele."_".strtolower($tmpobjectkey);
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=marketplace-".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'MARKETPLACE_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object', 'aZ09');
		if (!empty($tmpobjectkey)) {
			$constforval = 'MARKETPLACE_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if (getDolGlobalString($constforval) == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'MARKETPLACE_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->{$constforval} = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object', 'aZ09');
	if (!empty($tmpobjectkey)) {
		$constforval = 'MARKETPLACE_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$help_url = '';
$page_name = "MarketplaceSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-marketplace page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = marketplaceAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "fa-store");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("MarketplaceSetupPage").'</span><br><br>';


if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true, true);
	print '<br>';
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
