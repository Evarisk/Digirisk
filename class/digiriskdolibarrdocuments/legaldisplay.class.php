<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file        class/digiriskdocuments/legaldisplay.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for LegalDisplay
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskdocuments.class.php';

/**
 * Class for LegalDisplay
 */
class LegalDisplay extends DigiriskDocuments
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'legaldisplay';

	/**
	 * Constructor.
	 *
	 * @param DoliDb $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->element);
	}

	/**
	 * Function for JSON filling before saving in database
	 *
	 * @return false|string
	 * @throws Exception
	 */
	public function LegalDisplayFillJSON() {
		global $conf, $langs;

		$resources               = new DigiriskResources($this->db);
		$digirisk_resources      = $resources->fetchDigiriskResources();
		$json                    = array();

		// *** JSON FILLING ***
		if (!empty ($digirisk_resources )) {
			$labour_doctor_societe = new Societe($this->db);
			$result = $labour_doctor_societe->fetch($digirisk_resources['LabourDoctorSociety']->id[0]);
			if ($result > 0) {
				$morewhere = ' AND element_id = ' . $labour_doctor_societe->id;
				$morewhere .= ' AND element_type = ' . "'" . $labour_doctor_societe->element . "'";
				$morewhere .= ' AND status = 1';

				$thirdparty_openinghours = new SaturneSchedules($this->db);
				$thirdparty_openinghours->fetch(0, '', $morewhere);

                $labourHoursMonday    = explode(' ', $thirdparty_openinghours->monday);
                $labourHoursTuesday   = explode(' ', $thirdparty_openinghours->tuesday);
                $labourHoursWednesday = explode(' ', $thirdparty_openinghours->wednesday);
                $labourHoursThursday  = explode(' ', $thirdparty_openinghours->thursday);
                $labourHoursFriday    = explode(' ', $thirdparty_openinghours->friday);
                $labourHoursSaturday  = explode(' ', $thirdparty_openinghours->saturday);
                $labourHoursSunday    = explode(' ', $thirdparty_openinghours->sunday);

                $json['LegalDisplay']['occupational_health_service']['monday_morning']    = $labourHoursMonday[0];
                $json['LegalDisplay']['occupational_health_service']['tuesday_morning']   = $labourHoursTuesday[0];
                $json['LegalDisplay']['occupational_health_service']['wednesday_morning'] = $labourHoursWednesday[0];
                $json['LegalDisplay']['occupational_health_service']['thursday_morning']  = $labourHoursThursday[0];
                $json['LegalDisplay']['occupational_health_service']['friday_morning']    = $labourHoursFriday[0];
                $json['LegalDisplay']['occupational_health_service']['saturday_morning']  = $labourHoursSaturday[0];
                $json['LegalDisplay']['occupational_health_service']['sunday_morning']    = $labourHoursSunday[0];

                $json['LegalDisplay']['occupational_health_service']['monday_afternoon']    = $labourHoursMonday[1];
                $json['LegalDisplay']['occupational_health_service']['tuesday_afternoon']   = $labourHoursTuesday[1];
                $json['LegalDisplay']['occupational_health_service']['wednesday_afternoon'] = $labourHoursWednesday[1];
                $json['LegalDisplay']['occupational_health_service']['thursday_afternoon']  = $labourHoursThursday[1];
                $json['LegalDisplay']['occupational_health_service']['friday_afternoon']    = $labourHoursFriday[1];
                $json['LegalDisplay']['occupational_health_service']['saturday_afternoon']  = $labourHoursSaturday[1];
                $json['LegalDisplay']['occupational_health_service']['sunday_afternoon']    = $labourHoursSunday[1];
			}

			$labour_doctor_contact = new Contact($this->db);
			$result = $labour_doctor_contact->fetch($digirisk_resources['LabourDoctorContact']->id[0]);
			if ($result > 0) {
				$json['LegalDisplay']['occupational_health_service']['id']      = $labour_doctor_contact->id;
				$json['LegalDisplay']['occupational_health_service']['name']    = $labour_doctor_contact->firstname . " " . $labour_doctor_contact->lastname;
				$json['LegalDisplay']['occupational_health_service']['address'] = preg_replace('/\s\s+/', ' ', $labour_doctor_contact->address);
				$json['LegalDisplay']['occupational_health_service']['zip']     = $labour_doctor_contact->zip;
				$json['LegalDisplay']['occupational_health_service']['town']    = $labour_doctor_contact->town;
				$json['LegalDisplay']['occupational_health_service']['phone']   = $labour_doctor_contact->phone_pro;
			}

			$labour_inspector_societe = new Societe($this->db);
			$result = $labour_inspector_societe->fetch($digirisk_resources['LabourInspectorSociety']->id[0]);

			if ($result > 0) {
				$morewhere = ' AND element_id = ' . $labour_inspector_societe->id;
				$morewhere .= ' AND element_type = ' . "'" . $labour_inspector_societe->element . "'";
				$morewhere .= ' AND status = 1';
				$thirdparty_openinghours = new SaturneSchedules($this->db);
				$thirdparty_openinghours->fetch(0, '', $morewhere);

                $detectiveHoursMonday    = explode(' ', $thirdparty_openinghours->monday);
                $detectiveHoursTuesday   = explode(' ', $thirdparty_openinghours->tuesday);
                $detectiveHoursWednesday = explode(' ', $thirdparty_openinghours->wednesday);
                $detectiveHoursThursday  = explode(' ', $thirdparty_openinghours->thursday);
                $detectiveHoursFriday    = explode(' ', $thirdparty_openinghours->friday);
                $detectiveHoursSaturday  = explode(' ', $thirdparty_openinghours->saturday);
                $detectiveHoursSunday    = explode(' ', $thirdparty_openinghours->sunday);

                $json['LegalDisplay']['detective_work']['monday_morning']    = $detectiveHoursMonday[0];
                $json['LegalDisplay']['detective_work']['tuesday_morning']   = $detectiveHoursTuesday[0];
                $json['LegalDisplay']['detective_work']['wednesday_morning'] = $detectiveHoursWednesday[0];
                $json['LegalDisplay']['detective_work']['thursday_morning']  = $detectiveHoursThursday[0];
                $json['LegalDisplay']['detective_work']['friday_morning']    = $detectiveHoursFriday[0];
                $json['LegalDisplay']['detective_work']['saturday_morning']  = $detectiveHoursSaturday[0];
                $json['LegalDisplay']['detective_work']['sunday_morning']    = $detectiveHoursSunday[0];

                $json['LegalDisplay']['detective_work']['monday_afternoon']    = $detectiveHoursMonday[1];
                $json['LegalDisplay']['detective_work']['tuesday_afternoon']   = $detectiveHoursTuesday[1];
                $json['LegalDisplay']['detective_work']['wednesday_afternoon'] = $detectiveHoursWednesday[1];
                $json['LegalDisplay']['detective_work']['thursday_afternoon']  = $detectiveHoursThursday[1];
                $json['LegalDisplay']['detective_work']['friday_afternoon']    = $detectiveHoursFriday[1];
                $json['LegalDisplay']['detective_work']['saturday_afternoon']  = $detectiveHoursSaturday[1];
                $json['LegalDisplay']['detective_work']['sunday_afternoon']    = $detectiveHoursSunday[1];
			}

			$labourInspectorContact = new Contact($this->db);
			$result                 = $labourInspectorContact->fetch($digirisk_resources['LabourInspectorContact']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['detective_work']['id']      = $labourInspectorContact->id;
				$json['LegalDisplay']['detective_work']['name']    = $labourInspectorContact->firstname . " " . $labourInspectorContact->lastname;
				$json['LegalDisplay']['detective_work']['address'] = preg_replace('/\s\s+/', ' ', $labourInspectorContact->address);
				$json['LegalDisplay']['detective_work']['zip']     = $labourInspectorContact->zip;
				$json['LegalDisplay']['detective_work']['town']    = $labourInspectorContact->town;
				$json['LegalDisplay']['detective_work']['phone']   = $labourInspectorContact->phone_pro;
			}

			$samu   = new Societe($this->db);
			$result = $samu->fetch($digirisk_resources['SAMU']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['samu'] = $samu->phone;
			}

			$police = new Societe($this->db);
			$result = $police->fetch($digirisk_resources['Police']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['police'] = $police->phone;
			}

			$pompier = new Societe($this->db);
			$result  = $pompier->fetch($digirisk_resources['Pompiers']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['pompier'] = $pompier->phone;
			}

			$emergency = new Societe($this->db);
			$result    = $emergency->fetch($digirisk_resources['AllEmergencies']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['emergency'] = $emergency->phone;
			}

			$rights_defender = new Societe($this->db);
			$result          = $rights_defender->fetch($digirisk_resources['RightsDefender']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['right_defender'] = $rights_defender->phone;
			}

			$poison_control_center = new Societe($this->db);
			$result                = $poison_control_center->fetch($digirisk_resources['PoisonControlCenter']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['poison_control_center'] = $poison_control_center->phone;
			}

			$responsible_prevent = new User($this->db);
			$result = $responsible_prevent->fetch($digirisk_resources['Responsible']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['safety_rule']['id']                         = $responsible_prevent->id;
				$json['LegalDisplay']['safety_rule']['responsible_for_preventing'] = $responsible_prevent->firstname . " " . $responsible_prevent->lastname;
				$json['LegalDisplay']['safety_rule']['phone']                      = $responsible_prevent->office_phone;
			}

			$opening_hours_monday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_MONDAY);
			$opening_hours_tuesday   = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY);
			$opening_hours_wednesday = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY);
			$opening_hours_thursday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY);
			$opening_hours_friday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY);
			$opening_hours_saturday  = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY);
			$opening_hours_sunday    = explode(' ', $conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY);

			$json['LegalDisplay']['working_hour']['monday_morning']    = $opening_hours_monday[0];
			$json['LegalDisplay']['working_hour']['tuesday_morning']   = $opening_hours_tuesday[0];
			$json['LegalDisplay']['working_hour']['wednesday_morning'] = $opening_hours_wednesday[0];
			$json['LegalDisplay']['working_hour']['thursday_morning']  = $opening_hours_thursday[0];
			$json['LegalDisplay']['working_hour']['friday_morning']    = $opening_hours_friday[0];
			$json['LegalDisplay']['working_hour']['saturday_morning']  = $opening_hours_saturday[0];
			$json['LegalDisplay']['working_hour']['sunday_morning']    = $opening_hours_sunday[0];

			$json['LegalDisplay']['working_hour']['monday_afternoon']    = $opening_hours_monday[1];
			$json['LegalDisplay']['working_hour']['tuesday_afternoon']   = $opening_hours_tuesday[1];
			$json['LegalDisplay']['working_hour']['wednesday_afternoon'] = $opening_hours_wednesday[1];
			$json['LegalDisplay']['working_hour']['thursday_afternoon']  = $opening_hours_thursday[1];
			$json['LegalDisplay']['working_hour']['friday_afternoon']    = $opening_hours_friday[1];
			$json['LegalDisplay']['working_hour']['saturday_afternoon']  = $opening_hours_saturday[1];
			$json['LegalDisplay']['working_hour']['sunday_afternoon']    = $opening_hours_sunday[1];

			$json['LegalDisplay']['safety_rule']['location_of_detailed_instruction']                      = $conf->global->DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION;
			$json['LegalDisplay']['derogation_schedule']['permanent']                                     = $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT;
			$json['LegalDisplay']['derogation_schedule']['occasional']                                    = $conf->global->DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL;
			$json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE;
            $idcc = $this->getIDCCByCode($conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE);
            if (!empty($idcc)) {
                $json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] .= ' - ' . $idcc->libelle;
            }
			$json['LegalDisplay']['collective_agreement']['location_and_access_terms_of_the_agreement']   = $conf->global->DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION;
			$json['LegalDisplay']['DUER']['how_access_to_duer']                                           = $conf->global->DIGIRISKDOLIBARR_DUER_LOCATION;
			$json['LegalDisplay']['rules']['location']                                                    = $conf->global->DIGIRISKDOLIBARR_RULES_LOCATION;
			$json['LegalDisplay']['participation_agreement']['information_procedures']                    = $conf->global->DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

			$jsonFormatted = json_encode($json, JSON_UNESCAPED_UNICODE);

			return $jsonFormatted;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Get Collective Convention label with code
	 *
	 * @param $code
	 * @return Object|string
	 */
	public function getIDCCByCode($code) {
		if (isset($code) && $code !== '') {
			$sql = "SELECT rowid, libelle";
			$sql .= " FROM ".MAIN_DB_PREFIX.'c_conventions_collectives';
			$sql .= " WHERE code = " . $code ;

			$result = $this->db->query($sql);
			$obj = '';
			if ($result)
			{
				$obj = $this->db->fetch_object($result);
			}
			else {
				dol_print_error($this->db);
			}

			return $obj;
		} else {
			return '';
		}
	}
}
