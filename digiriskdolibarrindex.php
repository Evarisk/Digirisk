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
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';

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

require_once __DIR__ . '/core/tpl/digiriskdolibarr_dashboard.tpl.php';

// End of page
llxFooter();
$db->close();
