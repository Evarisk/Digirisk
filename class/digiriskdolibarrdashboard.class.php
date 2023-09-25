<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskdolibarrdashboard.class.php
 * \ingroup digiriskdolibarr
 * \brief   Class file for manage DigiriskDolibarrDashboard.
 */

require_once __DIR__ . '/digiriskstats.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/digirisktask.class.php';
require_once __DIR__ . '/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/evaluator.class.php';
require_once __DIR__ . '/digiriskresources.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturnedashboard.class.php';

/**
 * Class for DigiriskDolibarrDashboard.
 */
class DigiriskDolibarrDashboard extends SaturneDashboard
{
	const DASHBOARD_RISKASSESSMENTDOCUMENT = 0;
	const DASHBOARD_ACCIDENT = 1;
	const DASHBOARD_EVALUATOR = 2;
	const DASHBOARD_ACCIDENT_INDICATOR_RATE = 3;
	const DASHBOARD_DIGIRISKRESOURCES = 4;
	/**
	 * @var DoliDB Database handler.
	 */
	public DoliDB $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Load dashboard info.
	 *
	 * @param int $loadRiskassementdocument
	 * @param int $loadAccident
	 * @param int $loadEvaluator
	 * @param int $loadDigiriskResources
	 * @param int $loadRisk
	 * @param int $loadTask
	 * @return array
	 * @throws Exception
	 */
	public function load_dashboard($moreParams = []): array
	{
		$loadRiskAssessmentDocument = array_key_exists('loadRiskAssessmentDocument', $moreParams) ? $moreParams['loadRiskAssessmentDocument'] : 1;
		$loadAccident = array_key_exists('loadAccident', $moreParams) ? $moreParams['loadAccident'] : 1;
		$loadEvaluator = array_key_exists('loadEvaluator', $moreParams) ? $moreParams['loadEvaluator'] : 1;
		$loadDigiriskResources = array_key_exists('loadDigiriskResources', $moreParams) ? $moreParams['loadDigiriskResources'] : 1;
		$loadRisk = array_key_exists('loadRisk', $moreParams) ? $moreParams['loadRisk'] : 1;
		$loadTask = array_key_exists('loadTask', $moreParams) ? $moreParams['loadTask'] : 1;

		$risk                 = new Risk($this->db);
		$digirisktask         = new DigiriskTask($this->db);
		$accident             = new Accident($this->db);
		$riskassementdocument = new RiskAssessmentDocument($this->db);
		$evaluator            = new Evaluator($this->db);
		$digiriskresources    = new DigiriskResources($this->db);

		$risk_dashboard                 = $risk->load_dashboard();
		$digiriskresources_dashboard    = $digiriskresources->load_dashboard();
		$evaluator_dashboard            = $evaluator->load_dashboard();
		$accident_dashboard             = $accident->load_dashboard();

		$riskassessmentdocument_dashboard = $riskassementdocument->load_dashboard();
		$digirisktask_dashboard           = $digirisktask->load_dashboard();

		$dashboard_data = [
			'riskassessmentdocument' => [
				'widgets' => [
					($loadRiskAssessmentDocument) ? $riskassessmentdocument_dashboard['widgets'] : []
				],
				'graphs' => [

				]
			],
			'accident' => [
				'widgets' => [
					($loadAccident) ? $accident_dashboard['widgets'] : []
				],
				'graphs' => [
					($loadAccident) ? $accident_dashboard['graphs'] : []
				]
			],
			'evaluator' => [
				'widgets' => [
					($loadEvaluator) ? $evaluator_dashboard['widgets'] : []
				],
				'graphs' => [

				]
			],
			'evaluator' => [
				'widgets' => [
					($loadEvaluator) ? $evaluator_dashboard['widgets'] : []
				],
				'graphs' => [

				]
			],
			'digiriskresources' => [
				'widgets' => [
					($loadDigiriskResources) ? $digiriskresources_dashboard['widgets'] : []
				],
				'graphs' => [

				]
			],
			'risk' => [
				'widgets' => [],
				'graphs' => [
					($loadRisk) ? $risk_dashboard['graphs'] : [],
				]
			],
			'task' => [
				'widgets' => [],
				'graphs' => [
					($loadTask) ? $digirisktask_dashboard['graphs'] : [],
				]
			],
		];

		return $dashboard_data;
	}
}
