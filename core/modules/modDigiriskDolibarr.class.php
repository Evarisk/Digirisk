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
		$this->description     = $langs->trans('DigiriskDolibarrDescription');
		$this->descriptionlong = "Digirisk";
		$this->editor_name     = 'Evarisk';
		$this->editor_url      = 'https://evarisk.com';
		$this->version         = '8.5.4';
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
			'css' => array(),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'completeTabsHead',
				'admincompany',
				'globaladmin',
				'emailtemplates',
				'mainloginpage',
			),
			'tabs' => array(
				'mycompany_admin'
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		$this->dirs = array(
			"/digiriskdolibarr/riskassessment",
			"/digiriskdolibarr/accident",
			"/ecm/digiriskdolibarr",
			"/ecm/digiriskdolibarr/riskassessmentdocument",
			"/ecm/digiriskdolibarr/legaldisplay",
			"/ecm/digiriskdolibarr/informationssharing",
			"/ecm/digiriskdolibarr/firepermitdocument",
			"/ecm/digiriskdolibarr/preventionplandocument",
			"/ecm/digiriskdolibarr/groupmentdocument",
			"/ecm/digiriskdolibarr/workunitdocument",
			"/ecm/digiriskdolibarr/listingrisksaction",
			"/ecm/digiriskdolibarr/listingrisksphoto",
			"/ecm/digiriskdolibarr/medias"
		);

		// Config pages.
		$this->config_page_url = array("setup.php@digiriskdolibarr");
		// Dependencies

		$this->hidden                  = false;
		$this->depends                 = array('modECM', 'modProjet', 'modSociete', 'modTicket', 'modCategorie', 'modFckeditor', 'modApi');
		$this->requiredby              = array();
		$this->conflictwith            = array();
		$this->langfiles               = array("digiriskdolibarr@digiriskdolibarr");
		$this->phpmin                  = array(5, 5); // Minimum version of PHP required by module
		$this->need_dolibarr_version   = array(13, -3); // Minimum version of Dolibarr required by module
		$this->warnings_activation     = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'DigiriskDolibarrWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		$this->const = array(
			// CONST CONFIGURATION
			1 => array('DIGIRISK_GENERAL_MEANS','chaine','','General means', $conf->entity),
			2 => array('DIGIRISK_GENERAL_RULES','chaine','','General rules', $conf->entity),
			3 => array('DIGIRISK_IDCC_DICTIONNARY','chaine','','IDCC of company', $conf->entity),

			// CONST RISK ASSESSMENTDOCUMENT
			165 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE','date','','', $conf->entity),
			166 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE','date','','', $conf->entity),
			170 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT','integer',0,'', $conf->entity),
			171 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD','chaine','* Étape 1 : Récupération des informations<br>- Visite des locaux<br>- Récupération des données du personnel<br><br> * Étape 2 : Définition de la méthodologie et de document<br>- Validation des fiches d\'unité de travail standard<br>- Validation de l\'arborescence des unités<br><br>* Étape 3 : Réalisation de l\'étude de risques<br>- Sensibilisation des personnels aux risques et aux dangers<br>- Création des unités de travail avec le personnel et le ou les responsables<br>- Évaluations des risques par unités de travail avec le personnel<br><br>* Étape 4<br>- Traitement et rédaction du document unique','', $conf->entity),
			172 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES','chaine','La sensibilisation des risques est définie dans l\'ED840 édité par l\'INRS.<br>Dans ce document vous trouverez:<br>- La définition d\'un risque, d\'un danger et un schéma explicatif<br>- Les explications concernant les différentes methodes d\'évaluation<br>','', $conf->entity),
			173 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SITE_PLANS','chaine','Plan du site','', $conf->entity),
			174 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES','chaine','Notes importantes','', $conf->entity),

			4 => array('MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENTDOCUMENT_CREATE','chaine',1,'', $conf->entity),
			5 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON','chaine', 'mod_riskassessmentdocument_standard' ,'', $conf->entity),
			6 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/riskassessmentdocument/' ,'', $conf->entity),
			7 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/riskassessmentdocument/' ,'', $conf->entity),
			8 => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_DEFAULT_MODEL','chaine', 'riskassessmentdocument_odt' ,'', $conf->entity),

			// CONST LEGAL DISPLAY
			10 => array('DIGIRISK_LOCATION_OF_DETAILED_INSTRUCTION','chaine','','Location of detailed instruction', $conf->entity),
			11 => array('DIGIRISK_DEROGATION_SCHEDULE_PERMANENT','chaine','','Permanent exceptions to working hours', $conf->entity),
			12 => array('DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL','chaine','','Occasional exceptions to working hours', $conf->entity),
			13 => array('DIGIRISK_COLLECTIVE_AGREEMENT_TITLE','chaine','','Title of the collective agreement', $conf->entity),
			14 => array('DIGIRISK_COLLECTIVE_AGREEMENT_LOCATION','chaine','','Location of the collective agreement', $conf->entity),
			15 => array('DIGIRISK_DUER_LOCATION','chaine','','Location of risks evaluation', $conf->entity),
			16 => array('DIGIRISK_RULES_LOCATION','chaine','','Location of rules of procedure', $conf->entity),
			17 => array('DIGIRISK_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE','chaine','','Information procedure of participation agreement', $conf->entity),
			23 => array('DIGIRISK_FIRST_AID','chaine','','', $conf->entity),

			18 => array('MAIN_AGENDA_ACTIONAUTO_LEGALDISPLAY_CREATE','chaine',1,'', $conf->entity),
			19 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON','chaine', 'mod_legaldisplay_standard' ,'', $conf->entity),
			20 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/legaldisplay/' ,'', $conf->entity),
			21 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/legaldisplay/' ,'', $conf->entity),
			22 => array('DIGIRISKDOLIBARR_LEGALDISPLAY_DEFAULT_MODEL','chaine', 'legaldisplay_odt' ,'', $conf->entity),

			// CONST INFORMATIONS SHARING
			30 => array('MAIN_AGENDA_ACTIONAUTO_INFORMATIONSSHARING_CREATE','chaine',1,'', $conf->entity),
			31 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON','chaine', 'mod_informationssharing_standard' ,'', $conf->entity),
			32 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/informationssharing/' ,'', $conf->entity),
			33 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/informationssharing/' ,'', $conf->entity),
			34 => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_DEFAULT_MODEL','chaine', 'informationssharing_odt' ,'', $conf->entity),

			// CONST LISTING RISKS ACTION
			40 => array('MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSACTION_CREATE','chaine',1,'', $conf->entity),
			41 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON','chaine', 'mod_listingrisksaction_standard' ,'', $conf->entity),
			42 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/listingrisksaction/' ,'', $conf->entity),
			43 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/listingrisksaction/' ,'', $conf->entity),
			44 => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL','chaine', 'listingrisksaction_odt' ,'', $conf->entity),

			// CONST LISTING RISKS PHOTO
			50 => array('MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSPHOTO_CREATE','chaine',1,'', $conf->entity),
			51 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON','chaine', 'mod_listingrisksphoto_standard' ,'', $conf->entity),
			52 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/listingrisksphoto/' ,'', $conf->entity),
			53 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/listingrisksphoto/' ,'', $conf->entity),
			54 => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_DEFAULT_MODEL','chaine', 'listingrisksphoto_odt' ,'', $conf->entity),

			// CONST GROUPMENT DOCUMENT
			60 => array('MAIN_AGENDA_ACTIONAUTO_GROUPMENTDOCUMENT_CREATE','chaine',1,'', $conf->entity),
			61 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/groupmentdocument/' ,'', $conf->entity),
			62 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/groupmentdocument/' ,'', $conf->entity),
			63 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON','chaine', 'mod_groupmentdocument_standard' ,'', $conf->entity),
			64 => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL','chaine', 'groupmentdocument_odt' ,'', $conf->entity),

			// CONST WORKUNIT DOCUMENT
			70 => array('MAIN_AGENDA_ACTIONAUTO_WORKUNITDOCUMENT_CREATE','chaine',1,'', $conf->entity),
			71 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON','chaine', 'mod_workunitdocument_standard' ,'', $conf->entity),
			72 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/workunitdocument/' ,'', $conf->entity),
			73 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/workunitdocument/' ,'', $conf->entity),
			74 => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL','chaine', 'workunitdocument_odt' ,'', $conf->entity),

			// CONST PREVENTION PLAN
			80 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE','chaine',1,'', $conf->entity),
			81 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON','chaine', 'mod_preventionplan_standard' ,'', $conf->entity),
			83 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE','chaine',1,'', $conf->entity),
			84 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_EDIT','chaine',1,'', $conf->entity),
			250 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT','integer', 0,'', $conf->entity),
			251 => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE','integer', 0,'', $conf->entity),

			// CONST PREVENTION PLAN DOCUMENT
			85 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDOCUMENT_CREATE','chaine',1,'', $conf->entity),

			86 => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON','chaine', 'mod_preventionplandocument_standard' ,'', $conf->entity),
			87 => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/' ,'', $conf->entity),
			88 => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/preventionplandocument/' ,'', $conf->entity),
			89 => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL','chaine', 'preventionplandocument_odt' ,'', $conf->entity),
			260 =>array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/specimen/' ,'', $conf->entity),

			// CONST FIRE PERMIT
			90 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE','chaine',1,'', $conf->entity),
			91 => array('DIGIRISKDOLIBARR_FIREPERMIT_ADDON','chaine', 'mod_firepermit_standard' ,'', $conf->entity),
			92 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE','chaine',1,'', $conf->entity),
			93 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_EDIT','chaine',1,'', $conf->entity),
			270 => array('DIGIRISKDOLIBARR_FIREPERMIT_PROJECT','integer', 0,'', $conf->entity),
			271 => array('DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE','integer', 0,'', $conf->entity),

			// CONST FIRE PERMIT DOCUMENT
			94 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMITDOCUMENT_CREATE','chaine',1,'', $conf->entity),

			95 => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON','chaine', 'mod_firepermitdocument_standard' ,'', $conf->entity),
			96 => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/firepermitdocument/' ,'', $conf->entity),
			97 => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/firepermitdocument/' ,'', $conf->entity),
			98 => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL','chaine', 'firepermitdocument_odt' ,'', $conf->entity),

			// CONST GROUPMENT
			100 => array('MAIN_AGENDA_ACTIONAUTO_GROUPMENT_CREATE','chaine',1,'', $conf->entity),
			101 => array('DIGIRISKDOLIBARR_GROUPMENT_ADDON','chaine', 'mod_groupment_standard' ,'', $conf->entity),
			102 => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH','integer', 0 ,'', $conf->entity),
			103 => array('DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT','integer', 0 ,'', $conf->entity),
			104 => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED','integer', 0 ,'', $conf->entity),

			// CONST WORKUNIT
			110 => array('MAIN_AGENDA_ACTIONAUTO_WORKUNIT_CREATE','chaine',1,'', $conf->entity),
			111 => array('DIGIRISKDOLIBARR_WORKUNIT_ADDON','chaine', 'mod_workunit_standard' ,'', $conf->entity),

			//CONST DIGIRISKELEMENT
			115 => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY','integer', 0 ,'', $conf->entity),

			// CONST EVALUATOR
			120 => array('MAIN_AGENDA_ACTIONAUTO_EVALUATOR_CREATE','chaine',1,'', $conf->entity),
			121 => array('DIGIRISKDOLIBARR_EVALUATOR_ADDON','chaine', 'mod_evaluator_standard' ,'', $conf->entity),

			122 => array('DIGIRISKDOLIBARR_EVALUATOR_DURATION','integer', 15 ,'', $conf->entity),

			// CONST RISK ANALYSIS

			// CONST RISK
			130 => array('MAIN_AGENDA_ACTIONAUTO_RISK_CREATE','chaine',1,'', $conf->entity),
			131 => array('DIGIRISKDOLIBARR_RISK_ADDON','chaine', 'mod_risk_standard' ,'', $conf->entity),
			132 => array('DIGIRISKDOLIBARR_RISK_DESCRIPTION','integer', 1 ,'', $conf->entity),
			133 => array('DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT','integer', 0 ,'', $conf->entity),
			134 => array('DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL','integer', 0 ,'', $conf->entity),
			135 => array('DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION','integer', 1 ,'', $conf->entity),

			// CONST RISK ASSESSMENT
			140 => array('MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_CREATE','chaine',1,'', $conf->entity),
			141 => array('DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON','chaine', 'mod_riskassessment_standard' ,'', $conf->entity),

			142 => array('DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD','integer', 0 ,'', $conf->entity),
			143 => array('DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD','integer', 0 ,'', $conf->entity),
			144 => array('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE','integer', 0,'', $conf->entity),

			// CONST RISK SIGN
			150 => array('MAIN_AGENDA_ACTIONAUTO_RISKSIGN_CREATE','chaine',1,'', $conf->entity),
			151 => array('DIGIRISKDOLIBARR_RISKSIGN_ADDON','chaine', 'mod_risksign_standard' ,'', $conf->entity),

			// CONST PROJET
			155 => array('DIGIRISKDOLIBARR_PROJECT_TAGS_SET','integer', 0,'', $conf->entity),
			160 => array('DIGIRISKDOLIBARR_DU_PROJECT','integer', 0,'', $conf->entity),

			// CONST TASK
			161 => array('DIGIRISKDOLIBARR_ACTIVE_STANDARD','integer', 0,'', $conf->entity),
			162 => array('DIGIRISKDOLIBARR_DOCUMENT_MODELS_SET','integer', 0,'', $conf->entity),
			163 => array('DIGIRISKDOLIBARR_THIRDPARTY_SET','integer', 0,'', $conf->entity),
			164 => array('DIGIRISKDOLIBARR_TASK_MANAGEMENT','integer', 1 ,'', $conf->entity),
			310 => array('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE','integer', 0,'', $conf->entity),
			311 => array('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE','integer', 0,'', $conf->entity),
			312 => array('DIGIRISKDOLIBARR_SHOW_TASK_PROGRESS','integer', 1,'', $conf->entity),
			313 => array('DIGIRISKDOLIBARR_SHOW_ALL_TASKS','integer', 1,'', $conf->entity),

			// CONST PREVENTION PLAN LINE
			180 => array('MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDET_CREATE','chaine',1,'', $conf->entity),
			181 => array('DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON','chaine', 'mod_preventionplandet_standard','', $conf->entity),

			// CONST FIRE PERMIT LINE
			190 => array('MAIN_AGENDA_ACTIONAUTO_FIREPERMITDET_CREATE','chaine',1,'', $conf->entity),
			191 => array('DIGIRISKDOLIBARR_FIREPERMITDET_ADDON','chaine', 'mod_firepermitdet_standard','', $conf->entity),

			// CONST MODULE
			200 => array('DIGIRISKDOLIBARR_VERSION','chaine', $this->version,'', $conf->entity),
			201 => array('DIGIRISKDOLIBARR_SUBPERMCATEGORY_FOR_DOCUMENTS','integer', 1,'', $conf->entity),
			202 => array('DIGIRISKDOLIBARR_DB_VERSION','chaine', $this->version,'', $conf->entity),
			203 => array('DIGIRISKDOLIBARR_USERAPI_SET','integer', 0, '', $conf->entity),
			204 => array('DIGIRISKDOLIBARR_USERGROUP_SET','integer', 0, '', $conf->entity),
			205 => array('DIGIRISKDOLIBARR_ADMINUSERGROUP_SET','integer', 0, '', $conf->entity),
			206 => array('DIGIRISKDOLIBARR_CONTACTS_SET','integer', 0, '', $conf->entity),
			207 => array('DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION','integer', 0, '', $conf->entity),
			208 => array('DIGIRISKDOLIBARR_USE_CAPTCHA','integer', 0, '', $conf->entity),

			// CONST SIGNATURE
			210 => array('DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE','integer', 1,'', $conf->entity),
			211 => array('DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO','integer', 1,'', $conf->entity),

			//CONST TICKET & REGISTERS
			300 => array('DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 'integer', 0, '', $conf->entity),
			301 => array('DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 'integer', 0, '', $conf->entity),
			302 => array('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE','integer', 1,'', $conf->entity),
			303 => array('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO','integer', 1,'', $conf->entity),

			// CONST ACCIDENT
			330 => array('MAIN_AGENDA_ACTIONAUTO_ACCIDENT_CREATE','chaine',1,'', $conf->entity),
			331 => array('MAIN_AGENDA_ACTIONAUTO_ACCIDENT_EDIT','chaine',1,'', $conf->entity),
			332 => array('DIGIRISKDOLIBARR_ACCIDENT_ADDON','chaine', 'mod_accident_standard' ,'', $conf->entity),
			333 => array('DIGIRISKDOLIBARR_ACCIDENT_PROJECT','integer', 0,'', $conf->entity),

			// CONST ACCIDENT LINE
			350 => array('MAIN_AGENDA_ACTIONAUTO_ACCIDENT_WORKSTOP_CREATE','chaine',1,'', $conf->entity),
			351 => array('DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON','chaine', 'mod_accident_workstop_standard','', $conf->entity),
			352 => array('DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON','chaine', 'mod_accident_lesion_standard','', $conf->entity),

			// GENERAL CONSTS
			400 => array('MAIN_USE_EXIF_ROTATION','integer',1,'', $conf->entity),

//			// CONST ACCIDENT DOCUMENT
//			360 => array('MAIN_AGENDA_ACTIONAUTO_ACCIDENTDOCUMENT_CREATE','chaine',1,'', $conf->entity),
//
//			361 => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_ADDON','chaine', 'mod_accidentdocument_standard' ,'', $conf->entity),
//			362 => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_ADDON_ODT_PATH','chaine', DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/documents/doctemplates/accidentdocument/' ,'', $conf->entity),
//			363 => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_CUSTOM_ADDON_ODT_PATH','chaine', DOL_DATA_ROOT . '/ecm/digiriskdolibarr/accidentdocument/' ,'', $conf->entity),
//			364 => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_DEFAULT_MODEL','chaine', 'accidentdocument_odt' ,'', $conf->entity),
		);

		if ( ! isset($conf->digiriskdolibarr ) || ! isset( $conf->digiriskdolibarr->enabled ) ) {
			$conf->digiriskdolibarr          = new stdClass();
			$conf->digiriskdolibarr->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		$this->tabs[] = array('data'=>'mycompany_admin:+security:Sécurité:@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/securityconf.php');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data'=>'mycompany_admin:+social:Social:@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/socialconf.php');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data'=>'thirdparty:+openinghours:Horaires:@digiriskdolibarr:1:/custom/digiriskdolibarr/view/openinghours_card.php?id=__ID__');  					// To add a new tab identified by code tabname1

		// To remove an existing tab identified by code tabname
		// Dictionaries
		$this->dictionaries=array(
			'langs'=>'digiriskdolibarr@digiriskdolibarr',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(
				MAIN_DB_PREFIX."c_conventions_collectives",
				MAIN_DB_PREFIX."c_relative_location",
				MAIN_DB_PREFIX."c_lesion_localization",
				MAIN_DB_PREFIX."c_lesion_nature"
			),
			// Label of tables
			'tablib'=>array(
				"CollectiveAgreement",
				"RelativeLocation",
				"LesionLocalization",
				"LesionNature"
			),
			// Request to select fields
			'tabsql'=>array(
				'SELECT f.rowid as rowid, f.code, f.libelle, f.active FROM '.MAIN_DB_PREFIX.'c_conventions_collectives as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM '.MAIN_DB_PREFIX.'c_relative_location as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM '.MAIN_DB_PREFIX.'c_lesion_localization as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM '.MAIN_DB_PREFIX.'c_lesion_nature as f'
			),
			// Sort order
			'tabsqlsort'=>array(
				"libelle ASC",
				"label ASC",
				"label ASC",
				"label ASC"
			),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description"
			),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description"
			),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description"
			),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array(
				"rowid",
				"rowid",
				"rowid",
				"rowid"
			),
			// Condition to show each dictionary
			'tabcond'=>array(
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled
			)
		);

		// Boxes/Widgets
		$this->boxes = array(
//			0 => array(
//				'file' => 'box_riskassessmentdocument@digiriskdolibarr',
//				'note' => 'My notes',
//			)
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r            = 0;

		/* module PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('LireDigirisk');
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = 1;
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadDigirisk');
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = 1;
		$r++;

		/* RISK ASSESSMENT DOCUMENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
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
		$this->rights[$r][1] = $langs->trans('CreateListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteListingRisksAction');
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
		$this->rights[$r][1] = $langs->trans('CreateListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteListingRisksPhoto');
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
		$this->rights[$r][1] = $langs->trans('CreateEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* ADMINPAGE PANEL ACCESS PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadAdminPage');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read';
		$r++;

		/* API PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('GetAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('PostAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'write';
		$r++;

		/* ACCIDENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('ReadAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('CreateAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero.$r;
		$this->rights[$r][1] = $langs->trans('DeleteAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'delete';

		// Main menu entries to add
		$this->menu = array();
		$r          = 0;
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
			'perms'    => '$user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		);

		$langs->load("digiriskdolibarr@digiriskdolibarr");

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', // This is a Top menu entry
			'titre'    => '<i class="fas fa-home"></i>  ' . $langs->trans('Digirisk'),
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/digiriskdolibarr/digiriskdolibarrindex.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled', // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled.
			'perms'    => '$user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->digiriskconst->read' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', 										// This is a Left menu entry
			'titre'=>'<i class="fas fa-exclamation-triangle"></i>  ' . $langs->trans('RiskAssessmentDocument'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskstandard',
			'url'=>'/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id='.$conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD,
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->riskassessmentdocument->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', 										// This is a Left menu entry
			'titre'=>'<i class="fas fa-network-wired"></i>  ' . $langs->trans('Arborescence'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskstandard',
			'url'=>'/digiriskdolibarr/view/digiriskelement/digiriskelement_organization.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->riskassessmentdocument->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-list"></i>  ' . $langs->trans('RiskList'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digirisklistingrisk',
			'url'=>'/digiriskdolibarr/view/digiriskelement/risk_list.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->risk->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-info"></i>  ' . $langs->trans('PreventionPlan'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskpreventionplan',
			'url'=>'/digiriskdolibarr/view/preventionplan/preventionplan_list.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->preventionplan->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-fire-alt"></i>  ' . $langs->trans('FirePermit'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskfirepermit',
			'url'=>'/digiriskdolibarr/view/firepermit/firepermit_list.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->firepermit->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-user-injured"></i>  ' . $langs->trans('Accident'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskaccident',
			'url'=>'/digiriskdolibarr/view/accident/accident_list.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->accident->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-user"></i>  ' . $langs->trans('Users'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskusers',
			'url'=>'/digiriskdolibarr/view/digiriskusers.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$user->rights->user->user->lire',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->user->user->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-tasks"></i>  ' . $langs->trans('ActionPlan'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digirisktools',
			'url'=>'/projet/tasks.php?id=' . $conf->global->DIGIRISKDOLIBARR_DU_PROJECT,
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->admin && $user->rights->digiriskdolibarr->digiriskelement->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-wrench"></i>  ' . $langs->trans('Tools'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digirisktools',
			'url'=>'/digiriskdolibarr/view/digirisktools.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled && $user->admin',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->admin',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-cog"></i>  ' . $langs->trans('DigiriskConfig'),
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'digiriskdocumentmodels',
			'url'=>'/digiriskdolibarr/admin/setup.php',
			'langs'=>'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->digiriskdolibarr->adminpage->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<i class="fas fa-building"></i>  ' . $langs->trans('DigiriskConfigSociety'),
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

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'<span class="minimizeMenu"><i class="fas fa-bars"></i>  ' . $langs->trans('MinimizeMenu').'</span>',
			'mainmenu'=>'digiriskdolibarr',
			'leftmenu'=>'',
			'url'=>'',
			'langs'=>'',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>48520+$r,
			'enabled'=>'$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>1,			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
		);
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

		if ( $conf->global->DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE ==  0 ) {
			require_once __DIR__ . '/../../class/preventionplan.class.php';
			require_once __DIR__ . '/../../class/firepermit.class.php';

			$preventionPlanSignature = new PreventionPlanSignature($this->db);
			$preventionPlanSignatureList = $preventionPlanSignature->fetchAll('', '', 0, 0, array(), 'AND', 'digiriskdolibarr_preventionplan_signature');
			if (!empty($preventionPlanSignatureList)) {
				foreach ($preventionPlanSignatureList as $preventionPlanSignature) {
					$preventionPlanSignature->create($user, 1);
				}
			}

			$firePermitSignature = new FirePermitSignature($this->db);
			$firePermitSignatureList = $firePermitSignature->fetchAll('', '', 0, 0, array(), 'AND', 'digiriskdolibarr_firepermit_signature');
			if (!empty($firePermitSignatureList)) {
				foreach ($firePermitSignatureList as $firePermitSignature) {
					$firePermitSignature->create($user, 1);
				}
			}

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE', 1, 'integer', 0, '', $conf->entity);
		}

		$sql = array();
		$this->_load_tables('/digiriskdolibarr/sql/');

		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if (!preg_match('/\./', $subFolder)) {
				$this->_load_tables('/digiriskdolibarr/sql/' . $subFolder . '/');
			}
		}

		delDocumentModel('informationssharing_odt'            ,'informationssharing');
		delDocumentModel('legaldisplay_odt'                   ,'legaldisplay');
		delDocumentModel('firepermitdocument_odt'             ,'firepermitdocument');
		delDocumentModel('preventionplandocument_odt'         ,'preventionplandocument');
		delDocumentModel('preventionplandocument_specimen_odt','preventionplandocumentspecimen');
		delDocumentModel('groupmentdocument_odt'              ,'groupmentdocument');
		delDocumentModel('workunitdocument_odt'               ,'workunitdocument');
		delDocumentModel('listingrisksaction_odt'             ,'listingrisksaction');
		delDocumentModel('listingrisksphoto_odt'              ,'listingrisksphoto');
		delDocumentModel('riskassessmentdocument_odt'         ,'riskassessmentdocument');

		addDocumentModel('informationssharing_odt'            ,'informationssharing'           ,'ODT templates','DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH');
		addDocumentModel('legaldisplay_odt'                   ,'legaldisplay'                  ,'ODT templates','DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH');
		addDocumentModel('firepermitdocument_odt'             ,'firepermitdocument'            ,'ODT templates','DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_odt'         ,'preventionplandocument'        ,'ODT templates','DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_specimen_odt','preventionplandocumentspecimen','ODT templates','DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH');
		addDocumentModel('groupmentdocument_odt'              ,'groupmentdocument'             ,'ODT templates','DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('workunitdocument_odt'               ,'workunitdocument'              ,'ODT templates','DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('listingrisksaction_odt'             ,'listingrisksaction'            ,'ODT templates','DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH');
		addDocumentModel('listingrisksphoto_odt'              ,'listingrisksphoto'             ,'ODT templates','DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH');
		addDocumentModel('riskassessmentdocument_odt'         ,'riskassessmentdocument'        ,'ODT templates','DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH');

		if ( $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH ==  0 ) {
			require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

			$trashRef = 'GP0';
			$digiriskelement = new Groupment($this->db);
			$digiriskelement->ref = $trashRef;
			$digiriskelement->label = $langs->trans('HiddenElements');
			$digiriskelement->element_type = 'groupment';
			$digiriskelement->rank = 0;
			$digiriskelement->description = $langs->trans('TrashGroupment');
			$digiriskelement->status = 0;
			$trash_id = $digiriskelement->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH', $trash_id, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD ==  0 ) {
			require_once __DIR__ . '/../../class/digiriskstandard.class.php';

			$digiriskstandard = new DigiriskStandard($this->db);
			$digiriskstandard->ref = 'DU';
			$digiriskstandard->description = 'DUDescription';
			$digiriskstandard->date_creation = dol_now();
			$digiriskstandard->status = 1;

			$standard_id = $digiriskstandard->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_ACTIVE_STANDARD', $standard_id, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_THIRDPARTY_SET ==  0 ) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);

			$labour_inspector         = $societe;
			$labour_inspector->name   = $langs->trans('LabourInspectorName');
			$labour_inspector->client = 0;
			$labour_inspector->phone  = '';
			$labour_inspector->url    = $langs->trans('UrlLabourInspector');
			$labour_inspectorID       = $labour_inspector->create($user);

			$samu         = $societe;
			$samu->name   = $langs->trans('SAMU');
			$samu->client = 0;
			$samu->phone  = '15';
			$samu->url    = '';
			$samuID       = $samu->create($user);

			$pompiers         = $societe;
			$pompiers->name   = $langs->trans('Pompiers');
			$pompiers->client = 0;
			$pompiers->phone  = '18';
			$pompiers->url    = '';
			$pompiersID       = $pompiers->create($user);

			$police         = $societe;
			$police->name   = $langs->trans('Police');
			$police->client = 0;
			$police->phone  = '17';
			$police->url    = '';
			$policeID       = $police->create($user);

			$emergency         = $societe;
			$emergency->name   = $langs->trans('AllEmergencies');
			$emergency->client = 0;
			$emergency->phone  = '112';
			$emergency->url    = '';
			$emergencyID       = $emergency->create($user);

			$rights_defender         = $societe;
			$rights_defender->name   = $langs->trans('RightsDefender');
			$rights_defender->client = 0;
			$rights_defender->phone  = '';
			$rights_defender->url    = '';
			$rights_defenderID       = $rights_defender->create($user);

			$poison_control_center         = $societe;
			$poison_control_center->name   = $langs->trans('PoisonControlCenter');
			$poison_control_center->client = 0;
			$poison_control_center->phone  = '';
			$poison_control_center->url    = '';
			$poison_control_centerID       = $poison_control_center->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db,1,  'LabourInspectorSociety',  'societe', array($labour_inspectorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'Police',  'societe', array($policeID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'SAMU',  'societe', array($samuID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'Pompiers',  'societe', array($pompiersID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'AllEmergencies',  'societe', array($emergencyID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'RightsDefender',  'societe', array($rights_defenderID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'PoisonControlCenter',  'societe', array($poison_control_centerID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 2, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_THIRDPARTY_SET == 1) {
			//Install after 8.1.2

			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);

			$labour_inspector         = $societe;
			$labour_inspector->name   = $langs->trans('LabourInspectorName');
			$labour_inspector->client = 0;
			$labour_inspector->phone  = '';
			$labour_inspector->url    = $langs->trans('UrlLabourInspector');
			$labour_inspectorID       = $labour_inspector->create($user);

			$rights_defender         = $societe;
			$rights_defender->name   = $langs->trans('RightsDefender');
			$rights_defender->client = 0;
			$rights_defender->phone  = '';
			$rights_defender->url    = '';
			$rights_defenderID       = $rights_defender->create($user);

			$poison_control_center         = $societe;
			$poison_control_center->name   = $langs->trans('PoisonControlCenter');
			$poison_control_center->client = 0;
			$poison_control_center->phone  = '';
			$poison_control_center->url    = '';
			$poison_control_centerID       = $poison_control_center->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db,1,  'LabourInspectorSociety',  'societe', array($labour_inspectorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'RightsDefender',  'societe', array($rights_defenderID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db,1,  'PoisonControlCenter',  'societe', array($poison_control_centerID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 2, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_CONTACTS_SET ==  0 ) {
			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$contact   = new Contact($this->db);
			$resources = new DigiriskResources($this->db);
			$allLinks = $resources->digirisk_dolibarr_fetch_resource('LabourInspectorSociety');

			$labour_inspector            = $contact;
			$labour_inspector->socid     = $allLinks;
			$labour_inspector->firstname = $langs->trans('LabourInspectorFirstName');
			$labour_inspector->lastname  = $langs->trans('LabourInspectorLastName');
			$labour_inspectorID          = $labour_inspector->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db,1, 'LabourInspectorContact', 'socpeople', array($labour_inspectorID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CONTACTS_SET', 2, 'integer', 0, '', $conf->entity);
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extra_fields = new ExtraFields( $this->db );

		$extra_fields->update('fk_risk', $langs->trans("fk_risk"), 'sellist', '', 'projet_task', 0, 0, 1020, 'a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_risk:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->addExtraField( 'fk_risk', $langs->trans("fk_risk"), 'sellist', 1020, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_risk:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);

		$extra_fields->addExtraField( 'fk_preventionplan', $langs->trans("fk_preventionplan"), 'sellist', 1020, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:60:"digiriskdolibarr_preventionplan:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->addExtraField( 'fk_firepermit', $langs->trans("fk_firepermit"), 'sellist', 1030, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:56:"digiriskdolibarr_firepermit:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
//		$extra_fields->addExtraField( 'fk_accident', $langs->trans("fk_accident"), 'sellist', 1020, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_accident:ref:rowid::entity = $ENTITY$";N;}a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_risk:ref:rowid::entity = $ENTITY$";N;}}}', '', '', 1);

		//Used for data import from Digirisk Wordpress
		$extra_fields->addExtraField( 'wp_digi_id', $langs->trans("WPDigiID"), 'int', 100, '', 'digiriskdolibarr_digiriskelement', 1, 0, '', '', '', '', 0);

		$extra_fields->addExtraField( 'professional_qualification', $langs->trans("ProfessionalQualification"), 'varchar', 990, 255, 'user', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '', '', 0, 'digiriskdolibarr');
		$extra_fields->addExtraField( 'contract_type', $langs->trans("ContractType"), 'select', 1000, '', 'user', 0, 0, '', 'a:1:{s:7:"options";a:5:{i:1;s:3:"CDI";i:2;s:3:"CDD";i:3;s:18:"Apprentice/Student";i:4;s:7:"Interim";i:5;s:5:"Other";}}', 1, '', 1, '', '', 0, 'digiriskdolibarr');

		if ($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2 == 0) {
			dolibarr_set_const($this->db, 'MAIN_EXTRAFIELDS_USE_SELECT2', 1, 'integer', 0, '', $conf->entity);
		}

		dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

		//DigiriskElement favorite medias backward compatibility
		if ($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY == 0) {
			require_once __DIR__ . '/../../class/digiriskelement.class.php';

			$digiriskelement = new DigiriskElement($this->db);
			$digiriskElementList = $digiriskelement->fetchAll();

			if (!empty($digiriskElementList) && $digiriskElementList > 0) {
				foreach ($digiriskElementList as $digiriskElement) {
					$mediasDir = DOL_DATA_ROOT . ($conf->entity == 1 ? '' : '/' . $conf->entity) . '/digiriskdolibarr/' . $digiriskElement->element_type . '/' . $digiriskElement->ref;

					if (is_dir($mediasDir)) {
						$fileList = dol_dir_list($mediasDir);
						if (!empty($fileList) && $fileList > 0) {
							$digiriskElement->photo = $fileList[0]['name'];
							$digiriskElement->update($user);
						}
					}
				}
			}
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', $conf->entity);
		}

		//Categorie
		if ($conf->global->DIGIRISKDOLIBARR_PROJECT_TAGS_SET == 0) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

			$tags = new Categorie($this->db);

			$tags->label = 'QHSE';
			$tags->type = 'project';
			$tag_id = $tags->create($user);

			$tags->label = 'DU';
			$tags->type = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label = 'PP';
			$tags->type = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label = 'FP';
			$tags->type = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label = 'ACC';
			$tags->type = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 2, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_PROJECT_TAGS_SET == 1) {
			//Install after 8.3.0

			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

			$tags = new Categorie($this->db);

			$tags->fetch('', 'QHSE');

			$tags->label = 'FP';
			$tags->type = 'project';
			$tags->fk_parent = $tags->id;
			$tags->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 2, 'integer', 0, '', $conf->entity);
		}

		if ($conf->global->DIGIRISKDOLIBARR_TRIGGERS_UPDATED ==  0) {

			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

			$actioncomm = new Actioncomm($this->db);

			$allGroupments = $actioncomm->getActions( $this->db, 0, 0, '', ' AND a.elementtype = "groupment@digiriskdolibarr"');
			if (!empty($allGroupments)) {
				foreach ($allGroupments as $allGroupment) {
					$allGroupment->elementtype = 'digiriskelement@digiriskdolibarr';
					$allGroupment->update($user);
				}
			}

			$allWorkunits = $actioncomm->getActions( $this->db, 0, 0, '', ' AND a.elementtype = "workunit@digiriskdolibarr"');
			if (!empty($allWorkunits)) {
				foreach ($allWorkunits as $allWorkunit) {
					$allWorkunit->elementtype = 'digiriskelement@digiriskdolibarr';
					$allWorkunit->update($user);
				}
			}

			$allCompanies = $actioncomm->getActions( $this->db, 0, 0, '', ' AND a.elementtype = "societe@digiriskdolibarr"');
			if (!empty($allCompanies)) {
				foreach ($allCompanies as $allCompany) {
					$allCompany->fk_soc = $allCompany->fk_element;
					$allCompany->update($user);
				}
			}

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_TRIGGERS_UPDATED', 1, 'integer', 0, '', $conf->entity);
		}

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

		$options = 'noremoverights';

		return $this->_remove($sql, $options);
	}
}
