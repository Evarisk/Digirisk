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
         * @var DoliDB Database handler
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
            $this->version      = '1.0.0';
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
         * @param TCPDF $pdf pdf object
         * @param array $table array with values
         * @param array $tableWidth the total width of the table
         * @param float $lineHeight is the normal height value for lines
         * @param float $defaultFontSize to have the default font size
         * @return void
         *
         */
        function drawTable($pdf, $table, $tableWidth, $lineHeight, $defaultFontSize)
        {
            global $langs;

            if (!isset($table['rows'], $table['widths'], $table['align'])) {
                return;
            }

            if (!empty($table['title'])) {
                $pdf->SetFont('', 'B', $defaultFontSize);
                $pdf->SetFillColor(42, 157, 143);
                $pdf->SetTextColor(255, 255, 255);

                $pdf->Cell($tableWidth, 8, $table['title'], 1, 1, 'C', true);

                // Reset style
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $defaultFontSize - 2);
            }

            $widths = $table['widths'];

            if (isset($table['Ln'])) {
                $pdf->Ln($table['Ln']);
            }
            foreach ($table['rows'] as $cells) {
                $maxHeight = $lineHeight;

                // Calculating max height for a line to break after
                foreach ($cells as $i => $cellData) {
                    if (!isset($widths[$i])) {
                        continue;
                    }
                    if (is_array($cellData)) {
                        $cell = $cellData['text'] ?? '';
                    } else {
                        $cell = $cellData;
                    }
                    $cell    = $cell ?? $langs->transnoentities('NoData');
                    $nbLines = $pdf->getNumLines($cell, $widths[$i]);
                    $height  = $nbLines * $lineHeight;

                    if ($height > $maxHeight) {
                        $maxHeight = $height;
                    }
                }

                // draw the cells array
                foreach ($cells as $key => $cellData) {
                    if (!isset($widths[$key])) {
                        continue;
                    }

                    $isLabel = false;
                    $cell    = '';

                    if (is_array($cellData)) {
                        $cell    = $cellData['text'] ?? '';
                        $isLabel = !empty($cellData['label']);
                    } else {
                        $cell = $cellData;
                    }

                    if ($isLabel) {
                        $pdf->SetFont('', 'B', 10);
                    } else {
                        $pdf->SetFont('', '', 10);
                    }

                    $x     = $pdf->GetX();
                    $y     = $pdf->GetY();
                    $cell  = $cell ?? $langs->transnoentities('NoData');
                    $align = $aligns[$key] ?? 'C';

                    $pdf->MultiCell($widths[$key], $lineHeight, $cell, 1, $align, 0, 0, $x, $y, true, 0, false, true, $lineHeight, 'M');
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
            $posX       = ($pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite) / 2;
            if (file_exists($legalPicto)) {
                $pdf->SetXY($posX, $posY);
                $pdf->Image($legalPicto, $posX, $posY, 20, 4);
            }
            $pdf->setFontSize($defaultFontSize - 2);
            $htmlLink = 'www.digirisk.com';
            $posX = $pdf->GetPageWidth() - $this->marge_droite - $pdf->GetStringWidth($htmlLink);
            $pdf->setXY($posX, $posY);

            $pdf->writeHTML('<a href="https://www.digirisk.com/">' . $htmlLink . '</a>', true, false, false, false, 'R');
            $pdf->Ln(1);

            return $top_shift;
        }

        /**
         *  function that get table data
         *
         *  @param	TCPDF		    $pdf     		  Object PDF
         *  @param  Ticket		    $object     	  Object ticket
         *  @param  float           $tableWidth       Half pagewidth size
         *  @param  float           $pageWidth        pagewidth withour margin
         *  @param  DigiriskElement $digiriskElement  Object Digiriskelement
         *  @param  User            $userTmp          Object user
         *  @param  string          $contactNames     String with all contact names
         *  @param  string          $allCategories    String with all categories
         *  @return	array           $tables           Return tables array
         */
        function getTicketPdfTables($pdf, $object, $tableWidth, $pageWidth, $digiriskElement, $userTmp, $contactNames, $allCategories)
        {
            global $langs;
            $tables = [
                'header' => [
                    'widths' => [
                        $tableWidth * 0.3,
                        $tableWidth * 0.7
                    ],
                    'align' => ['C', 'C'],
                    'rows' => [
                        [
                            ['label' => true, 'text' => $langs->transnoentities('TicketNumber')],
                            ['text' => $object->ref]
                        ]
                    ]
                ],
                'author' => [
                    'title'  => $langs->transnoentities('AuthorRequest'),
                    'widths' => [
                        $tableWidth * 0.15,
                        $tableWidth * 0.60,
                        $tableWidth * 0.25
                    ],
                    'align' => ['C', 'C', 'C'],
                    'rows' => [
                        [
                            ['label' => true, 'text' => $langs->transnoentities('LastName')],
                            ['text' => $object->array_options['options_digiriskdolibarr_ticket_lastname']],
                            ['label' => true, 'text' => $langs->transnoentities('Phone')],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('FirstName')],
                            ['text' => $object->array_options['options_digiriskdolibarr_ticket_firstname']],
                            ['text' => $object->array_options['options_digiriskdolibarr_ticket_phone']],
                        ],
                    ]
                ],
                'ticket' => [
                    'title'  => $langs->transnoentities('FromAndDate'),
                    'widths' => [
                        $tableWidth * 0.20,
                        $tableWidth * 0.80
                    ],
                    'align' => ['C', 'C'],
                    'rows' => [
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Service')],
                            ['text' => $digiriskElement->ref . ' ' . $digiriskElement->label],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Location')],
                            ['text' => $object->array_options['options_digiriskdolibarr_ticket_location']],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('DateCreation')],
                            ['text' => dol_print_date($object->date_creation, 'dayhour')],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('DateValidation')],
                            ['text' => dol_print_date($object->date_validation, 'dayhour')],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('DateClosing')],
                            ['text' => dol_print_date($object->date_close, 'dayhour')],
                        ],
                    ]
                ],
                'progression' => [
                    'title'  => $langs->transnoentities('Info'),
                    'widths' => [
                        $tableWidth * 0.22,
                        $tableWidth * 0.78
                    ],
                    'align' => ['C', 'C'],
                    'rows' => [
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Progress')],
                            ['text' => $object->progress . '%'],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Status')],
                            ['text' => $object->getLibStatut()],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Assigned')],
                            ['text' => dol_ucfirst($userTmp->firstname) . ' ' . dol_ucfirst($userTmp->lastname)],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Contact')],
                            ['text' => $contactNames],
                        ],
                    ]
                ],
                'infos' => [
                    'Ln' => 3,
                    'widths' => [
                        $pageWidth * 0.10,
                        $pageWidth * 0.90
                    ],
                    'align' => ['C', 'L'],
                    'rows' => [
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Subject')],
                            ['text' => dol_string_nohtmltag($object->subject)],
                        ],
                        [
                            ['label' => true, 'text' => $langs->transnoentities('Message')],
                            ['text' => dol_string_nohtmltag($object->message)],
                        ],
                        [
                            ['label' => true, 'text' => 'Tags'],
                            ['text' => $allCategories],
                        ],
                    ]
                ],
            ];

            return $tables;
        }

        /**
         *  Show footer of page
         *
         *  @param	TCPDF		$pdf     		Object PDF
         *  @param  Ticket		$object     	Object to show
         *  @param  Translate	$outputlangs	Object lang for output
         *  @return	void
         */
        function _pageFooter($pdf, $object, $outputLangs, $defaultFontSize)
        {
            global $langs;

            $pdf->setFontSize($defaultFontSize - 2);

            $leftText  = $object->ref . ' - ' . $langs->transnoentities('GeneratedTicketDocumentDate') . ' ' . dol_print_date(dol_now(), 'dayhoursec', 'tzuser');
            $rightText = 'Version '. $this->version . ' - Page ' . $pdf->getNumPages() . '/' . $pdf->getAliasNbPages();

            $pdf->SetY($pdf->getPageHeight() - $this->marge_basse - $pdf->getStringHeight($pdf->GetStringWidth($leftText), $leftText));

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
            $userTmp         = new User($this->db);
            $digiriskElement = new DigiriskElement($this->db);

            $userTmp->fetch($object->fk_user_assign);
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
            $pdf->SetAuthor($outputLangs->convToOutputCharset($userTmp->getFullName($outputLangs)));

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
            $startX = $this->marge_gauche + $tableWidth;
            $pdf->setX($startX);
            $photoPath = getMultidirOutput($object, 'ticket') . '/' . $object->ref;
            $fileArray = dol_dir_list($photoPath, 'files', 0, '', '(\.odt|\.pdf)$');
            if (count($fileArray) && !empty($fileArray)) {
                $fileArray = dol_sort_array($fileArray, 'position');
                $thumbName = saturne_get_thumb_name($fileArray[0]['name'], 'medium', $photoPath);
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

            $pdf->setY($startY);
            $tables = $this->getTicketPdfTables($pdf, $object, $tableWidth, $pageWidth, $digiriskElement, $userTmp, $contactNames, $allCategories);
            if (!empty($tables) && is_array($tables)) {
                foreach ($tables as $table) {
                    $this->drawTable($pdf, $table, $tableWidth, $this->height, $defaultFontSize);
                }
            }

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
