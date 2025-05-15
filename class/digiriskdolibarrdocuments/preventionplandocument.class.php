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
 * \file    class/digiriskdolibarrdocuments/preventionplandocument.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a class file for PreventionPlanDocument
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskdocuments.class.php';
require_once __DIR__ . '/../digiriskresources.class.php';

/**
 * Class for PreventionPlanDocument
 */
class PreventionPlanDocument extends DigiriskDocuments
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'preventionplandocument';

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
	public function PreventionPlanDocumentFillJSON()
	{
		global $conf, $mysoc;

		$digiriskelement    = new DigiriskElement($this->db);
		$resources          = new DigiriskResources($this->db);
		$preventionplan     = new PreventionPlan($this->db);
		$signatory          = new SaturneSignature($this->db, $this->module, $preventionplan->element);
		$societe            = new Societe($this->db);
		$preventionplanline = new PreventionPlanLine($this->db);
		$risk               = new Risk($this->db);
		$saturneSchedules       = new SaturneSchedules($this->db);
		$json               = array();

		$id = GETPOST('id');
		if ($id > 0) {
			$preventionplan->fetch($id);
		} else {
            $track_id = GETPOST('track_id', 'alpha');
            $signatory->fetch(0, '', ' AND signature_url =' . "'" . $track_id . "'");
            $preventionplan->fetch($signatory->fk_object);
        }

		$preventionplanlines = $preventionplanline->fetchAll('', '', 0, 0, array(), 'AND', GETPOST('id'));
		$digirisk_resources = $resources->fetchDigiriskResources();

		$extsociety = $resources->fetchResourcesFromObject('ExtSociety', $preventionplan);

		if ($extsociety < 1) {
			$extsociety = new stdClass();
		}

		$maitreoeuvre = $signatory->fetchSignatory('MasterWorker', $preventionplan->id, 'preventionplan');
		$maitreoeuvre = is_array($maitreoeuvre) ? array_shift($maitreoeuvre) : $maitreoeuvre;
		$extsocietyresponsible = $signatory->fetchSignatory('ExtSocietyResponsible', $preventionplan->id, 'preventionplan');
		$extsocietyresponsible  = is_array($extsocietyresponsible) ? array_shift($extsocietyresponsible) : $extsocietyresponsible;
		$extsocietyintervenants = $signatory->fetchSignatory('ExtSocietyAttendant', $preventionplan->id, 'preventionplan');
		$labourinspector = $resources->fetchResourcesFromObject('LabourInspector', $preventionplan);
		if ($labourinspector < 1) {
			$labourinspector = new stdClass();
		}

		$labourinspectorcontact = $resources->fetchResourcesFromObject('LabourInspectorAssigned', $preventionplan);
		if ($labourinspectorcontact < 1) {
			$labourinspectorcontact = new stdClass();
		}


		if (!empty ($digirisk_resources)) {
			$societe->fetch($digirisk_resources['Pompiers']->id[0]);
			$json['PreventionPlan']['pompier_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['SAMU']->id[0]);
			$json['PreventionPlan']['samu_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['AllEmergencies']->id[0]);
			$json['PreventionPlan']['emergency_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['Police']->id[0]);
			$json['PreventionPlan']['police_number'] = $societe->phone;
		}

		if ($maitreoeuvre->id > 0) {
			$json['PreventionPlan']['maitre_oeuvre'] = array();
			$json['PreventionPlan']['maitre_oeuvre']['user_id']        = $maitreoeuvre->id;
			$json['PreventionPlan']['maitre_oeuvre']['phone']          = $maitreoeuvre->phone;
			$json['PreventionPlan']['maitre_oeuvre']['firstname']      = $maitreoeuvre->firstname;
			$json['PreventionPlan']['maitre_oeuvre']['lastname']       = $maitreoeuvre->lastname;
			$json['PreventionPlan']['maitre_oeuvre']['email']          = $maitreoeuvre->email;
			$json['PreventionPlan']['maitre_oeuvre']['signature']      = $maitreoeuvre->signature;
            $json['PreventionPlan']['maitre_oeuvre']['signature_date'] = $maitreoeuvre->signature_date;
            $json['PreventionPlan']['maitre_oeuvre']['attendance']     = $maitreoeuvre->attendance;
		}

        $json['PreventionPlan']['society_inside'] = array();
        $json['PreventionPlan']['society_inside']['id']      = $mysoc->id;
        $json['PreventionPlan']['society_inside']['name']    = $mysoc->name;
        $json['PreventionPlan']['society_inside']['siret']   = $mysoc->idprof2;
        $json['PreventionPlan']['society_inside']['address'] = $mysoc->address;
        $json['PreventionPlan']['society_inside']['postal']  = $mysoc->zip;
        $json['PreventionPlan']['society_inside']['town']    = $mysoc->town;

		if ($extsociety->id > 0) {
			$json['PreventionPlan']['society_outside'] = array();
			$json['PreventionPlan']['society_outside']['id']      = $extsociety->id;
			$json['PreventionPlan']['society_outside']['name']    = $extsociety->name;
			$json['PreventionPlan']['society_outside']['siret']   = $extsociety->idprof2;
			$json['PreventionPlan']['society_outside']['address'] = $extsociety->address;
			$json['PreventionPlan']['society_outside']['postal']  = $extsociety->zip;
			$json['PreventionPlan']['society_outside']['town']    = $extsociety->town;
		}

		if ($extsocietyresponsible->id > 0) {
			$json['PreventionPlan']['responsable_exterieur'] = array();
			$json['PreventionPlan']['responsable_exterieur']['id']             = $extsocietyresponsible->id;
			$json['PreventionPlan']['responsable_exterieur']['firstname']      = $extsocietyresponsible->firstname;
			$json['PreventionPlan']['responsable_exterieur']['lastname']       = $extsocietyresponsible->lastname;
			$json['PreventionPlan']['responsable_exterieur']['phone']          = $extsocietyresponsible->phone;
			$json['PreventionPlan']['responsable_exterieur']['email']          = $extsocietyresponsible->email;
			$json['PreventionPlan']['responsable_exterieur']['signature']      = $extsocietyresponsible->signature;
			$json['PreventionPlan']['responsable_exterieur']['signature_date'] = $extsocietyresponsible->signature_date;
            $json['PreventionPlan']['responsable_exterieur']['attendance']     = $maitreoeuvre->attendance;
        }

		if (!empty ($extsocietyintervenants) && $extsocietyintervenants > 0) {
			foreach ($extsocietyintervenants as $extsocietyintervenant) {
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id] = array();
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['firstname']      = $extsocietyintervenant->firstname;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['lastname']       = $extsocietyintervenant->lastname;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['phone']          = $extsocietyintervenant->phone;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['email']          = $extsocietyintervenant->email;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['signature']      = $extsocietyintervenant->signature;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['signature_date'] = $extsocietyintervenant->signature_date;
			}
		}

		if ($labourinspector->id > 0) {
			$json['PreventionPlan']['labour_inspector'] = array();
			$json['PreventionPlan']['labour_inspector']['id']      = $extsociety->id;
			$json['PreventionPlan']['labour_inspector']['name']    = $extsociety->name;
			$json['PreventionPlan']['labour_inspector']['siret']   = $extsociety->idprof2;
			$json['PreventionPlan']['labour_inspector']['address'] = $extsociety->address;
			$json['PreventionPlan']['labour_inspector']['postal']  = $extsociety->zip;
			$json['PreventionPlan']['labour_inspector']['town']    = $extsociety->town;
		}

		if ($labourinspectorcontact->id > 0) {
			$json['PreventionPlan']['labour_inspector_contact'] = array();
			$json['PreventionPlan']['labour_inspector_contact']['id']        = $extsocietyresponsible->id;
			$json['PreventionPlan']['labour_inspector_contact']['firstname'] = $extsocietyresponsible->firstname;
			$json['PreventionPlan']['labour_inspector_contact']['lastname']  = $extsocietyresponsible->lastname;
			$json['PreventionPlan']['labour_inspector_contact']['phone']     = $extsocietyresponsible->phone;
			$json['PreventionPlan']['labour_inspector_contact']['email']     = $extsocietyresponsible->email;
		}

		$json['PreventionPlan']['ref']   = $preventionplan->ref;
		$json['PreventionPlan']['label'] = $preventionplan->label;

		$json['PreventionPlan']['moyen_generaux_mis_disposition'] = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_GENERAL_MEANS, '<br>'));
		$json['PreventionPlan']['consigne_generale']              = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_GENERAL_RULES, '<br>'));
		$json['PreventionPlan']['premiers_secours']               = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_FIRST_AID, '<br>'));

		$json['PreventionPlan']['date']['start']      = $preventionplan->date_start;
		$json['PreventionPlan']['date']['end']        = $preventionplan->date_end;
		$json['PreventionPlan']['cssct_intervention'] = $preventionplan->cssct_intervention;
		$json['PreventionPlan']['prior_visit_bool']   = $preventionplan->prior_visit_bool;
		$json['PreventionPlan']['prior_visit_text']   = $preventionplan->prior_visit_text;
		$json['PreventionPlan']['prior_visit_date']   = $preventionplan->prior_visit_date;

		$morewhere = ' AND element_id = ' . $preventionplan->id;
		$morewhere .= ' AND element_type = ' . "'" . $preventionplan->element . "'";
		$morewhere .= ' AND status = 1';

		$saturneSchedules->fetch(0, '', $morewhere);

		$opening_hours_monday    = explode(' ', $saturneSchedules->monday);
		$opening_hours_tuesday   = explode(' ', $saturneSchedules->tuesday);
		$opening_hours_wednesday = explode(' ', $saturneSchedules->wednesday);
		$opening_hours_thursday  = explode(' ', $saturneSchedules->thursday);
		$opening_hours_friday    = explode(' ', $saturneSchedules->friday);
		$opening_hours_saturday  = explode(' ', $saturneSchedules->saturday);
		$opening_hours_sunday    = explode(' ', $saturneSchedules->sunday);

		$json['PreventionPlan']['lundi_matin']    = $opening_hours_monday[0];
		$json['PreventionPlan']['lundi_aprem']    = $opening_hours_monday[1];
		$json['PreventionPlan']['mardi_matin']    = $opening_hours_tuesday[0];
		$json['PreventionPlan']['mardi_aprem']    = $opening_hours_tuesday[1];
		$json['PreventionPlan']['mercredi_matin'] = $opening_hours_wednesday[0];
		$json['PreventionPlan']['mercredi_aprem'] = $opening_hours_wednesday[1];
		$json['PreventionPlan']['jeudi_matin']    = $opening_hours_thursday[0];
		$json['PreventionPlan']['jeudi_aprem']    = $opening_hours_thursday[1];
		$json['PreventionPlan']['vendredi_matin'] = $opening_hours_friday[0];
		$json['PreventionPlan']['vendredi_aprem'] = $opening_hours_friday[1];
		$json['PreventionPlan']['samedi_matin']   = $opening_hours_saturday[0];
		$json['PreventionPlan']['samedi_aprem']   = $opening_hours_saturday[1];
		$json['PreventionPlan']['dimanche_matin'] = $opening_hours_sunday[0];
		$json['PreventionPlan']['dimanche_aprem'] = $opening_hours_sunday[1];

		if (!empty($preventionplanlines) && $preventionplanlines > 0) {
			foreach ($preventionplanlines as $line) {
				$digiriskelement->fetch($line->fk_element);

				$json['PreventionPlan']['risk'][$line->id]['ref']               = $line->ref;
				$json['PreventionPlan']['risk'][$line->id]['unite_travail']     = $digiriskelement->ref . " - " . $digiriskelement->label;
				$json['PreventionPlan']['risk'][$line->id]['description']       = $line->description;
				$json['PreventionPlan']['risk'][$line->id]['name']              = $risk->getDangerCategoryName($line);
				$json['PreventionPlan']['risk'][$line->id]['prevention_method'] = $line->prevention_method;
			}
		}

		$jsonFormatted = json_encode($json, JSON_UNESCAPED_UNICODE);

		return $jsonFormatted;
	}
}
