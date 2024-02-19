<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/config/digiriskelement.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr digiriskelement page.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $moduleNameLowerCase, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskelement/workunit.class.php';
require_once __DIR__ . '/../../class/digiriskelement/groupment.class.php';

// Translations
saturne_load_langs(["admin"]);

// Technical objects
$digiriskelement = new DigiriskElement($db);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Actions update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

if ($action == 'setmod') {
    $value = GETPOST('value');
    $valueArray = explode('_', $value);
    $objectType = $valueArray[1];

    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. strtoupper($objectType) .'_ADDON', $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_Digirisk#L.27onglet_.C3.89l.C3.A9ment_DigiRisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'digiriskelement', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

$pictos = $digiriskelement->getPicto();

/*
 *  Numbering module
 */

$objectModSubdir = 'digiriskelement';

$object = new Groupment($db);

print load_fiche_titre($pictos['groupment'] . $langs->trans('GroupmentManagement'), '', '');
print '<hr>';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

$object = new WorkUnit($db);

print load_fiche_titre($pictos['workunit'] . $langs->trans('WorkUnitManagement'), '', '');
print '<hr>';

require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php';

/*
 *  Deleted elements
 */

print load_fiche_titre('<i class="fas fa-trash"></i> ' . $langs->trans('DeletedElements'), '', '');
print '<hr>';

$constArray[$moduleNameLowerCase] = [
	'DeletedDigiriskElement' => [
		'name'        => 'DeletedDigiriskElement',
		'description' => 'ShowDeletedDigiriskElement',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT',
	],
];
require __DIR__ . '/../../../saturne/core/tpl/admin/object/object_const_view.tpl.php';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
