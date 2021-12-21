<?php
/* Copyright (C) 2004-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/core/boxes/mymodulewidget1.php
 * \ingroup mymodule
 * \brief   Widget provided by MyModule
 *
 * Put detailed description here.
 */

include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

require_once __DIR__ . '/../../class/digiriskdocuments/riskassessmentdocument.class.php';

/**
 * Class to manage the box
 *
 * Warning: for the box to be detected correctly by dolibarr,
 * the filename should be the lowercase classname
 */
class box_riskassessmentdocument extends ModeleBoxes
{
	/**
	 * @var string Alphanumeric ID. Populated by the constructor.
	 */
	public $boxcode = "riskassessmentdocumentbox";

	/**
	 * @var string Box icon (in configuration page)
	 * Automatically calls the icon named with the corresponding "object_" prefix
	 */
	public $boximg = "digiriskdolibarr@digiriskdolibarr";

	/**
	 * @var string Box label (in configuration page)
	 */
	public $boxlabel;

	/**
	 * @var string[] Module dependencies
	 */
	public $depends = array();

	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var mixed More parameters
	 */
	public $param;

	/**
	 * @var array Header informations. Usually created at runtime by loadBox().
	 */
	public $info_box_head = array();

	/**
	 * @var array Contents informations. Usually created at runtime by loadBox().
	 */
	public $info_box_contents = array();

	/**
	 * @var string    Widget type ('graph' means the widget is a graph widget)
	 */
	public $widgettype = '';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct(DoliDB $db, $param = '')
	{
		global $langs;
		$langs->load("boxes");
		$langs->load('dgiriskdolibarr@dgiriskdolibarr');

		parent::__construct($db, $param);

		$this->boxlabel = $langs->transnoentitiesnoconv("RiskAssessmentDocumentBox");

		$this->param = $param;
	}

	/**
	 * Load data into info_box_contents array to show array later. Called by Dolibarr before displaying the box.
	 *
	 * @param int $max Maximum number of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $langs;

		// Use configuration value for max lines count
		$this->max = $max;

		$riskassessmentdocument = new RiskAssessmentDocument($this->db);

		$filter = array('customsql' => "t.type='riskassessmentdocument'");
		$riskassessmentdocument = $riskassessmentdocument->fetchAll('desc','t.rowid', 1, 0, $filter, 'AND');
		if (!empty($riskassessmentdocument)){
			$riskassessmentdocument = array_shift($riskassessmentdocument);
			$ref = dol_sanitizeFileName($riskassessmentdocument->ref);
		}

		// Populate the head at runtime
		$text = $langs->trans("RiskAssessmentDocumentBoxTitle", $max);
		$this->info_box_head = array(
			// Title text
			'text' => $text,
			// Add a link
			'sublink' => '',
			// Sublink icon placed after the text
			'subpicto' => '',
			// Sublink icon HTML alt text
			'subtext' => '',
			// Sublink HTML target
			'target' => '',
			// HTML class attached to the picto and link
			'subclass' => '',
			// Limit and truncate with "…" the displayed text lenght, 0 = disabled
			'limit' => 0,
			// Adds translated " (Graph)" to a hidden form value's input (?)
			'graph' => false
		);

		// Populate the contents at runtime
		$this->info_box_contents = array(
			0 => array( // First line
				0 => array( // First Column
					//  HTML properties of the TR element. Only available on the first column.
					'tr' => 'class="left"',
					// HTML properties of the TD element
					'td' => '',

					// Main text for content of cell
					'text' => $langs->transnoentities('LastGenerateDate'). ' : ' . dol_print_date($riskassessmentdocument->date_creation),
					// Link on 'text' and 'logo' elements
					'url' => '',
					// Link's target HTML property
					'target' => '',
					// Fist line logo (deprecated. Include instead logo html code into text or text2, and set asis property to true to avoid HTML cleaning)
					//'logo' => 'monmodule@monmodule',
					// Unformatted text, added after text. Usefull to add/load javascript code
					'textnoformat' => '',

					// Main text for content of cell (other method)
					//'text2' => '<p><strong>Another text</strong></p>',

					// Truncates 'text' element to the specified character length, 0 = disabled
					'maxlength' => 0,
					// Prevents HTML cleaning (and truncation)
					'asis' => false,
					// Same for 'text2'
					'asis2' => true
				),
				1 => array( // Another column
					// No TR for n≠0
					'td' => '',
					'text' => $langs->transnoentities('MandatoryUpdateEvery'). ' 365 ' . $langs->transnoentities('Days'),
				)
			),
//			1 => array( // Another line
//				0 => array( // TR
//					'tr' => 'class="left"',
//					'text' => 'Another line'
//				),
//				1 => array( // TR
//					'tr' => 'class="left"',
//					'text' => ''
//				)
//			),
//			2 => array( // Another line
//				0 => array( // TR
//					'tr' => 'class="left"',
//					'text' => ''
//				),
//				1 => array( // TR
//					'tr' => 'class="left"',
//					'text' => ''
//				)
//			),
		);
	}

	/**
	 * Method to show box. Called by Dolibarr eatch time it wants to display the box.
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @param int $nooutput No print, only return string
	 * @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		// You may make your own code here…
		// … or use the parent's class function using the provided head and contents templates
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
