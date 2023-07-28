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
 *  \file			core/modules/digiriskdolibarr/modules_accidentinvestigationdocument.php
 *  \ingroup		digiriskdolibarr
 *  \brief			File that contains parent class for digiriskelementdocuments document models
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 *	Parent class for documents models
 */
abstract class ModeleODTAccidentInvestigationDocument extends CommonDocGenerator
{

	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 * @param AccidentInvestigationDocument $object Object source to build document
	 * @param Translate $outputlangs Lang output object
	 * @param string $srctemplatepath Full path of source filename for generator using a template file
	 * @param int $hidedetails Do not show line details
	 * @param int $hidedesc Do not show desc
	 * @param int $hideref Do not show ref
	 * @param DigiriskElement $digiriskelement Object for get DigiriskElement info
	 * @return    int                                             1 if OK, <=0 if KO
	 * @throws    Exception
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $digiriskelement)
	{
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 * @param  DoliDB $db                Database handler
	 * @param  int    $maxfilenamelength Max length of value to show
	 * @return array                     List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		$type = 'accidentinvestigationdocument';

		require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_function.lib.php';
		return getListOfModelsDigirisk($db, $type, $maxfilenamelength);
	}
}
