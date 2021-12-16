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
 *  \file       view/accident/accident_document_metadata.php
 *  \ingroup    digiriskdolibarr
 *  \brief      Tab for documents linked to Accident Metadata
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

require_once __DIR__ . '/../../class/accident.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_accident.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';

global $conf, $db, $hookmanager, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "companies", "other", "mails"));

// Get parameters
$action    = GETPOST('action', 'aZ09');
$confirm   = GETPOST('confirm');
$id        = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref       = GETPOST('ref', 'alpha');
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset   = $liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

// Initialize technical objects
$object           = new Accident($db);
$accidentmetadata = new AccidentMetaData($db);
$extrafields      = new ExtraFields($db);
$project          = new Project($db);
$ecmfile          = new EcmFiles($db);

$hookmanager->initHooks(array('accidentdocument', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->digiriskdolibarr->multidir_output[$object->entity ? $object->entity : $conf->entity]."/accidentmetadata/".get_exdir(0, 0, 0, 1, $object);
}

// Security check
$permissiontoread   = $user->rights->digiriskdolibarr->accident->read;
$permissiontoadd    = $user->rights->digiriskdolibarr->accident->write;
$permissiontodelete = $user->rights->digiriskdolibarr->accident->delete;

if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

// Submit file/link
if (GETPOST('upload_file', 'alpha') && !empty($conf->global->MAIN_UPLOAD_DOC))
{
	$type = GETPOST('type', 'alpha');

	if (!empty($_FILES) && !empty($_FILES['userfile']['name'][0]))
	{
		if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
		else $userfiles = array($_FILES['userfile']['tmp_name']);

		foreach ($userfiles as $key => $userfile)
		{
			if (empty($_FILES['userfile']['tmp_name'][$key]))
			{
				$error++;
				if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}
		}

		if (!$error)
		{
			// Define if we have to generate thumbs or not
			$generatethumbs = 1;
			if (!empty($upload_dir))
			{
				$result = dol_add_file_process($upload_dir, 0, 1, 'userfile', GETPOST('savingdocmask', 'alpha'), null, '', $generatethumbs, $object);
			}

			if ($result > 0) {
				$ecmfile->fetch($result);
				$ecmfile->description = $type;
				$ecmfile->update($user);
				// Upload File OK
				$urltogo = dol_buildpath('/digiriskdolibarr/view/accident/accident_document_metadata.php', 1).'?id='.$id;
				header("Location: " . $urltogo);
				exit;
			}
			else {
				// Upload File KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else  setEventMessages($object->error, null, 'errors');
			}
		}
	}
}

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
 * Confirm form to delete
 */

/*
 * View
 */

$form     = new Form($db);
$formfile = new FormFile($db);

$title    = $langs->trans("Accident").' - '.$langs->trans("Files");
$help_url = '';
llxHeader('', $title, $help_url);

if ($object->id) {

	/*
	 * Confirm form to delete
	 */
	if ($action == 'delete')
	{
		$langs->load("companies"); // Need for string DeleteFile+ConfirmDeleteFiles
		print $form->formconfirm(
			$_SERVER["PHP_SELF"].'?id='.$object->id.'&urlfile='.urlencode(GETPOST("urlfile")).'&linkid='.GETPOST('linkid', 'int').(empty($param) ? '' : $param),
			$langs->trans('DeleteFile'),
			$langs->trans('ConfirmDeleteFile'),
			'confirm_deletefile',
			'',
			0,
			1
		);
	}

	$head = accidentPrepareHead($object);

	print dol_get_fiche_head($head, 'accidentDocumentMetaData', $title, -1, "digiriskdolibarr@digiriskdolibarr");

	// Object card
	// ------------------------------------------------------------
	dol_strlen($object->label) ? $morehtmlref = '<span>'. ' - ' .$object->label . '</span>' : '';
	$morehtmlref .= '<div class="refidno">';
	// Project
	$project->fetch($object->fk_project);
	$morehtmlref .= $langs->trans('Project').' : '.getNomUrlProject($project, 1, 'blank');
	$morehtmlref .= '</div>';

	$morehtmlleft = '<div class="floatleft inline-block valignmiddle divphotoref">'.digirisk_show_photos('digiriskdolibarr', $conf->digiriskdolibarr->multidir_output[$conf->entity].'/'.$object->element, 'small', 5, 0, 0, 0, $width,0, 0, 0, 0, $object->element, $object).'</div>';

	digirisk_banner_tab($object, 'ref', '', 0, 'ref', 'ref', $morehtmlref, '','',$morehtmlleft);

	print '<div class="fichecenter">';
	print '<table class="border centpercent tableforfield">';

	// Defined relative dir to DOL_DATA_ROOT
	$relativedir = '';
	if ($upload_dir)
	{
		$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);
		$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
	}

	// Build file list
	$filearray = dol_dir_list_in_database($relativedir, '', '', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;

	if (!empty($filearray)) {
		foreach ($filearray as $key => $file) {
			$totalsize += dol_filesize($file['fullname']);
		}
	}

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print load_fiche_titre($langs->trans("AttachANewFile"), '', '');

	print '<form class="upload_file-from" name="UploadFile" id="UploadFile" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" enctype="multipart/form-data" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td class="center">'.$langs->trans("Action").'</td>';
	print '</tr>';

	//Type -- Type
	print '<tr class="oddeven"><td>';
	print '<input class="flat" type="text" size="36" name="type" id="type" value="">';
	print '</td>';

	print '<td class="center upload_file">';
	print '<input class="flat" type="file" name="userfile[]" id="upload_file" />';
	print '<input type="submit" class="button reposition upload_file" name="upload_file" value="'.$langs->trans("Upload").'">';
	print '</td>';
	print '</tr>';

	print '</table>';
	print '</form>';

	print load_fiche_titre($langs->trans("AttachedFiles"), '', 'file-upload');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td class="center">'.$langs->trans("ActionsLine").'</td>';
	print '</tr>';

	foreach ($filearray as $key => $file) {
		print '<tr class="oddeven"><td>';
		print $file['name'];
		print "</td><td>";
		print  $file['description'];
		print '</td>';
		print '<td class="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'&urlfile='.urlencode('accidentmetadata/'.$object->ref.'/'.$file['name']).'" class="reposition deletefilelink">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
	}

	print '</table>';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
