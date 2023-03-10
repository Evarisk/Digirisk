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

if ( ! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if ( ! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if ( ! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if ( ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if ( ! defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
if ( ! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if ( ! defined('NOIPCHECK'))      define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if ( ! defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if ( ! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if ( ! $res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res          = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if ( ! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if ( ! $res && file_exists("../../main.inc.php")) $res          = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res       = @include "../../../main.inc.php";
if ( ! $res && file_exists("../../../../main.inc.php")) $res    = @include "../../../../main.inc.php";
if ( ! $res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/ticket/mod_ticket_simple.php';

require_once '../../lib/digiriskdolibarr_function.lib.php';
require_once '../../class/digiriskelement.class.php';

require_once __DIR__ . '/../../../saturne/lib/object.lib.php';

global $conf, $db, $hookmanager, $langs, $mc, $user;

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'mails', 'ticket', 'digiriskdolibarr@digiriskdolibarr'));

// Get parameters
$id            = GETPOST('id', 'int');
$msg_id        = GETPOST('msg_id', 'int');
$action        = GETPOST('action', 'aZ09');
$ticket_tmp_id = GETPOST('ticket_id');

if ( ! dol_strlen($ticket_tmp_id)) {
	$ticket_tmp_id = generate_random_id(16);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewticketcard', 'globalcard'));

$object          = new Ticket($db);
$formfile        = new FormFile($db);
$extrafields     = new ExtraFields($db);
$category        = new Categorie($db);
$digiriskelement = new DigiriskElement($db);

$numRefConf = strtoupper($object->element) . '_ADDON';

$numberingModuleName = [
	$object->element => $conf->global->$numRefConf,
];
list($modTicket) = saturne_require_objects_mod($numberingModuleName);

$extrafields->fetch_name_optionals_label($object->table_element);

$entity = GETPOST('entity');

if (!$conf->multicompany->enabled) {
	$entity = $conf->entity;
}

$conf->setEntityValues($db, $entity);

//ici charger les conf de la bonne entité
$upload_dir = $conf->categorie->multidir_output[isset($entity) ? $entity : 1];

$multiCompanyMention = (empty(dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_USE_MULTICOMPANY_CONFIG', $entity)) ? '' : 'MULTICOMPANY_');

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

// Define Conf
$emailVisibleConf            = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_EMAIL_VISIBLE';
$emailRequiredConf           = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_EMAIL_REQUIRED';

$firstnameVisibleConf        = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_FIRSTNAME_VISIBLE';
$firstnameRequiredConf       = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_FIRSTNAME_REQUIRED';

$lastnameVisibleConf         = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_LASTNAME_VISIBLE';
$lastnameRequiredConf        = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_LASTNAME_REQUIRED';

$phoneVisibleConf            = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_PHONE_VISIBLE';
$phoneRequiredConf           = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_PHONE_REQUIRED';

$locationVisibleConf         = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_LOCATION_VISIBLE';
$locationRequiredConf        = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_LOCATION_REQUIRED';

$digiriskelementVisibleConf  = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_DIGIRISKELEMENT_VISIBLE';
$digiriskelementRequiredConf = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_DIGIRISKELEMENT_REQUIRED';

$dateVisibleConf             = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_DATE_VISIBLE';
$dateRequiredConf            = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_DATE_REQUIRED';

$parentCategoryLabel         = 'DIGIRISKDOLIBARR_'. $multiCompanyMention .'TICKET_PARENT_CATEGORY_LABEL';

if ($action == 'add') {
	$error = 0;

	$parentCategory = GETPOST('parentCategory');
	$subCategory    = GETPOST('subCategory');
	$message        = GETPOST('message');
	$ticket_tmp_id  = GETPOST('ticket_id');

	// Check parameters
	if (empty($parentCategory)) {
		setEventMessages($langs->trans('ErrorFieldNotEmpty', $conf->global->$parentCategoryLabel), array(), 'errors');
		$error++;
	}

	$email = GETPOST('email', 'alpha');
	if ($conf->global->$emailRequiredConf && $conf->global->$emailVisibleConf) {
		if (empty($email)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Email')), array(), 'errors');
			$error++;
		}
	}

	$firstname = GETPOST('options_digiriskdolibarr_ticket_firstname', 'alpha');
	if ($conf->global->$firstnameRequiredConf && $conf->global->$firstnameVisibleConf) {
		if (empty($firstname)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('FirstName')), array(), 'errors');
			$error++;
		}
	}

	$lastname = GETPOST('options_digiriskdolibarr_ticket_lastname', 'alpha');
	if ($conf->global->$lastnameRequiredConf && $conf->global->$lastnameVisibleConf) {
		if (empty($lastname)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('LastName')), array(), 'errors');
			$error++;
		}
	}

	$phone = GETPOST('options_digiriskdolibarr_ticket_phone', 'alpha');
	if ($conf->global->$phoneRequiredConf && $conf->global->$phoneVisibleConf) {
		if (empty($phone)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Phone')), array(), 'errors');
			$error++;
		}
	}

	$location = GETPOST('options_digiriskdolibarr_ticket_location', 'alpha');
	if ($conf->global->$locationRequiredConf && $conf->global->$locationVisibleConf) {
		if (empty($location)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Location')), array(), 'errors');
			$error++;
		}
	}

	$regEmail = '/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/';
	if (preg_match($regEmail, $email) || empty($email)) {
		$object->origin_email = $email;
	} else {
		setEventMessages($langs->trans('ErrorFieldEmail'), array(), 'errors');
		$error++;
	}

	$regPhone = '/^(?:(?:(?:\+|00)\d{2}[\s]?(?:\(0\)[\s]?)?)|0){1}[1-9]{1}([\s.-]?)(?:\d{2}\1?){3}\d{2}$/';
	if (!preg_match($regPhone, $phone) && !empty($phone)) {
		setEventMessages($langs->trans('ErrorFieldPhone'), array(), 'errors');
		$error++;
	}

	// Quand le registre choisi est Danger Grave et Imminent, il ne faut pas check ça
	//  if (empty($subCategory)) {
	//      setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('Pertinence')), null, 'errors');
	//      $error++;
	//  }
	if (empty($message)) {
		setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('Message')), array(), 'errors');
		$error++;
	}

	if ($conf->global->$digiriskelementRequiredConf && $conf->global->$digiriskelementVisibleConf) {
		if (empty(GETPOST('options_digiriskdolibarr_ticket_service')) || GETPOST('options_digiriskdolibarr_ticket_service') == -1) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('GP/UT')), array(), 'errors');
			$error++;
		}
	}

	$date = GETPOST('options_digiriskdolibarr_ticket_date', 'alpha');
	if ($conf->global->$dateRequiredConf && $conf->global->$dateVisibleConf) {
		if (empty($date)) {
			setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('Date')), array(), 'errors');
			$error++;
		}
	}

	$object->ref = $modTicket->getNextValue($thirdparty, $object);

	$object->type_code  = 'OTHER';
	$label_type         = ($langs->trans("TicketTypeShort" . $object->type_code) != ("TicketTypeShort" . $object->type_code) ? $langs->trans("TicketTypeShort" . $object->type_code) : ($object->type_label != '-' ? $object->type_label : ''));
	$object->type_label = $label_type;

	$object->category_code  = 'OTHER';
	$label_category         = ($langs->trans("TicketCategoryShort" . $object->category_code) != ("TicketCategoryShort" . $object->category_code) ? $langs->trans("TicketCategoryShort" . $object->category_code) : ($object->category_label != '-' ? $object->category_label : ''));
	$object->category_label = $label_category;

	$object->severity_code  = 'NORMAL';
	$label_severity         = ($langs->trans("TicketSeverityShort" . $object->severity_code) != ("TicketSeverityShort" . $object->severity_code) ? $langs->trans("TicketSeverityShort" . $object->severity_code) : ($object->severity_label != '-' ? $object->severity_label : ''));
	$object->severity_label = $label_severity;

	$object->message = html_entity_decode($message);

	$object->fk_project = $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT;

	$extrafields->setOptionalsFromPost(null, $object);

	// Check Captcha code if is enabled
	if ( ! empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
		$sessionkey = 'dol_antispam_value';
		$ok         = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower(GETPOST('code', 'none'))));
		if ( ! $ok) {
			$error++;
			setEventMessage($langs->trans('ErrorBadValueForCode'), 'errors');
			$action = '';
		}
	}
	if (empty($error)) {
		$result = $object->create($user);
		$object->fetch($result);
		$track_id = $object->track_id;

		if ($result > 0) {
			//Add categories linked
			$parentCategoryCat = $category;
			$parentCategoryCat->fetch($parentCategory);
			$parentCategoryCat->add_type($object, Categorie::TYPE_TICKET);

			$subCategoryCat = $category;
			$subCategoryCat->fetch($subCategory);
			$subCategoryCat->add_type($object, Categorie::TYPE_TICKET);

			//Add files linked
			$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp';
			$fileList          = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/');
			$thumbsList        = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/thumbs/');
			$ticketDirPath     = $conf->ticket->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/';
			$fullTicketPath    = $ticketDirPath . $object->ref . '/';
			$thumbsTicketPath  = $ticketDirPath . $object->ref . '/thumbs/';

			if ( ! is_dir($ticketDirPath)) {
				dol_mkdir($ticketDirPath);
			}

			if ( ! is_dir($fullTicketPath)) {
				dol_mkdir($fullTicketPath);
			}

			if ( ! is_dir($thumbsTicketPath)) {
				dol_mkdir($thumbsTicketPath);
			}

			if ( ! empty($fileList)) {
				foreach ($fileList as $fileToCopy) {
					if (is_file($fileToCopy['fullname'])) {
						rename($fileToCopy['fullname'], $fullTicketPath . $fileToCopy['name']);
					}
				}
			}

			if ( ! empty($thumbsList)) {
				foreach ($thumbsList as $thumbsToCopy) {
					if (is_file($thumbsToCopy['fullname'])) {
						rename($thumbsToCopy['fullname'], $fullTicketPath . '/thumbs/' . $thumbsToCopy['name']);
					}
				}
			}

			// Creation OK
			dol_delete_dir_recursive($ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/');
			$urltogo = $_SERVER['PHP_SELF'] . '/../ticket_success.php?track_id=' . $track_id;
			setEventMessages($langs->trans("TicketSent", ''), null);
			header("Location: " . $urltogo);
			exit;
		}
	}
}

// Add file in ticket form
if ($action == 'sendfile') {
	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp';
	if ( ! is_dir($ticket_upload_dir)) {
		dol_mkdir($ticket_upload_dir);
	}
	if ( ! is_dir($ticket_upload_dir . '/ticket')) {
		dol_mkdir($ticket_upload_dir . '/ticket');
	}
	$fullTicketTmpPath = $ticket_upload_dir . '/ticket/' . $ticket_tmp_id . '/';
	dol_mkdir($fullTicketTmpPath);

	for ($k = 0; $k < count($_FILES['files']['name']); $k++) {
		if (($_FILES['files']['name'][$k] != "")) {
			// Where the file is going to be stored
			$target_dir        = $fullTicketTmpPath;
			$file              = $_FILES['files']['name'][$k];
			$path              = pathinfo($file);
			$filename          = $path['filename'];
			$ext               = $path['extension'];
			$temp_name         = $_FILES['files']['tmp_name'][$k];
			$path_filename_ext = $target_dir . $filename . "." . $ext;

			if (file_exists($path_filename_ext)) {
				echo "Sorry, file already exists.";
			} else {
				echo $temp_name;
				echo $path_filename_ext;
				move_uploaded_file($temp_name, $path_filename_ext);
				echo "Congratulations! File Uploaded Successfully.";

				global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

				// Create thumbs
				$imgThumbLarge = vignette($path_filename_ext, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_LARGE, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_LARGE, '_large', 50, "thumbs");
				$imgThumbMedium = vignette($path_filename_ext, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_WIDTH_MEDIUM, $conf->global->DIGIRISKDOLIBARR_MEDIA_MAX_HEIGHT_MEDIUM, '_medium', 50, "thumbs");
				$imgThumbSmall = vignette($path_filename_ext, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				$imgThumbMini = vignette($path_filename_ext, 30, 30, '_mini', 50, "thumbs");
			}
		}
	}
}
// Remove file
if ($action == 'removefile') {
	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	$filetodelete = GETPOST('filetodelete');

	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp';
	//Add files linked
	$ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp/ticket/' . $ticket_tmp_id . '/';
	$fileList          = dol_dir_list($ticket_upload_dir);

	if (is_file($ticket_upload_dir . $filetodelete)) {
		//Delete file
		dol_delete_file($ticket_upload_dir . $filetodelete);

		//Delete file thumbs
		$thumbs_names = getAllThumbsNames($filename);
		if (!empty($thumbs_names)) {
			foreach($thumbs_names as $thumb_name) {
				$thumb_fullname  = $ticket_upload_dir . 'thumbs/' . $thumb_name;
				if (file_exists($thumb_fullname)) {
					unlink($thumb_fullname);
				}
			}
		}
	}
	$action = '';
}

/*
 * View
 */

$form       = new Form($db);
$formticket = new FormTicket($db);

$arrayofjs  = array("/digiriskdolibarr/js/digiriskdolibarr.js");
$arrayofcss = array('/opensurvey/css/style.css', '/ticket/css/styles.css.php', "/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeaderTicketDigirisk($langs->trans("CreateTicket"), "", 0, 0, $arrayofjs, $arrayofcss);

if ($entity > 0) {
	$enablePublicInterfaceConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_ENABLE_PUBLIC_INTERFACE';
	if (!$conf->global->$enablePublicInterfaceConf) {
		print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
		$db->close();
		exit();
	}

	print '<div class="ticketpublicarea digirisk-page-container">';

	print load_fiche_titre($langs->trans("CreateTicket"), '', "digiriskdolibarr32px@digiriskdolibarr");

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" id="sendTicketForm">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="entity" value="'. $entity .'">';
	print '<input type="hidden" id="parentCategory" name="parentCategory" value="' . GETPOST('parentCategory') . '">';
	print '<input type="hidden" id="subCategory" name="subCategory" value="' . GETPOST('subCategory') . '">';
	print '<input type="hidden" id="ticket_id" name="ticket_id" value="' . $ticket_tmp_id . '">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print dol_get_fiche_head(array(), '0', '', 1);

	print '<div class="img-fields-container">';
	print '<div class="centpercent tableforimgfields form-registre">' . "\n";

	if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED)) : ?>
		<div class="wpeo-notice notice-info">
			<div class="notice-content">
				<div class="notice-title"><strong><?php echo $langs->trans("TicketCategoriesNotCreated"); ?></strong></div>
				<?php if (empty($multiCompanyMention)) {?>
					<div class="notice-subtitle"><strong><?php echo $langs->trans("HowToSetupTicketCategories") . '  ' ?><a href=../../admin/ticket/ticket.php#TicketCategories><?php echo $langs->trans('ConfigTicketCategories'); ?></a></strong></div>
				<?php } else {?>
					<div class="notice-subtitle"><strong><?php echo $langs->trans("HowToSetupTicketCategories") . '  ' ?><a href=../../admin/ticket/multicompany_ticket.php#TicketCategories><?php echo $langs->trans('ConfigTicketCategories'); ?></a></strong></div>
				<?php }?>
			</div>
		</div>
	<?php endif;

	$mainCategoryConf   = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_MAIN_CATEGORY';
	$mainCategoryObject = $category->rechercher($conf->global->$mainCategoryConf, '', 'ticket', true);

	if (!empty($mainCategoryObject) && $mainCategoryObject > 0) {
		print '<p><strong>' . $conf->global->$parentCategoryLabel . '</strong><span style="color:red"> *</span></p>';
		print '<div class="wpeo-gridlayout grid-3">';
		$mainCategoryChildren = $mainCategoryObject[0]->get_filles();
		if (!empty($mainCategoryChildren) && $mainCategoryChildren > 0) {
			$k = 1;
			foreach ($mainCategoryChildren as $cat) {
				if ($cat->id == GETPOST('parentCategory')) {
					print '<div class="ticket-parentCategory ticket-parentCategory'. $cat->id .' active" id="' . $cat->id . '">';
				} else {
					print '<div class="ticket-parentCategory ticket-parentCategory'. $cat->id .'" id="' . $cat->id . '">';
				}
				print '<div class="wpeo-button" style="background:#'. $cat->color.'; border-color:#'. $cat->color .'">';

				show_category_image($cat, $upload_dir);
				print '<span class="button-label">' . $cat->label . '</span>';
				print '</div>';

				print '</div>';
				$k++;
			}
			print '</div>';

			print '<div class="centpercent tableforimgfields">' . "\n";

			foreach ($mainCategoryChildren as $cat) {
				$selectedParentCategory = $category;
				$selectedParentCategory->fetch($cat->id);
				$selectedParentCategoryChildren = $selectedParentCategory->get_filles();
				if ( ! empty($selectedParentCategoryChildren)) {

					$childCategoryLabel = 'DIGIRISKDOLIBARR_' . $multiCompanyMention .'TICKET_CHILD_CATEGORY_LABEL';
					print '<div class="subCategories children'. $cat->id .'"'. (GETPOST('parentCategory') == $cat->id ? '' : ' style="display:none">');
					print '<p><strong>' . $conf->global->$childCategoryLabel . '</strong></p>';
					print '<div class="wpeo-gridlayout grid-5">';

					foreach ($selectedParentCategoryChildren as $subCategory) {
						if ($subCategory->id == GETPOST('subCategory')) {
							print '<div class="ticket-subCategory ticket-subCategory'. $subCategory->id .' center active" id="' . $subCategory->id . '">';
						} else {
							print '<div class="ticket-subCategory ticket-subCategory'. $subCategory->id .' center" id="' . $subCategory->id . '" style="background:#ffffff">';
						}
						show_category_image($subCategory, $upload_dir);
						print '<span class="button-label">' . $subCategory->label . '</span>';
						print '</div>';
					}
					print '</div>';
					print '</div>';
				}
			}
			print '</div>';

		}
	} ?>

	<div class="wpeo-form tableforinputfields">
		<div class="wpeo-gridlayout grid-2">
			<div class="form-element">
				<span class="form-label"><?php print $langs->trans("Message"); ?><span style="color:red"> *</span></span>
				<label class="form-field-container">
					<textarea name="message" id="message"><?php echo GETPOST('message');?></textarea>
				</label>
			</div>
			<div class="form-element">
				<?php
				$photoVisibleConf = 'DIGIRISKDOLIBARR_'. $multiCompanyMention . 'TICKET_PHOTO_VISIBLE';
				if ($conf->global->$photoVisibleConf) {?>
				<div class="wpeo-gridlayout grid-2">
					<span class="form-label"><?php print $langs->trans("FilesLinked"); ?></span>
					<label class="wpeo-button button-blue" for="sendfile">
						<i class="fas fa-image button-icon"></i>
						<span class="button-label"><?php print $langs->trans('AddDocument'); ?></span>
						<input type="file" name="userfile[]" multiple="multiple" id="sendfile" onchange="window.eoxiaJS.ticket.tmpStockFile()"  style="display: none"/>
					</label>
				</div>

				<div id="sendFileForm">
					<div id="fileLinkedTable" class="tableforinputfields">
						<?php $fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp/ticket/' . $ticket_tmp_id . '/thumbs/'); ?>
						<div class="wpeo-table table-flex table-3 files-uploaded">
							<?php
							if ( ! empty($fileLinkedList)) {
								foreach ($fileLinkedList as $fileLinked) {
									if (preg_match('/mini/', $fileLinked['name'])) { ?>
										<div class="table-row">
											<div class="table-cell table-50 table-padding-0">
												<?php print '<img class="photo"  width="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('/temp/ticket/' . $ticket_tmp_id . '/thumbs/' . $fileLinked['name']) . '" title="' . dol_escape_htmltag($alt) . '">'; ?>
											</div>
											<div class="table-cell">
												<?php print preg_replace('/_mini/', '', $fileLinked['name']); ?>
											</div>
											<div class="table-cell table-50 table-end table-padding-0">
												<?php print '<div class="linked-file-delete wpeo-button button-square-50 button-transparent" value="' . $fileLinked['name'] . '"><i class="fas fa-trash button-icon"></i></div>'; ?>
											</div>
										</div> <?php
									}
								}
							} else {
								?>
								<div class="table-row">
									<div class="table-cell"><?php print $langs->trans('NoFileLinked'); ?></div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>

		<?php
		if (!$conf->multicompany->enabled) {
			$entity = $conf->entity;
		} else {
			$entity = GETPOST('entity');
		}
		if ($entity > 0 && dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) == 1) {
			if ($conf->global->$digiriskelementVisibleConf) {
				$selectDigiriskElement = '</br> <span ' . (($conf->global->$digiriskelementRequiredConf) ? 'style="font-weight:600"' : '') . '>' . $langs->trans('Service') . (($conf->global->$digiriskelementRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';

				$alldisableddigiriskelement = $digiriskelement->fetchAll('', '', 0, 0, array('customsql' => 't.show_in_selector = 0'));
				if (is_array($alldisableddigiriskelement) && !empty($alldisableddigiriskelement)) {
					$filter = 's.rowid NOT IN (';
					foreach ($alldisableddigiriskelement as $disabled_digiriskelement) {
						$filter .= $disabled_digiriskelement->id . ',';
					}
					$filter = substr($filter, 0, -1);
					$filter .= ')';
				}

				$hideRefConf = 'DIGIRISKDOLIBARR_' . $multiCompanyMention . 'TICKET_DIGIRISKELEMENT_HIDE_REF';
				$selectDigiriskElement .= $digiriskelement->select_digiriskelement_list(GETPOST('options_digiriskdolibarr_ticket_service'), 'options_digiriskdolibarr_ticket_service', (!empty($filter) ? $filter : ''), $langs->trans('PleaseSelectADigiriskElement'), 0, array(), 0, 0, 'minwidth500', 0, false, 1, '', true, $conf->global->$hideRefConf);
				$selectDigiriskElement .= '<div><br></div>';
				print($selectDigiriskElement);
			}

			if ($conf->global->$lastnameVisibleConf) {
				$lastnamefield = '<div class="form-element">';
				$lastnamefield .= '<span class="form-label"' . (($conf->global->$lastnameRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("LastName") . (($conf->global->$lastnameRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$lastnamefield .= '<label class="form-lastname-field-container">';
				$lastnamefield .= '<input class="options_digiriskdolibarr_ticket_lastname" name="options_digiriskdolibarr_ticket_lastname" id="options_digiriskdolibarr_ticket_lastname" value="' . GETPOST('options_digiriskdolibarr_ticket_lastname') . '"/>';
				$lastnamefield .= '</label>';
				$lastnamefield .= '</div>';
				print($lastnamefield);
			}

			if ($conf->global->$firstnameVisibleConf) {
				$firstnamefield = '<div class="form-element">';
				$firstnamefield .= '<span class="form-label"' . (($conf->global->$firstnameRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("FirstName") . (($conf->global->$firstnameRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$firstnamefield .= '<label class="form-firstname-field-container">';
				$firstnamefield .= '<input class="options_digiriskdolibarr_ticket_firstname" name="options_digiriskdolibarr_ticket_firstname" id="options_digiriskdolibarr_ticket_firstname" value="' . GETPOST('options_digiriskdolibarr_ticket_firstname') . '"/>';
				$firstnamefield .= '</label>';
				$firstnamefield .= '</div>';
				print($firstnamefield);
			}

			if ($conf->global->$emailVisibleConf) {
				$emailfield = '<div class="form-element">';
				$emailfield .= '<span class="form-label"' . (($conf->global->$emailRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("Email") . (($conf->global->$emailRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$emailfield .= '<label class="form-field-container">';
				$emailfield .= '<input class="email" name="email" id="email" value="' . GETPOST('email') . '"/>';
				$emailfield .= '</label>';
				$emailfield .= '</div>';
				print($emailfield);
			}

			if ($conf->global->$phoneVisibleConf) {
				$phonefield = '<div class="form-element">';
				$phonefield .= '<span class="form-label"' . (($conf->global->$phoneRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("Phone") . (($conf->global->$phoneRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$phonefield .= '<input class="options_digiriskdolibarr_ticket_phone" name="options_digiriskdolibarr_ticket_phone" id="options_digiriskdolibarr_ticket_phone" value="' . GETPOST('options_digiriskdolibarr_ticket_phone') . '"/>';
				$phonefield .= '</label>';
				$phonefield .= '</div>';
				print($phonefield);
			}

			if ($conf->global->$locationVisibleConf) {
				$locationfield = '<div class="form-element">';
				$locationfield .= '<span class="form-label"' . (($conf->global->$locationRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("Location") . (($conf->global->$locationRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$locationfield .= '<label class="form-field-container">';
				$locationfield .= '<input class="options_digiriskdolibarr_ticket_location" name="options_digiriskdolibarr_ticket_location" id="options_digiriskdolibarr_ticket_location" value="' . GETPOST('options_digiriskdolibarr_ticket_location') . '"/>';
				$locationfield .= '</label>';
				$locationfield .= '</div>';
				print($locationfield);
			}

			if ($conf->global->$dateVisibleConf) {
				$datefield = '<div class="form-element">';
				$datefield .= '<span class="form-label"' . (($conf->global->$dateRequiredConf) ? '' : 'style="font-weight:300"') . '>' . $langs->trans("Date") . (($conf->global->$dateRequiredConf) ? '<span style="color:red"> *</span>' : '') . '</span>';
				$datefield .=  $form->selectDate(dol_now('tzuser'), 'options_digiriskdolibarr_ticket_date', 1, 1, 0, '', 1, 1);
				$datefield .= '</div>';
				print($datefield);
			}
		}

		//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

		if ( ! empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
			print '<div class="center"><label for="email"><span class="fieldrequired">' . $langs->trans("SecurityCode") . '</span><span style="color:red"> *</span></label>';
			print '<span class="span-icon-security inline-block">';
			print '<input id="securitycode" placeholder="' . $langs->trans("SecurityCode") . '" class="flat input-icon-security width125" type="text" maxlength="5" name="code" tabindex="3" />';
			print '</span>';
			print '<span class="nowrap inline-block">';
			print '<img class="inline-block valignmiddle" src="' . DOL_URL_ROOT . '/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
			print '<a class="inline-block valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?entity=' . $entity . '" tabindex="4" data-role="button">' . img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"') . '</a>';
			print '</span>';
			print '</div>';
		}?>

		<?php print '<div class="center"><button form="sendTicketForm" type="submit" id ="actionButtonSave" class="wpeo-button" name="add">' . '<i class="fas fa-paper-plane"></i>    ' . $langs->trans("Send") . '</button>'; ?>
	</div>
	<?php

	print dol_get_fiche_end();

} else {
	print '<div class="ticketpublicarea digirisk-page-container center">';
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] .'">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	print '<h1>' . $langs->trans('WelcomeToPublicTicketInterface') . '</h1>';
	print '<h2>' . $langs->trans('PleaseSelectAnEntity') . '</h2>';

	$multi_entity_selector = dolibarr_get_const($db, 'DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 0);
	if ($multi_entity_selector) {
		print $mc->select_entities('', 'entity');
		print '<input class="wpeo-button button-blue" type="submit" value="'. $langs->trans('Go') .'">';
	} else {
		$entities_list = $mc->getEntitiesList(false, false, true);

		print '<div class="wpeo-gridlayout grid-4">';
		if (!empty($entities_list)) {
			foreach($entities_list as $entityId => $entityName) {
				if (!preg_match('/('.$langs->transnoentities('Hidden').')/', $entityName)) {
					$logos_path = DOL_DATA_ROOT . ($entityId > 1 ? '/' . $entityId . '/' : '/') . 'mycompany/logos/thumbs/';
					$logos_list = dol_dir_list($logos_path);
					if (is_array($logos_list) && !empty($logos_list)) {
						$logo = array_shift($logos_list);
						$logo_src = DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=mycompany&entity=' . $entityId . '&file=' . urlencode('logos/thumbs/' . $logo['name']);
					} else {
						$logo_src = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
					}

					print '<a href="' . $_SERVER["PHP_SELF"] . '?entity=' . $entityId . '">';
					print '<div class="card" style="height: 200px">';
					print '<br>';
					print '<img src="' . $logo_src . '" alt="SocietyLogo" style="width:40%">';
					print '<div class="card-container">';
					print '<h4><b>' . $entityName . '</b></h4>';
					print '</div>';
					print '</div>';
					print '</a>';
				}
			}
		}
		print '</div>';
	}
	print '</form>';
	print '</div>';

}

// End of page
llxFooter('', 'public');
$db->close();
