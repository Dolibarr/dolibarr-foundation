#!/usr/bin/env php
<?php
/* Copyright (C) 2007-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *      \file       htdocs/modulebuilder/template/scripts/mymodule.php
 *		\ingroup    mymodule
 *      \brief      This file is a command line script for module MyModule. You can execute it with:
 *      			php mymodule/scripts/mymodule.php
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}	// On CLI mode, no need to use web sessions


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__ . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

// Global variables
$version = '1.0';
$error = 0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

// Load Dolibarr environment
$res = 0;
// Try master.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/master.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/master.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/master.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/master.inc.php";
}
// Try master.inc.php using relative path
if (!$res && file_exists("../master.inc.php")) {
	$res = @include "../master.inc.php";
}
if (!$res && file_exists("../../master.inc.php")) {
	$res = @include "../../master.inc.php";
}
if (!$res && file_exists("../../../master.inc.php")) {
	$res = @include "../../../master.inc.php";
}
if (!$res && file_exists("../../../../master.inc.php")) {
	$res = @include "../../../../master.inc.php";
}
if (!$res) {
	print "Include of master fails. Try to call script with full path.";
	exit(-1);
}
// After this $db, $mysoc, $langs, $conf and $hookmanager are defined (Opened $db handler to database will be closed at end of file).
// $user is created but empty.

//$langs->setDefaultLang('en_US'); 	// To change default language of $langs
$langs->load("main"); // To load language file for default language

// Load user and its permissions
$result = $user->fetch('', 'admin'); // Load user for login 'admin'. Comment line to run as anonymous user.
if (!($result > 0)) {
	dol_print_error(null, $user->error);
	exit;
}
$user->getrights();

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';

$now = dol_now();


/*
 * Main
 */

print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " *****\n";
if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]) || !isset($argv[4]) || !isset($argv[5])) {	// Check parameters
	print "Usage: " . $script_file . " db_host db_name db_user db_password db_port\n";
	print "NB: Limit is set to 20 by default (0 = All) \n";
	exit(-1);
}
if (!empty($argv[7])) {
	print "Too many parameters\n";
	exit(-1);
}

$db_host = $argv[1];
$db_name = $argv[2];
$db_user = $argv[3];
$db_password = $argv[4];
$db_port = $argv[5];
$websiteId = isset($argv[6]) ? (int) $argv[6] : getDolGlobalInt("MARKETPLACE_WEBSITE_ID");



$sql  = "select lsa.fk_soc, ls.ref_ext as id_customer ";
$sql .= "from ".MAIN_DB_PREFIX."societe_account lsa ";
$sql .= "left join ".MAIN_DB_PREFIX."societe ls on ls.rowid = lsa.fk_soc ";
$sql .= "where lsa.fk_website = " . $websiteId;
$sql .= " and ls.ref_ext is not null";
$sql .= " and ls.ref_ext != ''";
$sql .= " group by lsa.fk_soc ";
//print $query = $sql . "\n";
//exit;

$importkey = dol_print_date(dol_now(), 'dayhourlog');

$conn = getDoliDBInstance('mysqli', $db_host, $db_user, $db_password, $db_name, $db_port);
if (! $conn->connected) {
	die("Connection failed: " . $conn->connect_error);
}
print "Connected to ".$db_host." ".$db_name." successfully...\n";

print "Update newsletter subscriptions...\n";


// Start of transaction
$db->begin();

$error = 0;
$error_messages = array();
$count_updated = 0;

if ($resultSql = $db->query($sql)) {
	while ($obj = $resultSql->fetch_object()) {

		$checkSql = "SELECT newsletter FROM ps_customer WHERE id_customer = " . (int)$obj->id_customer;
		if ($resultCheck = $conn->query($checkSql)) {
			if ($resultCheck->num_rows > 0) {
				$row = $resultCheck->fetch_object();
				$object = new Societe($db);
				if ($object->fetch($obj->fk_soc) > 0) {
					$noEmail = ($row->newsletter == 0) ? 1 : 0;
					if ($object->setNoEmail($noEmail) > 0) {
						$count_updated++;
						print "Customer ROWID: " . $obj->fk_soc . " - NoEmail set to " . $noEmail . "\n";
					} else {
						$error++;
						$error_messages[] = "Failed to set NoEmail for Customer ID: " . $obj->fk_soc . " - Error code : " . $object->setNoEmail($noEmail);
					}
				} else {
					$error++;
					$error_messages[] = "Failed to fetch Societe object for Customer ID: " . $obj->fk_soc;
				}
			} else {
				$error++;
				$error_messages[] = "Customer ID: " . $obj->id_customer . " not found in ps_customer table.";
			}
		} else {
			$error++;
			$error_messages[] = "Error executing query: " . $conn->error;
		}
	}
} else {
	$error++;
	$error_messages[] = "Error executing query: " . $db->error;
}


// -------------------- END OF YOUR CODE --------------------

if (!$error) {
	$db->commit();
	print $count_updated . ' Society updated' . "\n";
	print '--- end ok' . "\n";
} else {
	print '--- end error nb=' . $error . "\n";
	print implode("\n", $error_messages);
	$db->rollback();
}

$db->close(); // Close $db database opened handler

exit($error);
