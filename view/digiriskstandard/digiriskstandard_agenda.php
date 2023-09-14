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
 *  \file       view/digiriskstandard/digiriskstandard_agenda.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Page of DigiriskStandard events
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

require_once __DIR__ . '/../../class/digiriskstandard.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $langs, $user, $hookmanager;

// Load translation files required by the page
saturne_load_langs(['other']);

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOST('actioncode', 'array')) {
	$actioncode                            = GETPOST('actioncode', 'array', 3);
	if ( ! count($actioncode)) $actioncode = '0';
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset                       = $limit * $page;
$pageprev                     = $page - 1;
$pagenext                     = $page + 1;
if ( ! $sortfield) $sortfield = 'a.datep,a.id';
if ( ! $sortorder) $sortorder = 'DESC,DESC';

// Initialize technical objects
$object      = new DigiriskStandard($db);
$emptyobject = new stdClass();
$extrafields = new ExtraFields($db);
$project     = new Project($db);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$hookmanager->initHooks(array('digiriskstandardagenda', 'digiriskstandardview', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd  = $user->rights->digiriskdolibarr->digiriskelement->write;

saturne_check_access($permissiontoread);

/*
 *  Actions
 */

$parameters = array('id' => $id);
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && ! empty($backtopage)) {
		header("Location: " . $backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode          = '';
		$search_agenda_label = '';
	}
}

/*
 *	View
 */

if ($object->id > 0) {
	$title   = $langs->trans("Agenda");
	$helpUrl = 'FR:Module_Digirisk#DigiRisk_-_Document_Unique';

	digirisk_header($title, $helpUrl);
	print '<div id="cardContent" value="">';

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}

	saturne_get_fiche_head($object, 'standardAgenda', $title);

	// Object card
	// ------------------------------------------------------------
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	$morehtmlref .= '</div>';

	$moduleNameLowerCase = 'mycompany';
	saturne_banner_tab($object,'ref','', 1, 'ref', 'ref', $morehtmlref, true);
	$moduleNameLowerCase = 'digiriskdolibarr';

	print '<div class="fichecenter">';
	print '</div>';

	print dol_get_fiche_end();

	// Actions buttons
	$out = '&origin=' . $object->element . '@digiriskdolibarr' . '&originid=' . $object->id . '&backtopage='. $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&percentage=-1';

	if (isModEnabled('agenda')) {
		$linktocreatetimeBtnStatus = ! empty($user->rights->agenda->myactions->create) || ! empty($user->rights->agenda->allactions->create);
		$morehtmlcenter            = dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out, '', $linktocreatetimeBtnStatus);

		if (( ! empty($user->rights->agenda->myactions->read) || ! empty($user->rights->agenda->allactions->read))) {
			$param                                                                      = '&id=' . $object->id;
			if ( ! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
			if ($limit > 0 && $limit != $conf->liste_limit) $param                     .= '&limit=' . urlencode($limit);

			print_barre_liste($langs->trans("ActionsOnDigiriskStandard"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlcenter, '', 0, 1, 1);

			// List of all actions
			$filters                        = array();
			$filters['search_agenda_label'] = $search_agenda_label;

			show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, 'digiriskdolibarr');
			print '</div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
