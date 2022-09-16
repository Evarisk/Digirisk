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
 *	\file       digiriskdolibarrindex.php
 *	\ingroup    digiriskdolibarr
 *	\brief      Home page of digiriskdolibarr top menu
 */

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
if ( ! $res && file_exists("../../main.inc.php")) $res    = @include "../../main.inc.php";
if ( ! $res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if ( ! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/project/mod_project_simple.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

require_once './core/modules/modDigiriskDolibarr.class.php';
require_once __DIR__ . '/class/dashboarddigiriskstats.class.php';

global $user, $langs, $conf, $db;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$digirisk    = new modDigiriskdolibarr($db);
$project     = new Project($db);
$third_party = new Societe($db);
$stats       = new DashboardDigiriskStats($db);
$projectRef  = new $conf->global->PROJECT_ADDON();

// Security check
if ( ! $user->rights->digiriskdolibarr->lire) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$WIDTH  = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');


$error = 0;

/*
 *  Actions
*/

require_once './core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

if ($action == 'adddashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['widgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	unset($visible->$dashboardWidgetName);

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

if ($action == 'closedashboardinfo') {
	$data = json_decode(file_get_contents('php://input'), true);

	$dashboardWidgetName = $data['dashboardWidgetName'];

	$visible = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);
	$visible->$dashboardWidgetName = 0;

	$tabparam['DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO'] = json_encode($visible);

	dol_set_user_param($db, $conf, $user, $tabparam);
	$action = '';
}

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("DigiriskDolibarrArea") . ' ' . $digirisk->version, $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("DigiriskDolibarrArea") . ' ' . $digirisk->version, '', 'digiriskdolibarr32px.png@digiriskdolibarr');
?>
<?php if ($conf->global->DIGIRISKDOLIBARR_JUST_UPDATED == 1) : ?>
	<div class="wpeo-notice notice-success">
		<div class="notice-content">
			<div class="notice-subtitle"><strong><?php echo $langs->trans("DigiriskUpdate"); ?></strong>
				<?php echo $langs->trans('DigiriskHasBeenUpdatedTo', $digirisk->version) ?>
			</div>
		</div>
	</div>
<?php
	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_JUST_UPDATED', 0, 'integer', 0, '', $conf->entity);
?>
<?php endif; ?>

<?php if ($conf->global->DIGIRISKDOLIBARR_VERSION != $digirisk->version) : ?>
<?php
	$digirisk->remove();
	global $langs;

	require_once DOL_DOCUMENT_ROOT . '/core/modules/modECM.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modProjet.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modSociete.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modTicket.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modCategorie.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modFckeditor.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/modules/modApi.class.php';

	$modEcm       = new modECM($db);
	$modProjet    = new modProjet($db);
	$modSociete   = new modSociete($db);
	$modTicket    = new modTicket($db);
	$modCategorie = new modCategorie($db);
	$modFckeditor = new modFckeditor($db);
	$modApi       = new modApi($db);

	$modEcm->init();
	$modProjet->init();
	$modSociete->init();
	$modTicket->init();
	$modCategorie->init();
	$modFckeditor->init();
	$modApi->init();
	$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr", "other"));

	$digirisk->init();

	dolibarr_set_const($db, 'DIGIRISKDOLIBARR_JUST_UPDATED', 1, 'integer', 0, '', $conf->entity);
?>
<script>
	window.location.reload()
</script>
<?php endif; ?>
<div class="wpeo-notice notice-info">
	<div class="notice-content">
		<div class="notice-subtitle"><?php echo $langs->trans("DigiriskIndexNotice1"); ?></div>
	</div>
</div>
<?php

$dataseries = array(
	'risk' => $stats->load_dashboard_risk(),
	'task' => $stats->load_dashboard_task()
);

$accidentdata = $stats->load_dashboard_accident();
$riskassementdocumentdata = $stats->load_dashboard_riskassementdocument();

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" class="dashboard" id="dashBoardForm">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="view">';

$dashboardLines = array(
	'daywithoutaccident' => array(
		'label' => $langs->trans("DayWithoutAccident"),
		'content' => $accidentdata,
		'picto' => 'fas fa-user-injured'
	),
	'lastgenerationdateDU' => array(
		'label' => $langs->trans("LastGenerateDate"),
		'content' => $riskassementdocumentdata[0],
		'picto' => 'fas fa-info-circle'
	),
	'nextgenerationdateDU' => array(
		'label' => $langs->trans("NextGenerateDate"),
		'content' => $riskassementdocumentdata[1],
		'picto' => 'fas fa-info-circle'
	),
);

$disableWidgetList = json_decode($user->conf->DIGIRISKDOLIBARR_DISABLED_DASHBOARD_INFO);

print '<div class="add-widget-box" style="' . (!empty($disableWidgetList) ? '' : 'display:none') . '">';
print Form::selectarray('boxcombo', $dashboardLines, -1, $langs->trans("ChooseBoxToAdd") . '...', 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth150onsmartphone hideonprint add-dashboard-widget', 0, 'hidden selected', 0, 1);
if (!empty($conf->use_javascript_ajax)) {
	include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	print ajax_combobox("boxcombo");
}
print '</div>';
print '<div class="fichecenter">';

if (!empty($dashboardLines)) {
	$openedDashBoard = '';
	foreach ($dashboardLines as $key => $dashboardLine) {
		if (!isset($disableWidgetList->$key)) {
			$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">';
			$openedDashBoard .= '<div class="info-box info-box-sm">';
			$openedDashBoard .= '<span class="info-box-icon">';
			$openedDashBoard .= '<i class="' . $dashboardLine["picto"] . '"></i>';
			$openedDashBoard .= '</span>';
			$openedDashBoard .= '<div class="info-box-content">';
			$openedDashBoard .= '<div class="info-box-title" title="' . $langs->trans("Close") . '">';
			$openedDashBoard .= '<span class="close-dashboard-info" data-widgetname="' . $key . '"><i class="fas fa-times"></i></span>';
			$openedDashBoard .= '</div>';
			$openedDashBoard .= '<div class="info-box-lines">';
			$openedDashBoard .= '<div class="info-box-line" style="font-size : 20px;">';
			$openedDashBoard .= '<span class=""><strong>' . $dashboardLine["label"] . ' ' . '</strong>';
			$openedDashBoard .= '<span class="classfortooltip badge badge-info" title="' . $dashboardLine["label"] . ' ' . $dashboardLine["content"] . '" >' . $dashboardLine["content"] . '</span>';
			$openedDashBoard .= '</span>';
			$openedDashBoard .= '</div>';
			$openedDashBoard .= '</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->';
			$openedDashBoard .= '</div><!-- /.info-box -->';
			$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->';
			$openedDashBoard .= '</div>';
		}
	}
	print '<div class="opened-dash-board-wrap"><div class="box-flex-container">' . $openedDashBoard . '</div></div>';
}

print '<div class="box-flex-container">';

if (is_array($dataseries) && !empty($dataseries)) {
	foreach ($dataseries as $keyelement => $datagraph['data']) {
		if (is_array($datagraph['data']) && !empty($datagraph['data'])) {
			$arraykeys = array_keys($datagraph['data']['data']);
			foreach ($arraykeys as $key) {
				$data[$keyelement][] = array(
					0 => $langs->trans($datagraph['data']['labels'][$key]['label']),
					1 => $datagraph['data']['data'][$key]
				);
				$datacolor[$keyelement][] = $langs->trans($datagraph['data']['labels'][$key]['color']);
			}

			$filename[$keyelement] = $keyelement . '.png';
			$fileurl[$keyelement]  = DOL_URL_ROOT . '/viewimage.php?modulepart=digiriskdolibarr&file=' . $keyelement . '.png';

			$graph = new DolGraph();
			$graph->SetData($data[$keyelement]);
			$graph->SetDataColor($datacolor[$keyelement]);
			$graph->SetType(array('pie'));
			$graph->SetWidth($WIDTH);
			$graph->SetHeight($HEIGHT);
			$graph->setShowLegend(2);
			$graph->draw($filename[$keyelement], $fileurl[$keyelement]);
			print '<div class="box-flex-item">';
			print '<div class="titre inline-block">';
			print $datagraph['data']['picto'] . ' ' . $datagraph['data']['title'];
			print '</div>';
			print $graph->show();
			print '</div>';
		}
	}
}

print '</div></div></div>';
print '</form>';

// End of page
llxFooter();
$db->close();
