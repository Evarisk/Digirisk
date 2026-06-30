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
 *   	\file       view/accident/accident_list.php
 *		\ingroup    digiriskdolibarr
 *		\brief      List page for accident
 */

// Load DigiriskDolibarr environment
if (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

require_once __DIR__ . '/../../class/accident.class.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['projects', 'companies', 'commercial']);

// Get parameters
$action      = GETPOST('action', 'alpha');

$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$hookmanager->initHooks(['digiriskelementview', 'accidentlist']); // Note that conf->hooks_modules contains array

$id = GETPOST('id', 'int'); // get if for actions_fetchobject.inc.php
// Load accident object, why ?
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

//Permission for accident
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

// Security check
saturne_check_access($permissiontoread);

/*
 * Actions
 */

require_once __DIR__ . '/../../../saturne/core/tpl/actions/dashboard_actions.tpl.php';

/*
 * View
 */

$title    = $langs->trans("AccidentList");
$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Accident_b.C3.A9nins_et_presque_accidents';

if ($fromid > 0) {
    digirisk_header($title, $helpUrl);
    $objectlinked = $digiriskelement;
	$objectlinked->fetch($fromid);
    saturne_get_fiche_head($objectlinked,'elementAccidents', $langs->trans('Accident'));
} else {
    saturne_header(0, '', $title, $helpUrl);
    if ($fromiduser > 0) {
        $userObject = new User($db);
        $userObject->fetch($fromiduser, '', '', 1);
        $userObject->loadRights();
        saturne_get_fiche_head($userObject, 'accidents', $langs->trans('Accidents'));
    } elseif ($accident->id > 0) {
        saturne_get_fiche_head($object,'elementAccidents', $langs->trans('Accident'));
    }
}

$moreParams = [
    'LoadAccident'               => 1,
    'specialModuleNameLowerCase' => 'digirisk'
];

require_once __DIR__ . '/../../../saturne/class/saturnedashboard.class.php';

$dashboard = new SaturneDashboard($db, $moduleNameLowerCase);
$dashboard->show_dashboard($moreParams);

// End of page
llxFooter();
$db->close();
