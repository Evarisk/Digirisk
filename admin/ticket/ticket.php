<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 *     \file        admin/ticket/ticket.php
 *     \ingroup     digiriskdolibarr
 *     \brief       Page to public interface of module DigiriskDolibarr for ticket
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php";
include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT . '/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_ticket.lib.php';

// Translations
saturne_load_langs(["admin"]);

// Initialize technical objects
$extra_fields = new ExtraFields($db);
$category     = new Categorie($db);
$ticket       = new Ticket($db);

// Initialize view objects
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}
$form      = new Form($db);
$formother = new FormOther($db);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');
$pageY      = GETPOST('page_y', 'int');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$TSProject = GETPOST('TSProject', 'none');
	$TSProject = explode('_', $TSProject);

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_TICKET_PROJECT", $TSProject[0], 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('TicketProjectUpdated'), array());

	if ($action != 'updateedit') {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
		exit;
	}
}

if ($action == 'update_user_group') {
    $TSProject = GETPOST('userGroup', 'none');
    
    echo '<pre>'; print_r( $TSProject ); echo '</pre>'; exit;

    dolibarr_set_const($db, "DIGIRISKDOLIBARR_TICKET_USER_GROUP_ID_FOR_USER_ASSIGN", $TSProject, 'integer', 0, '', $conf->entity);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'setPublicInterface') {
	if (GETPOST('value')) {
        dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'integer', 0, '', $conf->entity);
        setEventMessages($langs->transnoentities('TicketPublicInterfaceEnabled'), array());
    } else {
        dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'integer', 0, '', $conf->entity);
        setEventMessages($langs->transnoentities('TicketPublicInterfaceDisabled'), array(), 'errors');
    }
}

if ($action == 'update_ticket_public_interface_url') {
    $urlInfos             = ['origin', 'short', 'external'];
    $publicInterfaceTypes = array_merge(['current'], isModEnabled('multicompany') ? ['multicompany'] : []);
    foreach ($publicInterfaceTypes as $publicInterfaceType) {
        $radio = GETPOST($publicInterfaceType . '_ticket_public_interface_url');
        foreach ($urlInfos as $urlType) {
            if ($radio != $urlType . dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL') {
                continue;
            }

            $url = GETPOST($urlType . '_' . $publicInterfaceType . '_ticket_public_interface_url', 'custom', 0, FILTER_SANITIZE_URL);
            if (empty($url)) {
                setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities(dol_ucfirst($urlType) . 'URL')) . '<br>' . $langs->transnoentities(dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL'), [], 'errors');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
                exit;
            }

            dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_' . dol_strtoupper($publicInterfaceType) . '_PUBLIC_INTERFACE_URL_' . dol_strtoupper($urlType), $url, 'chaine', 0, '', $conf->entity);
        }

        dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_' . dol_strtoupper($publicInterfaceType) . '_PUBLIC_INTERFACE_RADIO', $radio, 'chaine', 0, '', $conf->entity);
    }

    setEventMessages('SavedConfig', []);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
    exit;
}

if ($action == 'setEmails') {
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO', GETPOST('emails'), 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('EmailsToNotifySet'), array());
}

if ($action == 'generateExtrafields') {
    $commonExtraFieldsValue = [
        'alwayseditable' => 1, 'list' => 1, 'help' => '', 'entity' => 0, 'langfile' => 'digiriskdolibarr@digiriskdolibarr', 'enabled' => "isModEnabled('digiriskdolibarr') && isModEnabled('ticket')", 'moreparams' => ['css' => 'minwidth100 maxwidth300']
    ];

    $extraFieldsArrays = [
        'digiriskdolibarr_ticket_lastname'  => ['Label' => 'LastName',        'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630210,                                                                                                        ],
        'digiriskdolibarr_ticket_firstname' => ['Label' => 'FirstName',       'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630220,                                                                                                        ],
        'digiriskdolibarr_ticket_phone'     => ['Label' => 'Phone',           'type' => 'varchar', 'length' => 255,  'elementtype' => ['ticket'], 'position' => 43630230,                                                                                                        ],
        'digiriskdolibarr_ticket_service'   => ['Label' => 'GP/UT',           'type' => 'link',                      'elementtype' => ['ticket'], 'position' => 43630240, 'params' => ['DigiriskElement:digiriskdolibarr/class/digiriskelement.class.php:1' => NULL], 'list' => 4],
        'digiriskdolibarr_ticket_location'  => ['Label' => 'Location',        'type' => 'varchar',  'length' => 255, 'elementtype' => ['ticket'], 'position' => 43630250,                                                                                                        ],
        'digiriskdolibarr_ticket_date'      => ['Label' => 'DeclarationDate', 'type' => 'datetime',                  'elementtype' => ['ticket'], 'position' => 43630260,                                                                                                        ]
    ];

    saturne_manage_extrafields($extraFieldsArrays, $commonExtraFieldsValue);
    setEventMessages($langs->transnoentities('ExtrafieldsCreated'), []);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 1, 'integer', 0, '', 0);
}

if ($action == 'generateCategories') {
	global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

	$upload_dir = $conf->categorie->multidir_output[$conf->entity?:1];

	$result = createTicketCategory($langs->transnoentities('Register'), '', '', 1, 'ticket');

	$category = new Categorie($db);
	$category->fetch($result);

	$category->table_element = 'categorie';
	$category->array_options['options_ticket_category_config'] = json_encode([
		"digiriskdolibarr_ticket_lastname_visible" => "on",
		"digiriskdolibarr_ticket_lastname_required" => "on",
		"digiriskdolibarr_ticket_firstname_visible" => "on",
		"digiriskdolibarr_ticket_firstname_required" => "on",
		"digiriskdolibarr_ticket_phone_visible" => "on",
		"digiriskdolibarr_ticket_phone_required" => "",
		"digiriskdolibarr_ticket_service_visible" => "on",
		"digiriskdolibarr_ticket_service_required" => "on",
		"digiriskdolibarr_ticket_location_visible" => "on",
		"digiriskdolibarr_ticket_location_required" => "",
		"digiriskdolibarr_ticket_date_visible" => "on",
		"digiriskdolibarr_ticket_date_required" => "on",
	]);
	$category->updateExtraField('ticket_category_config');


	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $result, 'integer', 0, '', $conf->entity);

	if ($result > 0) {

		$result2 = createTicketCategory($langs->transnoentities('Accident'), '', 'FFA660', 1, 'ticket', $result,'pictogramme_Accident_32px.png');

		if ($result2 > 0) {

			createTicketCategory($langs->transnoentities('PresquAccident'), '', '', 1, 'ticket', $result2,"pictogramme_presqu-accident_64px.png");
			createTicketCategory($langs->transnoentities('AccidentWithoutDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-benin_64px.png');
			createTicketCategory($langs->transnoentities('AccidentWithDIAT'), '', '', 1, 'ticket', $result2,'pictogramme_accident-du-travail_64px.png');

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		$result3 = createTicketCategory($langs->transnoentities('SST'), '', '7594F6', 1, 'ticket', $result,'pictogramme_Sante-et-securite_32px.png');

		if ($result3 > 0) {

			createTicketCategory($langs->transnoentities('AnticipatedLeave'), '', '', 1, 'ticket', $result3,'pictogramme_depart-anticipe_64px.png');
			createTicketCategory($langs->transnoentities('HumanProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-humain_64px.png');
			createTicketCategory($langs->transnoentities('MaterialProblem'), '', '', 1, 'ticket', $result3,'pictogramme_Probleme-materiel_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result3,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		createTicketCategory($langs->transnoentities('DGI'), '', 'E96A6A', 1, 'ticket', $result,'pictogramme_Danger-grave-et-imminent_32px.png');

		$result4 = createTicketCategory($langs->transnoentities('Quality'), '', 'FFDF77', 1, 'ticket', $result,'pictogramme_Qualité_32px.png');

		if ($result4 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result4,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('EnhancementSuggestion'), '', '', 1, 'ticket', $result4,"pictogramme_suggestions-amelioration_64px.png");

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		$result5 = createTicketCategory($langs->transnoentities('Environment'), '', '5CD264', 1, 'ticket', $result,'pictogramme_environnement_32px.png');

		if ($result5 > 0) {

			createTicketCategory($langs->transnoentities('NonCompliance'), '', '', 1, 'ticket', $result5,'pictogramme_non-conformite_64px.png');
			createTicketCategory($langs->transnoentities('Others'), '', '', 1, 'ticket', $result5,'pictogramme_autres_64px.png');

		} else {
			setEventMessages($category->error, array(), 'errors');
		}

		if ($result2 > 0 && $result3 > 0 && $result4 > 0) {
			dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED', 1, 'integer', 0, '', $conf->entity);
			setEventMessages($langs->transnoentities('CategoriesCreated'), array());
		}
	} else {
		setEventMessages($category->error, array(), 'errors');
	}
}

if ($action == 'setMainCategory') {
	$category_id = GETPOST('mainCategory');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY', $category_id, 'integer', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('MainCategorySet'), array());
}

if ($action == 'setParentCategoryLabel') {
	$label = GETPOST('parentCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('ParentCategoryLabelSet'), array());
}

if ($action == 'setChildCategoryLabel') {
	$label = GETPOST('childCategoryLabel');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL', $label, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('ChildCategoryLabelSet'), array());
}

if ($action == 'setTicketSuccessMessage') {
	$successmessage = GETPOST('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', 'none');
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->transnoentities('TicketSuccessMessageSet'), array());
}

if ($action == 'generateQRCode') {
	$urlToEncode = GETPOST('urlToEncode');
	$targetPath = GETPOST('targetPath');
	$size = '400x400';

	ob_clean();

	$QR = imagecreatefrompng('https://quickchart.io/qr?text=' .urlencode($urlToEncode. '&size='.$size )); // chart.googleapis.com n'existe plus

	if (! is_dir($targetPath)) {
		mkdir($targetPath, 0777, true);
	}

	$targetPath = $targetPath . "ticketQRCode.png";

	imagepng($QR,$targetPath);
	setEventMessages($langs->transnoentities('QRCodeGenerated'), array());
}

if ($action == 'createTimeRange') {
    $comparatorPost  = GETPOST('comparator');
    $rangeNumberPost = GETPOST('range_value');
    $timeRangePost   = GETPOST('time_range');
    $constraintLabel = GETPOST('range_label');

    if (empty($rangeNumberPost)) {
        setEventMessage($langs->trans('MissingRangeValue'), 'errors');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
        exit;
    }
    if (dol_strlen($constraintLabel) == 0) {
        $constraintLabel = $langs->trans($langs->transnoentities(ucfirst($comparatorPost) . 'Than') . ' ' . $rangeNumberPost . ' ' . $langs->transnoentities(ucfirst($timeRangePost)));
    }

    $accidentWorkStopTimeRangesJson = $conf->global->DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE;
    $accidentWorkStopTimeRanges     = json_decode($accidentWorkStopTimeRangesJson, true);
    $accidentWorkStopTimeRanges[$constraintLabel]  = $comparatorPost . ':' . $rangeNumberPost . ':' . $timeRangePost;

    $newTimeRangeJson = json_encode($accidentWorkStopTimeRanges);

    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE', $newTimeRangeJson, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->transnoentities('TimeRangeAdded'), array());

    header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
    exit;
}

if ($action == 'deleteTimeRange') {
    $constraintLabel = GETPOST('value');
    $accidentWorkStopTimeRangesJson = $conf->global->DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE;
    $accidentWorkStopTimeRanges     = json_decode($accidentWorkStopTimeRangesJson, true);
    unset($accidentWorkStopTimeRanges[$constraintLabel]);

    $newTimeRangeJson = json_encode($accidentWorkStopTimeRanges);

    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE', $newTimeRangeJson, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->transnoentities('TimeRangeDeleted'), array());

    header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
    exit;
}

if ($action == 'set_multi_company_ticket_public_interface') {
    $multiCompanyTicketPublicInterfaceTitle    = GETPOST('multiCompanyTicketPublicInterfaceTitle', 'none');
    $multiCompanyTicketPublicInterfaceSubtitle = GETPOST('multiCompanyTicketPublicInterfaceSubtitle', 'none');
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_TITLE', $multiCompanyTicketPublicInterfaceTitle, 'chaine', 0, '', 0);
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_SUBTITLE', $multiCompanyTicketPublicInterfaceSubtitle, 'chaine', 0, '', 0);

    setEventMessage('SavedConfig');
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page_y=' . $pageY);
    exit;
}

/*
 * View
 */

$title    = $langs->transnoentities("ModuleSetup", $moduleName);
$helpUrl  = 'FR:Module_Digirisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->transnoentities("BackToModuleList") . '</a>';

print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();

print dol_get_fiche_head($head, 'ticket', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

$enableDisableHtml = $langs->transnoentities("TicketActivatePublicInterface") . ' ';
if (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE)) {
	// Button off, click to enable
	$enableDisableHtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=1">';
	$enableDisableHtml .= img_picto($langs->transnoentities("Disabled"), 'switch_off');
} else {
	// Button on, click to disable
	$enableDisableHtml .= '<a class="reposition valignmiddle" href="' . $_SERVER["PHP_SELF"] . '?action=setPublicInterface&token=' . newToken() . '&value=0">';
	$enableDisableHtml .= img_picto($langs->transnoentities("Activated"), 'switch_on');
}
$enableDisableHtml .= '</a>';
print $enableDisableHtml;
print '<input type="hidden" id="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" name="DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE" value="' . (empty($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1) . '">';

print '<br><br>';

if ($conf->global->DIGIRISKDOLIBARR_TICKET_ENABLE_PUBLIC_INTERFACE == 1) {

    // Public interface configuration
    print load_fiche_titre($langs->transnoentities('TicketsPublicInterfaceConfig'), '', '');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="ticket_public_interface_form">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_ticket_public_interface_url">';
    print '<input type="hidden" name="page_y">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Name') . '</td>';
    print '<td class="widthcentpercentminusx">' . $langs->transnoentities('Value') . '</td>';
    print '</tr>';

    // Ticket public interface URL
    $urlInfos = [
        'origin'   => 'https://dolibarr.org',
        'short'    => 'https://demo.digirisk.com/registre',
        'external' => 'https://evarisk.com/help'
    ];
    $publicInterfaceTypes = array_merge(['current'], isModEnabled('multicompany') ? ['multicompany'] : []);
    foreach ($publicInterfaceTypes as $publicInterfaceType) {
        print '<tr class="oddeven"><td>' . $langs->transnoentities(dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL') . '</td>';
        print '<td class="widthcentpercentminusx">';
        foreach ($urlInfos as $urlType => $placeholder) {
            print '<input type="radio" id="' . $urlType . '-' . $publicInterfaceType . '-ticket-public-interface-url" name="' . $publicInterfaceType . '_ticket_public_interface_url" value="' . $urlType . dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL"' . (getDolGlobalString('DIGIRISKDOLIBARR_TICKET_' . dol_strtoupper($publicInterfaceType) . '_PUBLIC_INTERFACE_RADIO') == $urlType . dol_ucfirst($publicInterfaceType) . 'TicketPublicInterfaceURL' ? 'checked' : '') . '/>';
            $link                     = img_picto('', 'external-link-alt', 'class="paddingright"');
            $ticketPublicInterfaceURL = getDolGlobalString('DIGIRISKDOLIBARR_TICKET_' . dol_strtoupper($publicInterfaceType) . '_PUBLIC_INTERFACE_URL_' . dol_strtoupper($urlType));
            if (!empty($ticketPublicInterfaceURL)) {
                $link = '<a href="' . $ticketPublicInterfaceURL . '" target="_blank">' . img_picto('', 'external-link-alt', 'class="paddingright"') . '</a>';
            }
            print '<label for="' . $urlType . '-' . $publicInterfaceType . '-ticket-public-interface-url" id="' . $urlType . '-' . $publicInterfaceType . '-ticket-public-interface-url-label">' . $link . $langs->transnoentities(dol_ucfirst($urlType) . 'URL');
            if (!empty($ticketPublicInterfaceURL)) {
                print showValueWithClipboardCPButton($ticketPublicInterfaceURL, 0, 'none');
            }
            print '</label><br>';
            print '<input type="url" name="' . $urlType . '_' . $publicInterfaceType . '_ticket_public_interface_url" id="' . $urlType . '-' . $publicInterfaceType . '-ticket-public-interface-url-input" class="marginleftonly widthcentpercentminusx" placeholder="' . $placeholder . '" pattern="https?://.*" size="30" value="' . $ticketPublicInterfaceURL . '" /><br>';
        }
        print '</td></tr>';
    }

    print '</table>';
    print '<div class="tabsAction reposition"><button type="submit" class="butAction">' . $langs->trans('Save') . '</button></div>';
    print '</form>';

    print load_fiche_titre($langs->transnoentities('Config'), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

    // Show logo for company
    print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketShowCompanyLogo") . '</td>';
    print '<td class="center">';
    print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO');
    print '</td>';
    print '<td class="center">';
    print '';
    print '</td>';
    print '<td class="center">';
    print $form->textwithpicto('', $langs->transnoentities("TicketShowCompanyLogoHelp"));
    print '</td>';
    print '</tr>';

	// Show logo for company
	print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketShowCompanyLogo") . '</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_SHOW_COMPANY_LOGO');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("TicketShowCompanyLogoHelp"));
	print '</td>';
	print '</tr>';

	// GP/UT Hide ref
	print '<tr class="oddeven"><td>' . $langs->transnoentities("TicketDigiriskElementHideRef") . '</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_DIGIRISKELEMENT_HIDE_REF');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("TicketDigiriskElementHideRefHelp"));
	print '</td>';
	print '</tr>';

    // Use signatory
    print '<tr class="oddeven"><td>';
    print $langs->transnoentities('PublicInterfaceUseSignatoryDescription');
    print '</td><td class="center">';
    print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_USE_SIGNATORY');
    print '</td>';
    print '<td class="center"></td>';
    print '<td class="center">';
    print $form->textwithpicto('', $langs->transnoentities('TicketPublicInterfaceUseSignatoryDescription'));
    print '</td></tr>';

    // Show category description
    print '<tr class="oddeven"><td>';
    print $langs->transnoentities('TicketPublicInterfaceShowCategoryDescription');
    print '</td><td class="center">';
    print ajax_constantonoff('DIGIRISKDOLIBARR_TICKET_PUBLIC_INTERFACE_SHOW_CATEGORY_DESCRIPTION');
    print '</td>';
    print '<td class="center"></td>';
    print '<td class="center">';
    print $form->textwithpicto('', $langs->transnoentities('TicketPublicInterfaceShowCategoryDescriptionHelp'));
    print '</td></tr>';

	if (isModEnabled('multicompany')) {
		//Page de sélection de l'entité
		print '<tr class="oddeven"><td>' . $langs->transnoentities("ShowSelectorOnTicketPublicInterface") . '</td>';
		print '<td class="center">';
		print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_MULTI_ENTITY_SELECTOR_ON_TICKET_PUBLIC_INTERFACE', [], 0);
		print '</a>';
		print '</td>';
		print '<td class="center">';
		print '';
		print '</td>';
		print '<td class="center">';
		print $form->textwithpicto('', $langs->transnoentities("ShowSelectorOnTicketPublicInterfaceHelp"));
		print '</td>';
		print '</tr>';
	}

	//Envoi d'emails automatique
	print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailOnTicketSubmit") . '</td>';
	print '<td class="center">';
	print ajax_constantonoff('DIGIRISKDOLIBARR_SEND_EMAIL_ON_TICKET_SUBMIT');
	print '</td>';
	print '<td class="center">';
	print '';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("SendEmailOnTicketSubmitHelp"));
	print '</td>';
	print '</tr>';

	//Email to send ticket submitted
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setEmails">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("SendEmailTo") . '</td>';
	print '<td class="center">';
	print '<input name="emails" id="emails" value="' . $conf->global->DIGIRISKDOLIBARR_TICKET_SUBMITTED_SEND_MAIL_TO . '">';
	print '</td>';
	print '<td class="center">';
	print '<input type="submit" class="button reposition" value="'. $langs->transnoentities('Save').'">';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("MultipleEmailsSeparator"));
	print '</td>';
	print '</tr>';
	print '</form>';

	print load_fiche_titre($langs->transnoentities("TicketSuccessMessageData"), '', '');

	print '<table class="noborder centpercent">';

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setTicketSuccessMessage">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->transnoentities("Name").'</td>';
	print '<td>' . $langs->transnoentities("Description") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print "</tr>";

	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $ticket);
	complete_substitutions_array($substitutionarray, $langs, $ticket);

	// Substitution array/string
	$helpforsubstitution = '';
	if (is_array($substitutionarray) && count($substitutionarray)) {
		$helpforsubstitution .= $langs->trans('AvailableVariables').' :<br>'."\n";
	}
	foreach ($substitutionarray as $key => $val) {
		$helpforsubstitution .= $key.' -> '.$langs->trans(dol_string_nohtmltag(dolGetFirstLineOfText($val))).'<br>';
	}

	// Ticket success message
	$successmessage = $langs->transnoentities($conf->global->DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE) ?: $langs->transnoentities('YouMustNotifyYourHierarchy');
	print '<tr class="oddeven"><td>'.$form->textwithpicto($langs->transnoentities("TicketSuccessMessage"), $helpforsubstitution, 1, 'help', '', 0, 2, 'substittooltipfrombody');
	print '</td><td>';
	$doleditor = new DolEditor('DIGIRISKDOLIBARR_TICKET_SUCCESS_MESSAGE', $successmessage, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td><input type="submit" class="button reposition" name="save" value="' . $langs->transnoentities("Save") . '">';
	print '</td></tr>';
	print '</form>';
	print '</table>';

	print '</div>';

    // Multi company ticket public interface config
    print load_fiche_titre($langs->transnoentities('MultiCompanyTicketPublicInterfaceConfig'), '', '');

    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_multi_company_ticket_public_interface">';
    print '<input type="hidden" name="page_y">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans('Parameters') . '</td>';
    print '<td>' . $langs->trans('Description') . '</td>';
    print '<td class="center">' . $langs->trans('Action') . '</td>';
    print '</tr>';

    // Multi company ticket public interface title
    $multiCompanyTicketPublicInterfaceTitle = $langs->transnoentities(getDolGlobalString('DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_TITLE')) ?: $langs->transnoentities('WelcomeToPublicTicketInterface');
    print '<tr class="oddeven"><td>' . $langs->trans('Title') . '</td>';
    print '<td>';
    $dolEditor = new DolEditor('multiCompanyTicketPublicInterfaceTitle', $multiCompanyTicketPublicInterfaceTitle, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
    $dolEditor->Create();
    print '</td><td class="center">';
    print $form->buttonsSaveCancel('Save', '', [], 1, 'reposition');
    print '</td></tr>';

    // Multi company ticket public interface subtitle
    $multiCompanyTicketPublicInterfaceSubtitle = $langs->transnoentities(getDolGlobalString('DIGIRISKDOLIBARR_TICKET_MULTI_COMPANY_PUBLIC_INTERFACE_SUBTITLE')) ?: $langs->transnoentities('PleaseSelectAnEntity');
    print '<tr class="oddeven"><td>' . $langs->trans('Subtitle') . '</td>';
    print '<td>';
    $dolEditor = new DolEditor('multiCompanyTicketPublicInterfaceSubtitle', $multiCompanyTicketPublicInterfaceSubtitle, '100%', 120, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
    $dolEditor->Create();
    print '</td><td class="center">';
    print $form->buttonsSaveCancel('Save', '', [], 1, 'reposition');
    print '</td></tr>';
    print '</table>';
    print '</form>';

	// Project
	if (isModEnabled('project')) {
		print load_fiche_titre($langs->transnoentities("LinkedProject"), '', '');

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="project_form">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="page_y">';
		print '<table class="noborder centpercent editmode">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->transnoentities("Name") . '</td>';
		print '<td>' . $langs->transnoentities("SelectProject") . '</td>';
		print '<td>' . $langs->transnoentities("Action") . '</td>';
		print '</tr>';

		$langs->load("projects");
		print '<tr class="oddeven"><td><label for="TSProject">' . $langs->transnoentities("TSProject") . '</label></td><td>';
		$numprojet = $formproject->select_projects(-1,  $conf->global->DIGIRISKDOLIBARR_TICKET_PROJECT, 'TSProject', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities("AddProject") . '"></span></a>';
		print '<td><input type="submit" class="button reposition" name="save" value="' . $langs->transnoentities("Save") . '">';
		print '</td></tr>';

		print '</table>';
		print '</form>';
	}

    // Project
    print load_fiche_titre($langs->transnoentities('UserGroup'), '', '');

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="user_group_form">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_user_group">';
    print '<table class="noborder centpercent editmode">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->transnoentities('Parameter') . '</td>';
    print '<td>' . $langs->transnoentities('Description') . '</td>';
    print '<td>' . $langs->transnoentities('Value') . '</td>';
    print '<td>' . $langs->transnoentities('Action') . '</td>';
    print '</tr>';

    print '<tr class="oddeven"><td><label for="userGroup">' . $langs->transnoentities('UserGroup') . '</label></td>';
    print '<td>' . $langs->transnoentities('Choix du groupe d\'affectation des utilisateurs') . '</td>';
    print '<td>' . $form->select_dolgroups(getDolGlobalInt('DIGIRISKDOLIBARR_TICKET_USER_GROUP_ID_FOR_USER_ASSIGN'),  'userGroup', 1, 0, 0, 0, 0, 0, 0, 'maxwidth500') . '</td>';
    //print ' <a href="' . DOL_URL_ROOT . '/projet/card.php?&action=create&status=1&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?action=create') . '"><span class="fa fa-plus-circle valignmiddle" title="' . $langs->transnoentities("AddProject") . '"></span></a>';
    print '<td><input type="submit" class="button reposition" name="save" value="' . $langs->transnoentities('Save') . '">';
    print '</td></tr>';

    print '</table>';
    print '</form>';

	print load_fiche_titre($langs->transnoentities("TicketCategories"), '', '', 0, 'TicketCategories');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	//Categories generation
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateCategories">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateTicketCategories") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 1</a></sup></td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
	print '</td>';
	print '<td class="center">';
	print $conf->global->DIGIRISKDOLIBARR_TICKET_CATEGORIES_CREATED ? '<a type="" class=" butActionRefused" value="">'.$langs->transnoentities('Create') .'</a>' : '<input type="submit" class="button reposition" value="'.$langs->transnoentities('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("CategoriesGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set default main category
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setMainCategory">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("MainCategory") . '</td>';
	print '<td class="center">';
	print $formother->select_categories('ticket', $conf->global->DIGIRISKDOLIBARR_TICKET_MAIN_CATEGORY,'mainCategory');
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button reposition" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("MainCategorySetting"));
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set parent category label
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setParentCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("ParentCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 2</a></sup></td>';
	print '<td class="center">';
	print '<input name="parentCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_PARENT_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button reposition" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ParentCategorySetting"));
	print '</td>';
	print '</tr>';
	print '</form>';

	//Set child category label
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="setChildCategoryLabel">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("ChildCategoryLabel") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 3</a></sup></td>';
	print '<td class="center">';
	print '<input name="childCategoryLabel" value="'. $conf->global->DIGIRISKDOLIBARR_TICKET_CHILD_CATEGORY_LABEL .'">';
	print '</td>';

	print '<td class="center">';
	print '<input type="submit" class="button reposition" value="'. $langs->transnoentities('Save').'">';
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ChildCategorySetting"));
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

	// Extrafields generation
	print load_fiche_titre($langs->transnoentities("TicketExtrafields"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateExtrafields">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateExtrafields") . '<sup><a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" > 4</a></sup></td>';
	print '<td class="center">';
	print dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) ? $langs->transnoentities('AlreadyGenerated') : $langs->transnoentities('NotCreated');
	print '</td>';
	print '<td class="center">';
    print dolibarr_get_const($db, 'DIGIRISKDOLIBARR_TICKET_EXTRAFIELDS', 0) ? '<a type="" class=" butActionRefused" value="">'.$langs->transnoentities('Create') .'</a>' : '<input type="submit" class="button reposition" value="'.$langs->transnoentities('Create') .'">' ;
	print '</td>';

	print '<td class="center">';
	print $form->textwithpicto('', $langs->transnoentities("ExtrafieldsGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';

	print '</table>';
	print '</div>';

	// Entity QR Code generation
	print load_fiche_titre($langs->transnoentities("CompanyQRCodeGeneration"), '', '');

	$qrCodePath = $conf->digiriskdolibarr->multidir_output[$conf->entity?:1] . "/ticketqrcode/";
	$QRCodeList = dol_dir_list($qrCodePath);
	if (is_array($QRCodeList) && !empty($QRCodeList)) {
		$QRCode = array_shift($QRCodeList);
	} else {
		$QRCode = array();
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->transnoentities("Parameters") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
	print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
	print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
	print '</tr>';

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="generateQRCode">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    print '<input type="hidden" name="page_y">';

	print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateQRCode") . '</td>';

	$targetPath  = $qrCodePath;
	$urlToEncode = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php?entity=' . $conf->entity;

	print '<input hidden name="targetPath" value="'. $targetPath .'">';
	print '<input hidden name="urlToEncode" value="'. $urlToEncode .'">';

	print '<td class="center">';
	print array_key_exists('fullname', $QRCode) ? $langs->transnoentities('QRCodeAlreadyGenerated') : $langs->transnoentities('NotGenerated');
	print '</td>';
	print '<td class="center">';
	if (array_key_exists('fullname', $QRCode)) {
		$urladvanced = getAdvancedPreviewUrl('digiriskdolibarr', 'ticketqrcode/' . $QRCode['name']);
		print '<a class="clicked-photo-preview" href="'. $urladvanced .'">' . '<img width="200" src="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'" alt="' . $langs->transnoentities("TicketPublicInterfaceQRCode") . '"></a>';
		print '<a id="download" href="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'" download="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=' . $conf->entity . '&file=' . 'ticketqrcode/' . $QRCode['name'] .'"><i class="fas fa-download"></i></a>';
	} else {
		print '<input type="submit" class="button reposition" value="'.$langs->transnoentities('Generate') .'">' ;
	}
	print '</td>';

	print '<td class="center minwidth800">';
	print $form->textwithpicto('', $langs->transnoentities("QRCodeGeneration"));
	print '</td>';
	print '</tr>';
	print '</form>';

	if (isModEnabled('multicompany')) {

		// Multi Entity QR Code generation
		print load_fiche_titre($langs->transnoentities("MultiCompanyQRCodeGeneration"), '', '');

		$qrCodePath = DOL_DATA_ROOT . "/digiriskdolibarr/multicompany/ticketqrcode/";
		$QRCodeList = dol_dir_list($qrCodePath);

		if (is_array($QRCodeList) && !empty($QRCodeList)) {
			$QRCode = array_shift($QRCodeList);
		} else {
			$QRCode = array();
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->transnoentities("Parameters") . '</td>';
		print '<td class="center">' . $langs->transnoentities("Status") . '</td>';
		print '<td class="center">' . $langs->transnoentities("Action") . '</td>';
		print '<td class="center">' . $langs->transnoentities("ShortInfo") . '</td>';
		print '</tr>';

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="generateQRCode">';
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
        print '<input type="hidden" name="page_y">';

		print '<tr class="oddeven"><td>' . $langs->transnoentities("GenerateQRCode") . '</td>';

		$targetPath = $qrCodePath;
		$urlToEncode = DOL_MAIN_URL_ROOT . '/custom/digiriskdolibarr/public/ticket/create_ticket.php';

		print '<input hidden name="targetPath" value="'. $targetPath .'">';
		print '<input hidden name="urlToEncode" value="'. $urlToEncode .'">';

		print '<td class="center">';
		print array_key_exists('fullname', $QRCode) ? $langs->transnoentities('QRCodeAlreadyGenerated') : $langs->transnoentities('NotGenerated');
		print '</td>';
		print '<td class="center">';
		if (array_key_exists('fullname', $QRCode)) {
			$urladvanced = getAdvancedPreviewUrl('digiriskdolibarr', 'ticketqrcode/' . $QRCode['name']);
			print '<a class="clicked-photo-preview" href="'. $urladvanced .'">' . '<img width="200" src="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=1&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] .'" alt="' . $langs->transnoentities("MultiEntityTicketPublicInterfaceQRCode") . '"></a>';
			print '<a id="download" href="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr&entity=1&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] .'" download="'.DOL_URL_ROOT . '/custom/digiriskdolibarr/documents/viewimage.php?modulepart=digiriskdolibarr'. '&file=' . 'multicompany/ticketqrcode/' . $QRCode['name'] .'"><i class="fas fa-download"></i></a>';
		} else {
			print '<input type="submit" class="button reposition" value="'.$langs->transnoentities('Generate') .'">' ;
		}
		print '</td>';

		print '<td class="center minwidth800">';
		print $form->textwithpicto('', $langs->transnoentities("QRCodeGeneration"));
		print '</td>';
		print '</tr>';
		print '</form>';
	}

	print '</table>';
	print '</div>';
	print '<span class="opacitymedium">' . $langs->transnoentities("TicketPublicInterfaceConfigDocumentation") . '</span> : <a href="https://wiki.dolibarr.org/index.php?title=Module_Digirisk#DigiRisk_-_Registre_de_s.C3.A9curit.C3.A9_et_Tickets" target="_blank" >' . $langs->transnoentities('DigiriskDocumentation') . '</a>';
}

print load_fiche_titre($langs->transnoentities("TicketStatistics"), '', '');

$comparators = [
    'less' => $langs->trans('Inferior'),
    'more' => $langs->trans('Superior')
];
$range = [
    'days' => $langs->trans('Days'),
    'weeks' => $langs->trans('Weeks'),
    'months' => $langs->trans('Months'),
    'years' => $langs->trans('Years')
];

$accidentWorkStopTimeRangesJson = $conf->global->DIGIRISKDOLIBARR_TICKET_STATISTICS_ACCIDENT_TIME_RANGE;
$accidentWorkStopTimeRanges     = json_decode($accidentWorkStopTimeRangesJson, true);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->transnoentities("RangeLabel") . '</td>';
print '<td>' . $langs->transnoentities("Comparator") . '</td>';
print '<td>' . $langs->transnoentities("RangeNumber") . '</td>';
print '<td>' . $langs->transnoentities("TimeRange") . '</td>';
print '<td>' . $langs->transnoentities("Action") . '</td>';
print '</tr>';

// Existing constraints
if (is_array($accidentWorkStopTimeRanges) && !empty($accidentWorkStopTimeRanges)) {
    foreach ($accidentWorkStopTimeRanges as $rangeName => $rangeConstraint) {
        if (strstr($rangeConstraint, ':')) {
            $rangeConstraintDetails = explode(':', $rangeConstraint);
            $rangeComparator        = $rangeConstraintDetails[0] == 'less' ? $langs->trans('LessThan') : $langs->trans('MoreThan');
            $rangeNumber            = $rangeConstraintDetails[1];
            $rangeUnit              = $langs->trans(ucfirst($rangeConstraintDetails[2]));
        }
        print '<tr>';
        print '<td>';
        print $langs->transnoentities($rangeName);
        print '</td>';
        print '<td>';
        print $rangeComparator;
        print '</td>';
        print '<td>';
        print $rangeNumber;
        print '</td>';
        print '<td>';
        print $rangeUnit;
        print '</td>';
        print '<td>';
        print '<a href="'. $_SERVER['PHP_SELF'] . '?action=deleteTimeRange&token=' . newToken() . '&value=' . $rangeName.'" class="wpeo-button button-grey reposition">';
        print '<i class="fas fa-trash"></i>';
        print '</a>';
        print '</td>';
    }
}

// Add new constraint
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="createTimeRange">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
print '<input type="hidden" name="page_y">';
print '<tr>';
print '<td>';
print '<input type="text" name="range_label" />';
print '</td>';
print '<td>';
print $form::selectarray('comparator', $comparators);
print '</td>';
print '<td>';
print '<input type="number" min="0" name="range_value" />';
print '</td>';
print '<td>';
print $form::selectarray('time_range', $range);
print '</td>';
print '<td>';
print '<button type="submit" class="wpeo-button button-blue reposition"><i class="fas fa-plus"></i></button>';
print '</td>';
print '</td>';
print '</tr>';
print '</form>';

// End of page
llxFooter();
$db->close();
