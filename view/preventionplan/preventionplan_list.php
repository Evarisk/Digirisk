<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    view/preventionplan/preventionplan_list.php
 * \ingroup digiriskdolibarr
 * \brief   List page for prevention plan
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/preventionplan.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'companies', 'commercial']);

// Get parameters
$action     = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');

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
$object      = new PreventionPlan($db);
$extrafields = new ExtraFields($db);

// Initialize view objects
$form = new Form($db);

$hookmanager->initHooks([$contextpage]); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
    $sortfield = 't.ref';
}
if (!$sortorder) {
    $sortorder = 'ASC';
}

// Enable multi-select for status field and use short labels (matching LibStatut short)
$object->fields['status']['searchmulti']  = 1;
$object->fields['status']['arrayofkeyval'] = [
    PreventionPlan::STATUS_DRAFT     => 'Draft',
    PreventionPlan::STATUS_VALIDATED => 'Enabled',
    PreventionPlan::STATUS_LOCKED    => 'Locked',
    PreventionPlan::STATUS_ARCHIVED  => 'Archived',
];

// Fields excluded from SQL SELECT (virtual/computed columns)
$excludeFields = ['MasterWorker', 'ExtSociety', 'ExtSocietyResponsible', 'ExtSocietyAttendant'];

// Add custom display-only fields
$object->fields['MasterWorker']          = ['label' => 'MasterWorker',          'enabled' => 1, 'position' => 190, 'visible' => 1, 'disablesort' => 1, 'csslist' => 'tdoverflowmax125', 'type' => 'varchar(255)', 'disablesearch' => 1];
$object->fields['ExtSociety']            = ['label' => 'ExtSociety',            'enabled' => 1, 'position' => 200, 'visible' => 1, 'disablesort' => 1, 'csslist' => 'tdoverflowmax125', 'type' => 'integer',      'arrayofkeyval'  => []];
$object->fields['ExtSocietyResponsible'] = ['label' => 'ExtSocietyResponsible', 'enabled' => 1, 'position' => 210, 'visible' => 1, 'disablesort' => 1, 'csslist' => 'tdoverflowmax125', 'type' => 'varchar(255)', 'disablesearch' => 1];
$object->fields['ExtSocietyAttendant']   = ['label' => 'ExtSocietyAttendant',   'enabled' => 1, 'position' => 220, 'visible' => 1, 'disablesort' => 1, 'csslist' => 'tdoverflowmax125', 'type' => 'varchar(255)', 'disablesearch' => 1];

// Populate companies list for ExtSociety filter dropdown
$tmpsql   = 'SELECT rowid, nom FROM ' . MAIN_DB_PREFIX . 'societe WHERE entity = ' . $conf->entity . ' ORDER BY nom';
$tmpresql = $db->query($tmpsql);
while ($tmpobj = $db->fetch_object($tmpresql)) {
    $object->fields['ExtSociety']['arrayofkeyval'][$tmpobj->rowid] = $tmpobj->nom;
}
$db->free($tmpresql);

// Initialize array of search criterias
$searchAll = trim(GETPOST('search_all'));
$search    = [];
foreach ($object->fields as $key => $val) {
    if (!empty($val['searchmulti'])) {
        $search[$key] = GETPOST('search_' . $key, 'array');
    } elseif (GETPOST('search_' . $key, 'alpha') !== '') {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
    if (isset($val['type']) && in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
        $search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
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
$arrayfields = [];
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

// Permissions
$permissiontoread   = $user->hasRight($object->module, 'preventionplan', 'read');
$permissiontoadd    = $user->hasRight($object->module, 'preventionplan', 'write');
$permissiontodelete = $user->hasRight($object->module, 'preventionplan', 'delete');

// Security check
saturne_check_access($permissiontoread, $object);

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
        $searchAll            = '';
        $toselect             = [];
        $search_array_options = [];
    }
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
        || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
        $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
    }

    if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
        $massaction = '';
    }

    // Mass actions
    $objectclass = 'PreventionPlan';
    $objectlabel = 'PreventionPlan';
    $uploaddir   = $conf->digiriskdolibarr->dir_output;
    require_once DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

    // Mass actions archive
    require_once __DIR__ . '/../../../saturne/core/tpl/actions/list_massactions.tpl.php';
}

/*
 * View
 */

$title   = $langs->trans('PreventionPlanList');
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Plan_de_pr.C3.A9vention';

saturne_header(0, '', $title, $helpUrl, '', 0, 0, [], [], '', 'mod-' . $object->module . '-' . $object->element . ' page-list bodyforlist');

require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';

// End of page
llxFooter();
$db->close();
