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
 * \file    digiriskdolibarr/admin/digiriskdocuments.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr digiriskdocuments page.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php');

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$help_url  = 'FR:Module_DigiriskDolibarr';
$page_name = "DigiriskdolibarrSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'digiriskdocuments', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminDigiriskDocumentsPrepareHead();
dol_fiche_head($head, '', '', -1, "digiriskdolibarr@digiriskdolibarr");

// Page end
dol_fiche_end();

llxFooter();
$db->close();

