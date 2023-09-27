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
 */

/**
 * \file        class/digiriskelement/groupment.class.php
 * \ingroup     digiriskdolibarr
 * \brief       This file is a class file for Groupment
 */

require_once __DIR__ . '/../digiriskelement.class.php';

/**
 * Class for Groupment
 */
class Groupment extends DigiriskElement
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'groupment';
}
