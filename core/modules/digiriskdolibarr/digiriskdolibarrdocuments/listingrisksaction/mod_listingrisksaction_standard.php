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
 *  \file    core/modules/digiquali/controldocument/mod_controldocument_standard.php
 *  \ingroup digiquali
 *  \brief   File of class to manage controldocument numbering rules standard.
 */

// Load Saturne libraries.
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to manage controldocument numbering rules standard.
 */
class mod_listingrisksaction_standard extends ModeleNumRefSaturne
{
	/**
	 * @var string Numbering module ref prefix.
	 */
	public string $prefix = 'RLA';

	/**
	 * @var string Name.
	 */
	public string $name = 'Pan';
}
