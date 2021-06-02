<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * \file        class/openinghours.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for Openinghours (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for Openinghours
 */
class Openinghours extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'openinghours';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'element_openinghours';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for openinghours. Must be the part after the 'object_' into object_openinghours.png
	 */
	public $picto = 'openinghours@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => -2,),
		'tms'           => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 30, 'notnull' => 0, 'visible' => -2,),
		'status'        => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 40, 'notnull' => 0, 'visible' => -1,),
		'element_type'  => array('type' => 'varchar(50)', 'label' => 'ElementType', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => -1,),
		'element_id'    => array('type' => 'integer', 'label' => 'ElementID', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => -1,),
		'monday'        => array('type' => 'varchar(128)', 'label' => 'Day 0', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 1,),
		'tuesday'       => array('type' => 'varchar(128)', 'label' => 'Day 1', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1,),
		'wednesday'     => array('type' => 'varchar(128)', 'label' => 'Day 2', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 1,),
		'thursday'      => array('type' => 'varchar(128)', 'label' => 'Day 3', 'enabled' => '1', 'position' => 100, 'notnull' => 0, 'visible' => 1,),
		'friday'        => array('type' => 'varchar(128)', 'label' => 'Day 4', 'enabled' => '1', 'position' => 110, 'notnull' => 0, 'visible' => 1,),
		'saturday'      => array('type' => 'varchar(128)', 'label' => 'Day 5', 'enabled' => '1', 'position' => 120, 'notnull' => 0, 'visible' => 1,),
		'sunday'        => array('type' => 'varchar(128)', 'label' => 'Day 6', 'enabled' => '1', 'position' => 130, 'notnull' => 0, 'visible' => 1,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 140, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
	);

	public $rowid;
	public $entity;
	public $date_creation;
	public $tms;
	public $status;
	public $element_type;
	public $element_id;
	public $monday;
	public $tuesday;
	public $wednesday;
	public $thursday;
	public $friday;
	public $saturday;
	public $sunday;
	public $fk_user_creat;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 * @throws Exception
	 */
	public function create(User $user, $notrigger = false)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . "$this->table_element";
		$sql .= " SET status = 0";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
		$sql .= " AND element_type = " . "'" . $this->element_type . "'";
		$sql .= " AND element_id = " . $this->element_id;

		dol_syslog("admin.lib::create", LOG_DEBUG);
		$this->db->query($sql);
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int    $id				Id object
	 * @param	string $ref				Ref
	 * @param	string	$morewhere		More SQL filters (' AND ...')
	 * @return 	int         			<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $morewhere = '')
	{
		return $this->fetchCommon($id, $ref, $morewhere);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return 	string				Label of contact status
	 */
	public function getLibStatut($mode)
	{
		return '';
	}

}
