<?php
/* Copyright (C) 2021-2025 EVARISK <technique@evarisk.com>
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
 * \file    public/ticket/create_ticket.php
 * \ingroup digiriskdolibarr
 * \brief   Ticket creation form for public interface
 */

if ( ! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if ( ! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if ( ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if ( ! defined('NOLOGIN'))        define("NOLOGIN", 1); // This means this output page does not require to be logged.
if ( ! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if ( ! defined('NOIPCHECK'))      define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if ( ! defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

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
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/ticket/mod_ticket_simple.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr_function.lib.php';
require_once __DIR__ . '/../../class/digiriskelement.class.php';

// Load Saturne libraries
require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

global $conf, $db, $hookmanager, $langs, $mc, $user;

// Load translation files required by the page
saturne_load_langs(['companies', 'other', 'mails', 'ticket']);

// Get parameters
$action      = GETPOST('action', 'aZ09');
$entity      = !isModEnabled('multicompany') ? $conf->entity : GETPOSTINT('entity');
$ticketTmpId = GETPOST('ticket_id');

if ( ! dol_strlen($ticketTmpId)) {
	$ticketTmpId = generate_random_id(16);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(['publicticket', 'saturnepublicinterface']);

$object          = new Ticket($db);
$formfile        = new FormFile($db);
$extrafields     = new ExtraFields($db);
$category        = new Categorie($db);
$digiriskelement = new DigiriskElement($db);
$signatory       = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

$form = new Form($db);

$numRefConf = strtoupper($object->element) . '_ADDON';

$numberingModuleName = [
	$object->element => $conf->global->$numRefConf,
];
list($modTicket) = saturne_require_objects_mod($numberingModuleName);

$extrafields->fetch_name_optionals_label($object->table_element);

if ($entity > 0) {
    $conf->setEntityValues($db, $entity);
    $upload_dir = $conf->categorie->multidir_output[isset($entity) ? $entity : 1];
}

if (dolibarr_get_const($db, 'DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT') == 0) {
    $digiriskelement = $digiriskelement->getActiveDigiriskElements();
}

/*
 * Actions
 */

$parameters = [];
$resHook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks.
if ($resHook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($resHook)) {
    if ($action == 'add') {
        $error = 0;

        $parentCategoryId = GETPOST('parentCategory');
        $subCategoryId    = GETPOST('subCategory');
        $message          = GETPOST('message');
        $ticketTmpId      = GETPOST('ticket_id');

        $mainCategoryObject              = $category->rechercher($conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY, '', 'ticket', true);
        $mainCategoryExtrafields         = json_decode($mainCategoryObject[0]->array_options['options_ticket_category_config'], true);
        $mainCategoryChildrenExtrafields = new StdClass();
        $subCategoryExtrafields          = new StdClass();
        $mainCategoryChildrenItem        = null;

        if ( ! empty($mainCategoryObject) && $mainCategoryObject > 0) {
            $mainCategoryChildren = $mainCategoryObject[0]->get_filles();
            if ( ! empty($mainCategoryChildren) && $mainCategoryChildren > 0) {
                foreach ($mainCategoryChildren as $cat) {
                    if ($cat->id == GETPOST('parentCategory')) {
                        $mainCategoryChildrenExtrafields = json_decode($cat->array_options['options_ticket_category_config'], true);
                        $mainCategoryChildrenItem        = $cat;
                    }
                }

                if ($mainCategoryChildrenItem) {
                    $category->fetch($mainCategoryChildrenItem->id);
                    $selectedParentCategoryChildren = $category->get_filles();
                    if ( ! empty($selectedParentCategoryChildren)) {
                        foreach ($selectedParentCategoryChildren as $subCategory) {
                            if ($subCategory->id == GETPOSTINT('subCategory')) {
                                $subCategoryExtrafields = json_decode($subCategory->array_options['options_ticket_category_config'], true);
                            }
                        }
                    }
                }
            }
        }
        $config = array_merge(array_filter($mainCategoryExtrafields ?? []), array_filter($mainCategoryChildrenExtrafields ?? []), array_filter($subCategoryExtrafields ?? []));

        // Check parameters
        if (empty($parentCategoryId)) {
            setEventMessages($langs->trans('ErrorFieldNotEmpty', $conf->global->DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL), array(), 'errors');
            $error++;
        }

        $email = GETPOST('email', 'alpha');
        if ($config['digiriskdolibarr_ticket_email_visible'] && $config['digiriskdolibarr_ticket_email_required']) {
            if (empty($email)) {
                setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Email')), array(), 'errors');
                $error++;
            }
        }

        $firstname = GETPOST('options_digiriskdolibarr_ticket_firstname', 'alpha');
        if ($config['digiriskdolibarr_ticket_firstname_visible'] && $config['digiriskdolibarr_ticket_firstname_required']) {
            if (empty($firstname)) {
                setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('FirstName')), array(), 'errors');
                $error++;
            }
        }

        $lastname = GETPOST('options_digiriskdolibarr_ticket_lastname', 'alpha');
        if ($config['digiriskdolibarr_ticket_lastname_visible'] && $config['digiriskdolibarr_ticket_lastname_required']) {
            if (empty($lastname)) {
                setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('LastName')), array(), 'errors');
                $error++;
            }
        }

        $phone = GETPOST('options_digiriskdolibarr_ticket_phone', 'alpha');
        if ($config['digiriskdolibarr_ticket_phone_visible'] && $config['digiriskdolibarr_ticket_phone_required']) {
            if (empty($phone)) {
                setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentities('Phone')), array(), 'errors');
                $error++;
            }
        }

        $location = GETPOST('options_digiriskdolibarr_ticket_location', 'alpha');
        if ($config['digiriskdolibarr_ticket_location_visible'] && $config['digiriskdolibarr_ticket_location_required']) {
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

        if ($config['digiriskdolibarr_ticket_service_visible'] && $config['digiriskdolibarr_ticket_service_required']) {
            if (empty(GETPOST('options_digiriskdolibarr_ticket_service')) || GETPOST('options_digiriskdolibarr_ticket_service') == -1) {
                setEventMessages($langs->trans('ErrorFieldNotEmpty', $langs->transnoentitiesnoconv('GP/UT')), array(), 'errors');
                $error++;
            }
        }

        $date = GETPOST('options_digiriskdolibarr_ticket_date', 'alpha');
        if ($config['digiriskdolibarr_ticket_date_visible'] && $config['digiriskdolibarr_ticket_date_required']) {
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

        if (!empty($date)) {
            $timeStamp = dol_stringtotime($date);
            $date      = dol_getdate($timeStamp);
            $_POST['options_digiriskdolibarr_ticket_datehour']  = $date['hours'];
            $_POST['options_digiriskdolibarr_ticket_datemin']   = $date['minutes'];
            $_POST['options_digiriskdolibarr_ticket_dateday']   = $date['mday'];
            $_POST['options_digiriskdolibarr_ticket_datemonth'] = $date['mon'];
            $_POST['options_digiriskdolibarr_ticket_dateyear']  = $date['year'];
        }

        if (!empty($config['validate_text'])) {
            $validateText = $config['validate_text'];

            $substitutionarray = getCommonSubstitutionArray($langs);
            complete_substitutions_array($substitutionarray, $langs);
            $object->array_options['options_digiriskdolibarr_condition_message'] = make_substitutions($validateText, $substitutionarray, $langs);
        }

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
                $parentCategoryCat->fetch($parentCategoryId);
                $parentCategoryCat->add_type($object, Categorie::TYPE_TICKET);

                if (!empty($subCategoryId)) {
                    $subCategoryCat = $category;
                    $subCategoryCat->fetch($subCategoryId);
                    $subCategoryCat->add_type($object, Categorie::TYPE_TICKET);
                }

                //Add files linked
                $ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp';
                $fileList          = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticketTmpId . '/');
                $thumbsList        = dol_dir_list($ticket_upload_dir . '/ticket/' . $ticketTmpId . '/thumbs/');
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

                if (GETPOSTISSET('signature')) {
                    $signatory->status         = SaturneSignature::STATUS_SIGNED;
                    $signatory->role           = 'Attendant';
                    $signatory->firstname      = $firstname;
                    $signatory->lastname       = $lastname;
                    $signatory->signature_date = dol_now();
                    $signatory->signature      = GETPOST('signature');
                    $signatory->signature_url  = generate_random_id();
                    $signatory->element_type   = 'user';
                    $signatory->element_id     = 0;
                    $signatory->object_type    = 'ticket';
                    $signatory->fk_object      = $result;
                    $signatory->module_name    = 'digiriskdolibarr';

                    $signatory->create($user);
                    $object->call_trigger('TICKET_SIGN', $user);
                }

                $object->call_trigger('TICKET_PUBLIC_INTERFACE_CREATE', $user);

                // Creation OK
                dol_delete_dir_recursive($ticket_upload_dir . '/ticket/' . $ticketTmpId . '/');
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
        $fullTicketTmpPath = $ticket_upload_dir . '/ticket/' . $ticketTmpId . '/';
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
        $ticket_upload_dir = $conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp/ticket/' . $ticketTmpId . '/';
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
}

/*
 * View
 */

$title  = $langs->trans('CreateTicket');
$moreJS = ['/saturne/js/includes/signature-pad.min.js'];

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

saturne_header(0,'', $title, '', '', 0, 0, $moreJS, [], '', 'page-public-card page-signature');

if ($entity > 0) {
	if ( ! $conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) {
		print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
		$db->close();
		exit();
	}

	print '<div class="ticketpublicarea digirisk-page-container">';

	print load_fiche_titre($langs->trans("CreateTicket"), '', "digiriskdolibarr_color@digiriskdolibarr");

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '?entity=' . $entity . '" id="public-ticket-form">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="entity" value="'. $entity .'">';
	print '<input type="hidden" id="parentCategory" name="parentCategory" value="' . GETPOST('parentCategory') . '">';
	print '<input type="hidden" id="subCategory" name="subCategory" value="' . GETPOST('subCategory') . '">';
	print '<input type="hidden" id="ticket_id" name="ticket_id" value="' . $ticketTmpId . '">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	print dol_get_fiche_head(array(), '0', '', 1);

	print '<div class="img-fields-container">';
	print '<div class="centpercent tableforimgfields form-registre">' . "\n";

	if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED)) : ?>
		<div class="wpeo-notice notice-error">
			<div class="notice-content">
				<div class="notice-title"><strong><?php echo $langs->trans("TicketCategoriesNotCreated"); ?></strong></div>
				<div class="notice-subtitle"><strong><?php echo $langs->trans("HowToSetupTicketCategories") . '  ' ?><a href="../../admin/ticket/ticket.php#TicketCategories"><?php echo $langs->trans('ConfigTicketCategories'); ?></a></strong></div>
			</div>
		</div>
	<?php endif;

	print '<p><strong>' . $conf->global->DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL . '</strong><span style="color:red"> *</span></p>';

	$mainCategoryObject              = $category->rechercher($conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY, '', 'ticket', true);
    $mainCategoryExtrafields         = json_decode($mainCategoryObject[0]->array_options['options_ticket_category_config']);
	$mainCategoryChildrenExtrafields = new StdClass();
    $subCategoryExtrafields          = new StdClass();
    $categoryDescription             = '';
	$mainCategoryChildrenItem		 = null;

	print '<div class="wpeo-gridlayout grid-3 categories-container">';
	if ( ! empty($mainCategoryObject) && $mainCategoryObject > 0) {
		$mainCategoryChildren = $mainCategoryObject[0]->get_filles();
		if ( ! empty($mainCategoryChildren) && $mainCategoryChildren > 0) {
			$k = 1;
			foreach ($mainCategoryChildren as $cat) {
				$catArrayOptions = json_decode($cat->array_options['options_ticket_category_config']);
				if (!empty($catArrayOptions->external_link)) {
					print '<a href="' . $catArrayOptions->external_link . '" class="category-redirect"' . ($catArrayOptions->external_link_new_tab ? 'target="_blank"' : '') . '>';
				}
				if ($cat->id == GETPOST('parentCategory')) {
					$mainCategoryChildrenExtrafields = $catArrayOptions;
                    $categoryDescription             = $cat->description;
					$mainCategoryChildrenItem        = $cat;
					print '<div class="ticket-parentCategory ticket-parentCategory'. $cat->id .' active" id="' . $cat->id . '" data-rowid="' . $cat->id . '">';
				} else {
					print '<div class="ticket-parentCategory ticket-parentCategory'. $cat->id .'" id="' . $cat->id . '" data-rowid="' . $cat->id . '">';
				}
				print '<div class="wpeo-button" style="background:#'. $cat->color.'; border-color:#'. $cat->color .'">';

				show_category_image($cat, $upload_dir);
				print '<span class="button-label">' . $cat->label . '</span>';
				print '</div>';

				print '</div>';
				if ( ! empty($catArrayOptions->external_link)) {
					print '</a>';
				}
				$k++;
			}
			print '</div>';

			print '<div class="centpercent tableforimgfields">' . "\n";

			if ($mainCategoryChildrenItem) {
				$category->fetch($mainCategoryChildrenItem->id);
				$selectedParentCategoryChildren = $category->get_filles();
				if ( ! empty($selectedParentCategoryChildren)) {
					print '<div class="subCategories children'. $cat->id .'">';
					print '<p><strong>' . $conf->global->DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL . '</strong></p>';
					print '<div class="wpeo-gridlayout grid-5">';

					foreach ($selectedParentCategoryChildren as $subCategory) {
						$subCategoryExtrafieldsTmp = json_decode($subCategory->array_options['options_ticket_category_config']);
						if (!empty($subCategoryExtrafieldsTmp->external_link)) {
							print '<a href="' . $subCategoryExtrafieldsTmp->external_link . '" class="category-redirect"' . ($subCategoryExtrafieldsTmp->external_link_new_tab ? 'target="_blank"' : '') . '>';
						}
						if ($subCategory->id == GETPOSTINT('subCategory')) {
							$categoryDescription    = $subCategory->description;
							$subCategoryExtrafields = $subCategoryExtrafieldsTmp;
							print '<div class="ticket-subCategory ticket-subCategory'. $subCategory->id .' center active" id="' . $subCategory->id . '" data-rowid="' . $subCategory->id . '">';
						} else {
							print '<div class="ticket-subCategory ticket-subCategory'. $subCategory->id .' center" id="' . $subCategory->id . '" data-rowid="' . $subCategory->id . '" style="background:#ffffff">';
						}
						show_category_image($subCategory, $upload_dir);
						print '<span class="button-label">' . $subCategory->label . '</span>';
						print '</div>';
						if (!empty($subCategoryExtrafieldsTmp->external_link)) {
							print '</a>';
						}
					}
					print '</div>';
					print '</div>';
				}
			}
			print '</div>';

		}
	}

    if ((GETPOSTISSET('parentCategory') && empty($selectedParentCategoryChildren)) || (GETPOSTISSET('parentCategory') && GETPOSTISSET('subCategory'))) : ?>
        <div class="wpeo-form tableforinputfields">
            <div class="wpeo-gridlayout grid-2">
                <?php
				$visible = $mainCategoryExtrafields->show_description || $mainCategoryChildrenExtrafields->show_description || $subCategoryExtrafields->show_description;
                if ($visible && dol_strlen($categoryDescription) > 0) : ?>
                    <div class="form-element gridw-2">
                        <span class="form-label"><?php print $langs->trans('Description'); ?>
                        <label class="form-field-container">
                            <?php
                                $dolEditor = new DolEditor('category-description', $categoryDescription, '100%', 120, 'dolibarr_readonly', '', false, true, true, ROWS_2, 70, 1);
                                $dolEditor->Create();
                            ?>
                        </label>
                    </div>
                <?php endif;

				$fieldList = [
					'digiriskdolibarr_ticket_photo' => $langs->transnoentities('Photo'),
				];

				if (dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0)) {
					$fieldList['digiriskdolibarr_ticket_email'] = $langs->trans('Email');
					$fieldList = array_merge($fieldList, $extrafields->attributes[$object->table_element]['label']);
				}

				$order = $subCategoryExtrafields->order ?? $mainCategoryExtrafields->order ?? $mainCategoryChildrenExtrafields->order ?? [];
				uksort($fieldList, function($a, $b) use ($order) {
					$indexA = array_search($a, $order);
					$indexB = array_search($b, $order);

					$indexA = ($indexA === false) ? PHP_INT_MAX : $indexA;
					$indexB = ($indexB === false) ? PHP_INT_MAX : $indexB;

					return $indexA <=> $indexB;
				});
				$fieldList = array_merge(['message' => $langs->transnoentities('Message')], $fieldList);

                $fields = [
                    'digiriskdolibarr_ticket_email' => ['type' => 'email', 'name' => 'email'],
                    'digiriskdolibarr_ticket_date'  => ['type' => 'datetime-local']
                ];

				foreach ($fieldList as $key => $label) {
					if (strpos($key, 'digiriskdolibarr_ticket') === false && !in_array($key, ['message'])) {
						continue;
					}
					if ($key == 'digiriskdolibarr_ticket_photo') {
						$key = 'photo';
					}
					$visible = $mainCategoryExtrafields->{$key . '_visible'} || $mainCategoryChildrenExtrafields->{$key . '_visible'} || $subCategoryExtrafields->{$key . '_visible'};
					$required = $mainCategoryExtrafields->{$key . '_required'} || $mainCategoryChildrenExtrafields->{$key . '_required'} || $subCategoryExtrafields->{$key . '_required'};
					if (!$visible && $key != 'message') {
						continue;
					}
					$out  = '<div class="form-element form-field-container">';
					if ($key != 'photo' && $key != 'message') {
						$out .= '<label><span class="form-label"' . ($required ? '' : 'style="font-weight:300"') . '>' . $langs->transnoentities($label) . ($required ? '<span style="color:red"> *</span>' : '') . '</span>';
					}

					switch ($key) {
						case 'message':
							$out .= '<label class="form-field-container">' . ucfirst($key) . '<span style="color:red"> *</span></label>' ;
                            $out .= '<textarea name="message" id="message"' . ($required ? 'required' : '') . '>' . GETPOST('message') . '</textarea>';
							break;
						case 'photo':
							$out .= <<<HTML
								<div class="wpeo-gridlayout grid-2">
									<span class="form-label">{$langs->trans("FilesLinked")}</span>
									<label class="wpeo-button button-blue" for="sendfile">
										<i class="fas fa-image button-icon"></i>
										<span class="button-label">{$langs->trans('AddDocument')}</span>
										<input type="file" name="userfile[]" multiple="multiple" id="sendfile" onchange="window.digiriskdolibarr.ticket.tmpStockFile()"  style="display: none"/>
									</label>
								</div>
							HTML;
							$out .= '<div id="sendFileForm">';
							$out .= '<div id="fileLinkedTable" class="tableforinputfields">';
							$out .= '<div class="wpeo-table table-flex table-3 files-uploaded">';
							$fileLinkedList = dol_dir_list($conf->digiriskdolibarr->multidir_output[isset($conf->entity) ? $conf->entity : 1] . '/temp/ticket/' . $ticketTmpId . '/thumbs/');
							if (!empty($fileLinkedList)) {
								foreach ($fileLinkedList as $fileLinked) {
									if (!preg_match('/mini/', $fileLinked['name'])) {
										continue;
									}
									$out .= '<div class="table-row">';
									$out .= '<div class="table-cell table-50 table-padding-0">';
									$out .= '<img class="photo"  width="' . $maxHeight . '" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . urlencode('/temp/ticket/' . $ticketTmpId . '/thumbs/' . $fileLinked['name']) . '" title="' . dol_escape_htmltag($alt) . '">';
									$out .= '</div>';
									$out .= '<div class="table-cell">';
									$out .= preg_replace('/_mini/', '', $fileLinked['name']);
									$out .= '</div>';
									$out .= '<div class="table-cell table-50 table-end table-padding-0">';
									$out .= '<div class="linked-file-delete wpeo-button button-square-50 button-transparent" value="' . $fileLinked['name'] . '"><i class="fas fa-trash button-icon"></i></div>';
									$out .= '</div></div>';
								}
							} else {
								$out .= '<div class="table-row">';
								$out .= '<div class="table-cell">' . $langs->trans('NoFileLinked') . '</div></div>';
							}
							$out .= '</div></div></div>';
							break;
						case 'digiriskdolibarr_ticket_email':
							$out .= '<input type="' . $fields[$key]['type'] . '" name="' . ($fields[$key]['name'] ?? 'options_' . $key) . '" id="' . ($fields[$key]['name'] ?? 'options_' . $key) . '" value="' . GETPOST($fields[$key]['name'] ?? 'options_' . $key) . '"' . ($required ? 'required' : '') . '/>';
							break;
                        case 'digiriskdolibarr_ticket_service':
                            if (dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TASK_HIDE_REF_IN_DOCUMENT') == 1) {
                                $digiriskelementlabel = [];
                                foreach ($digiriskelement as $element) {
                                    $digiriskelementlabel[] = $element->label;
                                }
                                $out .= $extrafields->showInputField($key, $digiriskelementlabel, ($required ? 'required' : ''), '', '', 0, $object->id, $object->table_element);
                            } else {
							    $out .= $extrafields->showInputField($key, $digiriskelement, ($required ? 'required' : ''), '', '', 0, $object->id, $object->table_element);
                            }
                            break;
						default:
							$out .= $extrafields->showInputField($key, GETPOST($fields[$key]['name'] ?? 'options_' . $key), ($required ? 'required' : ''), '', '', 0, $object->id, $object->table_element);
							break;
					}

					$out .= '</label>';
					$out .= '</div>';
					print($out);
				}
            print '</div>';
            if (!empty($conf->global->DIGIRISKDOLIBARR_USE_CAPTCHA)) {
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
            }
        print '</div>';
        print dol_get_fiche_end();

        $visible = $mainCategoryExtrafields->use_signatory || $mainCategoryChildrenExtrafields->use_signatory || $subCategoryExtrafields->use_signatory;
        if ($visible) {

            $validateText = !empty($mainCategoryExtrafields->validate_text) || !empty($mainCategoryChildrenExtrafields->validate_text) || !empty($subCategoryExtrafields->validate_text);
            if ($validateText) {
                $content = $subCategoryExtrafields->validate_text ?: $mainCategoryChildrenExtrafields->validate_text ?: $mainCategoryExtrafields->validate_text;

                print '<label style="display: flex; align-items: flex-start; margin: 1em 0;">';
                print '<input type="checkbox" id="validate_text_checkbox" name="validate_text" style="margin-right: 1em; margin-top: 0.2em;" required>';
                print '<div style="flex: 1;">';
                $substitutionarray = getCommonSubstitutionArray($langs);
        		complete_substitutions_array($substitutionarray, $langs);
                print make_substitutions($content, $substitutionarray, $langs);
                print '</div>';
                print '</label>';
            }

            // Load Saturne libraries
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            require_once __DIR__ . '/../../../saturne/class/saturnesignature.class.php';

            $signatory = new SaturneSignature($db, $moduleNameLowerCase, $object->element);

            $previousStatus        = $object->status;
            $object->status        = $object::STATUS_READ; // Special case because public answer need draft status object to complete question
            $moreParams['moreCSS'] = 'hidden';             // Needed for prevent click on signature button action
            print '<div style="margin-top: 2em;">';
            require_once __DIR__ . '/../../../saturne/core/tpl/signature/public_signature_view.tpl.php';
            print '</div>';
            $object->status = $previousStatus;
            print '<input type="hidden" name="signature" value="">';
        }
        if ($object->status == $object::STATUS_NOT_READ) {
            print '<div class="public-card__footer center" style="margin-top: 2em;">';
            print '<button type="submit" class="wpeo-button no-load ' . ($visible ? 'public-ticket-validate signature-validate button-disable' : '') . '">' . '<i class="fas fa-paper-plane pictofixedwidth"></i>' . $langs->trans('Send') . '</button>';
            print '</div>';
        }
    endif;
} else {
	print '<div class="ticketpublicarea digirisk-page-container center">';
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] .'">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	print '<h1>' . $langs->transnoentities(dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_TITLE', 0)) ?: $langs->trans('WelcomeToPublicTicketInterface') . '</h1>';
	print '<h2>' . $langs->transnoentities(dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_SUBTITLE', 0)) ?: $langs->trans('PleaseSelectAnEntity') . '</h2>';

	$multi_entity_selector = dolibarr_get_const($db, 'DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', 0);
	if ($multi_entity_selector) {
		print $mc->select_entities('', 'entity');
		print '<input class="wpeo-button button-blue" type="submit" value="'. $langs->trans('Go') .'">';
	} else {
		$entities_list = $mc->getEntitiesList(true, false, true);

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
					print '<div class="card">';
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
