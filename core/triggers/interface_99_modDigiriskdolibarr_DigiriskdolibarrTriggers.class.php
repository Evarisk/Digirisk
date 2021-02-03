<?php
/* Copyright (C) 2021 SuperAdmin
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
 * \file    core/triggers/interface_99_modDigiriskdolibarr_DigiriskdolibarrTriggers.class.php
 * \ingroup digiriskdolibarr
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modDigiriskdolibarr_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
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
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
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

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		$date = dol_now();

		switch ($action) {
			case 'INFORMATIONSSHARING_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'informationssharing@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_INFORMATIONSSHARING_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('InformationsSharingGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			case 'LEGALDISPLAY_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'legaldisplay@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_LEGALDISPLAY_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('LegalDisplayGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			case 'FIREPERMIT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'firepermit@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_FIREPERMIT_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('FirePermitGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			case 'PREVENTIONPLAN_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'preventionplan@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_PREVENTIONPLAN_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('PreventionPlanGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			case 'GROUPMENT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'groupment@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_GROUPMENT_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('GroupmentGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			case 'WORKUNIT_GENERATE' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$now = dol_now();
				$actioncomm = new ActionComm($this->db);

				$actioncomm->elementtype = 'workunit@digiriskdolibarr';
				$actioncomm->elementid   = $object->id;
				$actioncomm->code 		 = 'AC_WORKUNIT_GENERATE';
				$actioncomm->type_code 	 = 'AC_OTH_AUTO';
				$actioncomm->label		 = $langs->trans('WorkUnitGeneratedWithDolibarr');
				$actioncomm->datep		 = $now;
				$actioncomm->fk_element  = $object->id;
				$actioncomm->userownerid = $user->id;
				$actioncomm->percentage = -1;

				$ret = $actioncomm->create($user);
				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
