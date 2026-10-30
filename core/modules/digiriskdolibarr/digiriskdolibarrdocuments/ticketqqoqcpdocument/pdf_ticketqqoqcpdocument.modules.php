<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/ticketqqoqcpdocument/pdf_ticketqqoqcpdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to generate the QQOQCP document of a ticket (#5549)
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load Saturne libraries
dol_include_once('/saturne/core/modules/saturne/modules_saturne.php');

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

/**
 * Class to build the QQOQCCP document of a ticket: What, Who, Where, When, How, How much and Why of the problem
 */
class pdf_ticketqqoqcpdocument extends SaturneDocumentModel
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
    public string $document_type = 'ticketqqoqcpdocument';

    /**
     * @var string Page orientation
     */
    public $orientation;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'ticketqqoqcpdocument';
        $this->description = $langs->trans('TicketQqoqcpDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->height      = 8;
        $this->orientation = 'P';
        $this->version     = '1.0.0';
    }

    /**
     * Show the top of the first page: logo of the company and link to Digirisk
     *
     * @param  TCPDF     $pdf             PDF handler
     * @param  Ticket    $object          Ticket of the document
     * @param  Translate $outputLangs     Lang object for output
     * @param  float     $defaultFontSize Font size of the document
     * @return void
     */
    protected function _pagehead($pdf, $object, Translate $outputLangs, float $defaultFontSize)
    {
        global $mysoc;

        $posX = $this->marge_gauche;
        $posY = $this->marge_haute;

        if (!getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO') && $mysoc->logo) {
            $logoDir = getMultidirOutput($object, 'mycompany');
            $logo    = $logoDir . (getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') ? '/logos/' . $mysoc->logo : '/logos/thumbs/' . $mysoc->logo_small);
            if (is_readable($logo)) {
                $pdf->Image($logo, $posX, $posY, 0, pdf_getHeightForLogo($logo));
            }
        }

        $pdf->setFontSize($defaultFontSize - 2);
        $htmlLink = 'www.digirisk.com';
        $pdf->setXY($pdf->GetPageWidth() - $this->marge_droite - $pdf->GetStringWidth($htmlLink), $posY);
        $pdf->writeHTML('<a href="https://www.digirisk.com/">' . $htmlLink . '</a>', true, false, false, false, 'R');
    }

    /**
     * Show the footer of a page: ticket, date of generation and page number
     *
     * @param  TCPDF     $pdf             PDF handler
     * @param  Ticket    $object          Ticket of the document
     * @param  Translate $outputLangs     Lang object for output
     * @param  float     $defaultFontSize Font size of the document
     * @return void
     */
    protected function _pagefooter($pdf, $object, Translate $outputLangs, float $defaultFontSize)
    {
        $pdf->setFontSize($defaultFontSize - 2);

        $leftText  = $object->ref . ' - ' . $outputLangs->transnoentities('QQOQCP') . ' - ' . $outputLangs->transnoentities('GeneratedTicketDocumentDate') . ' ' . dol_print_date(dol_now(), 'dayhoursec', 'tzuser');
        // getNumPages() is the page count, not the current page: the footer is written once per page at the very end
        $rightText = 'Version ' . $this->version . ' - Page ' . $pdf->getPage() . '/' . $pdf->getNumPages();

        $pdf->SetY($pdf->getPageHeight() - $this->marge_basse - $this->height);
        $pdf->MultiCell($pdf->GetStringWidth($leftText) + 5, $this->height, $leftText, 0, 'L', false, 0, $this->marge_gauche);
        $pdf->MultiCell($pdf->GetStringWidth($rightText) + 5, $this->height, $rightText, 0, 'R', false, 0, $pdf->GetPageWidth() - $this->marge_droite - $pdf->GetStringWidth($rightText) - 5);
    }

    /**
     * Tables of the document: the ticket, then the six questions
     *
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $pageWidth   Width of the page inside its margins
     * @return array                  Tables for drawTable()
     */
    protected function getQqoqcpTables($object, Translate $outputLangs, float $pageWidth): array
    {
        $projectLabel = '';
        if (!empty($object->fk_project)) {
            $project = new Project($this->db);
            if ($project->fetch((int) $object->fk_project) > 0) {
                $projectLabel = $project->ref . ($project->title ? ' - ' . $project->title : '');
            }
        }

        $ticketRows = [
            [['label' => true, 'text' => $outputLangs->transnoentities('Ticket')], $object->ref],
            [['label' => true, 'text' => $outputLangs->transnoentities('Subject')], (string) $object->subject],
            [['label' => true, 'text' => $outputLangs->transnoentities('DateCreation')], dol_print_date($object->datec, 'dayhour', 'tzuser')],
            [['label' => true, 'text' => $outputLangs->transnoentities('Project')], $projectLabel],
        ];

        $qqoqcpRows = [];
        foreach (digiriskdolibarr_ticket_qqoqcp_fields() as $key => $field) {
            // Browsers post a textarea with \r\n: TCPDF measures the row on it but drops the line after the \r
            $value = str_replace(["\r\n", "\r"], "\n", (string) ($object->array_options['options_' . $key] ?? ''));
            if ($key === 'digiriskdolibarr_qqoqcp_who') {
                $value = implode(', ', digiriskdolibarr_ticket_qqoqcp_who_names($value));
            }

            $qqoqcpRows[] = [
                ['label' => true, 'text' => $outputLangs->transnoentities($field['label'])],
                trim($value) !== '' ? $value : $outputLangs->transnoentities('QQOQCPEmpty'),
            ];
        }

        return [
            'ticket' => [
                'title'  => $outputLangs->transnoentities('Ticket'),
                'widths' => [$pageWidth * 0.2, $pageWidth * 0.8],
                'align'  => ['L', 'L'],
                'rows'   => $ticketRows,
            ],
            'qqoqcp' => [
                'title'  => $outputLangs->transnoentities('QQOQCPTitle'),
                'widths' => [$pageWidth * 0.2, $pageWidth * 0.8],
                'align'  => ['L', 'L'],
                'rows'   => $qqoqcpRows,
            ],
        ];
    }

    /**
     * Write the PDF file to disk
     *
     * @param  SaturneDocuments $objectDocument  Document being generated
     * @param  Translate        $outputLangs     Lang object for output
     * @param  string           $srcTemplatePath Unused, the model is not a template
     * @param  int              $hidedetails     Unused
     * @param  int              $hidedesc        Unused
     * @param  int              $hideref         Unused
     * @param  array|null       $moreparams      More parameters, the ticket in 'object'
     * @return int                               1 = OK, < 0 = KO
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = []): int
    {
        global $action, $hookmanager, $langs;

        $moreparams = self::getMoreParam($objectDocument, $moreparams);
        $object     = $moreparams['object'];
        if (empty($object->array_options)) {
            $object->fetch_optionals();
        }

        $moreparams['hideTemplateName'] = 1;

        $object->module = $this->module;
        $file           = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreparams);
        if ($file < 0) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        return $this->writePdf(pdf_getInstance($this->format), $object, $outputLangs, $file);
    }

    /**
     * Draw the document and save it
     *
     * @param  mixed     $pdf         PDF handler, a TCPDF instance
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @param  string    $file        Full path of the file to write
     * @return int                    1 = OK, < 0 = KO
     */
    protected function writePdf($pdf, $object, Translate $outputLangs, string $file): int
    {
        $defaultFontSize = pdf_getPDFFontSize($outputLangs) + 2;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();
        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetTitle($outputLangs->convToOutputCharset($outputLangs->transnoentities('QQOQCP') . ' ' . $object->ref));
        $pdf->SetSubject($outputLangs->transnoentities('TicketQqoqcpDocumentPDF'));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, true, $this->marge_basse);
        // The footer is written above the bottom margin: its height belongs to the break margin
        $pdf->SetAutoPageBreak(true, $this->marge_basse + $this->height);

        $pdf->AddPage();
        $this->_pagehead($pdf, $object, $outputLangs, $defaultFontSize);

        $pageWidth = $pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite;

        $pdf->SetY($this->marge_haute + 20);
        $pdf->SetFont('', 'B', $defaultFontSize + 6);
        $pdf->MultiCell($pageWidth, 10, $outputLangs->transnoentities('QQOQCP') . ' - ' . $object->ref, 0, 'C', false, 1, $this->marge_gauche);
        $pdf->SetFont('', '', $defaultFontSize);
        $pdf->Ln(4);

        foreach ($this->getQqoqcpTables($object, $outputLangs, $pageWidth) as $table) {
            $this->drawTable($pdf, $table, $pageWidth, $this->height, $defaultFontSize);
            $pdf->Ln(6);
        }

        // A long analysis spreads over several pages: each one gets its footer
        $this->drawFooterOnEveryPage($pdf, $object, $outputLangs, $defaultFontSize);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = 'Erreur PDF : ' . $exception->getMessage();
            return -1;
        }
        $this->result = ['fullpath' => $file];

        return 1;
    }
}
