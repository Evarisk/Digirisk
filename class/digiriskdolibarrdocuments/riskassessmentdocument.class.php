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
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'riskassessmentdocument';

    /**
     * @var string Name of icon for riskassessmentdocument. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'riskassessmentdocument@digiriskdolibarr' if picto is file 'img/object_riskassessmentdocument.png'
     */
    public string $picto = 'fontawesome_fa-file-alt_fas_#d35968';

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
	 * @param $object
	 * @return false|string
	 */
	public function RiskAssessmentDocumentFillJSON()
	{
		global $conf, $user;

		$json = [];
        $now  = dol_now(); // To change later because we have to use this->date_creation

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
		$json['RiskAssessmentDocument']['dateGeneration'] = dol_print_date($now, '%d/%m/%Y %H:%M:%S', 'tzuser');

		$userRecipient = json_decode($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);
		if (is_array($userRecipient) && !empty($userRecipient)) {
			foreach ($userRecipient as $recipientId) {
				$user->fetch($recipientId);

				$json['RiskAssessmentDocument']['destinataireDUER'] .= $user->lastname . ' ' . $user->firstname . ' - ';
				$json['RiskAssessmentDocument']['telephone'] .= $user->office_phone . ' - ';
				$json['RiskAssessmentDocument']['portable'] .= $user->user_mobile . ' - ';
			}
		} else {
			$json['RiskAssessmentDocument']['destinataireDUER'] = '';
			$json['RiskAssessmentDocument']['telephone'] = '';
			$json['RiskAssessmentDocument']['portable'] = '';
		}

		$json['RiskAssessmentDocument']['methodologie']       = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD;
		$json['RiskAssessmentDocument']['sources']            = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES;
		$json['RiskAssessmentDocument']['remarqueImportante'] = $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES;

		$jsonFormatted = json_encode($json, JSON_UNESCAPED_UNICODE);

		return $jsonFormatted;
	}

    /**
     * Load dashboard info riskassessmentdocument
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $langs;

        $arrayLastGenerateDate             = $this->getLastGenerateDate();
        $arrayNextGenerateDate             = $this->getNextGenerateDate();
        $arrayNbDaysBeforeNextGenerateDate = $this->getNbDaysBeforeNextGenerateDate();

        if (empty($arrayNbDaysBeforeNextGenerateDate['nbdaysbeforenextgeneratedate'])) {
            $arrayNbDaysAfterNextGenerateDate = $this->getNbDaysAfterNextGenerateDate();
            $arrayNbDaysBeforeNextGenerateDate = ['nbdaysbeforenextgeneratedate' => 'N/A'];
        } else {
            $arrayNbDaysAfterNextGenerateDate = ['nbdaysafternextgeneratedate' => 'N/A'];
        }

        $array['widgets'] = [
            'riskassessmentdocument' => [
                'label'      => [$langs->transnoentities('LastGenerateDate') ?? '', $langs->transnoentities('NextGenerateDate') ?? '', $langs->transnoentities('NbDaysBeforeNextGenerateDate') ?? '', $langs->transnoentities('NbDaysAfterNextGenerateDate') ?? ''],
                'content'    => [$arrayLastGenerateDate['lastgeneratedate'] ?? 0, $arrayNextGenerateDate['nextgeneratedate'] ?? 0, $arrayNbDaysBeforeNextGenerateDate['nbdaysbeforenextgeneratedate'] ?? 0, $arrayNbDaysAfterNextGenerateDate['nbdaysafternextgeneratedate'] ?? 0],
                'picto'      => 'fas fa-info-circle',
                'widgetName' => $langs->transnoentities('RiskAssessmentDocument')
            ]
        ];

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
			$array['nbdaysbeforenextgeneratedate'] = num_between_day(dol_now(), dol_stringtotime($arrayNextGenerateDate['nextgeneratedate']), 1);
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
			$array['nbdaysafternextgeneratedate'] = num_between_day(dol_stringtotime($arrayNextGenerateDate['nextgeneratedate']), dol_now(), 1);
		} else {
			$array['nbdaysafternextgeneratedate'] = 'N/A';
		}
		return $array;
	}
}
