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
 * \file    class/digiriskelement.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for DigiriskElement (Create/Read/Update/Delete).
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

// Load Saturne libraries.
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/riskanalysis/risk.class.php';

/**
 * Class for DigiriskElement.
 */
class DigiriskElement extends SaturneObject
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'digiriskelement';

    /**
     * @var string String with name of icon for digiriskelement.
     */
    public string $picto = 'fontawesome_fa-network-wired_fas_#d35968';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'digiriskdolibarr_digiriskelement';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table.
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public $isextrafieldmanaged = 1;

    public const STATUS_TRASHED   = -2;
    public const STATUS_DELETED   = -1;
    public const STATUS_VALIDATED = 1;
    public const STATUS_ARCHIVED  = 3;

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = [
        'rowid'            => ['type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'comment' => "Id"],
        'ref'              => ['type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"],
        'ref_ext'          => ['type' => 'varchar(128)', 'label' => 'RefExt', 'enabled' => '1', 'position' => 20, 'notnull' => 0, 'visible' => 0,],
        'entity'           => ['type' => 'integer', 'label' => 'Entity', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => -1,],
        'date_creation'    => ['type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 40, 'notnull' => 1, 'visible' => -2,],
        'tms'              => ['type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 50, 'notnull' => 0, 'visible' => -2,],
        'import_key'       => ['type' => 'integer', 'label' => 'ImportId', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => -2,],
        'status'           => ['type' => 'smallint', 'label' => 'Status', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'default' => 1, 'visible' => 1, 'index' => 1,],
        'label'            => ['type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 80, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth400', 'showoncombobox' => '1',],
        'description'      => ['type' => 'textarea', 'label' => 'Description', 'enabled' => '1', 'position' => 90, 'notnull' => 0, 'visible' => 3,],
        'element_type'     => ['type' => 'varchar(50)', 'label' => 'ElementType', 'enabled' => '1', 'position' => 100, 'notnull' => -1, 'visible' => 1,],
        'photo'            => ['type' => 'varchar(255)', 'label' => 'Photo', 'enabled' => '1', 'position' => 105, 'notnull' => -1, 'visible' => -2,],
        'show_in_selector' => ['type' => 'boolean', 'label' => 'ShowInSelectOnPublicTicketInterface', 'enabled' => '1', 'position' => 106, 'notnull' => 1, 'visible' => 1, 'default' => 1,],
        'fk_user_creat'    => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 110, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',],
        'fk_user_modif'    => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 120, 'notnull' => -1, 'visible' => -2,],
        'fk_parent'        => ['type' => 'integer', 'label' => 'ParentElement', 'enabled' => '1', 'position' => 130, 'notnull' => 1, 'visible' => 1, 'default' => 0,],
        'fk_standard'      => ['type' => 'integer', 'label' => 'Standard', 'enabled' => '1', 'position' => 140, 'notnull' => 1, 'visible' => 0, 'default' => 1,],
        'ranks'            => ['type' => 'integer', 'label' => 'Order', 'enabled' => '1', 'position' => 150, 'notnull' => 1, 'visible' => 0],
    ];

    public $rowid;
    public $ref;
    public $ref_ext;
    public $entity;
    public $date_creation;
    public $tms;
    public $import_key;
    public $status;
    public $label;
    public $description;
    public $element_type;
    public $photo;
    public $show_in_selector;
    public $fk_user_creat;
    public $fk_user_modif;
    public $fk_parent;
    public $fk_standard;
    public $ranks;

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
     * Create object into database.
     *
     * @param  User $user      User that creates.
     * @param  bool $notrigger false = launch triggers after, true = disable triggers.
     * @return int             0 < if KO, ID of created object if OK.
     */
    public function create(User $user, bool $notrigger = false): int
    {
        global $conf;
        if (empty($this->ref)) {
            $type           = 'DIGIRISKDOLIBARR_' . dol_strtoupper($this->element_type) . '_ADDON';
            $objectMod = $conf->global->$type;
            $numberingModules = [
                'digiriskelement/' . $this->element_type => $objectMod
            ];
            list($refDigiriskElementMod) = saturne_require_objects_mod($numberingModules, 'digiriskdolibarr');

            $ref = $refDigiriskElementMod->getNextValue($this);
            $this->ref = $ref;
        }
        $this->element     = $this->element_type . '@digiriskdolibarr';
        $this->fk_standard = $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD;
        $this->status      = 1;

        return $this->createCommon($user, $notrigger);
    }

    /**
     * Load ordered flat list of DigiriskElement in memory from the database
     *
     * @param  int   $parentID                 Id parent object
     * @param  array $fetchedDigiriskElements  Preloaded digirisk elements (optional)
     * @param  bool $addCurrentDigiriskElement Add current digirisk element info
     * @return array                           Flat list with depth
     * @throws Exception
     */
    public function fetchDigiriskElementFlat(int $parentID = 0, array $fetchedDigiriskElements = [], string $multiEntityManagement = 'all', bool $addCurrentDigiriskElement = false): array
    {
        $digiriskElements = $fetchedDigiriskElements ?: self::getActiveDigiriskElements($multiEntityManagement);
        if (!is_array($digiriskElements) || empty($digiriskElements)) {
            return [];
        }

        $tree = recurse_tree($parentID, 0, $digiriskElements, $addCurrentDigiriskElement);

        return flatten_tree($tree);
    }

    /**
     * Delete object in database.
     *
     * @param  User $user       User that deletes.
     * @param  bool $notrigger  false = launch triggers after, true = disable triggers.
     * @param  bool $softDelete Don't delete object.
     * @return int              0 < if KO, > 0 if OK.
     */
    public function delete(User $user, bool $notrigger = false, bool $softDelete = true): int
    {
        global $conf;

        $this->fk_parent = $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH;
        $this->status    = self::STATUS_TRASHED;

        $result = $this->update($user, true);
        if ($result > 0 && !empty($conf->global->DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_DELETE)) {
            $this->call_trigger('DIGIRISKELEMENT_DELETE', $user);
        }

        return $result;
    }

    /**
     * Sets object to supplied categories.
     *
     * Deletes object from existing categories not supplied.
     * Adds it to non-existing supplied categories.
     * Existing categories are left untouched.
     *
     * @param  int[]|int $categories Category or categories IDs.
     * @return float|int
     */
    public function setCategories($categories)
    {
        return 1;
    }

    /**
     * Load digirisk element infos
     *
     * @param  array     $moreParam More param (tmparray)
     * @return array     $array     Array of current and shared digirisk elements
     * @throws Exception
     */
    public  function loadDigiriskElementInfos(array $moreParam = []): array
    {
        global $conf;

        $array = [];

        $array['current']['digiriskElements'] = $this->fetchDigiriskElementFlat(0, [], 'current');
        if (empty($array['current']['digiriskElements'])) {
            $array['current']['digiriskElements'] = [];
        }

        $array['shared']['digiriskElements'] = [];
        if ($moreParam['tmparray']['showSharedRisk_nocheck']) {
            $array['shared']['digiriskElements'] = $this->fetchDigiriskElementFlat(0, [], 'shared');
        }

        $digiriskElements = array_merge($array['current']['digiriskElements'], $array['shared']['digiriskElements']);
        foreach ($digiriskElements as $digiriskElement) {
            $entity = ($digiriskElement['object']->entity == $conf->entity) ? 'current' : 'shared';
            if ($digiriskElement['object']->element_type == 'groupment') {
                $array[$entity]['nbGroupment']++;
            } else {
                $array[$entity]['nbWorkUnit']++;
            }
        }

        return $array;
    }

    /**
     *  Output html form to select a digirisk element.
     *
     * @param  string 		$selected 			Preselected type
     * @param  string 		$htmlname 			Name of field in form
     * @param  array 		$filter 			Optional filters criteras (example: 's.rowid <> x', 's.client in (1,3)')
     * @param  string 		$showempty 			Add an empty field (Can be '1' or text to use on empty line like 'SelectThirdParty')
     * @param  int 			$forcecombo 		Force to use standard HTML select component without beautification
     * @param  array 		$events 			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * @param  int 			$outputmode 		0=HTML select string, 1=Array
     * @param  int 			$limit 				Limit number of answers
     * @param  string 		$morecss 			Add more css styles to the SELECT component
     * @param  int	 		$current_element 	id of current digiriskelement
     * @param  bool 		$multiple 			add [] in the name of element and add 'multiple' attribut
     * @param  int 			$noroot  			Don't show root element
     * @param  string 		$contextpage 		Context page
     * @param  bool 		$multientitymanaged Multi entity filter
     * @param  bool 		$hideref 			Hide ref
     * @return string 							HTML string with
     * @throws Exception
     */
    public function selectDigiriskElementList($selected = '', $htmlname = 'fk_element', $filter = [], $showempty = '1', $forcecombo = 0, $events = array(), $outputmode = 0, $limit = 0, $morecss = 'minwidth100 maxwidth300', $current_element = 0, $multiple = false, $noroot = 0, $contextpage = '', $multientitymanaged = true, $hideref  = false)
    {
        global $conf, $form, $langs;

        if (dol_strlen($filter['customsql'])) {
            $filter['customsql'] .= ' AND t.rowid != ' . ($this->id ?? 0);
        }

        $objectList           = saturne_fetch_all_object_type('digiriskelement', '', '', $limit, 0, $filter, 'AND', false, $multientitymanaged);
        $digiriskElementsData = [];
        if ($noroot == 0) {
            $digiriskElementsData[0] = $langs->trans('Root') . ' : ' . $conf->global->MAIN_INFO_SOCIETE_NOM ;
        }
        if (is_array($objectList) && !empty($objectList)) {
            foreach ($objectList as $digiriskElement) {
                $digiriskElementsData[$digiriskElement->id] = ($hideref ? '' : $digiriskElement->ref . ' - ') . $digiriskElement->label;
            }
        }

        return $form::selectarray($htmlname, $digiriskElementsData, $selected, $showempty, 0, 0, '', 0, 0, 0, '', $morecss);
    }

    /**
     * check if parent digirisk element not exists for a digirisk element
     *
     * @param  int       $limit Limit
     * @return array|int        Int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function checkNotExistsDigiriskElementForParentDigiriskElement(int $limit = 0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql  = 'SELECT ';
        $sql .= $this->getFieldList('t');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE !EXISTS';
        $sql .= ' ( SELECT ';
        $sql .= $this->getFieldList('d');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as d';
        $sql .= ' WHERE d.rowid = t.fk_parent )';
        $sql .= ' AND t.fk_parent > 0';

        $records = [];
        $resql   = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new $this($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

    /**
     * Return fk_object from wp_digi_id extrafields
     *
     * @param $wp_digi_id
     * @return array|int                 int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function fetch_id_from_wp_digi_id($wp_digi_id)
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql  = 'SELECT ';
        $sql .= ' *';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . '_extrafields as t';
        $sql .= ' WHERE wp_digi_id =' . $wp_digi_id;
        $sql .= ' AND entity =' . $conf->entity;

        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i   = 0;
            $obj = new stdClass();
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $i++;
            }
            $this->db->free($resql);

            return $obj->fk_object;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

    /**
     *  Return list of deleted elements for all entities
     *
     * 	@return    array  Array with ids
     * 	@throws Exception
     */
    public function getMultiEntityTrashList()
    {
        $this->ismultientitymanaged = 0;
        $objects = $this->fetchAll('',  'ranks', 0,0, array('customsql' => ' status > 0'));
        $digiriskelement_trashes = $this->fetchAll('',  'ranks', 0,0, array('customsql' => ' status = 0'));
        $this->ismultientitymanaged = 1;
        if (is_array($digiriskelement_trashes) && !empty($digiriskelement_trashes)) {
            $ids          = [];
            foreach($digiriskelement_trashes as $digiriskelement_trash) {
                $recurse_tree = recurse_tree($digiriskelement_trash->id, 0, $objects);
                $ids[$digiriskelement_trash->id] = $digiriskelement_trash->id;

                array_walk_recursive($recurse_tree, function ($item) use (&$ids) {
                    if (is_object($item)) {
                        $ids[$item->id] = $item->id;
                    }
                }, $ids);
            }
            return $ids;
        } else {
            return array();
        }
    }

    /**
     * Return SQL filter for trash exclusion
     *
     * @return string
     * @throws Exception
     */
    public function getTrashExclusionSqlFilter(): string
    {
        $filter                = '';
        $digiriskElementTrashs = $this->getMultiEntityTrashList();
        if (!empty($digiriskElementTrashs) && is_array($digiriskElementTrashs)) {
            $filter = '(';
            foreach (array_keys($digiriskElementTrashs) as $digiriskElementTrash) {
                $filter .= $digiriskElementTrash . ', ';
            }
            if (preg_match('/, /', $filter)) {
                $filter = rtrim($filter, ', ');
            }
            $filter .= ')';
        }

        return $filter;
    }

    /**
     *  Return list of parent ids for an element
     *
     * 	@return    array  Array with ids
     * 	@throws Exception
     */
    public function getBranch($parent_id)
    {
        $object = new self($this->db);
        $object->fetch($parent_id);
        $branch_ids = array($parent_id);

        while ($object->fk_parent > 0) {
            $branch_ids[] = $object->fk_parent;
            $object->fetch($object->fk_parent);
        }
        return $branch_ids;
    }

    /**
     * Get active digirisk elements without trash
     *
     * @param  string    $multiEntityManagement                        Multi entity management (all, current, shared, entities)
     * @param  array     $moreParams                                   More params (filter SQL)
     * @return array|int $digiriskElements|$digiriskElementsByEntities int < 0 if KO, Array of digirisk elements or digirisk elements by entities
     * @throws Exception
     */
    public function getActiveDigiriskElements(string $multiEntityManagement = 'current', array $moreParams = [])
    {
        global $conf;

        $digiriskElements =  $this->fetchAll('',  '',  0,  0, ['customsql' => 't.status = ' . self::STATUS_VALIDATED . ($moreParams['filter'] ?? '')]);
        if (!is_array($digiriskElements) || empty($digiriskElements)) {
            return -1;
        }

        if ($multiEntityManagement == 'all') {
            return $digiriskElements;
        }

        $digiriskElementsByEntities = [];
        foreach ($digiriskElements as $key => $digiriskElement) {
            if ($multiEntityManagement == 'current' && ($digiriskElement->entity == $conf->entity || (!isModEnabled('multicompany') && empty($digiriskElement->entity)))) {
                $digiriskElementsByEntities[$key] = $digiriskElement;
            }
            if ($multiEntityManagement == 'shared' && $digiriskElement->entity != $conf->entity) {
                $digiriskElementsByEntities[$key] = $digiriskElement;
            }
            if ($multiEntityManagement == 'entities') {
                $digiriskElementsByEntities[$digiriskElement->entity][$key] = $digiriskElement;
            }
        }

        return $digiriskElementsByEntities;
    }

    /**
     * Return the status.
     *
     * @param  int    $status ID status.
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto.
     * @return string         Label of status.
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_VALIDATED]      = $langs->transnoentitiesnoconv('Validated');

            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status4';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Return pictos array.
     *
     * @return string         Picto html tags array.
     */
    public function getPicto(): array
    {
        global $conf;

        $numberingModules = [
            'digiriskelement/groupment' => $conf->global->DIGIRISKDOLIBARR_GROUPMENT_ADDON,
            'digiriskelement/workunit' => $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON
        ];
        list($groupmentMod, $workunitMod) = saturne_require_objects_mod($numberingModules, 'digiriskdolibarr');

        $groupmentPrefix = $groupmentMod->prefix;
        $workunitPrefix  = $workunitMod->prefix;

        $pictos = [
            'groupment' => '<span class="ref" style="font-size: 10px; color: #fff; text-transform: uppercase; font-weight: 600; display: inline-block; background: #263C5C; padding: 0.2em 0.4em; line-height: 10px !important">'. $groupmentPrefix .'</span> ',
            'workunit' => '<span class="ref" style="background: #0d8aff;  font-size: 10px; color: #fff; text-transform: uppercase; font-weight: 600; display: inline-block;; padding: 0.2em 0.4em; line-height: 10px !important">'. $workunitPrefix .'</span> '
        ];

        return $pictos;
    }

    /**
     * Return banner tab content.
     *
     * @return array
     */
    public function getBannerTabContent(): array
    {
        global $conf, $db, $langs;

        require_once __DIR__ . '/digiriskstandard.class.php';

        $digiriskstandard = new DigiriskStandard($db);

        // ParentElement
        $parent_element = new self($db);
        $result         = $parent_element->fetch($this->fk_parent);
        if ($result > 0) {
            $morehtmlref .= $langs->trans("Description") . ' : ' . $this->description;
            $morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $parent_element->getNomUrl(1, 'blank', 1);
        } else {
            $digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
            $morehtmlref .= $langs->trans("Description") . ' : ' . $this->description;
            $morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $digiriskstandard->getNomUrl(1, 'blank', 1);
        }
        $morehtmlref .= '<br>';
        $this->fetch($this->id);
        $riskType         = GETPOST('risk_type');
        $this->fk_project = $riskType == 'riskenvironmental' ? $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT : $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
        $moreParams['project']['disable_edit'] = 1;

        return [$morehtmlref, $moreParams];
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

        require_once __DIR__ . '/digiriskstandard.class.php';
        require_once __DIR__ . '/../../saturne/class/task/saturnetask.class.php';

        $digiriskStandard = new DigiriskStandard($this->db);
        $digiriskStandard->fetch($object->fk_standard);

        $ret = parent::getTriggerDescription($object);

        if (!empty($object->fk_parent)) {
            require_once __DIR__ . '/digiriskelement.class.php';
            $digiriskElement = new DigiriskElement($this->db);
            $digiriskElement->fetch($object->fk_parent);
            $ret .= $langs->trans('ParentElement') . ' : ' .  $digiriskElement->ref . ' - ' . $digiriskElement->label . '<br/>';
        }

        $ret .= $langs->trans('Standard') . ' : ' . $digiriskStandard->ref . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM . '<br/>';
        $ret .= $langs->trans('Photo') . ' : ' . (!empty($object->photo) ? $object->photo : 'N/A') . '<br>';
        $ret .= $langs->trans('ElementType') . ' : ' . $langs->trans($object->element_type) . '<br>';
        ($object->ranks != 0 ? $ret .= $langs->trans('Order') . ' : ' . $object->ranks . '<br>' : '');
        $ret .= $langs->trans('ShowInSelectOnPublicTicketInterface') . ' : ' . ($object->show_in_selector ? $langs->trans('Yes') : $langs->trans('No')) . '<br>';

        return $ret;
    }

    /**
     * Load dashboard info digirisk element
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $langs;

        $confName        = strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode(getDolUserString($confName));
        $array = ['graphs' => [], 'disabledGraphs' => []];

        $digiriskElements = $this->fetchDigiriskElementFlat(GETPOSTISSET('id') ? GETPOST('id') : 0);

        if (empty($dashboardConfig->graphs->DigiriskElementsRepartitionByDepth->hide)) {
            $array['graphs'][] = $this->getDigiriskElementListsByDepth($digiriskElements);
        } else {
            $array['disabledGraphs']['DigiriskElementsRepartitionByDepth'] = $langs->transnoentities('DigiriskElementsRepartitionByDepth');
        }
        if (empty($dashboardConfig->graphs->RisksRepartitionByDigiriskElement->hide)) {
            $array['graphs'][] = $this->getRisksByDigiriskElement('risk', $digiriskElements);
        } else {
            $array['disabledGraphs']['RisksRepartitionByDigiriskElement'] = $langs->transnoentities('RisksRepartitionByDigiriskElement');
        }

        return $array;
    }

    /**
     * Get list of risk by digirisk element
     *
     * @param string $riskType Risk type (risk, riskenvironmental or ...)
     *
     * @return array
     * @throws Exception
     */
    public function getRisksByDigiriskElement(string $riskType = 'risk', array $digiriskElements = []): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('RisksRepartitionByDigiriskElement');
        $array['name']  = 'RisksRepartitionByDigiriskElement';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        // Get current digirisk element and add data in $digiriskElements array
        if (GETPOSTISSET('id')) {
            $currentDigiriskElement = $this->fetchAll('', '', 0, 0, ['customsql' => 't.rowid = ' . GETPOST('id')]);
            if (is_array($currentDigiriskElement) && !empty($currentDigiriskElement)) {
                $currentDigiriskElement = array_shift($currentDigiriskElement);

                $array['title'] = $langs->transnoentities('RisksRepartitionByDigiriskElement', ': ' . $currentDigiriskElement->ref . ' - ' . $currentDigiriskElement->label);

                $digiriskElement[$currentDigiriskElement->id]['object'] = $currentDigiriskElement;
                $digiriskElements = array_merge($digiriskElement, $digiriskElements);
            }
        }

        if (!empty($digiriskElements)) {
            foreach ($digiriskElements as $digiriskElement) {
                $risks = saturne_fetch_all_object_type('Risk', '', '', 0, 0, ['customsql' => 't.status = ' . Risk::STATUS_VALIDATED . ' AND t.entity = ' . $conf->entity . ' AND t.type = "' . $riskType . '" AND t.fk_element = ' . $digiriskElement['object']->id]);
                if (is_array($risks) && !empty($risks)) {
                    $array['labels'][$digiriskElement['object']->id] = [
                        'label' => $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label,
                        'color' => SaturneDashboard::getColorRange($digiriskElement['object']->id)
                    ];
                    $array['data'][$digiriskElement['object']->id] = count($risks);
                }
            }
        }

        return $array;
    }

    /**
     * Get list of digirisk elements by depth
     *
     * @return array
     * @throws Exception
     */
    public function getDigiriskElementListsByDepth(array $digiriskElements = []): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('DigiriskElementsRepartitionByDepth', getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_DEPTH_GRAPH'));
        $array['name']  = 'DigiriskElementsRepartitionByDepth';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        $children = [];

        // Get current digirisk element and add data in $digiriskElements array
        if (GETPOSTISSET('id')) {
            $currentDigiriskElement = $this->fetchAll('', '', 0, 0, ['customsql' => 't.rowid = ' . GETPOST('id')]);
            if (is_array($currentDigiriskElement) && !empty($currentDigiriskElement)) {
                $currentDigiriskElement = array_shift($currentDigiriskElement);

                $array['title'] = $langs->transnoentities('DigiriskElementsRepartitionByDepth', ': ' . $currentDigiriskElement->ref . ' - ' . $currentDigiriskElement->label);

                $array['labels'][$currentDigiriskElement->id] = [
                    'label' => $currentDigiriskElement->ref . ' - ' . $currentDigiriskElement->label,
                    'color' => SaturneDashboard::getColorRange($currentDigiriskElement->id)
                ];
            }
        }

        if (!empty($digiriskElements)) {
            foreach ($digiriskElements as $digiriskElement) {
                if ($digiriskElement['depth'] <= getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_DEPTH_GRAPH') && $digiriskElement['object']->element_type == 'groupment') {
                    $array['labels'][$digiriskElement['object']->id] = [
                        'label' => $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label,
                        'color' => SaturneDashboard::getColorRange($digiriskElement['object']->id)
                    ];
                }
                if ($digiriskElement['depth'] <= getDolGlobalInt('DIGIRISKDOLIBARR_DIGIRISKELEMENT_DEPTH_GRAPH') + 1 && $digiriskElement['object']->fk_parent > 0) {
                    $children[] = $digiriskElement['object']->fk_parent;
                }
            }
            $array['data'] = array_count_values($children);
        }

        return $array;
    }
}
