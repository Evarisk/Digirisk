<?php
/*
 *  Actions
*/

require_once __DIR__ . '/../../class/accident.class.php';

$accident = new Accident($db);
$lastAccident = $accident->fetchAll('DESC', 'accident_date', 1, 0 );
if (is_array($lastAccident) && !empty($lastAccident)) {
	$lastTimeAccident = dol_now() - reset($lastAccident)->accident_date;
	$lastDayWithoutAccident = abs(round($lastTimeAccident / 86400));
} else {
	$lastDayWithoutAccident = 0;
}

if ($action == 'adddashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['widgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	unset($visible->$dashboardWidgetName);

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

if ($action == 'closedashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['dashboardWidgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	$visible->$dashboardWidgetName = 0;

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

/*
 * View
 */

if (empty($conf->global->MAIN_DISABLE_WORKBOARD)) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="view">';

	$dashboardLines = array();
	$dashboardLines['daywithoutaccident'] = array('label' => $langs->trans("DayWithoutAccident"), 'content' => $lastDayWithoutAccident, 'picto' => 'fas fa-user-injured');

	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	$disableWidgetList = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);

	print '<div class="add-widget-box" style="' . (empty($disableWidgetList) ? '' : 'display:none') . '">';
	print Form::selectarray('boxcombo', $dashboardLines, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
	if (!empty($conf->use_javascript_ajax)) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		print ajax_combobox("boxcombo");
	}
	print '</div>';

	if (!empty($dashboardLines)) {
		$openedDashBoard = '';
		foreach ($dashboardLines as $key => $dashboardLine) {
			if (!isset($disableWidgetList->$key)) {
				$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
				$openedDashBoard .= '<div class="info-box info-box-sm">';
				$openedDashBoard .= '<span class="info-box-icon">';
				$openedDashBoard .= '<i class="' . $dashboardLine["picto"] . '"></i>';
				$openedDashBoard .= '</span>';
				$openedDashBoard .= '<div class="info-box-content">';
				$openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans("Close") . '">';
				$openedDashBoard .= '<span class="close-dashboard-info" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
				$openedDashBoard .= '</div>';
				$openedDashBoard .= '<div class="info-box-lines">';
				$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
				$openedDashBoard .= '<span class=""><strong>' . $dashboardLine["label"] . ' ' . '</strong>';
				$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLine["label"] . ' ' . $dashboardLine["content"] . '" >' . $dashboardLine["content"] . '</span>';
				$openedDashBoard .= '</span>';
				$openedDashBoard .= '</div>';
				$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
				$openedDashBoard .= '</div><!-- /.info-box -->';
				$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
				$openedDashBoard .= '</div>';
			}
		}

		print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
	}
}
