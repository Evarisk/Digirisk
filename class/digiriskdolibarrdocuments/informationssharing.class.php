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

			$harassment_officer_cse = new User($this->db);
			$result = $harassment_officer_cse->fetch($digirisk_resources['HarassmentOfficerCSE']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['harassment_officer_cse']['id']    = $harassment_officer_cse->id;
				$json['InformationsSharing']['harassment_officer_cse']['name']  = $harassment_officer_cse->getFullName($langs);
				$json['InformationsSharing']['harassment_officer_cse']['phone'] = $harassment_officer_cse->office_phone;
			}

			$json['InformationsSharing']['delegues_du_personnels_date']    	  = (dol_strlen($conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE) > 0 && $conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE != '--' ? $conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE : '');
			$json['InformationsSharing']['delegues_du_personnels_titulaires'] = '';
            $json['InformationsSharing']['delegues_du_personnels_titulairesFullName'] = '';
			if (!empty ($digirisk_resources['TitularsDP']->id )) {
				foreach ($digirisk_resources['TitularsDP']->id as $dp_titular) {

					$dp_titulars = new User($this->db);
					$result = $dp_titulars->fetch($dp_titular);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_titulaires']  .= $dp_titulars->firstname . " " . $dp_titulars->lastname . '<br>';
						$json['InformationsSharing']['delegues_du_personnels_titulairesFullName']  .= $dp_titulars->getNomUrl(1) . '<br>';
					}
				}
			}

			$json['InformationsSharing']['delegues_du_personnels_suppleants'] = '';
            $json['InformationsSharing']['delegues_du_personnels_suppleantsFullName'] = '';
			if (!empty ($digirisk_resources['AlternatesDP']->id )) {
				foreach ($digirisk_resources['AlternatesDP']->id as $dp_alternate) {

					$dp_alternates = new User($this->db);
					$result = $dp_alternates->fetch($dp_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_suppleants']  .= $dp_alternates->firstname . " " . $dp_alternates->lastname . '<br>';
						$json['InformationsSharing']['delegues_du_personnels_suppleantsFullName']  .= $dp_alternates->getNomUrl(1) . '<br>';
					}
				}
			}

			// CSE
			$json['InformationsSharing']['membres_du_comite_entreprise_date']       = (dol_strlen($conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE) > 0 && $conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE != '--' ? $conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE : '');
			$json['InformationsSharing']['membres_du_comite_entreprise_titulaires'] = '';
            $json['InformationsSharing']['membres_du_comite_entreprise_titulairesFullName'] = '';
			if (!empty ($digirisk_resources['TitularsCSE']->id )) {
				foreach ($digirisk_resources['TitularsCSE']->id as $cse_titular) {
					$cse_titulars = new User($this->db);
					$result = $cse_titulars->fetch($cse_titular);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_titulaires']  .= $cse_titulars->firstname . " " . $cse_titulars->lastname  . '<br>';
						$json['InformationsSharing']['membres_du_comite_entreprise_titulairesFullName']  .= $cse_titulars->getNomUrl(1) . '<br>';
					}
				}
			}

			$json['InformationsSharing']['membres_du_comite_entreprise_suppleants'] = '';
            $json['InformationsSharing']['membres_du_comite_entreprise_suppleantsFullName'] = '';
			if (!empty ($digirisk_resources['AlternatesCSE']->id )) {
				foreach ($digirisk_resources['AlternatesCSE']->id as $cse_alternate) {
					$cse_alternates = new User($this->db);
					$result = $cse_alternates->fetch($cse_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_suppleants']  .= $cse_alternates->firstname . " " . $cse_alternates->lastname  . '<br>';
						$json['InformationsSharing']['membres_du_comite_entreprise_suppleantsFullName']  .= $cse_alternates->getNomUrl(1) . '<br>';
					}
				}
			}

			$json = json_encode($json, JSON_UNESCAPED_UNICODE);
			return $json;
		}
		else {
			return -1;
		}
	}
}
