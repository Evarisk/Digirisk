<?php
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
}

/*
 * View
 */

if (empty($conf->global->MAIN_DISABLE_WORKBOARD)) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboardticket" id="dashBoardTicketForm">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="view">';

	//Array that contains all WorkboardResponse classes to process them
	$dashboardlines = array();

	// Do not include sections without management permission
	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	$digiriskelement_flatlist = $digiriskelement->fetchDigiriskElementFlat(0);
	if (is_array($digiriskelement_flatlist) && !empty($digiriskelement_flatlist)) {
		foreach ($digiriskelement_flatlist as $digiriskelementobject) {
			$digiriskelementlist[$digiriskelementobject['object']->id] = $digiriskelementobject['object'];
		}
	} else {
		print '<div class="wpeo-notice notice-info">';
		print '<div class="notice-content">';
		print '<div class="notice-subtitle"><strong>'.$langs->trans("HowToSetupDigiriskElement") . '  ' . '<a href="' . DOL_URL_ROOT . '/custom/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card?id=' . $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD . '">' . $langs->trans('ConfigDigiriskElement') . '</a></strong></div>';
		print '</div>';
		print '</div>';
		exit;
	}

	$digiriskelementlist = dol_sort_array($digiriskelementlist, 'ranks');

	$categorie = new Categorie($db);

	$allCategories = $categorie->get_all_categories('ticket');
	if (is_array($allCategories) && !empty($allCategories)) {
		foreach ($allCategories as $category) {
			$arrayCats[$category->label] = array(
				'id' => $category->id,
				'name' => $category->label,
				'photo' => show_category_image($category, $upload_dir, 1)
			);
		}
	} else {
		print '<div class="wpeo-notice notice-info">';
		print '<div class="notice-content">';
		print '<div class="notice-subtitle"><strong>'.$langs->trans("HowToSetupTicketCategories") . '  ' . '<a href=' . '"../admin/ticket/ticket.php#TicketCategories">' . $langs->trans('ConfigTicketCategories') . '</a></strong></div>';
		print '</div>';
		print '</div>';
		exit;
	}

	$selectedDashboardInfos = json_decode($user->conf->DIGIRISKDOLIBARR_TICKET_SELECTED_DASHBOARD_INFO);
	if (!empty($selectedDashboardInfos)) {
		foreach ($selectedDashboardInfos as $key => $selectedDashboardInfo) {
			foreach ($selectedDashboardInfo as $keycat => $DashboardInfo) {
				if ($DashboardInfo == 0) {
					$category->fetch($keycat);
					$digiriskelement->fetch($key);
					$disabled_services[] = $digiriskelement->id . ' : ' . $digiriskelement->ref . ' - ' . $digiriskelement->label . ' : ' . $category->id . ' : ' . '<strong>' . $category->label . '</strong>';
				}
			}
		}
	}

	print '<div class="add-widget-box" style="'. (!empty($disabled_services) ? '' : 'display:none').'">';

	print Form::selectarray('boxcombo', $disabled_services, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone hideonprint add-dashboard-info', 0, 'hidden selected', 0, 1);
	if (!empty($conf->use_javascript_ajax)) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		print ajax_combobox("boxcombo");
	}

	print '</div>';
	print '<div class="fichecenter">';

	if (GETPOST('id')) {
		$digiriskelement->fetch(GETPOST('id'));
		$service = $digiriskelement;

		if (is_array($arrayCats) && !empty($arrayCats)) {
			foreach ($arrayCats as $key => $cat) {
				if (!empty($conf->ticket->enabled) && $user->rights->ticket->read) {
					$dashboardlines['ticket'][$service->id][$key] = load_board($user, $cat, $service);
				}
			}

			print '<div class="titre inline-block">';
			print load_fiche_titre($langs->transnoentities($service->ref), '', 'service');
			print '</div>';
		}

		// Show dashboard
		if (!empty($dashboardlines)) {
			$openedDashBoard = '';
			foreach ($dashboardlines['ticket'][$service->id] as $key => $board) {
				if ($board->visible) {
					$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
					$openedDashBoard .= '<div class="info-box info-box-sm">';
					$openedDashBoard .= '<span class="info-box-icon bg-infobox-ticket">';
					$openedDashBoard .= ($board->img) ?: '<i class="fa fa-dol-ticket"></i>';
					$openedDashBoard .= '</span>';
					$openedDashBoard .= '<div class="info-box-content">';
					$openedDashBoard .= '<div class="info-box-title" title="' . strip_tags($key) . '">' . $langs->trans($key);
					$openedDashBoard .= '<span class="close-dashboard-info" data-label="'.$service->id.'" data-catid="'.$board->id.'"><i class="fas fa-times"></i></span>';
					$openedDashBoard .= '</div>';
					$openedDashBoard .= '<div class="info-box-lines">';
					$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
					$openedDashBoard .= '<span class=""><strong>' . $board->label . '</strong>';
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
	} else {
		if (is_array($digiriskelementlist) && !empty($digiriskelementlist)) {
			foreach ($digiriskelementlist as $service) {

				if (is_array($arrayCats) && !empty($arrayCats)) {
					foreach ($arrayCats as $key => $cat) {
						if (!empty($conf->ticket->enabled) && $user->rights->ticket->read) {
							$dashboardlines['ticket'][$service->id][$key] = load_board($user, $cat, $service);
						}
					}

					print '<div class="titre inline-block">';
					print load_fiche_titre($langs->transnoentities($service->ref), '', 'service');
					print '</div>';
				}

				// Show dashboard
				if (!empty($dashboardlines)) {
					$openedDashBoard = '';
					foreach ($dashboardlines['ticket'][$service->id] as $key => $board) {
						if ($board->visible) {
							$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
							$openedDashBoard .= '<div class="info-box info-box-sm">';
							$openedDashBoard .= '<span class="info-box-icon bg-infobox-ticket">';
							$openedDashBoard .= ($board->img) ?: '<i class="fa fa-dol-ticket"></i>';
							$openedDashBoard .= '</span>';
							$openedDashBoard .= '<div class="info-box-content">';
							$openedDashBoard .= '<div class="info-box-title" title="' . strip_tags($key) . '">' . $langs->trans($key);
							$openedDashBoard .= '<span class="close-dashboard-info" data-label="'.$service->id.'" data-catid="'.$board->id.'"><i class="fas fa-times"></i></span>';
							$openedDashBoard .= '</div>';
							$openedDashBoard .= '<div class="info-box-lines">';
							$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
							$openedDashBoard .= '<span class=""><strong>' . $board->label . '</strong>';
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
	}
	print '</div>';
	print '</form>';
}
