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
 * \file    core/modules/digiriskdolibarrdocuments/ticketdocument/pdf_ticketdocument.modules.php
 * \ingroup digiquali
 * \brief   File of class to generate control document pdf
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';

// Load Digirisk libraries
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

/**
 * Class to build ticket document pdf
 */
class pdf_ticketdocument extends SaturneDocumentModel
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var string Module
     */
    public string $module = 'digiriskdolibarr';

    /**
     * @var string Document type
     */
    public string $document_type = 'ticketdocument';

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name         = 'ticketdocument';
        $this->description  = $langs->trans('TicketDocumentPDFDescription');
        $this->type         = 'pdf';
        $this->height       = 8;
        $this->orientation  = 'L';
    }

    /**
     *  Add a page in the pdf if the height is between two pages
     *
     * @param Object $pdf
     * @param float $neededHeight
     */
    public function checkPageBreak($pdf, $neededHeight) {
        $bottomMargin = $pdf->getBreakMargin();
        $pageHeight   = $pdf->getPageHeight();
        $currentY     = $pdf->GetY();

        if ($currentY + $neededHeight + $bottomMargin > $pageHeight) {
            $pdf->AddPage();
        }
    }

    /**
     *  Draw tables for pdf
     *
     * @param Object $pdf
     * @param array $data
     * @param array $widths
     * @param float $lineHeight
     */
    function drawTable($pdf, $data, $widths, $lineHeight)
    {
        global $langs;

        foreach ($data as $cells) {
            $maxHeight = $lineHeight;
            foreach ($cells as $i => $cell) {
                $nbLines = $pdf->getNumLines($cell, $widths[$i]);
                $height  = $nbLines * $lineHeight;
                if ($height > $maxHeight) {
                    $maxHeight = $height;
                }
            }
            foreach ($cells as $key => $cell) {
                $x    = $pdf->GetX();
                $y    = $pdf->GetY();
                $cell = $cell ?? $langs->transnoentities('NoData');

                if (($cells[0] == $langs->transnoentities('Subject') || $cells[0] == $langs->transnoentities('Message') || $cells[0] == 'Tags') && $key == 1) {
                    $pdf->MultiCell($widths[$key], $lineHeight, $cell, 1, 'L', 0, 0, $x, $y, true, 0, false, true, $lineHeight, 'M');
                } else {
                    $pdf->MultiCell($widths[$key], $lineHeight, $cell, 1, 'C', 0, 0, $x, $y, true, 0, false, true, $lineHeight, 'M');
                }
                $pdf->SetXY($x + $widths[$key], $y);
            }
            $pdf->Ln($maxHeight);
        }
    }

    /**
     *  Show top header of page
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param  Contrat		$object     	Object to show
     *  @param  Translate	$outputlangs	Object lang for output
     *  @return	float|int                   Return topshift value
     */
    protected function _pagehead(&$pdf, $object, $outputLangs, $defaultFontSize)
    {
        global $mysoc;

        $top_shift = 0;
        $posX      = $this->marge_gauche;
        $posY      = $this->marge_haute;
        // Logo
        if (!getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO')) {
            if ($mysoc->logo) {
                $logoDir = '';
                if (getMultidirOutput($object, 'mycompany')) {
                    $logoDir = getMultidirOutput($object, 'mycompany');
                }
                if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
                    $logo = $logoDir . '/logos/thumbs/' . $mysoc->logo_small;
                } else {
                    $logo = $logoDir . '/logos/' . $mysoc->logo;
                }
                if (is_readable($logo)) {
                    $height = pdf_getHeightForLogo($logo);
                    $pdf->SetX($posX);
                    $pdf->Image($logo, $posX, $posY, 0, $height);
                } else {
                    $width = 100;
                    $pdf->SetTextColor(200, 0, 0);
                    $pdf->SetFont('', 'B', $defaultFontSize);
                    $pdf->MultiCell($width, 3, $outputLangs->transnoentities('ErrorLogoFileNotFound', $logo), 0, 'L');
                    $pdf->MultiCell($width, 3, $outputLangs->transnoentities('ErrorGoToGlobalSetup'), 0, 'L');
                }
            }
        }

        $legalPicto = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/legalPicto/CreativeCommonsBySa.png';
        $posX       = $pdf->GetPageWidth() / 2;
        if (file_exists($legalPicto)) {
            $pdf->SetXY($posX, $posY);
            $pdf->Image($legalPicto, $posX, $posY, 20, 4);
        }
        $pdf->setX($this->page_largeur + $this->marge_droite);

        $pdf->writeHTML('<a href="https://www.digirisk.com/">www.digirisk.com</a>');
        $pdf->Ln(1);

        return $top_shift;
    }

    /**
     *  Show footer of page
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param  Contrat		$object     	Object to show
     *  @param  Translate	$outputlangs	Object lang for output
     *  @return	float|int                   Return topshift value
     */
    function _pageFooter($pdf, $object, $outputLangs, $defaultFontSize)
    {
        $pdf->SetY($pdf->getPageHeight() - ($this->marge_basse * 2));

        $leftText  = $object->ref;
        $rightText = 'Version 1.0.0 - Page 1/1';

        $pdf->MultiCell($pdf->GetStringWidth($leftText) + 5, $this->height, $leftText, 0, 'L', false, 0, $this->marge_gauche);
        $pdf->MultiCell($pdf->GetStringWidth($rightText) + 5, $this->height, $rightText, 0, 'L', false, 0, $pdf->GetPageWidth() - ($this->marge_droite * 5));
    }


    /**
     *  Write the PDF file to disk
     *
     * @param Object $object Object to generate (ex: control)
     * @param Translate $outputLangs Lang object
     * @param string $srctemplatepath
     * @param int $hidedetails
     * @param int $hidedesc
     * @param int $hideref
     * @param null|array $moreparams
     * @return int                         1=OK, <0=KO
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = array()): int
    {
        global $action, $langs, $hookmanager, $user;

        $object = $moreparams['object'];

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

        // Init PDF
        $pdf             = pdf_getInstance($this->format);
        $defaultFontSize = pdf_getPDFFontSize($outputLangs) + 2;

        $category        = new Categorie($this->db);
        $digiriskElement = new DigiriskElement($this->db);

        $user->fetch($object->fk_user_assign);
        $digiriskElement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);
        $categories = $category->containing($object->id, Categorie::TYPE_TICKET);
        if (!empty($categories)) {
            $index = 0;
            foreach ($categories as $cat) {
                if ($index == 0) {
                    $allCategories = $cat->label;
                } else {
                    $allCategories = $allCategories . ', ' . $cat->label;
                }
                $index++;
            }
        }
        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();

        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetTitle($outputLangs->convToOutputCharset($this->document_type));
        $pdf->SetSubject($outputLangs->transnoentities($this->document_type));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, $this->marge_basse);
        $pdf->SetAutoPageBreak(1, $this->marge_basse);

        $pdf->AddPage();
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $defaultFontSize);

        $pageWidth   = $pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite;
        $tableWidth  = $pageWidth * 0.5;
        $rectWidth   = $pageWidth * 0.5;
        $rectHeight  = 120;

        $pdf->SetFont('', 'B', 12);

        $this->_pagehead($pdf, $object, $outputLangs, $defaultFontSize);

        $pdf->Ln(10);

        $startY = $pdf->GetY();
        $startX = $this->marge_gauche + $tableWidth + 5;
        $pdf->setX($startX);
        $photoPath = getMultidirOutput($object, 'ticket') . '/' . $object->ref;
        $fileArray = dol_dir_list($photoPath, 'files', 0, '', '(\.odt|\.pdf)$');
        if (count($fileArray) && !empty($fileArray)) {
            $fileArray = dol_sort_array($fileArray, 'position');
            $thumbName = saturne_get_thumb_name($fileArray[0]['name']);
            $photo     = $photoPath . '/thumbs/' . $thumbName;
        }

        if (!empty($photo) && file_exists($photo)) {
            list($imgW_px, $imgH_px) = getimagesize($photo);

            $dpi     = 96;
            $imgW_mm = $imgW_px * 25.4 / $dpi;
            $imgH_mm = $imgH_px * 25.4 / $dpi;
            $imgX    = $startX + ($rectWidth - $imgW_mm) / 2;
            $imgY    = $startY + ($rectHeight - $imgH_mm) / 2;

            $pdf->Rect($startX, $startY, $rectWidth, $rectHeight);
            $pdf->Image($photo, $imgX - 5, $imgY - 5, 0, 0);
        } else {
            $pdf->Rect($startX, $startY, $rectWidth, $rectHeight);
            $pdf->SetFont('', 'I', 9);
            $pdf->SetXY($startX, $startY + ($rectHeight / 2) - 3);
            $pdf->Cell($rectWidth, 6, $langs->transnoentities('NoPhoto'), 0, 0, 'C');
        }

        $pdf->setY($startY);
        $header = [
            [$langs->transnoentities('TicketNumber'), $object->ref]
        ];

        $widths = [
            $tableWidth * 0.3,
            $tableWidth * 0.7
        ];

        $this->drawTable($pdf, $header, $widths, $this->height);

        $pdf->SetFont('', 'B', 11);
        $pdf->SetFillColor(42, 157, 143);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('AuthorRequest'), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 10);

        $author = [
            [$langs->transnoentities('LastName'), $object->array_options['options_digiriskdolibarr_ticket_lastname'], $langs->transnoentities('Phone')],
            [$langs->transnoentities('FirstName'), $object->array_options['options_digiriskdolibarr_ticket_firstname'], $object->array_options['options_digiriskdolibarr_ticket_phone']]
        ];

        $widths = [
            $tableWidth * 0.15,
            $tableWidth * 0.60,
            $tableWidth * 0.25,
        ];

        $this->drawTable($pdf, $author, $widths, $this->height);

        $pdf->SetFont('', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(42, 157, 143);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('FromAndDate'), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('', '', 10);

        $ticketData = [
            [$langs->transnoentities('Service'), $digiriskElement->ref . ' ' . $digiriskElement->label],
            [$langs->transnoentities('Location'), $object->array_options['options_digiriskdolibarr_ticket_location']],
            [$langs->transnoentities('DateCreation'), dol_print_date($object->date_creation, 'dayhour')],
            [$langs->transnoentities('DateValidation'), dol_print_date($object->date_validation, 'dayhour')],
            [$langs->transnoentities('DateClosing'), dol_print_date($object->date_close, 'dayhour')]
        ];

        $widths = [
            $tableWidth * 0.20,
            $tableWidth * 0.80
        ];

        $this->drawTable($pdf, $ticketData, $widths, $this->height);
        $pdf->SetFont('', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(42, 157, 143);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('Info'), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('', '', 10);

        $contactListExternal = $object->liste_contact(-1, 'external');
        $contactListInternal = $object->liste_contact(-1, 'internal');
        $contactList         = [];
        $contactNames        = '';

        if (!empty($contactListExternal) && is_array($contactListExternal)) {
            $contactList = array_merge($contactList, $contactListExternal);
        }
        if (!empty($contactListInternal) && is_array($contactListInternal)) {
            $contactList = array_merge($contactList, $contactListInternal);
        }
        if (!empty($contactList) && is_array($contactList)) {
            foreach ($contactList as $contact) {
                $contactNames .= dol_strtoupper($contact['lastname']) . ' ' . ucfirst($contact['firstname']) . ', ';
            }
        } else {
            $contactNames = $langs->transnoentities('NoData');
        }

        $progessionData = [
            [$langs->transnoentities('Progress'), $object->progress . '%'],
            [$langs->transnoentities('Status'), $object->getLibStatut()],
            [$langs->transnoentities('Assigned'), dol_ucfirst($user->firstname) . ' ' . dol_ucfirst($user->lastname)],
            [$langs->transnoentities('Contact'), $contactNames]
        ];

        $widths = [
            $tableWidth * 0.21,
            $tableWidth * 0.79
        ];

        $this->drawTable($pdf, $progessionData, $widths, $this->height);
        $infos = [
            [$langs->transnoentities('Subject'), dol_string_nohtmltag($object->subject)],
            [$langs->transnoentities('Message'), dol_string_nohtmltag($object->message)],
            ['Tags', $allCategories]
        ];

        $pdf->Ln(3);

        $widths = [
            $pageWidth * 0.10,
            $pageWidth * 0.92
        ];

        $this->drawTable($pdf, $infos, $widths, $this->height);
        $this->_pageFooter($pdf, $object, $outputLangs, $defaultFontSize);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = "Erreur PDF : " . $exception->getMessage();
            return -1;
        }
        $this->result = ['fullpath' => $file];
        return 1;
    }
}
