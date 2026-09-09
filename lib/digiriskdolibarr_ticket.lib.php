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

    $array['nbTickets'] = count($array['tickets']);

    return $array;
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
