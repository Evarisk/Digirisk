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
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object           = new DigiriskElement($db);
$extrafields      = new ExtraFields($db);
$digiriskstandard = new DigiriskStandard($db);
$project          = new Project($db);

$hookmanager->initHooks(array('digiriskelementregister', 'globalcard')); // Note that conf->hooks_modules contains array
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

saturne_check_access($permissiontoread);

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
	dol_strlen($object->label) ? $morehtmlref = ' - ' . $object->label : '';
	// Project
	$morehtmlref = '<div class="refidno">';
	$project->fetch($conf->global->DIGIRISKDOLIBARR_DU_PROJECT);
	$morehtmlref .= $langs->trans('Project') . ' : ' . getNomUrlProject($project, 1, 'blank', 1);
	// ParentElement
	$parent_element = new DigiriskElement($db);
	$result         = $parent_element->fetch($object->fk_parent);
	if ($result > 0) {
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $parent_element->getNomUrl(1, 'blank', 1);
	} else {
		$digiriskstandard->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);
		$morehtmlref .= '<br>' . $langs->trans("Description") . ' : ' . $object->description;
		$morehtmlref .= '<br>' . $langs->trans("ParentElement") . ' : ' . $digiriskstandard->getNomUrl(1, 'blank', 1);
	}
	$morehtmlref .= '</div>';
	$width        = 80;
	$height       = 80;
	$cssclass     = 'photoref';
	if (isset($object->element_type)) {
		$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref photo digirisk-element-photo-'. $object->id .'">';
		$morehtmlleft .= saturne_show_medias_linked('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity] . '/' . $object->element_type . '/' . $object->ref, 'small', 1, 0, 0, 0, 80, 80, 0, 0, 0, $object->element_type . '/' . $object->ref, $object, 'photo', 0, 0, 0, 1);
		$morehtmlleft .= '</div>';
	}
	$linkback = '<a href="' . dol_buildpath('/digiriskdolibarr/view/digiriskelement/risk_list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';
	digirisk_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print load_fiche_titre($langs->trans("DashBoard"), '', 'digiriskdolibarr32px.png@digiriskdolibarr');

	$digiriskelement = $object;

	require_once __DIR__ . '/../../core/tpl/digiriskdolibarr_dashboard_ticket.tpl.php';
}

// End of page
llxFooter();
$db->close();
