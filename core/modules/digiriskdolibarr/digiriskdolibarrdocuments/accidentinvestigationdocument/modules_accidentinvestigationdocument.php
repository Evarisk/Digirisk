<?php
/* Copyright (C) 2021 EVARISK <technique@evarisk.com>
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
 *  \file    core/modules/digiriskdolibarr/modules_accidentinvestigationdocument.php
 *  \ingroup digiriskdolibarr
 *  \brief   File that contains parent class for accident investigation document models
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Parent class for accident investigation document models
 */
abstract class ModeleODTAccidentInvestigationDocument extends SaturneDocumentModel
{
	/**
	 * Return list of active generation modules.
	 *
	 * @param  DoliDB $db                Database handler.
	 * @param  string $type              Document type.
	 * @param  int    $maxfilenamelength Max length of value to show.
	 *
	 * @return array|int                 0 if no module is activated, or array(key=>label). For modules that need directory scan, key is completed with ":filename".
	 * @throws Exception
	 */
	public static function liste_modeles(DoliDB $db, string $type, int $maxfilenamelength = 0): array
	{
		return parent::liste_modeles($db, 'accidentinvestigationdocument', $maxfilenamelength);
	}
}
