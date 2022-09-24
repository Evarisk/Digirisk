<?php
/* Copyright (C) 2022 EOXIA <dev@eoxia.com>
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
 *       \file       class/dashboarddigiriskstats.class.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Fichier de la classe de gestion des stats du dashboard
 */

require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/digirisktask.class.php';
require_once __DIR__ . '/digiriskdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';

/**
 *	Class to manage stats for dashboard
 */
class DashboardDigiriskStats extends DigiriskStats
{
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Load data and show dashboard.
	 *
	 * @param  int       $show_risk                  Show dashboard risk info
	 * @param  int       $show_task                  Show dashboard task info
	 * @param  int       $show_riskassementdocument  Show dashboard riskassessmentdocument info
	 * @param  int       $show_accident              Show dashboard accident info
	 * @return void
	 * @throws Exception
	 */
	public function show_dashboard($show_risk = 1, $show_task = 1, $show_riskassementdocument = 1, $show_accident = 1)
	{
		global $conf, $langs, $user;

		$WIDTH  = DolGraph::getDefaultGraphSizeForStats('width');
		$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

		$risk                 = new Risk($this->db);
		$digirisktask         = new DigiriskTask($this->db);
		$accident             = new Accident($this->db);
		$riskassementdocument = new RiskAssessmentDocument($this->db);

		$dataseries = array(
			'risk' => ($show_risk) ? $risk->load_dashboard() : -1,
			'task' => ($show_task) ? $digirisktask->load_dashboard() : -1,
			'accident' => ($show_accident) ? $accident->load_dashboard() : -1
		);

		$accidentdata             = ($show_accident) ? $accident->load_dashboard() : -1;
		$riskassementdocumentdata = ($show_riskassementdocument) ? $riskassementdocument->load_dashboard() : -1;

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="view">';

		$dashboardLines = array(
			$langs->trans('Accident') => ($show_accident) ? array(
				'label' => array($langs->trans("DayWithoutAccident"), $langs->trans("WorkStopDays")),
				'content' => array($accidentdata['daywithoutaccident'], $accidentdata['nbworkstopdays']),
				'picto' => 'fas fa-user-injured'
			) : -1,
			$langs->trans('RiskAssessmentDocument') => ($show_riskassementdocument) ? array(
				'label' => array($langs->trans("LastGenerateDate"), $langs->trans("NextGenerateDate")),
				'content' => array($riskassementdocumentdata[0], $riskassementdocumentdata[1]),
				'picto' => 'fas fa-info-circle'
			) : -1
		);

		$disableWidgetList = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);

		if (!empty($dashboardLines)) {
			foreach ($dashboardLines as $key => $dashboardLine) {
				if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
					$dashboardLinesArray[] = $key;
				}
			}
		}

		print '<div class="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
		print Form::selectarray('boxcombo', $dashboardLinesArray, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
		if (!empty($conf->use_javascript_ajax)) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			print ajax_combobox("boxcombo");
		}
		print '</div>';
		print '<div class="fichecenter">';

		if (!empty($dashboardLines)) {
			$openedDashBoard = '';
			foreach ($dashboardLines as $key => $dashboardLine) {
				if (!isset($disableWidgetList->$key) && is_array($dashboardLine) &&!empty($dashboardLine)) {
					$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
					$openedDashBoard .= '<div class="info-box info-box-sm">';
					$openedDashBoard .= '<span class="info-box-icon">';
					$openedDashBoard .= '<i class="' . $dashboardLine["picto"] . '"></i>';
					$openedDashBoard .= '</span>';
					$openedDashBoard .= '<div class="info-box-content">';
					$openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans("Close") . '">';
					$openedDashBoard .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
					$openedDashBoard .= '</div>';
					$openedDashBoard .= '<div class="info-box-lines">';
					$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
					for ($i = 0; $i < count($dashboardLine['label']); $i++) {
						$openedDashBoard .= '<span class=""><strong>' . $dashboardLine["label"][$i] . ' : ' . '</strong>';
						$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLine["label"][$i]  . ' ' . $dashboardLine["content"][$i]  . '" >' . $dashboardLine["content"][$i]  . '</span>';
						$openedDashBoard .= '</span>';
						$openedDashBoard .= '<br>';
					}
					$openedDashBoard .= '</div>';
					$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
					$openedDashBoard .= '</div><!-- /.info-box -->';
					$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
					$openedDashBoard .= '</div>';
				}
			}
			print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
		}

		print '<div class="box-flex-container">';

		if (is_array($dataseries) && !empty($dataseries)) {
			foreach ($dataseries as $keyelement => $datagraph['data']) {
				if (is_array($datagraph['data']['data']) && !empty($datagraph['data']['data'])) {
					foreach ($datagraph['data']['data'] as $datagraphsingle) {
						$nbdata += $datagraphsingle;
					}
					if ($nbdata > 0) {
						$arraykeys = array_keys($datagraph['data']['data']);
						foreach ($arraykeys as $key) {
							$data[$keyelement][] = array(
								0 => $langs->trans($datagraph['data']['labels'][$key]['label']),
								1 => $datagraph['data']['data'][$key]
							);
							$datacolor[$keyelement][] = $langs->trans($datagraph['data']['labels'][$key]['color']);
						}

						$filename[$keyelement] = $keyelement . '.png';
						$fileurl[$keyelement]  = DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&file=' . $keyelement . '.png';

						$graph = new DolGraph();
						$graph->SetData($data[$keyelement]);
						$graph->SetDataColor($datacolor[$keyelement]);
						$graph->SetType(array('pie'));
						$graph->SetWidth($WIDTH);
						$graph->SetHeight($HEIGHT);
						$graph->setShowLegend(2);
						$graph->draw($filename[$keyelement], $fileurl[$keyelement]);
						print '<div class="box-flex-item">';
						print '<div class="titre inline-block">';
						print $datagraph['data']['picto'] . ' ' . $datagraph['data']['title'];
						print '</div>';
						print $graph->show();
						print '</div>';
					}
				}
			}
		}

		print '</div></div></div>';
		print '</form>';
	}
}

