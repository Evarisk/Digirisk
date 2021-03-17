<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2019  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/modules/digiriskdolibarr/doc/doc_listing_risks_photos_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */


dol_include_once('/custom/digiriskdolibarr/lib/files.lib.php');
dol_include_once('/core/lib/files.lib.php');
require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/modules/digiriskdolibarr/modules_listingrisksaction.php';
//require_once DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/core/modules/digiriskdolibarr/mod_listingrisksaction_standard.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_listing_risks_actions_odt extends ModelePDFListingRisksAction
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
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = $langs->trans('ListingRisksActionDigiriskTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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

		$form = new Form($this->db);
		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH)));
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
		if (!empty($conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH))
		{
			$texte .= $langs->trans("DigiriskNumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte .= count($listoffiles);
			//$texte.=$nbofiles?'</a>':'';
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
	 *	@param		Commande	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills"));

		if ($object->id > 0) {

			// If $object is id instead of object
			if (!is_object($object))
			{
				$id = $object;
				$object = new DigiriskElement($this->db);
				$result = $object->fetch($id);
				if ($result < 0)
				{
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}
		} else {
			$object = new DigiriskElement($this->db);

			$object->modelpdf = 'listing_risks_actions_odt';
		}

//		$listingrisksaction = new ListingRisksAction($this->db);
//
//		$mod = new $conf->global->DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON($this->db);
//		$ref = $mod->getNextValue($listingrisksaction);

		$dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/listingrisksaction';
		$objectref = dol_sanitizeFileName($object->ref);
		if (strlen($objectref)) {
			if (!preg_match('/specimen/i', $objectref)) $dir .= '/' . $objectref;
		} else {
			$dir .= '/' . 'mycompany';
		}

		$file = $dir."/".$objectref.".odt";

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
			//print "srctemplatepath=".$srctemplatepath;	// Src filename
			$newfile = basename($srctemplatepath);
			$newfiletmp = preg_replace('/\.od(t|s)/i', '', $newfile);
			$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
			$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);
			$newfiletmp = $objectref.'_'.$newfiletmp;

			//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.odt';
			// Get extension (ods or odt)
			$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
			if (!empty($conf->global->MAIN_DOC_USE_TIMING))
			{
				$format = $conf->global->MAIN_DOC_USE_TIMING;
				if ($format == '1') $format = '%Y%m%d%H%M%S';
				$filename = $newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;

			}
			else
			{
				$objectlabel = dol_sanitizeFileName($object->label);
				$objectlabel = preg_replace('/ /', '_', $objectlabel);

				$filename = dol_print_date(dol_now(),'%Y%m%d') . (strlen($objectref) ? '_' . $objectref : '') . '_listing_risques_actions_correctives_' . (strlen($objectlabel) ? $objectlabel : 'global') . '.' . $newfileformat;

//				$filename = $ref . '.odt';
			}
			$object->last_main_doc = $filename;


			if ($object->id > 0) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."digirisk_documents";
				$sql .= " SET last_main_doc =" .(!empty($filename) ? "'".$this->db->escape($filename)."'" : 'null');
				$sql .= " WHERE rowid = ".$object->id;

				dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
				$this->db->query($sql);
			}


			$file = $dir.'/'.$filename;

			//print "newdir=".$dir;
			//print "newfile=".$newfile;
			//print "file=".$file;
			//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

			dol_mkdir($conf->digiriskdolibarr->dir_temp);

			// Recipient name
			$contactobject = null;
			if (!empty($usecontact))
			{
				// On peut utiliser le nom de la societe du contact
				if (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $socobject = $object->contact;
				else {
					$socobject = $object->thirdparty;
					// if we have a CUSTOMER contact and we dont use it as recipient we store the contact object for later use
					$contactobject = $object->contact;
				}
			}
			else
			{
				$socobject = $object->thirdparty;
			}

			// Make substitution
			$substitutionarray = array(
				'__FROM_NAME__' => $this->emetteur->name,
				'__FROM_EMAIL__' => $this->emetteur->email,
				'__TOTAL_TTC__' => $object->total_ttc,
				'__TOTAL_HT__' => $object->total_ht,
				'__TOTAL_VAT__' => $object->total_vat
			);
			complete_substitutions_array($substitutionarray, $langs, $object);
			// Call the ODTSubstitution hook
			$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$substitutionarray);
			$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Line of free text
			$newfreetext = '';
			$paramfreetext = 'ORDER_FREE_TEXT';
			if (!empty($conf->global->$paramfreetext))
			{
				$newfreetext = make_substitutions($conf->global->$paramfreetext, $substitutionarray);
			}

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
			// After construction $odfHandler->contentXml contains content and
			// [!-- BEGIN row.lines --]*[!-- END row.lines --] has been replaced by
			// [!-- BEGIN lines --]*[!-- END lines --]
			//print html_entity_decode($odfHandler->__toString());
			//print exit;


			// Make substitutions into odt of freetext
			try {
				$odfHandler->setVars('free_text', $newfreetext, true, 'UTF-8');
			}
			catch (OdfException $e)
			{
				dol_syslog($e->getMessage(), LOG_INFO);
			}

			// Define substitution array
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			$array_objet = $this->get_substitutionarray_object($object, $outputlangs);
			$array_user = $this->get_substitutionarray_user($user, $outputlangs);
			$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_thirdparty = $this->get_substitutionarray_thirdparty($socobject, $outputlangs);
			$array_other = $this->get_substitutionarray_other($outputlangs);
			// retrieve contact information for use in object as contact_xxx tags
			$array_thirdparty_contact = array();
			if ($usecontact && is_object($contactobject)) $array_thirdparty_contact = $this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact');

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_user, $array_soc, $array_thirdparty, $array_objet, $array_other, $array_thirdparty_contact);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			// Call the ODTSubstitution hook
			$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
			$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			foreach ($tmparray as $key=>$value)
			{
				try {
					if (preg_match('/logo$/', $key)) // Image
					{
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
					}
					else    // Text
					{
						$odfHandler->setVars($key, $value, true, 'UTF-8');
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
				try {
					//$listlines = $odfHandler->setSegment('lines');
				}
				catch (OdfException $e)
				{
					// We may arrive here if tags for lines not present into template
					$foundtagforlines = 0;
					dol_syslog($e->getMessage(), LOG_INFO);
				}
				if ($foundtagforlines)
				{
					$linenumber = 0;

					$risk = new Risk($this->db);

					if ( ! empty( $object ) ) {
						$risks = $risk->fetchRisksOrderedByCotation($object->id, true);
						if ($risks !== -1) {
							for ($i = 1; $i <= 4; $i++ ) {
								$listlines = $odfHandler->setSegment('risk' . $i);

								foreach ($risks as $line) {
									$evaluation = new DigiriskEvaluation($this->db);
									$lastEvaluation = $evaluation->fetchFromParent($line->id, 1);
									if ( !empty ($lastEvaluation) ) {
										$lastEvaluation = array_shift($lastEvaluation);
										$scale = $lastEvaluation->get_evaluation_scale();
									}


									if ( $scale == $i ) {

										$element = new DigiriskElement($this->db);
										$element->fetch($line->fk_element);

										$tmparray['nomElement'] = $element->ref . ' - ' . $element->label;
										$tmparray['nomDanger'] 	= $line->get_danger_name($line);

										$riskRef 		= substr($line->ref, 1);
										$riskRef 		= ltrim($riskRef, '0');
										$cotationRef 	= substr($lastEvaluation->ref, 1);
										$cotationRef 	= ltrim($cotationRef, '0');

										$tmparray['identifiantRisque'] 	= 'R'. $riskRef . ' - E' . $cotationRef;
										$tmparray['quotationRisque'] 	= $lastEvaluation->cotation;
										$tmparray['commentaireRisque']	= dol_print_date( $lastEvaluation->date_creation, '%A %e %B %G %H:%M' ) . ': ' . $lastEvaluation->comment;

										$path 						= DOL_DATA_ROOT .'/digiriskdolibarr/risk/' . $line->ref ;
										$image 						= $path . '/' . $lastEvaluation->photo;
										$tmparray['photoAssociee'] = $image;

										unset($tmparray['object_fields']);

										complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
										// Call the ODTSubstitutionLine hook
										$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
										$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
										foreach ($tmparray as $key => $val) {
											try {
												if (file_exists($val)) {
													dol_imageResizeOrCrop($val, 0, 200, 200);
													$listlines->setImage($key, $val);
												} else {
													$listlines->setVars($key, $val, true, 'UTF-8');
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
					else {
						$risks = $risk->fetchRisksOrderedByCotation(0, true);
						if ($risks !== -1) {
							for ($i = 1; $i <= 4; $i++ ) {
								$listlines = $odfHandler->setSegment('risk' . $i);

								foreach ($risks as $line) {
									$evaluation = new DigiriskEvaluation($this->db);
									$lastEvaluation = $evaluation->fetchFromParent($line->id, 1);
									$lastEvaluation = array_shift($lastEvaluation);
									$scale = $lastEvaluation->get_evaluation_scale();

									if ( $scale == $i ) {

										$element = new DigiriskElement($this->db);
										$element->fetch($line->fk_element);

										$tmparray['nomElement'] = $element->ref . ' - ' . $element->label;
										$tmparray['nomDanger'] 	= $line->get_danger_name($line);

										$riskRef 		= substr($line->ref, 1);
										$riskRef 		= ltrim($riskRef, '0');
										$cotationRef 	= substr($lastEvaluation->ref, 1);
										$cotationRef 	= ltrim($cotationRef, '0');

										$tmparray['identifiantRisque'] 	= 'R'. $riskRef . ' - E' . $cotationRef;
										$tmparray['quotationRisque'] 	= $lastEvaluation->cotation;
										$tmparray['commentaireRisque']	= dol_print_date( $lastEvaluation->date_creation, '%A %e %B %G %H:%M' ) . ': ' . $lastEvaluation->comment;

										$path 						= DOL_DATA_ROOT .'/digiriskdolibarr/risk/' . $line->ref ;
										$image 						= $path . '/' . $lastEvaluation->photo;
										$tmparray['photoAssociee'] = $image;

										unset($tmparray['object_fields']);

										complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
										// Call the ODTSubstitutionLine hook
										$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
										$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
										foreach ($tmparray as $key => $val) {
											try {
												if (file_exists($val)) {
													dol_imageResizeOrCrop($val, 0, 200, 200);
													$listlines->setImage($key, $val);
												} else {
													$listlines->setVars($key, $val, true, 'UTF-8');
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
