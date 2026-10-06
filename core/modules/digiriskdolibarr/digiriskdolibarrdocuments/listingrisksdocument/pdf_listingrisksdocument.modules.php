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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/listingrisksdocument/pdf_listingrisksdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Listing des risques en PDF natif, au format A3 paysage comme le gabarit ODT.
 *          Le document s'ouvre sur le mini document unique de l'element imprime (etablissement,
 *          arborescence, responsables, urgences, signalement), rappelle le cadre reglementaire et
 *          la methode de cotation, puis liste les risques par niveau decroissant.
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';
require_once __DIR__ . '/../../../../../../saturne/lib/dolibarr.lib.php';

/**
 * Class to build the listing risks document as a PDF.
 */
class pdf_listingrisksdocument extends SaturneDocumentModel
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
    public string $document_type = 'listingrisksdocument';

    /**
     * @var string Orientation de la page, A3 paysage comme le gabarit ODT
     */
    protected string $orientation = 'L';

    /**
     * @var array Bleu Digirisk #0067A6, couleur principale du document
     */
    protected array $accent = [0, 103, 166];

    /**
     * @var array Vert Digirisk #2A9D8F, couleur des encadres du mini document unique
     */
    protected array $accentAlt = [42, 157, 143];

    /**
     * @var array Rouge des numeros d'urgence
     */
    protected array $accentWarn = [224, 83, 83];

    /**
     * @var array Gris des entetes de tableau
     */
    protected array $headBg = [241, 243, 245];

    /**
     * @var array Couleur de fond de chaque niveau de cotation, celles de Risk::$cotations
     */
    protected array $levelBg = [
        1 => [236, 236, 236],
        2 => [233, 173, 79],
        3 => [224, 83, 83],
        4 => [43, 43, 43]
    ];

    /**
     * @var array Couleur du texte ecrit sur le fond du niveau de cotation
     */
    protected array $levelText = [
        1 => [0, 0, 0],
        2 => [0, 0, 0],
        3 => [255, 255, 255],
        4 => [255, 255, 255]
    ];

    /**
     * @var array Neuf couleurs de la roue des principes de prevention - issue #5307
     */
    protected array $wheelColors = [
        [214, 69, 65],
        [232, 125, 55],
        [240, 176, 60],
        [163, 188, 66],
        [76, 166, 107],
        [61, 158, 160],
        [59, 122, 176],
        [110, 92, 165],
        [186, 84, 140]
    ];

    /**
     * @var array Responsables des taches, indexes par identifiant de tache
     */
    protected array $taskResponsibles = [];

    /**
     * @var bool Afficher la colonne photo des evaluations - issue #5299
     */
    protected bool $showPhoto = true;

    /**
     * @var array Options de masquage du plan d'actions retenues pour cette generation - issue #5289
     */
    protected array $taskOptions = [];

    /**
     * Bande basse reservee au pied de page, en mm. Le contenu s'arrete au dessus, le pied
     * s'ecrit dedans : c'est ce qui garantit qu'ils ne se chevauchent jamais.
     */
    const FOOTER_BAND = 15;

    /**
     * Hauteur maximale, en mm, d'une image dessinee dans une cellule de tableau.
     */
    const MAX_IMAGE_HEIGHT = 20;

    /**
     * @var string Texte de gauche du pied de page
     */
    protected string $footerText = '';

    /**
     * @var float Taille de police du pied de page
     */
    protected float $footerFontSize = 7;

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'listingrisksdocument';
        $this->description = $langs->trans('ListingRisksDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->height      = 5;
        $this->version     = '1.0.0';

        // A3 paysage, comme le gabarit ODT : le tableau des risques a huit colonnes
        $this->orientation   = 'L';
        $this->page_largeur  = 420;
        $this->page_hauteur  = 297;
        $this->format        = [$this->page_largeur, $this->page_hauteur];
        $this->marge_gauche  = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
        $this->marge_droite  = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
        $this->marge_haute   = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
        $this->marge_basse   = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
    }

    /**
     * Largeur utile de la page.
     *
     * @param  TCPDF $pdf PDF handler
     * @return float      Largeur entre les marges
     */
    protected function contentWidth($pdf): float
    {
        return $pdf->GetPageWidth() - $this->marge_gauche - $this->marge_droite;
    }

    /**
     * Ajoute une page si le bloc a venir ne tient pas sur la page courante.
     *
     * @param  TCPDF $pdf          PDF handler
     * @param  float $neededHeight Hauteur necessaire
     * @return void
     */
    protected function checkPageBreak($pdf, float $neededHeight)
    {
        $usable = $pdf->getPageHeight() - $this->marge_haute - $pdf->getBreakMargin();

        // Un bloc plus haut qu'une page debordera quoi qu'il arrive : le repousser ne ferait que
        // laisser une page blanche derriere lui
        if ($neededHeight >= $usable) {
            return;
        }

        // Deja en haut d'une page : rien a gagner a en ajouter une
        if ($pdf->GetY() <= $this->marge_haute + 1) {
            return;
        }

        if ($pdf->GetY() + $neededHeight + $pdf->getBreakMargin() > $pdf->getPageHeight()) {
            $this->newPage($pdf);
        }
    }

    /**
     * Garantit qu'il reste la place demandee sur la page courante, en en ouvrant une nouvelle
     * sinon. A utiliser avant tout bloc dessine avec la rupture automatique coupee : sans cela le
     * bloc ecrit par dessus le pied de page.
     *
     * @param  TCPDF $pdf          PDF handler
     * @param  float $neededHeight Hauteur necessaire
     * @return void
     */
    protected function reserveSpace($pdf, float $neededHeight)
    {
        $available = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY();

        if ($available < $neededHeight) {
            $this->newPage($pdf);
        }
    }

    /**
     * Ouvre une nouvelle page.
     *
     * Le pied n'est pas pose ici : TCPDF ouvre aussi des pages tout seul quand un texte long
     * depasse, et ces pages la ne passeraient jamais par cette methode. Il est ecrit en une passe
     * finale sur toutes les pages, par _pagefooter().
     *
     * @param  TCPDF $pdf PDF handler
     * @return void
     */
    protected function newPage($pdf)
    {
        $pdf->AddPage($this->orientation);
        $pdf->SetY($this->marge_haute);
    }

    /**
     * Bandeau de titre de section.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $title Titre
     * @param  float  $size  Taille de police
     * @return void
     */
    protected function sectionTitle($pdf, string $title, float $size)
    {
        $this->checkPageBreak($pdf, 18);

        $pdf->Ln(2);
        $pdf->SetFont('', 'B', $size);
        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->contentWidth($pdf), 7, ' ' . $title, 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Paragraphe de texte courant.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $text  Texte
     * @param  float  $size  Taille de police
     * @param  string $style Style de police
     * @param  array  $color Couleur du texte
     * @return void
     */
    protected function paragraph($pdf, string $text, float $size, string $style = '', array $color = [60, 60, 60])
    {
        if (!dol_strlen($text)) {
            return;
        }

        $height = $pdf->getNumLines($text, $this->contentWidth($pdf)) * $this->height;
        $this->checkPageBreak($pdf, $height);

        $pdf->SetFont('', $style, $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($this->contentWidth($pdf), $this->height, $text, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    /**
     * Paragraphe issu d'un champ WYSIWYG : le HTML est rendu tel quel (gras, italique, listes).
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $html  Contenu HTML
     * @param  float  $size  Taille de police
     * @param  float  $width Largeur du bloc, la largeur utile par defaut
     * @param  array  $color Couleur du texte
     * @return void
     */
    protected function htmlParagraph($pdf, string $html, float $size, float $width = 0, array $color = [60, 60, 60])
    {
        if (!dol_strlen($html)) {
            return;
        }

        $width = $width ?: $this->contentWidth($pdf);

        // getNumLines ne sait pas mesurer du HTML : la hauteur est estimee sur le texte brut
        $height = $pdf->getNumLines(saturne_flatten_wysiwyg_blocks($html, true), $width) * $this->height;
        $this->checkPageBreak($pdf, $height);

        $pdf->SetFont('', '', $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->writeHTMLCell($width, $this->height, $this->marge_gauche, $pdf->GetY(), $html, 0, 1, false, true, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    /**
     * Tableau generique.
     *
     * $rows est une liste de lignes, chaque ligne une liste de cellules. Une cellule est une
     * chaine ou ['text' => string, 'align' => 'L|C|R', 'bold' => bool, 'image' => chemin,
     * 'fill' => [r, g, b], 'color' => [r, g, b]].
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $header Libelles d'entete, vide pour un tableau sans entete
     * @param  array $rows   Lignes
     * @param  array $widths Largeurs de colonnes
     * @param  float $size   Taille de police
     * @return void
     */
    protected function table($pdf, array $header, array $rows, array $widths, float $size)
    {
        if (empty($rows)) {
            return;
        }

        if (!empty($header)) {
            $this->drawTableHeader($pdf, $header, $widths, $size);
        }

        foreach ($rows as $cells) {
            // La ligne prend la hauteur de sa cellule la plus haute
            $maxHeight = $this->height + 1;
            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                if (is_array($cellData) && !empty($cellData['image'])) {
                    $maxHeight = max($maxHeight, 22);
                    continue;
                }
                $text      = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                $height    = $pdf->getNumLines((string) $text, $widths[$index]) * $this->height;
                $maxHeight = max($maxHeight, $height);
            }

            // Une ligne plus haute que la page ne tiendra nulle part : la laisser deborder plutot
            // que d'ouvrir indefiniment des pages devant elle
            $this->checkPageBreak($pdf, $maxHeight);
            if (!empty($header) && $pdf->GetY() <= $this->marge_haute + 1) {
                $this->drawTableHeader($pdf, $header, $widths, $size);
            }

            $pdf->SetX($this->marge_gauche);

            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $isArray = is_array($cellData);
                $text    = $isArray ? ($cellData['text'] ?? '') : $cellData;
                $align   = ($isArray && !empty($cellData['align'])) ? $cellData['align'] : 'L';
                $bold    = $isArray && !empty($cellData['bold']);
                $fill    = ($isArray && !empty($cellData['fill'])) ? $cellData['fill'] : null;
                $color   = ($isArray && !empty($cellData['color'])) ? $cellData['color'] : [0, 0, 0];

                if ($fill !== null) {
                    $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                }
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
                $pdf->SetFont('', $bold ? 'B' : '', $size - 1);
                $pdf->MultiCell($widths[$index], $maxHeight, (string) $text, 1, $align, $fill !== null, 0, $x, $y, true, 0, false, true, $maxHeight, 'M');
                $pdf->SetTextColor(0, 0, 0);

                // Image centree dans la cellule, dessinee par dessus le cadre. Largeur et hauteur
                // sont toutes deux imposees, avec fitbox : sans largeur maximale un picto grandit
                // avec la ligne et deborde sur la colonne suivante des que la ligne est haute
                if ($isArray && !empty($cellData['image']) && is_readable($cellData['image'])) {
                    $imageWidth  = $widths[$index] - 4;
                    $imageHeight = min($maxHeight - 4, self::MAX_IMAGE_HEIGHT);
                    $pdf->Image($cellData['image'], $x + 2, $y + (($maxHeight - $imageHeight) / 2), $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0, 'CM');
                }

                $pdf->SetXY($x + $widths[$index], $y);
            }
            $pdf->Ln($maxHeight);
        }

        $pdf->Ln(2);
    }

    /**
     * Ligne d'entete d'un tableau, repetee en haut de chaque page.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $header Libelles d'entete
     * @param  array $widths Largeurs de colonnes
     * @param  float $size   Taille de police
     * @return void
     */
    protected function drawTableHeader($pdf, array $header, array $widths, float $size)
    {
        $pdf->SetFont('', 'B', $size - 1);
        $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
        $pdf->SetX($this->marge_gauche);
        foreach ($header as $index => $label) {
            $pdf->Cell($widths[$index], 7, $label, 1, 0, 'C', true);
        }
        $pdf->Ln(7);
    }

    /**
     * Encadre du mini document unique : un titre sur fond vert puis des lignes de texte libre.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  float  $x     Abscisse
     * @param  float  $y     Ordonnee
     * @param  float  $width Largeur
     * @param  string $title Titre de l'encadre, vide pour un encadre sans titre
     * @param  array  $lines Lignes ['text' => string, 'size' => float, 'style' => string, 'align' => string, 'color' => array]
     * @param  float  $size  Taille de police par defaut
     * @return float         Ordonnee du bas de l'encadre
     */
    protected function coverBox($pdf, float $x, float $y, float $width, string $title, array $lines, float $size): float
    {
        $currentY = $y;

        if (dol_strlen($title)) {
            $pdf->SetFont('', 'B', $size);
            $pdf->SetFillColor($this->accentAlt[0], $this->accentAlt[1], $this->accentAlt[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 7, ' ' . $title, 1, 0, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $currentY += 7;
        }

        foreach ($lines as $line) {
            $text      = (string) ($line['text'] ?? '');
            $lineSize  = $line['size'] ?? $size;
            $lineStyle = $line['style'] ?? '';
            $lineAlign = $line['align'] ?? 'L';
            $lineColor = $line['color'] ?? [0, 0, 0];

            $pdf->SetFont('', $lineStyle, $lineSize);
            $pdf->SetTextColor($lineColor[0], $lineColor[1], $lineColor[2]);
            $height = max($this->height, $pdf->getStringHeight($width - 2, $text));
            $pdf->SetXY($x, $currentY);
            $pdf->MultiCell($width, $height, ' ' . $text, 'LR', $lineAlign, false, 0, $x, $currentY, true, 0, false, true, $height, 'M');
            $currentY += $height;
        }

        $pdf->SetTextColor(0, 0, 0);

        // Trait de fermeture : les lignes n'ont que leurs bords lateraux
        $pdf->Line($x, $currentY, $x + $width, $currentY);

        return $currentY;
    }

    /**
     * Entete de page : logo et titre du document.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function _pagehead(&$pdf, $object, $outputLangs, $size)
    {
        global $mysoc;

        $posY = $this->marge_haute;

        if (!getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO') && $mysoc->logo) {
            $logoDir = getMultidirOutput($object, 'mycompany') ?: '';
            $logo    = getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') ? $logoDir . '/logos/' . $mysoc->logo : $logoDir . '/logos/thumbs/' . $mysoc->logo_small;
            if (is_readable($logo)) {
                $pdf->Image($logo, $pdf->GetPageWidth() - $this->marge_droite - 30, $posY, 0, 12);
            }
        }

        $pdf->SetFont('', 'B', $size + 8);
        $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetXY($this->marge_gauche, $posY + 2);
        $pdf->Cell($this->contentWidth($pdf), 9, $outputLangs->transnoentities('ListingRisksDocumentPDFTitle'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('', '', $size);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->contentWidth($pdf), 6, $this->coverSubtitle($object, $outputLangs) . '  -  ' . $this->coverElementLabel($object), 0, 1, 'C');

        // Filet d'accent sous le titre
        $y = $pdf->GetY() + 1;
        $pdf->SetDrawColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetLineWidth(0.6);
        $pdf->Line($this->marge_gauche, $y, $pdf->GetPageWidth() - $this->marge_droite, $y);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->Ln(4);
    }

    /**
     * Nature de l'element imprime, affichee sous le titre du document.
     *
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @return string                 Libelle traduit
     */
    protected function coverSubtitle($object, Translate $outputLangs): string
    {
        if ($object->element == 'digiriskstandard') {
            return $outputLangs->transnoentities('ListingRisksEstablishment');
        }
        if ($object->element_type == 'workunit') {
            return $outputLangs->transnoentities('WorkUnit');
        }

        return $outputLangs->transnoentities('Groupment');
    }

    /**
     * Libelle de l'element imprime. Le standard n'a pas de libelle propre : il porte celui de
     * l'etablissement.
     *
     * @param  object $object Element imprime
     * @return string         Libelle a afficher
     */
    protected function coverElementLabel($object): string
    {
        global $mysoc;

        if ($object->element == 'digiriskstandard') {
            return $mysoc->name;
        }

        return dol_strlen($object->label ?? '') ? $object->label : $object->ref;
    }

    /**
     * Pied de page.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function _pagefooter($pdf, $object, $outputLangs, $size)
    {
        $this->writeFooter($pdf);
    }

    /**
     * Pose le pied sur toutes les pages, une fois le contenu ecrit.
     *
     * En une passe finale plutot qu'au fil de l'eau : TCPDF ouvre des pages de lui-meme quand un
     * texte long depasse, et ces pages n'auraient sinon ni pied ni numero. Le nombre total de
     * pages n'est de toute facon connu qu'ici, TCPDI ne supportant pas AliasNbPages.
     *
     * @param  TCPDF $pdf PDF handler
     * @return void
     */
    protected function writeFooter($pdf)
    {
        if (empty($this->footerText)) {
            return;
        }

        $currentPage = $pdf->getPage();
        $currentY    = $pdf->GetY();
        $totalPages  = $pdf->getNumPages();
        $halfWidth   = $this->contentWidth($pdf) / 2;

        $pdf->SetFont('', '', $this->footerFontSize);
        $pdf->SetTextColor(140, 140, 140);

        for ($page = 1; $page <= $totalPages; $page++) {
            $pdf->setPage($page);
            // Le pied s'ecrit dans la bande basse, sous le seuil de rupture : sans couper la
            // rupture automatique son Cell serait reporte en haut de la page suivante. A recouper
            // apres chaque setPage, qui restaure les reglages memorises de la page.
            $pdf->SetAutoPageBreak(false);
            $pdf->SetXY($this->marge_gauche, $pdf->getPageHeight() - 10);
            $pdf->Cell($halfWidth, 5, $this->footerText, 0, 0, 'L');
            $pdf->Cell($halfWidth, 5, 'Page ' . $page . ' / ' . $totalPages, 0, 0, 'R');
        }

        $pdf->SetTextColor(0, 0, 0);

        // Rendre la main sur la position d'avant la passe : laisser le curseur dans la bande
        // basse ferait ouvrir une page blanche des le retour de la rupture automatique
        $pdf->setPage($currentPage);
        $pdf->SetXY($this->marge_gauche, $currentY);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
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
        global $action, $hookmanager, $langs, $user;

        require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
        require_once __DIR__ . '/../../../../../class/digiriskresources.class.php';
        require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

        $moreParam = self::getMoreParam($objectDocument, $moreParam);

        $object = $moreParam['object'];

        // ticket : les entetes de la section des registres, Subject n'existe que la - issue #5235
        $outputLangs->loadLangs(['companies', 'projects', 'other', 'ticket', 'digiriskdolibarr@digiriskdolibarr']);

        $moreParam['hideTemplateName'] = 1;
        $object->module                = $this->module;

        $refOrig = $object->ref;
        if (preg_match('/^specimen/i', $object->ref)) {
            $object->ref = 'specimen';
        }

        // buildDocumentFilename rend -1 en cas d'echec, sinon le chemin du fichier. Comparer ce
        // chemin a 0 le compare en fait a la chaine '0' : sous Linux il commence par '/', qui est
        // inferieur a '0', et la generation echouerait systematiquement
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreParam);

        $object->ref = $refOrig;
        if (!is_string($file) || empty($file)) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        $pdf  = pdf_getInstance($this->format);
        $size = pdf_getPDFFontSize($outputLangs) + 1;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTitle($outputLangs->convToOutputCharset($object->ref));
        $pdf->SetSubject($outputLangs->transnoentities('ListingRisksDocumentPDFTitle'));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, self::FOOTER_BAND);
        $pdf->SetAutoPageBreak(1, self::FOOTER_BAND);

        $pdf->AddPage($this->orientation);
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $size);

        $this->footerText = $object->ref . ' - ' . $outputLangs->transnoentities('AccidentInvestigationDocumentGeneratedAt') . ' ' . dol_print_date(dol_now(), 'day', 'tzuser', $outputLangs);

        $this->_pagehead($pdf, $object, $outputLangs, $size);

        $this->sectionCover($pdf, $object, $outputLangs, $size);
        $this->sectionPerimeterTree($pdf, $object, $outputLangs, $size);
        $this->sectionElementPhoto($pdf, $object, $outputLangs, $size, $moreParam);
        $this->sectionLegalReminder($pdf, $outputLangs, $size);
        $this->sectionCotationMethod($pdf, $outputLangs, $size);
        $this->sectionRisks($pdf, $object, $outputLangs, $size, $moreParam);
        $this->sectionRegisters($pdf, $object, $outputLangs, $size, $moreParam);
        $this->sectionAccidents($pdf, $object, $outputLangs, $size, $moreParam);
        $this->sectionGlossary($pdf, $outputLangs, $size);

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
     * Mini document unique : la page d'accueil du listing. Trois colonnes, l'element et son
     * arborescence a gauche, le signalement et les urgences au centre, la photo a droite.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionCover($pdf, $object, Translate $outputLangs, float $size)
    {
        global $mysoc;

        $gap        = 5;
        $leftWidth  = $this->contentWidth($pdf) * 0.45;
        $midWidth   = $this->contentWidth($pdf) * 0.27;
        $rightWidth = $this->contentWidth($pdf) - $leftWidth - $midWidth - (2 * $gap);

        $leftX  = $this->marge_gauche;
        $midX   = $leftX + $leftWidth + $gap;
        $rightX = $midX + $midWidth + $gap;

        $startY = $pdf->GetY();

        // Les trois colonnes sont dessinees a des ordonnees imposees : la rupture automatique
        // renverrait la deuxieme colonne en page suivante des que la premiere est longue
        $pdf->SetAutoPageBreak(false);

        // Colonne de gauche : etablissement, element, arborescence, responsables
        $leftY = $startY;

        // Le standard porte deja le nom de l'etablissement : l'annoncer deux fois de suite serait
        // une ligne perdue sur la page d'accueil
        if ($object->element != 'digiriskstandard') {
            $leftY = $this->coverBox($pdf, $leftX, $startY, $leftWidth, $outputLangs->transnoentities('ListingRisksEstablishment'), [
                ['text' => $mysoc->name, 'style' => 'B', 'align' => 'C', 'size' => $size + 1, 'color' => $this->accentAlt]
            ], $size);
        }

        $leftY = $this->coverBox($pdf, $leftX, $leftY, $leftWidth, $this->coverSubtitle($object, $outputLangs), [
            ['text' => $this->coverElementLabel($object), 'style' => 'B', 'align' => 'C', 'size' => $size + 4, 'color' => $this->accentAlt]
        ], $size);

        $leftY = $this->coverBox($pdf, $leftX, $leftY, $leftWidth, $outputLangs->transnoentities('ListingRisksCoverContent'), $this->coverContentLines($object, $outputLangs, $size), $size);

        // Le standard n'a pas de responsables propres : son identifiant est celui d'un element,
        // les lire donnerait ceux d'un groupement sans rapport
        if ($object->element != 'digiriskstandard') {
            $leftY = $this->coverBox($pdf, $leftX, $leftY, $leftWidth, $outputLangs->transnoentities('ListingRisksCoverHierarchy'), $this->coverOfficerLines($object->id, $outputLangs, $size), $size);
        }

        $leftY = $this->coverBox($pdf, $leftX, $leftY, $leftWidth, $outputLangs->transnoentities('ListingRisksCoverDuerpFollowUp'), $this->coverOfficerLines(0, $outputLangs, $size), $size);

        // Colonne du centre : signalement, urgences, responsable de l'etablissement
        $midY = $this->coverBoxWithQrCode($pdf, $midX, $startY, $midWidth, $outputLangs, $size);

        $midY = $this->coverBox($pdf, $midX, $midY, $midWidth, $outputLangs->transnoentities('ListingRisksCoverEmergencyNumbers'), $this->coverEmergencyLines($outputLangs, $size), $size);

        $midY = $this->coverBox($pdf, $midX, $midY, $midWidth, $outputLangs->transnoentities('ResponsibleToNotify'), [
            ['text' => $this->establishmentResponsible($outputLangs), 'align' => 'C', 'style' => 'B']
        ], $size);

        // Colonne de droite : photo d'illustration de l'element
        $rightY = $this->coverPhoto($pdf, $object, $rightX, $startY, $rightWidth, max($leftY, $midY) - $startY, $outputLangs, $size);

        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
        $pdf->SetY(max($leftY, $midY, $rightY) + 4);

        // Description de l'element, saisie au WYSIWYG
        if (dol_strlen($object->description)) {
            $this->sectionTitle($pdf, $outputLangs->transnoentities('Description'), $size);
            $this->htmlParagraph($pdf, $object->description, $size);
        }
    }

    /**
     * Arborescence de l'element imprime : lui-meme puis ses enfants, decales par profondeur.
     *
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return array                  Lignes pour coverBox()
     */
    protected function coverContentLines($object, Translate $outputLangs, float $size): array
    {
        $lines = [];
        foreach ($this->perimeterElements($object) as $child) {
            $lines[] = [
                'text'  => str_repeat('   ', (int) $child['depth']) . $child['object']->ref . ' : ' . $child['object']->label,
                'size'  => $size - 1,
                'style' => empty($child['depth']) ? 'B' : ''
            ];
        }

        if (empty($lines)) {
            $lines[] = ['text' => $object->ref . ' : ' . $this->coverElementLabel($object), 'size' => $size - 1, 'style' => 'B'];
        }

        return $lines;
    }

    /**
     * Arborescence imprimee : l'element et ses descendants, la corbeille et ce qu'elle contient
     * en moins. Sert a la fois de sommaire sur la page d'accueil et de perimetre des risques.
     *
     * @param  object $object Element imprime
     * @return array          Elements ['object' => DigiriskElement, 'depth' => int], indexes par identifiant
     */
    protected function perimeterElements($object): array
    {
        $digiriskElement = new DigiriskElement($this->db);

        $parentId = ($object->element == 'digiriskstandard') ? 0 : $object->id;
        $elements = $digiriskElement->fetchDigiriskElementFlat($parentId, [], 'current', true);
        if (!is_array($elements)) {
            return [];
        }

        // La corbeille est un element actif comme un autre : sans ce filtre elle apparait dans le
        // sommaire du document. L'arbre etant a plat en profondeur d'abord, tout ce qui la suit
        // plus bas qu'elle est son contenu
        $trashId    = getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH');
        $trashDepth = null;

        $perimeter = [];
        foreach ($elements as $elementId => $element) {
            if ($trashDepth !== null) {
                if ((int) $element['depth'] > $trashDepth) {
                    continue;
                }
                $trashDepth = null;
            }
            if ($trashId > 0 && $elementId == $trashId) {
                $trashDepth = (int) $element['depth'];
                continue;
            }
            $perimeter[$elementId] = $element;
        }

        return $perimeter;
    }

    /**
     * Responsables de la prevention, ceux de l'element ou ceux de l'etablissement.
     *
     * @param  int       $elementId   Element ID, 0 pour les responsables de l'etablissement
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return array                  Lignes pour coverBox()
     */
    protected function coverOfficerLines(int $elementId, Translate $outputLangs, float $size): array
    {
        $digiriskResources = new DigiriskResources($this->db);

        // Les responsables de l'etablissement ne sont rattaches a aucun objet : les lire sans
        // filtre les melangerait avec ceux de chaque groupement
        $officerIds = $elementId > 0
            ? $digiriskResources->fetchResourcesIdsFromObject('PreventionOfficer', 'digiriskelement', $elementId)
            : $digiriskResources->fetchResourcesIdsFromObject('PreventionOfficer', '', 0);

        $lines = [];
        foreach ($officerIds as $officerId) {
            // Un objet par ligne : un fetch en echec laisse l'objet sur les valeurs du precedent
            $userTmp = new User($this->db);
            if ($userTmp->fetch($officerId) <= 0) {
                continue;
            }
            $line = dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname);
            if (dol_strlen($userTmp->job)) {
                $line = $userTmp->job . ' : ' . $line;
            }
            $lines[] = ['text' => $line, 'size' => $size - 1];
        }

        if (empty($lines)) {
            $lines[] = ['text' => $outputLangs->transnoentities('NoPreventionOfficerAssigned'), 'size' => $size - 1, 'style' => 'I', 'color' => [140, 140, 140]];
        }

        return $lines;
    }

    /**
     * Numeros d'urgence configures dans les reglages de securite.
     *
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return array                  Lignes pour coverBox()
     */
    protected function coverEmergencyLines(Translate $outputLangs, float $size): array
    {
        $digiriskResources = new DigiriskResources($this->db);

        $allLinks = $digiriskResources->fetchDigiriskResources();
        $lines    = [];

        foreach (['AllEmergencies', 'Pompiers', 'SAMU', 'Police'] as $resourceRef) {
            if (empty($allLinks[$resourceRef]->id[0])) {
                continue;
            }
            $society = new Societe($this->db);
            if ($society->fetch($allLinks[$resourceRef]->id[0]) <= 0) {
                continue;
            }
            $number = $society->phone ?: $society->name;
            $lines[] = [
                'text'  => $outputLangs->transnoentities($resourceRef) . ' : ' . $number,
                'size'  => $size,
                'style' => 'B',
                'align' => 'C',
                'color' => $this->accentWarn
            ];
        }

        if (empty($lines)) {
            $lines[] = ['text' => $outputLangs->transnoentities('NoEmergencyNumberConfigured'), 'size' => $size - 1, 'style' => 'I', 'color' => [140, 140, 140]];
        }

        return $lines;
    }

    /**
     * Responsable a prevenir de l'etablissement, configure dans les reglages de securite.
     *
     * @param  Translate $outputLangs Lang object
     * @return string                 Nom du responsable
     */
    protected function establishmentResponsible(Translate $outputLangs): string
    {
        $digiriskResources = new DigiriskResources($this->db);

        $allLinks = $digiriskResources->fetchDigiriskResources();
        if (empty($allLinks['Responsible']->id[0])) {
            return $outputLangs->transnoentities('NoResponsibleAssigned');
        }

        $userTmp = new User($this->db);
        if ($userTmp->fetch($allLinks['Responsible']->id[0]) <= 0) {
            return $outputLangs->transnoentities('NoResponsibleAssigned');
        }

        return dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname);
    }

    /**
     * Encadre de signalement : le QR code de l'interface publique de ticket, qui ouvre les
     * registres SST et DGI depuis un telephone.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  float     $x           Abscisse
     * @param  float     $y           Ordonnee
     * @param  float     $width       Largeur
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return float                  Ordonnee du bas de l'encadre
     */
    protected function coverBoxWithQrCode($pdf, float $x, float $y, float $width, Translate $outputLangs, float $size): float
    {
        $currentY = $this->coverBox($pdf, $x, $y, $width, $outputLangs->transnoentities('ListingRisksCoverReportIncident'), [], $size);

        $qrCodeSide = min(28, $width - 10);
        $qrCodePath = $this->ticketQrCodePath();

        if (dol_strlen($qrCodePath)) {
            $pdf->Image($qrCodePath, $x + (($width - $qrCodeSide) / 2), $currentY + 2, $qrCodeSide, $qrCodeSide);
        }

        $imageHeight = dol_strlen($qrCodePath) ? $qrCodeSide + 4 : 2;

        $pdf->SetFont('', '', $size - 1);
        $pdf->SetXY($x, $currentY);
        $pdf->MultiCell($width, $imageHeight, '', 'LR', 'C', false, 0, $x, $currentY, true, 0, false, true, $imageHeight, 'M');
        $currentY += $imageHeight;

        $labelHeight = $this->height;
        $pdf->SetXY($x, $currentY);
        $pdf->MultiCell($width, $labelHeight, $outputLangs->transnoentities('ListingRisksCoverRegisters'), 'LRB', 'C', false, 0, $x, $currentY, true, 0, false, true, $labelHeight, 'M');

        return $currentY + $labelHeight;
    }

    /**
     * Chemin du QR code de l'interface publique de ticket.
     *
     * @return string Chemin complet, vide si aucun QR code n'a ete genere
     */
    protected function ticketQrCodePath(): string
    {
        global $conf;

        // Le QR code partage entre entites prime, celui de l'entite prend le relais quand il n'y a
        // rien de partage : sans ce repli une base multi-societe montee apres coup n'en aurait aucun
        $qrCodeDirs = [$conf->digiriskdolibarr->multidir_output[$conf->entity ?: 1] . '/ticketqrcode/'];
        if (isModEnabled('multicompany')) {
            array_unshift($qrCodeDirs, DOL_DATA_ROOT . '/digiriskdolibarr/multicompany/ticketqrcode/');
        }

        foreach ($qrCodeDirs as $qrCodeDir) {
            $qrCodeList = dol_dir_list($qrCodeDir, 'files');
            if (!is_array($qrCodeList) || empty($qrCodeList)) {
                continue;
            }
            $qrCode = array_shift($qrCodeList);
            if (is_readable($qrCode['fullname'])) {
                return $qrCode['fullname'];
            }
        }

        return '';
    }

    /**
     * Photo d'illustration de l'element, a droite du mini document unique.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  float     $x           Abscisse
     * @param  float     $y           Ordonnee
     * @param  float     $width       Largeur
     * @param  float     $height      Hauteur disponible
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return float                  Ordonnee du bas du bloc
     */
    protected function coverPhoto($pdf, $object, float $x, float $y, float $width, float $height, Translate $outputLangs, float $size): float
    {
        global $conf;

        $photoPath = '';
        if (!empty($object->photo) && !empty($object->element_type)) {
            $path      = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref;
            $photoPath = $path . '/thumbs/' . saturne_get_thumb_name($object->photo);
            if (!is_readable($photoPath)) {
                $photoPath = $path . '/' . $object->photo;
            }
        }

        if (!dol_strlen($photoPath) || !is_readable($photoPath)) {
            return $y;
        }

        // Boite imposee et fitbox : la photo garde ses proportions sans jamais depasser la hauteur
        // des deux autres colonnes, quel que soit son cadrage d'origine
        $boxHeight = max($this->height * 2, $height - $this->height);
        $pdf->Image($photoPath, $x, $y, $width, $boxHeight, '', '', '', false, 300, '', false, false, 1, 'CT');

        // La legende se pose sous l'image reellement dessinee, pas sous la boite : sinon elle
        // s'ecrit par dessus la photo des que celle-ci est moins haute que la place disponible
        $captionY = $pdf->getImageRBY();

        $pdf->SetFont('', 'I', $size - 2);
        $pdf->SetTextColor(140, 140, 140);
        $pdf->SetXY($x, $captionY);
        $pdf->MultiCell($width, $this->height, $outputLangs->transnoentities('ListingRisksCoverIllustration'), 0, 'C', false, 1);
        $pdf->SetTextColor(0, 0, 0);

        return $captionY + $this->height;
    }

    /**
     * Arborescence du perimetre : l'organigramme des GP et UT couverts par ce document.
     *
     * Le SICTOM a dessine a la main ce qu'il attend (issue #5287) : une seule boite quand le
     * document porte sur une unite de travail, la boite du groupement et son arbre quand il
     * porte sur un groupement. Les boites sont indentees par profondeur et reliees a leur
     * parent, ce qui tient quelle que soit la taille de l'arbre - leur GP38 compte 18 UT.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionPerimeterTree($pdf, $object, Translate $outputLangs, float $size)
    {
        $perimeter = $this->perimeterElements($object);
        if (empty($perimeter)) {
            return;
        }

        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksPerimeterTreeTitle'), $size);

        $depths = $this->perimeterDepths($perimeter);

        $indent     = 12;
        $boxHeight  = 7;
        $gap        = 2.5;
        $left       = $this->marge_gauche;
        $maxWidth   = $this->contentWidth($pdf) - 40;
        // Ordonnee du bas de la derniere boite dessinee a chaque profondeur : c'est de la que
        // part le trait vers l'enfant suivant
        $lastBottom = [];

        foreach ($perimeter as $elementId => $element) {
            $depth = $depths[$elementId] ?? 0;
            $this->checkPageBreak($pdf, $boxHeight + $gap);

            $x = $left + ($depth * $indent);
            $y = $pdf->GetY();
            $width = min($maxWidth - ($depth * $indent), 140);

            // Le trait qui relie la boite a son parent : une descente puis une equerre
            if ($depth > 0 && isset($lastBottom[$depth - 1])) {
                $parentX = $left + (($depth - 1) * $indent) + 4;
                $pdf->SetDrawColor(160, 160, 160);
                $pdf->Line($parentX, $lastBottom[$depth - 1], $parentX, $y + ($boxHeight / 2));
                $pdf->Line($parentX, $y + ($boxHeight / 2), $x, $y + ($boxHeight / 2));
                $pdf->SetDrawColor(190, 190, 190);
            }

            // Un groupement porte la couleur d'accent, une unite de travail reste claire
            $isGroup = !empty($element['object']->element_type) && $element['object']->element_type == 'groupment';
            if ($isGroup) {
                $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('', 'B', $size);
            } else {
                $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $size);
            }

            $label = $element['object']->ref . ' - ' . $element['object']->label;
            $pdf->SetXY($x, $y);
            $pdf->Cell($width, $boxHeight, ' ' . dol_trunc($label, 70), 1, 0, 'L', true);

            $pdf->SetTextColor(0, 0, 0);
            $lastBottom[$depth] = $y + $boxHeight;
            // Les profondeurs plus basses sont refermees : leur dernier trait ne doit pas
            // redescendre vers une boite qui n'est plus leur enfant
            foreach (array_keys($lastBottom) as $openDepth) {
                if ($openDepth > $depth) {
                    unset($lastBottom[$openDepth]);
                }
            }

            $pdf->SetY($y + $boxHeight + $gap);
        }

        $pdf->SetY($pdf->GetY() + 4);
        $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksPerimeterTreeLegend'), $size - 1, 'I', [140, 140, 140]);
    }

    /**
     * Profondeur de chaque element du perimetre, relative a la racine du document.
     *
     * fetchDigiriskElementFlat() ne remplit sa cle depth que pour une lecture depuis la
     * racine de l'etablissement : demandee sur un groupement, elle rend 0 partout et
     * l'arbre sort plat. La profondeur se retrouve en remontant fk_parent tant que le
     * parent appartient au perimetre.
     *
     * @param  array $perimeter Elements du perimetre, indexes par identifiant
     * @return array            Profondeur par identifiant
     */
    protected function perimeterDepths(array $perimeter): array
    {
        $depths = [];

        foreach (array_keys($perimeter) as $elementId) {
            $depth  = 0;
            $cursor = $elementId;
            // Garde-fou : une boucle dans les fk_parent ferait tourner la generation sans fin
            for ($step = 0; $step < 50; $step++) {
                $parent = (int) ($perimeter[$cursor]['object']->fk_parent ?? 0);
                if ($parent <= 0 || !isset($perimeter[$parent])) {
                    break;
                }
                $depth++;
                $cursor = $parent;
            }
            $depths[$elementId] = $depth;
        }

        return $depths;
    }

    /**
     * Lexique des abreviations employees dans le document.
     *
     * Le SICTOM en tient un a la main au bas de son DUERP (issue #5287). Seules les
     * abreviations de Digirisk y figurent : celles qui tiennent a l'organisation d'un client
     * n'ont rien a faire dans un document genere pour tous.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionGlossary($pdf, Translate $outputLangs, float $size)
    {
        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksGlossaryTitle'), $size);

        $rows = [];
        foreach (['GP', 'UT', 'RK', 'RA', 'COT', 'ATMP', 'TK', 'DUERP', 'PAPRIPACT'] as $abbreviation) {
            $rows[] = [
                ['text' => $outputLangs->transnoentities('ListingRisksGlossary' . $abbreviation . 'Short'), 'bold' => true],
                $outputLangs->transnoentities('ListingRisksGlossary' . $abbreviation)
            ];
        }

        $this->table($pdf, [$outputLangs->transnoentities('ListingRisksGlossaryAbbreviation'), $outputLangs->transnoentities('Description')], $rows, [40, 360], $size);
    }

    /**
     * Photo de l'element, sur sa propre page.
     *
     * Digirisk ne porte aucune notion de plan ni de coordonnees : le seul mecanisme
     * d'image est DigiriskElement::photo, deja affichee en petit sur la couverture. La
     * montrer en grand permet d'y mettre ce que le client veut - le SICTOM y place la
     * carte de son territoire. Decochee par defaut : le document ne doit pas gagner une
     * page chez tout le monde. Issue #5307
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $moreParam   More param (showElementPhoto)
     * @return void
     */
    protected function sectionElementPhoto($pdf, $object, Translate $outputLangs, float $size, array $moreParam)
    {
        global $conf;

        if (empty($moreParam['showElementPhoto']) || empty($object->photo) || empty($object->element_type)) {
            return;
        }

        // La photo pleine page merite l'original, pas la vignette de la couverture
        $photoPath = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref . '/' . $object->photo;
        if (!is_readable($photoPath)) {
            return;
        }

        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksElementPhotoTitle'), $size);

        $caption = 6;
        $top     = $pdf->GetY() + 2;
        $width   = $this->contentWidth($pdf);
        // getBreakMargin() porte deja la bande du pied de page, posee au setPageOrientation.
        // La legende est retranchee ici : sinon l'image prend toute la hauteur restante et la
        // legende, ecrite sous elle, part seule a la page suivante
        $height  = $pdf->getPageHeight() - $top - $pdf->getBreakMargin() - $caption;

        // fitbox CT : l'image garde ses proportions et se cale en haut, centree, sans jamais
        // deborder sur le pied de page quel que soit son cadrage
        $pdf->Image($photoPath, $this->marge_gauche, $top, $width, $height, '', '', '', false, 300, '', false, false, 0, 'CT');

        $pdf->SetY($top + $height);
        $this->paragraph($pdf, $this->coverElementLabel($object), $size - 1, 'I', [140, 140, 140]);
    }

    /**
     * Rappel reglementaire : definitions et principes generaux de prevention, comme le gabarit.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionLegalReminder($pdf, Translate $outputLangs, float $size)
    {
        $this->newPage($pdf);

        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksLegalTitle'), $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('RiskDefinitionTitle'), $size, 'B', $this->accent);
        $this->paragraph($pdf, $outputLangs->transnoentities('RiskDefinitionText'), $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('DangerDefinitionTitle'), $size, 'B', $this->accent);
        $this->paragraph($pdf, $outputLangs->transnoentities('DangerDefinitionText'), $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('SingleDocumentTitle'), $size, 'B', $this->accent);
        $this->paragraph($pdf, $outputLangs->transnoentities('SingleDocumentText'), $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('PreventionPrinciplesTitle'), $size, 'B', $this->accent);
        $this->paragraph($pdf, $outputLangs->transnoentities('PreventionPrinciplesIntro'), $size);

        for ($principle = 1; $principle <= 9; $principle++) {
            $this->paragraph($pdf, $principle . '. ' . $outputLangs->transnoentities('PreventionPrinciple' . $principle), $size);
        }

        $pdf->SetY($pdf->GetY() + 2);
        $this->preventionWheel($pdf, $outputLangs, $size);
    }

    /**
     * Roue des neuf principes generaux de prevention.
     *
     * Le module ne livre pas cette illustration - img/legalPicto ne contient qu'un badge
     * Creative Commons - alors elle se dessine : un anneau de neuf secteurs numerotes, chacun
     * legende par le verbe du principe. Elle accompagne la liste en toutes lettres qui la
     * precede, elle ne la remplace pas. Issue #5307
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function preventionWheel($pdf, Translate $outputLangs, float $size)
    {
        $outer = 38;
        $inner = 18;
        // Hauteur du bloc : le diametre, plus la place des legendes au dessus et en dessous
        $needed = ($outer * 2) + 24;
        $this->checkPageBreak($pdf, $needed);

        $centerX = $this->marge_gauche + ($this->contentWidth($pdf) / 2);
        $centerY = $pdf->GetY() + 12 + $outer;

        $step = 360 / 9;

        for ($principle = 1; $principle <= 9; $principle++) {
            $start = ($principle - 1) * $step;
            $color = $this->wheelColors[$principle - 1];

            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetLineWidth(0.6);
            // Les deux derniers arguments de PieSector() sont laisses a leur defaut, qui est
            // deja ce qu'on veut : origine a midi et sens horaire. Les passer ferait buter
            // PHPStan, le PHPDoc de TCPDF typant $cw en float alors qu'il vaut true
            $pdf->PieSector($centerX, $centerY, $outer, $start, $start + $step, 'F');
        }

        // Le disque blanc creuse l'anneau
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($centerX, $centerY, $inner, 0, 360, 'F');
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->SetLineWidth(0.2);

        $numberRadius = ($outer + $inner) / 2;
        $labelRadius  = $outer + 3;

        for ($principle = 1; $principle <= 9; $principle++) {
            $middle = (($principle - 1) * $step) + ($step / 2);
            $angle  = deg2rad($middle);

            // Repere du PDF : les ordonnees descendent, d'ou le sinus en abscisse et le
            // cosinus retranche en ordonnee pour tourner dans le sens horaire depuis midi
            $numberX = $centerX + ($numberRadius * sin($angle));
            $numberY = $centerY - ($numberRadius * cos($angle));

            $pdf->SetFont('', 'B', $size + 1);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($numberX - 5, $numberY - 2.5);
            $pdf->Cell(10, 5, (string) $principle, 0, 0, 'C');

            $labelX = $centerX + ($labelRadius * sin($angle));
            $labelY = $centerY - ($labelRadius * cos($angle));

            $pdf->SetFont('', '', $size - 2);
            $pdf->SetTextColor(60, 60, 60);
            // A droite de l'axe le texte part vers la droite, a gauche il s'y termine :
            // les legendes s'ecartent de la roue au lieu de lui passer dessus
            $onTheRight = (sin($angle) >= 0);
            $pdf->SetXY($onTheRight ? $labelX : $labelX - 48, $labelY - 3);
            $pdf->MultiCell(48, 3.5, $outputLangs->transnoentities('PreventionPrincipleShort' . $principle), 0, $onTheRight ? 'L' : 'R', false, 1);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($centerY + $outer + 8);
    }

    /**
     * Methode de calcul des cotations : la grille qui fait passer d'une note sur 100 au niveau
     * de risque affiche dans le listing.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionCotationMethod($pdf, Translate $outputLangs, float $size)
    {
        // Sa propre page : depuis que la roue occupe le bas du cadre reglementaire, les deux
        // grilles n y tiennent plus et la derniere ligne partait seule a la page suivante
        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksCotationMethodTitle'), $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksCotationMethodText'), $size);

        $header = [
            $outputLangs->transnoentities('ListingRisksCotationLevel'),
            $outputLangs->transnoentities('ListingRisksCotationRange'),
            $outputLangs->transnoentities('ListingRisksCotationMeaning'),
            $outputLangs->transnoentities('ListingRisksCotationAction')
        ];

        $rows = [];
        // Meme decoupage que Risk::$cotations et RiskAssessment::getEvaluationScale()
        foreach ([4 => [80, 100], 3 => [51, 79], 2 => [48, 50], 1 => [0, 47]] as $level => $range) {
            $rows[] = [
                ['text' => (string) $level, 'align' => 'C', 'bold' => true, 'fill' => $this->levelBg[$level], 'color' => $this->levelText[$level]],
                ['text' => $range[0] . ' - ' . $range[1], 'align' => 'C'],
                $outputLangs->transnoentities($this->levelLabelKey($level)),
                $outputLangs->transnoentities('ListingRisksCotationAction' . $level)
            ];
        }

        $this->table($pdf, $header, $rows, [25, 35, 90, 250], $size);

        $pdf->SetY($pdf->GetY() + 4);
        $this->sectionCotationCriteria($pdf, $outputLangs, $size);
    }

    /**
     * Grille des criteres de la methode avancee, et la formule qui en tire la cotation.
     *
     * Les libelles sont lus dans js/json/default.json, le fichier que la fiche de risque
     * utilise deja pour construire sa grille : recopier ces seuils ici les ferait diverger
     * silencieusement du jour ou le fichier change. Issue #5303
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionCotationCriteria($pdf, Translate $outputLangs, float $size)
    {
        $criteria = $this->advancedCotationCriteria();
        if (empty($criteria)) {
            return;
        }

        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksCriteriaTitle'), $size);
        $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksCriteriaText'), $size);

        // Les seuils vont de 0 a 4 : une colonne par niveau, plus celle du nom du critere
        $header = [$outputLangs->transnoentities('ListingRisksCriteriaColumn')];
        for ($level = 0; $level <= 4; $level++) {
            $header[] = (string) $level;
        }

        $rows = [];
        foreach ($criteria as $name => $levels) {
            // Le fichier ecrit les noms sans accent : la cle de langue existe pour Gravite,
            // les quatre autres noms sont deja leur propre cle
            $label = $outputLangs->transnoentities($name == 'Gravite' ? 'Gravity' : $name);
            $row   = [['text' => $label, 'bold' => true]];
            for ($level = 0; $level <= 4; $level++) {
                $row[] = $levels[$level] ?? '';
            }
            $rows[] = $row;
        }

        $this->table($pdf, $header, $rows, [40, 72, 72, 72, 72, 72], $size);
    }

    /**
     * Criteres de la methode avancee, lus dans le fichier livre par le module.
     *
     * @return array Par nom de critere, le libelle de chaque seuil
     */
    protected function advancedCotationCriteria(): array
    {
        $path = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json';
        if (!is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        // risk_variable porte les criteres du risque professionnel, riskenvironmental_variable
        // ceux du risque environnemental : ce document est le document unique
        $variables = $decoded[0]['option']['risk_variable'] ?? null;
        if (!is_array($variables)) {
            return [];
        }

        $criteria = [];
        foreach ($variables as $variable) {
            if (empty($variable['name'])) {
                continue;
            }
            $levels = [];
            foreach (($variable['option']['survey']['request'] ?? []) as $request) {
                // Un seuil vide signale un niveau que ce critere n'utilise pas
                if (!isset($request['seuil']) || !dol_strlen((string) $request['seuil'])) {
                    continue;
                }
                $levels[(int) $request['seuil']] = (string) ($request['question'] ?? '');
            }
            $criteria[$variable['name']] = $levels;
        }

        return $criteria;
    }

    /**
     * Cle de traduction du libelle d'un niveau de cotation.
     *
     * @param  int    $level Niveau de cotation
     * @return string        Cle de traduction
     */
    protected function levelLabelKey(int $level): string
    {
        $keys = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

        return $keys[$level] ?? 'GreyRisk';
    }

    /**
     * Liste des risques, un tableau par niveau de cotation, du plus grave au plus faible.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $moreParam   Object/user/etc
     * @return void
     */
    protected function sectionRisks($pdf, $object, Translate $outputLangs, float $size, array $moreParam)
    {
        $risk = new Risk($this->db);

        // Le perimetre du document est l'arborescence de l'element : les risques portes par un
        // element absent de cet arbre sont ignores, comme le fait le gabarit ODT
        $digiriskElements = $this->perimeterElements($object);

        $moreParam['filter'] = '';
        $riskInfos           = $risk->loadRiskInfos($moreParam);
        $riskLevels          = $riskInfos['current']['riskByRiskAssessmentLevels'] ?? [];
        $riskTasks           = $riskInfos['current']['riskTasks'] ?? [];

        $this->taskResponsibles = $this->loadTaskResponsibles($riskTasks);

        // Sans choix explicite la photo reste affichee : le formulaire n'est pas toujours poste
        $this->showPhoto = !isset($moreParam['showPhoto']) || !empty($moreParam['showPhoto']);
        $this->taskOptions = $this->resolveTaskOptions($moreParam);

        $header = [
            $outputLangs->transnoentities('ListingRisksElementColumn'),
            $outputLangs->transnoentities('ListingRisksRiskRefColumn'),
            $outputLangs->transnoentities('ListingRisksCotationColumn'),
            $outputLangs->transnoentities('ListingRisksRiskColumn'),
            $outputLangs->transnoentities('ListingRisksRiskDescriptionColumn'),
            $outputLangs->transnoentities('ListingRisksAssessmentColumn'),
            $outputLangs->transnoentities('ListingRisksActionPlan')
        ];
        $widths = [55, 32, 14, 40, 95, 70, 94];

        if ($this->showPhoto) {
            array_splice($header, 5, 0, [$outputLangs->transnoentities('Photo')]);
            $widths = [55, 32, 14, 40, 80, 32, 60, 87];
        }

        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksListTitle'), $size);

        $printedRisks = 0;
        for ($level = 4; $level >= 1; $level--) {
            $rows = [];
            foreach ($riskLevels[$level] ?? [] as $riskLine) {
                if (empty($digiriskElements[$riskLine->fk_element])) {
                    continue; // Risque hors du perimetre de l'element imprime
                }
                $rows[] = $this->riskRow($riskLine, $digiriskElements[$riskLine->fk_element], $riskTasks, $level, $outputLangs);
            }

            if (empty($rows)) {
                continue;
            }

            $printedRisks += count($rows);
            $this->levelTitle($pdf, $level, count($rows), $outputLangs, $size);
            $this->table($pdf, $header, $rows, $widths, $size);
        }

        if (empty($printedRisks)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('NoRiskAssessed'), $size, 'I', [140, 140, 140]);
        }
    }

    /**
     * Bandeau annoncant un niveau de cotation, aux couleurs de ce niveau.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  int       $level       Niveau de cotation
     * @param  int       $riskCount   Nombre de risques de ce niveau
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function levelTitle($pdf, int $level, int $riskCount, Translate $outputLangs, float $size)
    {
        $this->checkPageBreak($pdf, 18);

        $background = $this->levelBg[$level];
        $textColor  = $this->levelText[$level];

        $pdf->Ln(2);
        $pdf->SetFont('', 'B', $size);
        $pdf->SetFillColor($background[0], $background[1], $background[2]);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->contentWidth($pdf), 7, ' ' . $outputLangs->transnoentities($this->levelLabelKey($level)) . ' (' . $riskCount . ')', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    /**
     * Une ligne du tableau des risques.
     *
     * @param  object    $riskLine        Risque, complete par son evaluation
     * @param  array     $digiriskElement Element porteur ['object' => DigiriskElement, 'depth' => int]
     * @param  array     $riskTasks       Taches du plan d'action, indexees par identifiant de risque
     * @param  int       $level           Niveau de cotation
     * @param  Translate $outputLangs     Lang object
     * @return array                      Cellules de la ligne
     */
    protected function riskRow($riskLine, array $digiriskElement, array $riskTasks, int $level, Translate $outputLangs): array
    {
        $elementLabel = str_repeat('  ', (int) $digiriskElement['depth']) . $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label;

        $categoryName = getDolGlobalInt('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME') ? $riskLine->getDangerCategoryName($riskLine, $riskLine->type) : '';
        $pictoPath    = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $riskLine->getDangerCategory($riskLine, $riskLine->type) . '.png';

        $cells = [
            $elementLabel,
            $riskLine->ref . "\n" . $riskLine->riskAssessmentRef,
            ['text' => (string) ($riskLine->riskAssessmentCotation ?: 0), 'align' => 'C', 'bold' => true, 'fill' => $this->levelBg[$level], 'color' => $this->levelText[$level]],
            ['text' => $categoryName, 'align' => 'C', 'image' => is_readable($pictoPath) ? $pictoPath : ''],
            saturne_flatten_wysiwyg_blocks($riskLine->description, true),
            $this->riskAssessmentComment($riskLine, $outputLangs),
            $this->riskTasksText($riskLine->id, $riskTasks, $outputLangs)
        ];

        if ($this->showPhoto) {
            array_splice($cells, 5, 0, [['image' => $this->riskAssessmentPhotoPath($riskLine), 'text' => '']]);
        }

        return $cells;
    }

    /**
     * Photo de la derniere evaluation d'un risque.
     *
     * @param  object $riskLine Risque, complete par son evaluation
     * @return string           Chemin complet de la vignette, vide si absente
     */
    protected function riskAssessmentPhotoPath($riskLine): string
    {
        if (empty($riskLine->riskAssessmentPhoto)) {
            return '';
        }

        $entityPath = $riskLine->entity != 1 ? '/' . $riskLine->entity : '';
        $path       = DOL_DATA_ROOT . $entityPath . '/digiriskdolibarr/riskassessment/' . $riskLine->riskAssessmentRef;
        $photoPath  = $path . '/thumbs/' . saturne_get_thumb_name($riskLine->riskAssessmentPhoto);

        return is_readable($photoPath) ? $photoPath : '';
    }

    /**
     * Informations sur l'evaluation d'un risque : date et commentaire.
     *
     * @param  object    $riskLine    Risque, complete par son evaluation
     * @param  Translate $outputLangs Lang object
     * @return string                 Texte de la cellule
     */
    protected function riskAssessmentComment($riskLine, Translate $outputLangs): string
    {
        $comment = '';

        if (!getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT')) {
            $date = (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($riskLine->riskAssessmentDate)) ? $riskLine->riskAssessmentDate : $riskLine->riskAssessmentDateCreation;
            if (!empty($date)) {
                $comment = dol_print_date($date, 'dayreduceformat', 'tzuser', $outputLangs) . ' : ';
            }
        }

        return $comment . saturne_flatten_wysiwyg_blocks($riskLine->riskAssessmentComment, true);
    }

    /**
     * Responsables des taches du plan d'action, charges en une seule requete.
     *
     * liste_contact() interroge la base tache par tache : sur un listing qui en compte
     * plusieurs centaines, la meme information couterait autant de requetes.
     *
     * @param  array $riskTasks Taches, indexees par identifiant de risque
     * @return array            Noms des responsables, indexes par identifiant de tache
     */
    protected function loadTaskResponsibles(array $riskTasks): array
    {
        $taskIds = [];
        foreach ($riskTasks as $tasks) {
            foreach ($tasks as $task) {
                $taskIds[(int) $task->id] = (int) $task->id;
            }
        }

        if (empty($taskIds)) {
            return [];
        }

        $sql  = 'SELECT ec.element_id, u.firstname, u.lastname FROM ' . MAIN_DB_PREFIX . 'element_contact as ec';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'user as u ON ec.fk_socpeople = u.rowid';
        $sql .= ' WHERE ec.element_id IN (' . implode(',', $taskIds) . ')';
        $sql .= " AND tc.element = 'project_task' AND tc.source = 'internal' AND tc.code = 'TASKEXECUTIVE'";

        $responsibles = [];
        $resql        = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $responsibles[(int) $obj->element_id][] = trim($obj->firstname . ' ' . $obj->lastname);
            }
            $this->db->free($resql);
        }

        return $responsibles;
    }

    /**
     * Options de masquage du plan d'actions pour cette generation.
     *
     * Elles ne valaient que globalement, par la configuration du module. Le formulaire de
     * generation peut desormais les choisir au coup par coup : ce qu'il poste l'emporte,
     * et sans lui la configuration du module continue de decider - un document genere
     * depuis ailleurs sort donc exactement comme avant. Issue #5289
     *
     * @param  array $moreParam More param
     * @return array            Par cle d'option, 1 pour masquer
     */
    protected function resolveTaskOptions(array $moreParam): array
    {
        $options = [];
        foreach ([
            'taskHideRef' => 'DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT',
            'taskHideResponsible' => 'DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT',
            'taskHideDate' => 'DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT',
            'taskHideBudget' => 'DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT',
        ] as $key => $constant) {
            $options[$key] = isset($moreParam[$key]) ? (int) $moreParam[$key] : getDolGlobalInt($constant);
        }

        return $options;
    }

    /**
     * Plan d'actions d'un risque, scinde entre actions en cours et actions terminees.
     *
     * Le SICTOM lit le listing bloc par bloc : une liste unique ne disait pas ou en etait
     * chaque action. Un bloc sans action n'est pas rendu du tout - la maquette rayait les
     * « N/A » qui occupaient la place sans rien apprendre. Issue #5299
     *
     * @param  int       $riskId      Identifiant du risque
     * @param  array     $riskTasks   Taches indexees par risque
     * @param  Translate $outputLangs Lang object
     * @return string                 Texte de la cellule, vide si le risque n'a aucune action
     */
    protected function riskTasksText(int $riskId, array $riskTasks, Translate $outputLangs): string
    {
        global $conf;

        $options = $this->taskOptions ?: $this->resolveTaskOptions([]);

        if (empty($riskTasks[$riskId])) {
            return '';
        }

        $inProgress = [];
        $done       = [];

        foreach ($riskTasks[$riskId] as $riskTask) {
            $progress = $riskTask->progress;
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS')) {
                $timeSpents = $riskTask->getSummaryOfTimeSpent();
                if ($timeSpents['total_duration'] > 0 && !empty($riskTask->planned_workload)) {
                    $progress = round($timeSpents['total_duration'] / $riskTask->planned_workload * 100, 2);
                }
            }

            $isDone = ($progress >= 100);

            // Les actions soldees peuvent etre masquees : le bloc le dit alors une fois,
            // au lieu de repeter la meme phrase par action
            if ($isDone && !getDolGlobalInt('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE')) {
                $done[0] = $outputLangs->transnoentities('ActionPreventionCompletedTaskDone');
                continue;
            }

            $line = $riskTask->label;
            // Une tache sans reference garde son seul libelle, sans le tiret de separation
            if (!$options['taskHideRef'] && !empty($riskTask->ref)) {
                $line = $riskTask->ref . ' - ' . $line;
            }
            if (!$options['taskHideResponsible'] && !empty($this->taskResponsibles[$riskTask->id])) {
                $line .= "\n" . $outputLangs->transnoentities('Responsible') . ' : ' . implode(', ', $this->taskResponsibles[$riskTask->id]);
            }
            if (!$options['taskHideDate']) {
                $startDate = (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE') && !empty($riskTask->dateo)) ? $riskTask->dateo : $riskTask->datec;
                $line     .= "\n" . $outputLangs->transnoentities('DateStart') . ' : ' . dol_print_date($startDate, 'dayreduceformat', 'tzuser', $outputLangs);
                if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE') && !empty($riskTask->datee)) {
                    $line .= ' - ' . $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($riskTask->datee, 'dayreduceformat', 'tzuser', $outputLangs);
                }
            }
            if (!$options['taskHideBudget']) {
                $line .= "\n" . $outputLangs->transnoentities('Budget') . ' : ' . price($riskTask->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency);
            }

            // Une action terminee porte son avancement dans son bloc : le repeter n'apprend rien
            if (!$isDone) {
                $line .= ($options['taskHideBudget'] ? "\n" : ' - ') . $outputLangs->transnoentities('DigiriskProgress') . ' : ' . ($progress ?: 0) . ' %';
            }

            if ($isDone) {
                $done[] = $line;
            } else {
                $inProgress[] = $line;
            }
        }

        $blocks = [];
        if (!empty($inProgress)) {
            $blocks[] = $outputLangs->transnoentities('ListingRisksActionsInProgress') . " :\n" . implode("\n\n", $inProgress);
        }
        if (!empty($done)) {
            $blocks[] = $outputLangs->transnoentities('ListingRisksActionsDone') . " :\n" . implode("\n\n", $done);
        }

        return implode("\n\n", $blocks);
    }

    /**
     * Registres sante securite rattaches au perimetre du document - issue #5235
     *
     * Les registres sont les tickets du projet de declaration. Un ticket porte ses GP/UT dans
     * l'extrafield digiriskdolibarr_ticket_service, un chkbxlst : la liste des identifiants y
     * est stockee en texte, donc le rattachement se resout en PHP et non par une jointure, qui
     * perdrait les tickets rattaches a plusieurs GP/UT.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $moreParam   More param (showRegisters, registerDateStart, registerDateEnd)
     * @return void
     */
    protected function sectionRegisters($pdf, $object, Translate $outputLangs, float $size, array $moreParam)
    {
        if (empty($moreParam['showRegisters'])) {
            return;
        }

        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

        $tickets = $this->registersInPerimeter($object, $moreParam);

        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksRegistersTitle'), $size);

        $period = $this->periodLabel($moreParam['registerDateStart'] ?? 0, $moreParam['registerDateEnd'] ?? 0, $outputLangs);
        if (dol_strlen($period)) {
            $this->paragraph($pdf, $period, $size, 'I');
        }

        if (empty($tickets)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksNoRegister'), $size, 'I', [140, 140, 140]);
            return;
        }

        $header = [
            $outputLangs->transnoentities('ListingRisksRegisterRefColumn'),
            $outputLangs->transnoentities('ListingRisksElementColumn'),
            $outputLangs->transnoentities('Categories'),
            $outputLangs->transnoentities('ListingRisksRegisterDeclarationDate'),
            $outputLangs->transnoentities('TicketMessage'),
            $outputLangs->transnoentities('Status')
        ];
        $widths = [32, 66, 52, 40, 172, 38];

        $rows = [];
        foreach ($tickets as $ticket) {
            $rows[] = [
                $ticket->ref,
                $ticket->digiriskElementRefLabel ?? '',
                $this->registerCategories($ticket),
                ['text' => dol_print_date($ticket->datec, 'dayreduceformat', 'tzuser', $outputLangs), 'align' => 'C'],
                dol_string_nohtmltag($ticket->message),
                ['text' => $ticket->getLibStatut(), 'align' => 'C']
            ];
        }

        $this->table($pdf, $header, $rows, $widths, $size);
    }

    /**
     * Registres du perimetre du document, sur la plage de dates demandee.
     *
     * @param  object $object    Element imprime
     * @param  array  $moreParam More param (registerDateStart, registerDateEnd)
     * @return array             Tickets, completes de leur GP/UT et de leur statut
     */
    protected function registersInPerimeter($object, array $moreParam): array
    {
        // dol_print_date() plutot que DoliDB::idate(), qui n'en est qu'une enveloppe : la methode
        // n'est pas resolue par PHPStan, qui ne voit que la classe abstraite DoliDB, et chaque
        // nouvel appel grossirait la baseline
        $filter = '';
        if (!empty($moreParam['registerDateStart'])) {
            $filter .= " AND t.datec >= '" . dol_print_date($moreParam['registerDateStart'], '%Y-%m-%d %H:%M:%S', 'tzserver') . "'";
        }
        if (!empty($moreParam['registerDateEnd'])) {
            $filter .= " AND t.datec <= '" . dol_print_date($moreParam['registerDateEnd'], '%Y-%m-%d %H:%M:%S', 'tzserver') . "'";
        }

        $ticketInfos = load_ticket_infos(['filterTicket' => $filter]);
        $tickets     = $ticketInfos['tickets'];

        // Le listing de l'etablissement porte sur toute la societe : un registre declare sans
        // GP/UT y a sa place, alors qu'il n'appartient a l'arbre d'aucun element
        if ($object->element == 'digiriskstandard') {
            return $tickets;
        }

        $perimeter = $this->perimeterElements($object);

        return array_filter($tickets, function ($ticket) use ($perimeter) {
            foreach (digiriskdolibarr_ticket_service_ids($ticket) as $elementId) {
                if (isset($perimeter[$elementId])) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Categories d'un registre, restreintes a celles retenues dans la configuration du module.
     *
     * @param  object $ticket Ticket
     * @return string         Libelles separes par une virgule
     */
    protected function registerCategories($ticket): string
    {
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        static $category         = null;
        static $allowedCategoryIds = null;

        if ($category === null) {
            $category           = new Categorie($this->db);
            $allowedCategoryIds = array_filter(array_map('intval', explode(',', getDolGlobalString('DIGIRISKDOLIBARR_TICKET_DOCUMENT_CATEGORIES'))));
        }

        $categories = $category->containing($ticket->id, Categorie::TYPE_TICKET);
        if (!is_array($categories)) {
            return '';
        }
        if (!empty($allowedCategoryIds)) {
            $categories = array_filter($categories, fn($cat) => in_array((int) $cat->id, $allowedCategoryIds, true));
        }

        return implode(', ', array_map(fn($cat) => $cat->label, $categories));
    }

    /**
     * Libelle de la plage de dates d'une section, vide quand elle n'est pas bornee.
     *
     * Les bornes sont imprimees dans le fuseau du serveur, celui dans lequel dol_mktime() les
     * a construites : en fuseau utilisateur, une fin de journee a 23:59:59 bascule au lendemain
     * et le document annoncerait un jour de plus qu'il n'en couvre.
     *
     * @param  int       $dateStart   Debut de la plage
     * @param  int       $dateEnd     Fin de la plage
     * @param  Translate $outputLangs Lang object
     * @return string                 Libelle a imprimer sous le titre de la section
     */
    protected function periodLabel(int $dateStart, int $dateEnd, Translate $outputLangs): string
    {
        if (empty($dateStart) && empty($dateEnd)) {
            return '';
        }

        if (empty($dateStart)) {
            return $outputLangs->transnoentities('ListingRisksPeriodUntil', dol_print_date($dateEnd, 'day', 'tzserver', $outputLangs));
        }
        if (empty($dateEnd)) {
            return $outputLangs->transnoentities('ListingRisksPeriodFrom', dol_print_date($dateStart, 'day', 'tzserver', $outputLangs));
        }

        return $outputLangs->transnoentities('ListingRisksPeriodRange', dol_print_date($dateStart, 'day', 'tzserver', $outputLangs), dol_print_date($dateEnd, 'day', 'tzserver', $outputLangs));
    }

    /**
     * Accidents rattaches au perimetre du document - issue #5235
     *
     * Un accident porte son GP/UT dans fk_element, directement : le filtrage se fait donc sur
     * la colonne, contrairement aux registres. Les jours d'arret sont ramenes en une requete
     * pour tous les accidents affiches, plutot qu'un fetchAll par ligne.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Element imprime
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  array     $moreParam   More param (showAccidents, accidentDateStart, accidentDateEnd)
     * @return void
     */
    protected function sectionAccidents($pdf, $object, Translate $outputLangs, float $size, array $moreParam)
    {
        if (empty($moreParam['showAccidents'])) {
            return;
        }

        require_once __DIR__ . '/../../../../../class/accident.class.php';

        $accidents = $this->accidentsInPerimeter($object, $moreParam);

        $this->newPage($pdf);
        $this->sectionTitle($pdf, $outputLangs->transnoentities('ListingRisksAccidentsTitle'), $size);

        $period = $this->periodLabel($moreParam['accidentDateStart'] ?? 0, $moreParam['accidentDateEnd'] ?? 0, $outputLangs);
        if (dol_strlen($period)) {
            $this->paragraph($pdf, $period, $size, 'I');
        }

        if (empty($accidents)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksNoAccident'), $size, 'I', [140, 140, 140]);
            return;
        }

        $header = [
            $outputLangs->transnoentities('ListingRisksAccidentRefColumn'),
            $outputLangs->transnoentities('ListingRisksAccidentWorkStopColumn'),
            $outputLangs->transnoentities('ListingRisksAccidentElementColumn'),
            $outputLangs->transnoentities('LesionLocalization'),
            $outputLangs->transnoentities('LesionNature'),
            $outputLangs->transnoentities('ListingRisksAccidentLabelColumn')
        ];
        $widths = [32, 26, 62, 60, 60, 160];

        $perimeter = $this->perimeterElements($object);
        $extraData = $this->accidentExtraData(array_keys($accidents));

        $rows = [];
        foreach ($accidents as $accident) {
            $elementLabel = '';
            if (!empty($perimeter[$accident->fk_element])) {
                $elementLabel = $perimeter[$accident->fk_element]['object']->ref . ' - ' . $perimeter[$accident->fk_element]['object']->label;
            }

            $rows[] = [
                $accident->ref,
                ['text' => empty($extraData[$accident->id]['days']) ? '' : (int) $extraData[$accident->id]['days'], 'align' => 'C'],
                $elementLabel,
                $extraData[$accident->id]['lesionLocalization'] ?? '',
                $extraData[$accident->id]['lesionNature'] ?? '',
                $accident->label
            ];
        }

        $this->table($pdf, $header, $rows, $widths, $size);

        $this->paragraph($pdf, $outputLangs->transnoentities('ListingRisksAccidentWorkStopNote'), $size - 1, 'I', [140, 140, 140]);
    }

    /**
     * Accidents du perimetre du document, sur la plage de dates demandee.
     *
     * @param  object $object    Element imprime
     * @param  array  $moreParam More param (accidentDateStart, accidentDateEnd)
     * @return array             Accidents indexes par identifiant
     */
    protected function accidentsInPerimeter($object, array $moreParam): array
    {
        $accident = new Accident($this->db);

        $filter = 't.status >= ' . Accident::STATUS_VALIDATED;
        if (!empty($moreParam['accidentDateStart'])) {
            $filter .= " AND t.accident_date >= '" . dol_print_date($moreParam['accidentDateStart'], '%Y-%m-%d %H:%M:%S', 'tzserver') . "'";
        }
        if (!empty($moreParam['accidentDateEnd'])) {
            $filter .= " AND t.accident_date <= '" . dol_print_date($moreParam['accidentDateEnd'], '%Y-%m-%d %H:%M:%S', 'tzserver') . "'";
        }

        // Le listing d'un element est borne a son arbre, celui de l'etablissement porte sur toute
        // la societe : un accident sans GP/UT y a sa place
        if ($object->element != 'digiriskstandard') {
            $perimeter = $this->perimeterElements($object);
            if (empty($perimeter)) {
                return [];
            }
            // Les cles du perimetre sont des identifiants, convertis en entiers : la liste est
            // sure sans passer par sanitize()
            $filter .= ' AND t.fk_element IN (' . implode(',', array_map('intval', array_keys($perimeter))) . ')';
        }

        $accidents = $accident->fetchAll('DESC', 't.accident_date', 0, 0, ['customsql' => $filter]);

        return is_array($accidents) ? $accidents : [];
    }

    /**
     * Jours d'arret et lesions des accidents, en deux requetes plutot qu'une.
     *
     * Un accident porte plusieurs arrets et plusieurs lesions : les joindre dans la meme
     * requete multiplierait les lignes et fausserait la somme des jours. Les lesions sont
     * donc lues a part, puis regroupees en PHP - ni le module ni le socle n'utilisent
     * GROUP_CONCAT, qui ne serait de toute facon pas portable hors MySQL.
     *
     * @param  array $accidentIds Identifiants des accidents
     * @return array              Par identifiant : days, lesionLocalization, lesionNature
     */
    protected function accidentExtraData(array $accidentIds): array
    {
        if (empty($accidentIds)) {
            return [];
        }

        $ids       = implode(',', array_map('intval', $accidentIds));
        $extraData = [];

        $sql  = 'SELECT ws.fk_accident, SUM(ws.workstop_days) as days';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_workstop as ws';
        $sql .= ' WHERE ws.fk_accident IN (' . $ids . ')';
        $sql .= ' GROUP BY ws.fk_accident';

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $extraData[(int) $obj->fk_accident]['days'] = (int) $obj->days;
            }
            $this->db->free($resql);
        }

        $sql  = 'SELECT l.fk_accident, l.lesion_localization, l.lesion_nature';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_accident_lesion as l';
        $sql .= ' WHERE l.fk_accident IN (' . $ids . ')';
        $sql .= ' ORDER BY l.rowid';

        $localizations = [];
        $natures       = [];
        $resql         = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $accidentId = (int) $obj->fk_accident;
                if (dol_strlen($obj->lesion_localization)) {
                    $localizations[$accidentId][] = $obj->lesion_localization;
                }
                if (dol_strlen($obj->lesion_nature)) {
                    $natures[$accidentId][] = $obj->lesion_nature;
                }
            }
            $this->db->free($resql);
        }

        foreach ($accidentIds as $accidentId) {
            $accidentId = (int) $accidentId;
            $extraData[$accidentId]['lesionLocalization'] = implode(', ', $localizations[$accidentId] ?? []);
            $extraData[$accidentId]['lesionNature']       = implode(', ', $natures[$accidentId] ?? []);
        }

        return $extraData;
    }
}
