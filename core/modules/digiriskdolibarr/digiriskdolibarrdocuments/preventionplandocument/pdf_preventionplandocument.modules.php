<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/preventionplandocument/pdf_preventionplandocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Plan de prevention en PDF natif.
 *          Suit section par section le gabarit template_preventionplandocument.odt : entreprises,
 *          numeros d'urgence, moyens et consignes, inspection commune prealable, intervention et
 *          horaires, analyse des risques, intervenants, signatures.
 */

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturneschedules.class.php';
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';
require_once __DIR__ . '/../../../../../../saturne/lib/dolibarr.lib.php';

/**
 * Class to build the prevention plan document as a PDF.
 */
class pdf_preventionplandocument extends SaturneDocumentModel
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
    public string $document_type = 'preventionplandocument';

    /**
     * @var array Bleu Digirisk #0067A6, couleur principale du document
     */
    protected array $accent = [0, 103, 166];

    /**
     * @var array Vert Digirisk #2A9D8F, pour les intertitres des textes de configuration
     */
    protected array $accentAlt = [42, 157, 143];

    /**
     * @var array Orange Digirisk #F58A07, reserve a ce qui doit sauter aux yeux
     */
    protected array $accentWarn = [245, 138, 7];

    /**
     * @var array Gris des entetes de tableau
     */
    protected array $headBg = [241, 243, 245];

    /**
     * @var string Repertoire temporaire des signatures decodees
     */
    protected string $tmpDir = '';

    /**
     * Bande basse reservee au pied de page, en mm. Le contenu s'arrete au dessus, le pied
     * s'ecrit dedans : c'est ce qui garantit qu'ils ne se chevauchent jamais.
     */
    const FOOTER_BAND = 20;

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
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'preventionplandocument';
        $this->description = $langs->trans('PreventionPlanDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->height      = 5;
        $this->orientation = 'P';
        $this->version     = '1.0.0';
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
        $pdf->AddPage();
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
     * Tableau generique.
     *
     * $rows est une liste de lignes, chaque ligne une liste de cellules. Une cellule est une
     * chaine ou ['text' => string, 'align' => 'L|C|R', 'image' => chemin].
     *
     * @param  TCPDF $pdf     PDF handler
     * @param  array $header  Libelles d'entete, vide pour un tableau sans entete
     * @param  array $rows    Lignes
     * @param  array $widths  Largeurs de colonnes
     * @param  float $size    Taille de police
     * @return void
     */
    protected function table($pdf, array $header, array $rows, array $widths, float $size)
    {
        if (empty($rows)) {
            return;
        }

        if (!empty($header)) {
            $this->checkPageBreak($pdf, 16);
            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
            $pdf->SetX($this->marge_gauche);
            foreach ($header as $index => $label) {
                $pdf->Cell($widths[$index], 7, $label, 1, 0, 'C', true);
            }
            $pdf->Ln(7);
        }

        foreach ($rows as $cells) {
            // La ligne prend la hauteur de sa cellule la plus haute
            $maxHeight = $this->height + 1;
            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                if (is_array($cellData) && !empty($cellData['image'])) {
                    $maxHeight = max($maxHeight, 14);
                    continue;
                }
                $text   = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                $height = $pdf->getNumLines((string) $text, $widths[$index]) * $this->height;
                $maxHeight = max($maxHeight, $height);
            }

            $this->checkPageBreak($pdf, $maxHeight);
            $pdf->SetX($this->marge_gauche);
            $pdf->SetFont('', '', $size - 1);

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

                $pdf->SetFont('', $bold ? 'B' : '', $size - 1);
                $pdf->MultiCell($widths[$index], $maxHeight, (string) $text, 1, $align, false, 0, $x, $y, true, 0, false, true, $maxHeight, 'M');

                // Image centree dans la cellule, dessinee par dessus le cadre
                if ($isArray && !empty($cellData['image']) && is_readable($cellData['image'])) {
                    $imageHeight = $maxHeight - 4;
                    $pdf->Image($cellData['image'], $x + 2, $y + 2, 0, $imageHeight, '', '', '', false, 300, '', false, false, 0, 'LM');
                }

                $pdf->SetXY($x + $widths[$index], $y);
            }
            $pdf->Ln($maxHeight);
        }

        $pdf->Ln(2);
    }

    /**
     * Deux encadres cote a cote, chacun avec son titre et ses lignes libelle / valeur.
     *
     * @param  TCPDF $pdf   PDF handler
     * @param  array $left  ['title' => string, 'rows' => [[label, value], ...]]
     * @param  array $right Idem
     * @param  float $size  Taille de police
     * @return void
     */
    protected function twoBoxes($pdf, array $left, array $right, float $size)
    {
        $gap        = 6;
        $boxWidth   = ($this->contentWidth($pdf) - $gap) / 2;
        $labelWidth = $boxWidth * 0.34;
        $valueWidth = $boxWidth - $labelWidth;
        $rowCount   = max(count($left['rows']), count($right['rows']));

        // Hauteur de chaque ligne, calculee sur les deux encadres a la fois : une adresse longue
        // passe a la ligne au lieu de deborder du cadre, et les deux colonnes restent alignees
        $rowHeights = [];
        for ($index = 0; $index < $rowCount; $index++) {
            $rowHeight = $this->height + 1;
            foreach ([$left, $right] as $content) {
                if (!isset($content['rows'][$index])) {
                    continue;
                }
                $pdf->SetFont('', 'B', $size - 2);
                $rowHeight = max($rowHeight, $pdf->getStringHeight($labelWidth, ' ' . $content['rows'][$index][0]));
                $pdf->SetFont('', '', $size - 2);
                $rowHeight = max($rowHeight, $pdf->getStringHeight($valueWidth, ' ' . $content['rows'][$index][1]));
            }
            $rowHeights[$index] = $rowHeight;
        }

        $bodyHeight = array_sum($rowHeights);
        $this->reserveSpace($pdf, 10 + $bodyHeight);

        // Meme raison que pour les signatures : les deux colonnes partent du meme Y
        $pdf->SetAutoPageBreak(false);

        $startY = $pdf->GetY();

        foreach ([[$left, $this->marge_gauche], [$right, $this->marge_gauche + $boxWidth + $gap]] as $box) {
            [$content, $x] = $box;

            $pdf->SetXY($x, $startY);
            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
            $pdf->Cell($boxWidth, 7, ' ' . $content['title'], 1, 1, 'L', true);

            $rowY = $startY + 7;
            foreach ($content['rows'] as $index => $row) {
                $pdf->SetXY($x, $rowY);
                $pdf->SetFont('', 'B', $size - 2);
                $pdf->MultiCell($labelWidth, $rowHeights[$index], ' ' . $row[0], 1, 'L', false, 0);

                $pdf->SetXY($x + $labelWidth, $rowY);
                $pdf->SetFont('', '', $size - 2);
                $pdf->MultiCell($valueWidth, $rowHeights[$index], ' ' . $row[1], 1, 'L', false, 0);

                $rowY += $rowHeights[$index];
            }
        }

        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
        $pdf->SetY($startY + 7 + $bodyHeight + 2);
    }

    /**
     * Entete de page : logo, titre du document, reference et nature de l'operation.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Plan de prevention
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
                $pdf->Image($logo, $this->marge_gauche, $posY, 0, 12);
            }
        }

        $pdf->SetFont('', 'B', $size + 6);
        $pdf->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetXY($this->marge_gauche, $posY + 2);
        $pdf->Cell($this->contentWidth($pdf), 9, $outputLangs->transnoentities('PreventionPlan'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('', '', $size);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->contentWidth($pdf), 6, $object->ref . (dol_strlen($object->label) ? '  -  ' . $object->label : ''), 0, 1, 'C');

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
     * Pied de page.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Plan de prevention
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
            $pdf->SetXY($this->marge_gauche, $pdf->getPageHeight() - 12);
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
        global $action, $conf, $hookmanager, $langs, $user;

        require_once __DIR__ . '/../../../../../class/preventionplan.class.php';
        require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
        require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
        require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_mobile.lib.php';

        $object = $moreParam['object'];

        $outputLangs->loadLangs(['companies', 'projects', 'other', 'digiriskdolibarr@digiriskdolibarr']);

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

        // Meme source de donnees que le modele ODT
        $objectDocument->DigiriskFillJSON();
        $json = json_decode($objectDocument->json);
        $data = (is_object($json) && isset($json->PreventionPlan)) ? (array) $json->PreventionPlan : [];

        $this->tmpDir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/preventionplandocument/tmp';
        dol_mkdir($this->tmpDir);

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
        $pdf->SetSubject($outputLangs->transnoentities('PreventionPlan'));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, self::FOOTER_BAND);
        $pdf->SetAutoPageBreak(1, self::FOOTER_BAND);

        $pdf->AddPage();
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $size);

        $this->footerText = $object->ref . ' - ' . $outputLangs->transnoentities('AccidentInvestigationDocumentGeneratedAt') . ' ' . dol_print_date(dol_now(), 'day', 'tzuser');

        $this->_pagehead($pdf, $object, $outputLangs, $size);

        // Rappel reglementaire, en tete de document comme dans le gabarit
        $this->paragraph($pdf, $outputLangs->transnoentities('PreventionPlanLegalNotice'), $size - 2, 'I', [110, 110, 110]);

        $this->sectionCompanies($pdf, $data, $outputLangs, $size);
        $this->sectionEmergency($pdf, $data, $outputLangs, $size);
        $this->sectionMeansAndRules($pdf, $data, $outputLangs, $size);
        $this->sectionPriorVisit($pdf, $object, $data, $outputLangs, $size);
        $this->sectionIntervention($pdf, $object, $outputLangs, $size);
        $this->sectionRisks($pdf, $object, $outputLangs, $size);
        $this->sectionCertifications($pdf, $object, $outputLangs, $size);
        $this->sectionSignatures($pdf, $object, $outputLangs, $size);

        $this->_pagefooter($pdf, $object, $outputLangs, $size);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = $exception->getMessage();
            dol_syslog($this->error, LOG_ERR);
            return -1;
        }

        // Les signatures decodees ne servaient qu'a la generation
        dol_delete_dir_recursive($this->tmpDir);

        $this->result = ['fullpath' => $file];

        return 1;
    }

    /**
     * Entreprise utilisatrice et entreprise exterieure, cote a cote.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionCompanies($pdf, array $data, Translate $outputLangs, float $size)
    {
        $inside  = $data['society_inside']  ?? null;
        $outside = $data['society_outside'] ?? null;

        $this->sectionTitle($pdf, $outputLangs->transnoentities('Companies'), $size);

        $buildRows = function ($society) use ($outputLangs) {
            return [
                [$outputLangs->transnoentities('Name'), is_object($society) ? $society->name : ''],
                ['SIRET', is_object($society) ? $society->siret : ''],
                [$outputLangs->transnoentities('Address'), is_object($society) ? $society->address : ''],
                [$outputLangs->transnoentities('Zip'), is_object($society) ? $society->postal : ''],
                [$outputLangs->transnoentities('Town'), is_object($society) ? $society->town : ''],
            ];
        };

        $this->twoBoxes(
            $pdf,
            ['title' => $outputLangs->transnoentities('PreventionPlanUserCompany'), 'rows' => $buildRows($inside)],
            ['title' => $outputLangs->transnoentities('PreventionPlanExteriorCompany'), 'rows' => $buildRows($outside)],
            $size
        );
    }

    /**
     * Numeros d'urgence, en quatre colonnes.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionEmergency($pdf, array $data, Translate $outputLangs, float $size)
    {
        $this->sectionTitle($pdf, $outputLangs->transnoentities('EmergencyNumbers'), $size);

        $numbers = [
            $outputLangs->transnoentities('Firefighters') => $data['pompier_number']   ?? '',
            $outputLangs->transnoentities('Police')       => $data['police_number']    ?? '',
            'SAMU'                                        => $data['samu_number']      ?? '',
            $outputLangs->transnoentities('AnyEmergency') => $data['emergency_number'] ?? '',
        ];

        $width  = $this->contentWidth($pdf) / 4;
        $widths = array_fill(0, 4, $width);

        $this->checkPageBreak($pdf, 20);

        $pdf->SetX($this->marge_gauche);
        $pdf->SetFont('', 'B', $size - 1);
        $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
        foreach (array_keys($numbers) as $label) {
            $pdf->Cell($width, 7, $label, 1, 0, 'C', true);
        }
        $pdf->Ln(7);

        $pdf->SetX($this->marge_gauche);
        $pdf->SetFont('', 'B', $size + 2);
        $pdf->SetTextColor($this->accentWarn[0], $this->accentWarn[1], $this->accentWarn[2]);
        foreach ($numbers as $number) {
            $pdf->Cell($width, 10, $number, 1, 0, 'C');
        }
        $pdf->Ln(10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Moyens generaux mis a disposition et consignes generales.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionMeansAndRules($pdf, array $data, Translate $outputLangs, float $size)
    {
        $blocks = [
            'GeneralMeansAtDisposal' => $data['moyen_generaux_mis_disposition'] ?? '',
            'GeneralInstructions'    => $data['consigne_generale']              ?? '',
            'FirstAid'               => $data['premiers_secours']               ?? '',
        ];

        foreach ($blocks as $titleKey => $html) {
            if (!dol_strlen($html)) {
                continue;
            }
            $this->sectionTitle($pdf, $outputLangs->transnoentities($titleKey), $size);
            $this->htmlBlock($pdf, $html, $size - 1);
        }
    }

    /**
     * Rend un texte de configuration, qui est du HTML saisi dans l'editeur du module.
     *
     * Passe par writeHTML plutot que par un MultiCell sur du texte deshabille : les titres, les
     * listes et les mises en gras portent le sens de ces consignes, les aplatir donne un pave
     * illisible. Aucun saut de page force ici, TCPDF fait couler le texte tout seul.
     *
     * @param  TCPDF  $pdf  PDF handler
     * @param  string $html Contenu HTML
     * @param  float  $size Taille de police
     * @return void
     */
    protected function htmlBlock($pdf, string $text, float $size)
    {
        // DigiriskFillJSON rend deja ces textes sans balises : il ne reste que des retours a la
        // ligne, des tabulations pour les puces, et les entites non decodees ("d&#39;accident").
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $text);

        if (!dol_strlen(trim($text))) {
            return;
        }

        $width       = $this->contentWidth($pdf);
        $bulletWidth = $width - 5;
        $blankRun    = 0;

        foreach (explode("\n", $text) as $line) {
            // Une ligne indentee est un element de liste, une ligne a la marge est un intertitre
            $isBullet = (bool) preg_match('/^[\t ]+\S/', $line);
            $line     = trim($line);

            if ($line === '') {
                // Les lignes vides en rafale ne valent qu'un seul espacement
                if ($blankRun === 0) {
                    $pdf->Ln(2);
                }
                $blankRun++;
                continue;
            }
            $blankRun = 0;

            // Une ligne courte et sans ponctuation finale est un intertitre ; une phrase complete
            // reste du texte courant, sinon des paragraphes entiers virent au rouge gras
            $isHeading = !$isBullet && dol_strlen($line) <= 70 && !preg_match('/[.;:!?]$/', $line);

            if ($isBullet) {
                $pdf->SetFont('', '', $size);
                $pdf->SetTextColor(50, 50, 50);
                $y = $pdf->GetY();
                $pdf->SetXY($this->marge_gauche + 1, $y);
                $pdf->Cell(4, $this->height, '-', 0, 0, 'L');
                $pdf->SetXY($this->marge_gauche + 5, $y);
                $pdf->MultiCell($bulletWidth, $this->height, $line, 0, 'L');
            } elseif ($isHeading) {
                $pdf->Ln(1);
                $pdf->SetFont('', 'B', $size);
                $pdf->SetTextColor($this->accentAlt[0], $this->accentAlt[1], $this->accentAlt[2]);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($width, $this->height + 1, $line, 0, 'L');
            } else {
                $pdf->SetFont('', '', $size);
                $pdf->SetTextColor(50, 50, 50);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($width, $this->height, $line, 0, 'L');
            }
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

    /**
     * Inspection commune prealable : le rappel legal, la date puis les commentaires.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Plan de prevention
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionPriorVisit($pdf, $object, array $data, Translate $outputLangs, float $size)
    {
        $this->sectionTitle($pdf, $outputLangs->transnoentities('PriorVisit'), $size);

        $date = $object->prior_visit_date ? dol_print_date($object->prior_visit_date, 'day', 'tzuser') : $outputLangs->transnoentities('NoData');
        $this->paragraph($pdf, $outputLangs->transnoentities('PriorVisitIntro', $date), $size - 1, '', [40, 40, 40]);

        // Les points que l'entreprise utilisatrice doit presenter, comme dans le gabarit
        $pdf->SetFont('', '', $size - 2);
        $pdf->SetTextColor(90, 90, 90);
        foreach (explode('|', $outputLangs->transnoentities('PriorVisitCheckList')) as $item) {
            $item   = trim($item);
            $height = $pdf->getNumLines($item, $this->contentWidth($pdf) - 6) * $this->height;
            $this->checkPageBreak($pdf, $height);
            $pdf->SetX($this->marge_gauche + 3);
            $pdf->MultiCell($this->contentWidth($pdf) - 6, $this->height, '-  ' . $item, 0, 'L');
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);

        if (dol_strlen($object->prior_visit_text)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('PriorVisitComments'), $size - 1, 'B', [40, 40, 40]);
            $this->paragraph($pdf, dol_string_nohtmltag($object->prior_visit_text, 0), $size - 1, '', [40, 40, 40]);
        }
    }

    /**
     * Periode d'intervention et horaires de la semaine.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Plan de prevention
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionIntervention($pdf, $object, Translate $outputLangs, float $size)
    {
        $this->sectionTitle($pdf, $outputLangs->transnoentities('Intervention'), $size);

        $start = $object->date_start ? dol_print_date($object->date_start, 'day', 'tzuser') : '';
        $end   = $object->date_end   ? dol_print_date($object->date_end, 'day', 'tzuser')   : '';

        // Les horaires sont ceux poses sur ce plan, pas ceux du module
        $schedules  = new SaturneSchedules($this->db);
        $moreWhere  = ' AND element_id = ' . ((int) $object->id);
        $moreWhere .= " AND element_type = '" . $this->db->escape($object->element) . "'";
        $moreWhere .= ' AND status = 1';
        $schedules->fetch(0, '', $moreWhere);

        $days = [
            $outputLangs->transnoentities('MondayMin')    => $schedules->monday,
            $outputLangs->transnoentities('TuesdayMin')   => $schedules->tuesday,
            $outputLangs->transnoentities('WednesdayMin') => $schedules->wednesday,
            $outputLangs->transnoentities('ThursdayMin')  => $schedules->thursday,
            $outputLangs->transnoentities('FridayMin')    => $schedules->friday,
            $outputLangs->transnoentities('SaturdayMin')  => $schedules->saturday,
            $outputLangs->transnoentities('SundayMin')    => $schedules->sunday,
        ];

        $hasSchedule = false;
        foreach ($days as $value) {
            if (dol_strlen(trim((string) $value))) {
                $hasSchedule = true;
                break;
            }
        }
        // La phrase n'annonce des horaires que s'il y en a : sinon elle laissait un vide
        $sentence = $hasSchedule
            ? $outputLangs->transnoentities('InterventionPeriodSentence', $start, $end)
            : $outputLangs->transnoentities('InterventionPeriodOnly', $start, $end);
        $this->paragraph($pdf, $sentence, $size - 1, '', [40, 40, 40]);

        if (!$hasSchedule) {
            return;
        }

        $firstWidth = $this->contentWidth($pdf) * 0.16;
        $dayWidth   = ($this->contentWidth($pdf) - $firstWidth) / 7;

        $this->checkPageBreak($pdf, 25);

        $pdf->SetX($this->marge_gauche);
        $pdf->SetFont('', 'B', $size - 2);
        $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
        $pdf->Cell($firstWidth, 7, '', 1, 0, 'C', true);
        foreach (array_keys($days) as $dayLabel) {
            $pdf->Cell($dayWidth, 7, $dayLabel, 1, 0, 'C', true);
        }
        $pdf->Ln(7);

        foreach ([0 => $outputLangs->transnoentities('Morning'), 1 => $outputLangs->transnoentities('Afternoon')] as $slot => $slotLabel) {
            $pdf->SetX($this->marge_gauche);
            $pdf->SetFont('', 'B', $size - 2);
            $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
            $pdf->Cell($firstWidth, 7, $slotLabel, 1, 0, 'L', true);
            $pdf->SetFont('', '', $size - 2);
            foreach ($days as $value) {
                $parts = explode(' ', (string) $value);
                $pdf->Cell($dayWidth, 7, $parts[$slot] ?? '', 1, 0, 'C');
            }
            $pdf->Ln(7);
        }

        $pdf->Ln(3);
    }

    /**
     * Analyse des actions, des risques et des moyens de prevention.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  object    $object      Plan de prevention
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionRisks($pdf, $object, Translate $outputLangs, float $size)
    {
        $line  = new PreventionPlanLine($this->db);
        $risk  = new Risk($this->db);
        $lines = $line->fetchAll('', '', 0, 0, ['fk_preventionplan' => $object->id]);

        $this->sectionTitle($pdf, $outputLangs->transnoentities('RiskAnalysis'), $size);

        if (!is_array($lines) || empty($lines)) {
            $this->paragraph($pdf, $outputLangs->transnoentities('NoData'), $size - 1, 'I', [120, 120, 120]);
            return;
        }

        $this->paragraph($pdf, $outputLangs->transnoentities('RiskAnalysisIntro', count($lines)), $size - 2, '', [110, 110, 110]);

        $object->fetch_optionals();
        $protections = !empty($object->array_options['options_mobile_protections'])    ? json_decode($object->array_options['options_mobile_protections'], true)    : [];
        $companies   = !empty($object->array_options['options_mobile_risk_companies']) ? json_decode($object->array_options['options_mobile_risk_companies'], true) : [];

        $signalisationFile = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
        $signalisationMap  = [];
        if (file_exists($signalisationFile)) {
            foreach ((json_decode(file_get_contents($signalisationFile), true) ?: []) as $signalisationItem) {
                $signalisationMap[$signalisationItem['position']] = $signalisationItem;
            }
        }

        $digiriskElement = new DigiriskElement($this->db);

        foreach ($lines as $riskLine) {
            $category  = (int) $riskLine->category;
            $thumb     = $risk->getDangerCategory($riskLine);
            $pictoPath = ($thumb != -1) ? DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $thumb . '.png' : '';
            $riskName  = $risk->getDangerCategoryName($riskLine);

            $riskProtections = [];
            if (is_array($protections)) {
                foreach ($protections as $protection) {
                    if (!isset($protection['risk_category'], $signalisationMap[$protection['position']]) || (int) $protection['risk_category'] !== $category) {
                        continue;
                    }
                    $riskProtections[] = [
                        'image'   => DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/' . $signalisationMap[$protection['position']]['name_thumbnail'],
                        'name'    => $signalisationMap[$protection['position']]['name'],
                        'comment' => $protection['comment'] ?? '',
                    ];
                }
            }

            $riskPhotos = digiriskMobileGetRiskPhotos($object->element, $object->ref, $category);

            $this->drawRiskBlock($pdf, $riskLine, $riskName, $pictoPath, $riskProtections, $riskPhotos, $companies, $digiriskElement, $outputLangs, $size);
        }
    }

    /**
     * Un bloc par risque : bandeau picto + nom, description et entreprises concernees, puis les
     * moyens de protection en pictos et les photos prises sur le terrain.
     *
     * @param  TCPDF           $pdf             PDF handler
     * @param  object          $riskLine        Ligne de plan de prevention
     * @param  string          $riskName        Nom de la categorie de danger
     * @param  string          $pictoPath       Chemin du picto de danger
     * @param  array           $protections     Protections rattachees au risque
     * @param  array           $photos          Photos du risque
     * @param  array           $companies       Entreprises concernees par categorie
     * @param  DigiriskElement $digiriskElement Instance reutilisee pour le GP/UT
     * @param  Translate       $outputLangs     Lang object
     * @param  float           $size            Taille de police
     * @return void
     */
    protected function drawRiskBlock($pdf, $riskLine, string $riskName, string $pictoPath, array $protections, array $photos, array $companies, $digiriskElement, Translate $outputLangs, float $size)
    {
        $width    = $this->contentWidth($pdf);
        $category = (int) $riskLine->category;

        // Le bloc ne doit pas etre coupe juste apres son bandeau
        // Un risque ne doit pas etre coupe : on mesure le bloc entier avant de le poser
        $this->checkPageBreak($pdf, $this->measureRiskBlock($pdf, $riskLine, $protections, $photos, $size));

        // Bandeau du risque : picto a gauche, nom et reference a droite
        $headY = $pdf->GetY();
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($width, 14, '', 1, 1, 'L', true);

        if (dol_strlen($pictoPath) && is_readable($pictoPath)) {
            $pdf->Image($pictoPath, $this->marge_gauche + 2, $headY + 1, 0, 12);
        }

        $pdf->SetXY($this->marge_gauche + 17, $headY + 2);
        $pdf->SetFont('', 'B', $size);
        $pdf->Cell($width - 20, 5, ($riskName != -1 ? $riskName : $riskLine->ref), 0, 1, 'L');

        $pdf->SetXY($this->marge_gauche + 17, $headY + 7.5);
        $pdf->SetFont('', '', $size - 3);
        $pdf->SetTextColor(120, 120, 120);
        $subTitle = $riskLine->ref;
        if ($riskLine->fk_element > 0 && $digiriskElement->fetch($riskLine->fk_element) > 0) {
            $subTitle .= '  -  ' . $digiriskElement->ref . ' ' . $digiriskElement->label;
        }
        if (isset($companies[(string) $category])) {
            $concerned = [];
            if (!empty($companies[(string) $category]['eu'])) {
                $concerned[] = $outputLangs->transnoentities('MobilePPUserCompanyShort');
            }
            if (!empty($companies[(string) $category]['ee'])) {
                $concerned[] = $outputLangs->transnoentities('MobilePPExteriorCompanyShort');
            }
            if (!empty($concerned)) {
                $subTitle .= '  -  ' . $outputLangs->transnoentities('MobilePPConcernedCompanies') . ' : ' . implode(' / ', $concerned);
            }
        }
        $pdf->Cell($width - 20, 4, $subTitle, 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetY($headY + 14);
        $pdf->Ln(1.5);

        // Description, sans cadre : le bandeau suffit a delimiter le bloc
        if (dol_strlen($riskLine->description)) {
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetX($this->marge_gauche + 2);
            $pdf->MultiCell($width - 4, $this->height, $riskLine->description, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }

        // Moyens de prevention saisis sur la fiche, quand il y en a
        if (dol_strlen($riskLine->prevention_method)) {
            $this->blockLabel($pdf, $outputLangs->transnoentities('PreventionMethod'), $size);
            $pdf->SetFont('', '', $size - 1);
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetX($this->marge_gauche + 2);
            $pdf->MultiCell($width - 4, $this->height, $riskLine->prevention_method, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }

        $this->drawProtections($pdf, $protections, $outputLangs, $size);
        $this->drawRiskPhotos($pdf, $photos, $outputLangs, $size);

        // Filet de separation entre deux risques
        $pdf->Ln(2);
        $y = $pdf->GetY();
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->Line($this->marge_gauche, $y, $pdf->GetPageWidth() - $this->marge_droite, $y);
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->Ln(3);
    }

    /**
     * Hauteur totale d'un bloc risque, entete comprise.
     *
     * Sert a decider s'il tient sur la page courante. Les constantes suivent celles employees
     * par drawRiskBlock, drawProtections et drawRiskPhotos : les faire diverger reintroduirait
     * des risques coupes en deux.
     *
     * @param  TCPDF  $pdf         PDF handler
     * @param  object $riskLine    Ligne de plan de prevention
     * @param  array  $protections Protections du risque
     * @param  array  $photos      Photos du risque
     * @param  float  $size        Taille de police
     * @return float               Hauteur en mm
     */
    protected function measureRiskBlock($pdf, $riskLine, array $protections, array $photos, float $size): float
    {
        $width  = $this->contentWidth($pdf);
        $height = 14 + 1.5; // bandeau + interligne

        $pdf->SetFont('', '', $size - 1);
        if (dol_strlen($riskLine->description)) {
            $height += $pdf->getNumLines($riskLine->description, $width - 4) * $this->height;
        }
        if (dol_strlen($riskLine->prevention_method)) {
            $height += 5 + $pdf->getNumLines($riskLine->prevention_method, $width - 4) * $this->height;
        }

        if (!empty($protections)) {
            $perRow  = max(1, (int) floor($width / 20));
            $height += 5 + ((int) ceil(count($protections) / $perRow)) * 18;
        }

        if (!empty($photos)) {
            $perRow  = max(1, (int) floor($width / 37));
            $height += 5 + ((int) ceil(count($photos) / $perRow)) * 30;
        }

        return $height + 5; // filet de separation
    }

    /**
     * Petit intitule gris au dessus d'une sous partie d'un bloc risque.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $label Intitule
     * @param  float  $size  Taille de police
     * @return void
     */
    protected function blockLabel($pdf, string $label, float $size)
    {
        $pdf->Ln(1);
        $pdf->SetFont('', 'B', $size - 3);
        $pdf->SetTextColor(130, 130, 130);
        $pdf->SetX($this->marge_gauche + 2);
        $pdf->Cell($this->contentWidth($pdf) - 4, 4, dol_strtoupper($label), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Bandeau des moyens de protection d'un risque : le picto EPI et son nom dessous.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $protections Protections du risque
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function drawProtections($pdf, array $protections, Translate $outputLangs, float $size)
    {
        if (empty($protections)) {
            return;
        }

        $width     = $this->contentWidth($pdf);
        // Sans libelle sous le picto, la cellule se resserre autour de l'image
        $cellWidth = 20;
        $rowHeight = 18;
        $perRow    = max(1, (int) floor($width / $cellWidth));
        $rowCount  = (int) ceil(count($protections) / $perRow);

        $this->checkPageBreak($pdf, 8 + $rowCount * $rowHeight);

        $this->blockLabel($pdf, $outputLangs->transnoentities('MobilePPProtections'), $size);

        $index = 0;
        while ($index < count($protections)) {
            $slice  = array_slice($protections, $index, $perRow);
            $startY = $pdf->GetY();

            $pdf->SetX($this->marge_gauche);
            $pdf->Cell($width, $rowHeight, '', 0, 1, 'L');

            $x = $this->marge_gauche + 2;
            foreach ($slice as $protection) {
                if (is_readable($protection['image'])) {
                    // Le picto seul : le libelle sous chaque pictogramme alourdissait la page
                    $pdf->Image($protection['image'], $x + ($cellWidth - 14) / 2, $startY + 2, 14, 14);
                }
                $x += $cellWidth;
            }

            $pdf->SetY($startY + $rowHeight);
            $index += $perRow;
        }
    }

    /**
     * Bandeau des photos du risque, en vignettes alignees.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $photos      Photos du risque, entrees de saturne_get_media_files
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function drawRiskPhotos($pdf, array $photos, Translate $outputLangs, float $size)
    {
        if (empty($photos)) {
            return;
        }

        $width       = $this->contentWidth($pdf);
        $photoWidth  = 34;
        $photoHeight = 26;
        $perRow      = max(1, (int) floor($width / ($photoWidth + 2)));
        $rowCount    = (int) ceil(count($photos) / $perRow);

        $this->checkPageBreak($pdf, 8 + $rowCount * ($photoHeight + 4));

        $this->blockLabel($pdf, $outputLangs->transnoentities('PreventionPlanRiskPhotos'), $size);

        $index = 0;
        while ($index < count($photos)) {
            $slice  = array_slice($photos, $index, $perRow);
            $startY = $pdf->GetY();

            $pdf->SetX($this->marge_gauche);
            $pdf->Cell($width, $photoHeight + 4, '', 0, 1, 'L');

            $x = $this->marge_gauche + 2;
            foreach ($slice as $photo) {
                // fullname pointe le fichier sur disque, l'url ne sert qu'a l'ecran
                $path = $photo['fullname'] ?? '';
                if (dol_strlen($path) && is_readable($path)) {
                    // Cadre fixe puis image ajustee dedans : sans le cadre, chaque photo prend une
                    // taille differente selon son ratio et la rangee part de travers
                    $pdf->Rect($x, $startY + 2, $photoWidth, $photoHeight);
                    $pdf->Image($path, $x, $startY + 2, $photoWidth, $photoHeight, '', '', '', false, 300, '', false, false, 0, 'CM', false, false);
                }
                $x += $photoWidth + 3;
            }

            $pdf->SetY($startY + $photoHeight + 4);
            $index += $perRow;
        }
    }

    /**
     * Intervenants de l'entreprise exterieure.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionCertifications($pdf, $object, Translate $outputLangs, float $size)
    {
        $mobileCertifications = !empty($object->array_options['options_mobile_certifications']) ? json_decode($object->array_options['options_mobile_certifications'], true) : [];
        if (empty($mobileCertifications)) {
            return;
        }

        require_once dol_buildpath('/digiriskdolibarr/lib/digiriskdolibarr_mobile.lib.php', 0);
        $certificationOptions = digiriskGetCertificationOptions(false);

        $this->sectionTitle($pdf, $outputLangs->transnoentities('MobilePPCertifications'), $size);

        $width  = $this->contentWidth($pdf);
        $widths = [$width * 0.85, $width * 0.15];
        $header = [
            $outputLangs->transnoentities('Label'),
            $outputLangs->transnoentities('MobilePPMandatory')
        ];

        $rows = [];
        foreach ($mobileCertifications as $mobileCertification) {
            $certLabel = isset($certificationOptions[$mobileCertification['code']]) ? $certificationOptions[$mobileCertification['code']] : $mobileCertification['code'];
            $mandatoryLabel = !empty($mobileCertification['mandatory']) ? $outputLangs->transnoentities('Yes') : $outputLangs->transnoentities('No');
            $rows[] = [
                $certLabel,
                ['text' => $mandatoryLabel, 'align' => 'C']
            ];
        }

        $this->table($pdf, $header, $rows, $widths, $size);
    }

    /**
     * Libelle court du statut d'un signataire.
     *
     * @param  object    $signatory   Signataire
     * @param  Translate $outputLangs Lang object
     * @return string                 Libelle
     */
    protected function signatoryStatus($signatory, Translate $outputLangs): string
    {
        if (!empty($signatory->signature) && $signatory->signature != $outputLangs->transnoentities('FileGenerated')) {
            return $outputLangs->transnoentities('Signed');
        }

        return $outputLangs->transnoentities('SignaturePending');
    }

    /**
     * Signatures des deux responsables, cote a cote, avec l'image quand elle existe.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  array     $data        Donnees du document
     * @param  Translate $outputLangs Lang object
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function sectionSignatures($pdf, $object, Translate $outputLangs, float $size)
    {
        // Le JSON du document ne porte pas les signataires : on les lit par leur role, sinon les
        // deux encadres restent vides alors que l'EU a signe des la creation
        global $conf;

        $signatory = new SaturneSignature($this->db, $this->module, $object->element);
        
        $masters   = $signatory->fetchSignatory('MasterWorker', $object->id, $object->element);
        $exteriors = $signatory->fetchSignatory('ExtSocietyResponsible', $object->id, $object->element);

        $master   = (is_array($masters) && !empty($masters)) ? array_shift($masters) : null;
        $exterior = (is_array($exteriors) && !empty($exteriors)) ? array_shift($exteriors) : null;

        $gap       = 6;
        $boxWidth  = ($this->contentWidth($pdf) - $gap) / 2;
        $boxHeight = 7 + 5 * ($this->height + 1) + 26;

        // Le titre doit rester avec ses encadres, sinon il finit seul en bas de la page precedente
        $this->reserveSpace($pdf, $boxHeight + 20);

        $this->sectionTitle($pdf, $outputLangs->transnoentities('Signatures'), $size);

        // La rupture automatique est coupee pendant le dessin : il faut donc s'assurer nous-memes
        // que le bloc entier tient, sinon il deborde dans la bande du pied de page
        $this->reserveSpace($pdf, $boxHeight + 4);

        // Les deux encadres partent du meme Y : une rupture automatique au milieu casserait la
        // mise en page et laisserait une page a moitie vide derriere
        $pdf->SetAutoPageBreak(false);

        $startY = $pdf->GetY();

        $boxes = [
            [$outputLangs->transnoentities('PreventionPlanUserCompanyResponsible'), $master, $this->marge_gauche],
            [$outputLangs->transnoentities('PreventionPlanExteriorCompanyResponsible'), $exterior, $this->marge_gauche + $boxWidth + $gap],
        ];

        foreach ($boxes as $box) {
            [$title, $signatory, $x] = $box;

            $pdf->SetXY($x, $startY);
            $pdf->SetFont('', 'B', $size - 1);
            $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
            $pdf->Cell($boxWidth, 7, ' ' . $title, 1, 1, 'L', true);

            $labelWidth = $boxWidth * 0.34;
            $rows       = [
                [$outputLangs->transnoentities('Lastname'), is_object($signatory) ? dol_strtoupper($signatory->lastname ?? '') : ''],
                [$outputLangs->transnoentities('Firstname'), is_object($signatory) ? ucfirst($signatory->firstname ?? '') : ''],
                [$outputLangs->transnoentities('Email'), is_object($signatory) ? ($signatory->email ?? '') : ''],
                [$outputLangs->transnoentities('Phone'), is_object($signatory) ? ($signatory->phone ?? '') : ''],
                [$outputLangs->transnoentities('Date'), (is_object($signatory) && !empty($signatory->signature_date)) ? dol_print_date($signatory->signature_date, 'day', 'tzuser') : ''],
            ];

            foreach ($rows as $row) {
                $pdf->SetX($x);
                $pdf->SetFont('', 'B', $size - 2);
                $pdf->Cell($labelWidth, $this->height + 1, ' ' . $row[0], 1, 0, 'L');
                $pdf->SetFont('', '', $size - 2);
                $pdf->Cell($boxWidth - $labelWidth, $this->height + 1, ' ' . $row[1], 1, 1, 'L');
            }

            // Cadre de signature
            $signY = $pdf->GetY();
            $pdf->Rect($x, $signY, $boxWidth, 26);
            $imagePath = $this->extractSignature($signatory, $outputLangs);
            if (dol_strlen($imagePath)) {
                $pdf->Image($imagePath, $x + 4, $signY + 3, 0, 20);
            }
        }

        $pdf->SetAutoPageBreak(true, self::FOOTER_BAND);
        $pdf->SetY($startY + $boxHeight + 2);
    }

    /**
     * Decode la signature base64 d'un signataire dans un fichier temporaire.
     *
     * @param  object|null $signatory   Signataire
     * @param  Translate   $outputLangs Lang object
     * @return string                   Chemin du fichier, vide si aucune signature
     */
    protected function extractSignature($signatory, Translate $outputLangs): string
    {
        if (!is_object($signatory) || empty($signatory->signature) || $signatory->signature == $outputLangs->transnoentities('FileGenerated')) {
            return '';
        }

        $imagePath = tempnam(sys_get_temp_dir(), 'sig_');
        // TCPDF requires an extension to determine image type. We rename the temp file to have a .png extension.
        rename($imagePath, $imagePath . '.png');
        $imagePath .= '.png';

        $signatureData = explode(',', $signatory->signature);
        if (count($signatureData) > 1) {
            file_put_contents($imagePath, base64_decode($signatureData[1]));
        } else {
            file_put_contents($imagePath, base64_decode($signatory->signature));
        }

        return $imagePath;
    }
}
