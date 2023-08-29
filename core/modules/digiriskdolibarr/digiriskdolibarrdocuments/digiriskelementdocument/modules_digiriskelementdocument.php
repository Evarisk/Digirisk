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
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';

/**
 *	Parent class for documents models
 */
abstract class ModeleODTDigiriskElementDocument extends CommonDocGenerator
{

	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 * @param 	DigiriskElementDocument		$object 			Object source to build document
	 * @param 	Translate 				$outputlangs 		Lang output object
	 * @param 	string 					$srctemplatepath 	Full path of source filename for generator using a template file
	 * @param	int						$hidedetails		Do not show line details
	 * @param	int						$hidedesc			Do not show desc
	 * @param	int						$hideref			Do not show ref
	 * @param 	DigiriskElement			$digiriskelement    Object for get DigiriskElement info
	 * @return	int                            				1 if OK, <=0 if KO
	 * @throws 	Exception
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $digiriskelement)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		$digiriskelement = $digiriskelement['object'];
		$type = $digiriskelement->element_type;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_". $type ."document_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if ( ! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		if ( ! is_object($outputlangs)) $outputlangs = $langs;
		$outputlangs->charset_output                 = 'UTF-8';

		$outputlangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr"));

		$refModName = 'DIGIRISKDOLIBARR_' . strtoupper($type) . 'DOCUMENT_ADDON';

		$mod = new $conf->global->$refModName($this->db);
		$ref = $mod->getNextValue($object);

		$object->ref = $ref;
		$id          = $object->create($user, true, $digiriskelement);

		$object->fetch($id);

		$dir                                             = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/'. $type .'document/' . $digiriskelement->ref;
		$objectref                                       = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/'. $type .'document\//', $srctemplatepath);
			preg_replace('/template_/', '', $filename[1]);
			$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $digiriskelement->ref . '_' . $objectref . '_' . $digiriskelement->label . '_' . $societyname . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);
			$filename = preg_replace('/[’‘‹›‚]/u', '', $filename);

			$object->last_main_doc = $filename;

			$sql  = "UPDATE " . MAIN_DB_PREFIX . "saturne_object_documents";
			$sql .= " SET last_main_doc =" . ( ! empty($filename) ? "'" . $this->db->escape($filename) . "'" : 'null');
			$sql .= " WHERE rowid = " . $object->id;

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$this->db->query($sql);
			$file = $dir . '/' . $filename;

			dol_mkdir($conf->digiriskdolibarr->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $object);
			// Call the ODTSubstitution hook
			$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $digiriskelement may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH . 'odf.php';
			try {
				$odfHandler = new odf(
					$srctemplatepath,
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
			$substitutionarray            = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			//$array_object = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc                   = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo'] = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			if (!empty($conf->multicompany->enabled)) {
				$tmparray['entity'] = 'S'. $conf->entity . ' - ';
			} else {
				$tmparray['entity'] = ' ';
			}
			$tmparray['nom']         = $digiriskelement->label;
			$tmparray['reference']   = $digiriskelement->ref;
			$tmparray['description'] = $digiriskelement->description;

			$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $digiriskelement->element_type . '/' . $digiriskelement->ref . '/thumbs/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
			if (count($filearray)) {
				if (!empty($digiriskelement->photo)) {
					$thumb_name               = getThumbName($digiriskelement->photo);
					$image                    = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $digiriskelement->element_type . '/' . $digiriskelement->ref . '/thumbs/' . $thumb_name;
					$tmparray['photoDefault'] = $image;
				}
			} else {
				$nophoto                  = '/public/theme/common/nophoto.png';
				$tmparray['photoDefault'] = DOL_DOCUMENT_ROOT . $nophoto;
			}

			foreach ($tmparray as $key => $value) {
				try {
					if ($key == 'photoDefault' || preg_match('/logo$/', $key)) { // Image
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
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
					$risk      = new Risk($this->db);
					$evaluator = new Evaluator($this->db);
					$usertmp   = new User($this->db);
					$risksign  = new RiskSign($this->db);
					$accident  = new Accident($this->db);
					$ticket    = new Ticket($this->db);
					$category  = new Categorie($this->db);

					if ( ! empty($digiriskelement) ) {
						//Fill risks data
						$risks = $risk->fetchRisksOrderedByCotation($digiriskelement->id, false, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

						$object->fillRiskData($odfHandler, $object, $outputlangs, $tmparray, $file, $risks, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

						//Fill evaluators data
						$evaluators = $evaluator->fetchFromParent($digiriskelement->id);
						$listlines = $odfHandler->setSegment('utilisateursPresents');
						if (is_array($evaluators) && !empty($evaluators)) {
							foreach ($evaluators as $line) {
								$element = new DigiriskElement($this->db);
								$element->fetch($line->fk_parent);
								$usertmp->fetch($line->fk_user);

								$tmparray['nomElement']                 = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_EVALUATORS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
								$tmparray['idUtilisateur']              = $line->ref;
								$tmparray['dateAffectationUtilisateur'] = dol_print_date($line->assignment_date, '%d/%m/%Y');
								$tmparray['dureeEntretien']             = $line->duration . ' min';
								$tmparray['nomUtilisateur']             = $usertmp->lastname;
								$tmparray['prenomUtilisateur']          = $usertmp->firstname;
								$tmparray['travailUtilisateur']         = $line->job;

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
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
							$tmparray['nomElement']                 = '';
							$tmparray['idUtilisateur']              = '';
							$tmparray['dateAffectationUtilisateur'] = '';
							$tmparray['dureeEntretien']             = '';
							$tmparray['nomUtilisateur']             = '';
							$tmparray['prenomUtilisateur']          = '';
							$tmparray['travailUtilisateur']         = '';
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

						//Fill risk signs data
						$risksigns = $risksign->fetchRiskSign($digiriskelement->id, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS);
						$listlines = $odfHandler->setSegment('affectedRecommandation');
						if (is_array($risksigns) && !empty($risksigns)) {
							foreach ($risksigns as $line) {
								if ($line->id > 0) {
									$element = new DigiriskElement($this->db);
									$element->fetch($line->fk_element);
									$path = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/';

									$tmparray['nomElement']                = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
									$tmparray['recommandationIcon']        = $path . '/' . $risksign->get_risksign_category($line);
									$tmparray['identifiantRecommandation'] = $line->ref;
									$tmparray['recommandationName']        = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $line->get_risksign_category($line, 'name') : ' ');
									$tmparray['recommandationComment']     = $line->description;

									unset($tmparray['object_fields']);

									complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
									// Call the ODTSubstitutionLine hook
									$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
									$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
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
							}
						} else {
							$tmparray['nomElement']                = '';
							$tmparray['recommandationIcon']        = '';
							$tmparray['identifiantRecommandation'] = '';
							$tmparray['recommandationName']        = '';
							$tmparray['recommandationComment']     = '';
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

						//Fill accidents data
						$accidents = $accident->fetchFromParent($digiriskelement->id);
						$listlines = $odfHandler->setSegment('affectedAccident');
						if (is_array($accidents) && !empty($accidents)) {
							foreach ($accidents as $line) {
								$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $line->element . '/' . $line->ref . '/thumbs/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
								if (count($filearray)) {
									$image                    = array_shift($filearray);
									$tmparray['AccidentIcon'] = $image['fullname'];
								} else {
									$nophoto                  = '/public/theme/common/nophoto.png';
									$tmparray['AccidentIcon'] = DOL_DOCUMENT_ROOT . $nophoto;
								}

								$tmparray['identifiantAccident'] = $line->ref;
								$tmparray['AccidentName']        = $line->label;

								$accidentWorkStop = new AccidentWorkStop($this->db);
								$allAccidentWorkStop = $accidentWorkStop->fetchFromParent($line->id);
								if (!empty($allAccidentWorkStop) && is_array($allAccidentWorkStop)) {
									foreach ($allAccidentWorkStop as $accidentWorkStopsingle) {
										$nbAccidentWorkStop += $accidentWorkStopsingle->workstop_days;
									}
								}

								$tmparray['AccidentWorkStopDays'] = $nbAccidentWorkStop;
								$tmparray['AccidentComment']     = $line->description;

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
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
							$tmparray['AccidentIcon']         = '';
							$tmparray['identifiantAccident']  = '';
							$tmparray['AccidentName']         = '';
							$tmparray['AccidentWorkStopDays'] = '';
							$tmparray['AccidentComment']      = '';
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

						//Fill tickets data
						if (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
							$filter = array('ef.digiriskdolibarr_ticket_service' => $digiriskelement->id);
							$ticket->fetchAll($user, '', '', '', 0, '', $filter);
						}
						$listlines = $odfHandler->setSegment('tickets');
						if (is_array($ticket->lines) && !empty($ticket->lines)) {
							foreach ($ticket->lines as $line) {
								$tmparray['refticket'] = $line->ref;

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

								$ticketmp = new Ticket($this->db);
								$ticketmp->fetch($line->id);
								$tmparray['status'] = $ticketmp->getLibStatut();

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
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
							$tmparray['refticket']     = '';
							$tmparray['categories']    = '';
							$tmparray['creation_date'] = '';
							$tmparray['subject']       = '';
							$tmparray['message']       = '';
							$tmparray['progress']      = '';
							$tmparray['status']        = '';
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
				}
			} catch (OdfException $e) {
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}

			// Replace labels translated
			$tmparray = $outputlangs->get_translations_for_substitutions();
			foreach ($tmparray as $key => $value) {
				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

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

			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

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
