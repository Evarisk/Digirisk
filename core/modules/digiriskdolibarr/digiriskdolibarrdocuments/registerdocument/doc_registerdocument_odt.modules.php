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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/registerdocument/doc_registerdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/accident.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';
require_once __DIR__ . '/../../../../../../saturne/class/saturnesignature.class.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_registerdocument_odt extends SaturneDocumentModel
{
	/**
	 * @var string Module.
	 */
	public string $module = 'digiriskdolibarr';

	/**
	 * @var string Document type.
	 */
	public string $document_type = 'registerdocument';

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

        $ticket           = new Ticket($this->db);
        $accident         = new Accident($this->db);
        $digiriskElement  = new DigiriskElement($this->db);
        $accidentLesion   = new AccidentLesion($this->db);
        $accidentMetaData = new AccidentMetaData($this->db);
        $userTmp          = new User($this->db);
        $thirdparty       = new Societe($this->db);
        $signatory        = new SaturneSignature($this->db);

        // Replace tags of lines.
        try {
            $accidentList = $accident->fetchAll('', '', 0, 0, ['customsql' => 'fk_ticket > 0']);
            $tempDir     = $conf->$moduleNameLowerCase->multidir_output[$conf->entity ?? 1] . '/temp/';

            $accidentTicketIds = '';

            if (is_array($accidentList) && !empty($accidentList)) {
                foreach ($accidentList as $accidentSingle) {
                    $accidentTicketIds .= $accidentSingle->fk_ticket . ', ';
                }
                $accidentTicketIds = rtrim($accidentTicketIds, ', ');
            }

            // Get register first tab data.
            $foundTagForLines = 1;
            try {
                $listLines = $odfHandler->setSegment('registers');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template.
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (is_array($accidentList) && !empty($accidentList)) {
                    foreach($accidentList as $accidentSingle) {
                        $ticket->fetch($accidentSingle->fk_ticket);
                        $digiriskElement->fetch($ticket->array_options['options_digiriskdolibarr_ticket_service']);
                        $accidentLesions       = $accidentLesion->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $accidentSingle->id]);
                        $accidentMetaData->fetch(0, '', ' AND fk_accident = ' . $accidentSingle->id . ' AND status = 1');
                        $userTmp->fetch($accidentMetaData->fk_user_witness);
                        $thirdparty->fetch($accidentMetaData->fk_soc_responsible);

                        $tmpArray['register_name'] = $ticket->ref;
                        $tmpArray['register_date'] = dol_print_date($ticket->datec, 'day');
                        $tmpArray['register_fullname'] = $ticket->array_options['options_digiriskdolibarr_ticket_lastname'] . ' ' . $ticket->array_options['options_digiriskdolibarr_ticket_firstname'];
                        $tmpArray['register_datehour'] = dol_print_date($ticket->array_options['options_digiriskdolibarr_ticket_date'], 'day');
                        $tmpArray['register_location'] = $digiriskElement->ref . ' ' . $digiriskElement->label;
                        $tmpArray['register_circumstances'] = $accidentSingle->description;

                        $lesionNatures = '';
                        $lesionLocation = '';
                        if (is_array($accidentLesions) && !empty($accidentLesions)) {
                            foreach ($accidentLesions as $accidentLinkedLesion) {
                                $lesionNatures .= $langs->trans($accidentLinkedLesion->lesion_nature) . ' ,';
                                $lesionLocation .= $langs->trans($accidentLinkedLesion->lesion_localization) . ' ,';
                            }
                        }
                        $tmpArray['register_lesion_location'] = rtrim($lesionLocation, ',');
                        $tmpArray['register_lesion_nature'] = rtrim($lesionNatures, ',');

                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);

                    }
                }
                $odfHandler->mergeSegment($listLines);
            }

            // Get register second tab data.
            try {
                $listLines = $odfHandler->setSegment('registers2');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template.
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (is_array($accidentList) && !empty($accidentList)) {
                    foreach ($accidentList as $accidentSingle) {
                        $ticket->fetch($accidentSingle->fk_ticket);
                        $tmpArray['register_name'] = $ticket->ref;
                        $digiriskElement->fetch($ticket->array_options['options_digiriskdolibarr_ticket_service']);
                        $accidentAttendants = $signatory->fetchSignatory('', $accidentSingle->id, 'accident');
                        $accidentMetaData->fetch(
                            0,
                            '',
                            ' AND fk_accident = ' . $accidentSingle->id . ' AND status = 1'
                        );
                        $userTmp->fetch($accidentMetaData->fk_user_witness);
                        $thirdparty->fetch($accidentMetaData->fk_soc_responsible);

                        $victimArray = $accidentAttendants['Victim'];
                        $careGiverArray = $accidentAttendants['Caregiver'];

                        if (is_array($victimArray) && !empty($victimArray)) {
                            $victimData = array_shift($victimArray);
                            $encodedImage = explode(',', $victimData->signature)[1];
                            $decodedImage = base64_decode($encodedImage);
                            file_put_contents($tempDir . 'signature' . $victimData->id . '.png', $decodedImage);
                            $tmpArray['register_victim_signature'] = $tempDir . 'signature' . $victimData->id . '.png';
                        } else {
                            $tmpArray['register_victim_signature'] = '';
                        }
                        if (is_array($careGiverArray) && !empty($careGiverArray)) {
                            $careGiverData = array_shift($careGiverArray);
                            $tmpArray['register_caregiver_fullname'] = dol_strtoupper($careGiverData->lastname) . ' ' . ucfirst($careGiverData->firstname);
                            $encodedImage = explode(',', $careGiverData->signature)[1];
                            $decodedImage = base64_decode($encodedImage);
                            file_put_contents($tempDir . 'signature' . $careGiverData->id . '.png', $decodedImage);
                            $tmpArray['register_caregiver_signature'] = $tempDir . 'signature' . $careGiverData->id . '.png';
                        } else {
                            $tmpArray['register_caregiver_fullname'] = '';
                            $tmpArray['register_caregiver_signature'] = '';
                        }

                        $tmpArray['register_witnesses_data'] = $userTmp->getFullName($langs);
                        $tmpArray['register_external_society_implied'] = $thirdparty->getFullName($langs);

                        $tmpArray['register_note'] = $ticket->note_public;

                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                    }
                }
                $odfHandler->mergeSegment($listLines);
            }

            // Get register second tab data.
            try {
                $listLines = $odfHandler->setSegment('caregivers');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template.
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (is_array($accidentList) && !empty($accidentList)) {
                    foreach($accidentList as $accidentSingle) {
                        $accidentCaregivers = $signatory->fetchSignatory('Caregiver', $accidentSingle->id, 'accident');

                        if (is_array($accidentCaregivers) && !empty($accidentCaregivers)) {
                            foreach($accidentCaregivers as $accidentCaregiver) {
                                $tmpArray['caregiver_id'] = $accidentCaregiver->id;
                                $tmpArray['caregiver_lastname'] = $accidentCaregiver->lastname;
                                $tmpArray['caregiver_firstname'] = $accidentCaregiver->firstname;
                                if ($accidentCaregiver->object_type == 'user') {
                                    $userTmp->fetch($accidentCaregiver->fk_object);
                                    $tmpArray['caregiver_qualification'] = $userTmp->job;
                                } else {
                                    $tmpArray['caregiver_qualification'] = '';
                                }
                                $encodedImage = explode(',', $accidentCaregiver->signature)[1];
                                $decodedImage = base64_decode($encodedImage);
                                file_put_contents($tempDir . 'signature' . $accidentCaregiver->id . '.png', $decodedImage);
                                $tmpArray['caregiver_signature'] = $tempDir . 'signature' . $accidentCaregiver->id . '.png';

                                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                            }
                        } else {
                            $tmpArray['caregiver_id'] = '';
                            $tmpArray['caregiver_lastname'] = '';
                            $tmpArray['caregiver_firstname'] = '';
                            $tmpArray['caregiver_qualification'] = '';
                            $tmpArray['caregiver_signature'] = '';
                            $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                        }
                    }
                } else {
                    $tmpArray['caregiver_id'] = '';
                    $tmpArray['caregiver_lastname'] = '';
                    $tmpArray['caregiver_firstname'] = '';
                    $tmpArray['caregiver_qualification'] = '';
                    $tmpArray['caregiver_signature'] = '';
                    $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                }
                $odfHandler->mergeSegment($listLines);
            }

            // Get register controllers.
            try {
                $listLines = $odfHandler->setSegment('controllers');
            } catch (OdfException|OdfExceptionSegmentNotFound $e) {
                // We may arrive here if tags for lines not present into template.
                $foundTagForLines = 0;
                $listLines = '';
                dol_syslog($e->getMessage());
            }

            if ($foundTagForLines) {
                if (isModEnabled('digiquali')) {
                    require_once __DIR__ . '/../../../../../../digiquali/class/control.class.php';
                    $control = new Control($this->db);
                    $ticketControls = $control->fetchAllWithLeftJoin('DESC','t.control_date', 0,0, ['customsql' => 't.rowid = je.fk_target AND t.status = ' . $control::STATUS_LOCKED], 'AND', true, 'LEFT JOIN '. MAIN_DB_PREFIX .'element_element as je on je.sourcetype = "ticket" AND je.fk_source IN ('. $accidentTicketIds .') AND je.targettype = "digiquali_control" AND je.fk_target = t.rowid' );

                    if (is_array($ticketControls) && !empty($ticketControls)) {
                        foreach($ticketControls as $ticketControl) {
                            $ticketController = $signatory->fetchSignatory('Controller', $ticketControl->id, 'control');

                            if (is_array($ticketController) && !empty($ticketController)) {
                                $ticketController = array_shift($ticketController);
                                $tmpArray['register_controller_id'] = $ticketController->id;
                                $tmpArray['register_controller_lastname'] = $ticketController->lastname;
                                $tmpArray['register_controller_firstname'] = $ticketController->firstname;
                                $tmpArray['register_controller_society'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
                                $tmpArray['register_controller_date'] = dol_print_date($ticketControl->control_date);
                                $encodedImage = explode(',', $ticketController->signature)[1];
                                $decodedImage = base64_decode($encodedImage);
                                file_put_contents($tempDir . 'signature' . $ticketController->id . '.png', $decodedImage);
                                $tmpArray['register_controller_signature'] = $tempDir . 'signature' . $ticketController->id . '.png';

                                $tmpArray['register_controller_note'] = $ticketControl->note_public;

                                $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                            }
                        }
                    } else {
                        $tmpArray['register_controller_id'] = '';
                        $tmpArray['register_controller_lastname'] = '';
                        $tmpArray['register_controller_firstname'] = '';
                        $tmpArray['register_controller_society'] = '';
                        $tmpArray['register_controller_date'] = '';
                        $tmpArray['register_controller_signature'] = '';
                        $tmpArray['register_controller_note'] = '';
                        $this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
                    }
                }
                $odfHandler->mergeSegment($listLines);
            }

        } catch (OdfException $e) {
            $this->error = $e->getMessage();
            dol_syslog($this->error, LOG_WARNING);
            return -1;
        }
        return 0;
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
        global $langs;

        $evaluator = new Evaluator($this->db);

		$tmpArray = [];
        $tmpArray['register_name'] = $langs->trans('RegisterDocument');

		$moreParam['tmparray']         = $tmpArray;
		$moreParam['hideTemplateName'] = 1;

        $arrayNbEmployees = $evaluator->getNbEmployees();

        $tmpArray['company_nb_employees'] = array_shift($arrayNbEmployees);

        $moreParam['tmparray'] = $tmpArray;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
	}
}
