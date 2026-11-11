<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       marketplace/public/productfeed.php
 *	\ingroup    marketplace
 *	\brief      Public XML feed of all modules of the marketplace, in Google Shopping (Merchant Center) RSS 2.0 format.
 *				Example: https://mydolibarr/custom/marketplace/public/productfeed.php?l=fr
 *				Parameters: l=Short code of the website language (en, fr, es, it, de), entity=Id of entity (1 by default)
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');		// This page is public, no login required
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');	// The feed is called by external services (Google Merchant Center, ...)
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');	// Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

// For MultiCompany module. Do not use GETPOST here, function is not defined yet.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : 1);
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

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
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';

$langs->loadLangs(array('errors'));

// Parameters
$lang = GETPOST('l', 'aZ09');

// Map of the website language short codes with the language codes used for product translations
$arrayoflangs = array('en' => 'en_US', 'fr' => 'fr_FR', 'es' => 'es_ES', 'it' => 'it_IT', 'de' => 'de_DE');

// Max number of modules in the feed (safety limit)
$maxnbofmodules = getDolGlobalInt('MARKETPLACE_FEED_MAX_ITEMS', 10000);

// Security check
if (!isModEnabled('marketplace') || !isModEnabled('website')) {
	httponly_accessforbidden($langs->trans('ErrorPublicInterfaceNotEnabled'), 403);
}

$rootcategoryid = getDolGlobalInt('MARKETPLACE_ROOT_CATEGORY_ID');
$websiteid = getDolGlobalInt('MARKETPLACE_WEBSITE_ID');

$website = new Website($db);
$result = ($websiteid > 0 ? $website->fetch($websiteid) : 0);
if ($rootcategoryid <= 0 || $result <= 0 || empty($website->virtualhost)) {
	dol_syslog("productfeed.php Setup of module marketplace is not complete (root category, website or its virtual host is missing)", LOG_WARNING);
	httponly_accessforbidden($langs->trans('ErrorModuleSetupNotComplete', 'Marketplace'), 503);
}

if (!array_key_exists($lang, $arrayoflangs)) {
	$lang = (array_key_exists($website->lang, $arrayoflangs) ? $website->lang : 'en');
}
$productlang = $arrayoflangs[$lang];
$baseurl = preg_replace('/\/+$/', '', $website->virtualhost);


/*
 * Actions
 */

// None


/*
 * View
 */

// Load modules visible on the marketplace (same filters than the product list of the website). Free modules are excluded as Google Shopping rejects a price of 0.
$modules = array();

$sql = "SELECT p.rowid, p.ref, p.price_ttc, pl.label, pl.description,";
$sql .= " (SELECT MIN(s.nom) FROM ".$db->prefix()."product_fournisseur_price as pfp";
$sql .= " INNER JOIN ".$db->prefix()."societe as s ON s.rowid = pfp.fk_soc";
$sql .= " WHERE pfp.fk_product = p.rowid) as brand";
$sql .= " FROM ".$db->prefix()."product as p";
$sql .= " INNER JOIN ".$db->prefix()."categorie_product as cp ON cp.fk_product = p.rowid";
$sql .= " INNER JOIN ".$db->prefix()."product_lang as pl ON pl.fk_product = p.rowid";
$sql .= " WHERE p.entity IN (".getEntity('product').")";
$sql .= " AND cp.fk_categorie = ".((int) $rootcategoryid);
$sql .= " AND pl.lang = '".$db->escape($productlang)."'";
$sql .= " AND p.tosell = 1";
$sql .= " AND p.price_ttc > 0";
$sql .= $db->order('p.rowid', 'ASC');
$sql .= $db->plimit($maxnbofmodules);

$resql = $db->query($sql);
if (!$resql) {
	dol_syslog("productfeed.php ".$db->lasterror(), LOG_ERR);
	httponly_accessforbidden($langs->trans('ErrorSQL'), 500);
}
while ($obj = $db->fetch_object($resql)) {
	$obj->images = array();
	$modules[(int) $obj->rowid] = $obj;
}
$db->free($resql);

// Load public images of all modules in one request. Order is the one of getImagePublicURLOfObject($object, $no, '', -1) used by the website.
if (count($modules)) {
	$sql = "SELECT ef.src_object_id, ef.share";
	$sql .= " FROM ".$db->prefix()."ecm_files as ef";
	$sql .= " INNER JOIN ".$db->prefix()."categorie_product as cp ON cp.fk_product = ef.src_object_id AND cp.fk_categorie = ".((int) $rootcategoryid);
	$sql .= " WHERE ef.entity IN (".getEntity('product').")";
	$sql .= " AND ef.src_object_type = 'product'";
	$sql .= " AND ef.share IS NOT NULL AND ef.share <> ''";
	$sql .= " AND ".$db->regexpsql('ef.filename', '\.(gif|jpg|jpeg|png|bmp|webp)$', 1);	// Only image formats supported by Google Shopping
	$sql .= $db->order('ef.src_object_id,ef.cover,ef.position,ef.rowid', 'ASC,DESC,ASC,ASC');

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			// Google Shopping accepts 1 main image and up to 10 additional images
			if (isset($modules[(int) $obj->src_object_id]) && count($modules[(int) $obj->src_object_id]->images) < 11) {
				$modules[(int) $obj->src_object_id]->images[] = $baseurl.'/wrapper.php?hashp='.urlencode($obj->share);
			}
		}
		$db->free($resql);
	} else {
		dol_syslog("productfeed.php ".$db->lasterror(), LOG_ERR);
	}
}

top_httphead('application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

print '<?xml version="1.0" encoding="UTF-8"?>'."\n";
print '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n";
print '<channel>'."\n";
print '<title>'.dol_escape_xml(getDolGlobalString('MARKETPLACE_NAME', $website->ref)).'</title>'."\n";
print '<link>'.dol_escape_xml($baseurl).'</link>'."\n";
print '<description>'.dol_escape_xml((string) $website->description).'</description>'."\n";

foreach ($modules as $module) {
	$label = ((string) $module->label !== '' ? (string) $module->label : (string) $module->ref);
	$description = dol_string_nohtmltag((string) $module->description);
	if ($description === '') {
		$description = $label;
	}
	$link = $baseurl.'/product.php?id='.((int) $module->rowid).'&title='.dolSlugify($label).'&l='.$lang;

	print '<item>'."\n";
	print '<g:id>'.((int) $module->rowid).'</g:id>'."\n";
	print '<g:title>'.dol_escape_xml(dol_trunc($label, 150, 'right', 'UTF-8', 1)).'</g:title>'."\n";
	print '<g:description>'.dol_escape_xml(dol_trunc($description, 5000, 'right', 'UTF-8', 1)).'</g:description>'."\n";
	print '<g:link>'.dol_escape_xml($link).'</g:link>'."\n";
	foreach ($module->images as $key => $imageurl) {
		$tagname = ($key == 0 ? 'g:image_link' : 'g:additional_image_link');
		print '<'.$tagname.'>'.dol_escape_xml($imageurl).'</'.$tagname.'>'."\n";
	}
	print '<g:availability>in_stock</g:availability>'."\n";
	// Machine format expected by Google (dot as decimal separator, 2 decimals), so price() can't be used
	print '<g:price>'.sprintf('%.2F', (float) $module->price_ttc).' '.dol_escape_xml($conf->currency).'</g:price>'."\n";
	print '<g:condition>new</g:condition>'."\n";
	if (!empty($module->brand)) {
		print '<g:brand>'.dol_escape_xml($module->brand).'</g:brand>'."\n";
	}
	print '<g:identifier_exists>no</g:identifier_exists>'."\n";
	print '</item>'."\n";
}

print '</channel>'."\n";
print '</rss>'."\n";

$db->close();
