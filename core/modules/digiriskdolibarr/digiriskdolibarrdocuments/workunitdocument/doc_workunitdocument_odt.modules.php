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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/workunitdocument/doc_workunitdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../digiriskelementdocument/modules_digiriskelementdocument.php';
require_once __DIR__ . '/../../../../../class/digiriskresources.class.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_workunitdocument_odt extends ModeleODTDigiriskElementDocument
{
	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'workunitdocument';

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

        $resources = new DigiriskResources($this->db);
        $userTmp   = new User($this->db);

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
            $QRCodeImagePath = DOL_DOCUMENT_ROOT . '/public/theme/common/nophoto.png';
        }

        $allLinks             = $resources->fetchDigiriskResources();
        $responsibleResources = $allLinks['Responsible'];
        $userTmp->fetch($responsibleResources->id[0]);

        // @todo The keyword "signature" is needed because we want the image to be cropped to fit in the table
        $tmpArray['helpUrl']               = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php';
        $tmpArray['signatureQRCodeTicket'] = $QRCodeImagePath;
        $tmpArray['securityResponsible']   = (!empty($userTmp) ? dol_strtoupper($userTmp->lastname) . ' ' . ucfirst($userTmp->firstname) : '');

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
