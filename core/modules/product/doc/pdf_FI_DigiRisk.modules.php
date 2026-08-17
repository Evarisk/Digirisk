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
        $iconPath = dol_buildpath('/digiriskdolibarr/img/digiriskdolibarr_color.png', 1);
        $this->name = 'FI_DigiRisk';
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
        $texte .= '<td class="right nowraponall">'.$form->textwithpicto('<input type="text" class="flat minwidth175" name="value1" placeholder="Masque" value="'.dol_escape_htmltag(getDolGlobalString('DIGIRISK_PDF_VERSION_MASK', '1.0.0-{yyyy}{mm}{dd}{hh}{ii}{ss}')).'">', $tooltip, 1, 'help', 'valignmiddle', 0, 3, $this->name).' &nbsp; <input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="' . $langs->trans("Modify") . '"></td>';
        $texte .= '<td class="left"></td>';
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
        
        // Height reserved for footer (value includes bottom margin)
        // Footer is drawn at SetY(-15), i.e. 15mm from bottom. We reserve 25mm total for safety.
        $heightforfooter = $this->marge_basse + 15;
        
        $pdf->SetAutoPageBreak(true, $heightforfooter);
        
        // Disable header and footer for custom drawing
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();
        // The only function to edit the bottom margin of current page to set it (Dolibarr pattern)
        $pdf->setPageOrientation('', true, $heightforfooter);

        // ----------------------------------------------------
        // HEADER
        // ----------------------------------------------------
        $currentY = $this->marge_haute;
        
        $borderThickness = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_THICKNESS', '0.5');
        $borderRadius = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_RADIUS', '2');

        // Header Box
        $pdf->RoundedRect($this->marge_gauche, $currentY, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 22, $borderRadius, '1111', 'D', array('width' => $borderThickness, 'color' => array(200, 200, 200)));

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
        $titleIdent = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_LABEL : 'IDENTIFICATION EQUIPEMENT';
        $colorIdent = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_COLOR : '#c07500';

        $entrepotStr = '';
        if (!empty($object->fk_default_warehouse)) {
            require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch($object->fk_default_warehouse);
            $entrepotStr = '<br><br><b>Entrepôt :</b> <span style="color:#000;">' . dol_escape_htmltag($entrepot->label) . '</span>';
        }

        $libelleStr = '<br><br><b>Libellé :</b> <span style="color:#000;">' . dol_escape_htmltag($object->label) . '</span>';
        
        $lotSerieStatus = "Non renseigné";
        if (isset($object->status_batch)) {
            if ($object->status_batch == 1) {
                $lotSerieStatus = "Oui (lot)";
            } elseif ($object->status_batch == 2) {
                $lotSerieStatus = "Oui (série)";
            } else {
                $lotSerieStatus = "Non (lot/série non utilisé)";
            }
        }
        $lotStr = '<br><br><b>Lots/Série :</b> <span style="color:#000;">' . $lotSerieStatus . '</span>';

        $contentEquip = '<b>Réf :</b> <span style="color:#000;">' . dol_escape_htmltag($object->ref) . '</span>' . $libelleStr . $lotStr . $entrepotStr;
        
        $totalW = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        $ratioLeft = (float) getDolGlobalString('DIGIRISK_PDF_EQUIP_LEFT_RATIO', '66');
        if ($ratioLeft <= 0 || $ratioLeft >= 100) $ratioLeft = 66;
        $leftW = $totalW * ($ratioLeft / 100);
        $rightW = $totalW - $leftW - 2;

        $blockY_start = $currentY;
        $minHeight = 0;
        
        $heightMode = getDolGlobalString('DIGIRISK_PDF_PHOTO_HEIGHT_MODE', 'photo');
        
        // Fetch photo logic
        $hasPhoto = false;
        $photo_to_use = '';
        $dir_product = $conf->product->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'product');
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        $filearray = dol_dir_list($dir_product, "files", 0, '\.(png|jpg|jpeg|gif|bmp)$', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 1);
        $relativedir = preg_replace('/^' . preg_quote(DOL_DATA_ROOT, '/') . '\//', '', $dir_product);
        $relativedir = rtrim($relativedir, '/');
        $sql = "SELECT filename, position FROM " . MAIN_DB_PREFIX . "ecm_files WHERE filepath = '" . $this->db->escape($relativedir) . "' AND entity = " . ((int) $conf->entity);
        $resql = $this->db->query($sql);
        $posMap = [];
        if ($resql) {
            while ($row = $this->db->fetch_object($resql)) {
                $posMap[$row->filename] = (int) $row->position;
            }
        }
        foreach ($filearray as $k => $f) {
            $pos = isset($posMap[$f['name']]) ? $posMap[$f['name']] : 999999;
            $filearray[$k]['position_name'] = str_pad($pos, 6, '0', STR_PAD_LEFT) . '_' . $f['name'];
        }
        $filearray = dol_sort_array($filearray, 'position_name', 'asc');
        
        if (is_array($filearray) && count($filearray) > 0) {
            $photo_path = $filearray[0]['fullname'];
            if (file_exists($photo_path)) {
                $hasPhoto = true;
                $photo_to_use = $photo_path;
                if (function_exists('exif_read_data') && preg_match('/\.(jpe?g)$/i', $photo_path)) {
                    $exif = @exif_read_data($photo_path);
                    if (!empty($exif['Orientation']) && $exif['Orientation'] > 1) {
                        $img = @imagecreatefromjpeg($photo_path);
                        if ($img) {
                            switch ($exif['Orientation']) {
                                case 3: $img = imagerotate($img, 180, 0); break;
                                case 6: $img = imagerotate($img, -90, 0); break;
                                case 8: $img = imagerotate($img, 90, 0); break;
                            }
                            $tmpFile = DOL_DATA_ROOT . '/digiriskdolibarr/temp_exif_' . basename($photo_path);
                            imagejpeg($img, $tmpFile, 95);
                            imagedestroy($img);
                            $photo_to_use = $tmpFile;
                        }
                    }
                }
                
                // If adapting to photo height, calculate it now
                if ($heightMode == 'photo') {
                    $maxWidth = (float) getDolGlobalString('DIGIRISK_PDF_PHOTO_MAX_WIDTH', '80');
                    $maxHeight = (float) getDolGlobalString('DIGIRISK_PDF_PHOTO_MAX_HEIGHT', '80');
                    if ($maxWidth <= 0) $maxWidth = $rightW - 4;
                    if ($maxHeight <= 0) $maxHeight = 80;
                    
                    list($imgW, $imgH) = @getimagesize($photo_to_use);
                    if ($imgW > 0 && $imgH > 0) {
                        $ratio = $imgW / $imgH;
                        $availW = min($rightW - 4, $maxWidth);
                        $reqH = $availW / $ratio;
                        $imgDrawH = min($reqH, $maxHeight);
                        $minHeight = $imgDrawH + 4; // padding
                    }
                }
            }
        }

        $currentY = $this->_drawBlock($pdf, "IDENTIFICATION EQUIPEMENT", $contentEquip, array(50, 120, 200), $currentY, $leftW, null, '#000000', 'LBR', $minHeight);

        // Right side image block (from mockup)
        $blockX = $this->marge_gauche + $leftW + 2;
        $blockY = $blockY_start;
        $blockW = $rightW;
        $blockH = $this->lastBlockHeight; // Now this will be AT LEAST $minHeight if heightMode is 'photo'
        
        $pdf->SetFillColor(240, 245, 250);
        $pdf->RoundedRect($blockX, $blockY, $blockW, $blockH, $borderRadius, '1111', 'DF', array('width' => $borderThickness, 'color' => array(200, 220, 240)));

        if ($hasPhoto && file_exists($photo_to_use)) {
            $maxWidth = (float) getDolGlobalString('DIGIRISK_PDF_PHOTO_MAX_WIDTH', '80');
            $maxHeight = (float) getDolGlobalString('DIGIRISK_PDF_PHOTO_MAX_HEIGHT', '80');
            if ($maxWidth <= 0) $maxWidth = $blockW - 4;
            if ($maxHeight <= 0) $maxHeight = $blockH - 4;
            
            $imgDrawW = min($blockW - 4, $maxWidth);
            $imgDrawH = min($blockH - 4, $maxHeight);
            
            $offsetX = $blockX + 2 + (($blockW - 4) - $imgDrawW) / 2;
            $offsetY = $blockY + 2 + (($blockH - 4) - $imgDrawH) / 2;

            $pdf->Image($photo_to_use, $offsetX, $offsetY, $imgDrawW, $imgDrawH, '', '', '', false, 300, '', false, false, 0, 'CM');
            
            if ($photo_to_use !== $photo_path && file_exists($photo_to_use)) {
                @unlink($photo_to_use);
            }
        }

        $iconsDir = DOL_DATA_ROOT . '/digiriskdolibarr/icons/';
        $defaultIconsDir = __DIR__ . '/../../../../img/icons/';
        
        // Helper function to get correct SVG path
        $getSvgPath = function($baseName) use ($iconsDir, $defaultIconsDir, $conf) {
            $chap = strtoupper(str_replace(['digirisk_', '_icon.svg'], '', $baseName));
            $useSvg = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_USE_SVG'} ?? 0;
            
            $customPath = $iconsDir . $baseName;
            if ($useSvg && file_exists($customPath)) {
                return $customPath;
            }
            return $defaultIconsDir . $baseName;
        };

        // ----------------------------------------------------
        // DESCRIPTION & CARACTÉRISTIQUES
        // ----------------------------------------------------
        $contentDescRaw = $object->array_options['options_digirisk_identification'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentDescRaw))))) {
            $contentDescRaw = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_DESC ?? '';
        }
        $contentDesc = trim(strip_tags(str_replace('&nbsp;', ' ', $contentDescRaw)));
        if (!empty($contentDesc)) {
            $svgIdent = $getSvgPath('digirisk_identification_icon.svg');
            $colorIdent = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_SVG_COLOR ?? '#5c6bc0';
            $currentY = $this->_drawBlock($pdf, "DESCRIPTION & CARACTÉRISTIQUES", $contentDescRaw, array(50, 120, 200), $currentY, 0, $svgIdent, $colorIdent);
        }

        // ----------------------------------------------------
        // SÉCURITÉ (WYSIWYG)
        // ----------------------------------------------------
        $contentSecWysiwygRaw = $object->array_options['options_digirisk_security'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentSecWysiwygRaw))))) {
            $contentSecWysiwygRaw = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_DESC ?? '';
        }
        $contentSecWysiwyg = trim(strip_tags(str_replace('&nbsp;', ' ', $contentSecWysiwygRaw)));
        if (!empty($contentSecWysiwyg)) {
            $svgSecWysiwyg = $getSvgPath('digirisk_security_icon.svg');
            $titleSec = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_LABEL : 'SÉCURITÉ';
            $colorSecWysiwyg = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_COLOR : '#b72020';
            $currentY = $this->_drawBlock($pdf, mb_strtoupper($titleSec), $contentSecWysiwygRaw, array(200, 50, 50), $currentY, 0, $svgSecWysiwyg, $colorSecWysiwyg);
        }

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
        $object->fetch_optionals();
        $risksDefaultDesc = $object->array_options['options_digirisk_risks'] ?? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_DESC ?? '';

        // Draw the section title
        $svgRisks = $getSvgPath('digirisk_risks_icon.svg');
        $colorRisks = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_COLOR ?? '#d32f2f';
        $titleRisks = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_LABEL) ? mb_strtoupper($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_LABEL) : mb_strtoupper($langs->transnoentities('DigiriskRisks'));

        if (!empty($risksDefaultDesc)) {
            $contentSec = $risksDefaultDesc;
        }
        if (empty($risks) && empty($risksDefaultDesc)) {
            $contentSec = '<i>Aucun risque identifié</i>';
        }

        // Draw title + optional description
        $currentY = $this->_drawBlock($pdf, $titleRisks, $contentSec, array(200, 50, 50), $currentY, 0, $svgRisks, $colorRisks);

        // Now render each risk block individually with manual page break checks (Dolibarr pattern)
        $heightforfooter = $this->marge_basse + 15; // Same as init
        $pageW = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

        if (!empty($risks)) {
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

                // Color for the risk block (use the same color as the RISQUES section)
                $borderColorHex = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_COLOR ?? '#d32f2f';

                // Build HTML for this single risk block
                $blockHtml = '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
                $blockHtml .= '<tr>';
                
                // LEFT COLUMN (Risk)
                $blockHtml .= '<td width="30%" align="center" valign="middle" style="background-color:#ffebeb;">';
                $blockHtml .= '<table width="100%" cellpadding="8"><tr><td align="center">';
                if ($thumbUrl && file_exists($thumbUrl)) {
                    $blockHtml .= '<img src="' . $thumbUrl . '" width="40" height="40" /><br>';
                }
                $blockHtml .= '<b style="color:' . $borderColorHex . '; font-size:11pt;">' . dol_escape_htmltag(mb_strtoupper($risk->description)) . '</b>';
                $blockHtml .= '</td></tr></table>';
                $blockHtml .= '</td>';

                // RIGHT COLUMN (Protections & Photo)
                $rightWidth = $hasPhoto ? "45%" : "70%";
                $blockHtml .= '<td width="' . $rightWidth . '" valign="middle" style="background-color:#ffffff;">';

                $prots = $risk->getProtections();
                $protsCount = 0;
                if (!empty($prots)) {
                    $protsCount = count($prots);
                    $blockHtml .= '<table width="100%" cellpadding="6" cellspacing="0" border="0">';
                    $i = 0;
                    foreach ($prots as $p) {
                        $i++;
                        $pCat = $protectionCatByPos[(int)$p['position']] ?? null;
                        $pThumbUrl = '';
                        if ($pCat && !empty($pCat['name_thumbnail'])) {
                            $pThumbUrl = $imgBaseDir . $pCat['name_thumbnail'];
                        }
                        
                        $borderBottom = ($i < $protsCount) ? 'border-bottom: 1px solid #eeeeee;' : '';
                        
                        $comment = trim($p['comment'] ?? $pCat['name'] ?? '');
                        $lines = explode("\n", $comment, 2);
                        $title = dol_escape_htmltag(trim($lines[0]));
                        $sub = isset($lines[1]) ? dol_escape_htmltag(trim($lines[1])) : '';

                        $blockHtml .= '<tr>';
                        if ($pThumbUrl && file_exists($pThumbUrl)) {
                            $blockHtml .= '<td width="20%" align="center" valign="middle" style="' . $borderBottom . '"><img src="' . $pThumbUrl . '" width="30" height="30" /></td>';
                            $blockHtml .= '<td width="80%" valign="middle" style="' . $borderBottom . ' line-height:16px;">';
                        } else {
                            $blockHtml .= '<td width="100%" valign="middle" style="' . $borderBottom . ' line-height:16px;">';
                        }
                        
                        $blockHtml .= '<b style="color:#000000; font-size:10pt;">' . $title . '</b>';
                        if ($sub) {
                            $blockHtml .= '<br><span style="color:#444444; font-size:9pt;">' . nl2br($sub) . '</span>';
                        }
                        $blockHtml .= '</td>';
                        $blockHtml .= '</tr>';
                    }
                    $blockHtml .= '</table>';
                } else {
                    $blockHtml .= '<br>&nbsp;&nbsp;<i>Aucune protection définie</i><br>';
                }
                $blockHtml .= '</td>';

                if ($hasPhoto) {
                    $blockHtml .= '<td width="25%" align="center" valign="middle" style="background-color:#ffffff; border-left: 1px solid #eeeeee;">';
                    $blockHtml .= '<table width="100%" cellpadding="4" border="0"><tr><td align="center"><img src="' . $riskPhotoUrl . '" width="70" /></td></tr></table>';
                    $blockHtml .= '</td>';
                }
                $blockHtml .= '</tr></table>';

                // --- MANUAL PAGE BREAK CHECK (Dolibarr pattern) ---
                // Estimate block height: ~14mm per row (1 risk + N protections), min 20mm
                $estimatedHeight = max(20, (1 + $protsCount) * 14);
                if ($hasPhoto && $estimatedHeight < 45) {
                    $estimatedHeight = 45;
                }

                $debugMsg = "RISK: " . $risk->description . " | page=" . $pdf->getPage() . " | currentY=" . round($currentY, 1) . " | estH=" . $estimatedHeight . " | limit=" . round($this->page_hauteur - $heightforfooter, 1) . " | sum=" . round($currentY + $estimatedHeight, 1);

                // Check if block fits on current page (like Dolibarr: $curY + height > $page_hauteur - $heightforfooter)
                if ($currentY + $estimatedHeight > ($this->page_hauteur - $heightforfooter)) {
                    $pdf->AddPage();
                    $pdf->setPageOrientation('', true, $heightforfooter);
                    $currentY = $this->marge_haute;
                    $debugMsg .= " => PAGE BREAK! new page=" . $pdf->getPage();
                } else {
                    $debugMsg .= " => FITS";
                }

                // Render this single risk block
                // Disable auto page break so TCPDF cannot create its own conflicting breaks
                $pdf->SetAutoPageBreak(false);
                $pdf->SetFont('', '', 9);
                $pdf->SetTextColor(0, 0, 0);
                $pageBefore = $pdf->getPage();
                $startY = $currentY;
                $pdf->writeHTMLCell($pageW, 0, $this->marge_gauche, $currentY, $blockHtml, 0, 1, false, true, 'L', true);
                $pageAfter = $pdf->getPage();
                $endY = $pdf->GetY();

                // Draw rounded border over the block
                $borderThickness = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_THICKNESS', '0.5');
                $borderRadius = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_RADIUS', '2');
                $borderColorArr = [211, 47, 47]; // default red
                if (preg_match('/^#?([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$/i', $borderColorHex, $matches)) {
                    $borderColorArr = [hexdec($matches[1]), hexdec($matches[2]), hexdec($matches[3])];
                } elseif (preg_match('/(\d+)[^0-9]+(\d+)[^0-9]+(\d+)/', $borderColorHex, $matches)) {
                    $borderColorArr = [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
                }
                
                $pdf->RoundedRect($this->marge_gauche, $startY, $pageW, $endY - $startY, $borderRadius, '1111', 'D', array('width' => $borderThickness, 'color' => $borderColorArr));

                $currentY = $endY + 4;
                // Re-enable auto page break for subsequent content
                $pdf->SetAutoPageBreak(true, $heightforfooter);

                $debugMsg .= " | afterRender: page=" . $pageAfter . " Y=" . round($pdf->GetY(), 1);
                if ($pageAfter != $pageBefore) {
                    $debugMsg .= " *** TCPDF ADDED PAGE INTERNALLY ***";
                }
                file_put_contents(DOL_DATA_ROOT . '/digirisk_pdf_debug.log', $debugMsg . "\n", FILE_APPEND);
            }
        }

        // ----------------------------------------------------
        // MODE D'EMPLOI SIMPLIFIÉ
        // ----------------------------------------------------
        $contentUsage = $object->array_options['options_digirisk_usermanual'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentUsage))))) {
            $contentUsage = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_DESC ?? '';
        }
        $svgUsage = $getSvgPath('digirisk_usermanual_icon.svg');
        $titleUsage = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_LABEL : "MODE D'EMPLOI SIMPLIFIÉ";
        $colorUsage = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_COLOR : '#1a7a3c';
        $currentY = $this->_drawBlock($pdf, mb_strtoupper($titleUsage), $contentUsage, array(60, 160, 100), $currentY, 0, $svgUsage, $colorUsage);

        // ----------------------------------------------------
        // QUALIFICATION & HABILITATION
        // ----------------------------------------------------
        $contentQualif = $object->array_options['options_digirisk_qualification'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentQualif))))) {
            $contentQualif = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_DESC ?? '';
        }
        $svgQualif = $getSvgPath('digirisk_qualification_icon.svg');
        $titleQualif = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_LABEL : "QUALIFICATION & HABILITATION";
        $colorQualif = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_COLOR : '#1a5fa8';
        $currentY = $this->_drawBlock($pdf, mb_strtoupper($titleQualif), $contentQualif, array(40, 80, 160), $currentY, 0, $svgQualif, $colorQualif);

        // ----------------------------------------------------
        // HYGIÈNE & NETTOYAGE
        // ----------------------------------------------------
        $contentHyg = $object->array_options['options_digirisk_hygiene'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentHyg))))) {
            $contentHyg = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_DESC ?? '';
        }
        $svgHyg = $getSvgPath('digirisk_hygiene_icon.svg');
        $titleHyg = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_LABEL : "HYGIÈNE & NETTOYAGE";
        $colorHyg = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_COLOR : '#0e7e7e';
        $currentY = $this->_drawBlock($pdf, mb_strtoupper($titleHyg), $contentHyg, array(220, 140, 40), $currentY, 0, $svgHyg, $colorHyg);

        // ----------------------------------------------------
        // MAINTENANCE & CONTRÔLES
        // ----------------------------------------------------
        $contentMaint = $object->array_options['options_digirisk_maintenance'] ?? '';
        if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $contentMaint))))) {
            $contentMaint = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_DESC ?? '';
        }
        $svgMaint = $getSvgPath('digirisk_maintenance_icon.svg');
        $titleMaint = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_LABEL : "MAINTENANCE & CONTRÔLES";
        $colorMaint = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_COLOR : '#8b4000';
        $currentY = $this->_drawBlock($pdf, mb_strtoupper($titleMaint), $contentMaint, array(60, 120, 180), $currentY, 0, $svgMaint, $colorMaint);

        // ----------------------------------------------------
        // FOOTER MSG
        // ----------------------------------------------------
        $pdf->SetAutoPageBreak(false, 0);
        $nbpages = $pdf->getNumPages();
        
        for ($i = 1; $i <= $nbpages; $i++) {
            $pdf->setPage($i);
            // Remove bottom margin completely so TCPDF won't trigger page breaks at Y=282
            $pdf->setPageOrientation('', true, 0);
            $footerY = $this->page_hauteur - 15;
            $pdf->SetY($footerY);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetTextColor(120, 120, 120);
            
            // Left text
            $pdf->SetX($this->marge_gauche);
            $footerText = "Fichier : " . basename($file) . " - CC BY-SA DigiRisk";
            $pdf->Cell(0, 5, $footerText, 0, 0, 'L');
            
            // Right text
            $pdf->SetX($this->marge_gauche);
            $pdf->Cell(0, 5, "Page " . $i . " / " . $nbpages, 0, 0, 'R');
        }

        // Output PDF to file
        $pdf->Output($file, 'F');
        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        return 1;
    }

    private $lastBlockHeight = 0;

    private function _drawBlock($pdf, $title, $content, $color, $startY, $width = 0, $iconSvgPath = null, $svgColor = '#000000', $contentBorder = 'LBR', $minHeight = 0)
    {
        if ($width <= 0) {
            $width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
        }

        // Calculate exactly if the block will trigger a page break using a transaction
        $threshold = 30;
        if ($content !== '') {
            $pdf->startTransaction();
            $startPage = $pdf->getPage();
            $pdf->SetFont('helvetica', '', 9);
            // Simulate drawing the content to see if TCPDF adds a page
            $pdf->writeHTMLCell($width, 0, $this->marge_gauche, $startY + 7, $content, 0, 1, false, true, 'L', true);
            $endPage = $pdf->getPage();
            $pdf->rollbackTransaction(true);
            
            if ($endPage > $startPage) {
                $threshold = 9999; // Force page break before drawing
            }
        }

        // Check page break - use same heightforfooter as Dolibarr pattern
        $heightforfooter = $this->marge_basse + 15;
        if ($startY > $this->page_hauteur - $heightforfooter - $threshold) {
            $pdf->AddPage();
            // The only function to edit the bottom margin of current page to set it (Dolibarr pattern)
            $pdf->setPageOrientation('', true, $heightforfooter);
            $startY = $this->marge_haute;
        }

        $borderThickness = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_THICKNESS', '0.5');
        $borderRadius = (float) getDolGlobalString('DIGIRISK_PDF_BORDER_RADIUS', '2');

        // Clean content for TCPDF HTML parser
        if (empty($content) && $content !== '') {
            $content = "<i>(Non renseigné)</i>";
        }
        // Small padding hack
        if ($content !== '') {
            $content = '<table cellpadding="4"><tr><td>' . $content . '</td></tr></table>';
        }

        $pdf->SetXY($this->marge_gauche, $startY);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        
        if ($title !== '') {
            // Disable linear borders since we will draw a rounded rect around the whole block
            $pdf->SetFillColor(250, 250, 250);
            $pdf->Cell($width, 7, "", 0, 0, 'L', 1);
            
            $titleOffset = 2;
            if (!empty($iconSvgPath) && is_readable($iconSvgPath)) {
                $svgContent = file_get_contents($iconSvgPath);
                $svgContent = preg_replace('/fill="[^"]*"/', 'fill="'.$svgColor.'"', $svgContent);
                if (strpos($svgContent, 'fill=') === false) {
                    $svgContent = str_replace('<svg ', '<svg fill="'.$svgColor.'" ', $svgContent);
                    $svgContent = str_replace('<path ', '<path fill="'.$svgColor.'" ', $svgContent);
                }
                $pdf->ImageSVG('@' . $svgContent, $this->marge_gauche + 2, $startY + 1.5, 4, 4);
                $titleOffset = 8;
            }
            $pdf->SetXY($this->marge_gauche + $titleOffset, $startY);
            $pdf->Cell($width - $titleOffset, 7, $title, 0, 1, 'L', 0);
        } else {
            $pdf->SetXY($this->marge_gauche, $startY);
        }
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        
        if ($content !== '') {
            // Draw content without borders
            $pdf->writeHTMLCell($width, 0, $this->marge_gauche, '', $content, 0, 1, false, true, 'L', true);
        }
        
        $yAfter = $pdf->GetY();
        $blockHeight = $yAfter - $startY;
        
        if ($minHeight > $blockHeight) {
            $blockHeight = $minHeight;
            $yAfter = $startY + $blockHeight;
        }
        
        // Draw the rounded border around the entire block
        // Color of the border matches the icon/title color
        if ($blockHeight > 0) {
            $pdf->RoundedRect($this->marge_gauche, $startY, $width, $blockHeight, $borderRadius, '1111', 'D', array('width' => $borderThickness, 'color' => $color));
        }
        
        $this->lastBlockHeight = $blockHeight;
        
        return $yAfter + 4; // Add small margin bottom
    }
}
