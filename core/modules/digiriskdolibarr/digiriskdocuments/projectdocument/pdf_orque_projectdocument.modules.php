<?php
/* Copyright (C) 2022-2023 EVARISK <dev@evarisk.com>
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
 *	\file       core/modules/digiriskdolibarr/digiriskdocuments/projectdocument/pdf_orque_projectdocument.modules.php
 *	\ingroup    digiriskdolibarr
 *	\brief      File of class to generate project document orque
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

require_once __DIR__ . '/mod_projectdocument_standard.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../../../../class/riskanalysis/riskassessment.class.php';

/**
 *	Class to manage generation of project document Orque
 */
class pdf_orque_projectdocument
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int     Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Page orientation
	 * @var string 'P' or 'Portait' (default), 'L' or 'Landscape'
	 */
	private $orientation = '';

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array('main', 'projects', 'companies'));

		$this->db = $db;
		$this->name = 'orque';
		$this->description = $langs->trans('DocumentModelOrque');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->orientation = 'L';
		if ($this->orientation == 'L' || $this->orientation == 'Landscape') {
			$this->page_largeur = $formatarray['height'];
			$this->page_hauteur = $formatarray['width'];
		} else {
			$this->page_largeur = $formatarray['width'];
			$this->page_hauteur = $formatarray['height'];
		}
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo FAC_PDF_LOGO
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1; // Display product-service code

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}

		// Define position of columns
		if ($this->orientation == 'L' || $this->orientation == 'Landscape') {
			$this->posxref = $this->marge_gauche + 1;
			$this->posxrisk = $this->marge_gauche + 25;
			$this->posxriskassessment = $this->marge_gauche + 40;
			$this->posxlabel = $this->marge_gauche + 60;
			$this->posxbudget = $this->marge_gauche + 170;
			$this->posxworkload = $this->marge_gauche + 190;
			$this->posxprogress = $this->marge_gauche + 210;
			$this->posxdatestart = $this->marge_gauche + 230;
			$this->posxdateend = $this->marge_gauche + 250;
		} else {
			$this->posxref = $this->marge_gauche + 1;
			$this->posxrisk = $this->marge_gauche + 25;
			$this->posxriskassessment = $this->marge_gauche + 40;
			$this->posxlabel = $this->marge_gauche + 60;
			$this->posxbudget = $this->marge_gauche + 120;
			$this->posxworkload = $this->marge_gauche + 130;
			$this->posxprogress = $this->marge_gauche + 140;
			$this->posxdatestart = $this->marge_gauche + 150;
			$this->posxdateend = $this->marge_gauche + 180;
		}
		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxref -= 20;
			$this->posxrisk -= 20;
			$this->posxriskassessment -= 20;
			$this->posxlabel -= 20;
			$this->posxbudget -= 20;
			$this->posxworkload -= 20;
			$this->posxprogress -= 20;
			$this->posxdatestart -= 20;
			$this->posxdateend -= 20;
		}
	}

	/**
	 *  Function to build a document on disk using the generic pdf module.
	 *
	 * 	@param	ProjectDocument $objectDocument		Object source to build document
	 * 	@param	Translate		$outputlangs		Lang output object
	 * 	@param 	string 			$srctemplatepath 	Full path of source filename for generator using a template file
	 * 	@param	int				$hidedetails		Do not show line details
	 * 	@param	int				$hidedesc			Do not show desc
	 * 	@param	int				$hideref			Do not show ref
	 * 	@param	array			$moreparams			Array to provide more information
	 *	@return	int         						1 if OK, <=0 if KO
	 * 	@throws Exception
	 */
	public function write_file(ProjectDocument $objectDocument, Translate $outputlangs, string $srctemplatepath, int $hidedetails, int $hidedesc, int $hideref, array $moreparams): int
	{
		global $conf, $hookmanager, $langs, $user;

		$object = $moreparams['object'];

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array('main', 'dict', 'companies', 'projects'));

		if ($conf->projet->dir_output) {
			$mod = new $conf->global->DIGIRISKDOLIBARR_PROJECTDOCUMENT_ADDON($this->db);
			$ref = $mod->getNextValue($objectDocument);

			$objectDocument->ref = $ref;
			$id = $objectDocument->create($user, true, $object);
			$objectDocument->fetch($id);

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->projet->dir_output;
			if (!preg_match('/specimen/i', $objectref)) {
				$dir .= '/' . $objectref;
			}
			$societyname = preg_replace('/\./', '_', $conf->global->MAIN_INFO_SOCIETE_NOM);

			$date     = dol_print_date(dol_now(), 'dayxcard');
			$filename = $date . '_' . $objectDocument->ref . '_' . $objectref . '_' .  $societyname . '.pdf';
			$filename = str_replace(' ', '_', $filename);
			$filename = dol_sanitizeFileName($filename);
			$filename = preg_replace('/[’‘‹›‚]/u', '', $filename);

			$objectDocument->last_main_doc = $filename;

			$sql  = 'UPDATE ' . MAIN_DB_PREFIX . 'digiriskdolibarr_digiriskdocuments';
			$sql .= ' SET last_main_doc =' . ( ! empty($filename) ? "'" . $this->db->escape($filename) . "'" : 'null');
			$sql .= ' WHERE rowid = ' . $objectDocument->id;

			dol_syslog('admin.lib::Insert last main doc', LOG_DEBUG);
			$this->db->query($sql);

			$file = $dir . '/' . $filename;
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				$heightforinfotot = 40; // Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				// Complete object by loading several other informations
				$task           = new Task($this->db);
				$extrafields    = new ExtraFields($this->db);
				$risk           = new Risk($this->db);
				$riskassessment = new RiskAssessment($this->db);

				$extrafields->fetch_name_optionals_label($task->table_element);

				$tasksarray = $task->getTasksArray(0, 0, $object->id, 0, 0, '', '-1', '', 0, 0, $extrafields);

				if (!$object->id > 0) {  // Special case when used with object = specimen, we may return all lines
					$tasksarray = array_slice($tasksarray, 0, min(5, count($tasksarray)));
				}

				$object->lines = $tasksarray;

				$nblines = count($object->lines);

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities('Project'));
				$pdf->SetCreator('Dolibarr ' .DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref). ' ' .$outputlangs->transnoentities('Project'));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage($this->orientation);
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 50;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				// Show public note
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if ($notetoshow) {
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$tab_top -= 2;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxref - 1, $tab_top - 2, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 2, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 2);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				$heightoftitleline = 10;
				$iniY = $tab_top + $heightoftitleline + 1;
				$curY = $tab_top + $heightoftitleline + 1;
				$nexY = $tab_top + $heightoftitleline + 1;

				// Sort the info by descending order of cotation
				$objectDoc = array();
				for ($i = 0; $i < $nblines; $i++) {
					$risk->fetch($object->lines[$i]->options_fk_risk);
					$lastEvaluation = $riskassessment->fetchFromParent($risk->id, 1);
					if ($lastEvaluation > 0 && !empty($lastEvaluation) && is_array($lastEvaluation)) {
						$lastEvaluation = array_shift($lastEvaluation);
					}

					$tmpArray = array("cotation" => empty($lastEvaluation->cotation) ? 0 : $lastEvaluation->cotation);
					$tmpArray += array("task_ref" => $object->lines[$i]->ref);
					$tmpArray += array("risk_ref" => $risk->ref);
					$tmpArray += array("label" => $object->lines[$i]->label);
					$tmpArray += array("budget" => $object->lines[$i]->budget_amount);
					$tmpArray += array("progress" => $object->lines[$i]->progress ? $object->lines[$i]->progress . '%' : '');
					$tmpArray += array("date_start" => $object->lines[$i]->date_start);
					$tmpArray += array("date_end" => $object->lines[$i]->date_end);
					$tmpArray += array("workload" =>  $object->lines[$i]->planned_workload);
					array_push($objectDoc, $tmpArray);
				}
				usort($objectDoc, function($a, $b) {
					return $b['cotation'] <=> $a['cotation'];
				});

				// Loop on each lines

				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation($this->orientation, 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					// Description of line
					$ref = $objectDoc[$i]['task_ref'];
					$libelleline = $objectDoc[$i]['label'];
					$riskref = $objectDoc[$i]['risk_ref'];
					$lastEvaluation = $objectDoc[$i]['cotation'];
					$budget = price($objectDoc[$i]['budget'], 0, $langs, 1, 0, 0, $conf->currency);
					$progress = $objectDoc[$i]['progress'];
					$datestart = dol_print_date($objectDoc[$i]['date_start'], 'day');
					$dateend = dol_print_date($objectDoc[$i]['date_end'], 'day');
					$planned_workload = convertSecondToTime((int) $objectDoc[$i]['workload'], 'allhourmin');

					$showpricebeforepagebreak = 1;

					$pdf->startTransaction();
					// Label
					$pdf->SetXY($this->posxlabel, $curY);
					$pdf->MultiCell($this->posxbudget - $this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
						// Label
						$pdf->SetXY($this->posxlabel, $curY);
						$posybefore = $pdf->GetY();
						$pdf->MultiCell($this->posxworkload - $this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage($this->orientation, '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break

							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}

							$forcedesconsamepage = 1;
							if ($forcedesconsamepage) {
								$pdf->rollbackTransaction(true);
								$pageposafter = $pageposbefore;
								$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
								$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut
								$pdf->MultiCell(0, 3, ''); // Set interline to 3
								$pdf->SetTextColor(0, 0, 0);

								$pdf->setPageOrientation($this->orientation, 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
								$curY = $tab_top_newpage + $heightoftitleline + 1;

								// Label
								$pdf->SetXY($this->posxlabel, $curY);
								$posybefore = $pdf->GetY();
								$pdf->MultiCell($this->posxbudget - $this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
								$pageposafter = $pdf->getPage();
								$posyafter = $pdf->GetY();
							}
						}
						//var_dump($i.' '.$posybefore.' '.$posyafter.' '.($this->page_hauteur -  ($heightforfooter + $heightforfreetext + $heightforinfotot)).' '.$showpricebeforepagebreak);
					} else // No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription = $pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation($this->orientation, 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						//var_dump($pageposbefore.'-'.$pageposafter.'-'.$showpricebeforepagebreak);
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage + $heightoftitleline + 1;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// Ref of task
					$pdf->SetXY($this->posxref, $curY);
					$pdf->MultiCell($this->posxrisk - $this->posxref, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');
					// Risk
					$pdf->SetXY($this->posxrisk, $curY);
					$pdf->MultiCell($this->posxriskassessment - $this->posxrisk, 3, $riskref, 0, 'L');
					// Risk assessment
					$pdf->SetXY($this->posxriskassessment, $curY);
					$pdf->MultiCell($this->posxlabel - $this->posxriskassessment, 3, $lastEvaluation, 0, 'C');
					// task budget
					$pdf->SetXY($this->posxbudget, $curY);
					$pdf->MultiCell($this->posxworkload - $this->posxbudget, 3, $budget, 0, 'R');
					// Workload
					$pdf->SetXY($this->posxworkload, $curY);
					$pdf->SetFont('', '', $default_font_size - 2); // We use a smaller font
					$pdf->MultiCell($this->posxprogress - $this->posxworkload, 3, $planned_workload ? $planned_workload : '', 0, 'R');
					// Progress
					$pdf->SetXY($this->posxprogress, $curY);
					$pdf->MultiCell($this->posxdatestart - $this->posxprogress, 3, $progress, 0, 'R');
					$pdf->SetFont('', '', $default_font_size - 1); // We restore font

					// Date start and end
					$pdf->SetXY($this->posxdatestart, $curY);
					$pdf->MultiCell($this->posxdateend - $this->posxdatestart, 3, $datestart, 0, 'C');
					$pdf->SetXY($this->posxdateend, $curY);
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxdateend, 3, $dateend, 0, 'C');

					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY += 2; // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage($this->orientation);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Footer of the page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			} else {
				$this->error = $langs->transnoentities('ErrorCanNotCreateDir', $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities('ErrorConstantNotDefined', 'PROJECT_OUTPUTDIR');
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		global $conf, $mysoc;

		$heightoftitleline = 10;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128, 128, 128);

		// Draw rect of all tab (title + lines). Rect takes a length in 3rd parameter
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);

		// Line takes a position y in 3rd parameter
		$pdf->line($this->marge_gauche, $tab_top + $heightoftitleline, $this->page_largeur - $this->marge_droite, $tab_top + $heightoftitleline);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size);

		$pdf->SetXY($this->posxref, $tab_top + 1);
		$pdf->MultiCell($this->posxrisk - $this->posxref, 3, $outputlangs->transnoentities('Tasks'), '', 'L');

		$pdf->SetXY($this->posxrisk, $tab_top + 1);
		$pdf->MultiCell($this->posxriskassessment - $this->posxrisk, 3, $outputlangs->transnoentities('Risk'), '', 'L');

		$pdf->SetXY($this->posxriskassessment, $tab_top + 1);
		$pdf->MultiCell($this->posxlabel - $this->posxriskassessment, 3, $outputlangs->transnoentities('RiskAssessment'), '', 'L');

		$pdf->SetXY($this->posxlabel, $tab_top + 1);
		$pdf->MultiCell($this->posxbudget - $this->posxlabel, 3, $outputlangs->transnoentities('Description'), 0, 'L');

		$pdf->SetXY($this->posxbudget, $tab_top + 1);
		$pdf->MultiCell($this->posxworkload - $this->posxbudget, 3, $outputlangs->transnoentities('Budget'), 0, 'R');

		$pdf->SetXY($this->posxworkload, $tab_top + 1);
		$pdf->MultiCell($this->posxprogress - $this->posxworkload, 3, $outputlangs->transnoentities('PlannedWorkloadShort'), 0, 'R');

		$pdf->SetXY($this->posxprogress, $tab_top + 1);
		$pdf->MultiCell($this->posxdatestart - $this->posxprogress, 3, '%', 0, 'R');

		// Date start
		$pdf->SetXY($this->posxdatestart, $tab_top + 1);
		$pdf->MultiCell($this->posxdateend - $this->posxdatestart, 3, $outputlangs->trans('Start'), 0, 'C');

		// Date end
		$pdf->SetXY($this->posxdateend, $tab_top + 1);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxdateend, 3, $outputlangs->trans('End'), 0, 'C');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Project		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx = $this->page_largeur - $this->marge_droite - 100;
		$posy = $this->marge_haute;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorLogoFileNotFound', $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities('ErrorGoToModuleSetup'), 0, 'L');
			}
		} else {
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities($this->emetteur->name), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities('Project'). ' ' .$outputlangs->convToOutputCharset($object->ref), '', 'R');
		$pdf->SetFont('', '', $default_font_size + 2);

		$posy += 6;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities('DateStart'). ' : ' .dol_print_date($object->date_start, 'day', false, $outputlangs, true), '', 'R');

		if ($object->date_end) {
			$posy += 6;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities('DateEnd'). ' : ' .dol_print_date($object->date_end, 'day', false, $outputlangs, true), '', 'R');
		}

		if (is_object($object->thirdparty)) {
			$posy += 6;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities('ThirdParty'). ' : ' .$object->thirdparty->getFullName($outputlangs), '', 'R');
		}

		$pdf->SetTextColor(0, 0, 60);

		// Add list of linked objects
		/* Removed: A project can have more than thousands linked objects (orders, invoices, proposals, etc....
		$object->fetchObjectLinked();

		foreach($object->linkedObjects as $objecttype => $objects)
		{
			var_dump($objects);exit;
			if ($objecttype == 'commande')
			{
				$outputlangs->load('orders');
				$num=count($objects);
				for ($i=0;$i<$num;$i++)
				{
					$posy+=4;
					$pdf->SetXY($posx,$posy);
					$pdf->SetFont('','', $default_font_size - 1);
					$text=$objects[$i]->ref;
					if ($objects[$i]->ref_client) $text.=' ('.$objects[$i]->ref_client.')';
					$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
				}
			}
		}
		*/
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf     			PDF
	 *  @param	Project		$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return    int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'PROJECT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
