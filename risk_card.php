<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       risk_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view risk
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


// Load Dolibarr environment
use digi\Risk_Category_Class;use digi\Risk_Evaluation_Class;$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/digiriskdolibarr/class/risk.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'riskcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new DigiriskElement($db);
$risk = new Risk($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('riskcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


//$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
//$permissiontoadd = $user->rights->digiriskdolibarr->risk->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
//$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
//$permissionnote = $user->rights->digiriskdolibarr->risk->write; // Used by the include of actions_setnotes.inc.php
//$permissiondellink = $user->rights->digiriskdolibarr->risk->write; // Used by the include of actions_dellink.inc.php
//$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

$permissiontoread = 1;
$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = 1 || 1;
$permissionnote = 1; // Used by the include of actions_setnotes.inc.php
$permissiondellink = 1; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->digiriskdolibarr->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'digiriskdolibarr', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

//if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = dol_buildpath('/digiriskdolibarr/risk_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/digiriskdolibarr/risk_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'DIGIRISKDOLIBARR_RISK_MODIFY'; // Name of trigger action code to execute when we modify record
	if ($action == 'add') {
		$riskComment = GETPOST('riskComment');
		$fk_element = GETPOST('id');

		$risk->description = $riskComment ? $riskComment : '';
		$risk->fk_element = $fk_element ? $fk_element : 0;
		if (!$error)
		{
			$result = $risk->create($user);
			if ($result > 0)
			{
				// Creation OK
				$urltogo = $backtopage ? str_replace('__ID__', $result, $backtopage) : $backurlforlist;
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $risk->id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: ".$urltogo);
				exit;
			}
			else
			{
				// Creation KO
				if (!empty($risk->errors)) setEventMessages(null, $risk->errors, 'errors');
				else  setEventMessages($risk->error, null, 'errors');
				$action = 'create';
			}
		}
	}
	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	//include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'RISK_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'RISK_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_RISK_TO';
	$trackid = 'risk'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Risk");
$help_url = '';
$morejs = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

$object->digiriskHeader('', $title, $help_url, '', '', '', $morejs);
?>
	<div id="cardContent" value="">

<?php
// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to create
//if ($action == 'create')
//{
//	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Risk")), '', 'object_'.$object->picto);
//
//	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
//	print '<input type="hidden" name="token" value="'.newToken().'">';
//	print '<input type="hidden" name="action" value="add">';
//	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
//	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
//
//	dol_fiche_head(array(), '');
//
//	// Set some default values
//	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';
//
//	print '<table class="border centpercent tableforfieldcreate">'."\n";
//
//	// Common attributes
//	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';
//
//	// Other attributes
//	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
//
//	print '</table>'."\n";
//
//	dol_fiche_end();
//
//	print '<div class="center">';
//	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
//	print '&nbsp; ';
//	print '<input type="'.($backtopage ? "submit" : "button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
//	print '</div>';
//
//	print '</form>';
//
//	//dol_set_focus('input[name="ref"]');
//}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("Risk"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";


?>

<?php


	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);
	dol_fiche_head($head, 'elementRisk', $langs->trans("Risk"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRisk'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/digiriskdolibarr/risk_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	$width = 80; $cssclass = 'photoref';
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type).'</div>';
	$object->digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter wpeo-wrap">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";
	$risk->fetch(1)
	?>
	<div class="wpeo-grid">
		<h1><?php echo $langs->trans('Risks'). ' - ' . $object->ref . ' ' . $object->label ?></h1>

	</div>
	<div class="digirisk-wrap wpeo-wrap">
		<div class="main-container">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active">
						<div class="wpeo-table table-flex table-risk">
							<div class="table-row table-header">
								<div class="table-cell table-75"><?php echo 'Ref'; ?>.</div>
								<div class="table-cell table-50"><?php echo 'Risque'; ?></div>
								<div class="table-cell table-50"><?php echo 'Cot'; ?></div>
								<div class="table-cell table-50"><?php echo 'Photo'; ?></div>
								<div class="table-cell table-300"><?php echo 'Description'; ?></div>
								<div class="table-cell"><?php echo 'Tâches'; ?></div>
								<div class="table-cell table-100 table-end"></div>
							</div>
							<!--
							SI le fetchFromParent des risques n'est pas vide alors pour chacun on affiche la vue suivante :
							-->
							<?php
							$risks = $risk->fetchFromParent($object->id);
							if (!empty($risks)) {
								foreach ($risks as $risk) {
							?>
							<div class="table-row risk-row method-evarisk-simplified">
								<div data-title="Ref." class="table-cell table-75 cell-reference">
									<!-- La popup pour les actions correctives -->
							<!--		--><?php //\eoxia\View_Util::exec( 'digirisk', 'corrective_task', 'popup', array() ); ?>

									<span>
										<strong>
											<?php echo $risk->ref; ?>
										</strong>
									</span>
								</div>
								<div class="table-cell table-50 cell-risk" data-title="Risque">
									<?php echo 'picto' ?>
								</div>
								<div class="table-cell table-50 cell-cotation" data-title="Cot.">
							<!--		--><?php //Risk_Evaluation_Class::g()->display( $risk ); ?>
									<?php echo '48' ?>

								</div>
								<div class="table-cell table-50 cell-photo" data-title="Photo">
							<!--		--><?php //echo do_shortcode( '[wpeo_upload id="' . $risk->data['id'] . '" model_name="' . $risk->get_class() . '" single="false" field_name="image" title="' . $risk->data['unique_identifier'] . '" ]' ); ?>
									<?php echo 'photo' ?>

									<!-- Trigger/Open The Modal -->
									<button id="myBtn">Open Modal</button>

									<!-- The Modal -->
									<div id="myModal" class="wpeo-modal">

										<!-- Modal content -->
										<div class="modal-container wpeo-modal-event">
											<div class="modal-content">
												<span class="close">&times;</span>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
												<p>Some text in the Modal..</p>
											</div>
										</div>
									</div>

									<script>
										// Get the modal
										var modal = document.getElementById("myModal");
										// Get the button that opens the modal
										var btn = document.getElementById("myBtn");

										// Get the <span> element that closes the modal
										var span = document.getElementsByClassName("close")[0];

										// When the user clicks the button, open the modal
										btn.onclick = function() {
											console.log(modal.style)
											$(modal).addClass('modal-active')
											modal.style.display = "block";
										}

										// When the user clicks on <span> (x), close the modal
										span.onclick = function() {
											modal.style.display = "none";
										}

										// When the user clicks anywhere outside of the modal, close it
										window.onclick = function(event) {
											if (event.target == modal) {
												modal.style.display = "none";
											}
										}
									</script>


								</div>

								<div class="table-cell table-300 cell-comment" data-title="Commentaire" class="padding">
							<!--        --><?php //do_shortcode( '[digi_comment id="' . $risk->data['id'] . '" namespace="digi" type="risk_evaluation_comment" display="view"]' ); ?>
										<?php echo $risk->description ?>

								</div>
								<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
									<!--        --><?php //do_shortcode( '[digi_comment id="' . $risk->data['id'] . '" namespace="digi" type="risk_evaluation_comment" display="view"]' ); ?>
									<?php echo 'les tâches liées' ?>

								</div>
								<div class="table-cell cell-action table-150 table-padding-0 table-end" data-title="Action">
									<div class="action wpeo-gridlayout grid-gap-0 grid-3">
										<!-- Editer un risque -->
										<div class="wpeo-button button-square-50 button-transparent w50 edit action-attribute">
											<i class="button-icon fas fa-pencil-alt"></i>
										</div>

										<!-- Options avancées -->
										<div class="wpeo-button button-square-50 button-transparent w50 move action-attribute">
												<i class="icon fas fa-arrows-alt"></i>
										</div>

										<!-- Supprimer un risque -->
										<div class="wpeo-button button-square-50 button-transparent w50 delete action-attribute">
											<i class="button-icon fas fa-times"></i>
										</div>
									</div>
								</div>
							</div>
									<?php }
								}
							?>


					<?php

					print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="add">';
					?>
							<div class="table-row risk-row edit" data-id="<?php echo $risk->data['id'] ; ?>">
								<!-- Les champs obligatoires pour le formulaire -->
								<input type="hidden" name="parent_id" value="<?php echo $society_id; ?>" />
								<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
								<input type="hidden" name="from_preset" value="<?php echo $risk->data['preset'] ? 1 : 0; ?>" />
								<?php  ?>
									<div data-title="Ref." class="table-cell table-75 cell-reference">
										<?php if ( $risk->data['preset'] ) : ?>
											-
										<?php
										else :
											// getNextValue
												?>
<!--												<span><strong>--><?php //echo $risk->data['unique_identifier'] . ' - ' . $risk->data['evaluation']->data['unique_identifier'] ); ?><!--</span></strong>-->
											<?php
										endif;
										?>
									</div>
									<div data-title="Risque" data-title="Risque" class="table-cell table-50 cell-risk">
<!--										--><?php
//										if ( isset( $can_edit_risk_category ) && $can_edit_risk_category ) :
//											do_shortcode( '[digi_dropdown_categories_risk id="' . $risk->data['id'] . '" type="risk" display="edit" category_risk_id="' . $risk->data['risk_category']->data['id'] . '" preset="' . ( ( $risk->data['preset'] ) ? '1' : '0' ) . '"]' );
//										else :
//											do_shortcode( '[digi_dropdown_categories_risk id="' . $risk->data['id'] . '" type="risk" display="' . ( ( 0 !== $risk->data['id'] && ! $risk->data['preset'] ) ? 'view' : 'edit' ) . '" category_risk_id="' . $risk->data['risk_category']->data['id'] . '" preset="' . ( ( $risk->data['preset'] ) ? '1' : '0' ) . '"]' );
//										endif;
//										?>
										selectbox picto cat
									</div>
									<div data-title="Cot." class="table-cell table-50 cell-cotation">
<!--										--><?php //do_shortcode( '[digi_dropdown_evaluation_method risk_id=' . $risk->data['id'] . ']' ); ?>
										cot
									</div>
									<div data-title="Photo" class="table-cell table-50 cell-photo">
<!--										--><?php //echo do_shortcode( '[wpeo_upload id="' . ( ( $risk->data['preset'] ) ? 0 : $risk->data['id'] ) . '" model_name="' . $risk->get_class() . '" single="false" field_name="image" title="' . $risk->data['unique_identifier'] . ' - ' . $risk->data['evaluation']->data['unique_identifier'] . '" ]' ); ?>
									photo
									</div>
									<div data-title="Description" class="table-cell table-100 cell-comment">
<!--										--><?php //do_shortcode( '[digi_comment id="' . $risk->data['id'] . '" namespace="digi" type="risk_evaluation_comment" display="edit" add_button="' . ( ( $risk->data['preset'] ) ? '0' : '1' ) . '"]' ); ?>

										<?php
												print '<textarea name="riskComment" id="riskComment" class="minwidth300" rows="'.ROWS_2.'">'.('').'</textarea></td></tr>'."\n";
									?></div>
									<div class="table-cell table-150 table-end cell-action" data-title="action">
										<?php if ( 0) : ?>
											<div class="action">
												<div data-parent="risk-row" data-loader="wpeo-table" class="wpeo-button button-square-50 button-green save action-input"><i class="button-icon fas fa-save"></i></div>
											</div>
										<?php else : ?>
											<div class="action">
												<?php if ( -1 != $risk->data['risk_category']->data['id'] && -1 != $risk->data['evaluation']->data['scale'] ) : ?>
<!--												<a href="risk_card.php?action=add" name="add" type="submit">-->
<!---->
<!--													<div class="wpeo-button button-square-50 add action-input button-progress">-->
<!--														<i class="button-icon fas fa-plus"></i></div>-->
<!--												</a>-->

														<?php
													print '&nbsp; ';
													print '<button type="submit" name="add" style="color: #3495f0; background-color: transparent; width:30%; border:none; margin-right:20%;">';
													print '<div class="wpeo-button button-square-50 button-event add action-input button-progress">
															<i class="button-icon fas fa-plus"></i>
															</button>';
													print '</div></div>';
													?>
												<?php else : ?>
													<div data-namespace="digirisk"
														 data-module="risk"
														 data-before-method="beforeSaveRisk"
														 data-loader="wpeo-table"
														 data-parent="risk-row"
														 class="wpeo-button button-square-50 button-disable button-event add action-input button-progress">
														<i class="button-icon fas fa-plus"></i></div>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
				<?php print '</table>'."\n";

				print '</form>'; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
</div>

	<?php
	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
			// Send
			if (empty($user->socid)) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>'."\n";
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes">'.$langs->trans("SetToDraft").'</a>';
				}
			}

			// Modify
			if ($permissiontoadd)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit">'.$langs->trans("Modify").'</a>'."\n";
			}
			else
			{
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT)
			{
				if ($permissiontoadd)
				{
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0))
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes">'.$langs->trans("Validate").'</a>';
					}
					else
					{
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Clone
			if ($permissiontoadd)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&object=risk">'.$langs->trans("ToClone").'</a>'."\n";
			}

			/*
			if ($permissiontoadd)
			{
				if ($object->status == $object::STATUS_ENABLED)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=disable">'.$langs->trans("Disable").'</a>'."\n";
				}
				else
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=enable">'.$langs->trans("Enable").'</a>'."\n";
				}
			}
			if ($permissiontoadd)
			{
				if ($object->status == $object::STATUS_VALIDATED)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=close">'.$langs->trans("Cancel").'</a>'."\n";
				}
				else
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen">'.$langs->trans("Re-Open").'</a>'."\n";
				}
			}
			*/

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd))
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>'."\n";
			}
			else
			{
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}
//
//	if ($action != 'presend')
//	{
//		print '<div class="fichecenter"><div class="fichehalfleft">';
//		print '<a name="builddoc"></a>'; // ancre
//
//		$includedocgeneration = 1;
//
//		// Documents
//		if ($includedocgeneration) {
//			$objref = dol_sanitizeFileName($object->ref);
//			$relativepath = $objref . '/' . $objref . '.pdf';
//			$filedir = $conf->digiriskdolibarr->dir_output.'/'.$object->element.'/'.$objref;
//			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
//			$genallowed = $user->rights->digiriskdolibarr->risk->read;	// If you can read, you can build the PDF to read content
//			$delallowed = $user->rights->digiriskdolibarr->risk->write;	// If you can create/edit, you can remove a file on card
//			print $formfile->showdocuments('digiriskdolibarr:Risk', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
//		}
//
//		// Show links to link elements
//		$linktoelem = $form->showLinkToObjectBlock($object, null, array('risk'));
//		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
//
//
//		print '</div><div class="fichehalfright"><div class="ficheaddleft">';
//
//		$MAXEVENT = 10;
//
//		$morehtmlright = '<a href="'.dol_buildpath('/digiriskdolibarr/risk_agenda.php', 1).'?id='.$object->id.'">';
//		$morehtmlright .= $langs->trans("SeeAll");
//		$morehtmlright .= '</a>';
//
//		// List of actions on element
//		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
//		$formactions = new FormActions($db);
//		$somethingshown = $formactions->showactions($object, $object->element.'@digiriskdolibarr', (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);
//
//		print '</div></div></div>';
//	}
//
//	//Select mail models is same action as presend
//	if (GETPOST('modelselected')) $action = 'presend';
//
//	// Presend form
//	$modelmail = 'risk';
//	$defaulttopic = 'InformationMessage';
//	$diroutput = $conf->digiriskdolibarr->dir_output;
//	$trackid = 'risk'.$object->id;
//
//	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
//	print '</div>';

}

// End of page
llxFooter();
$db->close();
