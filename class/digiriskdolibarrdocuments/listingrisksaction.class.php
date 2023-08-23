<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 */

/**
 * \file    class/digiriskdolibarrdocuments/listingrisksaction.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a class file for ListingRisksAction.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../saturne/class/saturnedocuments.class.php';

/**
 * Class for ListingRisksAction.
 */
class ListingRisksAction extends SaturneDocuments
{
    /**
     * @var string Module name.
     */
    public string $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'listingrisksaction';

    /**
     * Constructor.
     *
     * @param DoliDb $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }
}
