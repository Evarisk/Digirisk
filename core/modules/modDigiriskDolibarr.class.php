<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2020 Eoxia <technique@evarisk.com>
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
	public $dirs = [];

	/**
	 * @var array Module boxes
	 */
	public $boxes = [];

	/**
	 * @var array Module constants
	 */
	public $const = [];

	/**
	 * @var array Module cron jobs entries
	 */
	public $cronjobs = [];

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
	public $menu = [];

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
	public $module_parts = [];

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
	public $tabs = [];

	/**
	 * @var array To add new dictionaries on Dolibarr objects.
	 */
	public $dictionaries = [];

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

		$langs->loadLangs(['digiriskdolibarr@digiriskdolibarr', 'categories']);

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero       = 436302; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		$this->rights_class = 'digiriskdolibarr';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family          = "";
		$this->module_position = '';
		$this->familyinfo      = ['Evarisk' => ['position' => '01', 'label' => $langs->trans("Evarisk")]];
		$this->name            = preg_replace('/^mod/i', '', get_class($this));
		$this->description     = $langs->trans('DigiriskDolibarrDescription');
		$this->descriptionlong = "Digirisk";
		$this->editor_name     = 'Evarisk';
		$this->editor_url      = 'https://evarisk.com';
		$this->version         = '21.0.1';
		$this->const_name      = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto           = 'digiriskdolibarr_color@digiriskdolibarr';

		$this->module_parts = [
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
			'css' => [],
			// Set this to relative path of js file if module must load a js on all pages
			'js' => [],
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => [
				'completeTabsHead',
				'admincompany',
				'globaladmin',
				'emailtemplates',
				'mainloginpage',
				'ticketcard',
				'projecttaskcard',
				'projecttaskscard',
				'tasklist',
				'publicticket',
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
				'preventionplanschedules',
				'firepermitschedules',
				'digiriskdolibarradmindocuments',
                'digiriskelementview',
                'digiriskelementdocument',
                'digiriskelementagenda',
				'digiriskstandardview',
                'accidentdocument',
                'accidentagenda',
                'accidentsignature',
                'digiriskstandardagenda',
                'category',
                'categoryindex',
                'main'
			],
			'tabs' => [
				'mycompany_admin'
			],
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		];

		$this->dirs = [
			"/digiriskdolibarr/riskassessment",
			"/digiriskdolibarr/accident",
			"/digiriskdolibarr/ticketstats",
			"/digiriskdolibarr/temp",
			"/ecm/digiriskdolibarr",
			"/ecm/digiriskdolibarr/riskassessmentdocument",
			"/ecm/digiriskdolibarr/auditreportdocument",
			"/ecm/digiriskdolibarr/legaldisplay",
			"/ecm/digiriskdolibarr/informationssharing",
			"/ecm/digiriskdolibarr/firepermitdocument",
			"/ecm/digiriskdolibarr/preventionplandocument",
			"/ecm/digiriskdolibarr/groupmentdocument",
			"/ecm/digiriskdolibarr/workunitdocument",
            "/ecm/digiriskdolibarr/listingrisksaction",
            "/ecm/digiriskdolibarr/listingrisksdocument",
			"/ecm/digiriskdolibarr/listingrisksenvironmentalaction",
			"/ecm/digiriskdolibarr/listingrisksphoto",
			"/ecm/digiriskdolibarr/ticketdocument",
			"/ecm/digiriskdolibarr/accidentinvestigationdocument",
			"/ecm/digiriskdolibarr/medias"
		];

		// Config pages.
		$this->config_page_url = ["setup.php@digiriskdolibarr"];
		// Dependencies

		$this->hidden                  = false;
		$this->depends                 = ['modSaturne', 'modECM', 'modProjet', 'modSociete', 'modTicket', 'modCategorie', 'modFckeditor', 'modApi', 'modExport', 'modImport'];
		$this->requiredby              = ['modDigiBoard'];
		$this->conflictwith            = [];
		$this->langfiles               = ["digiriskdolibarr@digiriskdolibarr"];
		$this->phpmin                  = [7, 4]; // Minimum version of PHP required by module
		$this->need_dolibarr_version   = [20, 0]; // Minimum version of Dolibarr required by module
		$this->warnings_activation     = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'DigiriskDolibarrWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		$i = 0;
		$this->const = [
			// CONST CONFIGURATION
			$i++ => ['DIGIRISKDOLIBARR_GENERAL_MEANS', 'chaine', $langs->transnoentities('GeneralMeansAtDisposalValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GENERAL_RULES', 'chaine', $langs->transnoentities('GeneralInstructionsValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_IDCC_DICTIONNARY', 'chaine', '', 'IDCC of company', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SOCIETY_DESCRIPTION', 'chaine', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PEE_ENABLED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PERCO_ENABLED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SECURITY_SOCIAL_CONF_UPDATED', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_NB_EMPLOYEES', 'integer', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_NB_WORKED_HOURS', 'integer', '', '', 0, 'current'],

			// CONST RISK ASSESSMENTDOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_START_DATE', 'date', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_AUDIT_END_DATE', 'date', '', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_RECIPIENT', 'chaine', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_METHOD', 'chaine', $langs->transnoentities('RiskAssessmentDocumentMethod'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SOURCES', 'chaine', $langs->transnoentities('RiskAssessmentDocumentSources'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_IMPORTANT_NOTES', 'chaine', $langs->transnoentities('RiskAssessmentDocumentImportantNote'), '', 0, 'current'],

			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENTDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON','chaine', 'mod_riskassessmentdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/riskassessmentdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/riskassessmentdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_DEFAULT_MODEL', 'chaine', 'riskassessmentdocument_odt', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GENERATE_ARCHIVE_WITH_DIGIRISKELEMENT_DOCUMENTS', 'integer', 0, '', 0, 'current'],

            // CONST AUDIT REPORT DOCUMENT
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_AUDITREPORTDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_AUDITREPORTDOCUMENT_ADDON', 'chaine', 'mod_auditreportdocument_standard', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_AUDITREPORTDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/auditreportdocument/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_AUDITREPORTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/auditreportdocument/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_AUDITREPORTDOCUMENT_DEFAULT_MODEL', 'chaine', 'auditreportdocument_odt', '', 0, 'current'],

			// CONST LEGAL DISPLAY
			$i++ => ['DIGIRISKDOLIBARR_LOCATION_OF_DETAILED_INSTRUCTION', 'chaine', $langs->transnoentities('LocationOfDetailedInstructionsValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_PERMANENT', 'chaine', $langs->transnoentities('PermanentDerogationValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DEROGATION_SCHEDULE_OCCASIONAL', 'chaine', $langs->transnoentities('OccasionalDerogationValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_TITLE', 'chaine', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_COLLECTIVE_AGREEMENT_LOCATION', 'chaine', $langs->transnoentities('CollectiveAgreementValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DUER_LOCATION','chaine', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RULES_LOCATION', 'chaine', $langs->transnoentities('RulesOfProcedureValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PARTICIPATION_AGREEMENT_INFORMATION_PROCEDURE', 'chaine', $langs->transnoentities('ParticipationAgreementValue'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIRST_AID', 'chaine', $langs->transnoentities('FirstAidValue'), '', 0, 'current'],

			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LEGALDISPLAY_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON', 'chaine', 'mod_legaldisplay_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/legaldisplay/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LEGALDISPLAY_CUSTOM_ADDON_ODT_PATH','chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/legaldisplay/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LEGALDISPLAY_DEFAULT_MODEL', 'chaine', 'legaldisplay_odt', '', 0, 'current'],

			// CONST INFORMATIONS SHARING
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_INFORMATIONSSHARING_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON', 'chaine', 'mod_informationssharing_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/informationssharing/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_INFORMATIONSSHARING_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/informationssharing/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_INFORMATIONSSHARING_DEFAULT_MODEL', 'chaine', 'informationssharing_odt', '', 0, 'current'],

            // CONST REGISTER DOCUMENT
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_REGISTERDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_REGISTERDOCUMENT_ADDON', 'chaine', 'mod_registerdocument_standard', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_REGISTERDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/registerdocument/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_REGISTERDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/registerdocument/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_REGISTERDOCUMENT_DEFAULT_MODEL', 'chaine', 'registerdocument_odt', '', 0, 'current'],

            // CONST LISTING RISKS DOCUMENT
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_ADDON', 'chaine', 'mod_listingrisksdocument_standard', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksdocument/listingrisksdocument', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksdocument/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_DEFAULT_MODEL', 'chaine', 'listingrisksdocument_odt', '', 0, 'current'],

            // CONST LISTING RISKS ACTION
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSACTION_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON', 'chaine', 'mod_listingrisksaction_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksdocument/listingrisksaction/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSACTION_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksaction/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSACTION_DEFAULT_MODEL', 'chaine', 'listingrisksaction_odt', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSACTION_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'],

			// CONST LISTING RISKS PHOTO
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSPHOTO_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON', 'chaine', 'mod_listingrisksphoto_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksdocument/listingrisksphoto/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksphoto/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_DEFAULT_MODEL', 'chaine', 'listingrisksphoto_odt', '', 0, 'current'],

            // CONST LISTING RISKS ENVIRONMENTAL ACTION
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_LISTINGRISKSENVIRONMENTALACTION_GENERATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSENVIRONMENTALACTION_ADDON', 'chaine', 'mod_listingrisksenvironmentalaction_standard', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSENVIRONMENTALACTION_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksenvironmentaldocument/listingrisksenvironmentalaction/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSENVIRONMENTALACTION_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/listingrisksenvironmentalaction/', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSENVIRONMENTALACTION_DEFAULT_MODEL', 'chaine', 'listingrisksenvironmentalaction_odt', '', 0, 'current'],

			// CONST GROUPMENT DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_GROUPMENTDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON', 'chaine', 'mod_groupmentdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/groupmentdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/groupmentdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_DEFAULT_MODEL', 'chaine', 'groupmentdocument_odt', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'],


			// CONST WORKUNIT DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_WORKUNITDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON', 'chaine', 'mod_workunitdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/workunitdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_WORKUNITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/workunitdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_WORKUNITDOCUMENT_DEFAULT_MODEL', 'chaine', 'workunitdocument_odt', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_WORKUNITDOCUMENT_SHOW_TASK_DONE', 'integer', 1, '', 0, 'current'],

			// CONST PREVENTION PLAN
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_PENDINGSIGNATURE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_UNVALIDATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_LOCK', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_ARCHIVE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLAN_SENTBYMAIL', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON', 'chaine', 'mod_preventionplan_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLAN_PROJECT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLAN_MAITRE_OEUVRE', 'integer', 0, '', 0, 'current'],

			// CONST PREVENTION PLAN DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON', 'chaine', 'mod_preventionplandocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/preventionplandocument/specimen/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/preventionplandocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_DEFAULT_MODEL', 'chaine', 'preventionplandocument_odt', '', 0, 'current'],

			// CONST FIRE PERMIT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_CREATE','integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_PENDINGSIGNATURE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_UNVALIDATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_LOCK', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_ARCHIVE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMIT_SENTBYMAIL', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMIT_ADDON', 'chaine', 'mod_firepermit_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMIT_PROJECT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMIT_MAITRE_OEUVRE', 'integer', 0, '', 0, 'current'],

			// CONST FIRE PERMIT DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON', 'chaine', 'mod_firepermitdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/firepermitdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/firepermitdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL', 'chaine', 'firepermitdocument_odt', '', 0, 'current'],

			//CONST DIGIRISKELEMENT
			$i++ => ['DIGIRISKDOLIBARR_DIGIRISKELEMENT_MEDIAS_BACKWARD_COMPATIBILITY', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH_UPDATED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_DIGIRISKELEMENT_DEPTH_GRAPH', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_CREATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_MODIFY', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_DIGIRISKELEMENT_DELETE', 'integer', 1, '', 0, 'current'],

			// CONST GROUPMENT
			$i++ => ['DIGIRISKDOLIBARR_GROUPMENT_ADDON', 'chaine', 'mod_groupment_standard', '', 0, 'current'],

			// CONST WORKUNIT
			$i++ => ['DIGIRISKDOLIBARR_WORKUNIT_ADDON', 'chaine', 'mod_workunit_standard', '', 0, 'current'],

			// CONST EVALUATOR
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_EVALUATOR_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_EVALUATOR_ADDON', 'chaine', 'mod_evaluator_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_EVALUATOR_DURATION', 'integer', 15, '', 0, 'current'],

			// CONST RISK ANALYSIS

			// CONST RISK
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_MODIFY', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_DELETE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_IMPORT', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISK_UNLINK', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISK_ADDON', 'chaine', 'mod_risk_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISK_DESCRIPTION', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MOVE_RISKS', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_RISKS', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_RISK_LIST_PARENT_VIEW', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_SHOW_SHARED_RISKS', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_CATEGORY_ON_RISK', 'integer', 0, '', 0, 'current'],

			// CONST RISK ASSESSMENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKASSESSMENT_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON', 'chaine', 'mod_riskassessment_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT', 'integer', 0, '', 0, 'current'],

			// CONST RISK SIGN
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_MODIFY', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_DELETE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_IMPORT', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_RISKSIGN_UNLINK', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_RISKSIGN_ADDON', 'chaine', 'mod_risksign_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_RISKSIGNS', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS', 'integer', 0, '', 0, 'current'],

			// CONST PROJET
			$i++ => ['DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DU_PROJECT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT', 'integer', 0, '', 0, 'current'],

			// CONST TASK
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TASK_MANAGEMENT', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_TASK_START_DATE', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_TASK_END_DATE', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_TASKS_DONE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_TASK_CALCULATED_PROGRESS', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_ALL_TASKS', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TASK_TIMESPENT_DURATION', 'integer', 15, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TASK_HIDE_RESPONSIBLE_IN_DOCUMENT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TASK_HIDE_DATE_IN_DOCUMENT', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TASK_HIDE_BUDGET_IN_DOCUMENT', 'integer', 0, '', 0, 'current'],

            // CONST TASK TIMESPENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TASK_TIMESPENT_DELETE', 'integer', 1, '', 0, 'current'],

			// CONST PREVENTION PLAN LINE
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_PREVENTIONPLANLINE_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON', 'chaine', 'mod_preventionplandet_standard', '', 0, 'current'],

			// CONST FIRE PERMIT LINE
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_FIREPERMITLINE_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_FIREPERMITDET_ADDON', 'chaine', 'mod_firepermitdet_standard', '', 0, 'current'],

			//CONST TICKET & REGISTERS
			$i++ => ['DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 'integer', 0, '', 0, 0],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TICKET_CURRENT_PUBLIC_INTERFACE_RADIO', 'chaine', 'originCurrentTicketPublicInterfaceURL', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TICKET_CURRENT_PUBLIC_INTERFACE_URL_ORIGIN', 'chaine', dol_buildpath('custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity, 2), '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TICKET_MULTICOMPANY_PUBLIC_INTERFACE_RADIO', 'chaine', 'originMulticompanyTicketPublicInterfaceURL', '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TICKET_MULTICOMPANY_PUBLIC_INTERFACE_URL_ORIGIN', 'chaine', dol_buildpath('custom/digiriskdolibarr/public/ticket/create_ticket.php', 2), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', 'chaine', '', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', 'chaine', $langs->trans('Registre'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', 'chaine', $langs->trans('Rubriques'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_PROJECT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', 'chaine', $langs->trans('YouMustNotifyYourHierarchy'), '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_HIDE_REF', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE', 'chaine', '{"'. $langs->transnoentities("WithoutWorkStop") .'":"less:1:days", "'.  $langs->transnoentities("LessThanFourDays") .'":"less:4:days","'.  $langs->transnoentities("LessThanTwentyOneDays") .'":"less:21:days","'.  $langs->transnoentities("LessThanThreeMonth") .'":"less:3:months","'.  $langs->transnoentities("LessThanSixMonths") .'":"less:6:months","'.  $langs->transnoentities("LongTimeWorkStop") .'":"more:6:months"}', '', 0, 'current'],

			// CONST MODULE
			$i++ => ['DIGIRISKDOLIBARR_SUBPERMCATEGORY_FOR_DOCUMENTS', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_VERSION','chaine', $this->version, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_THIRDPARTY_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_CONTACTS_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_USERAPI_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_READERGROUP_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_USERGROUP_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ADMINUSERGROUP_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_READERGROUP_UPDATED', 'integer', 2, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_USERGROUP_UPDATED', 'integer', 3, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ADMINUSERGROUP_UPDATED', 'integer', 3, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_REDIRECT_AFTER_CONNECTION', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_USE_CAPTCHA', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_NEW_SIGNATURE_TABLE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACTIVE_STANDARD', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TRIGGERS_UPDATED', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_CONF_BACKWARD_COMPATIBILITY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ENCODE_BACKWARD_COMPATIBILITY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM', 'integer', 854, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM', 'integer', 480, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE', 'integer', 1280, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE', 'integer', 720, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MINI', 'integer', 128, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MINI', 'integer', 72, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_SMALL', 'integer', 480, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_SMALL', 'integer', 270, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_DISPLAY_NUMBER_MEDIA_GALLERY', 'integer', 8, '', 0, 'current'],
            // -- Deprecated conf, was used to generate custom template, we can now download default template to customize it
            //$i++ => ['DIGIRISKDOLIBARR_CUSTOM_DOCUMENTS_SET', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MANUAL_INPUT_NB_EMPLOYEES', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MANUAL_INPUT_NB_WORKED_HOURS', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET', 'integer', 0, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_BACKWARD_ODT_PATH_SET', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_BACKWARD_TRASH_ELEMENTS', 'integer', 1, '', 0, 'current'],

            // CONST ACCIDENT
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_DELETE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_VALIDATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_UNVALIDATE', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_LOCK', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENT_ARCHIVE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENT_ADDON', 'chaine', 'mod_accident_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENT_PROJECT', 'integer', 0, '', 0, 'current'],

			// CONST ACCIDENT LINE
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTWORKSTOP_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTWORKSTOP_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTWORKSTOP_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTLESION_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTLESION_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTLESION_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTWORKSTOP_ADDON', 'chaine', 'mod_accidentworkstop_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTLESION_ADDON', 'chaine', 'mod_accidentlesion_standard', '', 0, 'current'],

			// CONST TICKET DOCUMENT
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_TICKETDOCUMENT_GENERATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON_ODT_PATH', 'chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/ticketdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON', 'chaine', 'mod_ticketdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKETDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/ticketdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TICKETDOCUMENT_DEFAULT_MODEL', 'chaine', 'ticketdocument_odt', '', 0, 'current'],

			// CONST PROJECT DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_PROJECTDOCUMENT_ADDON', 'chaine', 'mod_projectdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_PROJECTDOCUMENT_DISPLAY_RISKASSESSMENT_COLOR', 'integer', 1, '', 0, 'current'],

			// GENERAL CONSTS
			$i++ => ['MAIN_ODT_AS_PDF', 'chaine', 'libreoffice', '', 0, 'current'],
			$i++ => ['MAIN_USE_EXIF_ROTATION', 'integer', 1, '', 0, 'current'],
			$i++ => ['MAIN_EXTRAFIELDS_USE_SELECT2', 'integer', 1, '', 0, 'current'],

			// CONST TOOLS
			$i++ => ['DIGIRISKDOLIBARR_TOOLS_ADVANCED_IMPORT', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TOOLS_TREE_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TOOLS_RISKS_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TOOLS_RISKSIGNS_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_TOOLS_GLOBAL_ALREADY_IMPORTED', 'integer', 0, '', 0, 'current'],

			// CONST SIGNATURE
			$i++ => ['DIGIRISKDOLIBARR_SIGNATURE_ENABLE_PUBLIC_INTERFACE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_SIGNATURE_SHOW_COMPANY_LOGO', 'integer', 1, '', 0, 'current'],

			// CONST DIGIRISK DOCUMENTS
			$i++ => ['DIGIRISKDOLIBARR_DOCUMENT_SHOW_PICTO_NAME', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_AUTOMATIC_PDF_GENERATION', 'integer', 0, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MANUAL_PDF_GENERATION', 'integer', 0, '', 0, 'current'],

			// CONST ACCIDENT INVESTIGATION
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTINVESTIGATION_ADDON', 'chaine', 'mod_accidentinvestigation_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_CREATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_MODIFY', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_DELETE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_VALIDATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_UNVALIDATE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_ARCHIVE', 'integer', 1, '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_LOCK', 'integer', 1, '', 0, 'current'],
            $i++ => ['DIGIRISKDOLIBARR_MAIN_AGENDA_ACTIONAUTO_ACCIDENTINVESTIGATION_SENTBYMAIL', 'integer', 1, '', 0, 'current'],

			// CONST ACCIDENT INVESTIGATION DOCUMENT
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON', 'chaine', 'mod_accidentinvestigationdocument_standard', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON_ODT_PATH','chaine', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/accidentinvestigationdocument/', '', 0, 'current'],
			$i++ => ['DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_CUSTOM_ADDON_ODT_PATH', 'chaine', 'DOL_DATA_ROOT' . (($conf->entity == 1 ) ? '/' : '/' . $conf->entity . '/') . 'ecm/digiriskdolibarr/accidentinvestigationdocument/', '', 0, 'current'],
            $i   => ['DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_DEFAULT_MODEL', 'chaine', 'template_accidentinvestigationdocument_odt', '', 0, 'current'],
		];

		if (!isModEnabled('digiriskdolibarr')) {
			$conf->digiriskdolibarr          = new stdClass();
			$conf->digiriskdolibarr->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = [];
		$pictopath = dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color.png', 1);
		$pictoDigirisk = img_picto('', $pictopath, '', 1, 0, 0, '', 'pictoModule');
		$this->tabs[] = ['data' => 'mycompany_admin:+security:'. $pictoDigirisk . $langs->trans('Security').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/securityconf.php'];  			// To add a new tab identified by code tabname1
		$this->tabs[] = ['data' => 'mycompany_admin:+social:'. $pictoDigirisk .$langs->trans('Social').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/admin/socialconf.php'];  					// To add a new tab identified by code tabname1
		$this->tabs[] = ['data' => 'thirdparty:+schedules:'. $pictoDigirisk .$langs->trans('Schedules').':digiriskdolibarr@digiriskdolibarr:1:/custom/saturne/view/saturne_schedules.php?id=__ID__&element_type=societe&module_name=societe']; // To add a new tab identified by code tabname1
		$this->tabs[] = ['data' => 'user:+participation:'. $pictoDigirisk .$langs->trans('GP/UTParticipation').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php?fromid=__ID__']; // To add a new tab identified by code tabname1
        $this->tabs[] = ['data' => 'user:+accidents:'. $pictoDigirisk .$langs->trans('Accidents').':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/accident/accident_list.php?fromiduser=__ID__']; // To add a new tab identified by code tabname1
        $this->tabs[] = ['data' => 'categories_ticket:+config:' . $pictoDigirisk .$langs->trans('WHSRegister') . ':digiriskdolibarr@digiriskdolibarr:1:/custom/digiriskdolibarr/view/ticket/category_config.php?id=__ID__&type=ticket'];

        // Dictionaries
        $this->dictionaries = [
            'langs' => 'digiriskdolibarr@digiriskdolibarr',
            // List of tables we want to see into dictionary editor
            'tabname' => [
                MAIN_DB_PREFIX . 'c_conventions_collectives',
                MAIN_DB_PREFIX . 'c_relative_location',
                MAIN_DB_PREFIX . 'c_lesion_localization',
                MAIN_DB_PREFIX . 'c_lesion_nature',
                MAIN_DB_PREFIX . 'c_digiriskdolibarr_action_trigger',
                MAIN_DB_PREFIX . 'c_accidentinvestigation_attendants_role',
                MAIN_DB_PREFIX . 'c_preventionplan_attendants_role',
                MAIN_DB_PREFIX . 'c_firepermit_attendants_role'
            ],
            // Label of tables
            'tablib' => [
                'CollectiveAgreement',
                'RelativeLocation',
                'LesionLocalization',
                'LesionNature',
                'DigiriskDolibarrActionTrigger',
                'AccidentInvestigationRole',
                'PreventionPlanRole',
                'FirePermitRole'
            ],
            // Request to select fields
            'tabsql' => [
                'SELECT f.rowid as rowid, f.code, f.libelle, f.active FROM ' . MAIN_DB_PREFIX . 'c_conventions_collectives as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_relative_location as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_lesion_localization as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_lesion_nature as f',
                'SELECT f.rowid as rowid, f.elementtype, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_digiriskdolibarr_action_trigger as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_accidentinvestigation_attendants_role as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_preventionplan_attendants_role as f',
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active FROM ' . MAIN_DB_PREFIX . 'c_firepermit_attendants_role as f'
            ],
            // Sort order
            'tabsqlsort' => [
                'code ASC',
                'position ASC',
                'position ASC',
                'position ASC',
                'position ASC',
                'position ASC',
                'position ASC',
                'position ASC'
            ],
            // List of fields (result of select to show dictionary)
            'tabfield' => [
                'code,libelle',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'elementtype,ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields to edit a record)
            'tabfieldvalue' => [
                'code,libelle',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'elementtype,ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // List of fields (list of fields for insert)
            'tabfieldinsert' => [
                'code,libelle',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'elementtype,ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position',
                'ref,label,description,position'
            ],
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid' => [
                'rowid',
                'rowid',
                'rowid',
                'rowid',
                'rowid',
                'rowid',
                'rowid',
                'rowid'
            ],
            // Condition to show each dictionary
            'tabcond' => [
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled,
                $conf->digiriskdolibarr->enabled
            ]
        ];

		// Boxes/Widgets
		$this->boxes = [
			  0 => [
				  'file' => 'box_riskassessmentdocument@digiriskdolibarr',
				  'note' => '',
				  'enabledbydefaulton' => 'Home',
			  ]
		];

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = [];

		// Permissions provided by this module
		$this->rights = [];
		$r            = 0;

		/* module PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('LireModule', 'DigiriskDolibarr');
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = 1;
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->trans('ReadModule', 'DigiriskDolibarr');
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = 1;
		$r++;

        /* DIGIRISK STANDARD PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('DigiriskStandardMin')); // Permission label
        $this->rights[$r][4] = 'digiriskstandard'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;

		/* RISK ASSESSMENT DOCUMENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('RiskAssessmentDocumentsMin')); // Permission label
		$this->rights[$r][4] = 'riskassessmentdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('RiskAssessmentDocumentsMin')); // Permission label
		$this->rights[$r][4] = 'riskassessmentdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('RiskAssessmentDocumentsMin')); // Permission label
		$this->rights[$r][4] = 'riskassessmentdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

        /* AUDIT REPORT DOCUMENT PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects', $langs->transnoentities('AuditReportDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'auditreportdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('AuditReportDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'auditreportdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('AuditReportDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'auditreportdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;

		/* LEGAL DISPLAY PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('LegalDisplaysMin')); // Permission label
		$this->rights[$r][4] = 'legaldisplay'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('LegalDisplaysMin')); // Permission label
		$this->rights[$r][4] = 'legaldisplay'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('LegalDisplaysMin')); // Permission label
		$this->rights[$r][4] = 'legaldisplay'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* INFORMATIONS SHARING PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('InformationsSharingsMin')); // Permission label
		$this->rights[$r][4] = 'informationssharing'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('InformationsSharingsMin')); // Permission label
		$this->rights[$r][4] = 'informationssharing'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('InformationsSharingsMin')); // Permission label
		$this->rights[$r][4] = 'informationssharing'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

        /* REGISTER DOCUMENT PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('RegisterDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'registerdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('RegisterDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'registerdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('RegisterDocumentsMin')); // Permission label
        $this->rights[$r][4] = 'registerdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;


        /* FIRE PERMIT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('FirePermitsMin')); // Permission label
		$this->rights[$r][4] = 'firepermit'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('FirePermitsMin')); // Permission label
		$this->rights[$r][4] = 'firepermit'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('FirePermitsMin')); // Permission label
		$this->rights[$r][4] = 'firepermit'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* PREVENTION PLAN PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('PreventionPlansMin')); // Permission label
		$this->rights[$r][4] = 'preventionplan'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('PreventionPlansMin')); // Permission label
		$this->rights[$r][4] = 'preventionplan'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('PreventionPlansMin')); // Permission label
		$this->rights[$r][4] = 'preventionplan'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* GP/UT ORGANISATION PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('DigiriskElementsMin')); // Permission label
		$this->rights[$r][4] = 'digiriskelement'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('DigiriskElementsMin')); // Permission label
		$this->rights[$r][4] = 'digiriskelement'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('DigiriskElementsMin')); // Permission label
		$this->rights[$r][4] = 'digiriskelement'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* RISKS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('RisksMin')); // Permission label
		$this->rights[$r][4] = 'risk'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('RisksMin')); // Permission label
		$this->rights[$r][4] = 'risk'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('RisksMin')); // Permission label
		$this->rights[$r][4] = 'risk'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

        /* ENVIRONMENTAL RISKS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects', $langs->transnoentities('RiskEnvironmentalsMin')); // Permission label
        $this->rights[$r][4] = 'riskenvironmental'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('RiskEnvironmentalsMin')); // Permission label
        $this->rights[$r][4] = 'riskenvironmental'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('RiskEnvironmentalsMin')); // Permission label
        $this->rights[$r][4] = 'riskenvironmental'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;

        /* LISTING RISKS DOCUMENT PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('ListingRisksDocumentMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('ListingRisksDocumentMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('ListingRisksDocumentMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksdocument'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;

		/* LISTING RISKS ACTION PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('ListingRisksActionsMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('ListingRisksActionsMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('ListingRisksActionsMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

        /* LISTING RISKS ENVIRONMENTAL ACTION PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('ListingRisksEnvironmentalActionMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksenvironmentalaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('ListingRisksEnvironmentalActionMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksenvironmentalaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('ListingRisksEnvironmentalActionMin')); // Permission label
        $this->rights[$r][4] = 'listingrisksenvironmentalaction'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;


        /* LISTING RISKS PHOTO PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('ListingRisksPhotosMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksphoto'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('ListingRisksPhotosMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksphoto'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('ListingRisksPhotosMin')); // Permission label
		$this->rights[$r][4] = 'listingrisksphoto'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* RISK SIGN PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('RiskSignsMin')); // Permission label
		$this->rights[$r][4] = 'risksign'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('RiskSignsMin')); // Permission label
		$this->rights[$r][4] = 'risksign'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('RiskSignsMin')); // Permission label
		$this->rights[$r][4] = 'risksign'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* EVALUATOR PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('EvaluatorsMin')); // Permission label
		$this->rights[$r][4] = 'evaluator'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('EvaluatorsMin')); // Permission label
		$this->rights[$r][4] = 'evaluator'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('EvaluatorsMin')); // Permission label
		$this->rights[$r][4] = 'evaluator'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* ACCIDENT PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('AccidentsMin')); // Permission label
		$this->rights[$r][4] = 'accident'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('AccidentsMin')); // Permission label
		$this->rights[$r][4] = 'accident'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('AccidentsMin')); // Permission label
		$this->rights[$r][4] = 'accident'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

		/* ACCIDENT INVESTIGATION PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('AccidentInvestigationsMin')); // Permission label
		$this->rights[$r][4] = 'accidentinvestigation'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('AccidentInvestigationsMin')); // Permission label
		$this->rights[$r][4] = 'accidentinvestigation'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('AccidentInvestigationsMin')); // Permission label
		$this->rights[$r][4] = 'accidentinvestigation'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
		$r++;

        /* DIGI AI PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects',$langs->transnoentities('DigiAIMin')); // Permission label
        $this->rights[$r][4] = 'digiai'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('DigiAIMin')); // Permission label
        $this->rights[$r][4] = 'digiai'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('DigiAIMin')); // Permission label
        $this->rights[$r][4] = 'digiai'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->digiriskdolibarr->level1->level2)
        $r++;

		/* ADMINPAGE PANEL ACCESS PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'DigiriskDolibarr');
		$this->rights[$r][4] = 'adminpage';
		$this->rights[$r][5] = 'read';
		$r++;

		/* API PERMISSIONS */
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('GetAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
		$this->rights[$r][1] = $langs->transnoentities('PostAPI');
		$this->rights[$r][4] = 'api';
		$this->rights[$r][5] = 'write';
		$r++;

		// Main menu entries to add
		$this->menu       = [];
		$r                = 0;
		$this->menu[$r++] = [
			'fk_menu'  => '', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'top', // This is a Top menu entry
			'titre'    => 'Digirisk',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/digiriskdolibarr/digiriskdolibarrindex.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled', // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled.
			'perms'    => '$user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		];

		$langs->load("digiriskdolibarr@digiriskdolibarr");

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', // This is a Top menu entry
			'titre'    => $langs->trans('Dashboard'),
			'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/digiriskdolibarr/digiriskdolibarrindex.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled', // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled.
			'perms'    => '$user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->digiriskconst->read' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 2, // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', 										// This is a Left menu entry
			'titre'    => $langs->trans('RiskAssessmentDocument'),
			'prefix'   => '<i class="fas fa-exclamation-triangle pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskstandard',
			'url'      => '/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?risk_type=risk',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->riskassessmentdocument->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskstandard',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => '<i class="fas fa-list pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->trans('Riskprofessionals'),
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisklistingrisk',
			'url'      => '/digiriskdolibarr/view/digiriskelement/risk_list.php?risk_type=risk',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->risk->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskstandard',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tasks pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->trans('PAPRIPACT'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskactionplan',
            'url'      => '/projet/tasks.php?id=' . $conf->global->DIGIRISKDOLIBARR_DU_PROJECT,
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->projet->enabled',
            'perms'    => '$user->rights->projet->lire',
            'target'   => '_blank',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digirisklistingrisk',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskdolibarr_risktags',
            'url'      => '/categories/index.php?type=risk',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->categorie->enabled && $user->rights->digiriskdolibarr->risk->read && $conf->global->DIGIRISKDOLIBARR_CATEGORY_ON_RISK',
            'perms'    => '$user->rights->digiriskdolibarr->risk->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',
            'type'     => 'left',
            'titre'    => $langs->trans('Environment'),
            'prefix'   => '<i class="fas fa-leaf pictofixedwidth"></i>',
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskstandard_riskenvironmental',
            'url'      => '/digiriskdolibarr/view/digiriskstandard/digiriskstandard_card.php?risk_type=riskenvironmental',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled',
            'perms'    => '$user->rights->digiriskdolibarr->riskassessmentdocument->read && $user->rights->digiriskdolibarr->riskenvironmental->read',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskstandard_riskenvironmental',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-list pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->trans('Riskenvironmentals'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digirisklistingrisksenvironmental',
            'url'      => '/digiriskdolibarr/view/digiriskelement/risk_list.php?risk_type=riskenvironmental',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled',
            'perms'    => '$user->rights->digiriskdolibarr->riskenvironmental->read',
            'target'   => '',
            'user'     => 0
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskstandard_riskenvironmental',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tasks pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->trans('ActionPlan'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskenvironmentalactionplan',
            'url'      => '/projet/tasks.php?id=' . $conf->global->DIGIRISKDOLIBARR_ENVIRONMENT_PROJECT,
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->projet->enabled',
            'perms'    => '$user->rights->projet->lire',
            'target'   => '_blank',
            'user'     => 0,
        ];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('PreventionPlan'),
			'prefix'   => '<i class="fas fa-info pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskpreventionplan',
			'url'      => '/digiriskdolibarr/view/preventionplan/preventionplan_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->preventionplan->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskpreventionplan',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskdolibarr_preventionplantags',
            'url'      => '/categories/index.php?type=preventionplan',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->categorie->enabled && $user->rights->digiriskdolibarr->preventionplan->read',
            'perms'    => '$user->rights->digiriskdolibarr->preventionplan->read',
            'target'   => '',
            'user'     => 0,
        ];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('FirePermit'),
			'prefix'   => '<i class="fas fa-fire-alt pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskfirepermit',
			'url'      => '/digiriskdolibarr/view/firepermit/firepermit_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->firepermit->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskfirepermit',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskdolibarr_firepermittags',
            'url'      => '/categories/index.php?type=firepermit',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->categorie->enabled && $user->rights->digiriskdolibarr->firepermit->read',
            'perms'    => '$user->rights->digiriskdolibarr->firepermit->read',
            'target'   => '',
            'user'     => 0,
        ];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('Accident'),
			'prefix'   => '<i class="fas fa-user-injured pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskaccident',
			'url'      => '/digiriskdolibarr/view/accident/accident_list.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->accident->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr,fk_leftmenu=digiriskaccident',
            'type'     => 'left',
            'titre'    => '<i class="fas fa-tags pictofixedwidth" style="padding-right: 4px;"></i>' . $langs->transnoentities('Categories'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskdolibarr_accidenttags',
            'url'      => '/categories/index.php?type=accident',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->categorie->enabled && $user->rights->digiriskdolibarr->accident->read',
            'perms'    => '$user->rights->digiriskdolibarr->accident->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'     => 'left',			                // This is a Left menu entry
            'titre'    => '<i class="fas fa-search pictofixedwidth"></i>' . $langs->transnoentities('AccidentInvestigation'),
            'mainmenu' => 'digiriskdolibarr',
            'leftmenu' => 'digiriskaccidentinvestigation',
            'url'      => '/digiriskdolibarr/view/accidentinvestigation/accidentinvestigation_list.php',
            'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 100 + $r,
            'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->saturne->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'    => '$user->rights->digiriskdolibarr->lire && $user->rights->digiriskdolibarr->accidentinvestigation->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
        ];

		$this->menu[$r++] = [
			'fk_menu' => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    =>  $langs->trans('Users'),
			'prefix'   => '<i class="fas fa-user pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskusers',
			'url'      => '/digiriskdolibarr/view/digiriskusers.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->adminpage->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->trans('Tools'),
			'prefix'   => '<i class="fas fa-wrench pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisktools',
			'url'      => '/digiriskdolibarr/view/digirisktools.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->adminpage->read',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    		// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left', 										// This is a Left menu entry
			'titre'    => $langs->trans('Organization'),
			'prefix'   => '<i class="fas fa-network-wired pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digiriskorganization',
			'url'      => '/digiriskdolibarr/view/digiriskelement/digiriskelement_organization.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->digiriskdolibarr->digiriskelement->read', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('TicketPublicInterface'),
			'prefix'   => '<i class="fa fa-ticket-alt pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => '',
			'url'      => '/custom/digiriskdolibarr/public/ticket/create_ticket.php' . ((!$conf->multicompany->enabled) ? '?entity=' . $conf->entity : ''),
			'langs'    => '',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => 1,			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=digiriskdolibarr',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('DigiriskConfigSociety'),
			'prefix'   => '<i class="fas fa-building pictofixedwidth"></i>',
			'mainmenu' => 'digiriskdolibarr',
			'leftmenu' => 'digirisksocietyconfig',
			'url'      => '/admin/company.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $user->admin',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->admin',			                // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		$this->menu[$r++] = [
			'fk_menu'  => 'fk_mainmenu=ticket',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'     => 'left',			                // This is a Left menu entry
			'titre'    => $langs->transnoentities('DashBoard'),
			'prefix'   => $pictoDigirisk,
			'mainmenu' => 'ticket',
			'leftmenu' => 'ticketstats',
			'url'      => '/digiriskdolibarr/view/ticket/ticket_management_dashboard.php',
			'langs'    => 'digiriskdolibarr@digiriskdolibarr',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 100 + $r,
			'enabled'  => '$conf->digiriskdolibarr->enabled && $conf->ticket->enabled',  // Define condition to show or hide menu entry. Use '$conf->digiriskdolibarr->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'    => '$user->rights->ticket->read && $user->rights->digiriskdolibarr->lire', // Use 'perms'=>'$user->rights->digiriskdolibarr->level1->level2' if you want your menu with a permission rules
			'target'   => '',
			'user'     => 0,				                // 0=Menu for internal users, 1=external users, 2=both
		];

		// Exports profiles provided by this module
		$r = 1;

		$this->export_code[$r] = $this->rights_class . '_ticket';
		$this->export_label[$r] = 'Ticket'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'Ticket';
		$this->export_enabled[$r] = '!empty($conf->ticket->enabled)';
		$this->export_permission[$r] = [["ticket", "manage"]];
		$this->export_fields_array[$r] = [
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 's.fk_pays'=>'Country',
			's.phone'=>'Phone', 's.email'=>'Email', 's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.code_compta'=>'CustomerAccountancyCode', 's.code_compta_fournisseur'=>'SupplierAccountancyCode',
			'cat.rowid'=>"CategId", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory",
			't.rowid'=>"Id", 't.ref'=>"Ref", 't.track_id'=>"TicketTrackId", 't.datec'=>"DateCreation", 't.origin_email'=>"OriginEmail", 't.subject'=>"Subject", 't.message'=>"Message", 't.fk_statut'=>"Status", 't.resolution'=>"Resolution", 't.type_code'=>"Type", 't.category_code'=>"TicketCategory", 't.severity_code'=>"Severity",
		];
		$this->export_TypeFields_array[$r] = [
			's.rowid'=>"List:societe:nom::thirdparty", 's.nom'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 's.fk_pays'=>'List:c_country:label',
			's.phone'=>'Text', 's.email'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text', 's.ape'=>'Text', 's.idprof4'=>'Text', 's.code_compta'=>'Text', 's.code_compta_fournisseur'=>'Text',
			'cat.description'=>"Text", 'cat.fk_parent'=>'List:categorie:label:rowid',
			't.rowid'=>"List:ticket:ref::ticket", 't.entity'=>'Numeric', 't.ref'=>"Text", 't.track_id'=>"Text", 't.datec'=>"Date", 't.origin_email'=>"Text", 't.subject'=>"Text", 't.message'=>"Text", 't.fk_statut'=>"Numeric", 't.resolution'=>"Text", 't.type_code'=>"Text", 't.category_code'=>"Text", 't.severity_code'=>"Text",
		];
		$this->export_entities_array[$r] = [
			's.rowid'=>"company", 's.nom'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 's.fk_pays'=>'company',
			's.phone'=>'company', 's.email'=>'company', 's.siren'=>'company', 's.siret'=>'company', 's.ape'=>'company', 's.idprof4'=>'company', 's.code_compta'=>'company', 's.code_compta_fournisseur'=>'company',
			'cat.rowid'=>'category', 'cat.description'=>'category', 'cat.fk_parent'=>'category'
		];
		// Add multicompany field
		if (!empty($conf->global->MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED)) {
			$nbofallowedentities = count(explode(',', getEntity('ticket'))); // If ticket are shared, nb will be > 1
			if (!empty($conf->multicompany->enabled) && $nbofallowedentities > 1) {
				$this->export_fields_array[$r] += ['t.entity'=>'Entity'];
			}
		}
		$this->export_fields_array[$r] = array_merge($this->export_fields_array[$r], ['group_concat(cat.label)'=>'Categories']);
		$this->export_TypeFields_array[$r] = array_merge($this->export_TypeFields_array[$r], ["group_concat(cat.label)"=>'Text']);
		$this->export_entities_array[$r] = array_merge($this->export_entities_array[$r], ["group_concat(cat.label)"=>'category']);
		$this->export_dependencies_array[$r] = ['category'=>'t.rowid'];
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
		$this->export_permission[$r] = [["categorie", "lire"], ["ticket", "manage"]];
		$this->export_fields_array[$r] = ['cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory", 't.rowid'=>'TicketId', 't.ref'=>'Ref', 't.datec'=>"DateCreation", 't.message' => 'Message', 's.rowid'=>"IdThirdParty", 's.nom'=>"Name"];
		$this->export_TypeFields_array[$r] = ['cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'List:categorie:label:rowid', 't.ref'=>'Text', 't.datec'=>"Date", 't.message'=>"Text", 's.rowid'=>"List:societe:nom:rowid", 's.nom'=>"Text"];
		$this->export_entities_array[$r] = ['t.rowid'=>'ticket', 't.ref'=>'ticket', 't.datec'=>'ticket', 't.message' => 'ticket', 's.rowid'=>"company", 's.nom'=>"company"]; // We define here only fields that use another picto

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

        $objectMetaDatas = [
            'digiriskelement'       => ['langs' => 'DigiriskElement',       'picto' => 'fontawesome_fa-network-wired_fas_#d35968'],
            'risk'                  => ['langs' => 'Risk',                  'picto' => 'fontawesome_fa-exclamation-triangle_fas_#d35968', 'classPath' => 'riskanalysis'],
            'riskassessment'        => ['langs' => 'RiskAssessment',        'picto' => 'fontawesome_fa-chart-line_fas_#d35968',           'classPath' => 'riskanalysis'],
            'evaluator'             => ['langs' => 'Evaluator',             'picto' => 'fontawesome_fa-user-check_fas_#d35968'],
            'risksign'              => ['langs' => 'RiskSign',              'picto' => 'fontawesome_fa-map-signs_fas_#d35968',            'classPath' => 'riskanalysis'],
            'preventionplan'        => ['langs' => 'PreventionPlan',        'picto' => 'fontawesome_fa-info_fas_#d35968'],
            'firepermit'            => ['langs' => 'FirePermit',            'picto' => 'fontawesome_fa-fire-alt_fas_#d35968'],
            'accident'              => ['langs' => 'Accident',              'picto' => 'fontawesome_fa-user-injured_fas_#d35968'],
            'accidentinvestigation' => ['langs' => 'AccidentInvestigation', 'picto' => 'fontawesome_fa-search_fas_#d35968']
        ];
        foreach ($objectMetaDatas as $key => $objectMetaData)  {
            $r++;
            $this->export_code[$r]       = $this->rights_class . '_' . $key;
            $this->export_label[$r]      = $objectMetaData['langs']; // Translation key (used only if key ExportDataset_xxx_z not found)
            $this->export_icon[$r]       = $objectMetaData['picto'];
            $this->export_enabled[$r]    = '!empty($conf->digiriskdolibarr->enabled)';

            $this->export_fields_array[$r]     = [];
            $this->export_TypeFields_array[$r] = [];
            $this->export_entities_array[$r]   = [];

            $keyforclass     = ucfirst($key);
            $keyforclassfile = '/' . $this->rights_class . '/class/' . (isset($objectMetaData['classPath']) ? $objectMetaData['classPath'] . '/' : '') . $key . '.class.php';
            $keyforelement   = $key;
            $keyforalias     = 't';

            require DOL_DOCUMENT_ROOT . '/core/commonfieldsinexport.inc.php';

            $this->export_sql_start[$r] = 'SELECT DISTINCT ';

            $this->export_sql_end[$r]  = ' FROM ' . MAIN_DB_PREFIX . $this->rights_class . '_' . $key . ' as t';
            $this->export_sql_end[$r] .= ' WHERE 1 = 1';
            $this->export_sql_end[$r] .= ' AND t.entity IN (' . getEntity($key) . ')';

            if ($key == 'riskassessment') {
                $key = 'risk';
            }
            $this->export_permission[$r] = [["$this->rights_class", "$key"]];
        }

        // Imports profiles provided by this module
        $r = 1;
        $this->import_code[$r]                 = $this->rights_class . '_risk_' . $r;
        $this->import_label[$r]                = 'Risk'; // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->import_icon[$r]                 = 'fontawesome_fa-exclamation-triangle_fas_#d35968';
        $this->import_tables_array[$r]         = ['t' => MAIN_DB_PREFIX . 'digiriskdolibarr_risk', 'extra' => MAIN_DB_PREFIX . 'digiriskdolibarr_risk_extrafields'];
        $this->import_tables_creator_array[$r] = ['t' => 'fk_user_creat']; // Fields to store import user id

        $importSample    = [];
        $keyforclass     = 'Risk';
        $keyforclassfile = '/digiriskdolibarr/class/riskanalysis/risk.class.php';
        $keyforelement   = 'risk';

        require DOL_DOCUMENT_ROOT . '/core/commonfieldsinimport.inc.php';

        $unsetFields = ['t.rowid', 't.entity', 't.tms', 't.import_key', 't.fk_user_creat', 't.fk_user_modif'];
        foreach ($unsetFields as $unsetField) {
            unset($this->import_fields_array[$r][$unsetField]);
            unset($this->import_TypeFields_array[$r][$unsetField]);
            unset($this->import_entities_array[$r][$unsetField]);
            unset($this->import_help_array[$r][$unsetField]);
        }

        $importExtrafieldSample = [];
        $keyforselect           = 'risk';
        $keyforaliasextra       = 'extra';
        $keyforelement          = 'risk';

        require DOL_DOCUMENT_ROOT . '/core/extrafieldsinimport.inc.php';

        $this->import_entities_array[$r]      = ['t.fk_projet' => 'project']; // We define here only fields that use another icon that the one defined into import_icon
        $this->import_fieldshidden_array[$r]  = ['extra.fk_object' => 'lastrowid-' . MAIN_DB_PREFIX . 'digiriskdolibarr_risk'];
        $this->import_regex_array[$r]         = [];
        $this->import_examplevalues_array[$r] = array_merge($importSample, $importExtrafieldSample);
        $this->import_updatekeys_array[$r]    = ['t.ref' => 'Ref'];
        $this->import_convertvalue_array[$r]  = [
            't.ref' => [
                'rule'        => 'getrefifauto',
                'class'       => (empty($conf->global->DIGIRISKDOLIBARR_RISK_ADDON) ? 'mod_risk_standard' : $conf->global->DIGIRISKDOLIBARR_RISK_ADDON),
                'path'        => '/core/modules/digiriskdolibarr/riskanalysis/risk/' . (empty($conf->global->DIGIRISKDOLIBARR_RISK_ADDON) ? 'mod_risk_standard' : $conf->global->DIGIRISKDOLIBARR_RISK_ADDON) . '.php',
                'classobject' => 'Risk',
                'pathobject'  => '/digiriskdolibarr/class/riskanalysis/risk.class.php'
            ],
            't.fk_projet' => [
                'rule'    => 'fetchidfromref',
                'file'    => '/projet/class/project.class.php',
                'class'   => 'Project',
                'method'  => 'fetch',
                'element' => 'Risk'
            ]
        ];

        $r++;
        $this->import_code[$r]                 = $this->rights_class . '_riskassessment_' . $r;
        $this->import_label[$r]                = 'RiskAssessment'; // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->import_icon[$r]                 = 'fontawesome_fa-chart-line_fas_#d35968';
        $this->import_tables_array[$r]         = ['t' => MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment', 'extra' => MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment_extrafields'];
        $this->import_tables_creator_array[$r] = ['t' => 'fk_user_creat']; // Fields to store import user id

        $importSample    = [];
        $keyforclass     = 'RiskAssessment';
        $keyforclassfile = '/digiriskdolibarr/class/riskanalysis/riskassessment.class.php';
        $keyforelement   = 'riskassessment';

        require DOL_DOCUMENT_ROOT . '/core/commonfieldsinimport.inc.php';

        $unsetFields = ['t.rowid', 't.entity', 't.tms', 't.import_key', 't.fk_user_creat', 't.fk_user_modif'];
        foreach ($unsetFields as $unsetField) {
            unset($this->import_fields_array[$r][$unsetField]);
            unset($this->import_TypeFields_array[$r][$unsetField]);
            unset($this->import_entities_array[$r][$unsetField]);
            unset($this->import_help_array[$r][$unsetField]);
        }

        $importExtrafieldSample = [];
        $keyforselect           = 'riskassessment';
        $keyforaliasextra       = 'extra';
        $keyforelement          = 'riskassessment';

        require DOL_DOCUMENT_ROOT . '/core/extrafieldsinimport.inc.php';

        $this->import_entities_array[$r]      = ['t.fk_risk' => 'fontawesome_fa-exclamation-triangle_fas_#d35968']; // We define here only fields that use another icon that the one defined into import_icon
        $this->import_fieldshidden_array[$r]  = ['extra.fk_object' => 'lastrowid-' . MAIN_DB_PREFIX . 'digiriskdolibarr_riskassessment'];
        $this->import_regex_array[$r]         = [];
        $this->import_examplevalues_array[$r] = array_merge($importSample, $importExtrafieldSample);
        $this->import_updatekeys_array[$r]    = ['t.ref' => 'Ref'];
        $this->import_convertvalue_array[$r]  = [
            't.ref' => [
                'rule'        => 'getrefifauto',
                'class'       => (empty($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON) ? 'mod_riskassessment_standard' : $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON),
                'path'        => '/core/modules/digiriskdolibarr/riskanalysis/riskassessment/' . (empty($conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON) ? 'mod_riskassessment_standard' : $conf->global->DIGIRISKDOLIBARR_RISKASSESSMENT_ADDON) . '.php',
                'classobject' => 'RiskAssessment',
                'pathobject'  => '/digiriskdolibarr/class/riskanalysis/riskassessment.class.php'
            ]
        ];
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

        if (empty($conf->global->DIGIRISKDOLIBARR_ACCIDENT_REMOVE_FK_USER_VICTIM)) {

            require_once __DIR__ . '/../../class/accident.class.php';
            require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

            $accident  = new Accident($this->db);
            $signatory = new SaturneSignature($this->db);

            $accidentList = $accident->fetchAll('','',0,0, ['customsql' => 'fk_user_victim > 0']);

            if (is_array($accidentList) && !empty($accidentList)) {
                foreach($accidentList as $accidentSingle) {
                    $signatory->setSignatory($accidentSingle->id, 'accident', 'user', array($accidentSingle->fk_user_victim), 'Victim');
                }
            }
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_ACCIDENT_REMOVE_FK_USER_VICTIM', 1, 'integer', 0, '', $conf->entity);
        }

		$sql = [];
		// Load sql sub folders
		$sqlFolder = scandir(__DIR__ . '/../../sql');
		foreach ($sqlFolder as $subFolder) {
			if ( ! preg_match('/\./', $subFolder)) {
				$this->_load_tables('/digiriskdolibarr/sql/' . $subFolder . '/');
			}
		}

		$this->_load_tables('/digiriskdolibarr/sql/');

        dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

		delDocumentModel('informationssharing_odt', 'informationssharing');
		delDocumentModel('legaldisplay_odt', 'legaldisplay');
		delDocumentModel('firepermitdocument_odt', 'firepermitdocument');
		delDocumentModel('preventionplandocument_odt', 'preventionplandocument');
		delDocumentModel('preventionplandocument_specimen_odt', 'preventionplandocumentspecimen');
		delDocumentModel('groupmentdocument_odt', 'groupmentdocument');
		delDocumentModel('workunitdocument_odt', 'workunitdocument');
		delDocumentModel('listingrisksaction_odt', 'listingrisksdocument');
        delDocumentModel('listingrisksdocument_odt', 'listingrisksdocument');
        delDocumentModel('listingrisksphoto_odt', 'listingrisksdocument');
		delDocumentModel('listingrisksenvironmentalaction_odt', 'listingrisksenvironmentaldocument');
		delDocumentModel('riskassessmentdocument_odt', 'riskassessmentdocument');
		delDocumentModel('auditreportdocument_odt', 'auditreportdocument');
		delDocumentModel('ticketdocument_odt', 'ticketdocument');
        delDocumentModel('papripact_a3_paysage_projectdocument', 'project');
        delDocumentModel('accidentinvestigationdocument_odt', 'accidentinvestigationdocument');
        delDocumentModel('registerdocument_odt', 'registerdocument');

		addDocumentModel('informationssharing_odt', 'informationssharing', 'ODT templates', 'DIGIRISKDOLIBARR_INFORMATIONSSHARING_ADDON_ODT_PATH');
		addDocumentModel('legaldisplay_odt', 'legaldisplay', 'ODT templates', 'DIGIRISKDOLIBARR_LEGALDISPLAY_ADDON_ODT_PATH');
		addDocumentModel('firepermitdocument_odt', 'firepermitdocument', 'ODT templates', 'DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_odt', 'preventionplandocument', 'ODT templates', 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('preventionplandocument_specimen_odt', 'preventionplandocumentspecimen', 'ODT templates', 'DIGIRISKDOLIBARR_PREVENTIONPLANDOCUMENT_SPECIMEN_ADDON_ODT_PATH');
		addDocumentModel('groupmentdocument_odt', 'groupmentdocument', 'ODT templates', 'DIGIRISKDOLIBARR_GROUPMENTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('workunitdocument_odt', 'workunitdocument', 'ODT templates', 'DIGIRISKDOLIBARR_WORKUNITDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('listingrisksdocument_odt', 'listingrisksdocument', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('listingrisksaction_odt', 'listingrisksdocument', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH');
        addDocumentModel('listingrisksphoto_odt', 'listingrisksdocument', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH');
        addDocumentModel('listingrisksenvironmentalaction_odt', 'listingrisksenvironmentaldocument', 'ODT templates', 'DIGIRISKDOLIBARR_LISTINGRISKSENVIRONMENTALACTION_ADDON_ODT_PATH');
		addDocumentModel('riskassessmentdocument_odt', 'riskassessmentdocument', 'ODT templates', 'DIGIRISKDOLIBARR_RISKASSESSMENTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('auditreportdocument_odt', 'auditreportdocument', 'ODT templates', 'DIGIRISKDOLIBARR_AUDITREPORTDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('ticketdocument_odt', 'ticketdocument', 'ODT templates', 'DIGIRISKDOLIBARR_TICKETDOCUMENT_ADDON_ODT_PATH');
		addDocumentModel('papripact_a3_paysage_projectdocument', 'project', 'PAPRIPACT-A3-PAYSAGE');
        addDocumentModel('accidentinvestigationdocument_odt', 'accidentinvestigationdocument', 'ODT templates', 'DIGIRISKDOLIBARR_ACCIDENTINVESTIGATIONDOCUMENT_ADDON_ODT_PATH');
        addDocumentModel('registerdocument_odt', 'registerdocument', 'ODT templates', 'DIGIRISKDOLIBARR_REGISTERDOCUMENT_ADDON_ODT_PATH');

		if ( $conf->global->DIGIRISKDOLIBARR_DIGIRISKELEMENT_TRASH == 0 ) {
			require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

			$trashRef                      = 'GP0';
			$digiriskelement               = new Groupment($this->db);
			$digiriskelement->ref          = $trashRef;
			$digiriskelement->label        = $langs->trans('HiddenElements');
			$digiriskelement->element_type = 'groupment';
			$digiriskelement->ranks        = 0;
			$digiriskelement->description  = $langs->trans('TrashGroupment');
			$digiriskelement->status       = DigiriskElement::STATUS_TRASHED;
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

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once __DIR__ . '/../../class/digiriskresources.class.php';

        $societe   = new Societe($this->db);
        $resources = new DigiriskResources($this->db);

        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_SET') == 0) {
            $societe->name   = $langs->trans('SAMU') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client = 0;
            $societe->phone  = '15';
            $societe->url    = '';
            $samuID          = $societe->create($user);

            $societe->name   = $langs->trans('Pompiers') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client = 0;
            $societe->phone  = '18';
            $societe->url    = '';
            $pompiersID      = $societe->create($user);

            $societe->name   = $langs->trans('Police') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client = 0;
            $societe->phone  = '17';
            $societe->url    = '';
            $policeID        = $societe->create($user);

            $societe->name   = $langs->trans('AllEmergencies') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client = 0;
            $societe->phone  = '112';
            $societe->url    = '';
            $emergencyID     = $societe->create($user);

            $resources->setDigiriskResources($this->db, 1,  'Police',  'societe', [$policeID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1,  'SAMU',  'societe', [$samuID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1,  'Pompiers',  'societe', [$pompiersID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1,  'AllEmergencies',  'societe', [$emergencyID], $conf->entity);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 1, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_SET') == 1) {
            //Install after 8.1.2
            $societe->name     = $langs->trans('LabourInspectorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client   = 0;
            $societe->phone    = '';
            $societe->url      = $langs->trans('UrlLabourInspector');
            $labourInspectorID = $societe->create($user);

            $societe->name    = $langs->trans('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client  = 0;
            $societe->phone   = '';
            $societe->url     = '';
            $rightsDefenderID = $societe->create($user);

            $societe->name         = $langs->trans('PoisonControlCenter') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client       = 0;
            $societe->phone        = '';
            $societe->url          = '';
            $poisonControlCenterID = $societe->create($user);

            $resources->setDigiriskResources($this->db, 1,  'LabourInspectorSociety',  'societe', [$labourInspectorID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1,  'RightsDefender',  'societe', [$rightsDefenderID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1,  'PoisonControlCenter',  'societe', [$poisonControlCenterID], $conf->entity);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 2, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_SET') == 2) {
            $societe->name   = $langs->trans('LabourDoctorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->client = 0;
            $societe->phone  = '';
            $societe->url    = '';
            $labourDoctorID  = $societe->create($user);

            $resources->setDigiriskResources($this->db, 1,  'LabourDoctorSociety',  'societe', [$labourDoctorID], $conf->entity);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 3, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_SET') == 3) {
            $poisonCenters = [
                'ANGERS'    => ['phone' => '02 41 48 21 21'],
                'BORDEAUX'  => ['phone' => '05 56 96 40 80'],
                'LILLE'     => ['phone' => '08 00 59 59 59'],
                'LYON'      => ['phone' => '04 72 11 69 11'],
                'MARSEILLE' => ['phone' => '04 91 75 25 25'],
                'NANCY'     => ['phone' => '03 83 22 50 50'],
                'PARIS'     => ['phone' => '01 40 05 48 48'],
                'TOULOUSE'  => ['phone' => '05 61 77 74 47']
            ];

            foreach ($poisonCenters as $city => $poisonCenter) {
                $societe->name         = $langs->trans('PoisonControlCenter') . ' ' . $city . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
                $societe->client       = 0;
                $societe->phone        = $poisonCenter['phone'];
                $societe->url          = '';
                $poisonControlCenterID = $societe->create($user);
            }

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_SET', 4, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIRISKDOLIBARR_CONTACTS_SET') == 0) {
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            require_once __DIR__ . '/../../class/digiriskresources.class.php';

            $contact   = new Contact($this->db);
            $resources = new DigiriskResources($this->db);

            $allLinks  = $resources->fetchDigiriskResource('LabourDoctorSociety');

            $labourDoctor            = $contact;
            $labourDoctor->socid     = $allLinks;
            $labourDoctor->firstname = $langs->transnoentities('LabourDoctorFirstName');
            $labourDoctor->lastname  = $langs->trans('LabourDoctorLastName');
            $labourDoctorID          = $labourDoctor->create($user);

            $allLinks = $resources->fetchDigiriskResource('LabourInspectorSociety');

            $labourInspector            = $contact;
            $labourInspector->socid     = $allLinks;
            $labourInspector->firstname = $langs->trans('LabourInspectorFirstName');
            $labourInspector->lastname  = $langs->trans('LabourInspectorLastName');
            $labourInspectorID          = $labourInspector->create($user);

            $resources->setDigiriskResources($this->db, 1, 'LabourDoctorContact', 'socpeople', [$labourDoctorID], $conf->entity);
            $resources->setDigiriskResources($this->db, 1, 'LabourInspectorContact', 'socpeople', [$labourInspectorID], $conf->entity);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CONTACTS_SET', 1, 'integer', 0, '', $conf->entity);
        }

        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_UPDATED') == 0) {
            $labourInspectorID = $resources->fetchDigiriskResource('LabourInspectorSociety');
            $societe->fetch($labourInspectorID);
            $societe->name = $langs->trans('LabourInspectorName') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $policeID = $resources->fetchDigiriskResource('Police');
            $societe->fetch($policeID);
            $societe->name = $langs->trans('Police') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $samuID = $resources->fetchDigiriskResource('SAMU');
            $societe->fetch($samuID);
            $societe->name = $langs->trans('SAMU') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $pompiersID = $resources->fetchDigiriskResource('Pompiers');
            $societe->fetch($pompiersID);
            $societe->name = $langs->trans('Pompiers') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $emergencyID = $resources->fetchDigiriskResource('AllEmergencies');
            $societe->fetch($emergencyID);
            $societe->name = $langs->trans('AllEmergencies') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $rightsDefenderID = $resources->fetchDigiriskResource('RightsDefender');
            $societe->fetch($rightsDefenderID);
            $societe->name = $langs->transnoentities('RightsDefender') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            $poisonControlCenterID = $resources->fetchDigiriskResource('PoisonControlCenter');
            $societe->fetch($poisonControlCenterID);
            $societe->name = $langs->trans('PoisonControlCenter') . ' - ' . $conf->global->MAIN_INFO_SOCIETE_NOM;
            $societe->update(0, $user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 1, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_UPDATED') == 1) {
            $rightsDefenderID = $resources->fetchDigiriskResource('RightsDefender');
            $societe->fetch($rightsDefenderID);
            $societe->phone = '09 69 39 00 00';
            $societe->url   = 'https://www.defenseurdesdroits.fr/';
            $societe->update(0, $user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 2, 'integer', 0, '', $conf->entity);
        }
		if (getDolGlobalInt('DIGIRISKDOLIBARR_THIRDPARTY_UPDATED') == 2) {
			require_once __DIR__ . '/../../../saturne/class/saturneschedules.class.php';

			$labourDoctorID = $resources->fetchDigiriskResource('LabourDoctorSociety');
			$societe->fetch($labourDoctorID);
            $result = $societe->setValueFrom('nom', $langs->transnoentities('LabourDoctorNameFull') . ' - ' . getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));

			if ($result >= 0) {
				$schedule             = new SaturneSchedules($this->db);
				$schedule->element_id = $labourDoctorID;

				$schedule->monday    = $langs->transnoentities('weekDayDefault');
				$schedule->tuesday 	 = $langs->transnoentities('weekDayDefault');
				$schedule->wednesday = $langs->transnoentities('weekDayDefault');
				$schedule->thursday  = $langs->transnoentities('weekDayDefault');
				$schedule->friday 	 = $langs->transnoentities('weekDayDefault');
				$schedule->saturday  = $langs->transnoentities('weekEndDefault');
				$schedule->sunday 	 = $langs->transnoentities('weekEndDefault');

				$schedule->element_type = 'societe';
				$schedule->status       = 1;

				$schedule->create($user);
			}

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_THIRDPARTY_UPDATED', 3, 'integer', 0, '', $conf->entity);
		}

        // Create extrafields during init
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

        $extraFields = new ExtraFields($this->db);

        $commonExtraFieldsValue = [
            'alwayseditable' => 1, 'list' => 1, 'help' => '', 'entity' => 0, 'langfile' => 'digiriskdolibarr@digiriskdolibarr', 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('project')", 'moreparams' => ['css' => 'minwidth100 maxwidth300']
        ];

        $extraFieldsArrays = [
            'fk_risk'                  => ['Label' => 'Risk',                  'type' => 'link', 'elementtype' => ['projet_task'], 'position' => $this->numero . 10, 'params' => ['Risk:digiriskdolibarr/class/riskanalysis/risk.class.php:1:(entity:IN:__SHARED_ENTITIES__)' => NULL],                    ],
            'fk_preventionplan'        => ['Label' => 'PreventionPlan',        'type' => 'link', 'elementtype' => ['projet_task'], 'position' => $this->numero . 20, 'params' => ['PreventionPlan:digiriskdolibarr/class/preventionplan.class.php:1:(entity:IN:__SHARED_ENTITIES__)' => NULL],             ],
            'fk_firepermit'            => ['Label' => 'FirePermit',            'type' => 'link', 'elementtype' => ['projet_task'], 'position' => $this->numero . 30, 'params' => ['FirePermit:digiriskdolibarr/class/firepermit.class.php:1:(entity:IN:__SHARED_ENTITIES__)' => NULL],                     ],
            'fk_accident'              => ['Label' => 'Accident',              'type' => 'link', 'elementtype' => ['projet_task'], 'position' => $this->numero . 40, 'params' => ['Accident:digiriskdolibarr/class/accident.class.php:1:(entity:IN:__SHARED_ENTITIES__)' => NULL],                         ],
            'fk_accidentinvestigation' => ['Label' => 'AccidentInvestigation', 'type' => 'link', 'elementtype' => ['projet_task'], 'position' => $this->numero . 50, 'params' => ['AccidentInvestigation:digiriskdolibarr/class/accidentinvestigation.class.php:1:(entity:IN:__SHARED_ENTITIES__)' => NULL]],

            'wp_digi_id' => ['Label' => 'WPDigiID', 'type' => 'int', 'length' => 100, 'elementtype' => ['digiriskdolibarr_digiriskelement'], 'position' => $this->numero . 10, 'list' => 0, 'enabled' => "isModEnabled('digiriskdolibarr')"],
            'entity'     => ['Label' => 'Entity',   'type' => 'int', 'length' => 100, 'elementtype' => ['digiriskdolibarr_digiriskelement'], 'position' => $this->numero . 20, 'list' => 0, 'enabled' => "isModEnabled('digiriskdolibarr')"],

            'professional_qualification' => ['Label' => 'ProfessionalQualification', 'type' => 'varchar', 'length' => 255, 'elementtype' => ['user'], 'position' => $this->numero . 10,                                                                                                'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('user')"],
            'contract_type'              => ['Label' => 'ContractType',              'type' => 'select',                   'elementtype' => ['user'], 'position' => $this->numero . 20, 'params' => [1 => 'CDI', 2 => 'CDD', 3 => 'Apprentice/Student', 4 => 'Interim', 5 => 'Other'], 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('user')"],

            'ticket_category_config' => ['Label' => 'TicketCategoryConfig', 'type' => 'text', 'elementtype' => ['categorie'], 'position' => $this->numero . 10, 'list' => 0, 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('categorie') && isModEnabled('ticket')", 'moreparams' => []]
        ];

        saturne_manage_extrafields($extraFieldsArrays, $commonExtraFieldsValue);

        if (dolibarr_get_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) == 1) {
            $commonExtraFieldsValue = [
                'alwayseditable' => 1, 'list' => 1, 'help' => '', 'entity' => 0, 'langfile' => 'digiriskdolibarr@digiriskdolibarr', 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('ticket')", 'moreparams' => ['css' => 'minwidth100 maxwidth300']
            ];

            $extraFieldsArrays = [
                'digiriskdolibarr_ticket_lastname'  => ['Label' => 'LastName',        'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630210,                                                                                                        ],
                'digiriskdolibarr_ticket_firstname' => ['Label' => 'FirstName',       'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630220,                                                                                                        ],
                'digiriskdolibarr_ticket_phone'     => ['Label' => 'Phone',           'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630230,                                                                                                        ],
                'digiriskdolibarr_ticket_service'   => ['Label' => 'GP/UT',           'type' => 'link',                      'elementtype' => ['ticket'], 'position' => 43630240, 'params' => ['DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php:1' => NULL], 'list' => 4],
                'digiriskdolibarr_ticket_location'  => ['Label' => 'Location',        'type' => 'varchar',  'length' => 255, 'elementtype' => ['ticket'], 'position' => 43630250,                                                                                                        ],
                'digiriskdolibarr_ticket_date'      => ['Label' => 'DeclarationDate', 'type' => 'datetime',                  'elementtype' => ['ticket'], 'position' => 43630260,                                                                                                        ]
            ];

            saturne_manage_extrafields($extraFieldsArrays, $commonExtraFieldsValue);
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 2, 'integer', 0, '', 0);
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
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $tags = new Categorie($this->db);

        if (getDolGlobalInt('DIGIRISKDOLIBARR_PROJECT_TAGS_SET') == 0) {
            $tags->label = 'QHSE';
            $tags->type  = 'project';
            $tagID       = $tags->create($user);

            $tags->label     = 'DU';
            $tags->type      = 'project';
            $tags->fk_parent = $tagID;
            $tags->create($user);

            $tags->label     = 'PP';
            $tags->type      = 'project';
            $tags->fk_parent = $tagID;
            $tags->create($user);

            $tags->label     = 'ACC';
            $tags->type      = 'project';
            $tags->fk_parent = $tagID;
            $tags->create($user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 3, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_PROJECT_TAGS_SET') == 1) {
            //Install after 8.3.0
            $tags->fetch('', 'QHSE');

            $tags->label     = 'FP';
            $tags->type      = 'project';
            $tags->fk_parent = $tags->id;
            $tags->create($user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 2, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_PROJECT_TAGS_SET') == 2) {
            //Install after 9.3.0
            $tags->fetch('', 'QHSE');
            $tags->label     = 'TS';
            $tags->type      = 'project';
            $tags->fk_parent = $tags->id;
            $tags->create($user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 3, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_PROJECT_TAGS_SET') == 3) {
            //Install after 10.0.0
            $tags->fetch('', 'QHSE');
            $tags->label     = 'ENV';
            $tags->type      = 'project';
            $tags->fk_parent = $tags->id;
            $tags->create($user);

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_PROJECT_TAGS_SET', 4, 'integer', 0, '', $conf->entity);
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

		if (empty($conf->global->DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE) && empty($conf->global->DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE)) {
			require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
			require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

			$project       = new Project($this->db);
			$projectTypeId = array_key_first($project->liste_type_contact('internal', 'position', 0, 1, 'PROJECTCONTRIBUTOR'));
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DEFAULT_PROJECT_CONTACT_TYPE', $projectTypeId, 'integer', 0, '', $conf->entity);

			$task       = new Task($this->db);
			$taskTypeId = array_key_first($task->liste_type_contact('internal', 'position', 0, 1, 'TASKCONTRIBUTOR'));
			dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_DEFAULT_TASK_CONTACT_TYPE', $taskTypeId, 'integer', 0, '', $conf->entity);
		}

		$params = [
			'digiriskdolibarr' => [																			// nom informatif du module externe qui apporte ses paramètres
				'sharingelements' => [																			// section des paramètres 'element' et 'object'
					//partage digiriskelement
					'digiriskelement' => [																		// Valeur utilisée dans getEntity()
						'type'    => 'element',																		// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'    => 'info-circle',																	// Font Awesome icon
						'lang'    => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip' => 'DigiriskElementSharedTooltip',												// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'enable'  => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'input'   => [																			// input : Paramétrage de la réaction du bouton on/off
							'global' => [																		// global : réaction lorsqu'on désactive l'option de partage global
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							]
						]
					],
					//partage risk
					'risk' => [																				// Valeur utilisée dans getEntity()
						'type'      => 'element',																	// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'      => 'exclamation-triangle',														// Font Awesome icon
						'lang'      => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip'   => 'RiskSharedTooltip',															// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'mandatory' => 'digiriskelement',															// partage principal obligatoire
						'enable'    => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'display'   => '! empty($conf->global->MULTICOMPANY_DIGIRISKELEMENT_SHARING_ENABLED)', 		// L'affichage de ce bloc de partage dépend de l'activation d'un partage parent
						'input'     => [																		// input : Paramétrage de la réaction du bouton on/off
							'global' => [																		// global : réaction lorsqu'on désactive l'option de partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							],
							'digiriskelement' => [																// digiriskelement (nom du module principal) : réaction lorsqu'on désactive le partage principal (ici le partage des digiriskelements)
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage principal
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage principal
								'del'      => true																	// del : supprime la constante du partage lors de la désactivation du partage principal
							]
						]
					],
					//partage risk sign
					'risksign' => [																			// Valeur utilisée dans getEntity()
						'type'      => 'element',																	// element: partage d'éléments principaux (thirdparty, product, member, etc...)
						'icon'      => 'map-signs',																	// Font Awesome icon
						'lang'      => 'digiriskdolibarr@digiriskdolibarr',											// Fichier de langue contenant les traductions
						'tooltip'   => 'RiskSignSharedTooltip',														// Message Tooltip (ne pas mettre cette clé si pas de tooltip)
						'mandatory' => 'digiriskelement',															// partage principal obligatoire
						'enable'    => '! empty($conf->digiriskdolibarr->enabled)',									// Conditions d'activation du partage
						'display'   => '! empty($conf->global->MULTICOMPANY_DIGIRISKELEMENT_SHARING_ENABLED)', 		// L'affichage de ce bloc de partage dépend de l'activation d'un partage parent
						'input'     => [																		// input : Paramétrage de la réaction du bouton on/off
							'global' => [																		// global : réaction lorsqu'on désactive l'option de partage global
								'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage global
								'del'      => true																	// del : suppression de la constante du partage lors de la désactivation du partage global
							],
							'digiriskelement' => [																// digiriskelement (nom du module principal) : réaction lorsqu'on désactive le partage principal (ici le partage des digiriskelements)
								'showhide' => true,																	// showhide : afficher/cacher le bloc de partage lors de l'activation/désactivation du partage principal
								//'hide'     => true,																	// hide : cache le bloc de partage lors de la désactivation du partage principal
								'del'      => true																	// del : supprime la constante du partage lors de la désactivation du partage principal
							]
						]
					],
				],
				'sharingmodulename' => [																		// correspondance des noms de modules pour le lien parent ou compatibilité (ex: 'productsupplierprice'	=> 'product')
					'digiriskelement' => 'digiriskdolibarr',
					'risk'            => 'digiriskdolibarr',
					'risksign'        => 'digiriskdolibarr',
				],
			]
		];

		$externalmodule = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
		$externalmodule = !empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING) ? array_merge($externalmodule, $params) : $params;
		$jsonformat = json_encode($externalmodule);
		dolibarr_set_const($this->db, "MULTICOMPANY_EXTERNAL_MODULES_SHARING", $jsonformat, 'json', 0, '', 0);

        // BACKWARD NUM REF
        $objectTypeAndMod = [];
        if (getDolGlobalInt('DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET') == 0) {
            $objectTypeAndMod = [
                'Risk'                  => ['tarqeq', 'RK{0}'],
                'RiskAssessment'        => ['jarnsaxa', 'RA{0}'],
                'RiskSign'              => ['greip', 'RS{0}'],
                'Evaluator'             => ['bebhionn', 'EV{0}'],
                'Groupment'             => ['sirius', 'GP{0}'],
                'WorkUnit'              => ['canopus', (version_compare($conf->global->DIGIRISKDOLIBARR_VERSION, '9.14.1', '>=')  ? 'UT{0}' : 'WU{0}')],
                'Accident'              => ['curtiss', 'ACC{0}'],
                'AccidentLesion'        => ['wright', 'ACCL{0}'],
                'AccidentWorkStop'      => ['richthofen', 'ACCW{0}'],
                'AccidentInvestigation' => ['peggy', 'AI{0}'],
                'PreventionPlan'        => ['hinkler', 'PP{0}'],
                'PreventionPlanDet'     => ['alvaldi', 'PPR{0}'],
                'FirePermit'            => ['bleriot', 'FP{0}'],
                'FirePermitDet'         => ['earhart', 'FPR{0}'],

                'LegalDisplay'                  => ['gerd', 'LD{0}'],
                'InformationsSharing'           => ['gridr', 'IS{0}'],
                'ListingRisksDocument'          => ['calypso', 'RLD{0}'],
                'ListingRisksAction'            => ['gunnlod', 'RLA{0}'],
                'ListingRisksPhoto'             => ['fornjot', 'RLP{0}'],
                'GroupmentDocument'             => ['mundilfari', 'GPD{0}'],
                'WorkUnitDocument'              => ['hati', (version_compare($conf->global->DIGIRISKDOLIBARR_VERSION, '9.14.1', '>=') ? 'UTD{0}' : 'WUD{0}')],
                'RiskAssessmentDocument'        => ['eggther', 'DU{0}'],
                'PreventionPlanDocument'        => ['bestla', 'PPD{0}'],
                'FirePermitDocument'            => ['greip', 'FPD{0}'],
                'AccidentInvestigationDocument' => ['siarnaq', 'AID{0}'],
                'TicketDocument'                => ['geirrod', 'TD{0}'],
                'ProjectDocument'               => ['angrboda', 'PJD{0}'],
            ];

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET', 1, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET') > 0 || getDolGlobalInt('DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET') < 3) {
            $objectTypeAndMod['RegisterDocument']                = ['thiazzi', 'RD{0}'];
            $objectTypeAndMod['ListingRisksDocument']            = ['calypso', 'RLD{0}'];
            $objectTypeAndMod['AuditReportDocument']             = ['lindberg', 'ARD{0}'];
            $objectTypeAndMod['ListingRisksEnvironmentalAction'] = ['jocaste', 'RLE{0}'];
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET', 3, 'integer', 0, '', $conf->entity);
        }
        if (getDolGlobalInt('DIGIRISKDOLIBARR_CUSTOM_NUM_REF_SET') >= 0) {
            foreach($objectTypeAndMod as $type => $mod) {
                $confNumRef    = 'DIGIRISKDOLIBARR_' . strtoupper($type) . '_' . strtoupper($mod[0]) . '_ADDON';
                $confObjectRef = 'DIGIRISKDOLIBARR_' . strtoupper($type) . '_ADDON';
                $prefix        = $mod[1];

                dolibarr_set_const($this->db, $confNumRef, $prefix, 'chaine', 0, '', $conf->entity);
                dolibarr_set_const($this->db, $confObjectRef, 'mod_'. strtolower($type) .'_' . $mod[0], 'chaine', 0, '', $conf->entity);
            }
        }

        // BACKWARD LISTINGRISKSDOCUMENT ODT PATH
        if (getDolGlobalInt('DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_BACKWARD_ODT_PATH_SET') == 0) {
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_LISTINGRISKSACTION_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksdocument/listingrisksaction/', 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_LISTINGRISKSPHOTO_ADDON_ODT_PATH', 'DOL_DOCUMENT_ROOT/custom/digiriskdolibarr/documents/doctemplates/listingrisksdocument/listingrisksphoto/', 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_LISTINGRISKSDOCUMENT_BACKWARD_ODT_PATH_SET', 1, 'integer', 0, '', $conf->entity);
        }

        $documentsPath = DOL_DATA_ROOT . ($conf->entity > 1 ? '/' . $conf->entity : '');
        $mediaPath     =  $documentsPath . '/digiriskdolibarr';

        if (is_dir($mediaPath . '/accident_investigation')) {
            chmod($mediaPath . '/accident_investigation', 0755);
            rename($mediaPath . '/accident_investigation', $mediaPath . '/accidentinvestigation');
        }
        if (is_dir($mediaPath . '/accident_investigationdocument')) {
            chmod($mediaPath . '/accident_investigationdocument', 0755);
            rename($mediaPath . '/accident_investigationdocument', $mediaPath . '/accidentinvestigationdocument');
        }

        if (!getDolGlobalInt('DIGIRISKDOLIBARR_BACKWARD_TRASH_ELEMENTS') && $conf->entity == 1) {
            require_once __DIR__ . '/../../class/digiriskelement.class.php';

            $digiriskElement = new DigiriskElement($this->db);

            $trashElementIds = $digiriskElement->getMultiEntityTrashList();
            if (!empty($trashElementIds)) {
                $filter = ['customsql' => 't.rowid IN ' . $digiriskElement->getTrashExclusionSqlFilter()];
                $digiriskElement->ismultientitymanaged = 0;
                $digiriskElements = $digiriskElement->fetchAll('', '', 0, 0, $filter);
                foreach ($digiriskElements as $digiriskElement) {
                    $digiriskElement->status = DigiriskElement::STATUS_TRASHED;
                    $digiriskElement->setValueFrom('status', $digiriskElement->status, '', '', 'int');
                }

                dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_BACKWARD_TRASH_ELEMENTS', 1, 'integer');
            }
        }

        // BACKWARD REMOVE TICKET PUBLIC INTERFACE CONST
        if (!getDolGlobalInt('DIGIRISKDOLIBARR_REMOVE_TICKET_PUBLIC_INTERFACE_CONST') && getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY')) {
            $category = new Categorie($this->db);

            $category->fetch(getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY'));

            $category->table_element = 'categorie';
            $category->array_options['options_ticket_category_config'] = json_encode([
                'digiriskdolibarr_ticket_lastname_visible'   => 'on',
                'digiriskdolibarr_ticket_lastname_required'  => 'on',
                'digiriskdolibarr_ticket_firstname_visible'  => 'on',
                'digiriskdolibarr_ticket_firstname_required' => 'on',
                'digiriskdolibarr_ticket_phone_visible'      => 'on',
                'digiriskdolibarr_ticket_service_visible'    => 'on',
                'digiriskdolibarr_ticket_service_required'   => 'on',
                'digiriskdolibarr_ticket_location_visible'   => 'on',
                'digiriskdolibarr_ticket_date_visible'       => 'on',
                'digiriskdolibarr_ticket_date_required'      => 'on',
				'digiriskdolibarr_ticket_email_visible'      => 'on',
				'photo_visible'                              => 'on',
            ]);
            $category->updateExtraField('ticket_category_config');

            dolibarr_set_const($this->db, 'DIGIRISKDOLIBARR_REMOVE_TICKET_PUBLIC_INTERFACE_CONST', 1, 'integer', 0, '', $conf->entity);
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
		global $conf;

		$sql = [];
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
