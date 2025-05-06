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
 *      \file       view/digiriskusers.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view Users
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

require_once __DIR__ . '/../lib/digiriskdolibarr_function.lib.php';

// Global variables definitions
global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by page
saturne_load_langs(['users', 'companies', 'hrm']);

// Get parameters
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digiriskuserslist'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');

// Load mode employee
$mode  = GETPOST("mode", 'alpha');
$group = $conf->global->DIGIRISKDOLIBARR_READERGROUP_SET;

// Load variable for pagination
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset                       = $limit * $page;
$pageprev                     = $page - 1;
$pagenext                     = $page + 1;
if ( ! $sortfield) $sortfield = "u.login";
if ( ! $sortorder) $sortorder = "ASC";

// Initialize technical objects
$object          = new User($db);
$extrafields     = new ExtraFields($db);
$userstatic      = new User($db);
$companystatic   = new Societe($db);
$usergroupstatic = new UserGroup($db);

$hookmanager->initHooks(array('digiriskuserlist', 'globalcard')); // Note that conf->hooks_modules contains array

// Define value to know what current user can do on users
$permissiontoadd  = ( ! empty($user->admin) || $user->rights->user->user->creer);
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;

// Security check - Protection if external user
saturne_check_access($permissiontoadd);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'u.login' => "Login",
	'u.lastname' => "Lastname",
	'u.firstname' => "Firstname",
	'u.accountancy_code' => "AccountancyCode",
	'u.email' => "EMail",
	'u.note' => "Note",
);
if ( ! empty($conf->api->enabled)) {
	$fieldstosearchall['u.api_key'] = "ApiKey";
}

// Definition of fields for list
$arrayfields = array(
	'u.login' => array('label' => $langs->trans("Login"), 'checked' => 1),
	'u.lastname' => array('label' => $langs->trans("Lastname"), 'checked' => 1),
	'u.firstname' => array('label' => $langs->trans("Firstname"), 'checked' => 1),
	'u.gender' => array('label' => $langs->trans("Gender"), 'checked' => 0),
	'u.employee' => array('label' => $langs->trans("Employee"), 'checked' => ($mode == 'employee' ? 1 : 0)),
	'u.accountancy_code' => array('label' => $langs->trans("AccountancyCode"), 'checked' => 0),
	'u.email' => array('label' => $langs->trans("EMail"), 'checked' => 1),
	'u.job' => array('label' => $langs->trans("PostOrFunction"), 'checked' => 1),
	'u.api_key' => array('label' => $langs->trans("ApiKey"), 'checked' => 0, "enabled" => ($conf->api->enabled && $user->admin)),
	'u.fk_soc' => array('label' => $langs->trans("Company"), 'checked' => 1),
	'u.entity' => array('label' => $langs->trans("Entity"), 'checked' => 1, 'enabled' => ( ! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))),
	'g.fk_usergroup' => array('label' => $langs->trans("UserGroup"), 'checked' => 1),
	'u.fk_user' => array('label' => $langs->trans("HierarchicalResponsible"), 'checked' => 0),
	'u.datelastlogin' => array('label' => $langs->trans("LastConnexion"), 'checked' => 0, 'position' => 100),
	'u.datepreviouslogin' => array('label' => $langs->trans("PreviousConnexion"), 'checked' => 0, 'position' => 110),
	'u.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
	'u.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 500),
	'u.statut' => array('label' => $langs->trans("Status"), 'checked' => 1, 'position' => 1000),
);

// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		if ( ! empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef." . $key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'checked' => (($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position' => $extrafields->attributes[$object->table_element]['pos'][$key], 'enabled' => (abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields    = dol_sort_array($arrayfields, 'position');

// Init search fields
$sall                    = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_user             = GETPOST('search_user', 'alpha');
$search_login            = GETPOST('search_login', 'alpha');
$search_lastname         = GETPOST('search_lastname', 'alpha');
$search_firstname        = GETPOST('search_firstname', 'alpha');
$search_gender           = GETPOST('search_gender', 'alpha');
$search_employee         = GETPOST('search_employee', 'alpha');
$search_accountancy_code = GETPOST('search_accountancy_code', 'alpha');
$search_email            = GETPOST('search_email', 'alpha');
$search_job              = GETPOST('search_job', 'alpha');
$search_api_key          = GETPOST('search_api_key', 'alphanohtml');
$search_statut           = GETPOST('search_statut', 'intcomma');
$search_thirdparty       = GETPOST('search_thirdparty', 'alpha');
$search_supervisor       = GETPOST('search_supervisor', 'intcomma');
$search_fk_usergroup     = GETPOST('search_fk_usergroup', 'intcomma');
$optioncss               = GETPOST('optioncss', 'alpha');
$search_categ            = GETPOST("search_categ", 'int');
$catid                   = GETPOST('catid', 'int');

// Default search
if ($search_statut == '') $search_statut                                       = '1';
if ($mode == 'employee' && ! GETPOSTISSET('search_employee')) $search_employee = 1;

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if ( isset($massaction) && !GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_user              = "";
		$search_login             = "";
		$search_lastname          = "";
		$search_firstname         = "";
		$search_gender            = "";
		$search_employee          = "";
		$search_accountancy_code  = "";
		$search_email             = "";
		$search_job               = "";
		$search_statut            = "";
		$search_thirdparty        = "";
		$search_supervisor        = "";
		$search_fk_usergroup      = "";
		$search_api_key           = "";
		$search_datelastlogin     = "";
		$search_datepreviouslogin = "";
		$search_date_creation     = "";
		$search_date_update       = "";
		$search_array_options     = array();
		$search_categ             = 0;
	}
}
// Action Add user
if ($action == 'add' && $permissiontoadd) {
	$error = 0;

	if ( ! $_POST["lastname"]) {
		$error++;
		setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
		$action = "create"; // Go back to create page
	}
	if ( ! $_POST["login"]) {
		$login = GETPOST('lastname') . GETPOST('firstname');
	}

	$email = GETPOST('email', 'alpha');
	if (empty($email)) {
		setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Email')), array(), 'errors');
		$error++;
	} else {
		$regEmail = '/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/';
		if (preg_match($regEmail, $email)) {
			$object->origin_email = $email;
		} else {
			setEventMessages($langs->trans('ErrorFieldEmail'), array(), 'errors');
			$error++;
		}
	}

	if ( ! empty($conf->file->main_limit_users)) { // If option to limit users is set
		$nb = $object->getNbOfUsers("active");
		if ($nb >= $conf->file->main_limit_users) {
			$error++;
			setEventMessages($langs->trans("YourQuotaOfUsersIsReached"), null, 'errors');
			$action = "create"; // Go back to create page
		}
	}

	if ( ! $error) {
		$object->lastname  = GETPOST("lastname", 'alphanohtml');
		$object->firstname = GETPOST("firstname", 'alphanohtml');
		$object->login     = $login;
		$object->email     = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
		$object->job       = GETPOST("job", 'alphanohtml');
		$object->fk_user   = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", 'int') : 0;

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}

		// Set entity property
		$object->entity = $conf->entity;

		$db->begin();

		$id = $object->create($user);
		if ($id > 0) {
			if (GETPOST('password')) {
				$newpassword = $object->setPassword($user, GETPOST('password'));
			}
			$object->SetInGroup($group, $conf->entity);

			if ($newpassword < 0) {
				// Echec
				setEventMessages($langs->trans("ErrorFailedToSetNewPassword"), null, 'errors');
			} else {
				// Success
				if (GETPOST('send_password')) {
					if ($object->send_password($user, $newpassword) > 0) {
						setEventMessages($langs->trans("UserCreated", $object->email), null, 'mesgs');
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				} else {
					setEventMessages($langs->trans("UserCreated", $newpassword), null, 'mesgs');
				}
			}

			$db->commit();

			if ($backtopage) {
				$urltogo = str_replace('USERID', $id, $backtopage);
				$urltogo = str_replace('JOB', $object->job, $urltogo);
				header("Location: " . $urltogo);
			} else {
				header("Location: " . $_SERVER['PHP_SELF']);
			}
			exit;
		} else {
			$langs->load("errors");
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = "create"; // Go back to create page
		}
	}
}

/*
 * View
 */

$form      = new Form($db);
$formother = new FormOther($db);

$user2 = new User($db);

$sql  = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.fk_soc, u.login, u.email, u.job, u.api_key, u.accountancy_code, u.gender, u.employee, u.photo,";
$sql .= " u.datelastlogin, u.datepreviouslogin,";
$sql .= " u.ldap_sid, u.statut, u.entity,";
$sql .= " u.tms as date_update, u.datec as date_creation,";
$sql .= " u2.rowid as id2, u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2, u2.admin as admin2, u2.fk_soc as fk_soc2, u2.email as email2, u2.gender as gender2, u2.photo as photo2, u2.entity as entity2, u2.statut as statut2,";
$sql .= " s.nom as name, s.canvas,";
$sql .= " g.fk_usergroup as fk_usergroup";
// Add fields from extrafields
if ( ! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . ' as options_' . $key : '');
}
// Add fields from hooks
$parameters                                                                                                                                        = array();
$reshook                                                                                                                                           = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql                                                                                                                                              .= $hookmanager->resPrint;
$sql                                                                                                                                              .= " FROM " . MAIN_DB_PREFIX . "user as u";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (u.rowid = ef.fk_object)";
$sql                                                                                                                                              .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON u.fk_soc = s.rowid";
$sql                                                                                                                                              .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u2 ON u.fk_user = u2.rowid";
$sql                                                                                                                                              .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as g ON u.rowid = g.fk_user";
if ( ! empty($search_categ) || ! empty($catid)) $sql                                                                                              .= ' LEFT JOIN ' . MAIN_DB_PREFIX . "categorie_user as cu ON u.rowid = cu.fk_user"; // We'll need this table joined to the select in order to filter by categ
// Add fields from hooks
$parameters = array();
$reshook    = $hookmanager->executeHooks('printUserListWhere', $parameters); // Note that $action and $object may have been modified by hook
if ($reshook > 0) {
	$sql .= $hookmanager->resPrint;
} else {
	$sql .= " WHERE u.entity IN (".getEntity('user').")";
}
if (isset($socid) && $socid > 0) $sql .= " AND u.fk_soc = " . $socid;
//if ($search_user != '')       $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
if ($search_supervisor > 0)   $sql                           .= " AND u.fk_user IN (" . $db->escape($search_supervisor) . ")";
if ($search_thirdparty != '') $sql                           .= natural_search(array('s.nom'), $search_thirdparty);
if ($search_login != '')      $sql                           .= natural_search("u.login", $search_login);
if ($search_lastname != '')   $sql                           .= natural_search("u.lastname", $search_lastname);
if ($search_firstname != '')  $sql                           .= natural_search("u.firstname", $search_firstname);
if ($search_gender != '' && $search_gender != '-1')     $sql .= " AND u.gender = '" . $db->escape($search_gender) . "'";	// Cannot use natural_search as looking for %man% also includes woman
if (is_numeric($search_employee) && $search_employee >= 0) {
	$sql .= ' AND u.employee = ' . (int) $search_employee;
}
if ($search_accountancy_code != '')  $sql             .= natural_search("u.accountancy_code", $search_accountancy_code);
if ($search_email != '')             $sql             .= natural_search("u.email", $search_email);
if ($search_job != '')             $sql               .= natural_search("u.job", $search_job);
if ($search_api_key != '')           $sql             .= natural_search("u.api_key", $search_api_key);
if ($search_statut != '' && $search_statut >= 0) $sql .= " AND u.statut IN (" . $db->escape($search_statut) . ")";
if ($sall)                           $sql             .= natural_search(array_keys($fieldstosearchall), $sall);
if ($catid > 0)     $sql                              .= " AND cu.fk_categorie = " . $catid;
if ($catid == -2)   $sql                              .= " AND cu.fk_categorie IS NULL";
if ($search_categ > 0)   $sql                         .= " AND cu.fk_categorie = " . $db->escape($search_categ);
if ($search_categ == -2) $sql                         .= " AND cu.fk_categorie IS NULL";
if ($search_fk_usergroup > 0)   $sql                  .= " AND g.fk_usergroup IN (" . $db->escape($search_fk_usergroup) . ")";

$user->fetchAll('','','','', "(login:=:'USERAPI')");

if (is_array($user->users) && !empty($user->users)) {
	$userIds = implode(',', array_keys($user->users));
	$sql .= ' AND u.rowid NOT IN (' . $userIds . ')';
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook    = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql       .= $hookmanager->resPrint;
$sql       .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = 0;
$result           = $db->query($sql);
if ($result) {
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ( ! $result) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($result);

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall) {
	$obj = $db->fetch_object($resql);
	$id  = $obj->rowid;
	header("Location: " . DOL_URL_ROOT . '/user/card.php?id=' . $id);
	exit;
}

$title    = $langs->trans("ListOfUsers");
$help_url = 'FR:Module_Digirisk';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

$param                                                                      = '';
if ( ! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&amp;contextpage=' . urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param                     .= '&amp;limit=' . urlencode($limit);
if ($sall != '') $param                                                    .= '&amp;sall=' . urlencode($sall);
if ($search_user != '') $param                                             .= "&amp;search_user=" . urlencode($search_user);
if ($search_login != '') $param                                            .= "&amp;search_login=" . urlencode($search_login);
if ($search_lastname != '') $param                                         .= "&amp;search_lastname=" . urlencode($search_lastname);
if ($search_firstname != '') $param                                        .= "&amp;search_firstname=" . urlencode($search_firstname);
if ($search_gender != '') $param                                           .= "&amp;search_gender=" . urlencode($search_gender);
if ($search_employee != '') $param                                         .= "&amp;search_employee=" . urlencode($search_employee);
if ($search_accountancy_code != '') $param                                 .= "&amp;search_accountancy_code=" . urlencode($search_accountancy_code);
if ($search_email != '') $param                                            .= "&amp;search_email=" . urlencode($search_email);
if ($search_job != '') $param                                              .= "&amp;search_job=" . urlencode($search_job);
if ($search_api_key != '') $param                                          .= "&amp;search_api_key=" . urlencode($search_api_key);
if ($search_supervisor > 0) $param                                         .= "&amp;search_supervisor=" . urlencode($search_supervisor);
if ($search_fk_usergroup > 0) $param                                       .= "&amp;search_fk_usergroup=" . urlencode($search_fk_usergroup);
if ($search_statut != '') $param                                           .= "&amp;search_statut=" . urlencode($search_statut);
if ($optioncss != '') $param                                               .= '&amp;optioncss=' . urlencode($optioncss);
if ($mode != '')      $param                                               .= '&amp;mode=' . urlencode($mode);
if ($search_categ > 0) $param                                              .= "&amp;search_categ=" . urlencode($search_categ);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="mode" value="' . $mode . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $nbtotalofrecords, 'user', 0, '', '', $limit, 0, 0, 1);

if ( ! empty($catid)) {
	print "<div id='ways'>";
	$c    = new Categorie($db);
	$ways = $c->print_all_ways(' &gt; ', 'user/list.php');
	print " &gt; " . $ways[0] . "<br>\n";
	print "</div><br>";
}

if ($sall) {
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter = '';

// Filter on categories
if ( ! empty($conf->categorie->enabled)) {
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Categories') . ': ';
	$moreforfilter .= $formother->select_categories(Categorie::TYPE_USER, $search_categ, 'search_categ', 1);
	$moreforfilter .= '</div>';
}

$parameters                          = array();
$reshook                             = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter                  = $hookmanager->resPrint;

if ($moreforfilter) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Search bar
print '<tr class="liste_titre_filter">';
if ( ! empty($arrayfields['u.login']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="' . $search_login . '"></td>';
}
if ( ! empty($arrayfields['u.lastname']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_lastname" class="maxwidth50" value="' . $search_lastname . '"></td>';
}
if ( ! empty($arrayfields['u.firstname']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_firstname" class="maxwidth50" value="' . $search_firstname . '"></td>';
}
if ( ! empty($arrayfields['u.gender']['checked'])) {
	print '<td class="liste_titre">';
	$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"));
	print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
	print '</td>';
}
if ( ! empty($arrayfields['u.employee']['checked'])) {
	print '<td class="liste_titre">';
	print $form->selectyesno('search_employee', $search_employee, 1, false, 1);
	print '</td>';
}
if ( ! empty($arrayfields['u.accountancy_code']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_accountancy_code" class="maxwidth50" value="' . $search_accountancy_code . '"></td>';
}
if ( ! empty($arrayfields['u.email']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_email" class="maxwidth75" value="' . $search_email . '"></td>';
}
if ( ! empty($arrayfields['u.api_key']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_api_key" class="maxwidth50" value="' . $search_api_key . '"></td>';
}
if ( ! empty($arrayfields['u.job']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_job" class="maxwidth75" value="' . $search_job . '"></td>';
}
if ( ! empty($arrayfields['u.fk_soc']['checked'])) {
	print '<td class="liste_titre"><input type="text" name="search_thirdparty" class="maxwidth75" value="' . $search_thirdparty . '"></td>';
}
if ( ! empty($arrayfields['u.entity']['checked'])) {
	print '<td class="liste_titre"></td>';
}
// Supervisor
if ( ! empty($arrayfields['u.fk_user']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_supervisor, 'search_supervisor', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'maxwidth200');
	print '</td>';
}
if ( ! empty($arrayfields['u.datelastlogin']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if ( ! empty($arrayfields['u.datepreviouslogin']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if ( ! empty($arrayfields['g.fk_usergroup']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_dolgroups($search_fk_usergroup, 'search_fk_usergroup', 1);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook    = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if ( ! empty($arrayfields['u.datec']['checked'])) {
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if ( ! empty($arrayfields['u.tms']['checked'])) {
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if ( ! empty($arrayfields['u.statut']['checked'])) {
	// Status
	print '<td class="liste_titre center">';
	print $form->selectarray('search_statut', array('-1' => '', '0' => $langs->trans('Disabled'), '1' => $langs->trans('Enabled')), $search_statut);
	print '</td>';
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';

print "</tr>\n";

print '<tr class="liste_titre">';
if ( ! empty($arrayfields['u.login']['checked']))          print_liste_field_titre("Login", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.lastname']['checked']))       print_liste_field_titre("Lastname", $_SERVER['PHP_SELF'], "u.lastname", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.firstname']['checked']))      print_liste_field_titre("FirstName", $_SERVER['PHP_SELF'], "u.firstname", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.gender']['checked']))         print_liste_field_titre("Gender", $_SERVER['PHP_SELF'], "u.gender", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.employee']['checked']))       print_liste_field_titre("Employee", $_SERVER['PHP_SELF'], "u.employee", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.accountancy_code']['checked'])) print_liste_field_titre("AccountancyCode", $_SERVER['PHP_SELF'], "u.accountancy_code", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.email']['checked']))          print_liste_field_titre("EMail", $_SERVER['PHP_SELF'], "u.email", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.job']['checked']))          print_liste_field_titre("PostOrFunction", $_SERVER['PHP_SELF'], "u.job", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.api_key']['checked']))        print_liste_field_titre("ApiKey", $_SERVER['PHP_SELF'], "u.api_key", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.fk_soc']['checked']))         print_liste_field_titre("Company", $_SERVER['PHP_SELF'], "u.fk_soc", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.entity']['checked']))         print_liste_field_titre("Entity", $_SERVER['PHP_SELF'], "u.entity", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['g.fk_usergroup']['checked']))         print_liste_field_titre("UserGroup", $_SERVER['PHP_SELF'], "g.fk_usergroup", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.fk_user']['checked']))        print_liste_field_titre("HierarchicalResponsible", $_SERVER['PHP_SELF'], "u.fk_user", $param, "", "", $sortfield, $sortorder);
if ( ! empty($arrayfields['u.datelastlogin']['checked']))  print_liste_field_titre("LastConnexion", $_SERVER['PHP_SELF'], "u.datelastlogin", $param, "", '', $sortfield, $sortorder, 'center ');
if ( ! empty($arrayfields['u.datepreviouslogin']['checked'])) print_liste_field_titre("PreviousConnexion", $_SERVER['PHP_SELF'], "u.datepreviouslogin", $param, "", '', $sortfield, $sortorder, 'center ');
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook    = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if ( ! empty($arrayfields['u.datec']['checked']))  print_liste_field_titre("DateCreationShort", $_SERVER["PHP_SELF"], "u.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if ( ! empty($arrayfields['u.tms']['checked']))    print_liste_field_titre("DateModificationShort", $_SERVER["PHP_SELF"], "u.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if ( ! empty($arrayfields['u.statut']['checked'])) print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "u.statut", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";

$i          = 0;
$totalarray = ['nbfield' => 0];
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($result);

	$userstatic->id        = $obj->rowid;
	$userstatic->admin     = $obj->admin;
	$userstatic->ref       = $obj->label ?? '';
	$userstatic->login     = $obj->login;
	$userstatic->statut    = $obj->statut;
	$userstatic->email     = $obj->email;
	$userstatic->job       = $obj->job;
	$userstatic->gender    = $obj->gender;
	$userstatic->socid     = $obj->fk_soc;
	$userstatic->firstname = $obj->firstname;
	$userstatic->lastname  = $obj->lastname;
	$userstatic->employee  = $obj->employee;
	$userstatic->photo     = $obj->photo;

	$li = $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);

	print "<tr>";
	if ( ! empty($arrayfields['u.login']['checked'])) {
		print '<td class="nowraponall">';
		print $li;
		if ( ! empty($conf->multicompany->enabled) && $obj->admin && ! $obj->entity) {
			print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
		} elseif ($obj->admin) {
			print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
		}
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.lastname']['checked'])) {
		print '<td>' . $obj->lastname . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.firstname']['checked'])) {
		print '<td>' . $obj->firstname . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.gender']['checked'])) {
		print '<td>';
		if ($obj->gender) print $langs->trans("Gender" . $obj->gender);
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.employee']['checked'])) {
		print '<td>' . yn($obj->employee) . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.accountancy_code']['checked'])) {
		print '<td>' . $obj->accountancy_code . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.email']['checked'])) {
		print '<td>' . $obj->email . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.job']['checked'])) {
		print '<td>' . $obj->job . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.api_key']['checked'])) {
		print '<td>' . $obj->api_key . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	if ( ! empty($arrayfields['u.fk_soc']['checked'])) {
		print "<td>";
		if ($obj->fk_soc) {
			$companystatic->id     = $obj->fk_soc;
			$companystatic->name   = $obj->name;
			$companystatic->canvas = $obj->canvas;
			print $companystatic->getNomUrl(1);
		} elseif ($obj->ldap_sid) {
			print $langs->trans("DomainUser");
		} else {
			print $langs->trans("InternalUser");
		}
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Multicompany enabled
	if ( ! empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
		if ( ! empty($arrayfields['u.entity']['checked'])) {
			print '<td>';
			if ( ! $obj->entity) {
				print $langs->trans("AllEntities");
			} else {
				$mc->getInfo($obj->entity);
				print $mc->label;
			}
			print '</td>';
			if ( ! $i) $totalarray['nbfield']++;
		}
	}
	if ( ! empty($arrayfields['g.fk_usergroup']['checked'])) {
		print '<td>';
		if ($obj->fk_usergroup) {
			$usergroupstatic->id = $obj->fk_usergroup;
			$usergroupstatic->fetch($usergroupstatic->id);
			print $usergroupstatic->getNomUrl(1);
		}
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Supervisor
	if ( ! empty($arrayfields['u.fk_user']['checked'])) {
		// Resp
		print '<td class="nowrap">';
		if ($obj->login2) {
			$user2->id        = $obj->id2;
			$user2->login     = $obj->login2;
			$user2->lastname  = $obj->lastname2;
			$user2->firstname = $obj->firstname2;
			$user2->gender    = $obj->gender2;
			$user2->photo     = $obj->photo2;
			$user2->admin     = $obj->admin2;
			$user2->email     = $obj->email2;
			$user2->job       = $obj->job;
			$user2->socid     = $obj->fk_soc2;
			$user2->statut    = $obj->statut2;
			print $user2->getNomUrl(-1, '', 0, 0, 24, 0, '', '', 1);
			if ( ! empty($conf->multicompany->enabled) && $obj->admin2 && ! $obj->entity2) {
				print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
			} elseif ($obj->admin2) {
				print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
			}
		}
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}

	// Date last login
	if ( ! empty($arrayfields['u.datelastlogin']['checked'])) {
		print '<td class="nowrap center">' . dol_print_date($db->jdate($obj->datelastlogin), "dayhour") . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Date previous login
	if ( ! empty($arrayfields['u.datepreviouslogin']['checked'])) {
		print '<td class="nowrap center">' . dol_print_date($db->jdate($obj->datepreviouslogin), "dayhour") . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook    = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if ( ! empty($arrayfields['u.datec']['checked'])) {
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Date modification
	if ( ! empty($arrayfields['u.tms']['checked'])) {
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Status
	if ( ! empty($arrayfields['u.statut']['checked'])) {
		$userstatic->statut = $obj->statut;
		print '<td class="center">' . $userstatic->getLibStatut(5) . '</td>';
		if ( ! $i) $totalarray['nbfield']++;
	}
	// Action column
	print '<td></td>';
	if ( ! $i) $totalarray['nbfield']++;

	print "</tr>\n";

	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook    = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";

print "</form>\n";

if ($permissiontoadd && (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) || $conf->entity == 1)) {
	print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST" name="createuser">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ( ! empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="' . dol_escape_htmltag($ldap_sid) . '">';
	print '<input type="hidden" name="entity" value="' . $conf->entity . '">';

	$generated_password = getRandomPassword(false);
	$password           = (GETPOSTISSET('password') ? GETPOST('password') : $generated_password);

	?>
	<div class="digirisk-wrap wpeo-wrap digirisk-users" id="addUser" style="padding-right: 0 !important;">
		<div class="main-container" style="width:auto;  margin-top:0 !important; padding-left:0 !important;">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active" style="padding:0 !important">
						<div class="wpeo-table table-flex table-risk">
							<div class="table-row user-row edit">
								<input type="hidden" name="action" value="add" />
								<input type="hidden" class="input-domain-mail" name="societyname" value="<?php echo preg_replace('/ /', '', $conf->global->MAIN_INFO_SOCIETE_NOM) . '.fr' ?>" />
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
								<div class="table-cell">
									<input type="submit" id="createuseraction" name="createuseraction" style="display : none">
									<label for="createuseraction">
										<div class="wpeo-button button-square-50 button-blue wpeo-tooltip-event" aria-label="<?php echo $langs->trans('CreateUser'); ?>">
											<i class="button-icon fas fa-plus"></i>
										</div>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	$action = '';
	print '</form>';
	print '</table></tr>';
} else { ?>
	<div class="wpeo-notice notice-info">
		<div class="notice-content">
			<div class="notice-subtitle"><?php echo $langs->trans("MulticompanyTransverseModeEnabled"); ?></div>
		</div>
	</div>
<?php }

print '</div>';

$db->free($result);

// End of page
llxFooter();
$db->close();
