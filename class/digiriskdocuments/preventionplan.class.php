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

dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/societe/class/societe.class.php');


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
	public function PreventionPlanFillJSON($intervenants_ids, $preventions_ids, $former_id,$maitre_oeuvre_id,$extintervenant_ids,$morethan400hours,$imminentdanger,$extsociety_id,$date_debut,$date_fin,$location) {

		$usertmp = new User($this->db);
		$societetmp = new Societe($this->db);

		$former = $usertmp;
		$maitre_oeuvre = $usertmp;
		$extintervenant = $usertmp;

		$extsociety = $societetmp;

		$former->fetch($former_id);

		$json['PreventionPlan']['former']['user_id']        = $former->id;
		$json['PreventionPlan']['former']['signature_id']   = '';
		$json['PreventionPlan']['former']['signature_date'] = '';

		$maitre_oeuvre->fetch($maitre_oeuvre_id);

		$json['PreventionPlan']['maitre_oeuvre']['user_id']        = $maitre_oeuvre->id;
		$json['PreventionPlan']['maitre_oeuvre']['phone']          = $maitre_oeuvre->office_phone;
		$json['PreventionPlan']['maitre_oeuvre']['signature_id']   = '';
		$json['PreventionPlan']['maitre_oeuvre']['signature_date'] = '';

		$extintervenant->fetch($extintervenant_ids);

		$json['PreventionPlan']['intervenant_exterieur']['firstname']      = $extintervenant->firstname;
		$json['PreventionPlan']['intervenant_exterieur']['lastname']       = $extintervenant->lastname;
		$json['PreventionPlan']['intervenant_exterieur']['phone']          = $extintervenant->office_phone;
		$json['PreventionPlan']['intervenant_exterieur']['email']          = $extintervenant->email;
		$json['PreventionPlan']['intervenant_exterieur']['signature_id']   = '';
		$json['PreventionPlan']['intervenant_exterieur']['signature_date'] = '';

		$json['PreventionPlan']['more_than_400_hours']  = $morethan400hours;

		$json['PreventionPlan']['imminent_danger']   = $imminentdanger;

		$json['PreventionPlan']['location']   = $location;

		$extsociety->fetch($extsociety_id);

		$json['PreventionPlan']['society_outside']['name']   = $extsociety->name;
		$json['PreventionPlan']['society_outside']['siret']   = $extsociety->siret;
		$json['PreventionPlan']['society_outside']['address']   = $extsociety->address;
		$json['PreventionPlan']['society_outside']['postal']   = $extsociety->zip;
		$json['PreventionPlan']['society_outside']['town']   = $extsociety->town;

		$json['PreventionPlan']['intervenants']   = $intervenants;

		$json['PreventionPlan']['preventions']   = $preventions;
		$json['PreventionPlan']['date']['debut']   = $date_debut;
		$json['PreventionPlan']['date']['fin']   = $date_fin;
	
		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}
}
