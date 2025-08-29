<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 */

/**
 *   	\file       view/digiriskelement/digiriskelement_digiai.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiai
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

saturne_load_langs(['other']);

$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiaicard';
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$fromid      = GETPOST('fromid', 'int');
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;

$object           = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$riskAssessment   = new RiskAssessment($db);
$extrafields      = new ExtraFields($db);
$usertmp          = new User($db);
$project          = new Project($db);

$hookmanager->initHooks(array('digiaicard', 'digiriskelementview', 'globalcard'));

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php';

//Permission for digiriskelement_digiai
$permissiontoread   = $user->rights->digiriskdolibarr->digiai->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiai->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiai->delete;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $digiai, $action); // Note that $action and $digiai may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // Selection of new fields
    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($digiai->fields as $key => $val) {
            $search[$key] = '';
        }
        $toselect             = '';
        $search_array_options = array();
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = '';
    }

    $error = 0;

    $backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_digiai.php', 1) . (empty($fromid) ? '?id=' . ($id > 0 ? $id : '__ID__') : '?fromid=' . ($fromid > 0 ? $fromid : '__ID__'));
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("DigiAI");
$helpUrl  = 'FR:Module_Digirisk#.C3.89valuateurs';

digirisk_header($title, $helpUrl);

print '<div id="cardContent" value="">';

if ($object->id > 0) {
    $res = $object->fetch_optionals();

    saturne_get_fiche_head($object, 'elementDigiAI', $title);

    // Object card
    // ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

    if ($fromid) {
        print '<div class="underbanner clearboth"></div>';
    }

    print '<div class="fichecenter digiailist wpeo-wrap">';

    print '<div class="digiAI-tabs-container">';
    print '<div class="digiAI-tabs">';
    print '<button class="digiAI-tab-button active" data-tab="text-tab"><i class="fas fa-keyboard"></i>'. $langs->trans('TextAnalysis') .'</button>';
    print '<button class="digiAI-tab-button" data-tab="image-tab"><i class="fas fa-image"></i>'. $langs->trans('ImageAnalysis') .'</button>';
    print '</div>';

    print '<div id="text-tab" class="digiAI-tab-content active">';
    print '<div class="digiAI-text-container-full">';
    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?id='. $id .'" name="analyze_text_form" id="analyze_text_form" class="digiAI-form">' . "\n";
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="analyze_text">';
    print '<input type="hidden" name="id" value="' . $id . '">';

    print '<div class="digiAI-text-area-container">';
    print '<label for="analysis_text"><i class="fas fa-keyboard"></i> ' . $langs->trans('EnterTextForAnalysis') . '</label>';
    print '<textarea name="analysis_text" id="analysis_text" placeholder="Décrivez la situation de travail, les équipements, les procédures ou tout autre élément que vous souhaitez analyser..." rows="8" class="digiAI-textarea"></textarea>';
    print '</div>';

    print '<div class="digiAI-text-submit">';
    print '<button type="submit" class="digiAI-analyze-button" id="analyze_text_button">';
    print $langs->trans('AnalyzeText');
    print '</button>';
    print '</div>';

    print '</form>';
    print '</div>';
    print '</div>';

    print '<div id="image-tab" class="digiAI-tab-content">';
    print '<div class="digiAI-upload-container-full">';
    print '<form method="POST" enctype="multipart/form-data" action="' . $_SERVER["PHP_SELF"] . '?id='. $id .'" name="upload_image_form" id="upload_image_form" class="digiAI-form">' . "\n";
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="upload_image">';
    print '<input type="hidden" name="id" id="digiriskElementId" value="' . $id . '">';

    print '<div class="digiAI-dropzone modal-open open-analyze-image-modal" id="dropzone">';
    print '<div class="modal-options" data-modal-to-open="media_gallery" data-from-id="' . $id . '" data-from-type="digiAi" data-from-subtype="photo" data-from-subdir="photos" data-from-module="digiriskdolibarr"></div>';
    $pathToECMImg = $conf->ecm->multidir_output[$conf->entity] . '/'. $moduleNameLowerCase .'/medias';
    print '<input hidden class="modal-file-path" value="'. $pathToECMImg.'">';
    print '<i class="fas fa-images"></i>';
    print '<p>' . $langs->trans('ChooseAnImage') . '</p>';
    print '<input type="file" name="image_file" id="image_file" accept="image/*" class="digiAI-input-file" style="display: none;">';
    print '</div>';

    print '</form>';
    print '</div>';
    print '</div>';

    print '</div>';

    print '<div class="wpeo-modal" id="digiai_modal" >';
    print '  <div class="modal-container"style="max-width: 65%; max-height: 65%">';
    print '    <div class="modal-header">';
    print '      <h2><i class="fas fa-robot"></i> '. $langs->trans('AIRiskAnalysis') .'</h2>';
    print '      <button class="modal-close" onclick="$(\'#digiai_modal\').removeClass(\'modal-active\')">&times;</button>';
    print '    </div>';

    print '    <div class="modal-analyse-phase">';
    print '      <div class="modal-body">';
    print '        <div class="modal-left">';
    print '          <img id="uploaded-image-preview" style="display: none;" />';
    print '          <div id="analyzed-text-preview" style="display: none;">';
    print '            <h4><i class="fas fa-file-text"></i> Texte analysé :</h4>';
    print '            <div class="text-preview-content"></div>';
    print '          </div>';
    print '        </div>';
    print '        <div class="modal-right">';
    print '          <div class="analysis-in-progress">';
    print '            <p class="digiai-loader-text">'. $langs->trans('AnalysisInProgress') .'</p>';
    print '            <div class="loader"></div>';
    print '          </div>';
    print '        </div>';
    print '      </div>';
    print '    </div>';

    print '    <div class="modal-result-phase" style="display: none;">';
    print '      <div class="modal-result-wrapper">';
    print '        <table id="risque_table" class="risque-table">';
    print '          <thead>';
    print '            <tr>';
    print '                <th>Action</th>';
    print '                <th>Image</th>';
    print '                <th style="width: 300px; text-align: center;">Cotation</th>';
    print '                <th>Description du Risque</th>';
    print '                <th>Actions de Prévention</th>';
    print '            </tr>';
    print '          </thead>';
    print '          <tbody></tbody>';
    print '        </table>';
    print '        <div class="text-right" style="margin-top: 20px; display: flex; justify-content: flex-end;">';
    print '          <button id="submit_selected_risks" class="wpeo-button" disabled style="opacity: 0.6; ">Ajouter les risques sélectionnés</button>';
    print '        </div>';
    print '      </div>';
    print '        <table class="risque-table">';
    print '        <h4 style="margin-bottom: 12px;"><i class="fas fa-history"></i> Risques précédemment ajoutés</h4>';
    print '         <tbody class="previous-risks-list"></tbody></table>';
    print '      </div>';

    print '    </div>';

    print '  </div>';
    print '</div>';

    print '<input hidden id="dol_url_root" value="'. DOL_URL_ROOT .'">';
    print dol_get_fiche_end();
    $json = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/dangerCategories.json');

    // Inclusion de la bibliothèque de médias Saturne
    $moduleNameLowerCase = 'digiriskdolibarr';
    $moduleName = 'DigiriskDolibarr';
    $moduleNameUpperCase = 'DIGIRISKDOLIBARR';
    if (file_exists(DOL_DOCUMENT_ROOT . '/custom/saturne/core/tpl/medias/medias_gallery_modal.tpl.php')) {
        include DOL_DOCUMENT_ROOT . '/custom/saturne/core/tpl/medias/medias_gallery_modal.tpl.php';
    }

    ?>
    <script>
        window.digiriskdolibarr = window.digiriskdolibarr || {};
        window.digiriskdolibarr.categoryMap = <?php echo $json; ?>;
    </script>
<?php
}

print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
