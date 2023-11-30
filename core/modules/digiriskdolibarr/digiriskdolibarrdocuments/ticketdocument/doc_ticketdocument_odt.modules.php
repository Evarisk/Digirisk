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
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';
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
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf;

        $object = $moreParam['object'];

        $object->fetch_optionals();

        $digiriskElement = new DigiriskElement($this->db);
        $category        = new Categorie($this->db);
        $userTmp         = new User($this->db);

        $tmpArray['ref']          = $object->ref;
        $tmpArray['lastname']     = $object->array_options['options_digiriskdolibarr_ticket_lastname'];
        $tmpArray['firstname']    = $object->array_options['options_digiriskdolibarr_ticket_firstname'];
        $tmpArray['phone_number'] = $object->array_options['options_digiriskdolibarr_ticket_phone'];
        if ($object->array_options['options_digiriskdolibarr_ticket_service'] > 0) {
            $digiriskElement->fetch($object->array_options['options_digiriskdolibarr_ticket_service']);
            $tmpArray['service'] = $digiriskElement->ref . ' - ' . $digiriskElement->label;
        } else {
            $tmpArray['service'] = '';
        }
        $tmpArray['location']         = $object->array_options['options_digiriskdolibarr_ticket_location'];
        $tmpArray['declaration_date'] = dol_print_date($object->array_options['options_digiriskdolibarr_ticket_date'],'dayhoursec','tzuser');
        $tmpArray['creation_date']    = dol_print_date($object->date_creation, 'dayhoursec', 'tzuser');
        $tmpArray['close_date']       = dol_print_date($object->date_close, 'dayhoursec', 'tzuser');
        $tmpArray['progress']         = !empty($object->progress) ? $object->progress . ' %' : '0 %';

        $categories = $category->containing($object->id, Categorie::TYPE_TICKET);
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                $allCategories[] = $cat->label;
            }
            $tmpArray['categories'] = implode(', ', $allCategories);
        } else {
            $tmpArray['categories'] = '';
        }

        $tmpArray['status'] = $object->getLibStatut();

        $userTmp->fetch($object->fk_user_assign);
        $tmpArray['assigned_to'] = ucfirst($userTmp->firstname) . ' ' . strtoupper($userTmp->lastname);

        $photoPath = $conf->ticket->multidir_output[$conf->entity] . '/' . $object->ref;
        $fileArray = dol_dir_list($photoPath, 'files', 0, '', '(\.odt|_preview.*\.png|\.pdf)$', 'date', 'desc', 1);
        if (count($fileArray) && !empty($fileArray)) {
            $fileArray         = dol_sort_array($fileArray, 'position');
            $thumbName         = saturne_get_thumb_name($fileArray[0]['name']);
            $tmpArray['photo'] = $photoPath . '/thumbs/' . $thumbName;
        } else {
            $noPhoto           = '/public/theme/common/nophoto.png';
            $tmpArray['photo'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $tmpArray['subject']         = $object->subject;
        $tmpArray['message']         = dol_htmlentitiesbr_decode(strip_tags($object->message, '<br>'));
        $tmpArray['generation_date'] = dol_print_date(dol_now(), 'dayhoursec', 'tzuser');

        $contactListExternal = $object->liste_contact(-1, 'external');
        $contactListInternal = $object->liste_contact(-1, 'internal');

        $contactList = [];
        if (!empty($contactListExternal) && is_array($contactListExternal)) {
            $contactList = array_merge($contactList, $contactListExternal);
        }
        if (!empty($contactListInternal) && is_array($contactListInternal)) {
            $contactList = array_merge($contactList, $contactListInternal);
        }
        if (!empty($contactList) && is_array($contactList)) {
            foreach ($contactList as $contact) {
                $tmpArray['contacts'] .= ucfirst($contact['firstname']) . ' ' . strtoupper($contact['lastname']) . ', ';
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

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
