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
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DigiriskDolibarr
 */
class modDigiriskdolibarr extends DolibarrModules
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var int Module unique ID
	 * @see https://wiki.dolibarr.org/index.php/List_of_modules_id
	 */
	public $numero;

	/**
	 * @var   string Publisher name
	 * @since 4.0.0
	 */
	public $editor_name;

	/**
	 * @var   string URL of module at publisher site
	 * @since 4.0.0
	 */
	public $editor_url;

	/**
	 * @var string Family
	 * @see $familyinfo
	 *
	 * Native values: 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic', 'other'.
	 * Use familyinfo to declare a custom value.
	 */
	public $family;

	/**
	 * @var array Custom family informations
	 * @see $family
	 *
	 * e.g.:
	 * array(
	 *     'myownfamily' => array(
	 *         'position' => '001',
	 *         'label' => $langs->trans("MyOwnFamily")
	 *     )
	 * );
	 */
	public $familyinfo;

	/**
	 * @var string    Module position on 2 digits
	 */
	public $module_position = '50';

	/**
	 * @var string Module name
	 *
	 * Only used if Module[ID]Name translation string is not found.
	 *
	 * You can use the following code to automatically derive it from your module's class name:
	 * preg_replace('/^mod/i', '', get_class($this))
	 */
	public $name;

	/**
	 * @var string[] Paths to create when module is activated
	 *
	 * e.g.: array('/mymodule/temp')
	 */
	public $dirs = array();

	/**
	 * @var array Module boxes
	 */
	public $boxes = array();

	/**
	 * @var array Module constants
	 */
	public $const = array();

	/**
	 * @var array Module cron jobs entries
	 */
	public $cronjobs = array();

	/**
	 * @var array Module access rights
	 */
	public $rights;

	/**
	 * @var string Module access rights family
	 */
	public $rights_class;

	/**
	 * @var array|int 	Module menu entries (1 means the menu entries are not declared into module descriptor but are hardcoded into menu manager)
	 */
	public $menu = array();

	/**
	 * @var array Module parts
	 *  array(
	 *      // Set this to 1 if module has its own trigger directory (/mymodule/core/triggers)
	 *      'triggers' => 0,
	 *      // Set this to 1 if module has its own login method directory (/mymodule/core/login)
	 *      'login' => 0,
	 *      // Set this to 1 if module has its own substitution function file (/mymodule/core/substitutions)
	 *      'substitutions' => 0,
	 *      // Set this to 1 if module has its own menus handler directory (/mymodule/core/menus)
	 *      'menus' => 0,
	 *      // Set this to 1 if module has its own theme directory (/mymodule/theme)
	 *      'theme' => 0,
	 *      // Set this to 1 if module overwrite template dir (/mymodule/core/tpl)
	 *      'tpl' => 0,
	 *      // Set this to 1 if module has its own barcode directory (/mymodule/core/modules/barcode)
	 *      'barcode' => 0,
	 *      // Set this to 1 if module has its own models directory (/mymodule/core/modules/xxx)
	 *      'models' => 0,
	 *      // Set this to relative path of css file if module has its own css file
	 *      'css' => '/mymodule/css/mymodule.css.php',
	 *      // Set this to relative path of js file if module must load a js on all pages
	 *      'js' => '/mymodule/js/mymodule.js',
	 *      // Set here all hooks context managed by module
	 *      'hooks' => array('hookcontext1','hookcontext2')
	 *  )
	 */
	public $module_parts = array();

	/**
	 * @var        string Module documents ?
	 * @deprecated Seems unused anywhere
	 */
	public $docs;

	/**
	 * @var        string ?
	 * @deprecated Seems unused anywhere
	 */
	public $dbversion = "-";

	/**
	 * @var string Error message
	 */
	public $error;

	/**
	 * @var string Module version
	 * @see http://semver.org
	 *
	 * The following keywords can also be used:
	 * 'development'
	 * 'experimental'
	 * 'dolibarr': only for core modules that share its version
	 * 'dolibarr_deprecated': only for deprecated core modules
	 */
	public $version;

	/**
	 * Module last version
	 * @var string $lastVersion
	 */
	public $lastVersion = '';

	/**
	 * true indicate this module need update
	 * @var bool $needUpdate
	 */
	public $needUpdate = false;

	/**
	 * @var string Module description (short text)
	 *
	 * Only used if Module[ID]Desc translation string is not found.
	 */
	public $description;

	/**
	 * @var   string Module description (long text)
	 * @since 4.0.0
	 *
	 * HTML content supported.
	 */
	public $descriptionlong;

	// For exports

	/**
	 * @var string Module export code
	 */
	public $export_code;

	/**
	 * @var string Module export label
	 */
	public $export_label;

	public $export_permission;
	public $export_fields_array;
	public $export_TypeFields_array; // Array of key=>type where type can be 'Numeric', 'Date', 'Text', 'Boolean', 'Status', 'List:xxx:login:rowid'
	public $export_entities_array;
	public $export_special_array; // special or computed field
	public $export_dependencies_array;
	public $export_sql_start;
	public $export_sql_end;
	public $export_sql_order;

	// For import

	/**
	 * @var string Module import code
	 */
	public $import_code;

	/**
	 * @var string Module import label
	 */
	public $import_label;

	/**
	 * @var string Module constant name
	 */
	public $const_name;

	/**
	 * @var bool Module can't be disabled
	 */
	public $always_enabled;

	/**
	 * @var int Module is enabled globally (Multicompany support)
	 */
	public $core_enabled;

	/**
	 * @var string Name of image file used for this module
	 *
	 * If file is in theme/yourtheme/img directory under name object_pictoname.png use 'pictoname'
	 * If file is in module/img directory under name object_pictoname.png use 'pictoname@module'
	 */
	public $picto;

	/**
	 * @var string[] List of config pages
	 *
	 * Name of php pages stored into module/admin directory, used to setup module.
	 * e.g.: "admin.php@module"
	 */
	public $config_page_url;

	/**
	 * @var string[] List of module class names that must be enabled if this module is enabled. e.g.: array('modAnotherModule', 'FR'=>'modYetAnotherModule')
	 * @see $requiredby
	 */
	public $depends;

	/**
	 * @var string[] List of module class names to disable if the module is disabled.
	 * @see $depends
	 */
	public $requiredby;

	/**
	 * @var string[] List of module class names as string this module is in conflict with.
	 * @see $depends
	 */
	public $conflictwith;

	/**
	 * @var string[] Module language files
	 */
	public $langfiles;

	/**
	 * @var array<string,string> Array of warnings to show when we activate the module
	 *
	 * array('always'='text') or array('FR'='text')
	 */
	public $warnings_activation;

	/**
	 * @var array<string,string> Array of warnings to show when we activate an external module
	 *
	 * array('always'='text') or array('FR'='text')
	 */
	public $warnings_activation_ext;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin;

	/**
	 * @var array Minimum version of Dolibarr required by module.
	 * e.g.: Dolibarr ≥ 3.6 = array(3, 6)
	 */
	public $need_dolibarr_version;

	/**
	 * @var bool Whether to hide the module.
	 */
	public $hidden = false;

	/**
	 * @var array To add new tabs on Dolibarr objects.
	 */
	public $tabs = array();

	/**
	 * @var array To add new dictionaries on Dolibarr objects.
	 */
	public $dictionaries = array();

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
			require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
			saturne_load_langs(['digiriskdolibarr@digiriskdolibarr']);
		} else {
			$this->error++;
			$this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'Digirisk', 'Saturne');
		}

		$langs->load("digiriskdolibarr@digiriskdolibarr");

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero       = 436302; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'digiriskdolibarr';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family          = "";
		$this->module_position = '';
		$this->familyinfo      = array('Evarisk' => array('position' => '01', 'label' => $langs->trans("Evarisk")));
		$this->name            = preg_replace('/^mod/i', '', get_class($this));
		$this->description     = $langs->trans('DigiriskDolibarrDescription');
		$this->descriptionlong = "Digirisk";
		$this->editor_name     = 'Evarisk';
		$this->editor_url      = 'https://evarisk.com';
		$this->version         = '9.11.1';
		$this->const_name      = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto           = 'digiriskdolibarr@digiriskdolibarr';

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
			'css' => array("/digiriskdolibarr/css/digiriskdolibarr.css.php"),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'completeTabsHead',
				'admincompany',
				'globaladmin',
				'emailtemplates',
				'mainloginpage',
				'ticketcard',
				'projecttaskcard',
				'projecttaskscard',
				'tasklist',
				'publicnewticketcard',
				'ticketlist',
				'thirdpartyticket',
				'projectticket',
				'projectcard',
				'projectcontactcard',
				'projecttasktime',
				'projectOverview',
				'userlist',
				'thirdpartycard',
				'contactcard',
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
			"/digiriskdolibarr/ticketstats",
			"/digiriskdolibarr/temp",
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
			"/ecm/digiriskdolibarr/ticketdocument",
			"/ecm/digiriskdolibarr/medias"
		);

		// Config pages.
		$this->config_page_url = array("setup.php@digiriskdolibarr");
		// Dependencies

		$this->hidden                  = false;
		$this->depends                 = array('modSaturne', 'modECM', 'modProjet', 'modSociete', 'modTicket', 'modCategorie', 'modFckeditor', 'modApi','modExport');
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
		$i = 0;
		$this->const = array(
			// CONST CONFIGURATION
			$i++ => array('DIGIRISKDOLIBARR_GENERAL_MEANS', 'chaine', $langs->transnoentities('GeneralMeansAtDisposalValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GENERAL_RULES', 'chaine', $langs->transnoentities('GeneralInstructionsValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_IDCC_DICTIONNARY', 'chaine', '', 'IDCC of company', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION', 'chaine', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PEE_ENABLED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PERCO_ENABLED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SECURITY_SOCIAL_CONF_UPDATED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_NB_EMPLOYEES', 'integer', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_NB_WORKED_HOURS', 'integer', '', '', 0, 'current'),

			// CONST RISK ASSESSMENTDOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENTDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', 'date', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE', 'date', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD', 'chaine', $langs->transnoentities('RiskAssessmentDocumentMethod'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES', 'chaine', $langs->transnoentities('RiskAssessmentDocumentSources'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES', 'chaine', $langs->transnoentities('RiskAssessmentDocumentImportantNote'), '', 0, 'current'),

			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON','chaine', 'mod_riskassessmentdocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/riskassessmentdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/riskassessmentdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_DEFAULT_MODEL', 'chaine', 'riskassessmentdocument_odt', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENTDOCUMENT_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS', 'integer', 1, '', 0, 'current'),

			// CONST LEGAL DISPLAY
			$i++ => array('DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION', 'chaine', $langs->transnoentities('LocationOfDetailedInstructionsValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT', 'chaine', $langs->transnoentities('PermanentDerogationValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL', 'chaine', $langs->transnoentities('OccasionalDerogationValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE', 'chaine', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION', 'chaine', $langs->transnoentities('CollectiveAgreementValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DUER_LOCATION','chaine', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RULES_LOCATION', 'chaine', $langs->transnoentities('RulesOfProcedureValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE', 'chaine', $langs->transnoentities('ParticipationAgreementValue'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIRST_AID', 'chaine', $langs->transnoentities('FirstAidValue'), '', 0, 'current'),

			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LEGALDISPLAY_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON', 'chaine', 'mod_legaldisplay_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/legaldisplay/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LEGALDISPLAY_CUSTOM_ADDON_ODT_PATH','chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/legaldisplay/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LEGALDISPLAY_DEFAULT_MODEL', 'chaine', 'legaldisplay_odt', '', 0, 'current'),

			// CONST INFORMATIONS SHARING
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_INFORMATIONSSHARING_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON', 'chaine', 'mod_informationssharing_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/informationssharing/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/informationssharing/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_INFORMATIONSSHARING_DEFAULT_MODEL', 'chaine', 'informationssharing_odt', '', 0, 'current'),

			// CONST LISTING RISKS ACTION
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSACTION_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON', 'chaine', 'mod_listingrisksaction_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksaction/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksaction/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL', 'chaine', 'listingrisksaction_odt', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSACTION_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'),

			// CONST LISTING RISKS PHOTO
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSPHOTO_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON', 'chaine', 'mod_listingrisksphoto_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksphoto/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksphoto/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_DEFAULT_MODEL', 'chaine', 'listingrisksphoto_odt', '', 0, 'current'),

			// CONST GROUPMENT DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_GROUPMENTDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON', 'chaine', 'mod_groupmentdocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/groupmentdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/groupmentdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL', 'chaine', 'groupmentdocument_odt', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'),

			// CONST WORKUNIT DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_WORKUNITDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON', 'chaine', 'mod_workunitdocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/workunitdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/workunitdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL', 'chaine', 'workunitdocument_odt', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'),

			// CONST PREVENTION PLAN
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_PENDINGSIGNATURE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_INPROGRESS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_LOCKED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_ARCHIVED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_SENTBYMAIL', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANSIGNATURE_ADDATTENDANT', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON', 'chaine', 'mod_preventionplan_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE', 'integer', 0, '', 0, 'current'),

			// CONST PREVENTION PLAN DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON', 'chaine', 'mod_preventionplandocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/specimen/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/preventionplandocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL', 'chaine', 'preventionplandocument_odt', '', 0, 'current'),

			// CONST FIRE PERMIT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE','integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_PENDINGSIGNATURE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_INPROGRESS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_LOCKED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_ARCHIVED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_SENTBYMAIL', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITSIGNATURE_ADDATTENDANT', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMIT_ADDON', 'chaine', 'mod_firepermit_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMIT_PROJECT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE', 'integer', 0, '', 0, 'current'),

			// CONST FIRE PERMIT DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON', 'chaine', 'mod_firepermitdocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/firepermitdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/firepermitdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL', 'chaine', 'firepermitdocument_odt', '', 0, 'current'),

			//CONST DIGIRISKELEMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT', 'integer', 0, '', 0, 'current'),

			// CONST GROUPMENT
			$i++ => array('DIGIRISKDOLIBARR_GROUPMENT_ADDON', 'chaine', 'mod_groupment_standard', '', 0, 'current'),

			// CONST WORKUNIT
			$i++ => array('DIGIRISKDOLIBARR_WORKUNIT_ADDON', 'chaine', 'mod_workunit_standard', '', 0, 'current'),

			// CONST EVALUATOR
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_EVALUATOR_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_EVALUATOR_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_EVALUATOR_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_EVALUATOR_ADDON', 'chaine', 'mod_evaluator_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_EVALUATOR_DURATION', 'integer', 15, '', 0, 'current'),

			// CONST RISK ANALYSIS

			// CONST RISK
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISK_ADDON', 'chaine', 'mod_risk_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISK_DESCRIPTION', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MOVE_RISKS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_RISKS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_SHARED_RISKS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN', 'integer', 1, '', 0, 'current'),

			// CONST RISK ASSESSMENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON', 'chaine', 'mod_riskassessment_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS', 'integer', 0, '', 0, 'current'),

			// CONST RISK SIGN
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_RISKSIGN_ADDON', 'chaine', 'mod_risksign_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_RISKSIGNS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS', 'integer', 0, '', 0, 'current'),

			// CONST PROJET
			$i++ => array('DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DU_PROJECT', 'integer', 0, '', 0, 'current'),

			// CONST TASK
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TASK_MANAGEMENT', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_TASK_START_DATE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_TASK_END_DATE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_TASKS_DONE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_ALL_TASKS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION', 'integer', 15, '', 0, 'current'),

			// CONST TASK TIMESPENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_DELETE', 'integer', 1, '', 0, 'current'),

			// CONST PREVENTION PLAN LINE
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON', 'chaine', 'mod_preventionplandet_standard', '', 0, 'current'),

			// CONST FIRE PERMIT LINE
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_FIREPERMITDET_ADDON', 'chaine', 'mod_firepermitdet_standard', '', 0, 'current'),

			//CONST TICKET & REGISTERS
			$i++ => array('DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 'integer', 0, '', 0, 0),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', 'chaine', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', 'chaine', $langs->trans('Registre'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', 'chaine', $langs->trans('Pertinence'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PROJECT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', 'chaine', $langs->trans('YouMustNotifyYourHierarchy'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_REQUIRED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_HIDE_REF', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PHOTO_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_EMAIL_VISIBLE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_EMAIL_REQUIRED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_FIRSTNAME_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_FIRSTNAME_REQUIRED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_LASTNAME_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_LASTNAME_REQUIRED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PHONE_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PHONE_REQUIRED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_LOCATION_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_LOCATION_REQUIRED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DATE_VISIBLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DATE_REQUIRED', 'integer', 1, '', 0, 'current'),

			// CONST MODULE
			$i++ => array('DIGIRISKDOLIBARR_SUBPERMCATEGORY_FOR_DOCUMENTS', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_VERSION','chaine', $this->version, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DB_VERSION', 'chaine', $this->version, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_THIRDPARTY_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_CONTACTS_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_USERAPI_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_READERGROUP_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_USERGROUP_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ADMINUSERGROUP_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_READERGROUP_UPDATED', 'integer', 2, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_USERGROUP_UPDATED', 'integer', 3, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 'integer', 3, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_USE_CAPTCHA', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACTIVE_STANDARD', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TRIGGERS_UPDATED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_CONF_BACKWARD_COMPATIBILITY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ENCODE_BACKWARD_COMPATIBILITY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_CUSTOM_DOCUMENTS_SET', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_OPENINGHOURS_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'),

			//CONST TICKET & REGISTERS
			$i++ => array('DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 'integer', 0, '', 0, 0),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', 'chaine', '', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', 'chaine', $langs->trans('Registre'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', 'chaine', $langs->trans('Pertinence'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_PROJECT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', 'chaine', $langs->trans('YouMustNotifyYourHierarchy'), '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_EMAIL_REQUIRED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_REQUIRED', 'integer', 1, '', 0, 'current'),

			// CONST ACCIDENT
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENT_ADDON', 'chaine', 'mod_accident_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENT_PROJECT', 'integer', 0, '', 0, 'current'),

			// CONST ACCIDENT LINE
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_WORKSTOP_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_WORKSTOP_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_WORKSTOP_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_LESION_CREATE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_LESION_MODIFY', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_LESION_DELETE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENT_WORKSTOP_ADDON', 'chaine', 'mod_accident_workstop_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENT_LESION_ADDON', 'chaine', 'mod_accident_lesion_standard', '', 0, 'current'),

			// CONST TICKET DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/ticketdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON', 'chaine', 'mod_ticketdocument_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/ticketdocument/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TICKETDOCUMENT_DEFAULT_MODEL', 'chaine', 'ticketdocument_odt', '', 0, 'current'),

			// CONST PROJECT DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_PROJECTDOCUMENT_ADDON', 'chaine', 'mod_projectdocument_standard', '', 0, 'current'),

//			// CONST ACCIDENT DOCUMENT
//			$i++ => array('MAIN_AGENDA_ACTIONAUTO_ACCIDENTDOCUMENT_CREATE', 'integer', 1, '', 0, 'current'),
//			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_ADDON', 'chaine', 'mod_accidentdocument_standard', '', 0, 'current'),
//			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_ADDON_ODT_PATH','chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/accidentdocument/', '', 0, 'current'),
//			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/accidentdocument/', '', 0, 'current'),
//			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTDOCUMENT_DEFAULT_MODEL', 'chaine', 'accidentdocument_odt', '', 0, 'current'),

			// GENERAL CONSTS
			$i++ => array('MAIN_ODT_AS_PDF', 'chaine', 'libreoffice', '', 0, 'current'),
			$i++ => array('MAIN_USE_EXIF_ROTATION', 'integer', 1, '', 0, 'current'),
			$i++ => array('MAIN_EXTRAFIELDS_USE_SELECT2', 'integer', 1, '', 0, 'current'),

			// MENU
			$i++ => array('DIGIRISKDOLIBARR_DIGIRISKSTANDARD_MENU_UPDATED', 'integer', 0, '', 0, 'current'),

			// CONST TOOLS
			$i++ => array('DIGIRISKDOLIBARR_TOOLS_ADVANCED_IMPORT', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TOOLS_TREE_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TOOLS_RISKS_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TOOLS_RISKSIGNS_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_TOOLS_GLOBAL_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'),

			// CONST SIGNATURE
			$i++ => array('DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO', 'integer', 1, '', 0, 'current'),

			// CONST DIGIRISK DOCUMENTS
			$i++ => array('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKSIGNATURE_SIGNED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKSIGNATURE_PENDING_SIGNATURE', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKSIGNATURE_ABSENT', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKSIGNATURE_DELETED', 'integer', 1, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_AUTOMATIC_PDF_GENERATION', 'integer', 0, '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_MANUAL_PDF_GENERATION', 'integer', 0, '', 0, 'current'),

			// CONST ACCIDENT INVESTIGATION DOCUMENT
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENT_INVESTIGATION_ADDON', 'chaine', 'mod_accident_investigation_standard', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON_ODT_PATH','chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/accident_investigation/', '', 0, 'current'),
			$i++ => array('DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT/ecm/digiriskdolibarr/accident_investigation/', '', 0, 'current'),
			$i   => array('DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_DOCUMENT_DEFAULT_MODEL', 'chaine', 'template_accidentinvestigation_odt', '', 0, 'current')
		);

		if ( ! isset($conf->digiriskdolibarr) || ! isset($conf->digiriskdolibarr->enabled) ) {
			$conf->digiriskdolibarr          = new stdClass();
			$conf->digiriskdolibarr->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		$pictopath = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr32px.png', 1);
		$pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoDigirisk');
		$this->tabs[] = array('data' => 'mycompany_admin:+security:'. $pictoDigirisk . $langs->trans('Security').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/securityconf.php');  			// To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'mycompany_admin:+social:'. $pictoDigirisk .$langs->trans('Social').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/socialconf.php');  					// To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'thirdparty:+openinghours:'. $pictoDigirisk .$langs->trans('OpeningHours').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/openinghours_card.php?id=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'user:+participation:'. $pictoDigirisk .$langs->trans('GP/UTParticipation').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php?fromid=__ID__'); // To add a new tab identified by code tabname1
		$this->tabs[] = array('data' => 'user:+accidents:'. $pictoDigirisk .$langs->trans('Accidents').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/accident/accident_list.php?fromiduser=__ID__'); // To add a new tab identified by code tabname1

		// To remove an existing tab identified by code tabname
		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'digiriskdolibarr@digiriskdolibarr',
			// List of tables we want to see into dictonnary editor
			'tabname' => array(
				MAIN_DB_PREFIX . "c_conventions_collectives",
				MAIN_DB_PREFIX . "c_relative_location",
				MAIN_DB_PREFIX . "c_lesion_localization",
				MAIN_DB_PREFIX . "c_lesion_nature",
				MAIN_DB_PREFIX . "c_digiriskdolibarr_action_trigger"
			),
			// Label of tables
			'tablib' => array(
				"CollectiveAgreement",
				"RelativeLocation",
				"LesionLocalization",
				"LesionNature",
				"DigiriskDolibarrActionTrigger"
			),
			// Request to select fields
			'tabsql' => array(
				'SELECT f.rowid as rowid, f.code, f.libelle, f.active FROM ' . MAIN_DB_PREFIX . 'c_conventions_collectives as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'c_relative_location as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'c_lesion_localization as f',
				'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'c_lesion_nature as f',
				'SELECT f.rowid as rowid, f.code, f.label, f.description FROM ' . MAIN_DB_PREFIX . 'c_digiriskdolibarr_action_trigger as f'
			),
			// Sort order
			'tabsqlsort' => array(
				"code ASC",
				"label ASC",
				"label ASC",
				"label ASC",
				"code ASC"
			),
			// List of fields (result of select to show dictionary)
			'tabfield' => array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description",
				"code,label,description"
			),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue' => array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description",
				"code,label,description"
			),
			// List of fields (list of fields for insert)
			'tabfieldinsert' => array(
				"code,libelle",
				"ref,label,description",
				"ref,label,description",
				"ref,label,description",
				"code,label,description"
			),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid' => array(
				"rowid",
				"rowid",
				"rowid",
				"rowid",
				"rowid"
			),
			// Condition to show each dictionary
			'tabcond' => array(
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled,
				$conf->digiriskdolibarr->enabled
			)
		);

		// Boxes/Widgets
		$this->boxes = array(
			  0 => array(
				  'file' => 'box_riskassessmentdocument@digiriskdolibarr',
				  'note' => '',
				  'enabledbydefaulton' => 'Home',
			  )
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array();

		// Permissions provided by this module
		$this->rights = array();
		$r            = 0;

		/* module PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('LireDigirisk');
		$this->rights[$r][4] = 'lire';
		//$this->rights[$r][5] = 1;
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadDigirisk');
		$this->rights[$r][4] = 'read';
		//$this->rights[$r][5] = 1;
		$r++;

		/* RISK ASSESSMENT DOCUMENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteRiskAssessmentDocument');
		$this->rights[$r][4] = 'riskassessmentdocument';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LEGAL DISPLAY PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('ReadLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('DeleteLegalDisplay');
		$this->rights[$r][4] = 'legaldisplay';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* INFORMATIONS SHARING PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteInformationsSharing');
		$this->rights[$r][4] = 'informationssharing';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* FIRE PERMIT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteFirePermit');
		$this->rights[$r][4] = 'firepermit';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* PREVENTION PLAN PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('ReadPreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreatePreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('DeletePreventionPlan');
		$this->rights[$r][4] = 'preventionplan';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* GP/UT ORGANISATION PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('ReadDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('DeleteDigiriskElement');
		$this->rights[$r][4] = 'digiriskelement';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* RISKS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteDigiriskRisk');
		$this->rights[$r][4] = 'risk';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LISTING RISKS ACTION PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteListingRisksAction');
		$this->rights[$r][4] = 'listingrisksaction';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* LISTING RISKS PHOTO PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteListingRisksPhoto');
		$this->rights[$r][4] = 'listingrisksphoto';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* RISK SIGN PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteDigiriskRiskSign');
		$this->rights[$r][4] = 'risksign';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* EVALUATOR PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('ReadEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('DeleteEvaluator');
		$this->rights[$r][4] = 'evaluator';
		$this->rights[$r][5] = 'delete';
		$r++;

		/* ADMINPAGE PANEL ACCESS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('ReadAdminPage');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read';
		$r++;

		/* API PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('GetAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('PostAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'write';
		$r++;

		/* ACCIDENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteAccident');
		$this->rights[$r][4] = 'accident';
		$this->rights[$r][5] = 'delete';

		/* ACCIDENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('ReadAccidentInvestigation');
		$this->rights[$r][4] = 'accident_investigation';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->transnoentities('CreateAccidentInvestigation');
		$this->rights[$r][4] = 'accident_investigation';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = $langs->trans('DeleteAccidentInvestigation');
		$this->rights[$r][4] = 'accident_investigation';
		$this->rights[$r][5] = 'delete';

		// Main menu entries to add
		$this->menu       = array();
		$r                = 0;
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
			'titre'    => $langs->trans('Dashboard'),
			'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
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

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', 										// This is a Left menu entry
			'titre'    => $langs->trans('RiskAssessmentDocument'),
			'prefix'   => '<i class="fas fa-exclamation-triangle pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskstandard',
			'url'      => '/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?id=' . $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD,
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->riskassessmentdocument->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('RiskList'),
			'prefix'   => '<i class="fas fa-list pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisklistingrisk',
			'url'      => '/digiriskdolibarr/view/digiriskelement/risk_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->risk->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('PreventionPlan'),
			'prefix'   => '<i class="fas fa-info pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskpreventionplan',
			'url'      => '/digiriskdolibarr/view/preventionplan/preventionplan_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->preventionplan->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('FirePermit'),
			'prefix'   => '<i class="fas fa-fire-alt pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskfirepermit',
			'url'      => '/digiriskdolibarr/view/firepermit/firepermit_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->firepermit->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('Accident'),
			'prefix'   => '<i class="fas fa-user-injured pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskaccident',
			'url'      => '/digiriskdolibarr/view/accident/accident_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->accident->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    =>  $langs->trans('Users'),
			'prefix'   => '<i class="fas fa-user pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskusers',
			'url'      => '/digiriskdolibarr/view/digiriskusers.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->adminpage->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('ActionPlan'),
			'prefix'   => '<i class="fas fa-tasks pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskactionplan',
			'url'      => '/projet/tasks.php?id=' . $conf->global->DIGIRISKDOLIBARR_DU_PROJECT,
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->digiriskelement->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '_blank',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('Tools'),
			'prefix'   => '<i class="fas fa-wrench pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisktools',
			'url'      => '/digiriskdolibarr/view/digirisktools.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->adminpage->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', 										// This is a Left menu entry
			'titre'    => $langs->trans('Organization'),
			'prefix'   => '<i class="fas fa-network-wired pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskorganization',
			'url'      => '/digiriskdolibarr/view/digiriskelement/digiriskelement_organization.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->digiriskelement->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('DigiriskConfig'),
			'prefix'   => '<i class="fas fa-cog pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskconfig',
			'url'      => '/digiriskdolibarr/admin/setup.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->adminpage->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('DigiriskConfigSociety'),
			'prefix'   => '<i class="fas fa-building pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisksocietyconfig',
			'url'      => '/admin/company.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $user->admin',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->admin',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('TicketPublicInterface'),
			'prefix'   => '<i class="fa fa-ticket pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/custom/digiriskdolibarr/public/ticket/create_ticket.php' . ((!$conf->multicompany->enabled) ? '?entity=' . $conf->entity : ''),
			'langs'    => '',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => 1,			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('MinimizeMenu'),
			'prefix'   => '<i class="fas fa-chevron-circle-left pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '',
			'langs'    => '',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => 1,			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=ticket',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('DashBoard'),
			'prefix'   => $pictoDigirisk,
			'mainmenu' => 'ticket',
			'leftmenu' => 'ticketstats',
			'url'      => '/digiriskdolibarr/view/ticket/ticketstats.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->ticket->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->ticket->read && $user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskaccident',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => '<i class="fas fa-tasks pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('AccidentInvestigation'),
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskaccident',
			'url'      => '/digiriskdolibarr/view/accident_investigation/accident_investigation_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 48520 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->saturne->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		);

//		$this->menu[$r++] = array(
//			'fk_menu'  => 'fk_mainmenu=ticket',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
//			'type'     => 'left',			                // This is a Left menu entry
//			'titre'    => $langs->transnoentities('DashBoard'),
//			'prefix'   => $pictoDigirisk,
//			'mainmenu' => 'ticket',
//			'leftmenu' => 'dashboardticket',
//			'url'      => '/digiriskdolibarr/view/ticket/dashboard_ticket.php',
//			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//			'position' => 48520 + $r,
//			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->ticket->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
//			'perms'    => '$user->rights->ticket->read && $user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
//			'target'   => '',
//			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
//		);

		// Exports profiles provided by this module
		$r = 1;

		$this->export_code[$r] = $this->rights_class . '_ticket';
		$this->export_label[$r] = 'Ticket'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'Ticket';
		$this->export_enabled[$r] = '!empty($conf->ticket->enabled)';
		$this->export_permission[$r] = array(array("ticket", "manage"));
		$this->export_fields_array[$r] = array(
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 's.fk_pays'=>'Country',
			's.phone'=>'Phone', 's.email'=>'Email', 's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.code_compta'=>'CustomerAccountancyCode', 's.code_compta_fournisseur'=>'SupplierAccountancyCode',
			'cat.rowid'=>"CategId", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory",
			't.rowid'=>"Id", 't.ref'=>"Ref", 't.track_id'=>"TicketTrackId", 't.datec'=>"DateCreation", 't.origin_email'=>"OriginEmail", 't.subject'=>"Subject", 't.message'=>"Message", 't.fk_statut'=>"Status", 't.resolution'=>"Resolution", 't.type_code'=>"Type", 't.category_code'=>"TicketCategory", 't.severity_code'=>"Severity",
		);
		$this->export_TypeFields_array[$r] = array(
			's.rowid'=>"List:societe:nom::thirdparty", 's.nom'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 's.fk_pays'=>'List:c_country:label',
			's.phone'=>'Text', 's.email'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text', 's.ape'=>'Text', 's.idprof4'=>'Text', 's.code_compta'=>'Text', 's.code_compta_fournisseur'=>'Text',
			'cat.description'=>"Text", 'cat.fk_parent'=>'List:categorie:label:rowid',
			't.rowid'=>"List:ticket:ref::ticket", 't.entity'=>'Numeric', 't.ref'=>"Text", 't.track_id'=>"Text", 't.datec'=>"Date", 't.origin_email'=>"Text", 't.subject'=>"Text", 't.message'=>"Text", 't.fk_statut'=>"Numeric", 't.resolution'=>"Text", 't.type_code'=>"Text", 't.category_code'=>"Text", 't.severity_code'=>"Text",
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 's.fk_pays'=>'company',
			's.phone'=>'company', 's.email'=>'company', 's.siren'=>'company', 's.siret'=>'company', 's.ape'=>'company', 's.idprof4'=>'company', 's.code_compta'=>'company', 's.code_compta_fournisseur'=>'company',
			'cat.rowid'=>'category', 'cat.description'=>'category', 'cat.fk_parent'=>'category'
		);
		// Add multicompany field
		if (!empty($conf->global->MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED)) {
			$nbofallowedentities = count(explode(',', getEntity('ticket'))); // If ticket are shared, nb will be > 1
			if (!empty($conf->multicompany->enabled) && $nbofallowedentities > 1) {
				$this->export_fields_array[$r] += array('t.entity'=>'Entity');
			}
		}
		$this->export_fields_array[$r] = array_merge($this->export_fields_array[$r], array('group_concat(cat.label)'=>'Categories'));
		$this->export_TypeFields_array[$r] = array_merge($this->export_TypeFields_array[$r], array("group_concat(cat.label)"=>'Text'));
		$this->export_entities_array[$r] = array_merge($this->export_entities_array[$r], array("group_concat(cat.label)"=>'category'));
		$this->export_dependencies_array[$r] = array('category'=>'t.rowid');
		$keyforselect = 'Ticket';
		$keyforelement = 'Ticket';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM '.MAIN_DB_PREFIX.'ticket as t';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as extra ON t.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_ticket as ct ON ct.fk_ticket = t.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as cat ON ct.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON t.fk_soc = s.rowid';
		$this->export_sql_end[$r] .= " WHERE t.entity IN (".getEntity('ticket').")";

		$this->export_sql_order[$r] = ' GROUP BY t.ref';

		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

		$r++;
		$langs->load("categories");
		$this->export_code[$r] = $this->rights_class.'_ticket_categories';
		$this->export_label[$r] = 'CatTicketsList';
		$this->export_icon[$r] = 'category';
		$this->export_enabled[$r] = '!empty($conf->ticket->enabled)';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("ticket", "manage"));
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory", 't.rowid'=>'TicketId', 't.ref'=>'Ref', 't.datec'=>"DateCreation", 't.message' => 'Message', 's.rowid'=>"IdThirdParty", 's.nom'=>"Name");
		$this->export_TypeFields_array[$r] = array('cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'List:categorie:label:rowid', 't.ref'=>'Text', 't.datec'=>"Date", 't.message'=>"Text", 's.rowid'=>"List:societe:nom:rowid", 's.nom'=>"Text");
		$this->export_entities_array[$r] = array('t.rowid'=>'ticket', 't.ref'=>'ticket', 't.datec'=>'ticket', 't.message' => 'ticket', 's.rowid'=>"company", 's.nom'=>"company"); // We define here only fields that use another picto

		$keyforselect = 'Ticket';
		$keyforelement = 'Ticket';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_ticket as ct ON ct.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'ticket as t ON t.rowid = ct.fk_ticket';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ticket_extrafields as extra ON extra.fk_object = t.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = t.fk_soc';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 12';
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                1 if OK, 0 if KO
	 * @throws Exception
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user;

		$langs->load("digiriskdolibarr@digiriskdolibarr");

		if ( $conf->global->DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE == 0 ) {
			require_once __DIR__ . '/../../class/preventionplan.class.php';
			require_once __DIR__ . '/../../class/firepermit.class.php';

			$preventionPlanSignature     = new PreventionPlanSignature($this->db);
			$preventionPlanSignatureList = $preventionPlanSignature->fetchAll('', '', 0, 0, array(), 'AND', 'digiriskdolibarr_preventionplan_signature');
			if (is_array($preventionPlanSignatureList) && count($preventionPlanSignatureList) > 0) {
				foreach ($preventionPlanSignatureList as $preventionPlanSignature) {
					$preventionPlanSignature->create($user, 1);
				}
			}

			$firePermitSignature     = new FirePermitSignature($this->db);
			$firePermitSignatureList = $firePermitSignature->fetchAll('', '', 0, 0, array(), 'AND', 'digiriskdolibarr_firepermit_signature');
			if (is_array($firePermitSignatureList) && count($firePermitSignatureList) > 0) {
				foreach ($firePermitSignatureList as $firePermitSignature) {
					$firePermitSignature->create($user, 1);
				}
			}

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE', 1, 'integer', 0, '', $conf->entity);
		}

		$sql = array();
		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/digiriskdolibarr/sql/' . $subFolder . '/');
			}
		}

		$this->_load_tables('/digiriskdolibarr/sql/');

		delDocumentModel('informationssharing_odt', 'informationssharing');
		delDocumentModel('legaldisplay_odt', 'legaldisplay');
		delDocumentModel('firepermitdocument_odt', 'firepermitdocument');
		delDocumentModel('preventionplandocument_odt', 'preventionplandocument');
		delDocumentModel('preventionplandocument_specimen_odt', 'preventionplandocumentspecimen');
		delDocumentModel('groupmentdocument_odt', 'groupmentdocument');
		delDocumentModel('workunitdocument_odt', 'workunitdocument');
		delDocumentModel('listingrisksaction_odt', 'listingrisksaction');
		delDocumentModel('listingrisksphoto_odt', 'listingrisksphoto');
		delDocumentModel('riskassessmentdocument_odt', 'riskassessmentdocument');
		delDocumentModel('ticketdocument_odt', 'ticketdocument');
		delDocumentModel('orque_projectdocument', 'project');
		delDocumentModel('accidentinvestigationdocument_odt', 'accidentinvestigationdocument');

		addDocumentModel('informationssharing_odt', 'informationssharing', 'ODT templates', 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH');
		addDocumentModel('legaldisplay_odt', 'legaldisplay', 'ODT templates', 'DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH');
		addDocumentModel('firepermitdocument_odt', 'firepermitdocument', 'ODT templates', 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_odt', 'preventionplandocument', 'ODT templates', 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_specimen_odt', 'preventionplandocumentspecimen', 'ODT templates', 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH');
		addDocumentModel('groupmentdocument_odt', 'groupmentdocument', 'ODT templates', 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('workunitdocument_odt', 'workunitdocument', 'ODT templates', 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('listingrisksaction_odt', 'listingrisksaction', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH');
		addDocumentModel('listingrisksphoto_odt', 'listingrisksphoto', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH');
		addDocumentModel('riskassessmentdocument_odt', 'riskassessmentdocument', 'ODT templates', 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('ticketdocument_odt', 'ticketdocument', 'ODT templates', 'DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('orque_projectdocument', 'project', 'orque');
		addDocumentModel('accidentinvestigationdocument_odt', 'accidentinvestigationdocument', 'ODT templates', 'DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON_ODT_PATH');

		if ( $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH == 0 ) {
			require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

			$trashRef                      = 'GP0';
			$digiriskelement               = new Groupment($this->db);
			$digiriskelement->ref          = $trashRef;
			$digiriskelement->label        = $langs->trans('HiddenElements');
			$digiriskelement->element_type = 'groupment';
			$digiriskelement->ranks        = 0;
			$digiriskelement->description  = $langs->trans('TrashGroupment');
			$digiriskelement->status       = 0;
			$trash_id                      = $digiriskelement->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH', $trash_id, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD == 0 ) {
			require_once __DIR__ . '/../../class/digiriskstandard.class.php';

			$digiriskstandard                = new DigiriskStandard($this->db);
			$digiriskstandard->ref           = 'DU';
			$digiriskstandard->description   = 'DUDescription';
			$digiriskstandard->date_creation = dol_now();
			$digiriskstandard->status        = 1;

			$standard_id = $digiriskstandard->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_ACTIVE_STANDARD', $standard_id, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_THIRDPARTY_SET == 0 ) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);

			$labour_doctor         = $societe;
			$labour_doctor->name   = $langs->trans('LabourDoctorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$labour_doctor->client = 0;
			$labour_doctor->phone  = '';
			$labour_doctor->url    = '';
			$labour_doctorID       = $labour_doctor->create($user);

			$labour_inspector         = $societe;
			$labour_inspector->name   = $langs->trans('LabourInspectorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$labour_inspector->client = 0;
			$labour_inspector->phone  = '';
			$labour_inspector->url    = $langs->trans('UrlLabourInspector');
			$labour_inspectorID       = $labour_inspector->create($user);

			$samu         = $societe;
			$samu->name   = $langs->trans('SAMU') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$samu->client = 0;
			$samu->phone  = '15';
			$samu->url    = '';
			$samuID       = $samu->create($user);

			$pompiers         = $societe;
			$pompiers->name   = $langs->trans('Pompiers') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$pompiers->client = 0;
			$pompiers->phone  = '18';
			$pompiers->url    = '';
			$pompiersID       = $pompiers->create($user);

			$police         = $societe;
			$police->name   = $langs->trans('Police') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$police->client = 0;
			$police->phone  = '17';
			$police->url    = '';
			$policeID       = $police->create($user);

			$emergency         = $societe;
			$emergency->name   = $langs->trans('AllEmergencies') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$emergency->client = 0;
			$emergency->phone  = '112';
			$emergency->url    = '';
			$emergencyID       = $emergency->create($user);

			$rights_defender         = $societe;
			$rights_defender->name   = $langs->trans('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$rights_defender->client = 0;
			$rights_defender->phone  = '';
			$rights_defender->url    = '';
			$rights_defenderID       = $rights_defender->create($user);

			$poison_control_center         = $societe;
			$poison_control_center->name   = $langs->trans('PoisonControlCenter') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$poison_control_center->client = 0;
			$poison_control_center->phone  = '';
			$poison_control_center->url    = '';
			$poison_control_centerID       = $poison_control_center->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'LabourDoctorSociety',  'societe', array($labour_doctorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'LabourInspectorSociety',  'societe', array($labour_inspectorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'Police',  'societe', array($policeID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'SAMU',  'societe', array($samuID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'Pompiers',  'societe', array($pompiersID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'AllEmergencies',  'societe', array($emergencyID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'RightsDefender',  'societe', array($rights_defenderID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'PoisonControlCenter',  'societe', array($poison_control_centerID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 3, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_THIRDPARTY_SET == 1) {
			//Install after 8.1.2

			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);

			$labour_doctor         = $societe;
			$labour_doctor->name   = $langs->trans('LabourDoctorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$labour_doctor->client = 0;
			$labour_doctor->phone  = '';
			$labour_doctor->url    = '';
			$labour_doctorID       = $labour_doctor->create($user);

			$labour_inspector         = $societe;
			$labour_inspector->name   = $langs->trans('LabourInspectorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$labour_inspector->client = 0;
			$labour_inspector->phone  = '';
			$labour_inspector->url    = $langs->trans('UrlLabourInspector');
			$labour_inspectorID       = $labour_inspector->create($user);

			$rights_defender         = $societe;
			$rights_defender->name   = $langs->transnoentities('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$rights_defender->client = 0;
			$rights_defender->phone  = '';
			$rights_defender->url    = '';
			$rights_defenderID       = $rights_defender->create($user);

			$poison_control_center         = $societe;
			$poison_control_center->name   = $langs->trans('PoisonControlCenter') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$poison_control_center->client = 0;
			$poison_control_center->phone  = '';
			$poison_control_center->url    = '';
			$poison_control_centerID       = $poison_control_center->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'LabourDoctorSociety',  'societe', array($labour_doctorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'LabourInspectorSociety',  'societe', array($labour_inspectorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'RightsDefender',  'societe', array($rights_defenderID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'PoisonControlCenter',  'societe', array($poison_control_centerID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 3, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_THIRDPARTY_SET == 2) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);

			$labour_doctor         = $societe;
			$labour_doctor->name   = $langs->trans('LabourDoctorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$labour_doctor->client = 0;
			$labour_doctor->phone  = '';
			$labour_doctor->url    = '';
			$labour_doctorID       = $labour_doctor->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db, 1,  'LabourDoctorSociety',  'societe', array($labour_doctorID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 3, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_CONTACTS_SET == 0 ) {
			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$contact   = new Contact($this->db);
			$resources = new DigiriskResources($this->db);

			$allLinks  = $resources->digirisk_dolibarr_fetch_resource('LabourDoctorSociety');

			$labour_doctor            = $contact;
			$labour_doctor->socid     = $allLinks;
			$labour_doctor->firstname = $langs->transnoentities('LabourDoctorFirstName');
			$labour_doctor->lastname  = $langs->trans('LabourDoctorLastName');
			$labour_doctorID          = $labour_doctor->create($user);

			$allLinks  = $resources->digirisk_dolibarr_fetch_resource('LabourInspectorSociety');

			$labour_inspector            = $contact;
			$labour_inspector->socid     = $allLinks;
			$labour_inspector->firstname = $langs->trans('LabourInspectorFirstName');
			$labour_inspector->lastname  = $langs->trans('LabourInspectorLastName');
			$labour_inspectorID          = $labour_inspector->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db, 1, 'LabourDoctorContact', 'socpeople', array($labour_doctorID), $conf->entity);
			$resources->digirisk_dolibarr_set_resources($this->db, 1, 'LabourInspectorContact', 'socpeople', array($labour_inspectorID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CONTACTS_SET', 2, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_CONTACTS_SET == 2) {
			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$contact   = new Contact($this->db);
			$resources = new DigiriskResources($this->db);
			$allLinks  = $resources->digirisk_dolibarr_fetch_resource('LabourDoctorSociety');

			$labour_doctor            = $contact;
			$labour_doctor->socid     = $allLinks;
			$labour_doctor->firstname = $langs->transnoentities('LabourDoctorFirstName');
			$labour_doctor->lastname  = $langs->trans('LabourDoctorLastName');
			$labour_doctorID          = $labour_doctor->create($user);

			$resources->digirisk_dolibarr_set_resources($this->db, 1, 'LabourDoctorContact', 'socpeople', array($labour_doctorID), $conf->entity);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CONTACTS_SET', 3, 'integer', 0, '', $conf->entity);
		}

		if ( $conf->global->DIGIRISKDOLIBARR_THIRDPARTY_UPDATED == 0 ) {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			require_once __DIR__ . '/../../class/digiriskresources.class.php';

			$societe   = new Societe($this->db);
			$resources = new DigiriskResources($this->db);
			$labour_inspectorID = $resources->digirisk_dolibarr_fetch_resource('LabourInspectorSociety');
			$societe->fetch($labour_inspectorID);
			$societe->name = $langs->trans('LabourInspectorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$policeID = $resources->digirisk_dolibarr_fetch_resource('Police');
			$societe->fetch($policeID);
			$societe->name = $langs->trans('Police') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$samuID = $resources->digirisk_dolibarr_fetch_resource('SAMU');
			$societe->fetch($samuID);
			$societe->name = $langs->trans('SAMU') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$pompiersID = $resources->digirisk_dolibarr_fetch_resource('Pompiers');
			$societe->fetch($pompiersID);
			$societe->name = $langs->trans('Pompiers') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$emergencyID = $resources->digirisk_dolibarr_fetch_resource('AllEmergencies');
			$societe->fetch($emergencyID);
			$societe->name = $langs->trans('AllEmergencies') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$rights_defenderID = $resources->digirisk_dolibarr_fetch_resource('RightsDefender');
			$societe->fetch($rights_defenderID);
			$societe->name = $langs->transnoentities('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			$poison_control_centerID = $resources->digirisk_dolibarr_fetch_resource('PoisonControlCenter');
			$societe->fetch($poison_control_centerID);
			$societe->name = $langs->trans('PoisonControlCenter') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
			$societe->update(0, $user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 1, 'integer', 0, '', $conf->entity);
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

		$extra_fields = new ExtraFields($this->db);

		$extra_fields->update('fk_risk', $langs->transnoentities("RiskLinked"), 'sellist', '', 'projet_task', 0, 0, 1010, 'a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_risk:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->addExtraField('fk_risk', $langs->transnoentities("RiskLinked"), 'sellist', 1010, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:50:"digiriskdolibarr_risk:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->update('fk_preventionplan', $langs->transnoentities("PreventionPlanLinked"), 'sellist', '', 'projet_task', 0, 0, 1020, 'a:1:{s:7:"options";a:1:{s:60:"digiriskdolibarr_preventionplan:ref:rowid::entity = $ENTITY$";N;}}', '', '', '', 1);
		$extra_fields->addExtraField('fk_preventionplan', $langs->transnoentities("PreventionPlanLinked"), 'sellist', 1020, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:60:"digiriskdolibarr_preventionplan:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->update('fk_firepermit', $langs->transnoentities("FirePermitLinked"), 'sellist', '', 'projet_task', 0, 0, 1030, 'a:1:{s:7:"options";a:1:{s:56:"digiriskdolibarr_firepermit:ref:rowid::entity = $ENTITY$";N;}}', '', '', '', 1);
		$extra_fields->addExtraField('fk_firepermit', $langs->transnoentities("FirePermitLinked"), 'sellist', 1030, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:56:"digiriskdolibarr_firepermit:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->update('fk_accident', $langs->transnoentities("AccidentLinked"), 'sellist', '', 'projet_task', 0, 0, 1040, 'a:1:{s:7:"options";a:1:{s:54:"digiriskdolibarr_accident:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);
		$extra_fields->addExtraField('fk_accident', $langs->transnoentities("AccidentLinked"), 'sellist', 1040, '', 'projet_task', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:54:"digiriskdolibarr_accident:ref:rowid::entity = $ENTITY$";N;}}', '', '', 1);

		if (!$conf->global->DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS_BACKWARD_COMPATIBILITY && (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) || dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', $conf->entity))) {
			if ($conf->multicompany->enabled) {
				$current_entity = $conf->entity;
				$object = new ActionsMulticompany($this->db);

				$entities = $object->getEntitiesList(false, false, true, true);
				foreach ($entities as $sub_entity => $entity_name) {
					$conf->setEntityValues($this->db, $sub_entity);

					extrafield_soft_delete('digiriskdolibarr_ticket_firstname', 'ticket', $extra_fields);
					extrafield_soft_delete('digiriskdolibarr_ticket_lastname', 'ticket', $extra_fields);
					extrafield_soft_delete('digiriskdolibarr_ticket_phone', 'ticket', $extra_fields);
					extrafield_soft_delete('digiriskdolibarr_ticket_service', 'ticket', $extra_fields);
					extrafield_soft_delete('digiriskdolibarr_ticket_location', 'ticket', $extra_fields);
					extrafield_soft_delete('digiriskdolibarr_ticket_date', 'ticket', $extra_fields);
				}
				$conf->setEntityValues($this->db, $current_entity);
			} else {
				extrafield_soft_delete('digiriskdolibarr_ticket_firstname', 'ticket', $extra_fields);
				extrafield_soft_delete('digiriskdolibarr_ticket_lastname', 'ticket', $extra_fields);
				extrafield_soft_delete('digiriskdolibarr_ticket_phone', 'ticket', $extra_fields);
				extrafield_soft_delete('digiriskdolibarr_ticket_service', 'ticket', $extra_fields);
				extrafield_soft_delete('digiriskdolibarr_ticket_location', 'ticket', $extra_fields);
				extrafield_soft_delete('digiriskdolibarr_ticket_date', 'ticket', $extra_fields);
			}

			$extra_fields->addExtraField('digiriskdolibarr_ticket_lastname', $langs->transnoentities("LastName"), 'varchar', 2000, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
			$extra_fields->addExtraField('digiriskdolibarr_ticket_firstname', $langs->transnoentities("FirstName"), 'varchar', 2100, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
			$extra_fields->addExtraField('digiriskdolibarr_ticket_phone', $langs->transnoentities("Phone"), 'phone', 2200, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
			$extra_fields->addExtraField('digiriskdolibarr_ticket_service', $langs->transnoentities("GP/UT"), 'sellist', 2300, '255', 'ticket', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:61:"digiriskdolibarr_digiriskelement:ref:rowid::entity = $ENTITY$";N;}}', 1, '', 4, '','',0);
			$extra_fields->addExtraField('digiriskdolibarr_ticket_location', $langs->transnoentities("Location"), 'varchar', 2400, 255, 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
			$extra_fields->addExtraField('digiriskdolibarr_ticket_date', $langs->transnoentities("Date"), 'datetime', 2500, '', 'ticket', 0, 0, '', '', 1, '', 1, '', '', 0);
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 1, 'integer', 0, '', 0);
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS_BACKWARD_COMPATIBILITY', 1, 'integer', 0, '', 0);
		}

		//Used for data import from Digirisk Wordpress
		$extra_fields->update('wp_digi_id', $langs->trans("WPDigiID"), 'int', 100, 'digiriskdolibarr_digiriskelement', 0, 0, 1020, '', '', '', 0);
		$extra_fields->addExtraField('wp_digi_id', $langs->trans("WPDigiID"), 'int', 100, '', 'digiriskdolibarr_digiriskelement', 0, 0, '', '', '', '', 0);
		$extra_fields->addExtraField('entity', $langs->trans("Entity"), 'int', 100, '', 'digiriskdolibarr_digiriskelement', 0, 0, '', '', '', '', 0);

		$extra_fields->addExtraField('professional_qualification', $langs->trans("ProfessionalQualification"), 'varchar', 990, 255, 'user', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}', 1, '', 1, '', '', 0, 'digiriskdolibarr');
		$extra_fields->addExtraField('contract_type', $langs->trans("ContractType"), 'select', 1000, '', 'user', 0, 0, '', 'a:1:{s:7:"options";a:5:{i:1;s:3:"CDI";i:2;s:3:"CDD";i:3;s:18:"Apprentice/Student";i:4;s:7:"Interim";i:5;s:5:"Other";}}', 1, '', 1, '', '', 0, 'digiriskdolibarr');

		if ($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2 == 0) {
			dolibarr_set_const($this->db, 'MAIN_EXTRAFIELDS_USE_SELECT2', 1, 'integer', 0, '', $conf->entity);
		}

		dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

		if ($conf->global->CATEGORIE_RECURSIV_ADD == 0) {
			dolibarr_set_const($this->db, 'CATEGORIE_RECURSIV_ADD', 1, 'integer', 0, '', $conf->entity);
		}

		//DigiriskElement favorite medias backward compatibility
		if ($conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY == 0) {
			require_once __DIR__ . '/../../class/digiriskelement.class.php';

			$digiriskelement     = new DigiriskElement($this->db);
			$digiriskElementList = $digiriskelement->fetchAll();

			if ( ! empty($digiriskElementList) && $digiriskElementList > 0) {
				foreach ($digiriskElementList as $digiriskElement) {
					$mediasDir = DOL_DATA_ROOT . ($conf->entity == 1 ? '' : '/' . $conf->entity) . '/digiriskdolibarr/' . $digiriskElement->element_type . '/' . $digiriskElement->ref;

					if (is_dir($mediasDir)) {
						$fileList = dol_dir_list($mediasDir);
						if ( ! empty($fileList) && $fileList > 0) {
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
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

			$tags = new Categorie($this->db);

			$tags->label = 'QHSE';
			$tags->type  = 'project';
			$tag_id      = $tags->create($user);

			$tags->label     = 'DU';
			$tags->type      = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label     = 'PP';
			$tags->type      = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label     = 'FP';
			$tags->type      = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label     = 'ACC';
			$tags->type      = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			$tags->label     = 'TS';
			$tags->type      = 'project';
			$tags->fk_parent = $tag_id;
			$tags->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 3, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_PROJECT_TAGS_SET == 1) {
			//Install after 8.3.0

			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

			$tags = new Categorie($this->db);

			$tags->fetch('', 'QHSE');

			$tags->label     = 'FP';
			$tags->type      = 'project';
			$tags->fk_parent = $tags->id;
			$tags->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 2, 'integer', 0, '', $conf->entity);
		} elseif ($conf->global->DIGIRISKDOLIBARR_PROJECT_TAGS_SET == 2) {
			//Install after 9.3.0

			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

			$tags = new Categorie($this->db);

			$tags->fetch('', 'QHSE');
			$tags->label     = 'TS';
			$tags->type      = 'project';
			$tags->fk_parent = $tags->id;
			$tags->create($user);

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 3, 'integer', 0, '', $conf->entity);
		}

		if ($conf->global->DIGIRISKDOLIBARR_TRIGGERS_UPDATED == 0) {
			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

			$actioncomm = new Actioncomm($this->db);

			$allGroupments = $actioncomm->getActions(0, 0, '', ' AND a.elementtype = "groupment@digiriskdolibarr"');
			if ( ! empty($allGroupments)) {
				foreach ($allGroupments as $allGroupment) {
					$allGroupment->elementtype = 'digiriskelement@digiriskdolibarr';
					$allGroupment->update($user);
				}
			}

			$allWorkunits = $actioncomm->getActions(0, 0, '', ' AND a.elementtype = "workunit@digiriskdolibarr"');
			if ( ! empty($allWorkunits)) {
				foreach ($allWorkunits as $allWorkunit) {
					$allWorkunit->elementtype = 'digiriskelement@digiriskdolibarr';
					$allWorkunit->update($user);
				}
			}

			$allCompanies = $actioncomm->getActions(0, 0, '', ' AND a.elementtype = "societe@digiriskdolibarr"');
			if ( ! empty($allCompanies)) {
				foreach ($allCompanies as $allCompany) {
					$allCompany->fk_soc = $allCompany->fk_element;
					$allCompany->update($user);
				}
			}

			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_TRIGGERS_UPDATED', 1, 'integer', 0, '', $conf->entity);
		}

		$params = array(
			'digiriskdolibarr' => array(																			// nom informatif du module externe qui apporte ses paramètres
				'sharingelements' => array(																			// section des paramètres 'element' et 'object'
					//partage digiriskelement
					'digiriskelement' => array(																		// Valeur utilisée dans getEntity()
						'type'    => 'element',																		// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'    => 'info-circle',																	// Font Awesome icon
						'lang'    => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip' => 'DigiriskElementSharedTooltip',												// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'enable'  => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'input'   => array(																			// input : Paramétrage de la réaction du bouton on/off
							'global' => array(																		// global : réaction lorsqu'on désactive l'option de partage global
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							)
						)
					),
					//partage risk
					'risk' => array(																				// Valeur utilisée dans getEntity()
						'type'      => 'element',																	// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'      => 'exclamation-triangle',														// Font Awesome icon
						'lang'      => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip'   => 'RiskSharedTooltip',															// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'mandatory' => 'digiriskelement',															// partage principal obligatoire
						'enable'    => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'display'   => '! empty($conf->global->MULTICOMPANY_DIGIRISKELEMENT_SHARING_ENABLED)', 		// L'affichage de ce bloc de partage dépend de l'activation d'un partage parent
						'input'     => array(																		// input : Paramétrage de la réaction du bouton on/off
							'global' => array(																		// global : réaction lorsqu'on désactive l'option de partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							),
							'digiriskelement' => array(																// digiriskelement (nom du module principal) : réaction lorsqu'on désactive le partage principal (ici le partage des digiriskelements)
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage principal
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage principal
								'del'      => true																	// del : supprime la constante du partage lors de la désactivation du partage principal
							)
						)
					),
					//partage risk sign
					'risksign' => array(																			// Valeur utilisée dans getEntity()
						'type'      => 'element',																	// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'      => 'map-signs',																	// Font Awesome icon
						'lang'      => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip'   => 'RiskSignSharedTooltip',														// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'mandatory' => 'digiriskelement',															// partage principal obligatoire
						'enable'    => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'display'   => '! empty($conf->global->MULTICOMPANY_DIGIRISKELEMENT_SHARING_ENABLED)', 		// L'affichage de ce bloc de partage dépend de l'activation d'un partage parent
						'input'     => array(																		// input : Paramétrage de la réaction du bouton on/off
							'global' => array(																		// global : réaction lorsqu'on désactive l'option de partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							),
							'digiriskelement' => array(																// digiriskelement (nom du module principal) : réaction lorsqu'on désactive le partage principal (ici le partage des digiriskelements)
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage principal
								//'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage principal
								'del'      => true																	// del : supprime la constante du partage lors de la désactivation du partage principal
							)
						)
					),
				),
				'sharingmodulename' => array(																		// correspondance des noms de modules pour le lien parent ou compatibilité (ex: 'productsupplierprice'	=> 'product')
					'digiriskelement' => 'digiriskdolibarr',
					'risk'            => 'digiriskdolibarr',
					'risksign'        => 'digiriskdolibarr',
				),
			)
		);

		$externalmodule = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
		$externalmodule = !empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING) ? array_merge($externalmodule, $params) : $params;
		$jsonformat = json_encode($externalmodule);
		dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", $jsonformat, 'json', 0, '', 0);
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
		global $conf;

		$sql = array();
		require_once __DIR__ . '/../../../../core/lib/admin.lib.php';
		$options = 'noremoverights';

		if (!empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING) && $conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING !== 0) {
			$externalmodule = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
			if (is_array($externalmodule) && array_key_exists('digiriskdolibarr',$externalmodule) ) {
				unset($externalmodule['digiriskdolibarr']);  // nom informatif du module externe qui apporte ses paramètres
			}
			$jsonformat = json_encode($externalmodule);
			dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", $jsonformat, 'json', 0, '', 0);
		}


		return $this->_remove($sql, $options);
	}
}
