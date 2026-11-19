<?php
/* Copyright (C) 2026       Laurent Destailleur      <eldy@users.sourceforge.net>
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
 * \file    marketplace/class/actions_marketplace.class.php
 * \ingroup marketplace
 * \brief   Hooks for module Marketplace
 */

/**
 * Class ActionsMarketplace
 */
class ActionsMarketplace
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error string
	 */
	public $error = '';

	/**
	 * @var string[] Array of errors
	 */
	public $errors = array();

	/**
	 * @var array<string,mixed> Hook results. Processed by HookManager::executeHooks()
	 */
	public $results = array();


	/**
	 * Constructor
	 *
	 * @param  DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Overloading the completeTabsHead function : add a tab on product cards
	 * pointing to the marketplace validation card, when the product is a
	 * marketplace module (a product recorded into a marketplace category).
	 *
	 * @param  array<string,mixed> $parameters Hook metadatas (context, head, mode, type, ...)
	 * @param  Product|null        $object     The object shown by the page
	 * @return int                                 0 = OK, <0 if KO (see HookManager doc)
	 */
	public function completeTabsHead($parameters, $object)
	{
		global $db, $langs, $user;

		// The tab is added when the head is built, not when tabs are removed, and only on product cards
		if (empty($parameters['mode']) || $parameters['mode'] != 'add' || (string) $parameters['type'] != 'product') {
			return 0;
		}
		if (!is_object($object) || empty($object->id) || $object->element != 'product') {
			return 0;
		}
		if (!$user->hasRight('marketplace', 'module', 'read')) {
			return 0;
		}

		// The hook is called several times when building the same head, so we check the tab was not already added
		if (!empty($parameters['head']) && is_array($parameters['head'])) {
			foreach ($parameters['head'] as $tab) {
				if (is_array($tab) && !empty($tab[2]) && $tab[2] == 'marketplacevalidation') {
					return 0;
				}
			}
		}

		// Show the tab only if the product is a marketplace module
		dol_include_once('/marketplace/lib/marketplace.lib.php');
		$catIds = marketplaceGetCategoryIds($db);
		$moduleIds = marketplaceFilterModuleIds($db, $catIds, array($object->id));
		if (empty($moduleIds)) {
			return 0;
		}

		$langs->loadLangs(array("marketplace@marketplace"));

		$this->results = array(array(
			dol_buildpath('/marketplace/module_validation_card.php', 1).'?id='.((int) $object->id),
			$langs->trans("MarketPlaceModuleValidation"),
			'marketplacevalidation'
		));

		return 0;
	}
}
