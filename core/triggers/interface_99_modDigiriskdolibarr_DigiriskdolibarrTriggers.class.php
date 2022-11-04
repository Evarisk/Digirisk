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

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

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
	 * @var string Trigger name.
	 */
	public $name;

	/**
	 * @var string Trigger family.
	 */
	public $family;

	/**
	 * @var string Trigger description.
	 */
	public $description;

	/**
	 * @var string Trigger version.
	 */
	public $version;

	/**
	 * @var string String with name of icon for digiriskdolibarr.
	 */
	public $picto;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name        = preg_replace('/^Interface/i', '', get_class($this));
		$this->family      = "demo";
		$this->description = "Digiriskdolibarr triggers.";
		$this->version     = '9.6.0';
		$this->picto       = 'digiriskdolibarr@digiriskdolibarr';
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
	 * @param string $action Event action code
	 * @param CommonObject $object Object
	 * @param User $user Object user
	 * @param Translate $langs Object langs
	 * @param Conf $conf Object conf
	 * @return int                    <0 if KO, 0 if no triggered ran, >0 if OK
	 * @throws Exception
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->digiriskdolibarr->enabled)) return 0; // If module is not enabled, we do nothing

		// Data and type of action are stored into $object and $action

		switch ($action) {
			case 'INFORMATIONSSHARING_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->parent_id);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_INFORMATIONSSHARING_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('InformationsSharingGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskstandard->ref . ' ' . $digiriskstandard->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'COMPANY_DELETE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once __DIR__ . '/../../class/preventionplan.class.php';
				require_once __DIR__ . '/../../class/firepermit.class.php';
				require_once __DIR__ . '/../../class/digiriskresources.class.php';

				$preventionplan 	  = new PreventionPlan($this->db);
				$firepermit 		  = new FirePermit($this->db);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$alldigiriskresources = $digiriskresources->fetchAll('', '', 0, 0, array('customsql' => 't.element_id = ' . $object->id . ' AND t.element_type = "societe"'));

				if (is_array($alldigiriskresources) && !empty($alldigiriskresources)) {
					foreach ($alldigiriskresources as $digiriskresourcesingle) {
						if ($digiriskresourcesingle->object_type == 'preventionplan') {
							$preventionplan->fetch($digiriskresourcesingle->object_id);
							if ($preventionplan->status > 0) {
								$error[++$i] = $langs->trans('ErrorThirdPartyHasAtLeastOneChildOfTypePreventionPlan') . ' ' . $preventionplan->getNomUrl();
							}
						} else if ($digiriskresourcesingle->object_type == 'firepermit') {
							$firepermit->fetch($digiriskresourcesingle->object_id);
							if ($firepermit->status > 0) {
								$error[++$i] = $langs->trans('ErrorThirdPartyHasAtLeastOneChildOfTypeFirePermit') . ' ' . $firepermit->getNomUrl();
							}
						}
					}
					if (!empty($error)) {
						$error[++$i] = $langs->trans('ErrorRecordHasChildren');
						$object->errors = $error;
						return -1;
					}
				}
				break;

			case 'CONTACT_DELETE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once __DIR__ . '/../../class/preventionplan.class.php';
				require_once __DIR__ . '/../../class/firepermit.class.php';
				require_once __DIR__ . '/../../class/digiriskresources.class.php';

				$preventionplan 	  = new PreventionPlan($this->db);
				$firepermit 		  = new FirePermit($this->db);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$alldigiriskresources = $digiriskresources->fetchAll('', '', 0, 0, array('customsql' => 't.element_id = ' . $object->fk_soc . ' AND t.element_type = "societe"'));

				if (is_array($alldigiriskresources) && !empty($alldigiriskresources)) {
					foreach ($alldigiriskresources as $digiriskresourcesingle) {
						if ($digiriskresourcesingle->object_type == 'preventionplan') {
							$preventionplan->fetch($digiriskresourcesingle->object_id);
							if ($preventionplan->status > 0) {
								$error[++$i] = $langs->trans('ErrorContactHasAtLeastOneChildOfTypePreventionPlan') . ' ' . $preventionplan->getNomUrl();
							}
						} else if ($digiriskresourcesingle->object_type == 'firepermit') {
							$firepermit->fetch($digiriskresourcesingle->object_id);
							if ($firepermit->status > 0) {
								$error[++$i] = $langs->trans('ErrorContactHasAtLeastOneChildOfTypeFirePermit') . ' ' . $firepermit->getNomUrl();
							}
						}
					}
					if (!empty($error)) {
						$error[++$i] = $langs->trans('ErrorRecordHasChildren');
						$object->errors = $error;
						return -1;
					}
				}
				break;

			case 'LEGALDISPLAY_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->parent_id);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LEGALDISPLAY_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('LegalDisplayGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskstandard->ref . ' ' . $digiriskstandard->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLANDOCUMENT_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	= dol_now();
				$actioncomm 	= new ActionComm($this->db);
				$preventionplan = new PreventionPlan($this->db);
				$preventionplan->fetch($object->parent_id);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_PREVENTIONPLANDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->trans('PreventionPlanDocumentGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $preventionplan->ref . ' ' . $preventionplan->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMITDOCUMENT_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$firepermit = new FirePermit($this->db);
				$firepermit->fetch($object->parent_id);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_FIREPERMITDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitDocumentGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $firepermit->ref . ' ' . $firepermit->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'GROUPMENTDOCUMENT_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$groupment  = new DigiriskElement($this->db);
				$groupment->fetch($object->parent_id);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_GROUPMENTDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('GroupmentDocumentGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $groupment->ref . ' ' . $groupment->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'WORKUNITDOCUMENT_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$workunit   = new DigiriskElement($this->db);
				$workunit->fetch($object->parent_id);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_WORKUNITDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('WorkUnitDocumentGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $workunit->ref . ' ' . $workunit->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'LISTINGRISKSPHOTO_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now                     = dol_now();
				$actioncomm              = new ActionComm($this->db);

				if ($object->parent_type == 'digiriskstandard') {
					$actioncomm->elementtype = 'digiriskstandard@digiriskdolibarr';
					$parentelement = new DigiriskStandard($this->db);
					$parentelement->fetch($object->parent_id);
				} else {
					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
					$parentelement = new DigiriskElement($this->db);
					$parentelement->fetch($object->parent_id);
				}

				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LISTINGRISKSPHOTO_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('ListingRisksPhotoGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $parentelement->ref . ' ' . $parentelement->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'LISTINGRISKSACTION_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				if ($object->parent_type == 'digiriskstandard') {
					$actioncomm->elementtype = 'digiriskstandard@digiriskdolibarr';
					$parentelement = new DigiriskStandard($this->db);
					$parentelement->fetch($object->parent_id);
				} else {
					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
					$parentelement = new DigiriskElement($this->db);
					$parentelement->fetch($object->parent_id);
				}

				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_LISTINGRISKSACTION_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('ListingRisksActionGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $parentelement->ref . ' ' . $parentelement->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKASSESSMENTDOCUMENT_GENERATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->parent_id);

				$actioncomm->elementtype = $object->parent_type . '@digiriskdolibarr';
				$actioncomm->elementid   = $object->parent_id;
				$actioncomm->code        = 'AC_RISKASSESSMENTDOCUMENT_GENERATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskAssessmentDocumentGeneratedWithDolibarr');
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->parent_type . '</br>';
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskstandard->ref . ' ' . $digiriskstandard->label . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LastMainDoc') . ' : ' . $object->last_main_doc . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Type') . ' : ' . $object->type . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->parent_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKELEMENT_CREATE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->fk_standard);

				if (!empty($object->fk_parent)) {
					$digiriskparent = new DigiriskElement($this->db);
					$digiriskparent->fetch($object->fk_parent);
					$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' .  $digiriskparent->ref . ' - ' . $digiriskparent->label . '<br/>';
				}

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code        = 'AC_DIGIRISKELEMENT_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities(ucfirst($object->element_type). 'CreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Standard') . ' : ' . $digiriskstandard->ref . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '<br/>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br/>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br/>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $langs->trans($object->element_type) . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : 1' . '<br>';
				($object->ranks != 0 ? $actioncomm->note_private .= $langs->trans('Order') . ' : ' . $object->ranks . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('ShowInSelectOnPublicTicketInterface') . ' : ' . ($object->show_in_selector ? $langs->trans('Yes') : $langs->trans('No')) . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKELEMENT_MODIFY' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->fk_standard);

				if (!empty($object->fk_parent)) {
					$digiriskparent = new DigiriskElement($this->db);
					$digiriskparent->fetch($object->fk_parent);
					$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' .  $digiriskparent->ref . ' - ' . $digiriskparent->label . '<br/>';
				}

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code        = 'AC_DIGIRISKELEMENT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities(ucfirst($object->element_type) . 'ModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Standard') . ' : ' . $digiriskstandard->ref . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '<br/>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br/>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br/>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $langs->trans($object->element_type) . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				($object->ranks != 0 ? $actioncomm->note_private .= $langs->trans('Order') . ' : ' . $object->ranks . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('ShowInSelectOnPublicTicketInterface') . ' : ' . ($object->show_in_selector ? $langs->trans('Yes') : $langs->trans('No')) . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKELEMENT_DELETE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$digiriskstandard->fetch($object->fk_standard);

				if (!empty($object->fk_parent)) {
					$digiriskparent = new DigiriskElement($this->db);
					$digiriskparent->fetch($object->fk_parent);
					$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' .  $digiriskparent->ref . ' - ' . $digiriskparent->label . '<br/>';
				}

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code        = 'AC_DIGIRISKELEMENT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities(ucfirst($object->element_type) . 'DeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Standard') . ' : ' . $digiriskstandard->ref . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '<br/>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br/>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br/>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $langs->trans($object->element_type) . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				($object->ranks != 0 ? $actioncomm->note_private .= $langs->trans('Order') . ' : ' . $object->ranks . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('ShowInSelectOnPublicTicketInterface') . ' : ' . ($object->show_in_selector ? $langs->trans('Yes') : $langs->trans('No')) . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        		  = dol_now();
				$actioncomm 		  = new ActionComm($this->db);
				$object->element = 'preventionplan';
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
				}
				foreach ($societies as $societename => $key) {
					$actioncomm->note_private .= $langs->trans($societename) . ' : ';
					foreach ($key as $societe) {
						if ($societename == 'PP_LABOUR_INSPECTOR_ASSIGNED') {
							$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
						} else {
							$actioncomm->note_private .= $societe->name . '<br>';
						}
						if ($societename == 'PP_EXT_SOCIETY') {
							$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
							$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('CSSCTIntervention') . ' : ' . ($object->cssct_intervention ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				$actioncomm->note_private .= $langs->trans('PriorVisit') . ' : ' . ($object->prior_visit_bool ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				if ($object->prior_visit_bool) {
					$actioncomm->note_private .= $langs->trans('PriorVisitText') . ' : ' . (!empty($object->prior_visit_text) ? $object->prior_visit_text : 'N/A') . '</br>';
					$actioncomm->note_private .= $langs->trans('PriorVisitDate') . ' : ' . dol_print_date($object->prior_visit_date, 'dayhoursec') . '<br>';
				}
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object::STATUS_IN_PROGRESS . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$check++;
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
					if ($check == 2) break;
				}
				if ($societies) {
					foreach ($societies as $societename => $key) {
						$actioncomm->note_private .= $langs->trans($societename) . ' : ';
						foreach ($key as $societe) {
							if ($societename == 'PP_LABOUR_INSPECTOR_ASSIGNED') {
								$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
							} else {
								$actioncomm->note_private .= $societe->name . '<br>';
							}
							if ($societename == 'PP_EXT_SOCIETY') {
								$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
								$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
							}
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('CSSCTIntervention') . ' : ' . ($object->cssct_intervention ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				$actioncomm->note_private .= $langs->trans('PriorVisit') . ' : ' . ($object->prior_visit_bool ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				if ($object->prior_visit_bool) {
					$actioncomm->note_private .= $langs->trans('PriorVisitText') . ' : ' . (!empty($object->prior_visit_text) ? $object->prior_visit_text : 'N/A') . '</br>';
					$actioncomm->note_private .= $langs->trans('PriorVisitDate') . ' : ' . dol_print_date($object->prior_visit_date, 'dayhoursec') . '<br>';
				}
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$check++;
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
					if ($check == 2) break;
				}
				if ($societies) {
					foreach ($societies as $societename => $key) {
						$actioncomm->note_private .= $langs->trans($societename) . ' : ';
						foreach ($key as $societe) {
							if ($societename == 'PP_LABOUR_INSPECTOR_ASSIGNED') {
								$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
							} else {
								$actioncomm->note_private .= $societe->name . '<br>';
							}
							if ($societename == 'PP_EXT_SOCIETY') {
								$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
								$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
							}
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('CSSCTIntervention') . ' : ' . ($object->cssct_intervention ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				$actioncomm->note_private .= $langs->trans('PriorVisit') . ' : ' . ($object->prior_visit_bool ? $langs->trans("Yes") : $langs->trans("No")) . '<br>';
				if ($object->prior_visit_bool) {
					$actioncomm->note_private .= $langs->trans('PriorVisitText') . ' : ' . (!empty($object->prior_visit_text) ? $object->prior_visit_text : 'N/A') . '</br>';
					$actioncomm->note_private .= $langs->trans('PriorVisitDate') . ' : ' . dol_print_date($object->prior_visit_date, 'dayhoursec') . '<br>';
				}
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_INPROGRESS' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_INPROGRESS';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanInprogressTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_PENDINGSIGNATURE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_PENDINGSIGNATURE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanPendingSignatureTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_LOCKED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanLockTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_ARCHIVED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_ARCHIVED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanArchivedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLANLINE_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanLineCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('PreventionMethod') . ' : ' . (!empty($object->prevention_method) ? $object->prevention_method : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLANLINE_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanLineModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('PreventionMethod') . ' : ' . (!empty($object->prevention_method) ? $object->prevention_method : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLANLINE_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLANLINE_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanLineDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('PreventionMethod') . ' : ' . (!empty($object->prevention_method) ? $object->prevention_method : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_preventionplan;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLANSIGNATURE_ADDATTENDANT' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = 'preventionplan@digiriskdolibarr';
				$actioncomm->code              = 'AC_PREVENTIONPLANSIGNATURE_ADDATTENDANT';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('PreventionPlanAddAttendantTrigger');
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'PREVENTIONPLAN_SENTBYMAIL' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->code        = 'AC_PREVENTIONPLAN_SENTBYMAIL';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('PreventionPlanSentByMailTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				$object->last_email_sent_date = dol_now('tzuser');
				$object->update($user, true);
				break;

			case 'FIREPERMIT_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        		  = dol_now();
				$actioncomm 		  = new ActionComm($this->db);
				$object->element = 'firepermit';
				$preventionplan 	  = new PreventionPlan($this->db);
				$preventionplan->fetch($object->fk_preventionplan);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
				}
				foreach ($societies as $societename => $key) {
					$actioncomm->note_private .= $langs->trans($societename) . ' : ';
					foreach ($key as $societe) {
						if ($societename == 'FP_LABOUR_INSPECTOR_ASSIGNED') {
							$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
						} else {
							$actioncomm->note_private .= $societe->name . '<br>';
						}
						if ($societename == 'FP_EXT_SOCIETY') {
							$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
							$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('PreventionPlan') . ' : ' . $preventionplan->ref . (!empty($preventionplan->label) ? ' ' . $preventionplan->label : '') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$preventionplan 	  = new PreventionPlan($this->db);
				$preventionplan->fetch($object->fk_preventionplan);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$check++;
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
					if ($check == 2) break;
				}
				if ($societies) {
					foreach ($societies as $societename => $key) {
						$actioncomm->note_private .= $langs->trans($societename) . ' : ';
						foreach ($key as $societe) {
							if ($societename == 'FP_LABOUR_INSPECTOR_ASSIGNED') {
								$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
							} else {
								$actioncomm->note_private .= $societe->name . '<br>';
							}
							if ($societename == 'FP_EXT_SOCIETY') {
								$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
								$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
							}
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('PreventionPlan') . ' : ' . $preventionplan->ref . (!empty($preventionplan->label) ? ' ' . $preventionplan->label : '') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);
				$preventionplan 	  = new PreventionPlan($this->db);
				$preventionplan->fetch($object->fk_preventionplan);
				$digiriskresources 	  = new DigiriskResources($this->db);
				$societies = $digiriskresources->fetchResourcesFromObject('', $object);
				$digirisksignature	  = new DigiriskSignature($this->db);
				$signatories = $digirisksignature->fetchSignatories($object->id, $object->element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . (!empty($object->label) ? $object->label : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('StartDate') . ' : ' . dol_print_date($object->date_start, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('EndDate') . ' : ' . dol_print_date($object->date_end, 'dayhoursec') . '<br>';
				foreach($signatories as $signatory) {
					$check++;
					$actioncomm->note_private .= $langs->trans($signatory->role) . ' : ' . $signatory->firstname . ' ' . $signatory->lastname . '<br>';
					if ($check == 2) break;
				}
				if ($societies) {
					foreach ($societies as $societename => $key) {
						$actioncomm->note_private .= $langs->trans($societename) . ' : ';
						foreach ($key as $societe) {
							if ($societename == 'FP_LABOUR_INSPECTOR_ASSIGNED') {
								$actioncomm->note_private .= $societe->firstname . ' ' . $societe->lastname . '<br>';
							} else {
								$actioncomm->note_private .= $societe->name . '<br>';
							}
							if ($societename == 'FP_EXT_SOCIETY') {
								$actioncomm->note_private .= $langs->trans('Address') . ' : ' . $societe->address . '<br>';
								$actioncomm->note_private .= $langs->trans('SIRET') . ' : ' . $societe->idprof2 . '<br>';
							}
						}
					}
				}
				$actioncomm->note_private .= $langs->trans('PreventionPlan') . ' : ' . $preventionplan->ref . (!empty($preventionplan->label) ? ' ' . $preventionplan->label : '') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_INPROGRESS' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_INPROGRESS';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitInProgressTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_PENDINGSIGNATURE' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_PENDINGSIGNATURE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitPendingSignatureTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_LOCKED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_LOCKED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitLockTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_ARCHIVED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_ARCHIVED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitArchivedTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMITLINE_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMITLINE_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitLineCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_fire_permit_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UsedEquipment') . ' : ' . (!empty($object->used_equipment) ? $object->used_equipment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_firepermit;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMITLINE_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMITLINE_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitLineModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_fire_permit_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UsedEquipment') . ' : ' . (!empty($object->used_equipment) ? $object->used_equipment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_firepermit;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMITLINE_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        	 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$risk 			 = new Risk($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMITLINE_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitLineDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '</br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('INRSRisk') . ' : ' .  $risk->get_fire_permit_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UsedEquipment') . ' : ' . (!empty($object->used_equipment) ? $object->used_equipment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_firepermit;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMITSIGNATURE_ADDATTENDANT' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = 'firepermit@digiriskdolibarr';
				$actioncomm->code              = 'AC_FIREPERMITSIGNATURE_ADDATTENDANT';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('FirePermitAddAttendantTrigger');
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'FIREPERMIT_SENTBYMAIL' :
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->code        = 'AC_FIREPERMIT_SENTBYMAIL';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('FirePermitSentByMailTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				$object->last_email_sent_date = dol_now('tzuser');
				$object->update($user, true);
				break;

			case 'DIGIRISKSIGNATURE_SIGNED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now = dol_now();

				if ($object->element_type == 'socpeople') {
					$people = new Contact($this->db);
				} elseif ($object->element_type == 'user') {
					$people = new User($this->db);
				}

				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = $object->object_type . '@digiriskdolibarr';
				$actioncomm->code        = 'AC_DIGIRISKSIGNATURE_SIGNED';
				$actioncomm->type_code   = 'AC_OTH_AUTO';

				if (!empty($people)) {
					$people->fetch($object->element_id);
					$actioncomm->label = $langs->transnoentities($object->role . 'Signed') . ' : ' . $people->firstname . ' ' . $people->lastname;
				} else {
					$actioncomm->label = $langs->transnoentities($object->role . 'Signed');
				}
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('SignatureDate') . ' : ' . dol_print_date($object->signature_date, 'dayhoursec') . '<br>';
				!empty($object->signature_location) ? $actioncomm->note_private .= $langs->trans('SignatureLocation') . ' : ' . $object->signature_location . '<br>' : '';
				!empty($object->signature_comment) ? $actioncomm->note_private .= $langs->trans('SignatureComment') . ' : ' . $object->signature_comment . '<br>' : '';
				!empty($object->role) ? $actioncomm->note_private .= $langs->trans('Role') . ' : ' . $langs->trans($object->role) . '<br>' : '';
				$actioncomm->note_private .= $langs->trans('Name') . ' : ' . $object->firstname . ' ' . $object->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('SocietyName') . ' : ' . $object->society_name . '<br>';
				!empty($object->email) ? $actioncomm->note_private .= $langs->trans('Email') . ' : ' . $object->email . '<br>' : '';
				!empty($object->last_email_sent_date) ? $actioncomm->note_private .= $langs->trans('LastEmailSentDate') . ' : ' . dol_print_date($object->last_email_sent_date, 'dayhoursec', 'tzuser') . '<br>' : '';
				!empty($object->phone) ? $actioncomm->note_private .= $langs->trans('Phone') . ' : ' . $object->phone . '<br>' : '';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->element_type . '<br>';
				!empty($object->stamp) ? $actioncomm->note_private .= $langs->trans('Stamp') . ' : ' . $object->stamp . '<br>' : '';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKSIGNATURE_PENDING_SIGNATURE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@digiriskdolibarr';
				$actioncomm->code              = 'AC_DIGIRISKSIGNATURE_PENDING_SIGNATURE';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DigiriskSignaturePendingSignatureTrigger');
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKSIGNATURE_ABSENT' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@digiriskdolibarr';
				$actioncomm->code              = 'AC_DIGIRISKSIGNATURE_ABSENT';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DigiriskSignatureAbsentTrigger');
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'DIGIRISKSIGNATURE_DELETED' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype       = $object->object_type . '@digiriskdolibarr';
				$actioncomm->code              = 'AC_DIGIRISKSIGNATURE_DELETED';
				$actioncomm->type_code         = 'AC_OTH_AUTO';
				$actioncomm->label             = $langs->transnoentities('DigiriskSignatureDeletedTrigger');
				$actioncomm->datep             = $now;
				$actioncomm->fk_element        = $object->fk_object;
				if ($object->element_type == 'socpeople') {
					$actioncomm->socpeopleassigned = array($object->element_id => $object->element_id);
				}
				$actioncomm->userownerid       = $user->id;
				$actioncomm->percentage        = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'TICKET_CREATE' :
				if ($conf->global->DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT) {
					//envoi du mail avec les infos de l'objet aux adresses mail configurées
					//envoi du mail avec une trad puis avec un model
					$error = 0;
					$formmail = new FormMail($this->db);
					$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'ticket_send', $user, $langs); // If $model_id is empty, preselect the first one

					$table_element = $object->table_element;
					$object->table_element = '';
					$substitutionarray = getCommonSubstitutionArray($langs, 0, null,$object);
					$object->table_element = $table_element;

					complete_substitutions_array($substitutionarray, $langs, $object);

					$subject = make_substitutions($arraydefaultmessage->topic,$substitutionarray);
					$message = make_substitutions($arraydefaultmessage->content,$substitutionarray) . '<br>' . $object->message;

					if ( ! $error) {
						$langs->load('mails');

						$listOfMails = $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO;
						if ( ! preg_match('/;/', $listOfMails)) {
							$sendto = $listOfMails;

							if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
								require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

								$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
								$trackid = 'tic' . $object->id;

								// Create form object
								// Send mail (substitutionarray must be done just before this)
								$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

								if ($mailfile->error) {
									setEventMessages($mailfile->error, $mailfile->errors, 'errors');
								} elseif ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
									$result = $mailfile->sendfile();
									if ( ! $result) {
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
									} else {
										dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
										require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
										$now        = dol_now();
										$actioncomm = new ActionComm($this->db);
										$actioncomm->elementtype = 'ticket';
										$actioncomm->code      = 'AC_TICKET_CREATION_MAIL_SENT';
										$actioncomm->type_code = 'AC_OTH_AUTO';
										$actioncomm->label = $langs->transnoentities('TicketCreationMailWellSent');
										$actioncomm->note = $langs->transnoentities('TicketCreationMailSent', $listOfMails);
										$actioncomm->datep       = $now;
										$actioncomm->fk_element  = $object->id;
										$actioncomm->userownerid = $user->id;
										$actioncomm->percentage  = -1;

										$result = $actioncomm->create($user);
										if ($result < 0) {
											$object->errors = array_merge($object->error, $actioncomm->errors);
											return $result;
										}
										break;
									}
								} else {
									setEventMessages($langs->trans('ErrorSetupEmail'), '', 'errors');
								}
							} else {
								$langs->load("errors");
								setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
								dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
							}
						} else {
							$listOfMails = preg_split('/;/', $listOfMails);
							if ( ! empty($listOfMails) && $listOfMails > 0) {
								if (end($listOfMails) == ';') {
									array_pop($listOfMails);
								}
								foreach ($listOfMails as $email) {
									$sendto = $email;

									if (dol_strlen($sendto) && ( ! empty($conf->global->MAIN_MAIL_EMAIL_FROM))) {
										require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

										$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
										$trackid = 'tic' . $object->id;

										// Create form object
										// Send mail (substitutionarray must be done just before this)
										$mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, -1, '', '', $trackid, '', 'ticket');

										if ($mailfile->error) {
											setEventMessages($mailfile->error, $mailfile->errors, 'errors');
										} else {
											if ( ! empty($conf->global->MAIN_MAIL_SMTPS_ID)) {
												$result = $mailfile->sendfile();
												if ( ! $result) {
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
												} else {
													dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
													require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
													$now        = dol_now();
													$actioncomm = new ActionComm($this->db);
													$actioncomm->elementtype = 'ticket';
													$actioncomm->code      = 'AC_TICKET_CREATION_MAIL_SENT';
													$actioncomm->type_code = 'AC_OTH_AUTO';
													$actioncomm->label = $langs->transnoentities('TicketCreationMailWellSent');
													$actioncomm->note = $langs->transnoentities('TicketCreationMailSent', $listOfMails);
													$actioncomm->datep       = $now;
													$actioncomm->fk_element  = $object->id;
													$actioncomm->userownerid = $user->id;
													$actioncomm->percentage  = -1;
													$result = $actioncomm->create($user);
													if ($result < 0) {
														$object->errors = array_merge($object->error, $actioncomm->errors);
														return $result;
													}
													break;
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
								if ( ! empty($error)) setEventMessages(null, $langs->trans('WrongEmailFormat'), 'errors');
								else setEventMessages($error, null, 'errors');
							}
						}
					}
				}
				break;

			case 'OPENINGHOURS_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				if ($object->element_type == 'societe') {
					$actioncomm->socid = $object->element_id;
				} else {
					$actioncomm->elementtype = $object->element_type . '@digiriskdolibarr';
				}
				$actioncomm->code      = 'AC_OPENINGHOURS_CREATE';
				$actioncomm->type_code = 'AC_OTH_AUTO';
				if ($object->element_type == 'preventionplan') {
					$actioncomm->label = $langs->transnoentities('PreventionPlanOpeningHoursCreateTrigger');
					$elementParent = new PreventionPlan($this->db);
					$elementParent->fetch($object->element_id);
				} elseif ($object->element_type == 'firepermit') {
					$actioncomm->label = $langs->transnoentities('FirePermitOpeningHoursCreateTrigger');
					$elementParent = new FirePermit($this->db);
					$elementParent->fetch($object->element_id);
				}
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $elementParent->ref . (!empty($elementparent->label) ? ' ' . $elementparent->label : '') . '<br>';
				$actioncomm->note_private .= $langs->trans('ElementType') . ' : ' . $object->element_type . '<br>';
				$actioncomm->note_private .= $langs->trans('Monday') . ' : ' . (!empty($object->monday) ? $object->monday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Tuesday') . ' : ' . (!empty($object->tuesday) ? $object->tuesday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Wednesday') . ' : ' . (!empty($object->wednesday) ? $object->wednesday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Thursday') . ' : ' . (!empty($object->thursday) ? $object->thursday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Friday') . ' : ' . (!empty($object->friday) ? $object->friday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Saturday') . ' : ' . (!empty($object->saturday) ? $object->saturday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Sunday') . ' : ' . (!empty($object->sunday) ? $object->sunday : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->element_id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISK_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 			 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$project 		 = new Project($this->db);
				$digiriskelement->fetch($object->fk_element);
				$project->fetch($object->fk_projet);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code 		 = 'AC_RISK_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label 		 = $langs->transnoentities('RiskCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' .  $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Projet') . ' : ' . $project->ref . " " . $project->title . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;
				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISK_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 			 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$project 		 = new Project($this->db);
				$digiriskelement->fetch($object->fk_element);
				$project->fetch($object->fk_projet);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code 		 = 'AC_RISK_MODIFY';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label 		 = $langs->transnoentities('RiskModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Projet') . ' : ' . $project->ref . " " . $project->title . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep 		 = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISK_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 			 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$project 		 = new Project($this->db);
				$digiriskelement->fetch($object->fk_element);
				$project->fetch($object->fk_projet);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code 		 = 'AC_RISK_DELETE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label 		 = $langs->transnoentities('RiskDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Projet') . ' : ' . $project->ref . " " . $project->title . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_danger_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep 		 = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'TASK_CREATE' :

				if (!empty($object->array_options['options_fk_risk'])) {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
					require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
					require_once __DIR__ . '/../../class/digiriskelement.class.php';
					$now = dol_now();
					$langs->load("projects");
					$actioncomm = new ActionComm($this->db);
					$digiriskelement = new DigiriskElement($this->db);
					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
					$actioncomm->code = 'AC_TASK_CREATE';
					$actioncomm->type_code = 'AC_OTH_AUTO';
					$actioncomm->label = $langs->transnoentities('TaskCreateTrigger', $object->ref);
					$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
					$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
					$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
					$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_c, 'dayhoursec', 'tzuser') . '<br>';
					$actioncomm->datep = $now;
					$actioncomm->fk_element = $risk->fk_element;
					$actioncomm->userownerid = $user->id;
					$actioncomm->percentage = -1;

					$result = $actioncomm->create($user);
					if ($result < 0) {
						$object->errors = array_merge($object->error, $actioncomm->errors);
						return $result;
					}
				}
				break;

			case 'TASK_MODIFY' :

				if (!empty($object->array_options['options_fk_risk'])) {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
					require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
					require_once __DIR__ . '/../../class/digiriskelement.class.php';
					$now = dol_now();
					$langs->load("projects");
					$actioncomm = new ActionComm($this->db);
					$digiriskelement = new DigiriskElement($this->db);
					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
					$actioncomm->code = 'AC_TASK_MODIFY';
					$actioncomm->type_code = 'AC_OTH_AUTO';
					$actioncomm->label = $langs->transnoentities('TaskModifyTrigger', $object->ref);
					$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
					$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
					$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
					$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_c, 'dayhoursec', 'tzuser') . '<br>';
					$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
					$actioncomm->datep = $now;
					$actioncomm->fk_element = $risk->fk_element;
					$actioncomm->userownerid = $user->id;
					$actioncomm->percentage = -1;

					$result = $actioncomm->create($user);
					if ($result < 0) {
						$object->errors = array_merge($object->error, $actioncomm->errors);
						return $result;
					}
				}
				break;

			case 'TASK_DELETE' :

				if ($object->array_options['options_fk_risk'] != 0) {
					dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
					require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
					require_once __DIR__ . '/../../class/digiriskelement.class.php';
					$now = dol_now();
					$langs->load("projects");
					$actioncomm = new ActionComm($this->db);
					$digiriskelement = new DigiriskElement($this->db);
					$risk = new Risk($this->db);
					$digiriskelement->fetch($object->fk_element);
					$risk->fetch($object->array_options['options_fk_risk']);

					if ($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS) {
						$timeSpent = $object->getSummaryOfTimeSpent();
						$label_progress = 'ProgressDeclared';
						if ($timeSpent['total_duration'] > 0 && !empty($object->planned_workload)) {
							$task_progress = round($timeSpent['total_duration'] / $object->planned_workload * 100, 2);
						} else {
							$task_progress = 0;
						}
					} else {
						(!empty($object->progress) ? $task_progress = $object->progress : $task_progress = 0);
						$label_progress = 'ProgressCalculated';
					}

					$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
					$actioncomm->code = 'AC_TASK_DELETE';
					$actioncomm->type_code = 'AC_OTH_AUTO';
					$actioncomm->label = $langs->transnoentities('TaskDeleteTrigger', $object->ref);
					$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
					$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
					$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
					$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
					$actioncomm->note_private .= $langs->trans($label_progress) . ' : ' . $task_progress . '%' . '<br>';
					$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_c, 'dayhoursec', 'tzuser') . '<br>';
					$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
					$actioncomm->datep = $now;
					$actioncomm->fk_element = $risk->fk_element;
					$actioncomm->userownerid = $user->id;
					$actioncomm->percentage = -1;

					$result = $actioncomm->create($user);
					if ($result < 0) {
						$object->errors = array_merge($object->error, $actioncomm->errors);
						return $result;
					}
				}
				break;

			case 'RISKASSESSMENT_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 				  = dol_now();
				$actioncomm 		  = new ActionComm($this->db);
				$digiriskelement	  = new DigiriskElement($this->db);
				$risk 				  = new Risk($this->db);
				$risk->fetch($object->fk_risk);
				$digiriskelement->fetch($risk->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code 		 = 'AC_RISKASSESSMENT_CREATE';
				$actioncomm->type_code	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->transnoentities('RiskAssessmentCreateTrigger', $object->ref);

				$actioncomm->note_private .= $langs->trans('ParentRisk') . ' : ' . $risk->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Comment') . ' : ' . (!empty($object->comment) ? $object->comment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				((!empty($object->date_riskassessment) && $conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) ? $actioncomm->note_private .= $langs->trans('RiskAssessmentDate') . ' : ' . dol_print_date($object->date_riskassessment, 'day') . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				if ($object->method == 'advanced') {
					$actioncomm->note_private .= $langs->trans('Evaluation') . ' : ' . $object->cotation . '<br>';
					$actioncomm->note_private .= $langs->trans('Gravity') . ' : ' . $object->gravite . '<br>';
					$actioncomm->note_private .= $langs->trans('Protection') . ' : ' . $object->protection . '<br>';
					$actioncomm->note_private .= $langs->trans('Occurrence') . ' : ' . $object->occurrence . '<br>';
					$actioncomm->note_private .= $langs->trans('Formation') . ' : ' . $object->formation . '<br>';
					$actioncomm->note_private .= $langs->trans('Exposition') . ' : ' . $object->exposition . '<br>';
				}
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $risk->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKASSESSMENT_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 			 = dol_now();
				$actioncomm		 = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$risk 			 = new Risk($this->db);
				$risk->fetch($object->fk_risk);
				$digiriskelement->fetch($risk->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_RISKASSESSMENT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskAssessmentModifyTrigger', $object->ref);

				$actioncomm->note_private .= $langs->trans('ParentRisk') . ' : ' . $risk->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Comment') . ' : ' . (!empty($object->comment) ? $object->comment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				((!empty($object->date_riskassessment) && $conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) ? $actioncomm->note_private .= $langs->trans('RiskAssessmentDate') . ' : ' . dol_print_date($object->date_riskassessment, 'day') . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				if ($object->method == 'advanced') {
					$actioncomm->note_private .= $langs->trans('Evaluation') . ' : ' . $object->cotation . '<br>';
					$actioncomm->note_private .= $langs->trans('Gravity') . ' : ' . $object->gravite . '<br>';
					$actioncomm->note_private .= $langs->trans('Protection') . ' : ' . $object->protection . '<br>';
					$actioncomm->note_private .= $langs->trans('Occurrence') . ' : ' . $object->occurrence . '<br>';
					$actioncomm->note_private .= $langs->trans('Formation') . ' : ' . $object->formation . '<br>';
					$actioncomm->note_private .= $langs->trans('Exposition') . ' : ' . $object->exposition . '<br>';
				}
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $risk->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKASSESSMENT_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now 			 = dol_now();
				$actioncomm 	 = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$risk 			 = new Risk($this->db);
				$risk->fetch($object->fk_risk);
				$digiriskelement->fetch($risk->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_RISKASSESSMENT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskAssessmentDeleteTrigger', $object->ref);

				$actioncomm->note_private .= $langs->trans('ParentRisk') . ' : ' . $risk->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Comment') . ' : ' . (!empty($object->comment) ? $object->comment : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				((!empty($object->date_riskassessment) && $conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE) ? $actioncomm->note_private .= $langs->trans('RiskAssessmentDate') . ' : ' . dol_print_date($object->date_riskassessment, 'day') . '<br>' : '');
				$actioncomm->note_private .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				if ($object->method == 'advanced') {
					$actioncomm->note_private .= $langs->trans('Evaluation') . ' : ' . $object->cotation . '<br>';
					$actioncomm->note_private .= $langs->trans('Gravity') . ' : ' . $object->gravite . '<br>';
					$actioncomm->note_private .= $langs->trans('Protection') . ' : ' . $object->protection . '<br>';
					$actioncomm->note_private .= $langs->trans('Occurrence') . ' : ' . $object->occurrence . '<br>';
					$actioncomm->note_private .= $langs->trans('Formation') . ' : ' . $object->formation . '<br>';
					$actioncomm->note_private .= $langs->trans('Exposition') . ' : ' . $object->exposition . '<br>';
				}
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $risk->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'EVALUATOR_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$userstat 		 = new User($this->db);
				$digiriskelement->fetch($object->fk_parent);
				$userstat->fetch($object->fk_user);
				$langs->load('companies');

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_EVALUATOR_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('EvaluatorCreateTrigger', $object->ref_ext);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref_ext . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('UserAssigned') . ' : ' . $userstat->firstname . " " . $userstat->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('PostOrFunction') . ' : ' . (!empty($object->job) ? $object->job : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('AssignmentDate') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('EvaluationDuration') . ' : ' . convertSecondToTime($object->duration * 60, 'allhourmin') . ' min' . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_parent;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'EVALUATOR_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$userstat 		 = new User($this->db);
				$digiriskelement->fetch($object->fk_parent);
				$userstat->fetch($object->fk_user);
				$langs->load('companies');

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_EVALUATOR_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('EvaluatorModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('UserAssigned') . ' : ' . $userstat->firstname . " " . $userstat->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('PostOrFunction') . ' : ' . (!empty($object->job) ? $object->job : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('AssignmentDate') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('EvaluationDuration') . ' : ' . convertSecondToTime($object->duration * 60, 'allhourmin') . ' min' . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_parent;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'EVALUATOR_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$userstat 		 = new User($this->db);
				$digiriskelement->fetch($object->fk_parent);
				$userstat->fetch($object->fk_user);
				$langs->load('companies');

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_EVALUATOR_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('EvaluatorDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('UserAssigned') . ' : ' . $userstat->firstname . " " . $userstat->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('PostOrFunction') . ' : ' . (!empty($object->job) ? $object->job : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('AssignmentDate') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('EvaluationDuration') . ' : ' . convertSecondToTime($object->duration * 60, 'allhourmin') . ' min' . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_parent;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKSIGN_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_RISKSIGN_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskSignCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_risksign_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKSIGN_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_RISKSIGN_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskSignModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_risksign_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'RISKSIGN_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);
				$digiriskelement = new DigiriskElement($this->db);
				$digiriskelement->fetch($object->fk_element);

				$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
				$actioncomm->code        = 'AC_RISKSIGN_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('RiskSignDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('RiskCategory') . ' : ' . $object->get_risksign_category_name($object) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENT_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskelement  = new DigiriskElement($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$society 		  = new Societe($this->db);
				$uservictim 	  = new User($this->db);
				$uservictim->fetch($object->fk_user_victim);
				$useremployer	  = new User($this->db);
				$useremployer->fetch($object->fk_user_employer);

				//1 : Accident in DU / GP, 2 : Accident in society, 3 : Accident in another location
				switch ($object->external_accident) {
					case 1:
						if (!empty($object->fk_standard)) {
							$actioncomm->elementtype = 'digiriskstandard@digiriskdolibarr';
							$digiriskstandard->fetch($object->fk_standard);
							$actioncomm->fk_element  = $object->fk_standard;
							$accidentLocation = $digiriskstandard->ref . " - " . $conf->global->MAIN_INFO_SOCIETE_NOM;
						} else if (!empty($object->fk_element)) {
							$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
							$digiriskelement->fetch($object->fk_element);
							$actioncomm->fk_element  = $object->fk_element;
							$accidentLocation = $digiriskelement->ref . " - " . $digiriskelement->label;
						}
						break;
					case 2:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$society->fetch($object->fk_soc);
						$actioncomm->fk_element  = $object->fk_soc;
						$accidentLocation = $society->ref . " - " . $society->label;
					case 3:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$actioncomm->fk_element  = $object->id;
						$accidentLocation = $object->accident_location;
						break;
				}

				$actioncomm->code        = 'AC_ACCIDENT_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . getEntity($object->element) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UserVictim') . ' : ' . $uservictim->firstname . $uservictim->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('UserEmployer') . ' : ' . $useremployer->firstname . $useremployer->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentLocation') . ' : ' . $accidentLocation  . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentType') . ' : ' . ($object->accident_type ? $langs->trans('CommutingAccident') : $langs->trans('WorkAccidentStatement')) . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentDate') . ' : ' . dol_print_date($object->accident_date, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENT_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskelement  = new DigiriskElement($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$society 		  = new Societe($this->db);
				$uservictim 	  = new User($this->db);
				$uservictim->fetch($object->fk_user_victim);
				$useremployer	  = new User($this->db);
				$useremployer->fetch($object->fk_user_employer);

				//1 : Accident in DU / GP, 2 : Accident in society, 3 : Accident in another location
				switch ($object->external_accident) {
					case 1:
						if (!empty($object->fk_standard)) {
							$actioncomm->elementtype = 'digiriskstandard@digiriskdolibarr';
							$digiriskstandard->fetch($object->fk_standard);
							$actioncomm->fk_element  = $object->fk_standard;
							$accidentLocation = $digiriskstandard->ref . " - " . $conf->global->MAIN_INFO_SOCIETE_NOM;
						} else if (!empty($object->fk_element)) {
							$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
							$digiriskelement->fetch($object->fk_element);
							$actioncomm->fk_element  = $object->fk_element;
							$accidentLocation = $digiriskelement->ref . " - " . $digiriskelement->label;
						}
						break;
					case 2:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$society->fetch($object->fk_soc);
						$actioncomm->fk_element  = $object->fk_soc;
						$accidentLocation = $society->ref . " - " . $society->label;
					case 3:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$actioncomm->fk_element  = $object->id;
						$accidentLocation = $object->accident_location;
						break;
				}

				$actioncomm->code        = 'AC_ACCIDENT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . getEntity($object->element) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UserVictim') . ' : ' . $uservictim->firstname . $uservictim->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('UserEmployer') . ' : ' . $useremployer->firstname . $useremployer->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentLocation') . ' : ' . $accidentLocation  . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentType') . ' : ' . ($object->accident_type ? $langs->trans('CommutingAccident') : $langs->trans('WorkAccidentStatement')) . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentDate') . ' : ' . dol_print_date($object->accident_date, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENT_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        	  = dol_now();
				$actioncomm 	  = new ActionComm($this->db);
				$digiriskelement  = new DigiriskElement($this->db);
				$digiriskstandard = new DigiriskStandard($this->db);
				$society 		  = new Societe($this->db);
				$uservictim 	  = new User($this->db);
				$uservictim->fetch($object->fk_user_victim);
				$useremployer	  = new User($this->db);
				$useremployer->fetch($object->fk_user_employer);

				//1 : Accident in DU / GP, 2 : Accident in society, 3 : Accident in another location
				switch ($object->external_accident) {
					case 1:
						if (!empty($object->fk_standard)) {
							$actioncomm->elementtype = 'digiriskstandard@digiriskdolibarr';
							$digiriskstandard->fetch($object->fk_standard);
							$actioncomm->fk_element  = $object->fk_standard;
							$accidentLocation = $digiriskstandard->ref . " - " . $conf->global->MAIN_INFO_SOCIETE_NOM;
						} else if (!empty($object->fk_element)) {
							$actioncomm->elementtype = 'digiriskelement@digiriskdolibarr';
							$digiriskelement->fetch($object->fk_element);
							$actioncomm->fk_element  = $object->fk_element;
							$accidentLocation = $digiriskelement->ref . " - " . $digiriskelement->label;
						}
						break;
					case 2:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$society->fetch($object->fk_soc);
						$actioncomm->fk_element  = $object->fk_soc;
						$accidentLocation = $society->ref . " - " . $society->label;
					case 3:
						$actioncomm->elementtype = 'accident@digiriskdolibarr';
						$actioncomm->fk_element  = $object->id;
						$accidentLocation = $object->accident_location;
						break;
				}

				$actioncomm->code        = 'AC_ACCIDENT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Label') . ' : ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . getEntity($object->element) . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->description) ? $object->description : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('UserVictim') . ' : ' . $uservictim->firstname . $uservictim->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('UserEmployer') . ' : ' . $useremployer->firstname . $useremployer->lastname . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentLocation') . ' : ' . $accidentLocation  . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentType') . ' : ' . ($object->accident_type ? $langs->trans('CommutingAccident') : $langs->trans('WorkAccidentStatement')) . '<br>';
				$actioncomm->note_private .= $langs->trans('AccidentDate') . ' : ' . dol_print_date($object->accident_date, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTWORKSTOP_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTWORKSTOP_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentWorkStopCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDays') . ' : ' . $object->workstop_days . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDocument') . ' : ' . (!empty($object->declaration_link) ? $object->declaration_link : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateStartWorkStop') . ' : ' . dol_print_date($object->date_start_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateEndWorkStop') . ' : ' . dol_print_date($object->date_end_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTWORKSTOP_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTWORKSTOP_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentWorkStopModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDays') . ' : ' . $object->workstop_days . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDocument') . ' : ' . (!empty($object->declaration_link) ? $object->declaration_link : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateStartWorkStop') . ' : ' . dol_print_date($object->date_start_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateEndWorkStop') . ' : ' . dol_print_date($object->date_end_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTWORKSTOP_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTWORKSTOP_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentWorkStopDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDays') . ' : ' . $object->workstop_days . '<br>';
				$actioncomm->note_private .= $langs->trans('WorkStopDocument') . ' : ' . (!empty($object->declaration_link) ? $object->declaration_link : 'N/A') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateStartWorkStop') . ' : ' . dol_print_date($object->date_start_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateEndWorkStop') . ' : ' . dol_print_date($object->date_end_workstop, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('Status') . ' : ' . $object->status . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTLESION_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTLESION_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentLesionCreateTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionLocalization') . ' : ' . $object->lesion_localization . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionNature') . ' : ' . $object->lesion_nature . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTLESION_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTLESION_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentLesionModifyTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionLocalization') . ' : ' . $object->lesion_localization . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionNature') . ' : ' . $object->lesion_nature . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTLESION_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTLESION_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentLesionDeleteTrigger', $object->ref);
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $object->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('Ref') . ' : ' . $object->ref . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->id . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionLocalization') . ' : ' . $object->lesion_localization . '<br>';
				$actioncomm->note_private .= $langs->trans('LesionNature') . ' : ' . $object->lesion_nature . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_creation, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'ACCIDENTMETADATA_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'accident@digiriskdolibarr';
				$actioncomm->code        = 'AC_ACCIDENTMETADATA_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('AccidentMetaDataCreateTrigger');
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_accident;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'TASK_TIMESPENT_CREATE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'task@digiriskdolibarr';
				$actioncomm->code        = 'AC_TASK_TIMESPENT_CREATE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('TaskTimeSpentCreateTrigger');
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->timespent_id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->timespent_note) ? $object->timespent_note : 'N/A') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->fk_project  = $object->fk_project;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'TASK_TIMESPENT_MODIFY' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now        = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'task@digiriskdolibarr';
				$actioncomm->code        = 'AC_TASK_TIMESPENT_MODIFY';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('TaskTimeSpentModifyTrigger');
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->timespent_id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_c, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->timespent_note) ? $object->timespent_note : 'N/A') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->fk_project  = $object->fk_project;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			case 'TASK_TIMESPENT_DELETE' :

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
				require_once __DIR__ . '/../../class/digiriskelement.class.php';
				$now             = dol_now();
				$actioncomm      = new ActionComm($this->db);

				$actioncomm->elementtype = 'task@digiriskdolibarr';
				$actioncomm->code        = 'AC_TASK_TIMESPENT_DELETE';
				$actioncomm->type_code   = 'AC_OTH_AUTO';
				$actioncomm->label       = $langs->transnoentities('TaskTimeSpentDeleteTrigger');
				$actioncomm->note_private .= $langs->trans('ParentElement') . ' : ' . $object->ref . ' - ' . $object->label . '<br>';
				$actioncomm->note_private .= $langs->trans('TechnicalID') . ' : ' . $object->timespent_id . '<br>';
				$actioncomm->note_private .= $langs->trans('Entity') . ' : ' . $conf->entity . '<br>';
				$actioncomm->note_private .= $langs->trans('DateCreation') . ' : ' . dol_print_date($object->date_c, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('DateModification') . ' : ' . dol_print_date($now, 'dayhoursec', 'tzuser') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDate') . ' : ' . dol_print_date($object->timespent_datehour, 'dayhoursec') . '<br>';
				$actioncomm->note_private .= $langs->trans('TaskTimeSpentDuration') . ' : ' .  convertSecondToTime($object->timespent_duration * 60, 'allhourmin') . '<br>';
				$actioncomm->note_private .= $langs->trans('Description') . ' : ' . (!empty($object->timespent_note) ? $object->timespent_note : 'N/A') . '<br>';
				$actioncomm->datep       = $now;
				$actioncomm->fk_element  = $object->fk_element;
				$actioncomm->userownerid = $user->id;
				$actioncomm->fk_project  = $object->fk_project;
				$actioncomm->percentage  = -1;

				$result = $actioncomm->create($user);
				if ($result < 0) {
					$object->errors = array_merge($object->error, $actioncomm->errors);
					return $result;
				}
				break;

			default:
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}


		return 0;
	}
}
