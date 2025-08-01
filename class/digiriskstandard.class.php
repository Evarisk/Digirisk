<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskstandard.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a CRUD class file for DigiriskStandard (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for DigiriskStandard
 */
class DigiriskStandard extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object
     */
    public $element = 'digiriskstandard';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table)
     */
    public $table_element = 'digiriskdolibarr_digiriskstandard';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, field@table = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes.
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string Name of icon for digiriskstandard. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'digiriskstandard@digiriskdolibarr' if picto is file 'img/object_digiriskstandard.png'
     */
    public string $picto = 'fontawesome_fa-sitemap_fas_#d35968';

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price', 'stock',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
     * 'length' the length of field. Example: 255, '24,8'
     * 'label' the translation key
     * 'langfile' the key of the language file for translation
     * 'alias' the alias used into some old hard coded SQL requests
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
     * 'position' is the sort order of field
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0)
     * 'visible' says if field is visible in list (Examples: 0 = Not visible, 1 = Visible on list and create/update/view forms, 2 = Visible on list only, 3 = Visible on create/update/view form only (not list), 4 = Visible on list and update/view form only (not create). 5 = Visible on list and view only (not create/not update). 6=visible on list and create/view form (not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'alwayseditable' says if field can be modified also when status is not draft (1 or 0)
     * 'default' is a default value for creation (can still be overwritten by the setup of default values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created
     * 'index' if we want an index in database
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...)
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'placeholder' to set the placeholder of a varchar field
     * 'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array(0 => 'Draft', 1 => 'Active', -1 => 'Cancel'). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1
     * 'comment' is not used. You can store here any text of your choice. It is not used by application
     * 'validate' is 1 if you need to validate with $this->validateField() Need MAIN_ACTIVATE_VALIDATION_RESULT
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1 = picto after label, 2 = picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    // BEGIN MODULEBUILDER PROPERTIES
    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'          => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'            => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => 'Reference of object'],
        'ref_ext'        => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => -2],
        'entity'         => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => -2, 'index' => 1],
        'date_creation'  => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => -2],
        'tms'            => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => -2],
        'import_key'     => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => -2],
        'status'         => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => -2, 'index' => 1],
        'description'    => ['type' => 'html',         'label' => 'Description',      'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => -2],
        'fk_user_creat'  => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 90,  'notnull' => 1, 'visible' => -2, 'index' => 1, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'  => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -2, 'index' => 1, 'foreignkey' => 'user.rowid']
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string Audit end date
     */
    public string $audit_start_date;

    /**
     * @var string Audit start date
     */
    public string $audit_end_date;

    /**
     * @var string|null Description
     */
    public ?string $description;

    /**
     * @var string|null Method
     */
    public ?string $method;

    /**
     * @var string|null Sources
     */
    public ?string $sources;

    /**
     * @var string|null Important notes
     */
    public ?string $important_notes;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    // END MODULEBUILDER PROPERTIES

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Return banner tab content
     *
     * @return array
     */
    public function getBannerTabContent() : array
    {
        $moreHtmlRef            = '';
        $moreParams['moreHtml'] = 'none';

        return [$moreHtmlRef, $moreParams];
    }
}
