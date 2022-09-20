<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file    class/api_digiriskdolibarr.class.php
 * \ingroup digiriskdolibarr
 * \brief   File for API management of DigiriskDolibarr.
 */

use Luracast\Restler\RestException;

require_once __DIR__ . '/../core/modules/modDigiriskDolibarr.class.php';

/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DigiriskDolibarr extends DolibarrApi
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var modDigiriskDolibarr $mod {@type modDigiriskDolibarr}
	 */
	public $mod;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db  = $db;
		$this->mod = new modDigiriskdolibarr($this->db);
	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order informations
	 *
	 * @return 	array|mixed data without useless information
	 *
	 */
	public function enableModule()
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/modules/modECM.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modProjet.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modSociete.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modTicket.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modCategorie.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modFckeditor.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/modules/modApi.class.php';

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
	 * @return    array|mixed data without useless information
	 *
	 * @url GET    disableModule
	 *
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
	 */
	public function uploadNewModule()
	{
		return exec('cd ../custom/digiriskdolibarr/shell/pull && bash update_version.sh');
	}

	/**
	 * Get dashboard info risks
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info risks
	 *
	 * @url    GET                   getDashBoardRisks
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardRisks($entity = 1)
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->risk->read) {
			throw new RestException(401);
		}

		global $conf, $db;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($db, $entity);

		require_once __DIR__ . '/riskanalysis/risk.class.php';

		$risk = new Risk($db);
		return $risk->load_dashboard();
	}

	/**
	 * Get dashboard info tasks
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info tasks
	 *
	 * @url    GET                   getDashBoardInfoTasks
	 *
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoTasks($entity = 1)
	{
		if (!DolibarrApiAccess::$user->rights->projet->lire) {
			throw new RestException(401);
		}

		global $conf;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($this->db, $entity);

		require_once __DIR__ . '/digirisktask.class.php';

		$digirisktask = new DigiriskTask($this->db);
		return $digirisktask->load_dashboard();
	}

	/**
	 * Get dashboard info riskassessmentdocument
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info riskassessmentdocument
	 *
	 * @url    GET                   getDashBoardInfoRiskAssessmentDocument
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoRiskAssessmentDocument($entity = 1)
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read) {
			throw new RestException(401);
		}

		global $conf;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($this->db, $entity);

		require_once __DIR__ . '/digiriskdocuments/riskassessmentdocument.class.php';

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->load_dashboard();
	}

	/**
	 * Get dashboard info accidents
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info accidents
	 *
	 * @url    GET                   getDashBoardInfoAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoAccidents($entity = 1)
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read) {
			throw new RestException(401);
		}

		global $conf;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($this->db, $entity);

		require_once __DIR__ . '/accident.class.php';

		$accident = new Accident($this->db);
		return $accident->load_dashboard();
	}
}
