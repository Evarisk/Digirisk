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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/ticketdocument/doc_ticketdocument_custom_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/mod_ticketdocument_standard.php';
require_once __DIR__ . '/modules_ticketdocument.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_ticketdocument_custom_odt extends ModeleODTTicketDocument
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
		$this->name        = $langs->trans('TicketDocumentCustomDigiriskTemplate');
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir     = 'DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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

		$form = new Form($this->db);

		$texte  = $this->description . ".<br>\n";
		$texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="' . newToken() . '">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="conf" value="DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH">';
		$texte .= '<input type="hidden" name="path" value="' . $conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH . '">';
		$texte .= '<table class="nobordernopadding">';

		// List of directories area
		$texte      .= '<tr><td>';
		$texttitle   = $langs->trans("ListOfDirectories");
		$listofdir   = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH)));
		$listoffiles = array();

		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if ( ! $tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if ( ! is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else {
				$tmpfiles                          = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp .= '<br>' . $langs->trans("FollowingSubstitutionKeysCanBeUsed") . '<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
		$texte .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte .= '<span class="flat" style="font-weight: bold">';
		$texte .= $conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH;
		$texte .= '</span>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if ( ! empty($conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH)) {
			$texte .= $langs->trans("NumberOfModelFilesFound") . ': <b>';
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
		// Add input to upload a new template file.
		$texte .= '<div>' . $langs->trans("UploadNewTemplate") . ' <input type="file" name="userfile">';
		$texte .= '<input type="hidden" value="DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH" name="keyforuploaddir">';
		$texte .= '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans("Upload")) . '" name="upload">';
		$texte .= '</div>';
		$texte .= '</td>';

		$texte .= '<td rowspan="2" class="tdtop hideonsmartphone">';
		$texte .= $langs->trans("PleaseNameTheFile", 'template_ticketdocument_custom.odt');
		$texte .= '</td>';
		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 * @param 	TicketDocument		$object 			Object source to build document
	 * @param 	Translate 				$outputlangs 		Lang output object
	 * @param 	string 					$srctemplatepath 	Full path of source filename for generator using a template file
	 * @param	int						$hidedetails		Do not show line details
	 * @param	int						$hidedesc			Do not show desc
	 * @param	int						$hideref			Do not show ref
	 * @param 	Ticket			$ticket    Object for get Ticket info
	 * @return	int                            				1 if OK, <=0 if KO
	 * @throws 	Exception
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $ticket){
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_ticketdocument_custom_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
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

		$outputlangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr", "other"));

		$mod = new $conf->global->DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON($this->db);
		$ref = $mod->getNextValue($object);

		$object->ref = $ref;
		$id          = $object->create($user, true, $ticket);

		$object->fetch($id);

		$dir       = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/ticketdocument/' . $ticket->ref;
		$objectref = dol_sanitizeFileName($ticket->ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/ticketdocument\//', $srctemplatepath);
			preg_replace('/template_/', '', $filename[1]);

			if (preg_match('/_events/', $filename[1])) {
				$foundtagforlines = 1;
				$events = '_events';
			} else {
				$foundtagforlines = 0;
				$events = '';
			}

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $ref . '_' . $objectref . '_' . $conf->global->MAIN_INFO_SOCIETE_NOM . $events . '.odt';
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
			complete_substitutions_array($substitutionarray, $langs, $ticket);
			// Call the ODTSubstitution hook
			$parameters = array('file' => $file, 'object' => $ticket, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $ticket may have been modified by some hooks

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
				$tmparray['categories']       = implode(', ', $allcategories);
			} else {
				$tmparray['categories']       = '';
			}

			$tmparray['status']           = $ticket->getLibStatut();

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
				$file_small               = preg_split('/\./', $filearray[0]['name']);
				$new_file                 = $file_small[0] . '_small.' . $file_small[1];
				$tmparray['photo'] = $photo_path . '/thumbs/' . $new_file;
			} else {
				$nophoto                  = '/public/theme/common/nophoto.png';
				$tmparray['photo'] = DOL_DOCUMENT_ROOT . $nophoto;
			}

			$message = preg_replace('/<br \/>/', '', $ticket->message);
			$tmparray['subject'] = $ticket->subject;
			$tmparray['message']  = $message;
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
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
			$actioncomm = new ActionComm($this->db);
			$event_list = $actioncomm->getActions('',$ticket->id,$ticket->element,'');

			$usertmp = new User($this->db);

			// Replace tags of lines
			try {
				if ($foundtagforlines) {
					if ( ! empty($event_list) && $event_list > 0) {
						$listlines = $odfHandler->setSegment('events');

						foreach ($event_list as $event) {
							$usertmp->fetch($event->authorid);
							$tmparray['event_ref'] = $event->ref;
							$tmparray['user'] = $usertmp->firstname . ' ' . $usertmp->lastname;
							$tmparray['type'] = $outputlangs->transnoentities('Action'.$event->type_code);
							$tmparray['title'] = $event->label;
							$content = preg_replace('/<br \/>/', '', $event->note);
							$tmparray['event_content'] = $content;
							$tmparray['date'] = dol_print_date($event->datec, 'dayreduceformat');

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

			$odfHandler = null; // Destroy object

			$this->result = array('fullpath' => $file);

			return 1; // Success
		} else {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}
	}
}
