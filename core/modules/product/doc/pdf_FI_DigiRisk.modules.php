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

        // Company Logo
        global $conf;
        $logodir = $conf->mycompany->dir_output;
        if (!empty($conf->mycompany->multidir_output[$object->entity ?? $conf->entity])) {
            $logodir = $conf->mycompany->multidir_output[$object->entity ?? $conf->entity];
        }

        $logo = '';
        if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED)) {
            if (is_readable($logodir.'/logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED)) {
                $logo = $logodir.'/logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED;
            } elseif (is_readable($logodir.'/logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED)) {
                $logo = $logodir.'/logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED;
            }
        }
        
        if (empty($logo) && !empty($conf->global->MAIN_INFO_SOCIETE_LOGO)) {
            if (is_readable($logodir.'/logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO)) {
                $logo = $logodir.'/logos/thumbs/'.$conf->global->MAIN_INFO_SOCIETE_LOGO;
            } elseif (is_readable($logodir.'/logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO)) {
                $logo = $logodir.'/logos/'.$conf->global->MAIN_INFO_SOCIETE_LOGO;
            }
        }

        if (!empty($logo) && is_readable($logo)) {
            // Draw logo in a 18x18 area on the left of the header
            $pdf->Image($logo, $this->marge_gauche + 2, $currentY + 2, 0, 18);
        }

        // Middle Title
        $pdf->SetXY($this->marge_gauche, $currentY + 4);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6, mb_strtoupper(dol_trunc($object->label, 60)), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX($this->marge_gauche);
        $pdf->Cell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6, "Fiche d'Instruction, de Sécurité et de Maintenance Machine", 0, 1, 'C');

        $currentY += 28;

        // ----------------------------------------------------
        // IDENTIFICATION EQUIPEMENT (Left) + PHOTO (Right)
        // ----------------------------------------------------
        $entrepotStr = '';
        if (!empty($object->fk_default_warehouse)) {
            require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch($object->fk_default_warehouse);
            $entrepotStr = '<br><br><b>Entrepôt :</b> <span style="color:#000;">' . $entrepot->label . '</span>';
        }

        $contentEquip = '<b>Réf :</b> <span style="color:#000;">' . $object->ref . '</span>' . $entrepotStr;
        
        $blockY_start = $currentY;
        $currentY = $this->_drawBlock($pdf, "IDENTIFICATION EQUIPEMENT", $contentEquip, array(50, 120, 200), $currentY, 95);

        // Right side image block (from mockup)
        $blockX = $this->marge_gauche + 95 + 2;
        $blockY = $blockY_start;
        $blockW = $this->page_largeur - $this->marge_gauche - $this->marge_droite - 97;
        $blockH = $this->lastBlockHeight;
        
        $pdf->SetFillColor(240, 245, 250);
        $pdf->SetDrawColor(200, 220, 240);
        $pdf->Rect($blockX, $blockY, $blockW, $blockH, 'DF');

        // Retrieve and display product photo if available
        $dir_product = $conf->product->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'product');
        
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        // Get images sorted by date descending to match the top of the UI list
        $filearray = dol_dir_list($dir_product, "files", 0, '\.(png|jpg|jpeg|gif|bmp)$', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 1);
        
        if (is_array($filearray) && count($filearray) > 0) {
            $photo_path = $filearray[0]['fullname'];
            if (file_exists($photo_path)) {
                // Fit image within the block with 2px padding, centered
                $pdf->Image($photo_path, $blockX + 2, $blockY + 2, $blockW - 4, $blockH - 4, '', '', '', false, 300, '', false, false, 0, 'CM');
            }
        }

        $iconsDir = DOL_DATA_ROOT . '/digiriskdolibarr/icons/';
        $defaultIconsDir = __DIR__ . '/../../../../img/icons/';
        
        // Helper function to get correct SVG path
        $getSvgPath = function($baseName) use ($iconsDir, $defaultIconsDir) {
            $customPath = $iconsDir . $baseName;
            return file_exists($customPath) ? $customPath : $defaultIconsDir . $baseName;
        };

        // ----------------------------------------------------
        // DESCRIPTION & CARACTÉRISTIQUES
        // ----------------------------------------------------
        $contentDesc = $object->array_options['options_digirisk_identification'] ?? '';
        $svgIdent = $getSvgPath('digirisk_identification_icon.svg');
        $colorIdent = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_SVG_COLOR ?? '#5c6bc0';
        $currentY = $this->_drawBlock($pdf, "DESCRIPTION & CARACTÉRISTIQUES", $contentDesc, array(50, 120, 200), $currentY, 0, $svgIdent, $colorIdent);

        // ----------------------------------------------------
        // SÉCURITÉ & PROTECTIONS (WYSIWYG)
        // ----------------------------------------------------
        $contentSecWysiwyg = $object->array_options['options_digirisk_security'] ?? '';
        $svgSecWysiwyg = $getSvgPath('digirisk_security_icon.svg');
        $colorSecWysiwyg = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_SVG_COLOR ?? '#d32f2f';
        $currentY = $this->_drawBlock($pdf, "SÉCURITÉ & PROTECTIONS", $contentSecWysiwyg, array(200, 50, 50), $currentY, 0, $svgSecWysiwyg, $colorSecWysiwyg);

        // ----------------------------------------------------
        // RISQUES & PROTECTIONS
        // ----------------------------------------------------
        dol_include_once('/digiriskdolibarr/class/riskanalysis/risk.class.php');
        $productRiskObj = new ProductRisk($this->db);
        $risks = $productRiskObj->fetchAllByProduct($object->id);

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

        $imgBaseDir = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/';

        $contentSec = '';
        if (empty($risks)) {
            $contentSec = '<i>Aucun risque identifié</i>';
        } else {
            foreach ($risks as $risk) {
                $cat = $dangerCatByPos[$risk->danger_category] ?? null;
                $thumbUrl = '';
                if ($cat && !empty($cat['thumbnail_name'])) {
                    $thumbUrl = $imgBaseDir . 'categorieDangers/' . $cat['thumbnail_name'] . '.png';
                }
                
                // Photo
                $photos = $risk->getPhotos();
                $riskPhotoUrl = '';
                if (!empty($photos)) {
                    $riskPhotoUrl = $risk->getPhotoDir() . $photos[0];
                }
                $hasPhoto = ($riskPhotoUrl && file_exists($riskPhotoUrl));
                $leftWidth = $hasPhoto ? "75%" : "100%";
                $rightWidth = $hasPhoto ? "25%" : "0%";

                // Outer table for border
                $contentSec .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
                $contentSec .= '<tr><td style="border: 1px solid #aaaaaa;">';
                
                // Table for columns (Left: Risk/Prot, Right: Photo)
                $contentSec .= '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
                $contentSec .= '<tr><td width="' . $leftWidth . '" valign="top">';

                // Inner table for content
                $contentSec .= '<table width="100%" cellpadding="3" cellspacing="0" border="0">';
                
                // Risk Block
                $contentSec .= '<tr style="background-color:#ffebeb;">';
                if ($thumbUrl && file_exists($thumbUrl)) {
                    $contentSec .= '<td width="10%" align="center" valign="middle"><img src="' . $thumbUrl . '" width="30" height="30" /></td>';
                } else {
                    $contentSec .= '<td width="10%" valign="middle"></td>';
                }
                $contentSec .= '<td width="90%" valign="middle" style="line-height:20px;"><b>' . dol_escape_htmltag($risk->description) . '</b></td>';
                $contentSec .= '</tr>';

                // Protections Block
                $prots = $risk->getProtections();
                if (!empty($prots)) {
                    foreach ($prots as $p) {
                        $pCat = $protectionCatByPos[(int)$p['position']] ?? null;
                        $pThumbUrl = '';
                        if ($pCat && !empty($pCat['name_thumbnail'])) {
                            $pThumbUrl = $imgBaseDir . $pCat['name_thumbnail'];
                        }
                        
                        $contentSec .= '<tr style="background-color:#f4f8ff;">';
                        if ($pThumbUrl && file_exists($pThumbUrl)) {
                            $contentSec .= '<td width="10%" align="center" valign="middle"><img src="' . $pThumbUrl . '" width="24" height="24" /></td>';
                        } else {
                            $contentSec .= '<td width="10%" valign="middle"></td>';
                        }
                        $contentSec .= '<td width="90%" valign="middle" style="color:#333; line-height:18px;">' . dol_escape_htmltag($p['comment'] ?? $pCat['name'] ?? '') . '</td>';
                        $contentSec .= '</tr>';
                    }
                }
                $contentSec .= '</table>';
                
                $contentSec .= '</td>'; // End left column

                if ($hasPhoto) {
                    $contentSec .= '<td width="' . $rightWidth . '" align="center" valign="middle" style="border-left: 1px solid #aaaaaa; background-color:#ffffff;">';
                    // Use nested table to add padding around the image
                    $contentSec .= '<table width="100%" cellpadding="4" border="0"><tr><td align="center"><img src="' . $riskPhotoUrl . '" width="80" /></td></tr></table>';
                    $contentSec .= '</td>';
                }
                $contentSec .= '</tr></table>'; // End columns table

                $contentSec .= '</td></tr></table>'; // End outer table
                // Small vertical spacing between risk groups
                $contentSec .= '<table width="100%" cellpadding="2"><tr><td></td></tr></table>';
            }
        }

        $svgSec = $getSvgPath('digirisk_security_icon.svg');
        $colorSec = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_SVG_COLOR ?? '#d32f2f';
        $currentY = $this->_drawBlock($pdf, "RISQUES & PROTECTIONS", $contentSec, array(200, 50, 50), $currentY, 0, $svgSec, $colorSec);

        // ----------------------------------------------------
        // MODE D'EMPLOI SIMPLIFIÉ
        // ----------------------------------------------------
        $contentUsage = $object->array_options['options_digirisk_usermanual'] ?? '';
        $svgUsage = $getSvgPath('digirisk_usermanual_icon.svg');
        $colorUsage = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_SVG_COLOR ?? '#388e3c';
        $currentY = $this->_drawBlock($pdf, "MODE D'EMPLOI SIMPLIFIÉ", $contentUsage, array(60, 160, 100), $currentY, 0, $svgUsage, $colorUsage);

        // ----------------------------------------------------
        // QUALIFICATION & HABILITATION
        // ----------------------------------------------------
        $contentQualif = $object->array_options['options_digirisk_qualification'] ?? '';
        $svgQualif = $getSvgPath('digirisk_qualification_icon.svg');
        $colorQualif = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_SVG_COLOR ?? '#1976d2';
        $currentY = $this->_drawBlock($pdf, "QUALIFICATION & HABILITATION", $contentQualif, array(40, 80, 160), $currentY, 0, $svgQualif, $colorQualif);

        // ----------------------------------------------------
        // HYGIÈNE & NETTOYAGE
        // ----------------------------------------------------
        $contentHyg = $object->array_options['options_digirisk_hygiene'] ?? '';
        $svgHyg = $getSvgPath('digirisk_hygiene_icon.svg');
        $colorHyg = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_SVG_COLOR ?? '#f57f17';
        $currentY = $this->_drawBlock($pdf, "HYGIÈNE & NETTOYAGE", $contentHyg, array(220, 140, 40), $currentY, 0, $svgHyg, $colorHyg);

        // ----------------------------------------------------
        // MAINTENANCE & CONTRÔLES
        // ----------------------------------------------------
        $contentMaint = $object->array_options['options_digirisk_maintenance'] ?? '';
        $svgMaint = $getSvgPath('digirisk_maintenance_icon.svg');
        $colorMaint = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_SVG_COLOR ?? '#e64a19';
        $currentY = $this->_drawBlock($pdf, "MAINTENANCE & CONTRÔLES", $contentMaint, array(60, 120, 180), $currentY, 0, $svgMaint, $colorMaint);

        // ----------------------------------------------------
        // FOOTER MSG
        // ----------------------------------------------------
        $pdf->SetAutoPageBreak(FALSE);
        $nbpages = $pdf->getNumPages();
        for ($i = 1; $i <= $nbpages; $i++) {
            $pdf->setPage($i);
            $pdf->SetY(-15); // 15mm from the bottom of the page
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(120, 120, 120);
            
            // Left text
            $pdf->SetX($this->marge_gauche);
            $footerText = "Fichier : " . basename($file) . " - CC BY-SA DigiRisk";
            $pdf->Cell(0, 5, $footerText, 0, 0, 'L');
            
            // Right text
            $pdf->SetX($this->marge_gauche);
            $pdf->Cell(0, 5, "Page " . $i . " / " . $nbpages, 0, 1, 'R');
        }

        // Output PDF to file
        $pdf->Output($file, 'F');
        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        return 1;
    }

    private $lastBlockHeight = 0;

    private function _drawBlock($pdf, $title, $content, $color, $startY, $width = 0, $iconSvgPath = null, $svgColor = '#000000')
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
        $pdf->Cell($width, 7, "", 'LTR', 0, 'L', 1);
        
        $titleOffset = 2;
        if (!empty($iconSvgPath) && is_readable($iconSvgPath)) {
            $svgContent = file_get_contents($iconSvgPath);
            // Replace existing fills and strokes, or inject if not present
            // Simple robust regex for SVG tags
            $svgContent = preg_replace('/fill="[^"]*"/', 'fill="'.$svgColor.'"', $svgContent);
            if (strpos($svgContent, 'fill=') === false) {
                // If there's no fill attribute to replace, inject it into the SVG tag and path tags
                $svgContent = str_replace('<svg ', '<svg fill="'.$svgColor.'" ', $svgContent);
                $svgContent = str_replace('<path ', '<path fill="'.$svgColor.'" ', $svgContent);
            }
            // Pass string prepended with '@' so TCPDF treats it as SVG string
            $pdf->ImageSVG('@' . $svgContent, $this->marge_gauche + 2, $startY + 1.5, 4, 4);
            $titleOffset = 8;
        }
        $pdf->SetXY($this->marge_gauche + $titleOffset, $startY);
        $pdf->Cell($width - $titleOffset, 7, $title, 0, 1, 'L', 0);
        
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
