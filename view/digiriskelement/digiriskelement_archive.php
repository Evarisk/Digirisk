<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 *  \file       view/digiriskelement/digiriskelement_archive.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Archive tab of a digirisk element: archived risks and archived sub elements
 *
 *  The archived risks are shown with the very same list as the risk tab, restricted to the
 *  archived status, so the page repeats the list setup of digiriskelement_risk.php.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $hookmanager, $langs, $user;

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/riskanalysis/riskassessment/mod_riskassessment_standard.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id          = GETPOSTINT('id');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'archivedrisklist';
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$riskType    = GETPOSTISSET('risk_type') ? GETPOST('risk_type') : 'risk';
$elementID   = GETPOSTINT('element_id');
// Level of the cotation scale the last assessment of the risk must fall in, 0 for all of them
$searchCotation = GETPOSTINT('search_cotation');
$page           = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');
$page           = is_numeric($page) ? $page : 0;
$page           = $page == -1 ? 0 : $page;
if (isModEnabled('categorie')) {
    $search_category_array = GETPOST('search_category_risk_list', 'array');
}

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskelement  = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$risk             = new Risk($db);
$evaluation       = new RiskAssessment($db);
$ecmdir           = new EcmDirectory($db);
$project          = new Project($db);
$task             = new SaturneTask($db);
$extrafields      = new ExtraFields($db);
$DUProject        = new Project($db);

$numberingModuleName = [
    'riskanalysis/' . $risk->element       => $conf->global->DIGIRISKDOLIBARR_RISK_ADDON,
    'riskanalysis/' . $evaluation->element => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON,
    $project->element                      => $conf->global->PROJECT_ADDON,
    'project/task'                         => $conf->global->PROJECT_TASK_ADDON,
];

list($refRiskMod, $refEvaluationMod, $refProjectMod, $refTaskMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$DUProject->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
$hookmanager->initHooks(['digiriskelementarchive', 'digiriskelementview', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risk->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risk->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? 'evaluation.cotation' : 'r.' . key($risk->fields);
if (!$sortorder) $sortorder = $conf->global->DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION ? 'DESC' : 'ASC';
if (!isset($evalsortfield) || !$evalsortfield) $evalsortfield = 'evaluation.' . key($evaluation->fields);

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search     = [];
foreach ($risk->fields as $key => $val) {
    $search[$key] = (GETPOST('search_' . $key, 'alpha') !== '') ? GETPOST('search_' . $key, 'alpha') : '';
}
// The whole point of the tab: only the archived risks of the element show up here
$search['status'] = Risk::STATUS_ARCHIVED;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = [];
foreach ($risk->fields as $key => $val) {
    if (!empty($val['searchall'])) $fieldstosearchall['r.' . $key] = $val['label'];
}
// A risk is also brought back by the ref of one of its assessments, see Risk::getSearchAllSqlFilter()
foreach ($evaluation->fields as $key => $val) {
    if (!empty($val['searchall'])) $fieldstosearchall['ra.' . $key] = 'RiskAssessment';
}

// Definition of fields for list
$arrayfields = [];
foreach ($risk->fields as $key => $val) {
    // If $val['visible']==0, then we never show the field
    if ($val['label'] == 'Entity' || $val['label'] == 'ParentElement') {
        $val['visible'] = 0;
    }
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible'], 1);
        $arrayfields['r.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'], 1)),
            'position' => $val['position'],
            'help'     => $val['help'] ?? ''
        ];
    }
}

foreach ($evaluation->fields as $key => $val) {
    // If $val['visible']==0, then we never show the field
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible'], 1);
        $arrayfields['evaluation.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'], 1)),
            'position' => $val['position'],
            'help'     => $val['help'] ?? ''
        ];
    }
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$risk->fields       = dol_sort_array($risk->fields, 'position');
$evaluation->fields = dol_sort_array($evaluation->fields, 'position');
$arrayfields        = dol_sort_array($arrayfields, 'position');

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Permissions
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;
$permissionToArchiveElement = $user->rights->digiriskdolibarr->digiriskelement->write;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $risk, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    $error = 0;

    $backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_archive.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__') . '&risk_type=' . $riskType;

    // Restore an archived sub element, with everything that was archived below it
    if ($action == 'unarchive_element' && $permissionToArchiveElement) {
        $archivedElement = new DigiriskElement($db);
        // Only a direct child of the displayed element can be restored from here
        if ($archivedElement->fetch($elementID) > 0 && $archivedElement->fk_parent == $object->id) {
            if ($archivedElement->unarchive($user) > 0) {
                setEventMessages($langs->trans('ElementUnarchived', $archivedElement->ref), null);
            } else {
                setEventMessages($archivedElement->error, $archivedElement->errors, 'errors');
            }
        } else {
            setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
        }

        header('Location: ' . str_replace('__ID__', $id, $backtopage));
        exit;
    }

    // Actions on the risks of the list, unarchive mass action included
    require_once __DIR__ . '/../../core/tpl/riskanalysis/risk/digiriskdolibarr_risk_actions.tpl.php';
}

/*
 * View
 */

$form    = new Form($db);
$title   = $langs->trans('Archives');
$helpUrl = 'FR:Module_Digirisk#.C3.89valuation_des_Risques';

// classforhorizontalscrolloftabs constrains #id-right width so the wide risk list table
// scrolls inside its own .div-table-responsive instead of widening the whole page
digirisk_header($title, $helpUrl, [], [], '', 'classforhorizontalscrolloftabs');

$onPhone = $conf->browser->layout == 'phone' ? 1 : 0;

if ($object->id > 0) {
    print '<div id="cardContent" value="">';

    $res = $object->fetch_optionals();

    saturne_get_fiche_head($object, 'elementArchive', $title);

    // Object card
    // ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

    require_once __DIR__ . '/../../core/tpl/digiriskelement/digiriskelement_archive_view.tpl.php';

    print '</div>' . "\n";
    print '<!-- End div class="cardcontent" -->';
}

// End of page
llxFooter();
$db->close();
