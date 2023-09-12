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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/doc_ticketdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/mod_ticketdocument_standard.php';
require_once __DIR__ . '/modules_ticketdocument.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_ticketdocument_odt extends ModeleODTTicketDocument
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
	public string $document_type = 'ticketdocument';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
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
		$ticket = $moreParam['object'];
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		if (empty($srcTemplatePath)) {
			dol_syslog("doc_ticketdocument_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
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

		$outputLangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr", "other"));

		$numberingModules = [
			'digiriskdolibarrdocuments/ticketdocument' => $conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON
		];

		list($mod) = saturne_require_objects_mod($numberingModules, 'digiriskdolibarr');
		$ref = $mod->getNextValue($objectDocument);

		$objectDocument->ref = $ref;
		$id          = $objectDocument->create($user, true, $ticket);

		$objectDocument->fetch($id);

		$dir       = $conf->digiriskdolibarr->multidir_output[isset($objectDocument->entity) ? $objectDocument->entity : 1] . '/ticketdocument/' . $ticket->ref;
		$objectref = dol_sanitizeFileName($ticket->ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/ticketdocument\//', $srcTemplatePath);
			preg_replace('/template_/', '', $filename[1]);
			$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $ref . '_' . $objectref . '_' . $societyname . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

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
			complete_substitutions_array($substitutionarray, $langs, $ticket);
			// Call the ODTSubstitution hook
			$parameters = array('file' => $file, 'object' => $ticket, 'outputlangs' => $outputLangs, 'substitutionarray' => &$substitutionarray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $ticket may have been modified by some hooks

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
				dol_syslog($e->getMessage());
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

			$ticket->fetch_optionals();
			$digiriskelement = new DigiriskElement($this->db);

			$tmparray['ref']              = $ticket->ref;
			$tmparray['lastname']         = $ticket->array_options['options_digiriskdolibarr_ticket_lastname'];
			$tmparray['firstname']        = $ticket->array_options['options_digiriskdolibarr_ticket_firstname'];
			$tmparray['phone_number']     = $ticket->array_options['options_digiriskdolibarr_ticket_phone'];
			if ($ticket->array_options['options_digiriskdolibarr_ticket_service'] > 0) {
				$digiriskelement->fetch($ticket->array_options['options_digiriskdolibarr_ticket_service']);
				$tmparray['service'] = $digiriskelement->ref . ' - ' . $digiriskelement->label;
			} else {
				$tmparray['service'] = '';
			}
			$tmparray['location']         = $ticket->array_options['options_digiriskdolibarr_ticket_location'];
			$tmparray['declaration_date'] = dol_print_date($ticket->array_options['options_digiriskdolibarr_ticket_date'], 'dayhoursec', 'tzuser');
			$tmparray['creation_date']    = dol_print_date($ticket->date_creation, 'dayhoursec', 'tzuser');
			$tmparray['close_date']       = dol_print_date($ticket->date_close, 'dayhoursec', 'tzuser');
			$tmparray['progress']         = !empty($ticket->progress) ? $ticket->progress . ' %' : '0 %';

			$category = new Categorie($this->db);
			$categories = $category->containing($ticket->id, Categorie::TYPE_TICKET);
			if (!empty($categories)) {
				foreach ($categories as $cat) {
					$allcategories[] = $cat->label;
				}
				$tmparray['categories'] = implode(', ', $allcategories);
			} else {
				$tmparray['categories'] = '';
			}

			$tmparray['status'] = $ticket->getLibStatut();

			$user = new User($this->db);
			$user->fetch($ticket->fk_user_assign);
			$tmparray['assigned_to'] = $user->firstname . ' ' . $user->lastname;

			$photo_path = $conf->ticket->multidir_output[$conf->entity] . '/' . $ticket->ref;
			$filearray = dol_dir_list($photo_path, "files", 0, '', '(\.odt|_preview.*\.png|\.pdf)$', 'date', 'desc', 1);
			if ($photo_path) {
				$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $photo_path);
				$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
			}
			completeFileArrayWithDatabaseInfo($filearray, $relativedir);

			require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

			$ecm = new EcmFiles($this->db);

			if (count($filearray)) {
				$filearray = dol_sort_array($filearray, 'position');
				$thumb_name = getThumbName($filearray[0]['name']);
				$tmparray['photo'] = $photo_path . '/thumbs/' . $thumb_name;
			} else {
				$nophoto                  = '/public/theme/common/nophoto.png';
				$tmparray['photo'] = DOL_DOCUMENT_ROOT . $nophoto;
			}

			$tmparray['subject'] = $ticket->subject;
			$tmparray['message']  = dol_htmlentitiesbr_decode(strip_tags($ticket->message, '<br>'));
			$tmparray['generation_date'] = dol_print_date(dol_now(), 'dayhoursec', 'tzuser');

			$contactlistexternal = $ticket->liste_contact(-1, 'external');
			$contactlistinternal = $ticket->liste_contact(-1, 'internal');

			$contactlist = array();

			if (!empty($contactlistexternal) && is_array($contactlistexternal)) {
				$contactlist = array_merge($contactlist,$contactlistexternal);
			}

			if (!empty($contactlistinternal) && is_array($contactlistinternal)) {
				$contactlist = array_merge($contactlist,$contactlistinternal);
			}

			if (!empty($contactlist) && is_array($contactlist)) {
				foreach ($contactlist as $contact) {
					$tmparray['contacts'] .= $contact['firstname'] . ' ' . $contact['lastname'] . ', ';
				}
			} else {
				$tmparray['contacts'] = '';
			}

			foreach ($tmparray as $key => $value) {
				try {
					if (preg_match('/photo$/', $key) || preg_match('/logo$/', $key)) {
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
					} elseif (empty($value)) { // Text
						$odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
					} else {
						$odfHandler->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
					}
				} catch (OdfException $e) {
					dol_syslog($e->getMessage());
				}
			}

			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
			$actioncomm = new ActionComm($this->db);
			$event_list = $actioncomm->getActions('',$ticket->id,$ticket->element,'');

			$usertmp = new User($this->db);

			// Replace tags of lines
			try {
				$foundtagforlines = 1;
				try  {
					$listlines = $odfHandler->setSegment('events');
				} catch (OdfException $e) {
					// We may arrive here if tags for lines not present into template
					$foundtagforlines = 0;
					dol_syslog($e->getMessage());
				}

				if ($foundtagforlines) {
					if ( ! empty($event_list) && $event_list > 0) {
						foreach ($event_list as $event) {
							$usertmp->fetch($event->authorid);
							$tmparray['event_ref'] = $event->ref;
							$tmparray['user'] = $usertmp->firstname . ' ' . $usertmp->lastname;
							$tmparray['type'] = $outputLangs->transnoentities('Action'.$event->type_code);
							$tmparray['title'] = $event->label;
							$tmparray['event_content'] = dol_htmlentitiesbr_decode(strip_tags($event->note, '<br>'));
							$tmparray['date'] = dol_print_date($event->datec, 'dayreduceformat');

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
			$tmparray = $outputLangs->get_translations_for_substitutions();
			foreach ($tmparray as $key => $value) {
				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				} catch (OdfException $e) {
					dol_syslog($e->getMessage());
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

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath' => $file);

			return 1; // Success
		} else {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}
	}
}
