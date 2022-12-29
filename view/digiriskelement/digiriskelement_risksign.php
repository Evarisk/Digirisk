<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       view/digiriskelement/digiriskelement_risksign.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view Risk Sign
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res       = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res    = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once './../../class/digiriskelement.class.php';
require_once './../../class/digiriskstandard.class.php';
require_once './../../class/riskanalysis/risksign.class.php';
require_once './../../core/modules/digiriskdolibarr/riskanalysis/risksign/mod_risksign_standard.php';
require_once './../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id              = GETPOST('id', 'int');
$action          = GETPOST('action', 'aZ09');
$massaction      = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm         = GETPOST('confirm', 'alpha');
$cancel          = GETPOST('cancel', 'aZ09');
$contextpage     = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'risksigncard'; // To manage different context of search
$backtopage      = GETPOST('backtopage', 'alpha');
$toselect        = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit           = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield       = GETPOST('sortfield', 'alpha');
$sortorder       = GETPOST('sortorder', 'alpha');
$sharedrisksigns = GETPOST('sharedrisksigns', 'int') ? GETPOST('sharedrisksigns', 'int') : $conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS;
$page            = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page            = is_numeric($page) ? $page : 0;
$page            = $page == -1 ? 0 : $page;

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskelement  = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$risksign         = new RiskSign($db);
$extrafields      = new ExtraFields($db);
$project          = new Project($db);

$hookmanager->initHooks(array('risksigncard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($risksign->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($risksign->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if ( ! $sortfield) $sortfield = "t." . key($risksign->fields); // Set here default search field. By default 1st field in definition.
if ( ! $sortorder) $sortorder = "ASC";

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search     = array();
foreach ($risksign->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') $search[$key] = GETPOST('search_' . $key, 'alpha');
}

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($risksign->fields as $key => $val) {
	if ($val['searchall']) $fieldstosearchall['t.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($risksign->fields as $key => $val) {
	if ($val['label'] == 'Entity' || $val['label'] == 'ParentElement') {
		$val['visible'] = 0;
	}
	if ( ! empty($val['visible'])) $arrayfields['t.' . $key] = array('label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']);
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$risksign->fields = dol_sort_array($risksign->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for digiriskelement_risksign
require_once __DIR__ . '/../../core/tpl/digirisk_security_checks.php';

$permissiontoread   = $user->rights->digiriskdolibarr->risksign->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->risksign->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risksign->delete;

// Security check - Protection if external user
if ( ! $permissiontoread) accessforbidden();

$refRiskSignMod = new $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON();

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $risksign, $action); // Note that $action and $risk may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($risksign->fields as $key => $val) {
			$search[$key] = '';
		}
		$toselect             = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_risksign.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');

	if ( ! $error && $action == 'add' && $permissiontoadd) {
		$data = json_decode(file_get_contents('php://input'), true);

		$riskSignCategory    = $data['riskSignCategory'];
		$riskSignDescription = $data['riskSignDescription'];

		$fk_element = GETPOST('id');

		$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
		$risksign->category    = $riskSignCategory;
		$risksign->description = $riskSignDescription;
		$risksign->status      = 1;

		$risksign->fk_element = $fk_element ? $fk_element : 0;

		if ( ! $error) {
			$result = $risksign->create($user);
			if ($result > 0) {
				// Creation risksign OK
				$urltogo = str_replace('__ID__', $result, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation risksign KO
				if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
				else setEventMessages($risksign->error, null, 'errors');
			}
		}
	}

	if ( ! $error && $action == 'saveRiskSign' && $permissiontoadd) {
		$data = json_decode(file_get_contents('php://input'), true);

		$riskSignID          = $data['riskSignID'];
		$riskSignCategory    = $data['riskSignCategory'];
		$riskSignDescription = $data['riskSignDescription'];

		$risksign->fetch($riskSignID);

		$risksign->category    = $riskSignCategory;
		$risksign->description = $riskSignDescription;

		$result = $risksign->update($user);

		if ($result > 0) {
			// Update risksign OK
			$urltogo = str_replace('__ID__', $result, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Update risksign KO
			if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
			else setEventMessages($risksign->error, null, 'errors');
		}
	}

	if ( ! $error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if ( ! empty($toselect)) {
			foreach ($toselect as $toselectedid) {
				$risksign->fetch($toselectedid);
				$result = $risksign->delete($user);

				if ($result > 0) {
					setEventMessages($langs->trans('DeleteRiskSignMessage') . ' ' . $risksign->ref, array());
				} else {
					// Delete risksign KO
					$error++;
					if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
					else setEventMessages($risksign->error, null, 'errors');
				}
			}

			if ($error > 0) {
				// Delete risksign KO
				if ( ! empty($risksign->errors)) setEventMessages(null, $risksign->errors, 'errors');
				else setEventMessages($risksign->error, null, 'errors');
			} else {
				// Delete risksign OK
				$urltogo = str_replace('__ID__', $id, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
		}
	}

	// Action import shared risk signs
	if ($action == 'confirm_import_shared_risksigns' && $confirm == 'yes') {

		$digiriskelementtmp = new DigiriskElement($db);

//	$AllSharingsRisks = $conf->mc->sharings['risk'];
//
//	foreach ($AllSharingsRisks as $Allsharingsrisk) {
//		$filter .= $Allsharingsrisk . ',';
//	}
//
//	$filter = rtrim($filter, ',');

		$allrisksigns = $risksign->fetchAll('', '', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));

		foreach ($allrisksigns as $key => $risksigns) {
			$digiriskelementtmp->fetch($risksigns->fk_element);
			$options['import_shared_risksigns'][$risksigns->id] = GETPOST($risksigns->id);

			if ($options['import_shared_risksigns'][$risksigns->id] == 'on') {
				if ($object->id > 0) {
					$object->element = 'digiriskdolibarr_' . $digiriskelementtmp->element;
					$result = $object->add_object_linked('digiriskdolibarr_' . $risksign->element, $risksigns->id);
					if ($result > 0) {
						continue;
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$action = '';
					}
				}
			}
		}

		$urltogo = str_replace('__ID__', $object->id, $backtopage);
		$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
		header("Location: " . $urltogo);
		exit;
	}

	if (! $error && $action == 'unlinkSharedRiskSign' && $permissiontodelete) {
		$data = json_decode(file_get_contents('php://input'), true);

		$risksign_id = $data['risksignID'];

		$risksign        = new RiskSign($db);
		$digiriskelement = new DigiriskElement($db);

		$risksign->fetch($risksign_id);
		$digiriskelement->fetch($risksign->fk_element);

		$result = deleteObjectLinkedDigirisk($digiriskelement, $risksign->id, 'digiriskdolibarr_' . $risksign->element, $object->id, 'digiriskdolibarr_' . $digiriskelement->element);

		if ($result > 0) {
			// Unlink shared risk sign OK
			$urltogo = str_replace('__ID__', $object->id, $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $urltogo);
			exit;
		} else {
			// Unlink shared risk sign KO
			if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("RiskSigns");
$help_url = 'FR:Module_DigiriskDolibarr#Signalisation';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader($title, $help_url, $morejs, $morecss);

print '<div id="cardContent" value="">';

if ($sharedrisksigns) {
	$formconfirm = '';

	// Import shared risks confirmation
	if (($action == 'import_shared_risksigns' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))        // Output when action = clone if jmobile or no js
		|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {                            // Always output when not jmobile nor js

		$digiriskelementtmp = new DigiriskElement($db);

//		$AllSharingsRisks = $conf->mc->sharings['risk'];
//
//		foreach ($AllSharingsRisks as $Allsharingsrisk) {
//			$filter .= $Allsharingsrisk . ',';
//		}
//
//		$filter = rtrim($filter, ',');

		$allrisksigns = $risksign->fetchAll('ASC', 'fk_element', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));
		$deleted_elements = $object->getMultiEntityTrashList();

		$formquestionimportsharedrisksigns = array(
			'text' => '<i class="fas fa-circle-info"></i>' . $langs->trans("ConfirmImportSharedRiskSigns"),
		);

		$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'name' =>'select_all_shared_risksigns', 'value' => 0);

		foreach ($allrisksigns as $key => $risksigns) {
			$digiriskelementtmp->fetch($risksigns->fk_element);
			$digiriskelementtmp->element = 'digiriskdolibarr';
			$digiriskelementtmp->fetchObjectLinked($risksigns->id, 'digiriskdolibarr_risksign', $object->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
			$alreadyImported = !empty($digiriskelementtmp->linkedObjectsIds) ? 1 : 0;
			$nameEntity = dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $risksigns->entity);

			if (!array_key_exists($digiriskelementtmp->id, $deleted_elements)) {
				$photoRiskSign = '<img class="danger-category-pic hover" src=' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksigns->get_risksign_category($risksigns) . '>';

				$importValue = '<div class="importsharedrisksign"><span class="importsharedrisksign-ref">' . 'S' . $risksigns->entity . '</span>';
				$importValue .= '<span>' . dol_trunc($nameEntity, 32) . '</span>';
				$importValue .= '</div>';

				$importValue .= '<div class="importsharedrisksign"><span class="importsharedrisksign-ref">' . $digiriskelementtmp->ref . '</span>';
				$importValue .= '<span>' . dol_trunc($digiriskelementtmp->label, 32) . '</span>';
				$importValue .= '</div>';

				$importValue .= '<div class="importsharedrisksign">';
				$importValue .= $photoRiskSign;
				$importValue .= '<span class="importsharedrisksign-ref">' . $risksigns->ref . '</span>';
				$importValue .= '<span>' . dol_trunc($risksigns->description, 32) . '</span>';
				$importValue .= '</div>';

				if ($alreadyImported > 0) {
					$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'name' => $risksigns->id, 'label' => $importValue . '<span class="importsharedrisksigns imported">' . $langs->trans('AlreadyImported') . '</span>', 'value' => 0, 'disabled' => 1);
				} else {
					$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'name' => $risksigns->id, 'label' => $importValue, 'value' => 0);
				}
			}

		}
		$formconfirm .= digiriskformconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ImportSharedRiskSigns'), '', 'confirm_import_shared_risksigns', $formquestionimportsharedrisksigns, 'yes', 'actionButtonImportSharedRiskSigns', 800, 800);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;
}

if ($object->id > 0) {
	$res = $object->fetch_optionals();

	$head = digiriskelementPrepareHead($object);
	print dol_get_fiche_head($head, 'elementRiskSign', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	$height = 80;
	$width = 80;
	dol_strlen($object->label) ? $morehtmlref = ' - ' . $object->label : '';
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $parent_element->getNomUrl(1, 'blank', 1);
	} else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $digiriskstandard->getNomUrl(1, 'blank', 1);
	}
	$morehtmlref .= '</div>';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">' . digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type, 'small', 5, 0, 0, 0, $height, $width, 0, 0, 0, $object->element_type, $object) . '</div>';
	$linkback = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';
	digirisk_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	// Buttons for actions
	print '<div class="tabsAction" >';
	if ($permissiontoadd && !empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS)) {
		print '<span class="butAction" id="actionButtonImportSharedRiskSigns" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=import_shared_risksigns' . '">' . $langs->trans("ImportSharedRiskSigns") . '</span>';
	}
	print '</div>';

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_RISKSIGNS)) {
		$contextpage = 'risksignlist';
		require_once './../../core/tpl/riskanalysis/risksign/digiriskdolibarr_risksignlist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS)) {
		$contextpage = 'inheritedrisksign';
		require_once './../../core/tpl/riskanalysis/risksign/digiriskdolibarr_inheritedrisksignlist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS)) {
		$contextpage = 'sharedrisksign';
		require_once './../../core/tpl/riskanalysis/risksign/digiriskdolibarr_sharedrisksignlist_view.tpl.php';
	}
}

print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

?>

	<script>
		$('.ulrisksignlist_selectedfields').attr('style','z-index:1050')
		$('.ulinherited_risksignlist_selectedfields').attr('style','z-index:1050')
		$('.ulshared_risksignlist_selectedfields').attr('style','z-index:1050')
	</script>

<?php
// End of page
llxFooter();
$db->close();
