<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/listingrisksaction/doc_listingrisksaction_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/mod_listingrisksaction_standard.php';
require_once __DIR__ . '/modules_listingrisksaction.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_listingrisksaction_odt extends ModeleODTListingRisksAction
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var array Fullpath file
	 */
	public $result;

	/**
	 * @var string ODT Template Name.
	 */
	public $name;

	/**
	 * @var string ODT Template Description.
	 */
	public $description;

	/**
	 * @var string ODT Template path.
	 */
	public $scandir;

	/**
	 * @var string Format file.
	 */
	public $type;

	/**
	 * @var int Width page.
	 */
	public $page_largeur;

	/**
	 * @var int Height page.
	 */
	public $page_hauteur;

	/**
	 * @var array Format page.
	 */
	public $format;

	/**
	 * @var int Left margin.
	 */
	public $marge_gauche;

	/**
	 * @var int Right margin.
	 */
	public $marge_droite;

	/**
	 * @var int Top margin.
	 */
	public $marge_haute;

	/**
	 * @var int Bottom margin.
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public $phpmin = array(5, 5);

	/**
	 * @var string Dolibarr version of the loaded document
	 */
	public $version = 'dolibarr';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db          = $db;
		$this->name        = $langs->trans('ListingRisksActionDigiriskTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir     = 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type         = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format       = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute  = 0;
		$this->marge_basse  = 0;

		// emetteur
		$this->emetteur                                                     = $mysoc;
		if ( ! $this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
	}

	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("errors", "companies"));

		$texte  = $this->description . ".<br>\n";
		$texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		$texte .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH">';
		$texte .= '<table class="nobordernopadding">';

		// List of directories area
		$texte      .= '<tr><td>';
		$texttitle   = $langs->trans("ListOfDirectories");
		$listofdir   = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			$tmpdir = preg_replace('/DOL_DOCUMENT_ROOT/', DOL_DOCUMENT_ROOT, $tmpdir);
			if ( ! $tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if ( ! is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else {
				$tmpfiles                          = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}

		// Scan directories
		$nbofiles = count($listoffiles);
		if ( ! empty($conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH)) {
			$texte .= $langs->trans("DigiriskNumberOfModelFilesFound") . ': <b>';
			$texte .= count($listoffiles);
			$texte .= '</b>';
		}

		if ($nbofiles) {
			$texte .= '<div id="div_' . get_class($this) . '" class="hidden">';
			foreach ($listoffiles as $file) {
				$texte .= $file['name'] . '<br>';
			}
			$texte .= '</div>';
		}

		$texte .= '</td>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 * @param 	ListingRisksAction	$object 			Object source to build document
	 * @param 	Translate 			$outputlangs 		Lang output object
	 * @param 	string 				$srctemplatepath 	Full path of source filename for generator using a template file
	 * @param	int					$hidedetails		Do not show line details
	 * @param	int					$hidedesc			Do not show desc
	 * @param	int					$hideref			Do not show ref
	 * @param 	DigiriskElement		$digiriskelement    Object for get DigiriskElement info
	 * @return	int                            			1 if OK, <=0 if KO
	 * @throws 	Exception
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $digiriskelement)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_listingrisksaction_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
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

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills","digiriskdolibarr@digiriskdolibarr"));

		$mod = new $conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON($this->db);
		$ref = $mod->getNextValue($object);

		$object->ref = $ref;
		$id          = $object->create($user, true, $digiriskelement);

		$object->fetch($id);

		if ( ! isset($digiriskelement->element_type) ) {
			$dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/listingrisksaction';
		} else {
			$dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/listingrisksaction/' . $digiriskelement->ref;
		}

		$objectref                                       = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/listingrisksaction\//', $srctemplatepath);
			preg_replace('/template_/', '', $filename[1]);

			$date = dol_print_date(dol_now(), 'dayxcard');
			if ( ! empty($digiriskelement)) {
				$filename = $date . '_' . $digiriskelement->ref . '_' . $objectref . '_' . $digiriskelement->label . '.odt';
			} else {
				$filename = $date . '_' . $objectref . '_' . $conf->global->MAIN_INFO_SOCIETE_NOM . '.odt';
			}
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			$object->last_main_doc = $filename;

			$sql  = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskdocuments";
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
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

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
			$array_object                 = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc                    = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo']  = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_object, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			// Call the ODTSubstitution hook
			$parameters            = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			$tmparray['reference'] = $object->ref;

			foreach ($tmparray as $key => $value) {
				try {
					if (preg_match('/logo$/', $key)) { // Image
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
					$risk = new Risk($this->db);
					$risks = $risk->fetchRisksOrderedByCotation($digiriskelement->id > 0 ? $digiriskelement->id : 0, true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);
					for ($i = 1; $i <= 4; $i++ ) {
						$listlines = $odfHandler->setSegment('risk' . $i);
						if (is_array($risks) && !empty($risks)) {
							foreach ($risks as $line) {
								$tmparray['actionPreventionUncompleted'] = "";
								$tmparray['actionPreventionCompleted']   = "";

								$evaluation     = new RiskAssessment($this->db);
								$lastEvaluation = $evaluation->fetchFromParent($line->id, 1);
								if ( ! empty($lastEvaluation) && $lastEvaluation > 0 && is_array($lastEvaluation)) {
									$lastEvaluation = array_shift($lastEvaluation);
									$scale          = $lastEvaluation->get_evaluation_scale();

									if ($scale == $i) {
										$element = new DigiriskElement($this->db);
										$element->fetch($line->fk_element);
										$tmparray['nomElement']            = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $element->entity . ' - ' : '') . $element->ref . ' - ' . $element->label;
										$tmparray['nomDanger']             = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $line->get_danger_category($line) . '.png';
										$tmparray['nomPicto']              = $line->get_danger_category_name($line);
										$tmparray['identifiantRisque']     = $line->ref . ' - ' . $lastEvaluation->ref;
										$tmparray['quotationRisque']       = $lastEvaluation->cotation ?: '0';
										$tmparray['descriptionRisque']     = $line->description;
										$tmparray['commentaireEvaluation'] = $lastEvaluation->comment ? dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && ( ! empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ' . $lastEvaluation->comment : '';

										$related_tasks = $line->get_related_tasks($line);
										$user          = new User($this->db);

										if ( ! empty($related_tasks) && is_array($related_tasks)) {
											foreach ($related_tasks as $related_task) {
												$AllInitiales = '';
												$related_task_contact_ids = $related_task->getListContactId();
												if ( ! empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
													foreach ($related_task_contact_ids as $related_task_contact_id) {
														$user->fetch($related_task_contact_id);
														$AllInitiales .= strtoupper(str_split($user->firstname, 1)[0]. str_split($user->lastname, 1)[0] . ',');
													}
												}

												$contactslistinternal = $related_task->liste_contact(-1, 'internal');

												if ( ! empty($contactslistinternal) && is_array($contactslistinternal)) {
													$responsible = '';
													foreach ($contactslistinternal as $contactlistinternal) {
														if ($contactlistinternal['code'] == 'TASKEXECUTIVE') {
															$responsible .= $contactlistinternal['firstname'] . ' ' . $contactlistinternal['lastname'] . ', ';
														}
													}
												}

												if ($related_task->progress == 100) {
													if ($conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_SHOW_TASK_DONE > 0) {
														$tmparray['actionPreventionCompleted'] .= $langs->trans('Ref') . ' : ' . ($related_task->ref ?: $langs->trans('NoData')) .  "\n";
														$tmparray['actionPreventionCompleted'] .= $langs->trans('Responsible') . ' : ' . ($responsible ?: $langs->trans('NoData')) . "\n";
														$tmparray['actionPreventionCompleted'] .= $langs->trans('DateStart') . ' : ' . dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . ' - ' . $langs->trans('Deadline') . ' : ' . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : $langs->trans('NoData')) . "\n";
														if (strcmp($related_task->budget_amount, '')) {
															$tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . "\n";
														} else {
															$tmparray['actionPreventionCompleted'] .= $langs->trans('Budget') . ' : ' . $langs->trans('NoData') . "\n";
														}
														$tmparray['actionPreventionCompleted'] .= $langs->trans('ContactsAction') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n";
														$tmparray['actionPreventionCompleted'] .= $langs->trans('Label') . ' : ' . ($related_task->label ?: $langs->trans('NoData')) . "\n";
														$tmparray['actionPreventionCompleted'] .= $langs->trans('Description') . ' : ' . ($related_task->description ?: $langs->trans('NoData')). "\n\n";
													} else {
														$tmparray['actionPreventionCompleted'] = $langs->transnoentities('ActionPreventionCompletedTaskDone');
													}
												} else {
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('Ref') . ' : ' . ($related_task->ref ?: $langs->trans('NoData')) . "\n";
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('Responsible') . ' : ' . ($responsible ?: $langs->trans('NoData')) . "\n";
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('DateStart') . ' : ' . dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && ( ! empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . ' - ' . $langs->trans('Deadline') . ' : ' . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && ( ! empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : $langs->trans('NoData')) . "\n";
													if (strcmp($related_task->budget_amount, '')) {
														$tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . price($related_task->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . ' - ';
													} else {
														$tmparray['actionPreventionUncompleted'] .= $langs->trans('Budget') . ' : ' . $langs->trans('NoData') . ' - ';
													}
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('DigiriskProgress') . ' : ' . ($related_task->progress ?: 0) . ' %' . "\n";
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('ContactsAction') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n";
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('Label') .  ' : ' . ($related_task->label ?: $langs->trans('NoData')) . "\n";
													$tmparray['actionPreventionUncompleted'] .= $langs->trans('Description') . ' : ' . ($related_task->description ?: $langs->trans('NoData')) . "\n\n";
												}
											}
										} else {
											$tmparray['actionPreventionUncompleted'] = "";
											$tmparray['actionPreventionCompleted']   = "";
										}

										unset($tmparray['object_fields']);

										complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
										// Call the ODTSubstitutionLine hook
										$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
										$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
										foreach ($tmparray as $key => $val) {
											try {
												if ($key == 'nomDanger') {
													if (file_exists($val)) {
														$listlines->setImage($key, $val);
													} else {
														$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
													}
												} elseif (empty($val) && $val != '0') {

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
							}
						} else {
							$tmparray['nomElement']                  = $langs->trans('NoData');
							$tmparray['nomDanger']                   = $langs->trans('NoData');
							$tmparray['nomPicto']                    = $langs->trans('NoData');
							$tmparray['identifiantRisque']           = $langs->trans('NoData');
							$tmparray['quotationRisque']             = $langs->trans('NoData');
							$tmparray['descriptionRisque']           = $langs->trans('NoDescriptionThere');
							$tmparray['commentaireEvaluation']       = $langs->trans('NoRiskThere');
							$tmparray['actionPreventionUncompleted'] = $langs->trans('NoTaskUnCompletedThere');
							$tmparray['actionPreventionCompleted']   = $langs->trans('NoTaskCompletedThere');
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

			// Write new file
			if ( ! empty($conf->global->MAIN_ODT_AS_PDF)) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
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
