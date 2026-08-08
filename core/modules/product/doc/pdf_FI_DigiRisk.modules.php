<?php

require_once DOL_DOCUMENT_ROOT . '/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

// Digirisk specific classes
dol_include_once('/digiriskdolibarr/class/riskanalysis/productrisk.class.php');
dol_include_once('/digiriskdolibarr/class/dictionary/dangercategory.class.php');
dol_include_once('/digiriskdolibarr/class/dictionary/protectiontype.class.php');

class pdf_FI_DigiRisk extends ModelePDFProduct
{
    public $db;
    public $name;
    public $description;
    public $type;
    public $page_largeur;
    public $page_hauteur;
    public $format;
    public $marge_gauche;
    public $marge_droite;
    public $marge_haute;
    public $marge_basse;
    public $option_logo;

    public function __construct($db)
    {
        global $langs;
        $this->db = $db;
        $this->name = "FI_DigiRisk";
        $this->description = $langs->transnoentities("Fiche Instruction DigiRisk");

        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 10;
        $this->option_logo = 1;
        
        // Versionning settings
        global $conf;
        $this->default_version_prefix = !empty($conf->global->DIGIRISK_PDF_VERSION_PREFIX) ? $conf->global->DIGIRISK_PDF_VERSION_PREFIX : "1.0.0-";
        $this->default_version_date_format = !empty($conf->global->DIGIRISK_PDF_VERSION_DATE_FORMAT) ? $conf->global->DIGIRISK_PDF_VERSION_DATE_FORMAT : "YmdHis"; // AAAAMMJJHHSS
    }

    public function info($langs)
    {
        global $conf, $form;
        
        $texte = $this->description . "<br>\n";
        
        $texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . newToken() . '">';
        $texte .= '<input type="hidden" name="action" value="setModuleOptions">';
        $texte .= '<input type="hidden" name="param1" value="DIGIRISK_PDF_VERSION_MASK">';
        
        $texte .= '<table class="nobordernopadding centpercent">';
        $texte .= '<tr>';
        
        $texte .= '<td>Masque pour la version</td>';
        
        $tooltip = "Vous pouvez saisir n'importe quel masque pour le numéro de version de la fiche.<br><br>";
        $tooltip .= "<b>{yyyy}</b> ou <b>{y}</b> : année sur 4 ou 2 chiffres<br>";
        $tooltip .= "<b>{mm}</b> : mois (01 à 12)<br>";
        $tooltip .= "<b>{dd}</b> : jour (01 à 31)<br>";
        $tooltip .= "<b>{hh}</b> : heure (00 à 23)<br>";
        $tooltip .= "<b>{ii}</b> (ou <b>{mi}</b>) : minute (00 à 59)<br>";
        $tooltip .= "<b>{ss}</b> : seconde (00 à 59)<br><br>";
        $tooltip .= "Exemple : 1.0.0-{yyyy}{mm}{dd}{hh}{ii}{ss} donnera 1.0.0-20231025143000";

        $texte .= '<td class="right nowraponall">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value1" placeholder="Masque" value="'.dol_escape_htmltag(getDolGlobalString('DIGIRISK_PDF_VERSION_MASK', '1.0.0-{yyyy}{mm}{dd}{hh}{ii}{ss}')).'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).'</td>';
        
        $texte .= '<td class="left">&nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . $langs->trans("Modify") . '"></td>';
        
        $texte .= '</tr>';
        $texte .= '</table>';
        $texte .= '</form>';
        
        return $texte;
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        $outputlangs->load("products");

        if (empty($object->array_options)) {
            $object->fetch_optionals();
        }

        $id = $object->id;
        $ref = $object->ref;

        if (!empty($object->specimen)) {
            $dir = $conf->product->dir_output;
            $file = $dir . "/SPECIMEN.pdf";
        } else {
            $dir = $conf->product->dir_output . "/" . dol_sanitizeFileName($ref);
            if (!file_exists($dir)) {
                dol_mkdir($dir);
            }

            $mask = getDolGlobalString('DIGIRISK_PDF_VERSION_MASK', '1.0.0-{yyyy}{mm}{dd}{hh}{ii}{ss}');
            $version = $mask;
            $version = str_replace('{yyyy}', date('Y'), $version);
            $version = str_replace('{y}', date('y'), $version);
            $version = str_replace('{mm}', date('m'), $version);
            $version = str_replace('{dd}', date('d'), $version);
            $version = str_replace('{hh}', date('H'), $version);
            $version = str_replace('{mi}', date('i'), $version);
            $version = str_replace('{ii}', date('i'), $version);
            $version = str_replace('{ss}', date('s'), $version);
            
            $file = $dir . "/FI_DigiRisk_" . dol_sanitizeFileName($ref) . "_" . $version . ".pdf";
        }

        $pdf = pdf_getInstance($this->format);
        $pdf->SetCreator("Dolibarr & Digirisk");
        $pdf->SetAuthor($user->getFullName($outputlangs));
        $pdf->SetTitle("Fiche HSE " . $object->ref);
        $pdf->SetSubject("Fiche Machine HSE");
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->SetAutoPageBreak(TRUE, $this->marge_basse + 10);
        
        // Disable header and footer for custom drawing
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();

        // ----------------------------------------------------
        // HEADER
        // ----------------------------------------------------
        $currentY = $this->marge_haute;
        
        $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(200, 200, 200)));
        // Header Box
        $pdf->Rect($this->marge_gauche, $currentY, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 22);

        // Logo / EQUIPEMENT Title
        $pdf->SetXY($this->marge_gauche, $currentY + 2);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 80, 160);
        $pdf->Cell(50, 6, "EQUIPEMENT", 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell(50, 6, "DOC-HSE-MAC-" . sprintf("%03d", $object->id), 0, 1, 'C');

        // Middle Title
        $pdf->SetXY($this->marge_gauche + 50, $currentY + 4);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(90, 6, mb_strtoupper(dol_trunc($object->label, 40)), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX($this->marge_gauche + 50);
        $pdf->Cell(90, 6, "Fiche d'Instruction, de Sécurité et de Maintenance Machine", 0, 1, 'C');

        // Right Info
        $pdf->SetXY($this->page_largeur - $this->marge_droite - 50, $currentY + 2);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell(50, 5, "Réf Dolibarr : " . $object->ref, 0, 1, 'L');
        $pdf->SetX($this->page_largeur - $this->marge_droite - 50);
        $pdf->Cell(50, 5, "Version : 1.0 (" . date('Y') . ")", 0, 1, 'L');
        $pdf->SetX($this->page_largeur - $this->marge_droite - 50);
        $pdf->Cell(50, 5, "Atelier : ", 0, 1, 'L');

        $currentY += 28;

        // ----------------------------------------------------
        // FETCH RISKS & DICTIONARIES
        // ----------------------------------------------------
        $productRiskObj = new ProductRisk($this->db);
        $existingRisks = $productRiskObj->fetchAllByProduct($object->id);
        
        dol_include_once('/digiriskdolibarr/class/riskanalysis/risk.class.php');
        $dangerCategories = Risk::getDangerCategories('risk');
        $dangerCatByPos   = [];
        foreach ($dangerCategories as $cat) {
            $dangerCatByPos[(int) $cat['position']] = $cat;
        }

        $signalisationFile    = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
        $allSignalisations    = file_exists($signalisationFile) ? (json_decode(file_get_contents($signalisationFile), true) ?: []) : [];
        $protectionCategories = array_values(array_filter($allSignalisations, static function ($s) {
            return strpos($s['name_thumbnail'] ?? '', 'OBLIGATION/') === 0;
        }));
        $protectionCatByPos   = [];
        foreach ($protectionCategories as $p) {
            $protectionCatByPos[(int) $p['position']] = $p;
        }

        // ----------------------------------------------------
        // IDENTIFICATION & CARACTÉRISTIQUES
        // ----------------------------------------------------
        $contentId = $object->array_options['options_digirisk_identification'] ?? '';
        $currentY = $this->_drawBlock($pdf, "IDENTIFICATION & CARACTÉRISTIQUES", $contentId, array(50, 120, 200), $currentY, 95);

        // Right side image block (from mockup)
        $blockX = $this->marge_gauche + 95 + 2;
        $blockY = $currentY - $this->lastBlockHeight;
        $blockW = $this->page_largeur - $this->marge_gauche - $this->marge_droite - 97;
        $blockH = $this->lastBlockHeight;
        
        $pdf->SetFillColor(240, 245, 250);
        $pdf->SetDrawColor(200, 220, 240);
        $pdf->Rect($blockX, $blockY, $blockW, $blockH, 'DF');

        // Retrieve and display product photo if available
        $dir_product = $conf->product->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'product') . '/';
        $photos = $object->liste_photos($dir_product, 1);
        if (is_array($photos) && count($photos) > 0 && !empty($photos[0]['photo'])) {
            $photo_path = $dir_product . $photos[0]['photo'];
            if (file_exists($photo_path)) {
                // Fit image within the block with 2px padding, centered
                $pdf->Image($photo_path, $blockX + 2, $blockY + 2, $blockW - 4, $blockH - 4, '', '', '', false, 300, '', false, false, 0, 'CM');
            }
        }

        // ----------------------------------------------------
        // SÉCURITÉ & PROTECTIONS
        // ----------------------------------------------------
        // Format risks HTML
        $risksHtml = '<b>RISQUES PRINCIPAUX :</b><br>';
        $episHtml = '<b>EPI OBLIGATOIRES :</b><br>';
        
        if (count($existingRisks) > 0) {
            foreach ($existingRisks as $risk) {
                $dcLabel = "Risque";
                if (isset($dangerCatByPos[$risk->danger_category])) {
                    $dcLabel = $dangerCatByPos[$risk->danger_category]['name'];
                }
                $risksHtml .= '<span style="color:#D32F2F; border:1px solid #D32F2F; padding:2px; margin-right:5px;">! ' . $dcLabel . '</span> ';
                
                $prots = json_decode($risk->protections_json, true);
                if (is_array($prots)) {
                    foreach ($prots as $protObj) {
                        $protPos = $protObj['position'] ?? null;
                        if ($protPos !== null && isset($protectionCatByPos[$protPos])) {
                            $protLabel = preg_replace('/^OBLIGATION\//', '', $protectionCatByPos[$protPos]['name_thumbnail'] ?? '');
                            $protLabel = str_replace('_', ' ', $protLabel);
                            $episHtml .= '<span style="color:#1976D2; border:1px solid #1976D2; padding:2px; margin-right:5px;">+ ' . $protLabel . '</span> ';
                        }
                    }
                }
            }
        } else {
            $risksHtml .= '<i>Aucun risque renseigné.</i>';
        }
        
        $securityRight = "<b>ORGANES DE SÉCURITÉ :</b><br>" . ($object->array_options['options_digirisk_security'] ?? '<i>Non renseigné</i>');
        
        $contentSec = '<table width="100%"><tr>
            <td width="50%">' . $risksHtml . '<br><br>' . $episHtml . '</td>
            <td width="50%">' . $securityRight . '</td>
        </tr></table>';
        
        $currentY = $this->_drawBlock($pdf, "SÉCURITÉ & PROTECTIONS", $contentSec, array(200, 50, 50), $currentY);

        // ----------------------------------------------------
        // MODE D'EMPLOI SIMPLIFIÉ
        // ----------------------------------------------------
        $contentUsage = $object->array_options['options_digirisk_usermanual'] ?? '';
        $currentY = $this->_drawBlock($pdf, "MODE D'EMPLOI SIMPLIFIÉ", $contentUsage, array(60, 160, 100), $currentY);

        // ----------------------------------------------------
        // QUALIFICATION & HABILITATION
        // ----------------------------------------------------
        $contentQualif = $object->array_options['options_digirisk_qualification'] ?? '';
        $currentY = $this->_drawBlock($pdf, "QUALIFICATION & HABILITATION", $contentQualif, array(40, 80, 160), $currentY);

        // ----------------------------------------------------
        // HYGIÈNE & NETTOYAGE
        // ----------------------------------------------------
        $contentHyg = $object->array_options['options_digirisk_hygiene'] ?? '';
        $currentY = $this->_drawBlock($pdf, "HYGIÈNE & NETTOYAGE", $contentHyg, array(220, 140, 40), $currentY);

        // ----------------------------------------------------
        // MAINTENANCE & CONTRÔLES
        // ----------------------------------------------------
        $contentMaint = $object->array_options['options_digirisk_maintenance'] ?? '';
        $currentY = $this->_drawBlock($pdf, "MAINTENANCE & CONTRÔLES", $contentMaint, array(60, 120, 180), $currentY);

        // ----------------------------------------------------
        // FOOTER MSG
        // ----------------------------------------------------
        $pdf->SetY($this->page_hauteur - $this->marge_basse - 12);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, "Standard Fiche Machine HSE - Intégration ERP Dolibarr / DigiRisk", 0, 0, 'L');
        $pdf->Cell(0, 5, "Page " . $pdf->getAliasNumPage() . " / " . $pdf->getAliasNbPages(), 0, 1, 'R');

        // Output PDF to file
        $pdf->Output($file, 'F');
        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        return 1;
    }

    private $lastBlockHeight = 0;

    private function _drawBlock($pdf, $title, $content, $color, $startY, $width = 0)
    {
        if ($width <= 0) {
            $width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        }

        // Check page break
        if ($startY > $this->page_hauteur - $this->marge_basse - 40) {
            $pdf->AddPage();
            $startY = $this->marge_haute;
        }

        // Clean content for TCPDF HTML parser
        if (empty($content)) {
            $content = "<i>(Non renseigné)</i>";
        }
        // Small padding hack
        $content = '<table cellpadding="4"><tr><td>' . $content . '</td></tr></table>';

        $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(200, 200, 200)));
        $pdf->SetXY($this->marge_gauche, $startY);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        // Title box with light background
        $pdf->SetFillColor(250, 250, 250);
        $pdf->Cell($width, 7, "  " . $title, 'LTR', 1, 'L', 1);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        // Record Y before HTML
        $yBefore = $pdf->GetY();
        $pdf->writeHTMLCell($width, 0, $this->marge_gauche, $yBefore, $content, 'LBR', 1, false, true, 'L', true);
        
        $yAfter = $pdf->GetY();
        $this->lastBlockHeight = $yAfter - $startY;
        
        return $yAfter + 4; // Add small margin bottom
    }
}
