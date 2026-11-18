<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    view/safetytalk/safetytalk_project.php
 * \ingroup digiriskdolibarr
 * \brief   Mobile screen of a cycle of safety talks: its name and the people assigned to it (creation and
 *          edition form), then its talks in the order they are held, and the PDF planning of the cycle.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_safetytalk.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'companies']);

// Get parameters
$id     = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Project($db);
$form   = new Form($db);

// Initialize hooks
$hookmanager->initHooks(['safetytalkproject', 'globalcard']);

// Security check
$permissiontoread = isModEnabled('project') && $user->hasRight('projet', 'lire');
$permissiontoadd  = isModEnabled('project') && $user->hasRight('projet', 'creer');
saturne_check_access($permissiontoread);

if ($id > 0) {
    if ($object->fetch($id) <= 0 || !digiriskSafetyTalkIsCycle($db, $object)) {
        accessforbidden($langs->trans('SafetyTalkCycleNotFound'));
    }
    $object->fetch_optionals();
    if ($object->restrictedProjectArea($user) < 0) {
        accessforbidden();
    }
    $permissiontoadd = $permissiontoadd && $object->restrictedProjectArea($user, 'write') > 0;
}

/*
 * Actions
 */

// Every other action of this screen changes data: anti-CSRF token required, whatever the setting. Opening
// the creation or edition form changes nothing, it is reached by a plain link
if (!in_array($action, ['create', 'edit'], true)) {
    digiriskMobileCheckToken($action);
}

if ($action == 'save' && $permissiontoadd) {
    $title          = trim(GETPOST('title', 'alphanohtml'));
    $description    = GETPOST('description', 'restricthtml');
    // The people of the cycle, one row each: the row of a Dolibarr user carries its id, the others are typed in
    $rowUsers       = GETPOST('guest_user', 'array');
    $rowFirstnames  = GETPOST('guest_firstname', 'array');
    $rowLastnames   = GETPOST('guest_lastname', 'array');
    $rowEmails      = GETPOST('guest_email', 'array');
    $participantIds = [];
    foreach ($rowUsers as $row => $rowUser) {
        if ((int) $rowUser > 0) {
            $participantIds[] = (int) $rowUser;
            unset($rowFirstnames[$row], $rowLastnames[$row], $rowEmails[$row]);
        }
    }
    $guestErrors = [];
    $guests      = digiriskSafetyTalkCleanGuests($rowFirstnames, $rowLastnames, $rowEmails, $guestErrors);

    if (!dol_strlen($title) || !empty($guestErrors)) {
        setEventMessages(dol_strlen($title) ? '' : $langs->trans('SafetyTalkErrorNameRequired'), $guestErrors, 'errors');
        $action = ($id > 0) ? 'edit' : 'create';
    } else {
        $db->begin();

        $error = 0;
        $tagId = digiriskSafetyTalkEnsureSetup($db, $user);
        if ($tagId <= 0) {
            $error++;
        }

        $isNew = ($id <= 0);
        if (!$error) {
            $object->title       = $title;
            $object->description = $description;
            if ($isNew) {
                list($refProjectMod) = saturne_require_objects_mod(['project' => getDolGlobalString('PROJECT_ADDON')], 'digiriskdolibarr');

                $object->ref        = $refProjectMod->getNextValue(null, $object);
                $object->date_c     = dol_now();
                $object->date_start = dol_now();
                $object->usage_task = 1;
                $object->public     = 0;
                $object->status     = Project::STATUS_VALIDATED;
                $object->statut     = Project::STATUS_VALIDATED;

                $result = $object->create($user);
                if ($result > 0) {
                    $tag = new Categorie($db);
                    $tag->fetch($tagId);
                    $tag->add_type($object, 'project');
                    // The one who creates the cycle leads it, and keeps seeing it without the right to read every project
                    $object->add_contact($user->id, 'PROJECTLEADER', 'internal');
                }
            } else {
                $result = $object->update($user);
            }
            if ($result <= 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        }

        if (!$error && digiriskSafetyTalkSetParticipants($db, $object, $participantIds, $guests, $user) < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        }

        if ($error) {
            $db->rollback();
            $action = $isNew ? 'create' : 'edit';
            if ($isNew) {
                $object->id = 0;
            }
        } else {
            $db->commit();
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&saved=' . ($isNew ? 'created' : 'updated'));
            exit;
        }
    }
}

// PDF planning of the cycle, saved with the documents of the project and opened right away
if ($action == 'planning' && $id > 0) {
    require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskdolibarrdocuments/safetytalkdocument/pdf_safetytalkdocument.modules.php';

    $outputLangs = $langs;
    $planningDoc = new pdf_safetytalkdocument($db);
    $planningPdf = $planningDoc->build($object, digiriskSafetyTalkFetchTalks($db, $object), $outputLangs, $user);

    $planningDir  = $conf->project->multidir_output[$object->entity ?: $conf->entity] . '/' . dol_sanitizeFileName($object->ref);
    $planningFile = dol_sanitizeFileName($object->ref . '_' . $langs->transnoentities('SafetyTalkPlanningFileName') . '_' . dol_print_date(dol_now(), 'dayxcard') . '.pdf');
    if (dol_mkdir($planningDir) >= 0) {
        $planningPdf->Output($planningDir . '/' . $planningFile, 'F');
    }

    if (dol_is_file($planningDir . '/' . $planningFile)) {
        header('Location: ' . DOL_URL_ROOT . '/document.php?modulepart=project&attachment=0&entity=' . ((int) ($object->entity ?: $conf->entity)) . '&file=' . urlencode(dol_sanitizeFileName($object->ref) . '/' . $planningFile));
        exit;
    }

    setEventMessages($langs->trans('SafetyTalkPlanningError'), [], 'errors');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
    exit;
}

/*
 * View
 */

$isForm = ($action == 'create' || $action == 'edit' || $id <= 0) && $permissiontoadd;
if ($id <= 0 && !$isForm) {
    accessforbidden();
}

$title = $langs->trans('PwaNavSafetyTalks');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

if ($isForm) {
    // Back from a refused save: what was typed stays
    $prefill = [
        'title'       => GETPOSTISSET('title') ? GETPOST('title', 'alphanohtml') : (string) $object->title,
        'description' => GETPOSTISSET('description') ? GETPOST('description', 'restricthtml') : (string) $object->description,
        'people'      => [],
    ];
    if (GETPOSTISSET('guest_firstname')) {
        $postedUsers     = GETPOST('guest_user', 'array');
        $postedLastnames = GETPOST('guest_lastname', 'array');
        $postedEmails    = GETPOST('guest_email', 'array');
        foreach (GETPOST('guest_firstname', 'array') as $row => $postedFirstname) {
            $prefill['people'][] = ['firstname' => $postedFirstname, 'lastname' => $postedLastnames[$row] ?? '', 'email' => $postedEmails[$row] ?? '', 'user_id' => (int) ($postedUsers[$row] ?? 0)];
        }
    } elseif ($id > 0) {
        // Users of the cycle first, as Dolibarr knows them, then the people typed in
        foreach (array_keys(digiriskSafetyTalkGetParticipants($object)) as $participantId) {
            $participant = new User($db);
            if ($participant->fetch($participantId) > 0) {
                $prefill['people'][] = ['firstname' => $participant->firstname, 'lastname' => $participant->lastname, 'email' => $participant->email, 'user_id' => (int) $participant->id];
            }
        }
        foreach (digiriskSafetyTalkGetGuests($object) as $guest) {
            $prefill['people'][] = $guest + ['user_id' => 0];
        }
    }
    // A new cycle opens on one empty row, ready to be filled in
    if (empty($prefill['people'])) {
        $prefill['people'][] = ['firstname' => '', 'lastname' => '', 'email' => '', 'user_id' => 0];
    }
    require __DIR__ . '/../../core/tpl/frontend/safetytalk_project_form.tpl.php';
} else {
    $talks  = digiriskSafetyTalkFetchTalks($db, $object);
    $people = digiriskSafetyTalkGetPeopleNames($db, $object, $langs);
    require __DIR__ . '/../../core/tpl/frontend/safetytalk_project_view.tpl.php';
}

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
