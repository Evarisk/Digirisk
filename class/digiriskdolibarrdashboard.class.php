<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \brief   Class file for manage DigiriskDolibarrDashboard
 */

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
     * Load dashboard info for all digirisk dolibarr object
     *
     * @param  array     $moreParams    Parameters for load dashboard info
     * @return array     $dashboardData Return all dashboardData after load info
     * @throws Exception
     */
    public function load_dashboard(array $moreParams = []): array
    {
        $dashboardDatas = [
            ['type' => 'RiskAssessmentDocument', 'classPath' => '/digiriskdolibarrdocuments/riskassessmentdocument.class.php'],
            ['type' => 'Accident',               'classPath' => '/accident.class.php'],
            ['type' => 'Evaluator',              'classPath' => '/evaluator.class.php'],
            ['type' => 'DigiriskResources',      'classPath' => '/digiriskresources.class.php'],
            ['type' => 'DigiriskElement',        'classPath' => '/digiriskelement.class.php'],
            ['type' => 'SaturneTask',            'classPath' => '/../../saturne/class/task/saturnetask.class.php'],
            ['type' => 'Risk',                   'classPath' => '/riskanalysis/risk.class.php'],
            ['type' => 'TicketDashboard',        'classPath' => '/ticketdashboard.class.php']
        ];
        foreach ($dashboardDatas as $dashboardData) {
            require_once __DIR__ . $dashboardData['classPath'];
            if ($dashboardData['type'] != 'TicketDashboard') {
                $className = new $dashboardData['type']($this->db);
            } else {
                $className = new TicketDashboard($this->db, $moreParams['socid'], $moreParams['userid'], $moreParams['userassignid'], $moreParams['categticketid'], $moreParams['from'], $moreParams['join'], $moreParams['where']);
            }
            if ($dashboardData['type'] != 'SaturneTask') {
                $array[$dashboardData['type']] = array_key_exists('Load' . $dashboardData['type'], $moreParams) ? $className->load_dashboard() : [];
            } else {
                $array[$dashboardData['type']] = array_key_exists('Load' . $dashboardData['type'], $moreParams) ? $className->load_dashboard(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT')) : [];
            }
        }

        return $array;
    }
}
