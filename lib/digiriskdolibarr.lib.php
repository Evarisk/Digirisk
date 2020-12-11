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

function digirisk_dolibarr_set_links($db, $name, $fk_user_author, $fk_soc, $contact_list = 0, $fk_user, $entity = 1)
{
	global $conf;

	// Clean parameters
	$name = trim($name);
		// Check parameters
	if (empty($name))
	{
		dol_print_error($db, "Error: Call to function digirisk_dolibarr_set_links with wrong parameters", LOG_ERR);
		exit;
	}
		//dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");
	$db->begin();

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."digirisk_links";
	$sql .= " WHERE ref = ".$db->encrypt($name, 1);
	if ($entity >= 0) $sql .= " AND entity = ".$entity;

	dol_syslog("admin.lib::digirisk_dolibarr_set_links", LOG_DEBUG);
	$resql = $db->query($sql);

	if (!is_array($contact_list)) {

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."digirisk_links(ref, entity, fk_user_author, fk_soc, fk_contact, fk_user)";
		$sql .= " VALUES (";
		$sql .= $db->encrypt($name, 1);
		$sql .= ", ".$entity;
		$sql .= ", ".(is_numeric($fk_user_author) ? $fk_user_author : '0');
		$sql .= ", ".(is_numeric($fk_soc) ? $fk_soc : '0');
		$sql .= ", ".(is_numeric($contact_list) ? $contact_list : '0') . ", ";

		if (is_array($fk_user)) {
			foreach ($fk_user as $user) {
				$users[$user] = is_numeric($user) ? $user : '0';
			}
			$sql .= implode("",$users);

		}
		else
		{
			$sql.= "0";
		}

		$sql .= ")";
		//print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
		//print "xx".$db->escape($value);
		dol_syslog("admin.lib::digirisk_dolibarr_set_links", LOG_DEBUG);
		$resql = $db->query($sql);

	}
	else
	{
		foreach ($contact_list as $fk_contact) {

			if (strcmp($fk_user_author, ''))    // true if different. Must work for $value='0' or $value=0
			{
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "digirisk_links(ref, entity, fk_user_author, fk_soc, fk_contact, fk_user)";
				$sql .= " VALUES (";
				$sql .= $db->encrypt($name, 1);
				$sql .= ", " . $entity;
				$sql .= ", " . (is_numeric($fk_user_author) ? $fk_user_author : '0');
				$sql .= ", " . (is_numeric($fk_soc) ? $fk_soc : '0');
				$sql .= ", " . (is_numeric($fk_contact) ? $fk_contact : '0');
				foreach ($fk_user as $user) {
					$sql .= ", ".(is_numeric($user) ? $user : '0');
				}
				$sql .= ")";
				//print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
				//print "xx".$db->escape($value);
				dol_syslog("admin.lib::digirisk_dolibarr_set_links", LOG_DEBUG);
				$resql = $db->query($sql);
			}
		}
	}

	if ($resql)
	{
		$db->commit();
		return $name;

	}
	else
	{
		$error = $db->lasterror();
		$db->rollback();
		return -1;
	}
}

function digirisk_dolibarr_fetch_links($db, $name)
{
	global $conf;

	//dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");
	$db->begin();

	if ($name == 'all') {
		$sql = "SELECT * FROM llx_digirisk_links";

	}
	else
	{
		$sql = "SELECT ";
		$sql .= "ref, fk_user_author, fk_soc, fk_contact, fk_user ";
		$sql .= "FROM ".MAIN_DB_PREFIX."digirisk_links";
		$sql .= " WHERE ref = '".$name . "'";
	}


	dol_syslog("admin.lib::digirisk_dolibarr_fetch_links", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql->num_rows > 1) {
		for ($i = 0; $i < $resql->num_rows; $i++) {
			$obj = $db->fetch_object($resql);
			$key = $obj->ref;
			if ($key !== 'fk_user') {
				$objects[$key] = $obj;
			}
			else
			{
				$objects[$key] = array($obj);
			}
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
		$obj = $db->fetch_object($resql);
		if (!empty($obj)) {
			$obj->fk_user = array($obj->fk_user);
		}
		if ($resql) {
			$db->commit();
			return $obj;

		} else {
			$error = $db->lasterror();
			$db->rollback();
			return -1;
		}
	}
}
