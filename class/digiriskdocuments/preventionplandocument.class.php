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
 * \file        class/preventionplandocument.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for PreventionPlanDocument
 */

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/../digiriskdocuments.class.php';
require_once __DIR__ . '/../digiriskresources.class.php';
require_once __DIR__ . '/../digiriskelement.class.php';
require_once __DIR__ . '/../openinghours.class.php';

/**
 * Class for PreventionPlanDocument
 */
class PreventionPlanDocument extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'preventionplandocument';

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
	 * @var string String with name of icon for preventionplandocument. Must be the part after the 'object_' into object_preventionplandocument.png
	 */
	public $picto = 'preventionplandocument@digiriskdolibarr';

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
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
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
	public function PreventionPlanDocumentFillJSON($object)
	{
		global $conf;

		$digiriskelement = new DigiriskElement($this->db);
		$resources = new DigiriskResources($this->db);
		$preventionplan = new PreventionPlan($this->db);
		$signatory = new PreventionPlanSignature($this->db);
		$societe = new Societe($this->db);
		$preventionplanline = new PreventionPlanLine($this->db);
		$risk = new Risk($this->db);
		$openinghours = new Openinghours($this->db);

		$id = GETPOST('id');
		if ($id > 0) {
			$preventionplan->fetch($id);
		}

		$preventionplanlines = $preventionplanline->fetchAll(GETPOST('id'));
		$digirisk_resources = $resources->digirisk_dolibarr_fetch_resources();

		$extsociety = $resources->fetchResourcesFromObject('PP_EXT_SOCIETY', $preventionplan);
		$maitreoeuvre = array_shift($signatory->fetchSignatory('PP_MAITRE_OEUVRE', $preventionplan->id));
		$extsocietyresponsible = array_shift($signatory->fetchSignatory('PP_EXT_SOCIETY_RESPONSIBLE', $preventionplan->id));
		$extsocietyintervenants = $signatory->fetchSignatory('PP_EXT_SOCIETY_INTERVENANTS', $preventionplan->id);
		$labourinspector = $resources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR', $preventionplan);
		$labourinspectorcontact = $resources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $preventionplan);

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
			$json['PreventionPlan']['maitre_oeuvre']['user_id'] = $maitreoeuvre->id;
			$json['PreventionPlan']['maitre_oeuvre']['phone'] = $maitreoeuvre->phone;
			$json['PreventionPlan']['maitre_oeuvre']['firstname'] = $maitreoeuvre->firstname;
			$json['PreventionPlan']['maitre_oeuvre']['lastname'] = $maitreoeuvre->lastname;
			$json['PreventionPlan']['maitre_oeuvre']['email'] = $maitreoeuvre->email;
			$json['PreventionPlan']['maitre_oeuvre']['signature'] = $maitreoeuvre->signature;
			$json['PreventionPlan']['maitre_oeuvre']['signature_date'] = $maitreoeuvre->signature_date;
		}

		if ($extsociety->id > 0) {
			$json['PreventionPlan']['society_outside']['id'] = $extsociety->id;
			$json['PreventionPlan']['society_outside']['name'] = $extsociety->name;
			$json['PreventionPlan']['society_outside']['siret'] = $extsociety->siret;
			$json['PreventionPlan']['society_outside']['address'] = $extsociety->address;
			$json['PreventionPlan']['society_outside']['postal'] = $extsociety->zip;
			$json['PreventionPlan']['society_outside']['town'] = $extsociety->town;
		}

		if ($extsocietyresponsible->id > 0) {
			$json['PreventionPlan']['responsable_exterieur']['id'] = $extsocietyresponsible->id;
			$json['PreventionPlan']['responsable_exterieur']['firstname'] = $extsocietyresponsible->firstname;
			$json['PreventionPlan']['responsable_exterieur']['lastname'] = $extsocietyresponsible->lastname;
			$json['PreventionPlan']['responsable_exterieur']['phone'] = $extsocietyresponsible->phone;
			$json['PreventionPlan']['responsable_exterieur']['email'] = $extsocietyresponsible->email;
			$json['PreventionPlan']['responsable_exterieur']['signature'] = $extsocietyresponsible->signature;
			$json['PreventionPlan']['responsable_exterieur']['signature_date'] = $extsocietyresponsible->signature_date;
		}

		if (!empty ($extsocietyintervenants) && $extsocietyintervenants > 0) {
			foreach ($extsocietyintervenants as $extsocietyintervenant) {
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['firstname'] = $extsocietyintervenant->firstname;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['lastname'] = $extsocietyintervenant->lastname;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['phone'] = $extsocietyintervenant->phone;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['email'] = $extsocietyintervenant->email;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['signature'] = $extsocietyintervenant->signature;
				$json['PreventionPlan']['intervenant_exterieur'][$extsocietyintervenant->id]['signature_date'] = $extsocietyintervenant->signature_date;
			}
		}

		if ($labourinspector->id > 0) {
			$json['PreventionPlan']['labour_inspector']['id'] = $extsociety->id;
			$json['PreventionPlan']['labour_inspector']['name'] = $extsociety->name;
			$json['PreventionPlan']['labour_inspector']['siret'] = $extsociety->siret;
			$json['PreventionPlan']['labour_inspector']['address'] = $extsociety->address;
			$json['PreventionPlan']['labour_inspector']['postal'] = $extsociety->zip;
			$json['PreventionPlan']['labour_inspector']['town'] = $extsociety->town;
		}

		if ($labourinspectorcontact->id > 0) {
			$json['PreventionPlan']['labour_inspector_contact']['id'] = $extsocietyresponsible->id;
			$json['PreventionPlan']['labour_inspector_contact']['firstname'] = $extsocietyresponsible->firstname;
			$json['PreventionPlan']['labour_inspector_contact']['lastname'] = $extsocietyresponsible->lastname;
			$json['PreventionPlan']['labour_inspector_contact']['phone'] = $extsocietyresponsible->phone;
			$json['PreventionPlan']['labour_inspector_contact']['email'] = $extsocietyresponsible->email;
		}

		$json['PreventionPlan']['ref'] = $preventionplan->ref;
		$json['PreventionPlan']['label'] = $preventionplan->label;

		$json['PreventionPlan']['moyen_generaux_mis_disposition'] = $conf->global->DIGIRISK_GENERAL_MEANS;
		$json['PreventionPlan']['consigne_generale'] = $conf->global->DIGIRISK_GENERAL_RULES;
		$json['PreventionPlan']['premiers_secours'] = $conf->global->DIGIRISK_FIRST_AID;

		$json['PreventionPlan']['date']['start'] = $preventionplan->date_start;
		$json['PreventionPlan']['date']['end'] = $preventionplan->date_end;
		$json['PreventionPlan']['cssct_intervention'] = $preventionplan->cssct_intervention;
		$json['PreventionPlan']['prior_visit_bool'] = $preventionplan->prior_visit_bool;
		$json['PreventionPlan']['prior_visit_text'] = $preventionplan->prior_visit_text;
		$json['PreventionPlan']['prior_visit_date'] = $preventionplan->prior_visit_date;

		$morewhere = ' AND element_id = ' . $preventionplan->id;
		$morewhere .= ' AND element_type = ' . "'" . $preventionplan->element . "'";
		$morewhere .= ' AND status = 1';

		$openinghours->fetch(0, '', $morewhere);

		$opening_hours_monday = explode(' ', $openinghours->monday);
		$opening_hours_tuesday = explode(' ', $openinghours->tuesday);
		$opening_hours_wednesday = explode(' ', $openinghours->wednesday);
		$opening_hours_thursday = explode(' ', $openinghours->thursday);
		$opening_hours_friday = explode(' ', $openinghours->friday);
		$opening_hours_saturday = explode(' ', $openinghours->saturday);
		$opening_hours_sunday = explode(' ', $openinghours->sunday);

		$json['PreventionPlan']['lundi_matin'] = $opening_hours_monday[0];
		$json['PreventionPlan']['lundi_aprem'] = $opening_hours_monday[1];
		$json['PreventionPlan']['mardi_matin'] = $opening_hours_tuesday[0];
		$json['PreventionPlan']['mardi_aprem'] = $opening_hours_tuesday[1];
		$json['PreventionPlan']['mercredi_matin'] = $opening_hours_wednesday[0];
		$json['PreventionPlan']['mercredi_aprem'] = $opening_hours_wednesday[1];
		$json['PreventionPlan']['jeudi_matin'] = $opening_hours_thursday[0];
		$json['PreventionPlan']['jeudi_aprem'] = $opening_hours_thursday[1];
		$json['PreventionPlan']['vendredi_matin'] = $opening_hours_friday[0];
		$json['PreventionPlan']['vendredi_aprem'] = $opening_hours_friday[1];
		$json['PreventionPlan']['samedi_matin'] = $opening_hours_saturday[0];
		$json['PreventionPlan']['samedi_aprem'] = $opening_hours_saturday[1];
		$json['PreventionPlan']['dimanche_matin'] = $opening_hours_sunday[0];
		$json['PreventionPlan']['dimanche_aprem'] = $opening_hours_sunday[1];

		if (!empty($preventionplanlines) && $preventionplanlines > 0) {
			foreach ($preventionplanlines as $line) {
				$digiriskelement->fetch($line->fk_element);

				$json['PreventionPlan']['risk'][$line->id]['ref'] = $line->ref;
				$json['PreventionPlan']['risk'][$line->id]['unite_travail'] = $digiriskelement->ref . " - " . $digiriskelement->label;
				$json['PreventionPlan']['risk'][$line->id]['description'] = $line->description;
				$json['PreventionPlan']['risk'][$line->id]['name'] = $risk->get_danger_category_name($line);
				$json['PreventionPlan']['risk'][$line->id]['prevention_method'] = $line->prevention_method;
			}
		}
		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}
}
