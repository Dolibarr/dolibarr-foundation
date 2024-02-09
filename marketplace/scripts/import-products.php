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

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formbarcode.class.php';

// Load object modBarCodeProduct
$res = 0;
if (isModEnabled('barcode') && getDolGlobalString('BARCODE_PRODUCT_ADDON_NUM')) {
	$module = strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);
	$dirbarcode = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);
	foreach ($dirbarcode as $dirroot) {
		$res = dol_include_once($dirroot . $module . '.php');
		if ($res) {
			break;
		}
	}
	if ($res > 0) {
		$modBarCodeProduct = new $module();
	}
}



/*
 * Main
 */

print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " *****\n";
if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]) || !isset($argv[4]) || !isset($argv[5])) {	// Check parameters
	print "Usage: " . $script_file . " db_host db_name db_user db_password db_port limit [clean_all_before_import]\n";
	print "NB: Limit is set to 20 by default \n";
	print "NB: clean_all_before_import is set to true by default \n";
	exit(-1);
}


$db_host = $argv[1];
$db_name = $argv[2];
$db_user = $argv[3];
$db_password = $argv[4];
$db_port = $argv[5];
$limit = isset($argv[6])?$argv[6] : 20;
$clean_all_before_import = isset($argv[7])?$argv[7] : true;


// Select from prestashop
$current_lang = $langs->getDefaultLang();
switch ($langs->getDefaultLang()) {
	case 'en_US':
		$current_lang = 'en-us';
		break;

	case 'fr_FR':
		$current_lang = 'fr-fr';
		break;

	default:
		$current_lang = "";
		break;
};

$products_query = "
SELECT
	p.id_product,
	pl.name,
	pl.description_short,
	pl.description,
	p.active,
	p.id_category_default,
	p.on_sale,
	p.price,
	p.unity,
	p.reference,
	p.available_for_order,
	p.show_price,
	p.date_upd,
	p.module_version,
	p.dolibarr_min,
	p.dolibarr_max,
	p.dolibarr_support,
	p.date_add,
	l.language_code
FROM ps_product p
LEFT JOIN ps_product_lang pl on p.id_product = pl.id_product
LEFT JOIN ps_lang l on pl.id_lang  = l.id_lang
WHERE l.language_code = '" . $current_lang . "'
order by date_add DESC
limit ". $limit;

$delete_products_query = "
SELECT
	p.id_product
FROM ps_product p
";

$importkey = dol_print_date(dol_now(), 'dayhourlog');


// Start of transaction
$db->begin();


$conn = new mysqli($db_host, $db_user, $db_password, $db_name, $db_port);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
print "Connected to ".$db_host." ".$db_name." successfully...\n";


if ($clean_all_before_import == true) {
	if ($result_all_products = $conn->query($delete_products_query)) {
		while ($obji = $result_all_products->fetch_object()) {
			$list_of_imported_products = new Product($db);
			$is_imported_before = $list_of_imported_products->fetch('', '', $obji->id_product);
			if ($is_imported_before > 0) {
				$resulti_delete = $list_of_imported_products->delete($user);
				if ($resulti_delete < 0) {
					print " - Error in deleting product ref_ext = " . $obji->id_product . " - " . $list_of_imported_products->errorsToString();
				} else {
					print " - Product ref_ext = " . $obji->id_product . " deleted.";
				}
				print "\n";
			}

		}
	}
}

if ($result_products = $conn->query($products_query)) {

	while ($obj = $result_products->fetch_object()) {

		//add one product
		$product = new Product($db);

		$product->type = 0;
		$product->status = $obj->active;
		$product->status_buy = 0;

		if (getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE')) {
			$fk_barcode_type = getDolGlobalInt("PRODUIT_DEFAULT_BARCODE_TYPE");
		} else {
			$fk_barcode_type = 0;
		}

		$formbarcode = new FormBarCode($db);
		if (is_object($modBarCodeProduct)) {
			$tmpcode = $modBarCodeProduct->getNextValue($product, $fk_barcode_type);
		} else {
			$tmpcode = null;
		}
		$product->barcode = $tmpcode;
		$product->ref = $obj->reference;
		$product->label = $obj->name;
		$product->description = $obj->description;
		$product->url = $obj->id_product;
		$product->ref_ext = $obj->id_product;
		$product->price = price2num($obj->price);
		$product->price_ttc = price2num($obj->price);
		$product->price_base_type = 'TTC';
		$product->tva_tx = "20";
		$product->mandatory_period = !empty(GETPOST("mandatoryperiod", 'alpha')) ? 1 : 0;

		// Extrafields
		$product->array_options['options_moduleversion'] = $obj->module_version;
		$product->array_options['options_dolibarrmin'] = $obj->dolibarr_min;
		$product->array_options['options_dolibarrmax'] = $obj->dolibarr_max;
		$product->array_options['options_shortdescription'] = $obj->description_short;
		$product->array_options['options_howtocontactsupport'] = $obj->dolibarr_support;


		// Check if this product exists
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE url = '" . $obj->id_product . "' LIMIT 1";

		$resql = $db->query($sql);
		$objsql = $db->fetch_object($resql);


		if (!empty($objsql->rowid)) {
			$action = 'updated';
			$result = $product->update($objsql->rowid, $user);
		} else {
			$action = 'added';
			$result = $product->create($user);

			$sql = 'UPDATE '.MAIN_DB_PREFIX."product SET import_key = '".$db->escape($importkey)."' WHERE rowid = ".((int) $result);
			$db->query($sql);
		}


		if ($result < 0) {
			print " - Create Error => " . $result . " - " . $product->errorsToString();
			$error++;
		} else {
			print " - Product ref_ext = " . $product->ref_ext . " " . $action . " successfully.";
		}


		// Add langs, categories, versions and photos
		if (!$error && 1) {

			// Add alternative languages
			$products_lang_query = "
			SELECT
				p.id_product,
				l.language_code,
				pl.name,
				pl.description_short,
				pl.description,
				CASE l.language_code
					WHEN 'en-us' THEN 'en_US'
					WHEN 'fr-fr' THEN 'fr_FR'
					WHEN 'es-es' THEN 'es_ES'
					WHEN 'it-it' THEN 'it_IT'
					WHEN 'de-de' THEN 'de_DE'
					ELSE ''
				END dol_lang_code
			FROM ps_product p
			LEFT JOIN ps_product_lang pl on p.id_product = pl.id_product
			LEFT JOIN ps_lang l on pl.id_lang  = l.id_lang
			WHERE p.id_product = " . $obj->id_product . "
			";

			if ($result_products_lang = $conn->query($products_lang_query)) {

				while ($objlang = $result_products_lang->fetch_object()) {
					$product->multilangs[$objlang->dol_lang_code] = array(
						'label' => $objlang->name,
						'description' => $objlang->description,
						'other' => $objlang->description_short,
					);
				}
			}

			$ret = $product->setMultiLangs($user);
			if ($ret < 0) {
				print " - Error in setMultiLangs result code = " . $ret . " - " . $product->errorsToString();
				$error++;
			} else {
				print " - setMultiLangs OK";
			}


			// Add  categories and verions
			$categries_and_versions_list = array();
			$products_categories_query = "
			select
				pcp.id_category
			FROM
				ps_category_product pcp ,
				ps_category pc ,
				ps_category_lang pcl ,
				ps_lang pl
			where
				pcp.id_product = " . $obj->id_product . " AND
				pcp.id_category = pc.id_category AND
				pcl.id_category = pcp.id_category AND
				pcl.id_lang = pl.id_lang
			GROUP BY pcp.id_category
			";
			if ($result_product_categories = $conn->query($products_categories_query)) {
				while ($objcategories = $result_product_categories->fetch_object()) {
					$get_cat = new Categorie($db);
					$resget = $get_cat->fetch('', '', Categorie::TYPE_PRODUCT, $objcategories->id_category);
					if ($resget <= 0 || empty($get_version->id)) {
						//print ' - Product category "'.$objverions->name.'" not found in Dolibarr, we discard it.';
					} else {
						$categries_and_versions_list[$get_cat->id] = $get_cat->id;
					}
				}
			}

			$products_versions_query = "
			select
				t.name
			FROM
				ps_product_tag pt,
				ps_tag t
			where
				pt.id_tag = t.id_tag and
				pt.id_product = " . $obj->id_product . "
			GROUP BY t.name
			ORDER BY t.name  ASC
			";
			if ($result_product_verions = $conn->query($products_versions_query)) {
				while ($objverions = $result_product_verions->fetch_object()) {
					if ($objverions->name) {	// Some tags are empty
						$get_version = new Categorie($db);
						$resget = $get_version->fetch('', $objverions->name, Categorie::TYPE_PRODUCT);
						if ($resget <= 0 || empty($get_version->id)) {
							//print ' - Product category "'.$objverions->name.'" not found in Dolibarr, we discard it.';
						} else {
							$categries_and_versions_list[$get_version->id] = $get_version->id;
						}
					}
				}
			} else {
				print 'SQL error';
			}

			if (!empty($categries_and_versions_list)) {
				$ret_cat = $product->setCategories($categries_and_versions_list);
				if ($ret_cat < 0) {
					print " - Error in setCategories result code = " . $ret_cat . " - " . $product->errorsToString().' '.join(',', $categries_and_versions_list);
					$error++;
				} else {
					print " - setCategories and versions OK";
				}
			} else {
				$error++;
				print " - not category found on this product";
			}

			// Add pictures
			$products_images_query = "
			select
				*
			FROM
				ps_image pi
			where
				pi.id_product = " . $obj->id_product . "
			";

			$upload_dir = $conf->product->multidir_output[1] . '/' . get_exdir(0, 0, 0, 1, $product, 'product');

			if ($result_product_images = $conn->query($products_images_query)) {
				while ($objimage = $result_product_images->fetch_object()) {

					$url = "https://www.dolistore.com/" . $objimage->id_image . "-thickbox/" . $objimage->id_image . ".jpg";
					if (!is_dir($upload_dir)) {
						mkdir($upload_dir);
					}
					$img = $upload_dir . '/' . $product->ref . '-' . $objimage->id_image . '.jpg';;
					if (!file_put_contents($img, file_get_contents($url))) {
						$error++;
					} else {
						// Add thumb
						$product->addThumbs($img);

						// Add index to db
						$filename = basename($product->ref . '-' . $objimage->id_image . '.jpg');
						addFileIntoDatabaseIndex($upload_dir, $filename, 'URL_PS', 'imported', 1, $product);

						// Update index to put position
						$rel_dir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '/', '', $upload_dir);
						$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);
						$ref = dol_hash($rel_dir . '/' . $filename, 3);

						$ecmfile = new EcmFiles($db);
						$result_get_ecmfile = $ecmfile->fetch('', $ref);
						$ecmfile->position = $objimage->position;
						$result_update_ecmfile = $ecmfile->update($user);

						if ($result_update_ecmfile < 0) {
							print " - Error in setPhoto result code = " . $result_update_ecmfile . " - " . $ecmfile->errorsToString();
							$error++;
						} else {
							print " - set Images " . $objimage->id_image . " OK";
						}
					}
				}
			}
		}

		print "\n";
	}
}


// -------------------- END OF YOUR CODE --------------------

if (!$error) {
	$db->commit();
	print '--- end ok' . "\n";
} else {
	print '--- end error code=' . $error . "\n";
	$db->rollback();
}

$db->close(); // Close $db database opened handler

exit($error);
