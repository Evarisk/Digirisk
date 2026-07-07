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
 * @param  array     $moreParam More param (filterTicket)
 * @return array     $array     Array of tickets
 * @throws Exception
 */
function load_ticket_infos(array $moreParam = []): array
{
    // Load DigiriskDolibarr libraries
    require_once __DIR__ . '/../class/digiriskelement.class.php';

    $array = [];

    $select           = ', d.ref AS digiriskElementRef, d.label AS digiriskElementLabel';
    $moreSelects      = ['digiriskElementRef', 'digiriskElementLabel'];
    $join             = ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement AS d ON d.rowid = eft.digiriskdolibarr_ticket_service';
    $filter           = 't.fk_project = ' . getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_PROJECT') . ' AND d.status = ' . DigiriskElement::STATUS_VALIDATED . ($moreParam['filterTicket'] ?? '');
    $array['tickets'] = saturne_fetch_all_object_type('Ticket', '', '', 0, 0,  ['customsql' => $filter], 'AND', true, true, false, $join, [], $select, $moreSelects);
    if (!is_array($array['tickets']) || empty($array['tickets'])) {
        $array['tickets'] = [];
    }

    $array['nbTickets'] = count($array['tickets']);

    return $array;
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
