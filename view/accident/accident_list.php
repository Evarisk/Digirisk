<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 *   	\file       view/accident/accident_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for accident
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'companies', 'commercial']);

// Get parameters
$action      = GETPOST('action', 'alpha');
$subaction   = GETPOST('subaction', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accidentlist';
$fromid      = GETPOST('fromid');
$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOSTISSET("sortfield") ? GETPOST("sortfield", "aZ09comma") : 't.date_creation';
$sortorder   = GETPOSTISSET("sortorder") ? GETPOST("sortorder", 'aZ09comma') : 'DESC';
$fromiduser  = GETPOST('fromiduser', 'int'); //element id
$page        = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$page        = is_numeric($page) ? $page : 0;
$page        = $page == -1 ? 0 : $page;

// Initialize technical objects
$accident         = new Accident($db);
$usertmp          = new User($db);
$thirdparty       = new Societe($db);
$digiriskelement  = new DigiriskElement($db);
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);
$accidentLesion   = new AccidentLesion($db);

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$hookmanager->initHooks(['digiriskelementview', 'accidentlist']); // Note that conf->hooks_modules contains array

// Initialize array of search criterias
$search_all = GETPOST('search_all') ? trim(GETPOST('search_all')) : trim(GETPOST('sall'));
$search     = [];
foreach ($accident->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha') !== '') $search[$key] = GETPOST('search_' . $key, 'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = [];
foreach ($accident->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.' . $key] = $val['label'];
	}
}

// Definition of fields for list
$arrayfields = [];

foreach ($accident->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.' . $key] = ['label' => $val['label'], 'checked' => (($val['visible'] < 0) ? 0 : 1), 'enabled' => ($val['enabled'] && ($val['visible'] != 3)), 'position' => $val['position']];
}

$id = GETPOST('id', 'int'); // get if for actions_fetchobject.inc.php
// Load accident object, why ?
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for accident
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

// Security check
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action     = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') {
	$massaction = '';
}

$parameters = ['id' => $fromid];
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	$backtopage = dol_buildpath('/digiriskdolibarr/view/accident/accident_list.php', 1) . (!empty($fromiduser) ? '?fromiduser=' . $fromiduser : '');

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		foreach ($accident->fields as $key => $val) {
			$search[$key] = '';
		}

		$toselect             = '';
		$search_array_options = [];
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$error = 0;
	if ( ! $error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		if ( ! empty($toselect)) {
			foreach ($toselect as $toselectedid) {
				$accidenttodelete = $accident;
				$accidenttodelete->fetch($toselectedid);

				$accidenttodelete->status = 0;
				$result                   = $accidenttodelete->delete($user);

				if ($result < 0) {
					// Delete accident KO
					if ( ! empty($accident->errors)) setEventMessages(null, $accident->errors, 'errors');
					else setEventMessages($accident->error, null, 'errors');
				}
			}

			// Delete accident OK
			$urltogo = str_replace('__ID__', (empty($fromiduser) ? $id : $fromiduser), $backtopage);
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (empty($fromiduser) ? $id : $fromiduser), $urltogo); // New method to autoselect project after a New on another form object creation
			header("Location: " . $_SERVER["PHP_SELF"]);
			exit;
		}
	}
}

/*
 * View
 */

$form      = new Form($db);
$formother = new FormOther($db);

$title    = $langs->trans("AccidentList");
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($fromid > 0) {
    digirisk_header($title, $helpUrl);
    $objectlinked = $digiriskelement;
	$objectlinked->fetch($fromid);
    saturne_get_fiche_head($objectlinked,'elementAccidents', $langs->trans('Accident'));
} else {
    saturne_header(0, '', $title, $helpUrl);
    if ($fromiduser > 0) {
        $userObject = new User($db);
        $userObject->fetch($fromiduser, '', '', 1);
        $userObject->getrights();
        saturne_get_fiche_head($userObject, 'accidents', $langs->trans('Accidents'));
    } elseif ($accident->id > 0) {
        saturne_get_fiche_head($object,'elementAccidents', $langs->trans('Accident'));
    }
}

// Object card
// ------------------------------------------------------------
if ($fromid > 0) {
	$height = 80;
	$width = 80;
	dol_strlen($objectlinked->label) ? $morehtmlref = ' - ' . $objectlinked->label : '';
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result = $parent_element->fetch($objectlinked->fk_parent);
	if ($result > 0) {
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $objectlinked->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $parent_element->getNomUrl(1, 'blank', 0, '', -1, 1);
	} else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $objectlinked->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $digiriskstandard->getNomUrl(1, 'blank', 0, '', -1, 1);
	}
	$morehtmlref .= '</div>';
    saturne_banner_tab($objectlinked, 'fromid', 'none', 0, 'rowid', 'ref', $morehtmlref, (dol_strlen($objectlinked->photo) > 0));
} elseif ($fromiduser > 0) {
	$linkback = '<a href="' . DOL_URL_ROOT . '/user/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
	dol_banner_tab($userObject, 'fromiduser', $linkback, $user->rights->user->user->lire || $user->admin);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = [];
if ($permissiontodelete) {
    $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>' . $langs->trans("Delete");
}
if (in_array($massaction, ['presend', 'predelete'])) {
    $arrayofmassactions = [];
}

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($permissiontoadd) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewAccident'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/custom/digiriskdolibarr/view/accident/accident_card.php?action=create' . (!empty($fromid) ? '&fromid=' . $fromid : '') . (!empty($fromiduser) ? '&fromiduser=' . $fromiduser : ''));
}

if ($fromiduser > 0) {
	print '<div class="underbanner clearboth"></div>';
}

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . (!empty($fromiduser) ? '?fromid=' . $fromiduser : '') .'">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

// Build and execute select
// --------------------------------------------------------------------

$sql = 'SELECT ';
foreach ($accident->fields as $key => $val) {
	$sql .= 't.' . $key . ', ';
}
// Add fields from extrafields
if ( ! empty($extrafields->attributes[$accident->table_element]['label'])) {
	foreach ($extrafields->attributes[$accident->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$accident->table_element]['type'][$key] != 'separate' ? "ef." . $key . ' as options_' . $key . ', ' : '');
}
// Add fields from hooks
$parameters = [];
$reshook    = $hookmanager->executeHooks('printFieldListSelect', $parameters, $accident); // Note that $action and $accidentdocument may have been modified by hook
$sql       .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql        = preg_replace('/,\s*$/', '', $sql);
$sql       .= " FROM " . MAIN_DB_PREFIX . $accident->table_element . " as t";

if (isset($extrafields->attributes[$accident->table_element]['label']) &&
    is_array($extrafields->attributes[$accident->table_element]['label']) && count($extrafields->attributes[$accident->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $accident->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
if ($accident->ismultientitymanaged == 1) $sql                                                                                                        .= " WHERE t.entity IN (" . getEntity($accident->element) . ")";
else $sql                                                                                                                                             .= " WHERE 1 = 1";
$sql                                                                                                                                                  .= ' AND status != ' . $accident::STATUS_DELETED;
if ($fromid > 0) {
	$sql .= ' AND fk_element =' . $fromid;
}

foreach ($search as $key => $val) {
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search = (($accident->isInt($accident->fields[$key]) || $accident->isFloat($accident->fields[$key])) ? 1 : 0);
	if (strpos($accident->fields[$key]['type'], 'integer:') === 0) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search                             = 2;
	}
	if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
}

if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = [];
$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters, $accident); // Note that $action and $accidentdocument may have been modified by hook
$sql       .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

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
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

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
	header("Location: " . dol_buildpath('/digiriskdolibarr/view/accident/accident_card.php', 1) . '?id=' . $id);
	exit;
}

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$arrayfields['Victim'] = array('label' => 'Victim', 'checked' => 1);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_' . $accident->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

$selectedfields                         = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";
print '<tr class="liste_titre">';

$accident->fields['Custom']['Victim']    = $arrayfields['Victim'] ;

// We manually add progress field here because it is a redundant information that doesn't need to be stored in db
$accident->fields['progress'] = ['type' => 'integer:', 'label' => 'Progress', 'enabled' => '1', 'position' => 70, 'notnull' => 0, 'visible' => 2, 'index' => 0,];
$arrayfields['t.progress']    = ['label' => 'Progress', 'checked' => 1, 'enabled' => 0, 'position' => 70, 'disablesort' => 1];

foreach ($accident->fields as $key => $val) {
	$cssforfield                        = (empty($val['css']) ? '' : $val['css']);
    if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
	if ( ! empty($arrayfields['t.' . $key]['checked'])) {
		print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';

		if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) print $form->selectarray('search_' . $key, $val['arrayofkeyval'], $search[$key] ?? '', $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth75');
		elseif (strpos($val['type'], 'integer:') === 0) {
			print $accident->showInputField($val, $key, $search[$key] ?? '', '', '', 'search_', 'maxwidth150', 1);
		} elseif ( ! preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag($search[$key] ?? '') . '">';
		print '</td>';
	}
    if ($key == 'Custom') {
        foreach ($val as $resource) {
            if ($resource['checked']) {
                print '<td>';
                print '</td>';
            }
        }
    }
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters, $accident); // Note that $action and $accidentdocument may have been modified by hook
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

foreach ($accident->fields as $key => $val) {
    $disablesort = !empty($arrayfields['t.' . $key]['disablesort']);
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    }
	if ( ! empty($arrayfields['t.' . $key]['checked'])) {
        if (preg_match('/Victim/', $arrayfields['t.' . $key]['label'])) {
            $disablesort = 1;
        } else {
            $disablesort = 0;
        }
		print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : ''), $disablesort) . "\n";
	}
    if ($key == 'Custom') {
        foreach ($val as $resource) {
            if ($resource['checked']) {
                print '<td>';
                print $langs->trans($resource['label']);
                print '</td>';
            }
        }
    }
}

// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters, $accident); // Note that $action and $accidentdocument may have been modified by hook
print $hookmanager->resPrint;

// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
print '</tr>' . "\n";

$arrayofselected = is_array($toselect) ? $toselect : [];

// Loop on record
// --------------------------------------------------------------------

// contenu
$i          = 0;
$kCounter   = 0;
$totalarray = ['nbfield' => 0];

while ($i < ($limit ? min($num, $limit) : $num)) {
	$obj = $db->fetch_object($resql);

	if (empty($obj)) break; // Should not happen


	// Store properties in $accidentdocument
	$accident->setVarsFromFetchObj($obj);
    $userVictim = $accident->getUserVictim();

    if (isset($accident->json)) {
        $json = json_decode($accident->json, false, 512, JSON_UNESCAPED_UNICODE)->Accident;
    } else {
        $json = [];
    }

	// Show here line of result
	print '<tr class="oddeven accidentdocument-row accident_row_' . $accident->id . ' accidentdocument-row-content-' . $accident->id . '" id="accident_row_' . $accident->id . '">';
	foreach ($accident->fields as $key => $val) {
		$cssforfield                                 = (empty($val['css']) ? '' : $val['css']);
		if ($key == 'status') $cssforfield          .= ($cssforfield ? ' ' : '') . 'center';
        elseif ($key == 'progress') $cssforfield    .= ($cssforfield ? ' ' : '') . 'center';
        elseif ($key == 'ref') $cssforfield         .= ($cssforfield ? ' ' : '') . 'nowrap';
		elseif ($key == 'description') $cssforfield .= ($cssforfield ? ' ' : '') . 'accidentdocument-description';

        if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . ' style="width:2%">';

			if ($key == 'progress') {
				$counter = 0;
				$kCounter++;

				$morecssGauge     = 'center';
				$move_title_gauge = 1;

				$arrayAccident   = [];
				$arrayAccident[] = $accident->ref;
				$arrayAccident[] = $accident->label;
				$arrayAccident[] = ( ! empty($accident->accident_type) ? $accident->accident_type : 0);
				$arrayAccident[] = $accident->accident_date;
				$arrayAccident[] = $accident->description;
				switch ($accident->external_accident) {
					case 1:
						$arrayAccident[] = $accident->fk_element > 0 ? $accident->fk_element : $accident->fk_standard;
						break;
					case 2:
						$arrayAccident[] = $accident->fk_soc;
						break;
					case 3:
						$arrayAccident[] = $accident->accident_location;
						break;
				}
                $arrayAccident[] = $userVictim->id > 0 ? $userVictim->id : '';

                $accidentLesions = $accidentLesion->fetchAll('', '', 0, 0, ['customsql' => 't.fk_accident = ' . $accident->id]);
                $arrayAccident[] = (is_array($accidentLesions) && !empty($accidentLesions)) ? count($accidentLesions) : '';

				$maxnumber = count($arrayAccident);

				foreach ($arrayAccident as $arrayAccidentData) {
					if (dol_strlen($arrayAccidentData) > 0 ) {
						$counter += 1;
					}
				}

				$advancement = price2Num((($counter / $maxnumber) * 100), 2);

				print $advancement . '%';
				?>
				<div class="progress-bar progress-bar-info" style="width: 100%; height:10px; background-color: lightgray" title=" <?php echo $advancement ?>%">
					<div class="progress-bar progress-bar-consumed" style="width:  <?php echo $advancement ?>%; background-color: forestgreen" title="0%"></div>
				</div>
				<?php
			} else if ($key == 'status') {
                print $accident->getLibStatut(5);
            } elseif ($key == 'ref') {
				print '<i class="fas fa-user-injured"></i>  ' . $accident->getNomUrl();
			} elseif ($key == 'fk_user_employer') {
				$usertmp->fetch($accident->fk_user_employer);
				if ($usertmp > 0) {
					print getNomUrlUser($usertmp, 1, 'blank', 0, 0, 0, 0, '', '', -1, 0);
				}
			} elseif ($key == 'accident_type') {
				if ($accident->accident_type == 0) {
					print $langs->trans('WorkAccidentStatement');
				} elseif ($accident->accident_type == 1) {
					print $langs->trans('CommutingAccident');
				}
			} elseif ($key == 'fk_element') {
				switch ($accident->external_accident) {
					case 1:
						if ($accident->fk_standard > 0) {
							$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
							print $digiriskstandard->getNomUrl(1, 'blank', 0, '', -1, 1);
						} else if ($accident->fk_element > 0) {
							$digiriskelement->fetch($accident->fk_element);
							print $digiriskelement->getNomUrl(1, 'blank', 0, '', -1, 1);
						}
						break;
					case 2:
						$thirdparty->fetch($accident->fk_soc);
						print getNomUrlSociety($thirdparty, 1, 'blank');
						break;
					case 3:
						print $accident->accident_location;
						break;
				}
			} elseif ($key == 'accident_date') {
				print dol_print_date($accident->accident_date, 'dayhour', 'tzserver');	// We suppose dates without time are always gmt (storage of course + output)
			} else print $accident->showOutputField($val, $key, $accident->$key, '');
			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
			if ( ! empty($val['isameasure'])) {
				if ( ! $i) $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
				$totalarray['val']['t.' . $key]                      += $accident->$key;
			}
        }
        if ($key == 'Custom') {
            foreach ($val as $name => $resource) {
                if ($resource['checked']) {
                    print '<td>';

                    if ($resource['label'] == 'Victim') {
                        print getNomUrlUser($userVictim, 1, 'blank', 0, 0, 0, 0, '', '', -1, 0);
                    }
                    print '</td>';
                }
            }
        }

    }
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected                                                = 0;
		if (in_array($accident->id, $arrayofselected)) $selected = 1;
		print '<input id="cb' . $accident->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $accident->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
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

$parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
$reshook    = $hookmanager->executeHooks('printFieldListFooter', $parameters, $risk); // Note that $action and $risk may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print '</div>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
