<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskdolibarrdocuments/firepermitdocument.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a class file for FirePermitDocument.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturnedocuments.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskdocuments.class.php';
require_once __DIR__ . '/../digiriskresources.class.php';

/**
 * Class for FirePermitDocument.
 */
class FirePermitDocument extends DigiriskDocuments
{
    /**
     * @var string Module name.
     */
	public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'firepermitdocument';

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
	public function FirePermitDocumentFillJSON()
	{
		global $conf;

		$digiriskelement    = new DigiriskElement($this->db);
		$resources          = new DigiriskResources($this->db);
		$firepermit         = new FirePermit($this->db);
		$signatory          = new SaturneSignature($this->db, $this->module, $firepermit->element);
		$societe            = new Societe($this->db);
		$firepermitline     = new FirePermitLine($this->db);
		$preventionplanline = new PreventionPlanLine($this->db);
		$risk               = new Risk($this->db);
		$saturneSchedules       = new SaturneSchedules($this->db);

		$id = GETPOST('id');
		if ($id > 0) {
			$firepermit->fetch($id);
		} else {
            $track_id = GETPOST('track_id', 'alpha');
            $signatory->fetch(0, '', ' AND signature_url =' . "'" . $track_id . "'");
            $firepermit->fetch($signatory->fk_object);
        }

		$firepermitlines     = $firepermitline->fetchAll('', '', 0, 0, array(), 'AND', GETPOST('id'));
		$preventionplanlines = $preventionplanline->fetchAll('', '', 0, 0, array(), 'AND', $firepermit->fk_preventionplan);
		$digirisk_resources  = $resources->fetchDigiriskResources();

		$extsociety          = $resources->fetchResourcesFromObject('ExtSociety', $firepermit);
		if ($extsociety < 1) {
			$extsociety = new stdClass();
		}
		$maitreoeuvre           = $signatory->fetchSignatory('MasterWorker', $firepermit->id, 'firepermit');
		$maitreoeuvre           = is_array($maitreoeuvre) ? array_shift($maitreoeuvre) : $maitreoeuvre;

		$extsocietyresponsible  = $signatory->fetchSignatory('ExtSocietyResponsible', $firepermit->id, 'firepermit');
		$extsocietyresponsible  = is_array($extsocietyresponsible) ? array_shift($extsocietyresponsible) : $extsocietyresponsible;

		$extsocietyintervenants = $signatory->fetchSignatory('ExtSocietyAttendant', $firepermit->id, 'firepermit');
		$labourinspector        = $resources->fetchResourcesFromObject('LabourInspector', $firepermit);
		if ($labourinspector < 1) {
			$labourinspector = new stdClass();
		}

		$labourinspectorcontact = $resources->fetchResourcesFromObject('LabourInspectorAssigned', $firepermit);
		if ($labourinspectorcontact < 1) {
			$labourinspectorcontact = new stdClass();
		}

		$json = array();

		if (!empty ($digirisk_resources)) {
			$societe->fetch($digirisk_resources['Pompiers']->id[0]);
			$json['FirePermit']['pompier_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['SAMU']->id[0]);
			$json['FirePermit']['samu_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['AllEmergencies']->id[0]);
			$json['FirePermit']['emergency_number'] = $societe->phone;

			$societe->fetch($digirisk_resources['Police']->id[0]);
			$json['FirePermit']['police_number'] = $societe->phone;
		}

		if ($maitreoeuvre->id > 0) {
			$json['FirePermit']['maitre_oeuvre'] = array();
			$json['FirePermit']['maitre_oeuvre']['user_id']        = $maitreoeuvre->id;
			$json['FirePermit']['maitre_oeuvre']['phone']          = $maitreoeuvre->phone;
			$json['FirePermit']['maitre_oeuvre']['firstname']      = $maitreoeuvre->firstname;
			$json['FirePermit']['maitre_oeuvre']['lastname']       = $maitreoeuvre->lastname;
			$json['FirePermit']['maitre_oeuvre']['email']          = $maitreoeuvre->email;
			$json['FirePermit']['maitre_oeuvre']['signature']      = $maitreoeuvre->signature;
            $json['FirePermit']['maitre_oeuvre']['signature_date'] = $maitreoeuvre->signature_date;
            $json['FirePermit']['maitre_oeuvre']['attendance']     = $maitreoeuvre->attendance;
		}

		if (is_object($extsociety) && $extsociety->id > 0) {
			$json['FirePermit']['society_outside'] = array();
			$json['FirePermit']['society_outside']['id']      = $extsociety->id;
			$json['FirePermit']['society_outside']['name']    = $extsociety->name;
			$json['FirePermit']['society_outside']['siret']   = $extsociety->idprof2;
			$json['FirePermit']['society_outside']['address'] = $extsociety->address;
			$json['FirePermit']['society_outside']['postal']  = $extsociety->zip;
			$json['FirePermit']['society_outside']['town']    = $extsociety->town;
		}

		if ($extsocietyresponsible->id > 0) {
			$json['FirePermit']['responsable_exterieur'] = array();
			$json['FirePermit']['responsable_exterieur']['id']             = $extsocietyresponsible->id;
			$json['FirePermit']['responsable_exterieur']['firstname']      = $extsocietyresponsible->firstname;
			$json['FirePermit']['responsable_exterieur']['lastname']       = $extsocietyresponsible->lastname;
			$json['FirePermit']['responsable_exterieur']['phone']          = $extsocietyresponsible->phone;
			$json['FirePermit']['responsable_exterieur']['email']          = $extsocietyresponsible->email;
			$json['FirePermit']['responsable_exterieur']['signature']      = $extsocietyresponsible->signature;
			$json['FirePermit']['responsable_exterieur']['signature_date'] = $extsocietyresponsible->signature_date;
		}

		if (!empty ($extsocietyintervenants) && $extsocietyintervenants > 0) {
			foreach ($extsocietyintervenants as $extsocietyintervenant) {
				$json['FirePermit']['intervenant_exterieur'] = array();
				$json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['firstname']      = $extsocietyintervenant->firstname;
				$json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['lastname']       = $extsocietyintervenant->lastname;
				$json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['phone']          = $extsocietyintervenant->phone;
				$json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['email']          = $extsocietyintervenant->email;
				$json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['signature']      = $extsocietyintervenant->signature;
                $json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['signature_date'] = $extsocietyintervenant->signature_date;
                $json['FirePermit']['intervenant_exterieur'][$extsocietyintervenant->id]['attendance']     = $extsocietyintervenant->attendance;
			}
		}

		if ($labourinspector->id > 0) {
			$json['FirePermit']['labour_inspector'] = array();
			$json['FirePermit']['labour_inspector']['id']      = $extsociety->id;
			$json['FirePermit']['labour_inspector']['name']    = $extsociety->name;
			$json['FirePermit']['labour_inspector']['siret']   = $extsociety->idprof2;
			$json['FirePermit']['labour_inspector']['address'] = $extsociety->address;
			$json['FirePermit']['labour_inspector']['postal']  = $extsociety->zip;
			$json['FirePermit']['labour_inspector']['town']    = $extsociety->town;
		}

		if ($labourinspectorcontact->id > 0) {
			$json['FirePermit']['labour_inspector_contact'] = array();
			$json['FirePermit']['labour_inspector_contact']['id']        = $extsocietyresponsible->id;
			$json['FirePermit']['labour_inspector_contact']['firstname'] = $extsocietyresponsible->firstname;
			$json['FirePermit']['labour_inspector_contact']['lastname']  = $extsocietyresponsible->lastname;
			$json['FirePermit']['labour_inspector_contact']['phone']     = $extsocietyresponsible->phone;
			$json['FirePermit']['labour_inspector_contact']['email']     = $extsocietyresponsible->email;
		}

		$json['FirePermit']['ref']   = $firepermit->ref;
		$json['FirePermit']['label'] = $firepermit->label;

		$json['FirePermit']['moyen_generaux_mis_disposition'] = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_GENERAL_MEANS, '<br>'));
		$json['FirePermit']['consigne_generale']              = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_GENERAL_RULES, '<br>'));
		$json['FirePermit']['premiers_secours']               = dol_htmlentitiesbr_decode(strip_tags($conf->global->DIGIRISKDOLIBARR_FIRST_AID, '<br>'));

		$json['FirePermit']['date']['start']     = $firepermit->date_start;
		$json['FirePermit']['date']['end']       = $firepermit->date_end;
		$json['FirePermit']['fk_preventionplan'] = $firepermit->fk_preventionplan;

		$morewhere = ' AND element_id = ' . $firepermit->id;
		$morewhere .= ' AND element_type = ' . "'" . $firepermit->element . "'";
		$morewhere .= ' AND status = 1';

		$saturneSchedules->fetch(0, '', $morewhere);

		$opening_hours_monday    = explode(' ', $saturneSchedules->monday);
		$opening_hours_tuesday   = explode(' ', $saturneSchedules->tuesday);
		$opening_hours_wednesday = explode(' ', $saturneSchedules->wednesday);
		$opening_hours_thursday  = explode(' ', $saturneSchedules->thursday);
		$opening_hours_friday    = explode(' ', $saturneSchedules->friday);
		$opening_hours_saturday  = explode(' ', $saturneSchedules->saturday);
		$opening_hours_sunday    = explode(' ', $saturneSchedules->sunday);

		$json['FirePermit']['lundi_matin']    = $opening_hours_monday[0];
		$json['FirePermit']['lundi_aprem']    = $opening_hours_monday[1];
		$json['FirePermit']['mardi_matin']    = $opening_hours_tuesday[0];
		$json['FirePermit']['mardi_aprem']    = $opening_hours_tuesday[1];
		$json['FirePermit']['mercredi_matin'] = $opening_hours_wednesday[0];
		$json['FirePermit']['mercredi_aprem'] = $opening_hours_wednesday[1];
		$json['FirePermit']['jeudi_matin']    = $opening_hours_thursday[0];
		$json['FirePermit']['jeudi_aprem']    = $opening_hours_thursday[1];
		$json['FirePermit']['vendredi_matin'] = $opening_hours_friday[0];
		$json['FirePermit']['vendredi_aprem'] = $opening_hours_friday[1];
		$json['FirePermit']['samedi_matin']   = $opening_hours_saturday[0];
		$json['FirePermit']['samedi_aprem']   = $opening_hours_saturday[1];
		$json['FirePermit']['dimanche_matin'] = $opening_hours_sunday[0];
		$json['FirePermit']['dimanche_aprem'] = $opening_hours_sunday[1];

		if (!empty($preventionplanlines) && $preventionplanlines > 0) {
			foreach ($preventionplanlines as $line) {
				$digiriskelement->fetch($line->fk_element);

				$json['FirePermit']['PreventionPlan']['risk'][$line->id]['ref']               = $line->ref;
				$json['FirePermit']['PreventionPlan']['risk'][$line->id]['unite_travail']     = $digiriskelement->ref . " - " . $digiriskelement->label;
				$json['FirePermit']['PreventionPlan']['risk'][$line->id]['description']       = $line->description;
				$json['FirePermit']['PreventionPlan']['risk'][$line->id]['name']              = $risk->getDangerCategoryName($line);
				$json['FirePermit']['PreventionPlan']['risk'][$line->id]['prevention_method'] = $line->prevention_method;
			}
		}

		if (!empty($firepermitlines) && $firepermitlines > 0) {
			foreach ($firepermitlines as $line) {
				$digiriskelement->fetch($line->fk_element);

				$json['FirePermit']['risk'][$line->id]['ref']           = $line->ref;
				$json['FirePermit']['risk'][$line->id]['unite_travail'] = $digiriskelement->ref . " - " . $digiriskelement->label;
				$json['FirePermit']['risk'][$line->id]['description']   = $line->description;
				$json['FirePermit']['risk'][$line->id]['name']          = $risk->getFirePermitDangerCategoryName($line);
				$json['FirePermit']['risk'][$line->id]['used_equipment'] = $line->used_equipment;
			}
		}
		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}
}
