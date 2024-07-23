<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 *  \file			core/modules/digiriskdolibarr/modules_digiriskelementdocument.php
 *  \ingroup		digiriskdolibarr
 *  \brief			File that contains parent class for digiriskelementdocuments document models
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/accident.class.php';
require_once __DIR__ . '/../../../../../class/digiriskresources.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Parent class for documents models
 */
abstract class ModeleODTDigiriskElementDocument extends SaturneDocumentModel
{
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
        global $conf, $hookmanager;

        $object = $moreParam['object'];

        $objectDocument = $moreParam['objectDocument'];

        try {
            $foundtagforlines = 1;
            if ($foundtagforlines) {
                $risk      = new Risk($this->db);
                $evaluator = new Evaluator($this->db);
                $usertmp   = new User($this->db);
                $risksign  = new RiskSign($this->db);
                $accident  = new Accident($this->db);
                $ticket    = new Ticket($this->db);
                $category  = new Categorie($this->db);

                if ( ! empty($object) ) {
                    //Fill risks data
                    $risks = $risk->fetchRisksOrderedByCotation($object->element != 'digiriskstandard' ? $object->id : 0, $object->element != 'digiriskstandard' ? false : true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS, $moreParam);

                    $objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, [], '', $risks, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

                    //Fill evaluators data
                    $foundTagForLines = 1;
                    try {
                        $listLines = $odfHandler->setSegment('utilisateursPresents');
                    } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                        // We may arrive here if tags for lines not present into template
                        $foundTagForLines = 0;
                        $listLines        = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundTagForLines) {
                        $evaluators = $evaluator->fetchAll('', '', 0, 0, ['customsql' => ($object->element != 'digiriskstandard' ? 'fk_parent = ' . $object->id . ' AND ' : '') . 'status = ' . Evaluator::STATUS_VALIDATED . $moreParam['filter']]);
                        if (is_array($evaluators) && !empty($evaluators)) {
                            foreach ($evaluators as $line) {
                                $element = new DigiriskElement($this->db);
                                $element->fetch($line->fk_parent);
                                $usertmp->fetch($line->fk_user);

                                $tmpArray['nomElement']                 = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_EVALUATORS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
                                $tmpArray['idUtilisateur']              = $line->ref;
                                $tmpArray['dateAffectationUtilisateur'] = dol_print_date($line->assignment_date, '%d/%m/%Y');
                                $tmpArray['dureeEntretien']             = $line->duration . ' min';
                                $tmpArray['nomUtilisateur']             = $usertmp->lastname;
                                $tmpArray['prenomUtilisateur']          = $usertmp->firstname;
                                $tmpArray['travailUtilisateur']         = $line->job;

                                complete_substitutions_array($tmpArray, $outputLangs, $objectDocument, $line, "completesubstitutionarray_lines");
                                // Call the ODTSubstitutionLine hook
                                $parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray, 'line' => $line);
                                $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
                                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                            }
                        } else {
                            $tmpArray['nomElement']                 = '';
                            $tmpArray['idUtilisateur']              = '';
                            $tmpArray['dateAffectationUtilisateur'] = '';
                            $tmpArray['dureeEntretien']             = '';
                            $tmpArray['nomUtilisateur']             = '';
                            $tmpArray['prenomUtilisateur']          = '';
                            $tmpArray['travailUtilisateur']         = '';
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                        $odfHandler->mergeSegment($listLines);
                    }

                    //Fill risk signs data
                    $foundTagForLines = 1;
                    try {
                        $listLines = $odfHandler->setSegment('affectedRecommandation');
                    } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                        // We may arrive here if tags for lines not present into template
                        $foundTagForLines = 0;
                        $listLines        = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundTagForLines) {
                        $risksigns = $risksign->fetchRiskSign($object->element != 'digiriskstandard' ? $object->id : 0, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS, $moreParam);
                        if (is_array($risksigns) && !empty($risksigns)) {
                            foreach ($risksigns as $line) {
                                if ($line->id > 0) {
                                    $element = new DigiriskElement($this->db);
                                    $element->fetch($line->fk_element);
                                    $path = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/';

                                    $tmpArray['nomElement']                = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
                                    $tmpArray['recommandation_photo']        = $path . $risksign->getRiskSignCategory($line);
                                    $tmpArray['identifiantRecommandation'] = $line->ref;
                                    $tmpArray['recommandationName']        = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $line->getRiskSignCategory($line, 'name') : ' ');
                                    $tmpArray['recommandationComment']     = $line->description;

                                    complete_substitutions_array($tmpArray, $outputLangs, $objectDocument, $line, "completesubstitutionarray_lines");
                                    // Call the ODTSubstitutionLine hook
                                    $parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray, 'line' => $line);
                                    $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
                                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                                }
                            }
                        } else {
                            $tmpArray['nomElement']                = '';
                            $tmpArray['recommandation_photo']      = '';
                            $tmpArray['identifiantRecommandation'] = '';
                            $tmpArray['recommandationName']        = '';
                            $tmpArray['recommandationComment']     = '';
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                        $odfHandler->mergeSegment($listLines);
                    }

                    //Fill accidents data
                    $foundTagForLines = 1;
                    try {
                        $listLines = $odfHandler->setSegment('affectedAccident');
                    } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                        // We may arrive here if tags for lines not present into template
                        $foundTagForLines = 0;
                        $listLines        = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundTagForLines) {
                        $accidents = $accident->fetchAll('', '', 0, 0, ['customsql' => ($object->element != 'digiriskstandard' ? 'fk_element = ' . $object->id . ' AND ' : '') . 'status >= ' . Accident::STATUS_VALIDATED . $moreParam['filter']]);
                        if (is_array($accidents) && !empty($accidents)) {
                            foreach ($accidents as $line) {
                                $tmpArray['identifiantAccident'] = $line->ref;
                                $tmpArray['AccidentName']        = $line->label;

                                $accidentWorkStop    = new AccidentWorkStop($this->db);
                                $allaccidentworkstop = $accidentWorkStop->fetchFromParent($line->id);
                                if (!empty($allAccidentWorkStop) && is_array($allAccidentWorkStop)) {
                                    foreach ($allAccidentWorkStop as $accidentWorkStopsingle) {
                                        $nbAccidentWorkStop += $accidentWorkStopsingle->workstop_days;
                                    }
                                }

                                $tmpArray['AccidentWorkStopDays'] = $nbAccidentWorkStop;
                                $tmpArray['AccidentComment']     = $line->description;

                                $parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray, 'line' => $line);
                                $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
                                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);

                            }
                        } else {
                            $tmpArray['identifiantAccident']  = '';
                            $tmpArray['AccidentName']         = '';
                            $tmpArray['AccidentWorkStopDays'] = '';
                            $tmpArray['AccidentComment']      = '';
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                        $odfHandler->mergeSegment($listLines);
                    }

                    //Fill tickets data
                    $foundTagForLines = 1;
                    try {
                        $listLines = $odfHandler->setSegment('tickets');
                    } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                        // We may arrive here if tags for lines not present into template
                        $foundTagForLines = 0;
                        $listLines        = '';
                        dol_syslog($e->getMessage());
                    }

                    if ($foundTagForLines) {
                        if (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
                            $tickets = saturne_fetch_all_object_type('Ticket', '', '', 0, 0,  ['customsql' => 'eft.digiriskdolibarr_ticket_service > ' . ($object->element != 'digiriskstandard' ? $object->id : 0) . $moreParam['specificFilter']], 'AND', true);
                        }
                        if (is_array($tickets) && !empty($tickets)) {
                            foreach ($tickets as $line) {
                                $tmpArray['refticket'] = $line->ref;

                                $categories = $category->containing($line->id, Categorie::TYPE_TICKET);
                                if (!empty($categories)) {
                                    $allcategories = [];
                                    foreach ($categories as $cat) {
                                        $allcategories[] = $cat->label;
                                    }
                                    $tmpArray['categories'] = implode(', ', $allcategories);
                                } else {
                                    $tmpArray['categories'] = '';
                                }

                                $tmpArray['creation_date'] = dol_print_date($line->datec, 'dayhoursec', 'tzuser');
                                $tmpArray['subject']       = $line->subject;
                                $tmpArray['message']       = $line->message;
                                $tmpArray['progress']      = (($line->progress) ?: 0) . ' %';

                                $ticketmp = new Ticket($this->db);
                                $ticketmp->fetch($line->id);
                                $tmpArray['status'] = $ticketmp->getLibStatut();

                                complete_substitutions_array($tmpArray, $outputLangs, $objectDocument, $line, "completesubstitutionarray_lines");
                                // Call the ODTSubstitutionLine hook
                                $parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmpArray, 'line' => $line);
                                $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
                                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                            }
                        } else {
                            $tmpArray['refticket']     = '';
                            $tmpArray['categories']    = '';
                            $tmpArray['creation_date'] = '';
                            $tmpArray['subject']       = '';
                            $tmpArray['message']       = '';
                            $tmpArray['progress']      = '';
                            $tmpArray['status']        = '';
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                        $odfHandler->mergeSegment($listLines);
                    }
                }
            }
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
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf;

        $object = $moreParam['object'];

        if (!empty($object->photo)) {
            $path              = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref;
            $fileSmall         = saturne_get_thumb_name($object->photo);
            $image             = $path . '/thumbs/' . $fileSmall;
            $tmpArray['photo'] = $image;
        } else {
            $noPhoto           = '/public/theme/common/nophoto.png';
            $tmpArray['photo'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $resources = new DigiriskResources($this->db);
        $userTmp   = new User($this->db);

        // Get QRCode to public interface
        if (isModEnabled('multicompany')) {
            $qrCodePath = DOL_DATA_ROOT . '/digiriskdolibarr/multicompany/ticketqrcode/';
        } else {
            $qrCodePath = $conf->digiriskdolibarr->multidir_output[$conf->entity ?: 1] . '/ticketqrcode/';
        }
        $QRCodeList = dol_dir_list($qrCodePath);
        if (is_array($QRCodeList) && !empty($QRCodeList)) {
            $QRCode          = array_shift($QRCodeList);
            $QRCodeImagePath = $QRCode['fullname'];
        } else {
            $QRCodeImagePath = DOL_DOCUMENT_ROOT . '/public/theme/common/nophoto.png';
        }

        $allLinks             = $resources->fetchDigiriskResources();
        $responsibleResources = $allLinks['Responsible'];
        $userTmp->fetch($responsibleResources->id[0]);

        // @todo The keyword "signature" is needed because we want the image to be cropped to fit in the table
        $ticketCreationOption = dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_CREATION_OPTION', $conf->entity);
        switch ($ticketCreationOption) {
            case 'default_redirection_url':
                $tmpArray['helpUrl'] = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php';
                break;
            case 'external_redirection_url':
                $tmpArray['helpUrl'] = dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_CREATION_EXTERNAL_REDIRECTION_URL', $conf->entity);
                break;
            case 'shortcut_redirection_url':
                // @todo Add the shortcut url
                break;
        }

        $tmpArray['signatureQRCodeTicket'] = $QRCodeImagePath;
        $tmpArray['securityResponsible']   = (!empty($userTmp) ? dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname) : '');

        if (isset($moreParam['tmparray']) && is_array($moreParam['tmparray'])) {
            $moreParam['tmparray'] = array_merge($moreParam['tmparray'], $tmpArray);
        } else {
            $moreParam['tmparray'] = $tmpArray;
        }
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['hideTemplateName'] = 1;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
