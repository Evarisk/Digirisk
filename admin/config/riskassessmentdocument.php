<?php
/* Copyright (C) 2021-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/config/riskassessmentdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr riskassessmentdocument page.
 */

// Load DigiriskDolibarr environment
if (file_exists('../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
	require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
	die('Include of digiriskdolibarr main fails');
}

global $conf, $db, $langs, $mc, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';
require_once __DIR__ . '/../../class/riskanalysis/risk.class.php';
require_once __DIR__ . '/../../class/evaluator.class.php';
require_once __DIR__ . '/../../class/riskanalysis/riskassessment.class.php';
require_once __DIR__ . '/../../class/riskanalysis/risksign.class.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_mobile.lib.php';
require_once __DIR__ . '/../../lib/digiriskdolibarr_riskoverview.lib.php';

$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

// Translations
saturne_load_langs(["admin"]);

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->digiriskdolibarr->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Actions
 */

// Actions update_mask
require_once dol_buildpath('/saturne/core/tpl/actions/admin_conf_actions.tpl.php');

// Les deux méthodes d'évaluation des RPS restent proposées tant que leur constante n'existe pas :
// sans cette création, une installation mise à jour sans réactivation du module afficherait deux
// commutateurs éteints alors que les deux méthodes sont bien proposées dans la liste des risques
if (saturne_check_admin_write_access()) {
    foreach (['DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_METHOD', 'DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_GRID_METHOD'] as $psychosocialRiskMethodConst) {
        if (!isset($conf->global->$psychosocialRiskMethodConst)) {
            dolibarr_set_const($db, $psychosocialRiskMethodConst, 1, 'integer', 0, '', $conf->entity);
        }
    }
}

// Public overview of the risks: its switch saves itself and reloads the page, which creates here the
// key carried by the link the first time the overview is published
if (saturne_check_admin_write_access() && getDolGlobalInt('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_INTERFACE') && !dol_strlen(getDolGlobalString('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY'))) {
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY', bin2hex(random_bytes(24)), 'chaine', 0, '', $conf->entity);
}

// The strenuousness, psychosocial and register tabs of the overview are shown until switched off:
// created here for an installation updated without reactivating the module, whose switches would read off
if (saturne_check_admin_write_access()) {
    foreach (DIGIRISK_RISK_OVERVIEW_TAB_CONSTS as $riskOverviewTabConst) {
        if (!isset($conf->global->$riskOverviewTabConst)) {
            dolibarr_set_const($db, $riskOverviewTabConst, 1, 'integer', 0, '', $conf->entity);
        }
    }
}

// A new key cuts off every link handed out so far
if ($action == 'setNewRiskOverviewKey' && saturne_check_admin_write_access()) {
    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_KEY', bin2hex(random_bytes(24)), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('RiskOverviewPublicKeyRenewed'), null, 'mesgs');
    header('Location: ' . $_SERVER['PHP_SELF'] . '#riskoverview');
    exit;
}

// PIN code asked by the overview after the key of its link, empty for the link alone to open it. A new
// code asks it again to every visitor
if ($action == 'setRiskOverviewPin' && saturne_check_admin_write_access()) {
    $riskOverviewPin = trim(GETPOST('riskOverviewPin', 'alphanohtml'));
    if ($riskOverviewPin !== '' && !preg_match('/^\d{4,8}$/', $riskOverviewPin)) {
        setEventMessages($langs->trans('RiskOverviewPublicPinInvalid'), null, 'errors');
    } else {
        if ($riskOverviewPin === '') {
            dolibarr_del_const($db, 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_PIN_SECRET', $conf->entity);
        } else {
            dolibarr_set_const($db, 'DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_PIN_SECRET', $riskOverviewPin, 'chaine', 0, '', $conf->entity);
        }
        setEventMessages($langs->trans($riskOverviewPin === '' ? 'RiskOverviewPublicPinRemoved' : 'RiskOverviewPublicPinSaved'), null, 'mesgs');
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '#riskoverview');
    exit;
}

if (GETPOST('action') == 'setmod') {
    $value = GETPOST('value');
    $valueArray = explode('_', $value);
    $objectType = $valueArray[1];

    dolibarr_set_const($db, 'DIGIRISKDOLIBARR_'. strtoupper($objectType) .'_ADDON', $value, 'chaine', 0, '', $conf->entity);
}

if (($action == 'update' && ! GETPOST("cancel", 'alpha')) || ($action == 'updateedit')) {
	$evaluatorDuration = GETPOST('EvaluatorDuration', 'alpha');

	if (!empty($evaluatorDuration) || $evaluatorDuration === '0') {
		dolibarr_set_const($db, "DIGIRISKDOLIBARR_EVALUATOR_DURATION", $evaluatorDuration, 'integer', 0, '', $conf->entity);
	}

	if ($action != 'updateedit') {
		setEventMessage($langs->trans('SavedConfig'));
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl  = 'FR:Module_Digirisk#L.27onglet_Analyse_des_risques';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'riskassessmentdocument', $title, -1, 'digiriskdolibarr_color@digiriskdolibarr');


// Risks
print load_fiche_titre('<i class="fas fa-exclamation-triangle"></i> ' . $langs->trans('RiskConfig'), '', '');
print '<hr>';

$object          = new Risk($db);
$objectModSubdir = 'riskanalysis';

require dol_buildpath('/saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php');

$areRisksShared = !empty($mc->entities['risk']) ? strpos($mc->entities['risk'], $conf->entity) : 0;
$areRisksSharable = isModEnabled('multicompany') && !empty($mc->sharings['risk']) && $areRisksShared > 0;

$constArray[$moduleNameLowerCase] = [
	'RiskDescription' => [
		'name'        => 'RiskDescription',
		'description' => 'RiskDescriptionDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_DESCRIPTION',
	],
	'RiskCategoryEdit' => [
		'name'        => 'RiskCategoryEdit',
		'description' => 'RiskCategoryEditDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_CATEGORY_EDIT',
	],
	'MoveRisks' => [
		'name'        => 'MoveRisks',
		'description' => 'MoveRisksDescription',
		'code'        => 'DIGIRISKDOLIBARR_MOVE_RISKS',
	],
	'SortRisksListingsByEvaluation' => [
		'name'        => 'SortRisksListingsByEvaluation',
		'description' => 'SortRisksListingsByEvaluationDescription',
		'code'        => 'DIGIRISKDOLIBARR_SORT_LISTINGS_BY_COTATION',
	],
	'RiskDescriptionPrefill' => [
		'name'        => 'RiskDescriptionPrefill',
		'description' => 'RiskDescriptionPrefillDescription',
		'code'        => 'DIGIRISKDOLIBARR_RISK_DESCRIPTION_PREFILL',
	],
	'ShowRiskOrigin' => [
		'name'        => 'ShowRiskOrigin',
		'description' => 'ShowRiskOriginDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_RISK_ORIGIN',
	],
    'RiskListParentView' => [
        'name'        => 'RiskListParentView',
        'description' => 'RiskListParentViewDescription',
        'code'        => 'DIGIRISKDOLIBARR_RISK_LIST_PARENT_VIEW',
    ],
    'CategoryOnRisk' => [
        'name'        => 'CategoryOnRisk',
        'description' => 'CategoryOnRiskDescription',
        'code'        => 'DIGIRISKDOLIBARR_CATEGORY_ON_RISK',
    ],
	'ShowInheritedRisksInDocuments' => [
		'name'        => 'ShowInheritedRisksInDocuments',
		'description' => 'ShowInheritedRisksInDocumentsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_DOCUMENTS',
	],
	'ShowInheritedRisksInListings' => [
		'name'        => 'ShowInheritedRisksInListings',
		'description' => 'ShowInheritedRisksInListingsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKS_IN_LISTINGS',
	],
	'ShowSharedRisks' => [
		'name'        => 'ShowSharedRisks',
		'description' => $langs->trans('ShowSharedRisksDescription') . (!$areRisksSharable ? '<br>' . img_picto('danger', 'fa-exclamation-triangle') . $langs->trans('DisabledSharedElement') : ''),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_SHARED_RISKS',
        'disabled'    => !$areRisksSharable
	],
    'PsychosocialRiskMethod' => [
        'name'        => 'PsychosocialRiskMethod',
        'description' => 'PsychosocialRiskMethodDescription',
        'code'        => 'DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_METHOD',
    ],
    'PsychosocialRiskGridMethod' => [
        'name'        => 'PsychosocialRiskGridMethod',
        'description' => 'PsychosocialRiskGridMethodDescription',
        'code'        => 'DIGIRISKDOLIBARR_PSYCHOSOCIAL_RISK_GRID_METHOD',
    ],
];

require dol_buildpath('/saturne/core/tpl/admin/object/object_const_view.tpl.php');

// Risk Assessments
print load_fiche_titre('<i class="fas fa-exclamation-circle"></i> ' . $langs->trans('RiskAssessmentConfig'), '', '');
print '<hr>';

$object          = new RiskAssessment($db);
$objectModSubdir = 'riskanalysis';

require dol_buildpath('/saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php');

$constArray[$moduleNameLowerCase] = [
	'AdvancedRiskAssessmentMethod' => [
		'name'        => 'AdvancedRiskAssessmentMethod',
		'description' => 'AdvancedRiskAssessmentMethodDescription',
		'code'        => 'DIGIRISKDOLIBARR_ADVANCED_RISKASSESSMENT_METHOD',
	],
	'MultipleRiskAssessmentMethodName' => [
		'name'        => 'MultipleRiskAssessmentMethodName',
		'description' => 'MultipleRiskAssessmentMethodDescription',
		'code'        => 'DIGIRISKDOLIBARR_MULTIPLE_RISKASSESSMENT_METHOD',
	],
    'ShowAllRiskAssessments' => [
        'name'        => 'ShowAllRiskAssessments',
        'description' => 'ShowAllRiskAssessmentsDescription',
        'code'        => 'DIGIRISKDOLIBARR_SHOW_ALL_RISKASSESSMENTS',
    ],
    'ShowRiskAssessmentDate' => [
        'name'        => 'ShowRiskAssessmentDate',
        'description' => 'ShowRiskAssessmentDateDescription',
        'code'        => 'DIGIRISKDOLIBARR_SHOW_RISKASSESSMENT_DATE',
    ],
    'RiskAssessmentHideDateInDocument' => [
        'name'        => 'RiskAssessmentHideDateInDocument',
        'description' => 'RiskAssessmentHideDateInDocumentDescription',
        'code'        => 'DIGIRISKDOLIBARR_RISKASSESSMENT_HIDE_DATE_IN_DOCUMENT',
    ]
];

require dol_buildpath('/saturne/core/tpl/admin/object/object_const_view.tpl.php');

// Evaluators
print load_fiche_titre('<i class="fas fa-user-check"></i> ' . $langs->trans("EvaluatorConfig"), '', '');
print '<hr>';

$object          = new Evaluator($db);
$objectModSubdir = 'digiriskelement';

require dol_buildpath('/saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php');

print load_fiche_titre($langs->trans("DigiriskEvaluatorData"), '', '');

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="EvaluatorDuration">' . $langs->trans("EvaluatorDuration") . '</label></td>';
print '<td>' . $langs->trans("EvaluatorDurationDescription") . '</td>';
print '<td><input type="number" name="EvaluatorDuration" value="' . $conf->global->DIGIRISKDOLIBARR_EVALUATOR_DURATION . '"></td>';
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';
print '</form>';

// Risk signs
print load_fiche_titre('<i class="fas fa-map-signs"></i> ' . $langs->trans("RiskSignConfig"), '', '');
print '<hr>';


$object          = new RiskSign($db);
$objectModSubdir = 'riskanalysis';

require dol_buildpath('/saturne/core/tpl/admin/object/object_numbering_module_view.tpl.php');

$areRiskSignsShared    = !empty($mc->entities['risksign']) ? strpos($mc->entities['risksign'], $conf->entity) : 0;
$areRisksSignsSharable = isModEnabled('multicompany') && !empty($mc->sharings['risksign']) && $areRiskSignsShared > 0;

$constArray[$moduleNameLowerCase] = [
	'ShowInheritedRiskSigns' => [
		'name'        => 'ShowInheritedRiskSigns',
		'description' => 'ShowInheritedRiskSignsDescription',
		'code'        => 'DIGIRISKDOLIBARR_SHOW_INHERITED_RISKSIGNS',
	],
	'ShowSharedRiskSigns' => [
		'name'        => 'ShowSharedRiskSigns',
		'description' => $langs->trans('ShowSharedRiskSignsDescription') . (!$areRisksSignsSharable ? '<br>' . img_picto('danger', 'fa-exclamation-triangle') . $langs->trans('DisabledSharedElement') : ''),
		'code'        => 'DIGIRISKDOLIBARR_SHOW_SHARED_RISKSIGNS',
        'disabled'    => !$areRisksSignsSharable

    ],
];
require dol_buildpath('/saturne/core/tpl/admin/object/object_const_view.tpl.php');

// Public overview of the risks of the groupments and work units
print '<div id="riskoverview"></div>';
print load_fiche_titre('<i class="fas fa-th pictofixedwidth"></i>' . $langs->trans('RiskOverviewPublicConfig'), '', '');

// Same switch as the other settings of the page, saved on click; the reload shows the link at once
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Parameters') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Status') . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>' . $langs->trans('RiskOverviewPublicInterface') . '</td>';
print '<td>' . $langs->trans('RiskOverviewPublicInterfaceHelp') . '</td>';
print '<td class="center">' . ajax_constantonoff('DIGIRISKDOLIBARR_RISK_OVERVIEW_PUBLIC_INTERFACE', [], null, 0, 0, 1, 2, 0, 1, '', '', 'inline-block', 0, '', saturne_check_admin_write_access() ? 0 : 1) . '</td>';
print '</tr>';
// The tabs the overview shows besides the risks, saved at 0 when switched off: absent, a tab is shown.
// The register tab counts tickets, it needs their module
$riskOverviewTabSwitches = [
    'penibility' => ['RiskOverviewPublicPenibility', 'RiskOverviewPublicPenibilityHelp'],
    'rps'        => ['RiskOverviewPublicPsychosocial', 'RiskOverviewPublicPsychosocialHelp'],
];
if (isModEnabled('ticket')) {
    $riskOverviewTabSwitches['register'] = ['RiskOverviewPublicRegisters', 'RiskOverviewPublicRegistersHelp'];
}
foreach ($riskOverviewTabSwitches as $riskOverviewTab => $riskOverviewTabLabels) {
    print '<tr class="oddeven">';
    print '<td>' . $langs->trans($riskOverviewTabLabels[0]) . '</td>';
    print '<td>' . $langs->trans($riskOverviewTabLabels[1]) . '</td>';
    print '<td class="center">' . ajax_constantonoff(DIGIRISK_RISK_OVERVIEW_TAB_CONSTS[$riskOverviewTab], [], null, 0, 0, 0, 2, 0, 1, '', '', 'inline-block', 0, '', saturne_check_admin_write_access() ? 0 : 1) . '</td>';
    print '</tr>';
}
print '<tr class="oddeven">';
print '<td>' . $langs->trans('RiskOverviewPublicPin') . '</td>';
print '<td>' . $langs->trans('RiskOverviewPublicPinHelp') . '</td>';
print '<td class="center nowraponall">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setRiskOverviewPin">';
// Hidden as a password, the eye shows it to the one who has to hand it out; new-password keeps the
// browser from filling in the Dolibarr password it remembers
print '<input type="password" class="maxwidth100" id="riskOverviewPin" name="riskOverviewPin" value="' . dol_escape_htmltag(digiriskRiskOverviewPublicPin()) . '" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" autocomplete="new-password"' . (saturne_check_admin_write_access() ? '' : ' disabled') . '>';
print showEyeForField('riskOverviewPinEye', 'riskOverviewPin') . ' ';
print '<input type="submit" class="button smallpaddingimp" value="' . $langs->trans('Save') . '"' . (saturne_check_admin_write_access() ? '' : ' disabled') . '>';
print '</form>';
print '</td>';
print '</tr>';
print '</table>';

$riskOverviewPublicUrl = digiriskRiskOverviewPublicUrl();
if (dol_strlen($riskOverviewPublicUrl)) {
    print '<div class="risk-overview-public-link">';
    print '<div class="risk-overview-public-link__qr">' . digiriskGetQrCodeSvg($riskOverviewPublicUrl) . '</div>';
    print '<div>';
    print '<div><strong>' . $langs->trans('RiskOverviewPublicLink') . '</strong></div>';
    print '<div class="opacitymedium small">' . $langs->trans('RiskOverviewPublicLinkAdminHelp') . '</div>';
    if (digiriskRiskOverviewPublicPin() !== '') {
        print '<div class="opacitymedium small"><i class="fas fa-lock pictofixedwidth"></i>' . $langs->trans('RiskOverviewPublicLinkPinHelp') . '</div>';
    }
    print '<div class="risk-overview-public-link__url"><a href="' . dol_escape_htmltag($riskOverviewPublicUrl) . '" target="_blank" rel="noopener">' . dol_escape_htmltag($riskOverviewPublicUrl) . '</a> ' . showValueWithClipboardCPButton($riskOverviewPublicUrl, 0, 'none') . '</div>';
    print '<div class="margintoponly"><a class="button smallpaddingimp" href="' . $_SERVER['PHP_SELF'] . '?action=setNewRiskOverviewKey&token=' . newToken() . '">' . $langs->trans('RiskOverviewRenewPublicKey') . '</a></div>';
    print '</div>';
    print '</div>';
}

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
