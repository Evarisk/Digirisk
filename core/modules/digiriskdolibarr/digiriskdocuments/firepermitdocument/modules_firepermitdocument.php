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
 *  \file			core/modules/digiriskdolibarr/digiriskdocuments/firepermitdocument/modules_firepermitdocument.php
 *  \ingroup		digiriskdolibarr
 *  \brief			File that contains parent class for firepermits document models
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';

/**
 *	Parent class for documents models
 */
abstract class ModeleODTFirePermitDocument extends CommonDocGenerator
{

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param int $maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		$type = 'firepermitdocument';

		require_once __DIR__ . '/../../../../../lib/digiriskdolibarr_function.lib.php';
		return getListOfModelsDigirisk($db, $type, $maxfilenamelength);
	}

	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 * @param 	FirePermitDocument	$object 			Object source to build document
	 * @param 	Translate 			$outputlangs 		Lang output object
	 * @param 	string 				$srctemplatepath 	Full path of source filename for generator using a template file
	 * @param	int					$hidedetails		Do not show line details
	 * @param	int					$hidedesc			Do not show desc
	 * @param	int					$hideref			Do not show ref
	 * @param 	FirePermit			$firepermit			FirePermit object
	 * @return	int                            			1 if OK, <=0 if KO
	 * @throws 	Exception
	 */
	public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $firepermit)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $action, $mysoc;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_firepermitdocument_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if ( ! is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));

		if ( ! is_object($outputlangs)) $outputlangs = $langs;
		$outputlangs->charset_output                 = 'UTF-8';

		$outputlangs->loadLangs(array("main", "dict", "companies", "digiriskdolibarr@digiriskdolibarr"));

		$mod = new $conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON($this->db);
		$ref = $mod->getNextValue($object);

		$object->ref = $ref;
		$id          = $object->create($user, true, $firepermit);

		$object->fetch($id);

		$dir                                             = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/firepermitdocument/' . $firepermit->ref;
		$objectref                                       = dol_sanitizeFileName($ref);
		if (preg_match('/specimen/i', $objectref)) $dir .= '/specimen';

		if ( ! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (file_exists($dir)) {
			$filename = preg_split('/firepermitdocument\//', $srctemplatepath);
			preg_replace('/template_/', '', $filename[1]);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $firepermit->ref . '_' . $objectref . '_' . $conf->global->MAIN_INFO_SOCIETE_NOM . '.odt';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);

			$object->last_main_doc = $filename;

			$sql  = "UPDATE " . MAIN_DB_PREFIX . "digiriskdolibarr_digiriskdocuments";
			$sql .= " SET last_main_doc =" . ( ! empty($filename) ? "'" . $this->db->escape($filename) . "'" : 'null');
			$sql .= " WHERE rowid = " . $object->id;

			dol_syslog("admin.lib::Insert last main doc", LOG_DEBUG);
			$this->db->query($sql);
			$file = $dir . '/' . $filename;

			dol_mkdir($conf->digiriskdolibarr->dir_temp);

			// Make substitution
			$substitutionarray = array();
			complete_substitutions_array($substitutionarray, $langs, $firepermit);
			// Call the ODTSubstitution hook
			$parameters = array('file' => $file, 'object' => $firepermit, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray);
			$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $firepermit may have been modified by some hooks

			// Open and load template
			require_once ODTPHP_PATH . 'odf.php';
			try {
				$odfHandler = new odf(
					$srctemplatepath,
					array(
						'PATH_TO_TMP'	  => $conf->digiriskdolibarr->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
					)
				);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_INFO);
				return -1;
			}

			// Define substitution array
			$substitutionarray            = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
			$array_object                 = $this->get_substitutionarray_object($object, $outputlangs);
			$array_soc                    = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
			$array_soc['mycompany_logo']  = preg_replace('/_small/', '_mini', $array_soc['mycompany_logo']);

			$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_object, $array_soc);
			complete_substitutions_array($tmparray, $outputlangs, $object);

			$digiriskelement    = new DigiriskElement($this->db);
			$resources          = new DigiriskResources($this->db);
			$signatory          = new FirePermitSignature($this->db);
			$societe            = new Societe($this->db);
			$firepermitline     = new FirePermitLine($this->db);
			$risk               = new Risk($this->db);
			$preventionplan     = new PreventionPlan($this->db);
			$preventionplanline = new PreventionPlanLine($this->db);
			$openinghours       = new Openinghours($this->db);
			$openinghoursFP     = new Openinghours($this->db);

			if ($firepermit->fk_preventionplan > 0) {
				$preventionplan->fetch($firepermit->fk_preventionplan);
				$preventionplanlines = $preventionplanline->fetchAll('', '', 0, 0, array(), 'AND', $preventionplan->id);
			}

			$firepermitlines = $firepermitline->fetchAll('', '', 0, 0, array(), 'AND', $firepermit->id);


			$digirisk_resources     = $resources->digirisk_dolibarr_fetch_resources();
			$extsociety             = $resources->fetchResourcesFromObject('FP_EXT_SOCIETY', $firepermit);
			if ($extsociety < 1) {
				$extsociety = new stdClass();
			}

			$maitreoeuvre           = $signatory->fetchSignatory('FP_MAITRE_OEUVRE', $firepermit->id, 'firepermit');
			$maitreoeuvre           = is_array($maitreoeuvre) ? array_shift($maitreoeuvre) : $maitreoeuvre;
			$extsocietyresponsible  = $signatory->fetchSignatory('FP_EXT_SOCIETY_RESPONSIBLE', $firepermit->id, 'firepermit');
			$extsocietyresponsible  = is_array($extsocietyresponsible) ? array_shift($extsocietyresponsible) : $extsocietyresponsible;
			$extsocietyintervenants = $signatory->fetchSignatory('FP_EXT_SOCIETY_INTERVENANTS', $firepermit->id, 'firepermit');

			$tmparray['titre_permis_feu']     = $firepermit->ref;
			$tmparray['raison_du_permis_feu'] = $firepermit->label;

			if ( ! empty($digirisk_resources)) {
				$societe->fetch($digirisk_resources['Pompiers']->id[0]);
				$tmparray['pompier_number'] = $societe->phone;

				$societe->fetch($digirisk_resources['SAMU']->id[0]);
				$tmparray['samu_number'] = $societe->phone;

				$societe->fetch($digirisk_resources['AllEmergencies']->id[0]);
				$tmparray['emergency_number'] = $societe->phone;

				$societe->fetch($digirisk_resources['Police']->id[0]);
				$tmparray['police_number'] = $societe->phone;
			}

			$tmparray['moyen_generaux_mis_disposition'] = $conf->global->DIGIRISKDOLIBARR_GENERAL_MEANS;
			$tmparray['consigne_generale']              = $conf->global->DIGIRISKDOLIBARR_GENERAL_RULES;
			$tmparray['premiers_secours']               = $conf->global->DIGIRISKDOLIBARR_FIRST_AID;

			$tmparray['titre_plan_prevention']  = $preventionplan->ref;
			$tmparray['raison_plan_prevention'] = $preventionplan->label;

			$tmparray['date_start_intervention_PPP'] = dol_print_date($preventionplan->date_start, 'dayhoursec');
			$tmparray['date_end_intervention_PPP']   = dol_print_date($preventionplan->date_end, 'dayhoursec');

			$morewhere  = ' AND element_id = ' . $preventionplan->id;
			$morewhere .= ' AND element_type = ' . "'" . $preventionplan->element . "'";
			$morewhere .= ' AND status = 1';

			$openinghours->fetch(0, '', $morewhere);

			$opening_hours_monday    = explode(' ', $openinghours->monday);
			$opening_hours_tuesday   = explode(' ', $openinghours->tuesday);
			$opening_hours_wednesday = explode(' ', $openinghours->wednesday);
			$opening_hours_thursday  = explode(' ', $openinghours->thursday);
			$opening_hours_friday    = explode(' ', $openinghours->friday);
			$opening_hours_saturday  = explode(' ', $openinghours->saturday);
			$opening_hours_sunday    = explode(' ', $openinghours->sunday);

			$tmparray['lundi_matin']    = $opening_hours_monday[0];
			$tmparray['lundi_aprem']    = $opening_hours_monday[1];
			$tmparray['mardi_matin']    = $opening_hours_tuesday[0];
			$tmparray['mardi_aprem']    = $opening_hours_tuesday[1];
			$tmparray['mercredi_matin'] = $opening_hours_wednesday[0];
			$tmparray['mercredi_aprem'] = $opening_hours_wednesday[1];
			$tmparray['jeudi_matin']    = $opening_hours_thursday[0];
			$tmparray['jeudi_aprem']    = $opening_hours_thursday[1];
			$tmparray['vendredi_matin'] = $opening_hours_friday[0];
			$tmparray['vendredi_aprem'] = $opening_hours_friday[1];
			$tmparray['samedi_matin']   = $opening_hours_saturday[0];
			$tmparray['samedi_aprem']   = $opening_hours_saturday[1];
			$tmparray['dimanche_matin'] = $opening_hours_sunday[0];
			$tmparray['dimanche_aprem'] = $opening_hours_sunday[1];

			if ( ! empty($preventionplanlines) && $preventionplanlines > 0 && is_array($preventionplanlines)) {
				$tmparray['interventions_info'] = count($preventionplanlines) . " " . $langs->trans('PreventionPlanLine');
			} else {
				$tmparray['interventions_info'] = 0;
			}

			$tmparray['date_start_intervention_FP'] = dol_print_date($firepermit->date_start, 'dayhoursec');
			$tmparray['date_end_intervention_FP']   = dol_print_date($firepermit->date_end, 'dayhoursec');

			$morewhere  = ' AND element_id = ' . $firepermit->id;
			$morewhere .= ' AND element_type = ' . "'" . $firepermit->element . "'";
			$morewhere .= ' AND status = 1';

			$openinghoursFP->fetch(0, '', $morewhere);

			$opening_hours_monday    = explode(' ', $openinghoursFP->monday);
			$opening_hours_tuesday   = explode(' ', $openinghoursFP->tuesday);
			$opening_hours_wednesday = explode(' ', $openinghoursFP->wednesday);
			$opening_hours_thursday  = explode(' ', $openinghoursFP->thursday);
			$opening_hours_friday    = explode(' ', $openinghoursFP->friday);
			$opening_hours_saturday  = explode(' ', $openinghoursFP->saturday);
			$opening_hours_sunday    = explode(' ', $openinghoursFP->sunday);

			$tmparray['lundi_matinF']    = $opening_hours_monday[0];
			$tmparray['lundi_apremF']    = $opening_hours_monday[1];
			$tmparray['mardi_matinF']    = $opening_hours_tuesday[0];
			$tmparray['mardi_apremF']    = $opening_hours_tuesday[1];
			$tmparray['mercredi_matinF'] = $opening_hours_wednesday[0];
			$tmparray['mercredi_apremF'] = $opening_hours_wednesday[1];
			$tmparray['jeudi_matinF']    = $opening_hours_thursday[0];
			$tmparray['jeudi_apremF']    = $opening_hours_thursday[1];
			$tmparray['vendredi_matinF'] = $opening_hours_friday[0];
			$tmparray['vendredi_apremF'] = $opening_hours_friday[1];
			$tmparray['samedi_matinF']   = $opening_hours_saturday[0];
			$tmparray['samedi_apremF']   = $opening_hours_saturday[1];
			$tmparray['dimanche_matinF'] = $opening_hours_sunday[0];
			$tmparray['dimanche_apremF'] = $opening_hours_sunday[1];

			if ( ! empty($firepermitlines) && $firepermitlines > 0 && is_array($firepermitlines)) {
				$tmparray['interventions_info_FP'] = count($firepermitlines) . " " . $langs->trans('FirePermitLine');
			} else {
				$tmparray['interventions_info_FP'] = 0;
			}

			//Informations entreprise extérieure

			if ( ! empty($extsociety) && $extsociety > 0) {
				$tmparray['society_title']    = $extsociety->name;
				$tmparray['society_siret_id'] = $extsociety->idprof2;
				$tmparray['society_address']  = $extsociety->address;
				$tmparray['society_postcode'] = $extsociety->zip;
				$tmparray['society_town']     = $extsociety->town;
			}

			if ( ! empty($extsocietyintervenants) && $extsocietyintervenants > 0 && is_array($extsocietyintervenants)) {
				$tmparray['intervenants_info'] = count($extsocietyintervenants);
			} else {
				$tmparray['intervenants_info'] = 0;
			}

			$tempdir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1] . '/temp/';

			//Signatures
			if ( ! empty($maitreoeuvre) && $maitreoeuvre > 0) {
				$tmparray['maitre_oeuvre_lname'] = $maitreoeuvre->lastname;
				$tmparray['maitre_oeuvre_fname'] = $maitreoeuvre->firstname;
				$tmparray['maitre_oeuvre_email'] = $maitreoeuvre->email;
				$tmparray['maitre_oeuvre_phone'] = $maitreoeuvre->phone;

				$tmparray['maitre_oeuvre_signature_date'] = dol_print_date($maitreoeuvre->signature_date, 'dayhoursec');
				$encoded_image                            = explode(",",  $maitreoeuvre->signature)[1];
				$decoded_image                            = base64_decode($encoded_image);
				file_put_contents($tempdir . "signature.png", $decoded_image);
				$tmparray['maitre_oeuvre_signature'] = $tempdir . "signature.png";
			}

			if ( ! empty($extsocietyresponsible) && $extsocietyresponsible > 0) {
				$tmparray['intervenant_exterieur_lname'] = $extsocietyresponsible->lastname;
				$tmparray['intervenant_exterieur_fname'] = $extsocietyresponsible->firstname;
				$tmparray['intervenant_exterieur_email'] = $extsocietyresponsible->email;
				$tmparray['intervenant_exterieur_phone'] = $extsocietyresponsible->phone;

				$tmparray['intervenant_exterieur_signature_date'] = dol_print_date($extsocietyresponsible->signature_date, 'dayhoursec');
				$encoded_image                                    = explode(",",  $extsocietyresponsible->signature)[1];
				$decoded_image                                    = base64_decode($encoded_image);
				file_put_contents($tempdir . "signature2.png", $decoded_image);
				$tmparray['intervenant_exterieur_signature'] = $tempdir . "signature2.png";
			}

			foreach ($tmparray as $key => $value) {
				try {
					if ($key == 'maitre_oeuvre_signature' || $key == 'intervenant_exterieur_signature') { // Image
						$list     = getimagesize($value);
						$newWidth = 350;
						if ($list[0]) {
							$ratio     = $newWidth / $list[0];
							$newHeight = $ratio * $list[1];
							dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
						}
						$odfHandler->setImage($key, $value);
					} elseif (preg_match('/logo$/', $key)) {
						if (file_exists($value)) $odfHandler->setImage($key, $value);
						else $odfHandler->setVars($key, $langs->transnoentities('ErrorFileNotFound'), true, 'UTF-8');
					} elseif (empty($value)) { // Text
						$odfHandler->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
					} else {
						$odfHandler->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
					}
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}
			// Replace tags of lines
			try {
				$foundtagforlines = 1;
				if ($foundtagforlines) {
					$listlines = $odfHandler->setSegment('interventions');
					if ( ! empty($preventionplanlines) && $preventionplanlines > 0) {
						foreach ($preventionplanlines as $line) {
							$digiriskelement->fetch($line->fk_element);

							$tmparray['key_unique']    = $line->ref;
							$tmparray['unite_travail'] = $digiriskelement->ref . " - " . $digiriskelement->label;
							$tmparray['action']        = $line->description;
							$tmparray['risk']          = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($line) . '.png';
							$tmparray['nomPicto']      = $risk->get_danger_category_name($line);
							$tmparray['prevention']    = $line->prevention_method;

							foreach ($tmparray as $key => $val) {
								try {
									if ($key == 'risk') {
										$listlines->setImage($key, $val);
									} elseif (empty($val)) {
										$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
									} else {
										$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
									}
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					} else {
						$tmparray['key_unique']    = '';
						$tmparray['unite_travail'] = '';
						$tmparray['action']        = '';
						$tmparray['risk']          = '';
						$tmparray['nomPicto']      = '';
						$tmparray['prevention']    = '';

						foreach ($tmparray as $key => $val) {
							try {
								if (empty($val)) {
									$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
								} else {
									$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								}
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listlines->merge();
						$odfHandler->mergeSegment($listlines);
					}

					$listlines = $odfHandler->setSegment('interventions_FP');
					if ( ! empty($firepermitlines) && $firepermitlines > 0) {
						foreach ($firepermitlines as $line) {
							$digiriskelement->fetch($line->fk_element);

							$tmparray['key_unique']      = $line->ref;
							$tmparray['unite_travail']   = $digiriskelement->ref . " - " . $digiriskelement->label;
							$tmparray['action']          = $line->description;
							$tmparray['type_de_travaux'] = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/typeDeTravaux/' . $risk->get_fire_permit_danger_category($line) . '.png';
							$tmparray['nomPictoT']       = $risk->get_fire_permit_danger_category_name($line);
							$tmparray['materiel']        = $line->use_equipment;

							foreach ($tmparray as $key => $val) {
								try {
									if ($key == 'type_de_travaux') {
										$listlines->setImage($key, $val);
									} elseif (empty($val)) {
										$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
									} else {
										$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
									}
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					} else {
						$tmparray['key_unique']      = '';
						$tmparray['unite_travail']   = '';
						$tmparray['action']          = '';
						$tmparray['type_de_travaux'] = '';
						$tmparray['nomPictoT']       = '';
						$tmparray['materiel']        = '';

						foreach ($tmparray as $key => $val) {
							try {
								if (empty($val)) {
									$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
								} else {
									$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								}
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listlines->merge();
						$odfHandler->mergeSegment($listlines);
					}

					$listlines = $odfHandler->setSegment('intervenants');
					if ( ! empty($extsocietyintervenants) && $extsocietyintervenants > 0) {
						$k         = 3;
						foreach ($extsocietyintervenants as $line) {
							if ($line->status == 5) {
								$encoded_image = explode(",", $line->signature)[1];
								$decoded_image = base64_decode($encoded_image);
								file_put_contents($tempdir . "signature" . $k . ".png", $decoded_image);
								$tmparray['intervenants_signature'] = $tempdir . "signature" . $k . ".png";
							} else {
								$tmparray['intervenants_signature'] = '';
							}
							$tmparray['id']       = $line->id;
							$tmparray['name']     = $line->firstname;
							$tmparray['lastname'] = $line->lastname;
							$tmparray['phone']    = $line->phone;
							$tmparray['mail']     = $line->email;
							$tmparray['status']   = $line->getLibStatut(1);

							$k++;

							foreach ($tmparray as $key => $value) {
								try {
									if ($key == 'intervenants_signature' && $line->status == 5) { // Image
										$list     = getimagesize($value);
										$newWidth = 200;
										if ($list[0]) {
											$ratio     = $newWidth / $list[0];
											$newHeight = $ratio * $list[1];
											dol_imageResizeOrCrop($value, 0, $newWidth, $newHeight);
										}
										$listlines->setImage($key, $value);
									} elseif (empty($value)) {  // Text
										$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
									} else {
										$listlines->setVars($key, html_entity_decode($value, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
									}
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();

							dol_delete_file($tempdir . "signature" . $k . ".png");
						}
						$odfHandler->mergeSegment($listlines);
					} else {
						$tmparray['id']                     = '';
						$tmparray['intervenants_signature'] = '';
						$tmparray['name']                   = '';
						$tmparray['lastname']               = '';
						$tmparray['phone']                  = '';
						$tmparray['mail']                   = '';
						$tmparray['status']                 = '';

						foreach ($tmparray as $key => $val) {
							try {
								if (empty($val)) {
									$listlines->setVars($key, $langs->trans('NoData'), true, 'UTF-8');
								} else {
									$listlines->setVars($key, html_entity_decode($val, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
								}
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listlines->merge();
						$odfHandler->mergeSegment($listlines);
					}
				}
			} catch (OdfException $e) {
				$this->error = $e->getMessage();
				dol_syslog($this->error, LOG_WARNING);
				return -1;
			}

			// Replace labels translated
			$tmparray = $outputlangs->get_translations_for_substitutions();
			foreach ($tmparray as $key => $value) {
				try {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}
			}

			// Call the beforeODTSave hook
			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

			// Write new file
			if ( ! empty($conf->global->MAIN_ODT_AS_PDF)) {
				try {
					$odfHandler->exportAsAttachedPDF($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			} else {
				try {
					$odfHandler->saveToDisk($file);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
			}

			$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
			$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

//			if ( ! empty($conf->global->MAIN_UMASK))
//				@chmod($file, octdec($conf->global->MAIN_UMASK));

			$odfHandler = null; // Destroy object

			dol_delete_file($tempdir . "signature.png");
			dol_delete_file($tempdir . "signature2.png");

			$this->result = array('fullpath' => $file);

			return 1; // Success
		} else {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}
	}
}
