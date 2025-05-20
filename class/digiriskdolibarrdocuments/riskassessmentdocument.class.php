<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
	 * @var string Module name
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object
	 */
	public $element = 'riskassessmentdocument';

    /**
     * @var string Name of icon for riskassessmentdocument. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'riskassessmentdocument@digiriskdolibarr' if picto is file 'img/object_riskassessmentdocument.png'
     */
    public string $picto = 'fontawesome_fa-file-alt_fas_#d35968';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
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

        $userTmp = new User($this->db);

		if (!isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) || dol_strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE) < 1) {
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', dol_now(), 'chaine', 0, '', $conf->entity);
		}
		if (!isset($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE) || !strlen($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE)) {
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE', dol_now(), 'chaine', 0, '', $conf->entity);
		}

		// *** JSON FILLING ***
		$json['RiskAssessmentDocument']['nomEntreprise']  = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$json['RiskAssessmentDocument']['dateAudit']      = dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE, 'day', 'tzuser') . ' - ' . dol_print_date($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE, 'day', 'tzuser');
		$json['RiskAssessmentDocument']['emetteurDUER']   = $user->lastname . ' ' . $user->firstname;
		$json['RiskAssessmentDocument']['dateGeneration'] = dol_print_date($now, 'dayhour', 'tzuser');

		$userRecipient = json_decode($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT);

        $json['RiskAssessmentDocument']['destinataireDUER'] = '';
        $json['RiskAssessmentDocument']['telephone'] = '';
        $json['RiskAssessmentDocument']['portable'] = '';
		if (is_array($userRecipient) && !empty($userRecipient)) {
			foreach ($userRecipient as $recipientId) {
				$userTmp->fetch($recipientId);

				$json['RiskAssessmentDocument']['destinataireDUER'] .= dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname) . chr(0x0A);
				$json['RiskAssessmentDocument']['telephone']        .= (dol_strlen($userTmp->office_phone) > 0 ? $userTmp->office_phone : '-') . chr(0x0A);
				$json['RiskAssessmentDocument']['portable']         .= (dol_strlen($userTmp->user_mobile) > 0 ? $userTmp->user_mobile : '-') . chr(0x0A);
			}
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

        $arrayGetGenerationDateInfos = $this->getGenerationDateInfos();

        $array['widgets'] = [
            'riskassessmentdocument' => [
                'title'       => $langs->transnoentities('RiskAssessmentDocument'),
                'picto'       => 'fas fa-book',
                'pictoColor'  => '#0D8AFF',
                'label'       => [$langs->transnoentities('NextGenerateDate') ?? '', $langs->transnoentities('LastGenerateDate') ?? '', $langs->transnoentities('DelayGenerateDate') ?? ''],
                'content'     => [$arrayGetGenerationDateInfos['nextgeneratedate'], $arrayGetGenerationDateInfos['lastgeneratedate'], $arrayGetGenerationDateInfos['delaygeneratedate']],
                'picto'       => 'fas fa-info-circle',
                'moreContent' => ['', $arrayGetGenerationDateInfos['moreContent'] ?? ''],
                'widgetName'  => $langs->transnoentities('RiskAssessmentDocument')
            ]
        ];

        return $array;
    }

    /**
     * Get generation date infos
     *
     * @param array $moreParam More param (Object/user/etc)
     *
     * @return array
     * @throws Exception
     */
    public function getGenerationDateInfos(array $moreParam = []): array
    {
        global $langs;

        $filter                  = ['customsql' => 't.type = "' . $this->element . '"' . ($moreParam['filter'] ?? '')];
        $riskAssessmentDocuments = $this->fetchAll('desc', 't.rowid', 1, 0, $filter);
        if (!empty($riskAssessmentDocuments) && is_array($riskAssessmentDocuments)) {
            $riskAssessmentDocument       = array_shift($riskAssessmentDocuments);
            $now                          = dol_now();
            $nextGenerateTimeStamp        = dol_time_plus_duree($riskAssessmentDocument->date_creation, '1', 'y');
            $nextGenerateDate             = dol_print_date($nextGenerateTimeStamp, 'day');
            $lastGenerateDate             = dol_print_date($riskAssessmentDocument->date_creation, 'day');
            $nbDaysAfterNextGenerateDate  = num_between_day($now, $nextGenerateTimeStamp, 1);
            $nbDaysBeforeNextGenerateDate = num_between_day($nextGenerateTimeStamp, $now, 1);

            $array['nextgeneratedate']  = img_picto('', 'fontawesome_fa-calendar_far_#263C5C80', 'class="pictofixedwidth"') . $nextGenerateDate;
            $array['nextgeneratedate'] .= ' ' . (!empty($nbDaysAfterNextGenerateDate) ? $nbDaysAfterNextGenerateDate . ' ' . $langs->transnoentities('Days') : '');
            $array['lastgeneratedate']  = img_picto('', 'fontawesome_fa-calendar_far_#263C5C80', 'class="pictofixedwidth"') . $lastGenerateDate;
            $array['delaygeneratedate'] = !empty($nbDaysBeforeNextGenerateDate) ? $nbDaysBeforeNextGenerateDate . ' ' . $langs->transnoentities('Days') : $langs->transnoentities('NoDelay');
        } else {
            $array['nextgeneratedate']  = 'N/A';
            $array['lastgeneratedate']  = 'N/A';
            $array['delaygeneratedate'] = 'N/A';
        }

        $array['moreContent']  = $this->showUrlOfLastGeneratedDocument($this->module, $this->element, 'odt', 'fontawesome_fa-file-word_far_#0D8AFF', $moreParam['entity'] ?? 1);
        $array['moreContent'] .= $this->showUrlOfLastGeneratedDocument($this->module, $this->element, 'pdf', 'fontawesome_fa-file-pdf_far_#FB4B54', $moreParam['entity'] ?? 1);
        return $array;
    }
}
