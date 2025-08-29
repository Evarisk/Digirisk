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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/modules_digiriskdolibarrdocument.php
 * \ingroup digiriskdolibarr
 * \brief   File that contains parent class for digiriskdolibarr documents models
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 * Parent class for documents models
 */
abstract class ModeleODTDigiriskDolibarrDocument extends SaturneDocumentModel
{
    /**
     * @var string Module
     */
    public string $module = 'digiriskdolibarr';

    /**
     * Set risk by risk assessment levels segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (segmentName, digiriskElements, riskByRiskAssessmentLevels)
     *
     * @throws OdfException
     * @throws Exception
     */
    protected static function setRiskByRiskAssessmentLevelsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
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
            $digiriskElements           = $moreParam['digiriskElements'];
            $riskByRiskAssessmentLevels = $moreParam['riskByRiskAssessmentLevels'];
            $riskAssessmentLevel        = explode('Risks', $moreParam['segmentName'])[1];
            if (empty($digiriskElements) || empty($riskByRiskAssessmentLevels) || empty($riskByRiskAssessmentLevels[$riskAssessmentLevel])) {
                $tmpArray['digiriskElementLabel']   = '';
                $tmpArray['picto']                  = '';
                $tmpArray['riskCategoryName']       = '-';
                $tmpArray['ref']                    = '-';
                $tmpArray['riskAssessmentCotation'] = '-';
                $tmpArray['description']            = '-';
                $tmpArray['riskAssessmentComment']  = '-';
                $tmpArray['riskTaskUncompleted']    = '-';
                $tmpArray['riskTaskCompleted']      = '-';
                $tmpArray['riskAssessment_photo']   = '-';

                SaturneDocumentModel::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($riskByRiskAssessmentLevels[$riskAssessmentLevel] as $risk) {
                $digiriskElement = $digiriskElements[$risk->fk_element];
                if (empty($digiriskElement)) {
                    continue; // Skip if digirisk element not found (case of GP/UT fiche with spécific id)
                }

                $depthHyphens                     = str_repeat('- ', $digiriskElement['depth']);
                $tmpArray['digiriskElementLabel'] = $depthHyphens . 'S' . $digiriskElement['object']->entity . ' - ' . $digiriskElement['object']->ref . ' - ' . $digiriskElement['object']->label;

                $tmpArray['picto']                  = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->getDangerCategory($risk, $risk->type) . '.png';
                $tmpArray['riskCategoryName']       = getDolGlobalInt('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME') ? $risk->getDangerCategoryName($risk, $risk->type) : ' ';
                $tmpArray['ref']                    = $risk->ref . ' - ' . $risk->riskAssessmentRef;
                $tmpArray['riskAssessmentCotation'] = $risk->riskAssessmentCotation ?: 0;
                $tmpArray['description']            = $risk->description;

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT') && !empty($risk->riskAssessmentComment)) {
                    $tmpArray['riskAssessmentComment'] = dol_print_date((getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE') && !empty($risk->riskAssessmentDate) ? $risk->riskAssessmentDate : $risk->riskAssessmentDateCreation), 'dayreduceformat') . ': ';
                }
                $tmpArray['riskAssessmentComment'] = $risk->riskAssessmentComment ?: '';

                $moreParam['riskId']             = $risk->id;
                $riskTask                        = static::setRiskTasksTag($outputLangs, $moreParam);
                $tmpArray['riskTaskUncompleted'] = $riskTask['riskTaskUncompleted'];
                $tmpArray['riskTaskCompleted']   = $riskTask['riskTaskCompleted'];

                $tmpArray['riskAssessment_photo'] = $outputLangs->transnoentities('NoFileLinked');
                if (!empty($risk->riskAssessmentPhoto)) {
                    $entityPath                       = $risk->entity != 1 ? '/' . $risk->entity : '';
                    $path                             = DOL_DATA_ROOT . $entityPath . '/digiriskdolibarr/riskassessment/' . $risk->riskAssessmentRef;
                    $fileSmall                        = saturne_get_thumb_name($risk->riskAssessmentPhoto);
                    $image                            = $path . '/thumbs/' . $fileSmall;
                    $tmpArray['riskAssessment_photo'] = $image;
                }

                SaturneDocumentModel::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set risk tasks segment
     *
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (projectEntities, riskId, riskTasks)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskTasksTag(Translate $outputLangs, array $moreParam): array
    {
        global $conf, $mc;

        $array = [];

        $result          = 1;
        $projectEntities = $moreParam['projectEntities'];
        if ($moreParam['entity'] == 'shared' && !empty($projectEntities)) {
            $result = !empty($mc->sharings['project']) ? empty(array_diff(array_keys($projectEntities), $mc->sharings['project'])) : 0;
        }

        if ($result == 0) {
            $array['riskTaskUncompleted'] = $outputLangs->trans('NoTaskShared');
            $array['riskTaskCompleted']   = $outputLangs->trans('NoTaskShared');
            return $array;
        }

        $riskId    = $moreParam['riskId'];
        $riskTasks = $moreParam['riskTasks'];
        if (empty($riskTasks) || empty($riskTasks[$riskId])) {
            $array['riskTaskUncompleted'] = '';
            $array['riskTaskCompleted']   = '';
            return $array;
        }

        foreach ($riskTasks[$riskId] as $riskTask) {
            //@todo a refaire
            $AllInitiales = '';
            $related_task_contact_ids = $riskTask->getListContactId();
            if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
                $userTmp = new User($riskTask->db);
                foreach ($related_task_contact_ids as $related_task_contact_id) {
                    $userTmp->fetch($related_task_contact_id);
                    $AllInitiales .= strtoupper(str_split($userTmp->firstname, 1)[0] . str_split($userTmp->lastname, 1)[0] . ',');
                }
            }

            $contactslistinternal = $riskTask->liste_contact(-1, 'internal');
            $responsible = '';

            if (!empty($contactslistinternal) && is_array($contactslistinternal)) {
                foreach ($contactslistinternal as $contactlistinternal) {
                    if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
                        $responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
                    }
                }
            }

            $riskTaskProgress = $riskTask->progress;
            if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS')) {
                $timeSpents = $riskTask->getSummaryOfTimeSpent();
                if ($timeSpents['total_duration'] > 0 && !empty($riskTask->planned_workload)) {
                    $riskTaskProgress = round($timeSpents['total_duration'] / $riskTask->planned_workload * 100, 2);
                }
            }

            $riskTaskTypes = [];
            if ($riskTaskProgress == 100) {
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE')) {
                    $array['riskTaskCompleted'] = $outputLangs->transnoentities('ActionPreventionCompletedTaskDone');
                } else {
                    $riskTaskTypes[] = 'Completed';
                }
            } else {
                $riskTaskTypes[] = 'Uncompleted';
            }
            foreach ($riskTaskTypes as $riskTaskType) {
                $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Label') . ' : ' . $riskTask->label . '<br>';
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Ref') . ' : ' . $riskTask->ref . '<br>';
                }
                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT')) {
                    (($responsible) ? $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Responsible') . ' : ' . $responsible . '<br>' : '');
                }

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('DateStart') . ' : ';
                    if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE') && !empty($riskTask->dateo)) {
                        $array['riskTask' . $riskTaskType] .= dol_print_date($riskTask->dateo, 'dayreduceformat') . '<br>';
                    } else {
                        $array['riskTask' . $riskTaskType] .= dol_print_date($riskTask->datec, 'dayreduceformat') . '<br>';
                    }
                    if (getDolGlobalInt('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE') && !empty($riskTask->datee)) {
                        $array['riskTask' . $riskTaskType] .= $outputLangs->transnoentities('Deadline') . ' : ' . dol_print_date($riskTask->datee, 'dayreduceformat') . '<br>';
                    }
                }

                if (!getDolGlobalInt('DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT')) {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->trans('Budget') . ' : ' . price($riskTask->budget_amount, 0, $outputLangs, 1, 0, 0, $conf->currency) . ' - ';
                }
                if ($riskTaskType != 'Completed') {
                    $array['riskTask' . $riskTaskType] .= $outputLangs->trans('DigiriskProgress') . ' : ' . ($riskTaskProgress ?: 0) . ' %'  . '<br>';
                }

                (($AllInitiales) ? $array['riskTask' . $riskTaskType] .= $outputLangs->trans('ContactsAction') . ' : ' . $AllInitiales . '<br>' : '');
                (($riskTask->description) ? $array['riskTask' . $riskTaskType] .= $outputLangs->trans('Description') . ' : ' . $riskTask->description . '<br>' : '');

                $array['riskTask' . $riskTaskType] .= '<br>';
            }
        }

        return $array;
    }

    /**
     * Set risk signs segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (riskSigns)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setRiskSignsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('affectedRecommandation');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $riskSigns = $moreParam['riskSigns'];
            if (empty($riskSigns)) {
                $tmpArray = [
                    'nomElement'                => '',
                    'recommandation_photo'      => '',
                    'identifiantRecommandation' => '',
                    'recommandationName'        => '',
                    'recommandationComment'     => '',
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($riskSigns as $riskSign) {
                $tmpArray = [
                    'nomElement'                => 'S' . $riskSign->digiriskElementEntity . ' - ' . $riskSign->digiriskElementRef . ' - ' . $riskSign->digiriskElementLabel,
                    'recommandation_photo'      => DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/' . $riskSign->getRiskSignCategory($riskSign),
                    'identifiantRecommandation' => $riskSign->ref,
                    'recommandationName'        => getDolGlobalInt('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME') ? $riskSign->getRiskSignCategory($riskSign, 'name') : ' ',
                    'recommandationComment'     => $riskSign->description
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set evaluators segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (evaluators)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setEvaluatorsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('utilisateursPresents');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $evaluators = $moreParam['evaluators'];
            if (empty($evaluators)) {
                $tmpArray = [
                    'nomElement'                 => '',
                    'idUtilisateur'              => '',
                    'dateAffectationUtilisateur' => '',
                    'dureeEntretien'             => '',
                    'nomUtilisateur'             => '',
                    'prenomUtilisateur'          => '',
                    'travailUtilisateur'         => '',
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($evaluators as $evaluator) {
                $tmpArray = [
                    'nomElement'                 => 'S' . $evaluator->digiriskElementEntity . ' - ' . $evaluator->digiriskElementRef . ' - ' . $evaluator->digiriskElementLabel,
                    'idUtilisateur'              => $evaluator->ref,
                    'dateAffectationUtilisateur' => dol_print_date($evaluator->assignment_date, 'day'),
                    'dureeEntretien'             => $evaluator->duration . ' min',
                    'nomUtilisateur'             => $evaluator->userLastName,
                    'prenomUtilisateur'          => $evaluator->userFirstName,
                    'travailUtilisateur'         => $evaluator->job
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set accidents segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (accidents)
     *
     * @throws OdfException
     * @throws Exception
     */
    private static function setAccidentsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('affectedAccident');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $accidents = $moreParam['accidents'];
            if (empty($accidents)) {
                $tmpArray = [
                    'identifiantAccident'  => '',
                    'AccidentName'         => '',
                    'AccidentWorkStopDays' => '',
                    'AccidentComment'      => '',
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            foreach ($accidents as $accident) {
                $tmpArray = [
                    'identifiantAccident'  => $accident->ref,
                    'AccidentName'         => $accident->label,
                    'AccidentWorkStopDays' => $accident->nbAccidentWorkStop,
                    'AccidentComment'      => $accident->description,
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
        }
    }

    /**
     * Set tickets segment
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (tickets)
     *
     * @throws OdfException
     * @throws Exception
     */
    protected function setTicketsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam): void
    {
        $foundTagForLines = 1;
        try {
            $listLines = $odfHandler->setSegment('tickets');
        } catch (OdfExceptionSegmentNotFound $e) {
            // We may arrive here if tags for lines not present into template
            $foundTagForLines = 0;
            $listLines        = '';
            dol_syslog($e->getMessage());
        }

        if ($foundTagForLines) {
            $tickets = $moreParam['tickets'];
            if (empty($tickets)) {
                $tmpArray = [
                    'refticket'                 => '',
                    'categories'                => '',
                    'creation_date'             => '',
                    'subject'                   => '',
                    'message'                   => '',
                    'progress'                  => '',
                    'digiriskelement_ref_label' => '',
                    'status'                    => '',
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                $odfHandler->mergeSegment($listLines);
                return;
            }

            // Load Dolibarr libraries
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

            $category = new Categorie($this->db);

            foreach ($tickets as $ticket) {
                $categories = $category->containing($ticket->id, Categorie::TYPE_TICKET);

                $tmpArray = [
                    'refticket'                 => $ticket->ref,
                    'categories'                => !empty($categories) ? implode(', ', array_map(fn($cat) => $cat->label, $categories)) : '',
                    'creation_date'             => dol_print_date($ticket->datec, 'dayhour', 'tzuser'),
                    'subject'                   => $ticket->subject,
                    'message'                   => $ticket->message,
                    'progress'                  => ($ticket->progress ?: 0) . ' %',
                    'digiriskelement_ref_label' => $ticket->digiriskElementRef . ' - ' . $ticket->digiriskElementLabel,
                    'status'                    => $ticket->getLibStatut()
                ];

                static::setTmpArrayVars($tmpArray, $listLines, $outputLangs);
            }
            $odfHandler->mergeSegment($listLines);
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
            // Load DigiriskDolibarr libraries
            require_once __DIR__ . '/../../../../class/riskanalysis/risk.class.php';
            require_once __DIR__ . '/../../../../class/riskanalysis/risksign.class.php';
            require_once __DIR__ . '/../../../../class/evaluator.class.php';
            require_once __DIR__ . '/../../../../class/accident.class.php';
            require_once __DIR__ . '/../../../../lib/digiriskdolibarr_ticket.lib.php';

            $risk     = new Risk($this->db);
            $accident = new Accident($this->db);

            $loadRiskInfos     = $risk->loadRiskInfos($moreParam);
            $loadRiskSignInfos = RiskSign::loadRiskSignInfos($moreParam);
            $loadAccidentInfos = $accident->loadAccidentInfos($moreParam);
            $loadTicketInfos   = load_ticket_infos($moreParam);

            $moreParam['filter'] = ''; // Need because Evaluator don't have fk_element @todo rework fk_parent in fk_element
            $loadEvaluatorInfos  = Evaluator::loadEvaluatorInfos($moreParam);

            if (!isset($moreParam['digiriskElements'])) {
                $digiriskElements[$moreParam['object']->id]['object'] = $moreParam['object'];
                $digiriskElements[$moreParam['object']->id]['depth']  = 0;
                $moreParam['digiriskElements']                        = $digiriskElements;
            }
            $moreParam['entity']                     = 'current';
            $moreParam['riskTasks']                  = $loadRiskInfos['current']['riskTasks'];
            $moreParam['riskByRiskAssessmentLevels'] = $loadRiskInfos['current']['riskByRiskAssessmentLevels'];
            for ($i = 4; $i >= 1; $i--) {
                $moreParam['segmentName'] = $moreParam['entity'] . 'Risks' . $i;
                static::setRiskByRiskAssessmentLevelsSegment($odfHandler, $outputLangs, $moreParam);
            }

            $moreParam['riskSigns'] = $loadRiskSignInfos['riskSigns'];
            static::setRiskSignsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['evaluators'] = $loadEvaluatorInfos['evaluators'];
            static::setEvaluatorsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['accidents'] = $loadAccidentInfos['accidents'];
            static::setAccidentsSegment($odfHandler, $outputLangs, $moreParam);

            $moreParam['tickets'] = $loadTicketInfos['tickets'];
            $this->setTicketsSegment($odfHandler, $outputLangs, $moreParam);
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
        if (!isset($moreParam['tmparray'])) {
            $moreParam['tmparray'] = [];
        }
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['hideTemplateName'] = 1;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
