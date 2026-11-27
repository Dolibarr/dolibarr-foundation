<?php
/* Copyright (C) 2026        Philippe Grand             <philippe.grand@atoo-net.com>
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
 * \file    dolibarr-foundation/marketplace/class/marketplacecron.class.php
 * \ingroup marketplace
 * \brief   Scheduled jobs of the marketplace module.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 * Class with the methods called by the scheduled jobs (cron module)
 */
class MarketplaceCron
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Last error message
	 */
	public $error = '';

	/**
	 * @var string[] Error messages
	 */
	public $errors = array();

	/**
	 * @var string Report shown in the list of scheduled jobs
	 */
	public $output = '';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Remove the "preferred customer" tag (member discount) from the third parties whose linked members
	 * are no longer up to date with their subscription.
	 *
	 * A third party keeps its tag if at least one of its linked members is validated and either belongs to
	 * a member type without subscription, or has a subscription ending after today minus the grace delay.
	 * Tagged third parties not linked to any member are never processed: they are only counted in the report.
	 *
	 * Parameters of the scheduled job, for example "30, 1" to simulate, then "30, 0" to really remove the tags.
	 *
	 * @param  int $gracedelay Number of days after the end of subscription before removing the tag
	 * @param  int $simulate   1 = only report what would be done, 0 = really remove the tag
	 * @return int             0 if OK, <0 if KO (this method is called by the cron module)
	 */
	public function removeExpiredPreferredCustomers($gracedelay = 30, $simulate = 1)
	{
		$this->output = '';
		$this->error = '';
		$this->errors = array();
		$error = 0;

		$gracedelay = abs((int) $gracedelay);
		$simulate = (int) $simulate;

		$catid = getDolGlobalInt('MARKETPLACE_PROSPECTCUSTOMER_PREFERRED_ID');
		if (empty($catid)) {
			$this->error = 'The category of preferred customers (MARKETPLACE_PROSPECTCUSTOMER_PREFERRED_ID) is not set in the marketplace module setup';
			return -1;
		}

		$category = new Categorie($this->db);
		if ($category->fetch($catid) <= 0) {
			$this->error = 'Failed to load the category of preferred customers id='.$catid;
			return -1;
		}

		$datetotest = dol_time_plus_duree(dol_now(), -1 * $gracedelay, 'd');

		dol_syslog(__METHOD__.' catid='.$catid.' gracedelay='.$gracedelay.' simulate='.$simulate, LOG_DEBUG);

		// Tagged third parties linked to at least one member, none of them being validated and up to date
		$sql = "SELECT s.rowid, s.nom";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie_societe as cs";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = cs.fk_soc";
		$sql .= " WHERE cs.fk_categorie = ".((int) $catid);
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		$sql .= " AND EXISTS (SELECT a.rowid FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql .= " WHERE a.fk_soc = s.rowid AND a.entity IN (".getEntity('adherent')."))";
		$sql .= " AND NOT EXISTS (SELECT a2.rowid FROM ".MAIN_DB_PREFIX."adherent as a2";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent_type as t ON t.rowid = a2.fk_adherent_type";
		$sql .= " WHERE a2.fk_soc = s.rowid AND a2.entity IN (".getEntity('adherent').")";
		$sql .= " AND a2.statut = 1";
		$sql .= " AND (t.subscription = 0 OR a2.datefin >= '".$this->db->idate($datetotest)."'))";
		$sql .= " ORDER BY s.nom";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$processed = array();
		while ($obj = $this->db->fetch_object($resql)) {
			if ($simulate) {
				$processed[] = $obj->nom;
				continue;
			}

			$thirdparty = new Societe($this->db);
			if ($thirdparty->fetch($obj->rowid) <= 0) {
				$error++;
				$this->errors[] = 'Failed to load third party id='.$obj->rowid;
				continue;
			}

			$result = $category->del_type($thirdparty, 'customer');
			if ($result < 0) {
				$error++;
				$this->errors[] = $category->error;
			} else {
				$processed[] = $obj->nom;
			}
		}
		$this->db->free($resql);

		// Tagged third parties not linked to any member: report only, a manual check is needed
		$nbunlinked = 0;
		$sql = "SELECT COUNT(cs.fk_soc) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie_societe as cs";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = cs.fk_soc";
		$sql .= " WHERE cs.fk_categorie = ".((int) $catid);
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		$sql .= " AND NOT EXISTS (SELECT a.rowid FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql .= " WHERE a.fk_soc = s.rowid AND a.entity IN (".getEntity('adherent')."))";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nbunlinked = (int) $obj->nb;
			}
			$this->db->free($resql);
		}

		$this->output = ($simulate ? '[SIMULATION] The tag would be removed from ' : 'Tag removed from ').count($processed).' third party(ies)';
		if (count($processed)) {
			$this->output .= ': '.implode(', ', $processed);
		}
		$this->output .= '. Tagged third parties not linked to any member (not processed): '.$nbunlinked.'.';

		if ($error) {
			$this->error = implode(', ', $this->errors);
			return -1;
		}

		return 0;
	}
}
