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

include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/digiriskelement.class.php';
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

	public function load_dashboard_risk() {
		global $langs;

		$digiriskelement = new DigiriskElement($this->db);
		$array['title'] = $langs->transnoentities('RisksRepartition');
		$array['picto'] = '<i class="fas fa-exclamation-triangle"></i>';
		$array['labels'] = array(
			1 => array(
				'label' => $langs->transnoentities('GreyRisk'),
				'color' => '#ececec'
			),
			2 => array(
				'label' => $langs->transnoentities('OrangeRisk'),
				'color' => '#e9ad4f'
			),
			3 => array(
				'label' => $langs->transnoentities('RedRisk'),
				'color' => 'e05353'
			),
			4 => array(
				'label' => $langs->transnoentities('BlackRisk'),
				'color' => '#2b2b2b'
			),
		);
		$array['data'] = $digiriskelement->getRiskAssessmentCategoriesNumber();
		if ($array['data'] < 0) {
			return -1;
		} else {
			return $array;
		}
	}

	public function load_dashboard_task() {
		global $conf, $langs;

		$task = new Task($this->db);

		$taskarray = $task->getTasksArray(0, 0, $conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
		if (is_array($taskarray) && !empty($taskarray)) {
			$array = array();
			$array['title'] = $langs->transnoentities('TasksRepartition');
			$array['picto'] = '<i class="fas fa-tasks"></i>';
			$array['labels'] = array(
				0 => array(
					'label' => $langs->transnoentities('TaskAt0Percent') . ' %',
					'color' => '#e05353'
				),
				1 => array(
					'label' => $langs->transnoentities('TaskInProgress'),
					'color' => '#e9ad4f'
				),
				2 => array(
					'label' => $langs->transnoentities('TaskAt100Percent') . ' %',
					'color' => '#47e58e'
				),
			);
			foreach ($taskarray as $tasksingle) {
				if ($tasksingle->progress == 0) {
					$array['data'][0] = $array['data'][0] + 1;
				} elseif ($tasksingle->progress > 0 && $tasksingle->progress < 100) {
					$array['data'][1] = $array['data'][1] + 1;
				} else {
					$array['data'][2] = $array['data'][2] + 1;
				}
			}
			return $array;
		} else {
			return -1;
		}
	}

	public function load_dashboard_riskassementdocument() {
		$riskassessmentdocument = new RiskAssessmentDocument($this->db);

		$filter                      = array('customsql' => "t.type='riskassessmentdocument'");
		$riskassessmentdocumentarray = $riskassessmentdocument->fetchAll('desc', 't.rowid', 1, 0, $filter, 'AND');
		if ( ! empty($riskassessmentdocumentarray) && $riskassessmentdocumentarray > 0 && is_array($riskassessmentdocumentarray)) {
			$riskassessmentdocument = array_shift($riskassessmentdocumentarray);
			$array[0] = dol_print_date($riskassessmentdocument->date_creation, 'daytext');
			$array[1] = dol_print_date(dol_time_plus_duree($riskassessmentdocument->date_creation, '1', 'y'), 'daytext');
		} else {
			$array[0] = 'N/A';
			$array[1] = 'N/A';
		}
		return $array;
	}

	public function load_dashboard_accident() {
		$accident = new Accident($this->db);

		$lastAccident = $accident->fetchAll('DESC', 'accident_date', 1, 0 );
		if (is_array($lastAccident) && !empty($lastAccident)) {
			$lastTimeAccident = dol_now() - reset($lastAccident)->accident_date;
			$array = abs(round($lastTimeAccident / 86400));
		} else {
			$array = 'N/A';
		}
		return $array;
	}
}

