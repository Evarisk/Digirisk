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
 * \file    view/safetytalk/safetytalk_card.php
 * \ingroup digiriskdolibarr
 * \brief   Mobile screen of one safety talk, the way the prevention plan works: a form (theme, title, date,
 *          content, files and videos), then the screen of the talk once diffused to the people of its cycle,
 *          with its progress, its QR code and where each signature stands.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../lib/digiriskdolibarr_pwa.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_safetytalk.lib.php';

// Load Saturne libraries: the media block of the files of a talk
dol_include_once('/saturne/lib/medias.lib.php');
dol_include_once('/saturne/lib/dolibarr.lib.php');

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'other']);

// Get parameters
$id        = GETPOSTINT('id');
$projectId = GETPOSTINT('project_id');
$action    = GETPOST('action', 'aZ09');

// Initialize technical objects
$object  = new Task($db);
$project = new Project($db);

// Initialize hooks
$hookmanager->initHooks(['safetytalkcard', 'globalcard']);

// Security check
$permissiontoread = isModEnabled('project') && $user->hasRight('projet', 'lire');
$permissiontoadd  = isModEnabled('project') && $user->hasRight('projet', 'creer');
saturne_check_access($permissiontoread);

if ($id > 0) {
    if ($object->fetch($id) <= 0) {
        accessforbidden($langs->trans('ErrorRecordNotFound'));
    }
    $object->fetch_optionals();
    $projectId = (int) $object->fk_project;
}
if ($projectId <= 0 || $project->fetch($projectId) <= 0 || !digiriskSafetyTalkIsCycle($db, $project)) {
    accessforbidden($langs->trans('SafetyTalkCycleNotFound'));
}
$project->fetch_optionals();
if ($project->restrictedProjectArea($user) < 0) {
    accessforbidden();
}
$permissiontoadd = $permissiontoadd && $project->restrictedProjectArea($user, 'write') > 0;

$cycleUrl = dol_buildpath('/digiriskdolibarr/view/safetytalk/safetytalk_project.php', 1) . '?id=' . $project->id;

// Files of the talk, through the media block of Saturne: in the documents of the task, or, on the creation
// form, in a temporary folder of the form session, brought to the talk once it is saved
$uploadContext = 'digiriskdolibarr_safetytalk_' . ($id > 0 ? $id : 'new_' . $project->id);
$mediaContext  = digiriskSafetyTalkMediaContext($project, $id > 0 ? $object : null, saturne_get_upload_token($uploadContext));
$mediaPrefix   = 'safetytalk-media';

/**
 * Answer an action: back to the screen, with the messages of a failure.
 *
 * @param  bool     $success  Whether the action succeeded
 * @param  string   $redirect Where to go next
 * @param  string[] $errors   Messages when the action failed
 * @return void
 */
$respond = function (bool $success, string $redirect, array $errors = []) {
    if (!$success && !empty($errors)) {
        setEventMessages('', $errors, 'errors');
    }
    header('Location: ' . $redirect);
    exit;
};

/*
 * Actions
 */

// Every other action of this screen changes data: anti-CSRF token required, whatever the setting. Opening
// the creation or edition form changes nothing, it is reached by a plain link
if (!in_array($action, ['create', 'edit'], true)) {
    digiriskMobileCheckToken($action);
}

// The media block sends its photos and files as soon as they are picked, and expects itself back refreshed.
// On the screen of a talk, the content of its spread comes with it, put in place by the script of the screen
if ($permissiontoadd && digiriskSafetyTalkMediaAction($action, $mediaContext, $mediaPrefix, $id > 0 ? $object : null, $user)) {
    if ($id > 0) {
        $documents = digiriskSafetyTalkGetDocuments($object);
        require __DIR__ . '/../../core/tpl/frontend/safetytalk_talk_documents_list.tpl.php';
    }
    exit;
}

if ($action == 'save' && $permissiontoadd) {
    $label       = trim(GETPOST('label', 'alphanohtml'));
    $dateStart   = digiriskSafetyTalkParseDate(GETPOST('date_start', 'alpha'));
    $description = GETPOST('description', 'restricthtml');
    $theme       = GETPOST('theme', 'alpha');
    $theme       = (digiriskSafetyTalkGetTheme($theme) !== null) ? $theme : '';

    $errors = [];
    if (!dol_strlen($label)) {
        $errors[] = $langs->transnoentities('SafetyTalkErrorTitleRequired');
    }
    if ($dateStart <= 0) {
        $errors[] = $langs->transnoentities('SafetyTalkErrorDateRequired');
    }
    $linkUrl = trim(GETPOST('link_url', 'alphanohtml'));
    if (dol_strlen($linkUrl) && (!preg_match('/^https?:\/\//i', $linkUrl) || !filter_var($linkUrl, FILTER_VALIDATE_URL))) {
        $errors[] = $langs->transnoentities('SafetyTalkErrorBadLink');
    }

    if (!empty($errors)) {
        setEventMessages('', $errors, 'errors');
        $action = ($id > 0) ? 'edit' : 'create';
    } else {
        $isNew = ($id <= 0);
        digiriskSafetyTalkEnsureSetup($db, $user);

        $object->label       = $label;
        $object->description = $description;
        $object->date_start  = $dateStart;
        $object->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] = $theme;

        if ($isNew) {
            list($refTaskMod) = saturne_require_objects_mod(['project/task' => getDolGlobalString('PROJECT_TASK_ADDON')], 'digiriskdolibarr');

            $object->fk_project     = $project->id;
            $object->fk_task_parent = 0;
            $object->ref            = $refTaskMod->getNextValue(0, $object);
            $object->date_c         = dol_now();
            $object->status         = 1;
            $result                 = $object->create($user);
        } else {
            $result = $object->update($user);
        }
        if ($result <= 0) {
            $respond(false, $_SERVER['PHP_SELF'] . ($isNew ? '?project_id=' . $project->id . '&action=create' : '?id=' . $object->id . '&action=edit'), array_merge([$object->error], (array) $object->errors));
        }
        $object->fetch($object->id);
        $object->fetch_optionals();

        // Everyone assigned to the cycle reads and signs this talk, with or without Dolibarr account
        $added = digiriskSafetyTalkSyncSpread($db, $object, array_keys(digiriskSafetyTalkGetParticipants($project)), $user, [], digiriskSafetyTalkGetGuests($project));

        $errors = [];
        if ($added < 0) {
            $errors[] = $object->error ? $langs->transnoentities($object->error) : $langs->transnoentities('Error');
        }
        // Files added on the creation form, kept aside until the talk existed
        if ($isNew) {
            digiriskSafetyTalkAttachUploads($object, $mediaContext, digiriskSafetyTalkMediaContext($project, $object), $user);
            saturne_invalidate_upload_token($uploadContext);
        }
        if (dol_strlen($linkUrl) && digiriskSafetyTalkAddLink($db, $object, $linkUrl, trim(GETPOST('link_label', 'alphanohtml')), $user) < 0) {
            $errors[] = $langs->transnoentities('SafetyTalkErrorBadLink');
        }

        // The talk is saved anyway: its screen shows what is missing and lets add it again
        $redirect = $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&saved=' . ($isNew ? 'created' : 'updated');
        if (!empty($errors)) {
            setEventMessages('', $errors, 'errors');
        }
        $respond(true, $redirect);
    }
}

if ($action == 'add_link' && $id > 0 && $permissiontoadd) {
    $result = digiriskSafetyTalkAddLink($db, $object, trim(GETPOST('link_url', 'alphanohtml')), trim(GETPOST('link_label', 'alphanohtml')), $user);
    $respond($result > 0, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-documents', [$langs->transnoentities('SafetyTalkErrorBadLink')]);
}

if ($action == 'delete_document' && $id > 0 && $permissiontoadd) {
    digiriskSafetyTalkDeleteDocument($db, $object, GETPOST('document_type', 'aZ09'), GETPOSTINT('document_id'), $user);
    $respond(true, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-documents');
}

// Someone present that day, without Dolibarr account, added to this talk alone
if ($action == 'add_person' && $id > 0 && $permissiontoadd) {
    $personErrors = [];
    $persons      = digiriskSafetyTalkCleanGuests([GETPOST('person_firstname', 'alphanohtml')], [GETPOST('person_lastname', 'alphanohtml')], [GETPOST('person_email', 'alphanohtml')], $personErrors);
    if (empty($persons)) {
        $respond(false, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-signatories', $personErrors ?: [$langs->transnoentities('SafetyTalkErrorPersonName')]);
    }
    $result = digiriskSafetyTalkAddPerson($db, $object, $persons[0], $user);
    if ($result < 0) {
        $respond(false, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-signatories', [$object->error ?: $langs->transnoentities('Error')]);
    }
    $personName = trim($persons[0]['firstname'] . ' ' . $persons[0]['lastname']);
    setEventMessages($langs->trans($result > 0 ? 'SafetyTalkPersonAdded' : 'SafetyTalkPersonAlreadyThere', $personName), [], $result > 0 ? 'mesgs' : 'warnings');
    $respond(true, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-signatories');
}

if ($action == 'remove_person' && $id > 0 && $permissiontoadd) {
    if (digiriskSafetyTalkRemovePerson($db, $object, GETPOSTINT('signatory_id'), $user) > 0) {
        setEventMessages($langs->trans('SafetyTalkPersonRemoved', GETPOST('person_name', 'alphanohtml')), []);
    }
    $respond(true, $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#safetytalk-signatories');
}

if ($action == 'send_links' && $id > 0 && $permissiontoadd) {
    $sendResult = digiriskSafetyTalkSendSignatureLinks($db, $object, $user, $langs);
    if (!empty($sendResult['sent'])) {
        setEventMessages($langs->trans('SafetyTalkLinksSent', count($sendResult['sent'])), []);
    }
    if (!empty($sendResult['failed'])) {
        setEventMessages($langs->trans('SafetyTalkLinksNotSent', implode(', ', $sendResult['failed'])), [], 'warnings');
    }
    if (dol_strlen($sendResult['error'])) {
        setEventMessages($sendResult['error'], [], 'errors');
    } elseif (empty($sendResult['sent']) && empty($sendResult['failed'])) {
        setEventMessages($langs->trans('SafetyTalkLinksNothingToSend'), []);
    }
    $respond(true, $_SERVER['PHP_SELF'] . '?id=' . $object->id);
}

if ($action == 'delete' && $id > 0 && $permissiontoadd) {
    $talkRef = $object->ref;
    $result  = digiriskSafetyTalkDelete($db, $object, $user);
    if ($result > 0) {
        $respond(true, $cycleUrl . '&saved=talk_deleted&ref=' . urlencode($talkRef));
    }
    $respond(false, $_SERVER['PHP_SELF'] . '?id=' . $object->id, [$result == -2 ? $langs->transnoentities('SafetyTalkErrorDeleteSigned') : ($object->error ?: $langs->transnoentities('Error'))]);
}

/*
 * View
 */

$isForm = ($id <= 0 || $action == 'edit' || $action == 'create');
if ($isForm && !$permissiontoadd) {
    accessforbidden();
}

$title = $langs->trans('SafetyTalk');
digiriskPwaHeader($title);

$pwaHeaderTitle = $title;
require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php';

// Biggest file the server takes, shown and checked before sending
$maxUploadKb = (int) getMaxFileSizeArray()['maxmin'];

if ($isForm) {
    // Back from a refused save: what was typed stays
    $prefill = [
        'label'       => GETPOSTISSET('label') ? GETPOST('label', 'alphanohtml') : (string) $object->label,
        'date_start'  => GETPOSTISSET('date_start') ? GETPOST('date_start', 'alpha') : dol_print_date($object->date_start ?: dol_now(), '%Y-%m-%dT%H:%M', 'tzuserrel'),
        'description' => GETPOSTISSET('description') ? GETPOST('description', 'restricthtml') : (string) $object->description,
        'theme'       => GETPOSTISSET('theme') ? GETPOST('theme', 'alpha') : (string) ($object->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] ?? ''),
    ];
    require __DIR__ . '/../../core/tpl/frontend/safetytalk_talk_form.tpl.php';
} else {
    require __DIR__ . '/../../core/tpl/frontend/safetytalk_talk_success.tpl.php';
}

require_once __DIR__ . '/../../core/tpl/frontend/digiriskdolibarr_pwa_bottom_nav.tpl.php';

llxFooter();
$db->close();
