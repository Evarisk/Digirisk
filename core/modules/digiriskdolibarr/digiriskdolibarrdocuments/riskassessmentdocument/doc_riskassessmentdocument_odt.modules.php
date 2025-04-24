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
 * or see https://www.gnu.org/
 */

/**
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/doc_riskassessmentdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_riskassessmentdocument_odt extends SaturneDocumentModel
{
	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'riskassessmentdocument';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->document_type);
	}

	/**
	 * Return description of a module.
	 *
	 * @param Translate $langs Lang object to use for output.
	 * @return string           Description.
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
	}

    /**
     * Load risk assessment document infos
     * @throws Exception
     */
    private function loadRiskAssessmentDocumentInfos(): array
    {
        $digiriskElement = new DigiriskElement($this->db);
        $risk            = new Risk($this->db);

        $currentDigiriskElements = $digiriskElement->fetchDigiriskElementFlat(0, [], 'current');
        $sharedDigiriskElements  = $digiriskElement->fetchDigiriskElementFlat(0, [], 'shared');

        $select          = 'r.fk_element, ';
        $select         .= 'SUM(CASE WHEN t.cotation < 48 THEN 1 ELSE 0 END) AS nb_risk_grey, ';
        $select         .= 'SUM(CASE WHEN t.cotation >= 48 AND t.cotation < 51 THEN 1 ELSE 0 END) AS nb_risk_orange, ';
        $select         .= 'SUM(CASE WHEN t.cotation >= 51 AND t.cotation < 80 THEN 1 ELSE 0 END) AS nb_risk_red, ';
        $select         .= 'SUM(CASE WHEN t.cotation >= 80 THEN 1 ELSE 0 END) AS nb_risk_black, ';
        $select         .= 'SUM(t.cotation) AS quotation_totale';
        $filter          = ' AND r.fk_element NOT IN ' . $digiriskElement->getTrashExclusionSqlFilter();
        $join            = ' LEFT JOIN ' . MAIN_DB_PREFIX . $risk->table_element . ' as r ON r.rowid = t.fk_risk';
        $groupBy         = ' GROUP BY r.fk_element';
        $riskAssessments = saturne_fetch_all_object_type('RiskAssessment', 'DESC', 'quotation_totale', 0, 0, ['customsql' => 't.status = ' . RiskAssessment::STATUS_VALIDATED . $filter], 'AND', false, true, false, $join, [], $select, $groupBy);

        $filter  = ['customsql' => 't.fk_project = ' . getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_PROJECT') . ' AND eft.digiriskdolibarr_ticket_service > 0'];
        $tickets = saturne_fetch_all_object_type('Ticket', '', '', 0, 0,  $filter, 'AND', true);

        return [
            'currentDigiriskElements' => $currentDigiriskElements,
            'sharedDigiriskElements'  => $sharedDigiriskElements,
            'riskAssessments'         => $riskAssessments,
            'tickets'                 => $tickets
        ];
    }

    /**
     * Set digirisk elements segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, loadRiskAssessmentDocumentInfos)
     *
     * @throws OdfException
     * @throws Exception
     */
    private function setDigiriskElementsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $digiriskElements = $this->loadRiskAssessmentDocumentInfos()[$moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat']];
            if (!is_array($digiriskElements) || empty($digiriskElements)) {
                $tmpArray['nomElement'] = '';

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($digiriskElements as $digiriskElement) {
                $depthHyphens           = str_repeat('- ', $digiriskElement['depth']);
                $tmpArray['nomElement'] = $depthHyphens . 'S' . $digiriskElement['object']->entity . ' - ' . $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label;

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set risk assessments segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, loadRiskAssessmentDocumentInfos)
     *
     * @throws OdfException
     * @throws Exception
     */
    private function setRiskAssessmentsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $loadRiskAssessmentDocumentInfos = $this->loadRiskAssessmentDocumentInfos();
            $digiriskElements                = $loadRiskAssessmentDocumentInfos[$moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat']];
            $riskAssessments                 = $loadRiskAssessmentDocumentInfos['riskAssessments'];

            if (!is_array($digiriskElements) || empty($digiriskElements) || !is_array($riskAssessments) || empty($riskAssessments)) {
                $tmpArray['nomElement']      = '';
                $tmpArray['description']     = '';
                $tmpArray['quotationTotale'] = '';
                $tmpArray['NbRiskBlack']     = '';
                $tmpArray['NbRiskRed']       = '';
                $tmpArray['NbRiskOrange']    = '';
                $tmpArray['NbRiskGrey']      = '';

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            // Order digirisk elements by risk assessment
            $orderedDigiriskElements = [];
            $digiriskElementIds      = array_keys($riskAssessments);
            foreach ($digiriskElementIds as $digiriskElementId) {
                if (isset($digiriskElements[$digiriskElementId])) {
                    $orderedDigiriskElements[$digiriskElementId] = $digiriskElements[$digiriskElementId];
                }
            }

            foreach ($orderedDigiriskElements as $orderedDigiriskElementId => $orderedDigiriskElement) {
                $tmpArray['nomElement']      = 'S' . $orderedDigiriskElement['object']->entity . ' - ' . $orderedDigiriskElement['object']->ref . ' - ' . $orderedDigiriskElement['object']->label;
                $tmpArray['description']     = $orderedDigiriskElement['object']->description;
                $tmpArray['quotationTotale'] = $riskAssessments[$orderedDigiriskElementId]->quotation_totale ?? 0;
                $tmpArray['NbRiskBlack']     = $riskAssessments[$orderedDigiriskElementId]->nb_risk_black ?? 0;
                $tmpArray['NbRiskRed']       = $riskAssessments[$orderedDigiriskElementId]->nb_risk_red ?? 0;
                $tmpArray['NbRiskOrange']    = $riskAssessments[$orderedDigiriskElementId]->nb_risk_orange ?? 0;
                $tmpArray['NbRiskGrey']      = $riskAssessments[$orderedDigiriskElementId]->nb_risk_grey ?? 0;

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set tickets segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     *
     * @throws OdfException
     * @throws Exception
     */
    private function setTicketsSegment(Odf $odfHandler, Translate $outputLangs): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('tickets');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $loadRiskAssessmentDocumentInfos = $this->loadRiskAssessmentDocumentInfos();
            $tickets                         = $loadRiskAssessmentDocumentInfos['tickets'];
            if (!is_array($tickets) || empty($tickets)) {
                $tmpArray = [
                    'refticket'                 => '',
                    'categories'                => '',
                    'creation_date'             => '',
                    'subject'                   => '',
                    'message'                   => '',
                    'progress'                  => '',
                    'digiriskelement_ref_label' => '',
                    'status'                    => '',
                ];

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            $category = new Categorie($this->db);

            foreach ($tickets as $ticket) {
                $categories      = $category->containing($ticket->id, Categorie::TYPE_TICKET);
                $digiriskElement = $loadRiskAssessmentDocumentInfos['currentDigiriskElements'][$ticket->array_options['options_digiriskdolibarr_ticket_service']]['object'];

                $tmpArray['refticket']                 = $ticket->ref;
                $tmpArray['categories']                = !empty($categories) ? implode(', ', array_map(fn($cat) => $cat->label, $categories)) : '';
                $tmpArray['creation_date']             = dol_print_date($ticket->datec, 'dayhoursec');
                $tmpArray['subject']                   = $ticket->subject;
                $tmpArray['message']                   = $ticket->message;
                $tmpArray['progress']                  = ($ticket->progress ?: 0) . ' %';
                $tmpArray['digiriskelement_ref_label'] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
                $tmpArray['status']                    = $ticket->getLibStatut();

                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Fill all odt tags for segments lines
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @return int                    1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        // Replace tags of lines
        try {
            global $conf;

            $objectDocument = $moreParam['objectDocument'];

            $digiriskelementobject = new DigiriskElement($this->db);
            $risk                  = new Risk($this->db);

            $moreParam['segmentName']                                                 = 'elementParHierarchie';
            $moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat'] = 'currentDigiriskElements';
            $this->setDigiriskElementsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['segmentName']                                                 = 'elementParHierarchie2';
            $moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat'] = 'sharedDigiriskElements';
            $this->setDigiriskElementsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['segmentName']                                                 = 'risqueFiche';
            $moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat'] = 'currentDigiriskElements';
            $this->setRiskAssessmentsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['segmentName']                                                 = 'risqueFiche2';
            $moreParam['loadRiskAssessmentDocumentInfos']['fetchDigiriskElementFlat'] = 'sharedDigiriskElements';
            $this->setRiskAssessmentsSegment($odfHandler, $outputLangs, $moreParam);

            //Fill risks data
            $moreParam['filterRisk']    = ' AND t.type = "risk"';
            $tmpArray['showSharedRisk'] = false;
            $risks                      = $risk->fetchRisksOrderedByCotation(0, true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, 0, $moreParam);
            $activeDigiriskElements     = $digiriskelementobject->getActiveDigiriskElements();
            $objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, $tmpArray, '', $risks, $activeDigiriskElements, false);
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS')) {
                $tmpArray['showSharedRisk'] = true;
                $risks                      = $risk->fetchRisksOrderedByCotation(0, true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS'), $moreParam);
                $objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, $tmpArray, '', $risks, $activeDigiriskElements, true);
            }
            $odfHandler->setVars('showSharedRisk', $tmpArray['showSharedRisk'], true, 'UTF-8');

            $this->setTicketsSegment($odfHandler, $outputLangs);
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }
        return 0;
    }

    /**
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf, $mysoc;

        $fileArray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $objectDocument->element . '/siteplans', 'files', 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
        if (is_array($fileArray) && !empty($fileArray)) {
            $sitePlans                    = array_shift($fileArray);
            $thumb_name                   = saturne_get_thumb_name($sitePlans['name']);
            $tmpArray['photo_site_plans'] = $sitePlans['path'] . '/thumbs/' . $thumb_name;
        } else {
            $noPhoto                      = '/public/theme/common/nophoto.png';
            $tmpArray['photo_site_plans'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $arraySoc                             = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
        $tmpArray['mycompany_photo_fullsize'] = $arraySoc['mycompany_logo'];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = $objectDocument->element . '@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

        $moreParam['tmparray']         = $tmpArray;
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['hideTemplateName'] = 1;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
