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
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/workunitdocument/doc_workunitdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for workunit document
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_workunitdocument_odt extends ModeleODTDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'workunitdocument';

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
}
