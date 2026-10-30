<?php
/* Copyright (C) 2024 Alice Adminson
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
 * \file    marketplace/lib/marketplace.lib.php
 * \ingroup marketplace
 * \brief   Library files with common functions for Marketplace
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; // For versioncompare() used by marketplaceValidationChecks()

/**
 * Prepare admin pages header
 *
 * @return array
 */
function marketplaceAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("marketplace@marketplace");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/marketplace/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/marketplace/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = is_countable($extrafields->attributes['myobject']['label']) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/marketplace/admin/setupcaptchagoogle.php", 1);
	$head[$h][1] = $langs->trans("MarketPlaceSetupRegistration");
	$head[$h][2] = 'setupcaptchagoogle';
	$h++;

	$head[$h][0] = dol_buildpath("/marketplace/admin/setuppayment.php", 1);
	$head[$h][1] = $langs->trans("MarketPlaceSetupPayment");
	$head[$h][2] = 'setuppayment';
	$h++;

	$head[$h][0] = dol_buildpath("/marketplace/admin/setupapi.php", 1);
	$head[$h][1] = $langs->trans("MarketPlaceSetupApi");
	$head[$h][2] = 'setupapi';
	$h++;

	$head[$h][0] = dol_buildpath("/marketplace/admin/setupmatomo.php", 1);
	$head[$h][1] = $langs->trans("MarketPlaceSetupMatomo");
	$head[$h][2] = 'setupmatomo';
	$h++;

	$head[$h][0] = dol_buildpath("/marketplace/admin/setupdolibarr.php", 1);
	$head[$h][1] = $langs->trans("DolibarrFeatureSetup");
	$head[$h][2] = 'dolibarr';
	$h++;

	$head[$h][0] = dol_buildpath("/marketplace/admin/setupvalidation.php", 1);
	$head[$h][1] = $langs->trans("MarketPlaceSetupValidation");
	$head[$h][2] = 'setupvalidation';
	$h++;

	if (getDolGlobalString('MARKETPLACE_SHOW_MIGRATION_FROM_PRESTASHOP')) {
		$head[$h][0] = dol_buildpath("/marketplace/admin/importtip.php", 1);
		$head[$h][1] = $langs->trans("ImportTip");
		$head[$h][2] = 'importtip';
		$h++;
	}

	$head[$h][0] = dol_buildpath("/marketplace/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@marketplace:/marketplace/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@marketplace:/marketplace/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'marketplace@marketplace');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'marketplace@marketplace', 'remove');

	return $head;
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

/**
 * Map of aliases to extrafield names of the marketplace services.
 *
 * Values are the extrafield codes as stored in llx_product_extrafields (column name).
 * To read a value from a Product object, prefix the code with "options_" (see marketplaceExtra()).
 * Adapt this list if an extrafield is renamed: it is the only place to change.
 *
 * @return array<string,string> Alias => extrafield code
 */
function marketplaceValidationKeys()
{
	return array(
		'version' => 'marketplace_module_version',
		'dolmin' => 'marketplace_min_version',
		'dolmax' => 'marketplace_max_version',
		'phpmin' => 'marketplace_php_min_version',
		'phpmax' => 'marketplace_php_max_version',
		'keywords' => 'marketplace_module_keywords',
		'support' => 'marketplace_contact_support',
		'duration' => 'marketplace_validity_duration',
		'pricecomment' => 'marketplace_pricecomment',
		'compprice' => 'marketplace_complementary_price',
		'disclaimer' => 'marketplace_disclaimer_message',
		'submitted' => 'marketplace_submitted',
		'reason' => 'marketplace_reason_disabled',
		'duplicateof' => 'marketplace_duplicate_of',
		'forkof' => 'marketplace_fork_of',
		'law' => 'marketplace_law_violation',
		'stopearning' => 'marketplace_date_stop_earning',
		'coresource' => 'marketplace_allow_source_in_core',
	);
}

/**
 * Get the SQL column of an extrafield, for queries using the alias "ef"
 * (llx_product_extrafields). The column has no "options_" prefix in the database.
 *
 * @param  string $alias Alias defined in marketplaceValidationKeys()
 * @return string        SQL column, for example "ef.marketplace_submitted"
 */
function marketplaceCol($alias)
{
	$keys = marketplaceValidationKeys();
	return 'ef.'.$keys[$alias];
}

/**
 * Get the value of a marketplace extrafield from a loaded product.
 * The product must have been loaded with fetch() (which loads the extrafields).
 *
 * @param  Product $product Product (service) object
 * @param  string  $alias   Alias defined in marketplaceValidationKeys()
 * @return mixed            Extrafield value, or '' if not set
 */
function marketplaceExtra($product, $alias)
{
	$keys = marketplaceValidationKeys();
	$k = 'options_'.$keys[$alias];
	return isset($product->array_options[$k]) ? $product->array_options[$k] : '';
}

/**
 * Get the IDs of the product categories that contain marketplace modules.
 * Starts from the root category (MARKETPLACE_ROOT_CATEGORY_ID), adds all its
 * sub-categories recursively, and removes the "versions" category.
 *
 * @param  DoliDB $db Database handler
 * @return int[]      Category IDs (empty array if the root category is not configured)
 */
function marketplaceGetCategoryIds($db)
{
	$root = getDolGlobalInt('MARKETPLACE_ROOT_CATEGORY_ID');
	if (!$root) {
		return array();
	}
	$ids = array($root);
	$toScan = array($root);
	while (!empty($toScan)) {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie WHERE type = 0 AND fk_parent IN (".implode(',', array_map('intval', $toScan)).")";
		$toScan = array();
		$resql = $db->query($sql);
		while ($resql && ($obj = $db->fetch_object($resql))) {
			if (!in_array((int) $obj->rowid, $ids)) {
				$ids[] = (int) $obj->rowid;
				$toScan[] = (int) $obj->rowid;
			}
		}
	}
	// Services of the "versions" category are not modules to validate
	$versions = getDolGlobalInt('MARKETPLACE_VERSIONS_CATEGORY_ID');
	return array_values(array_diff($ids, array($versions)));
}

/**
 * Filter a list of product IDs to keep only the marketplace modules
 * (services that belong to a marketplace category).
 *
 * @param  DoliDB $db     Database handler
 * @param  int[]  $catIds Category IDs from marketplaceGetCategoryIds()
 * @param  int[]  $ids    Product IDs to filter
 * @return int[]          Filtered product IDs
 */
function marketplaceFilterModuleIds($db, $catIds, $ids)
{
	if (empty($ids) || empty($catIds)) {
		return array();
	}
	$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."product as p";
	$sql .= " WHERE p.fk_product_type = 1 AND p.rowid IN (".implode(',', array_map('intval', $ids)).")";
	$sql .= " AND p.rowid IN (SELECT cp.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_categorie IN (".implode(',', array_map('intval', $catIds))."))";
	$filtered = array();
	$resql = $db->query($sql);
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$filtered[] = (int) $obj->rowid;
	}
	return $filtered;
}

/**
 * Get the sellers linked to a service.
 * The link is made through the supplier prices (llx_product_fournisseur_price).
 *
 * @param  DoliDB    $db        Database handler
 * @param  int       $productid ID of the service
 * @return Societe[]            Loaded third parties (empty array if none)
 */
function marketplaceGetSellers($db, $productid)
{
	$sellers = array();
	$sql = "SELECT DISTINCT s.rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
	$sql .= " WHERE pfp.fk_product = ".((int) $productid);
	$resql = $db->query($sql);
	if ($resql) {
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		while ($obj = $db->fetch_object($resql)) {
			$soc = new Societe($db);
			if ($soc->fetch($obj->rowid) > 0) {
				$sellers[] = $soc;
			}
		}
	}
	return $sellers;
}

/**
 * Find and classify the files attached to a service (module zip, images, others).
 *
 * The storage folder depends on the Dolibarr configuration (service or product
 * output dir, with or without sub-directories, old photo path). Several candidate
 * folders are tested and the first one that contains files is used.
 *
 * @param  Product $product Product (service) object
 * @return array            Array with keys:
 *                          - 'dir'    (string)  folder where files were found ('' if none)
 *                          - 'tried'  (array)   all the folders tested (useful for debug)
 *                          - 'zips'   (array)   zip files
 *                          - 'images' (array)   image files supported by Dolibarr
 *                          - 'others' (array)   any other file
 *                          Each file is an array from dol_dir_list() plus the key 'rel',
 *                          the path relative to the base folder (for document.php / viewimage.php).
 */
function marketplaceGetFiles($product)
{
	global $conf;

	$entity = empty($product->entity) ? 1 : (int) $product->entity;
	$ref = dol_sanitizeFileName($product->ref);

	// Base folders: service module, product module, then default data folder
	$bases = array();
	foreach (array('service', 'product') as $m) {
		if (isset($conf->$m->multidir_output[$entity])) {
			$bases[] = $conf->$m->multidir_output[$entity];
		} elseif (isset($conf->$m->dir_output)) {
			$bases[] = $conf->$m->dir_output;
		}
	}
	$bases[] = DOL_DATA_ROOT.'/produit';
	$bases = array_values(array_unique($bases));

	// Candidate sub-folders for each base folder
	$candidates = array();
	foreach ($bases as $b) {
		$candidates[] = array($b, $ref);
		$candidates[] = array($b, get_exdir(0, 0, 0, 0, $product, 'product').$ref);
		$candidates[] = array($b, get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id.'/photos');
	}

	$res = array('dir' => '', 'tried' => array(), 'zips' => array(), 'images' => array(), 'others' => array());
	foreach ($candidates as $cand) {
		$dir = rtrim($cand[0].'/'.ltrim($cand[1], '/'), '/');
		if (in_array($dir, $res['tried'])) {
			continue;
		}
		$res['tried'][] = $dir;

		// Ignore .meta files and generated previews
		$files = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 0);
		if (empty($files)) {
			continue;
		}

		$res['dir'] = $dir;
		foreach ($files as $f) {
			$f['rel'] = ltrim(ltrim(substr($dir, strlen($cand[0])), '/').'/'.$f['name'], '/');
			if (preg_match('/\.zip$/i', $f['name'])) {
				$res['zips'][] = $f;
			} elseif (image_format_supported($f['name']) > 0) {
				$res['images'][] = $f;
			} else {
				$res['others'][] = $f;
			}
		}
		break;
	}
	return $res;
}

/**
 * Run all the validation checks of a module (service).
 *
 * Levels:
 * - 'error'   : blocking, the module cannot be published
 * - 'warning' : to review, does not block the publication
 * - 'ok'      : check passed
 *
 * @param  DoliDB  $db      Database handler
 * @param  Product $product Product (service) loaded with fetch() and fetch_optionals()
 *                          (and getMultiLangs() for the translations check)
 * @return array            List of checks, each one is an array with keys:
 *                          'label' (translated), 'level' (ok|warning|error),
 *                          'detail' (short info shown on the right),
 *                          'reason' (English text proposed to the seller when the check fails)
 */
function marketplaceValidationChecks($db, $product)
{
	global $langs;
	$checks = array();
	$add = function ($label, $level, $detail = '', $reason = '') use (&$checks) {
		$checks[] = array('label' => $label, 'level' => $level, 'detail' => $detail, 'reason' => $reason);
	};

	$files = marketplaceGetFiles($product);
	$nbi = count($files['images']);
	$add($langs->trans('CheckImage'), $nbi > 0 ? 'ok' : 'error', $nbi.' image(s)', 'No image provided. Please add at least one image (logo or screenshot).');
	$nbz = count($files['zips']);
	if ($nbz == 1) {
		$add($langs->trans('CheckZip'), 'ok', $files['zips'][0]['name']);
	} elseif ($nbz > 1) {
		$add($langs->trans('CheckZip'), 'warning', $nbz.' zip', 'Several zip files were uploaded. Please keep only one zip file.');
	} else {
		$add($langs->trans('CheckZip'), 'error', '', 'The module zip file is missing.');
	}

	$descOk = dol_strlen(trim(strip_tags($product->description))) >= 30;
	$add($langs->trans('CheckDescription'), $descOk ? 'ok' : 'error', '', 'The description is missing or too short.');

	// Translations: label and description are expected in each language
	foreach (array('fr_FR', 'en_US', 'de_DE', 'it_IT', 'es_ES') as $code) {
		$ml = isset($product->multilangs[$code]) ? $product->multilangs[$code] : array();
		$has = !empty($ml['label']) && !empty($ml['description']);
		$add($langs->trans('CheckTranslation').' '.$code, $has ? 'ok' : 'warning', '', 'Missing translation: '.$code.'.');
	}

	// Required extrafields
	$required = array('version' => 'ModuleVersion', 'dolmin' => 'DolibarrMin', 'support' => 'HowtoContactSupport', 'keywords' => 'ModuleKeywords');
	foreach ($required as $alias => $lab) {
		$add($langs->trans('CheckRequired').' '.$langs->trans($lab), marketplaceExtra($product, $alias) !== '' ? 'ok' : 'error', '', 'Required field missing: '.$langs->transnoentitiesnoconv($lab).'.');
	}

	// Dolibarr versions: min must not be greater than max (letters like "V" are ignored)
	$dmin = marketplaceExtra($product, 'dolmin');
	$dmax = marketplaceExtra($product, 'dolmax');
	if ($dmin !== '' && $dmax !== '') {
		$ok = versioncompare(explode('.', preg_replace('/[^0-9.]/', '', $dmin)), explode('.', preg_replace('/[^0-9.]/', '', $dmax))) <= 0;
		$add($langs->trans('CheckDolVersions'), $ok ? 'ok' : 'error', $dmin.' -> '.$dmax, 'The Dolibarr min/max versions are inconsistent.');
	}

	// PHP versions: min must not be greater than max
	$pmin = marketplaceExtra($product, 'phpmin');
	$pmax = marketplaceExtra($product, 'phpmax');
	if ($pmin !== '' && $pmax !== '') {
		$ok = versioncompare(explode('.', $pmin), explode('.', $pmax)) <= 0;
		$add($langs->trans('CheckPhpVersions'), $ok ? 'ok' : 'error', $pmin.' -> '.$pmax, 'The PHP min/max versions are inconsistent.');
	}

	// Price and validity duration
	$add($langs->trans('CheckPrice'), ($product->price > 0 || marketplaceExtra($product, 'compprice') !== '') ? 'ok' : 'warning', price($product->price), 'The price is missing.');
	$add($langs->trans('CheckValidity'), marketplaceExtra($product, 'duration') !== '' ? 'ok' : 'warning', '', 'The validity duration is missing.');

	$sellers = marketplaceGetSellers($db, $product->id);
	$add($langs->trans('CheckSeller'), count($sellers) == 1 ? 'ok' : (count($sellers) > 1 ? 'warning' : 'error'), count($sellers).' vendeur(s)');
	if (count($sellers) >= 1 && empty($sellers[0]->email)) {
		$add($langs->trans('CheckSellerEmail'), 'error');
	}

	// Seller: exactly one is expected, with an email to contact him
	if (marketplaceExtra($product, 'duplicateof') !== '') {
		$add($langs->trans('CheckDuplicate'), 'warning', '', 'This module is declared as a copy of another module.');
	}
	if (marketplaceExtra($product, 'forkof') !== '') {
		$add($langs->trans('CheckFork'), 'warning', '', 'This module is declared as a fork of another module.');
	}
	if (marketplaceExtra($product, 'law') !== '') {
		$add($langs->trans('CheckLaw'), 'error', marketplaceExtra($product, 'law'), 'The module violates the marketplace rules or the law.');
	}

	// Another service with exactly the same label (possible duplicate)
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."product WHERE rowid <> ".((int) $product->id);
	$sql .= " AND fk_product_type = 1 AND label = '".$db->escape($product->label)."'";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql)) && $obj->nb > 0) {
		$add($langs->trans('CheckSameLabel'), 'warning', $obj->nb, 'Another module already uses the same name.');
	}

	return $checks;
}

/**
 * Tell if a list of checks has no blocking error.
 *
 * @param  array $checks List returned by marketplaceValidationChecks()
 * @return bool          True if no check is at the 'error' level (module can be published)
 */
function marketplaceChecksAreValid($checks)
{
	foreach ($checks as $c) {
		if ($c['level'] == 'error') {
			return false;
		}
	}
	return true;
}

/**
 * Tell if a list of checks has no blocking error.
 *
 * @return bool          True if no check is at the 'error' level (module can be published)
 */
function marketplaceGetQuickReasons()
{
	return array(
		'QuickReason_Image' => 'No image provided. Please add at least one image.',
		'QuickReason_Zip' => 'The module zip file is missing or invalid.',
		'QuickReason_Desc' => 'The description is missing or too short.',
		'QuickReason_Translations' => 'Translations are incomplete (FR, EN, DE, IT, ES).',
		'QuickReason_Versions' => 'The Dolibarr/PHP version information is missing or inconsistent.',
		'QuickReason_Duplicate' => 'This module is a copy of an existing module.',
		'QuickReason_Quality' => 'The module does not meet our quality requirements.',
		'QuickReason_Legal' => 'The module violates the marketplace rules or the law.',
	);
}

/**
 * Get the IDs of the modules of a given list, from the oldest to the newest.
 * Used for the previous/next navigation of the validation card.
 *
 * Lists:
 * - pending : not on sale and no reason set (waiting for validation)
 * - refused : not on sale and a reason is set (refused or unpublished)
 * - online  : on sale
 *
 * @param  DoliDB $db   Database handler
 * @param  string $mode 'pending', 'refused' or 'online' (any other value means 'pending')
 * @return int[]        Service IDs sorted by submission date (creation date if not set)
 */
function marketplaceGetListIds($db, $mode)
{
	$efSub = marketplaceCol('submitted');
	$efReason = marketplaceCol('reason');
	$catIds = marketplaceGetCategoryIds($db);
	if (empty($catIds)) {
		return array();
	}
	$conds = array(
		'pending' => "p.tosell = 0 AND (".$efReason." IS NULL OR ".$efReason." = '')",
		'refused' => "p.tosell = 0 AND ".$efReason." IS NOT NULL AND ".$efReason." <> ''",
		'online' => "p.tosell = 1",
	);
	$cond = isset($conds[$mode]) ? $conds[$mode] : $conds['pending'];
	$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."product as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef ON ef.fk_object = p.rowid";
	$sql .= " WHERE p.fk_product_type = 1 AND ".$cond;
	$sql .= " AND p.rowid IN (SELECT cp.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_categorie IN (".implode(',', $catIds)."))";
	$sql .= " ORDER BY COALESCE(".$efSub.", p.datec) ASC, p.rowid ASC";
	$ids = array();
	$resql = $db->query($sql);
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$ids[] = (int) $obj->rowid;
	}
	return $ids;
}

/**
 * Get the email template (subject and body) sent to the seller.
 * Values come from the module setup (tab "Validation"), with English defaults.
 *
 * Substitution keys available in subject and body:
 * __SELLER_NAME__, __MODULE_REF__, __MODULE_LABEL__, __REASON__
 *
 * @param  string $type 'refuse' (non-publication) or 'unpublish'
 * @return array        Array with keys 'subject' and 'body'
 */
function marketplaceGetMailTemplate($type)
{
	$defaults = array(
		'refuse' => array(
			'subject' => 'Your module __MODULE_REF__ was not published',
			'body' => "Hello __SELLER_NAME__,\n\nThank you for submitting your module \"__MODULE_LABEL__\" (__MODULE_REF__).\nAfter review, we are unable to publish it for the following reason:\n\n__REASON__\n\nYou can fix this and submit it again.\n\nBest regards,\nThe marketplace team",
		),
		'unpublish' => array(
			'subject' => 'Your module __MODULE_REF__ was unpublished',
			'body' => "Hello __SELLER_NAME__,\n\nYour module \"__MODULE_LABEL__\" (__MODULE_REF__) has been removed from the marketplace for the following reason:\n\n__REASON__\n\nPlease contact us if you want to discuss it.\n\nBest regards,\nThe marketplace team",
		),
	);
	$prefix = 'MARKETPLACE_VALIDATION_MAIL_'.strtoupper($type);
	return array(
		'subject' => getDolGlobalString($prefix.'_SUBJECT', $defaults[$type]['subject']),
		'body' => getDolGlobalString($prefix.'_BODY', $defaults[$type]['body']),
	);
}

/**
 * Send the non-publication or unpublication email to the seller of a module.
 * The recipient is the first seller linked to the service. The email is sent in plain text.
 *
 * @param  DoliDB  $db      Database handler
 * @param  Product $product Product (service) object
 * @param  string  $type    'refuse' or 'unpublish' (selects the template)
 * @param  string  $reason  Reason inserted in the email (replaces __REASON__)
 * @return string           Empty string if the email was sent, error message otherwise
 */
function marketplaceSendValidationMail($db, $product, $type, $reason)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$sellers = marketplaceGetSellers($db, $product->id);
	if (empty($sellers) || empty($sellers[0]->email)) {
		return 'No seller email found';
	}
	$seller = $sellers[0];
	$tpl = marketplaceGetMailTemplate($type);
	$subst = array(
		'__SELLER_NAME__' => $seller->name,
		'__MODULE_REF__' => $product->ref,
		'__MODULE_LABEL__' => $product->label,
		'__REASON__' => $reason,
	);
	$subject = strtr($tpl['subject'], $subst);
	$body = strtr($tpl['body'], $subst);
	$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');

	$mail = new CMailFile($subject, $seller->email, $from, $body, array(), array(), array(), '', '', 0, 0);
	if (!$mail->sendfile()) {
		return $mail->error;
	}
	return '';
}

/**
 * Record a validation action in the agenda of the service.
 * These events are used by the dashboard KPI (actions per period, average processing time).
 *
 * @param  DoliDB  $db      Database handler
 * @param  User    $user    User who performs the action
 * @param  Product $product Product (service) concerned
 * @param  string  $code    Event code: MKT_VALIDATE, MKT_REFUSE or MKT_UNPUBLISH
 * @param  string  $label   Event label
 * @return int              ID of the created event if > 0, <= 0 on error
 */
function marketplaceLogValidationAction($db, $user, $product, $code, $label)
{
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	$ev = new ActionComm($db);
	$ev->type_code = 'AC_OTH_AUTO';
	$ev->code = $code; // MKT_VALIDATE | MKT_REFUSE | MKT_UNPUBLISH
	$ev->label = $label;
	$ev->datep = dol_now();
	$ev->datef = dol_now();
	$ev->percentage = -1;
	$ev->userownerid = $user->id;
	$ev->elementtype = 'product';
	$ev->fk_element = $product->id;
	return $ev->create($user);
}
