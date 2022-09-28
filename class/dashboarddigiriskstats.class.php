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

require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/digirisktask.class.php';
require_once __DIR__ . '/digiriskdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/evaluator.class.php';
require_once __DIR__ . '/digiriskresources.class.php';

/**
 *	Class to manage stats for dashboard
 */
class DashboardDigiriskStats extends DigiriskStats
{
	const DASHBOARD_RISKASSESSMENTDOCUMENT = 0;
	const DASHBOARD_ACCIDENT = 1;
	const DASHBOARD_EVALUATOR = 2;
	const DASHBOARD_ACCIDENT_INDICATOR_RATE = 3;
	const DASHBOARD_DIGIRISKRESOURCES = 4;

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
	 * Load dashboard info.
	 *
	 * @param  int       $load_risk                  Load dashboard risk info
	 * @param  int       $load_task                  Load dashboard task info
	 * @param  int       $load_riskassementdocument  Load dashboard riskassessmentdocument info
	 * @param  int       $load_accident              Load dashboard accident info
	 * @param  int       $load_evaluator             Load dashboard evaluator info
	 * @param  int       $load_digiriskresources     Load dashboard digiriskresources info
	 *
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard($load_risk = 1, $load_task = 1, $load_riskassementdocument = 1, $load_accident = 1, $load_evaluator = 1, $load_digiriskresources = 1)
	{
		$risk                 = new Risk($this->db);
		$digirisktask         = new DigiriskTask($this->db);
		$accident             = new Accident($this->db);
		$riskassementdocument = new RiskAssessmentDocument($this->db);
		$evaluator            = new Evaluator($this->db);
		$digiriskresources    = new DigiriskResources($this->db);

		$dashboard_data = array(
			'widgets' => array(
				'riskassementdocument' => ($load_riskassementdocument) ? $riskassementdocument->load_dashboard()['widgets'] : -1,
				'accident'             => ($load_accident) ? $accident->load_dashboard()['widgets'] : -1,
				'evaluator'            => ($load_evaluator) ? $evaluator->load_dashboard()['widgets'] : -1,
				'digiriskresources'    => ($load_digiriskresources) ? $digiriskresources->load_dashboard()['widgets'] : -1
			),
			'graphs' => array(
				'risk'     => ($load_risk) ? $risk->load_dashboard()['graphs'] : -1,
				'task'     => ($load_task) ? $digirisktask->load_dashboard()['graphs'] : -1,
				'accident' => ($load_accident) ? $accident->load_dashboard()['graphs'] : -1
			)
		);

		return $dashboard_data;
	}

	/**
	 * Show dashboard.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function show_dashboard()
	{
		global $conf, $langs, $user;

		$WIDTH  = DolGraph::getDefaultGraphSizeForStats('width');
		$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

		$dashboard_data = $this->load_dashboard();

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="view">';

		$disableWidgetList = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);

		if (!empty($dashboard_data['widgets'])) {
			foreach ($dashboard_data['widgets'] as $dashboardLine) {
				foreach ($dashboardLine as $key => $dashboardLinesingle) {
					if (isset($disableWidgetList->$key) && $disableWidgetList->$key == 0) {
						$dashboardLinesArray[$key] = $dashboardLinesingle['widgetName'];
					}
				}
			}
		}

		print '<div class="add-widget-box" style="' . (!empty((array)$disableWidgetList) ? '' : 'display:none') . '">';
		print Form::selectarray('boxcombo', $dashboardLinesArray, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 1, 0, 0, 'DESC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
		if (!empty($conf->use_javascript_ajax)) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			print ajax_combobox("boxcombo");
		}
		print '</div>';
		print '<div class="fichecenter">';

		if (!empty($dashboard_data['widgets'])) {
			$openedDashBoard = '';
			foreach ($dashboard_data['widgets'] as $dashboardLine) {
				foreach ($dashboardLine as $key => $dashboardLinesingle) {
					if (!isset($disableWidgetList->$key) && is_array($dashboardLinesingle) && !empty($dashboardLinesingle)) {
						$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
						$openedDashBoard .= '<div class="info-box info-box-sm">';
						$openedDashBoard .= '<span class="info-box-icon">';
						$openedDashBoard .= '<i class="' . $dashboardLinesingle["picto"] . '"></i>';
						$openedDashBoard .= '</span>';
						$openedDashBoard .= '<div class="info-box-content">';
						$openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans("Close") . '">';
						$openedDashBoard .= '<span class="close-dashboard-widget" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
						$openedDashBoard .= '</div>';
						$openedDashBoard .= '<div class="info-box-lines">';
						$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
						for ($i = 0; $i < count($dashboardLinesingle['label']); $i++) {
							$openedDashBoard .= '<span class=""><strong>' . $dashboardLinesingle["label"][$i] . ' : ' . '</strong>';
							$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLinesingle["label"][$i] . ' ' . $dashboardLinesingle["content"][$i] . '" >' . $dashboardLinesingle["content"][$i] . '</span>';
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
			}
			print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
		}

		print '<div class="box-flex-container">';

		if (is_array($dashboard_data['graphs']) && !empty($dashboard_data['graphs'])) {
			foreach ($dashboard_data['graphs'] as $keyelement => $datagraph) {
				if (is_array($datagraph['data']) && !empty($datagraph['data'])) {
					foreach ($datagraph['data'] as $datagraphsingle) {
						$nbdata += $datagraphsingle;
					}
					if ($nbdata > 0) {
						$arraykeys = array_keys($datagraph['data']);
						foreach ($arraykeys as $key) {
							$data[$keyelement][] = array(
								0 => $langs->trans($datagraph['labels'][$key]['label']),
								1 => $datagraph['data'][$key]
							);
							$datacolor[$keyelement][] = $langs->trans($datagraph['labels'][$key]['color']);
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
						print $datagraph['picto'] . ' ' . $datagraph['title'];
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

