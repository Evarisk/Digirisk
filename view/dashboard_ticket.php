<?php
/* Copyright (C) 2022 EVARISK <dev@evarisk.com>
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
 *	\file       dashboard_ticket.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Dashboard page of Ticket
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once './../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $user, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

$upload_dir = $conf->categorie->multidir_output[$conf->entity];

// Security check
if ( ! $user->rights->digiriskdolibarr->lire && $user->rights->ticket->read) accessforbidden();

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("DashBoard"), $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("DashBoard"), '', 'digiriskdolibarr32px.png@digiriskdolibarr');

/*
 * Dashboard Ticket
 */

if (empty($conf->global->MAIN_DISABLE_WORKBOARD)) {
	//Array that contains all WorkboardResponse classes to process them
	$dashboardlines = array();

	// Do not include sections without management permission
	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	$arrayService = fetchDictionnary('c_services');

	$categorie = new Categorie($db);

	$allCategories = $categorie->get_all_categories('ticket');
	if (is_array($allCategories) && !empty($allCategories)) {
		foreach ($allCategories as $category) {
			$arrayCats[$category->label] = array(
				'id' => $category->id,
				'name' => $category->label,
				'label' => $langs->transnoentities('TotalTagByService', $category->label),
				'photo' => show_category_image($category, $upload_dir, 1)
			);
		}
	} else {
		print '<div class="wpeo-notice notice-info">';
		print '<div class="notice-content">';
		print '<div class="notice-subtitle"><strong>'.$langs->trans("HowToSetupTicketCategories") . '  ' . '<a href=' . '"../admin/ticket/ticket.php#TicketCategories">' . $langs->trans('ConfigTicketCategories') . '</a></strong></div>';
		print '</div>';
		print '</div>';
	}

	print '<div class="fichecenter">';

	if (is_array($arrayService) && !empty($arrayService)) {
		foreach ($arrayService as $service) {
			if (is_array($arrayCats) && !empty($arrayCats)) {
				foreach ($arrayCats as $key => $cat) {
					if (!empty($conf->ticket->enabled) && $user->rights->ticket->read) {
						$dashboardlines['ticket'][$service->label][$key] = load_board($cat, $service->label);
					}
				}

				print '<div class="titre inline-block">';
				print load_fiche_titre($service->label, '', 'service');
				print '</div>';
			}

			// Show dashboard
			if (!empty($dashboardlines)) {
				$openedDashBoard = '';
				foreach ($dashboardlines['ticket'][$service->label] as $key => $board) {
					$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
					$openedDashBoard .= '<div class="info-box info-box-sm">';
					$openedDashBoard .= '<span class="info-box-icon bg-infobox-ticket">';
					$openedDashBoard .= ($board->img) ?: '<i class="fa fa-dol-ticket"></i>';
					$openedDashBoard .= '</span>';
					$openedDashBoard .= '<div class="info-box-content">';
					$openedDashBoard .= '<div class="info-box-title" title="' . strip_tags($key) . '">' . $langs->trans($key) . '</div>';
					$openedDashBoard .= '<div class="info-box-lines">';
					$openedDashBoard .= '<div class="info-box-line">';
					$openedDashBoard .= '<span class="">' . $board->label;
					$openedDashBoard .= '<a href="' . $board->url . '" class="info-box-text info-box-text-a">';
					$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $board->label . $board->nbtodo . '" >' . $board->nbtodo . '</span>';
					$openedDashBoard .= '</a>';
					$openedDashBoard .= '</span>';
					$openedDashBoard .= '</div>';
					$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
					$openedDashBoard .= '</div><!-- /.info-box -->';
					$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
					$openedDashBoard .= '</div><!-- /.box-flex-item -->';
				}

				print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
			}
		}
	}

	print '</div>';
}

// End of page
llxFooter();
$db->close();
