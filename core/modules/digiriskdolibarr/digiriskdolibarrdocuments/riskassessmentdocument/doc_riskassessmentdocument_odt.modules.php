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
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/doc_riskassessmentdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for risk assessment document
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/../modules_digiriskdolibarrdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_riskassessmentdocument_odt extends ModeleODTDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'riskassessmentdocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Load risk assessment document infos
     *
     * @param array $moreParam More param (filter)
     *
     * @throws Exception
     */
    public function loadRiskAssessmentDocumentInfos(array $moreParam): array
    {
        $array = [];

        $risk = new Risk($this->db);

        $array['dangerCategories'] = Risk::getDangerCategories();
        $array['dangerSubCategories'] = Risk::getDangerSubCategories();

        $riskArray = $risk->loadRiskInfos($moreParam);

        $array['current']['risks']                         = $riskArray['current']['risks'];
        $array['current']['riskByRiskAssessmentCotations'] = $riskArray['current']['riskByRiskAssessmentCotations'];
        $array['current']['riskByCategories']              = $riskArray['current']['riskByCategories'];
        $array['current']['riskBySubCategories']           = $riskArray['current']['riskBySubCategories'];
        $array['current']['riskByRiskAssessmentLevels']    = $riskArray['current']['riskByRiskAssessmentLevels'];
        $array['shared']['risks']                          = $riskArray['shared']['risks'];
        $array['shared']['riskByCategories']               = $riskArray['shared']['riskByCategories'];
        $array['shared']['riskBySubCategories']            = $riskArray['shared']['riskBySubCategories'];
        $array['shared']['riskByRiskAssessmentCotations']  = $riskArray['shared']['riskByRiskAssessmentCotations'];
        $array['shared']['riskByRiskAssessmentLevels']     = $riskArray['shared']['riskByRiskAssessmentLevels'];
        $array['current']['totalRisks']                    = $riskArray['current']['totalRisks'];
        $array['shared']['totalRisks']                     = $riskArray['shared']['totalRisks'];
        $array['current']['riskTasks']                     = $riskArray['current']['riskTasks'];
        $array['shared']['riskTasks']                      = $riskArray['shared']['riskTasks'];
        $array['shared']['projectEntities']                = $riskArray['shared']['projectEntities'];
        $array['riskByEntities']                           = $riskArray['riskByEntities'];

        return $array;
    }

    /**
     * Set digirisk elements segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, digiriskElements)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setDigiriskElementsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $digiriskElements = $moreParam['digiriskElements'];
            if (empty($digiriskElements)) {
                $tmpArray['digiriskElementLabel'] = '';

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($digiriskElements as $digiriskElement) {
                $depthHyphens                     = str_repeat('&nbsp;', 8 * $digiriskElement['depth']);
                $tmpArray['digiriskElementLabel'] = $depthHyphens . 'S' . $digiriskElement['object']->entity . ' - ' . $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label;

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set risk by risk assessment cotations segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, digiriskElements, riskByRiskAssessmentCotations)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskByRiskAssessmentCotationsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $digiriskElements               = $moreParam['digiriskElements'];
            $riskByRiskAssessmentCotations  = $moreParam['riskByRiskAssessmentCotations'];
            $riskAssessmentCotationTypes    = [1 => 'RiskAssessmentGrey', 2 => 'RiskAssessmentOrange', 3 => 'RiskAssessmentRed', 4 => 'RiskAssessmentBlack'];
            if (empty($digiriskElements) || empty($riskByRiskAssessmentCotations)) {
                $tmpArray['digiriskElementLabel']         = '';
                $tmpArray['description']                  = '';
                $tmpArray['totalRiskAssessmentCotations'] = '';
                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = '';
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            uasort($riskByRiskAssessmentCotations, function ($a, $b) {
                return $b['totalRiskAssessmentCotations'] <=> $a['totalRiskAssessmentCotations'];
            });

            // Order digirisk elements by risk assessment
            $orderedDigiriskElements = [];
            $digiriskElementIds      = array_keys($riskByRiskAssessmentCotations);
            foreach ($digiriskElementIds as $digiriskElementId) {
                if (isset($digiriskElements[$digiriskElementId])) {
                    $orderedDigiriskElements[$digiriskElementId] = $digiriskElements[$digiriskElementId];
                }
            }

            foreach ($orderedDigiriskElements as $orderedDigiriskElementId => $orderedDigiriskElement) {
                $tmpArray['digiriskElementLabel']         = 'S' . $orderedDigiriskElement['object']->entity . ' - ' . $orderedDigiriskElement['object']->ref . ' - ' . $orderedDigiriskElement['object']->label;
                $tmpArray['description']                  = $orderedDigiriskElement['object']->description;
                $tmpArray['totalRiskAssessmentCotations'] = $riskByRiskAssessmentCotations[$orderedDigiriskElementId]['totalRiskAssessmentCotations'] ?: 0;
                foreach ($riskAssessmentCotationTypes as $i => $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = $riskByRiskAssessmentCotations[$orderedDigiriskElementId][$i] ?: 0;
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set risk by categories segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, dangerCategories, riskByCategories)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskByCategoriesSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $entityTag        = $moreParam['entity'] == 'current' ? 'C' : 'S';
            $dangerCategories = $moreParam['dangerCategories'];
            $riskByCategories = $moreParam['riskByCategories'];
            $totalRisks       = $moreParam['totalRisks'];

            $riskAssessmentCotationTypes             = [1 => 'RiskAssessmentGrey', 2 => 'RiskAssessmentOrange', 3 => 'RiskAssessmentRed', 4 => 'RiskAssessmentBlack'];
            $totalNbRiskByRiskAssessmentCotationType = [
                'RiskAssessmentGrey'   => ['value' => 0, 'tmpArrayName' => 'TNRBRA_RAG'],
                'RiskAssessmentOrange' => ['value' => 0, 'tmpArrayName' => 'TNRBRA_RAO'],
                'RiskAssessmentRed'    => ['value' => 0, 'tmpArrayName' => 'TNRBRA_RAR'],
                'RiskAssessmentBlack'  => ['value' => 0, 'tmpArrayName' => 'TNRBRA_RAB']
            ];
            if (empty($riskByCategories)) {
                $tmpArray['picto']            = '';
                $tmpArray['riskCategoryName'] = '';
                $tmpArray['percentage']       = '';
                $tmpArray['nbRiskByCategory'] = '';
                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = '';
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);

                $tmpArray[$entityTag . 'TPBC']  = '';
                $tmpArray[$entityTag . 'TNRBC'] = '';
                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray[$entityTag . $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['tmpArrayName']] = '';
                }

                static::setTmpArrayVars($tmpArray, $odfHandler, $outputLangs, false);
                return;
            }

            $totalPercentageByCategory = 0;
            $totalNbRiskByCategory     = 0;
            foreach ($dangerCategories as $dangerCategory) {
                if ($dangerCategory['position'] == 17) {
                    continue;
                }
                $tmpArray['picto']            = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png';
                $tmpArray['riskCategoryName'] = $dangerCategory['name'];

                $nbRiskByCategory = 0;
                foreach ($riskAssessmentCotationTypes as $i => $riskAssessmentCotationType) {
                    if (isset($riskByCategories[$dangerCategory['position']][$i])) {
                        $nbRiskByCategory += $riskByCategories[$dangerCategory['position']][$i];
                    }

                    $nbRiskByRiskAssessmentCotationType                                             = $riskByCategories[$dangerCategory['position']][$i] ?: 0;
                    $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['value'] += $nbRiskByRiskAssessmentCotationType;
                    $tmpArray['nb' . $riskAssessmentCotationType]                                   = $riskByCategories[$dangerCategory['position']][$i] ?: 0;
                }

                $percentageByCategory       = ($nbRiskByCategory > 0) ? round(($nbRiskByCategory / $totalRisks) * 100, 1) : 0;
                $totalPercentageByCategory += $percentageByCategory;
                $tmpArray['percentage']     = $percentageByCategory > 0 ? $percentageByCategory . ' %' : '0 %';

                $totalNbRiskByCategory       += $nbRiskByCategory;
                $tmpArray['nbRiskByCategory'] = $nbRiskByCategory ?: 0;

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);

            $tmpArray[$entityTag . 'TPBC']  = $totalPercentageByCategory > 0 ? round($totalPercentageByCategory) . ' %' : '0 %'; // Total percentage by category
            $tmpArray[$entityTag . 'TNRBC'] = $totalNbRiskByCategory ?: 0;                                                            // Total number by category

            foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                $tmpArray[$entityTag . $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['tmpArrayName']] = $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['value'] ?: 0; // Total number by cotation type
            }

            static::setTmpArrayVars($tmpArray, $odfHandler, $outputLangs, false);
        }
    }

    /**
     * Set psychosocial risk by categories segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, dangerCategories, riskByCategories)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setPsychosocialRiskByCategoriesSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment($moreParam['segmentName']);
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $entityTag           = $moreParam['entity'] == 'current' ? 'C' : 'S';
            $dangerCategories    = $moreParam['dangerCategories'];
            $dangerSubCategories = $moreParam['dangerSubCategories'];
            $riskBySubCategories = $moreParam['riskBySubCategories'];
            $totalRisks          = $moreParam['totalRisks'];

            $riskAssessmentCotationTypes             = [1 => 'RiskAssessmentGrey', 2 => 'RiskAssessmentOrange', 3 => 'RiskAssessmentRed', 4 => 'RiskAssessmentBlack'];
            $totalNbRiskByRiskAssessmentCotationType = [
                'RiskAssessmentGrey'   => ['value' => 0, 'tmpArrayName' => 'TNPRBRA_RAG'],
                'RiskAssessmentOrange' => ['value' => 0, 'tmpArrayName' => 'TNPRBRA_RAO'],
                'RiskAssessmentRed'    => ['value' => 0, 'tmpArrayName' => 'TNPRBRA_RAR'],
                'RiskAssessmentBlack'  => ['value' => 0, 'tmpArrayName' => 'TNPRBRA_RAB']
            ];
            if (empty($riskBySubCategories)) {
                $tmpArray['picto']            = '';
                $tmpArray['riskCategoryName'] = '';
                $tmpArray['percentage']       = '';
                $tmpArray['nbRiskByCategory'] = '';
                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = '';
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);

                $tmpArray[$entityTag . 'TPPRBC']  = '';
                $tmpArray[$entityTag . 'TNPRBC'] = '';

                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray[$entityTag . $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['tmpArrayName']] = '';
                }

                static::setTmpArrayVars($tmpArray, $odfHandler, $outputLangs, false);
                return;
            }

            $totalPercentageByCategory = 0;
            $totalNbRiskByCategory     = 0;
            foreach ($dangerCategories as $dangerCategory) {
                if ($dangerCategory['position'] != 17) {
                    continue;
                }

                foreach($dangerSubCategories[$dangerCategory['position']] as $dangerSubCategory) {
                    $tmpArray['picto']            = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png';
                    $tmpArray['riskCategoryName'] = $dangerSubCategory['name'];

                    $nbRiskByCategory = 0;
                    foreach ($riskAssessmentCotationTypes as $i => $riskAssessmentCotationType) {
                        if (isset($riskBySubCategories[$dangerSubCategory['position']][$i])) {
                            $nbRiskByCategory += $riskBySubCategories[$dangerSubCategory['position']][$i];
                        }

                        $nbRiskByRiskAssessmentCotationType                                             = $riskBySubCategories[$dangerSubCategory['position']][$i] ?: 0;
                        $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['value'] += $nbRiskByRiskAssessmentCotationType;
                        $tmpArray['nb' . $riskAssessmentCotationType]                                   = $riskBySubCategories[$dangerSubCategory['position']][$i] ?: 0;
                    }
                    $percentageByCategory       = ($nbRiskByCategory > 0) ? round(($nbRiskByCategory / $totalRisks) * 100, 1) : 0;
                    $totalPercentageByCategory += $percentageByCategory;
                    $tmpArray['percentage']     = $percentageByCategory > 0 ? $percentageByCategory . ' %' : '0 %';

                    $totalNbRiskByCategory       += $nbRiskByCategory;
                    $tmpArray['nbRiskByCategory'] = $nbRiskByCategory ?: 0;

                    static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
            }
            $odfHandler->mergeSegment($listLines);

            $tmpArray[$entityTag . 'TPPRBC']  = $totalPercentageByCategory > 0 ? round($totalPercentageByCategory) . ' %' : '0 %'; // Total percentage by category
            $tmpArray[$entityTag . 'TNPRBC'] = $totalNbRiskByCategory ?: 0;                                                            // Total number by category

            foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                $tmpArray[$entityTag . $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['tmpArrayName']] = $totalNbRiskByRiskAssessmentCotationType[$riskAssessmentCotationType]['value'] ?: 0; // Total number by cotation type
            }

            static::setTmpArrayVars($tmpArray, $odfHandler, $outputLangs, false);
        }
    }

    /**
     * Set risk by entities segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, entities, riskByCategories)
     *
     * @throws OdfException
     * @throws Exception
     */
    private function setRiskByEntitiesSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('RiskByEntities');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $riskByEntities              = $moreParam['riskByEntities'];
            $nbEvaluatorByEntities       = $moreParam['nbEvaluatorByEntities'];
            $riskAssessmentCotationTypes = [1 => 'RiskAssessmentGrey', 2 => 'RiskAssessmentOrange', 3 => 'RiskAssessmentRed', 4 => 'RiskAssessmentBlack'];
            if (empty($riskByEntities)) {
                $tmpArray['companyName']         = '';
                $tmpArray['siret']               = '';
                $tmpArray['nbEmployeesInvolved'] = '';
                $tmpArray['nbTotalRisks']        = '';
                foreach ($riskAssessmentCotationTypes as $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = '';
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($riskByEntities as $entity => $riskByEntity) {
                $tmpArray['companyName']         = dolibarr_get_const($this->db, 'MAIN_INFO_SOCIETE_NOM', $entity);
                $tmpArray['siret']               = dolibarr_get_const($this->db, 'MAIN_INFO_SIRET', $entity);
                $tmpArray['nbEmployeesInvolved'] = $nbEvaluatorByEntities[$entity] ?? 0;
                $tmpArray['nbTotalRisks']        = $riskByEntity['nbTotalRisks'] ?: 0;
                foreach ($riskAssessmentCotationTypes as $i => $riskAssessmentCotationType) {
                    $tmpArray['nb' . $riskAssessmentCotationType] = $riskByEntity[$i] ?: 0;
                }

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set risk assessment document by entity
     *
     * @param Odf       $odfHandler                      Object builder odf library
     * @param Translate $outputLangs                     Lang object to use for output
     * @param array     $moreParam                       More param (objectDocument, entity (current/shared))
     * @param array     $loadRiskAssessmentDocumentInfos Load risk assessment document infos (currentDigiriskElements, sharedDigiriskElements, riskByRiskAssessmentCotations, dangerCategories, riskByCategories)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskAssessmentDocumentByEntity(Odf $odfHandler, Translate $outputLangs, array $moreParam, array $loadRiskAssessmentDocumentInfos): void
    {
        $moreParam['segmentName'] = $moreParam['entity'] . 'DigiriskElements';
        static::setDigiriskElementsSegment($odfHandler, $outputLangs, $moreParam);

        $moreParam['riskByRiskAssessmentCotations'] = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['riskByRiskAssessmentCotations'];

        $moreParam['segmentName'] = $moreParam['entity'] . 'RiskByRiskAssessmentCotations';
        static::setRiskByRiskAssessmentCotationsSegment($odfHandler, $outputLangs, $moreParam);

        $moreParam['dangerCategories']    = $loadRiskAssessmentDocumentInfos['dangerCategories'];
        $moreParam['dangerSubCategories'] = $loadRiskAssessmentDocumentInfos['dangerSubCategories'];
        $moreParam['riskByCategories']    = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['riskByCategories'];
        $moreParam['riskBySubCategories'] = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['riskBySubCategories'];
        $moreParam['totalRisks']          = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['totalRisks'];

        $moreParam['segmentName'] = $moreParam['entity'] . 'RiskByCategories';
        static::setRiskByCategoriesSegment($odfHandler, $outputLangs, $moreParam);

        $moreParam['segmentName'] = $moreParam['entity'] . 'PsychosocialRiskByCategories';
        static::setPsychosocialRiskByCategoriesSegment($odfHandler, $outputLangs, $moreParam);

        $moreParam['riskTasks'] = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['riskTasks'];
        if ($moreParam['entity'] == 'shared') {
            $moreParam['projectEntities'] = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['projectEntities'];
        }
        $moreParam['riskByRiskAssessmentLevels'] = $loadRiskAssessmentDocumentInfos[$moreParam['entity']]['riskByRiskAssessmentLevels'];
        for ($i = 4; $i >= 1; $i--) {
            $moreParam['segmentName'] = $moreParam['entity'] . 'Risks' . $i;
            static::setRiskByRiskAssessmentLevelsSegment($odfHandler, $outputLangs, $moreParam);
        }
    }

    /**
     * Fill all odt tags for segments lines
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @return int                    1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        // Replace tags of lines
        try {
            require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_ticket.lib.php';

            $digiriskElement = new DigiriskElement($this->db);

            $loadRiskAssessmentDocumentInfos = $this->loadRiskAssessmentDocumentInfos($moreParam);
            $loadEvaluatorInfos              = Evaluator::loadEvaluatorInfos();
            $loadDigiriskElementInfos        = $digiriskElement->loadDigiriskElementInfos($moreParam);
            $loadTicketInfos                 = load_ticket_infos($moreParam);

            $moreParam['entity']           = 'current';
            $moreParam['digiriskElements'] = $loadDigiriskElementInfos[$moreParam['entity']]['digiriskElements'];
            static::setRiskAssessmentDocumentByEntity($odfHandler, $outputLangs, $moreParam, $loadRiskAssessmentDocumentInfos);

            if ($moreParam['tmparray']['showSharedRisk_nocheck']) {
                $moreParam['entity']           = 'shared';
                $moreParam['digiriskElements'] = $loadDigiriskElementInfos[$moreParam['entity']]['digiriskElements'];
                static::setRiskAssessmentDocumentByEntity($odfHandler, $outputLangs, $moreParam, $loadRiskAssessmentDocumentInfos);
            }

            $moreParam['nbEvaluatorByEntities'] = $loadEvaluatorInfos['nbEvaluatorByEntities'];
            $moreParam['riskByEntities']        = $loadRiskAssessmentDocumentInfos['riskByEntities'];
            $this->setRiskByEntitiesSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['tickets'] = $loadTicketInfos['tickets'];
            static::setTicketsSegment($odfHandler, $outputLangs, $moreParam);
        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }

        return 0;
    }

    /**
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
    {
        global $conf, $mysoc;

        $fileArray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $objectDocument->element . '/siteplans', 'files', 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
        if (is_array($fileArray) && !empty($fileArray)) {
            $sitePlans                    = array_shift($fileArray);
            $thumb_name                   = saturne_get_thumb_name($sitePlans['name']);
            $tmpArray['photo_site_plans'] = $sitePlans['path'] . '/thumbs/' . $thumb_name;
        } else {
            $noPhoto                      = '/public/theme/common/nophoto.png';
            $tmpArray['photo_site_plans'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $arraySoc                             = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
        $tmpArray['mycompany_photo_fullsize'] = $arraySoc['mycompany_logo'];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = $objectDocument->element . '@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

        $tmpArray['showSharedRisk_nocheck'] = false;
        if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS')) {
            $tmpArray['showSharedRisk_nocheck'] = true;
        }

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
