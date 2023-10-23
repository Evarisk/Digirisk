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

require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';
require_once __DIR__ . '/digiriskstats.php';

/**
 *	Class for DigiriskTask
 */
class DigiriskTask extends SaturneTask
{
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
		$sql .= " t.rowid as taskid, t.ref as taskref, t.label, t.description, t.fk_user_creat, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut as status,";
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
                    $tasks[$obj->taskid]->fk_user_creat = $obj->fk_user_creat;

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

