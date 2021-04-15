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
 * \file        class/digiriskstandard.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for DigiriskStandard (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for DigiriskStandard
 */
class DigiriskStandard extends CommonObject
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'digiriskstandard';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_digiriskstandard';

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
	 * @var string String with name of icon for digiriskstandard. Must be the part after the 'object_' into object_digiriskstandard.png
	 */
	public $picto = 'digiriskstandard@digiriskdolibarr';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext' => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => -2,),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => -2,),
		'status' => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 1, 'index' => 1,),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 3,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 90, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => -2,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $description;
	public $fk_user_creat;
	public $fk_user_modif;

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
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @param string $ref Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@return		string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0)
	{
		global $conf, $langs;

		$result = '';
		$label = $langs->trans("Show").': '.$this->ref;

		$link = '<a href="'.DOL_URL_ROOT.'/custom/digiriskdolibarr/digiriskstandard_card.php?id='.$conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$picto = 'trip';


		if ($withpicto) $result .= ($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';
		if ($withpicto != 2) $result .= $link.$this->ref.$linkend;
		return $result;
	}
}
