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
 *  \file       digiriskelement_document.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Tab for documents linked to DigiriskElement
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/digiriskdolibarr/class/digiriskelement.class.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_digiriskelement.lib.php');
dol_include_once('/digiriskdolibarr/lib/digiriskdolibarr_function.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "companies", "other", "mails"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref = GETPOST('ref', 'alpha');

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "name";
//if (! $sortfield) $sortfield="position_name";

// Initialize technical objects
$object = new DigiriskElement($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->digiriskdolibarr->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('digiriskelementdocument', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

//if ($id > 0 || ! empty($ref)) $upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity?$object->entity:$conf->entity] . "/digiriskelement/" . dol_sanitizeFileName($object->id);
if ($id > 0 || !empty($ref)) $upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity ? $object->entity : $conf->entity]."/".$object->element_type."/".dol_sanitizeFileName($object->ref);

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'digiriskdolibarr', $object->id);

$permissiontoadd = $user->rights->digiriskdolibarr->digiriskelement->write; // Used by the include of actions_addupdatedelete.inc.php



/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);
$emptyobject = new stdClass($db);

$title = $langs->trans("DigiriskElement").' - '.$langs->trans("Files");
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
$morejs  = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss = array("/digiriskdolibarr/css/digiriskdolibarr.css");

digiriskHeader('', $title, $help_url, '', '', '', $morejs, $morecss);
if (!$object->id) {

	$object->ref = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$object->label = 'Societe principale';
	$object->entity = 2;
	unset($object->fields['element_type']);


}
if (true)
{
	/*
	 * Show tabs
	 */
	print '<div id="cardContent" value="">';

	$head = digiriskelementPrepareHead($object);

	dol_fiche_head($head, 'elementDocument', $title, -1, "digiriskdolibarr@digiriskdolibarr");


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file)
	{
		$totalsize += $file['size'];
	}

	// Object card
	// ------------------------------------------------------------
	$morehtmlref = '<div class="refidno">';

	$width = 80; $cssclass = 'photoref';
	if (isset($object->element_type)) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$entity].'/'.$object->element_type, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element_type, $object).'</div>';
	} else {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('mycompany', $conf->mycompany->dir_output . '/logos', 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, 'logos', $emptyobject).'</div>';
	}
	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '', 0, $morehtmlleft);	$morehtmlref .= '</div>';


	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	$modulepart = 'digiriskdolibarr';
	$permission = $user->rights->digiriskdolibarr->digiriskelement->write;
	$permtoedit = $user->rights->digiriskdolibarr->digiriskelement->write;
	$param = '&id='.$object->id;

	//$relativepathwithnofile='digiriskelement/' . dol_sanitizeFileName($object->id).'/';
	$relativepathwithnofile = $object->element_type.'/'.dol_sanitizeFileName($object->ref).'/';
	include_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	print '</div>';

}
else
{
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
