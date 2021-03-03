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
dol_include_once('/digiriskdolibarr/class/digiriskevaluation.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/mod_risk_standard.php');
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
			else $backtopage = dol_buildpath('/digiriskdolibarr/digiriskelement_risk.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'DIGIRISKDOLIBARR_RISK_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'add') {

		$riskComment 	= GETPOST('riskComment');
		$fk_element		= GETPOST('id');
		$ref 			= GETPOST('ref');
		$cotation 		= GETPOST('cotation');
		$method 		= GETPOST('cotationMethod');
		$category 		= GETPOST('category');
		$risk->description = $db->escape($riskComment);
		$risk->fk_element = $fk_element ? $fk_element : 0;
		$risk->fk_projet = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
		$risk->category = $category;
		$refRisk = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON();
		if ($refRisk) {
			$risk->ref = $refRisk->getNextValue($risk);
		}

		if (!$error)
		{
			$result = $risk->create($user);

			if ($result > 0)
			{
				$task = new Task($db);
				$third_party = new Societe($db);
				$extrafields->fetch_name_optionals_label($task->table_element);

				$taskRef = new $conf->global->PROJECT_TASK_ADDON();

				$task->ref                              = $taskRef->getNextValue($third_party, $task);
				$task->label                            = 'salut';
				$task->fk_project                       = $conf->global->DIGIRISKDOLIBARR_DU_PROJECT;
				$task->date_c                           = dol_now();
				$task->fk_task_parent                   = 0;
				$task->array_options['options_fk_risk'] = $risk->id;

				$task->create($user);

				$formation = GETPOST('formation');
				$protection = GETPOST('protection');
				$occurrence = GETPOST('occurrence');
				$gravite = GETPOST('gravite');
				$exposition = GETPOST('exposition');
				$evaluationComment = GETPOST('evaluationComment');

				$evaluation = new DigiriskEvaluation($db);
				$refCot = new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();
				$evaluation->cotation = $cotation;
				$evaluation->fk_risk = $risk->id;
				$evaluation->status = 1;
				$evaluation->method = $method;
				$evaluation->ref   = $refCot->getNumRef($evaluation);
				$evaluation->formation  	= $formation;
				$evaluation->protection  	= $protection;
				$evaluation->occurrence  	= $occurrence;
				$evaluation->gravite  		= $gravite;
				$evaluation->exposition  	= $exposition;
				$evaluation->comment = $db->escape($evaluationComment);

				$result2 = $evaluation->create($user);

				if ($result2 > 0)
				{
					// Creation OK
					$urltogo = $backtopage ? str_replace('__ID__', $result2, $backtopage) : $backurlforlist;
					$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $evaluation->id, $urltogo); // New method to autoselect project after a New on another form object creation
					header("Location: ".$urltogo);
					exit;
				}
				else
				{
					// Creation KO
					if (!empty($evaluation->errors)) setEventMessages(null, $evaluation->errors, 'errors');
					else  setEventMessages($evaluation->error, null, 'errors');
					$action = 'create';
				}
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

	if ($action == 'saveRisk') {

		$riskID 			= GETPOST('riskID');
		$comment 			= GETPOST('riskComment');
		$cotation 			= GETPOST('cotation');
		$method 			= GETPOST('cotationMethod');
		$evaluationComment 	= GETPOST('evaluationComment');

		$formation 	= GETPOST('formation');
		$protection = GETPOST('protection');
		$occurrence = GETPOST('occurrence');
		$gravite 	= GETPOST('gravite');
		$exposition = GETPOST('exposition');

		$evaluation = new DigiriskEvaluation($db);
		$evaluation->cotation = $cotation;
		$evaluation->fk_risk = $riskID;
		$evaluation->status = 1;
		$evaluation->method = $method;
		$evaluation->comment = $db->escape($evaluationComment);

		$refCot = new $conf->global->DIGIRISKDOLIBARR_EVALUATION_ADDON();
		$evaluation->ref = $refCot->getNextValue($evaluation);

		if ($method == 'digirisk') {
			$evaluation->formation  	= $formation ;
			$evaluation->protection  	= $protection ;
			$evaluation->occurrence  	= $occurrence ;
			$evaluation->gravite  		= $gravite ;
			$evaluation->exposition  	= $exposition ;
		}

		$evaluation->create($user);
	}

	if ($action == "deleteRisk") {

		$id = GETPOST('deletedRiskId');

		$risk = new Risk($db);
		$risk->fetch($id);
		$risk->delete($user);
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
	dol_fiche_head($head, 'elementRisk', $langs->trans("Risk"), -1, $object->picto);

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
	$string = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$json_a = json_decode($string, true); ?>

	<div class="wpeo-grid">
		<h1><?php echo $langs->trans('Risks'). ' - ' . $object->ref . ' ' . $object->label ?></h1>
	</div>

	<?php $numref = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON(); ?>

	<div class="digirisk-wrap wpeo-wrap">
		<div class="main-container">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active">
						<div class="wpeo-table table-flex table-risk main-table">
							<div class="table-row table-header">
								<div class="table-cell table-50"><?php echo $langs->trans('Ref');?></div>
								<div class="table-cell table-50"><?php echo $langs->trans('Risk'); ?></div>
								<div class="table-cell table-150"><?php echo $langs->trans('RiskDescription'); ?></div>
								<div class="table-cell table-50"><?php echo $langs->trans('Photo'); ?></div>
								<div class="table-cell table-200"><?php echo $langs->trans('LastEvaluation'); ?></div>
								<div class="table-cell"><?php echo $langs->trans('Tasks'); ?></div>
								<div class="table-cell table-100 table-end"></div>
							</div>
							<!-- SI le fetchFromParent des risques n'est pas vide alors pour chacun on affiche la vue suivante : -->
							<?php
							$risks = $risk->fetchFromParent($object->id);
							if ($risks !== -1) :
								foreach ($risks as $risk) :
									$evaluation = new DigiriskEvaluation($db);
									$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
									$lastEvaluation = array_shift($lastEvaluation);
									// action = edit
									if ($action == 'editRisk' . $risk->id) : ?>
										<div class="table-row risk-row method-evarisk-simplified" id="risk_row_<?php echo $risk->id ?>">
											<div data-title="Ref." class="table-cell table-50 cell-reference">
												<span>
													<strong>
														<?php
														$riskRef = substr($risk->ref, 1);
														$riskRef = ltrim($riskRef, '0');
														$cotationRef = substr($lastEvaluation->ref, 1);
														$cotationRef = ltrim($cotationRef, '0');
														// au lieu de 'R' mettre l'accronyme des risques qui sera futurement configurable dans digirisk
														echo 'R' . $riskRef . ' - E' . $cotationRef; ?>
													</strong>
												</span>
											</div>

											<div class="table-cell table-150 cell-comment" data-title="Commentaire" class="padding">
												<?php print '<textarea name="riskComment" id="riskComment'.$risk->id.'" class="minwidth150" rows="'.ROWS_2.'">'.$risk->description.'</textarea>'."\n"; ?>
											</div>

											<div class="table-cell table-50 cell-photo" data-title="Photo">
												<div class="photo-container grid wpeo-modal-event tooltip hover">
													<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
													if (count($filearray)) : ?>
														<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$element['object']->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element['object']->element_type).'</span>'; ?>
													<?php else : ?>
													<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
													<div class="action photo default-photo modal-open" value="<?php echo $risk->id ?>">
														<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
													</div>
															<!-- Modal-AddPhoto -->
													<div id="photo_modal<?php echo $risk->id ?>" class="wpeo-modal">
														<div class="modal-container wpeo-modal-event">
															<!-- Modal-Header -->
															<div class="modal-header">
																<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
																<div class="modal-close"><i class="fas fa-times"></i></div>
															</div>
															<!-- Modal-Content -->
															<div class="modal-content" id="#modalContent">
																<div class="formattachnewfile">
																	<div class="centpercent notopnoleftnoright table-fiche-title">
																		<div class="titre">
																			<div class="nobordernopadding valignmiddle col-title">
																				<div class="titre inline-block">Ajouter un nouveau fichier/document</div>
																			</div>
																		</div>

																		<div>
																		<!-- The `multiple` attribute lets users select multiple files. -->
																			<input type="file" id="riskDocument" multiple>
																			<input id="riskDocumentSubmit" type="submit" class="button reposition" name="sendit" value="<?php echo $risk->id ?>">
<!--																			<input class="flat minwidth400 maxwidth200onsmartphone" type="file" name="userfile[]" multiple="" accept="">-->

<!--																				<input type="hidden" name="token" value="$2y$10$5rhdz.l2MZKXbsA2hOneJ.wCIUYwjTBympf/y0g1F4S5gjTZbxJLu">-->
<!--																				<input type="hidden" id="formuserfile_section_dir" name="section_dir" value="">-->
<!--																				<input type="hidden" id="formuserfile_section_id" name="section_id" value="0">-->
<!--																				<input type="hidden" name="sortfield" value="">-->
<!--																				<input type="hidden" name="sortorder" value="">-->
<!--																				<div class="nobordernopadding cenpercent">-->
<!--																					<div class="valignmiddle nowrap">-->
<!--																						<input type="hidden" name="max_file_size" value="2097152">-->
<!--																						<input class="flat minwidth400 maxwidth200onsmartphone" type="file" name="userfile[]" multiple="" accept="">-->
<!--																						<input type="submit" class="button reposition" name="sendit" value="Envoyer fichier">-->
<!--																					</div>-->
<!--																				</div>-->
																		</div>
																	</div>
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

											<div class="table-cell table-50 cell-risk" data-title="Risque">
												<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
													data-tooltip-persist="true"
													data-color="red"
													aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
													<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png' ; ?>" aria-label="" />
												</div>
											</div>

											<div class="table-cell table-50 cell-cotation" data-title="Cot.">
												<div class="cotation-container grid wpeo-modal-event tooltip hover cotation-square" id="cotation_square<?php echo $risk->id ?>">
													<?php if (!empty($lastEvaluation)) :
														//ici pas faire un foreach, accèder juste au premier élément
														$cot = $lastEvaluation;
															if ($action == 'editRisk' . $risk->id) : ?>
																<div data-title="Cot." class="table-cell table-50 cell-cotation">
																	<textarea style="display: none;" name="evaluation_variables"><?php echo ! empty( $risk->data['evaluation']->data ) ? $risk->data['evaluation']->data['variables'] : '{}'; ?></textarea>
																	<div class="wpeo-dropdown dropdown-grid dropdown-padding-0 cotation-container wpeo-tooltip-event"
																		 aria-label="<?php  echo 'Veuillez remplir la cotation'; ?>"
																		 data-color="red"
																		 data-tooltip-persist="true">
																		<input type="hidden" name="cotation" id="cotationInput" value="">
																		<input type="hidden" name="cotationMethod" id="cotationMethod<?php echo $risk->id ?>" value="">
																		<span data-scale="<?php echo $cot->get_evaluation_scale() ?>" class="dropdown-toggle dropdown-add-button cotation cotation<?php echo $risk->id ?>" id="cotationSpan<?php echo $risk->id ?>">
																			<?php echo $cot->cotation; ?>
																		</span>
																		<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0 dropdown-list">
																			<?php
																			$defaultCotation = array(0, 48, 51, 100);
																			$evaluation = new DigiriskEvaluation($db);
																			if ( ! empty( $defaultCotation )) :
																				foreach ( $defaultCotation as $request ) :
																					$evaluation->cotation = $request; ?>
																					<li data-id="<?php echo $risk->id; ?>"
																						data-evaluation-method="standard"
																						data-evaluation-id="<?php echo $request; ?>"
																						data-variable-id="<?php echo 152+$request; ?>"
																						data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																						data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
																						class="dropdown-item cotation"><?php echo $request; ?>
																					</li>
																				<?php endforeach;
																			endif; ?>
																			<li class="action digirisk-evaluation modal-open wpeo-tooltip-event cotation method"
																				data-evaluation-method="digirisk"
																				data-scale="<?php echo $evaluation->get_evaluation_scale() ?>"
																				value="<?php echo $risk->id ?>">
																				<i class="icon fa fa-cog"></i>
																			</li>
																		</ul>
																		<div id="digirisk_evaluation_modal<?php echo $risk->id ?>" class="wpeo-modal wpeo-wrap evaluation-method modal-risk-<?php echo $risk->id ?>" value="<?php echo $risk->id ?>">
																			<?php $evaluation_method = $json_a[0];
																			$evaluation_method_survey = $evaluation_method['option']['variable'];
																			?>
																			<div class="modal-container">
																				<div class="modal-header">
																					<h2><?php echo $langs->trans('CotationEdition') ?></h2>
																				</div>
																				<div class="modal-content" id="#modalContent">
																					<input type="hidden" class="digi-method-evaluation-id" value="<?php echo 0 ; ?>" />
																					<?php
																					$tmp_evaluation_variables[0] = $cot->gravite;
																					$tmp_evaluation_variables[1] = $cot->exposition;
																					$tmp_evaluation_variables[2] = $cot->occurence;
																					$tmp_evaluation_variables[3] = $cot->formation;
																					$tmp_evaluation_variables[4] = $cot->protection;
																					?>
																					<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo json_encode($tmp_evaluation_variables); ?></textarea>
																					<p><i class="fas fa-info-circle"></i> <?php echo 'Cliquez sur les cases du tableau pour remplir votre évaluation'; ?></p>
																					<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
																						<div class="table-row table-header">
																							<div class="table-cell">
																								<span></span>
																							</div>
																							<?php for ( $i = 0; $i < count($evaluation_method_survey); $i++ ) : ?>
																								<div class="table-cell">
																									<span><?php echo $i; ?></span>
																								</div>
																							<?php endfor; ?>
																						</div>
																						<?php $i = 0; ?>
																						<?php foreach($evaluation_method_survey as $critere) : ?>
																							<div class="table-row">
																								<div class="table-cell"><?php echo $critere['name'] ; ?></div>
																								<?php foreach($critere['option']['survey']['request'] as $request) :
																									$name = strtolower($critere['name']); ?>
																									<div class="table-cell can-select <?php echo $name ?><?php if($cot->$name == $request['seuil']) { echo ' active'; }  ?> cell-<?php echo $risk->id?>"
																										 data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
																										 data-type="<?php echo  $name ; ?>"
																										 data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
																										 data-variable-id="<?php echo $i ; ?>"
																										 data-seuil="<?php echo  $request['seuil']; ?>">
																										<?php echo  $request['question'] ; ?>
																									</div>
																								<?php endforeach; $i++; ?>
																							</div>
																						<?php endforeach; ?>
																					</div>
																				</div>
																				<div class="modal-footer">
																					<span data-scale="<?php echo $cot->get_evaluation_scale() ?>" class="cotation cotation-span<?php echo $risk->id ?>">
																						<span id="current_equivalence<?php echo $risk->id ?>"><?php echo $cot->cotation ?></span>
																					</span>
																					<div class="wpeo-button button-grey modal-close">
																						<span><?php echo $langs->trans('CloseTab'); ?></span>
																					</div>
																					<div class="wpeo-button button-main cotation-save <?php if (count($tmp_evaluation_variables) !== 5) echo 'button-disable' ?>" data-id="<?php echo $risk->id ? $risk->id : 0; ?>">
																						<span><?php echo 'Enregistrer la cotation'; ?></span>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																</div>
															<?php endif; ?>
													<?php endif; ?>
												</div>
											</div>

											<div class="table-cell table-150 cell-comment" data-title="Commentaire" class="padding">
												<?php print '<textarea name="evaluationComment" id="evaluationComment'.$risk->id.'" class="minwidth150" rows="'.ROWS_2.'">'.$lastEvaluation->comment.'</textarea>'."\n"; ?>
											</div>

											<div class="table-cell cell-tasks" data-title="Tâches" class="padding">

											</div>

											<div class="table-cell cell-action table-150 table-padding-0 table-end" data-title="Action">
												<div class="action wpeo-button button-square-50 button-green save action-input risk-save" value="<?php echo $risk->id ?>">
													<i class="button-icon fas fa-save"></i>
												</div>
											</div>
										</div>
									<!-- action = view -->
									<?php else : ?>
										<div class="table-row risk-row method-evarisk-simplified" id="risk_row_<?php echo $risk->id ?>">
										<div data-title="Ref." class="table-cell table-50 cell-reference">
											<span>
												<strong>
													<?php
													$riskRef = substr($risk->ref, 1);
													$riskRef = ltrim($riskRef, '0');
													// au lieu de 'R' mettre l'accronyme des risques qui sera futurement configurable dans digirisk
													echo 'R' . $riskRef?>
												</strong>
											</span>
										</div>

										<div class="table-cell table-50 cell-risk" data-title="Risque">
											<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
												data-tooltip-persist="true"
												data-color="red"
												aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
												<img class="danger-category-pic tooltip hover" src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $risk->get_danger_category($risk) . '.png' ; ?>" aria-label="" />
											</div>
										</div>

										<div class="table-cell table-150 cell-comment" data-title="Description" class="padding">
											<?php echo $risk->description; ?>
										</div>

										<div class="table-cell table-50 cell-photo" data-title="Photo">
											<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$risk->element.'/'.$risk->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
												if (count($filearray)) {
													print '<span class="floatleft inline-block valignmiddle divphotoref">'.$risk->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$risk->element, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $risk->element).'</span>';
												} else {
												$nophoto = '/public/theme/common/nophoto.png'; ?>
												<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
											<?php } ?>
										</div>

										<div class="table-cell table-50 cell-cotation" data-title="Cot.">
											<div class="cotation-container grid wpeo-modal-event tooltip hover cotation-square" id="cotation_square<?php echo $risk->id ?>">
											<?php
											$evaluation = new DigiriskEvaluation($db);
											$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
											if (!empty($lastEvaluation)) :
												//ici pas faire un foreach, accèder juste au premier élément
												foreach ($lastEvaluation as $cot) :
													if ($cot->cotation >= 0) : ?>
																<div class="action cotation default-cotation modal-open" data-scale="<?php echo $cot->get_evaluation_scale() ?>" value="<?php echo $risk->id ?>">
																	<span><?php echo $cot->cotation; ?></span>
																</div>

																<!-- Modal-EvaluationsList -->
																<div id="cotation_modal<?php echo $risk->id ?>" class="wpeo-modal" value="<?php echo $risk->id ?>">
																	<div class="modal-container wpeo-modal-event">
																		<!-- Modal-Header -->
																		<div class="modal-header">
																			<h2 class="modal-title"><?php echo $langs->trans('EvaluationsList') ?></h2>
																				<div class="modal-close"><i class="fas fa-times"></i></div>
																		</div>

																		<!-- Modal-Content -->
																		<div class="modal-content" id="#modalContent">
																			<ul class="evaluations-list" style="display: grid">
																				<?php
																				$cotationList = $evaluation->fetchFromParent($risk->id);
																				if (!empty($cotationList)) :
																					foreach ($cotationList as $cotation) : ?>
																						<li class="evaluations-item">
																							<div class="cotation-container grid">
																								<div class="action cotation default-cotation level<?php echo $cotation->get_evaluation_scale(); ?>">
																									<span><?php echo  $cotation->cotation; ?></span>
																								</div>
																							</div>
																							<div>
																								<span class="ref"><?php echo 'E'. $cotation->id ; ?></span>
																								<span class="author">
																									<div class="avatar" style="background-color: #50a1ed;">
																										<?php $user = new User($db); ?>
																										<?php $user->fetch($cotation->fk_user_creat); ?>
																										<span><?php echo $user->firstname[0] . $user->lastname[0]; ?></span>
																									</div>
																								</span>
																								<span class="date"><i class="fas fa-calendar-alt"></i> <?php echo date("d/m/Y", $cotation->date_creation); ?></span>
																							</div>
																							<span class="comment"><?php echo $cotation->comment; ?></span>
																							<hr>
																						</li>
																					<?php endforeach; ?>
																				<?php endif; ?>
																			</ul>
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
												<?php endforeach; ?>
											<?php endif; ?>
											</div>
										</div>

										<div class="table-cell table-150" data-title="evaluationComment" class="padding">
											<?php
											$lastEval = array_shift($lastEvaluation);
											$evaluations = $evaluation->fetchFromParent($risk->id);
											echo date("d/m/Y", $lastEval->date_creation) . ' : ';
											echo $lastEval->comment;
											print '</br>';
											echo 'il y a ' . count($evaluations) . ' evaluations sur ce risque';
											?>
										</div>

										<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
											<span class="cell-tasks-container">
	<!--										VUE SI Y A DES TACHES   -->
												<?php $related_tasks = $risk->get_related_tasks($risk);
												if (!empty($related_tasks) ) :
													foreach ($related_tasks as $related_task) :
														$related_task->fetchTimeSpent($related_task->id); ?>
														<span class="ref"><?php echo $related_task->ref; ?></span>
	<!--													// @todo truc sympa pour l'author.-->
														<span class="author">
															<div class="avatar" style="background-color: #50a1ed;">
																<?php $user = new User($db); ?>
																<?php $user->fetch($related_task->fk_user_creat); ?>
																<span><?php echo $user->firstname[0] . $user->lastname[0]; ?></span>
															</div>
														</span>
														<span class="date"><i class="fas fa-calendar-alt"></i><?php echo date("d/m/Y", $related_task->date_c) ?></span>
														<span class="label"><?php echo $related_task->label; ?></span>
	<!--													print '<a target="_blank" href="/dolibarr/htdocs/projet/tasks/time.php?id=' . $related_task->id . '&withproject=1">' . '&nbsp' .  gmdate('H:i', $related_task->duration_effective ) . '</a>';-->
												<?php endforeach; ?>
												<?php else : ?>
													<span class="name"><?php echo $langs->trans('NoTaskLinked'); ?></span>
												<?php endif; ?>
											</span>
	<!--									VUE SI Y EN A PAS   -->
										</div>

										<div class="table-cell cell-action table-150 table-padding-0 table-end" data-title="Action">
											<div class="action wpeo-gridlayout grid-gap-0 grid-3">
												<!-- Editer un risque -->
												<div class="wpeo-button button-square-50 button-transparent w50 edit action-attribute risk-edit" value="<?php echo $risk->id ?>">
													<i class="button-icon fas fa-pencil-alt"></i>
												</div>

												<!-- Options avancées -->
												<div class="wpeo-button button-square-50 button-transparent w50 move action-attribute">
														<i class="icon fas fa-arrows-alt"></i>
												</div>

												<!-- Supprimer un risque -->
												<div class="wpeo-button button-square-50 button-transparent w50 delete action-attribute risk-delete" value="<?php echo $risk->id ?>">
													<i class="button-icon fas fa-times"></i>
												</div>
											</div>
										</div>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>
							<!-- New item -->
							<?php $risk = new Risk($db); ?>
							<div class="table-row risk-row edit" data-id="<?php echo $risk->data['id'] ; ?>">
								<!-- Les champs obligatoires pour le formulaire -->
								<?php //@todo mettre id GP ?>
								<input type="hidden" name="parent_id" value="<?php echo ''; ?>" />
								<input type="hidden" name="id" value="<?php echo $object->id; ?>" />
								<input type="hidden" name="from_preset" value="<?php echo $risk->data['preset'] ? 1 : 0; ?>" />

								<div data-title="Ref." class="table-cell table-50 cell-reference">
									<input type="hidden" id="new_item_ref" name="new_item_ref" value="<?php echo $numref->getNextValue($risk); ?>">
								</div>

								<div data-title="Risque" data-title="Risque" class="table-cell table-50 cell-risk">
								<?php //@todo mettre id $selected_risk_category ?>
									<input class="input-hidden-danger" type="hidden" name="risk_category_id" value='<?php echo ''; ?>' />
									<div class="wpeo-dropdown dropdown-large category-danger padding wpeo-tooltip-event"
										data-tooltip-persist="true"
										data-color="red"
										data-nonce="<?php echo 'check_predefined_danger'; ?>"
										aria-label="<?php 'Vous devez choisir une catégorie de risque.'?>">
										<div class="dropdown-toggle dropdown-add-button button-cotation">
											<span class="<?php echo !empty($selected_risk_category) ? 'hidden' : '' ; ?>"><i class="fas fa-exclamation-triangle"></i><i class="fas fa-plus-circle icon-add"></i></span>
											<img class="danger-category-pic hidden tooltip hover" src="<?php echo $selected_risk_category?>" aria-label="" />
										</div>
										<ul class="dropdown-content wpeo-grid grid-5">
											<?php
											$dangerCategories = $risk->get_danger_categories();
//											faire une script/fichier qui les récupère et qui leur donne un nom pour le hover et un id pour le stock, dans une variable $risk_category
											if ( ! empty( $dangerCategories ) ) :
												foreach ( $dangerCategories as $dangerCategory ) :
													?>
													<li class="item dropdown-item wpeo-tooltip-event classfortooltip" title="<?php echo $dangerCategory['name']; ?>" data-is-preset="<?php echo ''; ?>" aria-label="<?php echo 'oui'; ?>" data-id="<?php echo $dangerCategory['position'] ?>">
														<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png'?>" class="attachment-thumbail size-thumbnail photo photowithmargin" alt="" loading="lazy" width="48" height="48">
													</li>
													<?php
												endforeach;
											endif;
											?>
										</ul>
									</div>
								</div>

								<div data-title="Description" class="table-cell table-150 cell-comment">
									<?php
									print '<textarea name="riskComment" id="riskComment" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n";
									?>
								</div>

								<div class="table-cell table-50 cell-photo" data-title="Photo">
									<div class="photo-container grid wpeo-modal-event tooltip hover">
										<?php $filearray = dol_dir_list($conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type.'/'.$element['object']->ref.'/', "files", 0, '', '(\.odt|_preview.*\.png)$', 'position_name', 'asc', 1);
										if (count($filearray)) : ?>
											<?php print '<span class="floatleft inline-block valignmiddle divphotoref">'.$element['object']->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$element['object']->element_type, 'small', 1, 0, 0, 0, 50, 0, 0, 0, 0, $element['object']->element_type).'</span>'; ?>
										<?php else : ?>
											<?php $nophoto = '/public/theme/common/nophoto.png'; ?>
											<div class="action photo default-photo modal-open" value="<?php echo $risk->id ?>">
												<span class="floatleft inline-block valignmiddle divphotoref"><img class="photodigiriskdolibarr" alt="No photo" src="<?php echo DOL_URL_ROOT.$nophoto ?>"></span>
											</div>

											<!-- Modal-AddPhoto -->
											<div id="photo_modal<?php echo $risk->id ?>" class="wpeo-modal">
												<div class="modal-container wpeo-modal-event">
													<!-- Modal-Header -->
													<div class="modal-header">
														<h2 class="modal-title"><?php echo $langs->trans('AddPhoto') ?></h2>
														<div class="modal-close"><i class="fas fa-times"></i></div>
													</div>
													<!-- Modal-Content -->
													<div class="modal-content" id="#modalContent">
														<div class="formattachnewfile">
															<div class="centpercent notopnoleftnoright table-fiche-title">
																<div class="titre">
																	<div class="nobordernopadding valignmiddle col-title">
																		<div class="titre inline-block">Ajouter un nouveau fichier/document</div>
																	</div>
																</div>
															</div>
															<form name="formuserfile" id="formuserfile" action="/dolibarr-12.0.3/htdocs/custom/digiriskdolibarr/archives/risk_document.php?id=1" enctype="multipart/form-data" method="POST"><input type="hidden" name="token" value="$2y$10$5rhdz.l2MZKXbsA2hOneJ.wCIUYwjTBympf/y0g1F4S5gjTZbxJLu">
																<input type="hidden" id="formuserfile_section_dir" name="section_dir" value="">
																<input type="hidden" id="formuserfile_section_id" name="section_id" value="0">
																<input type="hidden" name="sortfield" value="">
																<input type="hidden" name="sortorder" value="">
																<div class="nobordernopadding cenpercent">
																	<div class="valignmiddle nowrap">
																		<input type="hidden" name="max_file_size" value="2097152">
																		<input class="flat minwidth400 maxwidth200onsmartphone" type="file" name="userfile[]" multiple="" accept="">
																		<input type="submit" class="button reposition" name="sendit" value="Envoyer fichier">
																	</div>
																</div>
															</form>
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

								<div data-title="Cot." class="table-cell table-50 cell-cotation">
									<textarea style="display: none;" name="evaluation_variables"><?php echo ! empty( $risk->data['evaluation']->data ) ? $risk->data['evaluation']->data['variables'] : '{}'; ?></textarea>
									<div class="wpeo-dropdown dropdown-grid dropdown-padding-0 cotation-container wpeo-tooltip-event"
									 aria-label="<?php  echo 'Veuillez remplir la cotation'; ?>"
									 data-color="red"
									 data-tooltip-persist="true">
									 	<input type="hidden" name="cotation" id="cotationInput" value="">
										<input type="hidden" name="cotationMethod" id="cotationMethod0" value="">
										<span data-scale="<?php echo ! empty( $risk->data['evaluation'] ) ? $risk->data['evaluation']->data['scale'] : 0; ?>" class="dropdown-toggle dropdown-add-button cotation" id="cotationSpan0">
											<i class="fas fa-chart-line"></i>
											<i class="fas fa-plus-circle icon-add"></i>
										</span>
										<ul class="dropdown-content wpeo-gridlayout grid-5 grid-gap-0 dropdown-list">
											<?php
											$defaultCotation = array(0, 48, 51, 100);
											$evaluation = new DigiriskEvaluation($db);
											if ( ! empty( $defaultCotation )) :
												foreach ( $defaultCotation as $request ) :
													$evaluation->cotation = $request; ?>
													<li data-id="<?php echo 0; ?>"
														data-evaluation-method="standard"
														data-evaluation-id="<?php echo $request; ?>"
														data-variable-id="<?php echo 152+$request; ?>"
														data-seuil="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														data-scale="<?php echo  $evaluation->get_evaluation_scale(); ?>"
														class="dropdown-item cotation"><?php echo $request; ?>
													</li>
												<?php endforeach;
											endif; ?>
											<li class="action digirisk-evaluation modal-open wpeo-tooltip-event cotation method"
												data-evaluation-method="digirisk"
												data-scale="<?php echo $evaluation->get_evaluation_scale() ?>"
												value="<?php echo $risk->id ?>">
												<i class="icon fa fa-cog"></i>
											</li>
										</ul>
										<div id="digirisk_evaluation_modal<?php echo $risk->id ?>" class="wpeo-modal wpeo-wrap evaluation-method modal-risk-0" value="<?php echo $risk->id ?>">
										<?php $evaluation_method = $json_a[0];
										$evaluation_method_survey = $evaluation_method['option']['variable'];
										?>
											<div class="modal-container">
												<div class="modal-header">
													<h2><?php echo $langs->trans('CotationEdition') ?></h2>
												</div>
												<div class="modal-content" id="#modalContent">
													<input type="hidden" class="digi-method-evaluation-id" value="<?php echo 0 ; ?>" />
													<textarea style="display: none" name="evaluation_variables" class="tmp_evaluation_variable"><?php echo '{}'; ?></textarea>
													<p><i class="fas fa-info-circle"></i> <?php echo 'Cliquez sur les cases du tableau pour remplir votre évaluation'; ?></p>
													<div class="wpeo-table evaluation-method table-flex table-<?php echo count($evaluation_method_survey) + 1; ?>">
														<div class="table-row table-header">
															<div class="table-cell">
																<span></span>
															</div>
															<?php
															for ( $i = 0; $i < count($evaluation_method_survey); $i++ ) :
																?>
																<div class="table-cell">
																	<span><?php echo $i; ?></span>
																</div>
																<?php
															endfor; ?>
														</div>
														<?php $i = 0; ?>
														<?php foreach($evaluation_method_survey as $critere) {
															$name = strtolower($critere['name']);
															?>
														<div class="table-row">
															<div class="table-cell"><?php echo $critere['name'] ; ?></div>
															<?php foreach($critere['option']['survey']['request'] as $request) {
																?>
															<div class="table-cell can-select cell-<?php echo 0 ?>"
															data-type="<?php echo $name ?>"
															data-id="<?php echo  $risk->id ? $risk->id : 0 ; ?>"
															data-evaluation-id="<?php echo $evaluation_id ? $evaluation_id : 0 ; ?>"
															data-variable-id="<?php echo $i ; ?>"
															data-seuil="<?php echo  $request['seuil']; ?>">
															<?php echo  $request['question'] ; ?>
															</div>
															<?php } $i++;  ?>
														</div>
														<?php } ?>
													</div>
												</div>
												<div class="modal-footer">
													<?php $evaluation->cotation = 0  ?>
													<span data-scale="<?php echo $evaluation->get_evaluation_scale() ?>" class="cotation cotation-span0">
														<span id="current_equivalence<?php echo 0 ?>"></span>
													</span>
													<div class="wpeo-button button-grey modal-close">
														<span><?php echo $langs->trans('CloseTab'); ?></span>
													</div>
													<div class="wpeo-button button-main cotation-save button-disable" data-id="<?php echo $risk->id ? $risk->id : 0; ?>">
														<span><?php echo 'Enregistrer la cotation'; ?></span>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div data-title="evaluationComment" class="table-cell table-100 cell-comment">
									<?php print '<textarea name="evaluationComment" id="evaluationComment" class="minwidth150" rows="'.ROWS_2.'">'.('').'</textarea>'."\n"; ?>
								</div>

								<div class="table-cell cell-tasks" data-title="Tâches" class="padding">
										<span class="cell-tasks-container">

										</span>
									</div>

								<div class="table-cell table-150 table-end cell-action" data-title="action">
									<?php if ( 0) : ?>
										<div class="action">
											<div data-parent="risk-row" data-loader="wpeo-table" class="wpeo-button button-square-50 button-green save action-input" value="<?php echo $risk->id ?>"><i class="button-icon fas fa-save"></i></div>
										</div>
									<?php else : ?>
										<div class="action">
											<div class="risk-create wpeo-button button-square-50 button-event add action-input button-progress">
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
