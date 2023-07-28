<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiriskdolibarr_accident_investigation.lib.php
 * \ingroup digiriskdolibarr
 * \brief   Library files with common functions for Accident
 */

/**
 * Prepare array of tabs for Accident investigation
 *
 * @param  AccidentInvestigation $object Accident
 * @return array                         Array of tabs
 * @throws Exception
 */
function accident_investigation_prepare_head(AccidentInvestigation $object): array
{
	return saturne_object_prepare_head($object);
}
