<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
require_once __DIR__ . '/../digiriskresources.class.php';

/**
 * Class for InformationsSharing
 */
class InformationsSharing extends DigiriskDocuments
{
	/**
	 * @var string Module name
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object
	 */
	public $element = 'informationssharing';

    /**
     * @var string String with name of icon for informations sharing. Must be the part after the 'object_' into object_informations sharing.png
     */
    public $picto = 'fa-comment-dots_fas_#d35968';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
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
				$json['InformationsSharing']['occupational_health_service']['id']       = $labour_doctor_contact->id;
				$json['InformationsSharing']['occupational_health_service']['name']     = $labour_doctor_contact->firstname . " " . $labour_doctor_contact->lastname;
				$json['InformationsSharing']['occupational_health_service']['phone']    = $labour_doctor_contact->phone_pro;
                $json['InformationsSharing']['occupational_health_service']['fullname'] = $labour_doctor_contact->getNomUrl(1);
			}

			$labourInspectorContact = new Contact($this->db);
			$result = $labourInspectorContact->fetch($digirisk_resources['LabourInspectorContact']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['detective_work']['id']       = $labourInspectorContact->id;
				$json['InformationsSharing']['detective_work']['name']     = $labourInspectorContact->firstname . " " . $labourInspectorContact->lastname;
				$json['InformationsSharing']['detective_work']['phone']    = $labourInspectorContact->phone_pro;
                $json['InformationsSharing']['detective_work']['fullname'] = $labourInspectorContact->getNomUrl(1);
			}

			$harassment_officer = new User($this->db);
			$result = $harassment_officer->fetch($digirisk_resources['HarassmentOfficer']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['harassment_officer']['id']       = $harassment_officer->id;
				$json['InformationsSharing']['harassment_officer']['name']     = $harassment_officer->getFullName($langs);
				$json['InformationsSharing']['harassment_officer']['phone']    = $harassment_officer->office_phone;
                $json['InformationsSharing']['harassment_officer']['fullname'] = $harassment_officer->getNomUrl(0);
			}

			$harassment_officer_cse = new User($this->db);
			$result = $harassment_officer_cse->fetch($digirisk_resources['HarassmentOfficerCSE']->id[0]);

			if ($result > 0) {
				$json['InformationsSharing']['harassment_officer_cse']['id']       = $harassment_officer_cse->id;
				$json['InformationsSharing']['harassment_officer_cse']['name']     = $harassment_officer_cse->getFullName($langs);
				$json['InformationsSharing']['harassment_officer_cse']['phone']    = $harassment_officer_cse->office_phone;
                $json['InformationsSharing']['harassment_officer_cse']['fullname'] = $harassment_officer->getNomUrl(0);
            }

			$json['InformationsSharing']['delegues_du_personnels_date']    	  = (dol_strlen($conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE) > 0 && $conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE != '--' ? $conf->global->DIGIRISKDOLIBARR_DP_ELECTION_DATE : '');
			$json['InformationsSharing']['delegues_du_personnels_titulaires'] = '';
			if (!empty ($digirisk_resources['TitularsDP']->id )) {
				foreach ($digirisk_resources['TitularsDP']->id as $dp_titular) {

					$dp_titulars = new User($this->db);
					$result = $dp_titulars->fetch($dp_titular);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_titulaires']  .= $dp_titulars->firstname . " " . $dp_titulars->lastname;
						$json['InformationsSharing']['delegues_du_personnels_titulairesFullName']  .= $dp_titulars->getNomUrl(1);
					}
				}
			}

			$json['InformationsSharing']['delegues_du_personnels_suppleants'] = '';
			if (!empty ($digirisk_resources['AlternatesDP']->id )) {
				foreach ($digirisk_resources['AlternatesDP']->id as $dp_alternate) {

					$dp_alternates = new User($this->db);
					$result = $dp_alternates->fetch($dp_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_suppleants']  .= $dp_alternates->firstname . " " . $dp_alternates->lastname;
						$json['InformationsSharing']['delegues_du_personnels_suppleantsFullName']  .= $dp_alternates->getNomUrl(1);
					}
				}
			}

			// CSE
			$json['InformationsSharing']['membres_du_comite_entreprise_date']       = (dol_strlen($conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE) > 0 && $conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE != '--' ? $conf->global->DIGIRISKDOLIBARR_CSE_ELECTION_DATE : '');
			$json['InformationsSharing']['membres_du_comite_entreprise_titulaires'] = '';
			if (!empty ($digirisk_resources['TitularsCSE']->id )) {
				foreach ($digirisk_resources['TitularsCSE']->id as $cse_titular) {
					$cse_titulars = new User($this->db);
					$result = $cse_titulars->fetch($cse_titular);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_titulaires']  .= $cse_titulars->firstname . " " . $cse_titulars->lastname;
						$json['InformationsSharing']['membres_du_comite_entreprise_titulairesFullName']  .= $cse_titulars->getNomUrl(1);
					}
				}
			}

			$json['InformationsSharing']['membres_du_comite_entreprise_suppleants'] = '';
			if (!empty ($digirisk_resources['AlternatesCSE']->id )) {
				foreach ($digirisk_resources['AlternatesCSE']->id as $cse_alternate) {
					$cse_alternates = new User($this->db);
					$result = $cse_alternates->fetch($cse_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_suppleants']  .= $cse_alternates->firstname . " " . $cse_alternates->lastname;
						$json['InformationsSharing']['membres_du_comite_entreprise_suppleantsFullName']  .= $cse_alternates->getNomUrl(1);
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

    /**
     * Load dashboard info
     *
     * @return array     $dashboardData Return all dashboardData after load info
     * @throws Exception
     */
    public function load_dashboard(): array {
        global $langs;

        $informationsSharing         = json_decode($this->InformationsSharingFillJSON(), false, 512, JSON_UNESCAPED_UNICODE)->InformationsSharing;
        $getInformationsSharingInfos = $this->getInformationsSharingInfos();

        $dashboardData['graphs']  = [$getInformationsSharingInfos];
        $dashboardData['widgets'] = [
            'information_sharing_widget' => [
                'label' => [
                    $langs->transnoentities('LabourDoctor') ?? '',
                    $langs->transnoentities('LabourInspector') ?? '',
                    $langs->transnoentities('HarassmentOfficer') ?? '',
                    $langs->transnoentities('StaffRepresentatives') ?? '',
                    $langs->transnoentities('ESC') ?? '',
                ],
                'content' => [
                    0 => '<a href="' . dol_buildpath('societe/card.php?id=' . $legalDisplay->occupational_health_service->id . '&save_lastsearch_values=1', 1) .'" target="_blank">' . $langs->transnoentities('CongigureDoctorData') . ' <i class="fas fa-external-link-alt"></i></a>',
                    1 => '<a href="' . dol_buildpath('societe/card.php?id=' . $legalDisplay->detective_work->id . '&save_lastsearch_values=1', 1) .'" target="_blank">' . $langs->transnoentities('CongigureLabourInspectorData') . ' <i class="fas fa-external-link-alt"></i></a>',
                    2 => '<a href="' . dol_buildpath('/custom/digiriskdolibarr/admin/socialconf.php', 1) .'" target="_blank">' . $langs->transnoentities('SocialConfiguration') . ' <i class="fas fa-external-link-alt"></i></a>',
                    3 => '<a href="' . dol_buildpath('/custom/digiriskdolibarr/admin/socialconf.php', 1) .'" target="_blank">' . $langs->transnoentities('SocialConfiguration') . ' <i class="fas fa-external-link-alt"></i></a>',
                    4 => '<a href="' . dol_buildpath('/custom/digiriskdolibarr/admin/socialconf.php', 1) .'" target="_blank">' . $langs->transnoentities('SocialConfiguration') . ' <i class="fas fa-external-link-alt"></i></a>',
                ],
                'picto'       => 'fas fa-link',
                'widgetName'  => $langs->transnoentities('Society')
            ]
        ];
        return $dashboardData;
    }

    /**
     * get information sharing info
     *
     * @return array     $array Return all graph data for dashboardData after load info
     * @throws Exception
     */
    public function getInformationsSharingInfos(): array {
        global $langs;

        $informationsSharing = json_decode($this->InformationsSharingFillJSON(), false, 512, JSON_UNESCAPED_UNICODE)->InformationsSharing;

        $labourDoctor = [$informationsSharing->occupational_health_service->fullname, $informationsSharing->occupational_health_service->phone];
        $detectiveWork = [$informationsSharing->detective_work->fullname, $informationsSharing->detective_work->phone];
        $harassmentOfficer = [$informationsSharing->harassment_officer->fullname, $informationsSharing->harassment_officer_cse->fullname, $informationsSharing->harassment_officer->phone, $informationsSharing->harassment_officer_cse->phone];
        $deleguePersonnel = [$informationsSharing->delegues_du_personnels_titulairesFullName, $informationsSharing->delegues_du_personnels_suppleantsFullName];
        $membreComitee = [$informationsSharing->membres_du_comite_entreprise_titulairesFullName, $informationsSharing->membres_du_comite_entreprise_suppleantsFullName, $informationsSharing->membres_du_comite_entreprise_date];

        function isGreaterThanZero($value) {
            return dol_strlen($value) > 0;
        }

        // Define a function to count the number of non-empty values in an array
        function countNonEmptyValues($array) {
            return count(array_filter($array, 'isGreaterThanZero'));
        }

        $labourDoctorValue      = countNonEmptyValues($labourDoctor);
        $detectiveWorkValue     = countNonEmptyValues($detectiveWork);
        $harassmentOfficerValue = countNonEmptyValues($harassmentOfficer);
        $deleguePersonnelValue  = countNonEmptyValues($deleguePersonnel);
        $membreComiteeValue     = countNonEmptyValues($membreComitee);

        $array['title'] = $langs->transnoentities('ConfigureInformationsSharing');
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 300;
        $array['type']       = 'bars';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['labels']     = [
            0 => [
                'label' => $langs->transnoentities('Values'),
                'color' => '#FF0059'
            ],
            1 => [
                'label' => $langs->transnoentities('Total'),
                'color' => '#00CF68'
            ],
        ];
        $dataArray = [
            [$langs->transnoentities('LabourDoctor'), $labourDoctorValue, count($labourDoctor)],
            [$langs->transnoentities('LabourInspector'), $detectiveWorkValue, count($detectiveWork)],
            [$langs->transnoentities('HarassmentOfficer'), $harassmentOfficerValue, count($harassmentOfficer)],
            [$langs->transnoentities('StaffRepresentatives'), $deleguePersonnelValue, count($deleguePersonnel)],
            [$langs->transnoentities('ESC'), $membreComiteeValue, count($membreComitee)]
        ];

        $array['data'] = $dataArray;
        return $array;
    }
}
