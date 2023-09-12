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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/doc_riskassessmentdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/modules_riskassessmentdocument.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_riskassessmentdocument_odt extends ModeleODTRiskAssessmentDocument
{
	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public $phpmin = [7, 4];

	/**
	 * @var string Dolibarr version of the loaded document.
	 */
	public string $version = 'dolibarr';

	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'riskassessmentdocument';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->document_type);
	}

	/**
	 * Return description of a module.
	 *
	 * @param  Translate $langs Lang object to use for output.
	 * @return string           Description.
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
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
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc, $moduleNameLowerCase;

		ini_set('pcre.backtrack_limit', 10000000);

		if (empty($srcTemplatePath)) {
			dol_syslog("doc_riskassessmentdocument_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if ( ! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		if ( ! is_object($outputLangs)) $outputLangs = $langs;
		$outputLangs->charset_output                 = 'UTF-8';

		$outputLangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr"));

		$numberingModules = [
			'digiriskdolibarrdocuments/riskassessmentdocument' => $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON
		];

		list($mod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);
		$ref = $mod->getNextValue($objectDocument);


		$objectDocument->ref = $ref;
		$id          = $objectDocument->create($user, true);

		$objectDocument->fetch($id);

		$dir                                             = $conf->digiriskdolibarr->multidir_output[isset($objectDocument->entity) ? $objectDocument->entity : 1] . '/riskassessmentdocument';
		$objectref                                       = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/riskassessmentdocument\//', $srcTemplatePath);
			preg_replace('/template_/', '', $filename[1]);
			$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $objectref . '_' . $societyname . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);
			$filename = preg_replace('/[’‘‹›‚]/u', '', $filename);

			$objectDocument->last_main_doc = $filename;

			$sql  = "UPDATE " . MAIN_DB_PREFIX . "saturne_object_documents";
			$sql .= " SET last_main_doc =" . ( ! empty($filename) ? "'" . $this->db->escape($filename) . "'" : 'null');
			$sql .= " WHERE rowid = " . $objectDocument->id;

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$this->db->query($sql);
			$file = $dir . '/' . $filename;

			dol_mkdir($conf->digiriskdolibarr->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $objectDocument);
			// Call the ODTSubstitution hook
			$parameters = array('file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$substitutionarray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH . 'odf.php';
			try {
				$odfHandler = new odf(
					$srcTemplatePath,
					array(
						'PATH_TO_TMP'	  => $conf->digiriskdolibarr->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
					)
				);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}

			// Define substitution array
			$substitutionarray            = getCommonSubstitutionArray($outputLangs, 0, null, $objectDocument);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($objectDocument, $outputLangs);
			//$array_object = $this->get_substitutionarray_object($objectDocument, $outputLangs);
			$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_soc);
			complete_substitutions_array($tmparray, $outputLangs, $objectDocument);

			// Call the ODTSubstitution hook
			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks

			$filearray                 = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/riskassessmentdocument/', "files", 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
			if (is_array($filearray) && !empty($filearray)) {
				$sitePlans                 = array_shift($filearray);
				$thumb_name               = getThumbName($sitePlans['name']);
				$tmparray['dispoDesPlans'] = $sitePlans['path'] . '/thumbs/' . $thumb_name;
			} else {
				$tmparray['dispoDesPlans'] = '';
			}

			foreach ($tmparray as $key => $value) {
				try {
					if (preg_match('/logo$/', $key) || $key == 'dispoDesPlans') { // Image
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, ($key == 'dispoDesPlans') ? $langs->transnoentities('NoSitePlans') : $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
					} elseif (empty($value)) { // Text
						$odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
					} else {
						$odfHandler->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
					}
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}
			// Replace tags of lines
			try {
				$foundtagforlines = 1;
				if ($foundtagforlines) {
					$digiriskelementobject = new DigiriskElement($this->db);
					$risk                  = new Risk($this->db);
					$riskassessment        = new RiskAssessment($this->db);
					$ticket                = new Ticket($this->db);
					$category              = new Categorie($this->db);

					$digiriskelementlist   = $digiriskelementobject->fetchDigiriskElementFlat(0);
					$risks                 = $risk->fetchRisksOrderedByCotation(0, true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);
					$riskAssessmentList    = $riskassessment->fetchAll('', '', 0, 0, ['customsql' => 'status = 1']);
					$riskList              = $risk->fetchAll('', '', 0, 0, array(), 'AND', $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

					if (is_array($digiriskelementlist) && !empty($digiriskelementlist)) {
						$listlines = $odfHandler->setSegment('elementParHierarchie');

						//Fill digirisk element list table
						foreach ($digiriskelementlist as $line) {
							$depthHyphens = '';
							for ($k = 0; $k < $line['depth']; $k++) {
								$depthHyphens .= '- ';
							}
							$tmparray['nomElement'] = $depthHyphens . (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $line['object']->entity . ' - ' : '') . $line['object']->ref . ' ' . $line['object']->label;

							unset($tmparray['object_fields']);

							complete_substitutions_array($tmparray, $outputLangs, $objectDocument, $line['object'], "completesubstitutionarray_lines");
							// Call the ODTSubstitutionLine hook
							$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray, 'line' => $line['object']);
							$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
							foreach ($tmparray as $key => $val) {
								try {
									if (empty($val)) {
										$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
									} else {
										$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
									}
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);

						//Fill total cotation by digirisk element table
						$totalQuotation = 0;
						$scale_counter = [
							1 => 0,
							2 => 0,
							3 => 0,
							4 => 0
						];
						$line           = '';
						$listlines      = $odfHandler->setSegment('risqueFiche');

						if (is_array($risks) && !empty($risks)) {
							foreach($risks as $riskSingle) {
								$risksOfDigiriskElements[$riskSingle->appliedOn][] = $riskSingle;
							}
						}

						foreach ($digiriskelementlist as $digiriskelementsingle) {
							$digiriskElementId = $digiriskelementsingle['object']->id;
							$risksOfDigiriskElement = $risksOfDigiriskElements[$digiriskElementId];

							if ($risksOfDigiriskElement > 0 && ! empty($risksOfDigiriskElement)) {
								foreach ($risksOfDigiriskElement as $riskOfDigiriskElement) {
									$lastEvaluation                     = $riskOfDigiriskElement->lastEvaluation;
									$totalQuotation                    += $lastEvaluation->cotation;
									$riskAssessmentsOfDigiriskElement[$digiriskElementId][] = $lastEvaluation;
								}
							}

							$elementName  = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $digiriskelementsingle['object']->entity . ' - ' : '') . $digiriskelementsingle['object']->ref . ' ' . $digiriskelementsingle['object']->label;
							$scaleCounter = $riskassessment->getRiskAssessmentCategoriesNumber($riskAssessmentsOfDigiriskElement[$digiriskElementId], $risksOfDigiriskElement, $digiriskElementId);

							$cotationarray[$elementName] = array($totalQuotation, $digiriskelementsingle['object']->description, $scaleCounter);

							$totalQuotation = 0;

							unset($tmparray['object_fields']);
						}

						//use arsort to sort array according to value
						arsort($cotationarray);

						complete_substitutions_array($tmparray, $outputLangs, $objectDocument, $line, "completesubstitutionarray_lines");

						// Call the ODTSubstitutionLine hook
						$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray, 'line' => $line);
						$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
						foreach ($cotationarray as $key => $val) {
							try {
								$listlines->setVars('nomElement', html_entity_decode($key, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								$listlines->setVars('quotationTotale', $val[0], true, 'UTF-8');
								$listlines->setVars('description', html_entity_decode($val[1], ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								$listlines->setVars('NbRiskBlack', $val[2][4], true, 'UTF-8');
								$listlines->setVars('NbRiskRed', $val[2][3], true, 'UTF-8');
								$listlines->setVars('NbRiskOrange', $val[2][2], true, 'UTF-8');
								$listlines->setVars('NbRiskGrey', $val[2][1], true, 'UTF-8');
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					}

					//Fill risks data
					$objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, $tmparray, $file, $risks, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

					//Fill tickets data
					$filter = array('t.fk_project' => $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT);
					$ticket->fetchAll($user, '', '', '', 0, '', $filter);
					$listlines = $odfHandler->setSegment('tickets');
					if (is_array($ticket->lines) && !empty($ticket->lines)) {
						foreach ($ticket->lines as $line) {
							$tmparray['refticket']     = $line->ref;

							$categories = $category->containing($line->id, Categorie::TYPE_TICKET);
							if (!empty($categories)) {
								$allcategories = [];
								foreach ($categories as $cat) {
									$allcategories[] = $cat->label;
								}
								$tmparray['categories'] = implode(', ', $allcategories);
							} else {
								$tmparray['categories'] = '';
							}

							$tmparray['creation_date'] = dol_print_date($line->datec, 'dayhoursec', 'tzuser');
							$tmparray['subject']       = $line->subject;
							$tmparray['message']       = $line->message;
							$tmparray['progress']      = (($line->progress) ?: 0) . ' %';

							$tickettmp = new Ticket($this->db);
							$tickettmp->fetch($line->id);
							$tickettmp->fetch_optionals();
							$digiriskelementtmp = new DigiriskElement($this->db);
							$digiriskelementtmp->fetch($tickettmp->array_options['options_digiriskdolibarr_ticket_service']);
							$tmparray['digiriskelement_ref_label'] = $digiriskelementtmp->ref . ' - ' . $digiriskelementtmp->label;

							$tmparray['status'] = $tickettmp->getLibStatut();

							unset($tmparray['object_fields']);

							complete_substitutions_array($tmparray, $outputLangs, $objectDocument, $line, "completesubstitutionarray_lines");
							// Call the ODTSubstitutionLine hook
							$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray, 'line' => $line);
							$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks
							foreach ($tmparray as $key => $val) {
								try {
									if (file_exists($val)) {
										$listlines->setImage($key, $val);
									} elseif (empty($val)) {
										$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
									} else {
										$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
									}
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
					} else {
						$tmparray['refticket']                 = $langs->trans('NoData');
						$tmparray['categories']                = $langs->trans('NoData');
						$tmparray['creation_date']             = $langs->trans('NoData');
						$tmparray['subject']                   = $langs->trans('NoData');
						$tmparray['message']                   = $langs->trans('NoData');
						$tmparray['progress']                  = $langs->trans('NoData');
						$tmparray['digiriskelement_ref_label'] = $langs->trans('NoData');
						$tmparray['status']                    = $langs->trans('NoData');
						foreach ($tmparray as $key => $val) {
							try {
								if (empty($val)) {
									$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
								} else {
									$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								}
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage());
							}
						}
						$listlines->merge();
					}
					$odfHandler->mergeSegment($listlines);
				}
			} catch (OdfException $e) {
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}

			// Replace labels translated
			$tmparray = $outputLangs->get_translations_for_substitutions();
			foreach ($tmparray as $key => $value) {
				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks

			$fileInfos = pathinfo($filename);
			$pdfName   = $fileInfos['filename'] . '.pdf';

			// Write new file
			if ( ! empty($conf->global->MAIN_ODT_AS_PDF) && $conf->global->DIGIRISKDOLIBARR_AUTOMATIC_PDF_GENERATION > 0) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
					setEventMessages($langs->trans("FileGenerated") . ' - ' . $pdfName, null);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					setEventMessages($langs->transnoentities('FileCouldNotBeGeneratedInPDF') . '<br>' . $langs->transnoentities('CheckDocumentationToEnablePDFGeneration'), null, 'errors');
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			} else {
				try {
					$odfHandler->saveToDisk($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}

			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks

//			if ( ! empty($conf->global->MAIN_UMASK))
//				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath' => $file);

			return 1; // Success
		} else {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}
	}

}
