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

	$object = new Ticket($db);

	$extrafields->fetch_name_optionals_label($object->table_element);
	$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
	$search_array_options['search_options_digiriskdolibarr_ticket_service'] = $id;

	if (isModEnabled('categorie')) {
		$searchCategories = GETPOST('search_category_' . $object->element . '_list', 'array');
	}

	// Default sort order (if not yet defined by previous GETPOST)
	if (!$sortfield) {
		reset($object->fields);   // Reset is required to avoid key() to return null
		$sortfield = 't.datec'; // Set here default search field. By default, date_creation
	}
	if (!$sortorder) {
		$sortorder = 'DESC';
	}

	$excludeFields      = [];

	$excludeFields = array_merge($excludeFields, []);

	// Initialize array of search criterias
	$searchAll        = trim(GETPOST('search_all'));
	$search           = [];
	$search['status'] = [1,2];
	foreach ($object->fields as $key => $val) {
		if (GETPOST('search_' . $key, 'alpha') !== '') {
			$search[$key] = GETPOST('search_' . $key, 'alpha');
		}
		if (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
			$search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
			$search[$key . '_dtend']   = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
		}
	}

	// List of fields to search into when doing a "search in all"
	$fieldsToSearchAll = [];
	foreach ($object->fields as $key => $val) {
		if (!empty($val['searchall'])) {
			$fieldsToSearchAll['t.' . $key] = $val['label'];
		}
	}

	// Definition of array of fields for columns
	foreach ($object->fields as $key => $val) {
		if (!empty($val['visible'])) {
			$visible = (int) dol_eval($val['visible']);
			$arrayfields['t.' . $key] = [
				'label'    => $val['label'],
				'checked'  => (($visible < 0) ? 0 : 1),
				'enabled'  => ($visible != 3 && dol_eval($val['enabled'])),
				'position' => $val['position'],
				'help'     => $val['help'] ?? '',
			];
		}
	}


	// Extra fields
	require_once DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

	$object->fields = dol_sort_array($object->fields, 'position');
	$arrayfields    = dol_sort_array($arrayfields, 'position');

	$conf->global->MAIN_DISABLE_FULL_SCANLIST = 1;
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_build_sql_select.tpl.php';
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_header.tpl.php';
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_input.tpl.php';
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_search_title.tpl.php';
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_loop_object.tpl.php';
	require_once __DIR__ . '/../../../saturne/core/tpl/list/objectfields_list_footer.tpl.php';

}

// End of page
llxFooter();
$db->close();
