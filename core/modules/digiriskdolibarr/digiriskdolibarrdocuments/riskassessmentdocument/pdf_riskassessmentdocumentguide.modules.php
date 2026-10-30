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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/riskassessmentdocument/pdf_riskassessmentdocumentguide.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Mode d'emploi du document unique, en PDF natif A4 portrait.
 *
 *          Le document unique ne porte plus que les resultats de l'evaluation : tout ce qui
 *          l'explique - le cadre reglementaire, les notions de danger et de risque, les methodes
 *          de cotation, le referentiel des familles de danger, la lecture des tableaux et le
 *          programme de prevention - est ici, dans un document a part que l'on remet avec lui.
 *
 *          Le texte est fixe, seuls la societe et le referentiel des familles de danger viennent
 *          de la base : le guide reste juste meme quand l'evaluation n'est pas commencee.
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrpdfdocument.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

/**
 * Classe de generation du mode d'emploi du document unique.
 */
class pdf_riskassessmentdocumentguide extends ModelePDFDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'riskassessmentdocument';

    /**
     * Hauteur du bandeau de couverture, en mm.
     */
    const COVER_BAND = 132;

    /**
     * Place restante, en mm, en dessous de laquelle un chapitre s'ouvre sur une nouvelle page.
     */
    const CHAPTER_MIN_SPACE = 150;

    /**
     * @var array Page de debut de chaque chapitre, pour le sommaire de couverture
     */
    protected array $chapterPages = [];

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

        $this->name        = 'riskassessmentdocumentguide';
        $this->description = $langs->trans('DigiriskGuidePDFDescription');

        // A4 portrait : le guide s'imprime et se remet avec le document unique
        $this->orientation  = 'P';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format       = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche = 16;
        $this->marge_droite = 16;
        $this->marge_haute  = 16;
        $this->marge_basse  = 14;

        // La couverture ne se numerote pas
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
        $moreParam['documentName']     = 'ModeEmploi_';
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

        $size = pdf_getPDFFontSize($outputLangs);
        $pdf  = $this->openPdf($object, $outputLangs, $outputLangs->transnoentities('DigiriskGuideTitle'));

        $this->footerText = $outputLangs->transnoentities('DigiriskGuideTitle') . ' - ' . $mysoc->name;

        // La couverture est deja ouverte par openPdf(). Son sommaire porte les numeros de page
        // des chapitres : il s'ecrit en dernier, une fois ces pages connues
        $this->cover($pdf, $object, $outputLangs, $size);

        $this->chapterLegal($pdf, $outputLangs, $size);
        $this->chapterNotions($pdf, $outputLangs, $size);
        $this->chapterCotation($pdf, $outputLangs, $size);
        $this->chapterCategories($pdf, $outputLangs, $size);
        $this->chapterReading($pdf, $outputLangs, $size);
        $this->chapterPrevention($pdf, $outputLangs, $size);

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
     * Les six chapitres du guide : cle du titre et cle de l'accroche du sommaire.
     *
     * @return array Chapitres, numerotes a partir de 1
     */
    protected function chapters(): array
    {
        return [
            1 => ['DigiriskGuideChapterLegal',      'DigiriskGuideChapterLegalHint'],
            2 => ['DigiriskGuideChapterNotions',    'DigiriskGuideChapterNotionsHint'],
            3 => ['DigiriskGuideChapterCotation',   'DigiriskGuideChapterCotationHint'],
            4 => ['DigiriskGuideChapterCategories', 'DigiriskGuideChapterCategoriesHint'],
            5 => ['DigiriskGuideChapterReading',    'DigiriskGuideChapterReadingHint'],
            6 => ['DigiriskGuideChapterPrevention', 'DigiriskGuideChapterPreventionHint']
        ];
    }

    /**
     * Couverture : bandeau de titre plein, puis l'objet du guide.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function cover($pdf, $object, Translate $outputLangs, float $size)
    {
        global $mysoc;

        $pageWidth = $pdf->GetPageWidth();
        $width     = $this->contentWidth($pdf);
        $white     = [255, 255, 255];
        $light     = $this->tint($this->accent, 0.62);

        // Tout se pose a coordonnees imposees : un texte ecrit sous le seuil de rupture
        // ouvrirait une page de plus
        $pdf->SetAutoPageBreak(false);

        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->Rect(0, 0, $pageWidth, self::COVER_BAND, 'F');

        // Deux disques clairs en transparence, en haut a droite : ils donnent de la profondeur
        // au bandeau sans porter d'information
        $pdf->SetAlpha(0.10);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($pageWidth - 18, 24, 58, 0, 360, 'F');
        $pdf->Circle($pageWidth - 62, 104, 30, 0, 360, 'F');
        $pdf->SetAlpha(1);

        // Le logo, sur une carte blanche : pose directement sur le bleu, un logo a fond blanc
        // ou sombre ne se lirait pas
        // ou sombre ne se lirait pas
        $logo = $this->companyLogoPath($object);
        if (!empty($logo)) {
            $logoWidth = $this->companyLogoSize($logo, 13, 60)[0];
            $this->card($pdf, $this->marge_gauche, 14, $logoWidth + 8, 19, $white, 3);
            $this->companyLogo($pdf, $object, $this->marge_gauche + 4, 17, 13, 60);
        }

        $this->kicker($pdf, $this->marge_gauche, 46, $width, $outputLangs->transnoentities('DigiriskGuideKicker'), $size + 1.5, $light);

        $pdf->SetFont('', 'B', $size + 28);
        $pdf->SetTextColor($white[0], $white[1], $white[2]);
        $pdf->SetXY($this->marge_gauche, 53);
        $pdf->Cell($width, 16, $outputLangs->transnoentities('DigiriskGuideHeadline'), 0, 1, 'L');

        $pdf->SetFont('', '', $size + 5);
        $pdf->SetXY($this->marge_gauche, 71);
        $pdf->MultiCell($width * 0.75, 7, $outputLangs->transnoentities('DigiriskGuideSubHeadline'), 0, 'L');

        $pdf->SetFillColor($white[0], $white[1], $white[2]);
        $pdf->RoundedRect($this->marge_gauche, $pdf->GetY() + 6, 26, 1.6, 0.8, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 4);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 11);
        $pdf->Cell($width, 8, $mysoc->name, 0, 1, 'L');

        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($light[0], $light[1], $light[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($width, 6, $outputLangs->transnoentities('DigiriskSynthesisGeneratedAt') . ' ' . dol_print_date(dol_now(), 'day', 'tzuser', $outputLangs), 0, 1, 'L');

        // Sous le bandeau : a quoi sert le guide, en regard de ce que contient le DU
        $y         = self::COVER_BAND + 12;
        $half      = ($width - 8) / 2;
        $leftLines = [
            $outputLangs->transnoentities('DigiriskGuideCoverDuHolds1'),
            $outputLangs->transnoentities('DigiriskGuideCoverDuHolds2'),
            $outputLangs->transnoentities('DigiriskGuideCoverDuHolds3')
        ];
        $rightLines = [
            $outputLangs->transnoentities('DigiriskGuideCoverGuideHolds1'),
            $outputLangs->transnoentities('DigiriskGuideCoverGuideHolds2'),
            $outputLangs->transnoentities('DigiriskGuideCoverGuideHolds3')
        ];

        $bottomLeft  = $this->calloutBox($pdf, $this->marge_gauche, $y, $half, $outputLangs->transnoentities('DigiriskGuideCoverDuTitle'), $leftLines, $size, $this->ink);
        $bottomRight = $this->calloutBox($pdf, $this->marge_gauche + $half + 8, $y, $half, $outputLangs->transnoentities('DigiriskGuideCoverGuideTitle'), $rightLines, $size, $this->accent);

        // Le sommaire s'ecrit sous les deux encadres, a la fin de la generation
        $this->coverSummaryY = max($bottomLeft, $bottomRight) + 12;

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Sommaire de la couverture, ecrit une fois les pages des chapitres connues.
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

        $this->kicker($pdf, $this->marge_gauche, $y, $width, $outputLangs->transnoentities('DigiriskGuideInThisGuide'), $size + 1, $this->accent);
        $y += 8;

        // Deux colonnes de trois chapitres : sur une seule, le sommaire descendait jusqu'au logo
        $columnWidth = ($width - 8) / 2;
        $rowHeight   = 17;

        foreach ($this->chapters() as $number => $keys) {
            $column = (int) (($number - 1) / 3);
            $x      = $this->marge_gauche + ($column * ($columnWidth + 8));
            $rowY   = $y + ((($number - 1) % 3) * $rowHeight);

            $pdf->SetFont('', 'B', $size + 9);
            $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->SetXY($x, $rowY);
            $pdf->Cell(13, 9, sprintf('%02d', $number), 0, 0, 'L');

            $pdf->SetFont('', 'B', $size);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x + 14, $rowY + 0.6);
            $pdf->Cell($columnWidth - 24, 5, $outputLangs->transnoentities($keys[0]), 0, 0, 'L');

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($x + 14, $rowY + 5.6);
            $pdf->MultiCell($columnWidth - 24, 4, $outputLangs->transnoentities($keys[1]), 0, 'L');

            if (!empty($this->chapterPages[$number])) {
                $pdf->SetFont('', 'B', $size - 1);
                $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->SetXY($x + $columnWidth - 10, $rowY + 0.6);
                $pdf->Cell(10, 5, 'p. ' . $this->chapterPages[$number], 0, 0, 'R');
            }
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setPage($currentPage);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Ouvre un chapitre sur une nouvelle page : grand numero, titre, accroche.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $number      Numero du chapitre
     * @param  Translate $outputLangs Lang object
     * @param  string    $leadKey     Cle de l'accroche
     * @param  float     $size        Taille de police
     * @param  float     $minSpace    Place a trouver sur la page courante pour y ouvrir le chapitre
     * @return void
     */
    protected function chapter($pdf, int $number, Translate $outputLangs, string $leadKey, float $size, float $minSpace = self::CHAPTER_MIN_SPACE)
    {
        $chapters = $this->chapters();

        // Un chapitre ne s'ouvre sur une nouvelle page que si la page courante n'a plus la place
        // pour son en-tete et son premier bloc : sinon chaque fin de chapitre laisse une demi-page
        // blanche, et le guide gagne autant de pages sans contenu de plus
        $available = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY();
        if ($pdf->getPage() <= 1 || $available < $minSpace) {
            $this->newPage($pdf);
        } else {
            $pdf->Ln(12);
        }
        $this->chapterPages[$number] = $pdf->getPage();

        $width = $this->contentWidth($pdf);
        $y     = $pdf->GetY();

        $pdf->SetFont('', 'B', $size + 34);
        $pdf->SetTextColor(...$this->tint($this->accent, 0.8));
        $pdf->SetXY($this->marge_gauche - 1, $y - 2);
        $pdf->Cell(40, 20, sprintf('%02d', $number), 0, 0, 'L');

        $this->kicker($pdf, $this->marge_gauche + 38, $y + 2, $width - 38, $outputLangs->transnoentities('DigiriskGuideChapterKicker', (string) $number), $size + 1, $this->accent);

        $pdf->SetFont('', 'B', $size + 10);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($this->marge_gauche + 38, $y + 7.5);
        $pdf->MultiCell($width - 38, 9, $outputLangs->transnoentities($chapters[$number][0]), 0, 'L');

        $pdf->SetY(max($pdf->GetY(), $y + 20) + 3);
        $this->hairline($pdf, $pdf->GetY(), 0.4, $this->accent);
        $pdf->SetY($pdf->GetY() + 5);

        $pdf->SetFont('', '', $size + 0.5);
        $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($width, 5.2, $outputLangs->transnoentities($leadKey), 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
    }

    /**
     * Intertitre d'un chapitre.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  string $title  Titre
     * @param  float  $size   Taille de police
     * @param  float  $needed Hauteur du bloc qui suit, pour ne pas laisser le titre seul en bas de page
     * @return void
     */
    protected function subTitle($pdf, string $title, float $size, float $needed = 40)
    {
        $this->sectionTitle($pdf, $title, $size, $needed);
        $pdf->Ln(1);
    }

    /**
     * Pastilles en ligne, renvoyees a la ligne quand la largeur est pleine.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $items Libelles
     * @param  float $size  Taille de police
     * @param  array $color Couleur de reference, les pastilles en prennent une teinte claire
     * @return void
     */
    protected function chips($pdf, array $items, float $size, array $color = [])
    {
        $color  = $color ?: $this->accent;
        $fill   = $this->tint($color, 0.88);
        $height = 7.5;
        $gap    = 2.5;
        $right  = $pdf->GetPageWidth() - $this->marge_droite;

        $pdf->SetFont('', 'B', $size - 1.5);
        $lines = 1;
        $x     = $this->marge_gauche;
        foreach ($items as $item) {
            $chipWidth = $pdf->GetStringWidth($item) + 8;
            if ($x + $chipWidth > $right) {
                $lines++;
                $x = $this->marge_gauche;
            }
            $x += $chipWidth + $gap;
        }
        $this->reserveSpace($pdf, ($lines * ($height + $gap)) + 3);

        $x = $this->marge_gauche;
        $y = $pdf->GetY();
        foreach ($items as $item) {
            $pdf->SetFont('', 'B', $size - 1.5);
            $chipWidth = $pdf->GetStringWidth($item) + 8;
            if ($x + $chipWidth > $right) {
                $x  = $this->marge_gauche;
                $y += $height + $gap;
            }
            $this->pill($pdf, $x, $y, $chipWidth, $height, $item, $fill, $color, $size - 1.5);
            $x += $chipWidth + $gap;
        }

        $pdf->SetXY($this->marge_gauche, $y + $height + 5);
    }

    /**
     * Echelle des quatre niveaux de cotation : un bloc de couleur par niveau, sa plage de
     * cotation et ce qu'il signifie.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function levelScale($pdf, Translate $outputLangs, float $size)
    {
        $levels = [
            1 => ['0 - 47',   'DigiriskGuideLevelLow'],
            2 => ['48 - 50',  'DigiriskGuideLevelPlan'],
            3 => ['51 - 79',  'DigiriskGuideLevelTreat'],
            4 => ['80 - 100', 'DigiriskGuideLevelUnacceptable']
        ];

        $gap   = 3;
        $width = ($this->contentWidth($pdf) - (3 * $gap)) / 4;

        $pdf->SetFont('', '', $size - 1);
        $textHeight = 0;
        foreach ($levels as $level) {
            $textHeight = max($textHeight, $pdf->getNumLines($outputLangs->transnoentities($level[1]), $width - 4) * self::LINE);
        }

        $blockHeight = 24;
        $this->reserveSpace($pdf, $blockHeight + $textHeight + 10);

        $y = $pdf->GetY();
        $x = $this->marge_gauche;
        foreach ($levels as $level => $data) {
            $fill = $this->levelBg[$level];
            $ink  = $this->levelText[$level];

            $this->card($pdf, $x, $y, $width, $blockHeight, $fill, 3);

            $pdf->SetFont('', 'B', $size + 7);
            $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
            $pdf->SetXY($x + 4, $y + 3);
            $pdf->Cell($width - 8, 9, $data[0], 0, 0, 'L');

            $pdf->SetFont('', 'B', $size - 0.5);
            $pdf->SetXY($x + 4, $y + 14);
            $pdf->Cell($width - 8, 6, $outputLangs->transnoentities($this->levelLabelKey($level)), 0, 0, 'L');

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($width - 2, self::LINE, $outputLangs->transnoentities($data[1]), 0, 'L', false, 1, $x + 1, $y + $blockHeight + 2.5, true, 0, false, true, 0, 'T');

            $x += $width + $gap;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $y + $blockHeight + $textHeight + 7);
    }

    /**
     * Formule de la methode avancee : un bloc par critere, relies par le signe de la
     * multiplication, et le resultat au bout.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function formula($pdf, Translate $outputLangs, float $size)
    {
        $criteria = [
            ['G', 'DigiriskGuideCriterionGravity',    'DigiriskGuideCriterionGravityHint',    $this->categoryColors[0]],
            ['E', 'DigiriskGuideCriterionExposure',   'DigiriskGuideCriterionExposureHint',   $this->categoryColors[1]],
            ['P', 'DigiriskGuideCriterionOccurrence', 'DigiriskGuideCriterionOccurrenceHint', $this->categoryColors[3]],
            ['F', 'DigiriskGuideCriterionTraining',   'DigiriskGuideCriterionTrainingHint',   $this->categoryColors[5]],
            ['P', 'DigiriskGuideCriterionProtection', 'DigiriskGuideCriterionProtectionHint', $this->categoryColors[7]]
        ];

        $signWidth = 5;
        $count     = count($criteria) + 1;
        $boxWidth  = ($this->contentWidth($pdf) - (($count - 1) * $signWidth)) / $count;

        $pdf->SetFont('', '', $size - 2);
        $hintHeight = 0;
        foreach ($criteria as $criterion) {
            $hintHeight = max($hintHeight, $pdf->getNumLines($outputLangs->transnoentities($criterion[2]), $boxWidth - 4) * 3.9);
        }
        $boxHeight = 22 + $hintHeight + 3;

        $this->reserveSpace($pdf, $boxHeight + 6);

        $y = $pdf->GetY();
        $x = $this->marge_gauche;
        foreach ($criteria as $index => $criterion) {
            $color = $criterion[3];
            $this->card($pdf, $x, $y, $boxWidth, $boxHeight, $this->tint($color, 0.88), 3);

            $pdf->SetFont('', 'B', $size + 12);
            $pdf->SetTextColor($color[0], $color[1], $color[2]);
            $pdf->SetXY($x, $y + 2);
            $pdf->Cell($boxWidth, 12, $criterion[0], 0, 0, 'C');

            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x, $y + 14);
            $pdf->Cell($boxWidth, 5, $outputLangs->transnoentities($criterion[1]), 0, 0, 'C');

            $pdf->SetFont('', '', $size - 2);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($boxWidth - 4, 3.9, $outputLangs->transnoentities($criterion[2]), 0, 'C', false, 1, $x + 2, $y + 20.5, true, 0, false, true, 0, 'T');

            $x += $boxWidth;

            $pdf->SetFont('', 'B', $size + 6);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($x, $y + ($boxHeight / 2) - 5);
            $pdf->Cell($signWidth, 10, $index < count($criteria) - 1 ? 'x' : '=', 0, 0, 'C');

            $x += $signWidth;
        }

        // Le resultat : pose sur la couleur d'accent, c'est lui que la grille transcrit ensuite
        $this->card($pdf, $x, $y, $boxWidth, $boxHeight, $this->ink, 3);
        $pdf->SetFont('', 'B', $size + 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($boxWidth, 12, 'R', 0, 0, 'C');
        $pdf->SetFont('', 'B', $size - 1);
        $pdf->SetXY($x, $y + 14);
        $pdf->Cell($boxWidth, 5, $outputLangs->transnoentities('DigiriskGuideCriterionResult'), 0, 0, 'C');
        $pdf->SetFont('', '', $size - 2);
        $pdf->MultiCell($boxWidth - 4, 3.9, $outputLangs->transnoentities('DigiriskGuideCriterionResultHint'), 0, 'C', false, 1, $x + 2, $y + 20.5, true, 0, false, true, 0, 'T');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $y + $boxHeight + 6);
    }

    /**
     * Schema danger / risque : deux disques qui se recouvrent, le risque a leur intersection.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  float     $x           Abscisse du coin haut gauche du schema
     * @param  float     $y           Ordonnee du coin haut gauche du schema
     * @param  float     $width       Largeur disponible
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return float                  Hauteur du schema
     */
    protected function venn($pdf, float $x, float $y, float $width, Translate $outputLangs, float $size): float
    {
        $radius = min(24, $width / 3.4);
        $cy     = $y + $radius + 2;
        $cx1    = $x + ($width / 2) - ($radius * 0.62);
        $cx2    = $x + ($width / 2) + ($radius * 0.62);

        $pdf->SetAlpha(0.22);
        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->Circle($cx1, $cy, $radius, 0, 360, 'F');
        $pdf->SetFillColor($this->accentWarn[0], $this->accentWarn[1], $this->accentWarn[2]);
        $pdf->Circle($cx2, $cy, $radius, 0, 360, 'F');
        $pdf->SetAlpha(1);

        $pdf->SetFont('', 'B', $size);
        $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetXY($cx1 - $radius, $cy - 3);
        $pdf->Cell($radius * 0.95, 6, $outputLangs->transnoentities('DigiriskGuideVennPerson'), 0, 0, 'C');

        $pdf->SetTextColor($this->accentWarn[0], $this->accentWarn[1], $this->accentWarn[2]);
        $pdf->SetXY($cx2 + ($radius * 0.05), $cy - 3);
        $pdf->Cell($radius * 0.95, 6, $outputLangs->transnoentities('DigiriskGuideVennDanger'), 0, 0, 'C');

        $this->pill($pdf, $x + ($width / 2) - 11, $cy - 4, 22, 8, $outputLangs->transnoentities('DigiriskGuideVennRisk'), $this->ink, [255, 255, 255], $size - 0.5);

        $pdf->SetFont('', 'I', $size - 1.5);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->SetXY($x, $cy + $radius + 2);
        $pdf->MultiCell($width, 4.2, $outputLangs->transnoentities('DigiriskGuideVennCaption'), 0, 'C');

        $pdf->SetTextColor(0, 0, 0);

        return ($radius * 2) + 14;
    }

    /**
     * Enchainement d'etapes en blocs relies par une fleche.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $steps Etapes ['kicker' => string, 'title' => string, 'text' => string, 'color' => array]
     * @param  float $size  Taille de police
     * @return void
     */
    protected function flow($pdf, array $steps, float $size)
    {
        $arrow = 8;
        $count = count($steps);
        $width = ($this->contentWidth($pdf) - (($count - 1) * $arrow)) / $count;
        $inner = $width - (2 * self::CARD_PAD);

        $height = 0;
        foreach ($steps as $step) {
            $height = max($height, $this->cardHeight($pdf, $step, $inner, $size));
        }
        $this->reserveSpace($pdf, $height + 5);

        $y = $pdf->GetY();
        $x = $this->marge_gauche;
        foreach ($steps as $index => $step) {
            $this->drawCard($pdf, $step, $x, $y, $width, $height, $size, true);
            $x += $width;

            if ($index < $count - 1) {
                $midY = $y + ($height / 2);
                $pdf->SetFillColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
                $pdf->Polygon([$x + 2, $midY - 3, $x + $arrow - 2, $midY, $x + 2, $midY + 3], 'F');
            }
            $x += $arrow;
        }

        $pdf->SetXY($this->marge_gauche, $y + $height + 5);
    }

    /**
     * Liste d'etapes numerotees : un numero dans une pastille, un titre et un texte par ligne.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $steps Etapes ['title' => string, 'text' => string]
     * @param  float $size  Taille de police
     * @return void
     */
    protected function numberedSteps($pdf, array $steps, float $size)
    {
        $width = $this->contentWidth($pdf) - 16;

        foreach ($steps as $index => $step) {
            $pdf->SetFont('', '', $size - 1);
            $height = 6 + ($pdf->getNumLines($step['text'], $width) * self::LINE) + 4;
            $this->reserveSpace($pdf, $height);

            $y = $pdf->GetY();

            $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $pdf->Circle($this->marge_gauche + 5, $y + 4.5, 4.5, 0, 360, 'F');
            $pdf->SetFont('', 'B', $size);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($this->marge_gauche + 0.5, $y);
            $pdf->Cell(9, 9, (string) ($index + 1), 0, 0, 'C');

            // Filet vertical entre deux pastilles : la liste se lit comme un parcours
            if ($index < count($steps) - 1) {
                $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
                $pdf->SetLineWidth(0.6);
                $pdf->Line($this->marge_gauche + 5, $y + 10, $this->marge_gauche + 5, $y + $height);
                $pdf->SetLineWidth(0.2);
            }

            $pdf->SetFont('', 'B', $size + 0.5);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($this->marge_gauche + 16, $y + 1);
            $pdf->Cell($width, 5, $step['title'], 0, 1, 'L');

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetX($this->marge_gauche + 16);
            $pdf->MultiCell($width, self::LINE, $step['text'], 0, 'L');

            $pdf->SetY($y + $height);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Encadre pleine largeur, pose a la position courante.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $title Titre
     * @param  array  $lines Lignes, ou ['text' => string, 'sub' => true] en retrait
     * @param  float  $size  Taille de police
     * @param  array  $color Couleur
     * @return void
     */
    protected function callout($pdf, string $title, array $lines, float $size, array $color = [])
    {
        $width = $this->contentWidth($pdf);

        $pdf->SetFont('', '', $size - 1);
        $height = 13;
        foreach ($lines as $line) {
            $text    = is_array($line) ? (string) ($line['text'] ?? '') : (string) $line;
            $height += (max(1, $pdf->getNumLines($text, $width - 16)) * $this->height) + 1.5;
        }
        $this->reserveSpace($pdf, $height + 5);

        $bottom = $this->calloutBox($pdf, $this->marge_gauche, $pdf->GetY(), $width, $title, $lines, $size, $color);
        $pdf->SetXY($this->marge_gauche, $bottom + 5);
    }

    /**
     * Chapitre 1 : le cadre reglementaire.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterLegal($pdf, Translate $outputLangs, float $size)
    {
        $this->chapter($pdf, 1, $outputLangs, 'DigiriskGuideLegalLead', $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideLegalCodeTitle'), $size, 85);
        $this->cardGrid($pdf, [
            ['kicker' => 'Art. R. 4121-1', 'title' => $outputLangs->transnoentities('DigiriskGuideLegalTranscribe'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalTranscribeText')],
            ['kicker' => 'Art. R. 4121-2', 'title' => $outputLangs->transnoentities('DigiriskGuideLegalUpdate'), 'lines' => [
                $outputLangs->transnoentities('DigiriskGuideLegalUpdate1'),
                $outputLangs->transnoentities('DigiriskGuideLegalUpdate2'),
                $outputLangs->transnoentities('DigiriskGuideLegalUpdate3')
            ]],
            ['kicker' => 'Art. R. 4121-3', 'title' => $outputLangs->transnoentities('DigiriskGuideLegalCse'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalCseText'), 'color' => $this->accentAlt],
            ['kicker' => 'Art. R. 4121-4', 'title' => $outputLangs->transnoentities('DigiriskGuideLegalAccess'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalAccessText'), 'color' => $this->accentAlt]
        ], 2, $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideLegalWhoTitle'), $size, 30);
        $this->chips($pdf, [
            $outputLangs->transnoentities('DigiriskGuideLegalWho1'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho2'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho3'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho4'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho5'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho6'),
            $outputLangs->transnoentities('DigiriskGuideLegalWho7')
        ], $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideLegalTextsTitle'), $size, 95);
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideLegalCircularKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideLegalCircular'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalCircularText')],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideLegalLawKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideLegalLaw'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalLawText')],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideLegalHardshipKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideLegalHardship'), 'text' => $outputLangs->transnoentities('DigiriskGuideLegalHardshipText')]
        ], 3, $size);

        $this->callout($pdf, $outputLangs->transnoentities('DigiriskGuideLegalSanctions'), [
            $outputLangs->transnoentities('DigiriskGuideLegalSanctions1'),
            $outputLangs->transnoentities('DigiriskGuideLegalSanctions2')
        ], $size, $this->accentWarn);
    }

    /**
     * Chapitre 2 : les notions cles.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterNotions($pdf, Translate $outputLangs, float $size)
    {
        $this->chapter($pdf, 2, $outputLangs, 'DigiriskGuideNotionsLead', $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideNotionsDangerRisk'), $size, 70);

        // Le schema a gauche, les deux definitions a droite
        $width     = $this->contentWidth($pdf);
        $leftWidth = $width * 0.42;
        $rightX    = $this->marge_gauche + $leftWidth + 6;
        $rightW    = $width - $leftWidth - 6;
        $y         = $pdf->GetY();

        $vennHeight = $this->venn($pdf, $this->marge_gauche, $y, $leftWidth, $outputLangs, $size);

        $definitions = [
            ['DigiriskGuideVennDanger', 'DigiriskGuideNotionsDangerText', $this->accentWarn],
            ['DigiriskGuideVennRisk',   'DigiriskGuideNotionsRiskText',   $this->ink]
        ];
        $cursor = $y;
        foreach ($definitions as $definition) {
            $card   = ['title' => $outputLangs->transnoentities($definition[0]), 'text' => $outputLangs->transnoentities($definition[1]), 'color' => $definition[2]];
            $height = $this->cardHeight($pdf, $card, $rightW - (2 * self::CARD_PAD), $size);
            $this->drawCard($pdf, $card, $rightX, $cursor, $rightW, $height, $size, false);
            $cursor += $height + 4;
        }

        $pdf->SetXY($this->marge_gauche, max($y + $vennHeight, $cursor) + 2);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideNotionsStructure'), $size, 75);
        $this->cardGrid($pdf, [
            ['kicker' => 'UT', 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsWorkUnit'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsWorkUnitText')],
            ['kicker' => 'GP', 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsGroupment'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsGroupmentText')],
            ['kicker' => $outputLangs->transnoentities('Evaluators'), 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsEvaluators'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsEvaluatorsText'), 'color' => $this->accentAlt],
            ['kicker' => 'EVRP', 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsShared'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsSharedText'), 'color' => $this->accentAlt]
        ], 2, $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideNotionsRiskBuilt'), $size, 70);
        $this->flow($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideNotionsStep1Kicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsStep1'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsStep1Text'), 'color' => $this->accent],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideNotionsStep2Kicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsStep2'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsStep2Text'), 'color' => $this->levelBg[2]],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideNotionsStep3Kicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideNotionsStep3'), 'text' => $outputLangs->transnoentities('DigiriskGuideNotionsStep3Text'), 'color' => $this->accentAlt]
        ], $size);
    }

    /**
     * Chapitre 3 : la cotation des risques.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterCotation($pdf, Translate $outputLangs, float $size)
    {
        $this->chapter($pdf, 3, $outputLangs, 'DigiriskGuideCotationLead', $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideCotationLevels'), $size, 55);
        $this->levelScale($pdf, $outputLangs, $size);

        $this->callout($pdf, $outputLangs->transnoentities('DigiriskGuideLowRiskTitle'), [
            $outputLangs->transnoentities('DigiriskGuideLowRisk1'),
            ['text' => $outputLangs->transnoentities('DigiriskGuideLowRisk2'), 'sub' => true],
            ['text' => $outputLangs->transnoentities('DigiriskGuideLowRisk3'), 'sub' => true],
            $outputLangs->transnoentities('DigiriskGuideLowRisk4')
        ], $size, $this->levelBg[2]);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideSimpleMethod'), $size, 25);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuideSimpleMethodText'), $size - 0.5);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideAdvancedMethod'), $size, 60);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuideAdvancedMethodText'), $size - 0.5);
        $pdf->Ln(2);
        $this->formula($pdf, $outputLangs, $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideAdvancedGrid'), $size, 70);
        $width  = $this->contentWidth($pdf);
        $widths = [$width * 0.15, $width * 0.17, $width * 0.17, $width * 0.17, $width * 0.17, $width * 0.17];
        $header = [
            $outputLangs->transnoentities('DigiriskGuideCriterion'),
            ['text' => '0', 'align' => 'C'], ['text' => '1', 'align' => 'C'], ['text' => '2', 'align' => 'C'], ['text' => '3', 'align' => 'C'], ['text' => '4', 'align' => 'C']
        ];
        $rows = [];
        foreach (['Gravity', 'Exposure', 'Occurrence', 'Training', 'Protection'] as $criterion) {
            $row = [['text' => $outputLangs->transnoentities('DigiriskGuideCriterion' . $criterion), 'bold' => true]];
            for ($value = 0; $value <= 4; $value++) {
                $key   = 'DigiriskGuideGrid' . $criterion . $value;
                $label = $outputLangs->transnoentities($key);
                // Probabilite, formation et protection se cotent de 1 a 4 : la colonne 0 reste vide
                $row[] = ['text' => $label === $key ? '-' : $label, 'align' => 'C', 'color' => $label === $key ? $this->inkMuted : $this->inkSoft];
            }
            $rows[] = $row;
        }
        $this->table($pdf, $header, $rows, $widths, $size);

        $pdf->Ln(3);
        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideAdvancedTranscription'), $size, 45);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuideAdvancedTranscriptionText'), $size - 0.5);
        $widths = [$width * 0.46, $width * 0.30, $width * 0.24];
        $header = [
            $outputLangs->transnoentities('DigiriskGuideProductR'),
            ['text' => $outputLangs->transnoentities('DigiriskGuideLevel'), 'align' => 'C'],
            ['text' => $outputLangs->transnoentities('RiskCotation'), 'align' => 'C']
        ];
        $rows = [];
        foreach ([4 => ['DigiriskGuideProductR4', '80 - 100'], 3 => ['DigiriskGuideProductR3', '51 - 79'], 2 => ['DigiriskGuideProductR2', '48 - 50'], 1 => ['DigiriskGuideProductR1', '0 - 47']] as $level => $data) {
            $rows[] = [$outputLangs->transnoentities($data[0]), $this->levelCell($level, $outputLangs), ['text' => $data[1], 'align' => 'C', 'bold' => true]];
        }
        $this->table($pdf, $header, $rows, $widths, $size);

        $pdf->Ln(3);
        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideToolsTitle'), $size, 60);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuideToolsText'), $size - 0.5);
        $pdf->Ln(1);
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideToolRpsKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideToolRps'), 'text' => $outputLangs->transnoentities('DigiriskGuideToolRpsText'), 'color' => $this->categoryColors[7]],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideToolAsbestosKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideToolAsbestos'), 'text' => $outputLangs->transnoentities('DigiriskGuideToolAsbestosText'), 'color' => $this->categoryColors[1]],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideToolChemicalKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuideToolChemical'), 'text' => $outputLangs->transnoentities('DigiriskGuideToolChemicalText'), 'color' => $this->categoryColors[4]]
        ], 3, $size, true);

        $this->kicker($pdf, $this->marge_gauche, $pdf->GetY(), $width, $outputLangs->transnoentities('DigiriskGuideRpsFactors'), $size, $this->categoryColors[7]);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 7);
        $factors = [];
        for ($index = 1; $index <= 9; $index++) {
            $factors[] = $outputLangs->transnoentities('DigiriskGuideRpsFactor' . $index);
        }
        $this->chips($pdf, $factors, $size, $this->categoryColors[7]);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideToolsMatching'), $size, 70);
        $widths = [$width * 0.26, $width * 0.14, $width * 0.34, $width * 0.26];
        $header = [
            ['text' => $outputLangs->transnoentities('DigiriskGuideLevel'), 'align' => 'C'],
            ['text' => $outputLangs->transnoentities('RiskCotation'), 'align' => 'C'],
            $outputLangs->transnoentities('DigiriskGuideToolAsbestos'),
            $outputLangs->transnoentities('DigiriskGuideToolChemicalShort')
        ];
        $rows = [];
        foreach ([4 => '80 - 100', 3 => '51 - 79', 2 => '48 - 50', 1 => '0 - 47'] as $level => $range) {
            $rows[] = [
                $this->levelCell($level, $outputLangs),
                ['text' => $range, 'align' => 'C', 'bold' => true],
                $outputLangs->transnoentities('DigiriskGuideAsbestosLevel' . $level),
                $outputLangs->transnoentities('DigiriskGuideSeirichLevel' . $level)
            ];
        }
        $this->table($pdf, $header, $rows, $widths, $size);
    }

    /**
     * Chapitre 4 : les familles de danger, lues dans le referentiel du module.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterCategories($pdf, Translate $outputLangs, float $size)
    {
        // Le referentiel tient sur une page a condition de l'y commencer : ouvert en milieu de
        // page, il laissait une derniere famille seule en haut de la page suivante
        $this->chapter($pdf, 4, $outputLangs, 'DigiriskGuideCategoriesLead', $size, 999);

        $categories = Risk::getDangerCategories();
        if (empty($categories)) {
            return;
        }

        // Les familles de l'INRS d'abord, dans l'ordre de leur position, les facteurs de
        // penibilite ensuite : le referentiel les range dans un ordre de saisie, pas de lecture
        usort($categories, function ($first, $second) {
            return (int) $first['position'] <=> (int) $second['position'];
        });

        $columns   = 3;
        $gap       = 3;
        $cellWidth = ($this->contentWidth($pdf) - (($columns - 1) * $gap)) / $columns;
        $cellH     = 15;
        $picto     = 10;

        foreach (array_chunk($categories, $columns) as $row) {
            $this->reserveSpace($pdf, $cellH + $gap);
            $y = $pdf->GetY();
            $x = $this->marge_gauche;

            foreach ($row as $category) {
                $this->card($pdf, $x, $y, $cellWidth, $cellH, $this->surface);

                $path = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $category['thumbnail_name'] . '.png';
                if (is_readable($path)) {
                    $pdf->Image($path, $x + 2.5, $y + (($cellH - $picto) / 2), $picto, $picto, '', '', '', false, 300, '', false, false, 0, 'CM');
                }

                $pdf->SetFont('', '', $size - 1.5);
                $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
                $pdf->MultiCell($cellWidth - $picto - 7, 3.8, (string) $category['name'], 0, 'L', false, 1, $x + $picto + 5, $y, true, 0, false, true, $cellH, 'M');

                $x += $cellWidth + $gap;
            }

            $pdf->SetXY($this->marge_gauche, $y + $cellH + $gap);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $this->callout($pdf, $outputLangs->transnoentities('DigiriskGuideCategoriesNoteTitle'), [
            $outputLangs->transnoentities('DigiriskGuideCategoriesNote1'),
            $outputLangs->transnoentities('DigiriskGuideCategoriesNote2'),
            $outputLangs->transnoentities('DigiriskGuideCategoriesNote3')
        ], $size);
    }

    /**
     * Chapitre 5 : lire le document unique.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterReading($pdf, Translate $outputLangs, float $size)
    {
        $this->chapter($pdf, 5, $outputLangs, 'DigiriskGuideReadingLead', $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideReadingParts'), $size, 60);
        $steps = [];
        for ($index = 1; $index <= 6; $index++) {
            $steps[] = [
                'title' => $outputLangs->transnoentities('DigiriskGuideReadingPart' . $index),
                'text'  => $outputLangs->transnoentities('DigiriskGuideReadingPart' . $index . 'Text')
            ];
        }
        $this->numberedSteps($pdf, $steps, $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideReadingDashboards'), $size, 85);
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideReadingTableKicker', '1'), 'title' => $outputLangs->transnoentities('DigiriskGuideReadingByUnit'), 'text' => $outputLangs->transnoentities('DigiriskGuideReadingByUnitText')],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideReadingTableKicker', '2'), 'title' => $outputLangs->transnoentities('DigiriskGuideReadingByCategory'), 'text' => $outputLangs->transnoentities('DigiriskGuideReadingByCategoryText')],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideReadingTableKicker', '3'), 'title' => $outputLangs->transnoentities('DigiriskGuideReadingByLevel'), 'text' => $outputLangs->transnoentities('DigiriskGuideReadingByLevelText')],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuideReadingTableKicker', '4'), 'title' => $outputLangs->transnoentities('DigiriskGuideReadingRps'), 'text' => $outputLangs->transnoentities('DigiriskGuideReadingRpsText'), 'color' => $this->categoryColors[7]]
        ], 2, $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideReadingRiskTable'), $size, 60);
        $width  = $this->contentWidth($pdf);
        $widths = [$width * 0.24, $width * 0.76];
        $header = [$outputLangs->transnoentities('DigiriskGuideColumn'), $outputLangs->transnoentities('DigiriskGuideContent')];
        $rows   = [];
        for ($index = 1; $index <= 6; $index++) {
            $rows[] = [
                ['text' => $outputLangs->transnoentities('DigiriskGuideRiskColumn' . $index), 'bold' => true],
                $outputLangs->transnoentities('DigiriskGuideRiskColumn' . $index . 'Text')
            ];
        }
        $this->table($pdf, $header, $rows, $widths, $size);

        $pdf->Ln(3);
        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideReadingSheet'), $size, 95);
        $this->cardGrid($pdf, [
            ['badge' => '1', 'title' => $outputLangs->transnoentities('DigiriskGuideSheetPage1'), 'lines' => [
                $outputLangs->transnoentities('DigiriskGuideSheetPage1Line1'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage1Line2'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage1Line3'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage1Line4')
            ]],
            ['badge' => '2', 'title' => $outputLangs->transnoentities('DigiriskGuideSheetPage2'), 'lines' => [
                $outputLangs->transnoentities('DigiriskGuideSheetPage2Line1'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage2Line2')
            ]],
            ['badge' => '3', 'title' => $outputLangs->transnoentities('DigiriskGuideSheetPage3'), 'lines' => [
                $outputLangs->transnoentities('DigiriskGuideSheetPage3Line1'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage3Line2'),
                $outputLangs->transnoentities('DigiriskGuideSheetPage3Line3')
            ]]
        ], 3, $size);
    }

    /**
     * Chapitre 6 : passer a l'action, le programme de prevention.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function chapterPrevention($pdf, Translate $outputLangs, float $size)
    {
        $this->chapter($pdf, 6, $outputLangs, 'DigiriskGuidePreventionLead', $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuidePreventionByHeadcount'), $size, 95);
        $this->cardGrid($pdf, [
            ['kicker' => $outputLangs->transnoentities('DigiriskGuidePreventionBigKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuidePreventionBig'), 'lines' => [
                $outputLangs->transnoentities('DigiriskGuidePreventionBig1'),
                $outputLangs->transnoentities('DigiriskGuidePreventionBig2'),
                $outputLangs->transnoentities('DigiriskGuidePreventionBig3')
            ]],
            ['kicker' => $outputLangs->transnoentities('DigiriskGuidePreventionSmallKicker'), 'title' => $outputLangs->transnoentities('DigiriskGuidePreventionSmall'), 'text' => $outputLangs->transnoentities('DigiriskGuidePreventionSmallText'), 'color' => $this->accentAlt]
        ], 2, $size);

        $this->callout($pdf, $outputLangs->transnoentities('DigiriskGuidePreventionInDigirisk'), [
            $outputLangs->transnoentities('DigiriskGuidePreventionInDigirisk1'),
            $outputLangs->transnoentities('DigiriskGuidePreventionInDigirisk2')
        ], $size);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuidePreventionPriorities'), $size, 30);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuidePreventionPrioritiesText'), $size - 0.5);
        $pdf->Ln(2);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuidePreventionLevers'), $size, 90);
        $this->cardGrid($pdf, [
            ['badge' => 'G', 'title' => $outputLangs->transnoentities('DigiriskGuideCriterionGravity'),    'text' => $outputLangs->transnoentities('DigiriskGuideLeverGravity'),    'color' => $this->categoryColors[0]],
            ['badge' => 'E', 'title' => $outputLangs->transnoentities('DigiriskGuideCriterionExposure'),   'text' => $outputLangs->transnoentities('DigiriskGuideLeverExposure'),   'color' => $this->categoryColors[1]],
            ['badge' => 'F', 'title' => $outputLangs->transnoentities('DigiriskGuideCriterionTraining'),   'text' => $outputLangs->transnoentities('DigiriskGuideLeverTraining'),   'color' => $this->categoryColors[5]],
            ['badge' => 'P', 'title' => $outputLangs->transnoentities('DigiriskGuideCriterionProtection'), 'text' => $outputLangs->transnoentities('DigiriskGuideLeverProtection'), 'color' => $this->categoryColors[7]]
        ], 4, $size, true);
        $this->paragraph($pdf, $outputLangs->transnoentities('DigiriskGuideLeverOccurrence'), $size - 1, 'I', $this->inkMuted);

        $this->subTitle($pdf, $outputLangs->transnoentities('DigiriskGuideKeepAlive'), $size, 85);
        $this->cardGrid($pdf, [
            ['badge' => '1', 'title' => $outputLangs->transnoentities('DigiriskGuideKeepAliveUpdate'),  'text' => $outputLangs->transnoentities('DigiriskGuideKeepAliveUpdateText')],
            ['badge' => '2', 'title' => $outputLangs->transnoentities('DigiriskGuideKeepAliveCse'),     'text' => $outputLangs->transnoentities('DigiriskGuideKeepAliveCseText'),     'color' => $this->accentAlt],
            ['badge' => '3', 'title' => $outputLangs->transnoentities('DigiriskGuideKeepAliveDisplay'), 'text' => $outputLangs->transnoentities('DigiriskGuideKeepAliveDisplayText'), 'color' => $this->levelBg[2]]
        ], 3, $size);
    }
}
