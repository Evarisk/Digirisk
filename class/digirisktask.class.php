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
		switch (true) {
			case $progress < 50 :
				return 'progress-red';
			case $progress < 99 :
				return 'progress-yellow';
			case $progress :
				return 'progress-green';
		}
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 * @param $task
	 * @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * @param	string	$option			'withproject' or ''
	 * @param	string	$mode			Mode 'task', 'time', 'contact', 'note', document' define page to link to.
	 * @param	int		$addlabel		0=Default, 1=Add label into string, >1=Add first chars into string
	 * @param	string	$sep			Separator between ref and label if option addlabel is set
	 * @param	int   	$notooltip		1=Disable tooltip
	 * @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return	string					Chaine avec URL
	 */
	public function getNomUrlTask($task, $withpicto = 0, $option = '', $mode = 'task', $addlabel = 0, $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result     = '';
		$label      = img_picto('', $task->picto) . ' <u>' . $langs->trans("Task") . '</u>';
		if ( ! empty($task->ref))
			$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $task->ref;
		if ( ! empty($task->label))
			$label .= '<br><b>' . $langs->trans('LabelTask') . ':</b> ' . $task->label;
		if ($task->date_start || $task->date_end) {
			$label .= "<br>" . get_date_range($task->date_start, $task->date_end, '', $langs, 0);
		}

		$url = DOL_URL_ROOT . '/projet/tasks/' . $mode . '.php?id=' . $task->id . ($option == 'withproject' ? '&withproject=1' : '');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values                                                                                      = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
		if ($add_save_lastsearch_values) $url                                                                           .= '&save_lastsearch_values=1';

		$linkclose = '';
		if (empty($notooltip)) {
			if ( ! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label      = $langs->trans("ShowTask");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip nowraponall"';
		} else {
			$linkclose .= ' class="nowraponall"';
		}

		$linkstart  = '<a target="_blank" href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend    = '</a>';

		$picto = 'projecttask';

		$result                      .= $linkstart;
		if ($withpicto) $result      .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $task->ref;
		$result                      .= $linkend;
		if ($withpicto != 2) $result .= (($addlabel && $task->label) ? $sep . dol_trunc($task->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}
}

