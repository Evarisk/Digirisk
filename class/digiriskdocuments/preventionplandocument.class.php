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
	public function PreventionPlanDocumentFillJSON($object) {

		$contacttmp        = new Contact($this->db);
		$digiriskelement   = new DigiriskElement($this->db);
		$digiriskresources = new DigiriskResources($this->db);
		$preventionplan    = new PreventionPlan($this->db);

		$id = GETPOST('id');
		if ($id > 0) {
			$preventionplan->fetch($id);
		}

		$resources = $digiriskresources->fetchAll();

		$maitre_oeuvre   = $digiriskresources->fetchResourcesFromObject('PP_MAITRE_OEUVRE', $preventionplan);
		$extsociety      = $digiriskresources->fetchResourcesFromObject('PP_EXT_SOCIETY', $preventionplan);
		$extresponsible  = $digiriskresources->fetchResourcesFromObject('PP_EXT_SOCIETY_RESPONSIBLE', $preventionplan);
		$extintervenants = $digiriskresources->fetchResourcesFromObject('PP_EXT_SOCIETY_INTERVENANTS', $preventionplan);
		$labourinspector = $digiriskresources->fetchResourcesFromObject('PP_LABOUR_INSPECTOR_ASSIGNED', $preventionplan);

		if ($maitre_oeuvre->id > 0) {

			$json['PreventionPlan']['maitre_oeuvre']['user_id'] = $maitre_oeuvre->id;
			$json['PreventionPlan']['maitre_oeuvre']['phone'] = $maitre_oeuvre->office_phone;
			$json['PreventionPlan']['maitre_oeuvre']['signature_id'] = '';
			$json['PreventionPlan']['maitre_oeuvre']['signature_date'] = '';
		}

		if ($extsociety->id > 0) {

			$json['PreventionPlan']['society_outside']['id']      = $extsociety->id;
			$json['PreventionPlan']['society_outside']['name']    = $extsociety->name;
			$json['PreventionPlan']['society_outside']['siret']   = $extsociety->siret;
			$json['PreventionPlan']['society_outside']['address'] = $extsociety->address;
			$json['PreventionPlan']['society_outside']['postal']  = $extsociety->zip;
			$json['PreventionPlan']['society_outside']['town']    = $extsociety->town;
		}

		if ($extresponsible->id > 0) {

			$json['PreventionPlan']['responsable_exterieur']['id']             = $extresponsible->id;
			$json['PreventionPlan']['responsable_exterieur']['firstname']      = $extresponsible->firstname;
			$json['PreventionPlan']['responsable_exterieur']['lastname']       = $extresponsible->lastname;
			$json['PreventionPlan']['responsable_exterieur']['phone']          = $extresponsible->phone_pro;
			$json['PreventionPlan']['responsable_exterieur']['email']          = $extresponsible->email;
			$json['PreventionPlan']['responsable_exterieur']['signature_id']   = '';
			$json['PreventionPlan']['responsable_exterieur']['signature_date'] = '';
		}

		if (!empty ($extintervenants) && $extintervenants > 0) {
			foreach ($extintervenants as $extintervenant) {

				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['firstname']      = $extintervenant->firstname;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['lastname']       = $extintervenant->lastname;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['phone']          = $extintervenant->phone_pro;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['email']          = $extintervenant->email;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['signature_id']   = '';
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['signature_date'] = '';
			}
		}

		$json['PreventionPlan']['date']['debut']       = $preventionplan->date_debut;
		$json['PreventionPlan']['date']['fin']         = $preventionplan->date_fin;
		$json['PreventionPlan']['cssct_intervention']  = $preventionplan->cssct_intervention;
		$json['PreventionPlan']['prior_visit_bool']    = $preventionplan->prior_visit_bool;
		$json['PreventionPlan']['prior_visit_text']    = $preventionplan->prior_visit_text;
		$json['PreventionPlan']['labour_inspector']    = $labourinspector->firstname . ' ' . $labourinspector->lastname;

		if ($preventionplan->fk_element > 0) {
			$digiriskelement->fetch($object->fk_element);
			$json['PreventionPlan']['location']['id']  = $digiriskelement->id;
			$json['PreventionPlan']['location']['name'] = $digiriskelement->ref . ' - ' . $digiriskelement->label;
		}


		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

}
