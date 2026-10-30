<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    class/digiriskdolibarrdocuments/ticketqqoqcpdocument.class.php
 * \ingroup digiriskdolibarr
 * \brief   This file is a class file for TicketQqoqcpDocument, the QQOQCP analysis of a ticket (#5549).
 */

// Load Saturne libraries.
dol_include_once('/saturne/class/saturnedocuments.class.php');

/**
 * Class for TicketQqoqcpDocument.
 */
class TicketQqoqcpDocument extends SaturneDocuments
{
    /**
     * @var string Module name.
     */
    public $module = 'digiriskdolibarr';

    /**
     * @var string Element type of object.
     */
    public $element = 'ticketqqoqcpdocument';

    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler.
     */
    public function __construct(DoliDB $db)
    {
        parent::__construct($db, $this->module, $this->element);
    }
}
