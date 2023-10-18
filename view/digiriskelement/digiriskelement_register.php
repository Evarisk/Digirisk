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
 *  \file       view/digiriskelement/digiriskelement_register.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Page of DigiriskElement dashboard ticket
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';

require_once __DIR__ . '/../../class/digiriskelement.class.php';
require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskelement.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'alpha');
$subaction  = GETPOST('subaction', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object           = new DigiriskElement($db);
$extrafields      = new ExtraFields($db);
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);
$ticket           = new Ticket($db);

$hookmanager->initHooks(array('digiriskelementregister', 'digiriskelementview', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || ! empty($ref)) $upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity] . "/" . $object->id;

//Security check
$permissiontoread   = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->digiriskelement->write;
$permissiontodelete = $user->rights->digiriskdolibarr->digiriskelement->delete;
$upload_dir = $conf->categorie->multidir_output[$conf->entity];

saturne_check_access($permissiontoread, $object);

/*
 *  Actions
 */

$parameters = array('id' => $id);
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 *	View
 */

if ($object->id > 0) {
	$title    = $langs->trans("Register");
	$helpUrl  = 'FR:Module_Digirisk#Le_tableau_de_bord_et_indicateurs';

	digirisk_header($title, $helpUrl);

	print '<div id="cardContent" value="">';

	saturne_get_fiche_head($object, 'elementRegister', $title);

	// Object card
	// ------------------------------------------------------------
    list($morehtmlref, $moreParams) = $object->getBannerTabContent();

    saturne_banner_tab($object,'ref','none', 0, 'ref', 'ref', $morehtmlref, true, $moreParams);

	print load_fiche_titre($langs->trans("DashBoard"), '', 'digiriskdolibarr_color.png@digiriskdolibarr');

	$digiriskelement = $object;

	require_once __DIR__ . '/../../core/tpl/digiriskdolibarr_dashboard_ticket.tpl.php';
}

// End of page
llxFooter();
$db->close();
