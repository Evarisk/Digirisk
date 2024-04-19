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

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/digiriskdolibarrdocuments/riskassessmentdocument.class.php';
require_once __DIR__ . '/accident.class.php';
require_once __DIR__ . '/evaluator.class.php';
require_once __DIR__ . '/digiriskresources.class.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

/**
 * Class for DigiriskDolibarrDashboard
 */
class DigiriskDolibarrDashboard
{
    /**
     * @var DoliDB Database handler
     */
    public DoliDB $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Load dashboard info
     *
     * @param array      $moreParams    Parameters for load dashboard info
     *
     * @return array     $dashboardData Return all dashboardData after load info
     * @throws Exception
     */
    public function load_dashboard(array $moreParams = []): array
    {
        global $conf;

        $loadRiskAssessmentDocument = array_key_exists('loadRiskAssessmentDocument', $moreParams) ? $moreParams['loadRiskAssessmentDocument'] : 1;
        $loadAccident               = array_key_exists('loadAccident', $moreParams) ? $moreParams['loadAccident'] : 1;
        $loadEvaluator              = array_key_exists('loadEvaluator', $moreParams) ? $moreParams['loadEvaluator'] : 1;
        $loadDigiriskResources      = array_key_exists('loadDigiriskResources', $moreParams) ? $moreParams['loadDigiriskResources'] : 1;
        $loadRisk                   = array_key_exists('loadRisk', $moreParams) ? $moreParams['loadRisk'] : 1;
        $loadTask                   = array_key_exists('loadTask', $moreParams) ? $moreParams['loadTask'] : 1;
        $loadDigiriskElement        = array_key_exists('loadDigiriskElement', $moreParams) ? $moreParams['loadDigiriskElement'] : 1;

        $riskAssessmentDocument = new RiskAssessmentDocument($this->db);
        $accident               = new Accident($this->db);
        $evaluator              = new Evaluator($this->db);
        $digiriskResources      = new DigiriskResources($this->db);
        $risk                   = new Risk($this->db);
        $digiriskTask           = new SaturneTask($this->db);
        $digiriskElement        = new DigiriskElement($this->db);

        $dashboardData['riskassessmentdocument'] = ($loadRiskAssessmentDocument) ? $riskAssessmentDocument->load_dashboard() : [];
        $dashboardData['accident']               = ($loadAccident) ? $accident->load_dashboard() : [];
        $dashboardData['evaluator']              = ($loadEvaluator) ? $evaluator->load_dashboard() : [];
        $dashboardData['digiriskresources']      = ($loadDigiriskResources) ? $digiriskResources->load_dashboard() : [];
        $dashboardData['digiriskelement']        = ($loadDigiriskElement) ? $digiriskElement->load_dashboard() : [];
        $dashboardData['task']                   = ($loadTask) ? $digiriskTask->load_dashboard($conf->global->DIGIRISKDOLIBARR_DU_PROJECT) : [];
        $dashboardData['risk']                   = ($loadRisk) ? $risk->load_dashboard() : [];

        return $dashboardData;
    }
}
