<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file    marketplace/admin/importtip.php
 * \ingroup marketplace
 * \brief   Page with tips to import data from another ecommerce.
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
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/marketplace.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "marketplace@marketplace"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "MarketplaceSetup";

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-marketplace page-admin_about');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = marketplaceAdminPrepareHead();
print dol_get_fiche_head($head, 'importtip', $langs->trans($page_name), -1, 'fa-store');

print '<span class="opacitymedium">This is some tips on how to import existing data from a Prestashop 1.6 store...</span>';

print '<br>';
print '<br>';

$command = 'import-cats.php  db_host  db_user  db_password  db_port';
print 'To import (create or update) categories...<br>';
print '<div class="urllink">';
print '<input type="text" class="quatrevingtpercentminusx" value="'.$command.'">';
print '</div>';

print '<br>';

$command = 'import-third-parties.php  db_host  db_user  db_password  db_port  limit  clean_all_before_import(0|1)  id_website';
print 'To import (create or update) thirdparties (customers or sellers)...<br>';
print '<div class="urllink">';
print '<input type="text" class="quatrevingtpercentminusx" value="'.$command.'">';
print '</div>';

print '<br>';

$command = 'import-products.php  db_host  db_user  db_password  db_port  limit  clean_all_before_import(0|1)';
print 'To import (create or update) products...<br>';
print '<div class="urllink">';
print '<input type="text" class="quatrevingtpercentminusx" value="'.$command.'">';
print '</div>';


// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
