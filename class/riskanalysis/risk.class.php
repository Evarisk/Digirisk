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
 * \file        class/riskanalysis/risk.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a CRUD class file for Risk (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../digiriskelement.class.php';
dol_include_once('/saturne/class/task/saturnetask.class.php');
require_once __DIR__ . '/riskassessment.class.php';

// Load Saturne libraries
dol_include_once('/saturne/class/saturneobject.class.php');

/**
 * Class for Risk
 */
class Risk extends SaturneObject
{
	/**
	 * @var string Module name
	 */
	public $module = 'digiriskdolibarr';

	/**
	 * @var string Element type of object
	 */
	public $element = 'risk';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
	 */
	public $table_element = 'digiriskdolibarr_risk';

	/**
	 * @var int Does this object support multicompany module ?
	 * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

    /**
     * @var int Pseudo level of the cotation scale gathering the risks without any validated assessment.
     *          Negative so it never collides with a real level, -1 being the empty value of the list filter.
     */
    public const COTATION_NOT_ASSESSED = -2;

    /**
     * @var int Position of the psychosocial danger category, the only one carrying sub-categories.
     */
    public const PSYCHOSOCIAL_CATEGORY_POSITION = 17;

    /**
     * @var int Pseudo position of the psychosocial risks filed without any factor.
     *          Negative so it never collides with a real sub-category position, which starts at 0.
     */
    public const SUB_CATEGORY_NOT_SPECIFIED = -1;

	/**
	 * @var string String with name of icon for risk. Must be the part after the 'object_' into object_risk.png
	 */
	public string $picto = 'fontawesome_fa-exclamation-triangle_fas_#d35968';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
	 */
    public $fields = [
        'rowid'         => ['type' => 'integer',      'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1, 'notnull' => 1,  'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => "Id"],
        'ref'           => ['type' => 'varchar(128)', 'label' => 'Ref',              'enabled' => 1, 'position' => 10, 'notnull' => 1,  'visible' => 4, 'noteditable' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'comment' => "Reference of object"],
        'ref_ext'       => ['type' => 'varchar(128)', 'label' => 'RefExt',           'enabled' => 1, 'position' => 20, 'notnull' => 0,  'visible' => -2],
        'entity'        => ['type' => 'integer',      'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1,  'visible' => -2],
        'date_creation' => ['type' => 'datetime',     'label' => 'DateCreation',     'enabled' => 1, 'position' => 40, 'notnull' => 1,  'visible' => -2],
        'tms'           => ['type' => 'timestamp',    'label' => 'DateModification', 'enabled' => 1, 'position' => 50, 'notnull' => 0,  'visible' => -2],
        'import_key'    => ['type' => 'varchar(14)',  'label' => 'ImportId',         'enabled' => 1, 'position' => 60, 'notnull' => -1, 'visible' => -2],
        'status'        => ['type' => 'smallint',     'label' => 'Status',           'enabled' => 1, 'position' => 70, 'notnull' => 0,  'visible' => -2],
        'category'      => ['type' => 'integer',      'label' => 'RiskCategory',     'enabled' => 1, 'position' => 80, 'notnull' => 0,  'visible' => 1, 'csslist' => 'risk-category'],
        'sub_category'  => ['type' => 'integer',      'label' => 'SubCategory',      'enabled' => 1, 'position' => 85, 'notnull' => 0, 'visible' => 0],
        'description'   => ['type' => 'text',         'label' => 'Description',      'enabled' => 'getDolGlobalInt("DIGIRISKDOLIBARR_RISK_DESCRIPTION")', 'position' => 90, 'notnull' => 0, 'visible' => '$conf->global->DIGIRISKDOLIBARR_RISK_DESCRIPTION', 'csslist' => 'risk-description'],
        'type'          => ['type' => 'varchar(255)', 'label' => 'Type',             'enabled' => 1, 'position' => 100, 'notnull' => 1,  'visible' => 0, 'default' => '(PROV)'],
        'fk_user_creat' => ['type' => 'integer:User:user/class/user.class.php',                                   'label' => 'UserAuthor',    'enabled' => 1, 'position' => 110, 'notnull' => 1,  'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_user_modif' => ['type' => 'integer:User:user/class/user.class.php',                                   'label' => 'UserModif',     'enabled' => 1, 'position' => 120, 'notnull' => -1, 'visible' => -2, 'foreignkey' => 'user.rowid'],
        'fk_element'    => ['type' => 'integer:DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php', 'label' => 'ParentElement', 'enabled' => 1, 'position' => 130,   'notnull' => 1,  'visible' => 1, 'csslist' => 'minwidth200 maxwidth300'],
        'fk_projet'     => ['type' => 'integer:Project:projet/class/project.class.php',                           'label' => 'Projet',        'enabled' => 1, 'position' => 140, 'notnull' => 1,  'visible' => -2, 'foreignkey' => 'projet.rowid'],
    ];


	public $rowid;

	public $ref;
	public $ref_ext;
	public $entity;
	public $date_creation;
	public $tms;
	public $import_key;
	public $status;
	public $category;
    public $sub_category;
	public $description;
    public $type = 'risk';
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_element;
	public $fk_projet;
	public $lastEvaluation;
	public $appliedOn;
	public $riskAssessmentRef;
	public $riskAssessmentDateCreation;
	public $riskAssessmentCotation;
	public $riskAssessmentDate;

    private $cotations = [];

    /**
     * @var string Type of risk the dashboard is filtered on, read by the graphs to link to the matching list
     */
    protected string $dashboardRiskType = 'risk';

    /**
     * @var array|null Filters added to the risk list the graphs link to, null when the graphs must not link at all
     */
    protected ?array $dashboardListFilters = [];

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
        global $langs;

        $this->cotations = [
            1 => [
                'label' => $langs->transnoentities('GreyRisk'),
                'color' => '#ececec',
                'start' => 0,
                'end'   => 47
            ],
            2 => [
                'label' => $langs->transnoentities('OrangeRisk'),
                'color' => '#e9ad4f',
                'start' => 48,
                'end'   => 50
            ],
            3 => [
                'label' => $langs->transnoentities('RedRisk'),
                'color' => '#e05353',
                'start' => 51,
                'end'   => 79
            ],
            4 => [
                'label' => $langs->transnoentities('BlackRisk'),
                'color' => '#2b2b2b',
                'start' => 80,
                'end'   => 100
            ]
        ];

        $riskType = GETPOST('risk_type');
        if ($riskType == 'riskenvironmental') {
            $this->type  = 'riskenvironmental';
            $this->picto = 'fontawesome_fa-leaf_fas_#d35968';
        }

		parent::__construct($db, $this->module, $this->element);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchFromParent(int $parent_id)
	{
		$filter = array('customsql' => 'fk_element=' . $this->db->escape($parent_id));
		return $this->fetchAll('', '', 0, 0, $filter, 'AND');
	}


    /**
     * Load risk infos
     *
     * filterRiskDate replaces the generic filter when the caller needs a condition on the
     * riskassessment table too: the shared filter only knows the "t" alias, and a risk
     * re-evaluated over the period never has its own date_creation nor tms touched — issue #4459
     *
     * @param  array     $moreParam More param (tmparray/filterRisk/filterRiskDate)
     * @return array     $array     Array of risks (current and shared)
     * @throws Exception
     */
    public function loadRiskInfos(array $moreParam): array
    {
        global $conf, $mc;

        $array = [];

        $riskAssessment = new RiskAssessment($this->db);

        $select       = ', ra.ref AS riskAssessmentRef, ra.date_creation AS riskAssessmentDateCreation, ra.cotation AS riskAssessmentCotation, ra.date_riskassessment AS riskAssessmentDate, ra.comment AS riskAssessmentComment, ra.photo AS riskAssessmentPhoto';
        $sharedSelect = $select . ', ee.fk_target AS fk_target';

        $moreSelects       = ['riskAssessmentRef', 'riskAssessmentDateCreation', 'riskAssessmentCotation', 'riskAssessmentDate', 'riskAssessmentComment', 'riskAssessmentPhoto'];
        $sharedMoreSelects = array_merge($moreSelects, ['fk_target']);

        $join  = ' INNER JOIN ' . $this->db->prefix() . $this->module . '_digiriskelement AS d ON d.rowid = t.fk_element';
        $join .= ' INNER JOIN ' . $this->db->prefix() . $this->module . '_riskassessment AS ra ON t.rowid = ra.fk_risk';

        $sharedJoin  = ' INNER JOIN ' . $this->db->prefix() . 'element_element AS ee ON (ee.fk_source = t.rowid AND ee.sourcetype = \'' . $this->module . '_risk\' AND ee.targettype = \'' . $this->module . '_digiriskelement\')';
        $sharedJoin .= ' INNER JOIN ' . $this->db->prefix() . $this->module . '_digiriskelement AS d ON (d.rowid = ee.fk_target AND d.entity = ' . $conf->entity . ')';
        $sharedJoin .= ' INNER JOIN ' . $this->db->prefix() . $this->module . '_riskassessment AS ra ON t.rowid = ra.fk_risk';

        $dateFilter    = $moreParam['filterRiskDate'] ?? ($moreParam['filter'] ?? '');
        $filter        = 't.status = ' . Risk::STATUS_VALIDATED . ' AND d.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND ra.status = ' . RiskAssessment::STATUS_VALIDATED .  $dateFilter . (!empty($moreParam['filterRisk']) ? $moreParam['filterRisk'] : ' AND t.type = \'risk\'');
        $currentFilter = $filter . ' AND t.entity = ' . $conf->entity;

        // Each breakdown below is only filled for the risks that feed it: riskBySubCategories and
        // psychosocialRisksByGPUT describe the psychosocial category alone, so an entity holding
        // risks but not a single psychosocial one left those two keys undeclared, and the document
        // models read them without a guard. Declare the whole shape once instead.
        $array['riskByEntities'] = [];
        foreach (['current', 'shared'] as $scope) {
            $array[$scope] = [
                'risks'                         => [],
                'riskByRiskAssessmentCotations' => [],
                'riskByRiskAssessmentLevels'    => [],
                'riskByCategories'              => [],
                'riskBySubCategories'           => [],
                'psychosocialRisksByGPUT'       => [],
                'riskTasks'                     => [],
                'totalRisks'                    => 0,
            ];
        }
        $array['shared']['projectEntities'] = [];

        $currentRisks = saturne_fetch_all_object_type('Risk', 'DESC', 'riskAssessmentCotation', 0, 0, ['customsql' => $currentFilter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (is_array($currentRisks)) {
            $array['current']['risks'] = $currentRisks;
        }

        // ShowInheritedRisksInDocuments promises "Activez cette option pour les afficher dans les
        // documents", and the element template says its risk table holds, depending on the
        // settings, the risks of the unit, the inherited ones and the shared ones. The setting lost
        // its last reader when this collection was standardised on loadRiskInfos() - issue #5120.
        // Only for a document scoped on one element: the risk assessment document passes no element
        // filter and already lists every risk of the entity.
        $array['inheritedDigiriskElements'] = [];
        if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS') && ($moreParam['object'] ?? null) instanceof DigiriskElement) {
            $inherited = $this->fetchInheritedRisks($moreParam, $join, $select, $moreSelects);
            if (!empty($inherited['risks'])) {
                // Both sides are keyed by risk id, so a risk reachable twice is kept once. The
                // template states the table is sorted by descending cotation, which the union of
                // two already sorted fetches would break
                $array['current']['risks'] += $inherited['risks'];
                uasort($array['current']['risks'], fn($first, $second) => $second->riskAssessmentCotation <=> $first->riskAssessmentCotation);
                $array['inheritedDigiriskElements'] = $inherited['digiriskElements'];
            }
        }

        if (!empty($moreParam['tmparray']['showSharedRisk_nocheck'])) {
            $sharedRisks = saturne_fetch_all_object_type('Risk', 'DESC', 'riskAssessmentCotation', 0, 0, ['customsql' => $filter], 'AND', false, true, false, $sharedJoin, [], $sharedSelect, $sharedMoreSelects);
            if (is_array($sharedRisks)) {
                $array['shared']['risks'] = $sharedRisks;
            }
        }

        $array['risks'] = array_merge($array['current']['risks'], $array['shared']['risks']);
        $nbTotalRisks = ['current' => 0,'shared' => 0];

        // A consolidated psychosocial risk carries one line per unit and keeps its factors in
        // rpsanswer, so it reaches the two tables below with no sub-category of its own. Read
        // its factors back here: without this, a unit assessed with the consolidated grid is
        // absent from the RPS tables of the document while it does hold an assessment.
        $psychosocialFactors = static::getConsolidatedPsychosocialFactors($array['risks']);

        foreach ($array['risks'] as $risk) {
            $riskAssessment->cotation = $risk->riskAssessmentCotation;
            $entity                   = ($risk->entity == $conf->entity || (!isModEnabled('multicompany') && empty($risk->entity))) ? 'current' : 'shared';

            $scale     = $riskAssessment->getEvaluationScale();
            $fkElement = $risk->fk_element;

            $array[$entity]['riskByRiskAssessmentLevels'][$scale]
                = $array[$entity]['riskByRiskAssessmentLevels'][$scale] ?? [];

            $array[$entity]['riskByRiskAssessmentCotations'][$fkElement]['totalRiskAssessmentCotations']
                = $array[$entity]['riskByRiskAssessmentCotations'][$fkElement]['totalRiskAssessmentCotations'] ?? 0;

            $array[$entity]['riskByRiskAssessmentCotations'][$fkElement][$scale]
                = $array[$entity]['riskByRiskAssessmentCotations'][$fkElement][$scale] ?? 0;

            $array[$entity]['riskByCategories'][$risk->category ?? ''][$scale]
                = $array[$entity]['riskByCategories'][$risk->category ?? ''][$scale] ?? 0;

            $array['riskByEntities'][$risk->entity ?? '']['nbTotalRisks']
                = $array['riskByEntities'][$risk->entity ?? '']['nbTotalRisks'] ?? 0;

            $array['riskByEntities'][$risk->entity ?? ''][$scale]
                = $array['riskByEntities'][$risk->entity ?? ''][$scale] ?? 0;

            $nbTotalRisks[$entity] = $nbTotalRisks[$entity] ?? 0;

            $array[$entity]['riskByRiskAssessmentLevels'][$scale][] = $risk;
            $array[$entity]['riskByRiskAssessmentCotations'][$fkElement]['totalRiskAssessmentCotations'] += $risk->riskAssessmentCotation;
            $array[$entity]['riskByRiskAssessmentCotations'][$fkElement][$scale]++;
            $array[$entity]['riskByCategories'][$risk->category ?? ''][$scale]++;
            // Both sub-category breakdowns only describe the psychosocial category, the single one
            // carrying sub-categories, and the risk assessment document is their only reader.
            // sub_category is NULL on every other risk and null >= 0 was true, so every risk of the
            // entity used to land in the RPS table, one line per element - issue #4724
            if ($risk->category == self::PSYCHOSOCIAL_CATEGORY_POSITION) {
                // A psychosocial risk can be filed without any factor: give it a key of its own rather
                // than dropping it, the category table leaves the psychosocial category to these tables
                $subCategory = is_numeric($risk->sub_category) ? (int) $risk->sub_category : self::SUB_CATEGORY_NOT_SPECIFIED;

                // One risk per unit, factors in rpsanswer: the risk feeds the tables through its
                // factors rather than through its own line, which carries no sub-category
                $riskFactors = $psychosocialFactors[$risk->id] ?? [];

                if ($subCategory == self::SUB_CATEGORY_NOT_SPECIFIED && !empty($riskFactors)) {
                    foreach ($riskFactors as $factorPosition => $factorCotation) {
                        $riskAssessment->cotation = $factorCotation;
                        $factorScale              = $riskAssessment->getEvaluationScale();

                        $array[$entity]['riskBySubCategories'][$factorPosition][$factorScale] = ($array[$entity]['riskBySubCategories'][$factorPosition][$factorScale] ?? 0) + 1;

                        $lastCotation = $array[$entity]['psychosocialRisksByGPUT'][$fkElement][$factorPosition][$risk->riskAssessmentDate] ?? null;

                        $array[$entity]['psychosocialRisksByGPUT'][$fkElement][$factorPosition][$risk->riskAssessmentDate] = is_null($lastCotation) ? $factorCotation : max($lastCotation, $factorCotation);
                    }

                    // Restore the cotation of the risk itself for the counters that follow
                    $riskAssessment->cotation = $risk->riskAssessmentCotation;
                } else {
                    $array[$entity]['riskBySubCategories'][$subCategory][$scale] = ($array[$entity]['riskBySubCategories'][$subCategory][$scale] ?? 0) + 1;

                    if ($subCategory != self::SUB_CATEGORY_NOT_SPECIFIED) {
                        // The table holds one cell per element and sub-category: when a session assesses
                        // several factors of the same family on the same date, as the RPS-DU grid does,
                        // the cell has to report the worst of them rather than the last one read
                        $lastCotation = $array[$entity]['psychosocialRisksByGPUT'][$fkElement][$subCategory][$risk->riskAssessmentDate] ?? null;

                        $array[$entity]['psychosocialRisksByGPUT'][$fkElement][$subCategory][$risk->riskAssessmentDate] = is_null($lastCotation) ? $riskAssessment->cotation : max($lastCotation, $riskAssessment->cotation);
                    }
                }
            }
            $array['riskByEntities'][$risk->entity]['nbTotalRisks']++;
            $array['riskByEntities'][$risk->entity][$scale]++;
            $nbTotalRisks[$entity]++;
        }

        $array['current']['totalRisks'] = $nbTotalRisks['current'];
        $array['shared']['totalRisks']  = $nbTotalRisks['shared'];

        if (is_object($mc) && isModEnabled('multicompany')) {
            $filter = 't.entity IN (' . $mc->getEntity('project') . ')';
        } else {
            $filter = 't.entity = ' . $conf->entity;
        }
        $filter        .= ' AND eft.fk_risk > 0';
        $array['tasks'] = saturne_fetch_all_object_type('saturneTask', '', '', 0, 0, ['customsql' => $filter], 'AND', true, false);
        if (!is_array($array['tasks'])) {
            $array['tasks'] = [];
        }

        foreach ($array['tasks'] as $task) {
            $entity = ($task->entity == $conf->entity) ? 'current' : 'shared';

            $array[$entity]['riskTasks'][$task->array_options['options_fk_risk']][] = $task;
            if ($task->entity != $conf->entity) {
                $array['shared']['projectEntities'][$task->entity] = '';
            }
        }

        return $array;
    }

    /**
     * Fetch the risks every ancestor of an element carries
     *
     * "Inherited" is what the element card shows under ShowInheritedRisksInListings: declared
     * higher in the tree and applying downwards. The parent chain is read from the validated
     * elements, which one cached query already holds, so an unvalidated element ends the walk -
     * its own risks are excluded by the status filter anyway, and those of its parents are then
     * left out too.
     *
     * @param  array  $moreParam   More param (object/filterRisk/filterRiskDate)
     * @param  string $join        Join on the owning element and the risk assessment
     * @param  string $select      Extra columns the risk segments read
     * @param  array  $moreSelects Names of those extra columns
     * @return array               ['risks' => risks keyed by id, 'digiriskElements' => the ancestors they belong to]
     * @throws Exception
     */
    private function fetchInheritedRisks(array $moreParam, string $join, string $select, array $moreSelects): array
    {
        global $conf;

        $digiriskElement  = new DigiriskElement($this->db);
        $digiriskElements = $digiriskElement->getActiveDigiriskElements('all');
        if (!is_array($digiriskElements)) {
            $digiriskElements = [];
        }

        $ancestorIds = [];
        $parentId    = (int) $moreParam['object']->fk_parent;
        // An id already seen means fk_parent forms a cycle: stop rather than loop forever
        while ($parentId > 0 && !isset($ancestorIds[$parentId])) {
            $ancestorIds[$parentId] = $parentId;
            $parentId               = isset($digiriskElements[$parentId]) ? (int) $digiriskElements[$parentId]->fk_parent : 0;
        }

        if (empty($ancestorIds)) {
            return ['risks' => [], 'digiriskElements' => []];
        }

        // The caller filter carries the element condition this fetch replaces. A date condition is
        // passed apart, in filterRiskDate, and still applies
        $filter = 't.status = ' . self::STATUS_VALIDATED
            . ' AND d.status = ' . DigiriskElement::STATUS_VALIDATED
            . ' AND ra.status = ' . RiskAssessment::STATUS_VALIDATED
            . ($moreParam['filterRiskDate'] ?? '')
            . (!empty($moreParam['filterRisk']) ? $moreParam['filterRisk'] : ' AND t.type = \'risk\'')
            . ' AND t.entity = ' . $conf->entity
            . ' AND t.fk_element IN (' . implode(',', $ancestorIds) . ')';

        $risks = saturne_fetch_all_object_type('Risk', 'DESC', 'riskAssessmentCotation', 0, 0, ['customsql' => $filter], 'AND', false, false, false, $join, [], $select, $moreSelects);

        // The row renderer names a risk's element from moreParam['digiriskElements']; a document
        // scoped on one element only knows that one, and would skip every inherited row
        $ancestorElements = [];
        foreach ($ancestorIds as $ancestorId) {
            if (isset($digiriskElements[$ancestorId])) {
                $ancestorElements[$ancestorId] = ['object' => $digiriskElements[$ancestorId], 'depth' => 0];
            }
        }

        return ['risks' => is_array($risks) ? $risks : [], 'digiriskElements' => $ancestorElements];
    }

    /**
     * Load the cotation each risk had before a given date — issue #4459
     *
     * Feeds the "risk going up or down" column of the audit report: comparing the cotation shown
     * for the period with the one that stood just before it is the only way to tell an aggravation
     * from an improvement. Everything is read in one query, a report walks hundreds of risks.
     *
     * date_riskassessment is optional in database, date_creation stands in when it is missing.
     * Superseded assessments (status 0) are kept, they are precisely the history being looked up.
     *
     * @param  DoliDB $db         Database handler
     * @param  array  $riskIds    Ids of the risks to look up
     * @param  int    $beforeDate Timestamp the cotation is read before
     * @return array              Cotation indexed by risk id, risks assessed for the first time are absent
     */
    public static function loadPreviousRiskAssessmentCotations(DoliDB $db, array $riskIds, int $beforeDate): array
    {
        $riskIds = array_filter(array_map('intval', $riskIds));
        if (empty($riskIds) || empty($beforeDate)) {
            return [];
        }

        $dateField = 'COALESCE(t.date_riskassessment, t.date_creation)';

        $sql  = 'SELECT t.fk_risk, t.cotation FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment AS t';
        $sql .= ' WHERE t.fk_risk IN (' . $db->sanitize(implode(',', $riskIds)) . ')';
        $sql .= ' AND t.status >= 0 AND t.cotation IS NOT NULL';
        $sql .= ' AND ' . $dateField . " < '" . $db->idate($beforeDate) . "'";
        // The last row read for a risk wins, so the assessment closest to the date is the one kept
        $sql .= ' ORDER BY t.fk_risk ASC, ' . $dateField . ' ASC, t.rowid ASC';

        $resql = $db->query($sql);
        if (!$resql) {
            dol_syslog(__METHOD__ . ' ' . $db->lasterror(), LOG_ERR);
            return [];
        }

        $previousCotations = [];
        while ($obj = $db->fetch_object($resql)) {
            $previousCotations[(int) $obj->fk_risk] = (int) $obj->cotation;
        }
        $db->free($resql);

        return $previousCotations;
    }

    /**
	 * Load object in memory from the database
	 *
	 * @param int $parent_id Id parent object
	 * @param bool $get_children_data Get children risks data
	 * @param bool $get_parents_data Get parents risks data
	 * @param bool $get_shared_data Get parents risks data
     * @param  array     $moreParams More params(Object/user/etc)
	 * @return array|int         <0 if KO, 0 if not found, >0 if OK
	 * @throws Exception
	 */
	public function fetchRisksOrderedByCotation($parent_id, $get_children_data = false, $get_parents_data = false, $get_shared_data = false, $moreParams = [])
	{
		$object         = new DigiriskElement($this->db);
		$risk           = new Risk($this->db);
        $riskAssessment = new RiskAssessment($this->db);

		$objects = $object->getActiveDigiriskElements();

        if ($get_shared_data) {
            $activeDigiriskElements               = $object->getActiveDigiriskElements('shared');
            $risk->ismultientitymanaged           = 0;
            $riskAssessment->ismultientitymanaged = 0;
        }
		$riskList           = $risk->fetchAll('', '', 0, 0, ['customsql' => 'status = ' . self::STATUS_VALIDATED . $moreParams['filterRisk']]);
        $riskAssessmentList = $riskAssessment->fetchAll('', '', 0, 0, ['customsql' => 'status = ' . RiskAssessment::STATUS_VALIDATED . $moreParams['filterRiskAssessment']]);

		if (is_array($riskAssessmentList) && !empty($riskAssessmentList)) {
			foreach ($riskAssessmentList as $riskAssessmentSingle) {
				$riskAssessmentsOrderedByRisk[$riskAssessmentSingle->fk_risk] = $riskAssessmentSingle;
			}
		}

		$risksOrderedByDigiriskElement = [];

		// Entity management is disabled above when shared risks are requested, so $riskList holds the risks of every entity of the database
		// Only the risks carried by a digirisk element visible from the current entity belong to this listing
		$visibleDigiriskElements = is_array($objects) ? $objects : [];
		if ($get_shared_data && is_array($activeDigiriskElements)) {
			$visibleDigiriskElements += $activeDigiriskElements;
		}

		if (is_array($riskList) && !empty($riskList)) {
			foreach ($riskList as $riskSingle) {
				$riskSingle->lastEvaluation = $riskAssessmentsOrderedByRisk[$riskSingle->id] ?? null;
				$riskSingle->appliedOn      = $riskSingle->fk_element;
				if (isset($visibleDigiriskElements[$riskSingle->fk_element])) {
					$risksOrderedByDigiriskElement[$riskSingle->fk_element][] = $riskSingle;
				}
			}
		}
		$risks = [];

		//For groupment & workunit documents with given id
		if ($parent_id > 0) {
			$risksOfDigiriskElement = $risksOrderedByDigiriskElement[$parent_id];
			// RISKS de l'élément parent.
			if (is_array($risksOfDigiriskElement) && !empty($risksOfDigiriskElement)) {
				foreach ($risksOfDigiriskElement as $riskOfDigiriskElement) {
					$riskOfDigiriskElement->appliedOn = $parent_id;
					$risks[] = $riskOfDigiriskElement;
				}
			}
		}

		//For risks listing of risk assessment document & risks listings
		if ( $get_children_data ) {
			if (is_array($objects) && !empty($objects)) {
				$elementsChildren = recurse_tree($parent_id, 0, $objects);
			} else {
				return -1;
			}


            if ( is_array($elementsChildren) && ! empty($elementsChildren) ) {
                // Super function iterations flat.
                $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($elementsChildren));
                $element = array();
                foreach ($it as $key => $v) {
                    $element[$key][$v] = $v;
                }

                $children_ids = $element['id'];

				// RISKS parent children.
				if ( !empty($children_ids)) {
					foreach ($children_ids as $child_id) {
						// The entity scope is already applied when $risksOrderedByDigiriskElement is built
						// Filtering again on $risk->entity dropped every children risk on a mono entity install, where the entity field is unset from $fields
						if (!empty($risksOrderedByDigiriskElement[$child_id]) && is_array($risksOrderedByDigiriskElement[$child_id])) {
							$risks = array_merge($risks, $risksOrderedByDigiriskElement[$child_id]);
						}
					}
				}
			}
		}

//		for groupment & workunit document & risk assessment document if get inherited risks conf is activated
		if ( $get_parents_data ) {
			if ($parent_id > 0) {
				$parent_element_id = $objects[$parent_id]->fk_parent;
				while ($parent_element_id > 0) {
					if (is_array($risksOrderedByDigiriskElement[$parent_element_id]) && !empty($risksOrderedByDigiriskElement[$parent_element_id])) {
						foreach($risksOrderedByDigiriskElement[$parent_element_id] as $riskOfParentDigiriskElement) {
							$riskOfParentDigiriskElement->appliedOn = $parent_id;
							$risks[] = $riskOfParentDigiriskElement;
						}
					}
					$parent_element_id = $objects[$parent_element_id]->fk_parent;
				}
			} else {
				//For inherited risks in risk assessment document
				if (is_array($objects) && !empty($objects)) {
					foreach ($objects as $digiriskElement) {
                        $parent_element_id = $digiriskElement->fk_parent;
                        while ($parent_element_id > 0) {
							if (is_array($risksOrderedByDigiriskElement[$parent_element_id]) && !empty($risksOrderedByDigiriskElement[$parent_element_id])) {
								foreach($risksOrderedByDigiriskElement[$parent_element_id] as $riskOfParentDigiriskElement) {
									$tempRiskOfParentDigiriskElement = new Risk($this->db);
									$tempRiskOfParentDigiriskElement->setVarsFromFetchObj($riskOfParentDigiriskElement);

									$tempRiskOfParentDigiriskElement->lastEvaluation = $riskOfParentDigiriskElement->lastEvaluation;
									$tempRiskOfParentDigiriskElement->appliedOn = $digiriskElement->id;
									$tempRiskOfParentDigiriskElement->id = $riskOfParentDigiriskElement->id;

									$appliedOnIds[$riskOfParentDigiriskElement->id][] = $digiriskElement->id;

									$risks[] = $tempRiskOfParentDigiriskElement;
								}
							}
							$parentDigiriskElement = $objects[$parent_element_id];
							$parent_element_id = $parentDigiriskElement->fk_parent;
						}
					}
				}
			}
		}

        //For all documents
		if ( $get_shared_data ) {
            $inserted = [];
			if ($parent_id == 0) {
				$digiriskElementsOfEntity = $objects;
				if (is_array($digiriskElementsOfEntity) && !empty($digiriskElementsOfEntity)) {
					foreach ($digiriskElementsOfEntity as $digiriskElementOfEntity) {
						$digiriskElementOfEntity->fetchObjectLinked(null, '', $digiriskElementOfEntity->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
						if (!empty($digiriskElementOfEntity->linkedObjectsIds['digiriskdolibarr_risk'])) {
							foreach ($digiriskElementOfEntity->linkedObjectsIds['digiriskdolibarr_risk'] as $sharedRiskId) {
                                $sharedRisk         = $riskList[$sharedRiskId];
                                $sharedParentActive = array_search($sharedRisk->fk_element, array_column($activeDigiriskElements, 'id'));
								if (is_object($sharedRisk) && $sharedParentActive !== false && !in_array($sharedRisk->id, $inserted)) {
                                    $clonedRisk              = clone $sharedRisk;
                                    $clonedRisk->appliedOn   = $digiriskElementOfEntity->id;
                                    $clonedRisk->origin_type = 'shared';
                                    $risks[]                 = $clonedRisk;
                                    $inserted[]              = $sharedRisk->id;
                                }
							}
						}
					}
				}
			} else {
				if (array_key_exists($parent_id, $objects)) {
					$parentElement = $objects[$parent_id];
					$parentElement->fetchObjectLinked(null, '', $parent_id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
					if (!empty($parentElement->linkedObjectsIds['digiriskdolibarr_risk'])) {
						foreach ($parentElement->linkedObjectsIds['digiriskdolibarr_risk'] as $sharedRiskId) {
							$sharedRisk         = $riskList[$sharedRiskId];
                            $sharedParentActive = array_search($sharedRisk->fk_element, array_column($activeDigiriskElements, 'id'));
                            if (is_object($sharedRisk)  && $sharedParentActive !== false && !in_array($sharedRisk->id, $inserted)) {
                                $clonedRisk              = clone $sharedRisk;
                                $clonedRisk->appliedOn   = $parent_id;
                                $clonedRisk->origin_type = 'shared';
                                $risks[]                 = $clonedRisk;
                                $inserted[]              = $sharedRisk->id;
							}
						}
					}
				}
			}
		}


		if ( ! empty($risks) && is_array($risks)) {
			usort($risks, function ($first, $second) {
				return $first->lastEvaluation->cotation < $second->lastEvaluation->cotation;
			});
			return $risks;
		} else {
			return -1;
		}
	}

    /**
     * Build risk levels (1 to 4) for an element, split in two buckets:
     *  - 'inherited': inherited (parent tree) + children (descendants) risks of the same entity;
     *  - 'shared':    shared (multi-entity linked) risks from other entities.
     * Own risks are excluded: they are already handled by loadRiskInfos() with the element filter.
     *
     * @param  array     $moreParam More param (object)
     * @return array                ['inherited' => ['riskByRiskAssessmentLevels' => array, 'digiriskElements' => array],
     *                                'shared'    => ['riskByRiskAssessmentLevels' => array, 'digiriskElements' => array]]
     * @throws Exception
     */
    public function getInheritedAndSharedRiskLevels(array $moreParam): array
    {
        $result = [
            'inherited' => ['riskByRiskAssessmentLevels' => [], 'digiriskElements' => []],
            'shared'    => ['riskByRiskAssessmentLevels' => [], 'digiriskElements' => []],
        ];

        $object = $moreParam['object'] ?? null;
        if (!is_object($object) || $object->id <= 0) {
            return $result;
        }

        // Own + children (descendants) + inherited (ancestors) + shared (multi-entity linked) risks for this element.
        $fetchParam = ['filterRisk' => '', 'filterRiskAssessment' => ''];
        $risks      = $this->fetchRisksOrderedByCotation($object->id, true, true, true, $fetchParam);
        if (!is_array($risks) || empty($risks)) {
            return $result;
        }

        $digiriskElement = new DigiriskElement($this->db);
        $riskAssessment  = new RiskAssessment($this->db);

        $currentElements = $digiriskElement->getActiveDigiriskElements();
        $sharedElements  = $digiriskElement->getActiveDigiriskElements('shared');
        $allElements     = (is_array($currentElements) ? $currentElements : []) + (is_array($sharedElements) ? $sharedElements : []);

        foreach ($risks as $riskSingle) {
            $isShared = (isset($riskSingle->origin_type) && $riskSingle->origin_type === 'shared');

            // Skip own risks: they are already rendered by the base "current" segment.
            if (!$isShared && $riskSingle->fk_element == $object->id) {
                continue;
            }

            $lastEvaluation = $riskSingle->lastEvaluation;
            if (!is_object($lastEvaluation)) {
                continue;
            }

            // Flatten the risk assessment fields expected by setRiskByRiskAssessmentLevelsSegment().
            $riskSingle->riskAssessmentRef          = $lastEvaluation->ref;
            $riskSingle->riskAssessmentCotation     = $lastEvaluation->cotation;
            $riskSingle->riskAssessmentDate         = $lastEvaluation->date_riskassessment;
            $riskSingle->riskAssessmentDateCreation = $lastEvaluation->date_creation;
            $riskSingle->riskAssessmentComment      = $lastEvaluation->comment;
            $riskSingle->riskAssessmentPhoto        = $lastEvaluation->photo;

            $riskAssessment->cotation = $lastEvaluation->cotation;
            $scale                    = $riskAssessment->getEvaluationScale();

            $bucket = $isShared ? 'shared' : 'inherited';

            $result[$bucket]['riskByRiskAssessmentLevels'][$scale][] = $riskSingle;

            $fkElement = $riskSingle->fk_element;
            if (!isset($result[$bucket]['digiriskElements'][$fkElement]) && isset($allElements[$fkElement])) {
                $result[$bucket]['digiriskElements'][$fkElement] = ['object' => $allElements[$fkElement], 'depth' => 0];
            }
        }

        return $result;
    }

    /**
     * Get risk categories from the JSON file
     *
     * @param string $riskType Type of risk ('risk', 'riskenvironmental', etc.)
     * @return array           Array of risk categories, or empty array on failure
     */
    public static function getDangerCategories(string $riskType = 'risk'): array
    {
        $filePath = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/dangerCategories.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $jsonContent    = file_get_contents($filePath);
        $riskCategories = json_decode($jsonContent, true);

        if (!isset($riskCategories[0]) || !is_array($riskCategories[0]) || !isset($riskCategories[0][$riskType])) {
            return [];
        }

        return $riskCategories[0][$riskType];
    }


    /**
     * Get risk sub categories from the JSON file
     *
     * @param string $riskType Subtype of risk
     * @return array           Array of risk sub categories, or empty array on failure
     */
    public static function getDangerSubCategories(): array
    {
        $filePath = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/dangerSubCategories.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $jsonContent    = file_get_contents($filePath);
        $riskSubCategories = json_decode($jsonContent, true);

        if (!isset($riskSubCategories[0]) || !is_array($riskSubCategories[0])) {
            return [];
        }
        return $riskSubCategories[0];
    }

    /**
     * Get the factors of the consolidated psychosocial assessments, by risk
     *
     * A psychosocial risk assessed with the consolidated methods holds one assessment for the
     * whole unit and keeps the level of each factor in llx_digiriskdolibarr_rpsanswer. Both
     * psychosocial tables of the risk assessment document are built on sub-categories, so they
     * need those factors rather than the single line of the risk.
     *
     * Only the latest assessment of each risk is read, and a factor assessed by several criteria
     * keeps the worst of them, as the grid itself does.
     *
     * @param  array $risks Risks the document is built on
     * @return array        Cotation of each factor, by risk id then sub-category position
     */
    public static function getConsolidatedPsychosocialFactors(array $risks): array
    {
        global $db;

        $riskIds = [];
        foreach ($risks as $risk) {
            if ($risk->category == self::PSYCHOSOCIAL_CATEGORY_POSITION) {
                $riskIds[] = (int) $risk->id;
            }
        }

        if (empty($riskIds)) {
            return [];
        }

        $sql  = 'SELECT ra.fk_risk, ra.rowid AS fk_riskassessment, a.sub_category, a.cotation';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'digiriskdolibarr_rpsanswer a';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment ra ON ra.rowid = a.fk_riskassessment';
        $sql .= ' WHERE ra.status > 0 AND ra.fk_risk IN (' . implode(',', $riskIds) . ')';
        $sql .= ' ORDER BY ra.fk_risk ASC, ra.rowid ASC';

        $resql = $db->query($sql);
        if (!$resql) {
            return [];
        }

        $factors    = [];
        $lastOfRisk = [];
        while ($obj = $db->fetch_object($resql)) {
            $riskId       = (int) $obj->fk_risk;
            $assessmentId = (int) $obj->fk_riskassessment;

            // The query is ordered by assessment: a more recent one replaces the previous
            if (($lastOfRisk[$riskId] ?? 0) < $assessmentId) {
                $lastOfRisk[$riskId] = $assessmentId;
                $factors[$riskId]    = [];
            }

            if ($lastOfRisk[$riskId] != $assessmentId) {
                continue;
            }

            // Positions 6 and 7 describe the prevention context and the impact, not an exposure
            $subCategory = (int) $obj->sub_category;
            if ($subCategory < 0 || $subCategory > 5) {
                continue;
            }

            $factors[$riskId][$subCategory] = max($factors[$riskId][$subCategory] ?? 0, (int) $obj->cotation);
        }

        return $factors;
    }

    /**
     * Get the INRS psychosocial risk assessment grid (RPS-DU tool, brochure ED 6403)
     *
     * Each criterion carries the family heading it is printed under in the official grid, the
     * question asked during the interview and the sub-category of the psychosocial danger
     * category it is filed under, so a criterion assessed here feeds the same tables of the
     * risk assessment document as a factor entered with the "Faire le point" method.
     *
     * "reversed" tells which way the four colours run: on a criterion describing a constraint
     * ("Les salariés sont-ils soumis à des contraintes de rythmes élevés ?") answering "Jamais"
     * is the green answer, while on a criterion describing a resource ("Les objectifs des
     * salariés sont-ils clairement définis ?") answering "Jamais" is the red one.
     *
     * @return array Array of criteria, or empty array on failure
     */
    public static function getPsychosocialRiskGrid(): array
    {
        $filePath = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/psychosocialRiskGrid.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $criteria = json_decode(file_get_contents($filePath), true);

        if (!is_array($criteria)) {
            return [];
        }

        return $criteria;
    }


    /**
	 * Get danger category picto path
	 *
	 * @param         $object
     * @param  string $riskType                   Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['thumbnail_name'] Path to danger category picto, -1 if don't exist
	 */
	public function getDangerCategory($object, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Formate le libellé enrichi d'une catégorie de danger, destiné aux infobulles
	 *
	 * Les catégories de pénibilité portent un bloc réglementaire qui n'a de sens qu'au
	 * survol : il ne doit jamais atterrir dans un PDF, un ODT, un export ou une description.
	 *
	 * @param  array  $category Danger category as defined in dangerCategories.json
	 * @return string           Category name, suffixed by its regulatory block when it has one
	 */
	public function formatDangerCategoryTooltip(array $category)
	{
		global $langs;

		$tooltip = $category['name'];
		if (!empty($category['reference'])) {
			$tooltip .= '&#10;' . $langs->transnoentities('DangerCategoryReference') . ' : ' . $category['reference'];
			$tooltip .= '&#10;' . $langs->transnoentities('DangerCategoryC2P') . ' : ' . $category['c2p'];
			$tooltip .= '&#10;' . $langs->transnoentities('DangerCategoryExposureCriteria') . ' : ' . $category['criteres'];
			$tooltip .= '&#10;' . $langs->transnoentities('DangerCategoryRegulatoryThreshold') . ' : ' . $category['seuil'];
		}

		return $tooltip;
	}

	/**
	 * Get danger category name
	 *
	 * @param         $object
	 * @param  string $riskType         Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['name'] Name of the danger category, -1 if don't exist
	 */
	public function getDangerCategoryName($object, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category tooltip
	 *
	 * Réservé à la construction d'un aria-label : le retour contient le bloc
	 * réglementaire des catégories de pénibilité, contrairement à getDangerCategoryName().
	 *
	 * @param         $object
	 * @param  string $riskType Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string           Tooltip of the danger category, -1 if don't exist
	 */
	public function getDangerCategoryTooltip($object, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $this->formatDangerCategoryTooltip($category);
			}
		}

		return -1;
	}

    /**
     * Get danger sub category name
     *
     * @param  int    $position
     *
     */
    public function getDangerSubCategoryName($mainCategory, $position) {
        $subCategories = static::getDangerSubCategories();
        if (!isset($subCategories[$mainCategory]) || !is_array($subCategories[$mainCategory])) {
            return -1;
        }
        foreach ($subCategories[$mainCategory] as $subCategory) {
            if ($subCategory['position'] == $position) {
                return $subCategory['label'];
            }
        }

        return -1;
    }

    /**
     * Get danger sub category scale label
     *
     * @param $scale
     * @return mixed
     */
    public function getDangerSubCategoryScaleLabel($scale) {
        global $langs;
        if ($scale < 48) {
            return $langs->trans('Weak');
        } elseif ($scale < 51) {
            return $langs->trans('Moderate');
        } elseif ($scale < 80) {
            return $langs->trans('High');
        } else {
            // The cotation scale has a fourth level, which getEvaluationScale() already knows:
            // an unacceptable factor used to be printed as a dash in the risk assessment document
            return $langs->trans('Extreme');
        }
    }

	/**
	 * Get danger category picto name
	 *
	 * @param  string $name
     * @param  string $riskType         Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['name'] Name to danger category picto, -1 if don't exist
	 */
	public function getDangerCategoryPositionByName($name, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['name'] == $name || $category['nameDigiriskWordPress'] == $name) {
				return $category['position'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category picto path
	 *
	 * @param  int    $position
     * @param  string $riskType                   Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['thumbnail_name'] Path to danger category picto, -1 if don't exist
	 */
	public function getDangerCategoryByPosition($position, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $position) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category name by position
	 *
	 * @param  int    $position
	 * @param  string $riskType         Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['name'] Name of the danger category, -1 if don't exist
	 */
	public function getDangerCategoryNameByPosition($position, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $position) {
				return $category['name'];
			}
		}

		return -1;
	}

	/**
	 * Get danger category tooltip by position
	 *
	 * Réservé à la construction d'un aria-label, comme getDangerCategoryTooltip().
	 *
	 * @param  int    $position
	 * @param  string $riskType Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string           Tooltip of the danger category, -1 if don't exist
	 */
	public function getDangerCategoryTooltipByPosition($position, string $riskType = 'risk')
	{
		$risk_categories = static::getDangerCategories($riskType);
		foreach ($risk_categories as $category) {
			if ($category['position'] == $position) {
				return $this->formatDangerCategoryTooltip($category);
			}
		}

		return -1;
	}

	/**
	 * Get fire permit risk categories json in /digiriskdolibarr/js/json/
	 *
	 * @return	array $risk_categories
	 */
	public function getFirePermitDangerCategories()
	{
		$json_categories = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/firePermitDangerCategories.json');
		return json_decode($json_categories, true);
	}

	/**
	 * Get fire permit danger category picto path
	 *
	 * @param $object
	 * @return    string $category['thumbnail_name']     path to fire permit danger category picto, -1 if don't exist
	 */
	public function getFirePermitDangerCategory($object)
	{
		$risk_categories = $this->getFirePermitDangerCategories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['thumbnail_name'];
			}
		}

		return -1;
	}

	/**
	 * Get fire permit danger category picto name
	 *
	 * @param $object
	 * @return    string $category['name']     name to fire permit danger category picto, -1 if don't exist
	 */
	public function getFirePermitDangerCategoryName($object)
	{
		$risk_categories = $this->getFirePermitDangerCategories();
		foreach ($risk_categories as $category) {
			if ($category['position'] == $object->category) {
				return $category['name'];
			}
		}

		return -1;
	}

    /**
     * check if risk not exists for a digirisk element
     *
     * @param  int       $limit Limit
     * @return array|int        Int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function checkNotExistsDigiriskElementForRisk(int $limit = 0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $digiriskElement = new DigiriskElement($this->db);

        $sql  = 'SELECT ';
        $sql .= $this->getFieldList('t');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE !EXISTS';
        $sql .= ' ( SELECT ';
        $sql .= $digiriskElement->getFieldList('d');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $digiriskElement->table_element . ' as d';
        $sql .= ' WHERE d.rowid = t.fk_element )';

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
     * check if risk assessment not exists for a risk
     *
     * @param  int       $limit Limit
     * @return array|int        Int <0 if KO, array of pages if OK
     * @throws Exception
     */
    public function checkNotExistsRiskAssessmentForRisk(int $limit = 0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $riskAssessment = new RiskAssessment($this->db);

        $sql  = 'SELECT ';
        $sql .= $this->getFieldList('t');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
        $sql .= ' WHERE !EXISTS';
        $sql .= ' (SELECT ';
        $sql .= $riskAssessment->getFieldList('ra');
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $riskAssessment->table_element . ' as ra';
        $sql .= ' WHERE ra.fk_risk = t.rowid)';

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
	 * Get children tasks
	 *
	 * @param $risk
	 * @return array|int $records or -1 if error
	 * @throws Exception
	 */
	public function getRelatedTasks($risk)
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . 'projet_task_extrafields' . ' WHERE fk_risk =' . $risk->id;

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			$records = array();
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$record = new SaturneTask($this->db);
				$record->fetch($obj->fk_object);
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
	 * Get children tasks
	 *
	 * @param $risk
	 * @return array|int $records or -1 if error
	 * @throws Exception
	 */
	public function getTasksWithFkRisk()
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . 'projet_task_extrafields' . ' WHERE fk_risk > 0 ORDER BY fk_object ASC';
		$tasksList = saturne_fetch_all_object_type('SaturneTask', '', '', 0, 0, [], 'AND', false, false);

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i   = 0;
			$records = array();
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$records[$obj->fk_risk][$obj->rowid] = $tasksList[$obj->fk_object] ?? null;
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
     * Load dashboard info risk
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        global $langs;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode(getDolUserString($confName));
        $array = ['graphs' => [], 'lists' => [], 'disabledGraphs' => []];

        $riskType = !empty($dashboardConfig->filters->riskType) ? $dashboardConfig->filters->riskType : 'risk';

        // Kept for the graphs: the lists they open show the type of risk the dashboard is filtered on
        $this->dashboardRiskType = $riskType;

        $dangerCategories                         = static::getDangerCategories($riskType);
        $riskByDangerCategoriesAndRiskAssessments = $this->getRiskByDangerCategoriesAndRiskAssessments($dangerCategories, $riskType);

        $lastRisksWidgetData = $this->getLastRisksForDashboard($riskType, 3);
        $array['widgets']    = [
            'lastrisks' => [
                'title'      => $langs->transnoentities('LastRisksAdded'),
                'picto'      => 'fas fa-exclamation-triangle',
                'pictoColor' => '#D35968',
                'label'      => $lastRisksWidgetData['labels'],
                'content'    => $lastRisksWidgetData['contents'],
                'widgetName' => $langs->transnoentities('LastRisksAdded'),
            ],
        ];

        $array['graphsFilters'] = [
            'riskType' => [
                'title'        => $langs->transnoentities('ShowSelectedRiskTypes'),
                'type'         => 'selectarray',
                'filter'       => 'riskType',
                'values'       => ['risk' => $langs->transnoentities('Risk'), 'riskenvironmental' => $langs->transnoentities('Riskenvironmental')],
                'currentValue' => $riskType
        ]];

        if (empty($dashboardConfig->graphs->RisksRepartitionByDangerCategoriesAndCriticality->hide)) {
            $array['graphs'][] = $this->getRisksByDangerCategoriesAndCriticality($dangerCategories, $riskByDangerCategoriesAndRiskAssessments);
        } else {
            $array['disabledGraphs']['RisksRepartitionByDangerCategoriesAndCriticality'] = $langs->transnoentities('RisksRepartitionByDangerCategoriesAndCriticality');
        }
        if (empty($dashboardConfig->graphs->RisksRepartitionByDangerCategories->hide)) {
            $array['graphs'][] = $this->getRisksByDangerCategories($dangerCategories, $riskByDangerCategoriesAndRiskAssessments);
        } else {
            $array['disabledGraphs']['RisksRepartitionByDangerCategories'] = $langs->transnoentities('RisksRepartitionByDangerCategories');
        }
        if (empty($dashboardConfig->graphs->RisksRepartitionByCotation->hide)) {
            $array['graphs'][] = $this->getRisksByCotation($riskByDangerCategoriesAndRiskAssessments);
        } else {
            $array['disabledGraphs']['RisksRepartitionByCotation'] = $langs->transnoentities('RisksRepartitionByCotation');
        }
        if (empty($dashboardConfig->graphs->RiskListsByDangerCategories->hide)) {
            $array['lists'][] = $this->getRiskListsByDangerCategories($dangerCategories, $riskByDangerCategoriesAndRiskAssessments, '', $riskType);
        } else {
            $array['disabledGraphs']['RiskListsByDangerCategories'] = $langs->transnoentities('RiskListsByDangerCategories');
        }

        return $array;
    }

    /**
     * Load the risk dashboard of the information tab of a GP/UT
     *
     * A groupment counts the risks of everything it contains, a work unit only its own. The graphs of a
     * work unit open the risk list filtered on it; that list filters on one element only and would not
     * match the counts of a groupment, whose graphs do not link.
     *
     * @param  DigiriskElement $digiriskElement GP/UT of the tab
     * @param  array           $descendants     Elements it contains, as DigiriskElement::fetchDigiriskElementFlat() returns them
     * @return array                            Widgets, graphs and filters, in the format of load_dashboard()
     * @throws Exception
     */
    public function loadDigiriskElementDashboard(DigiriskElement $digiriskElement, array $descendants = []): array
    {
        global $langs;

        $confName        = dol_strtoupper($this->module) . '_DASHBOARD_CONFIG';
        $dashboardConfig = json_decode(getDolUserString($confName));
        $array           = ['widgets' => [], 'graphs' => [], 'disabledGraphs' => []];

        $riskType                   = !empty($dashboardConfig->filters->riskType) ? $dashboardConfig->filters->riskType : 'risk';
        $this->dashboardRiskType    = $riskType;
        $this->dashboardListFilters = ($digiriskElement->element_type == 'groupment' ? null : ['search_fk_element=' . ((int) $digiriskElement->id)]);

        $fkElements                               = array_merge([(int) $digiriskElement->id], array_map('intval', array_keys($descendants)));
        $dangerCategories                         = static::getDangerCategories($riskType);
        $riskByDangerCategoriesAndRiskAssessments = $this->getRiskByDangerCategoriesAndRiskAssessments($dangerCategories, $riskType, ['fkElements' => $fkElements]);
        $assessmentDates                          = $this->getRiskAssessmentDatesForDashboard($fkElements, $riskType);

        $elementRisksUrl = dol_buildpath('/' . $this->module . '/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . ((int) $digiriskElement->id) . '&risk_type=' . urlencode($riskType);
        $labels          = [$langs->transnoentities('NumberOfRisks')];
        $contents        = ['<a href="' . $elementRisksUrl . '">' . ((int) ($riskByDangerCategoriesAndRiskAssessments['totalRisks'] ?? 0)) . '</a>'];
        foreach ($this->cotations as $cotationLevel => $cotation) {
            $labels[]   = '<span class="fas fa-square paddingright" style="color: ' . $cotation['color'] . ';"></span>' . $cotation['label'] . ' (' . $cotation['start'] . '-' . $cotation['end'] . ')';
            $contents[] = (int) ($riskByDangerCategoriesAndRiskAssessments['nbRiskByCotations'][$cotationLevel] ?? 0);
        }
        $labels[]   = $langs->transnoentities('LastRiskAssessmentDate');
        $contents[] = $assessmentDates['lastAssessment'] > 0 ? dol_print_date($assessmentDates['lastAssessment'], 'day') : '-';
        $labels[]   = $langs->transnoentities('RisksNotReassessedForOneYear');
        $contents[] = $assessmentDates['notReassessed'];

        $lastRisksWidgetData = $this->getLastRisksForDashboard($riskType, 3, $fkElements);
        // Keys of their own: the widgets closed on the home dashboard stay shown here, and the other way round
        $array['widgets'] = [
            'digiriskelementriskkeyfigures' => [
                'title'      => $langs->transnoentities('DigiriskElementRiskKeyFigures'),
                'picto'      => 'fas fa-exclamation-triangle',
                'pictoColor' => '#D35968',
                'label'      => $labels,
                'content'    => $contents,
                'widgetName' => $langs->transnoentities('DigiriskElementRiskKeyFigures'),
            ],
            'digiriskelementlastrisks' => [
                'title'      => $langs->transnoentities('LastRisksAdded'),
                'picto'      => 'fas fa-exclamation-triangle',
                'pictoColor' => '#D35968',
                'label'      => $lastRisksWidgetData['labels'],
                'content'    => $lastRisksWidgetData['contents'],
                'widgetName' => $langs->transnoentities('LastRisksAdded'),
            ],
        ];

        $array['graphsFilters'] = [
            'riskType' => [
                'title'        => $langs->transnoentities('ShowSelectedRiskTypes'),
                'type'         => 'selectarray',
                'filter'       => 'riskType',
                'values'       => ['risk' => $langs->transnoentities('Risk'), 'riskenvironmental' => $langs->transnoentities('Riskenvironmental')],
                'currentValue' => $riskType
        ]];

        if (empty($dashboardConfig->graphs->RisksRepartitionByCotation->hide)) {
            $array['graphs'][] = $this->getRisksByCotation($riskByDangerCategoriesAndRiskAssessments);
        } else {
            $array['disabledGraphs']['RisksRepartitionByCotation'] = $langs->transnoentities('RisksRepartitionByCotation');
        }
        if (empty($dashboardConfig->graphs->RisksRepartitionByDangerCategories->hide)) {
            $array['graphs'][] = $this->getRisksByDangerCategories($dangerCategories, $riskByDangerCategoriesAndRiskAssessments);
        } else {
            $array['disabledGraphs']['RisksRepartitionByDangerCategories'] = $langs->transnoentities('RisksRepartitionByDangerCategories');
        }

        return $array;
    }

    /**
     * Get the date of the last assessment of a set of risks and how many were not reassessed for a year
     *
     * The date read is the one the risk lists show: the assessment date when DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE
     * is on, the creation date of the assessment otherwise.
     *
     * @param  int[]  $fkElements GP/UT the risks belong to
     * @param  string $riskType   Risk type ('risk' or 'riskenvironmental')
     * @return array{lastAssessment: int, notReassessed: int} Timestamp of the last assessment (0 without any) and number of risks last assessed more than a year ago
     */
    public function getRiskAssessmentDatesForDashboard(array $fkElements, string $riskType = 'risk'): array
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

        $result = ['lastAssessment' => 0, 'notReassessed' => 0];
        if (empty($fkElements)) {
            return $result;
        }

        $dateField = getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') ? 'COALESCE(lastra.date_riskassessment, lastra.date_creation)' : 'lastra.date_creation';
        $oneYearAgo = $this->db->idate(dol_time_plus_duree(dol_now(), -1, 'y'));

        $sql  = 'SELECT MAX(' . $dateField . ') as last_assessment,';
        $sql .= ' SUM(CASE WHEN ' . $dateField . " < '" . $oneYearAgo . "' THEN 1 ELSE 0 END) as not_reassessed";
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as r';
        $sql .= ' INNER JOIN ' . $this->getLastRiskAssessmentSqlTable() . ' as lastid ON lastid.fk_risk = r.rowid';
        $sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element . ' as lastra ON lastra.rowid = lastid.rowid';
        $sql .= ' WHERE r.entity = ' . ((int) $conf->entity);
        $sql .= ' AND r.status = ' . self::STATUS_VALIDATED;
        $sql .= " AND r.type = '" . $this->db->escape($riskType) . "'";
        $sql .= ' AND r.fk_element IN (' . implode(',', array_map('intval', $fkElements)) . ')';

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog(__METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
            return $result;
        }

        $obj = $this->db->fetch_object($resql);
        if ($obj) {
            $result['lastAssessment'] = !empty($obj->last_assessment) ? (int) $this->db->jdate($obj->last_assessment) : 0;
            $result['notReassessed']  = (int) $obj->not_reassessed;
        }

        return $result;
    }

    /**
     * Get the last added validated risks (outside trash) for the dashboard widget.
     *
     * @param  string $riskType   Risk type ('risk' or 'riskenvironmental')
     * @param  int    $limit      Maximum number of risks to return
     * @param  int[]  $fkElements GP/UT to restrict the risks to, every active one when empty
     * @return array{labels: array<int, string>, contents: array<int, string>}
     * @throws Exception
     */
    public function getLastRisksForDashboard(string $riskType = 'risk', int $limit = 3, array $fkElements = []): array
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

        $digiriskElement = new DigiriskElement($this->db);
        $activeElements  = $digiriskElement->getActiveDigiriskElements('current');
        if (is_array($activeElements) && !empty($fkElements)) {
            $activeElements = array_intersect_key($activeElements, array_flip(array_map('intval', $fkElements)));
        }
        if (!is_array($activeElements) || empty($activeElements)) {
            return [
                'labels'   => [$langs->transnoentities('NoData')],
                'contents' => ['N/A'],
            ];
        }

        $riskAssessmentTable = MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element;
        $sql                 = 'SELECT r.rowid, r.ref, r.date_creation, r.category, r.description, r.fk_element, r.type,';
        $sql                .= ' lastra.ref as ra_ref, lastra.date_creation as ra_date_creation, lastra.date_riskassessment as date_riskassessment,';
        $sql                .= ' lastra.cotation as cotation, lastra.method as ra_method, lastra.photo as ra_photo, lastra.comment as ra_comment';
        $sql                .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as r';
        $sql                .= ' LEFT JOIN ' . $this->getLastRiskAssessmentSqlTable() . ' as lastid ON lastid.fk_risk = r.rowid';
        $sql                .= ' LEFT JOIN ' . $riskAssessmentTable . ' as lastra ON lastra.rowid = lastid.rowid';
        $sql                .= ' WHERE r.entity = ' . ((int) $conf->entity);
        $sql                .= ' AND r.status = ' . self::STATUS_VALIDATED;
        $sql                .= " AND r.type = '" . $this->db->escape($riskType) . "'";
        $sql                .= ' AND r.fk_element IN (' . implode(',', array_map('intval', array_keys($activeElements))) . ')';
        $sql                .= ' ORDER BY r.date_creation DESC, r.rowid DESC';
        $sql                .= $this->db->plimit($limit);

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog(__METHOD__ . ' ' . $this->db->lasterror(), LOG_ERR);
            return [
                'labels'   => [$langs->transnoentities('NoData')],
                'contents' => ['N/A'],
            ];
        }

        $defaultCotation = [0 => '0-47', 48 => '48-50', 51 => '51-80', 100 => '81-100'];
        $baseOutputDir   = $conf->digiriskdolibarr->multidir_output[$conf->entity] ?? (DOL_DATA_ROOT . '/digiriskdolibarr');

        $labels   = [];
        $contents = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $elementObj = $activeElements[(int) $obj->fk_element] ?? null;

            // 1. Danger category pictogram
            $categoryThumb = (string) $this->getDangerCategoryByPosition((int) $obj->category, $riskType);
            $categoryName  = (string) $this->getDangerCategoryNameByPosition((int) $obj->category, $riskType);
            if ($categoryName === '-1') {
                $categoryName = '';
            }
            $categoryPictoUrl = ($categoryThumb !== '-1' && $categoryThumb !== '')
                ? DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . rawurlencode($categoryThumb) . '.png'
                : DOL_URL_ROOT . '/public/theme/common/nophoto.png';

            // 2. Risk assessment photo (or default nophoto placeholder when the assessment has no photo)
            $photoUrl = $this->resolveDashboardRiskPhotoUrl(
                (string) $baseOutputDir,
                (string) ($obj->ra_ref ?? ''),
                (string) ($obj->ra_photo ?? '')
            );

            // 3. Text lines:
            // - Line 1: RKxx (UTx - Label) - dd/mm/yyyy
            // - Line 2: Custom risk description (only if different from the danger category name)
            // - Line 3: RAxx : <assessment comment> (if present)
            $riskUrl     = dol_buildpath('/' . $this->module . '/view/risk/risk_card.php', 1) . '?id=' . ((int) $obj->rowid);
            $elementHtml = '';
            if ($elementObj instanceof DigiriskElement) {
                $elementUrl   = dol_buildpath('/' . $this->module . '/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . ((int) $elementObj->id) . '&risk_type=' . urlencode($riskType);
                $elementTitle = trim($elementObj->ref . ' - ' . $elementObj->label);
                $elementHtml  = ' (<a href="' . $elementUrl . '" style="color:inherit; text-decoration:none;" title="' . dol_escape_htmltag($elementTitle) . '">' . dol_escape_htmltag(dol_trunc($elementTitle, 32)) . '</a>)';
            }

            $dateRaw = (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($obj->date_riskassessment))
                ? $obj->date_riskassessment
                : (!empty($obj->ra_date_creation) ? $obj->ra_date_creation : $obj->date_creation);
            $dateTimestamp = $this->db->jdate($dateRaw);
            $dateHtml      = $dateTimestamp > 0 ? ' - ' . dol_print_date($dateTimestamp, 'day') : '';

            $cleanDesc    = trim(html_entity_decode(dol_string_nohtmltag((string) $obj->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $cleanComment = trim(html_entity_decode(dol_string_nohtmltag((string) ($obj->ra_comment ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $cleanCatName = trim(html_entity_decode($categoryName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $rowHtml  = '<span style="display:flex; align-items:center; gap:12px;">';
            $rowHtml .= '<img src="' . $categoryPictoUrl . '" alt="' . dol_escape_htmltag($cleanCatName) . '" title="' . dol_escape_htmltag($cleanCatName) . '" style="width:52px; height:52px; min-width:52px; border-radius:10px; object-fit:cover; flex-shrink:0;">';
            $rowHtml .= '<img src="' . $photoUrl . '" alt="" title="' . dol_escape_htmltag(!empty($obj->ra_photo) ? (string) $obj->ra_photo : $langs->transnoentities('NoPhotoYet')) . '" style="width:52px; height:52px; min-width:52px; border-radius:10px; object-fit:cover; background:#f1f5f9; flex-shrink:0;">';
            $rowHtml .= '<span style="display:flex; flex-direction:column; gap:2px; min-width:0; line-height:1.35;">';
            $rowHtml .= '<span style="font-weight:700; font-size:14px; color:#0f172a;"><a href="' . $riskUrl . '" style="color:inherit; text-decoration:none;">' . dol_escape_htmltag((string) $obj->ref) . '</a>' . $elementHtml . dol_escape_htmltag($dateHtml) . '</span>';
            if ($cleanDesc !== '' && $cleanDesc !== $cleanCatName) {
                $rowHtml .= '<span style="font-size:13px; color:#475569;" title="' . dol_escape_htmltag($cleanDesc) . '">' . dol_escape_htmltag(dol_trunc($cleanDesc, 80)) . '</span>';
            }
            if ($cleanComment !== '') {
                $raPrefix = !empty($obj->ra_ref) ? dol_escape_htmltag((string) $obj->ra_ref) . ' : ' : '';
                $rowHtml .= '<span style="font-size:13px; color:#64748b;" title="' . dol_escape_htmltag($cleanComment) . '">' . $raPrefix . dol_escape_htmltag(dol_trunc($cleanComment, 80)) . '</span>';
            }
            $rowHtml .= '</span>';
            $rowHtml .= '</span>';

            $labels[] = $rowHtml;

            if ($obj->cotation !== null) {
                $cotationVal     = (int) $obj->cotation;
                $level           = $this->getCotationLevel((float) $cotationVal);
                $bgColor         = $this->cotations[$level]['color'] ?? '#ececec';
                $textColor       = $level === 1 ? '#2b2b2b' : '#ffffff';
                $cotationDisplay = (($obj->ra_method ?? '') === 'standard' && isset($defaultCotation[$cotationVal]))
                    ? $defaultCotation[$cotationVal]
                    : (string) $cotationVal;
                $contents[]      = '<span class="risk-evaluation-cotation" data-scale="' . $level . '" style="display:inline-flex; align-items:center; justify-content:center; min-width:56px; height:46px; margin:-2px -10px; padding:0 10px; border-radius:10px; background:' . $bgColor . '; color:' . $textColor . '; font-size:14px; font-weight:700; box-shadow:inset 0 -2px 0 rgba(0,0,0,0.12);">' . dol_escape_htmltag($cotationDisplay) . '</span>';
            } else {
                $contents[] = 'N/A';
            }
        }
        $this->db->free($resql);

        if (empty($labels)) {
            $labels[]   = $langs->transnoentities('NoData');
            $contents[] = 'N/A';
        }

        return [
            'labels'   => $labels,
            'contents' => $contents,
        ];
    }

    /**
     * Resolve the thumbnail URL of a risk assessment photo, or return the default nophoto placeholder.
     *
     * The URL is served without login (saturne/utils/viewimage.php): the public overview of the risks
     * shows it as well.
     *
     * @param  string $baseOutputDir   Module multidir_output for current entity
     * @param  string $raRef           RiskAssessment reference
     * @param  string $raPhoto         RiskAssessment favorite photo filename
     * @param  bool   $withPlaceholder False to get an empty string for an assessment without photo
     * @return string                  URL to serve the image
     */
    public function resolveDashboardRiskPhotoUrl(string $baseOutputDir, string $raRef, string $raPhoto, bool $withPlaceholder = true): string
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

        if ($raRef !== '') {
            $raSubDir = 'riskassessment/' . $raRef;
            $fileName = $raPhoto;
            if ($fileName === '') {
                $files = dol_dir_list($baseOutputDir . '/' . $raSubDir, 'files', 0, '\.(jpg|jpeg|png|gif|webp)$', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 1);
                if (!empty($files[0]['name'])) {
                    $fileName = (string) $files[0]['name'];
                }
            }

            if ($fileName !== '') {
                $fullPath = $baseOutputDir . '/' . $raSubDir . '/' . $fileName;
                if (dol_is_file($fullPath)) {
                    $thumbName = basename(getImageFileNameForSize($fullPath, '_small'));
                    $thumbPath = $baseOutputDir . '/' . $raSubDir . '/thumbs/' . $thumbName;
                    $relFile   = dol_is_file($thumbPath)
                        ? $raSubDir . '/thumbs/' . $thumbName
                        : $raSubDir . '/' . $fileName;

                    return DOL_URL_ROOT . '/custom/saturne/utils/viewimage.php?modulepart=digiriskdolibarr&entity=' . ((int) $conf->entity) . '&file=' . urlencode($relFile);
                }
            }
        }

        return $withPlaceholder ? DOL_URL_ROOT . '/public/theme/common/nophoto.png' : '';
    }

    /**
     * Get the levels of the cotation scale
     *
     * @return array Label, color and bounds of each level, indexed by level
     */
    public function getCotations(): array
    {
        return $this->cotations;
    }

    /**
     * Get the risk list URL a graph part links to
     *
     * The graphs count the validated risks only, so the list they open leaves the other ones out.
     *
     * @param  array  $filters Criteria of the risk list, each one already url encoded
     * @return string          Risk list URL
     */
    protected function getRiskListUrl(array $filters = []): string
    {
        if ($this->dashboardListFilters === null) {
            return '';
        }

        $filters   = array_merge($filters, $this->dashboardListFilters);
        $filters[] = 'risk_type=' . $this->dashboardRiskType;
        $filters[] = 'search_status=' . self::STATUS_VALIDATED;

        return dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php', 1) . '?' . implode('&', array_filter($filters));
    }

    /**
     * Get the SQL criteria restricting a risk list to the risks assessed at a given level of the cotation scale
     *
     * A risk is assessed as many times as it is reviewed, and only its last validated assessment tells where it
     * stands today: that is the one the dashboard graph counts, so the criteria reads the same one.
     *
     * @param  int    $cotationLevel Level of the cotation scale, from 1 (grey) to 4 (black),
     *                               or COTATION_NOT_ASSESSED for the risks still to assess
     * @return string                SQL criteria, empty when the level is not one of the scale
     */
    public function getCotationSqlFilter(int $cotationLevel): string
    {
        if ($cotationLevel != self::COTATION_NOT_ASSESSED && empty($this->cotations[$cotationLevel])) {
            return '';
        }

        $riskAssessmentTable = MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element;

        if ($cotationLevel == self::COTATION_NOT_ASSESSED) {
            // A risk still to assess is one the levels of the scale leave out: its last validated assessment
            // is missing, or carries no cotation at all
            $operator       = 'NOT IN';
            $cotationFilter = ' WHERE lastra.cotation IS NOT NULL';
        } else {
            $operator = 'IN';

            // The highest level has no upper bound, a cotation computed with the advanced method can exceed its end
            $cotationFilter = ' WHERE lastra.cotation >= ' . (int) $this->cotations[$cotationLevel]['start'];
            if (isset($this->cotations[$cotationLevel + 1])) {
                $cotationFilter .= ' AND lastra.cotation <= ' . (int) $this->cotations[$cotationLevel]['end'];
            }
        }

        // The last validated assessment of every risk is picked in one pass: correlating that subquery to each
        // risk costs a full scan of the assessments per risk, seconds long on a base of a few thousand risks
        return ' AND r.rowid ' . $operator . ' (SELECT lastid.fk_risk'
             . ' FROM ' . $this->getLastRiskAssessmentSqlTable() . ' as lastid'
             . ' INNER JOIN ' . $riskAssessmentTable . ' as lastra ON lastra.rowid = lastid.rowid'
             . $cotationFilter . ')';
    }

    /**
     * Get the SQL criteria of the "search in all" of a risk list
     *
     * The refs of the assessments (RA...) are looked up as well: an assessment has no list of its own, so a
     * search made on its ref has to bring back the risk it belongs to instead of nothing.
     *
     * @param  array  $fieldsToSearchAll Fields to search into, keyed by their name prefixed with their table alias,
     *                                   "ra." standing for the assessments of the risk
     * @param  string $searchAll         Searched value
     * @return string                    SQL criteria, empty when there is nothing to search into
     */
    public function getSearchAllSqlFilter(array $fieldsToSearchAll, string $searchAll): string
    {
        $riskFields       = [];
        $assessmentFields = [];
        foreach (array_keys($fieldsToSearchAll) as $field) {
            if (strpos($field, 'ra.') === 0) {
                $assessmentFields[] = $field;
            } else {
                $riskFields[] = $field;
            }
        }

        $criterias = [];
        if (!empty($riskFields)) {
            $criterias[] = natural_search($riskFields, $searchAll, 0, 1);
        }
        if (!empty($assessmentFields)) {
            $riskAssessmentTable = MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element;

            $criterias[] = 'r.rowid IN (SELECT ra.fk_risk FROM ' . $riskAssessmentTable . ' as ra'
                         . ' WHERE ' . natural_search($assessmentFields, $searchAll, 0, 1) . ')';
        }

        return empty($criterias) ? '' : ' AND (' . implode(' OR ', $criterias) . ')';
    }

    /**
     * Get the SQL derived table giving the last validated assessment of every risk
     *
     * Its columns are fk_risk and rowid, the id of that assessment. The assessments are read in one pass:
     * correlating a subquery to each risk costs a full scan of the assessments per risk, seconds long on a
     * base of a few thousand risks.
     *
     * @return string SQL derived table, between parentheses and without alias
     */
    public function getLastRiskAssessmentSqlTable(): string
    {
        return '(SELECT ra.fk_risk as fk_risk, MAX(ra.rowid) as rowid'
             . ' FROM ' . MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element . ' as ra'
             . ' WHERE ra.status = ' . RiskAssessment::STATUS_VALIDATED . ' GROUP BY ra.fk_risk)';
    }

    /**
     * Get the SQL parts sorting a risk list, whose risks are read from the table aliased "r"
     *
     * A sort on a field of the assessments ("evaluation." prefixed) reads the last validated assessment of each
     * risk, joined to the risk under the alias "evaluation": a list read from the assessments instead had no row
     * for a risk still to assess, so the sort alone took it out. Such a risk has no value to sort on and comes
     * last in a descending sort. The sort on the tasks reads the last task of the risk, one row per risk.
     *
     * @param  string $sortField Sort field of the list
     * @param  string $sortOrder Sort order of the list
     * @return array{select: string, join: string, order: string} Column to add to the select list (with its
     *                                                             trailing comma), joins and ORDER BY clause;
     *                                                             select and join are empty for a field of the risk
     */
    public function getListSortSql(string $sortField, string $sortOrder): array
    {
        if (!preg_match('/^evaluation\./', $sortField)) {
            return ['select' => '', 'join' => '', 'order' => $this->db->order($sortField, $sortOrder)];
        }

        $join = ' LEFT JOIN ' . $this->getLastRiskAssessmentSqlTable() . ' as lastevaluation ON lastevaluation.fk_risk = r.rowid'
              . ' LEFT JOIN ' . MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element . ' as evaluation ON evaluation.rowid = lastevaluation.rowid';
        if ($sortField == 'evaluation.has_tasks') {
            $sortColumn = 'taskextrafields.fk_object';
            $join      .= ' LEFT JOIN (SELECT pte.fk_risk as fk_risk, MAX(pte.fk_object) as fk_object FROM ' . MAIN_DB_PREFIX . 'projet_task_extrafields as pte'
                        . ' WHERE pte.fk_risk > 0 GROUP BY pte.fk_risk) as taskextrafields ON taskextrafields.fk_risk = r.rowid';
        } else {
            $sortColumn = preg_replace('/[^0-9a-z_\.]/i', '', $sortField);
        }

        return [
            // A DISTINCT query can only be sorted on a column it selects
            'select' => $sortColumn . ' as sortvalue, ',
            'join'   => $join,
            // Many risks share a cotation: the id breaks the ties, or a risk could show on two pages and another on none
            'order'  => $this->db->order($sortColumn . ',r.rowid', $sortOrder . ',' . $sortOrder),
        ];
    }

    /**
     * Get the level of the cotation scale a cotation falls in
     *
     * @param  float $cotation Cotation of a risk assessment
     * @return int             Level of the scale, from 1 (grey) to 4 (black)
     */
    public function getCotationLevel(float $cotation): int
    {
        $level = (int) array_key_first($this->cotations);
        foreach ($this->cotations as $cotationLevel => $cotationScale) {
            if ($cotation >= $cotationScale['start']) {
                $level = $cotationLevel;
            }
        }

        return $level;
    }

    /**
     * Get the number of risks by level of the cotation scale, for the dashboard of the risk list
     *
     * Counts the risks the list shows when no filter is set: the ones of the current entity attached to an
     * active element. A risk is placed on the level of its last validated assessment, the one
     * getCotationSqlFilter() reads, so each count matches the number of rows the list shows once filtered
     * on that level.
     *
     * @param  string $riskType Type of risk ('risk' or 'riskenvironmental')
     * @return array            Number of risks, by level of the scale plus 'total' and COTATION_NOT_ASSESSED
     */
    public function getRiskCountsByCotationLevel(string $riskType = 'risk'): array
    {
        global $conf;

        $counts = ['total' => 0, self::COTATION_NOT_ASSESSED => 0];
        foreach (array_keys($this->cotations) as $cotationLevel) {
            $counts[$cotationLevel] = 0;
        }

        // The same elements as the list: the status of the element alone kept counting the work units of a
        // groupment put in the bin, whose descendants stay validated
        $digiriskElement = new DigiriskElement($this->db);
        $activeElements  = $digiriskElement->getActiveDigiriskElements();
        if (!is_array($activeElements) || empty($activeElements)) {
            return $counts;
        }

        $riskAssessmentTable = MAIN_DB_PREFIX . (new RiskAssessment($this->db))->table_element;
        $elementTable        = MAIN_DB_PREFIX . $digiriskElement->table_element;

        $sql  = 'SELECT lastra.cotation as cotation, COUNT(r.rowid) as nb';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as r';
        $sql .= ' INNER JOIN ' . $elementTable . ' as e ON e.rowid = r.fk_element';
        $sql .= ' AND e.rowid IN (' . implode(',', array_map('intval', array_keys($activeElements))) . ') AND e.entity = ' . (int) $conf->entity;
        // The last assessment of every risk is picked in one pass: correlating that subquery to each risk
        // costs a full scan of the assessments per risk, seconds long on a document unique of a few thousand
        $sql .= ' LEFT JOIN ' . $this->getLastRiskAssessmentSqlTable() . ' as lastid ON lastid.fk_risk = r.rowid';
        $sql .= ' LEFT JOIN ' . $riskAssessmentTable . ' as lastra ON lastra.rowid = lastid.rowid';
        $sql .= ' WHERE r.entity IN (' . getEntity($this->element) . ')';
        $sql .= " AND r.type = '" . $this->db->escape($riskType) . "'";
        // Deleted risks (soft delete) are never listed, archived ones only when the status filter asks for them
        $sql .= ' AND r.status NOT IN (' . self::STATUS_DELETED . ', ' . self::STATUS_ARCHIVED . ')';
        $sql .= ' GROUP BY lastra.cotation';

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

            return $counts;
        }

        while ($obj = $this->db->fetch_object($resql)) {
            $cotationLevel = isset($obj->cotation) ? $this->getCotationLevel((float) $obj->cotation) : self::COTATION_NOT_ASSESSED;

            $counts[$cotationLevel] += (int) $obj->nb;
            $counts['total']        += (int) $obj->nb;
        }
        $this->db->free($resql);

        return $counts;
    }

    /**
     * Get risks by cotation
     *
     * @param array $riskByDangerCategoriesAndRiskAssessments Risk by danger categories and risk assessments
     * @return array
     * @throws Exception
     */
    public function getRisksByCotation(array $riskByDangerCategoriesAndRiskAssessments): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('RisksRepartition');
        $array['name']  = 'RisksRepartition';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        // No layout without a User-Agent, which an API client may not send
        $array['showlegend'] = ($conf->browser->layout ?? '') == 'phone' ? 1 : 2;
        $array['dataset']    = 1;
        $array['labels']     = $this->cotations;

        // In the order of the labels: the colours follow it, the levels came in the order the risks were met
        // and the legend showed the orange and the red the wrong way round
        $array['data'] = [];
        foreach (array_keys($this->cotations) as $cotationLevel) {
            $array['data'][$cotationLevel] = $riskByDangerCategoriesAndRiskAssessments['nbRiskByCotations'][$cotationLevel] ?? 0;
        }

        $links = [];
        foreach (array_keys($array['data']) as $cotationLevel) {
            $links[] = $this->getRiskListUrl(['search_cotation=' . $cotationLevel]);
        }

        $array['morehtmlright'] = SaturneDashboard::getGraphOptionsInput(['links' => array_filter($links)]);

        return $array;
    }

    /**
     * Get risks by danger categories and criticality
     *
     * @param  array $dangerCategories                         Danger categories datas
     * @param  array $riskByDangerCategoriesAndRiskAssessments Risk by danger categories and risk assessments
     * @return array
     */
    public function getRisksByDangerCategoriesAndCriticality(array $dangerCategories, array $riskByDangerCategoriesAndRiskAssessments): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('RisksRepartitionByDangerCategoriesAndCriticality');
        $array['name']  = 'RisksRepartitionByDangerCategoriesAndCriticality';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 600;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 4;
        $array['moreCSS']    = 'grid-2';
        $array['labels']     = $this->cotations;

        // One series per level of the cotation scale, so each series carries the links of its own level
        $datasetLinks = [];
        foreach ($dangerCategories as $dangerCategory) {
            $array['data'][$dangerCategory['position']][0] = $dangerCategory['name'];
            for ($i = 1; $i <= 4; $i++) {
                $array['data'][$dangerCategory['position']]['y_combined_' . $array['labels'][$i]['label']] = !empty($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk']) ? $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i] / $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'] : 0;
                $datasetLinks[$i - 1][] = $this->getRiskListUrl(['search_category=' . $dangerCategory['position'], 'search_cotation=' . $i]);
            }
        }

        $array['morehtmlright'] = SaturneDashboard::getGraphOptionsInput(['datasetLinks' => $datasetLinks]);

        return $array;
    }

    /**
     * Get risks by danger categories
     *
     * @param array  $dangerCategories                         Danger categories datas
     * @param array  $riskByDangerCategoriesAndRiskAssessments Risk by danger categories and risk assessments
 *
     * @return array
     * @throws Exception
     */
    public function getRisksByDangerCategories(array $dangerCategories, array $riskByDangerCategoriesAndRiskAssessments): array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('RisksRepartitionByDangerCategories');
        $array['name']  = 'RisksRepartitionByDangerCategories';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 600;
        $array['type']       = 'bar';
        $array['showlegend'] = 1;
        $array['dataset']    = 2;
        $array['moreCSS']    = 'grid-2';

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('NumberOfRisks'),
                'color' => '#A1467E'
            ]
        ];

        $links = [];
        foreach ($dangerCategories as $dangerCategory) {
            $array['data'][$dangerCategory['position']][] = $dangerCategory['name'];
            $array['data'][$dangerCategory['position']][] = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'] ?? 0;
            $links[]                                     = $this->getRiskListUrl(['search_category=' . $dangerCategory['position']]);
        }

        $array['morehtmlright'] = SaturneDashboard::getGraphOptionsInput(['links' => array_filter($links)]);

        return $array;
    }

    /**
     * Get list of risks by danger categories
     *
     * @param array  $dangerCategories                         Danger categories datas
     * @param array  $riskByDangerCategoriesAndRiskAssessments Risk by danger categories and risk assessments
     * @param string $filter                                   SQL Filter
     * @param string $type                                     Risk type (risk, riskenvironmental or ...)
     *
     * @return array
     * @throws Exception
     */
    public function getRiskListsByDangerCategories(array $dangerCategories, array $riskByDangerCategoriesAndRiskAssessments) : array
    {
        global $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('RiskListsByDangerCategories');
        $array['name']  = 'RiskListsByDangerCategories';
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width'] = '100%';
        $array['type']  = 'list';

        $array['labels']['Ref']           = $langs->transnoentities('DangerCategories');
        $array['labels']['numberOfRisks'] = $langs->transnoentities('NumberOfRisks');

        if (empty($riskByDangerCategoriesAndRiskAssessments['totalRisks'])){
            return $array;
        }

        $totalNbRiskAssessments         = [1 => 0,  2 => 0, 3 => 0, 4 => 0];
        $totalPercentagesRiskAssessment = [1 => 0,  2 => 0, 3 => 0, 4 => 0];
        $totalPercentages               = 0;
        $arrayRiskLists                 = [];
        foreach ($dangerCategories as $dangerCategory) {

            $percentage        = price2num(($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'] / $riskByDangerCategoriesAndRiskAssessments['totalRisks']) * 100, 1);
            $totalPercentages += $percentage;

            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['value']    = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'];
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['value']   .= ($percentage > 0 ? ' (' . $percentage . ' %)' : '');
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['morecss']  = 'risk-evaluation-cotation';
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['moreAttr'] = 'style="line-height: normal; height: auto; border-radius: 0; background-color: #A1467EAA; color: #FFF;"';

            // Le conteneur flex est un div interne : un td en display: flex sort du flux table-cell et son contenu déborde sur les lignes voisines quand il passe sur plusieurs lignes (mobile)
            $arrayRiskLists[$dangerCategory['position']]['Ref']['value']  = '<div style="display: flex; align-items: center; max-width: 100%; gap: 5px;">';
            $arrayRiskLists[$dangerCategory['position']]['Ref']['value'] .= '<img src="' . dol_buildpath('digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png', 1) . '" class="photo" alt="' . $dangerCategory['name'] . '" title="' . $dangerCategory['name'] . '" loading="lazy" width="24px" height="24px" />';
            $arrayRiskLists[$dangerCategory['position']]['Ref']['value'] .= '<span>' . $dangerCategory['name'] . '</span>';
            $arrayRiskLists[$dangerCategory['position']]['Ref']['value'] .= '</div>';

            for ($i = 1; $i <= 4; $i++) {
                $array['labels'][$i] = $this->cotations[$i]['label'];

                $percentage                         = price2num(($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i] / $riskByDangerCategoriesAndRiskAssessments['totalRisks']) * 100, 1);
                $totalPercentagesRiskAssessment[$i] += $percentage;

                $arrayRiskLists[$dangerCategory['position']][$i]['value']    = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i];
                $totalNbRiskAssessments[$i]                                 += $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i];
                $arrayRiskLists[$dangerCategory['position']][$i]['value']   .= ($percentage > 0 ? ' (' . $percentage . ' %)' : '');
                $arrayRiskLists[$dangerCategory['position']][$i]['morecss']  = 'risk-evaluation-cotation';
                $arrayRiskLists[$dangerCategory['position']][$i]['moreAttr'] = 'data-scale = ' . $i . ' style="line-height: normal; height: auto; border-radius: 0;"';
            }
        }

        $arrayRiskLists[999]['numberOfRisks']['value']    = '<span class="badge badge-info">' . $riskByDangerCategoriesAndRiskAssessments['totalRisks'] . '</span>';
        $arrayRiskLists[999]['numberOfRisks']['morecss']  = 'risk-evaluation-cotation';
        $arrayRiskLists[999]['numberOfRisks']['moreAttr'] = 'style="line-height: normal; height: auto; border-radius: 0; background-color: #A1467EAA; color: #FFF;"';
        $arrayRiskLists[999]['numberOfRisks']['value']   .= ' (' . round($totalPercentages) . ' %)';

        $arrayRiskLists[999]['Ref']['value']              = $langs->transnoentities('Total');
        $arrayRiskLists[999]['Ref']['morecss']            = 'left';

        for ($i = 1; $i <= 4; $i++) {
            $arrayRiskLists[999][$i]['value']    = $totalNbRiskAssessments[$i] . ' (' . round($totalPercentagesRiskAssessment[$i]) . ' %)';
            $arrayRiskLists[999][$i]['morecss']  = 'risk-evaluation-cotation';
            $arrayRiskLists[999][$i]['moreAttr'] = 'data-scale = ' . $i . ' style="line-height: normal; height: auto; border-radius: 0;"';
        }

        $array['data'] = $arrayRiskLists;

        return $array;
    }

    /**
     * Get risk by danger categories and risk assessments
     *
     * @param array  $dangerCategories Danger categories datas
     * @param string $type             Risk type (risk, riskenvironmental or ...)
     * @param array  $moreParam        More param (filter)
     *
     * @return array
     * @throws Exception
     */
    public function getRiskByDangerCategoriesAndRiskAssessments(array $dangerCategories, string $type = 'risk', array $moreParam = []) : array
    {
        global $conf;

        $array = [];

        $riskAssessment  = new RiskAssessment($this->db);

        $select      = ', ra.cotation';
        $moreSelects = ['cotation'];
        $join        = ' INNER JOIN ' . MAIN_DB_PREFIX . $this->module . '_digiriskelement AS d ON d.rowid = t.fk_element';
        $join       .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $this->module . '_riskassessment AS ra ON t.rowid = ra.fk_risk';
        // fkElements: the GP/UT to count, a groupment with everything it contains; otherwise the element of the page
        if (!empty($moreParam['fkElements'])) {
            $elementFilter = ' AND t.fk_element IN (' . implode(',', array_map('intval', $moreParam['fkElements'])) . ')';
        } else {
            $elementFilter = (GETPOSTISSET('id') ? ' AND t.fk_element = ' . GETPOSTINT('id') : '');
        }
        $filter      = 't.status = ' . self::STATUS_VALIDATED . ($moreParam['filterEntity'] ?? ' AND t.entity = ' . $conf->entity) . $elementFilter . ' AND t.type = \'' . $this->db->escape($type) . '\' AND d.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND ra.status = ' . RiskAssessment::STATUS_VALIDATED;
        $risks       = saturne_fetch_all_object_type('Risk', '', '', 0, 0, ['customsql' => $filter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (!is_array($risks) || empty($risks)) {
            return $array;
        }

        $array['totalRisks'] = count($risks);

        $nbRiskByCotations          = [];
        $nbRiskByCategories         = [];
        $array['nbRiskByCotations'] = [];
        foreach ($risks as $risk) {
            if (!isset($nbRiskByCategories[$risk->category])) {
                $nbRiskByCategories[$risk->category] = 0;
            }
            $nbRiskByCategories[$risk->category]++;

            $riskAssessment->cotation = $risk->cotation;

            $scale = $riskAssessment->getEvaluationScale();
            if (!isset($array['nbRiskByCotations'][$scale])) {
                $array['nbRiskByCotations'][$scale] = 0;
            }
            $array['nbRiskByCotations'][$scale]++;

            $category = $risk->category;
            if (!isset($nbRiskByCotations[$category])) {
                $nbRiskByCotations[$category] = [];
            }

            if (!isset($nbRiskByCotations[$category][$scale])) {
                $nbRiskByCotations[$category][$scale] = 0;
            }
            $nbRiskByCotations[$category][$scale]++;
        }

        foreach ($dangerCategories as $dangerCategory) {
            $array[$dangerCategory['name']]['risk'] = !empty($nbRiskByCategories[$dangerCategory['position']]) ? $nbRiskByCategories[$dangerCategory['position']] : 0;
            for ($i = 1; $i <= 4; $i++) {
                $array[$dangerCategory['name']]['riskAssessments'][$i] = !empty($nbRiskByCotations[$dangerCategory['position']][$i]) ? $nbRiskByCotations[$dangerCategory['position']][$i] : 0;
            }
        }

        return $array;
    }

    /**
     * Write information of trigger description
     *
     * @return string Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(): string
    {
        global $conf, $langs;

        $ret = parent::getTriggerDescription();

        $digiriskelement = new DigiriskElement($this->db);
        $digiriskelement->fetch($this->fk_element);

        $ret .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        $ret .= $langs->trans('RiskCategory') . ' : ' . $this->getDangerCategoryName($this, $this->type) . '<br>';

        if (dol_strlen($this->applied_on) > 0) {
            $digiriskelement->fetch($this->applied_on);
            $ret .= $langs->trans('RiskSharedWithEntityRefLabel', $this->ref) . ' S' . $conf->entity . ' ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        }

        return $ret;
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto              Include picto in link (0 = No picto, 1 = Include picto into link, 2 = Only picto)
     *  @param  string  $option                 On what the link point to ('nolink', ...)
     *  @param  int     $notooltip              1 = Disable tooltip
     *  @param  string  $morecss                Add more css on link
     *  @param  int     $save_lastsearch_value -1 = Auto, 0 = No save of lastsearch_values when clicking, 1 = Save lastsearch_values whenclicking
     * 	@param	int     $addLabel               0 = Default, 1 = Add label into string, >1 = Add first chars into string
     *  @return	string                          String with URL
     */
    public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1, int $addLabel = 0): string
    {
        global $action, $conf, $hookmanager, $langs;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        $label = img_picto('', $this->picto) . ' <u>' . $langs->trans(ucfirst($this->element)) . '</u>';
        $label .= '<br><b>' . $langs->trans('Ref') . ' : </b> ' . $this->ref;
        $label .= '<br><b>' . $langs->transnoentities('Description') . ' : </b> ' . $this->description;

        $url = dol_buildpath('/' . $this->module . '/view/risk/risk_card.php', 1) . '?id=' . $this->id;

        if ($option != 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER['PHP_SELF'])) {
                $add_save_lastsearch_values = 1;
            }
            if ($add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans('Show' . ucfirst($this->element));
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
        } else {
            $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
        }

        if ($option == 'nolink') {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="' . $url . '"';
        }
        if ($option == 'blank') {
            $linkstart .= 'target=_blank';
        }
        $linkstart .= $linkclose . '>';
        if ($option == 'nolink' || empty($url)) {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if ($withpicto > 0) {
            $result .= img_picto('', $this->picto) . ' ';
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;

        if ($withpicto != 2) {
            if ($withpicto == 3) {
                $addLabel = 1;
            }
            $result .= (($addLabel && property_exists($this, 'label')) ? '<span class="opacitymedium">' . ' - ' . dol_trunc($this->label, ($addLabel > 1 ? $addLabel : 0)) . '</span>' : '');
        }

        $hookmanager->initHooks([$this->element . 'dao']);
        $parameters = ['id' => $this->id, 'getnomurl' => $result];
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks.
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }
}
