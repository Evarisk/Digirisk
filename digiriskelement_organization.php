<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       digiriskelement_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiriskelement
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './class/digiriskdocuments.class.php';
require_once './class/digiriskelement.class.php';
require_once './class/digiriskdocuments/groupmentdocument.class.php';
require_once './class/digiriskdocuments/workunitdocument.class.php';
require_once './lib/digiriskdolibarr_digiriskelement.lib.php';
require_once './lib/digiriskdolibarr_function.lib.php';
require_once './core/modules/digiriskdolibarr/digiriskelement/groupment/mod_groupment_standard.php';
require_once './core/modules/digiriskdolibarr/digiriskelement/workunit/mod_workunit_standard.php';
require_once './core/modules/digiriskdolibarr/digiriskdocuments/groupmentdocument/modules_groupmentdocument.php';
require_once './core/modules/digiriskdolibarr/digiriskdocuments/workunitdocument/modules_workunitdocument.php';

global $db, $conf, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'digiriskelementcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$element_type        = GETPOST('element_type', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$object      = new DigiriskElement($db);
$extrafields = new ExtraFields($db);

$object->fetch($id);

if ( $object->element_type == 'groupment') {
	$digiriskelementdocument = new GroupmentDocument($db);
} elseif (  $object->element_type == 'workunit' ) {
	$digiriskelementdocument = new WorkUnitDocument($db);
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('digiriskelementcard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

//Security check
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/digiriskstandard_card.php?id=1', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($object->id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/digiriskelement_card.php', 1).'?id='.($object->id > 0 ? $object->id : '__ID__');
		}
	}

	// Action to add record
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	if ($action == 'add' && $permissiontoadd) { ?>
		<script>
			jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );
			//console.log( this );
			let id = $(this).attr('value');
			jQuery( this ).closest( '.unit' ).addClass( 'active' );

			var unitActive = jQuery( this ).closest( '.unit.active' ).attr('id');
			localStorage.setItem('unitactive', unitActive );

			jQuery( this ).closest( '.unit' ).attr( 'value', id );
		</script>
		<?php
	}

	if ($action == 'view' && $permissiontoadd) {
		header('Location: ' . $backtopage);
	}

	// Action to build doc
	if ($action == 'builddoc' && $permissiontoadd) {
		$outputlangs = $langs;
		$newlang = '';

		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		if (empty($hidedetails)) $hidedetails = 0;
		if (empty($hidedesc)) $hidedesc = 0;
		if (empty($hideref)) $hideref = 0;
		if (empty($moreparams)) $moreparams = null;

		$model      = GETPOST('model', 'alpha');

		$moreparams['object'] = $object;
		$moreparams['user']   = $user;

		$result = $digiriskelementdocument->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			if (empty($donotredirect))
			{
				setEventMessages($langs->trans("FileGenerated") . ' - ' . $digiriskelementdocument->last_main_doc, null);

				$urltoredirect = $_SERVER['REQUEST_URI'];
				$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
				$urltoredirect = preg_replace('/action=builddoc&?/', '', $urltoredirect); // To avoid infinite loop

				header('Location: ' . $urltoredirect . '#builddoc');
				exit;
			}
		}
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $permissiontodelete) {
		if (!empty($upload_dir)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			$langs->load("other");
			$filetodelete = GETPOST('file', 'alpha');
			$file = $upload_dir.'/'.$filetodelete;
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
			else setEventMessages($langs->trans("ErrorFailToDeleteFile", $filetodelete), null, 'errors');

			// Make a redirect to avoid to keep the remove_file into the url that create side effects
			$urltoredirect = $_SERVER['REQUEST_URI'];
			$urltoredirect = preg_replace('/#builddoc$/', '', $urltoredirect);
			$urltoredirect = preg_replace('/action=remove_file&?/', '', $urltoredirect);

			header('Location: '.$urltoredirect);
			exit;
		}
		else {
			setEventMessages('BugFoundVarUploaddirnotDefined', null, 'errors');
		}
	}

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes")
	{
		$object->fetch($id);
		$result = $object->delete($user);

		if ($result > 0)
		{
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header('Location: '.$backurlforlist);
			exit;
		} else {
			dol_syslog($object->error, LOG_DEBUG);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

/*
 * View
 */

$form        = new Form($db);
$emptyobject = new stdClass($db);
$formconfirm = '';

$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;


if ( $object->element_type == 'groupment' ) {
	$title        = $langs->trans("Groupment");
	$title_create = $langs->trans("NewGroupment");
	$title_edit   = $langs->trans("ModifyGroupment");
	$object->picto = 'groupment@digiriskdolibarr';
} else if ( $object->element_type == 'workunit' ) {
	$title         = $langs->trans("WorkUnit");
	$title_create  = $langs->trans("NewWorkUnit");
	$title_edit    = $langs->trans("ModifyWorkUnit");
	$object->picto = 'workunit@digiriskdolibarr';
} else {
	$element_type = GETPOST('element_type', 'alpha');
	if ( $element_type == 'groupment' ){
		$title_create = $langs->trans("NewGroupment");
	}else {
		$title_create  = $langs->trans("NewWorkUnit");
	}
}

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss); ?>

	<div id="cardContent" value="">
<?php
$object  = new DigiriskElement($db);
if ($conf->global->DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT) {
	$objects = $object->fetchAll('',  'ref',  0,  0);
} else {
	$objects = $object->fetchAll('',  '',  0,  0, array('customsql' => 'status > 0'));
}
		$results = recurse_tree(0,0,$objects); ?>

<div id="id-container" class="id-container page-ut-gp-list">
		<div class="organization">
			<div class="digirisk-wrap wpeo-wrap treetable">
					<div class="navigation-container" id="oui">
						<h3 class="title" id="title0">jQuery Nested Sortable Plugin Demo</h3>
						<?php if (!empty($objects) && $objects > 0) : ?>
							<div class="toolbar">
								<div class="toggle-plus tooltip hover" aria-label="<?php echo $langs->trans('UnwrapAll'); ?>"><span class="icon fas fa-plus-square"></span></div>
								<div class="toggle-minus tooltip hover" aria-label="<?php echo $langs->trans('WrapAll'); ?>"><span class="icon fas fa-minus-square"></span></div>
							</div>
						<?php else : ?>
							<div class="society-header">
								<a id="newGroupment" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=groupment&fk_parent=0">
									<div class="wpeo-button button-square-40 button-secondary wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewGroupment'); ?>"><strong><?php echo $mod_groupment->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
								<a id="newWorkunit" href="../digiriskdolibarr/digiriskelement_card.php?action=create&element_type=workunit&fk_parent=0">
									<div class="wpeo-button button-square-40 wpeo-tooltip-event" data-direction="bottom" data-color="light" aria-label="<?php echo $langs->trans('NewWorkUnit'); ?>"><strong><?php echo $mod_workunit->prefix; ?></strong><span class="button-add animated fas fa-plus-circle"></span></div>
								</a>
							</div>
						<?php endif; ?>

						<ul class="workunit-list">
							<?php display_recurse_tree_organization($results) ?>
							<script>
								// Get previous menu to display it
								var MENU = localStorage.menu;
								if (MENU == null || MENU == '') {
									MENU = new Set()
								} else {
									MENU = JSON.parse(MENU);
									MENU = new Set(MENU);
								}

								MENU.forEach((id) =>  {
									jQuery( '#menu'+id).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
									jQuery( '#unit'+id ).addClass( 'toggled' );
								});

								<?php $object->fetch(GETPOST('id')); ?>;
								var idParent = <?php echo json_encode($object->fk_parent) ;?> ;

								jQuery( '#menu'+idParent).removeClass( 'fa-chevron-right').addClass( 'fa-chevron-down' );
								jQuery( '#unit'+idParent ).addClass( 'toggled' );

								// Set active unit active
								jQuery( '.digirisk-wrap .navigation-container .unit.active' ).removeClass( 'active' );

								var params = new window.URLSearchParams(window.location.search);
								var id = params.get('id');
								if (document.URL.match(/digiriskelement/)) {
									jQuery( '#unit'  + id ).addClass( 'active' );
									jQuery( '#unit'  +id  ).closest( '.unit' ).attr( 'value', id );
								};
							</script>
						</ul>
					</div>
				</div>
		</div>
<?php



// End of page
llxFooter();
$db->close();
