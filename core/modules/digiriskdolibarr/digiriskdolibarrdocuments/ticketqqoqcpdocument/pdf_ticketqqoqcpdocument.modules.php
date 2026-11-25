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
 * \brief   File of class to generate the QQOQCCP document of a ticket (#5549), with its corrective and preventive
 *          actions, in the style of the PDF documents of the DU: a title band, cards, round badges and status pills.
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';
require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_action.lib.php';

/**
 * Class to build the QQOQCCP document of a ticket: What, Who, Where, When, How, How much and Why of the problem, then
 * the corrective and preventive actions decided to solve it
 */
class pdf_ticketqqoqcpdocument extends ModelePDFDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'ticketqqoqcpdocument';

    /**
     * Width, in mm, of the column of the questions, the answers take the rest of the line
     */
    const QUESTION_WIDTH = 62;

    /**
     * Diameter, in mm, of the round badge carrying the letter of a question
     */
    const BADGE = 8;

    /**
     * @var array Color of the corrective actions
     */
    protected array $correctiveColor = [232, 125, 55];

    /**
     * @var array Color of a finished action
     */
    protected array $doneColor = [46, 150, 90];

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
        $this->version     = '2.0.0';

        $this->orientation  = 'P';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 14;
        $this->marge_droite = 14;
        $this->marge_haute  = 14;
        $this->marge_basse  = 14;
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

        $outputLangs->loadLangs(['main', 'projects', 'ticket', 'digiriskdolibarr@digiriskdolibarr']);

        $moreparams['hideTemplateName'] = 1;

        $object->module = $this->module;
        $file           = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreparams);
        if (!is_string($file) || $file === '') {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        return $this->writePdf($object, $outputLangs, $file);
    }

    /**
     * Draw the document and save it
     *
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @param  string    $file        Full path of the file to write
     * @return int                    1 = OK, < 0 = KO
     */
    protected function writePdf($object, Translate $outputLangs, string $file): int
    {
        $size = pdf_getPDFFontSize($outputLangs);
        $data = $this->gatherData($object, $outputLangs);
        $pdf  = $this->openPdf($object, $outputLangs, $outputLangs->transnoentities('QQOQCPTitle') . ' ' . $object->ref);

        $this->footerText = $outputLangs->transnoentities('QQOQCPTitle') . ' - ' . $object->ref . ' - ' . $outputLangs->transnoentities('DigiriskSynthesisGeneratedAt') . ' ' . dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputLangs);

        $this->band($pdf, $object, $outputLangs, $size, $data);
        $this->ticketCards($pdf, $object, $outputLangs, $size, $data);
        $this->summaryTiles($pdf, $outputLangs, $size, $data);
        $this->sectionAnalysis($pdf, $outputLangs, $size, $data);

        if ($data['withActions']) {
            // A ticket without a project cannot carry any action yet: say how to add them rather than "none yet"
            $emptyText = $outputLangs->transnoentities(empty($object->fk_project) ? 'TicketActionsNeedProject' : 'NoActionsYet');
            foreach (digiriskdolibarr_ticket_action_sections() as $type => $section) {
                $this->sectionActions($pdf, $outputLangs->transnoentities($section['titleKey']), $data['actions'][$type] ?? [], $data['tags'], $outputLangs, $size, $emptyText, $this->actionTypeColor($type));
            }
        }

        // A long analysis spreads over several pages: each one gets its footer
        $this->writeFooter($pdf);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = 'Erreur PDF : ' . $exception->getMessage();
            return -1;
        }
        $this->result = ['fullpath' => $file];

        return 1;
    }

    /**
     * Read everything the document prints: the ticket, its analysis and its actions
     *
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @return array                  Project, GP/UT, assignee and status of the ticket, fields of the analysis, actions
     *                                of each type and their tags
     */
    protected function gatherData($object, Translate $outputLangs): array
    {
        $data = ['project' => '', 'elements' => '', 'assignee' => '', 'status' => '', 'fields' => [], 'filled' => 0, 'withActions' => false, 'actions' => [], 'tags' => []];

        if (!empty($object->fk_project)) {
            $project = new Project($this->db);
            if ($project->fetch((int) $object->fk_project) > 0) {
                $data['project'] = $project->ref . ($project->title ? ' - ' . $project->title : '');
            }
        }

        digiriskdolibarr_ticket_set_digirisk_elements([$object]);
        $data['elements'] = (string) ($object->digiriskElementRefLabel ?? '');

        if (!empty($object->fk_user_assign)) {
            $assignee = new User($this->db);
            if ($assignee->fetch((int) $object->fk_user_assign) > 0) {
                $data['assignee'] = $assignee->getFullName($outputLangs);
            }
        }
        $data['status'] = trim(dol_string_nohtmltag((string) $object->getLibStatut(0)));

        foreach (digiriskdolibarr_ticket_qqoqcp_fields() as $key => $field) {
            $rawValue = (string) ($object->array_options['options_' . $key] ?? '');
            $label    = $outputLangs->transnoentities($field['label']);
            $item     = [
                // The letter the method is named after: Q, Q, O, Q, C, C, P in French, the W and H of 5W2H in English
                'letter' => dol_strtoupper(dol_substr($label, 0, 1)),
                'label'  => $label,
                'help'   => $outputLangs->transnoentities($field['help']),
                'text'   => '',
                'names'  => [],
            ];

            if ($key === 'digiriskdolibarr_qqoqcp_who') {
                $item['names'] = digiriskdolibarr_ticket_qqoqcp_who_names($rawValue);
            } else {
                // Browsers post a textarea with \r\n: TCPDF measures the text on it but drops the line after the \r
                $item['text'] = trim(str_replace(["\r\n", "\r"], "\n", $rawValue));
            }

            if ($item['text'] !== '' || !empty($item['names'])) {
                $data['filled']++;
            }
            $data['fields'][] = $item;
        }

        // The actions are project tasks: without the project module the ticket has none and the document leaves them out
        if (isModEnabled('project')) {
            $data['withActions'] = true;
            $data['actions']     = digiriskdolibarr_ticket_get_actions($this->db, digiriskdolibarr_ticket_get_parent_task($this->db, $object));

            $actionIds = [];
            foreach ($data['actions'] as $actions) {
                foreach ($actions as $action) {
                    $actionIds[] = (int) $action->rowid;
                }
            }
            $data['tags'] = digiriskdolibarr_get_actions_tags($this->db, $actionIds);
        }

        return $data;
    }

    /**
     * Title band of the first page: the ticket, the title of the analysis, the subject and the project of the ticket
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @param  array     $data        Data of the document, from gatherData()
     * @return void
     */
    protected function band($pdf, $object, Translate $outputLangs, float $size, array $data)
    {
        $pageWidth = $pdf->GetPageWidth();
        $textWidth = $this->contentWidth($pdf);
        $white     = [255, 255, 255];
        $light     = $this->tint($this->accent, 0.62);

        // The logo sits on a white card at the right of the band, the texts stop before it
        $logo      = $this->companyLogoPath($object);
        $logoWidth = !empty($logo) ? $this->companyLogoSize($logo, 12, 48)[0] : 0;
        if ($logoWidth > 0) {
            $textWidth -= $logoWidth + 14;
        }

        // The band grows with the subject, printed on two lines at most
        $pdf->SetFont('', 'B', $size + 2);
        $subject      = $this->fitLines($pdf, dol_string_nohtmltag((string) $object->subject), $textWidth, 2);
        $subjectLines = $subject !== '' ? min(2, $pdf->getNumLines($subject, $textWidth)) : 0;
        $metaY        = 35 + ($subjectLines * 5.6) + ($subjectLines > 0 ? 1.5 : 0);
        $bandHeight   = $metaY + (dol_strlen($data['project']) ? 6 : 0) + 6;

        $pdf->SetAutoPageBreak(false);

        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->Rect(0, 0, $pageWidth, $bandHeight, 'F');

        $pdf->SetAlpha(0.10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($pageWidth - 20, 6, 36, 0, 360, 'F');
        $pdf->Circle($pageWidth - 66, $bandHeight - 4, 15, 0, 360, 'F');
        $pdf->SetAlpha(1);

        if ($logoWidth > 0) {
            $cardX = $pageWidth - $this->marge_droite - $logoWidth - 8;
            $this->card($pdf, $cardX, 12, $logoWidth + 8, 18, $white, 3);
            $this->companyLogo($pdf, $object, $cardX + 4, 15, 12, 48);
        }

        $this->kicker($pdf, $this->marge_gauche, 12, $textWidth, $outputLangs->transnoentities('Ticket') . ' ' . $object->ref, $size + 1.5, $light);

        $pdf->SetFont('', 'B', $size + 14);
        $pdf->SetTextColor($white[0], $white[1], $white[2]);
        $pdf->SetXY($this->marge_gauche, 17);
        $pdf->Cell($textWidth, 11, $outputLangs->transnoentities('QQOQCPTitle'), 0, 1, 'L');

        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->RoundedRect($this->marge_gauche, 30.5, 22, 1.4, 0.7, '1111', 'F');

        if ($subjectLines > 0) {
            $pdf->SetFont('', 'B', $size + 2);
            $pdf->SetTextColor($white[0], $white[1], $white[2]);
            $pdf->MultiCell($textWidth, 5.6, $subject, 0, 'L', false, 1, $this->marge_gauche, 35, true, 0, false, true, $subjectLines * 5.6, 'T');
        }

        if (dol_strlen($data['project'])) {
            $pdf->SetFont('', '', $size - 0.5);
            $pdf->SetTextColor($light[0], $light[1], $light[2]);
            $pdf->SetXY($this->marge_gauche, $metaY);
            $pdf->Cell($textWidth, 5, $this->fitLines($pdf, $outputLangs->transnoentities('Project') . ' ' . $data['project'], $textWidth, 1), 0, 1, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
        $pdf->SetXY($this->marge_gauche, $bandHeight + 7);
    }

    /**
     * Cards of the ticket under the band: date, GP/UT, assignee and status
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Ticket    $object      Ticket of the document
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @param  array     $data        Data of the document, from gatherData()
     * @return void
     */
    protected function ticketCards($pdf, $object, Translate $outputLangs, float $size, array $data)
    {
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DateCreation'),             'title' => dol_print_date($object->datec, 'dayhour', 'tzuser', $outputLangs) ?: '-'],
            ['kicker' => $outputLangs->transnoentities('QQOQCPPdfElement'),         'title' => $data['elements'] ?: '-', 'color' => $this->accentAlt],
            ['kicker' => $outputLangs->transnoentities('DigiriskActionAssignedTo'), 'title' => $data['assignee'] ?: '-'],
            ['kicker' => $outputLangs->transnoentities('Status'),                   'title' => $data['status'] ?: '-', 'color' => $this->accentAlt],
        ], 4, $size);
    }

    /**
     * Key figures: questions answered, actions of each type and how many are done
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @param  array     $data        Data of the document, from gatherData()
     * @return void
     */
    protected function summaryTiles($pdf, Translate $outputLangs, float $size, array $data)
    {
        $tiles = [
            ['value' => $data['filled'] . ' / ' . count($data['fields']), 'label' => $outputLangs->transnoentities('QQOQCPPdfQuestionsFilled'), 'color' => $this->accent],
        ];

        if ($data['withActions']) {
            $sections = digiriskdolibarr_ticket_action_sections();
            $done     = 0;
            $total    = 0;
            foreach (digiriskdolibarr_ticket_count_actions($data['actions']) as $type => $counter) {
                if (!isset($sections[$type])) {
                    continue;
                }

                $tiles[] = ['value' => (string) $counter['total'], 'label' => $outputLangs->transnoentities($sections[$type]['titleKey']), 'color' => $this->actionTypeColor($type)];
                $done   += $counter['done'];
                $total  += $counter['total'];
            }
            $tiles[] = ['value' => $total > 0 ? $done . ' / ' . $total : '-', 'label' => $outputLangs->transnoentities('QQOQCPPdfActionsDone'), 'color' => $this->doneColor];
        }

        // 27 mm leave room for a label on two lines, as "Questions renseignées" on a quarter of the page
        $this->statTileRow($pdf, $tiles, 27, $size);
    }

    /**
     * Section of the analysis: one line per question, its letter, its label and its hint on the left, the answer on
     * the right
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @param  array     $data        Data of the document, from gatherData()
     * @return void
     */
    protected function sectionAnalysis($pdf, Translate $outputLangs, float $size, array $data)
    {
        // The band already reads "QQOQCCP analysis"
        $this->titleWithCounter($pdf, $outputLangs->transnoentities('QQOQCPPdfAnalysisSection'), '', $size, $this->accent);

        foreach ($data['fields'] as $field) {
            $this->analysisRow($pdf, $field, $outputLangs, $size);
        }
    }

    /**
     * One question of the analysis and its answer
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $field       Question, from gatherData()
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @return void
     */
    protected function analysisRow($pdf, array $field, Translate $outputLangs, float $size)
    {
        $pad        = 3;
        $textX      = $this->marge_gauche + self::BADGE + 3.5;
        $textWidth  = self::QUESTION_WIDTH - self::BADGE - 3.5 - 4;
        $valueX     = $this->marge_gauche + self::QUESTION_WIDTH;
        $valueWidth = $this->contentWidth($pdf) - self::QUESTION_WIDTH;
        $isEmpty    = $field['text'] === '' && empty($field['names']);
        $valueText  = $isEmpty ? $outputLangs->transnoentities('QQOQCPEmpty') : $field['text'];

        // Height of the line, measured with the fonts it is drawn with
        $pdf->SetFont('', 'B', $size + 0.5);
        $leftHeight = $pdf->getNumLines($field['label'], $textWidth) * 5;
        $pdf->SetFont('', '', $size - 2);
        $leftHeight = max(self::BADGE, $leftHeight + 0.8 + ($pdf->getNumLines($field['help'], $textWidth) * 3.6));

        if (!empty($field['names'])) {
            $valueHeight = $this->chips($pdf, $field['names'], $valueX, 0, $valueWidth, $size, false);
        } else {
            $pdf->SetFont('', $isEmpty ? 'I' : '', $size - 0.5);
            $valueHeight = $pdf->getNumLines($valueText, $valueWidth) * self::LINE;
        }

        // An answer longer than a page flows over the next ones, MultiCell keeping its column
        $this->checkPageBreak($pdf, max($leftHeight, $valueHeight) + (2 * $pad));

        $y         = $pdf->GetY();
        $startPage = $pdf->getPage();

        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->Circle($this->marge_gauche + (self::BADGE / 2), $y + $pad + (self::BADGE / 2), self::BADGE / 2, 0, 360, 'F');
        $pdf->SetFont('', 'B', $size + 1);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($this->marge_gauche, $y + $pad);
        $pdf->Cell(self::BADGE, self::BADGE, $field['letter'], 0, 0, 'C');

        $pdf->SetFont('', 'B', $size + 0.5);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->MultiCell($textWidth, 5, $field['label'], 0, 'L', false, 1, $textX, $y + $pad - 0.3, true, 0, false, true, 0, 'T');
        $pdf->SetFont('', '', $size - 2);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->MultiCell($textWidth, 3.6, $field['help'], 0, 'L', false, 1, $textX, $pdf->GetY() + 0.8, true, 0, false, true, 0, 'T');
        $leftBottom = $pdf->GetY();

        if (!empty($field['names'])) {
            $valueBottom = $y + $pad + $this->chips($pdf, $field['names'], $valueX, $y + $pad, $valueWidth, $size, true);
        } else {
            $color = $isEmpty ? $this->inkMuted : $this->inkSoft;
            $pdf->SetFont('', $isEmpty ? 'I' : '', $size - 0.5);
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->MultiCell($valueWidth, self::LINE, $valueText, 0, 'L', false, 1, $valueX, $y + $pad, true, 0, false, true, 0, 'T');
            $valueBottom = $pdf->GetY();
        }
        $pdf->SetTextColor(0, 0, 0);

        $bottom = ($pdf->getPage() === $startPage ? max($leftBottom, $valueBottom) : $valueBottom) + $pad;
        $this->hairline($pdf, $bottom);
        $pdf->SetXY($this->marge_gauche, $bottom);
    }

    /**
     * Pills of names, wrapped over as many lines as needed
     *
     * @param  TCPDF    $pdf    PDF handler
     * @param  string[] $labels Texts of the pills
     * @param  float    $x      Left of the area
     * @param  float    $y      Top of the area
     * @param  float    $width  Width of the area
     * @param  float    $size   Font size
     * @param  bool     $draw   Draw the pills, false only measures them
     * @return float            Height of the pills
     */
    protected function chips($pdf, array $labels, float $x, float $y, float $width, float $size, bool $draw): float
    {
        $height   = 6;
        $gap      = 2;
        $fontSize = $size - 1.5;
        $cursorX  = $x;
        $cursorY  = $y;

        foreach ($labels as $label) {
            $pdf->SetFont('', 'B', $fontSize);
            $chipWidth = min($width, $pdf->GetStringWidth($label) + 7);
            if ($cursorX > $x && $cursorX + $chipWidth > $x + $width) {
                $cursorX  = $x;
                $cursorY += $height + $gap;
            }

            if ($draw) {
                $this->pill($pdf, $cursorX, $cursorY, $chipWidth, $height, $label, $this->tint($this->accent, 0.88), $this->accent, $fontSize);
            }
            $cursorX += $chipWidth + $gap;
        }

        return $cursorY + $height - $y;
    }

    /**
     * Section of the actions of a type: its title and counter, then one card per action
     *
     * @param  TCPDF      $pdf         PDF handler
     * @param  string     $title       Title of the section
     * @param  stdClass[] $actions     Actions of the type, from digiriskdolibarr_ticket_get_actions()
     * @param  array      $tags        Tags of the actions, by action ID
     * @param  Translate  $outputLangs Lang object for output
     * @param  float      $size        Font size
     * @param  string     $emptyText   Text printed when the type has no action
     * @param  array      $color       Color of the type
     * @return void
     */
    protected function sectionActions($pdf, string $title, array $actions, array $tags, Translate $outputLangs, float $size, string $emptyText, array $color)
    {
        $done = 0;
        foreach ($actions as $action) {
            if ((int) $action->progress >= 100) {
                $done++;
            }
        }

        $counter = !empty($actions) ? $outputLangs->transnoentities('XActionsOfY', (string) $done, (string) count($actions)) : '';
        $this->titleWithCounter($pdf, $title, $counter, $size, $color, 50);

        if (empty($actions)) {
            $this->reserveSpace($pdf, 16);
            $y = $pdf->GetY();
            $this->card($pdf, $this->marge_gauche, $y, $this->contentWidth($pdf), 12, $this->surface);
            $pdf->SetFont('', 'I', $size - 1);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell($this->contentWidth($pdf), 12, $emptyText, 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($this->marge_gauche, $y + 16);
            return;
        }

        foreach ($actions as $action) {
            $this->actionCard($pdf, $action, $tags[(int) $action->rowid] ?? [], $outputLangs, $size);
        }
        $pdf->Ln(2);
    }

    /**
     * Card of an action: a bar of the color of its state, its label and its state, its details, its tags and its
     * progress
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  stdClass  $action      Action, from digiriskdolibarr_get_actions_by_type()
     * @param  array     $tags        Tags of the action
     * @param  Translate $outputLangs Lang object for output
     * @param  float     $size        Font size
     * @return void
     */
    protected function actionCard($pdf, $action, array $tags, Translate $outputLangs, float $size)
    {
        $pad        = 4;
        $barWidth   = 1.6;
        $width      = $this->contentWidth($pdf);
        $innerX     = $this->marge_gauche + $barWidth + $pad;
        $innerWidth = $width - $barWidth - (2 * $pad);
        $state      = $this->actionState($action, $outputLangs);
        $pillText   = dol_strtoupper($state['label']);
        $pillSize   = $size - 2.5;
        $details    = $this->actionDetails($action, $outputLangs);

        $pdf->SetFont('', 'B', $pillSize);
        $pillWidth  = $pdf->GetStringWidth($pillText) + 7;
        $labelWidth = $innerWidth - $pillWidth - 4;

        // Height of the card, measured with the fonts it is drawn with: it is drawn at fixed coordinates
        $pdf->SetFont('', 'B', $size + 0.5);
        $labelHeight = max(5.6, $pdf->getNumLines((string) $action->label, $labelWidth) * 5);
        $pdf->SetFont('', '', $size - 2);
        $detailsHeight = $pdf->getNumLines($details, $innerWidth) * 3.8;
        $tagsHeight    = !empty($tags) ? 2 + $this->tagChips($pdf, $tags, $innerX, 0, $innerWidth, $size, false) : 0;
        $height        = $pad + $labelHeight + 1.2 + $detailsHeight + $tagsHeight + 3 + 2 + $pad;

        $this->reserveSpace($pdf, $height + 3);
        $y = $pdf->GetY();

        $this->card($pdf, $this->marge_gauche, $y, $width, $height, $this->surface);
        $this->card($pdf, $this->marge_gauche, $y, $barWidth, $height, $state['color'], 0.8);

        $this->pill($pdf, $this->marge_gauche + $width - $pad - $pillWidth, $y + $pad - 0.3, $pillWidth, 5.6, $pillText, $this->tint($state['color'], 0.84), $this->shade($state['color'], 0.25), $pillSize);

        $pdf->SetFont('', 'B', $size + 0.5);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->MultiCell($labelWidth, 5, (string) $action->label, 0, 'L', false, 1, $innerX, $y + $pad, true, 0, false, true, 0, 'T');
        $cursor = $y + $pad + $labelHeight + 1.2;

        $pdf->SetFont('', '', $size - 2);
        $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
        $pdf->MultiCell($innerWidth, 3.8, $details, 0, 'L', false, 1, $innerX, $cursor, true, 0, false, true, 0, 'T');
        $cursor += $detailsHeight;

        if (!empty($tags)) {
            $cursor += 2 + $this->tagChips($pdf, $tags, $innerX, $cursor + 2, $innerWidth, $size, true);
        }

        // Progress bar, the percentage on its right
        $cursor    += 3;
        $trackWidth = $innerWidth - 13;
        $this->card($pdf, $innerX, $cursor, $trackWidth, 2, $this->rule, 1);
        if ($state['progress'] > 0) {
            $this->card($pdf, $innerX, $cursor, max(2, $trackWidth * $state['progress'] / 100), 2, $state['color'], 1);
        }
        $pdf->SetFont('', 'B', $size - 2);
        $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
        $pdf->SetXY($innerX + $trackWidth, $cursor - 1.5);
        $pdf->Cell(13, 5, $state['progress'] . ' %', 0, 0, 'R');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $y + $height + 3);
    }

    /**
     * State of an action: done, late, in progress or to do
     *
     * @param  stdClass  $action      Action, from digiriskdolibarr_get_actions_by_type()
     * @param  Translate $outputLangs Lang object for output
     * @return array{label: string, color: array, progress: int} Label and color of the state, progress of the action
     */
    protected function actionState($action, Translate $outputLangs): array
    {
        $progress = max(0, min(100, (int) $action->progress));
        $deadline = (!empty($action->datee) && $action->datee !== '0000-00-00 00:00:00') ? $this->db->jdate($action->datee) : 0;

        if ($progress >= 100) {
            return ['label' => $outputLangs->transnoentities('QQOQCPPdfStateDone'), 'color' => $this->doneColor, 'progress' => 100];
        }
        if (!empty($deadline) && $deadline < dol_now()) {
            return ['label' => $outputLangs->transnoentities('QQOQCPPdfStateLate'), 'color' => $this->accentWarn, 'progress' => $progress];
        }
        if ($progress > 0) {
            return ['label' => $outputLangs->transnoentities('QQOQCPPdfStateInProgress'), 'color' => $this->accent, 'progress' => $progress];
        }

        return ['label' => $outputLangs->transnoentities('QQOQCPPdfStateTodo'), 'color' => $this->inkMuted, 'progress' => 0];
    }

    /**
     * Line of details of an action: reference, assignee, dates and budget, the empty ones left out. No colon between a
     * label and its value: its spacing differs from a language to another
     *
     * @param  stdClass  $action      Action, from digiriskdolibarr_get_actions_by_type()
     * @param  Translate $outputLangs Lang object for output
     * @return string                 Details, separated by a middle dot
     */
    protected function actionDetails($action, Translate $outputLangs): string
    {
        global $conf;

        $details = [(string) $action->ref];

        $assignee = trim((string) ($action->user_fullname ?? ''));
        if ($assignee !== '') {
            $details[] = $outputLangs->transnoentities('DigiriskActionAssignedTo') . ' ' . $assignee;
        }

        // Dates written by dol_mktime() in the input timezone, deadline at 23:59:59: read back in that timezone ('auto'),
        // as the Actions tab does. Read in the browser timezone, the deadline moved to the next day east of the server
        foreach (['DateStart' => $action->dateo, 'ActionDeadline' => $action->datee] as $labelKey => $dateValue) {
            if (!empty($dateValue) && $dateValue !== '0000-00-00 00:00:00') {
                $details[] = $outputLangs->transnoentities($labelKey) . ' ' . dol_print_date($this->db->jdate($dateValue), 'day', 'auto', $outputLangs);
            }
        }

        if ($action->budget_amount !== null && $action->budget_amount !== '') {
            $details[] = $outputLangs->transnoentities('Budget') . ' ' . price($action->budget_amount, 0, $outputLangs, 1, -1, -1, $conf->currency);
        }

        return implode('   ·   ', $details);
    }

    /**
     * Pills of the tags of an action, each tinted with the color of its category
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $tags  Tags, from digiriskdolibarr_get_actions_tags()
     * @param  float $x     Left of the area
     * @param  float $y     Top of the area
     * @param  float $width Width of the area
     * @param  float $size  Font size
     * @param  bool  $draw  Draw the pills, false only measures them
     * @return float        Height of the pills
     */
    protected function tagChips($pdf, array $tags, float $x, float $y, float $width, float $size, bool $draw): float
    {
        $height   = 5;
        $gap      = 1.6;
        $fontSize = $size - 2.5;
        $cursorX  = $x;
        $cursorY  = $y;

        foreach ($tags as $tag) {
            $label = (string) $tag['label'];
            $pdf->SetFont('', 'B', $fontSize);
            $chipWidth = min($width, $pdf->GetStringWidth($label) + 6);
            if ($cursorX > $x && $cursorX + $chipWidth > $x + $width) {
                $cursorX  = $x;
                $cursorY += $height + $gap;
            }

            if ($draw) {
                $color = $this->hexColor((string) $tag['color']) ?: $this->inkMuted;
                $this->pill($pdf, $cursorX, $cursorY, $chipWidth, $height, $label, $this->tint($color, 0.8), $this->shade($color, 0.45), $fontSize);
            }
            $cursorX += $chipWidth + $gap;
        }

        return $cursorY + $height - $y;
    }

    /**
     * Title of a section, as sectionTitle() draws it, with a counter on its right
     *
     * @param  TCPDF  $pdf     PDF handler
     * @param  string $title   Title
     * @param  string $counter Text on the right of the title, empty for none
     * @param  float  $size    Font size
     * @param  array  $color   Color of the accent stroke
     * @param  float  $needed  Height to keep under the title, so that it never ends a page alone
     * @return void
     */
    protected function titleWithCounter($pdf, string $title, string $counter, float $size, array $color, float $needed = 42)
    {
        $this->checkPageBreak($pdf, $needed);

        $pdf->Ln(4);
        $y     = $pdf->GetY();
        $width = $this->contentWidth($pdf);

        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->RoundedRect($this->marge_gauche, $y, 11, 1.3, 0.65, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 3);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($this->marge_gauche, $y + 3.4);
        $pdf->Cell($width - 50, 7, $title, 0, 0, 'L');

        if ($counter !== '') {
            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($this->marge_gauche + $width - 50, $y + 3.4);
            $pdf->Cell(50, 7, $counter, 0, 0, 'R');
        }
        $pdf->SetTextColor(0, 0, 0);

        $this->hairline($pdf, $y + 10.9);
        $pdf->SetXY($this->marge_gauche, $y + 13.4);
    }

    /**
     * Color of a type of action
     *
     * @param  int   $type digirisk_action_type: 2 = corrective, 3 = preventive
     * @return array       Color
     */
    protected function actionTypeColor(int $type): array
    {
        return $type === 2 ? $this->correctiveColor : $this->accentAlt;
    }

    /**
     * Darken a color towards the black
     *
     * @param  array $color Color
     * @param  float $ratio Share of black, from 0 to 1
     * @return array        Darkened color
     */
    protected function shade(array $color, float $ratio): array
    {
        return [
            (int) round($color[0] * (1 - $ratio)),
            (int) round($color[1] * (1 - $ratio)),
            (int) round($color[2] * (1 - $ratio))
        ];
    }

    /**
     * Color of a category, stored as a hexadecimal code
     *
     * @param  string $hex Code, with or without its #
     * @return array       Color, empty when the code is not a color
     */
    protected function hexColor(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return [];
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    /**
     * Cut a text to a number of lines, an ellipsis ending it
     *
     * @param  TCPDF  $pdf      PDF handler, its font set
     * @param  string $text     Text
     * @param  float  $width    Width of a line
     * @param  int    $maxLines Lines to keep
     * @return string           Text, cut when it is longer
     */
    protected function fitLines($pdf, string $text, float $width, int $maxLines): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($text === '' || $pdf->getNumLines($text, $width) <= $maxLines) {
            return $text;
        }

        $words = explode(' ', $text);
        while (count($words) > 1) {
            array_pop($words);
            $candidate = implode(' ', $words) . '...';
            if ($pdf->getNumLines($candidate, $width) <= $maxLines) {
                return $candidate;
            }
        }

        return $text;
    }
}
