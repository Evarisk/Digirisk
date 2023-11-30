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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/riskassessmentdocument/doc_riskassessmentdocument_odt.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to build ODT documents for digiriskdolibarr
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load DigiriskDolibarr libraries
require_once __DIR__ . '/../../../../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../../../../class/evaluator.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risksign.class.php';

// Load saturne libraries
require_once __DIR__ . '/../../../../../../saturne/core/modules/saturne/modules_saturne.php';

/**
 *	Class to build documents using ODF templates generator
 */
class doc_riskassessmentdocument_odt extends SaturneDocumentModel
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
	public string $document_type = 'riskassessmentdocument';

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
	 * @param Translate $langs Lang object to use for output.
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
		global $conf, $user;

		$objectDocument = $moreParam['objectDocument'];

		// Replace tags of lines
		try {
			$foundtagforlines = 1;
			if ($foundtagforlines) {
				$digiriskelementobject = new DigiriskElement($this->db);
				$risk                  = new Risk($this->db);
				$riskassessment        = new RiskAssessment($this->db);
				$ticket                = new Ticket($this->db);
				$category              = new Categorie($this->db);

				$digiriskelementlist   = $digiriskelementobject->fetchDigiriskElementFlat(0);
				$risks                 = $risk->fetchRisksOrderedByCotation(0, true, $conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);
				$riskAssessmentList    = $riskassessment->fetchAll('', '', 0, 0, ['customsql' => 'status = 1']);
				$riskList              = $risk->fetchAll('', '', 0, 0, array(), 'AND', $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

				if (is_array($digiriskelementlist) && !empty($digiriskelementlist)) {
					$listLines = $odfHandler->setSegment('elementParHierarchie');

					//Fill digirisk element list table
					foreach ($digiriskelementlist as $line) {
						$depthHyphens = '';
						for ($k = 0; $k < $line['depth']; $k++) {
							$depthHyphens .= '- ';
						}
						$tmpArray['nomElement'] = $depthHyphens . (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $line['object']->entity . ' - ' : '') . $line['object']->ref . ' ' . $line['object']->label;

						$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
					}
					$odfHandler->mergeSegment($listLines);

					//Fill total cotation by digirisk element table
					$totalQuotation = 0;
					$scale_counter = [
						1 => 0,
						2 => 0,
						3 => 0,
						4 => 0
					];
					$line           = '';
					$listLines      = $odfHandler->setSegment('risqueFiche');

					if (is_array($risks) && !empty($risks)) {
						foreach($risks as $riskSingle) {
							$risksOfDigiriskElements[$riskSingle->appliedOn][] = $riskSingle;
						}
					}

					foreach ($digiriskelementlist as $digiriskelementsingle) {
						$digiriskElementId = $digiriskelementsingle['object']->id;
						$risksOfDigiriskElement = $risksOfDigiriskElements[$digiriskElementId];

						if ($risksOfDigiriskElement > 0 && ! empty($risksOfDigiriskElement)) {
							foreach ($risksOfDigiriskElement as $riskOfDigiriskElement) {
								$lastEvaluation                     = $riskOfDigiriskElement->lastEvaluation;
								$totalQuotation                    += $lastEvaluation->cotation;
								$riskAssessmentsOfDigiriskElement[$digiriskElementId][] = $lastEvaluation;
							}
						}

						$elementName  = (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS) ? 'S' . $digiriskelementsingle['object']->entity . ' - ' : '') . $digiriskelementsingle['object']->ref . ' ' . $digiriskelementsingle['object']->label;
						$scaleCounter = $riskassessment->getRiskAssessmentCategoriesNumber($riskAssessmentsOfDigiriskElement[$digiriskElementId], $risksOfDigiriskElement, $digiriskElementId);

						$cotationarray[$elementName] = array($totalQuotation, $digiriskelementsingle['object']->description, $scaleCounter);

						$totalQuotation = 0;

						unset($tmpArray['object_fields']);
					}

					//use arsort to sort array according to value
					arsort($cotationarray);

					foreach ($cotationarray as $key => $val) {
						try {
							$listLines->setVars('nomElement', html_entity_decode($key, ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
							$listLines->setVars('quotationTotale', $val[0], true, 'UTF-8');
							$listLines->setVars('description', html_entity_decode($val[1], ENT_QUOTES | ENT_HTML5), true, 'UTF-8');
							$listLines->setVars('NbRiskBlack', $val[2][4], true, 'UTF-8');
							$listLines->setVars('NbRiskRed', $val[2][3], true, 'UTF-8');
							$listLines->setVars('NbRiskOrange', $val[2][2], true, 'UTF-8');
							$listLines->setVars('NbRiskGrey', $val[2][1], true, 'UTF-8');
						} catch (SegmentException $e) {
							dol_syslog($e->getMessage(), LOG_INFO);
						}
						$listLines->merge();
					}
					$odfHandler->mergeSegment($listLines);
				}

				//Fill risks data
				$objectDocument->fillRiskData($odfHandler, $objectDocument, $outputLangs, $tmpArray, $file, $risks, $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKS);

				//Fill tickets data
				$filter = array('t.fk_project' => $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT);
                $tickets = saturne_fetch_all_object_type('Ticket', '', '', 0, 0,  $filter);
				$listLines = $odfHandler->setSegment('tickets');
				if (is_array($tickets) && !empty($tickets)) {
					foreach ($tickets as $line) {
						$tmpArray['refticket']     = $line->ref;

						$categories = $category->containing($line->id, Categorie::TYPE_TICKET);
						if (!empty($categories)) {
							$allcategories = [];
							foreach ($categories as $cat) {
								$allcategories[] = $cat->label;
							}
							$tmpArray['categories'] = implode(', ', $allcategories);
						} else {
							$tmpArray['categories'] = '';
						}

						$tmpArray['creation_date'] = dol_print_date($line->datec, 'dayhoursec', 'tzuser');
						$tmpArray['subject']       = $line->subject;
						$tmpArray['message']       = $line->message;
						$tmpArray['progress']      = (($line->progress) ?: 0) . ' %';

						$tickettmp = new Ticket($this->db);
						$tickettmp->fetch($line->id);
						$tickettmp->fetch_optionals();
						$digiriskelementtmp = new DigiriskElement($this->db);
						$digiriskelementtmp->fetch($tickettmp->array_options['options_digiriskdolibarr_ticket_service']);
						$tmpArray['digiriskelement_ref_label'] = $digiriskelementtmp->ref . ' - ' . $digiriskelementtmp->label;

						$tmpArray['status'] = $tickettmp->getLibStatut();

						$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
					}
				} else {
					$tmpArray['refticket']                 = '';
					$tmpArray['categories']                = '';
					$tmpArray['creation_date']             = '';
					$tmpArray['subject']                   = '';
					$tmpArray['message']                   = '';
					$tmpArray['progress']                  = '';
					$tmpArray['digiriskelement_ref_label'] = '';
					$tmpArray['status']                    = '';
					$this->setTmpArrayVars($tmpArray, $listLines, $outputLangs);
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
     * Function to build a document on disk
     *
     * @param  SaturneDocuments $objectDocument  Object source to build document
     * @param  Translate        $outputLangs     Lang object to use for output
     * @param  string           $srcTemplatePath Full path of source filename for generator using a template file
     * @param  int              $hideDetails     Do not show line details
     * @param  int              $hideDesc        Do not show desc
     * @param  int              $hideRef         Do not show ref
     * @param  array            $moreParam       More param (Object/user/etc)
     * @return int                               1 if OK, <=0 if KO
     * @throws Exception
     */
    public function write_file(SaturneDocuments $objectDocument, Translate $outputLangs, string $srcTemplatePath, int $hideDetails = 0, int $hideDesc = 0, int $hideRef = 0, array $moreParam): int
    {
        global $conf, $mysoc;

        $fileArray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $objectDocument->element . '/siteplans', 'files', 0, '', '(\.odt|\.zip)', 'date', 'asc', 1);
        if (is_array($fileArray) && !empty($fileArray)) {
            $sitePlans                    = array_shift($fileArray);
            $thumb_name                   = saturne_get_thumb_name($sitePlans['name']);
            $tmpArray['photo_site_plans'] = $sitePlans['path'] . '/thumbs/' . $thumb_name;
        } else {
            $noPhoto                      = '/public/theme/common/nophoto.png';
            $tmpArray['photo_site_plans'] = DOL_DOCUMENT_ROOT . $noPhoto;
        }

        $arraySoc                             = $this->get_substitutionarray_mysoc($mysoc, $outputLangs);
        $tmpArray['mycompany_photo_fullsize'] = $arraySoc['mycompany_logo'];

        $objectDocument->DigiriskFillJSON();

        $previousObjectDocumentElement = $objectDocument->element;
        $objectDocument->element       = $objectDocument->element . '@digiriskdolibarr';
        complete_substitutions_array($tmpArray, $outputLangs, $objectDocument);
        $objectDocument->element = $previousObjectDocumentElement;

        $moreParam['tmparray']         = $tmpArray;
        $moreParam['objectDocument']   = $objectDocument;
        $moreParam['subDir']           = 'digiriskdolibarrdocuments/';
        $moreParam['hideTemplateName'] = 1;

        return parent::write_file($objectDocument, $outputLangs, $srcTemplatePath, $hideDetails, $hideDesc, $hideRef, $moreParam);
    }
}
