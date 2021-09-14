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
 * \file        class/firepermitdocument.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for FirePermitDocument
 */

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once __DIR__ . '/../digiriskdocuments.class.php';
require_once __DIR__ . '/../digiriskresources.class.php';
require_once __DIR__ . '/../openinghours.class.php';

/**
 * Class for FirePermitDocument
 */
class FirePermitDocument extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'firepermitdocument';

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
	 * @var string String with name of icon for firepermitdocument. Must be the part after the 'object_' into object_firepermitdocument.png
	 */
	public $picto = 'firepermitdocument@digiriskdolibarr';

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
	public function FirePermitDocumentFillJSON($object) {

		$contacttmp        = new Contact($this->db);
		$digiriskelement   = new DigiriskElement($this->db);
		$digiriskresources = new DigiriskResources($this->db);
		$firepermit    	   = new FirePermit($this->db);

		$id = GETPOST('id');
		if ($id > 0) {
			$firepermit->fetch($id);
		}

		$resources = $digiriskresources->fetchAll();

		$maitre_oeuvre   = $digiriskresources->fetchResourcesFromObject('FP_MAITRE_OEUVRE', $firepermit);
		$extsociety      = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY', $firepermit);
		$extresponsible  = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY_RESPONSIBLE', $firepermit);
		$extintervenants = $digiriskresources->fetchResourcesFromObject('FP_EXT_SOCIETY_INTERVENANTS', $firepermit);
		$labourinspector = $digiriskresources->fetchResourcesFromObject('FP_LABOUR_INSPECTOR_ASSIGNED', $preventionplan);

		if ($maitre_oeuvre->id > 0) {

			$json['FirePermit']['maitre_oeuvre']['user_id'] = $maitre_oeuvre->id;
			$json['FirePermit']['maitre_oeuvre']['phone'] = $maitre_oeuvre->office_phone;
			$json['FirePermit']['maitre_oeuvre']['signature_id'] = '';
			$json['FirePermit']['maitre_oeuvre']['signature_date'] = '';
		}

		if ($extsociety->id > 0) {

			$json['FirePermit']['society_outside']['id']      = $extsociety->id;
			$json['FirePermit']['society_outside']['name']    = $extsociety->name;
			$json['FirePermit']['society_outside']['siret']   = $extsociety->siret;
			$json['FirePermit']['society_outside']['address'] = $extsociety->address;
			$json['FirePermit']['society_outside']['postal']  = $extsociety->zip;
			$json['FirePermit']['society_outside']['town']    = $extsociety->town;
		}

		if ($extresponsible->id > 0) {

			$json['FirePermit']['responsable_exterieur']['id']             = $extresponsible->id;
			$json['FirePermit']['responsable_exterieur']['firstname']      = $extresponsible->firstname;
			$json['FirePermit']['responsable_exterieur']['lastname']       = $extresponsible->lastname;
			$json['FirePermit']['responsable_exterieur']['phone']          = $extresponsible->phone_pro;
			$json['FirePermit']['responsable_exterieur']['email']          = $extresponsible->email;
			$json['FirePermit']['responsable_exterieur']['signature_id']   = '';
			$json['FirePermit']['responsable_exterieur']['signature_date'] = '';
		}

		if (!empty ($extintervenants) && $extintervenants > 0) {
			foreach ($extintervenants as $extintervenant) {

				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['firstname']      = $extintervenant->firstname;
				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['lastname']       = $extintervenant->lastname;
				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['phone']          = $extintervenant->phone_pro;
				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['email']          = $extintervenant->email;
				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['signature_id']   = '';
				$json['FirePermit']['intervenant_exterieur'][$extintervenant->id]['signature_date'] = '';
			}
		}

		$json['FirePermit']['date']['debut']       = $firepermit->date_debut;
		$json['FirePermit']['date']['fin']         = $firepermit->date_fin;
		$json['FirePermit']['cssct_intervention']  = $firepermit->cssct_intervention;
		$json['FirePermit']['prior_visit_bool']    = $firepermit->prior_visit_bool;
		$json['FirePermit']['prior_visit_text']    = $firepermit->prior_visit_text;
		$json['FirePermit']['labour_inspector']    = $labourinspector->firstname . ' ' . $labourinspector->lastname;

		if ($firepermit->fk_element > 0) {
			$digiriskelement->fetch($object->fk_element);
			$json['FirePermit']['location']['id']  = $digiriskelement->id;
			$json['FirePermit']['location']['name'] = $digiriskelement->ref . ' - ' . $digiriskelement->label;
		}

		return json_encode($json, JSON_UNESCAPED_UNICODE);
	}

}
