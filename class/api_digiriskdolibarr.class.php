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

require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/digirisktask.class.php';
require_once __DIR__ . '/digiriskdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/digiriskresources.class.php';
require_once __DIR__ . '/evaluator.class.php';

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
	 * Set conf entity
	 *
	 * @param  integer $entity Entity ID
	 *
	 */
	public function setConfEntity($entity)
	{
		global $conf;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($this->db, $entity);
	}

	/**
	 * Get properties of an order object by id
	 *
	 * Return an array with order information
	 *
	 * @return    int data without useless information
	 *
	 * @throws Exception
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
	 * Return an array with order information
	 *
	 * @return    int data without useless information
	 *
	 * @url GET    disableModule
	 *
	 */
	public function disableModule()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return $this->mod->remove();
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return    string data without useless information
	 *
	 * @url GET    getFilesVersion
	 *
	 */
	public function getFilesVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return $this->mod->version;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getActiveVersion
	 *
	 */
	public function getActiveVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|mixed data without useless information
	 *
	 * @url GET    getLatestVersion
	 *
	 */
	public function getLatestVersion()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf;

		return $conf->global->DIGIRISKDOLIBARR_VERSION;
	}

	/**
	 * Get properties of an order object by ref_ext
	 *
	 * Return an array with order information
	 *
	 * @return 	string|false data without useless information
	 *
	 * @url GET    uploadNewModule
	 *
	 */
	public function uploadNewModule()
	{
		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->adminpage->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		return exec('cd ../custom/digiriskdolibarr/shell/pull && bash update_version.sh');
	}

	// BEGIN ALL API ROUTE FOR DASHBOARD INFO

	/**
	 * Get dashboard info risks
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info risks
	 *
	 * @url    GET                   dashboard/getDashBoardRisks
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardRisks($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->risk->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$risk = new Risk($this->db);
		return $risk->load_dashboard();
	}

	/**
	 * Get dashboard info tasks
	 *
	 * @param integer $entity Entity ID
	 *
	 * @return array                 All data for dashboard info tasks
	 *
	 * @url    GET                   dashboard/getDashBoardInfoTasks
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoTasks($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->projet->lire || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

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
	 * @url    GET                   dashboard/getDashBoardInfoRiskAssessmentDocument
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoRiskAssessmentDocument($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

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
	 * @url    GET                   dashboard/getDashBoardInfoAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->load_dashboard();
	}

	/**
	 * Get dashboard info digiriskresources
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info digiriskresources
	 *
	 * @url    GET                   dashboard/getDashBoardInfoDigiriskResources
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoDigiriskResources($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digiriskresources = new DigiriskResources($this->db);
		return $digiriskresources->load_dashboard();
	}

	/**
	 * Get dashboard info evaluators
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for dashboard info evaluators
	 *
	 * @url    GET                   dashboard/getDashBoardInfoEvaluators
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getDashBoardInfoEvaluators($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->load_dashboard();
	}

	// END ALL API ROUTE FOR DASHBOARD INFO

	// BEGIN ALL UNIQUE OBJECT API ROUTE

	// RISK

	/**
	 * Get risks by cotation.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for risks by cotation.
	 *
	 * @url    GET                   risk/getRisksByCotation
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getRisksByCotation($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->risk->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		global $conf, $db;
		$entity = GETPOST('DOLENTITY') ? GETPOST('DOLENTITY') : $entity;
		$conf->setEntityValues($db, $entity);

		$risk = new Risk($db);
		return $risk->getRisksByCotation()['data'];
	}

	// TASK

	/**
	 * Get tasks by progress.
	 *
	 * @param integer $entity Entity ID
	 *
	 * @return array                 All data tasks by progress.
	 *
	 * @url    GET                   task/getTasksByProgress
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getTasksByProgress($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->projet->lire || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digirisktask = new DigiriskTask($this->db);
		return $digirisktask->getTasksByProgress()['data'];
	}

	// RISKASSESSMENTDOCUMENT

	/**
	 * Get riskassessmentdocument last generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for riskassessmentdocument last generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getLastGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getLastGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getLastGenerateDate();
	}

	/**
	 * Get riskassessmentdocument next generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for riskassessmentdocument next generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNextGenerateDate();
	}

	/**
	 * Get number days before next riskassessmentdocument generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days before next riskassessmentdocument generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNbDaysBeforeNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysBeforeNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNbDaysBeforeNextGenerateDate();
	}

	/**
	 * Get number days after next riskassessmentdocument generate date.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days before next riskassessmentdocument generate date.
	 *
	 * @url    GET                   riskassessmentdocument/getNbDaysAfterNextGenerateDate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysAfterNextGenerateDate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->riskassessmentdocument->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);
		return $riskassessmentdocument->getNbDaysAfterNextGenerateDate();
	}

	// ACCIDENT

	/**
	 * Get number days without accident.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number days without accident.
	 *
	 * @url    GET                   accident/getNbDaysWithoutAccident
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbDaysWithoutAccident($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbDaysWithoutAccident();
	}

	/**
	 * Get number accidents by type.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accidents by type.
	 *
	 * @url    GET                   accident/getNbAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidents()['data'];
	}

	/**
	 * Get number workstop days.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number workstop days.
	 *
	 * @url    GET                   accident/getNbWorkstopDays
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbWorkstopDays($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbWorkstopDays();
	}

	/**
	 * Get number accidents by employees.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accidents by employees.
	 *
	 * @url    GET                   accident/getNbAccidentsByEmployees
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidentsByEmployees($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidentsByEmployees();
	}

	/**
	 * Get number presqu'accidents.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number presqu'accidents.
	 *
	 * @url    GET                   accident/getNbPresquAccidents
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbPresquAccidents($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbPresquAccidents();
	}

	/**
	 * Get number accident investigations.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number accident investigations.
	 *
	 * @url    GET                   accident/getNbAccidentInvestigations
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbAccidentInvestigations($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getNbAccidentInvestigations();
	}

	/**
	 * Get frequency index (number accidents with DIAT by employees) x 1000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for frequency index (number accidents with DIAT by employees) x 1000.
	 *
	 * @url    GET                   accident/getFrequencyIndex
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getFrequencyIndex($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getFrequencyIndex();
	}

	/**
	 * Get frequency rate (number accidents with DIAT by working hours) x 1 000 000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for frequency rate (number accidents with DIAT by working hours) x 1 000 000.
	 *
	 * @url    GET                   accident/getFrequencyRate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getFrequencyRate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getFrequencyRate();
	}

	/**
	 * Get gravity rate (number workstop days by working hours) x 1 000.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for gravity rate (number workstop days by working hours) x 1 000.
	 *
	 * @url    GET                   accident/getGravityRate
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getGravityRate($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$accident = new Accident($this->db);
		return $accident->getGravityRate();
	}

	// DIGIRISKRESOURCES

	/**
	 * Get siret number.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for siret number.
	 *
	 * @url    GET                   digiriskresources/getSiretNumber
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getSiretNumber($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->accident->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$digiriskresources = new DigiriskResources($this->db);
		return $digiriskresources->getSiretNumber();
	}

	// EVALUATOR

	/**
	 * Get number employees involved.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number employees involved.
	 *
	 * @url    GET                   evaluator/getNbEmployeesInvolved
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbEmployeesInvolved($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->getNbEmployeesInvolved();
	}

	/**
	 * Get number employees.
	 *
	 * @param  integer       $entity Entity ID
	 *
	 * @return array                 All data for number employees.
	 *
	 * @url    GET                   evaluator/getNbEmployees
	 *
	 * @throws Exception
	 * @throws RestException         401 Not allowed
	 */
	public function getNbEmployees($entity = 1)
	{
		if (DolibarrApiAccess::$user->entity != $entity && DolibarrApiAccess::$user->entity > 0) {
			throw new RestException(401 , 'User not allowed in this entity');
		}

		if (!DolibarrApiAccess::$user->rights->digiriskdolibarr->evaluator->read || !DolibarrApiAccess::$user->rights->digiriskdolibarr->api->read) {
			throw new RestException(401);
		}

		$this->setConfEntity($entity);

		$evaluator = new Evaluator($this->db);
		return $evaluator->getNbEmployees();
	}

	// END ALL UNIQUE OBJECT API ROUTE

}
