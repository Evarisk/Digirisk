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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/riskassessmentdocument/pdf_riskassessmentdocumentclassic.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Document unique complet en PDF natif : les chapitres et les tableaux du gabarit ODT.
 *
 *          Memes chapitres, memes tableaux et memes donnees que template_riskassessmentdocument.odt :
 *          les donnees sont lues par les memes chargeurs que le generateur ODT, seule la mise en
 *          page change. Le PDF est ecrit directement par TCPDF, sans passer par LibreOffice.
 *
 *          Il suit la charte des autres PDF du document unique (DU-A4-PORTRAIT, mode d'emploi,
 *          presentation CSE) : couverture a bandeau, grands numeros de chapitre, tableaux sans
 *          quadrillage a filets fins, pastilles aux couleurs de cotation. Les chapitres 1 et 2 sont
 *          en portrait, le chapitre 3 (risques et registres) en paysage, comme dans le gabarit.
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_digiriskstandard.lib.php';

/**
 * Classe de generation du document unique complet en PDF.
 */
class pdf_riskassessmentdocumentclassic extends ModelePDFDigiriskDolibarrDocument
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
     * Marge interne horizontale d'une cellule de tableau, en mm. Sans bordure verticale, c'est
     * elle qui separe les colonnes.
     */
    const GRID_PAD_X = 2.2;

    /**
     * Marge interne verticale d'une cellule de tableau, en mm.
     */
    const GRID_PAD_Y = 1.8;

    /**
     * Interligne, en multiple du corps de la police.
     */
    const LINE_SPACING = 1.3;

    /**
     * Largeur minimale d'une pastille de tableau, en mm : un "1" seul reste une pastille.
     */
    const CHIP_MIN_WIDTH = 7;

    /**
     * @var array Fond de chaque niveau RPS, ceux des styles "RPS niveau 1" a "RPS niveau 4" du gabarit
     */
    protected array $rpsFill = [
        1 => [58, 157, 93],
        2 => [233, 173, 79],
        3 => [224, 83, 83],
        4 => [143, 34, 34]
    ];

    /**
     * @var array Encre ecrite sur le fond de chaque niveau RPS
     */
    protected array $rpsInk = [
        1 => [255, 255, 255],
        2 => [58, 42, 0],
        3 => [255, 255, 255],
        4 => [255, 255, 255]
    ];

    /**
     * @var array Fond et encre d'une cellule RPS sans cotation
     */
    protected array $rpsEmpty = ['fill' => [241, 245, 249], 'ink' => [100, 116, 139]];

    /**
     * @var array Entrees du sommaire : niveau, numero, titre, page et lien interne
     */
    protected array $summary = [];

    /**
     * @var array Compteurs de la numerotation des titres, un par niveau
     */
    protected array $headingCounters = [0, 0, 0, 0];

    /**
     * @var int Page reservee au sommaire, ecrite une fois les pages des titres connues
     */
    protected int $summaryPage = 0;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'riskassessmentdocumentclassic';
        $this->description = $langs->trans('DigiriskDuClassicPDFDescription');

        $this->orientation  = 'P';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 14;
        $this->marge_droite = 14;
        $this->marge_haute  = 14;
        $this->marge_basse  = self::FOOTER_BAND;

        // La couverture ne porte pas de pied de page
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
        if (preg_match('/^specimen/i', (string) $object->ref)) {
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

        $data = $this->loadDocumentData($moreParam);
        $size = pdf_getPDFFontSize($outputLangs);
        $pdf  = $this->openPdf($object, $outputLangs, $outputLangs->transnoentities('DigiriskDuTitle'));

        $this->footerText = $outputLangs->transnoentities('DigiriskDuTitle') . ' - ' . $mysoc->name;

        $this->cover($pdf, $object, $outputLangs, $size, $header, $data);

        // Le sommaire prend la deuxieme page, comme dans le gabarit : il s'ecrit a la fin
        $this->newPage($pdf);
        $this->summaryPage = $pdf->getPage();

        $this->chapterAdministrative($pdf, $outputLangs, $size, $header);
        $this->chapterAssessment($pdf, $outputLangs, $size, $data);
        $this->chapterRisks($pdf, $outputLangs, $size, $data);

        $this->writeSummary($pdf, $outputLangs, $size);
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
     * Donnees du document, lues par les chargeurs du generateur ODT.
     *
     * @param  array $moreParam More param (object/user/etc)
     * @return array            Risques, elements et registres, par perimetre (current/shared)
     * @throws Exception
     */
    protected function loadDocumentData(array $moreParam): array
    {
        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

        $showShared = (bool) getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS');

        $moreParam['tmparray']['showSharedRisk_nocheck'] = $showShared;

        $risk            = new Risk($this->db);
        $digiriskElement = new DigiriskElement($this->db);

        $riskInfos    = $risk->loadRiskInfos($moreParam);
        $elementInfos = $digiriskElement->loadDigiriskElementInfos($moreParam);

        $data = [
            'showShared'          => $showShared,
            'current'             => $riskInfos['current'],
            'shared'              => $riskInfos['shared'],
            'riskByEntities'      => $riskInfos['riskByEntities'] ?? [],
            'dangerCategories'    => Risk::getDangerCategories(),
            'dangerSubCategories' => Risk::getDangerSubCategories(),
            'evaluators'          => Evaluator::loadEvaluatorInfos(),
            'tickets'             => load_ticket_infos($moreParam)['tickets'] ?? []
        ];

        $data['current']['elements'] = $elementInfos['current']['digiriskElements'] ?? [];

        // Partager les elements entre entites rend visibles tous ceux des autres entites : seuls
        // ceux qui portent un risque partage, et leurs parents, appartiennent a ce document
        $data['shared']['elements'] = $showShared ? $this->filterSharedElements($elementInfos['shared']['digiriskElements'] ?? [], $riskInfos['shared']['risks'] ?? []) : [];

        return $data;
    }

    /**
     * Elements partages qui portent un risque partage avec l'entite courante, et leurs parents.
     *
     * @param  array $sharedElements Elements partages (id => ['object' => DigiriskElement, 'depth' => int])
     * @param  array $sharedRisks    Risques des autres entites lies a un element de l'entite courante
     * @return array                 Elements gardes, dans leur ordre d'origine
     */
    protected function filterSharedElements(array $sharedElements, array $sharedRisks): array
    {
        if (empty($sharedElements) || empty($sharedRisks)) {
            return [];
        }

        $keptIds = [];
        foreach ($sharedRisks as $sharedRisk) {
            // Les parents sont gardes aussi, sinon l'organigramme partage s'imprime avec des trous
            $elementId = $sharedRisk->fk_element;
            while ($elementId > 0 && isset($sharedElements[$elementId]) && !isset($keptIds[$elementId])) {
                $keptIds[$elementId] = $elementId;
                $elementId           = $sharedElements[$elementId]['object']->fk_parent;
            }
        }

        return array_intersect_key($sharedElements, $keptIds);
    }

    /*
     * Couverture et sommaire
     */

    /**
     * Couverture : bandeau de titre, emetteur et destinataires, chiffres cles.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $header      Champs de la fiche du DU
     * @param  array     $data        Donnees du document
     * @return void
     */
    protected function cover($pdf, $object, Translate $outputLangs, float $size, array $header, array $data)
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

        $logo = $this->coverLogoPath($object);
        if (!empty($logo)) {
            [$logoWidth, $logoHeight] = $this->companyLogoSize($logo, 13, 60);
            $this->card($pdf, $this->marge_gauche, 14, $logoWidth + 8, $logoHeight + 6, $white, 3);
            $pdf->Image($logo, $this->marge_gauche + 4, 17, $logoWidth, $logoHeight);
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
        $pdf->Cell($width, 9, (string) $mysoc->name, 0, 1, 'L');

        // Periode de la demarche, puis date de generation, dans la langue du document
        $period = $this->auditPeriod($outputLangs);
        $line   = (dol_strlen($period) ? $outputLangs->transnoentities('DigiriskDuClassicAuditPeriod', $period) . '  -  ' : '');
        $line  .= $outputLangs->transnoentities('DigiriskSynthesisGeneratedAt') . ' ' . dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputLangs);
        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($width, 6, $line, 0, 1, 'L');

        // Emetteur et destinataires, un destinataire par ligne avec ses numeros
        $recipients = array_values(array_filter(array_map('trim', explode("\n", (string) ($header['destinataireDUER'] ?? '')))));
        $phones     = array_values(array_map('trim', explode("\n", (string) ($header['telephone'] ?? ''))));
        $mobiles    = array_values(array_map('trim', explode("\n", (string) ($header['portable'] ?? ''))));
        $lines      = [];
        foreach ($recipients as $index => $recipient) {
            $numbers = array_filter([$phones[$index] ?? '', $mobiles[$index] ?? ''], function ($value) {
                return $value !== '' && $value !== '-';
            });
            $lines[] = $recipient . (!empty($numbers) ? '  -  ' . implode(' / ', $numbers) : '');
        }

        $pdf->SetY(self::COVER_BAND + 10);
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskDuIssuer'), 'title' => trim((string) ($header['emetteurDUER'] ?? '')) ?: '-'],
            ['kicker' => $outputLangs->transnoentities('DigiriskDuRecipients'), 'title' => '', 'lines' => $lines ?: ['-'], 'color' => $this->accentAlt]
        ], 2, $size);

        // Chiffres cles
        $types      = $this->countElementTypes($data['current']['elements']);
        $actionPlan = $this->summarizeActionPlan($data['current']['riskTasks'] ?? []);
        $doneShare  = $actionPlan['total'] > 0 ? round($actionPlan['done'] * 100 / $actionPlan['total']) . ' %' : '-';
        $this->statTileRow($pdf, [
            ['value' => (string) ((int) $data['current']['totalRisks']), 'label' => $outputLangs->transnoentities('DigiriskSynthesisRisksAssessed'), 'color' => $this->accent],
            ['value' => (string) $types['nbGroupment'], 'label' => $outputLangs->transnoentities('DigiriskSynthesisGroupmentsCount'), 'color' => $this->accentAlt],
            ['value' => (string) $types['nbWorkunit'], 'label' => $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount'), 'color' => $this->accentAlt],
            ['value' => (string) $actionPlan['total'], 'label' => $outputLangs->transnoentities('DigiriskSynthesisActionsTotal'), 'color' => $this->accent],
            ['value' => $doneShare, 'label' => $outputLangs->transnoentities('DigiriskSynthesisActionsDoneShare'), 'color' => $this->accent]
        ], 30, $size);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Periode de la demarche, saisie sur la fiche du DU : "debut - fin".
     *
     * Les deux dates sont des constantes, au format Y-m-d quand elles viennent de la fiche, en
     * timestamp quand DigiriskFillJSON() les a posees faute de saisie.
     *
     * @param  Translate $outputLangs Lang object
     * @return string                 Periode, vide si aucune date n'est connue
     */
    protected function auditPeriod(Translate $outputLangs): string
    {
        $dates = [];
        foreach (['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE'] as $constName) {
            $value = getDolGlobalString($constName);
            if (!dol_strlen($value)) {
                continue;
            }
            $time    = is_numeric($value) ? (int) $value : (int) dol_stringtotime($value, 1);
            $dates[] = $time > 0 ? dol_print_date($time, 'day', 'tzuser', $outputLangs) : '';
        }
        $dates = array_values(array_filter($dates));

        // Une demarche tenue sur une seule journee ne repete pas deux fois la meme date
        if (count($dates) == 2 && $dates[0] == $dates[1]) {
            return $dates[0];
        }

        return implode(' - ', $dates);
    }

    /**
     * Logo de la couverture : le grand logo de la societe, sa vignette a defaut.
     *
     * @param  object $object Objet imprime
     * @return string         Chemin lisible, vide s'il n'y a pas de logo a imprimer
     */
    protected function coverLogoPath($object): string
    {
        global $mysoc;

        if (getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO') || empty($mysoc->logo)) {
            return '';
        }

        $logoDir = getMultidirOutput($object, 'mycompany') ?: '';
        $logo    = $logoDir . '/logos/' . $mysoc->logo;

        return is_readable($logo) ? $logo : $this->companyLogoPath($object);
    }

    /**
     * Sommaire, ecrit sur la page reservee une fois les pages des titres connues.
     *
     * Chaque entree est un lien vers son titre ; le PDF porte aussi les memes entrees en signets.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function writeSummary($pdf, Translate $outputLangs, float $size)
    {
        $currentPage = $pdf->getPage();

        $pdf->setPage($this->summaryPage);
        $pdf->SetAutoPageBreak(false);

        $x      = $this->marge_gauche;
        $width  = $this->contentWidth($pdf);
        $bottom = $this->pageBottom($pdf);

        $this->kicker($pdf, $x, $this->marge_haute + 2, $width, $outputLangs->transnoentities('DigiriskDuTitle'), $size + 1, $this->accent);
        $pdf->SetFont('', 'B', $size + 12);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($x, $this->marge_haute + 7);
        $pdf->Cell($width, 12, $outputLangs->transnoentities('DigiriskDuClassicSummary'), 0, 1, 'L');
        $this->hairline($pdf, $pdf->GetY() + 1, 0.4, $this->accent);

        $y = $pdf->GetY() + 6;
        foreach ($this->summary as $entry) {
            $level  = (int) $entry['level'];
            $height = [0 => 11, 1 => 6.4, 2 => 5.6][$level] ?? 5.6;
            if ($level == 0) {
                $y += 3;
            }
            if ($y + $height > $bottom) {
                break;
            }

            if ($level == 0) {
                // Chapitre : son grand numero d'accent, son titre, sa page, un filet
                $pdf->SetFont('', 'B', $size + 6);
                $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
                $pdf->SetXY($x, $y);
                $pdf->Cell(14, $height - 2, sprintf('%02d', (int) $entry['number']), 0, 0, 'L', false, $entry['link']);

                $pdf->SetFont('', 'B', $size + 1);
                $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
                $pdf->SetXY($x + 15, $y);
                $pdf->Cell($width - 40, $height - 2, $this->fitText($pdf, $entry['title'], $width - 40), 0, 0, 'L', false, $entry['link']);

                $pdf->SetFont('', 'B', $size);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($x + $width - 20, $y);
                $pdf->Cell(20, $height - 2, 'p. ' . $entry['page'], 0, 0, 'R', false, $entry['link']);

                $this->hairline($pdf, $y + $height - 1);
            } else {
                $indent = $level == 1 ? 15 : 25;
                $pdf->SetFont('', $level == 1 ? 'B' : '', $level == 1 ? $size - 0.5 : $size - 1.5);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($x + $indent, $y);
                $numberWidth = $level == 1 ? 9 : 11;
                $pdf->Cell($numberWidth, $height, $entry['number'], 0, 0, 'L', false, $entry['link']);

                $pdf->SetTextColor($level == 1 ? $this->ink[0] : $this->inkSoft[0], $level == 1 ? $this->ink[1] : $this->inkSoft[1], $level == 1 ? $this->ink[2] : $this->inkSoft[2]);
                $titleWidth = $width - $indent - $numberWidth - 22;
                $pdf->Cell($titleWidth, $height, $this->fitText($pdf, $entry['title'], $titleWidth), 0, 0, 'L', false, $entry['link']);

                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($x + $width - 20, $y);
                $pdf->Cell(20, $height, 'p. ' . $entry['page'], 0, 0, 'R', false, $entry['link']);
            }

            $y += $height;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setPage($currentPage);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /*
     * Chapitre 1 : administratif
     */

    /**
     * Chapitre administratif : methodologie, sources, remarque importante, localisation.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $header      Champs de la fiche du DU
     * @return void
     */
    protected function chapterAdministrative($pdf, Translate $outputLangs, float $size, array $header)
    {
        $this->newPage($pdf);
        $this->heading($pdf, 0, $outputLangs->transnoentities('DigiriskDuClassicChapterAdministrative'), $outputLangs, $size);
        $this->text($pdf, $outputLangs->transnoentities('DigiriskDuClassicIntro'), $size - 0.5, 'I', $this->inkMuted);

        foreach (['methodologie' => 'DigiriskDuClassicMethod', 'sources' => 'DigiriskDuSources', 'remarqueImportante' => 'DigiriskDuImportantNote'] as $field => $key) {
            $this->heading($pdf, 1, $outputLangs->transnoentities($key), $outputLangs, $size, 15);

            // Les valeurs par defaut de la configuration sont des cles de traduction
            $this->richText($pdf, $outputLangs->transnoentities(trim((string) ($header[$field] ?? ''))), $size - 0.5);
        }

        $plans = $this->sitePlanPaths();
        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuSitePlan'), $outputLangs, $size, !empty($plans) ? 90 : 15);
        $this->sitePlans($pdf, $outputLangs, $size, $plans);
    }

    /**
     * Plans du site joints sur la fiche du DU, du plus ancien au plus recent.
     *
     * @return array Chemins des images lisibles
     */
    protected function sitePlanPaths(): array
    {
        global $conf;

        $dir   = ($conf->digiriskdolibarr->multidir_output[$conf->entity] ?? '') . '/riskassessmentdocument/siteplans';
        $files = dol_is_dir($dir) ? dol_dir_list($dir, 'files', 0, '\.(png|jpe?g|gif)$', '', 'date', SORT_ASC) : [];

        $plans = [];
        foreach ($files as $file) {
            $size = @getimagesize($file['fullname']);
            if (!empty($size[0]) && !empty($size[1])) {
                $plans[] = $file['fullname'];
            }
        }

        return $plans;
    }

    /**
     * Plans du site, chacun dans un cadre a la taille de la place restante : un plan qui
     * n'aurait plus que la moitie d'une page part sur la suivante, en entier.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $plans       Chemins des images
     * @return void
     */
    protected function sitePlans($pdf, Translate $outputLangs, float $size, array $plans)
    {
        if (empty($plans)) {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoSitePlan'), $size);
            return;
        }

        $pad      = 3;
        $maxWidth = $this->contentWidth($pdf) - (2 * $pad);
        foreach ($plans as $plan) {
            $imageSize = getimagesize($plan);
            $fullRatio = min($maxWidth / $imageSize[0], 200 / $imageSize[1]);
            $fullH     = $imageSize[1] * $fullRatio;

            // Le plan prend la place qui reste s'il y garde au moins 70 % de sa taille
            $available = $this->pageBottom($pdf) - $pdf->GetY() - (2 * $pad) - 4;
            if ($available < min($fullH, 200) * 0.7) {
                $this->newPage($pdf);
                $available = $this->pageBottom($pdf) - $pdf->GetY() - (2 * $pad) - 4;
            }

            $ratio  = min($maxWidth / $imageSize[0], min(200, $available) / $imageSize[1]);
            $width  = $imageSize[0] * $ratio;
            $height = $imageSize[1] * $ratio;
            $y      = $pdf->GetY() + 1;

            $this->card($pdf, $this->marge_gauche, $y, $this->contentWidth($pdf), $height + (2 * $pad), $this->surface, 2);
            $pdf->Image($plan, $this->marge_gauche + $pad + (($maxWidth - $width) / 2), $y + $pad, $width, $height);
            $pdf->SetY($y + $height + (2 * $pad) + 4);
        }
    }

    /*
     * Chapitre 2 : evaluation
     */

    /**
     * Chapitre evaluation : organigrammes et tableaux de bord.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $data        Donnees du document
     * @return void
     * @throws Exception
     */
    protected function chapterAssessment($pdf, Translate $outputLangs, float $size, array $data)
    {
        global $mysoc;

        $this->newPage($pdf);
        $this->heading($pdf, 0, $outputLangs->transnoentities('DigiriskDuClassicChapterAssessment'), $outputLangs, $size);

        // Organigrammes
        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuClassicOrganisationCharts'), $outputLangs, $size, 50);
        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicOrganisationChartCompany', $mysoc->name), $outputLangs, $size);
        $this->organisationTree($pdf, $outputLangs, $size, $data, 'current');

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicOrganisationChartShared'), $outputLangs, $size);
        if (!empty($data['shared']['elements'])) {
            $this->organisationTree($pdf, $outputLangs, $size, $data, 'shared');
        } else {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoSharedRisk'), $size);
        }

        // Tableau de bord
        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuClassicDashboard'), $outputLangs, $size, 65);

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRisksByElement'), $outputLangs, $size, 55);
        $this->heading($pdf, 3, $outputLangs->transnoentities('DigiriskDuClassicForCompany'), $outputLangs, $size, 40);
        $this->risksByElementTable($pdf, $outputLangs, $data, 'current');
        $this->heading($pdf, 3, $outputLangs->transnoentities('DigiriskDuClassicForShared'), $outputLangs, $size, 40);
        if ($data['showShared'] && !empty($data['shared']['riskByRiskAssessmentCotations'])) {
            $this->risksByElementTable($pdf, $outputLangs, $data, 'shared');
        } else {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoSharedRisk'), $size);
        }

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRisksByCategory'), $outputLangs, $size, 55);
        $this->heading($pdf, 3, $outputLangs->transnoentities('DigiriskDuClassicForCompany'), $outputLangs, $size, 40);
        $this->risksByCategoryTable($pdf, $outputLangs, $data, 'current');
        $this->heading($pdf, 3, $outputLangs->transnoentities('DigiriskDuClassicForShared'), $outputLangs, $size, 40);
        if ($data['showShared'] && !empty($data['shared']['riskByCategories'])) {
            $this->risksByCategoryTable($pdf, $outputLangs, $data, 'shared');
        } else {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoSharedRisk'), $size);
        }

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRisksByLevel'), $outputLangs, $size, 45);
        $this->heading($pdf, 3, $outputLangs->transnoentities('DigiriskDuClassicForCompany'), $outputLangs, $size, 30);
        $this->risksByEntityTable($pdf, $outputLangs, $data);

        // Risques psychosociaux
        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRpsCotation'), $outputLangs, $size, 50);
        $this->text($pdf, $outputLangs->transnoentities('DigiriskDuClassicRpsCotationIntro'), $size - 1);
        $this->psychosocialCotationTable($pdf, $outputLangs, $size);

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRpsAssessment'), $outputLangs, $size, 40);
        $this->psychosocialAssessmentTable($pdf, $outputLangs, $size, $data);

        $this->heading($pdf, 2, $outputLangs->transnoentities('DigiriskDuClassicRpsByLevel'), $outputLangs, $size, 50);
        $this->psychosocialByLevelTable($pdf, $outputLangs, $data);
    }

    /**
     * Organigramme en arborescence : la reference de chaque element en pastille, ses risques a
     * droite, un filet le rattache a son parent.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $data        Donnees du document
     * @param  string    $scope       current ou shared
     * @return void
     */
    protected function organisationTree($pdf, Translate $outputLangs, float $size, array $data, string $scope)
    {
        $elements = $data[$scope]['elements'];
        if (empty($elements)) {
            $this->emptyNote($pdf, '-', $size);
            return;
        }

        $width  = $this->contentWidth($pdf);
        $rowH   = 7.6;
        $counts = $data[$scope]['riskByRiskAssessmentCotations'] ?? [];

        foreach ($elements as $elementId => $element) {
            $this->ensureSpace($pdf, $rowH);

            $isGroupment = ($element['object']->element_type ?? '') == 'groupment';
            $depth       = (int) ($element['depth'] ?? 0);
            $x           = $this->marge_gauche + ($depth * 7);
            $y           = $pdf->GetY();
            $color       = $isGroupment ? $this->accent : $this->accentAlt;

            if ($depth > 0) {
                $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
                $pdf->SetLineWidth(0.4);
                $pdf->Line($x - 4.5, $y - 1, $x - 4.5, $y + ($rowH / 2));
                $pdf->Line($x - 4.5, $y + ($rowH / 2), $x - 1, $y + ($rowH / 2));
                $pdf->SetLineWidth(0.2);
            }

            // Un element partage vient d'une autre entite : sa pastille le dit
            $ref = ($scope == 'shared' ? 'S' . $element['object']->entity . ' ' : '') . $element['object']->ref;
            $pdf->SetFont('', 'B', $size - 2.5);
            $refWidth = max(12, $pdf->GetStringWidth($ref) + 5);
            $this->pill($pdf, $x, $y + 1.05, $refWidth, 5.5, $ref, $this->tint($color, 0.85), $color, $size - 2.5);

            $count      = array_sum(array_intersect_key($counts[$elementId] ?? [], [1 => 0, 2 => 0, 3 => 0, 4 => 0]));
            $labelWidth = $width - ($x - $this->marge_gauche) - $refWidth - 34;
            $pdf->SetFont('', $isGroupment ? 'B' : '', $size - 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x + $refWidth + 2.5, $y);
            $pdf->Cell($labelWidth, $rowH, $this->fitText($pdf, (string) $element['object']->label, $labelWidth), 0, 0, 'L');

            if ($count > 0) {
                $pdf->SetFont('', '', $size - 2);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($this->marge_gauche + $width - 30, $y);
                $pdf->Cell(30, $rowH, $outputLangs->transnoentities('DigiriskDuNbRisks', (string) $count), 0, 0, 'R');
            }

            $pdf->SetY($y + $rowH);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($pdf->GetY() + 3);
    }

    /**
     * Nombre de risques par UT/GP : somme des cotations et nombre par niveau, de la plus forte
     * somme a la plus faible.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  array     $data        Donnees du document
     * @param  string    $scope       current ou shared
     * @return void
     */
    protected function risksByElementTable($pdf, Translate $outputLangs, array $data, string $scope)
    {
        $elements = $data[$scope]['elements'];
        $byUnit   = $data[$scope]['riskByRiskAssessmentCotations'] ?? [];
        uasort($byUnit, function ($first, $second) {
            return ($second['totalRiskAssessmentCotations'] ?? 0) <=> ($first['totalRiskAssessmentCotations'] ?? 0);
        });

        $rows = [];
        foreach ($byUnit as $elementId => $counts) {
            if (!isset($elements[$elementId])) {
                continue;
            }
            $rows[] = array_merge([
                ['text' => $this->unitLabel($elements[$elementId], $scope), 'style' => 'B'],
                ['text' => $this->plainText((string) $elements[$elementId]['object']->description), 'size' => 6.8, 'color' => $this->inkMuted],
                ['text' => (string) ((int) ($counts['totalRiskAssessmentCotations'] ?? 0)), 'style' => 'B', 'align' => 'C']
            ], $this->levelCountCells($counts));
        }

        if (empty($rows)) {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoRisk'), 9);
            return;
        }

        $this->gridTable($pdf, array_merge([
            ['width' => 40, 'header' => $outputLangs->transnoentities('DigiriskDuUnit')],
            ['width' => 74, 'header' => $outputLangs->transnoentities('DigiriskDuClassicElementDescription')],
            ['width' => 18, 'header' => $outputLangs->transnoentities('DigiriskDuClassicRiskSum'), 'align' => 'C']
        ], $this->levelColumns($outputLangs, 12.5)), $rows, 7.5);
    }

    /**
     * Pourcentage de risques par famille de danger : toutes les familles, les risques
     * psychosociaux mis a part, avec la ligne des totaux.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  array     $data        Donnees du document
     * @param  string    $scope       current ou shared
     * @return void
     */
    protected function risksByCategoryTable($pdf, Translate $outputLangs, array $data, string $scope)
    {
        $riskByCategories = $data[$scope]['riskByCategories'] ?? [];
        $totalRisks       = (int) ($data[$scope]['totalRisks'] ?? 0);

        $lines = [];
        foreach ($data['dangerCategories'] as $dangerCategory) {
            // Les risques psychosociaux ont leur propre tableau, par facteur
            if ($dangerCategory['position'] == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                continue;
            }
            $lines[] = [
                'picto'  => $this->pictoPath($dangerCategory['thumbnail_name'] ?? ''),
                'name'   => $dangerCategory['name'],
                'counts' => $riskByCategories[$dangerCategory['position']] ?? []
            ];
        }

        $this->breakdownTable($pdf, $outputLangs, $lines, $totalRisks);
    }

    /**
     * Tableau de repartition par famille ou par facteur : picto, libelle, part du total, nombre
     * et nombre par niveau, puis les totaux.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  array     $lines       Lignes ['picto', 'name', 'counts' => nombre par niveau]
     * @param  int       $totalRisks  Nombre de risques du perimetre, base des pourcentages
     * @return void
     */
    protected function breakdownTable($pdf, Translate $outputLangs, array $lines, int $totalRisks)
    {
        $rows        = [];
        $totalShare  = 0;
        $totalCount  = 0;
        $levelTotals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($lines as $line) {
            $count = 0;
            foreach ($levelTotals as $level => $total) {
                $count               += (int) ($line['counts'][$level] ?? 0);
                $levelTotals[$level] += (int) ($line['counts'][$level] ?? 0);
            }
            $share       = ($count > 0 && $totalRisks > 0) ? round(($count / $totalRisks) * 100, 1) : 0;
            $totalShare += $share;
            $totalCount += $count;

            $rows[] = array_merge([
                ['image' => $line['picto']],
                ['text' => $line['name'], 'color' => $count > 0 ? $this->ink : $this->inkMuted],
                ['text' => $this->percent($share, $outputLangs), 'align' => 'C', 'color' => $count > 0 ? $this->inkSoft : $this->inkMuted],
                $this->countCell($count)
            ], $this->levelCountCells($line['counts']));
        }

        if (empty($rows)) {
            $this->emptyNote($pdf, '-', 9);
            return;
        }

        $rows[] = array_merge([
            '_total' => true,
            '',
            ['text' => $outputLangs->transnoentities('DigiriskDuClassicTotals'), 'style' => 'B'],
            ['text' => $this->percent(round($totalShare), $outputLangs), 'style' => 'B', 'align' => 'C'],
            ['text' => (string) $totalCount, 'style' => 'B', 'align' => 'C']
        ], $this->levelCountCells($levelTotals));

        $this->gridTable($pdf, array_merge([
            ['width' => 13, 'header' => ''],
            ['width' => 65, 'header' => $outputLangs->transnoentities('DigiriskDuDangerFamily')],
            ['width' => 18, 'header' => '%', 'align' => 'C'],
            ['width' => 16, 'header' => $outputLangs->transnoentities('DigiriskDuCount'), 'align' => 'C']
        ], $this->levelColumns($outputLangs, 17.5)), $rows, 7.5, ['imageSize' => 7]);
    }

    /**
     * Totaux de risques par niveau : une ligne par etablissement.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  array     $data        Donnees du document
     * @return void
     */
    protected function risksByEntityTable($pdf, Translate $outputLangs, array $data)
    {
        $rows = [];
        foreach ($data['riskByEntities'] as $entity => $counts) {
            $rows[] = array_merge([
                ['text' => (string) dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM', (int) $entity), 'style' => 'B'],
                (string) dolibarr_get_const($this->db, 'MAIN_INFO_SIRET', (int) $entity),
                ['text' => (string) ((int) ($data['evaluators']['nbEvaluatorByEntities'][$entity] ?? 0)), 'align' => 'C'],
                $this->countCell((int) ($counts['nbTotalRisks'] ?? 0))
            ], $this->levelCountCells($counts));
        }

        if (empty($rows)) {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoRisk'), 9);
            return;
        }

        $this->gridTable($pdf, array_merge([
            ['width' => 48, 'header' => $outputLangs->transnoentities('DigiriskDuClassicSite')],
            ['width' => 32, 'header' => $outputLangs->transnoentities('DigiriskDuClassicSiret')],
            ['width' => 24, 'header' => $outputLangs->transnoentities('DigiriskDuClassicEmployeesInvolved'), 'align' => 'C'],
            ['width' => 22, 'header' => $outputLangs->transnoentities('DigiriskDuClassicTotalRisks'), 'align' => 'C']
        ], $this->levelColumns($outputLangs, 14)), $rows, 7.5);
    }

    /**
     * Cotation des RPS par UT/GP : le niveau de chaque facteur et la moyenne des facteurs cotes,
     * comme l'onglet psychosocial de la synthese publique des risques.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     * @throws Exception
     */
    protected function psychosocialCotationTable($pdf, Translate $outputLangs, float $size)
    {
        global $db, $langs;

        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_riskoverview.lib.php';

        $digiriskElement           = new DigiriskElement($this->db);
        $activeDigiriskElementList = $digiriskElement->getActiveDigiriskElements();
        if (!is_array($activeDigiriskElementList)) {
            $activeDigiriskElementList = [];
        }

        // Perimetre et evaluations RPS des unites de travail, tels que les lit la synthese publique :
        // le gabarit remplit ces deux tableaux
        $rpsLines         = [];
        $rpsSubCategories = [];
        require __DIR__ . '/../../../../tpl/riskanalysis/risk/digiriskdolibarr_rps_scope_data.tpl.php';

        $factorColumns = digiriskRiskOverviewFactorColumns($rpsSubCategories, $outputLangs);
        // Sans les risques : le document les a deja charges, les relire doublerait la memoire
        $overviewRows  = digiriskRiskOverviewRows($this->db, $activeDigiriskElementList, $rpsLines, $rpsSubCategories, false);
        if (empty($overviewRows)) {
            $this->emptyNote($pdf, '-', $size);
            return;
        }

        $chip = function (array $cell) use ($outputLangs): array {
            if ($cell['value'] === null) {
                return ['chip' => true, 'text' => $outputLangs->transnoentities('RiskOverviewNotConcerned'), 'chipFill' => $this->rpsEmpty['fill'], 'color' => $this->rpsEmpty['ink']];
            }
            $scale = (int) $cell['scale'];

            return ['chip' => true, 'text' => digiriskRiskOverviewFormatLevel((float) $cell['value'], $outputLangs), 'style' => 'B', 'chipFill' => $this->rpsFill[$scale] ?? $this->rpsFill[1], 'color' => $this->rpsInk[$scale] ?? $this->rpsInk[1]];
        };

        $rows = [];
        foreach ($overviewRows as $overviewRow) {
            $row = [[
                'text'   => $overviewRow['ref'] . ' - ' . $overviewRow['label'],
                'style'  => $overviewRow['type'] == 'groupment' ? 'B' : '',
                'indent' => 3 * (int) $overviewRow['depth']
            ]];
            foreach ($factorColumns as $factorColumn) {
                $row[] = $chip(digiriskRiskOverviewCell($overviewRow, 'rps', $factorColumn['position'], $factorColumns));
            }
            $row[]  = $chip(digiriskRiskOverviewCell($overviewRow, 'rps', -1, $factorColumns));
            $rows[] = $row;
        }

        $factorWidth = min(18, 112 / max(1, count($factorColumns)));
        $columns     = [['width' => $this->contentWidth($pdf) - (count($factorColumns) * $factorWidth) - 18, 'header' => $outputLangs->transnoentities('DigiriskDuClassicUnitOrGroupment')]];
        foreach ($factorColumns as $factorColumn) {
            $columns[] = ['width' => $factorWidth, 'header' => ['text' => $factorColumn['factor'] . "\n" . $factorColumn['name'], 'align' => 'C', 'size' => 5.6, 'upper' => false], 'align' => 'C'];
        }
        $columns[] = ['width' => 18, 'header' => $outputLangs->transnoentities('DigiriskDuClassicAverage'), 'align' => 'C'];

        $this->gridTable($pdf, $columns, $rows, 7);
        $this->text($pdf, $outputLangs->transnoentities('DigiriskDuClassicRpsLegend'), $size - 2.5, 'I', $this->inkMuted);
    }

    /**
     * Evaluation des RPS par UT/GP : le dernier niveau releve sur chaque facteur, avec les outils
     * 37 et 42 de l'INRS.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $data        Donnees du document
     * @return void
     */
    protected function psychosocialAssessmentTable($pdf, Translate $outputLangs, float $size, array $data)
    {
        $risk                    = new Risk($this->db);
        $digiriskElement         = new DigiriskElement($this->db);
        $psychosocialRisksByGPUT = $data['current']['psychosocialRisksByGPUT'] ?? [];
        $subCategories           = $data['dangerSubCategories'][Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? [];

        $rows = [];
        foreach ($psychosocialRisksByGPUT as $elementId => $byFactor) {
            if ($digiriskElement->fetch($elementId) <= 0) {
                continue;
            }

            // Une seule date pour la ligne : celle de l'evaluation la plus recente de l'element
            $lastElementDate = null;
            $values          = [];
            foreach ($subCategories as $subCategory) {
                $assessments = $byFactor[$subCategory['position']] ?? [];
                if (!is_array($assessments) || empty($assessments)) {
                    $values[] = ['text' => '-', 'color' => $this->inkMuted];
                    continue;
                }

                $lastDate     = null;
                $lastCotation = null;
                foreach ($assessments as $date => $cotation) {
                    if (is_null($lastCotation) || $date > $lastDate) {
                        $lastDate     = $date;
                        $lastCotation = $cotation;
                    }
                }
                if ($lastDate > $lastElementDate) {
                    $lastElementDate = $lastDate;
                }

                $values[] = html_entity_decode((string) $risk->getDangerSubCategoryScaleLabel($lastCotation, $outputLangs), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $rows[] = array_merge([
                ['text' => $digiriskElement->ref . ' - ' . $digiriskElement->label, 'style' => 'B'],
                !empty($lastElementDate) ? dol_print_date($lastElementDate, 'day', 'tzuser', $outputLangs) : '-'
            ], $values);
        }

        $this->text($pdf, $outputLangs->transnoentities('DigiriskDuClassicRpsAssessmentIntro', (string) count($data['current']['elements']), (string) count($rows)), $size - 1);

        if (empty($rows) || empty($subCategories)) {
            return;
        }

        $valueWidth = min(17, 122 / count($subCategories));
        $columns    = [
            ['width' => $this->contentWidth($pdf) - 18 - (count($subCategories) * $valueWidth), 'header' => $outputLangs->transnoentities('DigiriskDuUnit')],
            ['width' => 18, 'header' => $outputLangs->transnoentities('Date'), 'align' => 'C']
        ];
        foreach ($subCategories as $subCategory) {
            $columns[] = ['width' => $valueWidth, 'header' => ['text' => $outputLangs->transnoentities($subCategory['label'] ?? $subCategory['name']), 'size' => 5.6, 'align' => 'C', 'upper' => false], 'align' => 'C'];
        }

        $this->gridTable($pdf, $columns, $rows, 7);
    }

    /**
     * Nombre de RPS par niveau : une ligne par facteur, et pour les risques psychosociaux saisis
     * sans facteur, avec la ligne des totaux.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  array     $data        Donnees du document
     * @return void
     */
    protected function psychosocialByLevelTable($pdf, Translate $outputLangs, array $data)
    {
        $riskBySubCategories = $data['current']['riskBySubCategories'] ?? [];
        $subCategories       = $data['dangerSubCategories'][Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? [];

        $picto = '';
        foreach ($data['dangerCategories'] as $dangerCategory) {
            if ($dangerCategory['position'] == Risk::PSYCHOSOCIAL_CATEGORY_POSITION) {
                $picto = $this->pictoPath($dangerCategory['thumbnail_name'] ?? '');
            }
        }

        $lines = [];
        foreach ($subCategories as $subCategory) {
            $lines[] = ['picto' => $picto, 'name' => $outputLangs->transnoentities($subCategory['label'] ?? $subCategory['name']), 'counts' => $riskBySubCategories[$subCategory['position']] ?? []];
        }
        // Un risque psychosocial saisi sans facteur n'est compte nulle part ailleurs
        if (!empty($riskBySubCategories[Risk::SUB_CATEGORY_NOT_SPECIFIED])) {
            $lines[] = ['picto' => $picto, 'name' => $outputLangs->transnoentities('PsychosocialRiskWithoutFactor'), 'counts' => $riskBySubCategories[Risk::SUB_CATEGORY_NOT_SPECIFIED]];
        }

        $this->breakdownTable($pdf, $outputLangs, $lines, (int) ($data['current']['totalRisks'] ?? 0));
    }

    /*
     * Chapitre 3 : risques et registres, en paysage
     */

    /**
     * Hierarchisation des risques et programme annuel de prevention, puis registres SST.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $data        Donnees du document
     * @return void
     * @throws Exception
     */
    protected function chapterRisks($pdf, Translate $outputLangs, float $size, array $data)
    {
        $this->orientation = 'L';
        $this->newPage($pdf);

        $this->heading($pdf, 0, $outputLangs->transnoentities('DigiriskDuClassicChapterRisks'), $outputLangs, $size);

        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuClassicRisksCompany'), $outputLangs, $size, 60);
        $this->risksTable($pdf, $outputLangs, $size, $data, 'current');

        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuClassicRisksShared'), $outputLangs, $size, 60);
        if ($data['showShared'] && !empty($data['shared']['risks'])) {
            $this->risksTable($pdf, $outputLangs, $size, $data, 'shared');
        } else {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoSharedRisk'), $size);
        }

        $this->heading($pdf, 1, $outputLangs->transnoentities('DigiriskDuClassicRegisters'), $outputLangs, $size, 40);
        $this->registersTable($pdf, $outputLangs, $size, $data['tickets']);
    }

    /**
     * Risques par ordre decroissant de cotation, avec les actions du programme annuel de
     * prevention : un tableau par niveau, ouvert par la pastille du niveau et son effectif.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $data        Donnees du document
     * @param  string    $scope       current ou shared
     * @return void
     * @throws Exception
     */
    protected function risksTable($pdf, Translate $outputLangs, float $size, array $data, string $scope)
    {
        $risk        = new Risk($this->db);
        $elements    = $data[$scope]['elements'];
        $riskLevels  = $data[$scope]['riskByRiskAssessmentLevels'] ?? [];
        $showName    = getDolGlobalInt('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME');
        $hideDate    = getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT');
        $showRaDate  = getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE');

        $categoryWidth = $showName ? 26 : 16;
        $textWidth     = $showName ? 48 : 53;
        $columns       = [
            ['width' => 34, 'header' => $outputLangs->transnoentities('DigiriskDuUnit')],
            ['width' => 22, 'header' => $outputLangs->transnoentities('DigiriskDuClassicRiskRef')],
            ['width' => 13, 'header' => $outputLangs->transnoentities('DigiriskDuClassicCotationShort'), 'align' => 'C'],
            ['width' => $categoryWidth, 'header' => $outputLangs->transnoentities('DigiriskDuClassicCategory'), 'align' => 'C'],
            ['width' => $textWidth, 'header' => $outputLangs->transnoentities('DigiriskDuClassicRiskDescription')],
            ['width' => $textWidth, 'header' => $outputLangs->transnoentities('DigiriskDuClassicAssessmentComment')],
            ['width' => 269 - 34 - 22 - 13 - $categoryWidth - (2 * $textWidth), 'header' => $outputLangs->transnoentities('DigiriskDuClassicPreventionActions')]
        ];

        foreach ([4, 3, 2, 1] as $level) {
            $rows = [];
            foreach ($riskLevels[$level] ?? [] as $riskLine) {
                $element = $elements[$riskLine->fk_element] ?? null;
                if (empty($element)) {
                    continue;
                }

                $comment = '';
                if (!$hideDate && !empty($riskLine->riskAssessmentComment)) {
                    $date    = ($showRaDate && !empty($riskLine->riskAssessmentDate)) ? $riskLine->riskAssessmentDate : $riskLine->riskAssessmentDateCreation;
                    $comment = dol_print_date($date, 'dayreduceformat', 'tzuser', $outputLangs) . ' : ';
                }
                $comment .= $this->plainText((string) ($riskLine->riskAssessmentComment ?? ''));

                $category = ['image' => $this->pictoPath((string) $risk->getDangerCategory($riskLine, $riskLine->type ?? 'risk'))];
                if ($showName) {
                    $category['text']  = (string) $risk->getDangerCategoryName($riskLine, $riskLine->type ?? 'risk');
                    $category['size']  = 6;
                    $category['align'] = 'C';
                    $category['color'] = $this->inkMuted;
                }

                $rows[] = [
                    ['text' => $this->unitLabel($element, $scope), 'style' => 'B'],
                    ['blocks' => [['text' => $riskLine->ref, 'style' => 'B'], ['text' => $riskLine->riskAssessmentRef, 'color' => $this->inkMuted]]],
                    $this->cotationChip((int) $riskLine->riskAssessmentCotation, $level),
                    $category,
                    $this->plainText((string) $riskLine->description),
                    dol_strlen(trim($comment)) ? $comment : ['text' => '-', 'color' => $this->inkMuted],
                    ['blocks' => $this->riskTaskBlocks((int) $riskLine->id, $scope, $data, $outputLangs)]
                ];
            }

            $this->levelBand($pdf, $level, count($rows), $outputLangs, $size);
            if (empty($rows)) {
                $this->emptyNote($pdf, $outputLangs->transnoentities('NoRiskAtThisLevel', $outputLangs->transnoentities($this->levelLabelKey($level))), $size);
                continue;
            }

            $this->gridTable($pdf, $columns, $rows, 7.5, ['valign' => 'T', 'imageSize' => 9]);
        }
    }

    /**
     * Ouverture d'un niveau de cotation : sa pastille et son effectif.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $level       Niveau de 1 a 4
     * @param  int       $count       Nombre de risques du niveau
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function levelBand($pdf, int $level, int $count, Translate $outputLangs, float $size)
    {
        $this->ensureSpace($pdf, $count > 0 ? 40 : 18);

        $label = $outputLangs->transnoentities($this->levelLabelKey($level)) . '  -  ' . $count;
        $pdf->SetFont('', 'B', $size - 1);
        $width = max(46, $pdf->GetStringWidth($label) + 12);

        $y = $pdf->GetY() + 2;
        $this->pill($pdf, $this->marge_gauche, $y, $width, 7.5, $label, $this->levelBg[$level], $this->levelText[$level], $size - 1);
        $pdf->SetY($y + 10);
    }

    /**
     * Actions d'un risque, chacune lisible d'un coup d'oeil : une pastille d'etat devant son
     * libelle (en cours avec son avancement, en retard, terminee), un trait de la meme couleur sur
     * toute sa hauteur, puis ses details sur une ligne plus discrete. Les actions en cours passent
     * avant les terminees, les plus en retard en tete.
     *
     * Les reglages sont ceux du gabarit ODT : reference, responsable, dates et budget masquables,
     * actions soldees detaillees ou seulement comptees, avancement calcule sur le temps passe,
     * actions d'un risque partage illisibles sans partage des projets.
     *
     * @param  int       $riskId      Identifiant du risque
     * @param  string    $scope       current ou shared
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @return array                  Blocs de la cellule
     * @throws Exception
     */
    protected function riskTaskBlocks(int $riskId, string $scope, array $data, Translate $outputLangs): array
    {
        global $mc;

        $none = [['text' => $outputLangs->transnoentities('DigiriskDuClassicNoAction'), 'style' => 'I', 'color' => $this->inkMuted]];

        // Les actions d'un risque partage vivent dans les projets d'une autre entite : sans partage
        // des projets, elles ne sont pas lisibles ici
        $projectEntities = $data['shared']['projectEntities'] ?? [];
        if ($scope == 'shared' && !empty($projectEntities)) {
            $sharedProjects = (is_object($mc) && !empty($mc->sharings['project'])) ? $mc->sharings['project'] : [];
            if (empty($sharedProjects) || !empty(array_diff(array_keys($projectEntities), $sharedProjects))) {
                return [['text' => $outputLangs->transnoentities('NoTaskShared'), 'style' => 'I', 'color' => $this->inkMuted]];
            }
        }

        $tasks = $data[$scope]['riskTasks'][$riskId] ?? [];
        if (empty($tasks)) {
            return $none;
        }

        $now   = dol_now();
        $items = [];
        foreach ($tasks as $task) {
            $progress = (float) $task->progress;
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS') && !empty($task->planned_workload)) {
                $timeSpent = $task->getSummaryOfTimeSpent();
                if (($timeSpent['total_duration'] ?? 0) > 0) {
                    $progress = round($timeSpent['total_duration'] / $task->planned_workload * 100, 2);
                }
            }
            $done    = $progress >= 100;
            $items[] = ['task' => $task, 'progress' => $progress, 'done' => $done, 'late' => !$done && !empty($task->datee) && $task->datee < $now];
        }

        usort($items, function ($first, $second) {
            return [$first['done'], $first['task']->datee ?: PHP_INT_MAX] <=> [$second['done'], $second['task']->datee ?: PHP_INT_MAX];
        });

        $showDone   = getDolGlobalInt('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE');
        $hiddenDone = 0;
        $blocks     = [];
        foreach ($items as $item) {
            if ($item['done'] && !$showDone) {
                $hiddenDone++;
                continue;
            }

            $state = $item['done'] ? 'done' : ($item['late'] ? 'late' : 'ongoing');
            $badge = $this->taskBadge($state, $outputLangs, $item['done'] ? '' : round($item['progress']) . ' %');

            if (!empty($blocks)) {
                $blocks[] = ['gap' => 2.2];
            }
            $blocks[] = ['text' => (string) $item['task']->label, 'style' => 'B', 'color' => $this->ink, 'bar' => $badge['ink'], 'badge' => $badge];

            $details = $this->taskDetails($item['task'], $outputLangs);
            if (dol_strlen($details)) {
                $blocks[] = ['text' => $details, 'size' => 6.6, 'color' => $this->inkMuted, 'bar' => $badge['ink']];
            }
            $description = $this->plainText((string) $item['task']->description);
            if (dol_strlen($description)) {
                $blocks[] = ['text' => $description, 'size' => 6.6, 'style' => 'I', 'color' => $this->inkSoft, 'bar' => $badge['ink']];
            }
        }

        // Actions soldees non detaillees par reglage : leur nombre seulement
        if ($hiddenDone > 0) {
            if (!empty($blocks)) {
                $blocks[] = ['gap' => 2.2];
            }
            $badge    = $this->taskBadge('done', $outputLangs);
            $blocks[] = ['text' => $outputLangs->transnoentities('DigiriskDuClassicDoneHidden', (string) $hiddenDone), 'size' => 6.6, 'color' => $this->inkMuted, 'bar' => $badge['ink'], 'badge' => $badge];
        }

        return !empty($blocks) ? $blocks : $none;
    }

    /**
     * Pastille d'etat d'une action : bleue en cours, rouge en retard, verte terminee.
     *
     * @param  string    $state       ongoing, late ou done
     * @param  Translate $outputLangs Lang object
     * @param  string    $suffix      Avancement ajoute au libelle
     * @return array                  ['text', 'fill', 'ink']
     */
    protected function taskBadge(string $state, Translate $outputLangs, string $suffix = ''): array
    {
        $states = [
            'ongoing' => ['key' => 'DigiriskDuClassicTaskOngoing', 'color' => $this->accent],
            'late'    => ['key' => 'DigiriskDuClassicTaskLate', 'color' => $this->accentWarn],
            'done'    => ['key' => 'DigiriskDuClassicTaskDone', 'color' => $this->accentAlt]
        ];
        $state = $states[$state] ?? $states['ongoing'];

        return [
            'text' => dol_strtoupper($outputLangs->transnoentities($state['key'])) . (dol_strlen($suffix) ? ' · ' . $suffix : ''),
            'fill' => $this->tint($state['color'], 0.85),
            'ink'  => $state['color']
        ];
    }

    /**
     * Details d'une action sur une ligne : reference, responsable, dates, budget, contacts.
     *
     * @param  object    $task        Tache du plan d'actions
     * @param  Translate $outputLangs Lang object
     * @return string                 Details separes par des points, vide s'il n'y en a aucun
     * @throws Exception
     */
    protected function taskDetails($task, Translate $outputLangs): string
    {
        global $conf;

        $parts = [];
        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT') && !empty($task->ref)) {
            $parts[] = $task->ref;
        }

        // Le responsable d'une tache est son contact interne TASKEXECUTIVE
        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT')) {
            $responsibles = [];
            $contacts     = $task->liste_contact(-1, 'internal');
            foreach (is_array($contacts) ? $contacts : [] as $contact) {
                if ($contact['code'] == 'TASKEXECUTIVE') {
                    $responsibles[] = trim($contact['firstname'] . ' ' . $contact['lastname']);
                }
            }
            if (!empty($responsibles)) {
                $parts[] = $outputLangs->transnoentities('Responsible') . ' : ' . implode(', ', $responsibles);
            }
        }

        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT')) {
            $start = (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE') && !empty($task->dateo)) ? $task->dateo : $task->datec;
            if (!empty($start)) {
                $parts[] = $outputLangs->transnoentities('DateStart') . ' : ' . dol_print_date($start, 'dayreduceformat', 'tzuser', $outputLangs);
            }
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE') && !empty($task->datee)) {
                $parts[] = $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($task->datee, 'dayreduceformat', 'tzuser', $outputLangs);
            }
        }

        if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT')) {
            $parts[] = $outputLangs->transnoentities('Budget') . ' : ' . price((float) $task->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency);
        }

        // Initiales des contacts de l'action, comme dans le gabarit
        $initials   = [];
        $contactIds = $task->getListContactId();
        if (is_array($contactIds) && !empty($contactIds)) {
            $userTmp = new User($this->db);
            foreach ($contactIds as $contactId) {
                if ($userTmp->fetch($contactId) > 0) {
                    $initials[] = dol_strtoupper(dol_substr((string) $userTmp->firstname, 0, 1) . dol_substr((string) $userTmp->lastname, 0, 1));
                }
            }
        }
        $initials = array_filter($initials);
        if (!empty($initials)) {
            $parts[] = $outputLangs->transnoentities('ContactsAction') . ' : ' . implode(', ', $initials);
        }

        return implode('  ·  ', $parts);
    }

    /**
     * Registres sante securite : les tickets du projet dedie, avec leurs registres.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $tickets     Tickets
     * @return void
     * @throws Exception
     */
    protected function registersTable($pdf, Translate $outputLangs, float $size, array $tickets)
    {
        if (empty($tickets)) {
            $this->emptyNote($pdf, $outputLangs->transnoentities('DigiriskDuClassicNoRegister'), $size);
            return;
        }

        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $category = new Categorie($this->db);

        // Seuls les registres choisis dans la configuration sont imprimes, tous si elle est vide
        $allowedCategoryIds = array_filter(array_map('intval', explode(',', getDolGlobalString('DIGIRISKDOLIBARR_TICKET_DOCUMENT_CATEGORIES'))));

        $rows = [];
        foreach ($tickets as $ticket) {
            $categories = $category->containing($ticket->id, Categorie::TYPE_TICKET);
            if (!is_array($categories)) {
                $categories = [];
            }
            if (!empty($allowedCategoryIds)) {
                $categories = array_filter($categories, function ($ticketCategory) use ($allowedCategoryIds) {
                    return in_array((int) $ticketCategory->id, $allowedCategoryIds, true);
                });
            }

            // Le statut est traduit dans la langue du document : getLibStatut() passe par celle de
            // l'utilisateur
            $status = $ticket->labelStatus[$ticket->status] ?? '';

            $rows[] = [
                ['text' => $ticket->ref, 'style' => 'B'],
                (string) ($ticket->digiriskElementRefLabel ?? ''),
                ['text' => implode(', ', array_map(function ($ticketCategory) {
                    return $ticketCategory->label;
                }, $categories)), 'color' => $this->inkMuted],
                ['text' => dol_print_date($ticket->datec, 'dayhour', 'tzuser', $outputLangs), 'align' => 'C'],
                ['text' => (string) $ticket->subject, 'style' => 'B'],
                dol_trunc($this->plainText((string) $ticket->message), 1500),
                ['chip' => true, 'text' => ((int) $ticket->progress) . ' %', 'chipFill' => $this->tint($this->accent, 0.86), 'color' => $this->accent, 'style' => 'B'],
                dol_strlen($status) ? $outputLangs->transnoentitiesnoconv($status) : ''
            ];
        }

        $this->gridTable($pdf, [
            ['width' => 24, 'header' => $outputLangs->transnoentities('DigiriskDuClassicRegisterRef')],
            ['width' => 34, 'header' => $outputLangs->transnoentities('DigiriskDuClassicLocation')],
            ['width' => 30, 'header' => $outputLangs->transnoentities('DigiriskDuClassicTags')],
            ['width' => 22, 'header' => $outputLangs->transnoentities('DigiriskDuClassicCreationDate'), 'align' => 'C'],
            ['width' => 40, 'header' => $outputLangs->transnoentities('Subject')],
            ['width' => 79, 'header' => $outputLangs->transnoentities('DigiriskDuClassicMessage')],
            ['width' => 16, 'header' => $outputLangs->transnoentities('DigiriskProgress'), 'align' => 'C'],
            ['width' => 24, 'header' => $outputLangs->transnoentities('Status')]
        ], $rows, 7.5, ['valign' => 'T']);
    }

    /*
     * Titres et textes
     */

    /**
     * Titre numerote : chapitre (0), section (1), sous-section (2), puis intertitre (3).
     *
     * Un chapitre ouvre sa page sur son grand numero d'accent ; une section a le trait d'accent et
     * le filet des autres documents du DU. Les trois premiers niveaux entrent au sommaire et dans
     * les signets du PDF. Un titre ne termine jamais une page : il passe a la suivante avec le
     * debut de son contenu.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $level       Niveau, de 0 a 3
     * @param  string    $title       Titre
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  float     $minAfter    Place a garder sous le titre pour le debut de son contenu
     * @return void
     */
    protected function heading($pdf, int $level, string $title, Translate $outputLangs, float $size, float $minAfter = 30)
    {
        $this->headingCounters[$level]++;
        for ($index = $level + 1; $index < count($this->headingCounters); $index++) {
            $this->headingCounters[$index] = 0;
        }
        $number = implode('.', array_slice($this->headingCounters, 0, $level + 1));

        $heights = [0 => 26, 1 => 15, 2 => 9, 3 => 7];
        if ($pdf->GetY() > $this->marge_haute + 1) {
            $this->ensureSpace($pdf, $heights[$level] + $minAfter);
        }

        $width = $this->contentWidth($pdf);
        $x     = $this->marge_gauche;
        $page  = $pdf->getPage();

        if ($level == 0) {
            $y = $pdf->GetY();

            $pdf->SetFont('', 'B', $size + 28);
            $pdf->SetTextColor(...$this->tint($this->accent, 0.8));
            $pdf->SetXY($x - 1, $y - 2);
            $pdf->Cell(32, 17, sprintf('%02d', (int) $number), 0, 0, 'L');

            $this->kicker($pdf, $x + 32, $y + 1, $width - 32, $outputLangs->transnoentities('DigiriskDuClassicChapterKicker', $number), $size + 1, $this->accent);

            $pdf->SetFont('', 'B', $size + 9);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x + 32, $y + 6);
            $pdf->Cell($width - 32, 9, $this->fitText($pdf, $title, $width - 32), 0, 1, 'L');

            $this->hairline($pdf, $y + 19, 0.4, $this->accent);
            $pdf->SetXY($x, $y + 24);
        } elseif ($level == 1) {
            if ($pdf->GetY() > $this->marge_haute + 1) {
                $pdf->SetY($pdf->GetY() + 4);
            }
            $y = $pdf->GetY();

            $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->RoundedRect($x, $y, 11, 1.3, 0.65, '1111', 'F');

            $pdf->SetFont('', 'B', $size + 3);
            $numberWidth = $pdf->GetStringWidth($number) + 3;
            $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->SetXY($x, $y + 3.4);
            $pdf->Cell($numberWidth, 7, $number, 0, 0, 'L');
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->Cell($width - $numberWidth, 7, $this->fitText($pdf, $title, $width - $numberWidth), 0, 1, 'L');

            $this->hairline($pdf, $pdf->GetY() + 0.5);
            $pdf->SetXY($x, $pdf->GetY() + 3.5);
        } elseif ($level == 2) {
            if ($pdf->GetY() > $this->marge_haute + 1) {
                $pdf->SetY($pdf->GetY() + 2.5);
            }
            $y = $pdf->GetY();

            $pdf->SetFont('', 'B', $size + 0.5);
            $numberWidth = $pdf->GetStringWidth($number) + 2.5;
            $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->SetXY($x, $y);
            $pdf->Cell($numberWidth, 6, $number, 0, 0, 'L');
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->MultiCell($width - $numberWidth, 6, $title, 0, 'L', false, 1, $x + $numberWidth, $y);
            $pdf->SetY($pdf->GetY() + 1.5);
        } else {
            $y = $pdf->GetY() + 1;
            $this->kicker($pdf, $x, $y, $width, $title, $size, $this->accentAlt);
            $pdf->SetXY($x, $y + 6.5);
        }

        $pdf->SetTextColor(0, 0, 0);

        if ($level <= 2) {
            $link = $pdf->AddLink();
            $pdf->SetLink($link, $y, $page);
            $pdf->Bookmark($number . ' ' . $title, $level, $y, $page);

            $this->summary[] = ['level' => $level, 'number' => $number, 'title' => $title, 'page' => $page, 'link' => $link];
        }
    }

    /**
     * Paragraphe de texte courant, coupe aux sauts de page par TCPDF.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $text  Texte
     * @param  float  $size  Taille de police
     * @param  string $style Style de police
     * @param  array  $color Couleur du texte, l'encre courante par defaut
     * @return void
     */
    protected function text($pdf, string $text, float $size, string $style = '', array $color = [])
    {
        if (!dol_strlen($text)) {
            return;
        }

        $color = $color ?: $this->inkSoft;

        $pdf->SetFont('', $style, $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($this->contentWidth($pdf), $this->lineHeight($size), $text, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Texte saisi a l'editeur sur la fiche du DU, mis en forme d'apres sa saisie : une ligne
     * "* Etape 1 : titre" devient une etape numerotee, ses lignes "- " des puces reliees a son
     * numero par un filet, une ligne en gras un intertitre, une date en debut de ligne une
     * pastille, une adresse web un lien. Le reste est du texte courant.
     *
     * @param  TCPDF  $pdf  PDF handler
     * @param  string $html Contenu saisi
     * @param  float  $size Taille de police
     * @return void
     */
    protected function richText($pdf, string $html, float $size)
    {
        $lines = $this->richTextLines($html);
        if (empty($lines)) {
            $this->text($pdf, '-', $size);
            return;
        }

        $left    = $this->marge_gauche;
        $width   = $this->contentWidth($pdf);
        $lineH   = $this->lineHeight($size);
        $padding = $pdf->getCellPaddings()['L'] ?? 0;
        $step    = [];
        $gap     = false;
        $first   = true;

        foreach ($lines as $line) {
            if ($line['type'] == 'gap') {
                $this->closeStep($pdf, $step);
                $step = [];
                $gap  = !$first;
                continue;
            }

            $space = $gap ? 2.5 : 0;
            if ($line['type'] == 'step') {
                $this->closeStep($pdf, $step);
                $step  = [];
                $space = $first ? 0 : max($space, 1.5);
            }
            if ($space > 0) {
                $pdf->SetY($pdf->GetY() + $space);
            }
            $gap   = false;
            $first = false;

            $indent = !empty($step) ? 9 : 0;
            $x      = $left + $indent;
            $w      = $width - $indent;

            switch ($line['type']) {
                case 'step':
                    $title  = $line['text'];
                    $wrap   = $this->wrapText($pdf, $title, $w - 9, 'B', $size + 0.5);
                    $height = max(6.4, count($wrap) * $this->lineHeight($size + 0.5));
                    $this->ensureSpace($pdf, $height + $lineH);

                    $y = $pdf->GetY();
                    // Une etape sans numero garde un point dans l'axe des numeros, comme sur la fiche
                    $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
                    $pdf->Circle($x + 3.1, $y + 3.1, $line['number'] !== '' ? 3.1 : 1.3, 0, 360, 'F');
                    if ($line['number'] !== '') {
                        $pdf->SetFont('', 'B', $size - 1);
                        $pdf->SetTextColor(255, 255, 255);
                        $pdf->SetXY($x, $y);
                        $pdf->Cell(6.2, 6.2, $line['number'], 0, 0, 'C');
                    }

                    $pdf->SetFont('', 'B', $size + 0.5);
                    $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
                    $top = $y + max(0, (6.2 - (count($wrap) * $this->lineHeight($size + 0.5))) / 2);
                    foreach ($wrap as $index => $text) {
                        $pdf->SetXY($x + 9 - $padding, $top + ($index * $this->lineHeight($size + 0.5)));
                        $pdf->Cell($w - 9 + (2 * $padding), $this->lineHeight($size + 0.5), $text, 0, 0, 'L');
                    }

                    $pdf->SetY($y + $height + 0.8);
                    $step = ['page' => $pdf->getPage(), 'x' => $x + 3.1, 'top' => $y + 6.2, 'children' => 0];
                    break;

                case 'bullet':
                    $wrap = $this->wrapText($pdf, $line['text'], $w - 4.5, '', $size);
                    $this->ensureSpace($pdf, count($wrap) * $lineH);
                    $y = $pdf->GetY();

                    $pdf->SetFillColor(...(!empty($step) ? $this->tint($this->accent, 0.45) : $this->accent));
                    $pdf->Circle($x + 1.2, $y + ($lineH / 2), 0.75, 0, 360, 'F');
                    $this->richTextLinesOut($pdf, $wrap, $x + 4.5, $y, $w - 4.5, $lineH, '', $size, $this->inkSoft, $padding);
                    if (!empty($step)) {
                        $step['children']++;
                    }
                    break;

                case 'heading':
                    $wrap = $this->wrapText($pdf, $line['text'], $w, 'B', $size + 0.5);
                    $this->ensureSpace($pdf, (count($wrap) + 1) * $lineH);
                    $this->richTextLinesOut($pdf, $wrap, $x, $pdf->GetY(), $w, $this->lineHeight($size + 0.5), 'B', $size + 0.5, $this->ink, $padding);
                    $pdf->SetY($pdf->GetY() + 0.6);
                    break;

                case 'lead':
                    $wrap = $this->wrapText($pdf, $line['text'], $w, 'B', $size);
                    $this->ensureSpace($pdf, (count($wrap) + 1) * $lineH);
                    $this->richTextLinesOut($pdf, $wrap, $x, $pdf->GetY(), $w, $lineH, 'B', $size, $this->ink, $padding);
                    $pdf->SetY($pdf->GetY() + 0.4);
                    break;

                case 'dated':
                    $pdf->SetFont('', 'B', $size - 1.5);
                    $chipW = $pdf->GetStringWidth($line['date']) + 4;
                    $wrap  = $this->wrapText($pdf, $line['text'], $w - $chipW - 2.5, '', $size);
                    $this->ensureSpace($pdf, count($wrap) * $lineH + 1);
                    $y = $pdf->GetY();

                    $this->pill($pdf, $x, $y + 0.15, $chipW, $lineH - 0.3, $line['date'], $this->tint($this->accent, 0.85), $this->accent, $size - 1.5);
                    $this->richTextLinesOut($pdf, $wrap, $x + $chipW + 2.5, $y, $w - $chipW - 2.5, $lineH, '', $size, $this->inkSoft, $padding);
                    $pdf->SetY($pdf->GetY() + 1);
                    break;

                case 'link':
                    $this->linkChip($pdf, $line['url'], $x, $w, $size, $padding);
                    break;

                default:
                    $wrap = $this->wrapText($pdf, $line['text'], $w, '', $size);
                    $this->ensureSpace($pdf, min(3, count($wrap)) * $lineH);
                    $this->richTextLinesOut($pdf, $wrap, $x, $pdf->GetY(), $w, $lineH, '', $size, $this->inkSoft, $padding);
                    break;
            }
        }

        $this->closeStep($pdf, $step);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($pdf->GetY() + 2);
    }

    /**
     * Adresse web en pastille cliquable, comme sur la fiche du DU : icone de lien externe, le site
     * en gras, puis le reste de l'adresse en gris, raccourci s'il ne tient pas.
     *
     * @param  TCPDF  $pdf     PDF handler
     * @param  string $url     Adresse
     * @param  float  $x       Abscisse de la pastille
     * @param  float  $width   Largeur disponible
     * @param  float  $size    Taille de police du texte courant
     * @param  float  $padding Marge interne gauche des cellules TCPDF
     * @return void
     */
    protected function linkChip($pdf, string $url, float $x, float $width, float $size, float $padding)
    {
        $parts    = digiriskstandard_link_parts($url);
        $height   = $this->lineHeight($size) + 1.6;
        $padX     = 2.6;
        $iconSize = 2.6;
        $gap      = 1.6;

        $pdf->SetFont('', 'B', $size - 0.5);
        $hostWidth = $pdf->GetStringWidth($parts['host']);
        $pdf->SetFont('', '', $size - 1);
        $path      = $parts['path'] !== '' ? $this->fitText($pdf, $parts['path'], max(0, $width - (2 * $padX) - $iconSize - (2 * $gap) - $hostWidth)) : '';
        $pathWidth = $path !== '' ? $pdf->GetStringWidth($path) : 0;
        $chipWidth = (2 * $padX) + $iconSize + $gap + $hostWidth + ($path !== '' ? ($gap / 2) + $pathWidth : 0);

        $this->ensureSpace($pdf, $height + 1.2);
        $y = $pdf->GetY();

        $pdf->SetFillColor(...$this->tint($this->accent, 0.9));
        $pdf->RoundedRect($x, $y, $chipWidth, $height, $height / 2, '1111', 'F');
        $this->linkIcon($pdf, $x + $padX, $y + (($height - $iconSize) / 2), $iconSize, $this->accent);

        $textX = $x + $padX + $iconSize + $gap;
        $pdf->SetFont('', 'B', $size - 0.5);
        $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetXY($textX - $padding, $y);
        $pdf->Cell($hostWidth + (2 * $padding), $height, $parts['host'], 0, 0, 'L');

        if ($path !== '') {
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($textX + $hostWidth + ($gap / 2) - $padding, $y);
            $pdf->Cell($pathWidth + (2 * $padding), $height, $path, 0, 0, 'L');
        }

        $pdf->Link($x, $y, $chipWidth, $height, $url);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($y + $height + 1.2);
    }

    /**
     * Icone de lien externe : un cadre ouvert en haut a droite, d'ou sort une fleche.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  float $x     Abscisse du coin haut gauche
     * @param  float $y     Ordonnee du coin haut gauche
     * @param  float $side  Cote de l'icone
     * @param  array $color Couleur du trait
     * @return void
     */
    protected function linkIcon($pdf, float $x, float $y, float $side, array $color)
    {
        $style = ['width' => 0.32, 'cap' => 'round', 'join' => 'round', 'color' => $color];

        $pdf->Line($x + ($side * 0.42), $y + ($side * 0.22), $x, $y + ($side * 0.22), $style);
        $pdf->Line($x, $y + ($side * 0.22), $x, $y + $side, $style);
        $pdf->Line($x, $y + $side, $x + ($side * 0.78), $y + $side, $style);
        $pdf->Line($x + ($side * 0.78), $y + $side, $x + ($side * 0.78), $y + ($side * 0.58), $style);
        $pdf->Line($x + ($side * 0.38), $y + ($side * 0.62), $x + $side, $y, $style);
        $pdf->Line($x + ($side * 0.55), $y, $x + $side, $y, $style);
        $pdf->Line($x + $side, $y, $x + $side, $y + ($side * 0.45), $style);

        $pdf->SetLineStyle(['width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'color' => [0, 0, 0]]);
    }

    /**
     * Lignes deja coupees, ecrites l'une sous l'autre a partir de l'ordonnee donnee, avec un saut
     * de page entre deux lignes si besoin.
     *
     * @param  TCPDF  $pdf     PDF handler
     * @param  array  $lines   Lignes
     * @param  float  $x       Abscisse du texte
     * @param  float  $y       Ordonnee de la premiere ligne
     * @param  float  $width   Largeur du texte
     * @param  float  $lineH   Hauteur d'une ligne
     * @param  string $style   Style de police
     * @param  float  $size    Taille de police
     * @param  array  $color   Couleur du texte
     * @param  float  $padding Marge interne gauche des cellules TCPDF, retiree pour aligner le texte sur $x
     * @return void
     */
    protected function richTextLinesOut($pdf, array $lines, float $x, float $y, float $width, float $lineH, string $style, float $size, array $color, float $padding)
    {
        $pdf->SetY($y);
        foreach ($lines as $text) {
            $this->ensureSpace($pdf, $lineH);
            $pdf->SetFont('', $style, $size);
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetXY($x - $padding, $pdf->GetY());
            $pdf->Cell($width + (2 * $padding), $lineH, $text, 0, 0, 'L');
            $pdf->SetY($pdf->GetY() + $lineH);
        }
    }

    /**
     * Filet qui relie le numero d'une etape a ses puces, quand elles sont sur la meme page.
     *
     * @param  TCPDF $pdf  PDF handler
     * @param  array $step Etape ouverte : page, x, top (bas de la pastille), children
     * @return void
     */
    protected function closeStep($pdf, array $step)
    {
        if (empty($step['children']) || $step['page'] != $pdf->getPage()) {
            return;
        }

        $bottom = $pdf->GetY() - 1.2;
        if ($bottom > $step['top'] + 1) {
            $pdf->SetDrawColor(...$this->tint($this->accent, 0.7));
            $pdf->SetLineWidth(0.5);
            $pdf->Line($step['x'], $step['top'] + 0.8, $step['x'], $bottom);
            $pdf->SetLineWidth(0.2);
        }
    }

    /**
     * Lignes typees d'un texte saisi, lues comme sur la fiche du DU : voir
     * digiriskstandard_rich_text_lines().
     *
     * @param  string $html Contenu saisi
     * @return array        Lignes
     */
    protected function richTextLines(string $html): array
    {
        return digiriskstandard_rich_text_lines($html);
    }

    /**
     * Mention d'une section vide, dans un encart clair.
     *
     * @param  TCPDF  $pdf  PDF handler
     * @param  string $text Texte
     * @param  float  $size Taille de police
     * @return void
     */
    protected function emptyNote($pdf, string $text, float $size)
    {
        $this->ensureSpace($pdf, 12);

        $y = $pdf->GetY();
        $this->card($pdf, $this->marge_gauche, $y, $this->contentWidth($pdf), 9, $this->surface, 2);
        $pdf->SetFont('', 'I', $size - 1);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->SetXY($this->marge_gauche + 4, $y);
        $pdf->Cell($this->contentWidth($pdf) - 8, 9, $text, 0, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($y + 12);
    }

    /**
     * Texte raccourci, points de suspension compris, pour tenir dans la largeur donnee.
     *
     * @param  TCPDF  $pdf   PDF handler, police deja posee
     * @param  string $text  Texte
     * @param  float  $width Largeur disponible
     * @return string        Texte qui tient
     */
    protected function fitText($pdf, string $text, float $width): string
    {
        if ($pdf->GetStringWidth($text) <= $width) {
            return $text;
        }

        $length = dol_strlen($text);
        $short  = $text;
        while ($length > 4 && $pdf->GetStringWidth($short) > $width) {
            $length--;
            $short = rtrim(dol_substr($text, 0, $length)) . '...';
        }

        return $short;
    }

    /**
     * Texte brut d'un contenu saisi a l'editeur : les retours a la ligne et les puces sont
     * gardes, les balises et les entites retirees.
     *
     * @param  string $html Contenu
     * @return string       Texte
     */
    protected function plainText(string $html): string
    {
        return digiriskstandard_html_to_text($html);
    }

    /**
     * Pourcentage avec le separateur decimal de la langue du document.
     *
     * @param  float     $value       Valeur
     * @param  Translate $outputLangs Lang object
     * @return string                 "8,9 %"
     */
    protected function percent(float $value, Translate $outputLangs): string
    {
        return str_replace('.', $outputLangs->transnoentitiesnoconv('SeparatorDecimal'), (string) $value) . ' %';
    }

    /**
     * Chemin d'un picto de famille de danger, livre avec le module.
     *
     * @param  string $thumbnailName Nom du picto, sans extension
     * @return string                Chemin lisible, vide s'il est introuvable
     */
    protected function pictoPath(string $thumbnailName): string
    {
        if (!dol_strlen($thumbnailName) || $thumbnailName === '-1') {
            return '';
        }

        $path = __DIR__ . '/../../../../../img/categorieDangers/' . $thumbnailName . '.png';

        return is_readable($path) ? $path : '';
    }

    /**
     * Libelle d'une unite : "UT2 - Atelier". Une unite partagee vient d'une autre entite, son
     * libelle le dit : "S2 - UT2 - Atelier", comme dans le gabarit.
     *
     * @param  array  $element Element ['object' => DigiriskElement, 'depth' => int]
     * @param  string $scope   current ou shared
     * @return string          Libelle
     */
    protected function unitLabel(array $element, string $scope): string
    {
        return ($scope == 'shared' ? 'S' . $element['object']->entity . ' - ' : '') . $element['object']->ref . ' - ' . $element['object']->label;
    }

    /**
     * Colonnes des quatre niveaux de cotation : le libelle du niveau, souligne de sa couleur.
     *
     * @param  Translate $outputLangs Lang object
     * @param  float     $width       Largeur de chaque colonne
     * @return array                  Colonnes
     */
    protected function levelColumns(Translate $outputLangs, float $width): array
    {
        $columns = [];
        foreach ([1, 2, 3, 4] as $level) {
            $columns[] = [
                'width'  => $width,
                'align'  => 'C',
                'header' => ['text' => $outputLangs->transnoentities('DigiriskDuLevelShort' . $level), 'align' => 'C', 'bar' => $this->levelBg[$level]]
            ];
        }

        return $columns;
    }

    /**
     * Cellules des quatre niveaux de cotation : une pastille de la couleur du niveau, un tiret a
     * zero.
     *
     * @param  array $counts Nombre par niveau, indexe de 1 a 4
     * @return array         Cellules
     */
    protected function levelCountCells(array $counts): array
    {
        $cells = [];
        foreach ([1, 2, 3, 4] as $level) {
            $count   = (int) ($counts[$level] ?? 0);
            $cells[] = $count > 0
                ? ['chip' => true, 'text' => (string) $count, 'style' => 'B', 'chipFill' => $this->levelBg[$level], 'color' => $this->levelText[$level]]
                : ['text' => '-', 'align' => 'C', 'color' => $this->inkMuted];
        }

        return $cells;
    }

    /**
     * Cellule de compteur : le nombre en gras, un tiret a zero.
     *
     * @param  int   $count Nombre
     * @return array        Cellule
     */
    protected function countCell(int $count): array
    {
        return $count > 0
            ? ['text' => (string) $count, 'align' => 'C', 'style' => 'B']
            : ['text' => '-', 'align' => 'C', 'color' => $this->inkMuted];
    }

    /**
     * Cotation d'un risque, en pastille de la couleur de son niveau.
     *
     * @param  int   $cotation Cotation
     * @param  int   $level    Niveau de 1 a 4
     * @return array           Cellule
     */
    protected function cotationChip(int $cotation, int $level): array
    {
        return ['chip' => true, 'text' => (string) $cotation, 'style' => 'B', 'chipFill' => $this->levelBg[$level] ?? $this->levelBg[1], 'color' => $this->levelText[$level] ?? $this->levelText[1]];
    }

    /*
     * Tableau
     */

    /**
     * Bas de la zone de contenu de la page courante.
     *
     * @param  TCPDF $pdf PDF handler
     * @return float      Ordonnee
     */
    protected function pageBottom($pdf): float
    {
        return $pdf->getPageHeight() - self::FOOTER_BAND;
    }

    /**
     * Ouvre une nouvelle page si la hauteur demandee ne tient pas sous la position courante.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $height Hauteur necessaire
     * @return void
     */
    protected function ensureSpace($pdf, float $height)
    {
        if ($pdf->GetY() + $height > $this->pageBottom($pdf)) {
            $this->newPage($pdf);
        }
    }

    /**
     * Hauteur d'une ligne de texte pour un corps donne.
     *
     * @param  float $size Taille de police
     * @return float       Hauteur en mm
     */
    protected function lineHeight(float $size): float
    {
        return $size * 0.3528 * self::LINE_SPACING;
    }

    /**
     * Tableau a la charte des documents du DU : entete en capitales sur un filet d'encre, lignes
     * separees par un filet fin, une sur deux sur un fond tres clair, pas de bordure verticale.
     *
     * Les cellules sont mises en lignes ici plutot que par MultiCell : une ligne de tableau plus
     * haute que la place restante est alors coupee entre deux lignes de texte et continue sur la
     * page suivante, sous l'entete repete. Une ligne qui tient sur une page n'est jamais coupee.
     *
     * Une colonne : ['width' => mm, 'header' => texte ou cellule (+ 'upper', 'bar' => couleur),
     * 'align' => L|C|R]. Une cellule : un texte, ou ['text' | 'blocks' => [['text', 'style',
     * 'color']], 'style', 'align', 'color', 'size', 'indent', 'image' => chemin, 'chip' => true,
     * 'chipFill']. Une ligne marquee '_total' est une ligne de totaux.
     *
     * @param  TCPDF $pdf     PDF handler
     * @param  array $columns Colonnes
     * @param  array $rows    Lignes, chacune une liste de cellules
     * @param  float $size    Taille de police
     * @param  array $options header (bool, vrai par defaut), valign (T|M), imageSize (mm), zebra (bool)
     * @return void
     */
    protected function gridTable($pdf, array $columns, array $rows, float $size, array $options = [])
    {
        $table = [
            'header'    => $options['header'] ?? true,
            'valign'    => $options['valign'] ?? 'M',
            'imageSize' => $options['imageSize'] ?? 8,
            'zebra'     => $options['zebra'] ?? true,
            'size'      => $size,
            'lineH'     => $this->lineHeight($size),
            'width'     => array_sum(array_column($columns, 'width'))
        ];

        $pdf->SetAutoPageBreak(false);

        if ($table['header']) {
            $headerCells = [];
            foreach ($columns as $index => $column) {
                $headerCell = is_array($column['header'] ?? '') ? $column['header'] : ['text' => (string) ($column['header'] ?? '')];
                if ($headerCell['upper'] ?? true) {
                    $headerCell['text'] = dol_strtoupper((string) ($headerCell['text'] ?? ''));
                }
                $headerCell = array_merge(['style' => 'B', 'color' => $this->inkMuted, 'size' => max(5, $size - 1.6), 'align' => $column['align'] ?? 'L', 'valign' => 'M', 'fitWords' => true], $headerCell);

                $headerCells[$index]        = $this->layoutCell($pdf, $headerCell, $column, $table);
                $headerCells[$index]['headerBar'] = $headerCell['bar'] ?? [];
            }
            $table['headerCells']  = $headerCells;
            $table['headerHeight'] = $this->rowHeight($headerCells, $table) + 1.5;
        }

        $layouts = [];
        foreach ($rows as $row) {
            $layout = [];
            foreach ($columns as $index => $column) {
                $layout[$index] = $this->layoutCell($pdf, $row[$index] ?? '', $column, $table);
            }
            $layouts[] = ['cells' => $layout, 'total' => !empty($row['_total'])];
        }

        // L'entete ne termine jamais une page : il garde au moins le debut de la premiere ligne
        $firstRow = !empty($layouts) ? min($this->rowHeight($layouts[0]['cells'], $table), 30) : 0;
        if ($pdf->GetY() + ($table['headerHeight'] ?? 0) + $firstRow > $this->pageBottom($pdf)) {
            $this->newPage($pdf);
        }
        if ($table['header']) {
            $this->drawGridHeader($pdf, $columns, $table);
        }

        foreach ($layouts as $index => $layout) {
            $rowStyle = ['zebra' => $table['zebra'] && ($index % 2) === 1 && !$layout['total'], 'total' => $layout['total']];
            $this->drawGridRow($pdf, $columns, $layout['cells'], $table, $rowStyle);
        }

        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
        $pdf->SetY($pdf->GetY() + 4);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Met une cellule en lignes a la largeur de sa colonne.
     *
     * Chaque ligne porte sa hauteur, sa taille de police et ses ornements : un bloc peut avoir
     * sa propre taille ('size'), un trait de couleur sur toute sa hauteur ('bar'), une pastille
     * devant sa premiere ligne ('badge' => ['text', 'fill', 'ink']), ou n'etre qu'un espace
     * ('gap' => mm).
     *
     * @param  TCPDF        $pdf    PDF handler
     * @param  string|array $cell   Cellule
     * @param  array        $column Colonne
     * @param  array        $table  Reglages du tableau
     * @return array                Cellule mise en lignes
     */
    protected function layoutCell($pdf, $cell, array $column, array $table): array
    {
        if (!is_array($cell)) {
            $cell = ['text' => (string) $cell];
        }

        $size   = (float) ($cell['size'] ?? $table['size']);
        $indent = (float) ($cell['indent'] ?? 0);
        $inner  = max(2, $column['width'] - (2 * self::GRID_PAD_X) - $indent);
        $isChip = !empty($cell['chip']);
        $blocks = $cell['blocks'] ?? [['text' => (string) ($cell['text'] ?? ''), 'style' => $cell['style'] ?? '', 'color' => $cell['color'] ?? null]];

        // Un entete se replie entre ses mots, jamais au milieu d'un mot : sa police retrecit
        // jusqu'a ce que son mot le plus long tienne dans la colonne
        if (!empty($cell['fitWords'])) {
            foreach ($blocks as $block) {
                $pdf->SetFont('', (string) ($block['style'] ?? ''), $size);
                foreach (preg_split('/\s+/', (string) ($block['text'] ?? '')) as $word) {
                    while ($size > 4.5 && $pdf->GetStringWidth($word) > $inner) {
                        $size -= 0.25;
                        $pdf->SetFont('', (string) ($block['style'] ?? ''), $size);
                    }
                }
            }
        }

        // Une cellule sans couleur imposee ecrit en encre de texte courant, en encre de titre
        // quand elle est en gras
        $cellColor = $cell['color'] ?? (strpos((string) ($cell['style'] ?? ''), 'B') !== false ? $this->ink : $this->inkSoft);

        $lines = [];
        foreach ($blocks as $block) {
            if (isset($block['gap'])) {
                $lines[] = ['text' => '', 'gap' => true, 'h' => (float) $block['gap']];
                continue;
            }

            $style     = (string) ($block['style'] ?? '');
            $blockSize = (float) ($block['size'] ?? $size);
            $color     = $block['color'] ?? (isset($cell['blocks']) ? (strpos($style, 'B') !== false ? $this->ink : $this->inkSoft) : $cellColor);
            $bar       = $block['bar'] ?? [];
            $badge     = $block['badge'] ?? [];
            $height    = $this->lineHeight($blockSize) + ($isChip ? 1.8 : 0);

            // Le trait decale le texte ; la pastille prend la place du debut de la premiere ligne
            $barOffset   = !empty($bar) ? 2.6 : 0;
            $badgeWidth  = 0;
            if (!empty($badge)) {
                $pdf->SetFont('', 'B', 5.6);
                $badgeWidth = $pdf->GetStringWidth($badge['text']) + 3.2;
            }
            $width      = $inner - $barOffset;
            $firstWidth = $width - ($badgeWidth > 0 ? $badgeWidth + 1.6 : 0);

            foreach ($this->wrapText($pdf, (string) ($block['text'] ?? ''), $width, $style, $blockSize, $firstWidth) as $index => $line) {
                $first   = $index === 0;
                $lines[] = [
                    'text'   => $line,
                    'style'  => $style,
                    'color'  => $color,
                    'size'   => $blockSize,
                    'h'      => $height + ($first && $badgeWidth > 0 ? 0.8 : 0),
                    'bar'    => $bar,
                    'offset' => $barOffset + ($first && $badgeWidth > 0 ? $badgeWidth + 1.6 : 0),
                    'badge'  => $first ? $badge : [],
                    'badgeW' => $badgeWidth
                ];
            }
        }

        // Les lignes vides de debut et de fin ne font qu'allonger la cellule
        while (!empty($lines) && $lines[0]['text'] === '' && empty($lines[0]['badge'])) {
            array_shift($lines);
        }
        while (!empty($lines) && end($lines)['text'] === '' && empty(end($lines)['badge'])) {
            array_pop($lines);
        }

        return [
            'lines'    => $lines,
            'align'    => $cell['align'] ?? ($column['align'] ?? 'L'),
            'valign'   => $cell['valign'] ?? $table['valign'],
            'indent'   => $indent,
            'image'    => (!empty($cell['image']) && is_readable($cell['image'])) ? $cell['image'] : '',
            'chip'     => $isChip,
            'chipFill' => $cell['chipFill'] ?? $this->surface
        ];
    }

    /**
     * Coupe un texte en lignes qui tiennent dans la largeur donnee, en respectant ses retours a
     * la ligne. Un mot plus long que la largeur est coupe.
     *
     * @param  TCPDF      $pdf        PDF handler
     * @param  string     $text       Texte
     * @param  float      $width      Largeur disponible
     * @param  string     $style      Style de police
     * @param  float      $size       Taille de police
     * @param  float|null $firstWidth Largeur de la premiere ligne, plus courte quand une pastille la precede
     * @return array                  Lignes
     */
    protected function wrapText($pdf, string $text, float $width, string $style, float $size, ?float $firstWidth = null): array
    {
        $pdf->SetFont('', $style, $size);

        $space = $pdf->GetStringWidth(' ');
        $lines = [];
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $lines[] = '';
                continue;
            }

            $line      = '';
            $lineWidth = 0;
            foreach (preg_split('/ +/', $paragraph) as $word) {
                $limit     = (empty($lines) && $firstWidth !== null) ? $firstWidth : $width;
                $wordWidth = $pdf->GetStringWidth($word);
                if ($line !== '' && $lineWidth + $space + $wordWidth <= $limit) {
                    $line      .= ' ' . $word;
                    $lineWidth += $space + $wordWidth;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $limit   = $width;
                }

                // Mot plus long que la ligne : coupe au caractere
                while ($wordWidth > $limit && dol_strlen($word) > 1) {
                    $cut = dol_strlen($word) - 1;
                    while ($cut > 1 && $pdf->GetStringWidth(dol_substr($word, 0, $cut)) > $limit) {
                        $cut--;
                    }
                    $lines[]   = dol_substr($word, 0, $cut);
                    $word      = dol_substr($word, $cut);
                    $wordWidth = $pdf->GetStringWidth($word);
                    $limit     = $width;
                }

                $line      = $word;
                $lineWidth = $wordWidth;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Hauteur des lignes d'une cellule, de la ligne donnee jusqu'a la fin.
     *
     * @param  array $layout   Cellule mise en lignes
     * @param  int   $nextLine Premiere ligne comptee
     * @return float           Hauteur en mm
     */
    protected function linesHeight(array $layout, int $nextLine): float
    {
        $height = 0;
        foreach (array_slice($layout['lines'], $nextLine) as $line) {
            $height += $line['h'];
        }

        return $height;
    }

    /**
     * Hauteur du contenu restant d'une cellule.
     *
     * @param  array $layout       Cellule mise en lignes
     * @param  int   $nextLine     Premiere ligne restant a ecrire
     * @param  bool  $imagePending Image restant a poser
     * @param  array $table        Reglages du tableau
     * @return float               Hauteur en mm, marges comprises
     */
    protected function cellHeight(array $layout, int $nextLine, bool $imagePending, array $table): float
    {
        $remaining = count($layout['lines']) - $nextLine;
        $height    = $this->linesHeight($layout, $nextLine);
        if ($imagePending) {
            $height += $table['imageSize'] + ($remaining > 0 ? 1 : 0);
        }

        return $height + (2 * self::GRID_PAD_Y);
    }

    /**
     * Hauteur d'une ligne de tableau : celle de sa cellule la plus haute.
     *
     * @param  array $layouts Cellules mises en lignes
     * @param  array $table   Reglages du tableau
     * @return float          Hauteur en mm
     */
    protected function rowHeight(array $layouts, array $table): float
    {
        $height = $table['lineH'] + (2 * self::GRID_PAD_Y);
        foreach ($layouts as $layout) {
            $height = max($height, $this->cellHeight($layout, 0, !empty($layout['image']), $table));
        }

        return $height;
    }

    /**
     * Entete du tableau, a la position courante : libelles en capitales, la couleur d'un niveau
     * en trait sous son libelle, un filet d'encre dessous.
     *
     * @param  TCPDF $pdf     PDF handler
     * @param  array $columns Colonnes
     * @param  array $table   Reglages du tableau
     * @return void
     */
    protected function drawGridHeader($pdf, array $columns, array $table)
    {
        $y      = $pdf->GetY();
        $x      = $this->marge_gauche;
        $height = $table['headerHeight'];
        foreach ($columns as $index => $column) {
            $layout = $table['headerCells'][$index];
            $this->drawGridCell($pdf, $x, $y, $column['width'], $height - 1.5, $layout, 0, count($layout['lines']), false, 'M', $table);

            if (!empty($layout['headerBar'])) {
                $barWidth = min(10, $column['width'] - (2 * self::GRID_PAD_X));
                $pdf->SetFillColor($layout['headerBar'][0], $layout['headerBar'][1], $layout['headerBar'][2]);
                $pdf->RoundedRect($x + (($column['width'] - $barWidth) / 2), $y + $height - 2.2, $barWidth, 1.1, 0.55, '1111', 'F');
            }
            $x += $column['width'];
        }

        $pdf->SetDrawColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetLineWidth(0.45);
        $pdf->Line($this->marge_gauche, $y + $height, $this->marge_gauche + $table['width'], $y + $height);
        $pdf->SetLineWidth(0.2);

        $pdf->SetY($y + $height + 0.6);
    }

    /**
     * Ligne de tableau, coupee entre deux pages si elle est plus haute qu'une page.
     *
     * @param  TCPDF $pdf      PDF handler
     * @param  array $columns  Colonnes
     * @param  array $layouts  Cellules mises en lignes
     * @param  array $table    Reglages du tableau
     * @param  array $rowStyle zebra (fond clair), total (ligne de totaux)
     * @return void
     */
    protected function drawGridRow($pdf, array $columns, array $layouts, array $table, array $rowStyle)
    {
        $nextLines     = [];
        $imagesPending = [];
        foreach ($layouts as $index => $layout) {
            $nextLines[$index]     = 0;
            $imagesPending[$index] = !empty($layout['image']);
        }

        $whole  = true;
        $guard  = 0;
        $header = $table['header'] ? $table['headerHeight'] + 0.6 : 0;
        while ($guard++ < 200) {
            $needed = $table['lineH'] + (2 * self::GRID_PAD_Y);
            foreach ($layouts as $index => $layout) {
                $needed = max($needed, $this->cellHeight($layout, $nextLines[$index], $imagesPending[$index], $table));
            }

            $available = $this->pageBottom($pdf) - $pdf->GetY();
            if ($needed <= $available + 0.01) {
                $this->drawGridChunk($pdf, $columns, $layouts, $nextLines, $imagesPending, $needed, $whole ? '' : 'T', $table, $rowStyle);
                return;
            }

            // Une ligne qui tient sur une page n'est pas coupee : elle passe entiere a la suivante
            $fullPage = $this->pageBottom($pdf) - $this->marge_haute - $header;
            if (($whole && $needed <= $fullPage) || $available < (2 * self::GRID_PAD_Y) + (3 * $table['lineH'])) {
                $this->newPage($pdf);
                if ($table['header']) {
                    $this->drawGridHeader($pdf, $columns, $table);
                }
                continue;
            }

            $this->drawGridChunk($pdf, $columns, $layouts, $nextLines, $imagesPending, $available, 'T', $table, $rowStyle);
            $whole = false;
            $this->newPage($pdf);
            if ($table['header']) {
                $this->drawGridHeader($pdf, $columns, $table);
            }
        }
    }

    /**
     * Partie d'une ligne de tableau, sur la hauteur donnee : chaque cellule y ecrit les lignes
     * qui y tiennent et garde les autres pour la page suivante.
     *
     * @param  TCPDF  $pdf           PDF handler
     * @param  array  $columns       Colonnes
     * @param  array  $layouts       Cellules mises en lignes
     * @param  array  $nextLines     Premiere ligne restant a ecrire, par cellule (mise a jour)
     * @param  array  $imagesPending Image restant a poser, par cellule (mise a jour)
     * @param  float  $height        Hauteur de la partie
     * @param  string $valign        Alignement vertical impose, vide pour celui des cellules
     * @param  array  $table         Reglages du tableau
     * @param  array  $rowStyle      zebra (fond clair), total (ligne de totaux)
     * @return void
     */
    protected function drawGridChunk($pdf, array $columns, array $layouts, array &$nextLines, array &$imagesPending, float $height, string $valign, array $table, array $rowStyle)
    {
        $y = $pdf->GetY();
        $x = $this->marge_gauche;

        if (!empty($rowStyle['zebra'])) {
            $pdf->SetFillColor($this->surface[0], $this->surface[1], $this->surface[2]);
            $pdf->Rect($x, $y, $table['width'], $height, 'F');
        }
        if (!empty($rowStyle['total'])) {
            $pdf->SetDrawColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($x, $y, $x + $table['width'], $y);
            $pdf->SetLineWidth(0.2);
        }

        foreach ($columns as $index => $column) {
            $layout = $layouts[$index];
            $usable = $height - (2 * self::GRID_PAD_Y);

            $withImage = $imagesPending[$index] && ($table['imageSize'] <= $usable + 0.01);
            if ($withImage) {
                $usable -= $table['imageSize'] + 1;
            }

            // Les lignes qui tiennent, chacune avec sa hauteur
            $count = 0;
            $used  = 0;
            for ($line = $nextLines[$index]; $line < count($layout['lines']); $line++) {
                if ($used + $layout['lines'][$line]['h'] > $usable + 0.01) {
                    break;
                }
                $used += $layout['lines'][$line]['h'];
                $count++;
            }

            $this->drawGridCell($pdf, $x, $y, $column['width'], $height, $layout, $nextLines[$index], $count, $withImage, $valign ?: $layout['valign'], $table);

            $nextLines[$index] += $count;
            if ($withImage) {
                $imagesPending[$index] = false;
            }
            $x += $column['width'];
        }

        if (empty($rowStyle['total'])) {
            $this->hairline($pdf, $y + $height);
        }

        $pdf->SetY($y + $height);
    }

    /**
     * Contenu d'une cellule : image puis lignes de texte, avec leurs traits et leurs pastilles.
     *
     * @param  TCPDF  $pdf       PDF handler
     * @param  float  $x         Abscisse
     * @param  float  $y         Ordonnee
     * @param  float  $width     Largeur
     * @param  float  $height    Hauteur
     * @param  array  $layout    Cellule mise en lignes
     * @param  int    $from      Premiere ligne a ecrire
     * @param  int    $count     Nombre de lignes a ecrire
     * @param  bool   $withImage Poser l'image de la cellule
     * @param  string $valign    T ou M
     * @param  array  $table     Reglages du tableau
     * @return void
     */
    protected function drawGridCell($pdf, float $x, float $y, float $width, float $height, array $layout, int $from, int $count, bool $withImage, string $valign, array $table)
    {
        $lines   = array_slice($layout['lines'], $from, $count);
        $content = array_sum(array_column($lines, 'h')) + ($withImage ? $table['imageSize'] + ($count > 0 ? 1 : 0) : 0);
        $top     = $y + self::GRID_PAD_Y;
        if ($valign == 'M') {
            $top = $y + max(self::GRID_PAD_Y, ($height - $content) / 2);
        }

        if ($withImage) {
            $imageSize = $table['imageSize'];
            $pdf->Image($layout['image'], $x + (($width - $imageSize) / 2), $top, $imageSize, $imageSize, '', '', '', false, 300, '', false, false, 0, 'CM');
            $top += $imageSize + 1;
        }

        $left  = $x + self::GRID_PAD_X + $layout['indent'];
        $inner = $width - (2 * self::GRID_PAD_X) - $layout['indent'];
        foreach ($lines as $lineData) {
            if (!empty($lineData['gap'])) {
                $top += $lineData['h'];
                continue;
            }

            $color = $lineData['color'] ?: $this->inkSoft;
            $pdf->SetFont('', $lineData['style'], $lineData['size']);

            if ($layout['chip']) {
                // Pastille arrondie, centree dans la cellule
                $chipHeight = $lineData['h'] - 0.6;
                $chipWidth  = min($inner, max(self::CHIP_MIN_WIDTH, $pdf->GetStringWidth($lineData['text']) + 4));
                $chipX      = $left + (($inner - $chipWidth) / 2);
                $pdf->SetFillColor($layout['chipFill'][0], $layout['chipFill'][1], $layout['chipFill'][2]);
                $pdf->RoundedRect($chipX, $top + 0.3, $chipWidth, $chipHeight, min($chipHeight / 2, 2.4), '1111', 'F');
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
                $pdf->SetXY($chipX, $top + 0.3);
                $pdf->Cell($chipWidth, $chipHeight, $lineData['text'], 0, 0, 'C');
                $top += $lineData['h'];
                continue;
            }

            // Trait de couleur sur toute la hauteur du bloc : les lignes se suivent, les traits aussi
            if (!empty($lineData['bar'])) {
                $pdf->SetFillColor($lineData['bar'][0], $lineData['bar'][1], $lineData['bar'][2]);
                $pdf->Rect($left, $top, 0.9, $lineData['h'], 'F');
            }

            if (!empty($lineData['badge'])) {
                $badge       = $lineData['badge'];
                $badgeHeight = min(4.2, $lineData['h'] - 0.4);
                $badgeX      = $left + (!empty($lineData['bar']) ? 2.6 : 0);
                $badgeY      = $top + (($lineData['h'] - $badgeHeight) / 2);
                $pdf->SetFillColor($badge['fill'][0], $badge['fill'][1], $badge['fill'][2]);
                $pdf->RoundedRect($badgeX, $badgeY, $lineData['badgeW'], $badgeHeight, $badgeHeight / 2, '1111', 'F');
                $pdf->SetFont('', 'B', 5.6);
                $pdf->SetTextColor($badge['ink'][0], $badge['ink'][1], $badge['ink'][2]);
                $pdf->SetXY($badgeX, $badgeY);
                $pdf->Cell($lineData['badgeW'], $badgeHeight, $badge['text'], 0, 0, 'C');
                $pdf->SetFont('', $lineData['style'], $lineData['size']);
            }

            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetXY($left + $lineData['offset'], $top);
            $pdf->Cell($inner - $lineData['offset'], $lineData['h'], $lineData['text'], 0, 0, $layout['align']);
            $top += $lineData['h'];
        }

        $pdf->SetTextColor(0, 0, 0);
    }
}
