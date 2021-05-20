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
 * \file        class/preventionplan.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for PreventionPlan
 */

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');

/**
 * Class for PreventionPlan
 */
class PreventionPlan extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'preventionplan';

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
	 * @var string String with name of icon for preventionplan. Must be the part after the 'object_' into object_preventionplan.png
	 */
	public $picto = 'preventionplan@digiriskdolibarr';

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
	public function PreventionPlanFillJSON($intervenants_ids, $interventions_ids,$maitre_oeuvre_id ,$extsociety_id, $extresponsible_id,$extintervenant_ids,$morethan400hours,$imminentdanger,$date_debut,$date_fin,$location_id, $labour_inspector_id) {

		$maitre_oeuvre   = new User($this->db);
		$extsociety      = new Societe($this->db);
		$contacttmp      = new Contact($this->db);
		$digiriskelement = new DigiriskElement($this->db);

		$extresponsible = $contacttmp;
		$extintervenant = $contacttmp;

		if (!empty ($maitre_oeuvre_id) && $maitre_oeuvre_id > 0) {

			$maitre_oeuvre->fetch($maitre_oeuvre_id);

			$json['PreventionPlan']['maitre_oeuvre']['user_id'] = $maitre_oeuvre->id;
			$json['PreventionPlan']['maitre_oeuvre']['phone'] = $maitre_oeuvre->office_phone;
			$json['PreventionPlan']['maitre_oeuvre']['signature_id'] = '';
			$json['PreventionPlan']['maitre_oeuvre']['signature_date'] = '';
		}

		if (!empty ($extsociety_id) && $extsociety_id > 0) {

			$extsociety->fetch($extsociety_id);

			$json['PreventionPlan']['society_outside']['id']      = $extsociety->id;
			$json['PreventionPlan']['society_outside']['name']    = $extsociety->name;
			$json['PreventionPlan']['society_outside']['siret']   = $extsociety->siret;
			$json['PreventionPlan']['society_outside']['address'] = $extsociety->address;
			$json['PreventionPlan']['society_outside']['postal']  = $extsociety->zip;
			$json['PreventionPlan']['society_outside']['town']    = $extsociety->town;
		}

		if (!empty ($extresponsible_id) && $extresponsible_id > 0) {
			$extresponsible_id = array_shift($extresponsible_id);
			$extresponsible->fetch($extresponsible_id);

			$json['PreventionPlan']['responsable_exterieur']['id']             = $extresponsible->id;
			$json['PreventionPlan']['responsable_exterieur']['firstname']      = $extresponsible->firstname;
			$json['PreventionPlan']['responsable_exterieur']['lastname']       = $extresponsible->lastname;
			$json['PreventionPlan']['responsable_exterieur']['phone']          = $extresponsible->phone_pro;
			$json['PreventionPlan']['responsable_exterieur']['email']          = $extresponsible->email;
			$json['PreventionPlan']['responsable_exterieur']['signature_id']   = '';
			$json['PreventionPlan']['responsable_exterieur']['signature_date'] = '';
		}

		if (!empty ($extintervenant_ids) && $extintervenant_ids > 0) {
			foreach ($extintervenant_ids as $extintervenant_id) {
				$extintervenant->fetch($extintervenant_id);

				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['firstname']      = $extintervenant->firstname;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['lastname']       = $extintervenant->lastname;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['phone']          = $extintervenant->phone_pro;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['email']          = $extintervenant->email;
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['signature_id']   = '';
				$json['PreventionPlan']['intervenant_exterieur'][$extintervenant->id]['signature_date'] = '';
			}
		}

		$json['PreventionPlan']['date']['debut']       = $date_debut;
		$json['PreventionPlan']['date']['fin']         = $date_fin;
		$json['PreventionPlan']['more_than_400_hours'] = $morethan400hours;
		$json['PreventionPlan']['imminent_danger']     = $imminentdanger;
		$json['PreventionPlan']['labour_inspector']    = $imminentdanger;

		if ($location_id > 0) {
			$digiriskelement->fetch($location_id);
			$json['PreventionPlan']['location']['id']  = $digiriskelement->id;
			$json['PreventionPlan']['location']['name'] = $digiriskelement->ref . ' - ' . $digiriskelement->label;
		}

	//@todo interventions et intervenants

		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return 	string				Label of contact status
	 */
	public function getLibStatut($mode)
	{
		return '';
	}
}
