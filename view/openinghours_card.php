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
 *   	\file       view/openinghours_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to view Opening Hours
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './../class/openinghours.class.php';

$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

$action = (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');

$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) $socid = $user->socid;
if (empty($socid) && $action == 'view') $action = 'create';

$societe = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartyopeninghours', 'globalcard'));

if ($action == 'view' && $societe->fetch($socid) <= 0) {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$societe->getCanvas($socid);
$canvas = $societe->canvas ? $societe->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$permissiontoadd = $user->rights->societe->creer;
restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);

/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $societe, $action); // Note that $action and $societe may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$object = new Openinghours($db);
	$object->element_type = $societe->element;
	$object->element_id = GETPOST('id');
	$object->status = 1;
	$object->monday = GETPOST('monday', 'string');
	$object->tuesday = GETPOST('tuesday', 'string');
	$object->wednesday = GETPOST('wednesday', 'string');
	$object->thursday = GETPOST('thursday', 'string');
	$object->friday = GETPOST('friday', 'string');
	$object->saturday = GETPOST('saturday', 'string');
	$object->sunday = GETPOST('sunday', 'string');
	$object->create($user);
	setEventMessages($langs->trans('ThirdPartyOpeningHoursSave'), null, 'mesgs');
}


/*
 *  View
 */

$object = new Openinghours($db);

$morewhere = ' AND element_id = ' . GETPOST('id');
$morewhere .= ' AND element_type = ' . "'" . $societe->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

if ($socid > 0 && empty($societe->id)) {
	$result = $societe->fetch($socid);
	if ($result <= 0) dol_print_error('', $societe->error);
}

$title = $langs->trans("ThirdParty");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $societe->name) $title = $societe->name." - ".$langs->trans('OpeningHours');
$help_url = 'EN:Module_Third_Parties|FR:Module_DigiriskDolibarr#L.27onglet_Horaire_de_travail|ES:Empresas';
llxHeader('', $title, $help_url);

if (!empty($societe->id)) $res = $societe->fetch_optionals();

// Object card
// ------------------------------------------------------------
$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';

$head = societe_prepare_head($societe);
print dol_get_fiche_head($head, 'openinghours', $langs->trans("ThirdParty"), 0, 'company');
$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($societe, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

print '<span class="opacitymedium">'.$langs->trans("ThirdPartyOpeningHours")."</span>\n";

//Show common fields
require_once __DIR__ . '/../core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

print dol_get_fiche_end();
// End of page
llxFooter();
$db->close();
