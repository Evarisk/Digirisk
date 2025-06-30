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
 *   	\file       view/digiriskelement/digiriskelement_evaluator.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view evaluator
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../class/evaluator.class.php';
require_once __DIR__ . '/../../core/modules/digiriskdolibarr/digiriskelement/evaluator/mod_evaluator_standard.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id          = GETPOST('id', 'int');
$action      = GETPOST('action', 'aZ09');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'evaluatorcard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$toselect    = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'alpha');
$sortorder   = GETPOST('sortorder', 'alpha');
$fromid      = GETPOST('fromid', 'int'); //element id
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;

// Initialize technical objects
$object           = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$evaluator        = new Evaluator($db);
$extrafields      = new ExtraFields($db);
$usertmp          = new User($db);
$project          = new Project($db);

$numberingModuleName = [
	'digiriskelement/' . $evaluator->element => $conf->global->DIGIRISKDOLIBARR_EVALUATOR_ADDON,
];

list($refEvaluatorMod) = saturne_require_objects_mod($numberingModuleName, $moduleNameLowerCase);

$hookmanager->initHooks(array('evaluatorcard', 'digiriskelementview', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($evaluator->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($evaluator->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if ( ! $sortfield) $sortfield = "t." . key($evaluator->fields); // Set here default search field. By default 1st field in definition.
if ( ! $sortorder) $sortorder = "ASC";

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml') ? trim(GETPOST('search_all', 'alphanohtml')) : trim(GETPOST('sall', 'alphanohtml'));
$search     = array();
foreach ($evaluator->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') $search[$key] = GETPOST('search_' . $key, 'alpha');
}

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($evaluator->fields as $key => $val) {
	if ($val['searchall']) $fieldstosearchall['t.' . $key] = $val['label'];
}

// Definition of fields for list
$arrayfields = array();
foreach ($evaluator->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if ($val['label'] == 'ParentElement') {
		if (empty($fromid)) {
			$val['visible'] = 0;
		}
	}
	if ( ! empty($val['visible'])) $arrayfields['t.' . $key] = array('label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']);
}

// Load Digirisk_element object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$evaluator->fields = dol_sort_array($evaluator->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

if ($object->id == 0) {
	$userObject = new User($db);
	$prehead = 'user_prepare_head';
	$userObject->fetch($fromid, '', '', 1);
	$userObject->getrights();
	$head = $prehead($userObject);
}

//Permission for digiriskelement_evaluator
$permissiontoread   = $user->rights->digiriskdolibarr->evaluator->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->evaluator->write;
$permissiontodelete = $user->rights->digiriskdolibarr->evaluator->delete;

// Security check
saturne_check_access($permissiontoread, $object);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $evaluator, $action); // Note that $action and $evaluator may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($evaluator->fields as $key => $val) {
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

	$backtopage = dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php', 1) . (empty($fromid) ? '?id=' . ($id > 0 ? $id : '__ID__') : '?fromid=' . ($fromid > 0 ? $fromid : '__ID__'));

	if ( ! $error && $action == 'add' && $permissiontoadd) {
		$data = json_decode(file_get_contents('php://input'), true);

		$duration    = $data['duration'];
		$date        = $data['date'];
		$job         = $data['job'];
		$evaluatorID = $data['evaluatorID'];

		$usertmp->fetch($evaluatorID);

		$evaluator->ref             = $evaluator->getNextNumRef();
		$evaluator->ref_ext         = $evaluator->ref;
		$evaluator->assignment_date = strtotime(preg_replace('/\//', '-', $date));
		$evaluator->duration        = $duration;
		$evaluator->job             = (!empty($job) ? $job : $usertmp->job);
		$evaluator->fk_user         = $evaluatorID;
		$evaluator->fk_parent       = $object->id;
		$evaluator->status          = 1;

		if ( ! $error) {
			$result = $evaluator->create($user);
			if ($result > 0) {
				// Creation evaluator OK
				$urltogo = str_replace('__ID__', $id, $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			} else {
				// Creation evaluator KO
				if ( ! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
			}
		}
	}

	if ( ! $error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if ( ! empty($toselect)) {
			foreach ($toselect as $toselectedid) {
				$evaluator->fetch($toselectedid);

				$result = $evaluator->delete($user);

				if ($result > 0) {
					setEventMessages($langs->trans('DeleteEvaluatorMessage') . ' ' . $evaluator->ref, array());
				} else {
					// Delete evaluator KO
					$error++;
					if ( ! empty($evaluator->errors)) setEventMessages(null, $evaluator->errors, 'errors');
					else setEventMessages($evaluator->error, null, 'errors');
				}
			}

			if ($error > 0) {
				// Delete evaluator KO
				if ( ! empty($evaluator->errors)) setEventMessages(null, $evaluator->errors, 'errors');
				else setEventMessages($evaluator->error, null, 'errors');
			} else {
				// Delete evaluator OK
				$urltogo = str_replace('__ID__', (empty($fromid) ? $id : $fromid), $backtopage);
				$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (empty($fromid) ? $id : $fromid), $urltogo); // New method to autoselect project after a New on another form object creation
				header("Location: " . $urltogo);
				exit;
			}
		}
	}

	if ( ! $error && $action == 'getEvaluatorJob' && $permissiontoadd) {
		$data = json_decode(file_get_contents('php://input'), true);
		$userID = $data['userID'];
		$usertmp->fetch($userID);
	}

	if ( ! $error && $action == 'create_user' && $permissiontoadd) {

		$data = json_decode(file_get_contents('php://input'), true);

		$user = new User($db);

		$user->lastname  = $data['lastname'];
		$user->firstname = $data['firstname'];
		$user->login     = $user->firstname . $user->lastname;
		$user->email     = preg_replace('/\s+/', '', $data['email']);
		$user->job       = $data['job'] ?? '';

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $user);
		if ($ret < 0) {
			$error++;
		}

		// Set entity property
		$user->entity = getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE') ? 1 : $conf->entity;

		$db->begin();

		$actionNewUserId = $user->create($user);
		if ($actionNewUserId > 0) {
			$user->SetInGroup($data['groupid'], $conf->entity);

			$actionMessage = $langs->trans("UserCreated");
			$db->commit();
		} else {
			$langs->load("errors");
			$db->rollback();
			$actionMessage = $langs->trans($object->error);
		}
	}
	$action = '';
}

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("Evaluators");
$helpUrl  = 'FR:Module_Digirisk#.C3.89valuateurs';

if ($fromid > 0) {
	saturne_header(0,'', $title, $helpUrl);
} else {
	digirisk_header($title, $helpUrl);
}

print '<div id="cardContent" value="">';

if (!empty($actionNewUserId)) {
	print '<input type="hidden" id="actionNewUserId" value="' . $actionNewUserId . '">';
	print '<input type="hidden" id="actionMessage" value="' . $actionMessage . '">';
}

if ($object->id > 0 || $fromid > 0) {
	$res = $object->fetch_optionals();

	if (empty($fromid)) {
		saturne_get_fiche_head($object, 'elementEvaluator', $title);
	} else {
		print dol_get_fiche_head($head, 'participation', $langs->trans('User'), -1, "user");
	}

	// Object card
	// ------------------------------------------------------------
	if (empty($fromid)) {
        list($morehtmlref, $moreParams) = $object->getBannerTabContent();

        saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);
	} else {
		$linkback = '<a href="' . DOL_URL_ROOT . '/user/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		dol_banner_tab($userObject, 'fromid', $linkback, $user->rights->user->user->lire || $user->admin);
	}

	if ($fromid) {
		print '<div class="underbanner clearboth"></div>';
	}

	print '<div class="fichecenter evaluatorlist wpeo-wrap">';
	print '<form method="POST" id="searchFormEvaluator" action="' . $_SERVER["PHP_SELF"] . (empty($fromid) ? '?id=' . $object->id : '?fromid=' . $fromid) . '" name="evaluator_form"">' . "\n";
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

	// NOTICES FOR ACTIONS
	?>
	<!--	EVALUATOR-->
	<div class="messageSuccessEvaluatorCreate notice hidden">
		<div class="wpeo-notice notice-success evaluator-create-success-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('EvaluatorWellCreated') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheEvaluator') . ' ' . $langs->trans('HasBeenCreatedM') ?></div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<div class="messageErrorEvaluatorCreate notice hidden">
		<div class="wpeo-notice notice-warning evaluator-create-error-notice">
			<div class="notice-content">
				<div class="notice-title"><?php echo $langs->trans('EvaluatorNotCreated') ?></div>
				<div class="notice-subtitle"><?php echo $langs->trans('TheEvaluator') . ' ' . $langs->trans('HasNotBeenCreatedM') ?></div>
			</div>
			<div class="notice-close"><i class="fas fa-times"></i></div>
		</div>
	</div>
	<?php
	// Build and execute select
	// --------------------------------------------------------------------
	$sql = 'SELECT ';
	foreach ($evaluator->fields as $key => $val) {
		$sql .= 't.' . $key . ', ';
	}
	// Add fields from extrafields
	if ( ! empty($extrafields->attributes[$evaluator->table_element]['label'])) {
		foreach ($extrafields->attributes[$evaluator->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$evaluator->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
	}
	// Add fields from hooks
	$parameters                                                                                                                                              = array();
	$reshook                                                                                                                                                 = $hookmanager->executeHooks('printFieldListSelect', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	$sql                                                                                                                                                    .= preg_replace('/^,/', '', $hookmanager->resPrint);
	$sql                                                                                                                                                     = preg_replace('/,\s*$/', '', $sql);
	$sql                                                                                                                                                    .= " FROM " . MAIN_DB_PREFIX . $evaluator->table_element . " as t";
	if (is_array($extrafields->attributes[$evaluator->table_element]['label']) && count($extrafields->attributes[$evaluator->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $evaluator->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
	if ($evaluator->ismultientitymanaged == 1) $sql                                                                                                         .= " WHERE t.entity IN (" . getEntity($evaluator->element) . ")";
	else $sql                                                                                                                                               .= " WHERE 1 = 1";
    $sql                                                                                                                                                    .= " AND status > " . $evaluator::STATUS_DELETED;
	if (empty($fromid)) {
		$sql .= " AND fk_parent = " . $id;
	} else {
		$sql .= " AND fk_user = " . $fromid;
	}

	foreach ($search as $key => $val) {
		if ($key == 'status' && $search[$key] == -1) continue;
		$mode_search = (($evaluator->isInt($evaluator->fields[$key]) || $evaluator->isFloat($evaluator->fields[$key])) ? 1 : 0);
		if (strpos($evaluator->fields[$key]['type'], 'integer:') === 0) {
			if ($search[$key] == '-1') $search[$key] = '';
			$mode_search                             = 2;
		}
		if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
	}

	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters       = array();
	$reshook          = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	$sql             .= $hookmanager->resPrint;
	$sql             .= $db->order($sortfield, $sortorder);
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	// Count total nb of records
	$nbtotalofrecords = '';
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$resql = $db->query($sql);

		$nbtotalofrecords = $db->num_rows($resql);
		if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
			$page   = 0;
			$offset = 0;
		}
	}
	// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
	if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
		$num = $nbtotalofrecords;
	} else {
		$resql = $db->query($sql);
		if ( ! $resql) {
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);
	}
	// Direct jump if only one record found
	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && ! $page) {
		$obj = $db->fetch_object($resql);
		$id  = $obj->rowid;
		header("Location: " . dol_buildpath('/digiriskdolibarr/view/digiriskelement/digiriskelement_evaluator.php', 1) . '?id=' . $id);
		exit;
	}

	if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters, $evaluation); // Note that $action and $evaluation may have been modified by hook
	$sql       .= $hookmanager->resPrint;

	$resql = $db->query($sql);
	if ( ! $resql) {
		dol_print_error($db);
		exit;
	} else {
		$num = $db->num_rows($resql);
	}

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param                                                                      = '';
	if ( ! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
	$param                                                                     .= '&id=' . $id;
	if ($limit > 0 && $limit != $conf->liste_limit) $param                     .= '&limit=' . urlencode($limit);
	foreach ($search as $key => $val) {
		if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_' . $key . '[]=' . urlencode($skey);
		else $param                                                                                  .= '&search_' . $key . '=' . urlencode($search[$key]);
	}
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions                                       = array();
	if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>' . $langs->trans("Delete");
    $massactionbutton = $form->selectMassAction('', $arrayofmassactions); ?>

	<!-- BUTTON MODAL EVALUATOR ADD -->
	<?php if ($permissiontoadd) {
		$newcardbutton = '<div class="evaluator-add wpeo-button button-blue modal-open" value="' . $object->id . '">';
		$newcardbutton .= '<i class="fas fa-user-check button-icon"></i><i class="fas fa-plus-circle button-add animated"></i>';
		$newcardbutton .= '	<input type="hidden" class="modal-options" data-modal-to-open="evaluator_add'. $object->id .'" data-from-id="'. $object->id .'" data-from-type="digiriskelement" data-from-subtype="photo" data-from-subdir="photos"/>';
		$newcardbutton .= '</div>';
	} else {
		$newcardbutton = '<div class="wpeo-button button-square-40 button-grey" value="' . $object->id . '"><i class="fas fa-user-check button-icon wpeo-tooltip-event" aria-label="' . $langs->trans('PermissionDenied') . '"></i><i class="fas fa-plus-circle button-add animated"></i></div>';
	} ?>

	<!-- EVALUATOR ADD MODAL-->
	<div class="evaluator-add-modal" value="<?php echo $object->id ?>">
		<div class="wpeo-modal modal-evaluator-0 <?php echo (GETPOST('modalactive') ? 'modal-active' : ''); ?>" id="evaluator_add<?php echo $object->id ?>">
			<div class="modal-container wpeo-modal-event">
				<!-- Modal-Header -->
				<div class="modal-header">
					<h2 class="modal-title"><?php echo $langs->trans('AddEvaluatorTitle') . ' ' . $refEvaluatorMod->getNextValue($evaluator);  ?></h2>
					<div class="modal-close"><i class="fas fa-times"></i></div>
				</div>
				<!-- Modal-ADD Evaluator Content-->
				<div class="modal-content" id="#modalContent">
					<div class="evaluator-content wpeo-gridlayout grid-2">
						<div class="evaluator-user">
							<span class="title"><?php echo $langs->trans('SelectUser'); ?><required>*</required></span>
							<input type="hidden" class="user-selected" value="<?php echo $user->id ?>">
							<?php
							$userlist = $form->select_dolusers(GETPOST('responsible_socid'), '', 0, null, 0, '', '', 0, 0, 0, '(u.statut:=:1)', 0, '', 'minwidth300', 0, 1);
							print '<table><tr>';
							print '<td>';
							print $form->selectarray('fk_user_employer', $userlist, (GETPOST('userid') ? GETPOST('userid') : $usertmp->id), $langs->trans('SelectUser'), null, null, null, "40%", 0, 0, '', 'minwidth300', 1);
							print '<span class="fa fa-plus-circle valignmiddle paddingleft" id="openCreateUser" title="' . $langs->trans("AddUser") . '"></span>';
							print '</td></tr></table>';
							?>
						</div>
						<div class="evaluator-assignment wpeo-gridlayout grid-3">
							<div class="evaluator-duration">
								<span class="title"><?php echo $langs->trans('Duration'); ?></span>
								<span class="time"><?php print '<input type="number" class="duration" name="evaluatorDuration" rows="' . ROWS_2 . '" value="' . $conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION . '"> min'; ?></span>
							</div>
							<div class="evaluator-date">
								<span class="title"><?php echo $langs->trans('Date'); ?></span>
								<?php print $form->selectDate('', 'EvaluatorDate', 0, 0, 0, 'evaluator_form', 1, 1,'','','','','',1,'','','tzuserrel'); ?>
							</div>
							<div class="evaluator-job">
								<span class="title"><?php echo $langs->trans('PostOrFunction'); ?></span>
								<span class=""><input type="text" class="evaluatorJob" name="evaluatorJob" value="<?php echo (!empty(GETPOST('job')) ? GETPOST('job') : $usertmp->job) ?>"></span>
							</div>
						</div>
					</div>

					<div class="user-create wpeo-gridlayout grid-6" style="display: none;">
						<input type="hidden" name="action" value="create_user" />
						<div class="table-cell table-150">
							<input type="text" id="lastname" placeholder="<?php echo $langs->trans('LastName'); ?>" name="lastname" value="<?php echo dol_escape_htmltag(GETPOST('lastname')); ?>" />
						</div>
						<div class="table-cell table-150">
							<input type="text" id="firstname" placeholder="<?php echo $langs->trans('FirstName'); ?>" name="firstname" value="<?php echo dol_escape_htmltag(GETPOST('firstname')); ?>" />
						</div>
						<div class="table-cell table-300">
							<input style="width:100%" type="email" id="email" class="email" placeholder="<?php echo $langs->trans('Email') ; ?>" name="email" value="<?php echo GETPOST('email'); ?>" />
						</div>
						<div class="table-cell table-150">
							<input type="text" id="job" placeholder="<?php echo $langs->trans('PostOrFunction'); ?>" name="job" value="<?php echo dol_escape_htmltag(GETPOST('job')); ?>" />
						</div>
						<div class="table-cell table-300">
							<?php echo $form->select_dolgroups(getDolGlobalInt('DIGIRISKDOLIBARR_READERGROUP_SET'), 'groupid', 1, '', 0, '', array(), (string) $conf->entity, false, 'minwidth100imp widthcentpercentminusxx groupselectcontact'); ?>
						</div>
						<div class="table-cell">
							<button type="button" class="wpeo-button button-square-50 button-blue wpeo-tooltip-event" aria-label="<?php echo $langs->trans('CreateUser'); ?>">
								<i class="button-icon fas fa-plus"></i>
							</button>
						</div>
					</div>

				</div>
				<!-- Modal-Footer -->
				<div class="modal-footer">
					<?php if ($permissiontoadd) : ?>
						<div class="evaluator-create wpeo-button button-primary modal-close<?php echo (GETPOST('modalactive') ? '' : ' button-disable'); ?>">
							<span><i class="fas fa-plus"></i>  <?php echo $langs->trans('AddEvaluatorButton'); ?></span>
						</div>
					<?php else : ?>
						<div class="wpeo-button button-grey wpeo-tooltip-event" aria-label="<?php echo $langs->trans('PermissionDenied') ?>">
							<i class="fas fa-plus"></i> <?php echo $langs->trans('AddEvaluatorButton'); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<?php $title = $langs->trans('EvaluatorList');
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, (empty($fromid) ? $newcardbutton : ''), '', $limit, 0, 0, 1);

	include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>';
	}

	$moreforfilter                       = '';
	$parameters                          = array();
	$reshook                             = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter                  = $hookmanager->resPrint;

	if ( ! empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage         = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields  = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($evaluator->fields as $key => $val) {
		$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		if ( ! empty($arrayfields['t.' . $key]['checked'])) {
			print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
			if (is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key], $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0) {
				if ($key == 'fk_user' && $fromid > 0) {
					$search[$key] = $fromid;
				}
				print $evaluator->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);
			} elseif ( ! preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key]) . '">';
			print '</td>';
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>' . "\n";

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($evaluator->fields as $key => $val) {
		$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		if ( ! empty($arrayfields['t.' . $key]['checked'])) {
			print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
	print '</tr>' . "\n";

	// Loop on record
	// --------------------------------------------------------------------

	// contenu
	$i          = 0;
	$totalarray = array();

	while ($i < ($limit ? min($num, $limit) : $num)) {
		$obj = $db->fetch_object($resql);

		if (empty($obj)) break; // Should not happen

		$evaluator->setVarsFromFetchObj($obj);

		// Show here line of result
		print '<tr class="oddeven evaluator-row evaluator_row_' . $evaluator->id . '" id="evaluator_row_' . $evaluator->id . '">';

		foreach ($evaluator->fields as $key => $val) {
			$cssforfield                         = (empty($val['css']) ? '' : $val['css']);
			if ($key == 'status') $cssforfield  .= ($cssforfield ? ' ' : '') . 'center';
			elseif ($key == 'ref') $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';

			if ( ! empty($arrayfields['t.' . $key]['checked'])) {
				if ($key == 'assignment_date') {
					print '<td>' . dol_print_date($evaluator->$key,'day','tzuser');
				} elseif ($key == 'duration') {
					print '<td>' . $evaluator->$key . ' min';
				} elseif ($key == 'fk_user') {
					$user->fetch($evaluator->$key);
					print '<td>' . $user->getNomUrl(1);
				} elseif ($key == 'fk_parent') {
					$object->fetch($evaluator->fk_parent);
					print '<td>' . $object->getNomUrl(1, 'blank', 0, '', -1, 1);
				} else {
					print '<td>' . $evaluator->$key;
				}
			}

			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
			if ( ! empty($val['isameasure'])) {
				if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key]                      += $evaluator->$key;
			}
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'object' => $evaluator, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook    = $hookmanager->executeHooks('printFieldListValue', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
		print $hookmanager->resPrint;

		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected                                                 = 0;
			if (in_array($evaluator->id, $arrayofselected)) $selected = 1;
			print '<input id="cb' . $evaluator->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $evaluator->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
		}
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
		print '</tr>' . "\n";
		$i++;
	}
	// If no record found
	if ($num == 0) {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) { if ( ! empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</td></tr>';
	}

	$db->free($resql);

	$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
	$reshook    = $hookmanager->executeHooks('printFieldListFooter', $parameters, $evaluator); // Note that $action and $evaluator may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>' . "\n";
	print '<!-- End table -->';
	print '</div>' . "\n";
	print '<!-- End div class="div-table-responsive" -->';
	print '</form>' . "\n";
	print '<!-- End form -->';
	print '</div>' . "\n";
	print '<!-- End div class="fichecenter" -->';

	print dol_get_fiche_end();
}

print '</div>' . "\n";
print '<!-- End div class="cardcontent" -->';

// End of page
llxFooter();
$db->close();
