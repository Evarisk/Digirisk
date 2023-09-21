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

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Parent class for documents models
 */
abstract class ModeleODTDigiriskElementDocument extends SaturneDocumentModel
{

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
					$risks = $risk->fetchRisksOrderedByCotation($object->id, false, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

					$objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, [], '', $risks, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

					//Fill evaluators data
					$evaluators = $evaluator->fetchFromParent($object->id);
					$listLines = $odfHandler->setSegment('utilisateursPresents');
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

							unset($tmpArray['object_fields']);

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

					//Fill risk signs data
					$risksigns = $risksign->fetchRiskSign($object->id, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS);
					$listLines = $odfHandler->setSegment('affectedRecommandation');
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

								unset($tmpArray['object_fields']);

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

					//Fill accidents data
					$accidents = $accident->fetchFromParent($object->id);
					$listLines = $odfHandler->setSegment('affectedAccident');
					if (is_array($accidents) && !empty($accidents)) {
						foreach ($accidents as $line) {
							$tmpArray['identifiantAccident'] = $line->ref;
							$tmpArray['AccidentName']        = $line->label;

							$accidentWorkStop = new AccidentWorkStop($this->db);
							$allAccidentWorkStop = $accidentWorkStop->fetchFromParent($line->id);
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

					//Fill tickets data
					if (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
						$filter = array('ef.digiriskdolibarr_ticket_service' => $object->id);
						$ticket->fetchAll($user, '', '', '', 0, '', $filter);
					}
					$listLines = $odfHandler->setSegment('tickets');
					if (is_array($ticket->lines) && !empty($ticket->lines)) {
						foreach ($ticket->lines as $line) {
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

							unset($tmpArray['object_fields']);

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
						foreach ($tmpArray as $key => $val) {
							try {
								if (empty($val)) {
									$listLines->setVars($key, $outputLangs->trans('NoData'), true, 'UTF-8');
								} else {
									$listLines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								}
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage());
							}
						}
						$listLines->merge();
					}
					$odfHandler->mergeSegment($listLines);
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
	 * Function to build a document on disk.
	 *
	 * @param  SaturneDocuments $objectDocument  Object source to build document.
	 * @param  Translate        $outputLangs     Lang object to use for output.
	 * @param  string           $srcTemplatePath Full path of source filename for generator using a template file.
	 * @param  int              $hideDetails     Do not show line details.
	 * @param  int              $hideDesc        Do not show desc.
	 * @param  int              $hideRef         Do not show ref.
	 * @param  array            $moreParam       More param (Object/user/etc).
	 * @return int                               1 if OK, <=0 if KO.
	 * @throws Exception
	 */

	public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
	{
		$tmpArray = [];

		$moreParam['tmparray'] = $tmpArray;
		$moreParam['objectDocument'] = $objectDocument;
		$moreParam['subDir'] = 'digiriskdolibarrdocuments/';
		$moreParam['hideTemplateName'] = 1;

		return parent::write_file(
			$objectDocument,
			$outputLangs,
			$srcTemplatePath,
			$hideDetails,
			$hideDesc,
			$hideRef,
			$moreParam
		);
	}
}
