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
 * \file    core/tpl/task/task_card_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Digirisk card of a task: banner, label, description, dates, files, responsible, progress, tags, what the task
 *          comes from and its events, edited on the spot by js/modules/ticket.js as the ticket card.
 *
 * Rendered by view/task/task_card.php (inherits its scope). Expects:
 *   $conf, $db, $langs, $user, $form, $userstat, $now, $object (Task, fetched), $project, $origins, $ticket, $ticketUrl,
 *   $linkbackUrl, $linkbackLabel, $permissionToWrite, $url_page_current, $upload_dir, $printExecutives
 */

// The classes of the ticket card bring its layout and its on-the-spot editing (js/modules/ticket.js)
print '<div class="digirisk-ticket-card digirisk-task-card">';

$head = digiriskdolibarr_task_prepare_head($object);
print dol_get_fiche_head($head, 'task_task', $langs->trans('Task'), -1, 'projecttask');

// Field pictos (#4885) — one meaningful icon per field, as on the ticket card.
$fieldPicto = static function (string $icon): string {
    return '<i class="fas fa-' . $icon . ' dtc-field-picto"></i>';
};

// Helper to render an inline editable field, as on the ticket card.
// tabindex="0" (never a positive value, which would hijack the tab order of the whole page):
// the fields are chained in DOM order by the "dtc-tabfield" class, see js/modules/ticket.js.
$renderInlineEditable = static function (string $field, string $type, string $val, string $placeholder, string $rawVal = '') use ($langs, $permissionToWrite, $object): string {
    if (!$permissionToWrite) {
        return $val === '' ? '<span class="opacitymedium">' . dol_escape_htmltag($placeholder) . '</span>' : $val;
    }
    $isEmpty    = ($val === '');
    $displayVal = $isEmpty ? dol_escape_htmltag($placeholder) : $val;
    $classes    = 'dtc-extrafield-value dtc-inline-editable dtc-tabfield' . ($isEmpty ? ' is-empty' : '');

    return '<span class="' . $classes . '" tabindex="0" title="' . dol_escape_htmltag($langs->trans('Modify')) . '" data-placeholder="' . dol_escape_htmltag($placeholder) . '" data-field="' . dol_escape_htmltag($field) . '" data-type="' . dol_escape_htmltag($type) . '" data-ticket-id="' . (int) $object->id . '" data-raw="' . dol_escape_htmltag($rawVal) . '">' . $displayVal . '</span>';
};

// ---- Build morehtmlref (native Dolibarr banner style) ----
$morehtmlref = '<div class="refidno">';

// Ticket the task belongs to
if ($ticket !== null) {
    $morehtmlref .= '<span class="dtc-inline-badge dtc-ticket">';
    $morehtmlref .= img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="dtc-badge-logo"');
    $morehtmlref .= '<a class="dtc-badge-nav" href="' . dol_escape_htmltag($ticketUrl) . '" title="' . dol_escape_htmltag($langs->trans('Register')) . '"><i class="fas fa-ticket-alt"></i></a>';
    $morehtmlref .= '<a class="dtc-badge-name" href="' . dol_escape_htmltag($ticketUrl) . '">' . dol_escape_htmltag($ticket->ref . (!empty($ticket->subject) ? ' - ' . $ticket->subject : '')) . '</a>';
    $morehtmlref .= '</span><br>';
}

// Project
$morehtmlref .= '<span class="opacitymedium">' . $langs->trans('Project') . ' : </span>' . $project->getNomUrl(1, '', 1);

// Author
if ($object->fk_user_creat > 0 && $userstat->fetch($object->fk_user_creat) > 0) {
    $morehtmlref .= '<br>' . $userstat->getNomUrl(-1);
}

$morehtmlref .= '</div>';

$linkback = '<a href="' . dol_escape_htmltag($linkbackUrl) . '"><strong>' . $langs->trans($linkbackLabel) . '</strong></a>';

// The previous / next arrows browse the other tasks of the same parent in the project, the tasks of the ticket
$object->next_prev_filter = '(fk_projet:=:' . (int) $object->fk_project . ') AND (fk_task_parent:=:' . (int) $object->fk_task_parent . ')';

dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';

/*
 * Left column
 */
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

// ---- Label (inline on-the-fly edit, as the subject of a ticket) ----
$labelRaw = (string) $object->label;
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">';
print '<span class="dtc-head-label"><i class="fas fa-heading dtc-field-picto"></i>' . $langs->trans('Label') . '</span>';
print '<span class="dtc-subject" data-value="' . dol_escape_htmltag($labelRaw) . '">';
print '<span class="dtc-subject-value' . ($permissionToWrite ? ' dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' tabindex="0"' : '') . '>' . dol_escape_htmltag($labelRaw) . '</span>';
print '</span>';
print '</div></td></tr>';
print '</tbody></table>';

// ---- Description (editable rich text, as the initial message of a ticket) ----
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">';
print '<i class="fas fa-align-left dtc-field-picto"></i>' . $langs->trans('Description');
if ($permissionToWrite) {
    print '<a class="dtc-head-edit editfielda dtc-message-edit" href="#" title="' . dol_escape_htmltag($langs->trans('Modify')) . '">' . img_edit($langs->trans('Modify'), 0) . '</a>';
}
print '</div></td></tr>';
print '<tr><td colspan="2">';
$descriptionClass = '';
if ($permissionToWrite) {
    $descriptionClass = ' class="dtc-message-value edit-message-on-click"'
        . ' data-raw="' . dol_escape_htmltag(dol_htmlentitiesbr((string) $object->description), 0, 1) . '"'
        . ' data-url="' . dol_escape_htmltag($url_page_current . '?id=' . $object->id) . '"'
        . ' data-empty="' . dol_escape_htmltag('<span class="opacitymedium">' . $langs->trans('None') . '</span>') . '"'
        . ' data-lang-save="' . dol_escape_htmltag($langs->transnoentities('Save')) . '"'
        . ' data-lang-cancel="' . dol_escape_htmltag($langs->transnoentities('Cancel')) . '"';
}
print '<div' . $descriptionClass . '>';
print !empty($object->description) ? dol_htmlentitiesbr($object->description) : '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
print '</div>';
print '</td></tr>';
print '</tbody></table>';

// ---- Informations of the task: dates, budget, planned workload ----
$dateStartRaw = isDolTms($object->date_start) ? dol_print_date($object->date_start, '%Y-%m-%d') : '';
$dateEndRaw   = isDolTms($object->date_end) ? dol_print_date($object->date_end, '%Y-%m-%d') : '';
$hasBudget    = !is_null($object->budget_amount) && strcmp((string) $object->budget_amount, '') !== 0;
$hasWorkload  = !is_null($object->planned_workload) && strcmp((string) $object->planned_workload, '') !== 0;

$deadlineValue = $renderInlineEditable('date_end', 'date', $dateEndRaw !== '' ? dol_print_date($object->date_end, 'day') : '', $langs->trans('Deadline'), $dateEndRaw);
if ($object->hasDelay()) {
    $deadlineValue .= ' ' . img_warning($langs->trans('Late'));
}

print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="pictoModule"') . ' ' . $langs->trans('TaskInformations') . '</div></td></tr>';
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('calendar-day') . $langs->trans('DateStart') . '</td><td>' . $renderInlineEditable('date_start', 'date', $dateStartRaw !== '' ? dol_print_date($object->date_start, 'day') : '', $langs->trans('DateStart'), $dateStartRaw) . '</td></tr>';
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('calendar-check') . $langs->trans('Deadline') . '</td><td>' . $deadlineValue . '</td></tr>';
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('euro-sign') . $langs->trans('Budget') . '</td><td>' . $renderInlineEditable('budget', 'number', $hasBudget ? price($object->budget_amount, 0, $langs, 1, -1, -1, $conf->currency) : '', $langs->trans('Budget'), $hasBudget ? (string) price2num($object->budget_amount) : '') . '</td></tr>';
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('hourglass-half') . $langs->trans('PlannedWorkload') . '</td><td>' . $renderInlineEditable('planned_workload', 'number', $hasWorkload ? convertSecondToTime((int) $object->planned_workload, 'allhourmin') : '', $langs->trans('TaskPlannedWorkloadHours'), $hasWorkload ? (string) round($object->planned_workload / 3600, 2) : '') . '</td></tr>';
print '</tbody></table>';

// ---- Attached files box (native Dolibarr showdocuments widget), as on the native task card ----
$formfileTask = new FormFile($db);
$taskFilesSub = dol_sanitizeFileName($project->ref) . '/' . dol_sanitizeFileName($object->ref);
print '<div class="dtc-files-box">';
print $formfileTask->showdocuments('project_task', $taskFilesSub, $upload_dir . '/' . $taskFilesSub, $url_page_current . '?id=' . $object->id, $user->hasRight('projet', 'lire'), $permissionToWrite, $object->model_pdf, 1, 0, 0, 0, 0, '', '', '', '', '', $object);
print '</div>';

print '</div>'; // fichehalfleft

/*
 * Right column
 */
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

// ---- Responsible and progress ----
$executives       = $object->liste_contact(-1, 'internal', 0, 'TASKEXECUTIVE');
$firstExecutiveId = 0;
foreach (is_array($executives) ? $executives : [] as $executive) {
    if ($firstExecutiveId === 0) {
        $firstExecutiveId = (int) $executive['id'];
    }
}
$contactsUrl = DOL_URL_ROOT . '/projet/tasks/contact.php?id=' . (int) $object->id . '&withproject=1';

print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $fieldPicto('user-clock') . $langs->trans('TicketResponsibleAndProgress') . '</div></td></tr>';

// Responsible (inline, on-the-fly editable)
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('user-tie') . $langs->trans('AssignedTo') . '</td>';
print '<td class="dtc-assignee" data-ticket-id="' . (int) $object->id . '" data-assign-url="' . dol_escape_htmltag($url_page_current) . '">';
print '<span class="dtc-assignee-value' . ($permissionToWrite ? ' dtc-editable dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' tabindex="0"' : '') . '>' . $printExecutives() . '</span>';
if ($permissionToWrite) {
    print '<span class="dtc-assignee-editor">';
    // forcecombo = 1 → plain <select> (no select2) so the native "change" event fires reliably.
    print $form->select_dolusers($firstExecutiveId, 'dtc_assignee_select', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'dtc-assignee-select maxwidth200', 0, 0, false, 1);
    print '</span>';
}
print '</td></tr>';

// Contributors, managed in the contacts tab of the task
$contributors     = $object->liste_contact(-1, 'internal', 0, 'TASKCONTRIBUTOR');
$contributorsHtml = '';
foreach (is_array($contributors) ? $contributors : [] as $contributor) {
    if ($userstat->fetch((int) $contributor['id']) > 0) {
        $contributorsHtml .= $userstat->getNomUrl(-1) . ' ';
    }
}
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('users') . $langs->trans('TypeContact_project_task_internal_TASKCONTRIBUTOR') . '</td><td>';
print $contributorsHtml !== '' ? trim($contributorsHtml) : '<span class="opacitymedium">' . $langs->trans('None') . '</span>';
if ($permissionToWrite) {
    print ' <a class="editfielda" href="' . dol_escape_htmltag($contactsUrl) . '" title="' . dol_escape_htmltag($langs->trans('TaskRessourceLinks')) . '">' . img_edit($langs->trans('TaskRessourceLinks'), 0) . '</a>';
}
print '</td></tr>';

// Progress (inline editable)
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('tasks') . $langs->trans('Progression') . '</td>';
print '<td class="dtc-progress" data-ticket-id="' . (int) $object->id . '" data-progress-url="' . dol_escape_htmltag($url_page_current) . '" data-value="' . (int) $object->progress . '">';
print '<span class="dtc-progress-value' . ($permissionToWrite ? ' dtc-editable dtc-tabfield' : '') . '"' . ($permissionToWrite ? ' contenteditable="true" tabindex="0"' : '') . '>' . ((int) $object->progress) . '</span> %';
print '</td></tr>';

// Time spent, against the planned workload
$timeSpent = $object->getSummaryOfTimeSpent();
$timeUrl   = DOL_URL_ROOT . '/projet/tasks/time.php?id=' . (int) $object->id . '&withproject=1';
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('stopwatch') . $langs->trans('TimeSpent') . '</td><td>';
print '<a href="' . dol_escape_htmltag($timeUrl) . '">' . convertSecondToTime((int) ($timeSpent['total_duration'] ?? 0), 'allhourmin') . '</a>';
if ($hasWorkload && (int) $object->planned_workload > 0) {
    print ' <span class="opacitymedium">(' . $langs->trans('ProgressCalculated') . ' : ' . round(((int) ($timeSpent['total_duration'] ?? 0)) / $object->planned_workload * 100, 2) . ' %)</span>';
}
print '</td></tr>';
print '</tbody></table>';

// ---- Classification (tags) + key dates ----
print '<table class="border centpercent tableforfield"><tbody>';
print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $fieldPicto('tags') . $langs->trans('TicketActionCardClassificationSection') . '</div></td></tr>';

if (isModEnabled('category')) {
    // The tags of the tasks and those of the projects, as on the kanban of the action plan and in the actions tab
    $catObj      = new Categorie($db);
    $taskTags    = digiriskdolibarr_get_actions_tags($db, [$object->id])[$object->id] ?? [];
    $assignedIds = array_column($taskTags, 'id');
    $allTags     = [];
    foreach ([Categorie::TYPE_PROJECT_TASK, Categorie::TYPE_PROJECT] as $categoryType) {
        $categories = $catObj->get_full_arbo($categoryType);
        if (is_array($categories)) {
            $allTags = array_merge($allTags, $categories);
        }
    }

    print '<tr><td class="titlefieldmiddle">' . $fieldPicto('tag') . $langs->trans('Categories') . '</td><td>';
    // Exact same markup as the tags of the ticket card, so it inherits their styling and their script
    print '<div class="kanban-card-tags" data-ticket-id="' . (int) $object->id . '" data-tag-url="' . dol_escape_htmltag($url_page_current . '?id=' . $object->id) . '">';

    foreach ($taskTags as $tag) {
        $tagBg = !empty($tag['color']) ? '#' . dol_escape_htmltag($tag['color']) : '#8c8c8c';
        print '<span class="kanban-tag" data-cat-id="' . (int) $tag['id'] . '" style="background:' . $tagBg . '">';
        print dol_escape_htmltag($tag['label']);
        if ($permissionToWrite) {
            print '<span class="kanban-tag-remove" title="' . dol_escape_htmltag($langs->trans('Remove')) . '">&times;</span>';
        }
        print '</span>';
    }

    if ($permissionToWrite) {
        print '<div class="kanban-tag-dropdown-wrapper">';
        print '<button class="kanban-add-tag-btn" title="' . dol_escape_htmltag($langs->trans('AddCategory')) . '"><i class="fas fa-tag"></i><i class="fas fa-plus"></i></button>';
        print '<div class="kanban-tag-dropdown" data-ticket-id="' . (int) $object->id . '">';
        foreach ($allTags as $tagOption) {
            $tagId = (int) ($tagOption['rowid'] ?? 0);
            if ($tagId <= 0) {
                continue;
            }
            $isAssigned = in_array($tagId, $assignedIds, true);
            $dotColor   = !empty($tagOption['color']) ? '#' . dol_escape_htmltag($tagOption['color']) : '#8c8c8c';
            print '<div class="kanban-tag-option' . ($isAssigned ? ' assigned' : '') . '" data-value="' . $tagId . '" data-color="' . dol_escape_htmltag((string) ($tagOption['color'] ?? '')) . '">';
            print '<span class="kanban-tag-dot" style="background:' . $dotColor . '"></span>';
            print dol_escape_htmltag($tagOption['label']);
            if ($isAssigned) {
                print '<i class="fas fa-check"></i>';
            }
            print '</div>';
        }
        print '</div>';
        print '</div>';
    }

    print '</div>';
    print '</td></tr>';
}

// Creation date + elapsed time
print '<tr><td class="titlefieldmiddle">' . $fieldPicto('calendar-plus') . $langs->trans('DateCreation') . '</td><td>';
print dol_print_date($object->date_c, 'dayhour', 'tzuser');
print '<span class="opacitymedium"><span class="small"> - ' . $langs->trans('TimeElapsedSince') . ': <b><i>' . convertSecondToTime(roundUpToNextMultiple($now - (int) $object->date_c, 60)) . '</i></b></span></span>';
print '</td></tr>';
print '</tbody></table>';

// ---- What the task comes from: its ticket, the risk of the action plan, the prevention plan, the accident… ----
$originRows = [];
if ($ticket !== null) {
    $originRows[] = [
        $fieldPicto('ticket-alt') . $langs->trans('Register'),
        '<a href="' . dol_escape_htmltag($ticketUrl) . '">' . img_picto('', 'ticket', 'class="pictofixedwidth"') . dol_escape_htmltag($ticket->ref) . '</a> ' . $ticket->getLibStatut(5)
            . (!empty($ticket->subject) ? '<div class="dtc-origin-detail">' . dolPrintLabel($ticket->subject) . '</div>' : ''),
    ];
}
if ($origins['risk'] !== null) {
    $risk          = $origins['risk'];
    $riskValue     = $risk->getNomUrl(1);
    $riskAssessed  = $origins['riskAssessment'];
    if ($riskAssessed !== null) {
        // Same figure as the card of the risk: the standard method shows its scale rather than its raw cotation
        $defaultCotation = [0 => '0-47', 48 => '48-50', 51 => '51-80', 100 => '81-100'];
        $riskValue      .= ' <span class="risk-evaluation-cotation dtc-origin-cotation" data-scale="' . (int) $riskAssessed->getEvaluationScale() . '">' . dol_escape_htmltag((string) ($riskAssessed->method == 'standard' ? ($defaultCotation[$riskAssessed->cotation] ?? 0) : $riskAssessed->cotation)) . '</span>';
    }
    $dangerCategoryName = $risk->getDangerCategoryName($risk, $risk->type ?: 'risk');
    if ($dangerCategoryName != -1 && $dangerCategoryName !== '') {
        $riskValue .= '<div class="dtc-origin-detail">' . dol_escape_htmltag((string) $dangerCategoryName) . '</div>';
    }
    $originRows[] = [$fieldPicto('exclamation-triangle') . $langs->trans('Risk'), $riskValue];

    if ($origins['digiriskElement'] !== null) {
        $originRows[] = [$fieldPicto('sitemap') . $langs->trans('ActionPlanElement'), $origins['digiriskElement']->getNomUrl(1, '', 0, '', -1, 1)];
    }
}
$linkedOrigins = [
    'preventionPlan'        => ['PreventionPlan', 'hard-hat'],
    'firePermit'            => ['FirePermit', 'fire-alt'],
    'accident'              => ['Accident', 'user-injured'],
    'accidentInvestigation' => ['AccidentInvestigation', 'search'],
];
foreach ($linkedOrigins as $originKey => [$labelKey, $icon]) {
    if ($origins[$originKey] !== null) {
        $originRows[] = [$fieldPicto($icon) . $langs->trans($labelKey), $origins[$originKey]->getNomUrl(1)];
    }
}

if (!empty($originRows)) {
    print '<table class="border centpercent tableforfield"><tbody>';
    print '<tr class="liste_titre trforfield"><td colspan="2"><div class="dtc-head">' . $fieldPicto('link') . $langs->trans('Origin') . '</div></td></tr>';
    foreach ($originRows as [$originLabel, $originValue]) {
        print '<tr><td class="titlefieldmiddle">' . $originLabel . '</td><td>' . $originValue . '</td></tr>';
    }
    print '</tbody></table>';
}

// ---- Status change buttons, as on the native task card — above recent events ----
if ($permissionToWrite) {
    $statusUrl = $url_page_current . '?id=' . (int) $object->id . '&action=set_task_status&token=' . newToken() . '&status=';
    print '<div class="tabsAction">';
    if ($object->status == Task::STATUS_DRAFT) {
        print '<a class="butAction" href="' . dol_escape_htmltag($statusUrl . Task::STATUS_VALIDATED) . '">' . $langs->trans('Validate') . '</a>';
    } elseif ($object->status == Task::STATUS_VALIDATED || $object->status == Task::STATUS_ONGOING) {
        print '<a class="butAction" href="' . dol_escape_htmltag($statusUrl . Task::STATUS_DRAFT) . '">' . $langs->trans('SetToDraft') . '</a>';
    }
    if ($object->status != Task::STATUS_CLOSED) {
        print '<a class="butAction" href="' . dol_escape_htmltag($statusUrl . Task::STATUS_CLOSED) . '">' . $langs->trans('Close') . '</a>';
    } else {
        print '<a class="butAction" href="' . dol_escape_htmltag($statusUrl . Task::STATUS_VALIDATED) . '">' . $langs->trans('ReOpen') . '</a>';
    }
    // What the card does not offer (project, parent task, extrafields, deletion…) stays on the native card
    print '<a class="butAction" href="' . dol_escape_htmltag(DOL_URL_ROOT . '/projet/tasks/task.php?id=' . (int) $object->id . '&withproject=1') . '">' . $langs->trans('TaskDolibarrCard') . '</a>';
    print '</div>';
}

// ---- Recent events (native Dolibarr widget) ----
$formactions = new FormActions($db);
$formactions->showactions($object, 'project_task', 0, 1, 'listactions', 5);

print '</div>'; // fichehalfright
print '</div>'; // fichecenter

print dol_get_fiche_end();

print '</div>'; // digirisk-ticket-card
