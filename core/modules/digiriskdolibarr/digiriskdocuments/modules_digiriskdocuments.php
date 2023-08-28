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
 *  \file			core/modules/digiriskdolibarr/modules_digiriskdocuments.php
 *  \ingroup		digiriskdolibarr
 *  \brief			File that contains parent class for digiriskdocuments numbering models
 */

/**
 *  Parent class to manage numbering of DigiriskDocuments
 */
abstract class ModeleNumRefDigiriskDocuments
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return        bool     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *	Returns the default description of the numbering template
	 *
	 *	@return     string      Texte descriptif
	 */
	public function info()
	{
		global $langs;
		$langs->load("digiriskdolibarr@digiriskdolibarr");
		return $langs->trans("NoDescription");
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 * @return bool                false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**
	 *    Returns next assigned value
	 *
	 * @param Object $object Object we need next value for
	 * @return    string      Valeur
	 */
	public function getNextValue($object)
	{
		global $langs;
		$object->ref = '';
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Returns version of numbering module
	 *
	 *	@return     string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
	}
}
