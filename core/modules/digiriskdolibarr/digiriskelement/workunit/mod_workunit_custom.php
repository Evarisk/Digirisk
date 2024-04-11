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
 *  \file    core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_custom.php
 *  \ingroup digiriskdolibarr
 *  \brief   File of class to manage mod_workunit_custom numbering rules standard.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *    Class to manage workunit numbering rules custom.
 */
class mod_workunit_custom extends CustomModeleNumRefSaturne
{
    /**
     * @var string Numbering module ref prefix.
     */
    public string $prefix = 'WU';

    /**
     * @var string Name.
     */
    public string $name = 'Thébé';

    /**
     *  Return description of module
     *
     * @param String $mode Either "standard" for normal prefix or "custom"
     * @return String       Descriptive text
     */
    public function info($mode = 'custom'): string
    {
        return parent::info($mode);
    }

    /**
     *  Return next value
     *
     * @return string Value if OK, 0 if KO
     */
    public function getNextValue(object $object): string
    {
        return parent::getNextCustomValue($object);
    }
}
