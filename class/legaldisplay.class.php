<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Eoxia <dev@eoxia.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/custom/digiriskdolibarr/class/legaldisplay.class.php
 *  \ingroup    digiriskdolibarr
 *  \brief      File for legal display class
 */

require_once(DOL_DOCUMENT_ROOT."/custom/digiriskdolibarr/class/digirisk_documents.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

/**
 *	Class to manage legal display objects
 */
//@todo change name for LegalDisplay
class Legaldisplay extends DigiriskDocuments
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)

	/**
	 *  Create a legal display into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		$now = dol_now();

		$digirisk_resources = digirisk_dolibarr_fetch_resources($this->db,"all");

		$labour_doctor_contact = new Contact($this->db);
		$result = $labour_doctor_contact->fetch($digirisk_resources['LabourDoctorContact']->element);

		if ($result < 0) dol_print_error($langs->trans('NoLabourDoctorAssigned'), $labour_doctor_contact->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['occupational_health_service']['name']    = $labour_doctor_contact->firstname . " " . $labour_doctor_contact->lastname;
			$this->json['LegalDisplay']['occupational_health_service']['address'] = preg_replace('/\s\s+/', ' ', $labour_doctor_contact->address);
			$this->json['LegalDisplay']['occupational_health_service']['zip']     = $labour_doctor_contact->zip;
			$this->json['LegalDisplay']['occupational_health_service']['town']    = $labour_doctor_contact->town;
			$this->json['LegalDisplay']['occupational_health_service']['phone']   = $labour_doctor_contact->phone_pro;
		}

		$labour_inspector_contact = new Contact($this->db);
		$result = $labour_inspector_contact->fetch($digirisk_resources['LabourInspectorContact']->element);

		if ($result < 0) dol_print_error($langs->trans('NoLabourInspectorAssigned'), $labour_inspector_contact->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['detective_work']['name']    = $labour_inspector_contact->firstname . " " . $labour_inspector_contact->lastname;
			$this->json['LegalDisplay']['detective_work']['address'] = preg_replace('/\s\s+/', ' ', $labour_inspector_contact->address);
			$this->json['LegalDisplay']['detective_work']['zip']     = $labour_inspector_contact->zip;
			$this->json['LegalDisplay']['detective_work']['town']    = $labour_inspector_contact->town;
			$this->json['LegalDisplay']['detective_work']['phone']   = $labour_inspector_contact->phone_pro;
		}

		$samu = new Societe($this->db);
		$result = $samu->fetch($digirisk_resources['SAMU']->element);

		if ($result < 0) dol_print_error($langs->trans('NoSamuAssigned'), $samu->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['samu'] = $samu->phone;
		}

		$police = new Societe($this->db);
		$result = $police->fetch($digirisk_resources['Police']->element);

		if ($result < 0) dol_print_error($langs->trans('NoPoliceAssigned'), $police->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['police'] = $police->phone;
		}

		$pompier = new Societe($this->db);
		$result = $pompier->fetch($digirisk_resources['Pompiers']->element);

		if ($result < 0) dol_print_error($langs->trans('NoPoliceAssigned'), $pompier->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['pompier'] = $pompier->phone;
		}

		$emergency = new Societe($this->db);
		$result = $emergency->fetch($digirisk_resources['AllEmergencies']->element);

		if ($result < 0) dol_print_error($langs->trans('NoAllEmergenciesAssigned'), $emergency->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['emergency'] = $emergency->phone;
		}

		$rights_defender = new Societe($this->db);
		$result = $rights_defender->fetch($digirisk_resources['RightsDefender']->element);

		if ($result < 0) dol_print_error($langs->trans('NoRightsDefenderAssigned'), $rights_defender->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['right_defender'] = $rights_defender->phone;
		}

		$antipoison = new Societe($this->db);
		$result = $antipoison->fetch($digirisk_resources['Antipoison']->element);

		if ($result < 0) dol_print_error($langs->trans('NoRightsDefenderAssigned'), $antipoison->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['emergency_service']['poison_control_center'] = $antipoison->phone;
		}

		$responsible_prevent = new User($this->db);
		$result = $responsible_prevent->fetch($digirisk_resources['Responsible']->element);

		if ($result < 0) dol_print_error($langs->trans('NoResponsibleAssigned'), $responsible_prevent->error);
		elseif ($result > 0) {
			$this->json['LegalDisplay']['safety_rule']['responsible_for_preventing'] = $responsible_prevent->firstname . " " . $responsible_prevent->lastname;
			$this->json['LegalDisplay']['safety_rule']['phone']                      = $responsible_prevent->office_phone;
		}

		$opening_hours_monday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_MONDAY);
		$opening_hours_tuesday   = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY);
		$opening_hours_wednesday = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY);
		$opening_hours_thursday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY);
		$opening_hours_friday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY);
		$opening_hours_saturday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY);
		$opening_hours_sunday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY);

		$this->json['LegalDisplay']['working_hour']['monday_morning']    = $opening_hours_monday[0];
		$this->json['LegalDisplay']['working_hour']['tuesday_morning']   = $opening_hours_tuesday[0];
		$this->json['LegalDisplay']['working_hour']['wednesday_morning'] = $opening_hours_wednesday[0];
		$this->json['LegalDisplay']['working_hour']['thursday_morning']  = $opening_hours_thursday[0];
		$this->json['LegalDisplay']['working_hour']['friday_morning']    = $opening_hours_friday[0];
		$this->json['LegalDisplay']['working_hour']['saturday_morning']  = $opening_hours_saturday[0];
		$this->json['LegalDisplay']['working_hour']['sunday_morning']    = $opening_hours_sunday[0];

		$this->json['LegalDisplay']['working_hour']['monday_afternoon']    = $opening_hours_monday[1];
		$this->json['LegalDisplay']['working_hour']['tuesday_afternoon']   = $opening_hours_tuesday[1];
		$this->json['LegalDisplay']['working_hour']['wednesday_afternoon'] = $opening_hours_wednesday[1];
		$this->json['LegalDisplay']['working_hour']['thursday_afternoon']  = $opening_hours_thursday[1];
		$this->json['LegalDisplay']['working_hour']['friday_afternoon']    = $opening_hours_friday[1];
		$this->json['LegalDisplay']['working_hour']['saturday_afternoon']  = $opening_hours_saturday[1];
		$this->json['LegalDisplay']['working_hour']['sunday_afternoon']    = $opening_hours_sunday[1];

		$this->json['LegalDisplay']['safety_rule']['location_of_detailed_instruction']                      = $conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION;
		$this->json['LegalDisplay']['derogation_schedule']['permanent']                                     = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT;
		$this->json['LegalDisplay']['derogation_schedule']['occasional']                                    = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL;
		$this->json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE;
		$this->json['LegalDisplay']['collective_agreement']['location_and_access_terms_of_the_agreement']   = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION;
		$this->json['LegalDisplay']['DUER']['how_access_to_duer']                                           = $conf->global->DIGIRISK_DUER_LOCATION;
		$this->json['LegalDisplay']['rules']['location']                                                    = $conf->global->DIGIRISK_RULES_LOCATION;
		$this->json['LegalDisplay']['participation_agreement']['information_procedures']                    = $conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

		// Create legal display.
		$fuserid = $this->fk_user_creat;
		if (empty($fuserid)) $fuserid = $user->id;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."digirisk_documents (";
		$sql .= "ref";
		$sql .= ", entity";
		$sql .= ", date_creation";
		$sql .= ", json";
		$sql .= ", import_key";
		$sql .= ", status";
		$sql .= ", fk_user_creat";
		$sql .= ", model_pdf";
		$sql .= ", model_odt";
		$sql .= ", type";
		$sql .= ") VALUES (";
		$sql .= " ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : 'null');
		$sql .= ", ".$conf->entity;
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '".json_encode($this->json, JSON_UNESCAPED_UNICODE)."'";
		$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : 'null');
		$sql .= ", ".(is_numeric($this->status) ? $this->status : '0');
		$sql .= ", ".$fuserid;
		$sql .= ", ".(!empty($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : 'null');
		$sql .= ", ".(!empty($this->model_odt) ? "'".$this->db->escape($this->model_odt)."'" : 'null');
		$sql .= ", "."'legaldisplay'";
		$sql .= ")";

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."digirisk_documents");
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}
}
?>
