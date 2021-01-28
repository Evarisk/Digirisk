<?php
/* Copyright (C) 2019       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/admin/openinghours.php
 *	\ingroup    core
 *	\brief      Setup page to configure opening hours
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
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/class/openinghours.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (!empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->loadLangs(array("companies", "commercial", "bills", "banks", "users"));
if (!empty($conf->categorie->enabled)) $langs->load("categories");
if (!empty($conf->incoterm->enabled)) $langs->load("incoterm");
if (!empty($conf->notification->enabled)) $langs->load("mails");

$mesg = ''; $error = 0; $errors = array();

$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel     = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm	= GETPOST('confirm');

$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) $socid = $user->socid;
if (empty($socid) && $action == 'view') $action = 'create';

$societe = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($societe->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartyopeninghours', 'globalcard'));

if ($action == 'view' && $societe->fetch($socid) <= 0)
{
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$societe->getCanvas($socid);
$canvas = $societe->canvas ? $societe->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);

$permissiontoread = $user->rights->societe->lire;
$permissiontoadd = $user->rights->societe->creer; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->societe->delete || ($permissiontoadd && isset($societe->status) && $societe->status == 0);
$permissionnote = $user->rights->societe->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->societe->creer; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->societe->multidir_output[isset($societe->entity) ? $societe->entity : 1];

/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $societe, $action); // Note that $action and $societe may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{
	$object = new Openinghours($db);
	$object->element_type = $societe->element;
	$object->element_id = GETPOST('id');
	$object->status = 1;
	$object->day0 = GETPOST('day0', 'string');
	$object->day1 = GETPOST('day1', 'string');
	$object->day2 = GETPOST('day2', 'string');
	$object->day3 = GETPOST('day3', 'string');
	$object->day4 = GETPOST('day4', 'string');
	$object->day5 = GETPOST('day5', 'string');
	$object->day6 = GETPOST('day6', 'string');
	$object->create($user);

}


/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);
$object = new Openinghours($db);
$object->fetch_by_element(GETPOST('id'), $societe->element);

if ($socid > 0 && empty($societe->id))
{
	$result = $societe->fetch($socid);
	if ($result <= 0) dol_print_error('', $societe->error);
}

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $societe->name) $title = $societe->name." - ".$langs->trans('Card');
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if (!empty($societe->id)) $res = $societe->fetch_optionals();
//if ($res < 0) { dol_print_error($db); exit; }


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/digiriskdolibarr/legaldisplay_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	$head = societe_prepare_head($societe);
	dol_fiche_head($head, 'openinghours', $langs->trans("ThirdParty"), 0, 'company');
	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	dol_banner_tab($societe, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');


	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';


print '<span class="opacitymedium">'.$langs->trans("ThirdpartyOpeningHours")."</span>\n";


	//Show common fields

	//@todo insert les champs de la classe openinghours
	include DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

	dol_fiche_end();
// End of page
llxFooter();
$db->close();
