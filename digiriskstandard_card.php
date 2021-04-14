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
 *   	\file       digiriskstandard_card.php
 *		\ingroup    digiriskdolibarr
 *		\brief      Page to create/edit/view digiriskstandard
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

dol_include_once('/digiriskdolibarr/class/digiriskstandard.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskstandard.lib.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');

global $db, $conf, $langs;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$object = new DigiriskStandard($db);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;

if (!$permissiontoread) accessforbidden();

/*
 * View
 */

$form        = new Form($db);
$emptyobject = new stdClass($db);

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");

digiriskHeader('', $title, $help_url, '', '', '', $morejs); ?>

<div id="cardContent" value="">

<?php // Part to show record
if ((empty($action) || ($action != 'edit' && $action != 'create')))
{
	$head = digiriskstandardPrepareHead($object);

	dol_fiche_head($head, 'standardCard', $langs->trans("Information"), -1, $object->picto);

	// Object card
	$width = 80; $cssclass = 'photoref';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	print '</table>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
