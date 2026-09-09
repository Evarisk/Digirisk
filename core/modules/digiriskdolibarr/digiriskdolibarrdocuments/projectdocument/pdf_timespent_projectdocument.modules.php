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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/projectdocument/pdf_timespent_projectdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   Suivi du temps passe d'un projet en PDF natif.
 *          Le document est filtre sur une liste d'utilisateurs et une periode, puis groupe par
 *          utilisateur : une section par personne, avec la date, la tache, la description et la
 *          duree de chaque saisie, un sous total par personne et un total general.
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/lib/object.lib.php';

/**
 * Class to build the project time spent report as a PDF.
 */
class pdf_timespent_projectdocument extends SaturneDocumentModel
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
    public string $document_type = 'timespent_projectdocument';

    /**
     * @var array Bleu Digirisk #0067A6, couleur principale du document
     */
    protected array $accent = [0, 103, 166];

    /**
     * @var array Gris des entetes de tableau
     */
    protected array $headBg = [241, 243, 245];

    /**
     * @var array Fond des lignes de sous total
     */
    protected array $totalBg = [225, 236, 244];

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

        $this->name        = 'timespent_projectdocument';
        $this->description = $langs->trans('TimeSpentDocumentPDFDescription');
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
     * Ouvre une nouvelle page.
     *
     * Le pied n'est pas pose ici : TCPDF ouvre aussi des pages tout seul quand un bloc deborde,
     * et ces pages la ne passeraient jamais par cette methode. Il est ecrit en une passe finale
     * sur toutes les pages, par writeFooter().
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
     * Bandeau de titre de section.
     *
     * @param  TCPDF  $pdf   PDF handler
     * @param  string $title Titre
     * @param  string $right Texte cale a droite du bandeau, le total de la section
     * @param  float  $size  Taille de police
     * @return void
     */
    protected function sectionTitle($pdf, string $title, string $right, float $size)
    {
        // Un bandeau seul en bas de page n'a pas de sens : reserver de quoi poser aussi l'entete
        // du tableau et sa premiere ligne
        $this->checkPageBreak($pdf, 26);

        $width = $this->contentWidth($pdf);

        $pdf->Ln(2);
        $pdf->SetFont('', 'B', $size);
        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($width * 0.7, 7, ' ' . $title, 0, 0, 'L', true);
        $pdf->Cell($width * 0.3, 7, $right . ' ', 0, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(1);
    }

    /**
     * Entete d'un tableau.
     *
     * Extrait de table() pour pouvoir etre repose en haut de chaque page de continuation.
     *
     * @param  TCPDF $pdf    PDF handler
     * @param  array $header Libelles d'entete
     * @param  array $widths Largeurs de colonnes
     * @param  float $size   Taille de police
     * @return void
     */
    protected function tableHeader($pdf, array $header, array $widths, float $size)
    {
        $pdf->SetFont('', 'B', $size - 1);
        $pdf->SetFillColor($this->headBg[0], $this->headBg[1], $this->headBg[2]);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetX($this->marge_gauche);
        foreach ($header as $index => $label) {
            $pdf->Cell($widths[$index], 6, $label, 1, 0, 'C', true);
        }
        $pdf->Ln(6);
    }

    /**
     * Tableau generique.
     *
     * $rows est une liste de lignes, chaque ligne une liste de cellules. Une cellule est une
     * chaine ou ['text' => string, 'align' => 'L|C|R', 'bold' => bool, 'fill' => bool].
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
            $this->checkPageBreak($pdf, 16);
            $this->tableHeader($pdf, $header, $widths, $size);
        }

        foreach ($rows as $cells) {
            // La ligne prend la hauteur de sa cellule la plus haute
            $maxHeight = $this->height + 1;
            foreach ($cells as $index => $cellData) {
                if (!isset($widths[$index])) {
                    continue;
                }
                $text      = is_array($cellData) ? ($cellData['text'] ?? '') : $cellData;
                $height    = $pdf->getNumLines((string) $text, $widths[$index]) * $this->height;
                $maxHeight = max($maxHeight, $height);
            }

            // Comparer le numero de page avant et apres est la seule facon exacte de savoir si la
            // rupture a bien eu lieu : checkPageBreak renonce dans plusieurs cas
            $pageBefore = $pdf->getPage();
            $this->checkPageBreak($pdf, $maxHeight);
            if (!empty($header) && $pdf->getPage() != $pageBefore) {
                $this->tableHeader($pdf, $header, $widths, $size);
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
                $fill    = $isArray && !empty($cellData['fill']);

                if ($fill) {
                    $pdf->SetFillColor($this->totalBg[0], $this->totalBg[1], $this->totalBg[2]);
                }

                $pdf->SetFont('', $bold ? 'B' : '', $size - 1);
                $pdf->MultiCell($widths[$index], $maxHeight, (string) $text, 1, $align, $fill, 0, $x, $y, true, 0, false, true, $maxHeight, 'M');

                $pdf->SetXY($x + $widths[$index], $y);
            }
            $pdf->Ln($maxHeight);
        }

        $pdf->Ln(2);
    }

    /**
     * Entete du document : logo, titre, projet, periode et personnes retenues.
     *
     * @param  TCPDF     $pdf         PDF handler
     * @param  Project   $object      Projet
     * @param  Translate $outputLangs Lang object
     * @param  array     $filters     Periode et libelle des utilisateurs retenus
     * @param  float     $size        Taille de police
     * @return void
     */
    protected function _pagehead(&$pdf, $object, $outputLangs, $filters = [], $size = 9)
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
        $pdf->Cell($this->contentWidth($pdf), 9, $outputLangs->transnoentities('TimeSpentReport'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('', '', $size);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->contentWidth($pdf), 6, $object->ref . (dol_strlen($object->title) ? '  -  ' . $object->title : ''), 0, 1, 'C');

        // Filet d'accent sous le titre
        $y = $pdf->GetY() + 1;
        $pdf->SetDrawColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetLineWidth(0.6);
        $pdf->Line($this->marge_gauche, $y, $pdf->GetPageWidth() - $this->marge_droite, $y);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->Ln(4);

        // Rappel des criteres : sans eux le document ne se relit pas
        $labelWidth = 32;
        $valueWidth = $this->contentWidth($pdf) - $labelWidth;
        $lines      = [
            $outputLangs->transnoentities('Period')  => $filters['period'] ?? '',
            $outputLangs->transnoentities('Users')   => $filters['users']  ?? '',
        ];

        $pdf->SetFont('', '', $size - 1);
        foreach ($lines as $label => $value) {
            if (!dol_strlen((string) $value)) {
                continue;
            }
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetFont('', 'B', $size - 1);
            $pdf->MultiCell($labelWidth, $this->height, $label, 0, 'L', false, 0, $this->marge_gauche, $y);
            $pdf->SetFont('', '', $size - 1);
            $pdf->MultiCell($valueWidth, $this->height, $value, 0, 'L', false, 1, $this->marge_gauche + $labelWidth, $y);
        }

        $pdf->Ln(2);
    }

    /**
     * Pose le pied sur toutes les pages, une fois le contenu ecrit.
     *
     * En une passe finale plutot qu'au fil de l'eau : TCPDF ouvre des pages de lui-meme quand un
     * bloc deborde, et ces pages n'auraient sinon ni pied ni numero. Le nombre total de pages
     * n'est de toute facon connu qu'ici, TCPDI ne supportant pas AliasNbPages.
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
     * Charge les temps passes du projet, filtres puis groupes par utilisateur.
     *
     * @param  Project   $object    Projet
     * @param  array     $moreParam Filtres : timeSpentUserIds, timeSpentDateStart, timeSpentDateEnd
     * @return array|int            Tableau indexe par utilisateur, -1 en cas d'erreur SQL
     */
    protected function fetchTimeSpent($object, array $moreParam)
    {
        $userIds   = array_filter(array_map('intval', $moreParam['timeSpentUserIds'] ?? []));
        $dateStart = $moreParam['timeSpentDateStart'] ?? 0;
        $dateEnd   = $moreParam['timeSpentDateEnd'] ?? 0;

        $sql  = 'SELECT et.rowid, et.element_date, et.element_datehour, et.element_date_withhour,';
        $sql .= ' et.element_duration, et.note, et.fk_user,';
        $sql .= ' u.lastname, u.firstname, u.login,';
        $sql .= ' pt.ref as task_ref, pt.label as task_label';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_time as et';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'projet_task as pt ON pt.rowid = et.fk_element';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON u.rowid = et.fk_user';
        $sql .= " WHERE et.elementtype = 'task'";
        $sql .= ' AND pt.fk_projet = ' . (int) $object->id;
        if (!empty($userIds)) {
            $sql .= ' AND et.fk_user IN (' . $this->db->sanitize(implode(',', $userIds)) . ')';
        }
        if (!empty($dateStart)) {
            $sql .= " AND et.element_date >= '" . $this->db->idate($dateStart) . "'";
        }
        if (!empty($dateEnd)) {
            $sql .= " AND et.element_date <= '" . $this->db->idate($dateEnd) . "'";
        }
        $sql .= ' ORDER BY u.lastname ASC, u.firstname ASC, et.fk_user ASC, et.element_date ASC, et.element_datehour ASC, et.rowid ASC';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $timeSpentByUser = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $userId = (int) $obj->fk_user;
            if (!isset($timeSpentByUser[$userId])) {
                $timeSpentByUser[$userId] = [
                    'label' => dol_strlen(trim($obj->lastname . $obj->firstname)) > 0 ? dolGetFirstLastname($obj->firstname, $obj->lastname) : (string) $obj->login,
                    'total' => 0,
                    'lines' => [],
                ];
            }
            $timeSpentByUser[$userId]['total']  += (float) $obj->element_duration;
            $timeSpentByUser[$userId]['lines'][] = $obj;
        }
        $this->db->free($resql);

        return $timeSpentByUser;
    }

    /**
     * Libelle de la periode retenue, pour l'entete du document.
     *
     * @param  Translate $outputLangs Lang object
     * @param  int       $dateStart   Debut de periode
     * @param  int       $dateEnd     Fin de periode
     * @return string                 Libelle
     */
    protected function periodLabel(Translate $outputLangs, $dateStart, $dateEnd): string
    {
        if (!empty($dateStart) && !empty($dateEnd)) {
            return $outputLangs->transnoentities('FromDate') . ' ' . dol_print_date($dateStart, 'day') . ' ' . $outputLangs->transnoentities('ToDate') . ' ' . dol_print_date($dateEnd, 'day');
        }
        if (!empty($dateStart)) {
            return $outputLangs->transnoentities('FromDate') . ' ' . dol_print_date($dateStart, 'day');
        }
        if (!empty($dateEnd)) {
            return $outputLangs->transnoentities('ToDate') . ' ' . dol_print_date($dateEnd, 'day');
        }

        return $outputLangs->transnoentities('TimeSpentReportWholePeriod');
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
     * @param  array            $moreParam       Object/user/filtres du rapport
     * @return int                               1 si OK, <= 0 si KO
     * @throws Exception
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hideDetails = 0, $hideDesc = 0, $hideRef = 0, $moreParam = []): int
    {
        global $action, $conf, $hookmanager, $langs, $user;

        if (empty($moreParam)) {
            $moreParam = $objectDocument->context['moreparams'] ?? [];
        }

        $object = $moreParam['object'];

        if (!is_object($outputLangs)) {
            $outputLangs = $langs;
        }
        $outputLangs->charset_output = 'UTF-8';
        $outputLangs->loadLangs(['companies', 'projects', 'other', 'digiriskdolibarr@digiriskdolibarr']);

        // Les donnees d'abord : inutile de reserver une reference de document et un fichier pour
        // une periode sans aucune saisie
        $timeSpentByUser = $this->fetchTimeSpent($object, $moreParam);
        if (!is_array($timeSpentByUser)) {
            return -1;
        }
        if (empty($timeSpentByUser)) {
            $this->error = $outputLangs->transnoentities('TimeSpentReportNoData');
            return -1;
        }

        if (empty($conf->projet->dir_output)) {
            $this->error = $outputLangs->transnoentities('ErrorDirNotFound', 'project');
            return -1;
        }

        // Reference du document : le module de numerotation des documents de projet, deja en place
        $numberingModules = ['digiriskdolibarrdocuments/projectdocument' => getDolGlobalString('DIGIRISKDOLIBARR_PROJECTDOCUMENT_ADDON')];
        list($refModName)  = saturne_require_objects_mod($numberingModules, $this->module);

        // La reference est retenue avant create() : celui-ci remplace ensuite ref en memoire par
        // le (PROVid) de createCommon, alors que la base garde bien la reference du compteur
        $objectDocumentRef    = $refModName->getNextValue($objectDocument);
        $objectDocument->ref  = $objectDocumentRef;
        $objectDocument->type = $this->document_type;
        $objectDocumentID     = $objectDocument->create($moreParam['user'], true, $object);
        if ($objectDocumentID < 0) {
            $this->error  = $objectDocument->error;
            $this->errors = $objectDocument->errors;
            return -1;
        }
        $objectDocumentRef = dol_sanitizeFileName($objectDocumentRef);

        // Meme emplacement que les autres documents de projet : l'onglet Documents du projet les
        // liste alors sans traitement particulier
        $dir = $conf->projet->dir_output . '/' . dol_sanitizeFileName($object->ref);
        if (!file_exists($dir) && dol_mkdir($dir) < 0) {
            $this->error = $outputLangs->transnoentities('ErrorCanNotCreateDir', $dir);
            return -1;
        }

        $societyName = preg_replace('/\./', '_', getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));
        $newFileTmp  = dol_print_date(dol_now(), 'dayxcard') . '_' . $object->ref . '_' . $objectDocumentRef . '_' . $outputLangs->transnoentities('TimeSpentReport') . '_' . $societyName;
        $newFileTmp  = str_replace(' ', '_', $newFileTmp);
        $fileName    = dol_sanitizeFileName($newFileTmp) . '.pdf';
        $file        = $dir . '/' . $fileName;

        $objectDocument->setValueFrom('last_main_doc', $fileName, '', null, '', '', $moreParam['user'], '', '');
        if (!empty($objectDocument->error)) {
            $objectDocument->errors[] = $objectDocument->ref;
            setEventMessages($objectDocument->error, $objectDocument->errors, 'errors');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        $pdf  = pdf_getInstance($this->format);
        $size = pdf_getPDFFontSize($outputLangs);

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $pdf->SetFont(pdf_getPDFFont($outputLangs));
        $pdf->Open();
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTitle($outputLangs->convToOutputCharset($outputLangs->transnoentities('TimeSpentReport') . ' - ' . $object->ref));
        $pdf->SetSubject($outputLangs->transnoentities('TimeSpentReport'));
        $pdf->SetCreator('Dolibarr ' . DOL_VERSION);
        $pdf->SetAuthor($outputLangs->convToOutputCharset($user->getFullName($outputLangs)));
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->setPageOrientation($this->orientation, 1, self::FOOTER_BAND);
        $pdf->SetAutoPageBreak(1, self::FOOTER_BAND);

        $pdf->AddPage();
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', $size);

        $this->footerText = $object->ref . ' - ' . $outputLangs->transnoentities('TimeSpentReportGeneratedAt') . ' ' . dol_print_date(dol_now(), 'dayhour', 'tzuser');

        $userLabels = [];
        $grandTotal = 0;
        foreach ($timeSpentByUser as $timeSpentUser) {
            $userLabels[] = $timeSpentUser['label'];
            $grandTotal  += $timeSpentUser['total'];
        }

        $filters = [
            'period' => $this->periodLabel($outputLangs, $moreParam['timeSpentDateStart'] ?? 0, $moreParam['timeSpentDateEnd'] ?? 0),
            'users'  => implode(', ', $userLabels),
        ];

        $this->_pagehead($pdf, $object, $outputLangs, $filters, $size);

        $width  = $this->contentWidth($pdf);
        $widths = [$width * 0.13, $width * 0.29, $width * 0.45, $width * 0.13];

        // Recapitulatif en tete de document : le total par personne se lit sans tourner les pages
        if (count($timeSpentByUser) > 1) {
            $summaryRows = [];
            foreach ($timeSpentByUser as $timeSpentUser) {
                $summaryRows[] = [
                    $timeSpentUser['label'],
                    ['text' => convertSecondToTime((int) $timeSpentUser['total'], 'allhourmin'), 'align' => 'R'],
                ];
            }
            $summaryRows[] = [
                ['text' => $outputLangs->transnoentities('TimeSpentReportGrandTotal'), 'bold' => true, 'fill' => true],
                ['text' => convertSecondToTime((int) $grandTotal, 'allhourmin'), 'align' => 'R', 'bold' => true, 'fill' => true],
            ];

            $this->sectionTitle($pdf, $outputLangs->transnoentities('TimeSpentReportSummary'), '', $size);
            $this->table($pdf, [$outputLangs->transnoentities('User'), $outputLangs->transnoentities('Duration')], $summaryRows, [$width * 0.75, $width * 0.25], $size);
        }

        // Une section par personne, ses saisies dans l'ordre chronologique
        foreach ($timeSpentByUser as $timeSpentUser) {
            $rows = [];
            foreach ($timeSpentUser['lines'] as $line) {
                $date = $this->db->jdate($line->element_date);
                if (!empty($line->element_date_withhour) && !empty($line->element_datehour)) {
                    $dateLabel = dol_print_date($this->db->jdate($line->element_datehour), 'dayhour', 'tzuserrel');
                } else {
                    $dateLabel = dol_print_date($date, 'day');
                }

                $task = $line->task_ref . (dol_strlen($line->task_label) ? "\n" . $line->task_label : '');

                $rows[] = [
                    ['text' => $dateLabel, 'align' => 'C'],
                    $task,
                    dol_string_nohtmltag((string) $line->note, 0),
                    ['text' => convertSecondToTime((int) $line->element_duration, 'allhourmin'), 'align' => 'R'],
                ];
            }

            $rows[] = [
                ['text' => $outputLangs->transnoentities('Total'), 'bold' => true, 'fill' => true],
                ['text' => '', 'fill' => true],
                ['text' => '', 'fill' => true],
                ['text' => convertSecondToTime((int) $timeSpentUser['total'], 'allhourmin'), 'align' => 'R', 'bold' => true, 'fill' => true],
            ];

            $this->sectionTitle($pdf, $timeSpentUser['label'], convertSecondToTime((int) $timeSpentUser['total'], 'allhourmin'), $size);
            $this->table(
                $pdf,
                [
                    $outputLangs->transnoentities('Date'),
                    $outputLangs->transnoentities('Task'),
                    $outputLangs->transnoentities('Description'),
                    $outputLangs->transnoentities('Duration'),
                ],
                $rows,
                $widths,
                $size
            );
        }

        // Total general, y compris quand il n'y a qu'une personne et donc pas de recapitulatif
        $this->checkPageBreak($pdf, 12);
        $pdf->SetFont('', 'B', $size);
        $pdf->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($width * 0.7, 7, ' ' . $outputLangs->transnoentities('TimeSpentReportGrandTotal'), 0, 0, 'L', true);
        $pdf->Cell($width * 0.3, 7, convertSecondToTime((int) $grandTotal, 'allhourmin') . ' ', 0, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);

        $this->writeFooter($pdf);

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
}
