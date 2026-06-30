<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/ticketdocument/doc_ticketdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for digiriskdolibarr ticket document
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/lib/medias.lib.php';
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';
/**
 *	Class to build documents using ODF templates generator
 */
class doc_ticketdocument_odt extends SaturneDocumentModel
{
    /**
     * @var string Module
     */
    public string $module = 'digiriskdolibarr';

    /**
     * @var string Document type
     */
    public string $document_type = 'ticketdocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Return description of a module
     *
     * @param Translate $langs Lang object to use for output
     * @return string          Description
     */
    public function info(Translate $langs): string
    {
        return parent::info($langs);
    }

    /**
     * Fill all odt tags for segments lines
     *
     * @param Odf       $odfHandler  Object builder odf library
     * @param Translate $outputLangs Lang object to use for output
     * @param array     $moreParam   More param (Object/user/etc)
     *
     * @return int                   1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        $object = $moreParam['object'];

        $userTmp    = new User($this->db);
        $actionComm = new ActionComm($this->db);

        try {
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('events');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template
                $foundTagForLines = 0;
                $listLines        = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                $outputLangs->load('commercial');
                $actionComms = $actionComm->getActions('', $object->id, $object->element);
                if (is_array($actionComms) && !empty($actionComms)) {
                    foreach ($actionComms as $actionComm) {
                        $userTmp->fetch($actionComm->authorid);
                        $tmpArray['event_ref']     = $actionComm->ref;
                        $tmpArray['user']          = dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname);
                        $tmpArray['type']          = $outputLangs->transnoentities('Action' . $actionComm->type_code);
                        $tmpArray['title']         = $actionComm->label;
                        $tmpArray['event_content'] = dol_htmlentitiesbr_decode($actionComm->note);
                        $tmpArray['date']          = dol_print_date($actionComm->datec, 'dayhourreduceformat');

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
        global $conf, $langs;

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
                $tmpArray['contacts'] .= dol_strtoupper($contact['lastname']) . ' ' . ucfirst($contact['firstname']) . ', ';
            }
        } else {
            $tmpArray['contacts'] = '';
        }

        $signatory   = new SaturneSignature($this->db);
        $signatories = $signatory->fetchSignatory('Attendant', $object->id, $object->element);

        if (!empty($signatories) && is_array($signatories)) {
            $tempDir = $conf->digiriskdolibarr->multidir_output[$object->entity ?? 1] . '/temp/';

            $signature = current($signatories);

             $encodedImage = explode(',', $signature->signature)[1];
            $decodedImage = base64_decode($encodedImage);
            file_put_contents($tempDir . 'signature.png', $decodedImage);

            $tmpArray['signature'] = $tempDir . 'signature.png';
        } else {
            $tmpArray['signature'] = $langs->trans('NoSignature');
        }

        $moreParam['tmparray']         = $tmpArray;
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['hideTemplateName'] = 1;

        if (preg_match('/event/', $srcTemplatePath)) {
            $moreParam['additionalName'] = '_events';
        }

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
