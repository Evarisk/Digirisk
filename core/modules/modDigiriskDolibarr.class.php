<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2020 Eoxia <dev@eoxia.com>
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
 * 	\defgroup   digiriskdolibarr     Module DigiriskDolibarr
 *  \brief      DigiriskDolibarr module descriptor.
 *
 *  \file       htdocs/custom/digiriskdolibarr/core/modules/modDigiriskDolibarr.class.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Description and activation file for module DigiriskDolibarr
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DigiriskDolibarr
 */
class modDigiriskdolibarr extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 436302; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'digiriskdolibarr';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family          = "interface";
		$this->module_position = '90';
		$this->name            = preg_replace('/^mod/i', '', get_class($this));
		$this->description     = $langs->trans('DigiriskDolibarrdDescription');
		$this->descriptionlong = "DigiriskDolibarr";
		$this->editor_name     = 'Evarisk';
		$this->editor_url      = 'https://evarisk.com';
		$this->version         = '1.0.0';
		$this->const_name      = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto           ='digiriskdolibarr@digiriskdolibarr';

		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 1,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
//				'/digiriskdolibarr/css/digiriskdolibarr.css.php',
				'/digiriskdolibarr/css/digiriskdolibarr.css',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				'/digiriskdolibarr/js/digiriskdolibarr.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'completeTabsHead',
				'admincompany',
				'globaladmin'
			),
			'tabs' => array(
				'mycompany_admin'
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		$this->dirs = array(
			"/digiriskdolibarr/riskassessment",
			"/digiriskdolibarr/risksign",
			"/ecm/digiriskdolibarr",
			"/ecm/digiriskdolibarr/actionplan",
			"/ecm/digiriskdolibarr/legaldisplay",
			"/ecm/digiriskdolibarr/informationssharing",
			"/ecm/digiriskdolibarr/firepermit",
			"/ecm/digiriskdolibarr/preventionplan",
			"/ecm/digiriskdolibarr/groupmentdocument",
			"/ecm/digiriskdolibarr/workunitdocument",
			"/ecm/digiriskdolibarr/listingrisksaction",
			"/ecm/digiriskdolibarr/listingrisksphoto",
			"/ecm/digiriskdolibarr/signalisation",
			"/ecm/digiriskdolibarr/medias"
		);

		// Config pages.
		$this->config_page_url = array("setup.php@digiriskdolibarr");
		// Dependencies

		$this->hidden                  = false;
		$this->depends                 = array('modAgenda', 'modECM', 'modProjet', 'modSociete');
		$this->requiredby              = array();
		$this->conflictwith            = array();
		$this->langfiles               = array("digiriskdolibarr@digiriskdolibarr");
		$this->phpmin                  = array(5, 5); // Minimum version of PHP required by module
		$this->need_dolibarr_version   = array(11, -3); // Minimum version of Dolibarr required by module
		$this->warnings_activation     = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'DigiriskDolibarrWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		//TODO ranger les const correctement
		$this->const = array(
			// CONST CONFIGURATION
			1 => array('DIGIRISK_GENERAL_MEANS','chaine','','General means', 1),
			2 => array('DIGIRISK_GENERAL_RULES','chaine','','General rules', 1),
			3 => array('DIGIRISK_IDCC_DICTIONNARY','chaine','','IDCC of company', 1),

			// CONST DIGIRISK DOCUMENTS

			// CONST ACTION PLAN
			4 => array('MAIN_AGENDA_ACTIONAUTO_ACTIONPLAN_CREATE','chaine',1,'', 1),
			5 => array('DIGIRISKDOLIBARR_ACTIONPLAN_ADDON','chaine', 'mod_legaldisplay_standard' ,'', 1),
			6 => array('DIGIRISKDOLIBARR_ACTIONPLAN_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/actionplan/' ,'', 1),
			7 => array('DIGIRISKDOLIBARR_ACTIONPLAN_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/actionplan/' ,'', 1),
			8 => array('DIGIRISKDOLIBARR_ACTIONPLAN__DEFAULT_MODEL','chaine', 'actionplan_odt' ,'', 1),

			// CONST LEGAL DISPLAY
			10 => array('DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION','chaine','','Location of detailed instruction', 1),
			11 => array('DIGIRISK_DEROGATION_SCHEDULE_PERMANENT','chaine','','Permanent exceptions to working hours', 1),
			12 => array('DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL','chaine','','Occasional exceptions to working hours', 1),
			13 => array('DIGIRISK_COLLECTIVE_AGREEMENT_TITLE','chaine','','Title of the collective agreement', 1),
			14 => array('DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION','chaine','','Location of the collective agreement', 1),
			15 => array('DIGIRISK_DUER_LOCATION','chaine','','Location of risks evaluation', 1),
			16 => array('DIGIRISK_RULES_LOCATION','chaine','','Location of rules of procedure', 1),
			17 => array('DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE','chaine','','Information procedure of participation agreement', 1),

			18 => array('MAIN_AGENDA_ACTIONAUTO_LEGALDISPLAY_CREATE','chaine',1,'', 1),
			19 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON','chaine', 'mod_legaldisplay_standard' ,'', 1),
			20 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/legaldisplay/' ,'', 1),
			21 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/legaldisplay/' ,'', 1),
			22 => array('DIGIRISKDOLIBARR_LEGALDISPLAY__DEFAULT_MODEL','chaine', 'legaldisplay_odt' ,'', 1),

			// CONST INFORMATIONS SHARING
			30 => array('MAIN_AGENDA_ACTIONAUTO_INFORMATIONSSHARING_CREATE','chaine',1,'', 1),
			31 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON','chaine', 'mod_informationssharing_standard' ,'', 1),
			32 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/informationssharing/' ,'', 1),
			33 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/informationssharing/' ,'', 1),
			34 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_DEFAULT_MODEL','chaine', 'informationssharing_odt' ,'', 1),

			// CONST LISTING RISKS ACTION
			40 => array('MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSACTION_CREATE','chaine',1,'', 1),
			41 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON','chaine', 'mod_listingrisksaction_standard' ,'', 1),
			42 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/listingrisksaction/' ,'', 1),
			43 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/listingrisksaction/' ,'', 1),
			44 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL','chaine', 'listingrisksaction_odt' ,'', 1),

			// CONST LISTING RISKS PHOTO
			50 => array('MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSPHOTO_CREATE','chaine',1,'', 1),
			51 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON','chaine', 'mod_listingrisksphoto_standard' ,'', 1),
			52 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/listingrisksphoto/' ,'', 1),
			53 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/listingrisksphoto/' ,'', 1),
			54 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_DEFAULT_MODEL','chaine', 'listingrisksphoto_odt' ,'', 1),

			// CONST GROUPMENT DOCUMENT
			60 => array('MAIN_AGENDA_ACTIONAUTO_GROUPMENTDOCUMENT_CREATE','chaine',1,'', 1),
			61 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/groupmentdocument/' ,'', 1),
			62 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/groupmentdocument/' ,'', 1),
			63 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON','chaine', 'mod_groupmentdocument_standard' ,'', 1),
			64 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL','chaine', 'groupmentdocument_odt' ,'', 1),

			// CONST WORKUNIT DOCUMENT
			70 => array('MAIN_AGENDA_ACTIONAUTO_WORKUNITDOCUMENT_CREATE','chaine',1,'', 1),
			71 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON','chaine', 'mod_workunitdocument_standard' ,'', 1),
			72 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/workunitdocument/' ,'', 1),
			73 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/workunitdocument/' ,'', 1),
			74 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL','chaine', 'workunitdocument_odt' ,'', 1),

			// CONST PREVENTION PLAN
			80 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE','chaine',1,'', 1),
			81 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON','chaine', 'mod_preventionplan_standard' ,'', 1),
			82 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/preventionplan/' ,'', 1),
			83 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/preventionplan/' ,'', 1),
			84 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_DEFAULT_MODEL','chaine', 'preventionplan_odt' ,'', 1),

			// CONST FIREPERMIT
			90 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE','chaine',1,'', 1),
			91 => array('DIGIRISKDOLIBARR_FIREPERMIT_ADDON','chaine', 'mod_firepermit_standard' ,'', 1),
			92 => array('DIGIRISKDOLIBARR_FIREPERMIT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/firepermit/' ,'', 1),
			93 => array('DIGIRISKDOLIBARR_FIREPERMIT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/firepermit/' ,'', 1),
			94 => array('DIGIRISKDOLIBARR_FIREPERMIT_DEFAULT_MODEL','chaine', 'firepermit_odt' ,'', 1),

			// CONST DIGIRISK ELEMENT

			// CONST GROUPMENT
			100 => array('MAIN_AGENDA_ACTIONAUTO_GROUPMENT_CREATE','chaine',1,'', 1),
			101 => array('DIGIRISKDOLIBARR_GROUPMENT_ADDON','chaine', 'mod_groupment_standard' ,'', 1),

			// CONST WORKUNIT
			110 => array('MAIN_AGENDA_ACTIONAUTO_WORKUNIT_CREATE','chaine',1,'', 1),
			111 => array('DIGIRISKDOLIBARR_WORKUNIT_ADDON','chaine', 'mod_workunit_standard' ,'', 1),

			// CONST EVALUATOR
			120 => array('MAIN_AGENDA_ACTIONAUTO_EVALUATOR_CREATE','chaine',1,'', 1),
			121 => array('DIGIRISKDOLIBARR_EVALUATOR_ADDON','chaine', 'mod_evaluator_standard' ,'', 1),

			122 => array('DIGIRISKDOLIBARR_EVALUATOR_DURATION','integer', 15 ,'', 1),

			// CONST RISK ANALYSIS

			// CONST RISK
			130 => array('MAIN_AGENDA_ACTIONAUTO_RISK_CREATE','chaine',1,'', 1),
			131 => array('DIGIRISKDOLIBARR_RISK_ADDON','chaine', 'mod_risk_standard' ,'', 1),

			// CONST RISK ASSESSMENT
			140 => array('MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_CREATE','chaine',1,'', 1),
			141 => array('DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON','chaine', 'mod_riskassessment_standard' ,'', 1),

			142 => array('DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD','integer', 0 ,'', 1),

			// CONST RISK SIGN
			150 => array('MAIN_AGENDA_ACTIONAUTO_RISKSIGN_CREATE','chaine',1,'', 1),
			151 => array('DIGIRISKDOLIBARR_RISKSIGN_ADDON','chaine', 'mod_risksign_standard' ,'', 1),

			// CONST TASK
			160 => array('DIGIRISKDOLIBARR_PROJECT_LINKED','integer', 0,'', 1),
		);

		if ( ! isset($conf->digiriskdolibarr ) || ! isset( $conf->digiriskdolibarr->enabled ) ) {
			$conf->digiriskdolibarr          = new stdClass();
			$conf->digiriskdolibarr->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Rajouter les onglets ici
		// Example:
		$this->tabs[] = array('data'=>'mycompany_admin:+security:Sécurité:@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/securityconf.php');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data'=>'mycompany_admin:+social:Social:@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/socialconf.php');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data'=>'thirdparty:+openinghours:Horaires:@digiriskdolibarr:1:/custom/digiriskdolibarr/openinghours_card.php?id=__ID__');  					// To add a new tab identified by code tabname1

		// To remove an existing tab identified by code tabname
		// Dictionaries
		$this->dictionaries=array(
			'langs'=>'digiriskdolibarr@digiriskdolibarr',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."c_conventions_collectives"),
			// Label of tables
			'tablib'=>array("CollectiveAgreement"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.libelle, f.active FROM '.MAIN_DB_PREFIX.'c_conventions_collectives as f'),
			// Sort order
			'tabsqlsort'=>array("libelle ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,libelle"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,libelle"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,libelle"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->digiriskdolibarr->enabled, $conf->digiriskdolibarr->enabled, $conf->digiriskdolibarr->enabled)
		);

		// Boxes/Widgets
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'digiriskdolibarrwidget1.php@digiriskdolibarr',
			//      'note' => 'Widget provided by DigiriskDolibarr',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r            = 0;

		/* ACTION PLAN PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadActionPlan');
		$this->rights[$r][4] = 'actionplan';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateActionPlan');
		$this->rights[$r][4] = 'actionplan';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteActionPlan');
		$this->rights[$r][4] = 'actionplan';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LEGAL DISPLAY PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* INFORMATIONS SHARING PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* FIRE PERMIT PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* PREVENTION PLAN PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadPreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreatePreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeletePreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* GP/UT ORGANISATION PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* RISKS PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LISTING RISKS ACTION PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateReadListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteReadListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LISTING RISKS PHOTO PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateReadListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteReadListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* RISK SIGN PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* EVALUATOR PERMISSIONS */

		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateReadEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteReadEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'delete';

		// Main menu entries to add
		$this->menu = array();
		$r          = 0;
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array(
			'fk_menu'  => '', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'top', // This is a Top menu entry
			'titre'    => 'Digirisk',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/digiriskdolibarr/digiriskdolibarrindex.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled', // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled.
			'perms'    => '1', // Use 'perms'=>'$user->rights->digiriskdolibarr->digiriskconst->read' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER TOPMENU */

//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'=>'left',			                // This is a Left menu entry
//			'titre'=>'Documents Légaux',
//			'mainmenu'=>'digiriskdolibarr',
//			'leftmenu'=>'documents',
//			'url'=>'/digiriskdolibarr/legaldocuments_list.php',
//			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position'=>48520+$r,
//			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
//			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'=>'',
//			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
//		);
//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=digiriskdolibarr,fk_leftmenu=documents',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'=>'left',                          // This is a Top menu entry
//			'titre'=>$langs->trans('LegalDisplay'),
//			'mainmenu'=>'digiriskdolibarr',
//			'leftmenu'=>'legaldisplay',
//			'url'=>'/digiriskdolibarr/legaldisplay_list.php',
//			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position'=>48520+$r,
//			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled.
//			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'=>'',
//			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
//		);
//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=digiriskdolibarr,fk_leftmenu=documents',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'=>'left',			                // This is a Left menu entry
//			'titre'=>$langs->trans('InformationsSharing'),
//			'mainmenu'=>'digiriskdolibarr',
//			'leftmenu'=>'informations',
//			'url'=>'/digiriskdolibarr/informationssharing_list.php',
//			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position'=>48520+$r,
//			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
//			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'=>'',
//			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
//		);


//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=digiriskdolibarr,fk_leftmenu=documents',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'=>'left',			                // This is a Left menu entry
//			'titre'=>$langs->trans('FirePermit'),
//			'mainmenu'=>'digiriskdolibarr',
//			'leftmenu'=>'firepermit',
//			'url'=>'/digiriskdolibarr/firepermit_list.php',
//			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position'=>48520+$r,
//			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
//			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'=>'',
//			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
//		);
//
//		$this->menu[$r++]=array(
//			'fk_menu'=>'fk_mainmenu=digiriskdolibarr,fk_leftmenu=documents',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'=>'left',			                // This is a Left menu entry
//			'titre'=>$langs->trans('PreventionPlan'),
//			'mainmenu'=>'digiriskdolibarr',
//			'leftmenu'=>'preventionplan',
//			'url'=>'/digiriskdolibarr/preventionplan_list.php',
//			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position'=>48520+$r,
//			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
//			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'=>'',
//			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
//		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', 										// This is a Left menu entry
			'titre'=>$langs->trans('RiskEvaluationDocument'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskstandard',
			'url'=>'/digiriskdolibarr/digiriskstandard_card.php?id='.$conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD,
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('RiskList'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digirisklistingrisk',
			'url'=>'/digiriskdolibarr/risk_list.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('Users'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskusers',
			'url'=>'/digiriskdolibarr/digiriskusers.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$user->rights->user->user->lire',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('DigiriskConfig'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskdocumentmodels',
			'url'=>'/digiriskdolibarr/admin/setup.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'1',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>$langs->trans('DigiriskConfigSociety'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskdocumentmodels',
			'url'=>'/admin/company.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled && $user->admin',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->admin',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		// Exports profiles provided by this module
		/* BEGIN MODULEBUILDER EXPORT DIGIRISKCONST */
		/*
		$langs->load("digiriskdolibarr@digiriskdolibarr");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='DigiriskConstLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='digiriskconst@digiriskdolibarr';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'DigiriskConst'; $keyforclassfile='/digiriskdolibarr/class/digiriskconst.class.php'; $keyforelement='digiriskconst@digiriskdolibarr';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'DigiriskConstLine'; $keyforclassfile='/digiriskdolibarr/class/digiriskconst.class.php'; $keyforelement='digiriskconstline@digiriskdolibarr'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='digiriskconst'; $keyforaliasextra='extra'; $keyforelement='digiriskconst@digiriskdolibarr';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='digiriskconstline'; $keyforaliasextra='extraline'; $keyforelement='digiriskconstline@digiriskdolibarr';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('digiriskconstline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'digiriskconst as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'digiriskconst_line as tl ON tl.fk_digiriskconst = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('digiriskconst').')';
		$r++; */
		/* END MODULEBUILDER EXPORT DIGIRISKCONST */

		// Imports profiles provided by this module
		/* BEGIN MODULEBUILDER IMPORT DIGIRISKCONST */
		/*
		 $langs->load("digiriskdolibarr@digiriskdolibarr");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='DigiriskConstLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='digiriskconst@digiriskdolibarr';
		 $keyforclass = 'DigiriskConst'; $keyforclassfile='/digiriskdolibarr/class/digiriskconst.class.php'; $keyforelement='digiriskconst@digiriskdolibarr';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='digiriskconst'; $keyforaliasextra='extra'; $keyforelement='digiriskconst@digiriskdolibarr';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'digiriskconst as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('digiriskconst').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT DIGIRISKCONST */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user;

		$langs->load("digiriskdolibarr@digiriskdolibarr");

		$sql = array();

		$this->_load_tables('/digiriskdolibarr/sql/');
		$this->_load_tables('/digiriskdolibarr/sql/digiriskdocuments/');
		$this->_load_tables('/digiriskdolibarr/sql/digiriskelement/');
		$this->_load_tables('/digiriskdolibarr/sql/riskanalysis/');

		if ( $conf->global->DIGIRISKDOLIBARR_DU_PROJECT == 0 ) {
			require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/modules/project/mod_project_simple.php';

			dolibarr_set_const($this->db, 'MAIN_MODULE_PROJET',1);

			$project     = new Project($this->db);
			$third_party = new Societe($this->db);
			$projectRef  = new $conf->global->PROJECT_ADDON();

			$project->ref         = $projectRef->getNextValue($third_party, $project);
			$project->title       = $langs->trans('ActionPlanDU');
			$project->description = $langs->trans('ActionPlanDUDescription');
			$project->date_c      = dol_now();
			//$project->date_start = dol_now(); -> option
			$project->usage_task  = 1;
			//$project->date_end = dol_now(); -> option
			$project->statut      = 1;

			$project_id = $project->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DU_PROJECT', $project_id);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD ==  0 ) {
			dol_include_once('/digiriskdolibarr/class/digiriskstandard.class.php');

			$digiriskstandard = new DigiriskStandard($this->db);
			$digiriskstandard->ref = 'DU';
			$digiriskstandard->description = 'DUDescription';
			$digiriskstandard->date_creation = dol_now();
			$digiriskstandard->status = 1;

			$standard_id = $digiriskstandard->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_ACTIVE_STANDARD', $standard_id, 'integer', 0, '', $digiriskstandard->entity);
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extra_fields = new ExtraFields( $this->db );

		$extra_fields->addExtraField( 'fk_risk', $langs->trans("fk_risk"), 'int', 1020, 10, 'task', 0, 0, '', '', '', '', 1);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
