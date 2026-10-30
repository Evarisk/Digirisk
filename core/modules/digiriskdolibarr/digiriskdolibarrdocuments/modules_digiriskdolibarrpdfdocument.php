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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/modules_digiriskdolibarrpdfdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Classe parente des modeles PDF natifs batis sur les chiffres du document unique.
 *
 *          Deux documents en heritent : le mode d'emploi du DU et la presentation CSE. Ils lisent les
 *          memes donnees - les risques valides de l'entite, leur cotation, le plan d'actions, le
 *          perimetre RPS - et partagent la meme palette et les memes primitives de dessin, seule
 *          leur mise en page differe. loadDigiriskStatistics() est le point d'entree de ces
 *          donnees : tout y est agrege une seule fois, les modeles ne requetent plus.
 *
 *          Parti pris graphique : pas de bandeau plein ni de quadrillage. Les titres sont poses
 *          par la typographie et un court trait d'accent, les tableaux ne gardent que leurs
 *          filets horizontaux, les cartes et les pastilles ont des angles arrondis. Les couleurs
 *          de cotation restent celles du module : elles portent du sens, pas de la decoration.
 */

// Load Saturne libraries
dol_include_once('/saturne/core/modules/saturne/modules_saturne.php');
dol_include_once('/saturne/lib/dolibarr.lib.php');

/**
 * Classe parente des modeles PDF du document unique.
 */
abstract class ModelePDFDigiriskDolibarrDocument extends SaturneDocumentModel
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
     * @var string Orientation de la page, 'P' portrait ou 'L' paysage
     */
    protected string $orientation = 'P';

    /**
     * @var array Bleu Digirisk #0067A6, couleur principale des documents
     */
    protected array $accent = [0, 103, 166];

    /**
     * @var array Vert Digirisk, couleur secondaire
     */
    protected array $accentAlt = [20, 148, 132];

    /**
     * @var array Rouge des alertes
     */
    protected array $accentWarn = [214, 69, 65];

    /**
     * @var array Encre des titres et des nombres
     */
    protected array $ink = [23, 32, 43];

    /**
     * @var array Encre du texte courant
     */
    protected array $inkSoft = [84, 96, 108];

    /**
     * @var array Encre des libelles secondaires et des entetes de colonne
     */
    protected array $inkMuted = [138, 148, 160];

    /**
     * @var array Gris des filets
     */
    protected array $rule = [226, 231, 236];

    /**
     * @var array Fond des cartes et des lignes paires
     */
    protected array $surface = [247, 249, 251];

    /**
     * @var array Couleur pleine de chaque niveau de cotation, celles de Risk::$cotations
     */
    protected array $levelBg = [
        1 => [214, 221, 228],
        2 => [240, 169, 55],
        3 => [214, 69, 65],
        4 => [38, 42, 48]
    ];

    /**
     * @var array Couleur du texte ecrit sur la couleur pleine du niveau
     */
    protected array $levelText = [
        1 => [55, 66, 78],
        2 => [60, 38, 0],
        3 => [255, 255, 255],
        4 => [255, 255, 255]
    ];

    /**
     * @var array Encre d'une pastille douce de niveau, sur le fond teinte correspondant
     */
    protected array $levelSoftText = [
        1 => [74, 85, 96],
        2 => [142, 92, 0],
        3 => [168, 36, 33],
        4 => [38, 42, 48]
    ];

    /**
     * Bande basse reservee au pied de page, en mm. Le contenu s'arrete au dessus, le pied
     * s'ecrit dedans : c'est ce qui garantit qu'ils ne se chevauchent jamais.
     */
    const FOOTER_BAND = 16;

    /**
     * Marge interne horizontale d'une cellule de tableau, en mm. Sans bordure verticale, c'est
     * elle qui separe les colonnes : la reduire recolle les textes.
     */
    const CELL_PAD = 2.4;

    /**
     * Air ajoute au dessus et au dessous du texte d'une ligne de tableau, en mm.
     */
    const ROW_PAD = 3.2;

    /**
     * Rayon des angles arrondis des cartes, en mm.
     */
    const CARD_RADIUS = 2.4;

    /**
     * Marge interne des cartes, en mm.
     */
    const CARD_PAD = 5;

    /**
     * Hauteur d'une ligne de texte courant, en mm.
     */
    const LINE = 4.5;

    /**
     * Hauteur maximale, en mm, d'un picto dessine dans une cellule de tableau.
     */
    const MAX_IMAGE_HEIGHT = 9;

    /**
     * @var array Couleurs des familles de danger, reprises de la roue des principes de
     *            prevention. Elles tournent : le referentiel compte plus de familles que de
     *            couleurs, et deux familles voisines dans un diagramme restent distinctes.
     */
    protected array $categoryColors = [
        [214, 69, 65],
        [232, 125, 55],
        [240, 176, 60],
        [120, 166, 70],
        [20, 148, 132],
        [61, 158, 160],
        [0, 103, 166],
        [110, 92, 165],
        [186, 84, 140]
    ];

    /**
     * @var string Texte de gauche du pied de page
     */
    protected string $footerText = '';

    /**
     * @var float Taille de police du pied de page
     */
    protected float $footerFontSize = 7.5;

    /**
     * @var int Premiere page a recevoir un pied de page. La couverture d'une presentation ne se
     *          numerote pas, elle la fait commencer a 2.
     */
    protected int $footerFirstPage = 1;

    /**
     * Constructor.
     *
     * @param  DoliDB $db                  Database handler
     * @param  string $moduleNameLowerCase Module name
     * @param  string $objectDocumentType  Object document type
     */
    public function __construct(DoliDB $db, string $moduleNameLowerCase = 'digiriskdolibarr', string $objectDocumentType = 'riskassessmentdocument')
    {
        parent::__construct($db, $moduleNameLowerCase, $objectDocumentType);

        $this->type    = 'pdf';
        $this->height  = 5;
        $this->version = '1.0.0';
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
     * Eclaircit une couleur vers le blanc.
     *
     * Les fonds teintes sont calcules plutot que fixes : une pastille douce reste ainsi liee a
     * la couleur pleine de son niveau, meme si cette derniere change.
     *
     * @param  array $color Couleur de depart
     * @param  float $ratio Part de blanc, de 0 a 1
     * @return array        Couleur eclaircie
     */
    protected function tint(array $color, float $ratio): array
    {
        return [
            (int) round($color[0] + ((255 - $color[0]) * $ratio)),
            (int) round($color[1] + ((255 - $color[1]) * $ratio)),
            (int) round($color[2] + ((255 - $color[2]) * $ratio))
        ];
    }

    /**
     * Ouvre une nouvelle page.
     *
     * Le pied n'est pas pose ici : TCPDF ouvre aussi des pages tout seul quand un texte long
     * depasse, et ces pages la ne passeraient jamais par cette methode. Il est ecrit en une passe
     * finale sur toutes les pages, par writeFooter().
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
     * sinon. A utiliser avant tout bloc dessine a coordonnees imposees : sans cela le bloc
     * ecrit par dessus le pied de page.
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
     * Filet horizontal fin, sur toute la largeur utile.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  float $y     Ordonnee
     * @param  float $width Epaisseur du trait
     * @param  array $color Couleur, le gris des filets par defaut
     * @return void
     */
    protected function hairline($pdf, float $y, float $width = 0.2, array $color = [])
    {
        $color = $color ?: $this->rule;

        $pdf->SetDrawColor($color[0], $color[1], $color[2]);
        $pdf->SetLineWidth($width);
        $pdf->Line($this->marge_gauche, $y, $pdf->GetPageWidth() - $this->marge_droite, $y);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
    }

    /**
     * Carte a angles arrondis, sans bordure.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $x      Abscisse
     * @param  float $y      Ordonnee
     * @param  float $width  Largeur
     * @param  float $height Hauteur
     * @param  array $fill   Couleur de fond
     * @param  float $radius Rayon des angles
     * @return void
     */
    protected function card($pdf, float $x, float $y, float $width, float $height, array $fill, float $radius = self::CARD_RADIUS)
    {
        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $pdf->RoundedRect($x, $y, $width, $height, $radius, '1111', 'F');
    }

    /**
     * Pastille arrondie portant un texte court : une cotation, un niveau de risque.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  float  $x      Abscisse
     * @param  float  $y      Ordonnee
     * @param  float  $width  Largeur
     * @param  float  $height Hauteur
     * @param  string $text   Texte
     * @param  array  $fill   Couleur de fond
     * @param  array  $ink    Couleur du texte
     * @param  float  $size   Taille de police
     * @return void
     */
    protected function pill($pdf, float $x, float $y, float $width, float $height, string $text, array $fill, array $ink, float $size)
    {
        $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
        $pdf->RoundedRect($x, $y, $width, $height, min($height / 2, 2.8), '1111', 'F');

        $pdf->SetFont('', 'B', $size);
        $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, $height, $text, 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Surtitre : libelle court en capitales espacees, au dessus d'un titre.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  float  $x     Abscisse
     * @param  float  $y     Ordonnee
     * @param  float  $width Largeur
     * @param  string $text  Texte
     * @param  float  $size  Taille de police
     * @param  array  $color Couleur, l'encre secondaire par defaut
     * @return void
     */
    protected function kicker($pdf, float $x, float $y, float $width, string $text, float $size, array $color = [])
    {
        $color = $color ?: $this->inkMuted;

        $pdf->SetFont('', 'B', $size - 2.5);
        $pdf->setFontSpacing(0.6);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY($x, $y);
        $pdf->Cell($width, 4.5, dol_strtoupper($text), 0, 0, 'L');
        $pdf->setFontSpacing(0);
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Titre de section : un court trait d'accent, le titre, un filet.
     *
     * Le bandeau plein d'avant pesait sur chaque page ; ici c'est la typographie qui hierarchise
     * et le trait de couleur qui signale le debut de section.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  string $title  Titre
     * @param  float  $size   Taille de police
     * @param  float  $needed Hauteur a reserver sous le titre, pour qu'il n'ouvre pas une
     *                        section en bas de page avec son contenu en page suivante
     * @return void
     */
    protected function sectionTitle($pdf, string $title, float $size, float $needed = 42)
    {
        $this->checkPageBreak($pdf, $needed);

        $pdf->Ln(4);
        $y = $pdf->GetY();

        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->RoundedRect($this->marge_gauche, $y, 11, 1.3, 0.65, '1111', 'F');

        $pdf->SetFont('', 'B', $size + 3);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($this->marge_gauche, $y + 3.4);
        $pdf->Cell($this->contentWidth($pdf), 7, $title, 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);

        $this->hairline($pdf, $pdf->GetY() + 0.5);
        $pdf->SetXY($this->marge_gauche, $pdf->GetY() + 3);
    }

    /**
     * Paragraphe de texte courant.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $text  Texte
     * @param  float  $size  Taille de police
     * @param  string $style Style de police
     * @param  array  $color Couleur du texte, l'encre courante par defaut
     * @return void
     */
    protected function paragraph($pdf, string $text, float $size, string $style = '', array $color = [])
    {
        if (!dol_strlen($text)) {
            return;
        }

        $color  = $color ?: $this->inkSoft;
        $height = $pdf->getNumLines($text, $this->contentWidth($pdf)) * $this->height;
        $this->checkPageBreak($pdf, $height);

        $pdf->SetFont('', $style, $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($this->contentWidth($pdf), $this->height, $text, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1.5);
    }

    /**
     * Tableau sans quadrillage : entete en capitales, lignes separees par un filet, une ligne
     * sur deux posee sur un fond tres clair.
     *
     * $rows est une liste de lignes, chaque ligne une liste de cellules. Une cellule est une
     * chaine ou ['text' => string, 'align' => 'L|C|R', 'bold' => bool, 'color' => [r, g, b]],
     * ou encore ['pill' => true, 'text' => string, 'fill' => [r, g, b], 'color' => [r, g, b]]
     * pour une pastille arrondie centree dans sa colonne.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $header Entetes, chaines ou ['text' => string, 'align' => string]
     * @param  array $rows   Lignes
     * @param  array $widths Largeurs de colonnes
     * @param  float $size   Taille de police
     * @param  bool  $zebra  Poser un fond clair une ligne sur deux
     * @return void
     */
    protected function table($pdf, array $header, array $rows, array $widths, float $size, bool $zebra = true)
    {
        if (empty($rows)) {
            return;
        }

        $textSize   = $size - 1;
        $totalWidth = array_sum($widths);

        if (!empty($header)) {
            $this->drawTableHeader($pdf, $header, $widths, $size);
        }

        $index = 0;
        foreach ($rows as $cells) {
            // La ligne prend la hauteur de sa cellule la plus haute, mesuree police posee :
            // getNumLines() rend un nombre de lignes pour la police courante
            $maxHeight = $this->height + self::ROW_PAD;
            foreach ($cells as $column => $cellData) {
                if (!isset($widths[$column]) || (is_array($cellData) && !empty($cellData['pill']))) {
                    continue;
                }
                if (is_array($cellData) && isset($cellData['image'])) {
                    $maxHeight = max($maxHeight, self::MAX_IMAGE_HEIGHT + self::ROW_PAD);
                    continue;
                }
                $text = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                $pdf->SetFont('', (is_array($cellData) && !empty($cellData['bold'])) ? 'B' : '', $textSize);
                $maxHeight = max($maxHeight, ($pdf->getNumLines((string) $text, $widths[$column] - (2 * self::CELL_PAD)) * $this->height) + self::ROW_PAD);
            }

            // Une ligne plus haute que la page ne tiendra nulle part : la laisser deborder plutot
            // que d'ouvrir indefiniment des pages devant elle
            $this->checkPageBreak($pdf, $maxHeight);
            if (!empty($header) && $pdf->GetY() <= $this->marge_haute + 1) {
                $this->drawTableHeader($pdf, $header, $widths, $size);
            }

            $y = $pdf->GetY();

            if ($zebra && ($index % 2) === 1) {
                $this->card($pdf, $this->marge_gauche, $y, $totalWidth, $maxHeight, $this->surface, 1.2);
            }

            $x = $this->marge_gauche;
            foreach ($cells as $column => $cellData) {
                if (!isset($widths[$column])) {
                    continue;
                }

                $isArray = is_array($cellData);

                if ($isArray && !empty($cellData['pill'])) {
                    $this->drawCellPill($pdf, $x, $y, $widths[$column], $maxHeight, $cellData, $size);
                    $x += $widths[$column];
                    continue;
                }

                if ($isArray && isset($cellData['image'])) {
                    // Largeur et hauteur toutes deux imposees, avec fitbox : sans largeur
                    // maximale un picto grandit avec la ligne et deborde sur la colonne voisine
                    if (dol_strlen($cellData['image']) && is_readable($cellData['image'])) {
                        $imageHeight = min($maxHeight - 2, self::MAX_IMAGE_HEIGHT);
                        $imageWidth  = min($widths[$column] - (2 * self::CELL_PAD), $imageHeight);
                        $pdf->Image($cellData['image'], $x + (($widths[$column] - $imageWidth) / 2), $y + (($maxHeight - $imageHeight) / 2), $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0, 'CM');
                    }
                    $x += $widths[$column];
                    continue;
                }

                $text  = $isArray ? ($cellData['text'] ?? '') : $cellData;
                $align = ($isArray && !empty($cellData['align'])) ? $cellData['align'] : 'L';
                $bold  = $isArray && !empty($cellData['bold']);
                // Une cellule en gras porte l'entree de sa ligne : elle prend l'encre des titres,
                // le reste du tableau reste en encre de texte courant
                $color = ($isArray && !empty($cellData['color'])) ? $cellData['color'] : ($bold ? $this->ink : $this->inkSoft);

                $pdf->SetFont('', $bold ? 'B' : '', $textSize);
                $pdf->SetTextColor($color[0], $color[1], $color[2]);
                $pdf->MultiCell($widths[$column] - (2 * self::CELL_PAD), $this->height, (string) $text, 0, $align, false, 0, $x + self::CELL_PAD, $y, true, 0, false, true, $maxHeight, 'M');
                $pdf->SetTextColor(0, 0, 0);

                $x += $widths[$column];
            }

            $pdf->SetXY($this->marge_gauche, $y + $maxHeight);
            $this->hairline($pdf, $y + $maxHeight);

            $index++;
        }

        $pdf->Ln(3);
    }

    /**
     * Pastille d'une cellule de tableau, centree dans sa colonne.
     *
     * @param  TCPDF $pdf       PDF handler
     * @param  float $x         Abscisse de la colonne
     * @param  float $y         Ordonnee de la ligne
     * @param  float $width     Largeur de la colonne
     * @param  float $rowHeight Hauteur de la ligne
     * @param  array $cellData  Cellule ['text', 'fill', 'color']
     * @param  float $size      Taille de police
     * @return void
     */
    protected function drawCellPill($pdf, float $x, float $y, float $columnWidth, float $rowHeight, array $cellData, float $size)
    {
        $text      = (string) ($cellData['text'] ?? '');
        $height    = 6.4;
        $available = $columnWidth - (2 * self::CELL_PAD);

        // Une pastille ne se replie pas sur deux lignes : la police retrecit jusqu'a ce que le
        // libelle tienne dans la colonne, faute de quoi Cell le couperait en plein mot
        $fontSize = $size - 2;
        $pdf->SetFont('', 'B', $fontSize);
        while ($fontSize > 5 && $pdf->GetStringWidth($text) > ($available - 5)) {
            $fontSize -= 0.5;
            $pdf->SetFont('', 'B', $fontSize);
        }

        $width = min($available, max(11, $pdf->GetStringWidth($text) + 6));

        $this->pill(
            $pdf,
            $x + (($columnWidth - $width) / 2),
            $y + (($rowHeight - $height) / 2),
            $width,
            $height,
            $text,
            $cellData['fill'] ?? $this->levelBg[1],
            $cellData['color'] ?? $this->levelText[1],
            $fontSize
        );
    }

    /**
     * Ligne d'entete d'un tableau, repetee en haut de chaque page.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $header Entetes
     * @param  array $widths Largeurs de colonnes
     * @param  float $size   Taille de police
     * @return void
     */
    protected function drawTableHeader($pdf, array $header, array $widths, float $size)
    {
        $y = $pdf->GetY();

        $pdf->setFontSpacing(0.2);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);

        $x = $this->marge_gauche;
        foreach ($header as $column => $label) {
            $isArray = is_array($label);
            $text    = dol_strtoupper($isArray ? ($label['text'] ?? '') : $label);
            $align   = ($isArray && !empty($label['align'])) ? $label['align'] : 'L';
            $inner   = $widths[$column] - (2 * self::CELL_PAD);

            // Sans bordure verticale, rien n'arrete un entete trop long : Cell le laisse
            // deborder sur la colonne suivante. La police retrecit donc jusqu'a ce qu'il tienne
            $fontSize = $size - 3;
            $pdf->SetFont('', 'B', $fontSize);
            while ($fontSize > 4.5 && $pdf->GetStringWidth($text) > $inner) {
                $fontSize -= 0.5;
                $pdf->SetFont('', 'B', $fontSize);
            }

            $pdf->SetXY($x + self::CELL_PAD, $y);
            $pdf->Cell($inner, 6.5, $text, 0, 0, $align);
            $x += $widths[$column];
        }

        $pdf->setFontSpacing(0);
        $pdf->SetTextColor(0, 0, 0);

        $this->hairline($pdf, $y + 6.8, 0.45, $this->ink);
        $pdf->SetXY($this->marge_gauche, $y + 7.6);
    }

    /**
     * Tuile de chiffre cle : un trait d'accent, un grand nombre, son libelle, sur une carte.
     *
     * Dessinee a coordonnees imposees : les tuiles d'une rangee doivent rester alignees, la
     * rupture automatique en renverrait une sur la page suivante. C'est a statTileRow() de
     * reserver la place.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  float  $x      Abscisse
     * @param  float  $y      Ordonnee
     * @param  float  $width  Largeur
     * @param  float  $height Hauteur
     * @param  string $value  Valeur affichee en grand
     * @param  string $label  Libelle sous la valeur
     * @param  array  $color      Couleur du trait d'accent
     * @param  float  $size       Taille de police de reference
     * @param  int    $labelLines Nombre de lignes de libelle a prevoir, 0 pour celui du libelle :
     *                            les tuiles d'une meme rangee passent le plus grand, sans quoi
     *                            leurs nombres ne sont pas a la meme hauteur
     * @return void
     */
    protected function statTile($pdf, float $x, float $y, float $width, float $height, string $value, string $label, array $color, float $size, int $labelLines = 0)
    {
        $this->card($pdf, $x, $y, $width, $height, $this->surface);

        // Le bloc trait + nombre + libelle est centre dans la carte plutot que cale en haut :
        // les tuiles d'une diapositive sont bien plus hautes que celles d'une page A4, et le
        // contenu y flottait sous le bord superieur
        $innerWidth = $width - 10;
        $lineHeight = $this->height - 1;

        $pdf->SetFont('', '', $size - 1);
        $labelLines = max(1, $labelLines, $pdf->getNumLines($label, $innerWidth));
        $valueHeight = ($size + 10) * 0.42;
        $blockHeight = 1.2 + 2.6 + $valueHeight + 1.5 + ($labelLines * $lineHeight);
        $top         = $y + max(4, ($height - $blockHeight) / 2);

        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->RoundedRect($x + 5, $top, 8, 1.2, 0.6, '1111', 'F');

        $valueY = $top + 3.8;
        $pdf->SetFont('', 'B', $size + 10);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($x + 5, $valueY);
        $pdf->Cell($innerWidth, $valueHeight, $value, 0, 0, 'L');

        // La hauteur maximale laissee au libelle ne retranche aucune marge : MultiCell deduit
        // encore son propre padding, et une zone calculee au plus juste coupait a la premiere
        // ligne un libelle comme "du plan d'actions realise"
        $labelY = $valueY + $valueHeight + 1.5;
        $pdf->SetFont('', '', $size - 1);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->MultiCell($innerWidth, $lineHeight, $label, 0, 'L', false, 1, $x + 5, $labelY, true, 0, false, true, max($this->height, $y + $height - $labelY), 'T');

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Rangee de tuiles de chiffres cles, reparties sur toute la largeur utile.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $tiles  Tuiles ['value' => string, 'label' => string, 'color' => array]
     * @param  float $height Hauteur des tuiles
     * @param  float $size   Taille de police de reference
     * @return void
     */
    protected function statTileRow($pdf, array $tiles, float $height, float $size)
    {
        if (empty($tiles)) {
            return;
        }

        $this->reserveSpace($pdf, $height + 5);

        $gap        = 5;
        $count      = count($tiles);
        $width      = ($this->contentWidth($pdf) - (($count - 1) * $gap)) / $count;
        $y          = $pdf->GetY();
        $x          = $this->marge_gauche;
        $labelLines = $this->tileLabelLines($pdf, array_column($tiles, 'label'), $width, $size);

        foreach ($tiles as $tile) {
            $this->statTile($pdf, $x, $y, $width, $height, (string) $tile['value'], (string) $tile['label'], $tile['color'] ?? $this->accent, $size, $labelLines);
            $x += $width + $gap;
        }

        $pdf->SetXY($this->marge_gauche, $y + $height + 5);
    }

    /**
     * Plus grand nombre de lignes des libelles d'une rangee de tuiles.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $labels Libelles des tuiles
     * @param  float $width  Largeur d'une tuile
     * @param  float $size   Taille de police de reference
     * @return int           Nombre de lignes du libelle le plus long
     */
    protected function tileLabelLines($pdf, array $labels, float $width, float $size): int
    {
        $pdf->SetFont('', '', $size - 1);

        $lines = 1;
        foreach ($labels as $label) {
            $lines = max($lines, $pdf->getNumLines((string) $label, $width - 10));
        }

        return $lines;
    }

    /**
     * Barres horizontales arrondies d'une serie de valeurs.
     *
     * La barre la plus longue occupe toute la largeur disponible : c'est une comparaison entre
     * les lignes, pas une mesure absolue, le nombre est donc ecrit au bout de chaque barre.
     *
     * @param  TCPDF $pdf        PDF handler
     * @param  array $bars       Barres ['label' => string, 'value' => int, 'color' => array]
     * @param  float $labelWidth Largeur reservee aux libelles
     * @param  float $size       Taille de police
     * @param  float $barHeight  Hauteur d'une barre
     * @return void
     */
    protected function barChart($pdf, array $bars, float $labelWidth, float $size, float $barHeight = 7)
    {
        if (empty($bars)) {
            return;
        }

        $max = 0;
        foreach ($bars as $bar) {
            $max = max($max, (int) $bar['value']);
        }

        $valueWidth = 14;
        $trackWidth = $this->contentWidth($pdf) - $labelWidth - $valueWidth;
        if ($trackWidth <= 0) {
            return;
        }

        $this->reserveSpace($pdf, (count($bars) * ($barHeight + 2.5)) + 4);

        $thickness = $barHeight - 2.5;
        foreach ($bars as $bar) {
            $value = (int) $bar['value'];
            $color = $bar['color'] ?? $this->accent;
            $y     = $pdf->GetY();

            // Le picto de la famille, quand il y en a un, tient lieu de puce devant le libelle
            $textX = $this->marge_gauche;
            if (!empty($bar['image']) && is_readable($bar['image'])) {
                $pictoSize = min($barHeight, 7);
                $pdf->Image($bar['image'], $textX, $y + (($barHeight - $pictoSize) / 2), $pictoSize, $pictoSize, '', '', '', false, 300, '', false, false, 0, 'CM');
                $textX += $pictoSize + 2.5;
            }

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($textX, $y);
            $pdf->Cell($labelWidth - ($textX - $this->marge_gauche), $barHeight, (string) $bar['label'], 0, 0, 'L');

            // Rail tres clair : il donne l'echelle, sans lui une barre courte ne se lit pas
            $this->card($pdf, $this->marge_gauche + $labelWidth, $y + 1.2, $trackWidth, $thickness, $this->tint($color, 0.9), $thickness / 2);

            // Une valeur non nulle garde un trait visible : une barre de 0 mm se lirait comme
            // une absence de donnee alors que le nombre, lui, n'est pas nul
            if ($value > 0 && $max > 0) {
                $this->card($pdf, $this->marge_gauche + $labelWidth, $y + 1.2, max($thickness, $trackWidth * $value / $max), $thickness, $color, $thickness / 2);
            }

            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($this->marge_gauche + $labelWidth + $trackWidth, $y);
            $pdf->Cell($valueWidth, $barHeight, (string) $value, 0, 0, 'R');

            $pdf->SetXY($this->marge_gauche, $y + $barHeight + 2.5);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    /**
     * Repartition des cotations : un segment arrondi par niveau, separes par un intervalle.
     *
     * Les segments sont detaches plutot qu'accoles : une barre empilee d'un seul tenant ne peut
     * pas avoir ses quatre extremites arrondies, et les parts les plus faibles s'y perdaient.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $counts      Nombre de risques par niveau, indexe de 1 a 4
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @param  float     $height      Hauteur des segments
     * @return void
     */
    protected function stackedLevelBar($pdf, array $counts, Translate $outputLangs, float $size, float $height = 10)
    {
        $total = array_sum($counts);
        if ($total <= 0) {
            return;
        }

        $this->reserveSpace($pdf, $height + 14);

        $gap       = 2;
        $minWidth  = 5;
        $width     = $this->contentWidth($pdf);
        $y         = $pdf->GetY();
        $filled    = array_filter($counts);
        $available = $width - ((count($filled) - 1) * $gap);

        // Un segment trop etroit disparait : il garde une largeur plancher, reprise au segment
        // le plus large pour que la rangee tienne toujours dans la largeur utile
        $segments = [];
        foreach ($filled as $level => $count) {
            $segments[$level] = max($minWidth, $available * $count / $total);
        }
        $overflow = array_sum($segments) - $available;
        if ($overflow > 0) {
            $widest            = array_search(max($segments), $segments, true);
            $segments[$widest] = max($minWidth, $segments[$widest] - $overflow);
        }

        $x = $this->marge_gauche;
        foreach ($segments as $level => $segment) {
            $fill = $this->levelBg[$level] ?? $this->levelBg[1];
            $this->card($pdf, $x, $y, $segment, $height, $fill, min($height / 2, 3));

            // Le pourcentage n'est ecrit dans le segment que s'il y tient
            if ($segment >= 16) {
                $pdf->SetFont('', 'B', $size - 1);
                $ink = $this->levelText[$level] ?? $this->ink;
                $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
                $pdf->SetXY($x, $y);
                $pdf->Cell($segment, $height, round($counts[$level] * 100 / $total) . ' %', 0, 0, 'C');
            }

            $x += $segment + $gap;
        }

        // Legende : les quatre niveaux, y compris ceux a zero, pour que le lecteur sache que le
        // niveau existe et qu'il est vide
        $legendWidth = $width / 4;
        $legendY     = $y + $height + 3;
        $legendX     = $this->marge_gauche;
        foreach ([1, 2, 3, 4] as $level) {
            $fill = $this->levelBg[$level];
            $this->card($pdf, $legendX, $legendY + 1.5, 3.2, 3.2, $fill, 1.6);
            $pdf->SetFont('', '', $size - 1.5);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($legendX + 5, $legendY);
            $pdf->Cell($legendWidth - 5, 6, $outputLangs->transnoentities($this->levelLabelKey($level)) . ' : ' . (int) ($counts[$level] ?? 0), 0, 0, 'L');
            $legendX += $legendWidth;
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($this->marge_gauche, $legendY + 9);
    }

    /**
     * Anneau de repartition : un secteur par valeur, un trou au centre portant le total.
     *
     * Un anneau dit la part de chaque niveau d'un coup d'oeil la ou une barre demande de
     * comparer des longueurs, et il occupe la hauteur disponible au lieu d'une bande.
     *
     * @param  TCPDF  $pdf         PDF handler
     * @param  float  $cx          Abscisse du centre
     * @param  float  $cy          Ordonnee du centre
     * @param  float  $radius      Rayon exterieur
     * @param  array  $slices      Secteurs ['value' => int, 'color' => array]
     * @param  string $centerValue Valeur inscrite au centre
     * @param  string $centerLabel Libelle inscrit sous la valeur
     * @param  float  $size        Taille de police
     * @return void
     */
    protected function donut($pdf, float $cx, float $cy, float $radius, array $slices, string $centerValue, string $centerLabel, float $size)
    {
        $total = 0;
        foreach ($slices as $slice) {
            $total += max(0, (int) $slice['value']);
        }
        if ($total <= 0) {
            return;
        }

        // Les secteurs sont separes par un filet blanc : sans lui deux niveaux voisins de
        // teintes proches se lisent comme un seul
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetLineWidth(0.7);

        $angle = 0;
        foreach ($slices as $slice) {
            $value = max(0, (int) $slice['value']);
            if ($value <= 0) {
                continue;
            }

            $sweep = 360 * $value / $total;
            $color = $slice['color'] ?? $this->accent;
            $pdf->SetFillColor($color[0], $color[1], $color[2]);

            // Un secteur unique couvre le tour complet : PieSector ne sait pas tracer un arc de
            // 360 degres, c'est un disque qu'il faut dessiner
            if ($sweep >= 359.9) {
                $pdf->Circle($cx, $cy, $radius, 0, 360, 'F');
            } else {
                $pdf->PieSector($cx, $cy, $radius, $angle, $angle + $sweep, 'FD', true, 90);
            }

            $angle += $sweep;
        }

        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);

        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($cx, $cy, $radius * 0.62, 0, 360, 'F');

        // Les hauteurs de la valeur et du libelle suivent leur corps : des hauteurs fixes
        // faisaient chevaucher le nombre et son libelle sur les diapositives, ou la police
        // de reference est plus grande que sur une page A4
        $valueHeight = ($size + 7) * 0.3528 * 1.15;
        $labelHeight = ($size - 2) * 0.3528 * 1.3;
        $top         = $cy - (($valueHeight + $labelHeight) / 2);

        $pdf->SetFont('', 'B', $size + 7);
        $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
        $pdf->SetXY($cx - $radius, $top);
        $pdf->Cell($radius * 2, $valueHeight, $centerValue, 0, 0, 'C', false, '', 0, false, 'T', 'M');

        $pdf->SetFont('', '', $size - 2);
        $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
        $pdf->SetXY($cx - $radius, $top + $valueHeight);
        $pdf->Cell($radius * 2, $labelHeight, $centerLabel, 0, 0, 'C', false, '', 0, false, 'T', 'M');

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Legende verticale d'un anneau : une pastille, un libelle, une valeur alignee a droite.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  float $x      Abscisse
     * @param  float $y      Ordonnee
     * @param  float $width  Largeur
     * @param  array $items  Entrees ['label' => string, 'value' => string, 'color' => array]
     * @param  float $size   Taille de police
     * @param  float $rowGap Hauteur d'une entree
     * @return float         Ordonnee du bas de la legende
     */
    protected function legend($pdf, float $x, float $y, float $width, array $items, float $size, float $rowGap = 7): float
    {
        foreach ($items as $item) {
            $color = $item['color'] ?? $this->accent;

            $this->card($pdf, $x, $y + 1.6, 3.4, 3.4, $color, 1.7);

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->SetXY($x + 6, $y);
            $pdf->Cell($width - 22, $rowGap - 1, (string) $item['label'], 0, 0, 'L');

            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->SetXY($x + $width - 16, $y);
            $pdf->Cell(16, $rowGap - 1, (string) $item['value'], 0, 0, 'R');

            $y += $rowGap;
        }

        $pdf->SetTextColor(0, 0, 0);

        return $y;
    }

    /**
     * Encadre de mise en avant : fond teinte, filet de couleur a gauche, un titre et des lignes.
     *
     * Il sert a dire ce que les chiffres voisins veulent dire, en une phrase par ligne. Les
     * lignes sont calculees par les modeles a partir des donnees, pas ecrites en dur.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  float  $x     Abscisse
     * @param  float  $y     Ordonnee
     * @param  float  $width Largeur
     * @param  string $title Titre de l'encadre
     * @param  array  $lines Lignes de texte, ou ['text' => string, 'sub' => true] en retrait
     * @param  float  $size  Taille de police
     * @param  array  $color Couleur du filet et du titre
     * @return float         Ordonnee du bas de l'encadre
     */
    protected function calloutBox($pdf, float $x, float $y, float $width, string $title, array $lines, float $size, array $color = []): float
    {
        $color      = $color ?: $this->accent;
        $innerWidth = $width - 16;
        $lineHeight = $this->height;

        // Une ligne est un texte, ou ['text' => string, 'sub' => true] pour une sous-puce en
        // retrait : les conditions d'une phrase qui les annonce ne se lisent pas au meme niveau
        // qu'elle
        $items = [];
        foreach ($lines as $line) {
            $isSub   = is_array($line) && !empty($line['sub']);
            $items[] = ['text' => (string) (is_array($line) ? ($line['text'] ?? '') : $line), 'indent' => $isSub ? 6 : 0];
        }

        // La hauteur se mesure avant de peindre le fond : un encadre dessine a hauteur fixe
        // laisse un vide sous un texte court et tronque un texte long
        $pdf->SetFont('', '', $size - 1);
        $textHeight = 0;
        foreach ($items as $item) {
            $textHeight += max(1, $pdf->getNumLines($item['text'], $innerWidth - $item['indent'])) * $lineHeight;
            $textHeight += 1.5;
        }
        $height = 9 + $textHeight + 4;

        $this->card($pdf, $x, $y, $width, $height, $this->tint($color, 0.93));
        $this->card($pdf, $x, $y, 1.8, $height, $color, 0.9);

        $this->kicker($pdf, $x + 7, $y + 4, $width - 14, $title, $size, $color);

        $lineY = $y + 11;
        foreach ($items as $item) {
            if ($item['indent'] > 0) {
                $this->card($pdf, $x + 7 + $item['indent'], $lineY + 2.3, 2.6, 0.9, $color, 0.45);
            } else {
                $this->card($pdf, $x + 7, $lineY + 1.8, 2.2, 2.2, $color, 1.1);
            }

            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($innerWidth - $item['indent'], $lineHeight, $item['text'], 0, 'L', false, 1, $x + 12 + $item['indent'], $lineY, true, 0, false, true, 0, 'T');

            $lineY = $pdf->GetY() + 1.5;
        }

        $pdf->SetTextColor(0, 0, 0);

        return $y + $height;
    }

    /**
     * Grille de cartes : un surtitre ou une pastille, un titre, un texte et des puces.
     *
     * Une carte est ['title' => string, 'text' => string, 'lines' => string[], 'badge' => string,
     * 'kicker' => string, 'color' => [r, g, b]]. Les cartes d'une meme rangee prennent la hauteur
     * de la plus haute : la rangee se lit comme un bloc.
     *
     * @param  TCPDF $pdf     PDF handler
     * @param  array $cards   Cartes
     * @param  int   $columns Nombre de colonnes
     * @param  float $size    Taille de police
     * @param  bool  $tinted  Fond teinte de la couleur de la carte plutot que gris clair
     * @return void
     */
    protected function cardGrid($pdf, array $cards, int $columns, float $size, bool $tinted = false)
    {
        $gap       = 4;
        $width     = $this->contentWidth($pdf);
        $cardWidth = ($width - (($columns - 1) * $gap)) / $columns;
        $inner     = $cardWidth - (2 * self::CARD_PAD);

        foreach (array_chunk($cards, $columns) as $row) {
            $rowHeight = 0;
            foreach ($row as $card) {
                $rowHeight = max($rowHeight, $this->cardHeight($pdf, $card, $inner, $size));
            }

            $this->reserveSpace($pdf, $rowHeight + $gap);

            $y = $pdf->GetY();
            $x = $this->marge_gauche;
            foreach ($row as $card) {
                $this->drawCard($pdf, $card, $x, $y, $cardWidth, $rowHeight, $size, $tinted);
                $x += $cardWidth + $gap;
            }

            $pdf->SetXY($this->marge_gauche, $y + $rowHeight + $gap);
        }
    }

    /**
     * Hauteur d'une carte, mesuree police posee.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $card  Carte
     * @param  float $inner Largeur utile de la carte
     * @param  float $size  Taille de police
     * @return float        Hauteur
     */
    protected function cardHeight($pdf, array $card, float $inner, float $size): float
    {
        $height = (2 * self::CARD_PAD) + 2.5;

        if (!empty($card['badge'])) {
            $height += 11;
        } elseif (!empty($card['kicker'])) {
            $height += 6;
        }
        if (!empty($card['title'])) {
            $pdf->SetFont('', 'B', $size + 0.5);
            $height += ($pdf->getNumLines($card['title'], $inner) * 5.2) + 1.5;
        }
        if (!empty($card['text'])) {
            $pdf->SetFont('', '', $size - 1);
            $height += $pdf->getNumLines($card['text'], $inner) * self::LINE;
        }
        foreach ($card['lines'] ?? [] as $line) {
            $pdf->SetFont('', '', $size - 1);
            $height += ($pdf->getNumLines($line, $inner - 5) * self::LINE) + 1.2;
        }

        return $height;
    }

    /**
     * Dessine une carte de la grille.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $card   Carte
     * @param  float $x      Abscisse
     * @param  float $y      Ordonnee
     * @param  float $width  Largeur
     * @param  float $height Hauteur
     * @param  float $size   Taille de police
     * @param  bool  $tinted Fond teinte de la couleur de la carte
     * @return void
     */
    protected function drawCard($pdf, array $card, float $x, float $y, float $width, float $height, float $size, bool $tinted)
    {
        $color = $card['color'] ?? $this->accent;
        $inner = $width - (2 * self::CARD_PAD);
        $left  = $x + self::CARD_PAD;

        $this->card($pdf, $x, $y, $width, $height, $tinted ? $this->tint($color, 0.92) : $this->surface);

        $cursor = $y + self::CARD_PAD;
        if (!empty($card['badge'])) {
            // Pastille ronde : une lettre de critere, un numero d'etape
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->Circle($left + 4.5, $cursor + 4.5, 4.5, 0, 360, 'F');
            $pdf->SetFont('', 'B', $size + 1);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($left, $cursor);
            $pdf->Cell(9, 9, $card['badge'], 0, 0, 'C');
            $cursor += 11;
        } else {
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->RoundedRect($left, $cursor, 9, 1.3, 0.65, '1111', 'F');
            $cursor += 3;
            if (!empty($card['kicker'])) {
                $this->kicker($pdf, $left, $cursor, $inner, $card['kicker'], $size, $color);
                $cursor += 6;
            }
        }

        if (!empty($card['title'])) {
            $pdf->SetFont('', 'B', $size + 0.5);
            $pdf->SetTextColor($this->ink[0], $this->ink[1], $this->ink[2]);
            $pdf->MultiCell($inner, 5.2, $card['title'], 0, 'L', false, 1, $left, $cursor, true, 0, false, true, 0, 'T');
            $cursor = $pdf->GetY() + 1.5;
        }
        if (!empty($card['text'])) {
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($inner, self::LINE, $card['text'], 0, 'L', false, 1, $left, $cursor, true, 0, false, true, 0, 'T');
            $cursor = $pdf->GetY();
        }
        foreach ($card['lines'] ?? [] as $line) {
            $this->card($pdf, $left + 0.5, $cursor + 1.6, 1.8, 1.8, $color, 0.9);
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor($this->inkSoft[0], $this->inkSoft[1], $this->inkSoft[2]);
            $pdf->MultiCell($inner - 5, self::LINE, $line, 0, 'L', false, 1, $left + 5, $cursor, true, 0, false, true, 0, 'T');
            $cursor = $pdf->GetY() + 1.2;
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Chemin du picto d'une famille de danger.
     *
     * @param  Risk   $risk     Instance servant a lire le referentiel
     * @param  object $riskLine Risque
     * @return string           Chemin complet du picto, vide s'il est introuvable
     */
    protected function categoryPictoPath(Risk $risk, $riskLine): string
    {
        $picto = $risk->getDangerCategory($riskLine, $riskLine->type ?? 'risk');
        if (empty($picto) || $picto === -1) {
            return '';
        }

        $path = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $picto . '.png';

        return is_readable($path) ? $path : '';
    }

    /**
     * Chemin du picto d'une famille de danger, connue par sa seule position.
     *
     * getDangerCategory() resout le picto depuis la propriete category de l'objet qu'on lui
     * passe : un diagramme, qui ne manipule que des positions, lui en fabrique un.
     *
     * @param  Risk   $risk     Instance servant a lire le referentiel
     * @param  int    $position Position de la famille
     * @return string           Chemin complet du picto, vide s'il est introuvable
     */
    protected function categoryPictoByPosition(Risk $risk, int $position): string
    {
        $holder           = new stdClass();
        $holder->category = $position;
        $holder->type     = 'risk';

        return $this->categoryPictoPath($risk, $holder);
    }

    /**
     * Cellule de picto de famille de danger.
     *
     * @param  string $path Chemin du picto
     * @return array        Cellule de tableau
     */
    protected function pictoCell(string $path): array
    {
        return ['image' => $path, 'text' => ''];
    }

    /**
     * Couleur attribuee a une famille de danger, par son rang d'affichage.
     *
     * @param  int   $rank Rang dans la liste affichee
     * @return array       Couleur
     */
    protected function categoryColor(int $rank): array
    {
        return $this->categoryColors[$rank % count($this->categoryColors)];
    }

    /**
     * Cle de traduction du libelle d'un niveau de cotation.
     *
     * @param  int    $level Niveau de 1 a 4
     * @return string        Cle de traduction
     */
    protected function levelLabelKey(int $level): string
    {
        $keys = [1 => 'GreyRisk', 2 => 'OrangeRisk', 3 => 'RedRisk', 4 => 'BlackRisk'];

        return $keys[$level] ?? 'GreyRisk';
    }

    /**
     * Pied de page.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Objet imprime
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

        for ($page = $this->footerFirstPage; $page <= $totalPages; $page++) {
            $pdf->setPage($page);
            // Le pied s'ecrit dans la bande basse, sous le seuil de rupture : sans couper la
            // rupture automatique son Cell serait reporte en haut de la page suivante. A recouper
            // apres chaque setPage, qui restaure les reglages memorises de la page.
            $pdf->SetAutoPageBreak(false);

            $baseY = $pdf->getPageHeight() - 12;
            $this->hairline($pdf, $baseY);

            $pdf->SetFont('', '', $this->footerFontSize);
            $pdf->SetTextColor($this->inkMuted[0], $this->inkMuted[1], $this->inkMuted[2]);
            $pdf->SetXY($this->marge_gauche, $baseY + 1.5);
            $pdf->Cell($halfWidth, 5, $this->footerText, 0, 0, 'L');
            $pdf->Cell($halfWidth, 5, $page . ' / ' . $totalPages, 0, 0, 'R');

            $this->decorateFooter($pdf, $page, $totalPages);
        }

        $pdf->SetTextColor(0, 0, 0);

        // Rendre la main sur la position d'avant la passe : laisser le curseur dans la bande
        // basse ferait ouvrir une page blanche des le retour de la rupture automatique
        $pdf->setPage($currentPage);
        $pdf->SetXY($this->marge_gauche, $currentY);
        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
    }

    /**
     * Ornement ajoute au pied d'une page, apres son texte. Sans effet par defaut.
     *
     * @param  TCPDF $pdf        PDF handler
     * @param  int   $page       Page courante
     * @param  int   $totalPages Nombre total de pages
     * @return void
     */
    protected function decorateFooter($pdf, int $page, int $totalPages)
    {
    }

    /**
     * Instancie et configure le PDF, premiere page ouverte.
     *
     * @param  object    $object      Objet imprime
     * @param  Translate $outputLangs Lang object
     * @param  string    $subject     Sujet du document
     * @return TCPDF                  PDF handler
     */
    protected function openPdf($object, Translate $outputLangs, string $subject)
    {
        global $user;

        $pdf  = pdf_getInstance($this->format);
        $size = pdf_getPDFFontSize($outputLangs) + 1;

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();
        $pdf->SetDrawColor($this->rule[0], $this->rule[1], $this->rule[2]);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTitle($outputLangs->convToOutputCharset($subject));
        $pdf->SetSubject($outputLangs->convToOutputCharset($subject));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, self::FOOTER_BAND);
        $pdf->SetAutoPageBreak(1, self::FOOTER_BAND);

        $pdf->AddPage($this->orientation);
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $size);

        return $pdf;
    }

    /**
     * Logo de la societe, pose a l'endroit demande.
     *
     * @param  TCPDF  $pdf    PDF handler
     * @param  object $object Objet imprime
     * @param  float  $x      Abscisse
     * @param  float  $y      Ordonnee
     * @param  float  $height   Hauteur du logo
     * @param  float  $maxWidth Largeur a ne pas depasser, 0 pour aucune : un logo tres allonge
     *                          est alors reduit pour tenir dans la largeur
     * @return float            Largeur du logo dessine, 0 s'il n'y en a pas
     */
    protected function companyLogo($pdf, $object, float $x, float $y, float $height = 12, float $maxWidth = 0): float
    {
        $logo = $this->companyLogoPath($object);
        if (empty($logo)) {
            return 0;
        }

        [$width, $height] = $this->companyLogoSize($logo, $height, $maxWidth);
        $pdf->Image($logo, $x, $y, $width, $height);

        return $width;
    }

    /**
     * Chemin du logo de la societe.
     *
     * @param  object $object Objet imprime
     * @return string         Chemin lisible, vide s'il n'y a pas de logo a imprimer
     */
    protected function companyLogoPath($object): string
    {
        global $mysoc;

        if (getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO') || empty($mysoc->logo)) {
            return '';
        }

        $logoDir = getMultidirOutput($object, 'mycompany') ?: '';
        $logo    = getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO') ? $logoDir . '/logos/' . $mysoc->logo : $logoDir . '/logos/thumbs/' . $mysoc->logo_small;

        return is_readable($logo) ? $logo : '';
    }

    /**
     * Dimensions d'impression du logo, proportions respectees.
     *
     * @param  string $logo     Chemin du logo
     * @param  float  $height   Hauteur voulue
     * @param  float  $maxWidth Largeur a ne pas depasser, 0 pour aucune
     * @return array            [largeur, hauteur]
     */
    protected function companyLogoSize(string $logo, float $height, float $maxWidth = 0): array
    {
        $imageSize = @getimagesize($logo);
        $width     = !empty($imageSize[1]) ? $height * $imageSize[0] / $imageSize[1] : $height;

        if ($maxWidth > 0 && $width > $maxWidth) {
            $height = $height * $maxWidth / $width;
            $width  = $maxWidth;
        }

        return [$width, $height];
    }

    /**
     * Cellule de cotation d'un risque, sous forme de pastille pleine.
     *
     * @param  int|string $cotation Cotation
     * @param  int        $level    Niveau de 1 a 4
     * @return array                Cellule de tableau
     */
    protected function cotationCell($cotation, int $level): array
    {
        return [
            'pill'  => true,
            'text'  => (string) ($cotation ?: 0),
            'fill'  => $this->levelBg[$level] ?? $this->levelBg[1],
            'color' => $this->levelText[$level] ?? $this->levelText[1]
        ];
    }

    /**
     * Cellule de niveau d'un risque, sous forme de pastille douce.
     *
     * @param  int       $level       Niveau de 1 a 4
     * @param  Translate $outputLangs Lang object
     * @return array                  Cellule de tableau
     */
    protected function levelCell(int $level, Translate $outputLangs): array
    {
        $level = isset($this->levelBg[$level]) ? $level : 1;

        return [
            'pill'  => true,
            'text'  => $outputLangs->transnoentities($this->levelLabelKey($level)),
            'fill'  => $this->tint($this->levelBg[$level], 0.66),
            'color' => $this->levelSoftText[$level]
        ];
    }

    /**
     * Chiffres du document unique, agreges en une passe.
     *
     * Tout ce que les deux documents affichent est calcule ici : les risques valides de l'entite
     * avec leur derniere cotation, leur repartition par niveau et par famille de danger, le
     * perimetre (GP/UT, evaluateurs), l'avancement du plan d'actions, la qualification RPS des
     * unites de travail et les accidents. Les modeles ne font plus que mettre en page.
     *
     * @param  array $moreParam More param (object/user/tmparray)
     * @return array            Chiffres du document unique
     * @throws Exception
     */
    protected function loadDigiriskStatistics(array $moreParam): array
    {
        require_once __DIR__ . '/../../../../class/digiriskelement.class.php';
        require_once __DIR__ . '/../../../../class/evaluator.class.php';
        require_once __DIR__ . '/../../../../class/riskanalysis/risk.class.php';

        $risk            = new Risk($this->db);
        $digiriskElement = new DigiriskElement($this->db);

        // Les deux documents portent sur tout le document unique de l'entite : aucun filtre
        // d'element, comme le gabarit ODT du DU
        $moreParam['filter'] = '';
        $riskInfos           = $risk->loadRiskInfos($moreParam);
        $elementInfos        = $digiriskElement->loadDigiriskElementInfos($moreParam);
        $evaluatorInfos      = Evaluator::loadEvaluatorInfos();

        $stats = [
            'risks'        => $riskInfos['current']['risks'] ?? [],
            'riskLevels'   => $riskInfos['current']['riskByRiskAssessmentLevels'] ?? [],
            'riskTasks'    => $riskInfos['current']['riskTasks'] ?? [],
            'totalRisks'   => (int) ($riskInfos['current']['totalRisks'] ?? 0),
            'elements'     => $elementInfos['current']['digiriskElements'] ?? [],
            'nbEvaluators' => (int) ($evaluatorInfos['nbEvaluators'] ?? 0),
            'levelCounts'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0]
        ];

        // Les compteurs nbGroupment/nbWorkunit de loadDigiriskElementInfos() ne sont pas lus :
        // fetchDigiriskElementFlat() ne renseigne pas la propriete entity des elements, et ce
        // champ vide range tout le perimetre de l'entite courante dans le compteur 'shared'.
        // Les documents comptent donc eux-memes les elements qu'ils affichent
        $stats = array_merge($stats, $this->countElementTypes($stats['elements']));

        foreach (array_keys($stats['levelCounts']) as $level) {
            $stats['levelCounts'][$level] = count($stats['riskLevels'][$level] ?? []);
        }

        $stats['byCategory']  = $this->countRisksByCategory($stats['risks']);
        $stats['byElement']   = $this->groupRisksByElement($stats['risks']);
        $stats['actionPlan']  = $this->summarizeActionPlan($stats['riskTasks']);
        $stats['rps']         = $this->summarizePsychosocialScope($stats['elements'], $riskInfos['current']['psychosocialRisksByGPUT'] ?? []);
        $stats['rpsByElement'] = $riskInfos['current']['psychosocialRisksByGPUT'] ?? [];
        $stats['rpsByFactor']  = $riskInfos['current']['riskBySubCategories'] ?? [];
        $stats['accidents']   = $this->countAccidents();
        $stats['auditStart']  = $this->auditDate('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE');
        $stats['auditEnd']    = $this->auditDate('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE');

        return $stats;
    }

    /**
     * Nombre de groupements et d'unites de travail du perimetre.
     *
     * @param  array $elements Elements du perimetre
     * @return array           ['nbGroupment', 'nbWorkunit']
     */
    protected function countElementTypes(array $elements): array
    {
        $counts = ['nbGroupment' => 0, 'nbWorkunit' => 0];

        foreach ($elements as $element) {
            if (($element['object']->element_type ?? '') == 'groupment') {
                $counts['nbGroupment']++;
            } else {
                $counts['nbWorkunit']++;
            }
        }

        return $counts;
    }

    /**
     * Nombre de risques par famille de danger, de la plus representee a la moins representee.
     *
     * @param  array $risks Risques
     * @return array        Nombre de risques par position de famille
     */
    protected function countRisksByCategory(array $risks): array
    {
        $counts = [];

        foreach ($risks as $riskLine) {
            $category          = (int) ($riskLine->category ?? -1);
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Risques regroupes par element porteur.
     *
     * loadRiskInfos() rend les risques deja tries par cotation decroissante : le regroupement
     * conserve cet ordre, il n'y a pas a retrier dans chaque groupe.
     *
     * @param  array $risks Risques
     * @return array        Risques par identifiant d'element
     */
    protected function groupRisksByElement(array $risks): array
    {
        $grouped = [];

        foreach ($risks as $riskLine) {
            $grouped[(int) $riskLine->fk_element][] = $riskLine;
        }

        return $grouped;
    }

    /**
     * Avancement du plan d'actions.
     *
     * Une action est soldee a 100 % d'avancement, en retard si son echeance est passee sans etre
     * soldee. Les prochaines echeances ne retiennent que les actions non soldees qui en portent
     * une, triees au plus proche : c'est ce que le lecteur a besoin de voir.
     *
     * @param  array $riskTasks Taches du plan d'actions, par identifiant de risque
     * @return array            ['total', 'done', 'ongoing', 'late', 'next' => taches]
     */
    protected function summarizeActionPlan(array $riskTasks): array
    {
        $summary = ['total' => 0, 'done' => 0, 'ongoing' => 0, 'late' => 0, 'next' => []];
        $now     = dol_now();

        foreach ($riskTasks as $tasks) {
            foreach ($tasks as $task) {
                $summary['total']++;

                if ((float) $task->progress >= 100) {
                    $summary['done']++;
                    continue;
                }

                $summary['ongoing']++;

                if (!empty($task->datee)) {
                    if ($task->datee < $now) {
                        $summary['late']++;
                    }
                    $summary['next'][] = $task;
                }
            }
        }

        usort($summary['next'], function ($first, $second) {
            return $first->datee <=> $second->datee;
        });

        return $summary;
    }

    /**
     * Qualification RPS des unites de travail.
     *
     * La qualification est prise une seule fois, dans le bloc psychosocial de la liste des
     * risques, et stockee dans llx_digiriskdolibarr_rpsscope : 1 concernee, 0 non concernee,
     * -1 a qualifier. Une unite sans ligne est a qualifier elle aussi. Une unite est dite
     * evaluee des qu'elle porte une cotation sur au moins un facteur.
     *
     * @param  array $elements                Elements du perimetre
     * @param  array $psychosocialRisksByGPUT Cotations RPS par element et par facteur
     * @return array                          ['units', 'concerned', 'notConcerned', 'toQualify', 'assessed',
     *                                        'assessedConcerned' => unites concernees et evaluees]
     */
    protected function summarizePsychosocialScope(array $elements, array $psychosocialRisksByGPUT): array
    {
        global $conf;

        $summary = ['units' => 0, 'concerned' => 0, 'notConcerned' => 0, 'toQualify' => 0, 'assessed' => 0, 'assessedConcerned' => 0];

        $workUnitIds = [];
        foreach ($elements as $elementId => $element) {
            if (($element['object']->element_type ?? '') == 'workunit') {
                $workUnitIds[(int) $elementId] = true;
            }
        }

        $summary['units'] = count($workUnitIds);
        if (empty($workUnitIds)) {
            return $summary;
        }

        $scope = [];
        $sql   = 'SELECT fk_element, concerned FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsscope';
        $sql  .= ' WHERE entity = ' . (int) $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $scope[(int) $obj->fk_element] = (int) $obj->concerned;
            }
        }

        foreach (array_keys($workUnitIds) as $unitId) {
            $state = $scope[$unitId] ?? -1;
            if ($state === 1) {
                $summary['concerned']++;
            } elseif ($state === 0) {
                $summary['notConcerned']++;
            } else {
                $summary['toQualify']++;
            }

            if (!empty($psychosocialRisksByGPUT[$unitId])) {
                $summary['assessed']++;
                if ($state === 1) {
                    $summary['assessedConcerned']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Accidents du travail de l'entite : le total et les douze derniers mois.
     *
     * @return array ['total', 'lastYear']
     * @throws Exception
     */
    protected function countAccidents(): array
    {
        require_once __DIR__ . '/../../../../class/accident.class.php';

        $accident = new Accident($this->db);

        $counts    = ['total' => 0, 'lastYear' => 0];
        $accidents = $accident->fetchAll('DESC', 't.accident_date', 0, 0, ['customsql' => 't.status >= ' . Accident::STATUS_VALIDATED]);
        if (!is_array($accidents)) {
            return $counts;
        }

        $oneYearAgo = dol_time_plus_duree(dol_now(), -1, 'y');
        foreach ($accidents as $accidentLine) {
            $counts['total']++;
            if (!empty($accidentLine->accident_date) && $accidentLine->accident_date >= $oneYearAgo) {
                $counts['lastYear']++;
            }
        }

        return $counts;
    }

    /**
     * Date d'audit du document unique, saisie sur la fiche du DU.
     *
     * Elle est stockee au format Y-m-d dans une constante : dol_stringtotime() la rend en
     * timestamp, 0 si elle n'a pas ete saisie.
     *
     * @param  string $constName Nom de la constante
     * @return int               Timestamp, 0 si absente
     */
    protected function auditDate(string $constName): int
    {
        $value = getDolGlobalString($constName);

        return dol_strlen($value) ? (int) dol_stringtotime($value, 'YYYYMMDD') : 0;
    }

    /**
     * Libelle d'un element du perimetre, prefixe par sa profondeur dans l'arborescence.
     *
     * @param  array $element Element ['object' => DigiriskElement, 'depth' => int]
     * @param  bool  $indent  Prefixer le libelle par sa profondeur
     * @return string         Libelle a afficher
     */
    protected function elementLabel(array $element, bool $indent = true): string
    {
        $label = $element['object']->ref . ' - ' . $element['object']->label;

        return $indent ? str_repeat('  ', (int) ($element['depth'] ?? 0)) . $label : $label;
    }

    /**
     * Libelle de la periode d'evaluation, pour l'en-tete des deux documents.
     *
     * @param  array     $stats       Chiffres du document unique
     * @param  Translate $outputLangs Lang object
     * @return string                 Periode, vide si aucune date n'est saisie
     */
    protected function auditPeriodLabel(array $stats, Translate $outputLangs): string
    {
        if (empty($stats['auditStart']) && empty($stats['auditEnd'])) {
            return '';
        }

        if (!empty($stats['auditStart']) && !empty($stats['auditEnd'])) {
            $start = dol_print_date($stats['auditStart'], 'day', 'tzuser', $outputLangs);
            $end   = dol_print_date($stats['auditEnd'], 'day', 'tzuser', $outputLangs);

            // Une evaluation tenue sur une seule journee ne repete pas deux fois la meme date
            if ($start == $end) {
                return $outputLangs->transnoentities('DigiriskSynthesisPeriodOn', $start);
            }

            return $outputLangs->transnoentities('DigiriskSynthesisPeriodFromTo', $start, $end);
        }

        return $outputLangs->transnoentities('DigiriskSynthesisPeriodSince', dol_print_date($stats['auditStart'] ?: $stats['auditEnd'], 'day', 'tzuser', $outputLangs));
    }

    /**
     * Libelle d'une famille de danger.
     *
     * @param  Risk   $risk     Instance servant a lire le referentiel
     * @param  int    $position Position de la famille
     * @return string           Nom de la famille, vide si elle n'existe plus au referentiel
     */
    protected function categoryLabel(Risk $risk, int $position): string
    {
        $name = $risk->getDangerCategoryNameByPosition($position);

        return ($name === -1 || $name === null) ? '' : (string) $name;
    }

    /**
     * Description d'un risque, debarrassee de son HTML et ramenee a une longueur lisible.
     *
     * @param  object $riskLine  Risque
     * @param  int    $maxLength Longueur maximale
     * @return string            Description
     */
    protected function riskDescription($riskLine, int $maxLength = 160): string
    {
        $description = saturne_flatten_wysiwyg_blocks($riskLine->description ?? '', true);

        return dol_strlen($description) > $maxLength ? dol_trunc($description, $maxLength) : $description;
    }

    /**
     * Mesures de prevention en place pour un risque : les libelles de ses actions.
     *
     * C'est la lecture terrain du plan d'actions - ce qui est fait ou engage pour ce risque -
     * la ou summarizeActionPlan() en donne la vue d'ensemble chiffree.
     *
     * @param  int       $riskId      Identifiant du risque
     * @param  array     $riskTasks   Taches du plan d'actions, par identifiant de risque
     * @param  Translate $outputLangs Lang object
     * @param  int       $maxActions  Nombre maximal d'actions citees
     * @return string                 Mesures, vide si le risque n'en porte aucune
     */
    protected function riskMeasures(int $riskId, array $riskTasks, Translate $outputLangs, int $maxActions = 3): string
    {
        if (empty($riskTasks[$riskId])) {
            return '';
        }

        $lines = [];
        foreach ($riskTasks[$riskId] as $task) {
            if (count($lines) >= $maxActions) {
                $lines[] = '...';
                break;
            }

            $state   = ((float) $task->progress >= 100) ? $outputLangs->transnoentities('ListingRisksActionsDone') : ((int) $task->progress) . ' %';
            $lines[] = $task->label . ' (' . $state . ')';
        }

        return implode("\n", $lines);
    }
}
