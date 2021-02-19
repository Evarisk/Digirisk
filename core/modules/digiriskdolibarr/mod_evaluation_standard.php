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
 *	\file       htdocs/custom/digiriskdolibarr/core/modules/digiriskdolibarr/mod_evaluation_standard.php
 * \ingroup     digiriskdolibarr evaluation
 *	\brief      File containing class for numbering module Standard
 */
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/core/modules/digiriskdolibarr/modules_digiriskevaluation.php';
/**
 * 	Class to manage evaluation numbering rules Standard
 */
class mod_evaluation_standard extends ModeleNumRefDigiriskEvaluation
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	public $prefixevaluation = '';

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
		return $langs->trans('DigiriskEvaluationStandardModel', $this->prefixevaluation);
	}

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		return $this->prefixevaluation."1";
	}
	/**
	 * Return next value not used or last value used
	 *
	 */
	public function getNextValue($object)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		// On defini critere recherche compteur
		$mask = $conf->global->EVALUATION_STANDARD_MASK;

		if (!$mask)
		{
			$this->error = 'NotConfigured';
			return 0;
		}

		// Get entities
		$entity = $conf->entity;

		$date = dol_now();

		$numFinal = get_next_value($db, $mask, 'digiriskdolibarr_digiriskevaluation', 'ref','', $object, $date, 'next', false, null, $entity);


		$this->prefixevaluation = $numFinal;
		return  $numFinal;
	}

	/**
	 *  Return next free value
	 *
	 *  @param  Societe     $objsoc         Object third party
	 *  @param  string      $objforref      Object for number to search
	 *  @param  string      $mode           'next' for next value or 'last' for last value
	 *  @return string                      Next free value
	 */
	public function getNumRef($objforref, $mode = 'next')
	{
		return $this->getNextValue($objforref, $mode);
	}
}
