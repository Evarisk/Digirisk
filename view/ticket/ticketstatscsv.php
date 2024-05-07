<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    view/ticket/ticketstatscsv.php
 * \ingroup digiriskdolibarr
 * \brief   Page with tickets statistics CSV
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

// Load DigiriskDolibarr librairies
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';
require_once __DIR__ . '/../../class/ticketdashboard.class.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs();

// Get parameters
$action    = GETPOST('action', 'aZ09');
$dateStart = dol_mktime(0, 0, 0, GETPOST('dateStartmonth', 'int'), GETPOST('dateStartday', 'int'), GETPOST('dateStartyear', 'int'));
$dateEnd   = dol_mktime(23, 59, 59, GETPOST('dateEndmonth', 'int'), GETPOST('dateEndday', 'int'), GETPOST('dateEndyear', 'int'));
$dateRange = GETPOST('daterange');

// Initialize technical objects
$dashboard = new ticketDashboard($db);
$category  = new Categorie($db);

// Initialize view objects
$form = new Form($db);

$upload_dir = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/ticketstats/';
dol_mkdir($upload_dir);

// Security check
$permissiontoread = $user->rights->ticket->read;
$permissionToAdd  = $user->rights->ticket->write;
saturne_check_access($permissiontoread);

/*
 * Action
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'generate_csv' && $permissionToAdd) {
        // Open a file in write mode ('w')
        $fileName = dol_print_date(dol_now(), 'dayxcard') . '_ticketstats.csv';
        $fp       = fopen($upload_dir . $fileName, 'w');

        fputcsv($fp, [$langs->transnoentities('ConcernedTimePeriod') . ' : ' . dol_print_date($dateStart) . ' ' . $langs->trans('To') . ' ' . dol_print_date($dateEnd)]);

        fputcsv($fp, []);
        fputcsv($fp, []);
        fputcsv($fp, []);

        list($data, $ticketCategoriesCounter) = $dashboard->getNbTicketByDigiriskElementAndTicketTags((!empty($dateRange) ? $dateStart : 0), (!empty($dateRange) ? $dateEnd : 0));
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
                    $category->fetch($categoryId);
                    $arrayCatWithLabels[$categoryId] = $category->label;
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
            setEventMessages($langs->trans('SuccessGenerateCSV', $fileName), []);
        } else {
            setEventMessages($langs->trans('ErrorMissingData'), [], 'errors');
        }
        $action = '';
    }
}

/*
 * View
 */

$title    = $langs->trans('TicketStatistics');
$help_url = 'FR:Module_Digirisk#Statistiques_des_tickets';

saturne_header(0, '', $title, $help_url);

print load_fiche_titre($title, '', 'ticket');

$head = ticketstats_prepare_head();
print dol_get_fiche_head($head, 'exportcsv', $langs->trans('ExportCSV'), -1);

print load_fiche_titre($langs->trans('CSVFileExport'), '', 'digiriskdolibarr@digiriskdolibarr');

print '<div class="fichecenter"><div class="fichehalfleft">';

// Show table
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="action" value="generate_csv">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Value') . '</td>';
print '</tr>';

// DateRange
$startYear = strftime('%Y', dol_now()) - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$startDay  = dol_mktime(0, 0, 0, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START'), 1, $startYear);
print '<tr><td>' . $langs->trans('DateRange') . '</td><td>';
print $langs->trans('From') . $form->selectDate((!empty($dateStart) ? $dateStart : $startDay), 'dateStart');
print $langs->trans('At') . $form->selectDate((!empty($dateEnd) ? $dateEnd : dol_now()), 'dateEnd');
print $langs->trans('UseDateRange');
print '<input type="checkbox" id="daterange" name="daterange" checked>';
print '</td></tr>';
print '</table>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th class="liste_titre center" colspan="3">';
print '<b>' . $langs->trans('GenerateCSV') . ' ' . '</b>';
print '<input style="display : none" class="button buttongen" id="form_csv_generatebutton" name="form_csv_generatebutton" type="submit" value="' . $langs->trans('Generate') . '"' . '>';
print '<label for="form_csv_generatebutton">';
print '<div class="wpeo-button button-square-40 button-blue wpeo-tooltip-event" aria-label="' . $langs->trans('Generate') . '"><i class="fas fa-file-csv button-icon"></i></div>';
print '</label>';
print '</th></tr>';

// Get list of files
$fileList = dol_dir_list($upload_dir, 'files', 0, '(\.csv)', '', 'date', SORT_DESC, 1);
if (is_array($fileList)) {
    foreach ($fileList as $file) {
        // Show file name with link to download
        print '<tr class="oddeven"><td class="minwidth200">';
        print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=digiriskdolibarr&file=' . urlencode('ticketstats/' . $file['name']) . '&entiy=' . $conf->entity . '">';
        print dol_trunc($file['name'], 150);
        print '</a></td>';

        // Show file size
        $size = (!empty($file['size']) ? $file['size'] : dol_filesize($upload_dir . '/' . $file['name']));
        print '<td>' . dol_print_size($size, 1, 1) . '</td>';

        // Show file date
        $date = (!empty($file['date']) ? $file['date'] : dol_filemtime($upload_dir . '/' . $file['name']));
        print '<td class="right">' . dol_print_date($date, 'dayhour', 'tzuser') . '</td>';
        print '</tr>';
    }
}

print '</table>';
print '</form>';

print '</div></div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
