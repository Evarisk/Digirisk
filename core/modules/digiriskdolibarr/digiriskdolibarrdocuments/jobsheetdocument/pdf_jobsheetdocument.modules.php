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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/jobsheetdocument/pdf_jobsheetdocument.modules.php
 * \ingroup digiriskdolibarr
 * \brief   PDF of a job sheet ("fiche de poste") laid out after the model of the INRS ED 126 sheet
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

// Load Saturne libraries
dol_include_once('/saturne/core/modules/saturne/modules_saturne.php');
dol_include_once('/saturne/lib/dolibarr.lib.php');

/**
 * Class pdf_jobsheetdocument
 *
 * One A4 portrait page when the data allows it, in black and white to be printed and posted at the work station.
 * The eight headings follow the order of the model: version, machine and photo, protective equipment,
 * tasks exposing the operator with their dangers and prevention measures, qualification, emergency.
 * A heading without data keeps dotted lines, to be completed by hand on the printed sheet.
 * The machine comes from the more params of the generation (fk_product), the GP/UT is the object of the document.
 */
class pdf_jobsheetdocument extends SaturneDocumentModel
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
    public string $document_type = 'jobsheetdocument';

    /**
     * @var float Page width, height and margins, in mm
     */
    public float $pageWidth    = 210;
    public float $pageHeight   = 297;
    public float $marginLeft   = 10;
    public float $marginRight  = 10;
    public float $marginTop    = 10;
    public float $marginBottom = 14;

    /**
     * @var Translate Output language of the file being written
     */
    private $outputLangs;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db, $this->module, $this->document_type);

        $this->name        = 'jobsheetdocument';
        $this->description = $langs->trans('JobSheetDocumentPDFDescription');
        $this->type        = 'pdf';
        $this->version     = '1.0.0';
    }

    /**
     * Write the PDF file of the job sheet
     *
     * @param  SaturneDocuments $objectDocument  Document being generated
     * @param  Translate        $outputLangs     Output language
     * @param  string           $srcTemplatePath Unused, no template for a PDF model
     * @param  int              $hideDetails     Unused
     * @param  int              $hideDesc        Unused
     * @param  int              $hideRef         Unused
     * @param  array            $moreParam       More param: object (the GP/UT), fk_product (the machine, 0 for none), user
     * @return int                               1 if OK, <0 if KO
     * @throws Exception
     */
    public function write_file($objectDocument, $outputLangs, $srcTemplatePath = '', $hideDetails = 0, $hideDesc = 0, $hideRef = 0, $moreParam = []): int
    {
        global $action, $hookmanager, $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        dol_include_once('/digiriskdolibarr/class/jobsheet.class.php');

        $moreParam = self::getMoreParam($objectDocument, $moreParam);
        $object    = $moreParam['object'];

        $this->outputLangs = $outputLangs;
        $outputLangs->loadLangs(['main', 'products', 'digiriskdolibarr@digiriskdolibarr']);

        $product   = null;
        $productId = (int) ($moreParam['fk_product'] ?? 0);
        if ($productId > 0) {
            $product = new Product($this->db);
            if ($product->fetch($productId) <= 0) {
                $product = null;
            }
        }

        // The machine goes in the file name, next to the ref of the GP/UT and of the document
        $moreParam['hideTemplateName'] = 1;
        $moreParam['documentName']     = (is_object($product) ? $product->ref : $outputLangs->transnoentities('JobSheetWorkStation')) . '_';

        // buildDocumentFilename rend -1 en cas d'echec, sinon le chemin du fichier : comparer a la chaine
        $file = $this->buildDocumentFilename($objectDocument, $outputLangs, $object, $moreParam);
        if (!is_string($file) || empty($file)) {
            $this->error = $langs->transnoentities('ErrorFileNameCanNotBeBuilt');
            return -1;
        }

        $hookmanager->initHooks(['pdfgeneration']);
        $parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputLangs];
        $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);

        // The document of this generation is already created: it is counted in the versions of the sheet.
        // Its ref is read back from the database, createCommon() left '(PROVid)' in memory
        $jobSheetDocument = new JobSheetDocument($this->db);
        $jobSheet         = new JobSheet($this->db);
        $data             = $jobSheet->load($object, $product, $jobSheetDocument->countVersions($object, $productId));
        if ($jobSheetDocument->fetch($objectDocument->id) > 0) {
            $data['ref'] = $jobSheetDocument->ref;
        }

        $pageCount = $this->writePdf(pdf_getInstance([$this->pageWidth, $this->pageHeight]), $data, $file, $user);
        if ($pageCount < 0) {
            return -1;
        }

        // The ED 126 model aims at one side of A4 or A3: the page tells when the sheet runs over
        if ($pageCount > 1) {
            setEventMessages($langs->trans('JobSheetMoreThanOnePage', $pageCount), [], 'warnings');
        }

        $this->result = ['fullpath' => $file];

        return 1;
    }

    /**
     * Draw the sheet and save it
     *
     * @param  mixed  $pdf  PDF handler, a TCPDF instance
     * @param  array  $data Data of the sheet, from JobSheet::load()
     * @param  string $file Full path of the file to write
     * @param  User   $user User generating the sheet
     * @return int          Number of pages, <0 if KO
     */
    protected function writePdf($pdf, array $data, string $file, User $user): int
    {
        $outputLangs = $this->outputLangs;
        $element     = $data['element'];

        $pdf->SetCreator('Dolibarr & Digirisk');
        $pdf->SetAuthor($user->getFullName($outputLangs));
        $pdf->SetTitle($outputLangs->transnoentities('JobSheet') . ' ' . $element->ref . ' ' . $data['machine']['label']);
        $pdf->SetSubject($outputLangs->transnoentities('JobSheetPdfSubject'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins($this->marginLeft, $this->marginTop, $this->marginRight);
        $pdf->SetAutoPageBreak(true, $this->marginBottom);
        $pdf->setCellHeightRatio(1.1);
        // Lists of the instruction sheet chapters are written tight, the sheet aims at one side of A4
        $noSpace = [0 => ['h' => 0, 'n' => 0], 1 => ['h' => 0, 'n' => 0]];
        $pdf->setHtmlVSpace(['ul' => $noSpace, 'ol' => $noSpace, 'p' => $noSpace, 'div' => $noSpace]);
        $pdf->setListIndentWidth(3);
        $pdf->SetFont(pdf_getPDFFont($outputLangs), '', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->AddPage();

        foreach ($this->buildSections($data) as $key => $section) {
            // TCPDF already breaks the line around a list, a <br/> next to it would leave an empty line
            $section = preg_replace('/(<br\s*\/?>\s*)+(<(ul|ol)>)/i', '$2', $section);
            $section = preg_replace('/(<\/(ul|ol)>)\s*<br\s*\/?>/i', '$1', $section);

            if ($key === 'tasks') {
                // The tasks table may run over the next page, its rows never split and its header is repeated, but it does not start at the very bottom
                if ($pdf->GetY() > $this->pageHeight - $this->marginBottom - 30) {
                    $pdf->AddPage();
                }
                $pdf->writeHTML($section, false, false, true, false, '');
            } else {
                // Any other block is kept whole: written again on a new page when it did not fit
                $startPage = $pdf->getPage();
                $pdf->startTransaction();
                $pdf->writeHTML($section, false, false, true, false, '');
                if ($pdf->getPage() > $startPage) {
                    $pdf->rollbackTransaction(true);
                    $pdf->AddPage();
                    $pdf->writeHTML($section, false, false, true, false, '');
                } else {
                    $pdf->commitTransaction();
                }
            }
            $pdf->SetY($pdf->GetY() + 2);
        }

        $pageCount = $pdf->getNumPages();
        $this->drawFooters($pdf, $data, $pageCount);

        try {
            $pdf->Output($file, 'F');
        } catch (Exception $exception) {
            $this->error = $exception->getMessage();
            dol_syslog($this->error, LOG_ERR);
            return -1;
        }
        dolChmod($file);

        return $pageCount;
    }

    /**
     * HTML of each section of the sheet, written one after the other
     *
     * @param  array    $data Data of the sheet
     * @return string[]       Sections, the table of the tasks under the key 'tasks'
     */
    private function buildSections(array $data): array
    {
        $langs   = $this->outputLangs;
        $element = $data['element'];
        $parent  = $data['parent'];
        $dots    = '<span style="color:#808080;">' . str_repeat('.', 70) . '</span>';

        $sections = [];

        // 1 Version · title · company and workshop
        $logo = '';
        if (!empty($data['company']['logo'])) {
            $logo = $this->imgTag($data['company']['logo'], 38, 11) . '<br/>';
        }
        $workshop = '';
        if (is_object($parent)) {
            $workshop .= $langs->transnoentities('JobSheetWorkshop') . ' : ' . $this->text($parent->ref . ' - ' . $parent->label) . '<br/>';
        }
        $workshop .= $langs->transnoentities('JobSheetWorkStation') . ' : <b>' . $this->text($element->ref . ' - ' . $element->label) . '</b>';

        $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
            . '<td width="26%"><span style="font-size:8pt;">' . $langs->transnoentities('JobSheetVersionLabel') . '</span><br/><b style="font-size:11pt;">' . $langs->transnoentities('JobSheetVersionValue', $data['version']) . '</b><br/>'
            . $langs->transnoentities('JobSheetVersionDate', dol_print_date($data['date'], 'day', false, $langs)) . (!empty($data['ref']) ? ' - ' . $this->text($data['ref']) : '') . '</td>'
            . '<td width="40%" align="center"><span style="font-size:18pt;font-weight:bold;">' . dol_strtoupper($langs->transnoentities('JobSheet')) . '</span><br/><b>' . $this->text($data['machine']['label']) . '</b></td>'
            . '<td width="34%" align="right">' . $logo . '<b>' . $this->text($data['company']['name']) . '</b><br/><span style="font-size:8pt;">' . $workshop . '</span></td>'
            . '</tr></table>';

        // 2 Machine identification and photo
        $characteristics = $this->html($data['machine']['characteristics']);
        $identification  = '<b>' . $langs->transnoentities('JobSheetMachine') . ' :</b> ' . (!empty($data['machine']['label']) ? $this->text($data['machine']['label']) : $dots) . '<br/>'
            . '<b>' . $langs->transnoentities('JobSheetBrandType') . ' :</b> ' . (!empty($data['machine']['ref']) ? $this->text($data['machine']['ref']) : $dots) . '<br/><br/>'
            . '<b>' . $langs->transnoentities('JobSheetCharacteristics') . ' :</b><br/>' . (!empty($characteristics) ? $characteristics : $dots . '<br/>' . $dots) . '<br/><br/>'
            . '<b>' . $langs->transnoentities('JobSheetCommissioningDate') . ' :</b><br/>' . $dots . '<br/><br/>'
            . '<b>' . $langs->transnoentities('JobSheetProductsToUse') . ' :</b><br/>' . $dots . '<br/>' . $dots;

        $photo = '';
        if (!empty($data['photo']['path'])) {
            $photo = $this->imgTag($data['photo']['path'], 100, 55);
        }
        if (empty($photo)) {
            $photo = '<br/><br/><span style="color:#808080;">' . $langs->transnoentities('JobSheetPhotoPlaceholder') . '</span><br/><br/>';
        }
        $usermanual = $this->html($data['machine']['usermanual']);
        $operating  = '<b>' . $langs->transnoentities('JobSheetOperating') . ' :</b><br/>';
        $operating .= !empty($usermanual) ? $usermanual : $langs->transnoentities('JobSheetStart') . ' : ' . $dots . '<br/>' . $langs->transnoentities('JobSheetEmergencyStop') . ' : ' . $dots . '<br/>' . $langs->transnoentities('JobSheetOutput') . ' : ' . $dots;

        $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
            . '<td width="42%">' . $identification . '</td>'
            . '<td width="58%"><div style="text-align:center;">' . $photo . '</div>' . $operating . '</td>'
            . '</tr></table>';

        // 3 Personal protective equipment, mandatory then forbidden
        $epiCells = [];
        foreach (['mandatory', 'forbidden'] as $kind) {
            foreach ($data['epi'][$kind] as $epi) {
                $caption = $this->text(dol_trunc($epi['label'], 38));
                if (!empty($epi['comments'])) {
                    $caption .= '<br/><i>' . $this->text(dol_trunc(implode(' ; ', $epi['comments']), 40)) . '</i>';
                }
                if ($kind == 'forbidden') {
                    $caption = '<b>' . dol_strtoupper($langs->transnoentities('JobSheetForbidden')) . '</b><br/>' . $caption;
                }
                $epiCells[] = '<td width="16.66%" align="center">' . (!empty($epi['picto']) ? $this->imgTag($epi['picto'], 13, 13) . '<br/>' : '') . '<span style="font-size:6.5pt;">' . $caption . '</span></td>';
            }
        }
        if (!empty($epiCells)) {
            $epiRows = '';
            foreach (array_chunk($epiCells, 6) as $chunk) {
                $epiRows .= '<tr>' . implode('', $chunk) . str_repeat('<td width="16.66%"></td>', 6 - count($chunk)) . '</tr>';
            }
            $epiContent = '<table cellspacing="0" cellpadding="2" border="0">' . $epiRows . '</table>';
        } else {
            $epiContent = $dots . '<br/>' . $dots;
        }
        $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
            . '<td width="18%" bgcolor="#E6E6E6"><b>' . $langs->transnoentities('JobSheetEpiTitle') . ' :</b></td>'
            . '<td width="82%">' . $epiContent . '</td>'
            . '</tr></table>';

        // General safety instructions of the machine, when its instruction sheet has them
        $security = $this->html($data['machine']['security']);
        if (!empty($security)) {
            $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
                . '<td width="18%" bgcolor="#E6E6E6"><b>' . $langs->transnoentities('JobSheetSafetyInstructions') . ' :</b></td>'
                . '<td width="82%">' . $security . '</td>'
                . '</tr></table>';
        }

        // 4 · 5 · 6 Tasks exposing the operator to a risk, dangers, prevention measures
        $rows = '';
        $num  = 0;
        foreach ($data['tasks'] as $task) {
            $num++;
            $source = $task['source'] == 'product' ? $langs->transnoentities('JobSheetSourceTagMachine') : $langs->transnoentities('JobSheetSourceTagRisk', $task['ref'], $task['cotation'] ?? '-');

            $measures = [];
            foreach ($task['measures'] as $measure) {
                $line = (!empty($measure['picto']) ? $this->imgTag($measure['picto'], 4.5, 4.5) . ' ' : '• ') . $this->text($measure['label']);
                if (!empty($measure['comment'])) {
                    $line .= ' : ' . $this->text($measure['comment']);
                }
                if (empty($measure['done'])) {
                    $line = '<i>' . $line . ' (' . $langs->transnoentities('JobSheetMeasurePlanned') . ')</i>';
                }
                $measures[] = $line;
            }

            $rows .= '<tr nobr="true">'
                . '<td width="5%" align="center"><b>' . $num . '</b></td>'
                . '<td width="33%">' . (!empty(trim($task['description'])) ? $this->text($task['description'], true) : $dots) . '<br/><span style="font-size:6.5pt;color:#555555;">' . $source . '</span></td>'
                . '<td width="20%" align="center">' . (!empty($task['danger']['picto']) ? $this->imgTag($task['danger']['picto'], 9, 9) . '<br/>' : '') . '<span style="font-size:7pt;">' . $this->text($task['danger']['name']) . '</span></td>'
                . '<td width="42%">' . (!empty($measures) ? implode('<br/>', $measures) : $dots . '<br/>' . $dots) . '</td>'
                . '</tr>';
        }
        if (empty($rows)) {
            for ($i = 1; $i <= 2; $i++) {
                $rows .= '<tr nobr="true"><td width="5%" align="center"><b>' . $i . '</b></td><td width="33%">' . $langs->transnoentities('JobSheetWhen') . ' ' . $dots . '<br/>' . $langs->transnoentities('JobSheetWhichTask') . ' ' . $dots . '<br/>' . $langs->transnoentities('JobSheetByWhom') . ' ' . $dots . '</td><td width="20%"></td><td width="42%">' . $dots . '<br/>' . $dots . '</td></tr>';
            }
        }
        $sections['tasks'] = '<table cellspacing="0" cellpadding="3" border="1">'
            . '<thead><tr bgcolor="#E6E6E6">'
            . '<th width="5%" align="center"><b>' . $langs->transnoentities('JobSheetNum') . '</b></th>'
            . '<th width="33%"><b>' . $langs->transnoentities('JobSheetTaskTitle') . '</b></th>'
            . '<th width="20%" align="center"><b>' . $langs->transnoentities('JobSheetDangerTitle') . '</b></th>'
            . '<th width="42%"><b>' . $langs->transnoentities('JobSheetMeasuresTitle') . '</b></th>'
            . '</tr></thead>'
            . $rows
            . '</table>';

        // 7 Qualification and authorisation
        $qualification = $this->html($data['machine']['qualification']);
        if (empty($qualification)) {
            $qualification = $langs->transnoentities('JobSheetOperation') . ' : ' . $dots . '<br/>'
                . $langs->transnoentities('JobSheetMaintenance') . ' : ' . $dots . '<br/>'
                . '<b>' . $langs->transnoentities('JobSheetMandatoryTraining') . ' :</b> ' . $dots;
        }
        $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
            . '<td width="18%" bgcolor="#E6E6E6"><b>' . $langs->transnoentities('JobSheetQualificationTitle') . ' :</b></td>'
            . '<td width="82%">' . $qualification . '</td>'
            . '</tr></table>';

        // 8 Emergency
        $samu     = !empty($data['emergency']['samu']) ? $data['emergency']['samu'] : '15';
        $pompiers = !empty($data['emergency']['pompiers']) ? $data['emergency']['pompiers'] : '18';
        $all      = !empty($data['emergency']['allemergencies']) ? $data['emergency']['allemergencies'] : '112';
        $sections[] = '<table cellspacing="0" cellpadding="4" border="1"><tr>'
            . '<td width="18%" bgcolor="#E6E6E6"><b>' . $langs->transnoentities('JobSheetEmergencyTitle') . ' :</b></td>'
            . '<td width="41%">' . $langs->transnoentities('JobSheetAccident') . '<br/>' . $langs->transnoentities('JobSheetSamu') . ' : <b style="font-size:11pt;">' . $this->text($samu) . '</b></td>'
            . '<td width="41%">' . $langs->transnoentities('JobSheetFire') . '<br/>' . $langs->transnoentities('JobSheetPompiers') . ' : <b style="font-size:11pt;">' . $this->text($pompiers) . '</b> · ' . $langs->transnoentities('JobSheetAllEmergencies') . ' : <b>' . $this->text($all) . '</b></td>'
            . '</tr></table>';

        return $sections;
    }

    /**
     * Footer of every page, drawn once the content is written
     *
     * @param  mixed $pdf       PDF handler, a TCPDF instance
     * @param  array $data      Data of the sheet
     * @param  int   $pageCount Number of pages
     * @return void
     */
    private function drawFooters($pdf, array $data, int $pageCount): void
    {
        $langs = $this->outputLangs;
        $left  = $langs->transnoentities('JobSheetFooter', $data['company']['name'], $data['element']->ref);

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf->setPage($page);
            // setPage() puts back the automatic page break, the footer is written inside the bottom margin
            $pdf->SetAutoPageBreak(false);
            $pdf->SetFont('', '', 7);
            $pdf->SetTextColor(85, 85, 85);
            $pdf->Text($this->marginLeft, $this->pageHeight - 9, $left);
            $pageLabel = $langs->transnoentities('Page') . ' ' . $page . '/' . $pageCount;
            $pdf->Text($this->pageWidth - $this->marginRight - $pdf->GetStringWidth($pageLabel), $this->pageHeight - 9, $pageLabel);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Image tag fitted in a box, with the file embedded so that TCPDF never has to interpret its path
     *
     * @param  string $path      Absolute path of a JPEG or PNG
     * @param  float  $maxWidth  Width of the box, in mm
     * @param  float  $maxHeight Height of the box, in mm
     * @return string            Tag, empty when the image can not be read
     */
    private function imgTag(string $path, float $maxWidth, float $maxHeight): string
    {
        if (!is_file($path)) {
            return '';
        }
        $size = @getimagesize($path);
        if (empty($size) || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            return '';
        }

        $content = file_get_contents($path);
        $width   = $size[0];
        $height  = $size[1];
        $mime    = $size['mime'];

        // Photos taken with a phone are stored as shot, the EXIF orientation tells how to turn them
        if ($size[2] == IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagecreatefromstring')) {
            $exif        = @exif_read_data($path);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            $angles      = [3 => 180, 6 => -90, 8 => 90];
            if (isset($angles[$orientation])) {
                $image = @imagecreatefromstring($content);
                if ($image !== false) {
                    $rotated = imagerotate($image, $angles[$orientation], 0);
                    ob_start();
                    imagejpeg($rotated, null, 90);
                    $content = ob_get_clean();
                    if ($orientation != 3) {
                        [$width, $height] = [$height, $width];
                    }
                }
            }
        }

        $ratio = min($maxWidth / max(1, $width), $maxHeight / max(1, $height));

        return '<img src="data:' . $mime . ';base64,' . base64_encode($content) . '" width="' . round($width * $ratio, 2) . 'mm" height="' . round($height * $ratio, 2) . 'mm"/>';
    }

    /**
     * Escaped plain text
     *
     * @param  string $text      Text
     * @param  bool   $keepLines Keep the line breaks
     * @return string            HTML
     */
    private function text(?string $text, bool $keepLines = false): string
    {
        $text = dol_string_nohtmltag((string) $text, 0);
        if ($keepLines) {
            return nl2br(dol_escape_htmltag(trim($text), 0, 1));
        }

        return dol_escape_htmltag(trim(preg_replace('/\s+/', ' ', $text)));
    }

    /**
     * Rich text of an instruction sheet chapter, reduced to the tags TCPDF lays out well and without colours
     *
     * @param  string $html HTML of the WYSIWYG editor
     * @return string       HTML, empty when there is no text
     */
    private function html(?string $html): string
    {
        $html = (string) $html;
        if (trim(dol_string_nohtmltag(str_replace('&nbsp;', ' ', $html), 0)) === '') {
            return '';
        }

        $tags = ['b', 'strong', 'i', 'em', 'u', 'br', 'p', 'div', 'ul', 'ol', 'li'];
        $html = dol_string_onlythesehtmltags($html, 1, 1, 1, 0, $tags);
        $html = preg_replace('/<(\/?)(' . implode('|', $tags) . ')\b[^>]*>/i', '<$1$2>', $html);
        $html = preg_replace('/<(p|div)>\s*<\/\1>/i', '', $html);
        $html = preg_replace('/<(p|div)>/i', '', $html);
        $html = preg_replace('/<\/(p|div)>/i', '<br/>', $html);
        $html = preg_replace('/(<br\s*\/?>\s*)+$/i', '', trim($html));

        return $html;
    }
}
