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
 * \file        class/digiriskdocuments/informationssharing.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for InformationsSharing
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskdocuments.class.php';

/**
 * Class for InformationsSharing
 */
class InformationsSharing extends DigiriskDocuments
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'informationssharing';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
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
	public function InformationsSharingFillJSON()
	{
		global $conf, $langs;

		$resources 			= new DigiriskResources($this->db);
		$digirisk_resources = $resources->fetchDigiriskResources();
		$json               = array();

		// 		*** JSON FILLING ***
		if (!empty ($digirisk_resources)) {
			$labour_doctor_contact = new Contact($this->db);
			$result = $labour_doctor_contact->fetch($digirisk_resources['LabourDoctorContact']->id[0]);
			if ($result > 0) {
				$json['InformationsSharing']['occupational_health_service']['id']    = $labour_doctor_contact->id;
				$json['InformationsSharing']['occupational_health_service']['name']  = $labour_doctor_contact->firstname . " " . $labour_doctor_contact->lastname;
				$json['InformationsSharing']['occupational_health_service']['phone'] = $labour_doctor_contact->phone_pro;
			}

			$labourInspectorContact = new Contact($this->db);
			$result = $labourInspectorContact->fetch($digirisk_resources['LabourInspectorContact']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['detective_work']['id']    = $labourInspectorContact->id;
				$json['InformationsSharing']['detective_work']['name']  = $labourInspectorContact->firstname . " " . $labourInspectorContact->lastname;
				$json['InformationsSharing']['detective_work']['phone'] = $labourInspectorContact->phone_pro;
			}

			$harassment_officer = new User($this->db);
			$result = $harassment_officer->fetch($digirisk_resources['HarassmentOfficer']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['harassment_officer']['id']    = $harassment_officer->id;

				$json['InformationsSharing']['harassment_officer']['name']  = $harassment_officer->getFullName($langs);
				$json['InformationsSharing']['harassment_officer']['phone'] = $harassment_officer->office_phone;
			}

			$activeStaffBodies = $resources->getActiveStaffRepresentativeBodies();

			// The ESC harassment officer goes with the ESC
			$harassment_officer_cse = new User($this->db);
			$result = isset($activeStaffBodies['CSE']) ? $harassment_officer_cse->fetch($digirisk_resources['HarassmentOfficerCSE']->id[0] ?? 0) : 0;

			if ($result > 0) {
				$json['InformationsSharing']['harassment_officer_cse']['id']    = $harassment_officer_cse->id;
				$json['InformationsSharing']['harassment_officer_cse']['name']  = $harassment_officer_cse->getFullName($langs);
				$json['InformationsSharing']['harassment_officer_cse']['phone'] = $harassment_officer_cse->office_phone;
			}

			// Staff representative bodies chosen in the social setup, in display order: their names are translated
			// when they are shown, in the language of the page or of the document
			$json['InformationsSharing']['staff_bodies'] = [];
			foreach (array_keys($activeStaffBodies) as $staffBodyCode) {
				$electionDate = getDolGlobalString('DIGIRISKDOLIBARR_' . $staffBodyCode . '_ELECTION_DATE');
				$staffBody    = [
					'code'          => $staffBodyCode,
					'election_date' => $electionDate != '--' ? $electionDate : '',
				];
				foreach (['titulars' => 'Titulars', 'alternates' => 'Alternates'] as $memberKey => $memberType) {
					$staffBody[$memberKey]              = '';
					$staffBody[$memberKey . 'FullName'] = '';
					foreach ($digirisk_resources[$memberType . $staffBodyCode]->id ?? [] as $memberId) {
						$member = new User($this->db);
						if ($member->fetch($memberId) > 0) {
							$staffBody[$memberKey]              .= $member->firstname . ' ' . $member->lastname . '<br>';
							$staffBody[$memberKey . 'FullName'] .= $member->getNomUrl(1) . '<br>';
						}
					}
				}
				$json['InformationsSharing']['staff_bodies'][$staffBodyCode] = $staffBody;
			}

			// Tags of the ESC and of the staff delegates from before the choice of the bodies, still read by the custom templates
			foreach (['CSE' => 'membres_du_comite_entreprise', 'DP' => 'delegues_du_personnels'] as $staffBodyCode => $legacyKey) {
				$staffBody = $json['InformationsSharing']['staff_bodies'][$staffBodyCode] ?? [];

				$json['InformationsSharing'][$legacyKey . '_date']               = $staffBody['election_date'] ?? '';
				$json['InformationsSharing'][$legacyKey . '_titulaires']         = $staffBody['titulars'] ?? '';
				$json['InformationsSharing'][$legacyKey . '_titulairesFullName'] = $staffBody['titularsFullName'] ?? '';
				$json['InformationsSharing'][$legacyKey . '_suppleants']         = $staffBody['alternates'] ?? '';
				$json['InformationsSharing'][$legacyKey . '_suppleantsFullName'] = $staffBody['alternatesFullName'] ?? '';
			}

			$json = json_encode($json, JSON_UNESCAPED_UNICODE);
			return $json;
		}
		else {
			return -1;
		}
	}
}
