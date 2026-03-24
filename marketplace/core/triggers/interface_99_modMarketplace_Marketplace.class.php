<?php
/* Copyright (C) 2018 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modMarketplace_Marketplace.class.php
 * \ingroup sellyoursaas
 * \brief   Trigger for sellyoursaas module.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Marketplace module
 */
class InterfaceMarketplace extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "marketplace";
		$this->description = "Marketplace triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 1.0;
		$this->picto = 'marketplace@marketplace';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('marketplace')) {
			return 0;     // Module not active, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		dol_syslog("Trigger InterfaceMarketplace");

		$error = 0;

		switch ($action) {
			case 'PRODUCT_MODIFY':
				/*var_dump($object->oldcopy->array_options['options_date_endfreeperiod']);
				 var_dump($object->array_options['options_date_endfreeperiod']);
				 var_dump($object->lines);*/

				if (isset($object->oldcopy)) {
					dol_syslog("We check that we don't try to enabled a product or service if ForkOf or LawViolation is on");

					dol_syslog("olddata: ".$object->oldcopy->array_options['options_marketplace_law_violation']." ".$object->oldcopy->array_options['options_marketplace_fork_of']." ".$object->oldcopy->array_options['options_marketplace_date_stop_earning']);
					dol_syslog("data: ".$object->array_options['options_marketplace_law_violation']." ".$object->array_options['options_marketplace_fork_of']." ".$object->array_options['options_marketplace_date_stop_earning']);

					if (1 == 2) {
						$this->error = "Can't set status to onsell if property forkof or datestopearning is set";
						$error++;
					}
				}
		}

		if ($error) {
			return -1;
		} else {
			return 0;
		}
	}
}
