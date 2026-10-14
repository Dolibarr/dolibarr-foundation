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

	$head[$h][0] = dol_buildpath("/marketplace/admin/importtip.php", 1);
	$head[$h][1] = $langs->trans("ImportTip");
	$head[$h][2] = 'importtip';
	$h++;

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

/** Noms des extrafields (colonne options_xxx). A ADAPTER si besoin. */
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
		'pricecomment' => 'marketplace_price_comment',
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

/** Retourne la valeur d'un extrafield par son alias */
function marketplaceExtra($product, $alias)
{
	$keys = marketplaceValidationKeys();
	$k = 'options_'.$keys[$alias];
	return isset($product->array_options[$k]) ? $product->array_options[$k] : '';
}

/** Vendeurs (fournisseurs) rattaches au service via les prix d'achat */
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

/** Repertoire des fichiers d'un service (meme logique que product/document.php) */
function marketplaceGetUploadDir($product)
{
	global $conf;
	$entity = empty($product->entity) ? 1 : $product->entity;
	if ($product->type == Product::TYPE_SERVICE) {
		$base = $conf->service->multidir_output[$entity];
	} else {
		$base = $conf->product->multidir_output[$entity];
	}
	if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
		return $base.'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id.'/photos';
	}
	return $base.'/'.get_exdir(0, 0, 0, 0, $product, 'product').dol_sanitizeFileName($product->ref);
}

/** Liste zip et images du service */
function marketplaceGetFiles($product)
{
	$dir = marketplaceGetUploadDir($product);
	$res = array('dir' => $dir, 'zips' => array(), 'images' => array());
	$files = dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 0);
	foreach ($files as $f) {
		if (preg_match('/\.zip$/i', $f['name'])) {
			$res['zips'][] = $f;
		} elseif (image_format_supported($f['name']) > 0) {
			$res['images'][] = $f;
		}
	}
	return $res;
}

/**
 * Controles de validation.
 * @return array list of array('label'=>, 'level'=>'ok|warning|error', 'detail'=>)
 */
function marketplaceValidationChecks($db, $product)
{
	global $langs;
	$checks = array();
	$add = function ($label, $level, $detail = '') use (&$checks, $langs) {
		$checks[] = array('label' => $langs->trans($label), 'level' => $level, 'detail' => $detail);
	};

	// Images (obligatoire) et zip (obligatoire)
	$files = marketplaceGetFiles($product);
	$add('CheckImage', count($files['images']) > 0 ? 'ok' : 'error', count($files['images']).' image(s)');
	if (count($files['zips']) == 1) {
		$add('CheckZip', 'ok', $files['zips'][0]['name']);
	} elseif (count($files['zips']) > 1) {
		$add('CheckZip', 'warning', count($files['zips']).' zip');
	} else {
		$add('CheckZip', 'error', '');
	}

	// Description par defaut
	$add('CheckDescription', dol_strlen(trim(strip_tags($product->description))) >= 30 ? 'ok' : 'error');

	// Traductions
	foreach (array('fr', 'en', 'de', 'it', 'es') as $l) {
		$code = $l.'_'.strtoupper($l);
		$ml = isset($product->multilangs[$code]) ? $product->multilangs[$code] : array();
		$has = !empty($ml['label']) && !empty($ml['description']);
		$add($langs->trans('CheckTranslation').' '.$code, $has ? 'ok' : 'warning');
	}

	// Champs obligatoires
	$required = array('version' => 'ModuleVersion', 'dolmin' => 'DolibarrMin', 'support' => 'HowtoContactSupport', 'keywords' => 'ModuleKeywords');
	foreach ($required as $alias => $lab) {
		$add($langs->trans('CheckRequired').' '.$langs->trans($lab), marketplaceExtra($product, $alias) !== '' ? 'ok' : 'error');
	}

	// Coherence des versions
	$dmin = marketplaceExtra($product, 'dolmin');
	$dmax = marketplaceExtra($product, 'dolmax');
	if ($dmin !== '' && $dmax !== '') {
		$add('CheckDolVersions', versioncompare(explode('.', $dmin), explode('.', $dmax)) <= 0 ? 'ok' : 'error', $dmin.' -> '.$dmax);
	}
	$pmin = marketplaceExtra($product, 'phpmin');
	$pmax = marketplaceExtra($product, 'phpmax');
	if ($pmin !== '' && $pmax !== '') {
		$add('CheckPhpVersions', versioncompare(explode('.', $pmin), explode('.', $pmax)) <= 0 ? 'ok' : 'error', $pmin.' -> '.$pmax);
	}

	// Prix et duree
	$add('CheckPrice', ($product->price > 0 || marketplaceExtra($product, 'compprice') !== '') ? 'ok' : 'warning', price($product->price));
	$add('CheckValidity', marketplaceExtra($product, 'duration') !== '' ? 'ok' : 'warning');

	// Vendeur
	$sellers = marketplaceGetSellers($db, $product->id);
	$add('CheckSeller', count($sellers) == 1 ? 'ok' : (count($sellers) > 1 ? 'warning' : 'error'), count($sellers).' vendeur(s)');
	if (count($sellers) >= 1 && empty($sellers[0]->email)) {
		$add('CheckSellerEmail', 'error');
	}

	// Doublons / forks / violation
	if (marketplaceExtra($product, 'duplicateof') !== '') {
		$add('CheckDuplicate', 'warning');
	}
	if (marketplaceExtra($product, 'forkof') !== '') {
		$add('CheckFork', 'warning');
	}
	if (marketplaceExtra($product, 'law') !== '') {
		$add('CheckLaw', 'error', marketplaceExtra($product, 'law'));
	}

	// Ref deja utilisee par un autre service au label proche
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."product WHERE rowid <> ".((int) $product->id);
	$sql .= " AND fk_product_type = 1 AND label = '".$db->escape($product->label)."'";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql)) && $obj->nb > 0) {
		$add('CheckSameLabel', 'warning', $obj->nb);
	}

	return $checks;
}

/** @return bool true si aucune erreur bloquante */
function marketplaceChecksAreValid($checks)
{
	foreach ($checks as $c) {
		if ($c['level'] == 'error') {
			return false;
		}
	}
	return true;
}

/** Modeles d'e-mail (type: refuse|unpublish), valeurs par defaut en anglais */
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

/** Envoie l'e-mail au vendeur. @return string '' si OK, sinon message d'erreur */
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

/** Trace l'action dans l'agenda du service (sert aux KPI) */
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
