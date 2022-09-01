<?php
/*
 *  Actions
*/

global $db;

require_once __DIR__ . '/../../class/accident.class.php';

$accident = new Accident($db);
$lastAccident = $accident->fetchAll('DESC', 'accident_date', 1, 0 );
$lastTimeAccident = dol_now() - reset ($lastAccident)->accident_date;
$lastDayWithoutAccident = abs(round($lastTimeAccident / 86400));

/*
 * View
 */

if (empty($conf->global->MAIN_DISABLE_WORKBOARD)) {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="view">';

	$dashboardLines = array();
	$dashboardLines[] = array('label' => 'Jour(s) sans accident: ', 'content' => $lastDayWithoutAccident, 'picto' => 'fas fa-user-injured');

	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	if (!empty($dashboardLines)) {
		$openedDashBoard = '';
		foreach ($dashboardLines as $dashboardLine) {
			$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
			$openedDashBoard .= '<div class="info-box info-box-sm">';
			$openedDashBoard .= '<span class="info-box-icon bg-infobox-ticket">';
			$openedDashBoard .= '<i class="' . $dashboardLine["picto"] . '"></i>';
			$openedDashBoard .= '</span>';
			$openedDashBoard .= '<div class="info-box-content">';
			$openedDashBoard .= '<div class="info-box-title" title="test">' . $dashboardLine["label"];
			$openedDashBoard .= '<span class="close-dashboard-info"><i class="fas fa-times"></i></span>';
			$openedDashBoard .= '</div>';
			$openedDashBoard .= '<div class="info-box-lines">';
			$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
			$openedDashBoard .= '<span class=""><strong>' . $dashboardLine["label"] . '</strong>';
			$openedDashBoard .= '<a href="' . '$board->url' . '" class="info-box-text info-box-text-a">';
			$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLine["label"] . $dashboardLine["content"] . '" >' . $dashboardLine["content"] . '</span>';
			$openedDashBoard .= '</a>';
			$openedDashBoard .= '</span>';
			$openedDashBoard .= '</div>';
			$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
			$openedDashBoard .= '</div><!-- /.info-box -->';
			$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
			$openedDashBoard .= '</div>';
		}

		print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
	}
}
