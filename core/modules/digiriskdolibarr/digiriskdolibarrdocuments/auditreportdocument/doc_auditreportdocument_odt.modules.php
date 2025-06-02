<?php
/* Copyright (C) 2024-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/auditreportdocument/doc_auditreportdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for audit report document
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_auditreportdocument_odt extends ModeleODTDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'auditreportdocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
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
        global $conf;

        if (!empty($moreParam['dateStart']) && !empty($moreParam['dateEnd'])) {
            $digiriskElement = new DigiriskElement($this->db);

            $loadDigiriskElementInfos = $digiriskElement->loadDigiriskElementInfos($moreParam);

            $startDate    = dol_print_date($moreParam['dateStart'], 'dayrfc');
            $endDate      = dol_print_date($moreParam['dateEnd'], 'dayrfc');
            $filter       = " AND (t.date_creation BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";
            $filterTicket = " AND (t.datec BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";

            $moreParam['filter']          = $filter;
            $moreParam['filterTicket']    = $filterTicket;
            $moreParam['filterEvaluator'] = ' AND t.entity = ' . $conf->entity;

            $moreParam['entity']           = 'current';
            $moreParam['digiriskElements'] = $loadDigiriskElementInfos[$moreParam['entity']]['digiriskElements'];
        }

        return parent::fillTagsLines($odfHandler, $outputLangs, $moreParam);
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
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
    {
        global $conf, $mysoc;

        // Load DigiriskDolibarr libraries
        require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
        require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
        require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';
        require_once __DIR__ . '/../../../../../class/evaluator.class.php';
        require_once __DIR__ . '/../../../../../class/accident.class.php';
        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

        $digiriskElement = new DigiriskElement($this->db);
        $risk            = new Risk($this->db);
        $accident        = new Accident($this->db);
        $userTmp         = new User($this->db);

        $arraySoc                             = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
        $tmpArray['mycompany_photo_fullsize'] = $arraySoc['mycompany_logo'];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = $objectDocument->element . '@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

        if (!empty($moreParam['dateStart']) && !empty($moreParam['dateEnd'])) {
            $startDate    = dol_print_date($moreParam['dateStart'], 'dayrfc');
            $endDate      = dol_print_date($moreParam['dateEnd'], 'dayrfc');
            $filter       = " AND (t.date_creation BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";
            $filterTicket = " AND (t.datec BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";

            $tmpArray['dateAudit'] = dol_print_date($moreParam['dateStart'], 'day') . ' - ' . dol_print_date($moreParam['dateEnd'], 'day');

            $moreParam['filter']          = $filter;
            $moreParam['filterTicket']    =  $filterTicket;
            $moreParam['filterEvaluator'] = ' AND t.entity = ' . $conf->entity;
        }

        if (is_array($moreParam['recipient']) && !empty($moreParam['recipient'])) {
            $userRecipient = $moreParam['recipient'];

            $tmpArray['destinataireDUER'] = '';
            $tmpArray['telephone']        = '';
            $tmpArray['portable']         = '';
            foreach ($userRecipient as $recipientId) {
                $userTmp->fetch($recipientId);

                $tmpArray['destinataireDUER'] .= dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname) . chr(0x0A);
                $tmpArray['telephone']        .= (dol_strlen($userTmp->office_phone) > 0 ? $userTmp->office_phone : '-') . chr(0x0A);
                $tmpArray['portable']         .= (dol_strlen($userTmp->user_mobile) > 0 ? $userTmp->user_mobile : '-') . chr(0x0A);
            }
        }

        $loadDigiriskElementInfos = $digiriskElement->loadDigiriskElementInfos($moreParam);
        $loadRiskInfos            = $risk->loadRiskInfos($moreParam);
        $loadRiskSignInfos        = RiskSign::loadRiskSignInfos($moreParam);
        $loadEvaluatorInfos       = Evaluator::loadEvaluatorInfos($moreParam);
        $loadAccidentInfos        = $accident->loadAccidentInfos($moreParam);
        $loadTicketInfos          = load_ticket_infos($moreParam);

        $tmpArray['nb_new_or_edit_groupments'] = $loadDigiriskElementInfos['current']['nbGroupment'];
        $tmpArray['nb_new_or_edit_workunits']  = $loadDigiriskElementInfos['current']['nbWorkUnit'];
        $tmpArray['nb_new_or_edit_risks']      = count($loadRiskInfos['risks']);
        $tmpArray['nb_new_or_edit_risksigns']  = $loadRiskSignInfos['nbRiskSigns'];
        $tmpArray['nb_new_or_edit_evaluators'] = $loadEvaluatorInfos['nbEvaluators'];
        $tmpArray['nb_new_or_edit_accidents']  = $loadAccidentInfos['nbAccidents'];
        $tmpArray['nb_new_or_edit_tickets']    = $loadTicketInfos['nbTickets'];

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
