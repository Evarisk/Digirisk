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
 *   	\file       firepermit_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view firepermit
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

require_once __DIR__ . '/class/digiriskdocuments.class.php';
require_once __DIR__ . '/class/digiriskelement.class.php';
require_once __DIR__ . '/class/digiriskresources.class.php';
require_once __DIR__ . '/class/firepermit.class.php';
require_once __DIR__ . '/class/riskanalysis/risk.class.php';
require_once __DIR__ . '/class/digiriskdocuments/firepermitdocument.class.php';
require_once __DIR__ . '/lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/lib/digiriskdolibarr_firepermit.lib.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/firepermit/mod_firepermit_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskelement/firepermitdet/mod_firepermitdet_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/firepermitdocument/mod_firepermitdocument_standard.php';
require_once __DIR__ . '/core/modules/digiriskdolibarr/digiriskdocuments/firepermitdocument/modules_firepermitdocument.php';

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
$contextpage         = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'firepermitcard'; // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$fk_parent           = GETPOST('fk_parent', 'int');

// Initialize technical objects
$firepermit                 = new FirePermit($db);
$object = new Openinghours($db);

$firepermit->fetch($id);

$digiriskelement   = new DigiriskElement($db);
$digiriskresources = new DigiriskResources($db);

$refFirePermitMod = new $conf->global->DIGIRISKDOLIBARR_FIREPERMIT_ADDON($db);
$refFirePermitDetMod = new  $conf->global->DIGIRISKDOLIBARR_FIREPERMITDET_ADDON($db);

$hookmanager->initHooks(array('firepermitcard', 'globalcard')); // Note that conf->hooks_modules contains array

$upload_dir         = $conf->digiriskdolibarr->multidir_output[isset($firepermit->entity) ? $firepermit->entity : 1];
$permissiontoread   = $user->rights->digiriskdolibarr->firepermitdocument->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->firepermitdocument->write;
$permissiontodelete = $user->rights->digiriskdolibarr->firepermitdocument->delete;

$morewhere = ' AND element_id = ' . GETPOST('id');
$morewhere .= ' AND element_type = ' . "'" . $firepermit->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

if (!$permissiontoread) accessforbidden();
/*
/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $firepermit, $action); // Note that $action and $firepermit may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
	|| ($action == 'updateedit'))
{
	$object->element_type = $firepermit->element;
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

$title = $langs->trans("FirePermit");
$help_url = 'EN:Module_Third_Parties|FR:Module_DigiriskDolibarr#L.27onglet_Horaire_de_travail|ES:Empresas';
llxHeader('', $title, $help_url);

if (!empty($firepermit->id)) $res = $firepermit->fetch_optionals();

// Object card
// ------------------------------------------------------------
$morehtmlref = '<div class="refidno">';
$morehtmlref .= '</div>';

$head = firepermitPrepareHead($firepermit);
dol_fiche_head($head, 'firepermitSchedule', $langs->trans("FirePermit"), 0, '');
dol_banner_tab($firepermit, 'ref', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

print '<span class="opacitymedium">'.$langs->trans("FirePermitSchedule")."</span>\n";

//Show common fields
require_once './core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

dol_fiche_end();
// End of page
llxFooter();
$db->close();
