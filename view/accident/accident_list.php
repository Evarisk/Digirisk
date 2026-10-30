<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    view/accident/accident_list.php
 * \ingroup digiriskdolibarr
 * \brief   List page for accident
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
if (isModEnabled('categorie')) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'companies', 'commercial']);

// Get parameters
$action     = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');                              // The bulk action (combo box choice into lists)
$fromId     = GETPOSTINT('fromid');                                         // The list is the Accidents tab of this GP/UT
$fromIdUser = GETPOSTINT('fromiduser');                                     // The list is the Accidents tab of this user

// Get list parameters
$toselect                                   = [];
[$confirm, $contextpage, $optioncss, $mode] = ['', '', '', ''];
$listParameters                             = saturne_load_list_parameters(basename(dirname(__FILE__)));
foreach ($listParameters as $listParameterKey => $listParameter) {
    $$listParameterKey = $listParameter;
}

// Get pagination parameters
[$limit, $page, $offset] = [0, 0, 0];
[$sortfield, $sortorder] = ['', ''];
$paginationParameters    = saturne_load_pagination_parameters();
foreach ($paginationParameters as $paginationParameterKey => $paginationParameter) {
    $$paginationParameterKey = $paginationParameter;
}

// Initialize technical objects
$object      = new Accident($db);
$extrafields = new ExtraFields($db);
if (isModEnabled('categorie')) {
    $categorie = new Categorie($db);
}

// Initialize view objects
$form = new Form($db);

// digiriskelementview loads the hooks of the module on this page: they render the computed columns and the
// filters of the list, and give its photo to the banner of the Accidents tab of a GP/UT
$hookmanager->initHooks(['digiriskelementview', $contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (isModEnabled('categorie')) {
    $searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
}

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    $sortfield = 't.date_creation'; // Set here default search field. By default, date_creation
}
if (!$sortorder) {
    $sortorder = 'DESC';
}

// Definition of the custom columns. They have no column of their own in the accident table: their rendering is
// provided by the hooks of the module (actions_digiriskdolibarr). Each one is listed in $excludeFields so the
// generic list neither selects nor filters a t.<key>
$object->fields['victim']   = ['label' => 'Victim', 'enabled' => 1, 'position' => 146, 'visible' => 2, 'noedit' => 1, 'disablesort' => 1, 'disablesearch' => 1, 'csslist' => 'nowraponall'];
$object->fields['progress'] = ['label' => 'Progress', 'enabled' => 1, 'position' => 147, 'visible' => 2, 'noedit' => 1, 'disablesort' => 1, 'disablesearch' => 1, 'csslist' => 'center minwidth100'];
$excludeFields              = ['victim', 'progress'];

// The type, the location and the date of the accident are rendered by the hooks of the module too: the location is a
// GP/UT, a third party or a free text depending on the kind of accident. Its filter picks a GP/UT
$object->fields['fk_element']['type']      = 'integer:DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php';
$object->fields['fk_element']['noedit']    = 1;
$object->fields['accident_type']['noedit'] = 1;
$object->fields['accident_date']['noedit'] = 1;
$object->fields['description']['csslist']  = 'minwidth300';

// The card does not edit the creation date, the list must not either
$object->fields['date_creation']['noedit'] = 1;

// A person or a ticket reference cut over two lines reads badly
$object->fields['fk_user_employer']['csslist'] = 'nowraponall';
$object->fields['fk_ticket']['csslist']        = 'nowraponall';

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
    }
}

// Criteria carried by no field of the accident: the work stops and the register are read on other tables by the
// printFieldListWhere hook. Kept in $search so the list carries them over when sorted or paged
foreach (['workstop', 'register'] as $key) {
    if (GETPOST('search_' . $key, 'aZ09') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'aZ09');
    }
}

// List of fields to search into when doing a "search in all"
$fieldsToSearchAll = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchall'])) {
        $fieldsToSearchAll['t.' . $key] = $val['label'];
    }
}

// Definition of array of fields for columns
foreach ($object->fields as $key => $val) {
    if (!empty($val['visible'])) {
        $visible = (int) dol_eval($val['visible']);
        $arrayfields['t.' . $key] = [
            'label'    => $val['label'],
            'checked'  => (($visible < 0) ? 0 : 1),
            'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
            'position' => $val['position'],
            'help'     => $val['help'] ?? '',
        ];
    }
}

// Extra fields
require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// The tabs of a GP/UT and of a user keep their id through the search, sort and paging of the list. The one of
// a GP/UT restricts the list to its accidents, through the printFieldListWhere hook so that removing the
// filters of the list does not lift it
$formMoreParams    = [];
$moreUrlParameters = '';
if ($fromId > 0) {
    $formMoreParams['fromid']             = $fromId;
    $conf->cache['accidentListElementId'] = $fromId;
} elseif ($fromIdUser > 0) {
    $formMoreParams['fromiduser'] = $fromIdUser;
}
foreach ($formMoreParams as $formMoreParamKey => $formMoreParamVal) {
    $moreUrlParameters .= '&' . $formMoreParamKey . '=' . $formMoreParamVal;
}

// Permissions
$permissiontoread   = $user->hasRight($object->module, $object->element, 'read');
$permissiontoadd    = $user->hasRight($object->module, $object->element, 'write');
$permissiontodelete = $user->hasRight($object->module, $object->element, 'delete');

// Security check
saturne_check_access($permissiontoread, $object);

// The Excel export needs the base export of Saturne
$xlsxExportAvailable = file_exists(dol_buildpath('/saturne/class/saturnexlsxexport.class.php'));

/*
 * Actions
 */

$parameters = ['arrayfields' => &$arrayfields];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    // Selection of new fields
    require_once DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
        foreach ($object->fields as $key => $val) {
            $search[$key] = '';
            if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
                $search[$key . '_dtstart'] = '';
                $search[$key . '_dtend']   = '';
            }
        }
        $search['workstop']   = '';
        $search['register']   = '';
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
        $searchCategories     = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass = 'Accident';
    $objectlabel = 'Accident';
    $uploaddir   = $conf->digiriskdolibarr->multidir_output[$conf->entity];

    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once dol_buildpath('/saturne/core/tpl/actions/list_massactions.tpl.php');

    // Excel export of every accident matching the filters, the way the list sorts them
    if ($action == 'export_xlsx' && $xlsxExportAvailable) {
        require_once __DIR__ . '/../../class/digiriskaccidentexport.class.php';

        // The query of the list without its paging, run by the template into $resql
        $limit  = 0;
        $page   = 0;
        $offset = 0;
        require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php');

        $accidentIds = [];
        if (isset($resql) && $resql) {
            while ($obj = $db->fetch_object($resql)) {
                $accidentIds[] = (int) $obj->rowid;
            }
        }

        $export = new DigiriskAccidentExport($db);
        if ($searchAll) {
            $export->filters[$langs->transnoentities('XlsxSearch')] = $searchAll;
        }
        $export->addFieldFilters($object, $search);
        $export->addExtraFieldFilters($extrafields, $object->table_element, $search_array_options);

        // Criteria the list reads on other tables than the accident one
        $workStopFilterLabels = [Accident::ANY_WORK_STOP_FILTER => 'AccidentWithDIAT', Accident::NO_WORK_STOP_FILTER => 'AccidentWithoutDIAT'];
        foreach (array_keys(Accident::WORK_STOP_DURATION_LIMITS) as $workStopBucket) {
            $workStopFilterLabels[$workStopBucket] = ucfirst($workStopBucket);
        }
        $registerFilterLabels = [Accident::WITH_REGISTER_FILTER => 'AccidentWithRegister', Accident::WITHOUT_REGISTER_FILTER => 'AccidentWithoutRegister'];
        if (isset($workStopFilterLabels[$search['workstop'] ?? ''])) {
            $export->filters[$langs->transnoentities('WorkStopDuration')] = $langs->transnoentities($workStopFilterLabels[$search['workstop']]);
        }
        if (isset($registerFilterLabels[$search['register'] ?? ''])) {
            $export->filters[$langs->transnoentities('AccidentRegister')] = $langs->transnoentities($registerFilterLabels[$search['register']]);
        }
        if ($fromId > 0) {
            $digiriskElement = new DigiriskElement($db);
            if ($digiriskElement->fetch($fromId) > 0) {
                $export->filters[$langs->transnoentities('AccidentLocation')] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
            }
        } elseif ($fromIdUser > 0) {
            $userObject = new User($db);
            if ($userObject->fetch($fromIdUser) > 0) {
                $export->filters[$langs->transnoentities('User')] = $userObject->getFullName($langs);
            }
        }
        $categoryLabels = [];
        foreach ($searchCategories ?? [] as $searchCategory) {
            $category = new Categorie($db);
            if ((int) ltrim((string) $searchCategory, '+-') > 0 && $category->fetch((int) ltrim((string) $searchCategory, '+-')) > 0) {
                $categoryLabels[] = (strpos((string) $searchCategory, '-') === 0 ? '- ' : '') . $category->label;
            }
        }
        if (!empty($categoryLabels)) {
            $export->filters[$langs->transnoentities('Categories')] = implode(', ', $categoryLabels);
        }

        $export->export($accidentIds, $langs->transnoentities('AccidentXlsxTitle') . '_' . dol_print_date(dol_now(), 'dayhourlog'));
        $db->close();
        exit;
    }
}

/*
 * View
 */

$title   = $langs->trans('AccidentList');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($fromId > 0) {
    // digirisk-horizontal-scroll bounds #id-right to the viewport so the wide accident list table
    // scrolls inside its own .div-table-responsive instead of widening the whole page
    digirisk_header($title, $helpUrl, [], [], '', 'digirisk-horizontal-scroll');

    $digiriskElement = new DigiriskElement($db);
    $digiriskElement->fetch($fromId);
    saturne_get_fiche_head($digiriskElement, 'elementAccidents', $langs->trans('Accident'));

    [$moreHtmlRef, $moreParams] = $digiriskElement->getBannerTabContent();
    saturne_banner_tab($digiriskElement, 'fromid', 'none', 0, 'rowid', 'ref', $moreHtmlRef, dol_strlen($digiriskElement->photo) > 0, $moreParams);
} else {
    // digirisk-horizontal-scroll: the list table scrolls inside its own .div-table-responsive rather than widening the page
    saturne_header(0, '', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist digirisk-horizontal-scroll');

    if ($fromIdUser > 0) {
        $userObject = new User($db);
        $userObject->fetch($fromIdUser, '', '', 1);
        $userObject->loadRights();
        saturne_get_fiche_head($userObject, 'accidents', $langs->trans('Accidents'));

        $linkBack = '<a href="' . DOL_URL_ROOT . '/user/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';
        dol_banner_tab($userObject, 'fromiduser', $linkBack, $user->hasRight('user', 'user', 'lire') || $user->admin);
        print '<div class="underbanner clearboth"></div>';
    }
}

// Excel export of the list, with its filters and its sort
if ($xlsxExportAvailable) {
    dol_include_once('/saturne/class/saturnexlsxexport.class.php');
    $newCardButton = SaturneXlsxExport::getListExportButton($formMoreParams);
}

require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php');
require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_header.tpl.php');
require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_search_input.tpl.php');
require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_search_title.tpl.php');
require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_loop_object.tpl.php');
require_once dol_buildpath('/saturne/core/tpl/list/objectfields_list_footer.tpl.php');

// End of page
llxFooter();
$db->close();
