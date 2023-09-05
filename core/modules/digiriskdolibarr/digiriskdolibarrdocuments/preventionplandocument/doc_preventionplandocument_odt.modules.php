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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/doc_preventionplandocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';

require_once __DIR__ . '/modules_preventionplandocument.php';
require_once __DIR__ . '/mod_preventionplandocument_standard.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_preventionplandocument_odt extends SaturneDocumentModel
{
	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.5 = array(5, 5)
	 */
	public $phpmin = [7, 4];

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
	public string $document_type = 'preventionplandocument';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
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
	 * Fill all odt tags for segments lines.
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @return int                    1 if OK, <=0 if KO.
	 * @throws Exception
	 */
	public function fillTagsLines(Odf $odfHandler, Translate $outputLangs, array $moreParam): int
	{
		global $conf, $moduleNameLowerCase, $langs;

		$object = $moreParam['object'];

		$digiriskelement = new DigiriskElement($this->db);
		$risk = new Risk($this->db);

		$preventionplanline = new PreventionPlanLine($this->db);
		$preventionplanlines = $preventionplanline->fetchAll('', '', 0, 0, array(), 'AND', $object->id);

		// Replace tags of lines.
		try {
			// Get attendants role controller.
			$foundTagForLines = 1;
			try {
				$listLines = $odfHandler->setSegment('interventions');
			} catch (OdfException $e) {
				// We may arrive here if tags for lines not present into template.
				$foundTagForLines = 0;
				$listLines = '';
				dol_syslog($e->getMessage());
			}

			if ($foundTagForLines) {
				if (is_array($preventionplanlines) && !empty($preventionplanlines)) {
					foreach ($preventionplanlines as $line) {
						$digiriskelement->fetch($line->fk_element);

						$tmparray['key_unique'] = $line->ref;
						$tmparray['unite_travail'] = $digiriskelement->ref . " - " . $digiriskelement->label;
						$tmparray['action'] = $line->description;
						$tmparray['risk'] = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category(
								$line
							) . '.png';
						$tmparray['nomPicto'] = (!empty($conf->global->DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME) ? $risk->get_danger_category_name(
							$line
						) : ' ');
						$tmparray['prevention'] = $line->prevention_method;

						foreach ($tmparray as $key => $val) {
							try {
								if ($key == 'risk') {
									$listLines->setImage($key, $val);
								} elseif (empty($val)) {
									$listLines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
								} else {
									$listLines->setVars(
										$key,
										html_entity_decode($val, ENT_QUOTES | ENT_HTML5),
										true,
										'UTF-8'
									);
								}
							} catch (OdfException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listLines->merge();
					}
				}
				$odfHandler->mergeSegment($listLines);
			}

			return 0;
		} catch (OdfException $e) {
			$this->error = $e->getMessage();
			dol_syslog($this->error, LOG_WARNING);
			return -1;
		}
	}

	/**
	 * Set attendants segment.
	 *
	 * @param  Odf       $odfHandler  Object builder odf library.
	 * @param  Translate $outputLangs Lang object to use for output.
	 * @param  array     $moreParam   More param (Object/user/etc).
	 *
	 * @throws Exception
	 */
	public function setAttendantsSegment(Odf $odfHandler, Translate $outputLangs, array $moreParam)
	{
		global $conf, $moduleNameLowerCase, $langs;

		// Get attendants.
		$foundTagForLines = 1;
		try {
			$listLines = $odfHandler->setSegment('attendants');
		} catch (OdfException $e) {
			// We may arrive here if tags for lines not present into template.
			$foundTagForLines = 0;
			$listLines        = '';
			dol_syslog($e->getMessage());
		}

		if ($foundTagForLines) {
			if (!empty($moreParam['object'])) {
				$signatory        = new SaturneSignature($this->db, $this->module, $moreParam['object']->element);
				$signatoriesArray = $signatory->fetchSignatories($moreParam['object']->id, $moreParam['object']->element);
				if (!empty($signatoriesArray) && is_array($signatoriesArray)) {
					$nbAttendant = 0;
					$tempDir     = $conf->$moduleNameLowerCase->multidir_output[$moreParam['object']->entity ?? 1] . '/temp/';
					if (empty($moreParam['excludeAttendantsRole'])) {
						$moreParam['excludeAttendantsRole'] = [];
					}
					foreach ($signatoriesArray as $objectSignatory) {
						if (!in_array($objectSignatory->role, $moreParam['excludeAttendantsRole'])) {
							$tmpArray['attendant_number']    = ++$nbAttendant;
							$tmpArray['attendant_lastname']  = strtoupper($objectSignatory->lastname);
							$tmpArray['attendant_firstname'] = dol_strlen($objectSignatory->firstname) > 0 ? ucfirst($objectSignatory->firstname) : '';
							switch ($objectSignatory->attendance) {
								case 1:
									$attendance = $outputLangs->trans('Delay');
									break;
								case 2:
									$attendance = $outputLangs->trans('Absent');
									break;
								default:
									$attendance = $outputLangs->transnoentities('Present');
									break;
							}
							switch ($objectSignatory->element_type) {
								case 'user':
									$user    = new User($this->db);
									$societe = new Societe($this->db);
									$user->fetch($objectSignatory->element_id);
									$tmpArray['attendant_job'] = $user->job;
									if ($user->fk_soc > 0) {
										$societe->fetch($user->fk_soc);
										$tmpArray['attendant_company'] = $societe->name;
									} else {
										$tmpArray['attendant_company'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
									}
									break;
								case 'socpeople':
									$contact = new Contact($this->db);
									$societe = new Societe($this->db);
									$contact->fetch($objectSignatory->element_id);
									$tmpArray['attendant_job'] = $contact->poste;
									if ($contact->fk_soc > 0) {
										$societe->fetch($contact->fk_soc);
										$tmpArray['attendant_company'] = $societe->name;
									} else {
										$tmpArray['attendant_company'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
									}
									break;
								default:
									$tmpArray['attendant_job']     = '';
									$tmpArray['attendant_company'] = '';
									break;
							}
							$tmpArray['attendant_role']           = $outputLangs->transnoentities($objectSignatory->role);
							$tmpArray['attendant_signature_date'] = dol_print_date($objectSignatory->signature_date, 'dayhour', 'tzuser');
							$tmpArray['attendant_attendance']     = $attendance;
							if (dol_strlen($objectSignatory->signature) > 0 && $objectSignatory->signature != $langs->transnoentities('FileGenerated')) {
								$confSignatureName = dol_strtoupper($this->module) . '_SHOW_SIGNATURE_SPECIMEN';
								if ($moreParam['specimen'] == 0 || ($moreParam['specimen'] == 1 && $conf->global->$confSignatureName == 1)) {
									$encodedImage = explode(',', $objectSignatory->signature)[1];
									$decodedImage = base64_decode($encodedImage);
									file_put_contents($tempDir . 'signature' . $objectSignatory->id . '.png', $decodedImage);
									$tmpArray['attendant_signature'] = $tempDir . 'signature' . $objectSignatory->id . '.png';
								} else {
									$tmpArray['attendant_signature'] = '';
								}
							} else {
								$tmpArray['attendant_signature'] = '';
							}
							$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
							dol_delete_file($tempDir . 'signature' . $objectSignatory->id . '.png');
						}
					}
				} else {
					$tmpArray['attendant_number']         = '';
					$tmpArray['attendant_lastname']       = '';
					$tmpArray['attendant_firstname']      = '';
					$tmpArray['attendant_job']            = '';
					$tmpArray['attendant_company']        = '';
					$tmpArray['attendant_role']           = '';
					$tmpArray['attendant_signature_date'] = '';
					$tmpArray['attendant_attendance']     = '';
					$tmpArray['attendant_signature']      = '';
					$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
				}
				$odfHandler->mergeSegment($listLines);
			}
		}
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
		global $conf, $langs;

		$object = $moreParam['object'];

		complete_substitutions_array($tmpArray, $langs, $object);

		$openinghours        = new Openinghours($this->db);
		$preventionplanline  = new PreventionPlanLine($this->db);
		$preventionplanlines = $preventionplanline->fetchAll('', '', 0, 0, array(), 'AND', $object->id);

		$jsonData  = $objectDocument->PreventionPlanDocumentFillJSON();
		$arrayData = json_decode($jsonData);
		$arrayData = (array) $arrayData->PreventionPlan;

		if (is_array($preventionplanlines) && !empty($preventionplanlines)) {
			$tmpArray['interventions_info'] = count($preventionplanlines) . ' ' . $langs->trans('PreventionPlanLine');
		} else {
			$tmpArray['interventions_info'] = 0;
		}

		$tmpArray['pompier_number']   = $arrayData['pompier_number'];
		$tmpArray['samu_number']      = $arrayData['samu_number'];
		$tmpArray['emergency_number'] = $arrayData['emergency_number'];
		$tmpArray['police_number']    = $arrayData['police_number'];

		$tmpArray['moyen_generaux_mis_disposition'] = $arrayData['moyen_generaux_mis_disposition'];
		$tmpArray['consigne_generale']              = $arrayData['consigne_generale'];
		$tmpArray['premiers_secours']               = $arrayData['premiers_secours'];

		$tmpArray['titre_prevention']             = $object->ref;
		$tmpArray['raison_du_plan_de_prevention'] = $object->label;

		$tmpArray['prior_visit_date'] = dol_print_date($object->prior_visit_date, 'dayhoursec');
		$tmpArray['prior_visit_text'] = $object->prior_visit_text;

		$tmpArray['date_start_intervention_PPP'] = dol_print_date($object->date_start, 'dayhoursec');
		$tmpArray['date_end_intervention_PPP']   = dol_print_date($object->date_end, 'dayhoursec');

		$morewhere  = ' AND element_id = ' . $object->id;
		$morewhere .= ' AND element_type = ' . "'" . $object->element . "'";
		$morewhere .= ' AND status = 1';

		$openinghours->fetch(0, '', $morewhere);

		$opening_hours_monday    = explode(' ', $openinghours->monday);
		$opening_hours_tuesday   = explode(' ', $openinghours->tuesday);
		$opening_hours_wednesday = explode(' ', $openinghours->wednesday);
		$opening_hours_thursday  = explode(' ', $openinghours->thursday);
		$opening_hours_friday    = explode(' ', $openinghours->friday);
		$opening_hours_saturday  = explode(' ', $openinghours->saturday);
		$opening_hours_sunday    = explode(' ', $openinghours->sunday);

		$tmpArray['lundi_matin']    = $opening_hours_monday[0];
		$tmpArray['lundi_aprem']    = $opening_hours_monday[1];
		$tmpArray['mardi_matin']    = $opening_hours_tuesday[0];
		$tmpArray['mardi_aprem']    = $opening_hours_tuesday[1];
		$tmpArray['mercredi_matin'] = $opening_hours_wednesday[0];
		$tmpArray['mercredi_aprem'] = $opening_hours_wednesday[1];
		$tmpArray['jeudi_matin']    = $opening_hours_thursday[0];
		$tmpArray['jeudi_aprem']    = $opening_hours_thursday[1];
		$tmpArray['vendredi_matin'] = $opening_hours_friday[0];
		$tmpArray['vendredi_aprem'] = $opening_hours_friday[1];
		$tmpArray['samedi_matin']   = $opening_hours_saturday[0];
		$tmpArray['samedi_aprem']   = $opening_hours_saturday[1];
		$tmpArray['dimanche_matin'] = $opening_hours_sunday[0];
		$tmpArray['dimanche_aprem'] = $opening_hours_sunday[1];

		//Informations entreprise extérieure
		$extsociety = $arrayData['society_outside'];

		if ( ! empty($extsociety) && $extsociety > 0) {
			$tmpArray['society_title']    = $extsociety->name;
			$tmpArray['society_siret_id'] = $extsociety->idprof2;
			$tmpArray['society_address']  = $extsociety->address;
			$tmpArray['society_postcode'] = $extsociety->zip;
			$tmpArray['society_town']     = $extsociety->town;
		}

		if ( ! empty($extsocietyintervenants) && $extsocietyintervenants > 0 && is_array($extsocietyintervenants)) {
			$tmpArray['intervenants_info'] = count($extsocietyintervenants);
		} else {
			$tmpArray['intervenants_info'] = 0;
		}

		$tempdir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/temp/';

		$maitreoeuvre = $arrayData['maitre_oeuvre'];

		//Signatures
		if ( ! empty($maitreoeuvre) && $maitreoeuvre > 0) {
			$tmpArray['maitre_oeuvre_lname'] = $maitreoeuvre->lastname;
			$tmpArray['maitre_oeuvre_fname'] = $maitreoeuvre->firstname;
			$tmpArray['maitre_oeuvre_email'] = $maitreoeuvre->email;
			$tmpArray['maitre_oeuvre_phone'] = $maitreoeuvre->phone;

			$tmpArray['maitre_oeuvre_signature_date'] = dol_print_date($maitreoeuvre->signature_date, 'dayhoursec');
			if ((!preg_match('/specimen/i', $tempfilepath[1]) && $object->status >= $object::STATUS_LOCKED)) {
				$encoded_image = explode(",", $maitreoeuvre->signature)[1];
				$decoded_image = base64_decode($encoded_image);
				file_put_contents($tempdir . "signature.png", $decoded_image);
				$tmpArray['maitre_oeuvre_signature'] = $tempdir . "signature.png";
			} else {
				$tmpArray['maitre_oeuvre_signature'] = '';
			}
		}

		$extsocietyresponsible = $arrayData['responsable_exterieur'];

		if ( ! empty($extsocietyresponsible) && $extsocietyresponsible > 0) {
			$tmpArray['intervenant_exterieur_lname'] = $extsocietyresponsible->lastname;
			$tmpArray['intervenant_exterieur_fname'] = $extsocietyresponsible->firstname;
			$tmpArray['intervenant_exterieur_email'] = $extsocietyresponsible->email;
			$tmpArray['intervenant_exterieur_phone'] = $extsocietyresponsible->phone;

			$tmpArray['intervenant_exterieur_signature_date'] = dol_print_date($extsocietyresponsible->signature_date, 'dayhoursec');
			if ((!preg_match('/specimen/i', $tempfilepath[1]) && $object->status >= $object::STATUS_LOCKED)) {
				$encoded_image = explode(",", $extsocietyresponsible->signature)[1];
				$decoded_image = base64_decode($encoded_image);
				file_put_contents($tempdir . "signature2.png", $decoded_image);
				$tmpArray['intervenant_exterieur_signature'] = $tempdir . "signature2.png";
			} else {
				$tmpArray['intervenant_exterieur_signature'] = '';
			}
		}

		$moreParam['tmparray'] = $tmpArray;

		return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}

}
