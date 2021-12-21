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
 *      \file       view/digiriskusers.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view Users
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (!$user->rights->user->user->lire && !$user->admin) {
	accessforbidden();
}

// Load translation files required by page
$langs->loadLangs(array('users', 'companies', 'hrm'));

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'digiriskuserslist'; // To manage different context of search

// Security check (for external users)
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

// Load mode employee
$mode = GETPOST("mode", 'alpha');
$group = GETPOST("group", "int", 3);

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$action		= GETPOST('action', 'aZ09');

if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "u.login";
if (!$sortorder) $sortorder = "ASC";

// Define value to know what current user can do on users
$canadduser = (!empty($user->admin) || $user->rights->user->user->creer);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new User($db);
$hookmanager->initHooks(array('userlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$userstatic      = new User($db);
$companystatic   = new Societe($db);
$usergroupstatic = new UserGroup($db);
$form            = new Form($db);
$formother       = new FormOther($db);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'u.login'=>"Login",
	'u.lastname'=>"Lastname",
	'u.firstname'=>"Firstname",
	'u.accountancy_code'=>"AccountancyCode",
	'u.email'=>"EMail",
	'u.note'=>"Note",
);
if (!empty($conf->api->enabled))
{
	$fieldstosearchall['u.api_key'] = "ApiKey";
}

// Definition of fields for list
$arrayfields = array(
	'u.login'=>array('label'=>$langs->trans("Login"), 'checked'=>1),
	'u.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
	'u.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
	'u.gender'=>array('label'=>$langs->trans("Gender"), 'checked'=>0),
	'u.employee'=>array('label'=>$langs->trans("Employee"), 'checked'=>($mode == 'employee' ? 1 : 0)),
	'u.accountancy_code'=>array('label'=>$langs->trans("AccountancyCode"), 'checked'=>0),
	'u.email'=>array('label'=>$langs->trans("EMail"), 'checked'=>1),
	'u.api_key'=>array('label'=>$langs->trans("ApiKey"), 'checked'=>0, "enabled"=>($conf->api->enabled && $user->admin)),
	'u.fk_soc'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
	'u.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(!empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))),
	'g.fk_usergroup'=>array('label'=>$langs->trans("UserGroup"), 'checked'=>1),
	'u.fk_user'=>array('label'=>$langs->trans("HierarchicalResponsible"), 'checked'=>1),
	'u.datelastlogin'=>array('label'=>$langs->trans("LastConnexion"), 'checked'=>1, 'position'=>100),
	'u.datepreviouslogin'=>array('label'=>$langs->trans("PreviousConnexion"), 'checked'=>0, 'position'=>110),
	'u.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'u.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'u.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Init search fields
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_user = GETPOST('search_user', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_lastname = GETPOST('search_lastname', 'alpha');
$search_firstname = GETPOST('search_firstname', 'alpha');
$search_gender = GETPOST('search_gender', 'alpha');
$search_employee = GETPOST('search_employee', 'alpha');
$search_accountancy_code = GETPOST('search_accountancy_code', 'alpha');
$search_email = GETPOST('search_email', 'alpha');
$search_api_key = GETPOST('search_api_key', 'alphanohtml');
$search_statut = GETPOST('search_statut', 'intcomma');
$search_thirdparty = GETPOST('search_thirdparty', 'alpha');
$search_supervisor = GETPOST('search_supervisor', 'intcomma');
$optioncss = GETPOST('optioncss', 'alpha');
$search_categ = GETPOST("search_categ", 'int');
$catid = GETPOST('catid', 'int');
//echo '<pre>';
//print_r(GETPOST('action'));
//print_r((GETPOST('lastname')));
//echo '</pre>';
//exit;
// Default search
if ($search_statut == '') $search_statut = '1';
if ($mode == 'employee' && !GETPOSTISSET('search_employee')) $search_employee = 1;



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_user = "";
		$search_login = "";
		$search_lastname = "";
		$search_firstname = "";
		$search_gender = "";
		$search_employee = "";
		$search_accountancy_code = "";
		$search_email = "";
		$search_statut = "";
		$search_thirdparty = "";
		$search_supervisor = "";
		$search_api_key = "";
		$search_datelastlogin = "";
		$search_datepreviouslogin = "";
		$search_date_creation = "";
		$search_date_update = "";
		$search_array_options = array();
		$search_categ = 0;
	}

}
// Action Add user
if ($action == 'add' && $canadduser) {
	$error = 0;

	if (!$_POST["lastname"]) {
		$error++;
		setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
		$action = "create"; // Go back to create page
	}
	if (!$_POST["login"]) {
		$login = GETPOST('lastname') . GETPOST('firstname');
	}

	if (!empty($conf->file->main_limit_users)) { // If option to limit users is set
		$nb = $object->getNbOfUsers("active");
		if ($nb >= $conf->file->main_limit_users) {
			$error++;
			setEventMessages($langs->trans("YourQuotaOfUsersIsReached"), null, 'errors');
			$action = "create"; // Go back to create page
		}
	}

	if (!$error) {
		$object->lastname = GETPOST("lastname", 'alphanohtml');
		$object->firstname = GETPOST("firstname", 'alphanohtml');
		$object->login = $login;
		$object->api_key = GETPOST("api_key", 'alphanohtml');
		$object->gender = GETPOST("gender", 'aZ09');
		$birth = dol_mktime(0, 0, 0, GETPOST('birthmonth', 'int'), GETPOST('birthday', 'int'), GETPOST('birthyear', 'int'));
		$object->birth = $birth;
		$object->admin = GETPOST("admin", 'int');
		$object->address = GETPOST('address', 'alphanohtml');
		$object->zip = GETPOST('zipcode', 'alphanohtml');
		$object->town = GETPOST('town', 'alphanohtml');
		$object->country_id = GETPOST('country_id', 'int');
		$object->state_id = GETPOST('state_id', 'int');
		$object->office_phone = GETPOST("office_phone", 'alphanohtml');
		$object->office_fax = GETPOST("office_fax", 'alphanohtml');
		$object->user_mobile = GETPOST("user_mobile", 'alphanohtml');

		//$object->skype = GETPOST("skype", 'alphanohtml');
		//$object->twitter = GETPOST("twitter", 'alphanohtml');
		//$object->facebook = GETPOST("facebook", 'alphanohtml');
		//$object->linkedin = GETPOST("linkedin", 'alphanohtml');
		$object->socialnetworks = array();
		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
			}
		}

		$object->email = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
		$object->job = GETPOST("job", 'nohtml');
		$object->signature = GETPOST("signature", 'none');
		$object->accountancy_code = GETPOST("accountancy_code", 'alphanohtml');
		$object->note = GETPOST("note", 'none');
		$object->ldap_sid = GETPOST("ldap_sid", 'alphanohtml');
		$object->fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", 'int') : 0;
		$object->fk_user_expense_validator = GETPOST("fk_user_expense_validator", 'int') > 0 ? GETPOST("fk_user_expense_validator", 'int') : 0;
		$object->fk_user_holiday_validator = GETPOST("fk_user_holiday_validator", 'int') > 0 ? GETPOST("fk_user_holiday_validator", 'int') : 0;
		$object->employee = GETPOST('employee', 'alphanohtml');

		$object->thm = GETPOST("thm", 'alphanohtml') != '' ? GETPOST("thm", 'alphanohtml') : '';
		$object->thm = price2num($object->thm);
		$object->tjm = GETPOST("tjm", 'alphanohtml') != '' ? GETPOST("tjm", 'alphanohtml') : '';
		$object->tjm = price2num($object->tjm);
		$object->salary = GETPOST("salary", 'alphanohtml') != '' ? GETPOST("salary", 'alphanohtml') : '';
		$object->salary = price2num($object->salary);
		$object->salaryextra = GETPOST("salaryextra", 'alphanohtml') != '' ? GETPOST("salaryextra", 'alphanohtml') : '';
		$object->weeklyhours = GETPOST("weeklyhours", 'alphanohtml') != '' ? GETPOST("weeklyhours", 'alphanohtml') : '';

		$object->color = GETPOST("color", 'alphanohtml') != '' ? GETPOST("color", 'alphanohtml') : '';
		$dateemployment = dol_mktime(0, 0, 0, GETPOST('dateemploymentmonth', 'int'), GETPOST('dateemploymentday', 'int'), GETPOST('dateemploymentyear', 'int'));
		$object->dateemployment = $dateemployment;

		$dateemploymentend = dol_mktime(0, 0, 0, GETPOST('dateemploymentendmonth', 'int'), GETPOST('dateemploymentendday', 'int'), GETPOST('dateemploymentendyear', 'int'));
		$object->dateemploymentend = $dateemploymentend;

		$object->fk_warehouse = GETPOST('fk_warehouse', 'int');

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}

		// Set entity property
		$entity = GETPOST('entity', 'int');
		if (!empty($conf->multicompany->enabled)) {
			if (GETPOST('superadmin', 'int')) {
				$object->entity = 0;
			} else {
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$object->entity = 1; // all users are forced into master entity
				} else {
					$object->entity = ($entity == '' ? 1 : $entity);
				}
			}
		} else {
			$object->entity = ($entity == '' ? 1 : $entity);
			/*if ($user->admin && $user->entity == 0 && GETPOST("admin",'alpha'))
			{
			}*/
		}

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
					if ($object->send_password($user, $newpassword) > 0)
					{
						setEventMessages($langs->trans("UserCreated", $object->email), null, 'mesgs');
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				} else {
					setEventMessages($langs->trans("UserCreated", $newpassword), null, 'mesgs');
				}
			}

			$db->commit();

			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
			exit;
		}
		else
		{
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

$htmlother = new FormOther($db);

$user2 = new User($db);


$sql = "SELECT DISTINCT u.rowid, u.lastname, u.firstname, u.admin, u.fk_soc, u.login, u.email, u.api_key, u.accountancy_code, u.gender, u.employee, u.photo,";
$sql .= " u.datelastlogin, u.datepreviouslogin,";
$sql .= " u.ldap_sid, u.statut, u.entity,";
$sql .= " u.tms as date_update, u.datec as date_creation,";
$sql .= " u2.rowid as id2, u2.login as login2, u2.firstname as firstname2, u2.lastname as lastname2, u2.admin as admin2, u2.fk_soc as fk_soc2, u2.email as email2, u2.gender as gender2, u2.photo as photo2, u2.entity as entity2, u2.statut as statut2,";
$sql .= " s.nom as name, s.canvas,";
$sql .= " g.fk_usergroup as fk_usergroup";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (u.rowid = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON u.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u2 ON u.fk_user = u2.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as g ON u.rowid = g.fk_user";
if (!empty($search_categ) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_user as cu ON u.rowid = cu.fk_user"; // We'll need this table joined to the select in order to filter by categ
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printUserListWhere', $parameters); // Note that $action and $object may have been modified by hook
if ($reshook > 0) {
	$sql .= $hookmanager->resPrint;
} else {
	$sql .= " WHERE u.entity IN (".getEntity('user').")";
}
if ($socid > 0) $sql .= " AND u.fk_soc = ".$socid;
//if ($search_user != '')       $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
if ($search_supervisor > 0)   $sql .= " AND u.fk_user IN (".$db->escape($search_supervisor).")";
if ($search_thirdparty != '') $sql .= natural_search(array('s.nom'), $search_thirdparty);
if ($search_login != '')      $sql .= natural_search("u.login", $search_login);
if ($search_lastname != '')   $sql .= natural_search("u.lastname", $search_lastname);
if ($search_firstname != '')  $sql .= natural_search("u.firstname", $search_firstname);
if ($search_gender != '' && $search_gender != '-1')     $sql .= " AND u.gender = '".$db->escape($search_gender)."'";	// Cannot use natural_search as looking for %man% also includes woman
if (is_numeric($search_employee) && $search_employee >= 0) {
	$sql .= ' AND u.employee = '.(int) $search_employee;
}
if ($search_accountancy_code != '')  $sql .= natural_search("u.accountancy_code", $search_accountancy_code);
if ($search_email != '')             $sql .= natural_search("u.email", $search_email);
if ($search_api_key != '')           $sql .= natural_search("u.api_key", $search_api_key);
if ($search_statut != '' && $search_statut >= 0) $sql .= " AND u.statut IN (".$db->escape($search_statut).")";
if ($sall)                           $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($catid > 0)     $sql .= " AND cu.fk_categorie = ".$catid;
if ($catid == -2)   $sql .= " AND cu.fk_categorie IS NULL";
if ($search_categ > 0)   $sql .= " AND cu.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2) $sql .= " AND cu.fk_categorie IS NULL";
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = 0;
$result = $db->query($sql);
if ($result)
{
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if (!$result)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($result);

if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/user/card.php?id='.$id);
	exit;
}

require_once './../lib/digiriskdolibarr_function.lib.php';

$title    = $langs->trans("ListOfUsers");
$help_url = '';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '','','',$morejs,$morecss);

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&amp;contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&amp;limit='.urlencode($limit);
if ($sall != '') $param .= '&amp;sall='.urlencode($sall);
if ($search_user != '') $param .= "&amp;search_user=".urlencode($search_user);
if ($search_login != '') $param .= "&amp;search_login=".urlencode($search_login);
if ($search_lastname != '') $param .= "&amp;search_lastname=".urlencode($search_lastname);
if ($search_firstname != '') $param .= "&amp;search_firstname=".urlencode($search_firstname);
if ($search_gender != '') $param .= "&amp;search_gender=".urlencode($search_gender);
if ($search_employee != '') $param .= "&amp;search_employee=".urlencode($search_employee);
if ($search_accountancy_code != '') $param .= "&amp;search_accountancy_code=".urlencode($search_accountancy_code);
if ($search_email != '') $param .= "&amp;search_email=".urlencode($search_email);
if ($search_api_key != '') $param .= "&amp;search_api_key=".urlencode($search_api_key);
if ($search_supervisor > 0) $param .= "&amp;search_supervisor=".urlencode($search_supervisor);
if ($search_statut != '') $param .= "&amp;search_statut=".urlencode($search_statut);
if ($optioncss != '') $param .= '&amp;optioncss='.urlencode($optioncss);
if ($mode != '')      $param .= '&amp;mode='.urlencode($mode);
if ($search_categ > 0) $param .= "&amp;search_categ=".urlencode($search_categ);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$text = $langs->trans("ListOfUsers");

$newcardbutton = '';

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($text, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $nbtotalofrecords, 'user', 0, ''.' '.$newcardbutton, '', $limit, 0, 0, 1);

if (!empty($catid))
{
	print "<div id='ways'>";
	$c = new Categorie($db);
	$ways = $c->print_all_ways(' &gt; ', 'user/list.php');
	print " &gt; ".$ways[0]."<br>\n";
	print "</div><br>";
}

if ($sall)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// Filter on categories
if (!empty($conf->categorie->enabled))
{
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Categories').': ';
	$moreforfilter .= $htmlother->select_categories(Categorie::TYPE_USER, $search_categ, 'search_categ', 1);
	$moreforfilter .= '</div>';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if ($moreforfilter)
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields


print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Search bar
print '<tr class="liste_titre_filter">';
if (!empty($arrayfields['u.login']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="'.$search_login.'"></td>';
}
if (!empty($arrayfields['u.lastname']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_lastname" class="maxwidth50" value="'.$search_lastname.'"></td>';
}
if (!empty($arrayfields['u.firstname']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_firstname" class="maxwidth50" value="'.$search_firstname.'"></td>';
}
if (!empty($arrayfields['u.gender']['checked']))
{
	print '<td class="liste_titre">';
	$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"));
	print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
	print '</td>';
}
if (!empty($arrayfields['u.employee']['checked']))
{
	print '<td class="liste_titre">';
	print $form->selectyesno('search_employee', $search_employee, 1, false, 1);
	print '</td>';
}
if (!empty($arrayfields['u.accountancy_code']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_accountancy_code" class="maxwidth50" value="'.$search_accountancy_code.'"></td>';
}
if (!empty($arrayfields['u.email']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_email" class="maxwidth75" value="'.$search_email.'"></td>';
}
if (!empty($arrayfields['u.api_key']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_api_key" class="maxwidth50" value="'.$search_api_key.'"></td>';
}
if (!empty($arrayfields['u.fk_soc']['checked']))
{
	print '<td class="liste_titre"><input type="text" name="search_thirdparty" class="maxwidth75" value="'.$search_thirdparty.'"></td>';
}
if (!empty($arrayfields['u.entity']['checked']))
{
	print '<td class="liste_titre"></td>';
}
// Supervisor
if (!empty($arrayfields['u.fk_user']['checked']))
{
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_supervisor, 'search_supervisor', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'maxwidth200');
	print '</td>';
}
if (!empty($arrayfields['u.datelastlogin']['checked']))
{
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['u.datepreviouslogin']['checked']))
{
	print '<td class="liste_titre"></td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['u.datec']['checked']))
{
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['u.tms']['checked']))
{
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['u.statut']['checked']))
{
	// Status
	print '<td class="liste_titre center">';
	print $form->selectarray('search_statut', array('-1'=>'', '0'=>$langs->trans('Disabled'), '1'=>$langs->trans('Enabled')), $search_statut);
	print '</td>';
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';

print "</tr>\n";


print '<tr class="liste_titre">';
if (!empty($arrayfields['u.login']['checked']))          print_liste_field_titre("Login", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.lastname']['checked']))       print_liste_field_titre("Lastname", $_SERVER['PHP_SELF'], "u.lastname", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.firstname']['checked']))      print_liste_field_titre("FirstName", $_SERVER['PHP_SELF'], "u.firstname", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.gender']['checked']))         print_liste_field_titre("Gender", $_SERVER['PHP_SELF'], "u.gender", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.employee']['checked']))       print_liste_field_titre("Employee", $_SERVER['PHP_SELF'], "u.employee", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.accountancy_code']['checked'])) print_liste_field_titre("AccountancyCode", $_SERVER['PHP_SELF'], "u.accountancy_code", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.email']['checked']))          print_liste_field_titre("EMail", $_SERVER['PHP_SELF'], "u.email", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.api_key']['checked']))        print_liste_field_titre("ApiKey", $_SERVER['PHP_SELF'], "u.api_key", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.fk_soc']['checked']))         print_liste_field_titre("Company", $_SERVER['PHP_SELF'], "u.fk_soc", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.entity']['checked']))         print_liste_field_titre("Entity", $_SERVER['PHP_SELF'], "u.entity", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['g.fk_usergroup']['checked']))         print_liste_field_titre("UserGroup", $_SERVER['PHP_SELF'], "g.fk_usergroup", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.fk_user']['checked']))        print_liste_field_titre("HierarchicalResponsible", $_SERVER['PHP_SELF'], "u.fk_user", $param, "", "", $sortfield, $sortorder);
if (!empty($arrayfields['u.datelastlogin']['checked']))  print_liste_field_titre("LastConnexion", $_SERVER['PHP_SELF'], "u.datelastlogin", $param, "", '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['u.datepreviouslogin']['checked'])) print_liste_field_titre("PreviousConnexion", $_SERVER['PHP_SELF'], "u.datepreviouslogin", $param, "", '', $sortfield, $sortorder, 'center ');
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['u.datec']['checked']))  print_liste_field_titre("DateCreationShort", $_SERVER["PHP_SELF"], "u.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['u.tms']['checked']))    print_liste_field_titre("DateModificationShort", $_SERVER["PHP_SELF"], "u.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['u.statut']['checked'])) print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "u.statut", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";



$i = 0;
$totalarray = array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($result);

	$userstatic->id = $obj->rowid;
	$userstatic->admin = $obj->admin;
	$userstatic->ref = $obj->label;
	$userstatic->login = $obj->login;
	$userstatic->statut = $obj->statut;
	$userstatic->email = $obj->email;
	$userstatic->gender = $obj->gender;
	$userstatic->socid = $obj->fk_soc;
	$userstatic->firstname = $obj->firstname;
	$userstatic->lastname = $obj->lastname;
	$userstatic->employee = $obj->employee;
	$userstatic->photo = $obj->photo;

	$li = $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);

	print "<tr>";
	if (!empty($arrayfields['u.login']['checked']))
	{
		print '<td class="nowraponall">';
		print $li;
		if (!empty($conf->multicompany->enabled) && $obj->admin && !$obj->entity)
		{
			print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
		}
		elseif ($obj->admin)
		{
			print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.lastname']['checked']))
	{
		print '<td>'.$obj->lastname.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.firstname']['checked']))
	{
		print '<td>'.$obj->firstname.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.gender']['checked']))
	{
		print '<td>';
		if ($obj->gender) print $langs->trans("Gender".$obj->gender);
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.employee']['checked']))
	{
		print '<td>'.yn($obj->employee).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.accountancy_code']['checked']))
	{
		print '<td>'.$obj->accountancy_code.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.email']['checked']))
	{
		print '<td>'.$obj->email.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.api_key']['checked']))
	{
		print '<td>'.$obj->api_key.'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	if (!empty($arrayfields['u.fk_soc']['checked']))
	{
		print "<td>";
		if ($obj->fk_soc)
		{
			$companystatic->id = $obj->fk_soc;
			$companystatic->name = $obj->name;
			$companystatic->canvas = $obj->canvas;
			print $companystatic->getNomUrl(1);
		}
		elseif ($obj->ldap_sid)
		{
			print $langs->trans("DomainUser");
		}
		else
		{
			print $langs->trans("InternalUser");
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Multicompany enabled
	if (!empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
	{
		if (!empty($arrayfields['u.entity']['checked']))
		{
			print '<td>';
			if (!$obj->entity)
			{
				print $langs->trans("AllEntities");
			}
			else
			{
				$mc->getInfo($obj->entity);
				print $mc->label;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['g.fk_usergroup']['checked'])) {
		print '<td>';
		if ($obj->fk_usergroup)
		{
			$usergroupstatic->id = $obj->fk_usergroup;
			$usergroupstatic->fetch($usergroupstatic->id);
			print $usergroupstatic->getNomUrl(1);
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Supervisor
	if (!empty($arrayfields['u.fk_user']['checked']))
	{
		// Resp
		print '<td class="nowrap">';
		if ($obj->login2)
		{
			$user2->id = $obj->id2;
			$user2->login = $obj->login2;
			$user2->lastname = $obj->lastname2;
			$user2->firstname = $obj->firstname2;
			$user2->gender = $obj->gender2;
			$user2->photo = $obj->photo2;
			$user2->admin = $obj->admin2;
			$user2->email = $obj->email2;
			$user2->socid = $obj->fk_soc2;
			$user2->statut = $obj->statut2;
			print $user2->getNomUrl(-1, '', 0, 0, 24, 0, '', '', 1);
			if (!empty($conf->multicompany->enabled) && $obj->admin2 && !$obj->entity2)
			{
				print img_picto($langs->trans("SuperAdministrator"), 'redstar', 'class="valignmiddle paddingleft"');
			}
			elseif ($obj->admin2)
			{
				print img_picto($langs->trans("Administrator"), 'star', 'class="valignmiddle paddingleft"');
			}
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Date last login
	if (!empty($arrayfields['u.datelastlogin']['checked']))
	{
		print '<td class="nowrap center">'.dol_print_date($db->jdate($obj->datelastlogin), "dayhour").'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Date previous login
	if (!empty($arrayfields['u.datepreviouslogin']['checked']))
	{
		print '<td class="nowrap center">'.dol_print_date($db->jdate($obj->datepreviouslogin), "dayhour").'</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['u.datec']['checked']))
	{
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Date modification
	if (!empty($arrayfields['u.tms']['checked']))
	{
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Status
	if (!empty($arrayfields['u.statut']['checked']))
	{
		$userstatic->statut = $obj->statut;
		print '<td class="center">'.$userstatic->getLibStatut(5).'</td>';
		if (!$i) $totalarray['nbfield']++;
	}
	// Action column
	print '<td></td>';
	if (!$i) $totalarray['nbfield']++;

	print "</tr>\n";

	$i++;
}

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";

print "</form>\n";

if ($canadduser) {
	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if (!empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="'.dol_escape_htmltag($ldap_sid).'">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

	$generated_password = getRandomPassword(false);
	$password = (GETPOSTISSET('password') ? GETPOST('password') : $generated_password);

	?>
	<div class="digirisk-wrap wpeo-wrap digirisk-users" style="padding-right: 0 !important;">
		<div class="main-container" style="width:auto;  margin-top:0 !important; padding-left:0 !important;">
			<div class="wpeo-tab">
				<div class="tab-container">
					<div class="tab-content tab-active" style="padding:0 !important">
						<div class="wpeo-table table-flex table-risk">
							<div class="table-row user-row edit">
								<input type="hidden" name="action" value="add" />
								<input type="hidden" class="input-domain-mail" name="societyname" value="<?php echo preg_replace('/ /', '',$conf->global->MAIN_INFO_SOCIETE_NOM) . '.fr' ?>" />
								<div class="table-cell table-150">
									<input type="text" id="lastname" placeholder="<?php echo $langs->trans('LastName'); ?>" name="lastname" value="<?php dol_escape_htmltag(GETPOST('lastname', 'alphanohtml'))?>" />
								</div>
								<div class="table-cell table-150">
									<input type="text" id="firstname" placeholder="<?php echo $langs->trans('FirstName'); ?>" name="firstname" value="<?php dol_escape_htmltag(GETPOST('firstname', 'alphanohtml'))?>" />
								</div>
								<div class="table-cell table-300">
									<input style="width:100%" type="text" id="email" placeholder="<?php echo $langs->trans('Email'); ?>" name="email" value="" />
								</div>
								<div class="table-cell table-300">
									<input style="width:100%" type="text" id="password" placeholder="<?php echo $langs->trans('Password'); ?>" name="password" value="<?php echo $password ?>" autocomplete="new-password" />
								</div>
								<div class="table-cell table-300">
									<?php print $form->select_dolgroups($conf->global->DIGIRISKDOLIBARR_USERGROUP_SET, 'group', 1, '', 0, '', '', $conf->entity); ?>
								</div>
								<div class="table-cell table-400">
									<?php print '<label style="margin-left: 50px" for="send_password">'.$langs->trans("SendPassword").'</label>';
									print '<input type="checkbox" id="send_password" name="send_password" style="text-align: right">'; ?>
								</div>
								<div class="table-cell">
									<button type="submit" name="create" style="color: #3495f0; background-color: transparent; width:30%; border:none; margin-right:30%;">
										<div class="wpeo-button button-square-50 button-event add action-input button-progress">
											<i class="button-icon fas fa-plus"></i>
										</div>
									</button>
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
}

print '</div>';

$db->free($result);

// End of page
llxFooter();
$db->close();
