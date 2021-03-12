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
 *   	\file       digiriskelement_risk.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view risk
 */


// Load Dolibarr environment
$res=0;
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
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/mod_task_simple.php';
dol_include_once('/digiriskdolibarr/class/risk.class.php');
dol_include_once('/digiriskdolibarr/class/digirisksignalisation.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_digirisksignalisation_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_evaluation_standard.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'signalisationcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new DigiriskElement($db);
$signalisation = new DigiriskSignalisation($db);
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
			else $backtopage = dol_buildpath('/digiriskdolibarr/digiriskelement_signalisation.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'DIGIRISKDOLIBARR_SIGNALISATION_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'add') {

		$signalisationComment 	= GETPOST('signalisationComment');
		$fk_element		= GETPOST('id');
		$ref 			= GETPOST('ref');
		$category 		= GETPOST('category');
		$photo 			= GETPOST('photo');

		$signalisation->description 	= $db->escape($signalisationComment);
		$signalisation->fk_element 		= $fk_element ? $fk_element : 0;
		$signalisation->category 		= $category;
		$refSignalisationMod 					= new $conf->global->DIGIRISKDOLIBARR_SIGNALISATION_ADDON();
		$refSignalisation 						= $refSignalisationMod->getNextValue($signalisation);
		if (!is_dir(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation')) {
			mkdir(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation');
		}
		mkdir(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' . $refSignalisation);
		copy(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo,DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' .  $refSignalisation . '/' . $photo);

		$signalisation->photo			= $photo;

		if ($refSignalisation) {
			$signalisation->ref = $refSignalisation;
		}

		if (!$error)
		{
			$result = $signalisation->create($user);
		}
		else
		{
				// Creation KO
			if (!empty($signalisation->errors)) setEventMessages(null, $signalisation->errors, 'errors');
			else  setEventMessages($signalisation->error, null, 'errors');
			$action = 'create';
		}
	}

	if ($action == 'saveSignalisation') {

		$signalisationID = GETPOST('signalisationID');
		$comment 		 = GETPOST('signalisationComment');
		$photo 			 = GETPOST('photo');

		$signalisation = new DigiriskSignalisation($db);
		$signalisation->fetch($signalisationID);

		$signalisation->description 	= $db->escape($comment);
		$signalisation->photo 			= $photo;

		$signalisation->update($user);
		$files = dol_dir_list(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' . $signalisation->ref . '/');
		foreach ($files as $file) {
			unlink(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' . $signalisation->ref . '/' . $file['name']);
		}

		dol_delete_dir(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' . $signalisation->ref );
		mkdir(DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' . $signalisation->ref);
		copy(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias/' . $photo,DOL_DATA_ROOT . '/digiriskdolibarr/signalisation/' .  $signalisation->ref . '/' . $photo);

	}

	if ($action == "deleteSignalisation") {

		$id = GETPOST('deletedSignalisationId');

		$signalisation = new DigiriskSignalisation($db);
		$signalisation->fetch($id);
		$signalisation->delete($user);
	}

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
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'SIGNALISATION_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'SIGNALISATION_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SIGNALISATION_TO';
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
//print '<script type="text/javascript" language="javascript">
//jQuery(document).ready(function() {
//	function init_myfunc()
//	{
//		jQuery("#myid").removeAttr(\'disabled\');
//		jQuery("#myid").attr(\'disabled\',\'disabled\');
//	}
//	init_myfunc();
//	jQuery("#mybutton").click(function() {
//		init_myfunc();
//	});
//});
//</script>';

// VIEW
if ($object->id > 0) {
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);
	dol_fiche_head($head, 'elementSignalisation', $langs->trans("Risk"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/digiriskdolibarr/signalisation_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	$width = 80; $cssclass = 'photoref';
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type).'</div>';
	$object->digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter wpeo-wrap">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";
	$string = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$json_a = json_decode($string, true); ?>

	<div class="wpeo-grid">
		<h1><?php echo $langs->trans('Signalisations'). ' - ' . $object->ref . ' ' . $object->label ?></h1>
	</div>
<!--// @todo num ref-->
	<?php $numref = new $conf->global->DIGIRISKDOLIBARR_SIGNALISATION_ADDON(); ?>

	<div class="digirisk-wrap wpeo-wrap">
		<div class="main-container">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active">
						<div class="wpeo-table table-flex table-recommendation main-table">
							<div class="table-row table-header">
								<div class="table-cell table-50"><?php echo $langs->trans('Ref');?></div>
								<div class="table-cell table-150"><?php echo $langs->trans('Signalisation'); ?></div>
								<div class="table-cell table-50"><?php echo $langs->trans('Photo'); ?></div>
								<div class="table-cell table-150"><?php echo $langs->trans('Description'); ?></div>
								<div class="table-cell table-100 table-end"></div>
							</div>
							<!-- SI le fetchFromParent des risques n'est pas vide alors pour chacun on affiche la vue suivante : -->
							<?php
							$signalisations = $signalisation->fetchFromParent($object->id);
							if ($signalisations !== -1) :
								foreach ($signalisations as $signalisation) :

									// action = edit
									if ($action == 'editSignalisation' . $signalisation->id) : ?>
										<div class="table-row recommendation-row" id="signalisation_row_<?php echo $signalisation->id ?>">
											<div data-title="Ref." class="table-cell table-50 cell-reference">
												<span>
													<strong>
														<?php
														$signalisationRef = substr($signalisation->ref, 1);
														$signalisationRef = ltrim($signalisationRef, '0');

														// au lieu de 'R' mettre l'accronyme des risques qui sera futurement configurable dans digirisk
														echo 'S' . $signalisationRef ?>
													</strong>
												</span>
											</div>

											<div class="table-cell table-150 cell-signalisation" data-title="Signalisation">
												<div class="wpeo-dropdown dropdown-large category-signalisation padding wpeo-tooltip-event"
													data-tooltip-persist="true"
													data-color="red"
													aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
													<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $signalisation->get_signalisation_category($signalisation) ; ?>" aria-label="" />
												</div>
											</div>

											<div class="table-cell table-50 cell-photo" data-title="Photo">
												<div class="photo-container grid wpeo-modal-event tooltip hover" value="<?php echo $signalisation->id ?>">
													<?php
													$relativepath = 'digiriskdolibarr/medias';
													$modulepart = 'ecm';
													$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
													$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$signalisation->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);

													if (count($filearray)) : ?>

														<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$signalisation->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/', 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0).'</span>'; ?>

													<?php else : ?>

														<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
														<div class="action photo default-photo modal-open" value="<?php echo $signalisation->id ?>">
															<span class="floatleft inline-block valignmiddle divphotoref photo-edit<?php echo $signalisation->id ?>">
																<input type="hidden" value="<?php echo $path ?>" id="pathToPhoto<?php echo $signalisation->id ?>">
																<img class="photo maxwidth50"  src="<?php echo $path . $signalisation->photo ?>">
															</span>
														</div>

														<!-- Modal-AddPhoto -->
														<div id="photo_modal<?php echo $signalisation->id ?>" class="wpeo-modal">
															<div class="modal-container wpeo-modal-event">
																<!-- Modal-Header -->
																<div class="modal-header">
																	<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
																	<div class="modal-close"><i class="fas fa-times"></i></div>
																</div>
																<!-- Modal-Content -->
																<div class="modal-content" id="#modalContent">
																	Ajoutez de nouveaux fichiers dans 'digiriskdolibarr/medias'
																	<div class="action">
																		<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
																			<div class="wpeo-button button-square-50 button-event add action-input button-progress">
																				<i class="button-icon fas fa-plus"></i>
																			</div>
																		</a>
																	</div>

																	<input type="hidden" id="photoLinked<?php echo $signalisation->id ?>" value="<?php echo $signalisation->photo ?>">
																	<div class="wpeo-table table-row">
																		<?php
																		$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
																		$relativepath = 'digiriskdolibarr/medias';
																		$modulepart = 'ecm';
																		$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
																		$i = 0;

																		if ( !empty($files)) {
																			foreach ($files as $file) {

																				print '<div class="table-cell center clickable-photo clickable-photo'. $i .'" value="'. $i .'">';
																				if (image_format_supported($file['name']) >= 0)
																				{
																					$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity;
																					?>
																						<input type="hidden" id="filename<?php echo $signalisation->id ?>" value="<?php echo $file['name'] ?>">
																						<img class="photo photo<?php echo $i ?> maxwidth200" src="<?php echo $fullpath; ?>">
																					<?php
																				}
																				else {
																					print '&nbsp;';
																				}
																				$i++;

																				print '</div>';
																			}
																		}

																		// 	 $formfile->list_of_documents($files, '', 'digiriskdolibarr');
																		?>
																	</div>

																</div>
																<!-- Modal-Footer -->
																<div class="modal-footer">
																	<div class="wpeo-button button-grey modal-close">
																		<span><?php echo $langs->trans('CloseModal'); ?></span>
																	</div>
																</div>
															</div>
														</div>
													<?php endif; ?>
												</div>
											</div>

											<div class="table-cell table-150 cell-comment" data-title="signalisationComment" class="padding">
												<?php print '<textarea name="evaluationComment" id="signalisationComment'.$signalisation->id.'" class="minwidth150" rows="'.ROWS_2.'">'.$signalisation->description.'</textarea>'."\n"; ?>
											</div>


											<div class="table-cell cell-action table-150 table-padding-0 table-end" data-title="Action">
												<div class="action wpeo-button button-square-50 button-green save action-input signalisation-save" value="<?php echo $signalisation->id ?>">
													<i class="button-icon fas fa-save"></i>
												</div>
											</div>
										</div>
									<!-- action = view -->
									<?php else : ?>
										<div class="table-row recommendation-row method-evarisk-simplified" id="signalisation_row_<?php echo $signalisation->id ?>">
										<div data-title="Ref." class="table-cell table-50 cell-reference">
											<span>
												<strong>
													<?php
													$signalisationRef = substr($signalisation->ref, 1);
													$signalisationRef = ltrim($signalisationRef, '0');
													// au lieu de 'R' mettre l'accronyme des risques qui sera futurement configurable dans digirisk
													echo 'S' . $signalisationRef?>
												</strong>
											</span>
										</div>

										<div class="table-cell table-150 cell-signalisation" data-title="Signalisation">
											<div class="wpeo-dropdown dropdown-large category-signalisation padding wpeo-tooltip-event"
												data-tooltip-persist="true"
												data-color="red"
												aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">

												<img class="signalisation-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $signalisation->get_signalisation_category($signalisation) ; ?>" aria-label="" />
											</div>
										</div>


										<div class="table-cell table-50 cell-photo" data-title="Photo">
											<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/signalisation/'.$signalisation->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
											if (count($filearray)) {
													print '<span class="floatleft inline-block valignmiddle divphotoref">'.$signalisation->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/signalisation', 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, 'signalisation').'</span>';
												} else {
												$nophoto = '/public/theme/common/nophoto.png'; ?>
												<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
											<?php } ?>
										</div>


										<div class="table-cell table-150" data-title="signalisationComment" class="padding">
											<span><i class="fas fa-calendar-alt"></i> <?php echo date("d/m/Y", $signalisation->date_creation) . ' : '; ?></span>
											<?php
											echo $signalisation->description;
											?>
										</div>

										<div class="table-cell cell-action table-150 table-padding-0 table-end" data-title="Action">
											<div class="action wpeo-gridlayout grid-gap-0 grid-3">
												<!-- Editer un risque -->
												<div class="wpeo-button button-square-50 button-transparent w50 edit action-attribute signalisation-edit" value="<?php echo $signalisation->id ?>">
													<i class="button-icon fas fa-pencil-alt"></i>
												</div>

												<!-- Options avancées -->
												<div class="wpeo-button button-square-50 button-transparent w50 move action-attribute">
														<i class="icon fas fa-arrows-alt"></i>
												</div>

												<!-- Supprimer un risque -->
												<div class="wpeo-button button-square-50 button-transparent w50 delete action-attribute signalisation-delete" value="<?php echo $signalisation->id ?>">
													<i class="button-icon fas fa-times"></i>
												</div>
											</div>
										</div>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>
							<!-- New item -->
							<?php $signalisation = new DigiriskSignalisation($db); ?>
							<div class="table-row signalisation-row edit" data-id="<?php echo $signalisation->data['id'] ; ?>">
								<!-- Les champs obligatoires pour le formulaire -->
								<?php //@todo mettre id GP ?>
								<input type="hidden" name="parent_id" value="<?php echo ''; ?>" />
								<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
								<input type="hidden" name="from_preset" value="<?php echo $signalisation->data['preset'] ? 1 : 0; ?>" />

								<div data-title="Ref." class="table-cell table-50 cell-reference">
									<input type="hidden" id="new_item_ref" name="new_item_ref" value="<?php echo $numref->getNextValue($signalisation); ?>">
										<span class="ref"><?php echo '-' ?></span>
								</div>

								<div data-title="Risque" data-title="Signalisation" class="table-cell table-150 cell-signalisation">
								<?php //@todo mettre id $selected_risk_category ?>
									<input class="input-hidden-danger" type="hidden" name="signalisation_category_id" value='<?php echo ''; ?>' />
									<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
										data-tooltip-persist="true"
										data-color="red"
										data-nonce="<?php echo 'check_predefined_danger'; ?>"
										aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
										<div class="dropdown-toggle dropdown-add-button button-cotation">
											<span class="<?php echo !empty($selected_signalisation_category) ? 'hidden' : '' ; ?>"><i class="fas fa-exclamation-triangle"></i><i class="fas fa-plus-circle icon-add"></i></span>
											<img class="danger-category-pic hidden tooltip hover" src="<?php echo $selected_signalisation_category?>" aria-label="" />
										</div>
										<ul class="dropdown-content wpeo-grid grid-5">
											<?php
											$signalisationCategories = $signalisation->get_signalisation_categories();
//											faire une script/fichier qui les récupère et qui leur donne un nom pour le hover et un id pour le stock, dans une variable $signalisation_category
											if ( ! empty( $signalisationCategories ) ) :
												foreach ( $signalisationCategories as $signalisationCategory ) :
													?>
													<li class="item dropdown-item wpeo-tooltip-event classfortooltip signalisation-pic" title="<?php echo $signalisationCategory['name']; ?>" data-is-preset="<?php echo ''; ?>" aria-label="<?php echo 'oui'; ?>" data-id="<?php echo $signalisationCategory['position'] ?>">
														<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $signalisationCategory['name_thumbnail']?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
													</li>
													<?php
												endforeach;
											endif;
											?>
										</ul>
									</div>
								</div>


								<div class="table-cell table-50 cell-photo" data-title="Photo">
									<div class="photo-container grid wpeo-modal-event tooltip hover">
										<?php
										$relativepath = 'digiriskdolibarr/medias';
										$modulepart = 'ecm';
										$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath) . '/';
										$filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
										if (count($filearray)) : ?>
											<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$element['object']->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element['object']->element_type).'</span>'; ?>
										<?php else : ?>
											<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
											<div class="action photo default-photo modal-open" value="<?php echo $signalisation->id ?>">
												<span class="floatleft inline-block valignmiddle divphotoref photo-edit0">
													<input type="hidden" value="<?php echo $path ?>" id="pathToPhoto0">
													<img class="photo maxwidth50"  src="<?php echo DOL_URL_ROOT.'/public/theme/common/nophoto.png' ?>">
												</span>
											</div>

											<!-- Modal-AddPhoto -->
											<div id="photo_modal<?php echo $signalisation->id ?>" class="wpeo-modal">
												<div class="modal-container wpeo-modal-event">
													<!-- Modal-Header -->
													<div class="modal-header">
														<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
														<div class="modal-close"><i class="fas fa-times"></i></div>
													</div>
													<!-- Modal-Content -->
													<div class="modal-content" id="#modalContent">
														Ajoutez de nouveaux fichiers dans 'digiriskdolibarr/medias'
														<div class="action">
															<a href="<?php echo '../../ecm/index.php' ?>" target="_blank">
																<div class="wpeo-button button-square-50 button-event add action-input button-progress">
																	<i class="button-icon fas fa-plus"></i>
																</div>
															</a>
														</div>

														<input type="hidden" id="photoLinked0" value="">
														<div class="wpeo-table table-row">
															<?php
															$files =  dol_dir_list(DOL_DATA_ROOT . '/ecm/digiriskdolibarr/medias');
															$relativepath = 'digiriskdolibarr/medias';
															$modulepart = 'ecm';
															$path = '/dolibarr/htdocs/document.php?modulepart=' . $modulepart  . '&attachment=0&file=' . str_replace('/', '%2F', $relativepath);
															$i = 0;

															if ( !empty($files)) {
																foreach ($files as $file) {

																	print '<div class="table-cell center clickable-photo clickable-photo'. $i .'" value="'. $i .'">';
																	if (image_format_supported($file['name']) >= 0)
																	{
																		$fullpath = $path . '/' . $file['relativename'] . '&entity=' . $conf->entity;
																		?>
																			<input type="hidden" id="filename0" value="<?php echo $file['name'] ?>">
																			<img class="photo photo<?php echo $i ?> maxwidth200" src="<?php echo $fullpath; ?>">
																		<?php
																	}
																	else {
																		print '&nbsp;';
																	}
																	$i++;

																	print '</div>';
																}
															}

															// 	 $formfile->list_of_documents($files, '', 'digiriskdolibarr');
															?>
														</div>

													</div>
													<!-- Modal-Footer -->
													<div class="modal-footer">
														<div class="wpeo-button button-grey modal-close">
															<span><?php echo $langs->trans('CloseModal'); ?></span>
														</div>
													</div>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>

								<div data-title="Description" class="table-cell table-150 cell-comment">
									<?php
									print '<textarea name="signalisation" id="signalisationComment" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n";
									?>
								</div>


								<div class="table-cell table-150 table-end cell-action" data-title="action">
									<?php if ( 0) : ?>
										<div class="action">
											<div data-parent="signalisation-row" data-loader="wpeo-table" class="wpeo-button button-square-50 button-green save action-input" value="<?php echo $signalisation->id ?>"><i class="button-icon fas fa-save"></i></div>
										</div>
									<?php else : ?>
										<div class="action">
											<div class="signalisation-create wpeo-button button-square-50 button-event add action-input button-progress button-disable">
												<i class="button-icon fas fa-plus"></i>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	dol_fiche_end();

}
// End of page
llxFooter();
$db->close();
