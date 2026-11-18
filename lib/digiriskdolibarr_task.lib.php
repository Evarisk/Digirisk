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
 * \file    lib/digiriskdolibarr_task.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Digirisk card of the tasks (view/task/task_card.php), opened by every link to a task in Digirisk instead of
 *          /projet/tasks/task.php: its URL, its tabs, what the task comes from and the history of its changes.
 */

/**
 * Return the URL of the Digirisk card of a task
 *
 * @param  int    $taskId Task ID
 * @param  int    $type   Type of URL of dol_buildpath(): 1 relative to the root, 2 with the domain of the main instance
 * @return string         URL of the card
 */
function digiriskdolibarr_task_card_url(int $taskId, int $type = 1): string
{
    return dol_buildpath('/digiriskdolibarr/view/task/task_card.php', $type) . '?id=' . $taskId;
}

/**
 * Return the link to a task, as getNomUrl() prints it (picto, tooltip), but leading to its Digirisk card
 *
 * @param  Task   $task      Task, fetched
 * @param  int    $withpicto 0 = ref only, 1 = picto and ref, 2 = picto only
 * @param  int    $addlabel  0 = ref only, 1 = ref and label, n > 1 = ref and label cut to n characters
 * @param  int    $notooltip 1 to leave the tooltip out
 * @return string            HTML link
 */
function digiriskdolibarr_task_get_nom_url(Task $task, int $withpicto = 0, int $addlabel = 0, int $notooltip = 0): string
{
    $nomUrl = $task->getNomUrl($withpicto, '', 'task', $addlabel, ' - ', $notooltip);

    return (string) preg_replace('#href="[^"]*/projet/tasks/task\.php\?id=' . (int) $task->id . '(?:&[^"]*)?"#', 'href="' . dol_escape_htmltag(digiriskdolibarr_task_card_url((int) $task->id)) . '"', $nomUrl, 1);
}

/**
 * Prepare the tabs of the Digirisk card of a task
 *
 * The native tabs of the task, with the "Task" one leading back to the Digirisk card instead of /projet/tasks/task.php,
 * as digiriskdolibarr_ticket_prepare_head() does for the ticket.
 *
 * @param  Task  $task Task, with its project loaded in $task->project
 * @return array       Array of tabs
 */
function digiriskdolibarr_task_prepare_head(Task $task): array
{
    require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';

    $head = task_prepare_head($task);
    foreach ($head as $key => $tab) {
        if (isset($tab[2]) && $tab[2] === 'task_task') {
            $head[$key][0] = digiriskdolibarr_task_card_url((int) $task->id);
        }
    }

    return $head;
}

/**
 * Return what a task comes from in Digirisk: the ticket it is an action of (#4881), and the objects its extrafields
 * link it to, a risk of the action plan, a prevention plan, a fire permit, an accident and its investigation
 *
 * @param  DoliDB $db   Database handler
 * @param  Task   $task Task, fetched with its extrafields
 * @return array{ticket: Ticket|null, risk: Risk|null, riskAssessment: RiskAssessment|null, digiriskElement: DigiriskElement|null, preventionPlan: PreventionPlan|null, firePermit: FirePermit|null, accident: Accident|null, accidentInvestigation: AccidentInvestigation|null, actionType: int}
 */
function digiriskdolibarr_task_get_origins(DoliDB $db, Task $task): array
{
    require_once __DIR__ . '/digiriskdolibarr_ticket.lib.php';

    $origins = [
        'ticket'                => digiriskdolibarr_ticket_get_task_ticket($db, $task),
        'risk'                  => null,
        'riskAssessment'        => null,
        'digiriskElement'       => null,
        'preventionPlan'        => null,
        'firePermit'            => null,
        'accident'              => null,
        'accidentInvestigation' => null,
        'actionType'            => (int) ($task->array_options['options_digirisk_action_type'] ?? 0),
    ];

    $riskId = (int) ($task->array_options['options_fk_risk'] ?? 0);
    if ($riskId > 0) {
        require_once __DIR__ . '/../class/riskanalysis/risk.class.php';
        require_once __DIR__ . '/../class/riskanalysis/riskassessment.class.php';
        require_once __DIR__ . '/../class/digiriskelement.class.php';

        $risk = new Risk($db);
        if ($risk->fetch($riskId) > 0) {
            $origins['risk'] = $risk;

            // The cotation of a risk is the one of its last validated assessment, as on its card
            $riskAssessment  = new RiskAssessment($db);
            $riskAssessments = $riskAssessment->fetchFromParent($risk->id, 0, 'DESC');
            foreach (is_array($riskAssessments) ? $riskAssessments : [] as $riskAssessmentSingle) {
                if ($riskAssessmentSingle->status == RiskAssessment::STATUS_VALIDATED) {
                    $origins['riskAssessment'] = $riskAssessmentSingle;
                    break;
                }
            }

            $digiriskElement = new DigiriskElement($db);
            if ($risk->fk_element > 0 && $digiriskElement->fetch($risk->fk_element) > 0) {
                $origins['digiriskElement'] = $digiriskElement;
            }
        }
    }

    $linkedObjects = [
        'preventionPlan'        => ['fk_preventionplan', 'PreventionPlan', 'preventionplan.class.php'],
        'firePermit'            => ['fk_firepermit', 'FirePermit', 'firepermit.class.php'],
        'accident'              => ['fk_accident', 'Accident', 'accident.class.php'],
        'accidentInvestigation' => ['fk_accidentinvestigation', 'AccidentInvestigation', 'accidentinvestigation.class.php'],
    ];
    foreach ($linkedObjects as $originKey => [$extrafield, $className, $classFile]) {
        $linkedId = (int) ($task->array_options['options_' . $extrafield] ?? 0);
        if ($linkedId > 0) {
            require_once __DIR__ . '/../class/' . $classFile;

            $linkedObject = new $className($db);
            if ($linkedObject->fetch($linkedId) > 0) {
                $origins[$originKey] = $linkedObject;
            }
        }
    }

    return $origins;
}

/**
 * Log a change of a task made on its Digirisk card (#4885)
 *
 * The change of a task of a ticket is written in the history of the ticket, under the ref of the task, as its progress
 * edited from the ticket card. That of any other task is written in its own history, shown on its card.
 *
 * @param  Task        $task       Task changed
 * @param  Ticket|null $ticket     Ticket the task belongs to, from digiriskdolibarr_task_get_origins()
 * @param  string      $fieldLabel Translated label of the field
 * @param  mixed       $oldValue   Value before the change
 * @param  mixed       $newValue   Value after the change
 * @return int                     ID of the event, 0 when nothing changed or the log is off, < 0 if KO
 */
function digiriskdolibarr_task_log_field_change(Task $task, ?Ticket $ticket, string $fieldLabel, $oldValue, $newValue): int
{
    global $db, $user;

    require_once __DIR__ . '/digiriskdolibarr_ticket.lib.php';

    if ($ticket !== null) {
        return digiriskdolibarr_ticket_log_field_change($ticket, $fieldLabel . ' — ' . $task->ref, $oldValue, $newValue, 'DIGIRISKDOLIBARR_TICKET_LOG_MODIFICATIONS');
    }

    $old = digiriskdolibarr_ticket_log_value($oldValue);
    $new = digiriskdolibarr_ticket_log_value($newValue);
    if ($old === $new || empty($task->id)) {
        return 0;
    }

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

    $detail = $fieldLabel . ' : ' . $old . ' → ' . $new;

    $actioncomm = new ActionComm($db);
    $actioncomm->type_code = 'AC_OTH_AUTO';
    $actioncomm->code      = 'AC_TASK_MODIFY';
    // actioncomm.label is a varchar(255): keep the title short, the full detail lives in the note.
    $actioncomm->label        = dol_trunc($detail, 250);
    $actioncomm->note_private = $detail;
    $actioncomm->fk_element   = $task->id;
    $actioncomm->elementtype  = 'project_task';
    $actioncomm->fk_project   = (int) $task->fk_project;
    $actioncomm->datep        = dol_now();
    $actioncomm->userownerid  = $user->id;
    $actioncomm->percentage   = -1;

    return $actioncomm->create($user);
}
