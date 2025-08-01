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
            ['type' => 'Risk',                   'classPath' => '/riskanalysis/risk.class.php'],
            ['type' => 'Accident',               'classPath' => '/accident.class.php'],
            ['type' => 'Evaluator',              'classPath' => '/evaluator.class.php'],
            ['type' => 'DigiriskResources',      'classPath' => '/digiriskresources.class.php'],
            ['type' => 'DigiriskElement',        'classPath' => '/digiriskelement.class.php'],
            ['type' => 'SaturneTask',            'classPath' => '/../../saturne/class/task/saturnetask.class.php'],
            ['type' => 'TicketDashboard',        'classPath' => '/ticketdashboard.class.php'],
            ['type' => 'TicketStatsDashboard',   'classPath' => '/ticketstatsdashboard.class.php']
        ];
        foreach ($dashboardDatas as $dashboardData) {
            require_once __DIR__ . $dashboardData['classPath'];
            if ($dashboardData['type'] != 'TicketDashboard') {
                $className = new $dashboardData['type']($this->db);
            } else {
                $className = new TicketDashboard($this->db, $moreParams['join'] ?? '', $moreParams['where'] ?? '');
            }
            if ($dashboardData['type'] != 'SaturneTask') {
                $array[$dashboardData['type']] = array_key_exists('Load' . $dashboardData['type'], $moreParams) && $moreParams['Load' . $dashboardData['type']] ? $className->load_dashboard() : [];
            } else {
                $array[$dashboardData['type']] = array_key_exists('Load' . $dashboardData['type'], $moreParams) && $moreParams['Load' . $dashboardData['type']] ? $className->load_dashboard(getDolGlobalInt('DIGIRISKDOLIBARR_DU_PROJECT')) : [];
            }
        }

        return $array;
    }

    /**
     * Return nb of elements by month for several years
     *
     * @param  int  $endYear    Start year
     * @param  int  $startYear  End year
     * @param  int  $startMonth month of the fiscal year start min 1 max 12 ; if 1 = january
     * @return array            Array of values
     */
    public function getNbByMonthWithPrevYear(int $startYear, int $endYear, int $startMonth = 1)
    {
        if ($startYear > $endYear) {
            return -1;
        }

        $datay = [];
        $year  = $startYear;
        $sm    = $startMonth - 1;
        if ($sm != 0) {
            $year = $year - 1;
        }
        while ($year <= $endYear) {
            $datay[$year] = $this->getNbByMonth($year);
            $year++;
        }

        $data = [];
        for ($i = 0; $i < 12; $i++) {
            $data[$i][] = $datay[$endYear][($i + $sm) % 12][0];
            $year       = $startYear;
            while ($year <= $endYear) {
                $data[$i][] = $datay[$year][($i + $sm) % 12][1];
                $year++;
            }
        }

        return $data;
    }

    //@todo a bouger dans Saturne
    /**
     * Return nb of elements, total amount and avg amount each year
     *
     * @param  string    $sql    SQL request
     * @return array     $result Array with nb, average for each year
     * @throws Exception
     */
    protected function _getAllByYear(string $sql): array
    {
        dol_syslog(get_class($this) . '::' . __FUNCTION__, LOG_DEBUG);

        $result = [];
        $resql  = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i   = 0;
            while ($i < $num) {
                $row                = $this->db->fetch_object($resql);
                $result[$i]['year'] = $row->year;
                $result[$i]['nb']   = $row->nb;
                if ($i > 0 && $row->nb > 0) {
                    $result[$i - 1]['avg'] = -($row->nb - ($result[$i - 1]['nb'])) / $row->nb * 100;
                }
                $i++;
            }
            $this->db->free($resql);
        } else {
            dol_print_error($this->db);
        }

        return $result;
    }

    //@todo a bouger dans Saturne
    /**
     * Return number of elements per month
     *
     * @param  string    $sql  SQL request
     * @return array     $data Array of nb each month
     * @throws Exception
     */
    protected function _getNbByMonth(string $sql): array
    {
        global $langs;

        dol_syslog(get_class($this) . '::' . __FUNCTION__, LOG_DEBUG);

        $result = [];
        $resql  = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $row        = $this->db->fetch_row($resql);
                $j          = $row[0] * 1;
                $result[$j] = $row[1];
                $i++;
            }
            $this->db->free($resql);
        } else {
            dol_print_error($this->db);
        }

        $res = [];
        for ($i = 1; $i < 13; $i++) {
            $res[$i] = ($result[$i] ?? 0);
        }

        $data = [];
        for ($i = 1; $i < 13; $i++) {
            $month = $langs->transnoentitiesnoconv('MonthShort' . sprintf('%02d', $i));
            $data[$i - 1] = array($month, $res[$i]);
        }

        return $data;
    }
}
