<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

use Luracast\Restler\RestException;

require_once __DIR__ .'/../core/modules/modDigiriskDolibarr.class.php';

/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DigiriskDolibarr extends DolibarrApi
{
	/**
	 * @var modDigiriskDolibarr $mod {@type modDigiriskDolibarr}
	 */
	public $mod;
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array();
	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->mod = new modDigiriskdolibarr($this->db);

	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order informations
	 *
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	public function enableModule()
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT .'/core/modules/modECM.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modProjet.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modSociete.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modTicket.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modCategorie.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modFckeditor.class.php';
		require_once DOL_DOCUMENT_ROOT .'/core/modules/modApi.class.php';

		$modEcm       = new modECM($this->db);
		$modProjet    = new modProjet($this->db);
		$modSociete   = new modSociete($this->db);
		$modTicket    = new modTicket($this->db);
		$modCategorie = new modCategorie($this->db);
		$modFckeditor = new modFckeditor($this->db);
		$modApi       = new modApi($this->db);

		$modEcm->init();
		$modProjet->init();
		$modSociete->init();
		$modTicket->init();
		$modCategorie->init();
		$modFckeditor->init();
		$modApi->init();
		$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

		return $this->mod->init();
	}

	/**
	 * Get properties of an order object by ref
	 *
	 * Return an array with order informations
	 *
	 * @param       int         $contact_list  0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return 	array|mixed data without useless information
	 *
	 * @url GET    disableModule
	 *
	 * @throws 	RestException
	 */
	public function disableModule()
	{
		return $this->mod->remove();
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order informations
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getFilesVersion
	 *
	 * @throws 	RestException
	 */
	public function getFilesVersion()
	{
		return $this->mod->version;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order informations
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getActiveVersion
	 *
	 * @throws 	RestException
	 */
	public function getActiveVersion()
	{
		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order informations
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getLatestVersion
	 *
	 * @throws 	RestException
	 */
	public function getLatestVersion()
	{
		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order informations
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    uploadNewModule
	 *
	 * @throws 	RestException
	 */
	public function uploadNewModule()
	{
		return exec('cd ../custom/digiriskdolibarr/shell/pull && bash update_version.sh');
	}
}
