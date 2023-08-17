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
 *	\file       core/modules/digiriskdolibarr/digiriskdolibarrdocuments/accidentinvestigationdocument/doc_accidentinvestigationdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';

require_once __DIR__ . '/../../../../../class/digiriskstandard.class.php';
require_once __DIR__ . '/modules_accidentinvestigationdocument.php';
require_once __DIR__ . '/mod_accidentinvestigationdocument_standard.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_accidentinvestigationdocument_odt extends ModeleODTAccidentInvestigationDocument
{
	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public array $phpmin = [7, 4];

	/**
	 * @var string Dolibarr version of the loaded document.
	 */
	public string $version = 'dolibarr';

	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'accidentinvestigationdocument';

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler.
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db, $this->module, $this->document_type);
	}

	/**
	 * Return description of a module.
	 *
	 * @param  Translate $langs Lang object to use for output.
	 * @return string           Description.
	 */
	public function info(Translate $langs): string
	{
		return parent::info($langs);
	}

	/**
	 * Function to build a document on disk.
	 *
	 * @param  SaturneDocuments $objectDocument  Object source to build document.
	 * @param  Translate        $outputLangs     Lang object to use for output.
	 * @param  string           $srcTemplatePath Full path of source filename for generator using a template file.
	 * @param  int              $hideDetails     Do not show line details.
	 * @param  int              $hideDesc        Do not show desc.
	 * @param  int              $hideRef         Do not show ref.
	 * @param  array            $moreParam       More param (Object/user/etc).
	 * @return int                               1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
	{
		global $conf, $db, $langs;

		$object           = $moreParam['object'];
		$accident         = new Accident($db);
		$accidentMetadata = new AccidentMetaData($db);
		$victim           = new User($db);
		$tmpArray         = [];

		$accident->fetch($object->fk_accident);
		$accidentMetadata->fetch(0, '', 'AND status = 1 AND fk_accident = ' . $accident->id);
		$victim->fetch($accident->fk_user_victim);

		$tmpArray['mycompany_name']    = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$tmpArray['mycompany_siret']   = $conf->global->MAIN_INFO_SIRET;
		$tmpArray['mycompany_address'] = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
		$tmpArray['mycompany_contact'] = $conf->global->MAIN_INFO_SOCIETE_MANAGERS;
		$tmpArray['mycompany_mail']    = $conf->global->MAIN_INFO_SOCIETE_MAIL;
		$tmpArray['mycompany_phone']   = $conf->global->MAIN_INFO_SOCIETE_PHONE;

		$tmpArray['involved_user_lastname']  = 't';
		$tmpArray['involved_user_firstname'] = 't';
		$tmpArray['involved_user_job']       = 't';
		$tmpArray['involved_user_company']   = 't';

		$tmpArray['victim_lastname']        = $victim->lastname;
		$tmpArray['victim_firstname']       = $victim->firstname;
		$tmpArray['victim_date_employment'] = $victim->dateemployment;

		$tmpArray['accident_date'] = dol_print_date($accident->accident_date, 'day');
		$tmpArray['accident_hour'] = dol_print_date($accident->accident_date, 'hour');
		$tmpArray['accident_day']  = $langs->trans(date('l', $accident->accident_date));

		if ($accident->external_accident == 1) {
			if ($accident->fk_element > 0) {
				$element = new DigiriskElement($db);
				$element->fetch($accident->fk_element);
			} else {
				$element = new DigiriskStandard($db);
				$element->fetch($accident->fk_standard);
			}
			$tmpArray['gp_ut'] = $element->getNomUrl(0, 'nolink', 1);
		} else if ($accident->external_accident == 2) {
			$societe = new Societe($db);
			$societe->fetch($accident->fk_soc);
			$tmpArray['gp_ut'] = $societe->getNomUrl(0, 'nolink');
		} else {
			$tmpArray['gp_ut'] = $accident->accident_location;
		}

		$tmpArray['investigation_date_start'] = dol_print_date($object->date_start, 'dayhour');
		$tmpArray['investigation_date_end']   = dol_print_date($object->date_end, 'dayhour');

		$tmpArray['victim_skills']        = $object->victim_skills;
		$tmpArray['collective_equipment'] = $object->victim_skills;
		$tmpArray['individual_equipment'] = $object->victim_skills;
		$tmpArray['circumstances']        = $object->circumstances;
		$tmpArray['public_note']          = $object->note_public;

		$tmpArray['relative_location'] = $accidentMetadata->relative_location;

		$moreParam['tmparray'] = $tmpArray;

		return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}
}
