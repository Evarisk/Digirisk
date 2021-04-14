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
 *	\file       digiriskdolibarrindex.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Home page of digiriskdolibarr top menu
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Security check
//if (!$user->rights->digiriskdolibarr->read) accessforbidden();

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';

llxHeader("", $langs->trans("DigiriskDolibarrArea"), $help_url);

print load_fiche_titre($langs->trans("DigiriskDolibarrArea"), '', 'digiriskdolibarr.png@digiriskdolibarr');

// End of page
llxFooter();
$db->close();
