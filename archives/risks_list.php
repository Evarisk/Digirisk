<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       risk_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for risk
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       		  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

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
// load digiriskdolibarr libraries
require_once __DIR__.'/class/risk.class.php';

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'risklist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new Risk($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('risklist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) $sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
if (!$sortorder) $sortorder = "ASC";

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["ef.".$key] = array(
				'label'=>$extrafields->attributes[$object->table_element]['label'][$key],
				'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1),
				'position'=>$extrafields->attributes[$object->table_element]['pos'][$key],
				'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]),
				'langfile'=>$extrafields->attributes[$object->table_element]['langfile'][$key]
			);
		}
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$permissiontoread = $user->rights->digiriskdolibarr->risk->read;
$permissiontoadd = $user->rights->digiriskdolibarr->risk->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risk->delete;

// Security check
if (empty($conf->digiriskdolibarr->enabled)) accessforbidden('Module not enabled');
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'digiriskdolibarr', $id, '');
//if (!$permissiontoread) accessforbidden();



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		foreach ($object->fields as $key => $val)
		{
			$search[$key] = '';
		}
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Risk';
	$objectlabel = 'Risk';
	$uploaddir = $conf->digiriskdolibarr->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$risk = new Risk($db);
$elementParent = new DigiriskElement($db);
$title = $langs->trans("Risk");
$help_url = '';
$morejs = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

llxHeader('', $title, $help_url, '', '', '', $morejs);
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
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/digiriskdolibarr/risk_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	$width = 80; $cssclass = 'photoref';
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.$object->digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type).'</div>';

	print '<div class="fichecenter wpeo-wrap">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";
	$string = file_get_contents(DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/default.json');
	$json_a = json_decode($string, true); ?>

	<div class="wpeo-grid">
		<h1><?php echo $langs->trans('RiskList')?></h1>
	</div>

	<?php $numref = new $conf->global->DIGIRISKDOLIBARR_RISK_ADDON(); ?>

	<div class="digirisk-wrap wpeo-wrap">
		<div class="main-container">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active">
						<div class="wpeo-table table-flex table-risk main-table">
							<div class="table-row table-header">
								<div class="table-cell table-100"><?php echo $langs->trans('Parent');?></div>
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
							$risks = $risk->fetchAll();

							if ($risks !== -1) :
								foreach ($risks as $risk) {
									$evaluation = new DigiriskEvaluation($db);
									$lastEvaluation = $evaluation->fetchFromParent($risk->id,1);
									$lastEvaluation = array_shift($lastEvaluation);
									$risk->lastEvaluation = $lastEvaluation;
								}
								usort($risks,function($first,$second){
									return $first->lastEvaluation > $second->lastEvaluation;
								});

								foreach ($risks as $risk) :


									// action = edit
									if ($action == 'editRisk' . $risk->id) : ?>
										<div class="table-row risk-row method-evarisk-simplified" id="risk_row_<?php echo $risk->id ?>">

											<div data-title="Parent." class="table-cell table-100 cell-parent">
												<?php echo $risk->fk_element ?>
											</div>

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
											<div data-title="Parent." class="table-cell table-100 cell-parent">
												<?php
												$elementParent->fetch($risk->fk_element);
												echo $elementParent->ref ?>
											</div>

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
																										<span><?php echo $user->fisrtname[0] . $user->lastname[0]; ?></span>
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
																<span><?php echo $user->fisrtname[0] . $user->lastname[0]; ?></span>
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
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	dol_fiche_end();

// End of page
llxFooter();
$db->close();
