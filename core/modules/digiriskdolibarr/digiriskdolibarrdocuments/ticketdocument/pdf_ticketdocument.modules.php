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

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

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
        $this->description  = $langs->trans('ControlDocumentPDFDescription');
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

                $pdf->MultiCell($widths[$key], $lineHeight, $cell, 1, 'C', 0, 0, $x, $y, true, 0, false, true, $lineHeight, 'M');
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

        $moreparams['hideTemplateName'] = 1;
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $moreparams['object'], $moreparams);

        if ($file < 0) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $moreparams['object'], 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $moreparams['object'], $action);

        // Init PDF
        $pdf             = pdf_getInstance($this->format);
        $defaultFontSize = pdf_getPDFFontSize($outputLangs) + 2;

        $category        = new Categorie($this->db);
        $user            = new User($this->db);
        $digiriskElement = new DigiriskElement($this->db);

        $user->fetch($moreparams['object']->fk_user_assign);
        $digiriskElement->fetch($moreparams['object']->array_options['options_digiriskdolibarr_ticket_service']);
        $categories = $category->containing($moreparams['object']->id, Categorie::TYPE_TICKET);
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
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, $this->marge_basse);
        $pdf->SetAutoPageBreak(1, $this->marge_basse);

        $pdf->AddPage();
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $defaultFontSize);

        $pageWidth   = $pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite;
        $tableWidth  = $pageWidth * 0.5;
        $imageWidth  = $pageWidth * 0.5;
        $imageHeight = 120;

        $pdf->SetFont('', 'B', 12);

        $this->_pagehead($pdf, $moreparams['object'], $outputLangs, $defaultFontSize);
        $pdf->Ln(25);
        $startY = $pdf->GetY();
        $startX = $this->marge_gauche + $tableWidth + 5;
        $pdf->setX($startX);
        $photoPath = getMultidirOutput($moreparams['object'], $moreparams['object']->module) . '/' . $moreparams['object']->ref;
        $fileArray = dol_dir_list($photoPath, 'files', 0, '', '(\.odt|\.pdf)$');
        if (count($fileArray) && !empty($fileArray)) {
            $fileArray = dol_sort_array($fileArray, 'position');
            $thumbName = saturne_get_thumb_name($fileArray[0]['name']);
            $photo = $photoPath . '/thumbs/' . $thumbName;
        }

        if (!empty($photo) && file_exists($photo)) {
            $pdf->Image($photo, $startX, $startY, $imageWidth, $imageHeight);
        } else {
            $pdf->Rect($startX, $startY, $imageWidth, $imageHeight);
            $pdf->SetFont('', 'I', 9);
            $pdf->SetXY($startX, $startY + ($imageHeight / 2) - 3);
            $pdf->Cell($imageWidth, 6, $langs->transnoentities('NoPhoto'), 0, 0, 'C');
        }

        $pdf->setY($startY);
        $header = [
            [$langs->transnoentities('TicketNumber'), $moreparams['object']->ref]
        ];

        $widths = [
            $tableWidth * 0.3,
            $tableWidth * 0.7
        ];

        $this->drawTable($pdf, $header, $widths, $this->height);

        $pdf->SetFont('', 'B', 11);
        $pdf->SetFillColor(153, 204, 204);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('AuthorRequest'), 1, 1, 'C', true);
        $pdf->SetFont('', '', 10);

        $author = [
            [$langs->transnoentities('LastName'), $moreparams['object']->array_options['options_digiriskdolibarr_ticket_lastname'], $langs->transnoentities('Phone')],
            [$langs->transnoentities('FirstName'), $moreparams['object']->array_options['options_digiriskdolibarr_ticket_firstname'], $moreparams['object']->array_options['options_digiriskdolibarr_ticket_phone']]
        ];

        $widths = [
            $tableWidth * 0.34,
            $tableWidth * 0.33,
            $tableWidth * 0.33,
        ];

        $this->drawTable($pdf, $author, $widths, $this->height);

        $pdf->SetFont('', 'B', 11);
        $pdf->SetFillColor(153, 204, 204);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('FromAndDate'), 1, 1, 'C', true);

        $pdf->SetFont('', '', 10);

        $ticketData = [
            [$langs->transnoentities('Service'), $digiriskElement->ref . ' ' . $digiriskElement->label],
            [$langs->transnoentities('Location'), $moreparams['object']->array_options['options_digiriskdolibarr_ticket_location']],
            [$langs->transnoentities('DateCreation'), dol_print_date($moreparams['object']->date_creation, 'dayhour')],
            [$langs->transnoentities('DateValidation'), dol_print_date($moreparams['object']->date_validation, 'dayhour')],
            [$langs->transnoentities('DateClosing'), dol_print_date($moreparams['object']->date_close, 'dayhour')]
        ];

        $widths = [
            $tableWidth * 0.3,
            $tableWidth * 0.7
        ];

        $this->drawTable($pdf, $ticketData, $widths, $this->height);
        $pdf->SetFont('', 'B', 11);
        $pdf->SetFillColor(153, 204, 204);
        $pdf->Cell($tableWidth, 8, $langs->transnoentities('Info'), 1, 1, 'C', true);

        $pdf->SetFont('', '', 10);

        $contactListExternal = $moreparams['object']->liste_contact(-1, 'external');
        $contactListInternal = $moreparams['object']->liste_contact(-1, 'internal');
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
            [$langs->transnoentities('Progress'), $moreparams['object']->progress . '%'],
            [$langs->transnoentities('Status'), $moreparams['object']->getLibStatut()],
            [$langs->transnoentities('Assigned'), dol_ucfirst($user->firstname) . ' ' . dol_ucfirst($user->lastname)],
            [$langs->transnoentities('Contact'), $contactNames]
        ];

        $widths = [
            $tableWidth * 0.3,
            $tableWidth * 0.7
        ];

        $this->drawTable($pdf, $progessionData, $widths, $this->height);
        $infos = [
            [$langs->transnoentities('Subject'), $moreparams['object']->subject],
            [$langs->transnoentities('Message'), $moreparams['object']->message],
            ['Tags', $allCategories]
        ];

        $widths = [
            $pageWidth * 0.15,
            $pageWidth * 0.87
        ];

        $this->drawTable($pdf, $infos, $widths, $this->height);

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
