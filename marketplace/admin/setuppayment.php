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
 * \file    marketplace/admin/setuppayment.php
 * \ingroup marketplace
 * \brief   Marketplace setup page for payment.
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "marketplace@marketplace"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('marketplacesetup', 'globalsetup', 'newpayment'));

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

/*

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

$formSetup->newItem('MARKETPLACE_BLOCK_SALES')->setAsYesNo();

//$item = $formSetup->newItem('MARKETPLACE_PAYMENT_IN_FRAME')->setAsYesNo();
//$item->nameText = $langs->trans("UseFrameDesc");
//$item->helpText = $langs->transnoentities('AnHelpMessage');

//$setupnotempty += count($formSetup->items);

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

$reg = array();
if (preg_match('/setMARKETPLACE_BLOCK_SALES/i', $action, $reg)) {
	if (dolibarr_set_const($db, 'MARKETPLACE_BLOCK_SALES', 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/delMARKETPLACE_BLOCK_SALES/i', $action, $reg)) {
	if (dolibarr_del_const($db, 'MARKETPLACE_BLOCK_SALES', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/setMARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT/i', $action, $reg)) {
	if (dolibarr_set_const($db, 'MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT', 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/delMARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT/i', $action, $reg)) {
	if (dolibarr_del_const($db, 'MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}


if (preg_match('/setMARKETPLACE_PAYMENT_IN_FRAME/i', $action, $reg)) {
	if (dolibarr_set_const($db, 'MARKETPLACE_PAYMENT_IN_FRAME', 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/delMARKETPLACE_PAYMENT_IN_FRAME/i', $action, $reg)) {
	if (dolibarr_del_const($db, 'MARKETPLACE_PAYMENT_IN_FRAME', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

$iframeSupportedPaymentMethods = array('stripe'); // List of payment methods that support being displayed inside an iframe
if (preg_match('/setMARKETPLACE_MAIN_PAYMENT_METHOD/i', $action, $reg)) {
	$mainpaymentmethod = GETPOST('MARKETPLACE_MAIN_PAYMENT_METHOD', 'alpha');
	$mainpaymentmethod = $mainpaymentmethod === '-1' ? '' : $mainpaymentmethod;
	if (dolibarr_set_const($db, 'MARKETPLACE_MAIN_PAYMENT_METHOD', $mainpaymentmethod, 'chaine', 0, '', $conf->entity) > 0) {
		if (getDolGlobalString('MARKETPLACE_PAYMENT_IN_FRAME') && !in_array($mainpaymentmethod, $iframeSupportedPaymentMethods)) {
			// If selected main payment method does not support being displayed inside an iframe, we disable the option to use payment in frame
			dolibarr_del_const($db, 'MARKETPLACE_PAYMENT_IN_FRAME', $conf->entity);
		}
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
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
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = marketplaceAdminPrepareHead();
print dol_get_fiche_head($head, 'setuppayment', $langs->trans($page_name), -1, "fa-store");

global $dolibarr_main_url_root;
$param = '';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current



print $langs->trans("MARKETPLACE_BLOCK_SALES")." ";
$enabledisablehtml = '';
if (!getDolGlobalString('MARKETPLACE_BLOCK_SALES')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMARKETPLACE_BLOCK_SALES&token='.newToken().$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=delMARKETPLACE_BLOCK_SALES&token='.newToken().$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on', 'class="warning"');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="MARKETPLACE_BLOCK_SALES" name="MARKETPLACE_BLOCK_SALES" value="'.(!getDolGlobalString('MARKETPLACE_BLOCK_SALES') ? 0 : 1).'">';

print '<hr>';

print $langs->trans("MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT")." ";
$enabledisablehtml = '';
if (!getDolGlobalString('MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT&token='.newToken().$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=delMARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT&token='.newToken().$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT" name="MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT" value="'.(!getDolGlobalString('MARKETPLACE_CLOSE_ORDER_AFTER_PAYMENT') ? 0 : 1).'">';

print '<hr>';

// Setup to select main payment method
$validpaymentmethod = getValidOnlinePaymentMethods('', 1);
$validpaymentmethodarray = array();
foreach ($validpaymentmethod as $key => $paymentmethod) {
	$validpaymentmethodarray[$key] = !empty($paymentmethod['label']) ? $paymentmethod['label'] : $key;
}
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=setMARKETPLACE_MAIN_PAYMENT_METHOD&token='.newToken().$param.'" spellcheck="false" >';
print $langs->trans("MARKETPLACE_MAIN_PAYMENT_METHOD")." ";
print $form->selectarray(
	'MARKETPLACE_MAIN_PAYMENT_METHOD',
	$validpaymentmethodarray,
	getDolGlobalString('MARKETPLACE_MAIN_PAYMENT_METHOD'),
	1,
	0,
	0,
	'onchange="this.form.submit()"',
	0,
	0,
	0,
	'',
	'minwidth300'
);
print '<hr>';
print '</form>';

if (!empty(getDolGlobalString('MARKETPLACE_MAIN_PAYMENT_METHOD'))) {
	// Check if selected main payment method support being displayed inside an iframe
	$canUseIframe = in_array(getDolGlobalString('MARKETPLACE_MAIN_PAYMENT_METHOD'), $iframeSupportedPaymentMethods);

	print $langs->trans("UseFrameDesc")." ";
	$enabledisablehtml = '';
	if (!getDolGlobalString('MARKETPLACE_PAYMENT_IN_FRAME')) {
		// Button off, click to enable
		$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMARKETPLACE_PAYMENT_IN_FRAME&token='.newToken().$param.'"'.($canUseIframe ? '' : ' onclick="return false;" style="opacity:0.5; cursor:not-allowed;"').'>';
		$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
		$enabledisablehtml .= '</a>';
	} else {
		// Button on, click to disable
		$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=delMARKETPLACE_PAYMENT_IN_FRAME&token='.newToken().$param.'" '.($canUseIframe ? '' : ' onclick="return false;" style="opacity:0.5; cursor:not-allowed;"').'>';
		$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on', '');
		$enabledisablehtml .= '</a>';
	}
	if (!empty(getDolGlobalString('MARKETPLACE_MAIN_PAYMENT_METHOD')) && !$canUseIframe) {
		$enabledisablehtml .= '<span class="error"> ('.$langs->trans("marketplacePaymentInFrameNotSupported", getDolGlobalString('MARKETPLACE_MAIN_PAYMENT_METHOD')).')</span>';
	}
	print $enabledisablehtml;
	print '<input type="hidden" id="MARKETPLACE_PAYMENT_IN_FRAME" name="MARKETPLACE_PAYMENT_IN_FRAME" value="'.(!getDolGlobalString('MARKETPLACE_PAYMENT_IN_FRAME') ? 0 : 1).'">';

	print '<br>';

	// Setup page goes here
	print '<span class="opacitymedium">'."<br>\n";
	print $langs->trans("MarketplaceSetupPaymentPage1")."<br>\n";
	print "* ".$langs->trans("MarketplaceSetupPaymentPage1Pro")."<br>\n";
	print "* ".$langs->trans("MarketplaceSetupPaymentPage1Cons")."<br>\n";
	print "<br>\n";
	print $langs->trans("MarketplaceSetupPaymentPage2")."<br>\n";
	print "* ".$langs->trans("MarketplaceSetupPaymentPage2Pro")."<br>\n";
	print "* ".$langs->trans("MarketplaceSetupPaymentPage2Cons")."<br>\n";
	print '</span>'."<br>\n";
	print '<br>';


	if (!getDolGlobalString('MARKETPLACE_PAYMENT_IN_FRAME')) {
		print "You are using the payment outside of a frame, no particular setup is required for this module.\n";
		print "<br>\n";
		print '<span class="info">In this mode, you can create a page called "htmlheaderpayment" with the type "banner" to define a header to add to the payment page.</span><br>'."\n";
	}

	if (getDolGlobalString('MARKETPLACE_PAYMENT_IN_FRAME')) {
		print "<small>You are using the payment inside a frame, you must modify the virtual host of you marketplace web server to
		include a proxy of the payment URLs to the URLs of your Dolibarr backend server.</small><br>\n";
		print '<textarea class="quatrevingtpercent" rows=20 spellcheck="false">';
		print "# If you need include the payment page into a frame of the marketplace website,\n";
		print "# you need to make a proxy redirection of URLs required for the payment to your backoffice public payment pages\n";
		print "#SSLProxyEngine On\n";
		print "#SSLProxyVerify none\n";
		print "#SSLProxyCheckPeerCN off\n";
		print "#SSLProxyCheckPeerName off\n";
		print "#ProxyPreserveHost Off\n";
		print '#ProxyPass "/public/payment/" "'.$urlwithroot.'/public/payment/'."\n";
		print '#ProxyPassReverse "/public/payment/" "'.$urlwithroot.'/public/payment/'."\n";
		print '#ProxyPass "/includes/" "'.$urlwithroot.'/includes/'."\n";
		print '#ProxyPassReverse "/includes/" "'.$urlwithroot.'/includes/'."\n";
		print '#ProxyPass "/public/includes/" "'.$urlwithroot.'/public/includes/'."\n";
		print '#ProxyPassReverse "/public/includes/" "'.$urlwithroot.'/public/includes/'."\n";
		print '#ProxyPass "/theme/" "'.$urlwithroot.'/theme/'."\n";
		print '#ProxyPassReverse "/theme/" "'.$urlwithroot.'/theme/'."\n";
		print '#ProxyPass "/core/js/" "'.$urlwithroot.'/core/js/'."\n";
		print '#ProxyPassReverse "/core/js/" "'.$urlwithroot.'/core/js/'."\n";
		print "</textarea><br>\n";
	}
}

print "<br>\n";

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
