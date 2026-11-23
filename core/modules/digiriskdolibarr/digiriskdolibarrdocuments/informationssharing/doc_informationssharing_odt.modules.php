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
dol_include_once('/saturne/core/modules/saturne/modules_saturne.php');

/**
 *	Class to build documents using ODF templates generator
 */
class doc_informationssharing_odt extends SaturneDocumentModel
{
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
	public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam = []): int
	{
		$tmpArray = [];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = $objectDocument->element . '@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

		$moreParam['tmparray']         = $tmpArray;
		$moreParam['hideTemplateName'] = 1;
		// Staff representative bodies chosen in the social setup, one staffbody segment each
		$moreParam['staffBodies'] = json_decode((string) $objectDocument->json, true)['InformationsSharing']['staff_bodies'] ?? [];

		return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}

	/**
	 * Fill all odt tags for segments lines.
	 *
	 * The staffbody segment repeats the members of each staff representative body chosen in the social setup
	 * (CSE, CHSCT, F3SCT, DP): its title, its election date, its titulars and its alternates
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @return int                    1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
	{
		$result = parent::fillTagsLines($odfHandler, $outputLangs, $moreParam);
		if ($result < 0) {
			return $result;
		}

		try {
			$listLines = $odfHandler->setSegment('staffbody');
		} catch (OdfExceptionSegmentNotFound $e) {
			// A template without the segment, as the custom ones written before the choice of the bodies
			dol_syslog($e->getMessage());
			return 0;
		}

		foreach ($moreParam['staffBodies'] ?? [] as $staffBody) {
			$tmpArray = [
				'staffbody_title'         => dol_strtoupper($outputLangs->transnoentities('StaffBodyMembers' . $staffBody['code'])),
				'staffbody_election_date' => dol_print_date($staffBody['election_date'], 'day', 'auto', $outputLangs),
				'staffbody_titulars'      => dol_htmlentitiesbr_decode(strip_tags($staffBody['titulars'], '<br>')),
				'staffbody_alternates'    => dol_htmlentitiesbr_decode(strip_tags($staffBody['alternates'], '<br>')),
			];
			$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
		}
		// Without any body, the segment is merged empty and the block disappears
		$odfHandler->mergeSegment($listLines);

		return 0;
	}
}
