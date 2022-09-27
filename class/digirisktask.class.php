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
 *       \file       class/digirisktask.class.php
 *       \ingroup    digiriskdolibarr
 *       \brief      This file is a CRUD class file for DigiriskTask (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

require_once __DIR__ . '/digiriskstats.php';

/**
 *	Class for DigiriskTask
 */
class DigiriskTask extends Task
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
	 * Load dashboard info task, get number tasks by progress.
	 *
	 * @return array|int
	 */
	public function load_dashboard()
	{
		global $conf, $langs;


		$taskarray = $this->getTasksArray(0, 0, $conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
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
	public function getTaskProgressColorClass($progress) {
		switch ($progress) {
			case $progress < 50 :
				return 'progress-red';
			case $progress < 99 :
				return 'progress-yellow';
			case $progress :
				return 'progress-green';
		}
	}
}

