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
 *	\file       core/modules/digiriskdolibarr/digiriskdolibarrdocuments/listingrisksphoto/doc_listingrisksphoto_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_listingrisksphoto_odt extends SaturneDocumentModel
{
	/**
	 * @var string Module
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type
	 */
	public string $document_type = 'listingrisksphoto';

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
	 * Return description of a module
	 *
	 * @param  Translate $langs Lang object to use for output
	 * @return string           Description
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
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
        $objectDocument = $moreParam['objectDocument'];

        try {
            $digiriskElement = new DigiriskElement($this->db);
            $risk            = new Risk($this->db);

            //@todo a refaire
            $loadRiskInfos = $risk->loadRiskInfos($moreParam);

            $moreParam['digiriskElements']           = $digiriskElement->fetchDigiriskElementFlat(0, [], 'current');
            $moreParam['entity']                     = 'current';
            $moreParam['riskTasks']                  = $loadRiskInfos['current']['riskTasks'];
            $moreParam['riskByRiskAssessmentLevels'] = $loadRiskInfos['current']['riskByRiskAssessmentLevels'];
            $objectDocument->fillRiskData($odfHandler, $outputLangs, $moreParam);
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
        $tmpArray = [];

        $moreParam['tmparray']         = $tmpArray;
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['hideTemplateName'] = 1;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
