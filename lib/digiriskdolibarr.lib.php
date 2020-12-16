<?php
/* Copyright (C) 2020 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    digiriskdolibarr/lib/digiriskdolibarr.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for DigiriskDolibarr
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function digiriskdolibarrAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("digiriskdolibarr@digiriskdolibarr");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/custom/digiriskdolibarr/admin/digiriskdolibarr.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/digiriskdolibarr/admin/accronym.php", 1);
	$head[$h][1] = $langs->trans("Accronym");
	$head[$h][2] = 'accronym';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@digiriskdolibarr:/digiriskdolibarr/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@digiriskdolibarr:/digiriskdolibarr/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'digiriskdolibarr');

	return $head;
}

function digirisk_dolibarr_set_const($db, $name, $value, $type = 'chaine', $visible = 0, $note = '', $entity = 1)
{
	global $conf;

	// Clean parameters
	$name = trim($name);

	// Check parameters
	if (empty($name)) {
		dol_print_error($db, "Error: Call to function dolibarr_set_const with wrong parameters", LOG_ERR);
		exit;
	}

	//dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");

	$db->begin();

	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "digirisk_const";
	$sql .= " WHERE name = " . $db->encrypt($name, 1);
	if ($entity >= 0) $sql .= " AND entity = " . $entity;

	dol_syslog("admin.lib::digirisk_dolibarr_set_const", LOG_DEBUG);
	$resql = $db->query($sql);

	if (strcmp($value, ''))    // true if different. Must work for $value='0' or $value=0
	{
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "digirisk_const(name,value,type,visible,note,entity)";
		$sql .= " VALUES (";
		$sql .= $db->encrypt($name, 1);
		$sql .= ", " . $db->encrypt($value, 1);
		$sql .= ",'" . $db->escape($type) . "'," . $visible . ",'" . $db->escape($note) . "'," . $entity . ")";

		//print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
		//print "xx".$db->escape($value);
		dol_syslog("admin.lib::dolibarr_set_const", LOG_DEBUG);
		$resql = $db->query($sql);
	}

	if ($resql) {
		$db->commit();
		$conf->global->$name = $value;
		return 1;
	} else {
		$error = $db->lasterror();
		$db->rollback();
		return -1;
	}
}

function digirisk_dolibarr_fetch_const($db, $type = 'chaine', $visible = 0, $note = '', $entity = 1)
{
	global $conf;


	$db->begin();

	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "digirisk_const";

	$resql = $db->query($sql);

	if ($resql->num_rows > 0) {
		for ($i = 0; $i < $resql->num_rows; $i++) {
			$obj = $db->fetch_object($resql);
			$key = $obj->name;
			$objects[$key] = $obj->value;
		}
		$objects = (object) $objects;
		if ($resql) {
			$db->commit();
			return $objects;

		} else {
			$error = $db->lasterror();
			$db->rollback();
			return -1;
		}
	}
}

function digirisk_dolibarr_set_resources($db, $ref, $fk_user_creat, $element_type, $element)
{
	global $conf, $langs;

	$now = dol_now();

	// Clean parameters.
	$ref = trim($ref);

	// Check parameters.
	if (empty($ref))
	{
		//Error: Call to function digirisk_dolibarr_set_resources with wrong parameters"
		dol_print_error($db, $langs->trans("ErrorDigirikDolibarrSetResources"), LOG_ERR);
		exit;
	}

	//dol_syslog("dolibarr_set_const name=ref, value=$value type=$type, visible=$visible, note=$note entity=$entity");
	$db->begin();

	$sql = "UPDATE ".MAIN_DB_PREFIX."digirisk_resources";
	$sql .= " SET status = 0";
	$sql .= " WHERE ref = ".$db->encrypt($ref, 1);
	$sql .= " AND element_type = '".$element_type . "'";
	$sql .= " AND entity IN (".getEntity('digiriskdolibarr').")";

	dol_syslog("admin.lib::digirisk_dolibarr_set_resources", LOG_DEBUG);
	$db->query($sql);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."digirisk_resources (";
	$sql .= "ref";
	$sql .= ", entity";
	$sql .= ", date_creation";
	$sql .= ", status";
	$sql .= ", element_type";
	$sql .= ", element";
	$sql .= ", fk_user_creat";
	$sql .= ") VALUES (";
	$sql .= " ".(!empty($ref) ? "'".$db->escape($ref)."'" : 'null');
	$sql .= ", ".$conf->entity;
	$sql .= ", '".$db->idate($now)."'";
	$sql .= ", ". 1;
	$sql .= ", ".(!empty($element_type) ? "'".$db->escape($element_type)."'" : 'null');
	$sql .= ", ".(is_numeric($element) ? $element : '0');
	$sql .= ", ".$fk_user_creat;
	$sql .= ")";

	dol_syslog("admin.lib::digirisk_dolibarr_set_links", LOG_DEBUG);
	$resql = $db->query($sql);

	if ($resql)
	{
		$db->commit();
		return $ref;
	}
	else
	{
		$error = $db->lasterror();
		$db->rollback();
		return -1;
	}
}

function digirisk_dolibarr_fetch_resources($db, $name, $element_type = '')
{
	global $conf;

	//dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");
	$db->begin();

	if ($name == 'all') {
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."digirisk_resources";
		$sql .= " WHERE status = 1";
	}
	else
	{
		$sql = "SELECT ";
		$sql .= "ref, status, element_type, element";
		$sql .= " FROM ".MAIN_DB_PREFIX."digirisk_resources";
		$sql .= " WHERE ref = '".$name . "'";
		$sql .= " AND element_type = '".$element_type . "'";
		$sql .= " AND status = 1";
	}

	dol_syslog("admin.lib::digirisk_dolibarr_fetch_resources", LOG_DEBUG);
	$resql = $db->query($sql);
	if ( !empty( $resql )) {
		if ($resql->num_rows > 1) {
			for ($i = 0; $i < $resql->num_rows; $i++) {
				$obj = $db->fetch_object($resql);
				$key = $obj->ref;
				$objects[$key] = $obj;
			}

			if ($resql) {
				$db->commit();
				return $objects;

			} else {
				$error = $db->lasterror();
				$db->rollback();
				return -1;
			}
		}
		else
		{
			if ($resql) {
				$db->commit();
				$obj = $db->fetch_object($resql);
				return $obj;
			} else {
				$error = $db->lasterror();
				$db->rollback();
				return -1;
			}
		}
	}
}
