<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/riskassessmentdocument/pdf_riskassessmentdocumentpdf.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Document unique en PDF natif A4 portrait, a la charte du mode d'emploi et de la
 *          presentation CSE.
 *
 *          Il ne porte que les resultats de l'evaluation : l'etablissement, son organisation, les
 *          tableaux de bord, les risques hierarchises avec le programme de prevention et les
 *          registres. Tout ce qui explique comment les lire est dans le mode d'emploi : aucune
 *          phrase d'explication ici, et une section sans donnee n'est pas imprimee.
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

/**
 * Classe de generation du document unique en PDF.
 */
class pdf_riskassessmentdocumentpdf extends ModelePDFDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'riskassessmentdocument';

    /**
     * Hauteur du bandeau de couverture, en mm.
     */
    const COVER_BAND = 112;

    /**
     * Place restante, en mm, en dessous de laquelle une section s'ouvre sur une nouvelle page.
     */
    const SECTION_MIN_SPACE = 110;

    /**
     * @var array Page de debut de chaque section, pour le sommaire de couverture
     */
    protected array $sectionPages = [];

    /**
     * @var float Ordonnee du sommaire de couverture, posee par cover()
     */
    protected float $coverSummaryY = 0;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'riskassessmentdocumentpdf';
        $this->description = $langs->trans('DigiriskDuPDFDescription');

        $this->orientation  = 'P';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 14;
        $this->marge_droite = 14;
        $this->marge_haute  = 14;
        $this->marge_basse  = 14;

        $this->footerFirstPage = 2;
    }

    /**
     * Ecrit le document sur disque.
     *
     * @param  SaturneDocuments $objectDocument  Document source
     * @param  Translate        $outputLangs     Lang object
     * @param  string           $srcTemplatePath Inutilise, ce modele porte sa mise en page
     * @param  int              $hideDetails     Non utilise
     * @param  int              $hideDesc        Non utilise
     * @param  int              $hideRef         Non utilise
     * @param  array            $moreParam       Object/user/etc
     * @return int                               1 si OK, <= 0 si KO
     * @throws Exception
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hideDetails = 0, $hideDesc = 0, $hideRef = 0, $moreParam = []): int
    {
        global $action, $hookmanager, $langs, $mysoc;

        $moreParam = self::getMoreParam($objectDocument, $moreParam);
        $object    = $moreParam['object'];

        $outputLangs->loadLangs(['main', 'companies', 'projects', 'other', 'ticket', 'digiriskdolibarr@digiriskdolibarr']);

        $moreParam['hideTemplateName'] = 1;
        $moreParam['documentName']     = $outputLangs->transnoentities('DigiriskDuFileName') . '_';
        $object->module                = $this->module;

        $refOrig = $object->ref;
        if (preg_match('/^specimen/i', $object->ref)) {
            $object->ref = 'specimen';
        }

        $file        = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreParam);
        $object->ref = $refOrig;
        if (!is_string($file) || empty($file)) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        // Les champs saisis sur la fiche du DU (emetteur, destinataires, methodologie...) sont
        // ceux que le gabarit ODT lit dans le JSON du document
        $objectDocument->DigiriskFillJSON();
        $header = json_decode((string) $objectDocument->json, true)['RiskAssessmentDocument'] ?? [];

        $stats             = $this->loadDigiriskStatistics($moreParam);
        $stats['levelOf']  = $this->levelByRisk($stats);
        $stats['tickets']  = $this->loadTickets();
        $size              = pdf_getPDFFontSize($outputLangs);
        $pdf               = $this->openPdf($object, $outputLangs, $outputLangs->transnoentities('DigiriskDuTitle'));

        $this->footerText = $outputLangs->transnoentities('DigiriskDuTitle') . ' - ' . $mysoc->name;

        $this->cover($pdf, $object, $outputLangs, $size, $stats, $header);

        $this->sectionEstablishment($pdf, $object, $outputLangs, $size, $header);
        $this->sectionOrganisation($pdf, $outputLangs, $size, $stats);
        $this->sectionDashboards($pdf, $outputLangs, $size, $stats);
        $this->sectionRisks($pdf, $outputLangs, $size, $stats);
        $this->sectionRegisters($pdf, $outputLangs, $size, $stats);

        $this->coverSummary($pdf, $outputLangs, $size);
        $this->_pagefooter($pdf, $object, $outputLangs, $size);

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
     * Les sections du document : cle du titre, dans l'ordre du sommaire.
     *
     * @return array Sections, numerotees a partir de 1
     */
    protected function sections(): array
    {
        return [
            1 => 'DigiriskDuSectionEstablishment',
            2 => 'DigiriskDuSectionOrganisation',
            3 => 'DigiriskDuSectionDashboards',
            4 => 'DigiriskDuSectionRisks',
            5 => 'DigiriskDuSectionRegisters'
        ];
    }

    /**
     * Niveau de chaque risque, lu dans le classement par niveau deja calcule.
     *
     * @param  array $stats Chiffres du document unique
     * @return array        Niveau par identifiant de risque
     */
    protected function levelByRisk(array $stats): array
    {
        $levels = [];
        foreach ($stats['riskLevels'] as $level => $riskLines) {
            foreach ($riskLines as $riskLine) {
                $levels[(int) $riskLine->id] = (int) $level;
            }
        }

        return $levels;
    }

    /**
     * Registres sante securite : les tickets du projet dedie.
     *
     * @return array Tickets
     */
    protected function loadTickets(): array
    {
        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_PROJECT')) {
            return [];
        }

        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

        return load_ticket_infos()['tickets'] ?? [];
    }

    /**
     * Couverture : bandeau de titre, fiche du document, chiffres cles.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @param  array     $header      Champs de la fiche du DU
     * @return void
     */
    protected function cover($pdf, $object, Translate $outputLangs, float $size, array $stats, array $header)
    {
        global $mysoc;

        $pageWidth = $pdf->GetPageWidth();
        $width     = $this->contentWidth($pdf);
        $white     = [255, 255, 255];
        $light     = $this->tint($this->accent, 0.62);

        $pdf->SetAutoPageBreak(false);

        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->Rect(0, 0, $pageWidth, self::COVER_BAND, 'F');

        $pdf->SetAlpha(0.10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($pageWidth - 18, 20, 54, 0, 360, 'F');
        $pdf->Circle($pageWidth - 60, 92, 26, 0, 360, 'F');
        $pdf->SetAlpha(1);

        $logo = $this->companyLogoPath($object);
        if (!empty($logo)) {
            $logoWidth = $this->companyLogoSize($logo, 13, 60)[0];
            $this->card($pdf, $this->marge_gauche, 14, $logoWidth + 8, 19, $white, 3);
            $this->companyLogo($pdf, $object, $this->marge_gauche + 4, 17, 13, 60);
        }

        $this->kicker($pdf, $this->marge_gauche, 44, $width, $outputLangs->transnoentities('DigiriskGuideKicker'), $size + 1.5, $light);

        $pdf->SetFont('', 'B', $size + 26);
        $pdf->SetTextColor($white[0], $white[1], $white[2]);
        $pdf->SetXY($this->marge_gauche, 51);
        $pdf->Cell($width, 15, $outputLangs->transnoentities('DigiriskCseCoverTitle'), 0, 1, 'L');

        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->RoundedRect($this->marge_gauche, 72, 26, 1.6, 0.8, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 5);
        $pdf->SetXY($this->marge_gauche, 78);
        $pdf->Cell($width, 9, $mysoc->name, 0, 1, 'L');

        $period = $this->auditPeriodLabel($stats, $outputLangs);
        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetX($this->marge_gauche);
        // La date de generation se formate ici, dans la langue du document : celle de l'en-tete
        // du DU est formatee dans la langue de l'utilisateur, d'ou un "10/30/2026 02:37 PM" dans
        // un document francais
        $pdf->Cell($width, 6, (dol_strlen($period) ? $period . '  -  ' : '') . $outputLangs->transnoentities('DigiriskSynthesisGeneratedAt') . ' ' . dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputLangs), 0, 1, 'L');

        // Fiche du document : qui l'emet, a qui il est destine
        $y           = self::COVER_BAND + 10;
        $recipients  = array_values(array_filter(explode("\n", (string) ($header['destinataireDUER'] ?? ''))));
        $phones      = array_values(array_filter(explode("\n", (string) ($header['telephone'] ?? ''))));
        $mobiles     = array_values(array_filter(explode("\n", (string) ($header['portable'] ?? ''))));
        $recipientLines = [];
        foreach ($recipients as $index => $recipient) {
            $contact = array_filter([$phones[$index] ?? '', $mobiles[$index] ?? ''], function ($value) {
                return $value !== '' && $value !== '-';
            });
            $recipientLines[] = trim($recipient) . (!empty($contact) ? '  -  ' . implode(' / ', $contact) : '');
        }

        $cards = [
            ['kicker' => $outputLangs->transnoentities('DigiriskDuIssuer'), 'title' => trim((string) ($header['emetteurDUER'] ?? '')) ?: '-'],
            ['kicker' => $outputLangs->transnoentities('DigiriskDuRecipients'), 'title' => '', 'lines' => $recipientLines ?: ['-'], 'color' => $this->accentAlt]
        ];
        $pdf->SetY($y);
        $this->cardGrid($pdf, $cards, 2, $size);

        // Chiffres cles
        $doneShare = $stats['actionPlan']['total'] > 0 ? round($stats['actionPlan']['done'] * 100 / $stats['actionPlan']['total']) . ' %' : '-';
        $this->statTileRow($pdf, [
            ['value' => (string) $stats['totalRisks'],          'label' => $outputLangs->transnoentities('DigiriskSynthesisRisksAssessed'),    'color' => $this->accent],
            ['value' => (string) $stats['nbGroupment'],         'label' => $outputLangs->transnoentities('DigiriskSynthesisGroupmentsCount'),  'color' => $this->accentAlt],
            ['value' => (string) $stats['nbWorkunit'],          'label' => $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount'),   'color' => $this->accentAlt],
            ['value' => (string) $stats['actionPlan']['total'], 'label' => $outputLangs->transnoentities('DigiriskSynthesisActionsTotal'),     'color' => $this->accent],
            ['value' => $doneShare,                             'label' => $outputLangs->transnoentities('DigiriskSynthesisActionsDoneShare'), 'color' => $this->accent]
        ], 30, $size);

        $this->coverSummaryY = $pdf->GetY() + 4;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Sommaire de la couverture, ecrit une fois les pages des sections connues.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function coverSummary($pdf, Translate $outputLangs, float $size)
    {
        $currentPage = $pdf->getPage();

        $pdf->setPage(1);
        $pdf->SetAutoPageBreak(false);

        $width = $this->contentWidth($pdf);
        $y     = $this->coverSummaryY;

        $this->kicker($pdf, $this->marge_gauche, $y, $width, $outputLangs->transnoentities('DigiriskCseAgenda'), $size + 1, $this->accent);
        $y += 9;

        foreach ($this->sections() as $number => $key) {
            if (empty($this->sectionPages[$number])) {
                continue;
            }

            $pdf->SetFont('', 'B', $size + 6);
            $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->SetXY($this->marge_gauche, $y);
            $pdf->Cell(14, 8, sprintf('%02d', $number), 0, 0, 'L');

            $pdf->SetFont('', 'B', $size + 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($this->marge_gauche + 15, $y);
            $pdf->Cell($width - 40, 8, $outputLangs->transnoentities($key), 0, 0, 'L');

            $pdf->SetFont('', 'B', $size);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($this->marge_gauche + $width - 20, $y);
            $pdf->Cell(20, 8, 'p. ' . $this->sectionPages[$number], 0, 0, 'R');

            $this->hairline($pdf, $y + 9.5);
            $y += 11.5;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setPage($currentPage);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Ouvre une section : grand numero, surtitre, titre, filet.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $number      Numero de la section
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  bool      $newPage     Toujours ouvrir une nouvelle page
     * @return void
     */
    protected function section($pdf, int $number, Translate $outputLangs, float $size, bool $newPage = false)
    {
        $available = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY();
        if ($newPage || $pdf->getPage() <= 1 || $available < self::SECTION_MIN_SPACE) {
            $this->newPage($pdf);
        } else {
            $pdf->Ln(10);
        }
        $this->sectionPages[$number] = $pdf->getPage();

        $width = $this->contentWidth($pdf);
        $y     = $pdf->GetY();

        $pdf->SetFont('', 'B', $size + 28);
        $pdf->SetTextColor(...$this->tint($this->accent, 0.8));
        $pdf->SetXY($this->marge_gauche - 1, $y - 2);
        $pdf->Cell(32, 17, sprintf('%02d', $number), 0, 0, 'L');

        $this->kicker($pdf, $this->marge_gauche + 32, $y + 1, $width - 32, $outputLangs->transnoentities('DigiriskDuSectionKicker', (string) $number), $size + 1, $this->accent);

        $pdf->SetFont('', 'B', $size + 9);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($this->marge_gauche + 32, $y + 6);
        $pdf->Cell($width - 32, 9, $outputLangs->transnoentities($this->sections()[$number]), 0, 1, 'L');

        $this->hairline($pdf, $y + 19, 0.4, $this->accent);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $y + 23);
    }

    /**
     * Section 1 : l'etablissement, tel que saisi sur la fiche du document unique.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $header      Champs de la fiche du DU
     * @return void
     */
    protected function sectionEstablishment($pdf, $object, Translate $outputLangs, float $size, array $header)
    {
        global $conf;

        $this->section($pdf, 1, $outputLangs, $size, true);

        $cards = [];
        foreach (['methodologie' => 'DigiriskDuMethod', 'sources' => 'DigiriskDuSources', 'remarqueImportante' => 'DigiriskDuImportantNote'] as $field => $key) {
            // Les valeurs par defaut de la configuration sont des cles de traduction, dont le texte
            // porte du HTML : les retours a la ligne sont gardes, les balises et les liens retires
            $value = $outputLangs->transnoentities(trim((string) ($header[$field] ?? '')));
            $value = preg_replace('/<br\s*\/?>/i', "
", $value);
            $value = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (dol_strlen($value)) {
                $cards[] = ['kicker' => $outputLangs->transnoentities($key), 'text' => $value, 'color' => $field == 'remarqueImportante' ? $this->accentWarn : $this->accent];
            }
        }
        if (!empty($cards)) {
            $this->cardGrid($pdf, $cards, 1, $size + 0.5);
        }

        // Plan du site, s'il y en a un
        $plans = dol_dir_list(($conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '') . '/riskassessmentdocument/siteplans', 'files', 0, '\.(png|jpe?g|gif)$', '', 'date', SORT_ASC);
        if (!empty($plans)) {
            $plan = $plans[0]['fullname'];
            $this->sectionTitle($pdf, $outputLangs->transnoentities('DigiriskDuSitePlan'), $size, 90);
            $maxWidth  = $this->contentWidth($pdf);
            $maxHeight = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY() - 4;
            $imageSize = @getimagesize($plan);
            if (!empty($imageSize[0]) && $maxHeight > 30) {
                $ratio  = min($maxWidth / $imageSize[0], $maxHeight / $imageSize[1]);
                $pdf->Image($plan, $this->marge_gauche, $pdf->GetY() + 2, $imageSize[0] * $ratio, $imageSize[1] * $ratio);
                $pdf->SetY($pdf->GetY() + ($imageSize[1] * $ratio) + 6);
            }
        }
    }

    /**
     * Section 2 : l'organisation, groupements et unites de travail en arborescence.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function sectionOrganisation($pdf, Translate $outputLangs, float $size, array $stats)
    {
        if (empty($stats['elements'])) {
            return;
        }

        $this->section($pdf, 2, $outputLangs, $size);

        $width = $this->contentWidth($pdf);
        $rowH  = 8.5;

        foreach ($stats['elements'] as $elementId => $element) {
            $this->reserveSpace($pdf, $rowH);

            $isGroupment = ($element['object']->element_type ?? '') == 'groupment';
            $depth       = (int) ($element['depth'] ?? 0);
            $x           = $this->marge_gauche + ($depth * 8);
            $y           = $pdf->GetY();
            $color       = $isGroupment ? $this->accent : $this->accentAlt;

            // Filet de rattachement au parent
            if ($depth > 0) {
                $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
                $pdf->SetLineWidth(0.4);
                $pdf->Line($x - 5, $y - 1, $x - 5, $y + ($rowH / 2));
                $pdf->Line($x - 5, $y + ($rowH / 2), $x - 1, $y + ($rowH / 2));
                $pdf->SetLineWidth(0.2);
            }

            $pdf->SetFont('', 'B', $size - 1.5);
            $refWidth = max(14, $pdf->GetStringWidth($element['object']->ref) + 6);
            $this->pill($pdf, $x, $y + 1.2, $refWidth, 6, $element['object']->ref, $this->tint($color, 0.85), $color, $size - 1.5);

            $pdf->SetFont('', $isGroupment ? 'B' : '', $size);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x + $refWidth + 3, $y);
            $pdf->Cell($width - ($x - $this->marge_gauche) - $refWidth - 40, $rowH, $element['object']->label, 0, 0, 'L');

            $count = count($stats['byElement'][$elementId] ?? []);
            if ($count > 0) {
                $pdf->SetFont('', '', $size - 1.5);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($this->marge_gauche + $width - 36, $y);
                $pdf->Cell(36, $rowH, $outputLangs->transnoentities('DigiriskDuNbRisks', (string) $count), 0, 0, 'R');
            }

            $pdf->SetY($y + $rowH);
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Section 3 : les tableaux de bord.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function sectionDashboards($pdf, Translate $outputLangs, float $size, array $stats)
    {
        global $mysoc;

        if (empty($stats['totalRisks'])) {
            return;
        }

        $this->section($pdf, 3, $outputLangs, $size, true);
        $width = $this->contentWidth($pdf);

        // Totaux par niveau : anneau et legende, fiche de l'etablissement a droite
        $this->sectionTitle($pdf, $outputLangs->transnoentities('DigiriskDuByLevel'), $size, 70);
        $y      = $pdf->GetY() + 2;
        $radius = 25;
        $slices = [];
        $items  = [];
        foreach ([4, 3, 2, 1] as $level) {
            $count    = (int) $stats['levelCounts'][$level];
            $slices[] = ['value' => $count, 'color' => $this->levelBg[$level]];
            $items[]  = ['label' => $outputLangs->transnoentities($this->levelLabelKey($level)), 'value' => $count . '  (' . round($count * 100 / $stats['totalRisks']) . ' %)', 'color' => $this->levelBg[$level]];
        }
        $this->donut($pdf, $this->marge_gauche + $radius + 2, $y + $radius, $radius, $slices, (string) $stats['totalRisks'], $outputLangs->transnoentities('DigiriskSynthesisTotalRisks'), $size + 2);
        $this->legend($pdf, $this->marge_gauche + (2 * $radius) + 10, $y + 7, 70, $items, $size, 9);

        $cardX = $this->marge_gauche + (2 * $radius) + 88;
        $cardW = $width - (2 * $radius) - 88;
        $card  = ['kicker' => $outputLangs->transnoentities('DigiriskDuSite'), 'title' => $mysoc->name, 'lines' => array_filter([
            dol_strlen($mysoc->idprof2) ? $outputLangs->transnoentities('ProfId2FR') . ' : ' . $mysoc->idprof2 : '',
            $outputLangs->transnoentities('DigiriskDuEmployeesInvolved', (string) $stats['nbEvaluators'])
        ])];
        $this->drawCard($pdf, $card, $cardX, $y, $cardW, 2 * $radius, $size, false);
        $pdf->SetXY($this->marge_gauche, $y + (2 * $radius) + 6);

        // Risques par unite : somme des cotations et nombre par niveau
        $rows = [];
        $totals = [];
        foreach ($stats['byElement'] as $elementId => $riskLines) {
            if (empty($stats['elements'][$elementId])) {
                continue;
            }
            $levels = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            $sum    = 0;
            foreach ($riskLines as $riskLine) {
                $sum += (int) $riskLine->riskAssessmentCotation;
                $levels[$stats['levelOf'][(int) $riskLine->id] ?? 1]++;
            }
            $totals[$elementId] = ['sum' => $sum, 'levels' => $levels];
        }
        uasort($totals, function ($first, $second) {
            return $second['sum'] <=> $first['sum'];
        });
        foreach ($totals as $elementId => $total) {
            $rows[] = array_merge(
                [$this->elementLabel($stats['elements'][$elementId], false), ['text' => (string) $total['sum'], 'align' => 'C', 'bold' => true]],
                $this->levelCountCells($total['levels'])
            );
        }

        $this->sectionTitle($pdf, $outputLangs->transnoentities('DigiriskDuByUnit'), $size, 50);
        $this->table($pdf, array_merge([$outputLangs->transnoentities('DigiriskDuUnit'), ['text' => $outputLangs->transnoentities('DigiriskDuSum'), 'align' => 'C']], $this->levelHeaders($outputLangs)),
            $rows, array_merge([$width * 0.48, $width * 0.12], array_fill(0, 4, $width * 0.10)), $size - 0.5);

        // Risques par famille : les familles representees seulement
        $risk     = new Risk($this->db);
        $byFamily = [];
        foreach ($stats['risks'] as $riskLine) {
            $position = (int) $riskLine->category;
            $byFamily[$position] = $byFamily[$position] ?? [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            $byFamily[$position][$stats['levelOf'][(int) $riskLine->id] ?? 1]++;
        }
        uasort($byFamily, function ($first, $second) {
            return array_sum($second) <=> array_sum($first);
        });
        $rows = [];
        foreach ($byFamily as $position => $levels) {
            $count  = array_sum($levels);
            $rows[] = array_merge([
                $this->pictoCell($this->categoryPictoByPosition($risk, $position)),
                $this->categoryLabel($risk, $position),
                ['text' => str_replace('.', $outputLangs->transnoentitiesnoconv('SeparatorDecimal'), (string) round($count * 100 / $stats['totalRisks'], 1)) . ' %', 'align' => 'C'],
                ['text' => (string) $count, 'align' => 'C', 'bold' => true]
            ], $this->levelCountCells($levels));
        }
        $rows[] = array_merge(['', ['text' => $outputLangs->transnoentities('Total'), 'bold' => true], ['text' => '100 %', 'align' => 'C', 'bold' => true], ['text' => (string) $stats['totalRisks'], 'align' => 'C', 'bold' => true]],
            $this->levelCountCells($stats['levelCounts']));

        $pdf->Ln(4);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('DigiriskDuByFamily'), $size, 50);
        $this->table($pdf, array_merge(['', $outputLangs->transnoentities('DigiriskDuDangerFamily'), ['text' => '%', 'align' => 'C'], ['text' => $outputLangs->transnoentities('DigiriskDuCount'), 'align' => 'C']], $this->levelHeaders($outputLangs)),
            $rows, array_merge([$width * 0.07, $width * 0.33, $width * 0.11, $width * 0.09], array_fill(0, 4, $width * 0.10)), $size - 0.5);

        $this->dashboardPsychosocial($pdf, $outputLangs, $size, $stats);
    }

    /**
     * Entetes des quatre colonnes de niveau, en pastilles de couleur.
     *
     * @param  Translate $outputLangs Lang object
     * @return array                  Entetes
     */
    protected function levelHeaders(Translate $outputLangs): array
    {
        $headers = [];
        foreach ([1, 2, 3, 4] as $level) {
            $headers[] = ['text' => $outputLangs->transnoentities('DigiriskDuLevelShort' . $level), 'align' => 'C'];
        }

        return $headers;
    }

    /**
     * Cellules des quatre niveaux : une pastille de la couleur du niveau, vide a zero.
     *
     * @param  array $levels Nombre par niveau, indexe de 1 a 4
     * @return array         Cellules
     */
    protected function levelCountCells(array $levels): array
    {
        $cells = [];
        foreach ([1, 2, 3, 4] as $level) {
            $count   = (int) ($levels[$level] ?? 0);
            $cells[] = $count > 0
                ? ['pill' => true, 'text' => (string) $count, 'fill' => $this->levelBg[$level], 'color' => $this->levelText[$level]]
                : ['text' => '-', 'align' => 'C', 'color' => $this->inkMuted];
        }

        return $cells;
    }

    /**
     * Tableaux de bord RPS : resultats par unite et par facteur.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function dashboardPsychosocial($pdf, Translate $outputLangs, float $size, array $stats)
    {
        if (empty($stats['rpsByElement'])) {
            return;
        }

        $risk    = new Risk($this->db);
        $factors = Risk::getDangerSubCategories()[Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? [];
        if (empty($factors)) {
            return;
        }

        // Derniere cotation de chaque facteur, par unite
        $units = [];
        foreach ($stats['rpsByElement'] as $elementId => $byFactor) {
            if (empty($stats['elements'][$elementId])) {
                continue;
            }
            $units[$elementId] = [];
            foreach ($factors as $factor) {
                $assessments = $byFactor[$factor['position']] ?? [];
                if (!empty($assessments)) {
                    krsort($assessments);
                    $units[$elementId][$factor['position']] = (int) reset($assessments);
                }
            }
        }
        if (empty($units)) {
            return;
        }

        // Les facteurs en lignes, les unites en colonnes : huit facteurs en colonnes ne tenaient
        // pas sur la largeur d'une page A4. Au dela de cinq unites, le tableau se repete
        $width       = $this->contentWidth($pdf);
        $perTable    = 5;
        $factorWidth = $width * 0.34;

        $pdf->Ln(4);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('DigiriskDuRps'), $size, 70);

        foreach (array_chunk($units, $perTable, true) as $chunk) {
            $unitWidth = min(32, ($width - $factorWidth) / count($chunk));
            $header    = [$outputLangs->transnoentities('DigiriskDuFactor')];
            foreach (array_keys($chunk) as $elementId) {
                $header[] = ['text' => $stats['elements'][$elementId]['object']->ref . ' - ' . $stats['elements'][$elementId]['object']->label, 'align' => 'C'];
            }

            $rows = [];
            foreach ($factors as $factor) {
                $row = [$factor['name']];
                foreach ($chunk as $cotations) {
                    if (!isset($cotations[$factor['position']])) {
                        $row[] = ['text' => '-', 'align' => 'C', 'color' => $this->inkMuted];
                        continue;
                    }
                    $cotation = $cotations[$factor['position']];
                    $level    = $cotation < 48 ? 1 : ($cotation < 51 ? 2 : ($cotation < 80 ? 3 : 4));
                    $row[]    = ['pill' => true, 'text' => html_entity_decode($risk->getDangerSubCategoryScaleLabel($cotation, $outputLangs), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'fill' => $this->levelBg[$level], 'color' => $this->levelText[$level]];
                }
                $rows[] = $row;
            }

            $this->table($pdf, $header, $rows, array_merge([$factorWidth], array_fill(0, count($chunk), $unitWidth)), $size - 1);
            $pdf->Ln(3);
        }
    }

    /**
     * Section 4 : les risques hierarchises et le programme de prevention.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function sectionRisks($pdf, Translate $outputLangs, float $size, array $stats)
    {
        if (empty($stats['totalRisks'])) {
            return;
        }

        $this->section($pdf, 4, $outputLangs, $size, true);

        $risk     = new Risk($this->db);
        $width    = $this->contentWidth($pdf);
        $widths   = [$width * 0.08, $width * 0.07, $width * 0.17, $width * 0.30, $width * 0.38];
        $header   = [
            ['text' => $outputLangs->transnoentities('RiskCotation'), 'align' => 'C'],
            '',
            $outputLangs->transnoentities('DigiriskDuUnit'),
            $outputLangs->transnoentities('DigiriskDuRiskAndAssessment'),
            $outputLangs->transnoentities('DigiriskDuActions')
        ];
        $showDone = getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SHOW_TASK_DONE');

        foreach ([4, 3, 2, 1] as $level) {
            $riskLines = $stats['riskLevels'][$level] ?? [];
            if (empty($riskLines)) {
                continue;
            }

            // Bandeau du niveau : sa couleur, son nom, son effectif
            $this->reserveSpace($pdf, 30);
            $pdf->Ln(2);
            $y = $pdf->GetY();
            $this->pill($pdf, $this->marge_gauche, $y, 56, 8, $outputLangs->transnoentities($this->levelLabelKey($level)) . '  -  ' . count($riskLines), $this->levelBg[$level], $this->levelText[$level], $size);
            $pdf->SetY($y + 11);

            $rows = [];
            foreach ($riskLines as $riskLine) {
                $element = $stats['elements'][$riskLine->fk_element] ?? null;

                $description = $this->riskDescription($riskLine, 400);
                $comment     = trim(strip_tags((string) ($riskLine->riskAssessmentComment ?? '')));
                $family      = $this->categoryLabel($risk, (int) $riskLine->category);
                $text        = $riskLine->ref . ' - ' . $riskLine->riskAssessmentRef . "\n" . $family;
                // Une description qui ne fait que repeter la famille n'apporte rien
                if (dol_strlen($description) && dol_strtolower($description) !== dol_strtolower($family)) {
                    $text .= "\n" . $description;
                }
                if (dol_strlen($comment)) {
                    $date  = !empty($riskLine->riskAssessmentDate) ? $riskLine->riskAssessmentDate : ($riskLine->riskAssessmentDateCreation ?? 0);
                    $text .= "\n" . ($date ? dol_print_date($date, 'day', 'tzuser', $outputLangs) . ' : ' : '') . $comment;
                }

                $rows[] = [
                    $this->cotationCell($riskLine->riskAssessmentCotation, $level),
                    $this->pictoCell($this->categoryPictoPath($risk, $riskLine)),
                    $element ? $this->elementLabel($element, false) : '',
                    $text,
                    $this->taskLines((int) $riskLine->id, $stats['riskTasks'], $outputLangs, (bool) $showDone)
                ];
            }

            $this->table($pdf, $header, $rows, $widths, $size - 1);
        }
    }

    /**
     * Actions d'un risque : en cours puis terminees, une par ligne.
     *
     * @param  int       $riskId      Identifiant du risque
     * @param  array     $riskTasks   Taches du plan d'actions, par identifiant de risque
     * @param  Translate $outputLangs Lang object
     * @param  bool      $showDone    Lister aussi les actions terminees
     * @return string                 Actions, un tiret s'il n'y en a aucune
     */
    protected function taskLines(int $riskId, array $riskTasks, Translate $outputLangs, bool $showDone): string
    {
        $ongoing = [];
        $done    = [];
        foreach ($riskTasks[$riskId] ?? [] as $task) {
            if ((float) $task->progress >= 100) {
                $done[] = '- ' . $task->label;
                continue;
            }
            $details   = [((int) $task->progress) . ' %'];
            if (!empty($task->datee)) {
                $details[] = dol_print_date($task->datee, 'day', 'tzuser', $outputLangs);
            }
            $ongoing[] = '- ' . $task->label . ' (' . implode(', ', $details) . ')';
        }

        $lines = [];
        if (!empty($ongoing)) {
            $lines[] = $outputLangs->transnoentities('DigiriskDuOngoing');
            $lines   = array_merge($lines, $ongoing);
        }
        if ($showDone && !empty($done)) {
            $lines[] = $outputLangs->transnoentities('DigiriskDuDone');
            $lines   = array_merge($lines, $done);
        }

        return !empty($lines) ? implode("\n", $lines) : '-';
    }

    /**
     * Section 5 : les registres sante securite.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function sectionRegisters($pdf, Translate $outputLangs, float $size, array $stats)
    {
        if (empty($stats['tickets'])) {
            return;
        }

        $this->section($pdf, 5, $outputLangs, $size);

        $rows = [];
        foreach ($stats['tickets'] as $ticket) {
            // Le statut est traduit dans la langue du document : getLibStatut() passe par la
            // langue de l'utilisateur, ou ticket n'est pas forcement charge, et imprimait alors
            // des cles brutes comme "SolvedClosed". L'avancement a sa propre colonne
            $status = $ticket->labelStatusShort[$ticket->status] ?? '';

            $rows[] = [
                ['text' => $ticket->ref, 'bold' => true],
                ['text' => dol_print_date($ticket->datec, 'day', 'tzuser', $outputLangs), 'align' => 'C'],
                $ticket->digiriskElementRefLabel ?? '',
                $ticket->subject . (dol_strlen(trim(strip_tags((string) $ticket->message))) ? "\n" . dol_trunc(trim(strip_tags((string) $ticket->message)), 160) : ''),
                ['text' => ((int) $ticket->progress) . ' %', 'align' => 'C'],
                dol_strlen($status) ? $outputLangs->transnoentitiesnoconv($status) : ''
            ];
        }

        // La date et le statut tiennent sur une ligne : a 11 et 10 % de la largeur, l'annee
        // passait a la ligne et "Resolu/Ferme" se coupait en deux
        $width = $this->contentWidth($pdf);
        $this->table($pdf, [
            $outputLangs->transnoentities('Ref'),
            ['text' => $outputLangs->transnoentities('Date'), 'align' => 'C'],
            $outputLangs->transnoentities('DigiriskDuUnit'),
            $outputLangs->transnoentities('Subject'),
            ['text' => $outputLangs->transnoentities('DigiriskProgress'), 'align' => 'C'],
            $outputLangs->transnoentities('Status')
        ], $rows, [$width * 0.13, $width * 0.13, $width * 0.16, $width * 0.31, $width * 0.12, $width * 0.15], $size - 1.5);
    }
}
