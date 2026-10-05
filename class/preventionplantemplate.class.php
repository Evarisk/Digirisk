<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    class/preventionplantemplate.class.php
 * \ingroup digiriskdolibarr
 * \brief   CRUD class of the prevention plan templates ("trames").
 *
 * Beaucoup de plans de prevention se ressemblent (maintenance annuelle, nettoyage...) : une trame
 * garde le contenu reutilisable d'un plan existant (motif, tags, risques et protections,
 * certifications, horaires) pour pre-remplir le formulaire de la PWA au prochain plan. Ce qui est
 * propre a une intervention (entreprise, responsable, dates, signatures, photos) n'y est pas.
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for PreventionPlanTemplate
 */
class PreventionPlanTemplate extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object
     */
    public $element = 'preventionplantemplate';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'digiriskdolibarr_preventionplan_template';

    /**
     * @var int<0, 1>|string Does this object support multicompany module ?
     */
    public $ismultientitymanaged = 1;

    /**
     * @var string Name of icon for the template
     */
    public string $picto = 'fontawesome_fa-copy_fas_#d35968';

    public const STATUS_DELETED = -1;
    public const STATUS_ACTIVE  = 1;

    /**
     * Keys of the form pre-fill a template may carry: anything else is ignored when it is applied
     */
    public const CONTENT_KEYS = [
        'label', 'categories', 'risks', 'certifications',
        'schedule_monday_am', 'schedule_monday_pm', 'schedule_tuesday_am', 'schedule_tuesday_pm',
        'schedule_wednesday_am', 'schedule_wednesday_pm', 'schedule_thursday_am', 'schedule_thursday_pm',
        'schedule_friday_am', 'schedule_friday_pm', 'schedule_saturday_am', 'schedule_saturday_pm',
        'schedule_sunday_am', 'schedule_sunday_pm',
    ];

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,  'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 0],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => 0],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 0, 'default' => 1, 'index' => 1],
        'label'         => ['type' => 'varchar(255)', 'label' => 'Label',            'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => 0],
        'content'       => ['type' => 'text',         'label' => 'Content',          'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 0],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int|null Technical id
     */
    public $rowid;

    public $entity;
    public $date_creation;
    public $tms;
    public $status;

    /**
     * @var string|null Name of the template, as chosen when saving it
     */
    public $label;

    /**
     * @var string|null Form pre-fill as JSON (see CONTENT_KEYS)
     */
    public $content;

    public $fk_user_creat;
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Templates offered when creating a plan, by name.
     *
     * @return PreventionPlanTemplate[] Templates, empty when none was saved
     */
    public function fetchTemplates(): array
    {
        $records = $this->fetchAll('ASC', 'label', 0, 0, ['customsql' => 't.status = ' . self::STATUS_ACTIVE]);

        $templates = [];
        foreach (is_array($records) ? $records : [] as $record) {
            if ($record instanceof self) {
                $templates[] = $record;
            }
        }

        return $templates;
    }

    /**
     * Load the template carrying a name, so that saving under an existing name updates it.
     *
     * @param  string $label Name of the template
     * @return int           > 0 if found, 0 if not, < 0 if KO
     */
    public function fetchByLabel(string $label): int
    {
        return $this->fetch(0, '', " AND t.label = '" . $this->db->escape($label) . "' AND t.status = " . self::STATUS_ACTIVE);
    }

    /**
     * Form pre-fill carried by the template, limited to the keys a template may set.
     *
     * @return array Pre-fill, empty when the content cannot be read
     */
    public function getContent(): array
    {
        $content = json_decode((string) $this->content, true);

        return is_array($content) ? array_intersect_key($content, array_flip(self::CONTENT_KEYS)) : [];
    }

    /**
     * Take the reusable content of a prevention plan.
     *
     * @param  DoliDB         $db   Database handler
     * @param  PreventionPlan $plan Plan the template is saved from
     * @return void
     * @throws Exception
     */
    public function setContentFromPlan(DoliDB $db, PreventionPlan $plan)
    {
        require_once __DIR__ . '/../lib/digiriskdolibarr_preventionplan.lib.php';

        $this->content = json_encode(array_intersect_key(digiriskPreventionPlanReusableContent($db, $plan), array_flip(self::CONTENT_KEYS)));
    }
}
