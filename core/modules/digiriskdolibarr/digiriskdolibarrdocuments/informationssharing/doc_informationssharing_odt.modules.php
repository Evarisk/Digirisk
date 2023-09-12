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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/informationssharing/doc_informationssharing_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_informationssharing_odt extends SaturneDocumentModel
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
	public string $document_type = 'informationssharing';

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
		$object = $moreParam['object'];

		global $user, $langs, $conf, $hookmanager, $action, $mysoc, $moduleNameLowerCase;

		if (empty($srcTemplatePath)) {
			dol_syslog("doc_informationssharing_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
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
			'digiriskdolibarrdocuments/informationssharing' => $conf->global->DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON
		];

		list($mod) = saturne_require_objects_mod($numberingModules, $moduleNameLowerCase);
		$ref = $mod->getNextValue($objectDocument);

		$objectDocument->ref = $ref;
		$id          = $objectDocument->create($user, true);

		$objectDocument->fetch($id);

		$dir                                             = $conf->digiriskdolibarr->multidir_output[isset($objectDocument->entity) ? $objectDocument->entity : 1] . '/informationssharing';
		$objectref                                       = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';
		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/informationssharing\//', $srcTemplatePath);
			preg_replace('/template_/', '', $filename[1]);
			$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $objectref . '_' . $societyname . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			$objectDocument->last_main_doc = $filename;

			$sql  = "UPDATE " . MAIN_DB_PREFIX . "saturne_object_document";
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
			$array_object                 = $this->get_substitutionarray_object($objectDocument, $outputLangs);
			$array_soc                    = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
			$array_soc['mycompany_logo']  = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_object, $array_soc);
			complete_substitutions_array($tmparray, $outputLangs, $objectDocument);

			// Call the ODTSubstitution hook
			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $objectDocument, 'outputlangs' => $outputLangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $objectDocument may have been modified by some hooks

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
