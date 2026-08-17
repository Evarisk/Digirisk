<?php
/**
 * \file    custom/digiriskdolibarr/view/product/product_digirisk_risks.php
 * \ingroup digiriskdolibarr
 * \brief   Tab "Risques" for Product â€” product risk blocks
 */

require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../../class/riskanalysis/productrisk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/medias.lib.php';

$moduleNameLowerCase  = 'digiriskdolibarr';
$moduleNameUpperCase  = 'DIGIRISKDOLIBARR';

$langs->loadLangs(['digiriskdolibarr@digiriskdolibarr', 'products']);

$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

if ($id < 1 && empty($ref)) {
    accessforbidden();
}

$object = new Product($db);
$object->fetch($id, $ref);
if ($object->id <= 0) {
    accessforbidden();
}

$productRiskObj = new ProductRisk($db);

$permissiontoadd = $user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer');

// â”€â”€ Danger categories â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$dangerCategories = Risk::getDangerCategories('risk');
$dangerCatByPos   = [];
foreach ($dangerCategories as $cat) {
    $dangerCatByPos[(int) $cat['position']] = $cat;
}
$pictoBaseUrl = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/';

// â”€â”€ Signalisation categories (EPI protections â€” OBLIGATION/* only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$signalisationFile    = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
$allSignalisations    = file_exists($signalisationFile) ? (json_decode(file_get_contents($signalisationFile), true) ?: []) : [];
$protectionCategories = array_values(array_filter($allSignalisations, static function ($s) {
    return strpos($s['name_thumbnail'] ?? '', 'OBLIGATION/') === 0;
}));
$protectionCatByPos   = [];
foreach ($protectionCategories as $p) {
    $protectionCatByPos[(int) $p['position']] = $p;
}

// â”€â”€ Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// AJAX: save danger category
if ($action == 'ajax_save_danger_category' && $permissiontoadd) {
    header('Content-Type: application/json');
    $riskId = GETPOSTINT('risk_id');
    $catPos = GETPOSTINT('danger_category');
    $ajRisk = new ProductRisk($db);
    if ($ajRisk->fetch($riskId) > 0 && $ajRisk->fk_product == $object->id) {
        $ajRisk->danger_category = $catPos;
        $ajRisk->update($user);
        $ajRisk->fetch($riskId);
        echo json_encode(['ok' => true, 'date' => dol_print_date($ajRisk->tms, 'dayhour')]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// AJAX: inline description save
if ($action == 'ajax_save_risk_desc' && $permissiontoadd) {
    header('Content-Type: application/json');
    $riskId  = GETPOSTINT('risk_id');
    $desc    = GETPOST('description', 'restricthtml');
    $ajRisk  = new ProductRisk($db);
    if ($ajRisk->fetch($riskId) > 0 && $ajRisk->fk_product == $object->id) {
        $ajRisk->description = $desc;
        $ajRisk->update($user);
        $ajRisk->fetch($riskId);
        echo json_encode(['ok' => true, 'date' => dol_print_date($ajRisk->tms, 'dayhour')]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// AJAX: add a protection to a risk
if ($action == 'ajax_add_protection' && $permissiontoadd) {
    header('Content-Type: application/json');
    $riskId   = GETPOSTINT('risk_id');
    $protPos  = GETPOSTINT('protection_position');
    $protCom  = GETPOST('protection_comment', 'restricthtml');
    $ajRisk   = new ProductRisk($db);
    if ($ajRisk->fetch($riskId) > 0 && $ajRisk->fk_product == $object->id) {
        $prots = $ajRisk->getProtections();
        $prots[] = ['position' => $protPos, 'comment' => dol_escape_htmltag(trim($protCom ?? '')), 'date' => 0];
        $ajRisk->protections_json = json_encode($prots, JSON_UNESCAPED_UNICODE);
        $ajRisk->update($user);
        $ajRisk->fetch($riskId);
        // Update date with DB tms
        $prots[count($prots) - 1]['date'] = $ajRisk->tms;
        $ajRisk->protections_json = json_encode($prots, JSON_UNESCAPED_UNICODE);
        $db->query('UPDATE ' . MAIN_DB_PREFIX . 'product_risk SET protections_json = \'' . $db->escape($ajRisk->protections_json) . '\' WHERE rowid = ' . $riskId);
        echo json_encode(['ok' => true, 'count' => count($prots)]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// AJAX: delete a protection from a risk by index
if ($action == 'ajax_delete_protection' && $permissiontoadd) {
    header('Content-Type: application/json');
    $riskId  = GETPOSTINT('risk_id');
    $protIdx = GETPOSTINT('prot_index');
    $ajRisk  = new ProductRisk($db);
    if ($ajRisk->fetch($riskId) > 0 && $ajRisk->fk_product == $object->id) {
        $prots = $ajRisk->getProtections();
        if (isset($prots[$protIdx])) {
            array_splice($prots, $protIdx, 1);
        }
        $ajRisk->protections_json = json_encode(array_values($prots), JSON_UNESCAPED_UNICODE);
        $ajRisk->update($user);
        echo json_encode(['ok' => true, 'count' => count($prots)]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// AJAX: save protection comment
if ($action == 'ajax_save_prot_comment' && $permissiontoadd) {
    header('Content-Type: application/json');
    $riskId  = GETPOSTINT('risk_id');
    $protIdx = GETPOSTINT('prot_index');
    $comment = GETPOST('comment', 'restricthtml');
    $ajRisk  = new ProductRisk($db);
    if ($ajRisk->fetch($riskId) > 0 && $ajRisk->fk_product == $object->id) {
        $prots = $ajRisk->getProtections();
        if (isset($prots[$protIdx])) {
            $prots[$protIdx]['comment'] = dol_escape_htmltag(trim($comment ?? ''));
            $prots[$protIdx]['date'] = 0;
            $ajRisk->protections_json = json_encode($prots, JSON_UNESCAPED_UNICODE);
            $ajRisk->update($user);
            $ajRisk->fetch($riskId);
            // Update date with DB tms
            $prots[$protIdx]['date'] = $ajRisk->tms;
            $ajRisk->protections_json = json_encode($prots, JSON_UNESCAPED_UNICODE);
            $db->query('UPDATE ' . MAIN_DB_PREFIX . 'product_risk SET protections_json = \'' . $db->escape($ajRisk->protections_json) . '\' WHERE rowid = ' . $riskId);
            echo json_encode(['ok' => true, 'date' => dol_print_date($ajRisk->tms, 'dayhour')]);
        } else {
            echo json_encode(['ok' => false]);
        }
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// Saturne standard: upload photo via AJAX (called by mediaBlock.js)
if ($action == 'uploadPhoto' && $permissiontoadd && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    $uploadSubDir = GETPOST('sub_dir', 'restricthtml');
    $uploadDir    = $conf->digiriskdolibarr->dir_output;
    if (!empty($uploadSubDir)) {
        $uploadDir .= '/' . $uploadSubDir;
    }
    if (!dol_is_dir($uploadDir)) {
        dol_mkdir($uploadDir);
    }
    $allowOverwrite = GETPOSTINT('overwrite') ? 1 : 0;
    dol_add_file_process($uploadDir, $allowOverwrite, 1, 'userfile', '', null, '', 1);
}

if ($action == 'deletePhoto' && $permissiontoadd) {
    $delFilename   = GETPOST('filename', 'restricthtml');
    $delSubDir     = GETPOST('sub_dir', 'restricthtml');
    $delDir        = $conf->digiriskdolibarr->dir_output;
    if (!empty($delSubDir)) {
        $delDir .= '/' . $delSubDir;
    }
    $fullPath = $delDir . '/' . $delFilename;
    if (is_file($fullPath)) {
        unlink($fullPath);
        // Also remove thumbs
        foreach (['_mini', '_small', '_medium', '_large'] as $thumbSuffix) {
            $thumbName = $delDir . '/thumbs/' . preg_replace('/(\.[^.]+)$/', $thumbSuffix . '$1', $delFilename);
            if (is_file($thumbName)) {
                unlink($thumbName);
            }
        }
    }
}

// Update global risks description for this product
if ($action == 'update_risks_desc' && $permissiontoadd) {
    $object->array_options['options_digirisk_risks'] = GETPOST('options_digirisk_risks', 'restricthtml');
    $result = $object->updateExtraField('digirisk_risks', '', $user);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
    }
    $action = 'view';
}

// Add a new product risk
if ($action == 'add_product_risk' && $permissiontoadd) {
    $newRisk                   = new ProductRisk($db);
    $newRisk->fk_product       = $object->id;
    $newRisk->danger_category  = GETPOSTINT('danger_category');
    $newRisk->description      = GETPOST('risk_description', 'restricthtml');
    $newRisk->protections_json = '[]';
    $result = $newRisk->create($user);
    if ($result < 0) {
        setEventMessages($newRisk->error, [], 'errors');
        $action = 'view';
    } else {
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
        // Redirect to edit mode on the new risk so it opens at top
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_risk&risk_id=' . $newRisk->id . '&token=' . newToken() . '#risk-' . $newRisk->id);
        exit;
    }
}



// Delete a product risk
if ($action == 'delete_product_risk' && $permissiontoadd) {
    $riskId  = GETPOSTINT('risk_id');
    $delRisk = new ProductRisk($db);
    if ($delRisk->fetch($riskId) > 0 && $delRisk->fk_product == $object->id) {
        $msgDesc = trim(strip_tags($delRisk->description));
        if (strlen($msgDesc) > 50) {
            $msgDesc = dol_trunc($msgDesc, 50) . '...';
        }
        $delRisk->delete($user);
        setEventMessages('Suppression risque : ' . dol_escape_htmltag($msgDesc), null, 'mesgs');
    }
    $action = 'view';
}

// Upload photo on an existing risk
if ($action == 'upload_risk_photo' && $permissiontoadd) {
    $riskId = GETPOSTINT('risk_id');
    $upRisk = new ProductRisk($db);
    if ($upRisk->fetch($riskId) > 0 && $upRisk->fk_product == $object->id) {
        $photoDir = $upRisk->getPhotoDir();
        dol_mkdir($photoDir);
        dol_add_file_process($photoDir, 0, 1, 'risk_photo', '', null, '', 0);
        setEventMessages($langs->trans('FileSuccessfullyAdded'), null, 'mesgs');
    }
    $action = 'view';
}

// Delete a photo
if ($action == 'delete_risk_photo' && $permissiontoadd) {
    $riskId   = GETPOSTINT('risk_id');
    $filename = dol_sanitizeFileName(GETPOST('filename', 'alphanohtml'));
    $delPhRisk = new ProductRisk($db);
    if ($delPhRisk->fetch($riskId) > 0 && $delPhRisk->fk_product == $object->id && !empty($filename)) {
        $file = $delPhRisk->getPhotoDir() . $filename;
        if (dol_is_file($file)) {
            dol_delete_file($file, 0, 0, 0, null, 1);
        }
        setEventMessages($langs->trans('FileWasRemoved', $filename), null, 'mesgs');
    }
    $action = 'view';
}

// â”€â”€ Page output â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$form  = new Form($db);
$title = $langs->trans('Product') . ' - ' . $langs->trans('Risks');
llxHeader('', $title, '');

$head  = product_prepare_head($object);
$titre = $langs->trans('CardProduct' . $object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

print dol_get_fiche_head($head, 'digirisk_risks', $titre, -1, $picto);

$linkback = '<a href="' . DOL_URL_ROOT . '/product/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '', '', 0, '', '', 1);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// â”€â”€ CSS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
print '<link rel="stylesheet" href="' . DOL_URL_ROOT . '/custom/saturne/css/saturne.min.css">';
print '<script src="' . DOL_URL_ROOT . '/custom/saturne/js/saturne.min.js"></script>';
print '<script>window.saturne = window.saturne || {}; window.saturne.config = window.saturne.config || {}; window.saturne.config.urlRoot = "' . DOL_URL_ROOT . '";</script>';
print '<style>
.dr-risk-block { border:1px solid #ddd; border-radius:6px; margin-bottom:6px; overflow:hidden; position:relative; }
.dr-risk-header { display:flex; align-items:center; gap:8px; padding:7px 12px; background:#f7f7f7; border-bottom:1px solid #e0e0e0; font-weight:bold; }
.dr-risk-header.is-edit { border-radius:0; }
.dr-risk-header img.dr-cat-icon { width:48px; height:48px; object-fit:contain; flex-shrink:0; }
/* Description inline in header */
.dr-risk-desc-inline { flex:1; font-size:.85em; font-weight:normal; color:#444; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
.dr-editable { cursor:text; border-radius:3px; padding:1px 4px; transition:background .15s, box-shadow .15s; outline:none; }
.dr-editable:hover { background:rgba(0,0,0,.04); }
.dr-editable:focus { white-space:normal; overflow:visible; background:#fff; box-shadow:0 0 0 2px #1a5fa8; color:#111; }
.dr-editable:empty:before { content:attr(data-placeholder); color:#aaa; font-style:italic; pointer-events:none; }
@keyframes dr-saved { 0%{box-shadow:0 0 0 2px #27ae60} 80%{box-shadow:0 0 0 2px #27ae60} 100%{box-shadow:none} }
.dr-saved-flash { animation:dr-saved 1.2s ease forwards; }
@keyframes dr-block-saved { 0%{border-color:#27ae60;box-shadow:0 0 0 2px rgba(39,174,96,.5)} 80%{border-color:#27ae60;box-shadow:0 0 0 2px rgba(39,174,96,.3)} 100%{border-color:#ddd;box-shadow:none} }
.dr-block-saved { animation:dr-block-saved 1.4s ease forwards; }
.dr-risk-date { font-size:.76em; font-weight:normal; white-space:nowrap; margin-right:4px; flex-shrink:0; }
/* Header photo thumbs */
.dr-header-photos { display:flex; gap:4px; align-items:center; flex-shrink:0; }
.dr-header-photo { width:32px; height:32px; border-radius:3px; overflow:hidden; border:1px solid #ccc; flex-shrink:0; }
.dr-header-photo img { width:100%; height:100%; object-fit:cover; }
/* Saturne media block in header: force perfect vertical alignment */
.dr-risk-header .linked-medias { margin:0 !important; padding:0 !important; flex-shrink:0; display:inline-flex !important; align-items:center !important; line-height:0 !important; }
.dr-risk-header .add-medias { margin:0 !important; padding:0 !important; }
.dr-risk-header .saturne-media-upload-block { gap:6px !important; align-items:center !important; margin:0 !important; padding:0 !important; }
.dr-risk-header .saturne-media-gallery { display:inline-flex !important; align-items:center !important; gap:6px !important; margin:0 !important; padding:0 !important; }
.dr-risk-header .fast-upload-options { margin:0 !important; padding:0 !important; height:0 !important; overflow:hidden !important; }
.dr-risk-header .saturne-media-count-badge { font-size:10px; height:16px; min-width:16px; padding:0 3px; top:-5px; right:-5px; }
/* Edit mode body */
.dr-risk-body { padding:10px 12px; }
.dr-edit-layout { display:flex; gap:14px; align-items:flex-start; }
.dr-edit-left { flex:1; min-width:0; }
.dr-edit-right { flex:0 0 auto; display:flex; flex-direction:column; align-items:flex-end; gap:6px; }
.dr-risk-desc { width:100%; min-height:40px; max-height:80px; border:1px solid #ccc; border-radius:4px; padding:6px 8px; font-size:.9em; resize:vertical; }
.dr-risk-actions { display:flex; gap:6px; align-items:flex-start; margin-top:6px; }
.dr-risk-actions-footer { display:flex; justify-content:flex-end; gap:6px; margin-top:10px; padding-top:8px; border-top:1px solid #eee; }
.dr-risk-photos-inline { display:flex; flex-wrap:wrap; gap:4px; align-items:center; }
.dr-risk-photo-thumb { position:relative; width:48px; height:48px; border-radius:4px; overflow:hidden; border:1px solid #ddd; }
.dr-risk-photo-thumb img { width:100%; height:100%; object-fit:cover; }
.dr-risk-photo-del { position:absolute; top:1px; right:1px; background:rgba(180,0,0,.8); color:#fff; border:none; border-radius:50%; width:16px; height:16px; font-size:9px; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; line-height:1; }
.dr-protection-row { display:flex; gap:6px; align-items:center; margin-top:6px; }
.dr-protection-row input[type=text] { flex:1; }
.dr-add-protection { margin-top:8px; display:inline-flex; align-items:center; gap:5px; font-size:.88em; cursor:pointer; color:#fff; background:#1a5fa8; border:none; border-radius:4px; padding:5px 12px; }
.dr-btn { border:none; border-radius:4px; cursor:pointer; padding:6px 10px; font-size:.85em; color:#fff; }
.dr-btn-orange { background:#e68a00; }
.dr-upload-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; background:#e68a00; border-radius:4px; color:#fff; cursor:pointer; border:none; font-size:1em; }
.dr-cat-modal { display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; }
.dr-cat-modal.open { display:block; }
.dr-cat-modal__overlay { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:1; }
.dr-cat-modal__dialog { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:8px; width:min(680px,95vw); max-height:80vh; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,.25); z-index:2; }
.dr-cat-modal__header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #ddd; font-weight:bold; font-size:1.05em; }
.dr-cat-modal__header button { background:none; border:none; font-size:1.3em; cursor:pointer; color:#555; }
.dr-cat-modal__grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; padding:16px; overflow-y:auto; }
.dr-cat-option { display:flex; flex-direction:column; align-items:center; gap:6px; padding:10px 6px; border:2px solid transparent; border-radius:8px; cursor:pointer; transition:border-color .15s, background .15s; text-align:center; font-size:.82em; }
.dr-cat-option:hover { border-color:#1a5fa8; background:#f0f4ff; }
.dr-cat-option img { width:60px; height:60px; object-fit:contain; }
</style>';

// ── Add button (top-LEFT) ─────────────────────────────────────────────────────────
if ($permissiontoadd) {
    // Add button and title banner
    $risksTitle = !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_LABEL) ? mb_strtoupper($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_LABEL) : mb_strtoupper($langs->transnoentities('DigiriskRisks'));
    $risksIcon = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON ?? 'fas fa-exclamation-triangle';
    $risksColor = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_COLOR ?? '#D32F2F';
    $object->fetch_optionals();
    $risksDefaultDesc = $object->array_options['options_digirisk_risks'] ?? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_DESC ?? '';
    
    $editRisksDesc = (GETPOST('action', 'aZ09') == 'edit_risks_desc');

    $useSvg = $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_USE_SVG ?? 0;
    $svgPath = $conf->digiriskdolibarr->dir_output . '/icons/digirisk_risks_icon.svg';
    $hasSvg = file_exists($svgPath);
    
    if ($useSvg && $hasSvg) {
        $svgContent = file_get_contents($svgPath);
        $svgContent = preg_replace('/<svg([^>]*)>/i', '<svg$1 fill="' . dol_escape_htmltag($risksColor) . '" width="20" height="20">', $svgContent);
        $svgContent = preg_replace('/fill="[^"]*"/i', 'fill="' . dol_escape_htmltag($risksColor) . '"', $svgContent);
        $iconHtml = '<span style="display:inline-block; margin-right:8px; vertical-align:middle;">' . $svgContent . '</span>';
    } else {
        $iconHtml = '<i class="' . dol_escape_htmltag($risksIcon) . '" style="margin-right: 8px;"></i> ';
    }

    print '<div style="border-bottom: 2px solid ' . dol_escape_htmltag($risksColor) . '; padding-bottom: 5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">';
    print '<div style="color: ' . dol_escape_htmltag($risksColor) . '; font-size: 1.1em; font-weight: bold; display: flex; align-items: center;">';
    print $iconHtml . $risksTitle;
    print '</div>';

    print '<button type="button" class="wpeo-button button-square-40 button-blue" onclick="drOpenCatModal(\'new\')" title="' . dol_escape_htmltag($langs->trans('AddProductRisk')) . '">';
    print '<i class="fas fa-exclamation-triangle button-icon"></i>';
    print '<i class="fas fa-plus-circle button-add animated"></i>';
    print '</button>';
    
    print '</div>';

    if ($editRisksDesc) {
        print '<div style="margin-bottom: 20px;">';
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
        print '<input type="hidden" name="token" value="' . currentToken() . '">';
        print '<input type="hidden" name="action" value="update_risks_desc">';
        
        require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $doleditor = new DolEditor('options_digirisk_risks', $risksDefaultDesc, '', 150, 'dolibarr_details', 'In', false, true, true, ROWS_5, '100%');
        $doleditor->Create();
        
        print '<div style="text-align: right; margin-top: 10px;">';
        print '<input type="submit" class="button button-save" value="' . $langs->trans('Save') . '">';
        print ' <a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" class="button button-cancel">' . $langs->trans('Cancel') . '</a>';
        print '</div>';
        print '</form></div>';
    } else {
        print '<div style="margin-bottom: 20px; font-size: 0.95em; color: #333; display: flex; justify-content: space-between; align-items: flex-start;">';
        print '<div style="flex: 1;">' . (empty($risksDefaultDesc) ? '<span style="color:#aaa;font-style:italic;">' . $langs->trans('NoDescription') . '</span>' : $risksDefaultDesc) . '</div>';
        if ($permissiontoadd) {
            $pencilUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_risks_desc&token=' . currentToken();
            print '<a href="' . $pencilUrl . '" title="' . $langs->trans('Modify') . '" style="margin-left: 10px; opacity: 0.5;">' . img_edit() . '</a>';
        }
        print '</div>';
    }
}

// ── Existing risks ─────────────────────────────────────────────────────────────
$existingRisks = $productRiskObj->fetchAllByProduct($object->id);
foreach ($existingRisks as $risk) {
    $cat      = $dangerCatByPos[$risk->danger_category] ?? null;
    $catName  = $cat ? $cat['name'] : $langs->trans('UnknownCategory');
    $catThumb = $cat ? $cat['thumbnail_name'] : '';
    $thumbUrl = $catThumb ? ($pictoBaseUrl . $catThumb . '.png') : '';
    $prots    = $risk->getProtections();

    print '<div class="dr-risk-block" id="risk-' . $risk->id . '">';

    // ── Header (always visible) ────────────────────────────────────────────
    print '<div class="dr-risk-header">';
    // Category icon
    if ($thumbUrl) {
        $iconClickAttr = $permissiontoadd ? ' onclick="drOpenCatModal(\'update_' . $risk->id . '\')" style="cursor:pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.7;" onmouseout="this.style.opacity=1;" title="' . dol_escape_htmltag($langs->trans('Modify')) . '"' : '';
        print '<img class="dr-cat-icon" src="' . dol_escape_htmltag($thumbUrl) . '" alt=""' . $iconClickAttr . '>';
    }
    // Description inline (always editable)
    $descShort = !empty($risk->description) ? dol_escape_htmltag($risk->description) : '';
    $placeholder = $langs->trans('DescribeRiskOnProduct');
    print '<span class="dr-risk-desc-inline dr-editable"
        contenteditable="true"
        data-risk-id="' . $risk->id . '"
        data-ajax-url="' . dol_escape_htmltag($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=ajax_save_risk_desc&token=' . currentToken()) . '"
        data-placeholder="' . dol_escape_htmltag($placeholder) . '"
        spellcheck="false">' . $descShort . '</span>';
        
    // Add Protection button (right aligned, matching Saturne media block style)
    if ($permissiontoadd) {
        print '<div style="display: inline-flex; align-items: center; justify-content: center; width: 50px; min-width: 50px; height: 50px; min-height: 50px; cursor: pointer; flex-shrink: 0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,.05);" onclick="drAddProtection(' . $risk->id . ')" title="' . dol_escape_htmltag($langs->trans('AddProtection')) . '">';
        print '<img src="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/OBLIGATION/OBLIGATION-general.jpg" style="width:100%; height:100%; object-fit:contain; border-radius:12px;" alt="">';
        print '</div>';
    }

    // Saturne media block in header (always visible)
    $riskSubDir = 'medias/product/' . $object->id . '/risks/' . $risk->id;
    print saturne_render_media_block('digiriskdolibarr', $riskSubDir, 'risk-' . $risk->id, '', [
        'show_photo'  => true,
        'show_audio'  => false,
        'show_upload' => $permissiontoadd,
    ]);
    // Date
    $dateToShow = !empty($risk->tms) ? $risk->tms : $risk->date_creation;
    $dateLabel  = !empty($risk->tms) ? $langs->trans('LastModification') : $langs->trans('DateCreation');
    if ($dateToShow) {
        print '<span class="dr-risk-date opacitymedium" title="' . dol_escape_htmltag($dateLabel) . '">';
        print '<i class="fas fa-clock" style="margin-right:3px;font-size:.8em;"></i>';
        print dol_print_date($dateToShow, 'dayhour');
        print '</span>';
    }
    // Delete
    if ($permissiontoadd) {
        $delRiskUrl = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete_product_risk&risk_id=' . $risk->id . '&token=' . currentToken();
        $descForModal = dol_escape_js(dol_trunc(strip_tags($risk->description), 100));
        print ' <a href="#" onclick="drConfirmRiskDelete(\'' . dol_escape_js($langs->trans('ConfirmDeleteProductRisk')) . '\', \'' . $descForModal . '\', \'' . $delRiskUrl . '\'); return false;" title="' . $langs->trans('Delete') . '">' . img_delete() . '</a>';
    }
    print '</div>'; // header

    // ── Protections (always inline) ──────────────────────────────────────
    if (!empty($prots) || $permissiontoadd) {
        $ajaxBaseUrl = dol_escape_htmltag($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . currentToken());
        print '<div class="dr-risk-body" style="padding:6px 12px;">';
        print '<div class="dr-prot-inline" id="dr-prot-container-' . $risk->id . '" data-risk-id="' . $risk->id . '" data-ajax-url="' . $ajaxBaseUrl . '">';
        foreach ($prots as $ki => $prot) {
            $pCat  = $protectionCatByPos[$prot['position']] ?? null;
            $pThumb = $pCat ? (DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $pCat['name_thumbnail']) : '';
            print '<div class="dr-protection-row" data-prot-index="' . $ki . '">';
            if ($pThumb) {
                print '<img src="' . dol_escape_htmltag($pThumb) . '" style="width:48px;height:48px;object-fit:contain;flex-shrink:0;" alt="">';
            }
            print '<span class="dr-prot-comment dr-editable"
                contenteditable="true"
                data-risk-id="' . $risk->id . '"
                data-prot-index="' . $ki . '"
                data-placeholder="' . $langs->trans('Comment') . '"
                spellcheck="false"
                style="flex:1;font-size:.88em;font-weight:normal;">' . dol_escape_htmltag($prot['comment'] ?? '') . '</span>';
            // Date (stored as DB tms timestamp, same as header)
            $protDate = !empty($prot['date']) ? $prot['date'] : (!empty($risk->tms) ? $risk->tms : $risk->date_creation);
            if ($protDate) {
                // Handle legacy string dates
                if (is_string($protDate) && !is_numeric($protDate)) {
                    $protDate = strtotime($protDate);
                }
                print '<span class="dr-risk-date opacitymedium" style="flex-shrink:0;"><i class="fas fa-clock" style="margin-right:3px;font-size:.8em;"></i>' . dol_print_date($protDate, 'dayhour') . '</span>';
            }
            if ($permissiontoadd) {
                print '<a href="#" onclick="drDeleteProtection(' . $risk->id . ',' . $ki . ',this);return false;" title="' . $langs->trans('Delete') . '">' . img_delete() . '</a>';
            }
            print '</div>';
        }
        print '</div>'; // dr-prot-inline
        print '</div>'; // dr-risk-body
    }

    print '</div>'; // dr-risk-block
}

// â”€â”€ Add risk form (hidden, shown by JS after category picked) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($permissiontoadd) {
    print '<div id="dr-new-form-wrap" style="display:none;">';
    print '<div class="dr-risk-block" style="border-color:#1a5fa8;">';
    print '<div class="dr-risk-header" style="background:#f0f4ff;" id="dr-new-header">';
    print '<span class="dr-risk-name" style="color:#1a5fa8;"></span>';
    print '</div>';
    print '<div class="dr-risk-body">';
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" enctype="multipart/form-data" id="dr-new-form">';
    print '<input type="hidden" name="token" value="' . currentToken() . '">';
    print '<input type="hidden" name="action" value="add_product_risk">';
    print '<input type="hidden" name="danger_category" id="dr-new-cat-input">';

    print '<div style="margin-bottom:8px;">';
    print '<label style="font-weight:bold;display:block;margin-bottom:4px;">' . $langs->trans('Description') . '</label>';
    print '<textarea name="risk_description" class="dr-risk-desc" placeholder="' . $langs->trans('DescribeRiskOnProduct') . '"></textarea>';
    print '</div>';

    print '<div class="dr-risk-actions" style="margin-bottom:10px;">';
    print '<label class="dr-btn dr-btn-orange" style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;">';
    print '<i class="fas fa-camera"></i> ' . $langs->trans('Photo');
    print '<input type="file" name="risk_photo" accept="image/*" style="display:none">';
    print '</label>';
    print '</div>';

    print '<div class="dr-risk-actions">';
    print '<input type="submit" class="button button-save" value="' . $langs->trans('Add') . '">';
    print ' <button type="button" class="button button-cancel" onclick="drCancelNewRisk()">' . $langs->trans('Cancel') . '</button>';
    print '</div>';
    print '</form>';
    print '</div></div></div>'; // body / block / wrap
}



// â”€â”€ Danger category modal (picker for risk family) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
print '<div id="dr-cat-modal" class="dr-cat-modal">';
print '<div class="dr-cat-modal__overlay" onclick="drCloseCatModal()"></div>';
print '<div class="dr-cat-modal__dialog">';
print '<div class="dr-cat-modal__header"><span>' . $langs->trans('SelectDangerCategory') . '</span><button type="button" onclick="drCloseCatModal()">&times;</button></div>';
print '<div class="dr-cat-modal__grid">';
foreach ($dangerCategories as $cat) {
    $thumb = dol_escape_htmltag(DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $cat['thumbnail_name'] . '.png');
    $pos   = (int) $cat['position'];
    $name  = dol_escape_htmltag($cat['name']);
    print '<div class="dr-cat-option" onclick="drSelectCategory(' . $pos . ', \'' . addslashes($cat['name']) . '\', \'' . addslashes(DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $cat['thumbnail_name'] . '.png') . '\')">'
        . '<img src="' . $thumb . '" alt="">'
        . '<span>' . $name . '</span>'
        . '</div>';
}
print '</div></div></div>';

// â”€â”€ Protection modal (OBLIGATION EPI picker) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
print '<div id="dr-prot-modal" class="dr-cat-modal">';
print '<div class="dr-cat-modal__overlay" onclick="drCloseProtModal()"></div>';
print '<div class="dr-cat-modal__dialog">';
print '<div class="dr-cat-modal__header"><span>' . $langs->trans('SelectProtection') . '</span><button type="button" onclick="drCloseProtModal()">&times;</button></div>';
print '<div class="dr-cat-modal__grid">';
foreach ($protectionCategories as $prot) {
    $thumb = dol_escape_htmltag(DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $prot['name_thumbnail']);
    $pos   = (int) $prot['position'];
    $name  = dol_escape_htmltag($prot['name']);
    print '<div class="dr-cat-option" onclick="drSelectProtection(' . $pos . ', \'' . addslashes($prot['name']) . '\', \'' . addslashes(DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $prot['name_thumbnail']) . '\')">'
        . '<img src="' . $thumb . '" alt="">'
        . '<span>' . $name . '</span>'
        . '</div>';
}
print '</div></div></div>';

// ── Custom Confirm Modal ───────────────────────────────────────────────
print '<div id="dr-confirm-modal" class="dr-cat-modal">';
print '<div class="dr-cat-modal__overlay" onclick="drCloseConfirmModal()"></div>';
print '<div class="dr-cat-modal__dialog" style="max-width:400px;text-align:center;">';
print '<div class="dr-cat-modal__header"><span>' . $langs->trans('Confirmation') . '</span><button type="button" onclick="drCloseConfirmModal()">&times;</button></div>';
print '<div style="padding:20px;">';
print '<p id="dr-confirm-msg" style="margin-bottom:5px;font-weight:bold;"></p>';
print '<p id="dr-confirm-desc" style="margin-bottom:20px;font-style:italic;color:#666;word-break:break-word;"></p>';
print '<div style="display:flex;justify-content:center;gap:10px;">';
print '<a id="dr-confirm-btn" href="#" class="button button-save" style="background:#b72020;border-color:#a01a1a;">' . $langs->trans('Delete') . '</a>';
print '<button type="button" class="button button-cancel" onclick="drCloseConfirmModal()">' . $langs->trans('Cancel') . '</button>';
print '</div></div></div></div>';

// â”€â”€ JS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
print '<script>
var _drCatModalTarget = null;
var _drProtContainer  = null;

/* ── Inline description auto-save ─────────────────────────────────────── */
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".dr-editable").forEach(function(el) {
        // Prevent newlines (Enter = blur = save)
        el.addEventListener("keydown", function(e) {
            if (e.key === "Enter") { e.preventDefault(); el.blur(); }
            if (e.key === "Escape") { el.blur(); }
        });

        el.addEventListener("blur", function() {
            var text    = el.innerText.trim();
            var url     = el.dataset.ajaxUrl;
            var riskId  = el.dataset.riskId;
            var block   = el.closest(".dr-risk-block");

            var fd = new FormData();
            fd.append("risk_id",     riskId);
            fd.append("description", text);

            fetch(url, { method: "POST", body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        // Green flash on the block
                        if (block) {
                            block.classList.remove("dr-block-saved");
                            void block.offsetWidth; // reflow
                            block.classList.add("dr-block-saved");
                            block.addEventListener("animationend", function() {
                                block.classList.remove("dr-block-saved");
                            }, { once: true });
                        }
                        // Also flash the editable
                        el.classList.remove("dr-saved-flash");
                        void el.offsetWidth;
                        el.classList.add("dr-saved-flash");
                        // Update header date
                        if (block && data.date) {
                            var headerDate = block.querySelector(".dr-risk-header .dr-risk-date");
                            if (headerDate) { headerDate.innerHTML = "<i class=\"fas fa-clock\" style=\"margin-right:3px;font-size:.8em;\"></i>" + data.date; }
                        }
                    }
                });
        });
    });
});


/* Risk family picker */
function drOpenCatModal(target) {
    _drCatModalTarget = target;
    document.getElementById("dr-cat-modal").classList.add("open");
}

/* Custom confirm modal */
function drConfirmRiskDelete(msg, desc, url) {
    document.getElementById("dr-confirm-msg").textContent = msg;
    document.getElementById("dr-confirm-desc").textContent = desc;
    document.getElementById("dr-confirm-btn").href = url;
    document.getElementById("dr-confirm-modal").classList.add("open");
}

function drCloseConfirmModal() {
    document.getElementById("dr-confirm-modal").classList.remove("open");
}
function drCloseCatModal() {
    document.getElementById("dr-cat-modal").classList.remove("open");
    _drCatModalTarget = null;
}
function drSelectCategory(pos, name, thumb) {
    var target = _drCatModalTarget; // save BEFORE drCloseCatModal nulls it
    drCloseCatModal();
    if (target === "new") {
        document.getElementById("dr-new-cat-input").value = pos;
        document.getElementById("dr-new-form").submit();
    } else if (target && target.startsWith("update_")) {
        var riskId = target.split("_")[1];
        var url = "' . dol_escape_js($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=ajax_save_danger_category&token=' . currentToken()) . '";
        
        var fd = new FormData();
        fd.append("risk_id", riskId);
        fd.append("danger_category", pos);

        fetch(url, { method: "POST", body: fd })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (data.ok) {
                    var block = document.getElementById("risk-" + riskId);
                    if (block) {
                        var img = block.querySelector(".dr-cat-icon");
                        if (img && thumb) {
                            img.src = thumb;
                        }
                        var headerDate = block.querySelector(".dr-risk-header .dr-risk-date");
                        if (headerDate && data.date) { 
                            headerDate.innerHTML = "<i class=\"fas fa-clock\" style=\"margin-right:3px;font-size:.8em;\"></i>" + data.date; 
                        }
                        
                        block.classList.remove("dr-block-saved");
                        void block.offsetWidth; // reflow
                        block.classList.add("dr-block-saved");
                        block.addEventListener("animationend", function() {
                            block.classList.remove("dr-block-saved");
                        }, { once: true });
                    }
                }
            });
    }
}

/* Protection EPI picker */
var _drProtRiskId = null;
function drOpenProtModal(riskId) {
    _drProtRiskId = riskId;
    document.getElementById("dr-prot-modal").classList.add("open");
}
function drCloseProtModal() {
    document.getElementById("dr-prot-modal").classList.remove("open");
    _drProtRiskId = null;
}
function drSelectProtection(pos, name, thumb) {
    var riskId = _drProtRiskId;
    drCloseProtModal();
    if (!riskId) return;
    var container = document.getElementById("dr-prot-container-" + riskId);
    if (!container) return;
    var url = container.dataset.ajaxUrl + "&action=ajax_add_protection";
    var fd = new FormData();
    fd.append("risk_id", riskId);
    fd.append("protection_position", pos);
    fd.append("protection_comment", "");
    fetch(url, { method: "POST", body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.ok) { location.reload(); }
        });
}

function drAddProtection(riskId) {
    drOpenProtModal(riskId);
}
function drDeleteProtection(riskId, protIdx, btn) {
    var container = document.getElementById("dr-prot-container-" + riskId);
    if (!container) return;
    var url = container.dataset.ajaxUrl + "&action=ajax_delete_protection";
    var fd = new FormData();
    fd.append("risk_id", riskId);
    fd.append("prot_index", protIdx);
    fetch(url, { method: "POST", body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.ok) { 
                btn.closest(".dr-protection-row").remove(); 
                if (typeof $.jnotify !== "undefined") {
                    $.jnotify(\'' . dol_escape_js($langs->transnoentities('RecordDeleted')) . '\', "success");
                }
            }
        });
}

/* Protection comment inline save */
document.addEventListener("DOMContentLoaded", function() {
    // Remove data-confirm from Saturne media delete buttons to avoid native popups
    setTimeout(function() {
        if (typeof $ !== "undefined") {
            $("#saturne-btn-delete-photo").removeAttr("data-confirm");
            $(".saturne-delete-media-icon, .saturne-file-delete").removeAttr("data-confirm");
        }
    }, 1000);

    document.querySelectorAll(".dr-prot-comment").forEach(function(el) {
        el.addEventListener("keydown", function(e) {
            if (e.key === "Enter") { e.preventDefault(); el.blur(); }
            if (e.key === "Escape") { el.blur(); }
        });
        el.addEventListener("blur", function() {
            var riskId   = el.dataset.riskId;
            var protIdx  = el.dataset.protIndex;
            var comment  = el.innerText.trim();
            var container = el.closest(".dr-prot-inline");
            if (!container) return;
            var url = container.dataset.ajaxUrl + "&action=ajax_save_prot_comment";
            var fd = new FormData();
            fd.append("risk_id", riskId);
            fd.append("prot_index", protIdx);
            fd.append("comment", comment);
            fetch(url, { method: "POST", body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (data.ok) {
                        el.classList.remove("dr-saved-flash");
                        void el.offsetWidth;
                        el.classList.add("dr-saved-flash");
                        // Update date in the same row
                        var row = el.closest(".dr-protection-row");
                        if (row && data.date) {
                            var dateSpan = row.querySelector(".dr-risk-date");
                            if (dateSpan) { dateSpan.innerHTML = "<i class=\"fas fa-clock\" style=\"margin-right:3px;font-size:.8em;\"></i>" + data.date; }
                        }
                    }
                });
        });
    });
});

function drCancelNewRisk() {
    document.getElementById("dr-new-form-wrap").style.display = "none";
    document.getElementById("dr-new-cat-input").value = "";
    document.getElementById("dr-new-header").innerHTML = "<span class=\"dr-risk-name\" style=\"color:#1a5fa8;\"></span>";
    document.getElementById("dr-new-form").reset();
}
</script>';

print '</div>'; // fichecenter
print dol_get_fiche_end();

// Photo editor modal (required by saturne_render_media_block)
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/core/tpl/medias/photo_editor_modal.tpl.php';

// Fix for hidden preview icon (loupe) in Saturne theme or specific Dolibarr configurations
print '
<style>
/* Force display of the preview icon next to documents */
a.pictopreview {
    display: inline-block !important;
    margin-left: 8px !important;
    text-decoration: none !important;
    vertical-align: middle;
}
a.pictopreview span.fa-search-plus, a.pictopreview span.fas {
    font-family: "Font Awesome 5 Free", "Font Awesome 6 Free", "FontAwesome" !important;
    font-weight: 900 !important;
    visibility: visible !important;
    opacity: 1 !important;
    font-size: 1.1em;
}
a.pictopreview:hover {
    text-decoration: none !important;
}
</style>
<script>
$(document).ready(function() {
    // Fallback if fa-search-plus is not supported by the loaded FA version
    $(".pictopreview span.fa-search-plus").each(function() {
        if ($(this).css("content") === "none" || $(this).width() < 5) {
            $(this).removeClass("fa-search-plus").addClass("fa-search");
        }
    });
});
</script>
';

llxFooter();

// â”€â”€ Helper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function _dr_print_protection_row(int $idx, $pos, string $comment, array $protCatByPos, Translate $langs): void
{
    $pCat  = $protCatByPos[$pos] ?? null;
    $thumb = $pCat ? (DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $pCat['name_thumbnail']) : '';
    $name  = $pCat ? $pCat['name'] : '';
    print '<div class="dr-protection-row">';
    print '<input type="hidden" name="protection_position[]" value="' . (int) $pos . '">';
    if ($thumb) {
        print '<img src="' . dol_escape_htmltag($thumb) . '" style="width:28px;height:28px;object-fit:contain;" alt="">';
        print '<span style="flex:0 0 auto;font-size:.88em;max-width:160px;">' . dol_escape_htmltag($name) . '</span>';
    }
    print '<input type="text" name="protection_comment[]" value="' . dol_escape_htmltag($comment) . '" placeholder="' . $langs->trans('Comment') . '" style="flex:1">';
    print '<button type="button" onclick="this.parentNode.remove()" style="border:none;background:#b72020;color:#fff;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:14px;line-height:1;">&#x2715;</button>';
    print '</div>';
}
