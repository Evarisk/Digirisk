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

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_ticketdocument_odt extends SaturneDocumentModel
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
	 *    Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db, $this->module, $this->document_type);
	}

	/**
	 * Return description of a module.
	 *
	 * @param Translate $langs Lang object to use for output.
	 * @return string           Description.
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
	}

	/**
	 * Fill all odt tags for segments lines.
	 *
	 * @param Odf $odfHandler Object builder odf library.
	 * @param Translate $outputLangs Lang object to use for output.
	 * @param array $moreParam More param (Object/user/etc).
	 *
	 * @return int                    1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
	{
		global $conf, $hookmanager;


		$object = $moreParam['object'];

		$objectDocument = $moreParam['objectDocument'];
		$usertmp = new User($this->db);
		try {
			$foundtagforlines = 1;
			try {
				$listLines = $odfHandler->setSegment('events');
			} catch (OdfException $e) {
				// We may arrive here if tags for lines not present into template
				$foundtagforlines = 0;
				dol_syslog($e->getMessage());
			}

			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
			$actioncomm = new ActionComm($this->db);
			$event_list = $actioncomm->getActions('',$object->id,$object->element,'');

			if ($foundtagforlines) {
				if (!empty($event_list) && $event_list > 0) {
					foreach ($event_list as $event) {
						$usertmp->fetch($event->authorid);
						$tmpArray['event_ref'] = $event->ref;
						$tmpArray['user'] = $usertmp->firstname . ' ' . $usertmp->lastname;
						$tmpArray['type'] = $outputLangs->transnoentities('Action' . $event->type_code);
						$tmpArray['title'] = $event->label;
						$tmpArray['event_content'] = dol_htmlentitiesbr_decode(strip_tags($event->note, '<br>'));
						$tmpArray['date'] = dol_print_date($event->datec, 'dayreduceformat');

						$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
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
	 * @param SaturneDocuments $objectDocument Object source to build document.
	 * @param Translate $outputLangs Lang object to use for output.
	 * @param string $srcTemplatePath Full path of source filename for generator using a template file.
	 * @param int $hideDetails Do not show line details.
	 * @param int $hideDesc Do not show desc.
	 * @param int $hideRef Do not show ref.
	 * @param array $moreParam More param (Object/user/etc).
	 * @return int                               1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0,	int $hideDesc = 0, int $hideRef = 0, array $moreParam): int {

		$ticket = $moreParam['object'];

		$ticket->fetch_optionals();
		$digiriskelement = new DigiriskElement($this->db);

		$tmpArray['ref'] = $ticket->ref;
		$tmpArray['lastname'] = $ticket->array_options['options_digiriskdolibarr_ticket_lastname'];
		$tmpArray['firstname'] = $ticket->array_options['options_digiriskdolibarr_ticket_firstname'];
		$tmpArray['phone_number'] = $ticket->array_options['options_digiriskdolibarr_ticket_phone'];
		if ($ticket->array_options['options_digiriskdolibarr_ticket_service'] > 0) {
			$digiriskelement->fetch($ticket->array_options['options_digiriskdolibarr_ticket_service']);
			$tmpArray['service'] = $digiriskelement->ref . ' - ' . $digiriskelement->label;
		} else {
			$tmpArray['service'] = '';
		}
		$tmpArray['location'] = $ticket->array_options['options_digiriskdolibarr_ticket_location'];
		$tmpArray['declaration_date'] = dol_print_date(
			$ticket->array_options['options_digiriskdolibarr_ticket_date'],
			'dayhoursec',
			'tzuser'
		);
		$tmpArray['creation_date'] = dol_print_date($ticket->date_creation, 'dayhoursec', 'tzuser');
		$tmpArray['close_date'] = dol_print_date($ticket->date_close, 'dayhoursec', 'tzuser');
		$tmpArray['progress'] = !empty($ticket->progress) ? $ticket->progress . ' %' : '0 %';

		$category = new Categorie($this->db);
		$categories = $category->containing($ticket->id, Categorie::TYPE_TICKET);
		if (!empty($categories)) {
			foreach ($categories as $cat) {
				$allcategories[] = $cat->label;
			}
			$tmpArray['categories'] = implode(', ', $allcategories);
		} else {
			$tmpArray['categories'] = '';
		}

		$tmpArray['status'] = $ticket->getLibStatut();

		$user = new User($this->db);
		$user->fetch($ticket->fk_user_assign);
		$tmpArray['assigned_to'] = $user->firstname . ' ' . $user->lastname;


		require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

		$photo_path = $conf->ticket->multidir_output[$conf->entity] . '/' . $ticket->ref;
		$filearray = dol_dir_list($photo_path, "files", 0, '', '(\.odt|_preview.*\.png|\.pdf)$', 'date', 'desc', 1);
		if ($photo_path) {
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $photo_path);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
		}

		if (count($filearray)) {
			$filearray = dol_sort_array($filearray, 'position');
			$thumb_name = getThumbName($filearray[0]['name']);
			$tmpArray['photo'] = $photo_path . '/thumbs/' . $thumb_name;
		} else {
			$nophoto = '/public/theme/common/nophoto.png';
			$tmpArray['photo'] = DOL_DOCUMENT_ROOT . $nophoto;
		}

		$tmpArray['subject'] = $ticket->subject;
		$tmpArray['message'] = dol_htmlentitiesbr_decode(strip_tags($ticket->message, '<br>'));
		$tmpArray['generation_date'] = dol_print_date(dol_now(), 'dayhoursec', 'tzuser');

		$contactlistexternal = $ticket->liste_contact(-1, 'external');
		$contactlistinternal = $ticket->liste_contact(-1, 'internal');

		$contactlist = array();

		if (!empty($contactlistexternal) && is_array($contactlistexternal)) {
			$contactlist = array_merge($contactlist, $contactlistexternal);
		}

		if (!empty($contactlistinternal) && is_array($contactlistinternal)) {
			$contactlist = array_merge($contactlist, $contactlistinternal);
		}

		if (!empty($contactlist) && is_array($contactlist)) {
			foreach ($contactlist as $contact) {
				$tmpArray['contacts'] .= $contact['firstname'] . ' ' . $contact['lastname'] . ', ';
			}
		} else {
			$tmpArray['contacts'] = '';
		}

		$moreParam['tmparray']         = $tmpArray;
		$moreParam['objectDocument']   = $objectDocument;
		$moreParam['subDir']           = 'digiriskdolibarrdocuments/';
		$moreParam['hideTemplateName'] = 1;

		if (preg_match('/event/', $srcTemplatePath)) {
			$moreParam['additionalName'] = '_events';
		}

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
