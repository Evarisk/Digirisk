<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../class/openinghours.class.php';

global $db, $conf, $langs, $user, $hookmanager;

// Load translation files required by the page
saturne_load_langs();

$action = (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');

$socid                                          = GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) $socid                        = $user->socid;
if (empty($socid) && $action == 'view') $action = 'create';

$societe = new Societe($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartyopeninghours', 'globalcard'));

if ($action == 'view' && $societe->fetch($socid) <= 0) {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Security check - Protection if external user
$permissiontoadd = $user->rights->societe->creer;
saturne_check_access($permissiontoadd);

/*
/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $societe, $action); // Note that $action and $societe may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$object               = new Openinghours($db);
	$object->element_type = $societe->element;
	$object->element_id   = GETPOST('id');
	$object->status       = 1;
	$object->monday       = GETPOST('monday', 'string');
	$object->tuesday      = GETPOST('tuesday', 'string');
	$object->wednesday    = GETPOST('wednesday', 'string');
	$object->thursday     = GETPOST('thursday', 'string');
	$object->friday       = GETPOST('friday', 'string');
	$object->saturday     = GETPOST('saturday', 'string');
	$object->sunday       = GETPOST('sunday', 'string');
	$object->create($user);
	setEventMessages($langs->trans('ThirdPartyOpeningHoursSave'), null,);
}


/*
 *  View
 */

$object = new Openinghours($db);

$morewhere  = ' AND element_id = ' . GETPOST('id');
$morewhere .= ' AND element_type = ' . "'" . $societe->element . "'";
$morewhere .= ' AND status = 1';

$object->fetch(0, '', $morewhere);

if ($socid > 0 && empty($societe->id)) {
	$result = $societe->fetch($socid);
	if ($result <= 0) dol_print_error('', $societe->error);
}

$title = $langs->trans("ThirdParty");
if ( ! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $societe->name) $title = $societe->name . " - " . $langs->trans('OpeningHours');
$helpUrl = 'FR:Module_Digirisk#L.27onglet_Horaire_d.27ouverture';

saturne_header(0,'', $title, $helpUrl);

$societe->fetch_optionals();

// Object card
// ------------------------------------------------------------

print saturne_get_fiche_head($societe, 'openinghours', $title);

$linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

saturne_banner_tab($societe, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print dol_get_fiche_end();

print load_fiche_titre($langs->trans("ThirdPartyOpeningHours"), '', '');

//Show common fields
require_once __DIR__ . '/../core/tpl/digiriskdolibarr_openinghours_view.tpl.php';

// End of page
llxFooter();
$db->close();
