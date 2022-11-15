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
require_once DOL_DOCUMENT_ROOT . '/includes/parsedown/Parsedown.php';

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
$parse       = new Parsedown();
$projectRef  = new $conf->global->PROJECT_ADDON();

// Security check
if ( ! $user->rights->digiriskdolibarr->lire) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$error = 0;

/*
 *  Actions
*/

require_once './core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

if ($action == 'closenotice') {
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_SHOW_PATCH_NOTE", 0, 'integer', 0, '', $conf->entity);
}

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js");
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
<?php endif;

if ($conf->global->DIGIRISKDOLIBARR_SHOW_PATCH_NOTE) : ?>
	<div class="wpeo-notice notice notice-info">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>">
		<div class="notice-content">
			<div class="notice-title"><?php echo $langs->trans("DigiriskPatchNote", $digirisk->version); ?>
				<div class="show-patchnote wpeo-button button-square-40 button-blue wpeo-tooltip-event modal-open" aria-label="<?php echo $langs->trans('ShowPatchNote'); ?>">
					<i class="fas fa-list button-icon"></i>
				</div>
			</div>
		</div>
		<div class="notice-close notice-close-forever wpeo-tooltip-event" aria-label="<?php echo $langs->trans("DontShowPatchNote"); ?>" data-direction="left"><i class="fas fa-times"></i></div>
	</div>

	<div class="wpeo-modal wpeo-modal-patchnote">
		<div class="modal-container wpeo-modal-event" style="max-width: 1280px; max-height: 1000px">
			<!-- Modal-Header -->
			<div class="modal-header">
				<h2 class="modal-title"><?php echo $langs->trans("DigiriskPatchNote", $digirisk->version);  ?></h2>
				<div class="modal-close"><i class="fas fa-times"></i></div>
			</div>
			<!-- Modal Content-->
			<div class="modal-content">
				<?php $ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/evarisk/digirisk/releases/tags/' . $digirisk->version);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_USERAGENT,'DigiriskDolibarr');
				$output  = curl_exec($ch);
				curl_close($ch);
				$data = json_decode($output);
				$data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
				$data->body = preg_replace('/- #\b\d{1,4}\b/', '-', $data->body);
				$html = $parse->text($data->body);
				print $html;
				?>
			</div>
			<!-- Modal-Footer -->
			<div class="modal-footer">
				<div class="wpeo-button button-grey button-uppercase modal-close">
					<span><?php echo $langs->trans('CloseModal'); ?></span>
				</div>
			</div>
		</div>
	</div>
<?php endif;

require_once __DIR__ . '/core/tpl/digiriskdolibarr_dashboard.tpl.php';

// End of page
llxFooter();
$db->close();
