<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiriskdolibarr_ticket.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Ticket
 */
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 * Create category
 *
 * @param	Categorie 	$category Category
 * @return 	string		Success or fail
 */
function createTicketCategory($label, $description, $color, $visible, $type, $fk_parent = 0, $photo_name = '')
{
	global $db, $user, $conf, $langs;
	$upload_dir = $conf->categorie->multidir_output[$conf->entity?:1];

	$category     = new Categorie($db);

	$category->label       = $label;
	$category->description = $description;
	$category->color       = $color;
	$category->visible     = $visible;
	$category->type        = $type;
	$category->fk_parent   = $fk_parent;
	$result                = $category->create($user);

	if ($result < 0) {
		return 0;
	}

	if (dol_strlen($photo_name) > 0) {
		global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

		$dir = $upload_dir . '/' . get_exdir($result, 2, 0, 0, $category, 'category') . $result . "/";
		$dir .= "photos/";
		if (!file_exists($dir)) {
			dol_mkdir($dir);
		}
		$origin_file = '../../img/picto_tickets/'.$photo_name;
		dol_copy($origin_file, $dir . $photo_name);
		vignette($dir . $photo_name, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
		vignette($dir . $photo_name, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
	}
	return $result;
}

/**
 * Prepare ticket card header — issue #4443.
 *
 * Reuses the native Dolibarr ticket tabs (so the Digirisk hook tabs stay) but rewrites
 * the main "Ticket" tab so it points back to the Digirisk custom card
 * (view/ticket/ticket_card.php) instead of the native /ticket/card.php. This keeps
 * navigation inside the custom UI instead of falling back to native Dolibarr.
 *
 * @param  Ticket $object Ticket object
 * @return array          Array of tabs
 */
function digiriskdolibarr_ticket_prepare_head(Ticket $object): array
{
    require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';

    $head          = ticket_prepare_head($object);
    $customCardUrl = dol_buildpath('/custom/digiriskdolibarr/view/ticket/ticket_card.php', 1) . '?id=' . (int) $object->id;

    foreach ($head as $key => $tab) {
        if (isset($tab[2]) && $tab[2] === 'tabTicket') {
            $head[$key][0] = $customCardUrl;
        }
    }

    return $head;
}

/**
 * Flatten a field value into the one-line, tag-free text used by the traceability log.
 *
 * @param  mixed $value Raw value (may be HTML, an array, a null or a scalar)
 * @return string       Single-line text, or the translated "None" when empty
 */
function digiriskdolibarr_ticket_log_value($value): string
{
    global $langs;

    if (is_array($value)) {
        $value = implode(', ', $value);
    }

    $text = trim(preg_replace('/\s+/', ' ', dol_string_nohtmltag((string) $value)));

    return $text !== '' ? $text : $langs->transnoentities('None');
}

/**
 * Log a ticket modification as an automatic agenda event — issue #4885.
 *
 * The event is written by hand rather than left to the native TICKET_MODIFY trigger: that
 * trigger only fires when the matching MAIN_AGENDA_ACTIONAUTO_* option is on, and it never
 * says which field changed. The ActionComm is attached to the ticket, so it shows up both in
 * the "5 derniers événements" widget and in the card conversation timeline.
 *
 * @param  Ticket $ticket       Ticket the change belongs to
 * @param  string $label        Short event title (actioncomm.label, shown in the widget)
 * @param  string $note         Full detail (actioncomm.note_private); falls back to $label
 * @param  string $constName    Constant gating the log; an empty name always logs
 * @param  int    $constDefault Value assumed when the constant has never been set
 * @return int                  <0 KO, 0 not logged, >0 id of the created event
 */
function digiriskdolibarr_ticket_log_event(Ticket $ticket, string $label, string $note = '', string $constName = '', int $constDefault = 1): int
{
    global $db, $user;

    if (empty($ticket->id) || trim($label) === '') {
        return 0;
    }
    if ($constName !== '' && getDolGlobalInt($constName, $constDefault) <= 0) {
        return 0;
    }

    require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

    $actioncomm = new ActionComm($db);
    $actioncomm->type_code = 'AC_OTH_AUTO';
    $actioncomm->code      = 'AC_TICKET_MODIFY';
    // actioncomm.label is a varchar(255): keep the title short, the full detail lives in the note.
    $actioncomm->label        = dol_trunc($label, 250);
    $actioncomm->note_private = $note !== '' ? $note : $label;
    $actioncomm->fk_element   = $ticket->id;
    $actioncomm->elementtype  = 'ticket';
    $actioncomm->fk_project   = (int) ($ticket->fk_project ?? 0);
    $actioncomm->socid        = (int) ($ticket->socid ?? 0);
    $actioncomm->datep        = dol_now();
    $actioncomm->userownerid  = $user->id;
    $actioncomm->percentage   = -1;

    return $actioncomm->create($user);
}

/**
 * Resolve a ticket extrafield's translated label and printable value for the traceability log.
 *
 * Ids stored by sellist/chkbxlst/link fields (GP/UT, …) are resolved to their human label, and
 * timestamps to a formatted date, so the history reads "GP/UT : GP1 - Siège → GP4 - Atelier"
 * instead of "12 → 15".
 *
 * @param  string $key   Extrafield key, without the "options_" prefix
 * @param  mixed  $value Raw stored value
 * @return array         [translated label, printable value]
 */
function digiriskdolibarr_ticket_extrafield_log_parts(string $key, $value): array
{
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    static $extrafields = null;
    if ($extrafields === null) {
        $extrafields = new ExtraFields($db);
        $extrafields->fetch_name_optionals_label('ticket');
    }

    $attributes = $extrafields->attributes['ticket'] ?? [];
    $label      = $langs->transnoentities($attributes['label'][$key] ?? $key);
    $type       = (string) ($attributes['type'][$key] ?? 'varchar');

    if (in_array($type, ['date', 'datetime'], true)) {
        $timestamp = is_numeric($value) ? (int) $value : (int) strtotime((string) $value);

        return [$label, $timestamp > 0 ? dol_print_date($timestamp, $type === 'date' ? 'day' : 'dayhour', 'tzuser') : ''];
    }

    if (in_array($type, ['sellist', 'chkbxlst', 'link', 'select', 'checkbox', 'radio', 'boolean'], true)) {
        return [$label, $extrafields->showOutputField($key, $value, '', 'ticket')];
    }

    return [$label, $value];
}

/**
 * Log a "field : old value -> new value" ticket modification — issue #4885.
 *
 * Nothing is written when the flattened values match, so a save that did not actually change
 * anything (re-opening an inline editor and leaving it) never pollutes the history.
 *
 * @param  Ticket $ticket       Ticket the change belongs to
 * @param  string $fieldLabel   Translated field name
 * @param  mixed  $oldValue     Value before the change
 * @param  mixed  $newValue     Value after the change
 * @param  string $constName    Constant gating the log; an empty name always logs
 * @param  int    $constDefault Value assumed when the constant has never been set
 * @return int                  <0 KO, 0 not logged, >0 id of the created event
 */
function digiriskdolibarr_ticket_log_field_change(Ticket $ticket, string $fieldLabel, $oldValue, $newValue, string $constName = '', int $constDefault = 1): int
{
    $old = digiriskdolibarr_ticket_log_value($oldValue);
    $new = digiriskdolibarr_ticket_log_value($newValue);

    if ($old === $new) {
        return 0;
    }

    $detail = $fieldLabel . ' : ' . $old . ' → ' . $new;

    return digiriskdolibarr_ticket_log_event($ticket, $detail, $detail, $constName, $constDefault);
}

/**
 * Return the parent task "TKP-<ref>" the tasks and the actions of a ticket hang under (#4881)
 *
 * @param  DoliDB $db     Database handler
 * @param  Ticket $ticket Ticket
 * @return int            ID of the parent task, 0 when the ticket has no project or no parent task yet
 */
function digiriskdolibarr_ticket_get_parent_task(DoliDB $db, Ticket $ticket): int
{
    if (empty($ticket->fk_project)) {
        return 0;
    }

    $resql = $db->query('SELECT rowid FROM ' . MAIN_DB_PREFIX . "projet_task WHERE ref = '" . $db->escape('TKP-' . $ticket->ref) . "' AND fk_projet = " . (int) $ticket->fk_project);
    $obj   = $resql ? $db->fetch_object($resql) : null;

    return $obj ? (int) $obj->rowid : 0;
}

/**
 * Create the parent task "TKP-<ref>" of a ticket, along with the first task or action added to it
 *
 * On failure the error is shown to the user, the caller only has to redirect.
 *
 * @param  Ticket $ticket   Ticket, linked to a project
 * @param  User   $user     User creating the task
 * @param  string $logConst Constant gating the traceability event
 * @return int              ID of the parent task, <= 0 on error
 */
function digiriskdolibarr_ticket_create_parent_task(Ticket $ticket, User $user, string $logConst = ''): int
{
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

    $parentTask             = new Task($db);
    $parentTask->fk_project = $ticket->fk_project;
    $parentTask->ref        = 'TKP-' . $ticket->ref;
    $parentTask->label      = $langs->trans('Ticket') . ' ' . $ticket->ref;
    $parentTask->progress   = 0;

    $parentTaskId = (int) $parentTask->create($user);
    if ($parentTaskId <= 0) {
        setEventMessages($parentTask->error, $parentTask->errors, 'errors');
        return $parentTaskId;
    }

    $db->query('UPDATE ' . MAIN_DB_PREFIX . "projet_task SET ref = '" . $db->escape($parentTask->ref) . "' WHERE rowid = " . $parentTaskId);
    digiriskdolibarr_ticket_log_event($ticket, $langs->transnoentities('TicketTaskCreated', $parentTask->ref), $parentTask->label, $logConst);

    return $parentTaskId;
}

/**
 * Sections of the actions tab of a ticket, by digirisk_action_type
 *
 * @return array<int, array{titleKey: string, addKey: string}> Title and add-button keys of each type
 */
function digiriskdolibarr_ticket_action_sections(): array
{
    return [
        2 => ['titleKey' => 'CorrectiveActions', 'addKey' => 'AddCorrectiveAction'],
        3 => ['titleKey' => 'PreventiveActions', 'addKey' => 'AddPreventiveAction'],
    ];
}

/**
 * Return the corrective and preventive actions of a ticket (#5549)
 *
 * They are project tasks typed by digirisk_action_type, under one container task per type, itself a child of the
 * parent task "TKP-<ref>" of the ticket.
 *
 * @param  DoliDB $db           Database handler
 * @param  int    $parentTaskId Parent task of the ticket, from digiriskdolibarr_ticket_get_parent_task()
 * @return array<int, array<int, stdClass>> Actions of each type
 */
function digiriskdolibarr_ticket_get_actions(DoliDB $db, int $parentTaskId): array
{
    require_once __DIR__ . '/digiriskdolibarr_action.lib.php';

    $actionsByType = [];
    foreach (array_keys(digiriskdolibarr_ticket_action_sections()) as $type) {
        $actionsByType[$type] = digiriskdolibarr_get_actions_by_type($db, $parentTaskId, $type);
    }

    return $actionsByType;
}

/**
 * Count the actions of a ticket, done and in total, for each type
 *
 * @param  array<int, array<int, stdClass>> $actionsByType Actions of each type, from digiriskdolibarr_ticket_get_actions()
 * @return array<int, array{done: int, total: int}>        Counters of each type
 */
function digiriskdolibarr_ticket_count_actions(array $actionsByType): array
{
    $counters = [];
    foreach ($actionsByType as $type => $actions) {
        $done = 0;
        foreach ($actions as $action) {
            if ((int) $action->progress >= 100) {
                $done++;
            }
        }
        $counters[$type] = ['done' => $done, 'total' => count($actions)];
    }

    return $counters;
}

/**
 * Fields of the QQOQCCP analysis of a ticket (#5549): ticket extrafields, hidden from the native card, shown by the
 * QQOQCCP tab and its document, in the order of the method. Who is a list of users, the six others are free text.
 *
 * @return array<string, array{label: string, help: string, picto: string}> Definition of each field, by extrafield key
 */
function digiriskdolibarr_ticket_qqoqcp_fields(): array
{
    return [
        'digiriskdolibarr_qqoqcp_what'    => ['label' => 'QQOQCPWhat',    'help' => 'QQOQCPWhatHelp',    'picto' => 'fa-question'],
        'digiriskdolibarr_qqoqcp_who'     => ['label' => 'QQOQCPWho',     'help' => 'QQOQCPWhoHelp',     'picto' => 'fa-users'],
        'digiriskdolibarr_qqoqcp_where'   => ['label' => 'QQOQCPWhere',   'help' => 'QQOQCPWhereHelp',   'picto' => 'fa-map-marker-alt'],
        'digiriskdolibarr_qqoqcp_when'    => ['label' => 'QQOQCPWhen',    'help' => 'QQOQCPWhenHelp',    'picto' => 'fa-calendar-alt'],
        'digiriskdolibarr_qqoqcp_how'     => ['label' => 'QQOQCPHow',     'help' => 'QQOQCPHowHelp',     'picto' => 'fa-cogs'],
        'digiriskdolibarr_qqoqcp_howmuch' => ['label' => 'QQOQCPHowMuch', 'help' => 'QQOQCPHowMuchHelp', 'picto' => 'fa-calculator'],
        'digiriskdolibarr_qqoqcp_why'     => ['label' => 'QQOQCPWhy',     'help' => 'QQOQCPWhyHelp',     'picto' => 'fa-lightbulb'],
    ];
}

/**
 * Full names of the users of the Who field of the QQOQCP analysis
 *
 * @param  string $userIds Comma separated user ids, as stored by the chkbxlst extrafield
 * @return string[]        Full names, sorted by last name
 */
function digiriskdolibarr_ticket_qqoqcp_who_names(string $userIds): array
{
    global $db;

    $ids = array_filter(array_map('intval', explode(',', $userIds)));
    if (empty($ids)) {
        return [];
    }

    $names = [];
    $resql = $db->query('SELECT firstname, lastname, login FROM ' . MAIN_DB_PREFIX . 'user WHERE rowid IN (' . implode(',', $ids) . ') ORDER BY lastname, firstname');
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $names[] = trim($obj->firstname . ' ' . $obj->lastname) ?: (string) $obj->login;
        }
        $db->free($resql);
    }

    return $names;
}

/**
 * Number of the filled fields of the QQOQCP analysis of a ticket, for the badge of its tab
 *
 * @param  Ticket $ticket Ticket, its extrafields fetched
 * @return int            Filled fields, out of seven
 */
function digiriskdolibarr_ticket_qqoqcp_filled_count(Ticket $ticket): int
{
    $filled = 0;
    foreach (array_keys(digiriskdolibarr_ticket_qqoqcp_fields()) as $key) {
        if (trim((string) ($ticket->array_options['options_' . $key] ?? '')) !== '') {
            $filled++;
        }
    }

    return $filled;
}

/**
 * Prepare ticket statistics pages header
 *
 * @return array $head   Array of tabs
 */
function ticketstats_prepare_head(): array
{
    // Global variables definitions
    global $conf, $langs, $user;

    // Load translation files required by the page
    saturne_load_langs();

    // Initialize values
    $h    = 0;
    $head = [];

    if ($user->hasRight('ticket', 'read')) {
        $head[$h][0] = dol_buildpath('custom/digiriskdolibarr/view/ticket/ticket_management_dashboard.php', 1);
        $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-chart-line pictofixedwidth"></i>' . $langs->transnoentities('TicketManagementDashboard') : '<i class="fas fa-chart-line"></i>';
        $head[$h][2] = 'dashboard';
        $h++;

        $head[$h][0] = dol_buildpath('custom/digiriskdolibarr/view/ticket/ticketstats.php', 1);
        $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-calendar-alt pictofixedwidth"></i>'  . $langs->trans('ByMonthYear') : '<i class="fas fa-calendar-alt"></i>';
        $head[$h][2] = 'byyear';
        $h++;

        $head[$h][0] = dol_buildpath('custom/digiriskdolibarr/view/ticket/ticketstatscsv.php', 1);
        $head[$h][1] = $conf->browser->layout == 'classic' ? '<i class="fas fa-file-csv pictofixedwidth"></i>' . $langs->trans('ExportCSV') : '<i class="fas fa-file-csv"></i>';
        $head[$h][2] = 'exportcsv';
    }

    return $head;
}

/**
 * Load ticket infos
 *
 * The GP/UT a ticket belongs to used to be resolved with an INNER JOIN on the
 * digiriskdolibarr_ticket_service extrafield. That field is a chkbxlst, so it holds a
 * comma-separated list of ids: the join silently dropped every ticket carrying several
 * GP/UT and every ticket carrying none. Combined with the "d.status = validated"
 * condition, a ticket whose GP/UT had been deleted disappeared too. The elements are now
 * resolved in PHP, so a register is always listed whatever its GP/UT — issue #4459.
 *
 * @param  array     $moreParam More param (filterTicket)
 * @return array     $array     Array of tickets
 * @throws Exception
 */
function load_ticket_infos(array $moreParam = []): array
{
    $array = [];

    $filter           = 't.fk_project = ' . getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_PROJECT') . ($moreParam['filterTicket'] ?? '');
    $array['tickets'] = saturne_fetch_all_object_type('Ticket', '', '', 0, 0, ['customsql' => $filter], 'AND', true, true);
    if (!is_array($array['tickets']) || empty($array['tickets'])) {
        $array['tickets'] = [];
    }

    digiriskdolibarr_ticket_set_digirisk_elements($array['tickets']);
    digiriskdolibarr_ticket_set_status($array['tickets']);

    $array['nbTickets'] = count($array['tickets']);

    return $array;
}

/**
 * Mirror the fk_statut column onto the status property of each ticket — issue #5235
 *
 * Ticket declares its status column as fk_statut but reads it from $status everywhere,
 * Ticket::fetch() being the only place that copies one onto the other. A ticket loaded
 * through the generic setVarsFromFetchObj() of saturne_fetch_all_object_type() therefore
 * keeps a null $status, Ticket::LibStatut() matches its case 0 on that null, returns an
 * empty label, and the register lists of the documents print "N/A" in their status column.
 *
 * @param  array $tickets Tickets to complete, each one gets its status property set
 * @return void
 */
function digiriskdolibarr_ticket_set_status(array $tickets): void
{
    foreach ($tickets as $ticket) {
        if (!isset($ticket->status) && isset($ticket->fk_statut)) {
            $ticket->status = (int) $ticket->fk_statut;
        }
    }
}

/**
 * Set on each ticket the "REF - Label" list of the GP/UT it is attached to — issue #4459
 *
 * The digiriskdolibarr_ticket_service extrafield is a chkbxlst, so its raw value is a
 * comma-separated list of digirisk element ids. Every element is read in a single query,
 * deleted ones included: a register must keep naming where the event happened even once
 * the GP/UT is gone.
 *
 * @param  array $tickets Tickets to complete, each one gets a digiriskElementRefLabel property
 * @return void
 */
function digiriskdolibarr_ticket_set_digirisk_elements(array $tickets): void
{
    global $db;

    $elementIds = [];
    foreach ($tickets as $ticket) {
        $ticket->digiriskElementRefLabel = '';
        foreach (digiriskdolibarr_ticket_service_ids($ticket) as $elementId) {
            $elementIds[$elementId] = $elementId;
        }
    }

    if (empty($elementIds)) {
        return;
    }

    $sql   = 'SELECT rowid, ref, label FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement';
    $sql  .= ' WHERE rowid IN (' . $db->sanitize(implode(',', $elementIds)) . ')';
    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return;
    }

    $elements = [];
    while ($obj = $db->fetch_object($resql)) {
        $elements[(int) $obj->rowid] = $obj->ref . ' - ' . $obj->label;
    }
    $db->free($resql);

    foreach ($tickets as $ticket) {
        $refLabels = [];
        foreach (digiriskdolibarr_ticket_service_ids($ticket) as $elementId) {
            if (!empty($elements[$elementId])) {
                $refLabels[] = $elements[$elementId];
            }
        }

        $ticket->digiriskElementRefLabel = implode(', ', $refLabels);
    }
}

/**
 * Read the digirisk element ids stored in the chkbxlst ticket_service extrafield
 *
 * @param  object $ticket Ticket carrying the extrafield
 * @return int[]          Digirisk element ids, empty when no GP/UT is set
 */
function digiriskdolibarr_ticket_service_ids($ticket): array
{
    $rawValue = $ticket->array_options['options_digiriskdolibarr_ticket_service'] ?? '';
    if (is_array($rawValue)) {
        $rawValue = implode(',', $rawValue);
    }

    $elementIds = [];
    foreach (explode(',', (string) $rawValue) as $elementId) {
        if ((int) $elementId > 0) {
            $elementIds[] = (int) $elementId;
        }
    }

    return $elementIds;
}

/**
 * Render one conversation bubble (<li>) for the ticket card thread (V2 style).
 *
 * Shared by the initial server render and the AJAX post_message response so both
 * produce identical markup.
 *
 * @param  Translate $langs Lang object.
 * @param  Conf      $conf  Conf object.
 * @param  stdClass  $m     Normalized message: id(int), type(initial|internal|public),
 *                          mine(bool), author(string), av_uid, av_first, av_last, av_login,
 *                          av_photo, ts(int), subject(string), body_html(string),
 *                          sent_mail(bool), to(array), cc(array).
 * @param  int       $now   Current timestamp (dol_now()).
 * @return string           The <li> HTML.
 */
function digiriskdolibarr_ticket_conversation_bubble($langs, $conf, stdClass $m, int $now): string
{
    $diff = $now - (int) $m->ts;
    if ($diff < 60) {
        $rel = $langs->trans('JustNow');
    } elseif ($diff < 3600) {
        $rel = round($diff / 60) . ' min';
    } elseif ($diff < 86400) {
        $rel = round($diff / 3600) . ' h';
    } elseif ($diff < 172800) {
        $rel = $langs->trans('Yesterday');
    } else {
        $rel = dol_print_date((int) $m->ts, 'day', 'tzuser');
    }

    $initials = strtoupper(dol_substr((string) ($m->av_first ?: ($m->av_last ?: ($m->av_login ?: '?'))), 0, 1));
    if (!empty($m->av_photo) && (int) $m->av_uid > 0 && file_exists(DOL_DATA_ROOT . '/users/' . (int) $m->av_uid . '/' . $m->av_photo)) {
        $photoUrl = DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=' . (int) $conf->entity . '&file=' . urlencode((int) $m->av_uid . '/' . $m->av_photo) . '&cache=' . (int) $m->av_uid;
        $avatar   = '<img class="dtc-msg__avatar" src="' . dol_escape_htmltag($photoUrl) . '" alt="' . dol_escape_htmltag($initials) . '">';
    } else {
        $avatar   = '<span class="dtc-msg__avatar">' . dol_escape_htmltag($initials) . '</span>';
    }

    $type = in_array($m->type, ['initial', 'internal', 'public'], true) ? $m->type : 'public';
    if ($type === 'internal') {
        $typeLabel = '<i class="fas fa-lock"></i> ' . $langs->trans('PrivateMessage');
    } elseif ($type === 'initial') {
        $typeLabel = '<i class="fas fa-flag"></i> ' . $langs->trans('OriginalMessage');
    } else {
        $typeLabel = '<i class="fas fa-share"></i> ' . $langs->trans('PublicMessage');
    }

    $cls = 'dtc-msg dtc-msg--' . $type . (!empty($m->mine) ? ' dtc-msg--mine' : '');
    $out = '<li class="' . $cls . '" data-msg-id="' . (int) $m->id . '" data-msg-mine="' . (!empty($m->mine) ? '1' : '0') . '">';
    $out .= $avatar;
    $out .= '<div class="dtc-msg__card">';
    $out .= '<div class="dtc-msg__head">';
    $out .= '<span class="dtc-msg__author">' . dol_escape_htmltag((string) $m->author) . '</span>';
    $out .= '<span class="dtc-msg__type">' . $typeLabel . '</span>';
    $out .= '<span class="dtc-msg__time" title="' . dol_escape_htmltag(dol_print_date((int) $m->ts, 'dayhour', 'tzuser')) . '">' . dol_escape_htmltag($rel) . '</span>';
    $out .= '</div>';
    if ($type === 'public' && (!empty($m->to) || !empty($m->cc))) {
        $out .= '<div class="dtc-msg__to">';
        if (!empty($m->to)) {
            $out .= dol_escape_htmltag($langs->trans('ConvTo')) . ' : ';
            foreach ($m->to as $recipient) {
                if (trim((string) $recipient) !== '') {
                    $out .= '<span class="dtc-pill">' . dol_escape_htmltag(trim((string) $recipient)) . '</span> ';
                }
            }
        }
        if (!empty($m->cc)) {
            $out .= ' ' . dol_escape_htmltag($langs->trans('ConvCc')) . ' : ';
            foreach ($m->cc as $recipient) {
                if (trim((string) $recipient) !== '') {
                    $out .= '<span class="dtc-pill">' . dol_escape_htmltag(trim((string) $recipient)) . '</span> ';
                }
            }
        }
        $out .= '</div>';
    }
    if ($type === 'internal' && !empty($m->mentions)) {
        $out .= '<div class="dtc-msg__to dtc-msg__mentions"><i class="fas fa-bell"></i> ' . dol_escape_htmltag($langs->trans('Mentioned')) . ' : ';
        foreach ($m->mentions as $mentionName) {
            if (trim((string) $mentionName) !== '') {
                $out .= '<span class="dtc-pill">' . dol_escape_htmltag(trim((string) $mentionName)) . '</span> ';
            }
        }
        $out .= '</div>';
    }
    if (!empty($m->subject)) {
        $out .= '<div class="dtc-msg__subject">' . dol_escape_htmltag((string) $m->subject) . '</div>';
    }
    $out .= '<div class="dtc-msg__body" data-msg-body>' . $m->body_html . '</div>';
    if (!empty($m->file_count)) {
        $out .= '<a class="dtc-msg__files" href="' . DOL_URL_ROOT . '/comm/action/document.php?id=' . (int) $m->id . '" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> ' . dol_escape_htmltag($langs->trans('NAttachedFiles', (string) (int) $m->file_count)) . '</a>';
    }
    if (!empty($m->sent_mail)) {
        $out .= '<div class="dtc-msg__sent"><i class="fas fa-check"></i> ' . $langs->trans('SentByMail') . '</div>';
    }
    $out .= '<div class="dtc-msg__actions">';
    $out .= '<button type="button" class="dtc-msg__act" data-dtc-quote title="' . dol_escape_htmltag($langs->trans('Quote')) . '"><i class="fas fa-quote-right"></i></button>';
    if (!empty($m->mine) && $type !== 'initial') {
        $out .= '<button type="button" class="dtc-msg__act" data-dtc-edit title="' . dol_escape_htmltag($langs->trans('Modify')) . '"><i class="fas fa-pen"></i></button>';
        $out .= '<button type="button" class="dtc-msg__act dtc-msg__act--danger" data-dtc-delete title="' . dol_escape_htmltag($langs->trans('Delete')) . '"><i class="fas fa-trash"></i></button>';
    }
    $out .= '</div>';
    $out .= '</div></li>';

    return $out;
}

/**
 * Input mode of the Location field on the register form (issue #4732)
 *
 * @return string free : free text (historical behaviour), list : dictionary only,
 *                listfree : dictionary plus an "Other" entry opening a free text field
 */
function digiriskdolibarr_ticket_location_input_mode(): string
{
    $mode = getDolGlobalString('DIGIRISKDOLIBARR_TICKET_LOCATION_INPUT_MODE', 'free');

    return in_array($mode, ['free', 'list', 'listfree']) ? $mode : 'free';
}

/**
 * Locations offered by the register form, read from the c_digiriskdolibarr_ticket_location dictionary
 *
 * The declared ticket stores the label as plain text, never the row id : renaming or
 * deactivating a row must not rewrite the locations already recorded in the register.
 * The array is therefore keyed by label, which is also the posted value.
 *
 * @return array<string,string> Labels of the active rows of the entity, ordered by position
 */
function digiriskdolibarr_ticket_location_dictionary(): array
{
    global $langs;

    $locations = [];
    foreach (digiriskdolibarr_ticket_location_records() as $record) {
        // A row seeded with a translation key shows up translated, a row typed by the customer stays as typed
        $label             = $langs->transnoentities($record->label);
        $locations[$label] = $label;
    }

    return $locations;
}

/**
 * Active rows of the location dictionary, ordered by position
 *
 * saturne_fetch_dictionary() selects a fixed list of columns that leaves out fk_digiriskelement,
 * the column the GP/UT filtering of the register form is built on, hence this reader (issue #5176)
 *
 * @return stdClass[] Rows carrying rowid, label and fk_digiriskelement
 */
function digiriskdolibarr_ticket_location_records(): array
{
    global $db;

    $sql  = 'SELECT t.rowid, t.label, t.fk_digiriskelement';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_digiriskdolibarr_ticket_location as t';
    $sql .= ' WHERE t.active = 1';
    $sql .= ' AND t.entity IN (0, ' . getEntity('c_digiriskdolibarr_ticket_location') . ')';
    $sql .= $db->order('t.position', 'ASC');

    $resql = $db->query($sql);
    if (!$resql) {
        return [];
    }

    $records = [];
    while ($obj = $db->fetch_object($resql)) {
        if (!dol_strlen($obj->label)) {
            continue;
        }

        $records[] = $obj;
    }
    $db->free($resql);

    return $records;
}

/**
 * GP/UT each location of the dictionary is offered on (issue #5176)
 *
 * A location attached to a site or to a GP stays offered on everything underneath, otherwise it
 * would have to be declared again on each work unit. A location attached to nothing, or attached
 * to an element of another entity, is offered everywhere and carries an empty list.
 *
 * @return array<string,int[]> Label => digirisk element ids, empty array when offered everywhere
 */
function digiriskdolibarr_ticket_location_element_map(): array
{
    global $db, $langs;

    $records = digiriskdolibarr_ticket_location_records();
    if (empty($records)) {
        return [];
    }

    require_once __DIR__ . '/../class/digiriskelement.class.php';

    $digiriskElement  = new DigiriskElement($db);
    $digiriskElements = $digiriskElement->getActiveDigiriskElements();
    if (!is_array($digiriskElements)) {
        $digiriskElements = [];
    }

    $childrenByParent = [];
    foreach ($digiriskElements as $element) {
        $childrenByParent[(int) $element->fk_parent][] = (int) $element->id;
    }

    $map = [];
    foreach ($records as $record) {
        $label     = $langs->transnoentities($record->label);
        $elementID = (int) $record->fk_digiriskelement;

        // An element that the current entity cannot see leaves the location offered everywhere,
        // rather than filtered out of every GP/UT of that entity
        $map[$label] = isset($digiriskElements[$elementID]) ? digiriskdolibarr_element_with_descendants($elementID, $childrenByParent) : [];
    }

    return $map;
}

/**
 * An element id followed by the ids of everything below it
 *
 * @param  int               $elementID        Digirisk element to walk down from
 * @param  array<int,int[]>  $childrenByParent Element ids indexed by their parent id
 * @return int[]                               The element id and its descendants
 */
function digiriskdolibarr_element_with_descendants(int $elementID, array $childrenByParent): array
{
    $elementIDs = [$elementID];
    $toVisit    = [$elementID];

    // Walked iteratively and guarded against ids already seen : a corrupted parent chain
    // must not turn into an infinite loop on the public form
    while (!empty($toVisit)) {
        $currentID = array_pop($toVisit);
        foreach ($childrenByParent[$currentID] ?? [] as $childID) {
            if (in_array($childID, $elementIDs)) {
                continue;
            }

            $elementIDs[] = $childID;
            $toVisit[]    = $childID;
        }
    }

    return $elementIDs;
}

/**
 * Tell whether the register form must offer the dictionary for the Location field
 *
 * An empty dictionary falls back to the free text field : a list mode must never
 * leave the declarant with no way to fill a required field.
 *
 * @param  array<string,string> $locations Result of digiriskdolibarr_ticket_location_dictionary()
 * @return bool                            True when the select must be rendered
 */
function digiriskdolibarr_ticket_location_use_list(array $locations): bool
{
    return !empty($locations) && digiriskdolibarr_ticket_location_input_mode() !== 'free';
}

/**
 * Modèles d'email utilisables pour un ticket, pour un sélecteur de configuration — issue #5235
 *
 * Même périmètre que le socle : les types de modèle ticket, ticket_send et all, actifs et
 * visibles depuis l'entité courante. L'entrée vide est celle qui conserve le contenu écrit
 * en dur, de sorte qu'une configuration jamais touchée ne change rien aux envois en place.
 *
 * @return array Libellé du modèle en clé, libellé affiché en valeur
 */
function digiriskdolibarr_ticket_mail_models(): array
{
    global $db, $langs;

    $mailModels = ['' => $langs->transnoentities('TicketSubmittedMailModelDefault')];

    $sql  = 'SELECT label, lang FROM ' . MAIN_DB_PREFIX . 'c_email_templates';
    $sql .= " WHERE type_template IN ('ticket', 'ticket_send', 'all')";
    $sql .= ' AND entity IN (' . getEntity('c_email_templates') . ')';
    $sql .= ' AND active = 1';
    $sql .= $db->order('position, label', 'ASC, ASC');

    $resql = $db->query($sql);
    if (!$resql) {
        dol_syslog(__FUNCTION__ . ' ' . $db->lasterror(), LOG_ERR);
        return $mailModels;
    }

    while ($obj = $db->fetch_object($resql)) {
        if (empty($obj->label)) {
            continue;
        }
        $mailModels[$obj->label] = $obj->label . (!empty($obj->lang) ? ' (' . $obj->lang . ')' : '');
    }
    $db->free($resql);

    return $mailModels;
}

/**
 * Whether the public ticket interface of the entity may be used by the visitor (#5549)
 *
 * Without a PIN code set for the entity, everyone may declare. With one, only the session that typed it: the
 * session keeps a hash of the code it unlocked, so changing the code locks the interface again for everyone.
 *
 * @param  int  $entity Entity of the public interface
 * @return bool         True when the declaration form may be shown and its actions processed
 */
function digiriskdolibarr_ticket_public_pin_unlocked(int $entity): bool
{
    $pin = getDolGlobalString('DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_PIN');
    if ($pin === '') {
        return true;
    }

    return hash_equals(hash('sha256', $pin), (string) ($_SESSION['digiriskdolibarr_ticket_public_pin'][$entity] ?? ''));
}

/**
 * Check the PIN code typed on the public ticket interface, and unlock it for the session when it matches
 *
 * At most five wrong codes per quarter of an hour and per session, each one answered a second later, so the code
 * cannot be found by trying them all.
 *
 * @param  string $pin    Code typed by the visitor
 * @param  int    $entity Entity of the public interface
 * @return int            1 unlocked, 0 wrong code, < 0 too many attempts: minutes left to wait, as a negative
 */
function digiriskdolibarr_ticket_public_pin_check(string $pin, int $entity): int
{
    $now         = dol_now();
    $lockSeconds = 900;
    $attempts    = array_values(array_filter((array) ($_SESSION['digiriskdolibarr_ticket_public_pin_attempts'] ?? []), static function ($attempt) use ($now, $lockSeconds): bool {
        return (int) $attempt > $now - $lockSeconds;
    }));
    if (count($attempts) >= 5) {
        $_SESSION['digiriskdolibarr_ticket_public_pin_attempts'] = $attempts;

        return -max(1, (int) ceil((min($attempts) + $lockSeconds - $now) / 60));
    }

    $expected = getDolGlobalString('DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_PIN');
    if ($expected !== '' && hash_equals($expected, $pin)) {
        $_SESSION['digiriskdolibarr_ticket_public_pin'][$entity] = hash('sha256', $expected);
        unset($_SESSION['digiriskdolibarr_ticket_public_pin_attempts']);

        return 1;
    }

    $attempts[] = $now;
    $_SESSION['digiriskdolibarr_ticket_public_pin_attempts'] = $attempts;
    sleep(1);

    return 0;
}

/**
 * Address of the public ticket interface the documents send people to
 *
 * The interface shared between entities comes first when Multicompany is enabled, the one of the entity
 * otherwise, the order the documents always picked their QR code in. Within an interface, the address
 * chosen in the configuration of the tickets (origin, short or external) is used, the origin one when the
 * chosen one is empty, and the address of this server when nothing was ever saved.
 *
 * @return string Full URL
 */
function digiriskdolibarr_ticket_public_interface_url(): string
{
    global $conf;

    $publicInterfaceTypes = isModEnabled('multicompany') ? ['multicompany', 'current'] : ['current'];
    foreach ($publicInterfaceTypes as $publicInterfaceType) {
        $constPrefix = 'DIGIRISKDOLIBARR_TICKET_' . dol_strtoupper($publicInterfaceType) . '_PUBLIC_INTERFACE_';
        foreach (['origin', 'short', 'external'] as $urlType) {
            $url = getDolGlobalString($constPrefix . 'URL_' . dol_strtoupper($urlType));
            if (getDolGlobalString($constPrefix . 'RADIO') == $urlType . dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL' && dol_strlen($url)) {
                return $url;
            }
        }
        if (getDolGlobalString($constPrefix . 'URL_ORIGIN')) {
            return getDolGlobalString($constPrefix . 'URL_ORIGIN');
        }
    }

    return dol_buildpath('/digiriskdolibarr/public/ticket/create_ticket.php', 2) . '?entity=' . $conf->entity;
}

/**
 * QR code of the public ticket interface, drawn for the document being generated
 *
 * Drawn again for every document from the address configured at that moment: a code stored once kept
 * pointing to an address changed since, and a document generated before anyone pressed the button of
 * the configuration printed N/A in its place. The ODT templates resize the image in place to fit their
 * cell, a reason more not to keep it.
 *
 * @param  string $url Address to encode
 * @return string      Path of the PNG image, empty when it could not be drawn
 */
function digiriskdolibarr_ticket_qrcode_file(string $url): string
{
    global $conf;

    require_once __DIR__ . '/digiriskdolibarr_mobile.lib.php';

    $filePath = $conf->digiriskdolibarr->multidir_output[$conf->entity ?: 1] . '/temp/ticketqrcode.png';

    return digiriskGetQrCodePng($url, $filePath) ? $filePath : '';
}

/**
 * Files of the ticket directory a mail can carry (#4650): public interface photos, generated documents,
 * attachments of earlier messages. Most recent first
 *
 * @param  Ticket $ticket Ticket
 * @return array          dol_dir_list() entries, size included
 */
function digiriskdolibarr_ticket_files(Ticket $ticket): array
{
    global $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    return dol_dir_list($conf->ticket->dir_output . '/' . dol_sanitizeFileName($ticket->ref), 'files', 0, '', ['(\.meta|_preview.*\.png)$', '^\.'], 'date', SORT_DESC, 1);
}

/**
 * Options of the "Attach ticket files" selector, for Form::multiselectarray()
 *
 * @param  array $files Files of the ticket, from digiriskdolibarr_ticket_files()
 * @return array        Options, the escaped file name as id
 */
function digiriskdolibarr_ticket_files_options(array $files): array
{
    $options = [];
    foreach ($files as $file) {
        $fileSize  = dol_print_size($file['size'], 1, 1);
        $options[] = [
            'id'        => dol_escape_htmltag($file['name']),
            'label'     => $file['name'] . ' (' . $fileSize . ')',
            // A span, not the <i> of img_mime(): the data-html of the option only keeps a few tags.
            'labelhtml' => '<span class="fa fa-' . dol_mimetype($file['name'], '', 4) . ' pictofixedwidth"></span>' . dol_escape_htmltag($file['name']) . ' <span class="opacitymedium">' . $fileSize . '</span>',
        ];
    }

    return $options;
}

/**
 * Files of the ticket picked in the "Attach ticket files" selector
 *
 * A name is only accepted if it is in the listing of the ticket directory, so nothing outside of it can
 * be attached. The listing is keyed by the same filter GETPOST applies: it alters some names (double
 * spaces), which would otherwise drop the file without a word.
 *
 * @param  Ticket $ticket Ticket
 * @param  array  $names  Names posted by the selector, read with GETPOST(..., 'array')
 * @return array          dol_dir_list() entries of the picked files, each one once
 */
function digiriskdolibarr_ticket_linked_files(Ticket $ticket, array $names): array
{
    if (empty($names)) {
        return [];
    }

    $ticketFiles = [];
    foreach (digiriskdolibarr_ticket_files($ticket) as $ticketFile) {
        $ticketFiles[sanitizeVal($ticketFile['name'], 'alphanohtml')] = $ticketFile;
    }

    $linkedFiles = [];
    foreach ($names as $name) {
        if (is_string($name) && isset($ticketFiles[$name])) {
            $linkedFiles[$name] = $ticketFiles[$name];
        }
    }

    return array_values($linkedFiles);
}
