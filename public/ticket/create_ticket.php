<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       public/ticket/create_ticket.php
 *       \ingroup    digiriskdolibarr
 *       \brief      Display public form to add new ticket
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))      define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

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
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if (!$res) die("Include of main fails");require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/ticket/mod_ticket_simple.php';

require_once '../../lib/digiriskdolibarr_function.lib.php';

global $langs;
// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'mails', 'ticket', 'digiriskdolibarr@digiriskdolibarr'));

// Get parameters
$id            = GETPOST('id', 'int');
$msg_id        = GETPOST('msg_id', 'int');
$action        = GETPOST('action', 'aZ09');
$ticket_tmp_id = GETPOST('ticket_id');

if (!dol_strlen($ticket_tmp_id)) {
	$ticket_tmp_id = generate_random_id(16);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewticketcard', 'globalcard'));

$object      = new Ticket($db);
$formfile    = new FormFile($db);
$extrafields = new ExtraFields($db);
$category    = new Categorie($db);
$modTicket   = new mod_ticket_simple($db);

$extrafields->fetch_name_optionals_label($object->table_element);

$upload_dir = $conf->categorie->multidir_output[isset($conf->entity) ? $conf->entity : 1];

/*
 * Actions
 */

$parameters = array(
	'id' => $id,
);
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'add') {
	$register      = GETPOST('register');
	$pertinence    = GETPOST('pertinence');
	$message       = GETPOST('message');
	$ticket_tmp_id = GETPOST('ticket_id');

	$object->ref = $modTicket->getNextValue($thirdparty,$object);

	$object->type_code  = 'OTHER';
	$label_type         = ($langs->trans("TicketTypeShort".$object->type_code) != ("TicketTypeShort".$object->type_code) ? $langs->trans("TicketTypeShort".$object->type_code) : ($object->type_label != '-' ? $object->type_label : ''));
	$object->type_label = $label_type;

	$object->category_code  = 'OTHER';
	$label_category         = ($langs->trans("TicketCategoryShort".$object->category_code) != ("TicketCategoryShort".$object->category_code) ? $langs->trans("TicketCategoryShort".$object->category_code) : ($object->category_label != '-' ? $object->category_label : ''));
	$object->category_label = $label_category;

	$object->severity_code  = 'NORMAL';
	$label_severity         = ($langs->trans("TicketSeverityShort".$object->severity_code) != ("TicketSeverityShort".$object->severity_code) ? $langs->trans("TicketSeverityShort".$object->severity_code) : ($object->severity_label != '-' ? $object->severity_label : ''));
	$object->severity_label = $label_severity;

	$object->message = html_entity_decode($message);

	$extrafields->setOptionalsFromPost(null, $object);

	$result = $object->create($user);
	$object->fetch($result);
	$track_id = $object->track_id;

	if ($result > 0) {
		//Add categories linked
		$registerCat = $category;
		$registerCat->fetch($register);
		$registerCat->add_type($object, Categorie::TYPE_TICKET);

		$pertinenceCat = $category;
		$pertinenceCat->fetch($pertinence);
		$pertinenceCat->add_type($object, Categorie::TYPE_TICKET);

		//Add files linked
		$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1].'/temp';
		$fileList          = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/');
		$thumbsList        = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/thumbs/');
		$ticketDirPath     = $conf->ticket->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/';
		$fullTicketPath    = $ticketDirPath . $object->ref . '/';
		$thumbsTicketPath  = $ticketDirPath . $object->ref . '/thumbs/';

		if (!is_dir($ticketDirPath)) {
			dol_mkdir($ticketDirPath);
		}

		if (!is_dir($fullTicketPath)) {
			dol_mkdir($fullTicketPath);
		}

		if (!is_dir($thumbsTicketPath)) {
			dol_mkdir($thumbsTicketPath);
		}

		if (!empty($fileList)) {
			foreach ($fileList as $fileToCopy) {
				if (is_file($fileToCopy['fullname'])) {
					rename($fileToCopy['fullname'],$fullTicketPath . $fileToCopy['name']);
				}
			}
		}

		if (!empty($thumbsList)) {
			foreach ($thumbsList as $thumbsToCopy) {
				if (is_file($thumbsToCopy['fullname'])) {
					rename($thumbsToCopy['fullname'],$fullTicketPath . '/thumbs/' . $thumbsToCopy['name']);
				}
			}
		}

		// Creation OK
		dol_delete_dir_recursive($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/');
		$urltogo = $_SERVER['PHP_SELF'] . '/../create_success.php?track_id=' . $track_id ;
		setEventMessages($langs->trans("TicketSend", ''), null);
		header("Location: ".$urltogo);
		exit;
	}

}

// Add file in ticket form
if ($action == 'sendfile') {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1].'/temp';
	if (!is_dir($ticket_upload_dir . '/ticket')) {
		dol_mkdir($ticket_upload_dir . '/ticket');
	}
	$fullTicketTmpPath = $ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/';
	dol_mkdir($fullTicketTmpPath);

	for ($k = 0; $k < count($_FILES['files']['name']); $k++) {
		if (($_FILES['files']['name'][$k]!="")){

			// Where the file is going to be stored
			$target_dir = $fullTicketTmpPath;
			$file = $_FILES['files']['name'][$k];
			$path = pathinfo($file);
			$filename = $path['filename'];
			$ext = $path['extension'];
			$temp_name = $_FILES['files']['tmp_name'][$k];
			$path_filename_ext = $target_dir.$filename.".".$ext;

			if (file_exists($path_filename_ext)) {
				echo "Sorry, file already exists.";
			} else {
				echo $temp_name;
				echo $path_filename_ext;
				move_uploaded_file($temp_name,$path_filename_ext);
				echo "Congratulations! File Uploaded Successfully.";

				global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

				// Create thumbs
				$imgThumbSmall = vignette($path_filename_ext, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				$imgThumbMini = vignette($path_filename_ext, 30, 30, '_mini', 50, "thumbs");
			}
		}
	} $action = '';
}

// Remove file
if ($action == 'removefile') {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$filetodelete = GETPOST('filetodelete');

	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1].'/temp';
	//Add files linked
	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1].'/temp/ticket/' . $ticket_tmp_id . '/';
	$fileList          = dol_dir_list($ticket_upload_dir);

	if (is_file($ticket_upload_dir . $filetodelete)) {
		dol_delete_file($ticket_upload_dir . $filetodelete);

		$thumbsList = dol_dir_list($ticket_upload_dir . 'thumbs/');
		if (!empty($thumbsList)) {
			foreach ($thumbsList as $thumb) {
				dol_delete_file($ticket_upload_dir . 'thumbs/' . $thumb['name'] );
			}
			dol_delete_dir($ticket_upload_dir . 'thumbs/');
		}
	}
	$action = '';
}


/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

if (!$conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) {
	print '<div class="error">'.$langs->trans('TicketPublicInterfaceForbidden').'</div>';
	$db->close();
	exit();
}

$arrayofjs  =  array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$arrayofcss = array('/opensurvey/css/style.css', '/ticket/css/styles.css.php', "/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeaderTicket($langs->trans("CreateTicket"), "", 0, 0, $arrayofjs, $arrayofcss);

print '<div class="ticketpublicarea">';

print load_fiche_titre($title_edit, '', "digiriskdolibarr32px@digiriskdolibarr");

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="sendTicketForm">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" id="register" name="register" value="'.GETPOST('register').'">';
print '<input type="hidden" id="pertinence" name="pertinence" value="'.GETPOST('pertinence').'">';
print '<input type="hidden" id="ticket_id" name="ticket_id" value="'.$ticket_tmp_id.'">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

print dol_get_fiche_head(array(), '0', '', 1);

print '<div class="img-fields-container">';
print '<table class="border centpercent tableforimgfields">'."\n";

print '<tr><td>'.$langs->trans("Register").'</td></tr>';
print '<tr>';
$registerCategory = $category->rechercher(0,'Registre','ticket', true);
$registerChildren = $registerCategory[0]->get_filles();
foreach ($registerChildren as $register) {
	if ($register->id == GETPOST('register')) {
		print '<td class="ticket-register center" id="'.$register->id.'" style="border: solid">';
	} else {
		print '<td class="ticket-register center" id="'.$register->id.'">';
	}

	if ($register->label == 'Santé & Sécurité au Travail') {
		print '<div class="wpeo-button button-blue">';
		print '<i class="fas fa-shield-alt"></i>';
		print $register->label;
		print '</div>';
	} elseif ($register->label == 'Accidents') {
		print '<div class="wpeo-button button-yellow">';
		print '<i class="fas fa-user-injured"></i>';
		print $register->label;
		print '</div>';
	} elseif ($register->label == 'Danger Grave et Imminent') {
		print '<div class="wpeo-button button-red">';
		print '<i class="fas fa-exclamation-triangle"></i>';
		print $register->label;
		print '</div>';
	} else {
		show_category_image($register, $upload_dir);
	}
	print '</td>';

}

print '</tr>';
print '</table>';

print '<table class="border centpercent tableforimgfields">'."\n";

if (GETPOST('register')) {
	$selectedRegister = $category;
	$selectedRegister->fetch(GETPOST('register'));
	$selectedRegisterChildren = $selectedRegister->get_filles();
	if (!empty($selectedRegisterChildren)) {
		print '<tr><td>' . $langs->trans("Pertinence") . '</td></tr>';
		foreach ($selectedRegisterChildren as $pertinence) {
			if ($pertinence->id == GETPOST('pertinence')) {
				print '<td class="ticket-pertinence center" id="' . $pertinence->id . '" style="border: solid">';
			} else {
				print '<td class="ticket-pertinence center" id="' . $pertinence->id . '">';
			}
			show_category_image($pertinence, $upload_dir);
			print '</td>';
		}
	}
}

print '</table>';
print '<br>';

print '</div>';
print '<table class="border centpercent tableforinputfields">'."\n";

print '<tr><td>'.$langs->trans("Message").'</td>';
print '<td>'.$langs->trans("FilesLinked").'</td>';
print '<td>';

print '<label class="wpeo-button button-blue" for="sendfile">';
print '<i class="fas fa-image button-icon"></i>';
print ' '.$langs->trans('AddDocument');
print '<input type="file" name="userfile[]" multiple="multiple" id="sendfile" onchange="window.eoxiaJS.ticket.tmpStockFile()"  style="display: none"/>';
print '</label>';
print '</td>';

print '<tr><td>';
$doleditor = new DolEditor('message', $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL ? $conf->global->DIGIRISK_DEROGATION_SCHEDULE_OCCASIONAL : '', '', 90, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
$doleditor->Create();

print '</td><td><div id="sendFileForm">';
print '<table class="border centpercent tableforinputfields" id="fileLinkedTable">'."\n";

$fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1].'/temp/ticket/'.$ticket_tmp_id.'/thumbs/');

if (!empty($fileLinkedList)){
	foreach ($fileLinkedList as $fileLinked) {
		if (preg_match('/mini/', $fileLinked['name'])) {
			print '<tr><td>';
			print '<img class="photo"  width="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=digiriskdolibarr&entity='.$conf->entity.'&file='.urlencode('/temp/ticket/'.$ticket_tmp_id.'/thumbs/' . $fileLinked['name']).'" title="'.dol_escape_htmltag($alt).'">';

			print '</td><td>';
			print preg_replace('/_mini/','', $fileLinked['name']);
			print '</td><td>';

			print '<div class="linked-file-delete wpeo-button button-square-50 button-transparent" value="'. $fileLinked['name'] .'"><i class="fas fa-trash button-icon"></i></div>';
			print '</td></tr>';
		}
	}
} else {
	print '<td>';
	print $langs->trans('NoFileLinked');
	print '</td>';
}

print '</table>';
print '</div></div></td></tr>';

include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

print '</form>';
print '</td></tr>';
print '</table>';

print dol_get_fiche_end();

print '<div class="center"><button form="sendTicketForm" type="submit" id ="actionButtonSave" class="wpeo-button" name="add">'.'<i class="fas fa-paper-plane"></i>    '.$langs->trans("Send") . '</button>';
print '</div>';

print '</div>';

// End of page
llxFooter('', 'public');
$db->close();
