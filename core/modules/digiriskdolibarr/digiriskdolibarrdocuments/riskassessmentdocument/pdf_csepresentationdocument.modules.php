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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/riskassessmentdocument/pdf_csepresentationdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Presentation du document unique au CSE, en diapositives PDF natives au format 16:9.
 *
 *          Le deroule est celui d'un rapport de bilan : une couverture, un sommaire, une synthese
 *          chiffree, puis trois parties ouvertes chacune par une diapositive de separation - les
 *          resultats de l'evaluation, le plan d'actions, les prochaines etapes - et une diapositive
 *          de cloture. Un fil de navigation en tete de chaque diapositive dit ou l'on en est.
 *
 *          Chaque diapositive est bornee : les listes sont plafonnees par les constantes MAX_*
 *          plutot que de deborder sur une page de plus, ce qui casserait le decoupage.
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

/**
 * Classe de generation de la presentation CSE du document unique.
 */
class pdf_csepresentationdocument extends ModelePDFDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'riskassessmentdocument';

    /**
     * Nombre de risques prioritaires projetes. Une diapositive qui en porte plus ne se lit
     * plus au videoprojecteur, et le detail est dans le document unique complet.
     */
    const MAX_PRIORITY_RISKS = 6;

    /**
     * Nombre de familles de risques dans le diagramme en barres.
     */
    const MAX_CATEGORIES = 8;

    /**
     * Nombre de familles detaillees sur la diapositive de zoom.
     */
    const MAX_ZOOM_CATEGORIES = 3;

    /**
     * Nombre d'unites de travail dans le classement des plus exposees.
     */
    const MAX_EXPOSED_UNITS = 7;

    /**
     * Nombre d'echeances a venir projetees.
     */
    const MAX_NEXT_DEADLINES = 5;

    /**
     * Nombre d'unites de travail citees sur la diapositive du perimetre.
     */
    const MAX_WORK_UNITS = 14;

    /**
     * Epaisseur de la barre de progression du pied de diapositive, en mm.
     */
    const PROGRESS_HEIGHT = 2.4;

    /**
     * Hauteur du fil de navigation en tete de diapositive, en mm.
     */
    const NAV_HEIGHT = 9;

    /**
     * @var array Bleu nuit des diapositives pleines : couverture, separateurs, cloture
     */
    protected array $deep = [9, 44, 79];

    /**
     * @var array Diapositives sur fond plein, sans pied de page clair
     */
    protected array $darkPages = [];

    /**
     * @var array Parties du deroule, dans l'ordre du fil de navigation
     */
    protected array $sections = [
        'summary' => 'DigiriskCseNavSummary',
        'results' => 'DigiriskCseNavResults',
        'actions' => 'DigiriskCseNavActions',
        'next'    => 'DigiriskCseNavNext'
    ];

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'csepresentationdocument';
        $this->description = $langs->trans('DigiriskCsePresentationPDFDescription');

        // 16:9 paysage : le format des videoprojecteurs et des ecrans de salle de reunion
        $this->orientation  = 'L';
        $this->page_largeur = 338;
        $this->page_hauteur = 190;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 18;
        $this->marge_droite = 18;
        $this->marge_haute  = 11;
        $this->marge_basse  = 13;

        // La couverture ne se numerote pas : le pied de page commence a la diapositive suivante
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

        $outputLangs->loadLangs(['main', 'companies', 'projects', 'other', 'digiriskdolibarr@digiriskdolibarr']);

        // Les modeles PDF du document unique ecrivent dans le meme repertoire que le gabarit
        // ODT : le nom du document est ce qui les distingue dans la liste des fichiers
        $moreParam['hideTemplateName'] = 1;
        $moreParam['documentName']     = 'PresentationCSE_';
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

        $stats = $this->loadDigiriskStatistics($moreParam);
        $size  = pdf_getPDFFontSize($outputLangs) + 2;
        $pdf   = $this->openPdf($object, $outputLangs, $outputLangs->transnoentities('DigiriskCsePresentationTitle'));

        $this->footerText = $outputLangs->transnoentities('DigiriskCsePresentationTitle') . ' - ' . $mysoc->name;

        // La couverture est deja ouverte par openPdf() : elle ne passe pas par slide()
        $this->slideCover($pdf, $object, $outputLangs, $size, $stats);
        $this->slideAgenda($pdf, $outputLangs, $size);
        $this->slideExecutiveSummary($pdf, $outputLangs, $size, $stats);

        $this->separator($pdf, 1, 'DigiriskCseNavResults', 'DigiriskCsePartResultsHint', $outputLangs, $size);
        $this->slidePerimeter($pdf, $outputLangs, $size, $stats);
        $this->slideLevels($pdf, $outputLangs, $size, $stats);
        $this->slideCategories($pdf, $outputLangs, $size, $stats);
        $this->slideCategoryZoom($pdf, $outputLangs, $size, $stats);
        $this->slideExposedUnits($pdf, $outputLangs, $size, $stats);
        $this->slidePriorityRisks($pdf, $outputLangs, $size, $stats);
        $this->slidePsychosocial($pdf, $outputLangs, $size, $stats);

        $this->separator($pdf, 2, 'DigiriskCseNavActions', 'DigiriskCsePartActionsHint', $outputLangs, $size);
        $this->slideActionPlan($pdf, $outputLangs, $size, $stats);
        $this->slideAccidents($pdf, $outputLangs, $size, $stats);

        $this->separator($pdf, 3, 'DigiriskCseNavNext', 'DigiriskCsePartNextHint', $outputLangs, $size);
        $this->slideAskCse($pdf, $outputLangs, $size, $stats);
        $this->slideRoadmap($pdf, $outputLangs, $size, $stats);
        $this->slideClosing($pdf, $object, $outputLangs, $size);

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
     * Pose le pied sur les diapositives claires, la seule barre de progression sur les pleines.
     *
     * @param  TCPDF $pdf PDF handler
     * @return void
     */
    protected function writeFooter($pdf)
    {
        $currentPage = $pdf->getPage();
        $currentY    = $pdf->GetY();
        $totalPages  = $pdf->getNumPages();
        $halfWidth   = $this->contentWidth($pdf) / 2;

        for ($page = $this->footerFirstPage; $page <= $totalPages; $page++) {
            $pdf->setPage($page);
            $pdf->SetAutoPageBreak(false);

            // Un filet et un texte gris se perdraient sur le bleu nuit : les diapositives pleines
            // ne gardent que la progression
            if (empty($this->darkPages[$page])) {
                $pdf->SetFont('', '', $this->footerFontSize);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($this->marge_gauche, $pdf->getPageHeight() - 10);
                $pdf->Cell($halfWidth, 5, $this->footerText, 0, 0, 'L');
                $pdf->Cell($halfWidth, 5, $page . ' / ' . $totalPages, 0, 0, 'R');
            }

            $this->decorateFooter($pdf, $page, $totalPages);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setPage($currentPage);
        $pdf->SetXY($this->marge_gauche, $currentY);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Barre de progression du deroule, en bas de chaque diapositive.
     *
     * @param  TCPDF $pdf        PDF handler
     * @param  int   $page       Diapositive courante
     * @param  int   $totalPages Nombre de diapositives
     * @return void
     */
    protected function decorateFooter($pdf, int $page, int $totalPages)
    {
        if ($totalPages <= 1) {
            return;
        }

        $width = $pdf->GetPageWidth();
        $y     = $pdf->GetPageHeight() - self::PROGRESS_HEIGHT;
        $dark  = !empty($this->darkPages[$page]);

        $rail = $dark ? $this->tint($this->deep, 0.2) : $this->tint($this->accent, 0.88);
        $fill = $dark ? $this->tint($this->accent, 0.45) : $this->accent;

        $pdf->SetFillColor($rail[0], $rail[1], $rail[2]);
        $pdf->Rect(0, $y, $width, self::PROGRESS_HEIGHT, 'F');

        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $pdf->Rect(0, $y, $width * $page / $totalPages, self::PROGRESS_HEIGHT, 'F');
    }

    /**
     * Fond plein bleu nuit, avec deux disques clairs en transparence pour la profondeur.
     *
     * @param  TCPDF $pdf PDF handler
     * @return void
     */
    protected function deepBackground($pdf)
    {
        $width  = $pdf->GetPageWidth();
        $height = $pdf->GetPageHeight();

        $pdf->SetFillColor($this->deep[0], $this->deep[1], $this->deep[2]);
        $pdf->Rect(0, 0, $width, $height, 'F');

        $pdf->SetAlpha(0.07);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($width - 40, 30, 92, 0, 360, 'F');
        $pdf->Circle($width - 110, $height - 10, 46, 0, 360, 'F');
        $pdf->SetAlpha(1);

        $this->darkPages[$pdf->getPage()] = true;
    }

    /**
     * Fil de navigation : les parties du deroule, la partie courante soulignee.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  string    $current     Cle de la partie courante
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function nav($pdf, string $current, Translate $outputLangs, float $size)
    {
        global $mysoc;

        $x = $this->marge_gauche;
        $y = $this->marge_haute;

        $pdf->setFontSpacing(0.35);
        foreach ($this->sections as $key => $labelKey) {
            $label  = dol_strtoupper($outputLangs->transnoentities($labelKey));
            $active = $key === $current;

            $pdf->SetFont('', 'B', $size - 4);
            $width = $pdf->GetStringWidth($label) + 2;
            $ink   = $active ? $this->accent : $this->inkMuted;

            $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
            $pdf->SetXY($x, $y);
            $pdf->Cell($width, 5, $label, 0, 0, 'L');

            if ($active) {
                $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
                $pdf->RoundedRect($x, $y + 6, $width - 2, 0.9, 0.45, '1111', 'F');
            }

            $x += $width + 8;
        }
        $pdf->setFontSpacing(0);

        $pdf->SetFont('', '', $size - 4);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->SetXY($pdf->GetPageWidth() - $this->marge_droite - 100, $y);
        $pdf->Cell(100, 5, $mysoc->name, 0, 0, 'R');

        $this->hairline($pdf, $y + 7.9);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Ouvre une diapositive claire : fil de navigation, titre, accroche.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  string    $section     Cle de la partie dans le fil de navigation
     * @param  string    $title       Titre de la diapositive
     * @param  string    $subtitle    Accroche sous le titre, vide pour aucune
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police de reference
     * @return void
     */
    protected function slide($pdf, string $section, string $title, string $subtitle, Translate $outputLangs, float $size)
    {
        $this->newPage($pdf);
        $this->nav($pdf, $section, $outputLangs, $size);

        $y = $this->marge_haute + self::NAV_HEIGHT + 5;

        $pdf->SetFont('', 'B', $size + 10);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($this->marge_gauche, $y);
        $pdf->Cell($this->contentWidth($pdf), 11, $title, 0, 1, 'L');

        if (dol_strlen($subtitle)) {
            $pdf->SetFont('', '', $size);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetX($this->marge_gauche);
            $pdf->MultiCell($this->contentWidth($pdf), $this->height + 1, $subtitle, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 6);
    }

    /**
     * Diapositive de separation d'une partie : grand numero, titre ponctue, accroche.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $number      Numero de la partie
     * @param  string    $titleKey    Cle du titre de la partie
     * @param  string    $hintKey     Cle de l'accroche
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function separator($pdf, int $number, string $titleKey, string $hintKey, Translate $outputLangs, float $size)
    {
        $this->newPage($pdf);
        $pdf->SetAutoPageBreak(false);
        $this->deepBackground($pdf);

        $light = $this->tint($this->accent, 0.55);

        $pdf->SetFont('', 'B', $size + 60);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetXY($this->marge_gauche + 10, 42);
        $pdf->Cell(120, 40, sprintf('%02d', $number), 0, 0, 'L');

        $pdf->SetFont('', 'B', $size + 30);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($this->marge_gauche + 10, 88);
        $pdf->Cell($this->contentWidth($pdf) - 20, 20, $outputLangs->transnoentities($titleKey) . '.', 0, 0, 'L');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect($this->marge_gauche + 11, 114, 30, 1.6, 0.8, '1111', 'F');

        $pdf->SetFont('', '', $size + 3);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetXY($this->marge_gauche + 10, 121);
        $pdf->MultiCell($this->contentWidth($pdf) * 0.6, 7, $outputLangs->transnoentities($hintKey), 0, 'L');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Liste a puces d'une diapositive.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $items Lignes de texte
     * @param  float $size  Taille de police
     * @param  float $width Largeur de la liste, la largeur utile par defaut
     * @param  float $x     Abscisse de la liste, la marge gauche par defaut
     * @return void
     */
    protected function bulletList($pdf, array $items, float $size, float $width = 0, float $x = 0)
    {
        if (empty($items)) {
            return;
        }

        $width = $width ?: $this->contentWidth($pdf);
        $x     = $x ?: $this->marge_gauche;

        // Une puce se lit a plusieurs metres : la liste est posee plus grande et plus aeree que
        // le texte courant du document
        $pdf->SetFont('', '', $size + 1);

        foreach ($items as $item) {
            $y = $pdf->GetY();

            // La puce est un disque de la couleur d'accent : un caractere de puce depend de la
            // police embarquee, une forme dessinee non
            $this->card($pdf, $x + 1, $y + 2.4, 2.8, 2.8, $this->accent, 1.4);

            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($x + 8, $y);
            $pdf->MultiCell($width - 8, $this->height + 2, $item, 0, 'L');
            $pdf->Ln(3);
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Grand chiffre pose sans carte : la valeur, puis son libelle dessous.
     *
     * @param  TCPDF  $pdf       PDF handler
     * @param  float  $x         Abscisse
     * @param  float  $y         Ordonnee
     * @param  float  $width     Largeur
     * @param  string $value     Valeur
     * @param  string $label     Libelle
     * @param  float  $valueSize Taille de police de la valeur
     * @param  float  $size      Taille de police du libelle
     * @param  array  $color     Couleur de la valeur
     * @param  array  $ink       Couleur du libelle
     * @return float             Ordonnee du bas du bloc
     */
    protected function bigNumber($pdf, float $x, float $y, float $width, string $value, string $label, float $valueSize, float $size, array $color, array $ink = []): float
    {
        $ink = $ink ?: $this->inkSoft;

        $pdf->SetFont('', 'B', $valueSize);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $valueSize * 0.42, $value, 0, 0, 'L');

        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $pdf->MultiCell($width, $this->height, $label, 0, 'L', false, 1, $x, $y + ($valueSize * 0.42) + 1, true, 0, false, true, 0, 'T');

        $pdf->SetTextColor(0, 0, 0);

        return $pdf->GetY();
    }

    /**
     * Barre de niveaux miniature : un segment par niveau non vide, proportionnel a son nombre.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $x      Abscisse
     * @param  float $y      Ordonnee
     * @param  float $width  Largeur
     * @param  float $height Hauteur
     * @param  array $counts Nombre de risques par niveau, indexe de 1 a 4
     * @return void
     */
    protected function miniLevelBar($pdf, float $x, float $y, float $width, float $height, array $counts)
    {
        $total = array_sum($counts);
        if ($total <= 0) {
            return;
        }

        // Du plus grave au moins grave, de gauche a droite : c'est la part grave qui se lit. Les
        // segments sont separes d'un filet, comme ceux de stackedLevelBar()
        $gap       = 0.8;
        $filled    = array_filter($counts);
        $available = $width - ((count($filled) - 1) * $gap);
        foreach ([4, 3, 2, 1] as $level) {
            $count = (int) ($counts[$level] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $segment = $available * $count / $total;
            $this->card($pdf, $x, $y, $segment, $height, $this->levelBg[$level], min($height / 2, $segment / 2));
            $x += $segment + $gap;
        }
    }

    /**
     * Couverture : fond plein, le titre a gauche, trois chiffres a droite.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideCover($pdf, $object, Translate $outputLangs, float $size, array $stats)
    {
        global $mysoc;

        // Toute la couverture se pose a coordonnees imposees, jusqu'en bas de la diapositive :
        // la rupture automatique ouvrirait une page de plus des le premier texte ecrit sous le
        // seuil de rupture, et le deroule gagnerait une diapositive vide
        $pdf->SetAutoPageBreak(false);
        $this->deepBackground($pdf);

        $width = $this->contentWidth($pdf);
        $light = $this->tint($this->accent, 0.55);

        $logo = $this->companyLogoPath($object);
        if (!empty($logo)) {
            $logoWidth = $this->companyLogoSize($logo, 14, 70)[0];
            $this->card($pdf, $this->marge_gauche, 16, $logoWidth + 10, 22, [255, 255, 255], 3);
            $this->companyLogo($pdf, $object, $this->marge_gauche + 5, 20, 14, 70);
        }

        $titleWidth = $width * 0.58;

        $this->kicker($pdf, $this->marge_gauche, 60, $titleWidth, $outputLangs->transnoentities('DigiriskCseKicker'), $size, $light);

        $pdf->SetFont('', 'B', $size + 30);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($this->marge_gauche, 67);
        $pdf->Cell($titleWidth, 20, $outputLangs->transnoentities('DigiriskCseCoverTitle'), 0, 1, 'L');

        $pdf->SetFont('', '', $size + 10);
        $pdf->SetXY($this->marge_gauche, 88);
        $pdf->Cell($titleWidth, 11, $outputLangs->transnoentities('DigiriskCseHeadline'), 0, 1, 'L');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect($this->marge_gauche, 106, 30, 1.6, 0.8, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 4);
        $pdf->SetXY($this->marge_gauche, 113);
        $pdf->Cell($titleWidth, 9, $mysoc->name, 0, 1, 'L');

        $pdf->SetFont('', '', $size - 1);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $period = $this->auditPeriodLabel($stats, $outputLangs);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($titleWidth, 6, (dol_strlen($period) ? $period . '  -  ' : '') . $outputLangs->transnoentities('DigiriskSynthesisGeneratedAt') . ' ' . dol_print_date(dol_now(), 'day', 'tzuser', $outputLangs), 0, 1, 'L');

        // Les trois chiffres qui situent le document avant meme la premiere diapositive
        $columnX = $this->marge_gauche + ($width * 0.68);
        $columnW = $width * 0.32;
        $figures = [
            [(string) $stats['totalRisks'],          $outputLangs->transnoentities('DigiriskSynthesisRisksAssessed')],
            [(string) $stats['nbWorkunit'],          $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount')],
            [(string) $stats['actionPlan']['total'], $outputLangs->transnoentities('DigiriskSynthesisActionsTotal')]
        ];

        $y = 50;
        foreach ($figures as $index => $figure) {
            if ($index > 0) {
                $pdf->SetDrawColor($light[0], $light[1], $light[2]);
                $pdf->SetLineWidth(0.2);
                $pdf->Line($columnX, $y - 5, $columnX + $columnW, $y - 5);
            }
            $this->bigNumber($pdf, $columnX, $y, $columnW, $figure[0], $figure[1], $size + 22, $size, [255, 255, 255], $light);
            $y += 33;
        }

        $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Sommaire : une carte par partie, numero en grand.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function slideAgenda($pdf, Translate $outputLangs, float $size)
    {
        $this->slide($pdf, 'summary', $outputLangs->transnoentities('DigiriskCseAgenda'), '', $outputLangs, $size);

        $parts = [
            ['00', 'DigiriskCseNavSummary', 'DigiriskCsePartSummaryHint', $this->accentAlt],
            ['01', 'DigiriskCseNavResults', 'DigiriskCsePartResultsHint', $this->accent],
            ['02', 'DigiriskCseNavActions', 'DigiriskCsePartActionsHint', $this->levelBg[2]],
            ['03', 'DigiriskCseNavNext',    'DigiriskCsePartNextHint',    $this->categoryColors[7]]
        ];

        $gap    = 6;
        $width  = ($this->contentWidth($pdf) - (3 * $gap)) / 4;
        $height = 112;
        $y      = $pdf->GetY() + 4;
        $x      = $this->marge_gauche;

        foreach ($parts as $part) {
            $color = $part[3];
            $this->card($pdf, $x, $y, $width, $height, $this->surface, 4);
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->RoundedRect($x, $y, $width, 3, 1.5, '1100', 'F');

            $pdf->SetFont('', 'B', $size + 26);
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetXY($x + 8, $y + 14);
            $pdf->Cell($width - 16, 18, $part[0], 0, 0, 'L');

            $pdf->SetFont('', 'B', $size + 3);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->MultiCell($width - 16, 7, $outputLangs->transnoentities($part[1]), 0, 'L', false, 1, $x + 8, $y + 42, true, 0, false, true, 0, 'T');

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($width - 16, $this->height + 0.5, $outputLangs->transnoentities($part[2]), 0, 'L', false, 1, $x + 8, $pdf->GetY() + 3, true, 0, false, true, 0, 'T');

            $x += $width + $gap;
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Synthese : le chiffre qui resume tout, quatre chiffres d'appui, ce qu'il faut retenir.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideExecutiveSummary($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'summary', $outputLangs->transnoentities('DigiriskCseSlideSummary'), $outputLangs->transnoentities('DigiriskCseSlideSummarySubtitle'), $outputLangs, $size);

        $width      = $this->contentWidth($pdf);
        $y          = $pdf->GetY();
        $leftWidth  = $width * 0.36;
        $priority   = (int) $stats['levelCounts'][3] + (int) $stats['levelCounts'][4];
        $actionPlan = $stats['actionPlan'];

        // A gauche, le chiffre heros sur une carte d'accent
        $heroHeight = 100;
        $this->card($pdf, $this->marge_gauche, $y, $leftWidth, $heroHeight, $this->accent, 4);
        $light = $this->tint($this->accent, 0.7);

        $this->kicker($pdf, $this->marge_gauche + 10, $y + 10, $leftWidth - 20, $outputLangs->transnoentities('DigiriskCseHeroKicker'), $size, $light);
        $this->bigNumber($pdf, $this->marge_gauche + 10, $y + 20, $leftWidth - 20, (string) $stats['totalRisks'], $outputLangs->transnoentities('DigiriskSynthesisRisksAssessed'), $size + 46, $size + 2, [255, 255, 255], [255, 255, 255]);

        $share    = $stats['totalRisks'] > 0 ? round($priority * 100 / $stats['totalRisks']) : 0;
        $heroText = $priority > 0 ? $outputLangs->transnoentities('DigiriskCseHeroPriority', $priority, $share) : $outputLangs->transnoentities('DigiriskCseHeroNoPriority');
        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->MultiCell($leftWidth - 20, $this->height + 1, $heroText, 0, 'L', false, 1, $this->marge_gauche + 10, $y + 66, true, 0, false, true, 0, 'T');

        if ($stats['totalRisks'] > 0) {
            $this->miniLevelBar($pdf, $this->marge_gauche + 10, $y + $heroHeight - 16, $leftWidth - 20, 5, $stats['levelCounts']);
        }

        // A droite, quatre chiffres d'appui en deux rangees, puis l'encadre
        $rightX     = $this->marge_gauche + $leftWidth + 8;
        $rightWidth = $width - $leftWidth - 8;
        $doneShare  = $actionPlan['total'] > 0 ? round($actionPlan['done'] * 100 / $actionPlan['total']) . ' %' : '-';
        $tiles      = [
            [(string) $stats['nbWorkunit'],         $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount'),   $this->accentAlt],
            [(string) $actionPlan['total'],          $outputLangs->transnoentities('DigiriskSynthesisActionsTotal'),     $this->accent],
            [$doneShare,                             $outputLangs->transnoentities('DigiriskSynthesisActionsDoneShare'), $this->accentAlt],
            [(string) $stats['accidents']['lastYear'], $outputLangs->transnoentities('DigiriskCseAccidentsLastYearLong'), $this->accentWarn]
        ];

        $tileGap    = 5;
        $tileWidth  = ($rightWidth - (3 * $tileGap)) / 4;
        $tileX      = $rightX;
        $labelLines = $this->tileLabelLines($pdf, array_column($tiles, 1), $tileWidth, $size);
        foreach ($tiles as $tile) {
            $this->statTile($pdf, $tileX, $y, $tileWidth, 38, $tile[0], $tile[1], $tile[2], $size, $labelLines);
            $tileX += $tileWidth + $tileGap;
        }

        $this->calloutBox($pdf, $rightX, $y + 44, $rightWidth, $outputLangs->transnoentities('DigiriskSynthesisHighlights'), $this->highlights($outputLangs, $stats), $size);
    }

    /**
     * Lecture des chiffres du document unique, en une phrase par constat.
     *
     * @param  Translate $outputLangs Lang object
     * @param  array     $stats       Chiffres du document unique
     * @return array                  Phrases de l'encadre
     */
    protected function highlights(Translate $outputLangs, array $stats): array
    {
        $priority   = (int) $stats['levelCounts'][3] + (int) $stats['levelCounts'][4];
        $actionPlan = $stats['actionPlan'];
        $rps        = $stats['rps'];
        $lines      = [];

        $lines[] = $priority > 0
            ? $outputLangs->transnoentities('DigiriskSynthesisHighlightPriority', $priority)
            : $outputLangs->transnoentities('DigiriskSynthesisHighlightNoPriority');

        if ($actionPlan['late'] > 0) {
            $lines[] = $outputLangs->transnoentities('DigiriskSynthesisHighlightLate', $actionPlan['late']);
        }
        if ($actionPlan['total'] > 0) {
            $lines[] = $outputLangs->transnoentities('DigiriskSynthesisHighlightPlan', $actionPlan['done'], $actionPlan['total']);
        }

        if ($rps['toQualify'] > 0) {
            $lines[] = $outputLangs->transnoentities('DigiriskSynthesisHighlightRpsToQualify', $rps['toQualify']);
        } elseif ($rps['concerned'] > 0) {
            $lines[] = $outputLangs->transnoentities('DigiriskSynthesisHighlightRps', $rps['assessedConcerned'], $rps['concerned']);
        }

        $lines[] = $outputLangs->transnoentities('DigiriskSynthesisHighlightAccidents', $stats['accidents']['lastYear']);

        return $lines;
    }

    /**
     * Diapositive du perimetre et de la methode.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slidePerimeter($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlidePerimeter'), $outputLangs->transnoentities('DigiriskCseSlidePerimeterSubtitle'), $outputLangs, $size);

        $width     = $this->contentWidth($pdf);
        $leftWidth = $width * 0.42;
        $y         = $pdf->GetY();

        // A gauche : trois chiffres en colonne, puis l'echelle de cotation
        $tileWidth = ($leftWidth - 10) / 3;
        $tileX     = $this->marge_gauche;
        $tiles     = [
            [(string) $stats['nbGroupment'],  $outputLangs->transnoentities('DigiriskSynthesisGroupmentsCount'), $this->accent],
            [(string) $stats['nbWorkunit'],   $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount'),  $this->accentAlt],
            [(string) $stats['nbEvaluators'], $outputLangs->transnoentities('EvaluatorsMin'),                    $this->accent]
        ];
        $labelLines = $this->tileLabelLines($pdf, array_column($tiles, 1), $tileWidth, $size);
        foreach ($tiles as $tile) {
            $this->statTile($pdf, $tileX, $y, $tileWidth, 36, $tile[0], $tile[1], $tile[2], $size, $labelLines);
            $tileX += $tileWidth + 5;
        }

        $this->kicker($pdf, $this->marge_gauche, $y + 46, $leftWidth, $outputLangs->transnoentities('DigiriskCseScale'), $size, $this->accent);
        $scaleY = $y + 54;
        foreach ([4 => '80 - 100', 3 => '51 - 79', 2 => '48 - 50', 1 => '0 - 47'] as $level => $range) {
            $this->pill($pdf, $this->marge_gauche, $scaleY, 24, 8, $range, $this->levelBg[$level], $this->levelText[$level], $size - 2);
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($this->marge_gauche + 28, $scaleY);
            $pdf->Cell($leftWidth - 28, 8, $outputLangs->transnoentities($this->levelLabelKey($level)) . ' - ' . $outputLangs->transnoentities('DigiriskCseScaleHint' . $level), 0, 0, 'L');
            $scaleY += 10.5;
        }

        // A droite : les unites de travail evaluees, sur deux colonnes
        $rightX     = $this->marge_gauche + $leftWidth + 12;
        $rightWidth = $width - $leftWidth - 12;

        $units = [];
        foreach ($stats['elements'] as $element) {
            if (($element['object']->element_type ?? '') != 'workunit') {
                continue;
            }
            if (count($units) >= self::MAX_WORK_UNITS) {
                $units[] = $outputLangs->transnoentities('DigiriskCseMoreWorkUnits', $stats['nbWorkunit'] - self::MAX_WORK_UNITS);
                break;
            }
            $units[] = $this->elementLabel($element, false);
        }

        $this->card($pdf, $rightX, $y, $rightWidth, 100, $this->surface, 4);
        $this->kicker($pdf, $rightX + 8, $y + 7, $rightWidth - 16, $outputLangs->transnoentities('DigiriskCseWorkUnitsEvaluated'), $size, $this->accent);

        if (empty($units)) {
            $pdf->SetFont('', '', $size);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($rightX + 8, $y + 16);
            $pdf->Cell($rightWidth - 16, 6, $outputLangs->transnoentities('DigiriskSynthesisNoWorkUnit'), 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            return;
        }

        $columnWidth = ($rightWidth - 16) / 2;
        $perColumn   = (int) ceil(count($units) / 2);
        $pdf->SetFont('', '', $size - 1);
        foreach ($units as $index => $label) {
            $column = (int) floor($index / $perColumn);
            $lineX  = $rightX + 8 + ($column * $columnWidth);
            $lineY  = $y + 16 + (($index % $perColumn) * 10.5);

            $this->card($pdf, $lineX, $lineY + 2.2, 2.4, 2.4, $this->accentAlt, 1.2);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($lineX + 5, $lineY);
            $pdf->Cell($columnWidth - 8, 7, dol_trunc($label, 38), 0, 0, 'L');
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Diapositive de la repartition des risques par niveau.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideLevels($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlideLevels'), $outputLangs->transnoentities('DigiriskCseSlideLevelsSubtitle'), $outputLangs, $size);

        if (empty($stats['totalRisks'])) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoRisk'), $size + 1, 'B', $this->accentWarn);
            return;
        }

        $width = $this->contentWidth($pdf);
        $y     = $pdf->GetY();

        $slices = [];
        foreach ([4, 3, 2, 1] as $level) {
            $slices[] = ['value' => (int) $stats['levelCounts'][$level], 'color' => $this->levelBg[$level]];
        }

        $radius = 44;
        $this->donut($pdf, $this->marge_gauche + $radius + 4, $y + $radius + 4, $radius, $slices, (string) $stats['totalRisks'], $outputLangs->transnoentities('DigiriskSynthesisTotalRisks'), $size + 6);

        // A droite, une carte par niveau : le nombre, la part, ce que le niveau appelle
        $cardsX    = $this->marge_gauche + (2 * $radius) + 22;
        $cardsW    = $width - (2 * $radius) - 22;
        $cardGap   = 4;
        $cardWidth = ($cardsW - $cardGap) / 2;
        $cardH     = 45;

        foreach ([4, 3, 2, 1] as $index => $level) {
            $count = (int) $stats['levelCounts'][$level];
            $share = round($count * 100 / $stats['totalRisks']);
            $cardX = $cardsX + (($index % 2) * ($cardWidth + $cardGap));
            $cardY = $y + ((int) floor($index / 2) * ($cardH + $cardGap));

            $this->card($pdf, $cardX, $cardY, $cardWidth, $cardH, $this->tint($this->levelBg[$level], $level == 1 ? 0.5 : 0.86), 4);
            $this->pill($pdf, $cardX + 7, $cardY + 7, 48, 7, $outputLangs->transnoentities($this->levelLabelKey($level)), $this->levelBg[$level], $this->levelText[$level], $size - 3);

            $pdf->SetFont('', 'B', $size + 16);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($cardX + 7, $cardY + 17);
            $pdf->Cell(40, 12, (string) $count, 0, 0, 'L');

            $pdf->SetFont('', 'B', $size + 2);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($cardX + $cardWidth - 37, $cardY + 19);
            $pdf->Cell(30, 10, $share . ' %', 0, 0, 'R');

            $pdf->SetFont('', '', $size - 1.5);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($cardX + 7, $cardY + 32);
            $pdf->Cell($cardWidth - 14, 6, $outputLangs->transnoentities('DigiriskCseScaleHint' . $level), 0, 0, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Diapositive des familles de risques les plus representees.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideCategories($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlideCategories'), $outputLangs->transnoentities('DigiriskCseSlideCategoriesSubtitle'), $outputLangs, $size);

        if (empty($stats['byCategory'])) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoRisk'), $size + 1);
            return;
        }

        $risk = new Risk($this->db);
        $bars = [];

        foreach ($stats['byCategory'] as $position => $count) {
            if (count($bars) >= self::MAX_CATEGORIES) {
                break;
            }

            $label = $this->categoryLabel($risk, (int) $position);
            $bars[] = [
                'label' => dol_strlen($label) ? dol_trunc($label, 48) : $outputLangs->transnoentities('NoData'),
                'value' => $count,
                'color' => $this->categoryColor(count($bars)),
                'image' => $this->categoryPictoByPosition($risk, (int) $position)
            ];
        }

        $this->barChart($pdf, $bars, $this->contentWidth($pdf) * 0.38, $size, 11);
    }

    /**
     * Zoom sur les familles les plus representees : leur poids, leurs niveaux, leur risque le
     * plus cote.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideCategoryZoom($pdf, Translate $outputLangs, float $size, array $stats)
    {
        if (empty($stats['byCategory'])) {
            return;
        }

        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlideZoom'), $outputLangs->transnoentities('DigiriskCseSlideZoomSubtitle'), $outputLangs, $size);

        // Niveau de chaque risque, lu dans le classement par niveau deja calcule
        $levelByRisk = [];
        foreach ($stats['riskLevels'] as $level => $riskLines) {
            foreach ($riskLines as $riskLine) {
                $levelByRisk[(int) $riskLine->id] = (int) $level;
            }
        }

        $risk     = new Risk($this->db);
        $families = array_slice($stats['byCategory'], 0, self::MAX_ZOOM_CATEGORIES, true);
        $count    = count($families);
        $gap      = 6;
        $width    = ($this->contentWidth($pdf) - (($count - 1) * $gap)) / max(1, $count);
        $height   = 118;
        $y        = $pdf->GetY();
        $x        = $this->marge_gauche;
        $rank     = 0;

        foreach ($families as $position => $total) {
            $color  = $this->categoryColor($rank++);
            $levels = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            $top    = null;
            foreach ($stats['risks'] as $riskLine) {
                if ((int) $riskLine->category !== (int) $position) {
                    continue;
                }
                $levels[$levelByRisk[(int) $riskLine->id] ?? 1]++;
                // Les risques sont tries par cotation decroissante : le premier est le plus cote
                $top = $top ?? $riskLine;
            }

            $this->card($pdf, $x, $y, $width, $height, $this->surface, 4);
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->RoundedRect($x, $y, $width, 3, 1.5, '1100', 'F');

            $picto = $this->categoryPictoByPosition($risk, (int) $position);
            if (!empty($picto)) {
                $pdf->Image($picto, $x + 8, $y + 10, 18, 18, '', '', '', false, 300, '', false, false, 0, 'CM');
            }

            $pdf->SetFont('', 'B', $size + 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->MultiCell($width - 40, 6, $this->categoryLabel($risk, (int) $position), 0, 'L', false, 1, $x + 31, $y + 10, true, 0, false, true, 18, 'M');

            $share = $stats['totalRisks'] > 0 ? round($total * 100 / $stats['totalRisks']) : 0;
            $this->bigNumber($pdf, $x + 8, $y + 36, ($width - 16) / 2, $share . ' %', $outputLangs->transnoentities('DigiriskCseZoomShare'), $size + 16, $size - 1.5, $color);
            $this->bigNumber($pdf, $x + 8 + (($width - 16) / 2), $y + 36, ($width - 16) / 2, (string) $total, $outputLangs->transnoentities('DigiriskSynthesisTotalRisks'), $size + 16, $size - 1.5, $this->ink);

            $this->kicker($pdf, $x + 8, $y + 62, $width - 16, $outputLangs->transnoentities('DigiriskCseZoomLevels'), $size - 1);
            $this->miniLevelBar($pdf, $x + 8, $y + 69, $width - 16, 5, $levels);
            $pdf->SetFont('', '', $size - 3);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($x + 8, $y + 76);
            $pdf->Cell($width - 16, 5, $outputLangs->transnoentities('DigiriskCseZoomLevelsDetail', $levels[4], $levels[3], $levels[2], $levels[1]), 0, 0, 'L');

            if ($top !== null) {
                $element = $stats['elements'][$top->fk_element] ?? null;
                $level   = $levelByRisk[(int) $top->id] ?? 1;

                $this->kicker($pdf, $x + 8, $y + 88, $width - 16, $outputLangs->transnoentities('DigiriskCseZoomTopRisk'), $size - 1);
                $this->pill($pdf, $x + 8, $y + 95, 14, 8, (string) ($top->riskAssessmentCotation ?: 0), $this->levelBg[$level], $this->levelText[$level], $size - 2);

                $pdf->SetFont('', 'B', $size - 1.5);
                $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
                $pdf->SetXY($x + 25, $y + 94);
                $pdf->Cell($width - 33, 5, $element ? dol_trunc($this->elementLabel($element, false), 40) : '', 0, 0, 'L');

                $pdf->SetFont('', '', $size - 2.5);
                $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
                $pdf->MultiCell($width - 33, 4.2, $this->riskDescription($top, 90) ?: $top->ref, 0, 'L', false, 1, $x + 25, $y + 99.5, true, 0, false, true, 13, 'T');
            }

            $x += $width + $gap;
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Diapositive des unites de travail les plus exposees : la somme de leurs cotations.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideExposedUnits($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlideExposed'), $outputLangs->transnoentities('DigiriskCseSlideExposedSubtitle'), $outputLangs, $size);

        $totals = [];
        foreach ($stats['byElement'] as $elementId => $riskLines) {
            if (empty($stats['elements'][$elementId])) {
                continue;
            }
            $sum = 0;
            foreach ($riskLines as $riskLine) {
                $sum += (int) $riskLine->riskAssessmentCotation;
            }
            $totals[$elementId] = ['sum' => $sum, 'count' => count($riskLines)];
        }

        if (empty($totals)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoRisk'), $size + 1);
            return;
        }

        uasort($totals, function ($first, $second) {
            return $second['sum'] <=> $first['sum'];
        });

        $width     = $this->contentWidth($pdf);
        $chartW    = $width * 0.68;
        $rightX    = $this->marge_gauche + $chartW + 10;
        $rightW    = $width - $chartW - 10;
        $y         = $pdf->GetY();
        $bars      = [];
        $topCount  = 0;
        $topSum    = 0;
        $grandSum  = 0;

        foreach ($totals as $elementId => $total) {
            $grandSum += $total['sum'];
            if (count($bars) >= self::MAX_EXPOSED_UNITS) {
                continue;
            }
            $bars[] = [
                'label' => dol_trunc($this->elementLabel($stats['elements'][$elementId], false), 40) . '  (' . $total['count'] . ')',
                'value' => $total['sum'],
                'color' => count($bars) < 3 ? $this->accentWarn : $this->accent
            ];
        }

        // Le premier tiers des unites : c'est la que le mode d'emploi conseille de commencer
        $third = max(1, (int) ceil(count($totals) / 3));
        foreach (array_slice($totals, 0, $third, true) as $total) {
            $topSum   += $total['sum'];
            $topCount += 1;
        }

        // barChart() prend toute la largeur utile : la marge droite est elargie le temps du trace
        $marginRight         = $this->marge_droite;
        $this->marge_droite  = $marginRight + $rightW + 10;
        $this->barChart($pdf, $bars, $chartW * 0.5, $size - 1, 11);
        $this->marge_droite  = $marginRight;

        $share = $grandSum > 0 ? round($topSum * 100 / $grandSum) : 0;
        $this->card($pdf, $rightX, $y, $rightW, 100, $this->tint($this->accentWarn, 0.92), 4);
        $this->kicker($pdf, $rightX + 8, $y + 9, $rightW - 16, $outputLangs->transnoentities('DigiriskCseExposedKicker'), $size, $this->accentWarn);
        $this->bigNumber($pdf, $rightX + 8, $y + 20, $rightW - 16, $share . ' %', $outputLangs->transnoentities('DigiriskCseExposedShare', $topCount, count($totals)), $size + 26, $size - 0.5, $this->accentWarn);

        $pdf->SetFont('', 'I', $size - 2);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->MultiCell($rightW - 16, $this->height, $outputLangs->transnoentities('DigiriskCseExposedHint'), 0, 'L', false, 1, $rightX + 8, $y + 72, true, 0, false, true, 0, 'T');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Diapositive des risques a traiter en priorite.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slidePriorityRisks($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlidePriorityRisks'), $outputLangs->transnoentities('DigiriskCseSlidePriorityRisksSubtitle'), $outputLangs, $size);

        $priorityRisks = $this->priorityRisks($stats);
        if (empty($priorityRisks)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoPriorityRisk'), $size + 1);
            return;
        }

        $risk     = new Risk($this->db);
        $elements = $stats['elements'];
        $rows     = [];

        foreach ($priorityRisks as $entry) {
            $riskLine = $entry['risk'];
            $level    = $entry['level'];
            $element  = $elements[$riskLine->fk_element] ?? null;

            $rows[] = [
                $this->cotationCell($riskLine->riskAssessmentCotation, $level),
                $this->pictoCell($this->categoryPictoPath($risk, $riskLine)),
                $element ? $this->elementLabel($element, false) : $outputLangs->transnoentities('NoData'),
                $this->riskDescription($riskLine, 110) ?: $this->categoryLabel($risk, (int) $riskLine->category),
                $this->riskMeasures((int) $riskLine->id, $stats['riskTasks'], $outputLangs, 2) ?: $outputLangs->transnoentities('DigiriskSynthesisNoMeasureYet')
            ];
        }

        $width  = $this->contentWidth($pdf);
        $widths = [$width * 0.08, $width * 0.06, $width * 0.20, $width * 0.33, $width * 0.33];
        $header = [
            ['text' => $outputLangs->transnoentities('RiskCotation'), 'align' => 'C'],
            '',
            $outputLangs->transnoentities('WorkUnit'),
            $outputLangs->transnoentities('Description'),
            $outputLangs->transnoentities('DigiriskSynthesisMeasures')
        ];

        $this->table($pdf, $header, $rows, $widths, $size - 1);

        $remaining = ((int) $stats['levelCounts'][3] + (int) $stats['levelCounts'][4]) - count($priorityRisks);
        if ($remaining > 0) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisMorePriorityRisks', $remaining), $size - 2, 'I', $this->inkMuted);
        }
    }

    /**
     * Les risques des deux niveaux les plus graves, le plus cote en premier, plafonnes.
     *
     * Les niveaux sont parcourus du plus grave au moins grave et non la liste des risques
     * triee par cotation : un risque noir peu cote doit passer devant un risque rouge tres
     * cote, c'est le niveau qui commande la priorite de traitement.
     *
     * @param  array $stats Chiffres du document unique
     * @return array        Entrees ['risk' => risque, 'level' => niveau]
     */
    protected function priorityRisks(array $stats): array
    {
        $priorityRisks = [];

        foreach ([4, 3] as $level) {
            foreach ($stats['riskLevels'][$level] ?? [] as $riskLine) {
                if (count($priorityRisks) >= self::MAX_PRIORITY_RISKS) {
                    return $priorityRisks;
                }
                $priorityRisks[] = ['risk' => $riskLine, 'level' => $level];
            }
        }

        return $priorityRisks;
    }

    /**
     * Diapositive des risques psychosociaux : qualification du perimetre et avancement.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slidePsychosocial($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $rps = $stats['rps'];

        $this->slide($pdf, 'results', $outputLangs->transnoentities('DigiriskCseSlidePsychosocial'), $outputLangs->transnoentities('DigiriskCseSlidePsychosocialSubtitle'), $outputLangs, $size);

        if (empty($rps['units'])) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoWorkUnit'), $size + 1);
            return;
        }

        $width     = $this->contentWidth($pdf);
        $leftWidth = $width * 0.5;
        $y         = $pdf->GetY();

        // A gauche, l'anneau de qualification des unites
        $radius = 36;
        $cx     = $this->marge_gauche + $radius + 4;
        $cy     = $y + $radius + 4;
        $this->donut($pdf, $cx, $cy, $radius, [
            ['value' => $rps['concerned'],    'color' => $this->categoryColors[7]],
            ['value' => $rps['notConcerned'], 'color' => $this->accentAlt],
            ['value' => $rps['toQualify'],    'color' => $this->levelBg[1]]
        ], (string) $rps['units'], $outputLangs->transnoentities('DigiriskSynthesisWorkUnitsCount'), $size + 2);

        $this->legend($pdf, $cx + $radius + 10, $y + 18, $leftWidth - (2 * $radius) - 18, [
            ['label' => $outputLangs->transnoentities('DigiriskCseRpsConcerned'),    'value' => $rps['concerned'],    'color' => $this->categoryColors[7]],
            ['label' => $outputLangs->transnoentities('DigiriskCseRpsNotConcerned'), 'value' => $rps['notConcerned'], 'color' => $this->accentAlt],
            ['label' => $outputLangs->transnoentities('DigiriskCseRpsToQualify'),    'value' => $rps['toQualify'],    'color' => $this->levelBg[1]]
        ], $size, 10);

        // A droite, l'avancement et la lecture
        $rightX = $this->marge_gauche + $leftWidth + 8;
        $rightW = $width - $leftWidth - 8;

        // Rapporte les unites evaluees a celles declarees concernees, et a toutes les unites
        // tant qu'aucune ne l'est : "2 / 2 sur celles declarees concernees" a cote d'une legende
        // qui annoncait zero unite concernee se contredisait
        if ($rps['concerned'] > 0) {
            $rpsRatio = $rps['assessedConcerned'] . ' / ' . $rps['concerned'];
            $rpsLabel = $outputLangs->transnoentities('DigiriskCseRpsAssessedLong');
        } else {
            $rpsRatio = $rps['assessed'] . ' / ' . $rps['units'];
            $rpsLabel = $outputLangs->transnoentities('DigiriskCseRpsAssessedOfUnits');
        }
        $this->bigNumber($pdf, $rightX, $y + 2, $rightW, $rpsRatio, $rpsLabel, $size + 22, $size, $this->categoryColors[7]);

        $pdf->SetXY($rightX, $y + 38);
        $this->bulletList($pdf, [
            $outputLangs->transnoentities('DigiriskCseRpsUnicity'),
            $rps['toQualify'] > 0 ? $outputLangs->transnoentities('DigiriskCseRpsToQualifyAlert', $rps['toQualify']) : $outputLangs->transnoentities('DigiriskCseRpsFullyQualified')
        ], $size - 1, $rightW, $rightX);
    }

    /**
     * Diapositive du plan d'actions.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideActionPlan($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $actionPlan = $stats['actionPlan'];

        $this->slide($pdf, 'actions', $outputLangs->transnoentities('DigiriskCseSlideActionPlan'), $outputLangs->transnoentities('DigiriskCseSlideActionPlanSubtitle'), $outputLangs, $size);

        if (empty($actionPlan['total'])) {
            $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskSynthesisNoAction'), $size + 1);
            return;
        }

        $width     = $this->contentWidth($pdf);
        $leftWidth = $width * 0.3;
        $y         = $pdf->GetY();

        // A gauche, l'avancement en anneau : soldees, en cours a l'heure, en retard
        $radius    = 34;
        $cx        = $this->marge_gauche + $radius + 6;
        $cy        = $y + $radius + 2;
        $onTime    = max(0, $actionPlan['ongoing'] - $actionPlan['late']);
        $doneShare = round($actionPlan['done'] * 100 / $actionPlan['total']) . ' %';

        $this->donut($pdf, $cx, $cy, $radius, [
            ['value' => $actionPlan['done'], 'color' => $this->accentAlt],
            ['value' => $onTime,             'color' => $this->levelBg[2]],
            ['value' => $actionPlan['late'], 'color' => $this->accentWarn]
        ], $doneShare, $outputLangs->transnoentities('DigiriskSynthesisActionsDone'), $size + 2);

        $this->legend($pdf, $this->marge_gauche, $cy + $radius + 6, $leftWidth, [
            ['label' => $outputLangs->transnoentities('DigiriskSynthesisActionsDone'),    'value' => $actionPlan['done'], 'color' => $this->accentAlt],
            ['label' => $outputLangs->transnoentities('DigiriskCseActionsOnTime'),        'value' => $onTime,             'color' => $this->levelBg[2]],
            ['label' => $outputLangs->transnoentities('DigiriskSynthesisActionsLate'),    'value' => $actionPlan['late'], 'color' => $this->accentWarn]
        ], $size - 1, 8);

        // A droite, les prochaines echeances
        $rightX = $this->marge_gauche + $leftWidth + 12;
        $rightW = $width - $leftWidth - 12;

        $this->kicker($pdf, $rightX, $y, $rightW, $outputLangs->transnoentities('DigiriskSynthesisNextDeadlines'), $size, $this->accent);

        if (empty($actionPlan['next'])) {
            $pdf->SetFont('', '', $size);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($rightX, $y + 9);
            $pdf->Cell($rightW, 6, $outputLangs->transnoentities('DigiriskCseNoDeadline'), 0, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            return;
        }

        $now  = dol_now();
        $rows = [];
        foreach (array_slice($actionPlan['next'], 0, self::MAX_NEXT_DEADLINES) as $task) {
            $isLate = $task->datee < $now;

            $rows[] = [
                ['text' => dol_print_date($task->datee, 'day', 'tzuser', $outputLangs), 'align' => 'C', 'bold' => $isLate, 'color' => $isLate ? $this->accentWarn : $this->ink],
                dol_trunc($task->label, 90),
                ['text' => ((int) $task->progress) . ' %', 'align' => 'C']
            ];
        }

        $marginLeft         = $this->marge_gauche;
        $this->marge_gauche = $rightX;
        $pdf->SetXY($rightX, $y + 8);
        $this->table($pdf, [
            ['text' => $outputLangs->transnoentities('DigiriskSynthesisDeadline'), 'align' => 'C'],
            $outputLangs->transnoentities('Label'),
            ['text' => $outputLangs->transnoentities('DigiriskProgress'), 'align' => 'C']
        ], $rows, [$rightW * 0.18, $rightW * 0.67, $rightW * 0.15], $size - 1);
        $this->marge_gauche = $marginLeft;
    }

    /**
     * Diapositive des accidents du travail.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideAccidents($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'actions', $outputLangs->transnoentities('DigiriskCseSlideAccidents'), $outputLangs->transnoentities('DigiriskCseSlideAccidentsSubtitle'), $outputLangs, $size);

        $width     = $this->contentWidth($pdf);
        $leftWidth = $width * 0.42;
        $y         = $pdf->GetY();

        $this->card($pdf, $this->marge_gauche, $y, $leftWidth, 100, $this->tint($this->accentWarn, 0.92), 4);
        $this->bigNumber($pdf, $this->marge_gauche + 10, $y + 12, $leftWidth - 20, (string) $stats['accidents']['lastYear'], $outputLangs->transnoentities('DigiriskCseAccidentsLastYearLong'), $size + 44, $size + 1, $this->accentWarn);

        $pdf->SetDrawColor($this->tint($this->accentWarn, 0.6)[0], $this->tint($this->accentWarn, 0.6)[1], $this->tint($this->accentWarn, 0.6)[2]);
        $pdf->Line($this->marge_gauche + 10, $y + 68, $this->marge_gauche + $leftWidth - 10, $y + 68);
        $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);

        $this->bigNumber($pdf, $this->marge_gauche + 10, $y + 73, $leftWidth - 20, (string) $stats['accidents']['total'], $outputLangs->transnoentities('DigiriskCseAccidentsTotalLong'), $size + 10, $size - 1, $this->ink);

        $rightX = $this->marge_gauche + $leftWidth + 14;
        $pdf->SetXY($rightX, $y + 4);
        $this->bulletList($pdf, [
            $outputLangs->transnoentities('DigiriskCseAccidentsFeedback'),
            $outputLangs->transnoentities('DigiriskCseAccidentsInvestigation'),
            $outputLangs->transnoentities('DigiriskCseAccidentsRegister')
        ], $size, $width - $leftWidth - 14, $rightX);
    }

    /**
     * Ce qui est attendu du comite : une carte numerotee par demande.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideAskCse($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'next', $outputLangs->transnoentities('DigiriskCseSlideAsk'), $outputLangs->transnoentities('DigiriskCseSlideAskSubtitle'), $outputLangs, $size);

        // Le comite ne se prononce pas sur zero risque prioritaire ni sur zero action en retard :
        // ces demandes ne sont posees que lorsqu'elles portent sur quelque chose
        $priority = (int) $stats['levelCounts'][3] + (int) $stats['levelCounts'][4];
        $asks     = [$outputLangs->transnoentities('DigiriskCseAskOpinion')];
        if ($priority > 0) {
            $asks[] = $outputLangs->transnoentities('DigiriskCseAskPriorities', $priority);
        }
        if ($stats['actionPlan']['late'] > 0) {
            $asks[] = $outputLangs->transnoentities('DigiriskCseAskLateActions', $stats['actionPlan']['late']);
        }
        $asks[] = $outputLangs->transnoentities('DigiriskCseAskRps');
        $asks[] = $outputLangs->transnoentities('DigiriskCseAskNextUpdate');

        $gap    = 5;
        $count  = count($asks);
        $width  = ($this->contentWidth($pdf) - (($count - 1) * $gap)) / $count;
        $height = 100;
        $y      = $pdf->GetY() + 2;
        $x      = $this->marge_gauche;

        foreach ($asks as $index => $ask) {
            $color = $index === 0 ? $this->accent : $this->tint($this->accent, 0.15 * $index);
            $this->card($pdf, $x, $y, $width, $height, $index === 0 ? $this->accent : $this->surface, 4);

            $pdf->SetFont('', 'B', $size + 20);
            $pdf->SetTextColor(...($index === 0 ? [255, 255, 255] : $color));
            $pdf->SetXY($x + 7, $y + 8);
            $pdf->Cell($width - 14, 16, sprintf('%02d', $index + 1), 0, 0, 'L');

            $pdf->SetFont('', 'B', $size);
            $pdf->SetTextColor(...($index === 0 ? [255, 255, 255] : $this->ink));
            $pdf->MultiCell($width - 14, $this->height + 1, $ask, 0, 'L', false, 1, $x + 7, $y + 34, true, 0, false, true, 0, 'T');

            $x += $width + $gap;
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Les prochaines etapes sur une frise : de l'avis du comite a la prochaine mise a jour.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $stats       Chiffres du document unique
     * @return void
     */
    protected function slideRoadmap($pdf, Translate $outputLangs, float $size, array $stats)
    {
        $this->slide($pdf, 'next', $outputLangs->transnoentities('DigiriskCseSlideRoadmap'), $outputLangs->transnoentities('DigiriskCseSlideRoadmapSubtitle'), $outputLangs, $size);

        $now   = dol_now();
        $steps = [
            [$now,                                   'DigiriskCseRoadmapStep1', 'DigiriskCseRoadmapStep1Text', $this->accent],
            [dol_time_plus_duree($now, 3, 'm'),      'DigiriskCseRoadmapStep2', 'DigiriskCseRoadmapStep2Text', $this->accentAlt],
            [dol_time_plus_duree($now, 6, 'm'),      'DigiriskCseRoadmapStep3', 'DigiriskCseRoadmapStep3Text', $this->levelBg[2]],
            [dol_time_plus_duree($now, 1, 'y'),      'DigiriskCseRoadmapStep4', 'DigiriskCseRoadmapStep4Text', $this->categoryColors[7]]
        ];

        $width  = $this->contentWidth($pdf);
        $count  = count($steps);
        $stepW  = $width / $count;
        $y      = $pdf->GetY() + 8;
        $lineY  = $y + 16;

        // La frise : un rail continu, un jalon par etape
        $this->card($pdf, $this->marge_gauche, $lineY - 0.8, $width, 1.6, $this->rule, 0.8);

        foreach ($steps as $index => $step) {
            $x     = $this->marge_gauche + ($index * $stepW);
            $color = $step[3];

            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetXY($x, $y);
            $pdf->Cell($stepW - 8, 6, dol_strtoupper(dol_print_date($step[0], '%B %Y', 'tzuser', $outputLangs)), 0, 0, 'L');

            $pdf->SetFillColor(255, 255, 255);
            $pdf->Circle($x + 5, $lineY, 5, 0, 360, 'F');
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->Circle($x + 5, $lineY, 3.6, 0, 360, 'F');

            $this->card($pdf, $x, $lineY + 10, $stepW - 8, 62, $this->surface, 4);
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->RoundedRect($x, $lineY + 10, $stepW - 8, 2.4, 1.2, '1100', 'F');

            $pdf->SetFont('', 'B', $size + 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->MultiCell($stepW - 22, 6, $outputLangs->transnoentities($step[1]), 0, 'L', false, 1, $x + 7, $lineY + 18, true, 0, false, true, 0, 'T');

            $pdf->SetFont('', '', $size - 1.5);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($stepW - 22, $this->height, $outputLangs->transnoentities($step[2]), 0, 'L', false, 1, $x + 7, $pdf->GetY() + 2, true, 0, false, true, 0, 'T');
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Diapositive de cloture.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function slideClosing($pdf, $object, Translate $outputLangs, float $size)
    {
        global $mysoc;

        $this->newPage($pdf);
        $pdf->SetAutoPageBreak(false);
        $this->deepBackground($pdf);

        $light = $this->tint($this->accent, 0.55);

        $pdf->SetFont('', 'B', $size + 46);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($this->marge_gauche + 10, 52);
        $pdf->Cell(200, 30, $outputLangs->transnoentities('DigiriskCseThanks'), 0, 0, 'L');

        $pdf->SetFont('', '', $size + 6);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetXY($this->marge_gauche + 10, 88);
        $pdf->Cell(200, 10, $outputLangs->transnoentities('DigiriskCseThanksHint'), 0, 0, 'L');

        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect($this->marge_gauche + 11, 108, 30, 1.6, 0.8, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 3);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($this->marge_gauche + 10, 116);
        $pdf->Cell(200, 8, $mysoc->name, 0, 1, 'L');

        $contact = array_filter([$mysoc->address, trim($mysoc->zip . ' ' . $mysoc->town), $mysoc->phone, $mysoc->email]);
        if (!empty($contact)) {
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($light[0], $light[1], $light[2]);
            $pdf->SetX($this->marge_gauche + 10);
            $pdf->Cell(260, 6, implode('  -  ', $contact), 0, 1, 'L');
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }
}
