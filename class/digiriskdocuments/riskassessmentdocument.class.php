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
 * \file        class/digiriskdocuments/riskassessmentdocument.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for RiskAssessmentDocument
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

require_once __DIR__ . '/../digiriskdocuments.class.php';

/**
 * Class for RiskAssessmentDocument
 */
class RiskAssessmentDocument extends DigiriskDocuments
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'riskassessmentdocument';

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
	 * @var string String with name of icon for riskassessmentdocument. Must be the part after the 'object_' into object_riskassessmentdocuement.png
	 */
	public $picto = 'riskassessmentdocument@digiriskdolibarr';

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
	public function RiskAssessmentDocumentFillJSON($object)
	{
		global $conf;

		$user = new User($this->db);
		$user->fetch($this->fk_user_creat);
		$json = array();

		if (!isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) || dol_strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) < 1) {
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', dol_now(), 'chaine', 0, '', $conf->entity);
		}
		if (!isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE) || !strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE)) {
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE', dol_now(), 'chaine', 0, '', $conf->entity);
		}

		// *** JSON FILLING ***
		$json['RiskAssessmentDocument']['nomEntreprise']  = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$json['RiskAssessmentDocument']['dateAudit']      = dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, '%d/%m/%Y', 'tzuser') . ' - ' . dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, '%d/%m/%Y', 'tzuser');
		$json['RiskAssessmentDocument']['emetteurDUER']   = $user->lastname . ' ' . $user->firstname;
		$json['RiskAssessmentDocument']['dateGeneration'] = dol_print_date(strtotime($this->date_creation), '%d/%m/%Y %H:%M:%S', 'tzuser');

		if ($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT > 0) {
			$user->fetch($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);

			$json['RiskAssessmentDocument']['destinataireDUER'] = $user->lastname . ' ' . $user->firstname;
			$json['RiskAssessmentDocument']['telephone'] = $user->office_phone;
			$json['RiskAssessmentDocument']['portable'] = $user->user_mobile;
		}else {
			$json['RiskAssessmentDocument']['destinataireDUER'] = '';
			$json['RiskAssessmentDocument']['telephone'] = '';
			$json['RiskAssessmentDocument']['portable'] = '';
		}

		$json['RiskAssessmentDocument']['methodologie']       = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD;
		$json['RiskAssessmentDocument']['sources']            = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES;
		$json['RiskAssessmentDocument']['remarqueImportante'] = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES;

		$object->json = json_encode($json, JSON_UNESCAPED_UNICODE);

		return $object->json;
	}

	/**
	 * Load dashboard info riskassessmentdocument
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard()
	{
		$arrayLastGenerateDate   = $this->getLastGenerateDate();
		$arrayNextGenerateDate   = $this->getNextGenerateDate();
		$arrayNbDaysBeforeNextGenerateDate = $this->getNbDaysBeforeNextGenerateDate();

		if (empty($arrayNbDaysBeforeNextGenerateDate['nbdaysbeforenextgeneratedate'])) {
			$arrayNbDaysAfterNextGenerateDate = $this->getNbDaysAfterNextGenerateDate();
			$arrayNbDaysBeforeNextGenerateDate = array('nbdaysbeforenextgeneratedate' => 'N/A');
		} else {
			$arrayNbDaysAfterNextGenerateDate = array('nbdaysafternextgeneratedate' => 'N/A');
		}

		$array = array_merge($arrayLastGenerateDate, $arrayNextGenerateDate, $arrayNbDaysBeforeNextGenerateDate, $arrayNbDaysAfterNextGenerateDate);

		return $array;
	}

	/**
	 * Get last riskassessmentdocument generate date.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getLastGenerateDate()
	{
		// Last riskassessmentdocument generate date
		$filter                      = array('customsql' => "t.type='riskassessmentdocument'");
		$riskassessmentdocumentarray = $this->fetchAll('desc', 't.rowid', 1, 0, $filter, 'AND');
		if ( ! empty($riskassessmentdocumentarray) && $riskassessmentdocumentarray > 0 && is_array($riskassessmentdocumentarray)) {
			$riskassessmentdocument = array_shift($riskassessmentdocumentarray);
			$array['lastgeneratedate'] = dol_print_date($riskassessmentdocument->date_creation, 'day');
		} else {
			$array['lastgeneratedate'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get next riskassessmentdocument generate date.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNextGenerateDate()
	{
		// Next riskassessmentdocument generate date
		$filter                      = array('customsql' => "t.type='riskassessmentdocument'");
		$riskassessmentdocumentarray = $this->fetchAll('desc', 't.rowid', 1, 0, $filter, 'AND');
		if ( ! empty($riskassessmentdocumentarray) && $riskassessmentdocumentarray > 0 && is_array($riskassessmentdocumentarray)) {
			$riskassessmentdocument = array_shift($riskassessmentdocumentarray);
			$array['nextgeneratedate'] = dol_print_date(dol_time_plus_duree($riskassessmentdocument->date_creation, '1', 'y'), 'day');
		} else {
			$array['nextgeneratedate'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number days before next riskassessmentdocument generate date.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbDaysBeforeNextGenerateDate()
	{
		// Number days before next riskassessmentdocument generate date
		$arrayNextGenerateDate = $this->getNextGenerateDate();
		if ($arrayNextGenerateDate['nextgeneratedate'] > 0) {
			$array['nbdaysbeforenextgeneratedate'] = num_between_day(dol_now(), dol_stringtotime($arrayNextGenerateDate['nextgeneratedate']));
		} else {
			$array['nbdaysbeforenextgeneratedate'] = 'N/A';
		}
		return $array;
	}

	/**
	 * Get number days after next riskassessmentdocument generate date.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getNbDaysAfterNextGenerateDate()
	{
		// Number days after next riskassessmentdocument generate date
		$arrayNextGenerateDate = $this->getNextGenerateDate();
		if ($arrayNextGenerateDate['nextgeneratedate'] > 0) {
			$array['nbdaysafternextgeneratedate'] = num_between_day(dol_stringtotime($arrayNextGenerateDate['nextgeneratedate']), dol_now());
		} else {
			$array['nbdaysafternextgeneratedate'] = 'N/A';
		}
		return $array;
	}
}
