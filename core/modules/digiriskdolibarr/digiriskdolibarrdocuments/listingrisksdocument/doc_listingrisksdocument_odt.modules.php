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
 * GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>
 * or see https://www.gnu.org/
 */

/**
 * \file    core/modules/digiriskdolibarr/digiriskdolibarrdocuments/listingrisksdocument/doc_listingrisksdocument_odt.modules.php
 * \ingroup digiriskdolibarr
 * \brief   File of class to build ODT documents for for listing risks document
 */

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../modules_digiriskdolibarrdocument.php';

/**
 * Class to build documents using ODF templates generator
 */
class doc_listingrisksdocument_odt extends ModeleODTDigiriskDolibarrDocument
{
    /**
     * @var string Document type
     */
    public string $document_type = 'listingrisksdocument';

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
        // Load DigiriskDolibarr libraries
        require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';

        $digiriskElement = new DigiriskElement($this->db);

        $parentId = 0;
        if ($moreParam['object']->element != 'digiriskstandard') {
            $parentId = $moreParam['object']->id;
        }
        $moreParam['digiriskElements'] = $digiriskElement->fetchDigiriskElementFlat($parentId, [], 'current', true);

        return parent::fillTagsLines($odfHandler, $outputLangs, $moreParam);
    }
}
