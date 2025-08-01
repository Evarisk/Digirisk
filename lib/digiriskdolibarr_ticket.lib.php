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
