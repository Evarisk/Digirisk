<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/riskassessmentdocument/doc_auditreportdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for audit report document
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskelementdocument/modules_digiriskelementdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_auditreportdocument_odt extends ModeleODTDigiriskElementDocument
{
    /**
     * @var array Minimum version of PHP required by module
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public $phpmin = [7, 4];

    /**
     * @var string Dolibarr version of the loaded document
     */
    public $version = 'dolibarr';

    /**
     * @var string Module
     */
    public string $module = 'digiriskdolibarr';

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
     * Return description of a module
     *
     * @param  Translate $langs Lang object to use for output
     * @return string           Description
     */
    public function info(Translate $langs): string
    {
        return parent::info($langs);
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
        if (!empty($moreParam['dateStart']) && $moreParam['dateEnd']) {
            $startDate      = dol_print_date($moreParam['dateStart'], 'dayrfc');
            $endDate        = dol_print_date($moreParam['dateEnd'], 'dayrfc');
            $filter         = " AND (t.date_creation BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";
            $specificFilter = " AND (t.datec BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";

            $moreParam['filter']         = $filter;
            $moreParam['specificFilter'] = $specificFilter;
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
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $mysoc;

        $digiriskElement = new DigiriskElement($this->db);
        $risk            = new Risk($this->db);
        $riskSign        = new RiskSign($this->db);
        $evaluator       = new Evaluator($this->db);
        $accident        = new Accident($this->db);

        $arraySoc                             = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
        $tmpArray['mycompany_photo_fullsize'] = $arraySoc['mycompany_logo'];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = 'riskassessmentdocument@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

        if (!empty($moreParam['dateStart']) && $moreParam['dateEnd']) {
            $startDate      = dol_print_date($moreParam['dateStart'], 'dayrfc');
            $endDate        = dol_print_date($moreParam['dateEnd'], 'dayrfc');
            $filter         = " AND (t.date_creation BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";
            $specificFilter = " AND (t.datec BETWEEN '$startDate' AND '$endDate' OR t.tms BETWEEN '$startDate' AND '$endDate')";

            $moreParam['filter']         = $filter;
            $moreParam['specificFilter'] = $specificFilter;
        }

        $groupments       = [];
        $workUnits        = [];
        $digiriskElements = $digiriskElement->getActiveDigiriskElements();
        if (is_array($digiriskElements) && !empty($digiriskElements)) {
            foreach ($digiriskElements as $digiriskElement) {
                if ($digiriskElement->element_type == 'groupment') {
                    $groupments[] = $digiriskElement;
                } else {
                    $workUnits[] = $digiriskElement;
                }
            }
        }

        $risks      = $risk->fetchRisksOrderedByCotation(0, true, getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS'), getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS'), $moreParam);;
        $riskSigns  = $riskSign->fetchAll('', '', 0, 0, ['customsql' => 'status = ' . RiskSign::STATUS_VALIDATED . $moreParam['filter']]);
        $evaluators = $evaluator->fetchAll('', '', 0, 0, ['customsql' => 'status = ' . Evaluator::STATUS_VALIDATED . $moreParam['filter']]);
        $accidents  = $accident->fetchAll('', '', 0, 0, ['customsql' => 'status >= ' . Accident::STATUS_VALIDATED . $moreParam['filter']]);
        $tickets    = [];
        if (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
            $tickets = saturne_fetch_all_object_type('Ticket', '', '', 0, 0,  ['customsql' => 'eft.digiriskdolibarr_ticket_service > 0' . $moreParam['specificFilter']], 'AND', true);
        }

        $tmpArray['nb_new_or_edit_groupments'] = is_array($groupments) && !empty($groupments) ? count($groupments) : '';
        $tmpArray['nb_new_or_edit_workunits']  = is_array($workUnits) && !empty($workUnits) ? count($workUnits) : '';
        $tmpArray['nb_new_or_edit_risks']      = is_array($risks) && !empty($risks) ? count($risks) : '';
        $tmpArray['nb_new_or_edit_risksigns']  = is_array($riskSigns) && !empty($riskSigns) ? count($riskSigns) : '';
        $tmpArray['nb_new_or_edit_evaluators'] = is_array($evaluators) && !empty($evaluators) ? count($evaluators) : '';
        $tmpArray['nb_new_or_edit_accidents']  = is_array($accidents) && !empty($accidents) ? count($accidents) : '';
        $tmpArray['nb_new_or_edit_tickets']    = is_array($tickets) && !empty($tickets) ? count($tickets) : '';

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
