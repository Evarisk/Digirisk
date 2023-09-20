<?php
/* Copyright (C) 2022 EOXIA <technique@evarisk.com>
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
		$array['picto'] = $this->picto;
		$array['dataset'] = 1;
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
		$taskarray = $this->getTasksArray(0, 0, $conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
		$array['data'][0]   = 0;
		$array['data'][1]   = 0;
		$array['data'][2]   = 0;
		if (is_array($taskarray) && !empty($taskarray)) {
			foreach ($taskarray as $tasksingle) {
				if ($tasksingle->progress == 0) {
					$array['data'][0] = $array['data'][0] + 1;
				} elseif ($tasksingle->progress > 0 && $tasksingle->progress < 100) {
					$array['data'][1] = $array['data'][1] + 1;
				} else {
					$array['data'][2] = $array['data'][2] + 1;
				}
			}
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
	 * @param  int		$withpicto		        0=No picto, 1=Include picto into link, 2=Only picto
	 * @param  string	$option			        'withproject' or ''
	 * @param  string	$mode			        Mode 'task', 'time', 'contact', 'note', document' define page to link to.
	 * @param  int		$addlabel		        0=Default, 1=Add label into string, >1=Add first chars into string
	 * @param  string	$sep					Separator between ref and label if option addlabel is set
	 * @param  int   	$notooltip		        1=Disable tooltip
	 * @param  int      $save_lastsearch_value  -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string					        Chaine avec URL
	 */
	public function getNomUrlTask($withpicto = 0, $option = '', $mode = 'task', $addlabel = 0, $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs;

		if ( ! empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result     = '';
		$label      = img_picto('', $this->picto) . ' <u>' . $langs->trans("Task") . '</u>';
		if ( ! empty($this->ref))
			$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if ( ! empty($this->label))
			$label .= '<br><b>' . $langs->trans('LabelTask') . ':</b> ' . $this->label;
		if ($this->date_start || $this->date_end) {
			$label .= "<br>" . get_date_range($this->date_start, $this->date_end, '', $langs, 0);
		}

		$url = DOL_URL_ROOT . '/projet/tasks/' . $mode . '.php?id=' . $this->id . ($option == 'withproject' ? '&withproject=1' : '');
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
		if ($withpicto != 2) $result .= $this->ref;
		$result                      .= $linkend;
		if ($withpicto != 2) $result .= (($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 *  Load all records of time spent for all user
	 *
	 * @param string       $morewherefilter Add more filter into where SQL request (must start with ' AND ...')
	 * @param string       $sortorder       Sort Order
	 * @param string       $sortfield       Sort field
	 *
	 * @return array|int                    0 < if KO, array of time spent if OK
	 * @throws Exception
	 */
	public function fetchAllTimeSpentAllUser($morewherefilter = '', $sortfield = '', $sortorder = '', $sortedByTasks = 0)
	{
		$arrayres = array();

		$sql = "SELECT";
		$sql .= " s.rowid as socid,";
		$sql .= " s.nom as thirdparty_name,";
		$sql .= " s.email as thirdparty_email,";
		$sql .= " ptt.rowid,";
		$sql .= " ptt.fk_task,";
		$sql .= " ptt.task_date,";
		$sql .= " ptt.task_datehour,";
		$sql .= " ptt.task_date_withhour,";
		$sql .= " ptt.task_duration,";
		$sql .= " ptt.fk_user,";
		$sql .= " ptt.note,";
		$sql .= " ptt.thm,";
		$sql .= " pt.rowid as task_id,";
		$sql .= " pt.ref as task_ref,";
		$sql .= " pt.label as task_label,";
		$sql .= " p.rowid as project_id,";
		$sql .= " p.ref as project_ref,";
		$sql .= " p.title as project_label,";
		$sql .= " p.public as public";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql .= " WHERE ptt.fk_task = pt.rowid AND pt.fk_projet = p.rowid";
		$sql .= " AND pt.entity IN (".getEntity('project').")";
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		dol_syslog(get_class($this)."::fetchAllTimeSpentAllUser", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$newobj = new stdClass();

				$newobj->socid            = $obj->socid;
				$newobj->thirdparty_name  = $obj->thirdparty_name;
				$newobj->thirdparty_email = $obj->thirdparty_email;

				$newobj->fk_project    = $obj->project_id;
				$newobj->project_ref   = $obj->project_ref;
				$newobj->project_label = $obj->project_label;
				$newobj->public        = $obj->project_public;

				$newobj->fk_task	= $obj->task_id;
				$newobj->task_ref   = $obj->task_ref;
				$newobj->task_label = $obj->task_label;

				$newobj->timespent_id       = $obj->rowid;
				$newobj->timespent_date     = $this->db->jdate($obj->task_date);
				$newobj->timespent_datehour	= $this->db->jdate($obj->task_datehour);
				$newobj->timespent_withhour = $obj->task_date_withhour;
				$newobj->timespent_duration = $obj->task_duration;
				$newobj->timespent_fk_user  = $obj->fk_user;
				$newobj->timespent_thm      = $obj->thm;	// hourly rate
				$newobj->timespent_note     = $obj->note;

				$arrayres[] = $newobj;

				$i++;
			}

			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}

		if ($sortedByTasks > 0) {
			$timeSpentSortedByTasks = [];
			if (is_array($arrayres) && !empty($arrayres)) {
				foreach ($arrayres as $timeSpent) {
					$timeSpentSortedByTasks[$timeSpent->fk_task][$timeSpent->timespent_id] = $timeSpent;
				}
			}
			return $timeSpentSortedByTasks;
		} else {
			return $arrayres;
		}
	}

	/**
	 * Return list of tasks for all projects or for one particular project
	 * Sort order is on project, then on position of task, and last on start date of first level task
	 *
	 * @param	User	$usert				Object user to limit tasks affected to a particular user
	 * @param	User	$userp				Object user to limit projects of a particular user and public projects
	 * @param	int		$projectid			Project id
	 * @param	int		$socid				Third party id
	 * @param	int		$mode				0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 * @param	string	$filteronproj    	Filter on project ref or label
	 * @param	string	$filteronprojstatus	Filter on project status ('-1'=no filter, '0,1'=Draft+Validated only)
	 * @param	string	$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 * @param	string	$filteronprojuser	Filter on user that is a contact of project
	 * @param	string	$filterontaskuser	Filter on user assigned to task
	 * @param	array	$extrafields	    Show additional column from project or task
	 * @param   int     $includebilltime    Calculate also the time to bill and billed
	 * @param   array   $search_array_options Array of search
	 * @param   int     $loadextras         Fetch all Extrafields on each task
	 * @param	int		$loadRoleMode		1= will test Roles on task;  0 used in delete project action
	 * @return 	array						Array of tasks
	 */
	public function fetchAll($usert = null, $userp = null, $projectid = 0, $socid = 0, $mode = 0, $filteronproj = '', $filteronprojstatus = '-1', $morewherefilter = '', $filteronprojuser = 0, $filterontaskuser = 0, $extrafields = array(), $includebilltime = 0, $search_array_options = array(), $loadextras = 0, $loadRoleMode = 1)
	{
		global $conf, $hookmanager;

		$tasks = array();

		//print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT ";
		if ($filteronprojuser > 0 || $filterontaskuser > 0) {
			$sql .= " DISTINCT"; // We may get several time the same record if user has several roles on same project/task
		}
		$sql .= " p.rowid as projectid, p.ref, p.title as plabel, p.public, p.fk_statut as projectstatus, p.usage_bill_time,";
		$sql .= " t.rowid as taskid, t.ref as taskref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut as status,";
		$sql .= " t.dateo as date_start, t.datee as date_end, t.planned_workload, t.rang, t.datec as date_c, ";
		$sql .= " t.description, ";
		$sql .= " t.budget_amount, ";
		$sql .= " s.rowid as thirdparty_id, s.nom as thirdparty_name, s.email as thirdparty_email,";
		$sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount as project_budget_amount";
		if (!empty($extrafields->attributes['projet']['label'])) {
			foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
				$sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key." as options_".$key : '');
			}
		}
		if (!empty($extrafields->attributes['projet_task']['label'])) {
			foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
				$sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key." as options_".$key : '');
			}
		}
		if ($includebilltime) {
			$sql .= ", SUM(tt.task_duration * ".$this->db->ifsql("invoice_id IS NULL", "1", "0").") as tobill, SUM(tt.task_duration * ".$this->db->ifsql("invoice_id IS NULL", "0", "1").") as billed";
		}

		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as efp ON (p.rowid = efp.fk_object)";

		if ($mode == 0) {
			if ($filteronprojuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
			if ($includebilltime) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time as tt ON tt.fk_task = t.rowid";
			}
			if ($filterontaskuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			}
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
			$sql .= " WHERE p.entity IN (".getEntity('project').")";
			$sql .= " AND t.fk_projet = p.rowid";
		} elseif ($mode == 1) {
			if ($filteronprojuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			if ($filterontaskuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
				if ($includebilltime) {
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time as tt ON tt.fk_task = t.rowid";
				}
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			} else {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
				if ($includebilltime) {
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time as tt ON tt.fk_task = t.rowid";
				}
			}
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
			$sql .= " WHERE p.entity IN (".getEntity('project').")";
		} else {
			return 'BadValueForParameterMode';
		}

		if ($filteronprojuser > 0) {
			$sql .= " AND p.rowid = ec.element_id";
			$sql .= " AND ctc.rowid = ec.fk_c_type_contact";
			$sql .= " AND ctc.element = 'project'";
			$sql .= " AND ec.fk_socpeople = ".((int) $filteronprojuser);
			$sql .= " AND ec.statut = 4";
			$sql .= " AND ctc.source = 'internal'";
		}
		if ($filterontaskuser > 0) {
			$sql .= " AND t.fk_projet = p.rowid";
			$sql .= " AND p.rowid = ec2.element_id";
			$sql .= " AND ctc2.rowid = ec2.fk_c_type_contact";
			$sql .= " AND ctc2.element = 'project_task'";
			$sql .= " AND ec2.fk_socpeople = ".((int) $filterontaskuser);
			$sql .= " AND ec2.statut = 4";
			$sql .= " AND ctc2.source = 'internal'";
		}
		if ($socid) {
			$sql .= " AND p.fk_soc = ".((int) $socid);
		}
		if ($projectid) {
			$sql .= " AND p.rowid IN (".$this->db->sanitize($projectid).")";
		}
		if ($filteronproj) {
			$sql .= natural_search(array("p.ref", "p.title"), $filteronproj);
		}
		if ($filteronprojstatus && $filteronprojstatus != '-1') {
			$sql .= " AND p.fk_statut IN (".$this->db->sanitize($filteronprojstatus).")";
		}
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}
		// Add where from extra fields
		$extrafieldsobjectkey = 'projet_task';
		$extrafieldsobjectprefix = 'efpt.';
		global $db; // needed for extrafields_list_search_sql.tpl
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		if ($includebilltime) {
			$sql .= " GROUP BY p.rowid, p.ref, p.title, p.public, p.fk_statut, p.usage_bill_time,";
			$sql .= " t.datec, t.dateo, t.datee, t.tms,";
			$sql .= " t.rowid, t.ref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut,";
			$sql .= " t.dateo, t.datee, t.planned_workload, t.rang,";
			$sql .= " t.description, ";
			$sql .= " t.budget_amount, ";
			$sql .= " s.rowid, s.nom, s.email,";
			$sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount";
			if (!empty($extrafields->attributes['projet']['label'])) {
				foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
					$sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key : '');
				}
			}
			if (!empty($extrafields->attributes['projet_task']['label'])) {
				foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
					$sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key : '');
				}
			}
		}


		$sql .= " ORDER BY p.ref, t.rang, t.dateo";

		//print $sql;exit;
		dol_syslog(get_class($this)."::getTasksArray", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num) {
				$error = 0;

				$obj = $this->db->fetch_object($resql);

				if ($loadRoleMode) {
					if ((!$obj->public) && (is_object($userp))) {    // If not public project and we ask a filter on project owned by a user
						if (!$this->getUserRolesForProjectsOrTasks($userp, 0, $obj->projectid, 0)) {
							$error++;
						}
					}
					if (is_object($usert)) {                            // If we ask a filter on a user affected to a task
						if (!$this->getUserRolesForProjectsOrTasks(0, $usert, $obj->projectid, $obj->taskid)) {
							$error++;
						}
					}
				}

				if (!$error) {
					$tasks[$obj->taskid] = new DigiriskTask($this->db);
					$tasks[$obj->taskid]->id = $obj->taskid;
					$tasks[$obj->taskid]->ref = $obj->taskref;
					$tasks[$obj->taskid]->fk_project = $obj->projectid;
					$tasks[$obj->taskid]->projectref = $obj->ref;
					$tasks[$obj->taskid]->projectlabel = $obj->plabel;
					$tasks[$obj->taskid]->projectstatus = $obj->projectstatus;

					$tasks[$obj->taskid]->fk_opp_status = $obj->fk_opp_status;
					$tasks[$obj->taskid]->opp_amount = $obj->opp_amount;
					$tasks[$obj->taskid]->opp_percent = $obj->opp_percent;
					$tasks[$obj->taskid]->budget_amount = $obj->budget_amount;
					$tasks[$obj->taskid]->project_budget_amount = $obj->project_budget_amount;
					$tasks[$obj->taskid]->usage_bill_time = $obj->usage_bill_time;

					$tasks[$obj->taskid]->label = $obj->label;
					$tasks[$obj->taskid]->description = $obj->description;
					$tasks[$obj->taskid]->fk_parent = $obj->fk_task_parent; // deprecated
					$tasks[$obj->taskid]->fk_task_parent = $obj->fk_task_parent;
					$tasks[$obj->taskid]->duration		= $obj->duration_effective;
					$tasks[$obj->taskid]->planned_workload = $obj->planned_workload;

					if ($includebilltime) {
						$tasks[$obj->taskid]->tobill = $obj->tobill;
						$tasks[$obj->taskid]->billed = $obj->billed;
					}

					$tasks[$obj->taskid]->progress		= $obj->progress;
					$tasks[$obj->taskid]->fk_statut = $obj->status;
					$tasks[$obj->taskid]->public = $obj->public;
					$tasks[$obj->taskid]->date_start = $this->db->jdate($obj->date_start);
					$tasks[$obj->taskid]->date_end		= $this->db->jdate($obj->date_end);
					$tasks[$obj->taskid]->date_c		= $this->db->jdate($obj->date_c);
					$tasks[$obj->taskid]->rang	   		= $obj->rang;

					$tasks[$obj->taskid]->socid           = $obj->thirdparty_id; // For backward compatibility
					$tasks[$obj->taskid]->thirdparty_id = $obj->thirdparty_id;
					$tasks[$obj->taskid]->thirdparty_name	= $obj->thirdparty_name;
					$tasks[$obj->taskid]->thirdparty_email = $obj->thirdparty_email;

					if (!empty($extrafields->attributes['projet']['label'])) {
						foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
							if ($extrafields->attributes['projet']['type'][$key] != 'separate') {
								$tasks[$obj->taskid]->{'options_'.$key} = $obj->{'options_'.$key};
							}
						}
					}

					if (!empty($extrafields->attributes['projet_task']['label'])) {
						foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
							if ($extrafields->attributes['projet_task']['type'][$key] != 'separate') {
								$tasks[$obj->taskid]->{'options_'.$key} = $obj->{'options_'.$key};
							}
						}
					}

					if ($loadextras) {
						$tasks[$obj->taskid]->fetch_optionals();
					}
				}

				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		return $tasks;
	}

}

