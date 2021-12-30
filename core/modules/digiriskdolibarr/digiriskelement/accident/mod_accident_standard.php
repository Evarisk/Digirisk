<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *	\file       htdocs/custom/digiriskdolibarr/core/modules/digiriskdolibarr/mod_accident_standard.php
 * \ingroup     digiriskdolibarr
 *	\brief      File containing class for numbering module Standard
 */

require_once __DIR__ . '/../../digiriskdocuments/modules_digiriskdocuments.php';

/**
 * 	Class to manage accident numbering rules Standard
 */
class mod_accident_standard extends ModeleNumRefDigiriskDocuments
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string document prefix
	 */
	public $prefix = 'ACC';

	/**
	 * @var string model name
	 */
	public $name = 'Aegir';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 *  Returns the description of the numbering model
	 *
	 *  @return     string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("digiriskdolibarr@digiriskdolibarr");
		return $langs->trans('DigiriskAccidentStandardModel', $this->prefix);
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefix . "1";
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  Object		$object		Object we need next value for
	 *  @return string      			Value if KO, <0 if KO
	 */
	public function getNextValue($object, $version = 0)
	{
		global $db, $conf;

		// first we get the max value
		$posindice = strlen($this->prefix) + 1;
		$sql       = "SELECT MAX(CAST(SUBSTRING(ref FROM " . $posindice . ") AS SIGNED)) as max";
		$sql      .= " FROM " . MAIN_DB_PREFIX . "digiriskdolibarr_accident";
		$sql      .= " WHERE ref LIKE '" . $db->escape($this->prefix) . "%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = " . $conf->entity;
		}

		$resql = $db->query($sql);
		if ($resql) {
			$obj           = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max      = 0;
		} else {
			dol_syslog("mod_accident_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		else $num                          = sprintf("%s", $max + 1);

		dol_syslog("mod_accident_standard::getNextValue return " . $this->prefix . $num);
		return $this->prefix . $num;
	}
}
