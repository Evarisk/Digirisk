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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/registerdocument/doc_registerdocument_odt.modules.php
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
class doc_registerdocument_odt extends SaturneDocumentModel
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
	public string $document_type = 'registerdocument';

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
		$tmpArray = [];

        $ticket   = new Ticket($this->db);
        $accident = new Accident($this->db);

		$objectDocument->element = $objectDocument->element . '@digiriskdolibarr';
		complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
		$objectDocument->element = $objectDocument->element;

		$moreParam['tmparray']         = $tmpArray;
		$moreParam['subDir']           = 'digiriskdolibarrdocuments/';
		$moreParam['hideTemplateName'] = 1;

        $tmpArray['company_nb_employees'] = ;
        $tmpArray['total_page_nb'] = 6;
        //foreach
//            $tmpArray['caregiver_id'] = ;
//            $tmpArray['caregiver_lastname'] = ;
//            $tmpArray['caregiver_firstname'] = ;
//            $tmpArray['caregiver_qualification'] = ;
//            $tmpArray['caregiver_signature'] = ;
        //foreach
    //        $tmpArray['register_controller_id'] = ;
    //        $tmpArray['register_controller_lastname'] = ;
    //        $tmpArray['register_controller_firstname'] = ;
    //        $tmpArray['register_controller_society'] = ;
    //        $tmpArray['register_controller_date'] = ;
    //        $tmpArray['register_controller_signature'] = ;
    //        $tmpArray['register_controller_note'] = ;

        $accidentList = $accident->fetchAll('', '', 0, 0, ['customsql' => 'fk_ticket > 0']);

        if (is_array($accidentList) && !empty($accidentList)) {
            // foreach
//            $tmpArray['register_name'] = ;
//            $tmpArray['register_date'] = ;
//            $tmpArray['register_fullname'] = ;
//            $tmpArray['register_datehour'] = ;
//            $tmpArray['register_location'] = ;
//            $tmpArray['register_circumstances'] = ;
//            $tmpArray['register_lesion_location'] = ;
//            $tmpArray['register_lesion_nature'] = ;
//            $tmpArray['register_witnesses_data'] = ;
//            $tmpArray['register_external_society_implied'] = ;
//            $tmpArray['register_caregiver_fullname'] = ;
//            $tmpArray['register_victim_signature'] = ;
//            $tmpArray['register_note'] = ;
        }



        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}
}
