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
 * \file    view/digiriskstandard/digiriskstandard_exposuregroup.php
 * \ingroup digiriskdolibarr
 * \brief   Exposure groups of the company (#5644): the groups of people who hold the same jobs, with their headcount,
 *          then the grid that spreads each group over the GP/UT in percentages of its working time, with the people
 *          exposed in each GP/UT.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_exposuregroup.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$groupId = GETPOSTINT('groupid');

// Initialize technical objects
$object          = new DigiriskStandard($db);
$digiriskElement = new DigiriskElement($db);
$project         = new Project($db);

$hookmanager->initHooks(['digiriskstandardexposuregroup', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

// Security check - The groups and their shares describe the GP/UT: the rights of the GP/UT apply
$permissiontoread = $user->hasRight('digiriskdolibarr', 'digiriskstandard', 'read') && $user->hasRight('digiriskdolibarr', 'digiriskelement', 'read');
$permissiontoadd  = $user->hasRight('digiriskdolibarr', 'digiriskelement', 'write');

saturne_check_access($permissiontoread);

$pageUrl = $_SERVER['PHP_SELF'];
$groups  = digiriskdolibarr_exposuregroup_fetch_all($db);
// Shares typed in the grid, shown again when they are refused
$typedShares = [];

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Create or update a group: a label is required, the headcount is a whole number of people
    if (in_array($action, ['addGroup', 'updateGroup'], true) && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
        $label    = trim(GETPOST('label', 'alphanohtml'));
        $nbPeople = GETPOST('nb_people', 'alpha');
        if ($label === '') {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), [], 'errors');
            $action = $action === 'addGroup' ? 'create' : 'edit';
        } elseif ($nbPeople === '' || !ctype_digit($nbPeople)) {
            setEventMessages($langs->trans('ErrorExposureGroupNbPeople'), [], 'errors');
            $action = $action === 'addGroup' ? 'create' : 'edit';
        } else {
            $savedGroupId = $action === 'updateGroup' && isset($groups[$groupId]) ? $groupId : 0;
            $result       = digiriskdolibarr_exposuregroup_save($db, $user, $savedGroupId, $label, GETPOST('description', 'alphanohtml'), (int) $nbPeople);
            if ($result > 0) {
                setEventMessages($langs->trans($savedGroupId > 0 ? 'ExposureGroupUpdated' : 'ExposureGroupCreated', $label), []);
                header('Location: ' . $pageUrl);
                exit;
            }
            setEventMessages($db->lasterror(), [], 'errors');
        }
    }

    if ($action === 'confirm_deleteGroup' && $confirm === 'yes' && $permissiontoadd && isset($groups[$groupId])) {
        if (digiriskdolibarr_exposuregroup_delete($db, $groupId) > 0) {
            setEventMessages($langs->trans('ExposureGroupDeleted', $groups[$groupId]->label), []);
        } else {
            setEventMessages($db->lasterror(), [], 'errors');
        }
        header('Location: ' . $pageUrl);
        exit;
    }

    // Save the grid: every share between 0 and 100, an empty one takes the group out of the GP/UT
    if ($action === 'saveShares' && $permissiontoadd) {
        // One field per group and GP/UT, named share[<group ID>_<GP/UT ID>]: GETPOST only sanitizes one level of array
        $postedShares = GETPOST('share', 'array');
        $shares       = [];
        $invalid      = 0;
        foreach ($postedShares as $shareKey => $value) {
            if (!preg_match('/^(\d+)_(\d+)$/', (string) $shareKey, $shareIds) || !isset($groups[(int) $shareIds[1]])) {
                continue;
            }
            $value = trim((string) $value);
            if ($value === '') {
                $shares[(int) $shareIds[1]][(int) $shareIds[2]] = 0.0;
                continue;
            }
            $percentage = price2num($value);
            if (!is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
                $invalid++;
                continue;
            }
            $shares[(int) $shareIds[1]][(int) $shareIds[2]] = (float) $percentage;
        }

        if ($invalid > 0) {
            setEventMessages($langs->trans('ErrorExposureGroupShare', $invalid), [], 'errors');
            // The grid shows again what was typed, to be corrected rather than typed again
            $typedShares = $postedShares;
        } elseif (digiriskdolibarr_exposuregroup_save_shares($db, $user, $shares) >= 0) {
            setEventMessages($langs->trans('ExposureGroupSharesSaved'), []);
            header('Location: ' . $pageUrl . '#exposuregroup-grid');
            exit;
        } else {
            setEventMessages($db->lasterror(), [], 'errors');
        }
    }
}

/*
 * View
 */

$title   = $langs->trans('ExposureGroups');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

$form = new Form($db);

// Confirmation to delete a group
if ($action === 'deleteGroup' && isset($groups[$groupId])) {
    print $form->formconfirm($pageUrl . '?groupid=' . $groupId, $langs->trans('DeleteExposureGroup'), $langs->trans('ConfirmDeleteExposureGroup', $groups[$groupId]->label), 'confirm_deleteGroup', '', 'yes', 1);
}

saturne_get_fiche_head($object, 'standardExposureGroup', $title);

// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

print '<div class="fichecenter exposuregroup">';
print '<div class="opacitymedium exposuregroup-intro">' . $langs->trans('ExposureGroupsIntro') . '</div>';

$shares = digiriskdolibarr_exposuregroup_fetch_shares($db);

// Total share and people spread of each group
$groupTotals = [];
foreach ($groups as $group) {
    $groupTotals[$group->rowid] = array_sum($shares[$group->rowid] ?? []);
}

/*
 * The groups
 */

$addButton = dolGetButtonTitle($langs->trans('NewExposureGroup'), '', 'fa fa-plus-circle', $pageUrl . '?action=create#exposuregroup-form', '', $permissiontoadd ? 1 : 0);
print load_fiche_titre($langs->trans('ExposureGroups'), $addButton, 'fontawesome_fa-users_fas');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent exposuregroup-list">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Label') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="right">' . $form->textwithpicto($langs->trans('ExposureGroupNbPeople'), $langs->trans('ExposureGroupNbPeopleHelp')) . '</td>';
print '<td class="right">' . $form->textwithpicto($langs->trans('ExposureGroupTotalShare'), $langs->trans('ExposureGroupTotalShareHelp')) . '</td>';
print '<td class="right"></td>';
print '</tr>';

// Form of a new group, or of the group being edited, in place of its row
$groupForm = static function (?stdClass $group) use ($langs, $pageUrl): string {
    $out  = '<tr class="oddeven exposuregroup-form" id="exposuregroup-form">';
    $out .= '<td colspan="5">';
    $out .= '<form method="POST" action="' . $pageUrl . '">';
    $out .= '<input type="hidden" name="token" value="' . newToken() . '">';
    $out .= '<input type="hidden" name="action" value="' . ($group ? 'updateGroup' : 'addGroup') . '">';
    if ($group) {
        $out .= '<input type="hidden" name="groupid" value="' . (int) $group->rowid . '">';
    }
    $out .= '<input type="text" name="label" class="minwidth200" maxlength="255" required placeholder="' . dol_escape_htmltag($langs->trans('Label')) . '" value="' . dol_escape_htmltag(GETPOSTISSET('label') ? GETPOST('label', 'alphanohtml') : ($group->label ?? '')) . '"> ';
    $out .= '<input type="text" name="description" class="minwidth300" placeholder="' . dol_escape_htmltag($langs->trans('Description')) . '" value="' . dol_escape_htmltag(GETPOSTISSET('description') ? GETPOST('description', 'alphanohtml') : ($group->description ?? '')) . '"> ';
    $out .= '<input type="number" name="nb_people" class="width75" min="0" step="1" required placeholder="' . dol_escape_htmltag($langs->trans('ExposureGroupNbPeople')) . '" value="' . dol_escape_htmltag(GETPOSTISSET('nb_people') ? GETPOST('nb_people', 'alpha') : (string) ($group->nb_people ?? '')) . '"> ';
    $out .= '<input type="submit" class="button button-save smallpaddingimp" value="' . dol_escape_htmltag($langs->trans('Save')) . '"> ';
    $out .= '<a class="button button-cancel smallpaddingimp" href="' . $pageUrl . '">' . $langs->trans('Cancel') . '</a>';
    $out .= '</form>';
    $out .= '</td>';
    $out .= '</tr>';

    return $out;
};

if (empty($groups) && $action !== 'create') {
    print '<tr><td colspan="5"><span class="opacitymedium">' . $langs->trans('NoExposureGroup') . '</span></td></tr>';
}

foreach ($groups as $group) {
    if ($action === 'edit' && $groupId === $group->rowid && $permissiontoadd) {
        print $groupForm($group);
        continue;
    }

    $totalShare = $groupTotals[$group->rowid];
    $shareState = abs($totalShare - 100) < 0.01 ? 'complete' : ($totalShare > 100 ? 'over' : 'partial');

    print '<tr class="oddeven">';
    print '<td class="tdoverflowmax200">' . dol_escape_htmltag($group->label) . '</td>';
    print '<td class="tdoverflowmax300">' . dol_escape_htmltag($group->description) . '</td>';
    print '<td class="right">' . (int) $group->nb_people . '</td>';
    print '<td class="right"><span class="exposuregroup-total exposuregroup-total--' . $shareState . '" data-group="' . (int) $group->rowid . '">' . digiriskdolibarr_exposuregroup_format_number($totalShare, 2) . ' %</span></td>';
    print '<td class="right nowraponall">';
    if ($permissiontoadd) {
        print '<a class="editfielda paddingright" href="' . $pageUrl . '?action=edit&token=' . newToken() . '&groupid=' . (int) $group->rowid . '#exposuregroup-form">' . img_edit() . '</a>';
        print '<a class="paddingright" href="' . $pageUrl . '?action=deleteGroup&token=' . newToken() . '&groupid=' . (int) $group->rowid . '">' . img_delete() . '</a>';
    }
    print '</td>';
    print '</tr>';
}

if ($action === 'create' && $permissiontoadd) {
    print $groupForm(null);
}

print '</table>';
print '</div>';

/*
 * The grid: one row per GP/UT, in the order of the tree, one column per group
 */

if (!empty($groups)) {
    $activeDigiriskElements = $digiriskElement->getActiveDigiriskElements();
    $elementNodes           = is_array($activeDigiriskElements) ? flatten_tree(recurse_tree(0, 0, $activeDigiriskElements)) : [];

    print '<div id="exposuregroup-grid"></div>';
    print load_fiche_titre($langs->trans('ExposureGroupGrid'), '', 'fontawesome_fa-th_fas');
    print '<div class="opacitymedium exposuregroup-intro">' . $langs->trans('ExposureGroupGridHelp') . '</div>';

    if (empty($elementNodes)) {
        print '<div class="opacitymedium">' . $langs->trans('ExposureGroupNoElement') . '</div>';
    } else {
        print '<form method="POST" action="' . $pageUrl . '" class="exposuregroup-grid-form">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="saveShares">';

        print '<div class="div-table-responsive">';
        print '<table class="noborder centpercent exposuregroup-grid" data-decimal="' . dol_escape_htmltag($langs->transnoentitiesnoconv('SeparatorDecimal')) . '">';
        print '<thead><tr class="liste_titre">';
        print '<th>' . $langs->trans('ExposureGroupElement') . '</th>';
        foreach ($groups as $group) {
            print '<th class="center exposuregroup-grid__group" data-group="' . (int) $group->rowid . '" data-people="' . (int) $group->nb_people . '">';
            print dol_escape_htmltag($group->label) . '<br><span class="opacitymedium small">' . $langs->trans('ExposureGroupNbPeopleShort', (int) $group->nb_people) . '</span>';
            print '</th>';
        }
        print '<th class="right">' . $form->textwithpicto($langs->trans('ExposureGroupExposedPeople'), $langs->trans('ExposureGroupExposedPeopleHelp')) . '</th>';
        print '</tr></thead>';

        print '<tbody>';
        foreach ($elementNodes as $elementId => $elementNode) {
            $element       = $elementNode['object'];
            $exposedPeople = 0.0;

            print '<tr class="oddeven" data-element="' . (int) $elementId . '">';
            print '<td class="nowraponall" style="padding-left: ' . (8 + 18 * (int) $elementNode['depth']) . 'px">';
            print $element->getNomUrl(1, 'blank', 1) . ' <span class="opacitymedium">' . dol_escape_htmltag($element->label) . '</span>';
            print '</td>';
            foreach ($groups as $group) {
                $percentage     = $shares[$group->rowid][(int) $elementId] ?? 0.0;
                $exposedPeople += digiriskdolibarr_exposuregroup_people($group->nb_people, $percentage);

                print '<td class="center">';
                $shareKey = (int) $group->rowid . '_' . (int) $elementId;
                $value    = $percentage > 0 ? digiriskdolibarr_exposuregroup_format_number($percentage, 2) : '';
                if ($permissiontoadd) {
                    $value = isset($typedShares[$shareKey]) ? trim((string) $typedShares[$shareKey]) : $value;
                    print '<input type="text" inputmode="decimal" class="width50 right exposuregroup-share" name="share[' . $shareKey . ']" data-group="' . (int) $group->rowid . '" value="' . dol_escape_htmltag($value) . '" placeholder="0" aria-label="' . dol_escape_htmltag($group->label . ' - ' . $element->ref) . '"> %';
                } else {
                    print $value !== '' ? $value . ' %' : '<span class="opacitymedium">&mdash;</span>';
                }
                print '</td>';
            }
            print '<td class="right exposuregroup-exposed">' . ($exposedPeople > 0 ? digiriskdolibarr_exposuregroup_format_number($exposedPeople, 1) : '<span class="opacitymedium">&mdash;</span>') . '</td>';
            print '</tr>';
        }
        print '</tbody>';

        // Total of each group, 100 % once it is fully spread
        print '<tfoot><tr class="liste_total">';
        print '<td>' . $langs->trans('ExposureGroupTotalShare') . '</td>';
        foreach ($groups as $group) {
            $totalShare = $groupTotals[$group->rowid];
            $shareState = abs($totalShare - 100) < 0.01 ? 'complete' : ($totalShare > 100 ? 'over' : 'partial');
            print '<td class="center"><span class="exposuregroup-total exposuregroup-total--' . $shareState . '" data-group="' . (int) $group->rowid . '">' . digiriskdolibarr_exposuregroup_format_number($totalShare, 2) . ' %</span></td>';
        }
        print '<td></td>';
        print '</tr></tfoot>';
        print '</table>';
        print '</div>';

        if ($permissiontoadd) {
            print '<div class="center"><input type="submit" class="button button-save" value="' . dol_escape_htmltag($langs->trans('ExposureGroupSaveShares')) . '"></div>';
        }
        print '</form>';
    }
}

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
