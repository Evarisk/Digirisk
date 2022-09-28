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
	 * Load dashboard info task
	 *
	 * @return array|int
	 * @throws Exception
	 */
	public function load_dashboard()
	{
		$arrayTasksByProgress = $this->getTasksByProgress();

		$array['graphs'] = $arrayTasksByProgress;

		return $array;
	}

	/**
	 * Get tasks by progress.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getTasksByProgress()
	{
		// Tasks by progress
		global $conf, $langs;

		$array['title'] = $langs->transnoentities('TasksRepartition');
		$array['picto'] = '<i class="fas fa-tasks"></i>';
		$array['labels'] = array(
			'taskat0percent' => array(
				'label' => $langs->transnoentities('TaskAt0Percent') . ' %',
				'color' => '#e05353'
			),
			'taskinprogress' => array(
				'label' => $langs->transnoentities('TaskInProgress'),
				'color' => '#e9ad4f'
			),
			'taskat100percent' => array(
				'label' => $langs->transnoentities('TaskAt100Percent') . ' %',
				'color' => '#47e58e'
			),
		);
		$taskarray = $this->getTasksArray(0, 0, $conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
		if (is_array($taskarray) && !empty($taskarray)) {
			foreach ($taskarray as $tasksingle) {
				if ($tasksingle->progress == 0) {
					$array['data']['taskat0percent'] = $array['data']['taskat0percent'] + 1;
				} elseif ($tasksingle->progress > 0 && $tasksingle->progress < 100) {
					$array['data']['taskinprogress'] = $array['data']['taskinprogress'] + 1;
				} else {
					$array['data']['taskat100percent'] = $array['data']['taskat100percent'] + 1;
				}
			}
		} else {
			$array['data']['taskat0percent']   = 0;
			$array['data']['taskinprogress']   = 0;
			$array['data']['taskat100percent'] = 0;
		}
		return $array;
	}

	/**
	 * get task progress css class.
	 *
	 * @param  float  $progress Progress of the task
	 *
	 * @return string           CSS class
	 */
	public function getTaskProgressColorClass($progress)
	{
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

