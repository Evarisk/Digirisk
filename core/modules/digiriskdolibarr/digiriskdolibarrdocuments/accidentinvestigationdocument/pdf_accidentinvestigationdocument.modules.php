<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/accidentinvestigationdocument/pdf_accidentinvestigationdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to generate the accident investigation document as a native PDF.
 *          Carries the same content as the ODT model, so both stay interchangeable.
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';
// saturne_get_thumb_name() falls back on saturne_vignette() when the thumb is missing
require_once __DIR__ . '/../../../../../../saturne/lib/dolibarr.lib.php';

/**
 * Class to build the accident investigation document as a PDF.
 */
class pdf_accidentinvestigationdocument extends SaturneDocumentModel
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Model name
     */
    public $name;

    /**
     * @var string Model description (short text)
     */
    public $description;

    /**
     * @var string Module
     */
    public string $module = 'digiriskdolibarr';

    /**
     * @var string Document type
     */
    public string $document_type = 'accidentinvestigationdocument';

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'accidentinvestigationdocument';
        $this->description = $langs->trans('AccidentInvestigationDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->height      = 6;
        $this->orientation = 'P';
        $this->version     = '1.0.0';
    }

    /**
     * Add a page when the block about to be written would not fit on the current one.
     *
     * @param  TCPDF $pdf          PDF handler
     * @param  float $neededHeight Height the next block needs
     * @return void
     */
    public function checkPageBreak($pdf, float $neededHeight)
    {
        if ($pdf->GetY() + $neededHeight + $pdf->getBreakMargin() > $pdf->getPageHeight()) {
            $pdf->AddPage();
            $pdf->SetY($this->marge_haute);
        }
    }

    /**
     * Draw one titled table.
     *
     * Rows are arrays of cells, a cell being either a string or ['text' => string, 'label' => 1]
     * for the bold left column. Same contract as the ticket document model.
     *
     * @param  TCPDF $pdf             PDF handler
     * @param  array $table           Table definition: title, widths, rows
     * @param  float $tableWidth      Total width of the table
     * @param  float $lineHeight      Height of a single line
     * @param  float $defaultFontSize Font size of the document
     * @return void
     */
    public function drawTable($pdf, array $table, float $tableWidth, float $lineHeight, float $defaultFontSize)
    {
        global $langs;

        if (!isset($table['rows'], $table['widths']) || empty($table['rows'])) {
            return;
        }

        $this->checkPageBreak($pdf, $lineHeight * (count($table['rows']) + 1));

        if (!empty($table['title'])) {
            $pdf->SetFont('', 'B', $defaultFontSize);
            $pdf->SetFillColor(211, 89, 104);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetX($this->marge_gauche);
            $pdf->Cell($tableWidth, 7, $table['title'], 1, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
        }

        $widths = $table['widths'];

        foreach ($table['rows'] as $cells) {
            // A row is as tall as its tallest cell, otherwise a long text overlaps the next line
            $maxHeight = $lineHeight;
            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                $cell   = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                $height = $pdf->getNumLines((string) $cell, $widths[$index]) * $lineHeight;
                if ($height > $maxHeight) {
                    $maxHeight = $height;
                }
            }

            $this->checkPageBreak($pdf, $maxHeight);
            $pdf->SetX($this->marge_gauche);

            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                $isLabel = is_array($cellData) && !empty($cellData['label']);
                $cell    = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                if (!dol_strlen((string) $cell)) {
                    $cell = $langs->transnoentities('NoData');
                }

                $pdf->SetFont('', $isLabel ? 'B' : '', $defaultFontSize - 1);
                if ($isLabel) {
                    $pdf->SetFillColor(245, 245, 245);
                }

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell($widths[$index], $maxHeight, (string) $cell, 1, 'L', $isLabel, 0, $x, $y, true, 0, false, true, $maxHeight, 'M');
                $pdf->SetXY($x + $widths[$index], $y);
            }
            $pdf->Ln($maxHeight);
        }

        $pdf->Ln(3);
    }

    /**
     * Show the top header of the page: company logo, document title and object ref.
     *
     * @param  TCPDF     $pdf             PDF handler
     * @param  object    $object          Accident investigation
     * @param  Translate $outputLangs     Lang object for output
     * @param  float     $defaultFontSize Font size of the document
     * @return void
     */
    protected function _pagehead(&$pdf, $object, $outputLangs, $defaultFontSize)
    {
        global $mysoc;

        $posX = $this->marge_gauche;
        $posY = $this->marge_haute;

        if (!getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO') && $mysoc->logo) {
            $logoDir = getMultidirOutput($object, 'mycompany') ?: '';
            $logo    = getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') ? $logoDir . '/logos/' . $mysoc->logo : $logoDir . '/logos/thumbs/' . $mysoc->logo_small;
            if (is_readable($logo)) {
                $pdf->Image($logo, $posX, $posY, 0, pdf_getHeightForLogo($logo));
            }
        }

        $pdf->SetFont('', 'B', $defaultFontSize + 3);
        $pdf->SetXY($this->marge_gauche, $posY + 14);
        $pdf->Cell(0, 8, $outputLangs->transnoentities('AccidentInvestigationDocument'), 0, 1, 'C');

        $pdf->SetFont('', '', $defaultFontSize);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell(0, 6, $object->ref . (dol_strlen($object->label) ? ' - ' . $object->label : ''), 0, 1, 'C');

        $pdf->Ln(2);
    }

    /**
     * Show the bottom footer of the page.
     *
     * @param  TCPDF     $pdf             PDF handler
     * @param  object    $object          Accident investigation
     * @param  Translate $outputLangs     Lang object for output
     * @param  float     $defaultFontSize Font size of the document
     * @return void
     */
    protected function _pagefooter($pdf, $object, $outputLangs, $defaultFontSize)
    {
        $pdf->SetFont('', '', $defaultFontSize - 2);
        $pdf->SetTextColor(128, 128, 128);

        $leftText  = $object->ref . ' - ' . $outputLangs->transnoentities('AccidentInvestigationDocumentGeneratedAt') . ' ' . dol_print_date(dol_now(), 'dayhoursec', 'tzuser');
        // TCPDI ne fournit pas les alias de pagination de TCPDF, on numerote a la main
        $rightText = 'Version ' . $this->version . ' - Page ' . $pdf->getPage() . '/' . $pdf->getNumPages();

        $pdf->SetY($pdf->getPageHeight() - $this->marge_basse - 6);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell(0, 5, $leftText, 0, 0, 'L');
        $pdf->Cell(0, 5, $rightText, 0, 0, 'R');

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Build the tables of the document, in the order of the ODT model.
     *
     * @param  object    $object           Accident investigation
     * @param  Translate $outputLangs      Lang object for output
     * @param  array     $data             Data gathered by write_file
     * @param  float     $tableWidth       Total width of a table
     * @return array                       List of table definitions
     */
    protected function getTables($object, Translate $outputLangs, array $data, float $tableWidth): array
    {
        $labelWidth = $tableWidth * 0.35;
        $valueWidth = $tableWidth - $labelWidth;
        $widths     = [$labelWidth, $valueWidth];

        $tables = [];

        $tables[] = [
            'title'  => $outputLangs->transnoentities('AccidentInvestigation'),
            'widths' => $widths,
            'rows'   => [
                [['text' => $outputLangs->transnoentities('DateStart'), 'label' => 1], $data['investigation_date_start']],
                [['text' => $outputLangs->transnoentities('DateEnd'), 'label' => 1], $data['investigation_date_end']],
                [['text' => $outputLangs->transnoentities('SeniorityInPosition'), 'label' => 1], $data['seniority_in_position']],
                [['text' => $outputLangs->transnoentities('Attendants'), 'label' => 1], $data['attendants_number']],
            ],
        ];

        $tables[] = [
            'title'  => $outputLangs->transnoentities('Accident'),
            'widths' => $widths,
            'rows'   => [
                [['text' => $outputLangs->transnoentities('AccidentDate'), 'label' => 1], $data['accident_date'] . ' ' . $data['accident_hour'] . ' (' . $data['accident_day'] . ')'],
                [['text' => $outputLangs->transnoentities('AccidentLocation'), 'label' => 1], $data['gp_ut']],
                [['text' => $outputLangs->transnoentities('RelativeLocation'), 'label' => 1], $data['relative_location']],
            ],
        ];

        $tables[] = [
            'title'  => $outputLangs->transnoentities('Victim'),
            'widths' => $widths,
            'rows'   => [
                [['text' => $outputLangs->transnoentities('Lastname'), 'label' => 1], $data['victim_lastname']],
                [['text' => $outputLangs->transnoentities('Firstname'), 'label' => 1], $data['victim_firstname']],
                [['text' => $outputLangs->transnoentities('DateEmployment'), 'label' => 1], $data['victim_date_employment']],
                [['text' => $outputLangs->transnoentities('VictimSkills'), 'label' => 1], $data['victim_skills']],
            ],
        ];

        $tables[] = [
            'title'  => $outputLangs->transnoentities('Circumstances'),
            'widths' => $widths,
            'rows'   => [
                [['text' => $outputLangs->transnoentities('Circumstances'), 'label' => 1], $data['circumstances']],
                [['text' => $outputLangs->transnoentities('CollectiveEquipment'), 'label' => 1], $data['collective_equipment']],
                [['text' => $outputLangs->transnoentities('IndividualEquipment'), 'label' => 1], $data['individual_equipment']],
                [['text' => $outputLangs->transnoentities('NotePublic'), 'label' => 1], $data['public_note']],
            ],
        ];

        $tables[] = [
            'title'  => $outputLangs->transnoentities('ActionsTab'),
            'widths' => $widths,
            'rows'   => [
                [['text' => $outputLangs->transnoentities('CurativeActions'), 'label' => 1], $data['total_curative_action']],
                [['text' => $outputLangs->transnoentities('PreventiveActions'), 'label' => 1], $data['total_preventive_action']],
                [['text' => $outputLangs->transnoentities('TotalPlannedBudget'), 'label' => 1], $data['total_planned_budget']],
            ],
        ];

        if (!empty($data['attendants'])) {
            $attendantRows = [];
            foreach ($data['attendants'] as $attendant) {
                $attendantRows[] = [['text' => $attendant['role'], 'label' => 1], $attendant['name']];
            }
            $tables[] = [
                'title'  => $outputLangs->transnoentities('Attendants'),
                'widths' => $widths,
                'rows'   => $attendantRows,
            ];
        }

        return $tables;
    }

    /**
     * Function to build the document on disk.
     *
     * @param  SaturneDocuments $objectDocument  Object source to build the document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Unused, this model owns its layout
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <= 0 if KO
     * @throws Exception
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hideDetails = 0, $hideDesc = 0, $hideRef = 0, $moreParam = []): int
    {
        global $action, $conf, $hookmanager, $langs, $user;

        require_once __DIR__ . '/../../../../../class/accident.class.php';
        require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
        require_once __DIR__ . '/../../../../../class/digiriskstandard.class.php';
        // get_recursive_task_budget() lives there, the ODT flow gets it from the card
        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_accidentinvestigation.lib.php';
        require_once __DIR__ . '/../../../../../../saturne/class/task/saturnetask.class.php';

        $object = $moreParam['object'];

        // Lastname / Firstname live in companies, the action labels in projects: without this the
        // document would print the raw keys
        $outputLangs->loadLangs(['companies', 'projects', 'other', 'digiriskdolibarr@digiriskdolibarr']);

        $moreParam['hideTemplateName'] = 1;
        $object->module                = $this->module;

        // buildDocumentFilename rend -1 en cas d'echec, sinon le chemin du fichier. Comparer ce
        // chemin a 0 le compare en fait a la chaine '0' : sous Linux il commence par '/', qui est
        // inferieur a '0', et la generation echouerait systematiquement
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreParam);
        if (!is_string($file) || empty($file)) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        $data = $this->gatherData($object, $outputLangs);

        // Init PDF
        $pdf             = pdf_getInstance($this->format);
        $defaultFontSize = pdf_getPDFFontSize($outputLangs) + 1;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetTitle($outputLangs->convToOutputCharset($object->ref));
        $pdf->SetSubject($outputLangs->transnoentities('AccidentInvestigationDocument'));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, $this->marge_basse);
        $pdf->SetAutoPageBreak(1, $this->marge_basse);

        $pdf->AddPage();
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $defaultFontSize);

        $tableWidth = $pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite;

        $this->_pagehead($pdf, $object, $outputLangs, $defaultFontSize);

        foreach ($this->getTables($object, $outputLangs, $data, $tableWidth) as $table) {
            $this->drawTable($pdf, $table, $tableWidth, $this->height, $defaultFontSize);
        }

        // Causality tree: the drawing is the heart of the investigation, it closes the document
        if (!empty($data['causality_tree_photo']) && is_readable($data['causality_tree_photo'])) {
            $this->checkPageBreak($pdf, 90);
            $pdf->SetFont('', 'B', $defaultFontSize);
            $pdf->SetFillColor(211, 89, 104);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetX($this->marge_gauche);
            $pdf->Cell($tableWidth, 7, $outputLangs->transnoentities('CausalityTree'), 1, 1, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(2);
            // Width only: TCPDF keeps the ratio and the drawing is often wider than tall
            $pdf->Image($data['causality_tree_photo'], $this->marge_gauche, $pdf->GetY(), $tableWidth);
        }

        $this->_pagefooter($pdf, $object, $outputLangs, $defaultFontSize);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = $exception->getMessage();
            dol_syslog($this->error, LOG_ERR);
            return -1;
        }

        $this->result = ['fullpath' => $file];

        return 1;
    }

    /**
     * Gather everything the document shows, with the same rules as the ODT model.
     *
     * @param  object    $object      Accident investigation
     * @param  Translate $outputLangs Lang object for output
     * @return array                  Data indexed like the ODT tags
     */
    protected function gatherData($object, Translate $outputLangs): array
    {
        global $conf;

        $accident         = new Accident($this->db);
        $accidentMetadata = new AccidentMetaData($this->db);
        $signatory        = new SaturneSignature($this->db, $this->module, $object->element);
        $now              = dol_now();

        $accident->fetch($object->fk_accident);
        $accidentMetadata->fetch(0, '', 'AND status = 1 AND fk_accident = ' . ((int) $accident->id));
        $victim = $accident->getUserVictim();

        $actionTask           = saturne_fetch_all_object_type('SaturneTask', '', '', 2, 0, ['customsql' => 'fk_task_parent = ' . ((int) $object->fk_task)]);
        $curativeActionTask   = is_array($actionTask) ? array_shift($actionTask) : null;
        $preventiveActionTask = is_array($actionTask) ? array_pop($actionTask) : null;
        $totalCATask          = is_object($curativeActionTask) ? $curativeActionTask->hasChildren() : 0;
        $totalPATask          = is_object($preventiveActionTask) ? $preventiveActionTask->hasChildren() : 0;
        $totalBudget          = get_recursive_task_budget((int) $object->fk_task);

        $data = [
            'investigation_date_start' => $object->date_start ? dol_print_date($object->date_start, 'dayhour', 'tzuser') : '',
            'investigation_date_end'   => $object->date_end ? dol_print_date($object->date_end, 'dayhour', 'tzuser') : '',
            'seniority_in_position'    => $object->seniority_in_position,
            'total_curative_action'    => $totalCATask > 0 ? $totalCATask : '0',
            'total_preventive_action'  => $totalPATask > 0 ? $totalPATask : '0',
            'total_planned_budget'     => price($totalBudget, 0, '', 1, -1, -1, 'auto'),
            'victim_skills'            => $object->victim_skills,
            'collective_equipment'     => $object->collective_equipment,
            'individual_equipment'     => $object->individual_equipment,
            'circumstances'            => $object->circumstances,
            'public_note'              => $object->note_public,
            'relative_location'        => $accidentMetadata->relative_location,
            'accident_date'            => dol_print_date($accident->accident_date, 'day'),
            'accident_hour'            => dol_print_date($accident->accident_date, 'hour'),
            'accident_day'             => dol_print_date($accident->accident_date, '%A'),
            'attendants'               => [],
        ];

        // Attendants, listed with their role like the ODT segment does
        $signatoriesArray             = $signatory->fetchSignatories($object->id, $object->element);
        $data['attendants_number']    = (is_array($signatoriesArray) && !empty($signatoriesArray)) ? (string) count($signatoriesArray) : '0';
        if (is_array($signatoriesArray)) {
            foreach ($signatoriesArray as $signatoryItem) {
                $data['attendants'][] = [
                    'role' => $outputLangs->transnoentities($signatoryItem->role),
                    'name' => dol_strtoupper($signatoryItem->lastname) . ' ' . ucfirst($signatoryItem->firstname),
                ];
            }
        }

        if (is_object($victim) && $victim->id > 0) {
            $data['victim_lastname']  = dol_strtoupper($victim->lastname);
            $data['victim_firstname'] = ucfirst($victim->firstname);
            if ($victim->dateemployment > 0) {
                $daysEmployee                   = round(dol_time_plus_duree($now, -$victim->dateemployment, 's') / 60 / 60 / 24);
                $data['victim_date_employment'] = dol_print_date($victim->dateemployment, 'day', 'tzuser') . ' - ' . $daysEmployee . ' ' . ($daysEmployee <= 1 ? $outputLangs->trans('Day') : $outputLangs->trans('Days'));
            } else {
                $data['victim_date_employment'] = '';
            }
        } else {
            $data['victim_lastname']        = '';
            $data['victim_firstname']       = '';
            $data['victim_date_employment'] = '';
        }

        // Where the accident happened: inside a GP/UT, at another company, or a free location
        if ($accident->external_accident == 1) {
            if ($accident->fk_element > 0) {
                $element = new DigiriskElement($this->db);
                $element->fetch($accident->fk_element);
                $data['gp_ut'] = $element->ref . ' - ' . $element->label;
            } else {
                $element = new DigiriskStandard($this->db);
                $element->fetch($accident->fk_standard);
                $data['gp_ut'] = $element->ref . ' - ' . getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
            }
        } elseif ($accident->external_accident == 2) {
            $societe = new Societe($this->db);
            $societe->fetch($accident->fk_soc);
            $data['gp_ut'] = $societe->name;
        } else {
            // Champ saisi en WYSIWYG affiche dans une cellule de tableau non HTML : on l'aplatit
            $data['gp_ut'] = dol_string_nohtmltag(str_replace(['</p>', '</li>'], '<br>', $accident->accident_location), 0);
        }

        $data['causality_tree_photo'] = '';
        if (dol_strlen($object->causality_tree)) {
            $photoDir  = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/accidentinvestigation/' . $object->ref . '/causality_tree/';
            $thumbName = saturne_get_thumb_name($object->causality_tree, 'medium', $photoDir);
            if (is_readable($photoDir . 'thumbs/' . $thumbName)) {
                $data['causality_tree_photo'] = $photoDir . 'thumbs/' . $thumbName;
            } elseif (is_readable($photoDir . $object->causality_tree)) {
                $data['causality_tree_photo'] = $photoDir . $object->causality_tree;
            }
        }

        return $data;
    }
}
