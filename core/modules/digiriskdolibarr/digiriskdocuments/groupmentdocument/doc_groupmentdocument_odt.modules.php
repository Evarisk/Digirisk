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
 *	\file       htdocs/core/modules/digiriskdolibarr/digiriskdocuments/groupmentdocument/doc_groupmentdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/mod_groupmentdocument_standard.php';
require_once __DIR__ . '/modules_groupmentdocument.php';
/**
 *	Class to build documents using ODF templates generator
 */
class doc_groupmentdocument_odt extends ModeleODTGroupmentDocument
{
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

		$this->db = $db;
		$this->name = $langs->trans('GroupmentDocumentDigiriskTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		// Recupere emetteur
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
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

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key=>$tmpdir)
		{
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if (!is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else
			{
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH))
		{
			$texte .= $langs->trans("DigiriskNumberOfModelFilesFound").': <b>';
			$texte .= count($listoffiles);
			$texte .= '</b>';
		}

		if ($nbofiles)
		{
			$texte .= '<div id="div_'.get_class($this).'" class="hidden">';
			foreach ($listoffiles as $file)
			{
				$texte .= $file['name'].'<br>';
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
	 *	@param		GroupmentDocument	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $digiriskelement)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_groupmentdocument_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$outputlangs->charset_output = 'UTF-8';

		$outputlangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr"));

		$mod = new $conf->global->DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON($this->db);
		$ref = $mod->getNextValue($object);

		$object->ref = $ref;

		$id = $object->create($user, true, $digiriskelement);

		$object->fetch($id);

		$dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/groupmentdocument/'. $digiriskelement->ref;
		$objectref = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if (!file_exists($dir))
		{
			if (dol_mkdir($dir) < 0)
			{
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir))
		{
			$filename = preg_split('/groupmentdocument\//' , $srctemplatepath);
			$filename = preg_replace('/template_/','', $filename[1]);

			$date = dol_print_date(dol_now(),'dayxcard');
			$filename = $objectref.'_'.$digiriskelement->label.'_'.$date.'.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			$object->last_main_doc = $filename;

			$sql = "UPDATE ".MAIN_DB_PREFIX."digiriskdolibarr_digiriskdocuments";
			$sql .= " SET last_main_doc =" .(!empty($filename) ? "'".$this->db->escape($filename)."'" : 'null');
			$sql .= " WHERE rowid = ".$object->id;

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$this->db->query($sql);
			$file = $dir.'/'.$filename;

			dol_mkdir($conf->digiriskdolibarr->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $object);
			// Call the ODTSubstitution hook
			$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$substitutionarray);
			$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH.'odf.php';
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
			}
			catch (Exception $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}

			//Define substitution array
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			//$array_object = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo'] = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $digiriskelement->element_type . '/' . $digiriskelement->ref . '/thumbs/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'desc', 1);
			if (count($filearray)) {
				$image = array_shift($filearray);
				$tmparray['photoDefault'] = $image['fullname'];
			}else {
				$nophoto = '/public/theme/common/nophoto.png';
				$tmparray['photoDefault'] = DOL_DOCUMENT_ROOT.$nophoto;
			}

			$tmparray['nom']         = $digiriskelement->label;
			$tmparray['reference']   = $digiriskelement->ref;
			$tmparray['description'] = $digiriskelement->description;

			foreach ($tmparray as $key=>$value)
			{
				try {
					if ($key == 'photoDefault' || preg_match('/logo$/', $key)) // Image
					{
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
					}
					else    // Text
					{
						if (empty($value)) {
							$odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
						} else {
							$odfHandler->setVars($key, html_entity_decode($value,ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
						}
					}
				}
				catch (OdfException $e)
				{
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}
			// Replace tags of lines
			try
			{
				$foundtagforlines = 1;
				if ($foundtagforlines)
				{
					$risk = new Risk($this->db);
					if ( ! empty( $digiriskelement ) ) {
						$risks = $risk->fetchRisksOrderedByCotation($digiriskelement->id, false, $conf->global->DIGIRISKDOLIBARR_SHOW_PARENT_RISKS);
						for ($i = 1; $i <= 4; $i++ ) {
							$listlines = $odfHandler->setSegment('risq' . $i);
							if ($risks > 0 && !empty($risks)) {
								foreach ($risks as $line) {
									$tmparray['actionPreventionUncompleted'] = "";
									$tmparray['actionPreventionCompleted'] = "";

									$evaluation = new RiskAssessment($this->db);
									$lastEvaluation = $evaluation->fetchFromParent($line->id, 1);

									if ($lastEvaluation > 0 && !empty($lastEvaluation)) {
										$lastEvaluation = array_shift($lastEvaluation);
										$scale = $lastEvaluation->get_evaluation_scale();

										if ($scale == $i) {
											$element = new DigiriskElement($this->db);
											$element->fetch($line->fk_element);
											$tmparray['nomElement'] = $element->ref . ' - ' . $element->label;
											$tmparray['nomDanger'] = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $line->get_danger_category($line) . '.png';
											$tmparray['identifiantRisque'] = $line->ref . ' - ' . $lastEvaluation->ref;
											$tmparray['quotationRisque'] = $lastEvaluation->cotation ? $lastEvaluation->cotation : '0';
											$tmparray['descriptionRisque'] = $line->description;
											$tmparray['commentaireEvaluation'] = dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE && (!empty($lastEvaluation->date_riskassessment))) ? $lastEvaluation->date_riskassessment : $lastEvaluation->date_creation), 'dayreduceformat') . ': ' . $lastEvaluation->comment;

											$related_tasks = $line->get_related_tasks($line);
											$user = new User($this->db);

											if (!empty($related_tasks) && is_array($related_tasks)) {
												foreach ($related_tasks as $related_task) {
													$related_task_contact_ids = $related_task->getListContactId();
													if (!empty($related_task_contact_ids) && is_array($related_task_contact_ids)) {
														foreach ($related_task_contact_ids as $related_task_contact_id) {
															$user->fetch($related_task_contact_id);
															$contact_array[$related_task_contact_id] = $user;
														}
													}
													$AllInitiales = '';
													if (!empty($contact_array) && is_array($contact_array)) {
														foreach ($contact_array as $contact_array_single) {
															$initiales = '';
															if (dol_strlen($contact_array_single->firstname)) {
																$initiales .= str_split($contact_array_single->firstname, 1)[0];
															}
															if (dol_strlen($contact_array_single->lastname)) {
																$initiales .= str_split($contact_array_single->lastname, 1)[0];
															}
															$AllInitiales .= strtoupper($initiales) . ',';
														}
													}
													if ($related_task->progress == 100) {
														$tmparray['actionPreventionCompleted'] .= dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : '') . "\n" . ' ' . $langs->trans('Contacts') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n" . $related_task->label . "\n\n";
													} else {
														$tmparray['actionPreventionUncompleted'] .= dol_print_date((($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_START_DATE && (!empty($related_task->date_start))) ? $related_task->date_start : $related_task->date_c), 'dayreduceformat') . (($conf->global->DIGIRISKDOLIBARR_SHOW_TASK_END_DATE && (!empty($related_task->date_end))) ? ' - ' . dol_print_date($related_task->date_end, 'dayreduceformat') : '') . ' - ' . $langs->trans('DigiriskProgress') . ' : ' . ($related_task->progress ?: 0) . '%' . ' ' . $langs->trans('Contacts') . ' : ' . ($AllInitiales ?: $langs->trans('NoData')) . "\n" . $related_task->label . "\n\n";
													}
												}
											} else {
												$tmparray['actionPreventionUncompleted'] = "";
												$tmparray['actionPreventionCompleted'] = "";
											}

											unset($tmparray['object_fields']);

											complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
											// Call the ODTSubstitutionLine hook
											$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
											$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

											foreach ($tmparray as $key => $val) {
												try {
													if ($val == $tmparray['nomDanger']) {
														$listlines->setImage($key, $val);
													} else {
														if (empty($val) && $val != '0') {
															$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
														} else {
															$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
														}
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
									} catch (OdfException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									} catch (SegmentException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									}
								}
								$listlines->merge();
							}
							$odfHandler->mergeSegment($listlines);
						}
					}

					$evaluator = new Evaluator($this->db);
					$user = new User($this->db);

					if ( ! empty( $digiriskelement ) ) {
						$evaluators = $evaluator->fetchFromParent($digiriskelement->id);

						if ($evaluators !== -1) {
							$listlines = $odfHandler->setSegment('utilisateursPresents');
							foreach ($evaluators as $line) {

								$user->fetch($line->fk_user);

								$tmparray['idUtilisateur']              = $line->ref;
								$tmparray['dateAffectationUtilisateur'] = dol_print_date($line->assignment_date, '%d/%m/%Y');
								$tmparray['dureeEntretien']             = $line->duration;
								$tmparray['nomUtilisateur']             = $user->lastname;
								$tmparray['prenomUtilisateur']          = $user->firstname;

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

								foreach ($tmparray as $key => $val) {
									try {
										if (file_exists($val)) {
											$listlines->setImage($key, $val);
										} else {
											if (empty($val)) {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											} else {
												$listlines->setVars($key, html_entity_decode($val,ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
											}
										}
									} catch (OdfException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									} catch (SegmentException $e) {
										dol_syslog($e->getMessage(), LOG_INFO);
									}
								}
								$listlines->merge();
							}
							$odfHandler->mergeSegment($listlines);
						}
					}

					$risksign = new RiskSign($this->db);

					if ( ! empty( $digiriskelement ) ) {
						$risksigns = $risksign->fetchFromParent($digiriskelement->id);
						if ($risksigns !== -1) {
							$listlines = $odfHandler->setSegment('affectedRecommandation');
							foreach ($risksigns as $line) {
								$path             = DOL_DOCUMENT_ROOT .'/custom/digiriskdolibarr/img/';

								$tmparray['recommandationIcon']        = $path . '/' . $risksign->get_risksign_category($line);
								$tmparray['identifiantRecommandation'] = $line->ref;
								$tmparray['recommandationName']        = $line->get_risksign_category($line, 'name');
								$tmparray['recommandationComment']     = $line->description;

								unset($tmparray['object_fields']);

								complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
								// Call the ODTSubstitutionLine hook
								$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
								$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
								foreach ($tmparray as $key => $val) {
									try {
										if (file_exists($val)) {
											$listlines->setImage($key, $val);
										} else {
											if (empty($val)) {
												$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
											} else {
												$listlines->setVars($key, html_entity_decode($val,ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
											}
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
						$odfHandler->mergeSegment($listlines);
					}
				}
			}
			catch (OdfException $e)
			{
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}
			// Replace labels translated
			$tmparray = $outputlangs->get_translations_for_substitutions();

			foreach ($tmparray as $key=>$value)
			{

				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				}
				catch (OdfException $e)
				{
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Write new file
			if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}
			else {
				try {
					$odfHandler->saveToDisk($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}

			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath'=>$file);

			return 1; // Success
		}
		else
		{
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}

		return -1;
	}
}
