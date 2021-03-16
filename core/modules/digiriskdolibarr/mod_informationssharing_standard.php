<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/custom/digiriskdolibarr/core/modules/digiriskdolibarr/mod_informationssharing_standard.php
 *	\file       htdocs/custom/digiriskdolibarr/core/modules/digiriskdolibarr/mod_informationssharing_standard.php
 * \ingroup     digiriskdolibarr informationssharing
 * \ingroup     digiriskdolibarr informationssharing
 *	\brief      File containing class for numbering module Standard
 */
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/core/modules/digiriskdolibarr/modules_digiriskelement.php';
/**
 * 	Class to manage informationssharing numbering rules Standard
 * 	Class to manage informationssharing numbering rules Standard
 */
class mod_informationssharing_standard extends ModeleNumRefDigiriskElement
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefixinformationssharing = 'V';

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
		global $langs, $db;
		$langs->load("digiriskdolibarr@digiriskdolibarr");
		$now = $db->idate(dol_now());
		$nowDate = str_replace(':','-', $now);
		$nowDate = str_replace(' ','_', $nowDate);
		return $langs->trans('DigiriskInformationsSharingStandardModel',$nowDate . '_diffusion_informations_' . $this->prefixinformationssharing);
	}

	/**Â
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $conf,$db;
		$now = $db->idate(dol_now());
		$now = preg_split('/ /', $now);

		$now = $now[0];
		return $now . '_diffusion_informations_' . $this->prefixinformationssharing."1";
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
		$now = $db->idate(dol_now());
		$now = preg_split('/ /', $now);

		$now = $now[0];
		// first we get the max value
		$posindice = strlen($now . '_diffusion_informations_' . $this->prefixinformationssharing) + 1;
		$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$posindice.") AS SIGNED)) as max";
		$sql .= " FROM ".MAIN_DB_PREFIX."digiriskdolibarr_digiriskdocuments";
		$sql .= " WHERE ref LIKE '".$db->escape($now . '_diffusion_informations_' . $this->prefixinformationssharing)."%'";
		if ($object->ismultientitymanaged == 1) {
			$sql .= " AND entity = ".$conf->entity;
		}
		elseif ($object->ismultientitymanaged == 2) {
			// TODO
		}

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);

			if ($obj) $max = intval($obj->max);
			else $max = 0;
		}
		else
		{
			dol_syslog("mod_informationssharing_standard::getNextValue", LOG_DEBUG);
			return -1;
		}

		if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
		else $num = sprintf("%s", $max + 1);

		dol_syslog("mod_informationssharing_standard::getNextValue return ".$this->prefixinformationssharing.$num);


		return $now . '_diffusion_informations_' . $this->prefixinformationssharing.$num;
	}



	/**
	 *  Return next free value
	 *
	 *  @param  Societe     $objsoc         Object third party
	 *  @param  string      $objforref      Object for number to search
	 *  @param  string      $mode           'next' for next value or 'last' for last value
	 *  @return string                      Next free value
	 */
	public function getNumRef($objsoc, $objforref, $mode = 'next')
	{
		return $this->getNextValue($objsoc, $objforref, $mode);
	}
}
