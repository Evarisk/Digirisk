<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

require_once './../../class/digiriskstandard.class.php';
require_once './../../lib/digiriskdolibarr_digiriskstandard.lib.php';
require_once './../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $langs, $user, $hookmanager;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

// Get parameters
$id         = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action     = GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) $actioncode = '0';
}
else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

$limit     = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'a.datep,a.id';
if (!$sortorder) $sortorder = 'DESC,DESC';

// Initialize technical objects
$object      = new DigiriskStandard($db);
$emptyobject = new stdClass($db);
$extrafields = new ExtraFields($db);

$object->fetch($conf->global->DIGIRISKDOLIBARR_ACTIVE_STANDARD);

$hookmanager->initHooks(array('digiriskstandardagenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) $upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity]."/".$object->id;

//Security check
$permissiontoread = $user->rights->digiriskdolibarr->digiriskelement->read;
$permissiontoadd  = $user->rights->digiriskdolibarr->digiriskelement->write;

if (!$permissiontoread) accessforbidden();

/*
 *  Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode = '';
		$search_agenda_label = '';
	}
}

/*
 *	View
 */

if ($object->id > 0){
	$title = $langs->trans("Agenda");
	//if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
	$help_url = 'FR:Module_DigiriskDolibarr';
	$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
	$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

	digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss);
	print '<div id="cardContent" value="">';

	if (!empty($conf->notification->enabled)) $langs->load("mails");
	$head = digiriskstandardPrepareHead($object);

	print dol_get_fiche_head($head, 'standardAgenda', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';
	$width = 80; $cssclass = 'photoref';
	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 1, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);

	print '<div class="fichecenter">';
	print '</div>';

	print dol_get_fiche_end();

	// Actions buttons
	$out = '&origin='.$object->element.'@digiriskdolibarr'.'&originid='.$object->id;

	if (!empty($conf->agenda->enabled)) {
		$linktocreatetimeBtnStatus = !empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create);
		$morehtmlcenter = dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out, '', $linktocreatetimeBtnStatus);
	}

	if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
		$param = '&id='.$object->id;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);

		print_barre_liste($langs->trans("ActionsOnDigiriskStandard"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlcenter, '', 0, 1, 1);

		// List of all actions
		$filters = array();
		$filters['search_agenda_label'] = $search_agenda_label;

		// TODO Replace this with same code than into list.php

		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, 'digiriskdolibarr');
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
