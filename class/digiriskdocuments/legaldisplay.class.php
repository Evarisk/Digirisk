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
 * \file        class/legaldisplay.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for LegalDisplay
 */

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/../../class/digiriskdocuments.class.php';
require_once __DIR__ . '/../../class/digiriskresources.class.php';
require_once __DIR__ . '/../../class/openinghours.class.php';

/**
 * Class for LegalDisplay
 */
class LegalDisplay extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'legaldisplay';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for legaldisplay. Must be the part after the 'object_' into object_legaldisplay.png
	 */
	public $picto = 'legaldisplay@digiriskdolibarr';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Function for JSON filling before saving in database
	 *
	 * @param $object
	 * @return false|string
	 */
	public function LegalDisplayFillJSON($object) {
		global $conf;

		$resources               = new DigiriskResources($this->db);
		$digirisk_resources      = $resources->digirisk_dolibarr_fetch_resources();
		$thirdparty_openinghours = new Openinghours($this->db);

		// *** JSON FILLING ***
		if (!empty ($digirisk_resources )) {
			$labour_doctor_societe = new Societe($this->db);
			$result = $labour_doctor_societe->fetch($digirisk_resources['LabourDoctorSociety']->id[0]);
			if ($result > 0) {
				$morewhere = ' AND element_id = ' . $labour_doctor_societe->id;
				$morewhere .= ' AND element_type = ' . "'" . $labour_doctor_societe->element . "'";
				$morewhere .= ' AND status = 1';

				$thirdparty_openinghours->fetch(0, '', $morewhere);
				$json['LegalDisplay']['occupational_health_service']['openinghours'] = "\r\n" . $thirdparty_openinghours->monday . "\r\n" . $thirdparty_openinghours->tuesday . "\r\n" . $thirdparty_openinghours->wednesday . "\r\n" . $thirdparty_openinghours->thursday . "\r\n" . $thirdparty_openinghours->friday . "\r\n" . $thirdparty_openinghours->saturday . "\r\n" . $thirdparty_openinghours->sunday;
			}

			$labour_doctor_contact = new Contact($this->db);
			$result = $labour_doctor_contact->fetch($digirisk_resources['LabourDoctorContact']->id[0]);
			if ($result > 0) {
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

				$thirdparty_openinghours->fetch(0, '', $morewhere);
				$json['LegalDisplay']['detective_work']['openinghours'] = "\r\n" . $thirdparty_openinghours->monday . "\r\n" . $thirdparty_openinghours->tuesday . "\r\n" . $thirdparty_openinghours->wednesday . "\r\n" . $thirdparty_openinghours->thursday . "\r\n" . $thirdparty_openinghours->friday . "\r\n" . $thirdparty_openinghours->saturday . "\r\n" . $thirdparty_openinghours->sunday;
			}

			$labour_inspector_contact = new Contact($this->db);
			$result = $labour_inspector_contact->fetch($digirisk_resources['LabourInspectorContact']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['detective_work']['name']    = $labour_inspector_contact->firstname . " " . $labour_inspector_contact->lastname;
				$json['LegalDisplay']['detective_work']['address'] = preg_replace('/\s\s+/', ' ', $labour_inspector_contact->address);
				$json['LegalDisplay']['detective_work']['zip']     = $labour_inspector_contact->zip;
				$json['LegalDisplay']['detective_work']['town']    = $labour_inspector_contact->town;
				$json['LegalDisplay']['detective_work']['phone']   = $labour_inspector_contact->phone_pro;
			}

			$samu = new Societe($this->db);
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
			$result = $pompier->fetch($digirisk_resources['Pompiers']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['pompier'] = $pompier->phone;
			}

			$emergency = new Societe($this->db);
			$result = $emergency->fetch($digirisk_resources['AllEmergencies']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['emergency'] = $emergency->phone;
			}

			$rights_defender = new Societe($this->db);
			$result = $rights_defender->fetch($digirisk_resources['RightsDefender']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['right_defender'] = $rights_defender->phone;
			}

			$poison_control_center = new Societe($this->db);
			$result = $poison_control_center->fetch($digirisk_resources['PoisonControlCenter']->id[0]);

			if ($result > 0) {
				$json['LegalDisplay']['emergency_service']['poison_control_center'] = $poison_control_center->phone;
			}

			$responsible_prevent = new User($this->db);
			$result = $responsible_prevent->fetch($digirisk_resources['Responsible']->id[0]);

			if ($result > 0) {
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

			$json['LegalDisplay']['safety_rule']['location_of_detailed_instruction']                      = $conf->global->DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION;
			$json['LegalDisplay']['derogation_schedule']['permanent']                                     = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_PERMANENT;
			$json['LegalDisplay']['derogation_schedule']['occasional']                                    = $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL;
			$json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE;
			$json['LegalDisplay']['collective_agreement']['title_of_the_applicable_collective_agreement'] = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE . ' - ' . $this->getIDCCByCode($conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_TITLE)->libelle;
			$json['LegalDisplay']['collective_agreement']['location_and_access_terms_of_the_agreement']   = $conf->global->DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION;
			$json['LegalDisplay']['DUER']['how_access_to_duer']                                           = $conf->global->DIGIRISK_DUER_LOCATION;
			$json['LegalDisplay']['rules']['location']                                                    = $conf->global->DIGIRISK_RULES_LOCATION;
			$json['LegalDisplay']['participation_agreement']['information_procedures']                    = $conf->global->DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE;

			$object->json = json_encode($json, JSON_UNESCAPED_UNICODE);

			return $object->json;
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
