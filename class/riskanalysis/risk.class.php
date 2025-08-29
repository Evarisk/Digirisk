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
require_once __DIR__ . '/../../../saturne/class/task/saturnetask.class.php';
require_once __DIR__ . '/riskassessment.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturneobject.class.php';

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

	/**
	 * @var int Does object support extrafields ? 0 = No, 1 = Yes
	 */
	public $isextrafieldmanaged = 1;

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_LOCKED    = 2;
    public const STATUS_ARCHIVED  = 3;

	/**
	 * @var string String with name of icon for risk. Must be the part after the 'object_' into object_risk.png
	 */
	public $picto = 'fontawesome_fa-exclamation-triangle_fas_#d35968';

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

    private $cotations = [];

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
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
     * @param  array     $moreParam More param (tmparray/filterRisk)
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

        $filter        = 't.status = ' . Risk::STATUS_VALIDATED . ' AND d.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND ra.status = ' . RiskAssessment::STATUS_VALIDATED .  ($moreParam['filter'] ?? '') . (!empty($moreParam['filterRisk']) ? $moreParam['filterRisk'] : ' AND t.type = \'risk\'');
        $currentFilter = $filter . ' AND t.entity = ' . $conf->entity;

        $array['riskByEntities']   = [];
        $array['current']['risks'] = saturne_fetch_all_object_type('Risk', 'DESC', 'riskAssessmentCotation', 0, 0, ['customsql' => $currentFilter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (!is_array($array['current']['risks']) || empty($array['current']['risks'])) {
            $array['current']['risks']                         = [];
            $array['current']['riskByRiskAssessmentCotations'] = [];
            $array['current']['riskByCategories']              = [];
            $array['current']['riskBySubCategories']           = [];
            $array['current']['psychosocialRisksByGPUT']       = [];
            $array['current']['riskByRiskAssessmentLevels']    = [];
        }

        if (empty($moreParam['tmparray']['showSharedRisk_nocheck'])) {
            $array['shared']['risks']                         = [];
            $array['shared']['riskByCategories']              = [];
            $array['shared']['riskBySubCategories']           = [];
            $array['shared']['psychosocialRisksByGPUT']       = [];
            $array['shared']['riskByRiskAssessmentCotations'] = [];
            $array['shared']['riskByRiskAssessmentLevels']    = [];
        }

        if ($moreParam['tmparray']['showSharedRisk_nocheck']) {
            $array['shared']['risks'] = saturne_fetch_all_object_type('Risk', 'DESC', 'riskAssessmentCotation', 0, 0, ['customsql' => $filter], 'AND', false, true, false, $sharedJoin, [], $sharedSelect, $sharedMoreSelects);
            if (!is_array($array['shared']['risks']) || empty($array['shared']['risks'])) {
                $array['shared']['risks']                          = [];
                $array['shared']['riskByCategories']               = [];
                $array['shared']['riskBySubCategories']            = [];
                $array['shared']['psychosocialRisksByGPUT']       = [];
                $array['shared']['riskByRiskAssessmentCotations']  = [];
                $array['shared']['riskByRiskAssessmentLevels']     = [];
            }
        }

        $array['risks'] = array_merge($array['current']['risks'], $array['shared']['risks']);
        $nbTotalRisks = ['current' => 0,'shared' => 0];

        foreach ($array['risks'] as $risk) {
            $riskAssessment->cotation = $risk->riskAssessmentCotation;
            $entity                   = ($risk->entity == $conf->entity || (!isModEnabled('multicompany') && empty($risk->entity))) ? 'current' : 'shared';

            $array[$entity]['riskByRiskAssessmentLevels'][$riskAssessment->getEvaluationScale()][] = $risk;
            $array[$entity]['riskByRiskAssessmentCotations'][$risk->fk_element]['totalRiskAssessmentCotations'] += $risk->riskAssessmentCotation;
            $array[$entity]['riskByRiskAssessmentCotations'][$risk->fk_element][$riskAssessment->getEvaluationScale()]++;
            if ($risk->sub_category >= 0) {
                $array[$entity]['psychosocialRisksByGPUT'][$risk->fk_element][$risk->sub_category][$risk->riskAssessmentDate] = $riskAssessment->cotation;
            }
            $array[$entity]['riskByCategories'][$risk->category][$riskAssessment->getEvaluationScale()]++;
            $array[$entity]['riskBySubCategories'][$risk->sub_category][$riskAssessment->getEvaluationScale()]++;
            $array['riskByEntities'][$risk->entity]['nbTotalRisks']++;
            $array['riskByEntities'][$risk->entity][$riskAssessment->getEvaluationScale()]++;
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
        if (!is_array($array['tasks']) || empty($array['tasks'])) {
            $array['tasks']                     = [];
            $array['current']['riskTasks']      = [];
            $array['shared']['riskTasks']       = [];
            $array['shared']['projectEntities'] = [];
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
        global $conf;

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

		if (is_array($riskList) && !empty($riskList)) {
			foreach ($riskList as $riskSingle) {
				$riskSingle->lastEvaluation                               = $riskAssessmentsOrderedByRisk[$riskSingle->id];
				$riskSingle->appliedOn                                    = $riskSingle->fk_element;
				$risksOrderedByDigiriskElement[$riskSingle->fk_element][] = $riskSingle;
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
						if (is_array($risksOrderedByDigiriskElement[$child_id]) && !empty($risksOrderedByDigiriskElement[$child_id])) {
                            foreach ($risksOrderedByDigiriskElement[$child_id] as $riskOfChildDigiriskElement) {
                                if ($riskOfChildDigiriskElement->entity == $conf->entity) {
                                    $risks[] = $riskOfChildDigiriskElement;
                                }
                            }
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
								if (is_object($sharedRisk) && $sharedParentActive > 0 && !in_array($sharedRisk->id, $inserted)) {
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
                            if (is_object($sharedRisk)  && $sharedParentActive > 0 && !in_array($sharedRisk->id, $inserted)) {
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
	 * Get danger category picto name
	 *
	 * @param         $object
     * @param  string $riskType         Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['name'] Name to danger category picto, -1 if don't exist
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
            return $langs->trans('-');
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
	 * Get danger category picto path
	 *
	 * @param  int    $position
     * @param  string $riskType                   Type of risk ('risk', 'riskenvironmental', etc.)
	 * @return string $category['thumbnail_name'] Path to danger category picto, -1 if don't exist
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

        $dangerCategories                         = static::getDangerCategories($riskType);
        $riskByDangerCategoriesAndRiskAssessments = $this->getRiskByDangerCategoriesAndRiskAssessments($dangerCategories, $riskType);

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
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;
        $array['labels']     = $this->cotations;

        $array['data'] = $riskByDangerCategoriesAndRiskAssessments['nbRiskByCotations'];

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

        foreach ($dangerCategories as $dangerCategory) {
            $array['data'][$dangerCategory['position']][0] = $dangerCategory['name'];
            for ($i = 1; $i <= 4; $i++) {
                $array['data'][$dangerCategory['position']]['y_combined_' . $array['labels'][$i]['label']] = !empty($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk']) ? $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i] / $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'] : 0;
            }
        }

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

        foreach ($dangerCategories as $dangerCategory) {
            $array['data'][$dangerCategory['position']][] = $dangerCategory['name'];
            $array['data'][$dangerCategory['position']][] = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'];
        }

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

        $totalNbRiskAssessments         = [];
        $totalPercentagesRiskAssessment = [];
        $totalPercentages               = 0;
        $arrayRiskLists                 = [];
        foreach ($dangerCategories as $dangerCategory) {

            $percentage        = price2num(($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'] / $riskByDangerCategoriesAndRiskAssessments['totalRisks']) * 100, 1);
            $totalPercentages += $percentage;

            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['value']    = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['risk'];
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['value']   .= ($percentage > 0 ? ' (' . $percentage . ' %)' : '');
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['morecss']  = 'risk-evaluation-cotation';
            $arrayRiskLists[$dangerCategory['position']]['numberOfRisks']['moreAttr'] = 'style="line-height: 0; border-radius: 0; background-color: #A1467EAA; color: #FFF;"';

            $arrayRiskLists[$dangerCategory['position']]['Ref']['value']    = '<img src="' . dol_buildpath('digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png', 1) . '" class="photo" alt="' . $dangerCategory['name'] . '" title="' . $dangerCategory['name'] . '" loading="lazy" width="24px" height="24px" />';
            $arrayRiskLists[$dangerCategory['position']]['Ref']['value']   .= $dangerCategory['name'];
            $arrayRiskLists[$dangerCategory['position']]['Ref']['moreAttr'] = 'style="display: flex; align-items: center; max-width: 100%; gap: 5px;"';

            for ($i = 1; $i <= 4; $i++) {
                $array['labels'][$i] = $this->cotations[$i]['label'];

                $percentage                          = price2num(($riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i] / $riskByDangerCategoriesAndRiskAssessments['totalRisks']) * 100, 1);
                $totalPercentagesRiskAssessment[$i] += $percentage;

                $arrayRiskLists[$dangerCategory['position']][$i]['value']    = $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i];
                $totalNbRiskAssessments[$i]                                 += $riskByDangerCategoriesAndRiskAssessments[$dangerCategory['name']]['riskAssessments'][$i];
                $arrayRiskLists[$dangerCategory['position']][$i]['value']   .= ($percentage > 0 ? ' (' . $percentage . ' %)' : '');
                $arrayRiskLists[$dangerCategory['position']][$i]['morecss']  = 'risk-evaluation-cotation';
                $arrayRiskLists[$dangerCategory['position']][$i]['moreAttr'] = 'data-scale = ' . $i . ' style="line-height: 0; border-radius: 0;"';
            }
        }

        $arrayRiskLists[23]['numberOfRisks']['value']    = '<span class="badge badge-info">' . $riskByDangerCategoriesAndRiskAssessments['totalRisks'] . '</span>';
        $arrayRiskLists[23]['numberOfRisks']['morecss']  = 'risk-evaluation-cotation';
        $arrayRiskLists[23]['numberOfRisks']['moreAttr'] = 'style="line-height: 0; border-radius: 0; background-color: #A1467EAA; color: #FFF;"';
        $arrayRiskLists[23]['numberOfRisks']['value']   .= ' (' . round($totalPercentages) . ' %)';

        $arrayRiskLists[23]['Ref']['value']              = $langs->transnoentities('Total');
        $arrayRiskLists[23]['Ref']['morecss']            = 'left';

        for ($i = 1; $i <= 4; $i++) {
            $arrayRiskLists[23][$i]['value']    = $totalNbRiskAssessments[$i] . ' (' . round($totalPercentagesRiskAssessment[$i]) . ' %)';
            $arrayRiskLists[23][$i]['morecss']  = 'risk-evaluation-cotation';
            $arrayRiskLists[23][$i]['moreAttr'] = 'data-scale = ' . $i . ' style="line-height: 0; border-radius: 0;"';
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
        $filter      = 't.status = ' . self::STATUS_VALIDATED . ($moreParam['filterEntity'] ?? ' AND t.entity = ' . $conf->entity) . (GETPOSTISSET('id') ? ' AND t.fk_element = ' . GETPOSTINT('id') : '') . ' AND t.type = \'' . $this->db->escape($type) . '\' AND d.status = ' . DigiriskElement::STATUS_VALIDATED . ' AND ra.status = ' . RiskAssessment::STATUS_VALIDATED;
        $risks       = saturne_fetch_all_object_type('Risk', '', '', 0, 0, ['customsql' => $filter], 'AND', false, false, false, $join, [], $select, $moreSelects);
        if (!is_array($risks) || empty($risks)) {
            return $array;
        }

        $array['totalRisks'] = count($risks);

        $nbRiskByCotations  = [];
        $nbRiskByCategories = [];
        foreach ($risks as $risk) {
            $nbRiskByCategories[$risk->category]++;
            $riskAssessment->cotation = $risk->cotation;
            $array['nbRiskByCotations'][$riskAssessment->getEvaluationScale()]++;
            $nbRiskByCotations[$risk->category][$riskAssessment->getEvaluationScale()]++;
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
     * @param  Object $object Object calling the trigger
     * @return string         Description to display in actioncomm->note_private
     */
    public function getTriggerDescription(SaturneObject $object): string
    {
        global $conf, $langs;

        $ret = parent::getTriggerDescription($object);

        $digiriskelement = new DigiriskElement($this->db);
        $digiriskelement->fetch($object->fk_element);

        $ret .= $langs->trans('ParentElement') . ' : ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
        $ret .= $langs->trans('RiskCategory') . ' : ' . $object->getDangerCategoryName($object, $object->type) . '<br>';

        if (dol_strlen($object->applied_on) > 0) {
            $digiriskelement->fetch($object->applied_on);
            $ret .= $langs->trans('RiskSharedWithEntityRefLabel', $object->ref) . ' S' . $conf->entity . ' ' . $digiriskelement->ref . " - " . $digiriskelement->label . '<br>';
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

        $url = dol_buildpath('/' . $this->module . '/view/digiriskelement/digiriskelement_risk.php', 1) . '?id=' . $this->fk_element;

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
