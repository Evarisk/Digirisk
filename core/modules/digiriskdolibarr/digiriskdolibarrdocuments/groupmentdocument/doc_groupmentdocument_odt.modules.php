<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/groupmentdocument/doc_groupmentdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for for groupment document
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_groupmentdocument_odt extends ModeleODTDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'groupmentdocument';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->document_type);
    }

    /**
     * Fill all odt tags for segments lines
     *
     * @param  Odf       $odfHandler  Object builder odf library
     * @param  Translate $outputLangs Lang object to use for output
     * @param  array     $moreParam   More param (Object/user/etc)
     *
     * @return int                    1 if OK, <=0 if KO
     * @throws Exception
     */
    public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
    {
        $moreParam['filterEvaluator'] = ' AND t.fk_parent = ' . $moreParam['object']->id;
        $moreParam['filterTicket']    = ' AND eft.digiriskdolibarr_ticket_service = ' . $moreParam['object']->id;
        $moreParam['filter']          = ' AND t.fk_element = ' . $moreParam['object']->id;

        return parent::fillTagsLines($odfHandler, $outputLangs, $moreParam);
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
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
    {
        global $conf;

        // Load DigiriskDolibarr libraries
        require_once __DIR__ . '/../../../../../class/digiriskresources.class.php';

        $digiriskResources = new DigiriskResources($this->db);
        $userTmp           = new User($this->db);

        $object = $moreParam['object'];

        if (!empty($object->photo)) {
            $path              = $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref;
            $fileSmall         = saturne_get_thumb_name($object->photo);
            $image             = $path . '/thumbs/' . $fileSmall;
            $tmpArray['photo'] = $image;
        } else {
            $noPhoto           = '/public/theme/common/nophoto.png';
            $tmpArray['photo'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        // Get QRCode to public interface
        if (isModEnabled('multicompany')) {
            $qrCodePath = DOL_DATA_ROOT . '/digiriskdolibarr/multicompany/ticketqrcode/';
        } else {
            $qrCodePath = $conf->digiriskdolibarr->multidir_output[$conf->entity ?: 1] . '/ticketqrcode/';
        }
        $QRCodeList = dol_dir_list($qrCodePath);
        if (is_array($QRCodeList) && !empty($QRCodeList)) {
            $QRCode          = array_shift($QRCodeList);
            $QRCodeImagePath = $QRCode['fullname'];
        } else {
            $QRCodeImagePath = ''; // @todo gerer le cas avec le nouveau fonctionnel des liens externes
        }

        $allLinks             = $digiriskResources->fetchDigiriskResources();
        $responsibleResources = $allLinks['Responsible'];
        $userTmp->fetch($responsibleResources->id[0]);

        // @todo The keyword "signature" is needed because we want the image to be cropped to fit in the table
        $tmpArray['helpUrl']               = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php';
        $tmpArray['signatureQRCodeTicket'] = $QRCodeImagePath;
        $tmpArray['securityResponsible']   = $userTmp->id > 0 ? dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname) : '';

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
