<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/riskanalysis/risksign/mod_risksign_standard.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

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

$numberingModuleName = [
	'riskanalysis/' . $risksign->element => $conf->global->DIGIRISKDOLIBARR_RISKSIGN_ADDON,
];

list($refRiskSignMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$hookmanager->initHooks(array('risksigncard', 'digiriskelementview', 'globalcard')); // Note that conf->hooks_modules contains array

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

$permissiontoread   = $user->rights->digiriskdolibarr->risksign->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->risksign->write;
$permissiontodelete = $user->rights->digiriskdolibarr->risksign->delete;

saturne_check_access($permissiontoread);

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

		$fkElement = GETPOST('id');

		$risksign->ref         = $refRiskSignMod->getNextValue($risksign);
		$risksign->category    = $riskSignCategory;
		$risksign->description = $riskSignDescription;
		$risksign->status      = 1;

		$risksign->fk_element = $fkElement ?: 0;

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

		$allrisksigns = $risksign->fetchAll('', '', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));

		foreach ($allrisksigns as $key => $risksigns) {
			$digiriskelementtmp->fetch($risksigns->fk_element);
			$options['import_shared_risksigns'][$risksigns->id] = GETPOST($risksigns->id);

			if ($options['import_shared_risksigns'][$risksigns->id] == 'on') {
				if ($object->id > 0) {
					$object->element = 'digiriskdolibarr_' . $digiriskelementtmp->element;
					$result = $object->add_object_linked('digiriskdolibarr_' . $risksign->element, $risksigns->id);
					if ($result > 0) {
						$risksigns->applied_on = $object->id;
						$risksigns->call_trigger('RISKSIGN_IMPORT', $user);
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
			$risksign->applied_on = $object->id;
			$risksign->call_trigger('RISKSIGN_UNLINK', $user);
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
$helpUrl  = 'FR:Module_Digirisk#Signalisation';

digirisk_header($title, $helpUrl);

print '<div id="cardContent" value="">';

if ($sharedrisksigns) {
	$formconfirm = '';

	// Import shared risks confirmation
	if (($action == 'import_shared_risksigns' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))        // Output when action = clone if jmobile or no js
		|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {                            // Always output when not jmobile nor js

		$digiriskelementtmp = new DigiriskElement($db);

		$allrisksigns = $risksign->fetchAll('ASC', 'fk_element', 0, 0, array('customsql' => 'status > 0 AND entity NOT IN (' . $conf->entity . ') AND fk_element > 0'));
		$deleted_elements = $object->getMultiEntityTrashList();

		$formquestionimportsharedrisksigns = array(
			'text' => '<i class="fas fa-circle-info"></i>' . $langs->trans("ConfirmImportSharedRiskSigns"),
		);

		$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'name' =>'select_all_shared_elements', 'value' => 0);

		$previousDigiriskElement = 0;
		foreach ($allrisksigns as $key => $risksigns) {
			$digiriskelementtmp->fetch($risksigns->fk_element);
			$digiriskelementtmp->element = 'digiriskdolibarr';
			$digiriskelementtmp->fetchObjectLinked($risksigns->id, 'digiriskdolibarr_risksign', $object->id, 'digiriskdolibarr_digiriskelement', 'AND', 1, 'sourcetype', 0);
			$alreadyImported = !empty($digiriskelementtmp->linkedObjectsIds) ? 1 : 0;
			$nameEntity = dolibarr_get_const($db, 'MAIN_INFO_SOCIETE_NOM', $risksigns->entity);

			if (!array_key_exists($digiriskelementtmp->id, $deleted_elements)) {
				$photoRiskSign = '<img class="danger-category-pic hover" src=' . DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $risksigns->getRiskSignCategory($risksigns) . '>';

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

				if ($alreadyImported == 0 && $previousDigiriskElement != $digiriskelementtmp->id) {
					$importValue .= '<input type="checkbox" id="select_all_shared_elements_by_digiriskelement" name="' . $digiriskelementtmp->id . '" value="0">';
				}
				$previousDigiriskElement = $digiriskelementtmp->id;

				if ($alreadyImported > 0) {
					$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'morecss' => 'importsharedelement-digiriskelement-'.$digiriskelementtmp->id, 'name' => $risksigns->id, 'label' => $importValue . '<span class="importsharedrisksigns imported">' . $langs->trans('AlreadyImported') . '</span>', 'value' => 0, 'disabled' => 1);
				} else {
					$formquestionimportsharedrisksigns[] = array('type' => 'checkbox', 'morecss' => 'importsharedelement-digiriskelement-'.$digiriskelementtmp->id, 'name' => $risksigns->id, 'label' => $importValue, 'value' => 0);
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

	saturne_get_fiche_head($object, 'elementRiskSign', $title);
	// Object card
	// ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','', 1, 'ref', 'ref', $morehtmlref, true, $moreParams);

	// Buttons for actions
	print '<div class="tabsAction" >';
	if ($permissiontoadd && !empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS)) {
		print '<span class="butAction" id="actionButtonImportSharedRiskSigns" title="" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=import_shared_risksigns' . '">' . $langs->trans("ImportSharedRiskSigns") . '</span>';
	}
	print '</div>';

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_RISKSIGNS)) {
		$contextpage = 'risksignlist';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risksign/digiriskdolibarr_risksignlist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS)) {
		$contextpage = 'inheritedrisksign';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risksign/digiriskdolibarr_inheritedrisksignlist_view.tpl.php';
	}

	if (!empty($conf->global->DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS)) {
		$contextpage = 'sharedrisksign';
		require_once __DIR__ . '/../../core/tpl/riskanalysis/risksign/digiriskdolibarr_sharedrisksignlist_view.tpl.php';
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
