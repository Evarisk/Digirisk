<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 *   	\file       preventionplan_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view preventionplan
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

dol_include_once('/digiriskdolibarr/class/digiriskdocuments.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskresources.class.php');
dol_include_once('/digiriskdolibarr/class/preventionplan.class.php');
dol_include_once('/digiriskdolibarr/class/riskanalysis/risk.class.php');
dol_include_once('/digiriskdolibarr/class/digiriskdocuments/preventionplandocument.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_preventionplan.lib.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/preventionplan/mod_preventionplan_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/preventionplandet/mod_preventionplandet_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/mod_preventionplandocument_standard.php');
dol_include_once('/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/preventionplandocument/modules_preventionplandocument.php');

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id                  = GETPOST('id', 'int');
$lineid                  = GETPOST('lineid', 'int');
$ref                 = GETPOST('ref', 'alpha');
$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'preventionplancard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$preventionplan                 = new PreventionPlan($db);
$object = new Openinghours($db);

$preventionplan->fetch($id);

$digiriskelement   = new DigiriskElement($db);
$digiriskresources = new DigiriskResources($db);

$refPreventionPlanMod = new $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON($db);
$refPreventionPlanDetMod = new  $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLANDET_ADDON($db);

$hookmanager->initHooks(array('preventionplancard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($preventionplan->entity) ? $preventionplan->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->preventionplandocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->preventionplandocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->preventionplandocument->delete;

$morewhere = ' AND element_id = ' . GETPOST('id');
$morewhere .= ' AND element_type = ' . "'" . $preventionplan->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

if (!$permissiontoread) accessforbidden();
/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $preventionplan, $action); // Note that $action and $preventionplan may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{
	$object->element_type = $preventionplan->element;
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
}


/*
 *  View
 */

$title = $langs->trans("PreventionPlan");
$help_url = 'EN:Module_Third_Parties|FR:Module_DigiriskDolibarr#L.27onglet_Horaire_de_travail|ES:Empresas';
llxHeader('', $title, $help_url);

if (!empty($preventionplan->id)) $res = $preventionplan->fetch_optionals();

// Object card
// ------------------------------------------------------------
$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';

$head = preventionplanPrepareHead($preventionplan);
dol_fiche_head($head, 'preventionplanSchedule', $langs->trans("PreventionPlan"), 0, '');
dol_banner_tab($preventionplan, 'ref', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

print '<span class="opacitymedium">'.$langs->trans("PreventionPlanSchedule")."</span>\n";

//Show common fields
include DOL_DOCUMENT_ROOT.'/custom/digiriskdolibarr/core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

dol_fiche_end();
// End of page
llxFooter();
$db->close();
