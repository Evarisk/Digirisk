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
 * \file        class/informationssharing.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for InformationsSharing
 */

require_once __DIR__ . '/../digiriskdocuments.class.php';
require_once __DIR__ . '/../digiriskresources.class.php';

/**
 * Class for InformationsSharing
 */
class InformationsSharing extends DigiriskDocuments
{

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $element = 'informationssharing';

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
	 * @var string String with name of icon for informationssharing. Must be the part after the 'object_' into object_informationssharing.png
	 */
	public $picto = 'informationssharing@digiriskdolibarr';

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
	public function InformationsSharingFillJSON($object) {

		global $langs, $conf;

		$resources 			= new DigiriskResources($this->db);
		$digirisk_resources = $resources->digirisk_dolibarr_fetch_resources();

		// 		*** JSON FILLING ***
		if (!empty ($digirisk_resources)) {

			$json['InformationsSharing']['delegues_du_personnels_date']    	  = $conf->global->DIGIRISK_DP_ELECTION_DATE;
			$json['InformationsSharing']['delegues_du_personnels_titulaires'] = '';

			if (!empty ($digirisk_resources['TitularsDP']->id )) {
				foreach ($digirisk_resources['TitularsDP']->id as $dp_titular) {

					$dp_titulars = new User($this->db);
					$result = $dp_titulars->fetch($dp_titular);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_titulaires']  .= $dp_titulars->firstname . " " . $dp_titulars->lastname ;
					}
				}
			}

			$json['InformationsSharing']['delegues_du_personnels_suppleants'] = '';

			if (!empty ($digirisk_resources['AlternatesDP']->id )) {
				foreach ($digirisk_resources['AlternatesDP']->id as $dp_alternate) {

					$dp_alternates = new User($this->db);
					$result = $dp_alternates->fetch($dp_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_suppleants']  .= $dp_alternates->firstname . " " . $dp_alternates->lastname ;
					}
				}
			}

			$json['InformationsSharing']['delegues_du_personnels_suppleants'] = '';

			if (!empty ($digirisk_resources['AlternatesDP']->id )) {
				foreach ($digirisk_resources['AlternatesDP']->id as $dp_alternate) {

					$dp_alternates = new User($this->db);
					$result = $dp_alternates->fetch($dp_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['delegues_du_personnels_suppleants']  .= $dp_alternates->firstname . " " . $dp_alternates->lastname ;
					}
				}
			}

			// CSE
			$json['InformationsSharing']['membres_du_comite_entreprise_date'] = $conf->global->DIGIRISK_CSE_ELECTION_DATE;


			$json['InformationsSharing']['membres_du_comite_entreprise_titulaires'] = '';

			if (!empty ($digirisk_resources['TitularsCSE']->id )) {
				foreach ($digirisk_resources['TitularsCSE']->id as $cse_titular) {

					$cse_titulars = new User($this->db);
					$result = $cse_titulars->fetch($cse_titular);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_titulaires']  .= $cse_titulars->firstname . " " . $cse_titulars->lastname ;
					}
				}
			}

			$json['InformationsSharing']['membres_du_comite_entreprise_suppleants'] = '';

			if (!empty ($digirisk_resources['AlternatesCSE']->id )) {
				foreach ($digirisk_resources['AlternatesCSE']->id as $cse_alternate) {

					$cse_alternates = new User($this->db);
					$result = $cse_alternates->fetch($cse_alternate);
					if ($result > 0) {
						$json['InformationsSharing']['membres_du_comite_entreprise_suppleants']  .= $cse_alternates->firstname . " " . $cse_alternates->lastname ;
					}
				}
			}

			$object->json = json_encode($json, JSON_UNESCAPED_UNICODE);
			return $object->json;

		}
		else
		{
			return -1;
		}
	}
}
