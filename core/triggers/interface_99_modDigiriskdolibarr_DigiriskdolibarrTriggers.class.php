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
 * or see https://www.gnu.org/
 */


/**
 * \file    core/triggers/interface_99_modDigiriskdolibarr_DigiriskdolibarrTriggers.class.php
 * \ingroup digiriskdolibarr
 * \brief   Digirisk Dolibarr trigger.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for Digiriskdolibarr module
 */
class InterfaceDigiriskdolibarrTriggers extends DolibarrTriggers
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
		$this->family = "demo";
		$this->description = "Digiriskdolibarr triggers.";
		$this->version = '8.2.1';
		$this->picto = 'digiriskdolibarr@digiriskdolibarr';
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
	 * Function called when a Dolibarrr business event is done.
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
		if (empty($conf->digiriskdolibarr->enabled)) return 0; // If module is not enabled, we do nothing

		// Data and type of action are stored into $object and $action

		switch ($action) {
			case 'INFORMATIONSSHARING_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_INFORMATIONSSHARING_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('InformationsSharingGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'LEGALDISPLAY_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LEGALDISPLAY_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('LegalDisplayGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLANDOCUMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;

				$actioncomm->code        = 'AC_PREVENTIONPLANDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanDocumentGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'FIREPERMITDOCUMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;

				$actioncomm->code        = 'AC_FIREPERMITDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('FirePermitDocumentGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'GROUPMENTDOCUMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_GROUPMENTDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('GroupmentDocumentGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$res = $actioncomm->create($user);

				break;

			case 'WORKUNITDOCUMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_WORKUNITDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('WorkUnitDocumentGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'LISTINGRISKSPHOTO_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LISTINGRISKSPHOTO_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ListingRisksPhotoGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'LISTINGRISKSACTION_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LISTINGRISKSACTION_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('ListingRisksActionGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
			break;

			case 'RISKASSESSMENTDOCUMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_RISKASSESSMENTDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('RiskAssessmentDocumentGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'DIGIRISKELEMENT_CREATE' :

				if ( $object->element_type == 'groupment' ) {
					$object->call_trigger('GROUPMENT_CREATE', $user);
				}

				if ( $object->element_type == 'workunit' ) {
					$object->call_trigger('WORKUNIT_CREATE', $user);
				}

				break;

			case 'FIREPERMITDET_CREATE' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->elementid   = $object->fk_preventionplan;

				$actioncomm->code        = 'AC_FIREPERMITLINE_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('FirePermitLineCreatedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_firepermit;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'SIGNATURE_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'digirisksignature@digiriskdolibarr';
				$actioncomm->elementid   = $object->fk_preventionplan;

				$actioncomm->code        = 'AC_SIGNATURE_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('SignatureGeneratedWithDolibarr');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_MODIFY' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_INPROGRESS' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_INPROGRESS';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanInprogressTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_PENDINGSIGNATURE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_PENDINGSIGNATURE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanPendingSignatureTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_LOCKED' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanLockTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_ARCHIVED' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_ARCHIVED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanArchivedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLANLINE_CREATE' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanLineCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLANLINE_MODIFY' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanLineModifyTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLANLINE_DELETE' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanLineDeleteTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'DIGIRISKSIGNATURE_SIGNED' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$object_type = $object->table_element;
				$object_type = preg_replace('/digiriskdolibarr_/', '', $object_type);
				$object_type = preg_replace('/_signature/', '', $object_type);

				$actioncomm->elementtype        = $object_type.'@digiriskdolibarr';
				$actioncomm->code               = 'AC_DIGIRISKSIGNATURE_SIGNED';
				$actioncomm->type_code          = 'AC_OTH_AUTO';
				$actioncomm->label              = $langs->trans('DigiriskSignatureSignedTrigger');
				$actioncomm->datep              = $now;
				$actioncomm->fk_element         = $object->fk_object;
				$actioncomm->socpeopleassigned  = array($object->element_id => $object->element_id);
				$actioncomm->userownerid        = $user->id;
				$actioncomm->percentage         = -1;

				$actioncomm->create($user);
				break;

			case 'DIGIRISKSIGNATURE_PENDING_SIGNATURE' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$object_type = $object->table_element;
				$object_type = preg_replace('/digiriskdolibarr_/', '', $object_type);
				$object_type = preg_replace('/_signature/', '', $object_type);

				$actioncomm->elementtype        = $object_type.'@digiriskdolibarr';
				$actioncomm->code               = 'AC_DIGIRISKSIGNATURE_PENDING_SIGNATURE';
				$actioncomm->type_code          = 'AC_OTH_AUTO';
				$actioncomm->label              = $langs->trans('DigiriskSignaturePendingSignatureTrigger');
				$actioncomm->datep              = $now;
				$actioncomm->fk_element         = $object->fk_object;
				$actioncomm->socpeopleassigned  = array($object->element_id => $object->element_id);
				$actioncomm->userownerid        = $user->id;
				$actioncomm->percentage         = -1;

				$actioncomm->create($user);
				break;

			case 'DIGIRISKSIGNATURE_ABSENT' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$object_type = $object->table_element;
				$object_type = preg_replace('/digiriskdolibarr_/', '', $object_type);
				$object_type = preg_replace('/_signature/', '', $object_type);

				$actioncomm->elementtype        = $object_type.'@digiriskdolibarr';
				$actioncomm->code               = 'AC_DIGIRISKSIGNATURE_ABSENT';
				$actioncomm->type_code          = 'AC_OTH_AUTO';
				$actioncomm->label              = $langs->trans('DigiriskSignatureAbsentTrigger');
				$actioncomm->datep              = $now;
				$actioncomm->fk_element         = $object->fk_object;
				$actioncomm->socpeopleassigned  = array($object->element_id => $object->element_id);
				$actioncomm->userownerid        = $user->id;
				$actioncomm->percentage         = -1;

				$actioncomm->create($user);
				break;

			case 'DIGIRISKSIGNATURE_DELETED' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$object_type = $object->table_element;
				$object_type = preg_replace('/digiriskdolibarr_/', '', $object_type);
				$object_type = preg_replace('/_signature/', '', $object_type);

				$actioncomm->elementtype = $object_type.'@digiriskdolibarr';
				$actioncomm->code        = 'AC_DIGIRISKSIGNATURE_DELETED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('DigiriskSignatureDeletedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_object;
				$actioncomm->socpeopleassigned  = array($object->element_id => $object->element_id);
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'PREVENTIONPLANSIGNATURE_ADDATTENDANT' :

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANSIGNATURE_ADDATTENDANT';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanAddAttendantTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_object;
				$actioncomm->socpeopleassigned  = array($object->element_id => $object->element_id);
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				break;

			case 'TICKET_CREATE' :
				//envoi du mail avec les infos de l'objet aux adresses mail configurées
				//envoi du mail avec une trad puis avec un model
				$error = 0;
				if ($conf->global->DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT) {
					if (!$error) {
						$langs->load('mails');

						$listOfMails = $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO;
						if (!preg_match('/;/', $listOfMails)) {
							$sendto = $listOfMails;

							if (dol_strlen($sendto) && (!empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
								require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

								$from = $conf->global->MAIN_MAIL_EMAIL_FROM;

								$message = 	 $object->message;
								$subject = 	$langs->trans('NewTicketSubmitted') . ' : ' . $object->subject . $langs->trans('By') . /* extrafield */ '';
								$trackid = 'tic'.$object->id;

								// Create form object
								// Send mail (substitutionarray must be done just before this)
								$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

								if ($mailfile->error) {
									setEventMessages($mailfile->error, $mailfile->errors, 'errors');
								} else {
									if (!empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
										$result = $mailfile->sendfile();
										if (!$result) {
											$langs->load("other");
											$mesg = '<div class="error">';
											if ($mailfile->error) {
												$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
												$mesg .= '<br>' . $mailfile->error;
											} else {
												$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
											}
											$mesg .= '</div>';
											setEventMessages($mesg, null, 'warnings');
										}
									} else {
										setEventMessages($langs->trans('ErrorSetupEmail'), '', 'errors');
									}
								}

							} else {
								$langs->load("errors");
								setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
								dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
							}
						} else {
							$listOfMails = preg_split('/;/', $listOfMails);
							if (!empty($listOfMails) && $listOfMails > 0) {
								if (end($listOfMails) == ';') {
									array_pop($listOfMails);
								}
								foreach ($listOfMails as $email) {
									$sendto = $email;

									if (dol_strlen($sendto) && (!empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
										require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

										$from = $conf->global->MAIN_MAIL_EMAIL_FROM;

										$message = 	 $object->message;
										$subject = 	$langs->trans('NewTicketSubmitted') . ' : ' . $object->subject . $langs->trans('By') . /* extrafield */ '';
										$trackid = 'tic'.$object->id;

										// Create form object
										// Send mail (substitutionarray must be done just before this)
										$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

										if ($mailfile->error) {
											setEventMessages($mailfile->error, $mailfile->errors, 'errors');
										} else {
											if (!empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
												$result = $mailfile->sendfile();
												if (!$result) {
													$langs->load("other");
													$mesg = '<div class="error">';
													if ($mailfile->error) {
														$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
														$mesg .= '<br>' . $mailfile->error;
													} else {
														$mesg .= $langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), dol_escape_htmltag($sendto));
													}
													$mesg .= '</div>';
													setEventMessages($mesg, null, 'warnings');
												}
											} else {
												setEventMessages($langs->trans('ErrorSetupEmail'), '', 'errors');
											}
										}

									} else {
										$langs->load("errors");
										setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
										dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
									}
								}
							} else {
								// Mail sent KO
								$error++;
								if (!empty($error)) setEventMessages(null, $langs->trans('WrongEmailFormat'), 'errors');
								else  setEventMessages($error, null, 'errors');
							}
						}
					}
				}
				break;

			case 'PREVENTIONPLAN_SENTBYMAIL' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_SENTBYMAIL';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanSentByMailTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$actioncomm->create($user);
				$object->last_email_sent_date = dol_now('tzuser');
				$object->update($user, true);
				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}


		return 0;
	}
}
