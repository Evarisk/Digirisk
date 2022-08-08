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

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("boxes", "digiriskdolibarr@digiriskdolibarr"));

// Get parameters
$action = GETPOST('action', 'aZ09');

$upload_dir = $conf->categorie->multidir_output[$conf->entity];

//Permission for digiriskelement_evaluator
$permissiontoread = $user->rights->digiriskdolibarr->lire;

// Security check
if ( ! $permissiontoread && $user->rights->ticket->read) accessforbidden();

/*
 * Actions
 */
if ( $action == 'adddashboardinfo' && $permissiontoread) {
	$data = json_decode(file_get_contents('php://input'), true);

	$serviceLabel = $data['serviceLabel'];
	$catID        = $data['catID'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO);
	$visible->$serviceLabel->$catID = 1;

	$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

if ( $action == 'closedashboardinfo' && $permissiontoread) {
	$data = json_decode(file_get_contents('php://input'), true);

	$serviceLabel = $data['serviceLabel'];
	$catID        = $data['catID'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO);
	$visible->$serviceLabel->$catID = 0;

	$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
	header('Location:' . $_SERVER['PHP_SELF']);
}

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
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboardticket" id="dashBoardTicketForm">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="view">';

	//Array that contains all WorkboardResponse classes to process them
	$dashboardlines = array();
	$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO_INITIALIZED'] = 0;

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

	if (is_array($arrayService) && !empty($arrayService)) {
		foreach ($arrayService as $service) {
			if (is_array($arrayCats) && !empty($arrayCats)) {
				foreach ($arrayCats as $key => $cat) {
					if (!empty($conf->ticket->enabled) && $user->rights->ticket->read) {
						if ($tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO_INITIALIZED'] == 0) {
							$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO'][$service->label][$cat['id']] = 1;
						}
					}
				}
			}
		}

		if ($user->conf->DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO_INITIALIZED == 0) {
			$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO_INITIALIZED'] = 1;
			$tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO'] = json_encode($tabparam['DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO']);
			dol_set_user_param($db, $conf, $user, $tabparam);
		}
	}

	$selectedDashboardInfos = json_decode($user->conf->DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO);
	if (!empty($selectedDashboardInfos)) {
		foreach ($selectedDashboardInfos as $key => $selectedDashboardInfo) {
			foreach ($selectedDashboardInfo as $keycat => $DashboardInfo) {
				if ($DashboardInfo == 0) {
					$category->fetch($keycat);
					$disable[] = $key . ' : ' . $keycat . ' : ' . $langs->transnoentities('TotalTagByService', $category->label);
				}
			}
		}
	}


		print '<div class="add-widget-box" style="'. (!empty($disable) ? '' : 'display:none').'">';
		print Form::selectarray('boxcombo', $disable, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone hideonprint add-dashboard-info', 0, 'hidden selected', 0, 1);
		if (!empty($conf->use_javascript_ajax)) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			print ajax_combobox("boxcombo");
		}
		print '</div>';

	print '<div class="fichecenter">';

	if (is_array($arrayService) && !empty($arrayService)) {
		foreach ($arrayService as $service) {
			if (is_array($arrayCats) && !empty($arrayCats)) {
				foreach ($arrayCats as $key => $cat) {
					if (!empty($conf->ticket->enabled) && $user->rights->ticket->read) {
						$dashboardlines['ticket'][$service->label][$key] = load_board($user, $cat, $service);
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
					if ($board->visible) {
						$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
						$openedDashBoard .= '<div class="info-box info-box-sm">';
						$openedDashBoard .= '<span class="info-box-icon bg-infobox-ticket">';
						$openedDashBoard .= ($board->img) ?: '<i class="fa fa-dol-ticket"></i>';
						$openedDashBoard .= '</span>';
						$openedDashBoard .= '<div class="info-box-content">';
						$openedDashBoard .= '<div class="info-box-title" title="' . strip_tags($key) . '">' . $langs->trans($key);
						$openedDashBoard .= '<span class="close-dashboard-info" data-label="'.$service->label.'" data-catid="'.$board->id.'"><i class="fas fa-times"></i></span>';
						$openedDashBoard .= '</div>';
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
				}

				print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
			}
		}
	}

	print '</div>';
	print '</form>';
}

// End of page
llxFooter();
$db->close();
