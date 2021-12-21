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
 *   	\file       view/digiriskstandard/digiriskstandard_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiriskstandard
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
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './../../class/digiriskstandard.class.php';
require_once './../../core/boxes/box_riskassessmentdocument.php';
require_once './../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';

global $db, $conf, $langs, $user,  $maxwidthmini, $maxheightmini, $maxwidthsmall,$maxheightsmall;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$object = new DigiriskStandard($db);
$box    = new box_riskassessmentdocument($db);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

// Security check
$permissiontoread = $user->rights->digiriskdolibarr->riskassessmentdocument->read;

if (!$permissiontoread) accessforbidden();

/*
 * View
 */

$emptyobject = new stdClass($db);

$title    = $langs->trans("DigiriskStandardInformation");
$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss); ?>

<div id="cardContent" value="">

<?php // Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = digiriskstandardPrepareHead($object);

	print dol_get_fiche_head($head, 'standardCard', $langs->trans("Information"), -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	$width = 80; $cssclass = 'photoref';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 1, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter">';
	print '<br>';

	$box->loadBox();
	$box->showBox();

	print '<div class="fichecenter">';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
