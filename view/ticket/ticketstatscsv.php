<?php
/* Copyright (C) 2022 EOXIA <technique@evarisk.com>
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
 *	    \file       view/ticket/ticketstatscsv.php
 *      \ingroup    digiriskdolibarr
 *		\brief      Page with tickets statistics CSV
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Global variables definitions
global $conf, $db, $langs, $user;

// Libraries
include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../class/ticketdigiriskstats.class.php';

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$stats = new TicketDigiriskStats($db);

// Security check
$permissiontoread = $user->rights->ticket->read;

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity];
$upload_dir = $upload_dir . '/ticketstats/';
dol_mkdir($upload_dir);

saturne_check_access($permissiontoread);

/*
 * Action
 */

if ($action == 'generate_csv') {
    $categorie = new Categorie($db);
	// Open a file in write mode ('w')
	$now = dol_now();
	$filename = dol_print_date($now, 'dayxcard') . '_ticketstats.csv';

	$date_start = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
	$date_end   = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
	$daterange  = GETPOST('daterange');

	$fp = fopen($upload_dir . $filename, 'w');

	list($data, $ticketCategoriesCounter) = $stats->getNbTicketByDigiriskElementAndTicketTags((!empty($daterange) ? $date_start : 0), (!empty($daterange) ? $date_end : 0));

    fputcsv($fp, [$langs->transnoentities('ConcernedTimePeriod') . ' : ' . dol_print_date($date_start) . ' ' . $langs->trans('To') . ' ' . dol_print_date($date_end)]);

    fputcsv($fp, []);
    fputcsv($fp, []);
    fputcsv($fp, []);

    if (is_array($ticketCategoriesCounter) && !empty($ticketCategoriesCounter)) {
        foreach($ticketCategoriesCounter as $ticketCategoryName => $ticketCategoryCounter) {
            fputcsv($fp, [$ticketCategoryName => $ticketCategoryName . ' : ' . $ticketCategoryCounter]);
        }
    }

    fputcsv($fp, []);
    fputcsv($fp, []);
    fputcsv($fp, []);

    if (is_array($data) && !empty($data)) {

		// Loop through file pointer and a line
		$arrayCat = array_keys($data['labels']);

        foreach($arrayCat as $categoryId) {
            if (is_int($categoryId)) {
                $categorie->fetch($categoryId);
                $arrayCatWithLabels[$categoryId] = $categorie->label;
            } else {
                $arrayCatWithLabels[$categoryId] = $categoryId;
            }
        }

        unset($data['labels']);

		array_unshift($arrayCatWithLabels, $langs->trans('GP/UT'));
		fputcsv($fp, $arrayCatWithLabels);
		$i = 0;
		foreach ($data as $row) {
			array_unshift($row, array_keys($data)[$i]);
			fputcsv($fp, $row);
			$i++;
		}

		fclose($fp);
		setEventMessages($langs->trans('SuccessGenerateCSV', $filename), null);
	} else {
		setEventMessages($langs->trans('ErrorMissingData'), null, 'errors');
	}
	$action = '';
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("TicketStatistics");
$help_url = 'FR:Module_Digirisk#Statistiques_des_tickets';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($title, '', 'ticket');

$head = ticketstats_prepare_head();
print dol_get_fiche_head($head, 'exportcsv', $langs->trans("ExportCSV"), -1);

print '<div class="fichecenter"><div class="fichehalfleft">';

// Get list of files
if ( ! empty($upload_dir)) {
	$file_list = dol_dir_list($upload_dir, 'files', 0, '(\.csv)', '', 'date', SORT_DESC, 1);
}

print load_fiche_titre($langs->trans("CSVFileExport"), '', 'digiriskdolibarr@digiriskdolibarr');

// Show table
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="form_csv">';
print '<input type="hidden" name="action" value="generate_csv">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<div class="div-table-responsive-no-min">';
print '<table class="liste noborder centpercent">';

//DateRange -- Plage de date
if (!empty($conf->global->SOCIETE_FISCAL_MONTH_START)) {
	$startday = dol_mktime(0, 0, 0, $conf->global->SOCIETE_FISCAL_MONTH_START, 1, strftime("%Y", dol_now()));
} else {
	$startday = dol_now();
}
print '<tr><td colspan="2">' . $langs->trans("DateRange") . '</td><td class="right">';
print $langs->trans('From') . $form->selectDate($startday, 'datestart', 0, 0, 0, '', 1);
print $langs->trans('At') . $form->selectDate(dol_time_plus_duree($startday, 1, 'y'), 'dateend', 0, 0, 0, '', 1);
print '</td></tr>';

print '<tr class="liste_titre">';
// Button
print '<th class="liste_titre center" colspan="3">';
print '<b>' . $langs->trans('GenerateCSV') . ' ' . '</b>';
print '<input style="display : none" class="button buttongen" id="form_csv_generatebutton" name="form_csv_generatebutton" type="submit" value="' . $langs->trans('Generate') . '"' . '>';
print '<label for="form_csv_generatebutton">';
print '<div class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" aria-label="' . $langs->trans('Generate') . '"><i class="fas fa-file-csv button-icon"></i></div>';
print '</label>';
print ' ' . $langs->trans('UseDateRange') . ' ';
print '<input type="checkbox" id="daterange" name="daterange"' . (GETPOST('daterange') ? ' checked=""' : '') . '>';
print '</th>';
print '</tr>';

// Get list of files
if ( ! empty($upload_dir)) {
	// Loop on each file found
	if (is_array($file_list)) {
		foreach ($file_list as $file) {
			// Show file name with link to download
			print '<tr class="oddeven">';
			print '<td class="minwidth200">';
			print '<a class="documentdownload paddingright" href="' . DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=' . urlencode('ticketstats/'.$file['name']) . '&entiy='.$conf->entity . '">';
			print  img_mime($file["name"], $langs->trans("File") . ': ' . $file["name"]);
			print  dol_trunc($file["name"], 150);
			print  '</a>';
			print  '</td>';

			// Show file size
			$size = (!empty($file['size']) ? $file['size'] : dol_filesize($upload_dir . "/" . $file["name"]));
			print '<td class="nowrap right">' . dol_print_size($size, 1, 1) . '</td>';

			// Show file date
			$date = (!empty($file['date']) ? $file['date'] : dol_filemtime($upload_dir . "/" . $file["name"]));
			print '<td class="nowrap right">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';
			print '</tr>';
		}
	}
}

print '</table>';
print '</div>';
print '</form>';

print '</div>';
print '</div>';
// End of page
print dol_get_fiche_end();
llxFooter();
$db->close();
