<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file        class/riskanalysis/risksign.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for RiskSign (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturneobject.class.php';

/**
 * Class for RiskSign
 */
class RiskSign extends SaturneObject
{
	/**
	 * @var string Module name.
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object.
	 */
	public $element = 'risksign';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'digiriskdolibarr_risksign';

	/**
	 * @var int Does this object support multicompany module ?
	 * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int Does object support extrafields ? 0 = No, 1 = Yes.
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for risksign. Must be the part after the 'object_' into object_risksign.png
	 */
	public $picto = 'risksign@digiriskdolibarr';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"),
		'ref'           => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'ref_ext'       => array('type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 8, 'notnull' => 1, 'visible' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => 0,),
		'tms'           => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => 0,),
		'import_key'    => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => 0,),
		'status'        => array('type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 0,),
		'category'      => array('type' => 'integer', 'label' => 'RiskSignCategory', 'enabled' => '1', 'position' => 80, 'notnull' => 0, 'visible' => 1,),
		'description'   => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 1,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 100, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 110, 'notnull' => -1, 'visible' => 0,),
		'fk_element'    => array('type' => 'integer', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 9, 'notnull' => 1, 'visible' => 1,),
	);

	public $rowid;
	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $category;
	public $description;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_element;

	/**
	 * Constructor.
	 *
	 * @param DoliDb $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->element);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent($parent_id)
	{
		$filter = array('customsql' => 'fk_element=' . $this->db->escape($parent_id) . ' AND status > ' . $this::STATUS_DELETED);
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}

    /**
     * Load risk sign infos
     *
     * @param  array     $moreParam More param (filter)
     * @return array     $array     Array of risk signs
     * @throws Exception
     */
    public static function loadRiskSignInfos(array $moreParam = []): array
    {
        $array = [];

        //@todo: missing shared and inherited risksigns

        $select             = ', d.ref AS digiriskElementRef, d.entity AS digiriskElementEntity, d.label AS digiriskElementLabel';
        $moreSelects        = ['digiriskElementRef', 'digiriskElementEntity', 'digiriskElementLabel'];
        $join               = ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskelement AS d ON d.rowid = t.fk_element';
        $filter             = 'd.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND t.status = ' . self::STATUS_VALIDATED . ($moreParam['filter'] ?? '');
        $array['riskSigns'] = saturne_fetch_all_object_type('RiskSign', '', '', 0, 0, ['customsql' => $filter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (!is_array($array['riskSigns']) || empty($array['riskSigns'])) {
            $array['riskSigns'] = [];
        }

        $array['nbRiskSigns'] = count($array['riskSigns']);

        return $array;
    }

	/**
	 * Get risksign categories json in /digiriskdolibarr/js/json/
	 *
	 * @return    array $riskSignCategories
	 */
	public function getRiskSignCategories()
	{
		$json_categories = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json');
		return json_decode($json_categories, true);
	}

	/**
	 * Get risksign category picto path
	 *
	 * @param $risksign
	 * @param string $param
	 * @return    string $risksign['thumbnail_name']     path to risksign picto, -1 if don't exist
	 */
	public function getRiskSignCategory($risksign, $param = 'name_thumbnail')
	{
		$riskSignCategories = $this->getRiskSignCategories();

		foreach ($riskSignCategories as $category) {
			if ($category['position'] == $risksign->category) {
				return $category[$param];
			}
		}
		return -1;
	}

	/**
	 * Get risksign category picto name
	 *
	 * @param $risksign
	 * @return    string $risksign['name']     name to risksign picto, -1 if don't exist
	 */
	public function getRiskSignCategoryName($risksign)
	{
		$riskSignCategories = $this->getRiskSignCategories();

		foreach ($riskSignCategories as $category) {
			if ($category['position'] == $risksign->category) {
				return $category['name'];
			}
		}
		return -1;
	}

	/**
	 * Get risksign category picto name
	 *
	 * @param $name
	 * @return    string $category['name']     name to danger category picto, -1 if don't exist
	 */
	public function getRiskSignCategoryPositionByName($name)
	{
		$riskSignCategories = $this->getRiskSignCategories();
		foreach ($riskSignCategories as $category) {
			if ($category['name'] == $name) {
				return $category['position'];
			}
		}

		return -1;
	}

	/**
	 * Get risksign category picto path
	 *
	 * @param int $position
	 * @return    string $category['name_thumbnail']     path to danger category picto, -1 if don't exist
	 */
	public function getRiskSignCategoryByPosition($position)
	{
		$riskSignCategories  = $this->getRiskSignCategories();
		foreach ($riskSignCategories as $category) {
			if ($category['position'] == $position) {
				return $category['name_thumbnail'];
			}
		}

		return -1;
	}

	/**
	 * Get risksign category picto name
	 *
	 * @param int $position
	 * @return    string $category['name']     path to risksign category picto, -1 if don't exist
	 */
	public function getRiskSignCategoryNameByPosition($position)
	{
		$riskSignCategories = $this->getRiskSignCategories();
		foreach ($riskSignCategories as $category) {
			if ($category['position'] == $position) {
				return $category['name'];
			}
		}

		return -1;
	}

    /**
     * Load object in memory from the database
     *
     * @param  int       $parent_id        Id parent object
     * @param  bool      $get_parents_data Get parents risks signs data
     * @param  bool      $get_shared_data  Get shared risks signs data
     * @param  array     $moreParams       More params(Object/user/etc)
     * @return array|int                   Int <0 if KO, array of pages if OK
     * @throws Exception
     */
	public function fetchRiskSign($parent_id, $get_parents_data = false, $get_shared_data = false, $moreParams = [])
	{
		$object   = new DigiriskElement($this->db);
		$objects  = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0' ));
		$risksign = new RiskSign($this->db);
		$result   = $risksign->fetchAll('', '', 0, 0, ['customsql' => ($parent_id > 0 ? 'fk_element = ' . $parent_id . ' AND ' : '') . 'status = ' . self::STATUS_VALIDATED . $moreParams['filter']]);

		$trashList = $object->getMultiEntityTrashList();
		if (!empty($trashList) && is_array($trashList)) {
			foreach($trashList as $trash_element_id) {
				unset($objects[$trash_element_id]);
			}
		}

		if ($result > 0 && ! empty($result)) {
			foreach ($result as $risksign) {
				$risksigns[$risksign->id] = $risksign;
			}
		}

		if ( $get_parents_data ) {
			$parent_element_id = $objects[$parent_id]->id;
			while ($parent_element_id > 0) {
				$result = $risksign->fetchFromParent($parent_element_id);
				if ($result > 0 && ! empty($result)) {
					foreach ($result as $risksign) {
						$risksigns[$risksign->id] = $risksign;
					}
				}
				$parent_element_id = $objects[$parent_element_id]->fk_parent;
			}
		}

		if ( $get_shared_data ) {
			$digiriskelementtmp = new DigiriskElement($this->db);
			if ($parent_id == 0) {
				$digiriskelement_flatlist = $digiriskelementtmp->fetchDigiriskElementFlat(0);
				if (is_array($digiriskelement_flatlist) && !empty($digiriskelement_flatlist)) {
					foreach ($digiriskelement_flatlist as $sub_digiriskelement) {
						$digiriskelement = $sub_digiriskelement['object'];
						$digiriskelement->fetchObjectLinked(null, '', $digiriskelement->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
						if (!empty($digiriskelement->linkedObjectsIds['digiriskdolibarr_risksign'])) {
							foreach ($digiriskelement->linkedObjectsIds['digiriskdolibarr_risksign'] as $risksign_id) {
								$risksign = new self($this->db);
								$risksign->fetch($risksign_id);
								if (!array_key_exists($risksign->fk_element, $trashList)) {
									$risksigns[$risksign->id] = $risksign;
								}
							}
						}
					}
				}
			} else {
				$digiriskelementtmp->fetch($parent_id);
				$digiriskelementtmp->fetchObjectLinked(null, '', $digiriskelementtmp->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
				if (!empty($digiriskelementtmp->linkedObjectsIds['digiriskdolibarr_risksign'])) {
					foreach ($digiriskelementtmp->linkedObjectsIds['digiriskdolibarr_risksign'] as $risksign_id) {
						$risksign = new self($this->db);
						$risksign->fetch($risksign_id);
						if (!array_key_exists($risksign->fk_element, $trashList)) {
							$risksigns[$risksign->id] = $risksign;
						}
					}
				}
			}
		}

		if ( ! empty($risksigns) ) {
			return $risksigns;
		} else {
			return -1;
		}
	}

    /**
     * Write information of trigger description
     *
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $conf, $langs;

        require_once __DIR__ . '/../digiriskelement.class.php';
        require_once __DIR__ . '/risk.class.php';

        $ret = parent::getTriggerDescription($object);

        $digiriskelement = new DigiriskElement($this->db);
        $digiriskelement->fetch($object->fk_element);

        $ret .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        $ret .= $langs->trans('RiskSignCategory') . ' : ' . $this->getRiskSignCategory($object, 'name') . '<br>';

        if (dol_strlen($object->applied_on) > 0) {
            $digiriskelement->fetch($object->applied_on);
            $ret .= $langs->trans('RiskSignSharedWithEntityRefLabel', $object->ref) . ' S' . $conf->entity . ' ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        }

        return $ret;
    }
}
