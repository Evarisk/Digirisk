<?php
/**
 * \file    custom/digiriskdolibarr/view/product/product_digirisk.php
 * \ingroup digiriskdolibarr
 * \brief   Tab DigiRisk for Product - extrafields WYSIWYG + product risk blocks
 */

require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../../class/riskanalysis/productrisk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';

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

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);
$object->fetch_optionals();

$productRiskObj = new ProductRisk($db);

$permissiontoadd = $user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer');

// â”€â”€ Danger categories (from DigiRisk JSON) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$dangerCategories = Risk::getDangerCategories('risk');
$dangerCatByPos   = [];
foreach ($dangerCategories as $cat) {
    $dangerCatByPos[(int) $cat['position']] = $cat;
}

$pictoBaseUrl = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/';

// â”€â”€ Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

// Save one extrafield (WYSIWYG section)
$editfield = GETPOST('attribute', 'aZ09');
if ($action == 'update_digirisk' && $permissiontoadd && !empty($editfield)) {
    $object->oldcopy = dol_clone($object, 2);
    $object->array_options['options_' . $editfield] = GETPOST('options_' . $editfield, 'restricthtml');
    $result = $object->updateExtraField($editfield, '', $user);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
        $action    = 'view';
        $editfield = '';
    }
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
    } else {
        // Handle photo upload if any
        if (!empty($_FILES['risk_photo']['name'])) {
            $photoDir = $newRisk->getPhotoDir();
            dol_mkdir($photoDir);
            dol_add_file_process($photoDir, 0, 1, 'risk_photo', '', null, '', 0);
        }
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
    }
    $action = 'view';
}

// Update a product risk (description + protections)
if ($action == 'update_product_risk' && $permissiontoadd) {
    $riskId     = GETPOSTINT('risk_id');
    $editedRisk = new ProductRisk($db);
    if ($editedRisk->fetch($riskId) > 0 && $editedRisk->fk_product == $object->id) {
        $editedRisk->description      = GETPOST('risk_description', 'restricthtml');

        // Build protections from POST
        $protPos  = GETPOST('protection_position', 'array');
        $protCom  = GETPOST('protection_comment', 'array');
        $prots    = [];
        if (is_array($protPos)) {
            foreach ($protPos as $k => $pos) {
                if ($pos !== '') {
                    $prots[] = [
                        'position' => (int) $pos,
                        'comment'  => isset($protCom[$k]) ? dol_htmlcleanlastchars($protCom[$k]) : '',
                    ];
                }
            }
        }
        $editedRisk->protections_json = json_encode($prots, JSON_UNESCAPED_UNICODE);
        $editedRisk->update($user);
    }
    $action = 'view';
}

// Delete a product risk
if ($action == 'delete_product_risk' && $permissiontoadd) {
    $riskId     = GETPOSTINT('risk_id');
    $delRisk    = new ProductRisk($db);
    if ($delRisk->fetch($riskId) > 0 && $delRisk->fk_product == $object->id) {
        $delRisk->delete($user);
        setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
    }
    $action = 'view';
}

// Upload photo on an existing risk
if ($action == 'upload_risk_photo' && $permissiontoadd) {
    $riskId   = GETPOSTINT('risk_id');
    $upRisk   = new ProductRisk($db);
    if ($upRisk->fetch($riskId) > 0 && $upRisk->fk_product == $object->id) {
        $photoDir = $upRisk->getPhotoDir();
        dol_mkdir($photoDir);
        dol_add_file_process($photoDir, 0, 1, 'risk_photo', '', null, '', 0);
        setEventMessages($langs->trans('FileSuccessfullyAdded'), null, 'mesgs');
    }
    $action = 'view';
}

// Delete a photo from a risk
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

// â”€â”€ Page output â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$form  = new Form($db);
$title = $langs->trans('Product') . ' - DigiRisk';
llxHeader('', $title, '');

$head  = product_prepare_head($object);
$titre = $langs->trans('CardProduct' . $object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

print dol_get_fiche_head($head, 'digirisk', $titre, -1, $picto);

$linkback = '<a href="' . DOL_URL_ROOT . '/product/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';
dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '', '', 0, '', '', 1);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$digirisk_sections = [
    'digirisk_identification' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_LABEL : $langs->transnoentities('DigiriskIdentification')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_ICON : 'fas fa-tag',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_IDENTIFICATION_COLOR : '#c07500',
		'bg' => '#fffbf0'
	],
    'digirisk_security' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_LABEL : $langs->transnoentities('DigiriskSecurity')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_ICON : 'fas fa-shield-alt',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_SECURITY_COLOR : '#b72020',
		'bg' => '#fff8f8'
	],
    'digirisk_usermanual' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_LABEL : $langs->transnoentities('DigiriskUserManual')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_ICON : 'fas fa-book',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_USERMANUAL_COLOR : '#1a7a3c',
		'bg' => '#f4fff8'
	],
    'digirisk_qualification' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_LABEL : $langs->transnoentities('DigiriskQualification')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_ICON : 'fas fa-graduation-cap',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_QUALIFICATION_COLOR : '#1a5fa8',
		'bg' => '#f4f8ff'
	],
    'digirisk_hygiene' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_LABEL : $langs->transnoentities('DigiriskHygiene')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_ICON : 'fas fa-broom',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_HYGIENE_COLOR : '#0e7e7e',
		'bg' => '#f4ffff'
	],
    'digirisk_maintenance' => [
		'label' => mb_strtoupper(!empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_LABEL) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_LABEL : $langs->transnoentities('DigiriskMaintenance')),
		'icon' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_ICON) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_ICON : 'fas fa-wrench',
		'color' => !empty($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_COLOR) ? $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_MAINTENANCE_COLOR : '#8b4000',
		'bg' => '#fff8f2'
	],
];

$currentEditField = ($action == 'edit_extras' && $permissiontoadd) ? $editfield : '';

print '<style>
.digirisk-section { border: 1px solid #ddd; border-radius: 6px; margin-bottom: 18px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.digirisk-section-header { display:flex; align-items:center; justify-content:space-between; padding:9px 14px; border-bottom-width:3px; border-bottom-style:solid; font-size:.95em; font-weight:bold; }
.digirisk-section-title { display:flex; align-items:center; gap:8px; }
.digirisk-section-body { padding:12px 15px; min-height:40px; font-size:.92em; line-height:1.5; }
.digirisk-section-body.empty { color:#aaa; font-style:italic; cursor:pointer; }
.digirisk-edit-icon { opacity:.5; transition:opacity .2s; }
.digirisk-edit-icon:hover { opacity:1; }

/* Risk block */
.dr-risk-block { border:1px solid #ddd; border-radius:6px; margin-bottom:14px; overflow:hidden; }
.dr-risk-header { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#f7f7f7; border-bottom:1px solid #e0e0e0; font-weight:bold; }
.dr-risk-header img { width:28px; height:28px; object-fit:contain; }
.dr-risk-header .dr-risk-name { flex:1; font-size:.93em; }
.dr-risk-body { padding:10px 12px; }
.dr-risk-desc { width:100%; min-height:60px; border:1px solid #ccc; border-radius:4px; padding:6px 8px; font-size:.9em; resize:vertical; }
.dr-risk-actions { display:flex; gap:6px; align-items:flex-start; margin-top:6px; }
.dr-risk-photos { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.dr-risk-photo-thumb { position:relative; width:70px; height:70px; border-radius:4px; overflow:hidden; border:1px solid #ddd; }
.dr-risk-photo-thumb img { width:100%; height:100%; object-fit:cover; }
.dr-risk-photo-del { position:absolute; top:2px; right:2px; background:rgba(180,0,0,.8); color:#fff; border:none; border-radius:50%; width:18px; height:18px; font-size:10px; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
.dr-protection-row { display:flex; gap:6px; align-items:center; margin-top:6px; }
.dr-protection-row select { flex:0 0 220px; }
.dr-protection-row input[type=text] { flex:1; }
.dr-add-protection { margin-top:4px; font-size:.85em; cursor:pointer; color:#1a5fa8; background:none; border:none; padding:0; }
.dr-btn { border:none; border-radius:4px; cursor:pointer; padding:6px 10px; font-size:.85em; color:#fff; }
.dr-btn-orange { background:#e68a00; }
.dr-btn-green  { background:#1a7a3c; }
.dr-btn-red    { background:#b72020; }
.dr-btn-blue   { background:#1a5fa8; }
</style>';

// ── 1. WYSIWYG extrafields ──────────────────────────────────────
foreach ($digirisk_sections as $fieldkey => $section) {
    $val        = $object->array_options['options_' . $fieldkey] ?? '';
    if (empty(trim(strip_tags(str_replace('&nbsp;', ' ', $val))))) {
        $defaultKey = 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . strtoupper(str_replace('digirisk_', '', $fieldkey)) . '_DESC';
        $val = $conf->global->$defaultKey ?? '';
    }
    $color      = $section['color'];
    $bg         = $section['bg'];
    $icon       = $section['icon'];
    $label      = $section['label'];
    $isThisEdit = ($currentEditField === $fieldkey);
    $pencilUrl  = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit_extras&attribute=' . urlencode($fieldkey) . '&token=' . currentToken();

    $keyStr = strtoupper(str_replace('digirisk_', '', $fieldkey));
    $useSvg = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $keyStr . '_USE_SVG'} ?? 0;
    $svgPath = $conf->digiriskdolibarr->dir_output . '/icons/digirisk_' . strtolower($keyStr) . '_icon.svg';
    $hasSvg = file_exists($svgPath);
    
    if ($useSvg && $hasSvg) {
        $svgContent = file_get_contents($svgPath);
        $svgContent = preg_replace('/<svg([^>]*)>/i', '<svg$1 fill="' . dol_escape_htmltag($color) . '" style="width: 1.25em; height: 1.25em; vertical-align: -0.125em;">', $svgContent);
        $svgContent = preg_replace('/fill="[^"]*"/i', 'fill="' . dol_escape_htmltag($color) . '"', $svgContent);
        $iconHtml = '<span style="display:inline-block; margin-right:8px; vertical-align:middle;">' . $svgContent . '</span>';
    } else {
        $iconHtml = '<i class="' . dol_escape_htmltag($icon) . '"></i>&nbsp;';
    }

    print '<div class="digirisk-section">';
    print '<div class="digirisk-section-header" style="border-bottom-color:' . $color . '; background:' . $bg . ';">';
    print '<span class="digirisk-section-title" style="color:' . $color . ';">' . $iconHtml . $label . '</span>';
    if (!$isThisEdit && $permissiontoadd) {
        print '<a class="digirisk-edit-icon" href="' . $pencilUrl . '" title="' . $langs->trans('Modify') . '">' . img_edit() . '</a>';
    }
    print '</div>';

    if ($isThisEdit) {
        print '<div class="digirisk-section-body" style="background:' . $bg . ';">';
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . currentToken() . '">';
        print '<input type="hidden" name="action" value="update_digirisk">';
        print '<input type="hidden" name="attribute" value="' . dol_escape_htmltag($fieldkey) . '">';
        $extrafields->attributes[$object->table_element]['list'][$fieldkey] = '1'; // Force visibility for this form
        print $extrafields->showInputField($fieldkey, $val, '', '', '', 'centpercent', $object->id, $object->table_element);
        print '<div class="center" style="padding:10px 0 5px;">';
        print '<input type="submit" class="button button-save" value="' . $langs->trans('Save') . '">';
        print ' &nbsp; <a class="button button-cancel" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">' . $langs->trans('Cancel') . '</a>';
        print '</div></form></div>';
    } else {
        if (!empty($val)) {
            print '<div class="digirisk-section-body" style="background:' . $bg . ';">' . $val . '</div>';
        } else {
            print '<div class="digirisk-section-body empty" style="background:' . $bg . ';" onclick="window.location=\'' . $pencilUrl . '\'">' . $langs->trans('ClickToAddContent') . '</div>';
        }
    }
    print '</div>';
}


print '</div>'; // fichecenter
print dol_get_fiche_end();


llxFooter();
$db->close();
