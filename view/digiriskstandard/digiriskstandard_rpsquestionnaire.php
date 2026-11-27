<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/digiriskstandard/digiriskstandard_rpsquestionnaire.php
 * \ingroup digiriskdolibarr
 * \brief   Psychosocial risk questionnaires of the company (#5324): the sessions in which the employees of a job
 *          category answer the RPS-DU grid themselves, through a public link, for their work unit. Each session
 *          shows its link and QR code, the number of answers of each work unit and their averages; closing it
 *          records those averages as the psychosocial assessment of the work units.
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
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_riskoverview.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_rpsquestionnaire.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $moduleNameLowerCase, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'aZ09');
$confirm   = GETPOST('confirm', 'alpha');
$sessionId = GETPOSTINT('sessionid');

// Initialize technical objects
$object          = new DigiriskStandard($db);
$digiriskElement = new DigiriskElement($db);
$project         = new Project($db);

$hookmanager->initHooks(['digiriskstandardrpsquestionnaire', 'digiriskstandardview', 'globalcard']); // Note that conf->hooks_modules contains array

// Load object
$object->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_ACTIVE_STANDARD'));

// Security check - A session ends in the psychosocial assessment of its work units: the rights of the risks apply
$permissiontoread = $user->hasRight('digiriskdolibarr', 'digiriskstandard', 'read') && $user->hasRight('digiriskdolibarr', 'risk', 'read');
$permissiontoadd  = $user->hasRight('digiriskdolibarr', 'risk', 'write');

saturne_check_access($permissiontoread);

$pageUrl  = $_SERVER['PHP_SELF'];
$sessions = digiriskRpsSessionFetchAll($db);
$grid     = digiriskRpsQuestionnaireGrid();
$groups   = digiriskdolibarr_exposuregroup_fetch_all($db);
$shares   = digiriskdolibarr_exposuregroup_fetch_shares($db);

// The work units of the company, in the order of the tree: the questionnaire is answered for a work unit
$activeDigiriskElements = $digiriskElement->getActiveDigiriskElements();
$elementNodes           = is_array($activeDigiriskElements) ? flatten_tree(recurse_tree(0, 0, $activeDigiriskElements)) : [];
$workUnits              = [];
foreach ($elementNodes as $elementId => $elementNode) {
    if ($elementNode['object']->element_type == 'workunit') {
        $workUnits[(int) $elementId] = $elementNode['object'];
    }
}

/*
 * Actions
 */

$parameters = [];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    // Create or update a session: a label, at least one work unit, a whole number of answers per work unit
    if (in_array($action, ['addSession', 'updateSession'], true) && $permissiontoadd && !GETPOST('cancel', 'alpha')) {
        $label      = trim(GETPOST('label', 'alphanohtml'));
        $groupId    = GETPOSTINT('fk_exposuregroup');
        $maxAnswers = trim(GETPOST('max_answers', 'alpha'));
        $unitIds    = array_values(array_intersect(array_map('intval', GETPOST('units', 'array')), array_keys($workUnits)));

        $formAction = $action === 'addSession' ? 'create' : 'edit';
        if ($label === '') {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), [], 'errors');
            $action = $formAction;
        } elseif (empty($unitIds)) {
            setEventMessages($langs->trans('PsychosocialQuestionnaireErrorNoUnit'), [], 'errors');
            $action = $formAction;
        } elseif ($maxAnswers !== '' && !ctype_digit($maxAnswers)) {
            setEventMessages($langs->trans('PsychosocialQuestionnaireErrorMaxAnswers'), [], 'errors');
            $action = $formAction;
        } else {
            $savedSessionId = $action === 'updateSession' && isset($sessions[$sessionId]) ? $sessionId : 0;
            $result         = digiriskRpsSessionSave($db, $user, $savedSessionId, $label, isset($groups[$groupId]) ? $groupId : 0, (int) $maxAnswers, $unitIds);
            if ($result > 0) {
                setEventMessages(dol_escape_htmltag($langs->transnoentities($savedSessionId > 0 ? 'PsychosocialQuestionnaireSessionUpdated' : 'PsychosocialQuestionnaireSessionCreated', $label)), []);
                header('Location: ' . $pageUrl . '#rps-session-' . $result);
                exit;
            }
            setEventMessages($db->lasterror(), [], 'errors');
        }
    }

    // Close a session: no more answers, and its averages become the psychosocial assessment of its work units
    if ($action === 'confirm_closeSession' && $confirm === 'yes' && $permissiontoadd && isset($sessions[$sessionId])) {
        $assessed = digiriskRpsSessionRecordAssessments($db, $user, $sessions[$sessionId], digiriskRpsSessionResults($db, $sessions[$sessionId], $grid), $grid, $langs);
        if ($assessed >= 0 && digiriskRpsSessionSetStatus($db, $user, $sessionId, DIGIRISK_RPS_SESSION_CLOSED) > 0) {
            setEventMessages(dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireSessionClosed', $sessions[$sessionId]['label'], $assessed)), []);
        } else {
            setEventMessages($langs->trans('PsychosocialQuestionnaireErrorClose') . ' ' . $db->lasterror(), [], 'errors');
        }
        header('Location: ' . $pageUrl . '#rps-session-' . $sessionId);
        exit;
    }

    if ($action === 'setOpenSession' && $permissiontoadd && isset($sessions[$sessionId])) {
        if (digiriskRpsSessionSetStatus($db, $user, $sessionId, DIGIRISK_RPS_SESSION_OPEN) > 0) {
            setEventMessages(dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireSessionReopened', $sessions[$sessionId]['label'])), []);
        } else {
            setEventMessages($db->lasterror(), [], 'errors');
        }
        header('Location: ' . $pageUrl . '#rps-session-' . $sessionId);
        exit;
    }

    if ($action === 'confirm_deleteSession' && $confirm === 'yes' && $permissiontoadd && isset($sessions[$sessionId])) {
        if (digiriskRpsSessionDelete($db, $sessionId) > 0) {
            setEventMessages(dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireSessionDeleted', $sessions[$sessionId]['label'])), []);
        } else {
            setEventMessages($db->lasterror(), [], 'errors');
        }
        header('Location: ' . $pageUrl);
        exit;
    }
}

// Averages of the work units of every session, their public link and QR code
$sessionResults = [];
$sessionLinks   = [];
foreach ($sessions as $session) {
    $sessionResults[$session['rowid']] = digiriskRpsSessionResults($db, $session, $grid);
    $sessionLinks[$session['rowid']]   = digiriskRpsSessionPublicUrl($session);
}

// The psychosocial factors, F1 to F6, as the other psychosocial screens show them
$rpsSubCategories = [];
foreach ((Risk::getDangerSubCategories()[Risk::PSYCHOSOCIAL_CATEGORY_POSITION] ?? []) as $subCategory) {
    if ($subCategory['position'] <= 5) {
        $rpsSubCategories[(int) $subCategory['position']] = $subCategory;
    }
}
$rpsScaleLabels = [1 => $langs->trans('Weak'), 2 => $langs->trans('Moderate'), 3 => $langs->trans('High'), 4 => $langs->trans('Extreme')];

// Work units of each job category, and its headcount, proposed by the form of a session
$groupUnits = [];
foreach ($groups as $group) {
    $groupUnits[$group->rowid] = [];
    foreach (($shares[$group->rowid] ?? []) as $elementId => $percentage) {
        if ($percentage > 0 && isset($workUnits[$elementId])) {
            $groupUnits[$group->rowid][] = (int) $elementId;
        }
    }
}

// The form of a new session, or of the session edited
$sessionForm = null;
if ($permissiontoadd && ($action === 'create' || ($action === 'edit' && isset($sessions[$sessionId])))) {
    $editedSession = $action === 'edit' ? $sessions[$sessionId] : null;
    $sessionForm   = [
        'action'      => $editedSession ? 'updateSession' : 'addSession',
        'sessionid'   => $editedSession ? $editedSession['rowid'] : 0,
        'label'       => GETPOSTISSET('label') ? GETPOST('label', 'alphanohtml') : ($editedSession['label'] ?? $langs->transnoentities('PsychosocialQuestionnaireDefaultLabel', dol_print_date(dol_now(), '%Y'))),
        'group'       => GETPOSTISSET('fk_exposuregroup') ? GETPOSTINT('fk_exposuregroup') : ($editedSession['fk_exposuregroup'] ?? 0),
        'max_answers' => GETPOSTISSET('max_answers') ? GETPOST('max_answers', 'alpha') : (!empty($editedSession['max_answers']) ? (string) $editedSession['max_answers'] : ''),
        'units'       => GETPOSTISSET('units') ? array_map('intval', GETPOST('units', 'array')) : array_keys($editedSession['elements'] ?? []),
        // A work unit that got answers stays in the session
        'locked'      => array_keys(array_filter($editedSession['elements'] ?? [], function ($sessionElement) {
            return $sessionElement['count'] > 0;
        })),
    ];
}

/*
 * View
 */

$title   = $langs->trans('PsychosocialQuestionnaires');
$helpUrl = 'FR:Module_Digirisk';

digirisk_header($title, $helpUrl);

$form = new Form($db);

// Confirmation to close or delete a session
if ($action === 'closeSession' && isset($sessions[$sessionId])) {
    print $form->formconfirm($pageUrl . '?sessionid=' . $sessionId, $langs->trans('PsychosocialQuestionnaireCloseSession'), dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireConfirmClose', $sessions[$sessionId]['label'])), 'confirm_closeSession', '', 'yes', 1);
}
if ($action === 'deleteSession' && isset($sessions[$sessionId])) {
    print $form->formconfirm($pageUrl . '?sessionid=' . $sessionId, $langs->trans('PsychosocialQuestionnaireDeleteSession'), dol_escape_htmltag($langs->transnoentities('PsychosocialQuestionnaireConfirmDelete', $sessions[$sessionId]['label'], $sessions[$sessionId]['total'])), 'confirm_deleteSession', '', 'yes', 1);
}

saturne_get_fiche_head($object, 'standardRpsQuestionnaire', $title);

// Project
$moreHtmlRef = '<div class="refidno">';
$project->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT'));
$moreHtmlRef .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
$moreHtmlRef .= '</div>';

$moduleNameLowerCase = 'mycompany';
saturne_banner_tab($object, 'ref', 'none', 0, 'ref', 'ref', $moreHtmlRef, true);
$moduleNameLowerCase = 'digiriskdolibarr';

require __DIR__ . '/../../core/tpl/riskanalysis/rpsquestionnaire/digiriskdolibarr_rpsquestionnaire_view.tpl.php';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
