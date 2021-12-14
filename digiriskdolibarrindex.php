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
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/mod_project_simple.php';
require_once './core/modules/modDigiriskDolibarr.class.php';

global $user, $langs, $conf, $db;

// Load translation files required by the page
$langs->loadLangs(array("digiriskdolibarr@digiriskdolibarr"));

// Initialize technical objects
$digirisk    = new modDigiriskdolibarr($db);
$project     = new Project($db);
$third_party = new Societe($db);
$projectRef  = new $conf->global->PROJECT_ADDON();

// Security check
if (!$user->rights->digiriskdolibarr->lire) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$error = 0;

require_once './core/tpl/digiriskdolibarr_projectcreation_action.tpl.php';

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr';
$morejs   = array("/digiriskdolibarr/js/tiny-slider.min.js", "/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/tiny-slider.min.css", "/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader("", $langs->trans("DigiriskDolibarrArea") . ' ' . $digirisk->version, $help_url, '', '', '', $morejs, $morecss);

print load_fiche_titre($langs->trans("DigiriskDolibarrArea") . ' ' . $digirisk->version, '', 'digiriskdolibarr32px.png@digiriskdolibarr');
?>

<?php if ($conf->global->DIGIRISKDOLIBARR_VERSION < $digirisk->version) : ?>
<div class="wpeo-notice notice-warning">
	<div class="notice-content">
		<div class="notice-subtitle"><?php echo $langs->trans("WarningDigiriskNotUpdated"); ?>
			<a href="<?php echo DOL_URL_ROOT.'/admin/modules.php?mainmenu=home'?>" target="_blank"><?php echo DOL_URL_ROOT.'/admin/modules.php?mainmenu=home'?></a>
		</div>
	</div>
</div>
<?php endif; ?>
<div class="wpeo-notice notice-info">
	<div class="notice-content">
		<div class="notice-subtitle"><?php echo $langs->trans("DigiriskIndexNotice1"); ?></div>
	</div>
</div>
<div class="wpeo-carousel">
	<div class="slide-element bloc-1">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide1Content1"); ?></h2>
				<p><a href="https://www.legifrance.gouv.fr/affichTexte.do?cidTexte=JORFTEXT000000408526&categorieLien=id" class="center" target="_blank"><?php echo $langs->trans("PresentationSlide1Content2"); ?></a></p>
				<p class="light oversize center"><?php echo $langs->trans("PresentationSlide1Content3"); ?></p>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-1.jpg'?>" alt="01" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-2">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide2Content1"); ?></h2>
				<p><?php echo $langs->trans("PresentationSlide2Content2"); ?></p>
				<p><?php echo $langs->trans("PresentationSlide2Content3"); ?></p>
				<p><?php echo $langs->trans("PresentationSlide2Content4"); ?></p>
				<p><?php echo $langs->trans("PresentationSlide2Content5"); ?></p>
				<ul>
					<li><a href="http://www.inrs.fr/media.html?refINRS=ED%20887" target="_blank"><?php echo $langs->trans("PresentationSlide2Content6"); ?></a></li>
					<li><a href="http://travail-emploi.gouv.fr/publications/picts/bo/05062002/A0100004.htm" target="_blank"><?php echo $langs->trans("PresentationSlide2Content7"); ?></a></li>
				</ul>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-2.jpg'?>" alt="02" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-3">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2 class="center"><?php echo $langs->trans("PresentationSlide3Content1"); ?></h2>
				<p class="center"><a href="http://www.larousse.fr/dictionnaires/francais/risque/69557#8VAKqHCtvXCADLK3.99" target="_blank"><?php echo $langs->trans("PresentationSlide3Content2"); ?></a></p>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-3.jpg'?>" alt="03" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-4">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h3><?php echo $langs->trans("PresentationSlide4Content1"); ?></h3>
				<p><?php echo $langs->trans("PresentationSlide4Content2"); ?></p>
				<h3><?php echo $langs->trans("PresentationSlide4Content3"); ?></h3>
				<p><?php echo $langs->trans("PresentationSlide4Content4"); ?></p>
				<h2 class="center"><?php echo $langs->trans("PresentationSlide4Content5"); ?></h2>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-4.jpg'?>" alt="04" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-5">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide5Content1"); ?></h2>
				<p><?php echo $langs->trans("PresentationSlide5Content2"); ?></p>
				<ul>
					<li><?php echo $langs->trans("PresentationSlide5Content3"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide5Content4"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide5Content5"); ?></li>
				</ul>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-5.jpg'?>" alt="05" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-6">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide6Content1"); ?></h2>
				<p><?php echo $langs->trans("PresentationSlide6Content2"); ?></p>
				<h3><?php echo $langs->trans("PresentationSlide6Content3"); ?></h3>
				<p><?php echo $langs->trans("PresentationSlide6Content4"); ?></p>
				<ul>
					<li><?php echo $langs->trans("PresentationSlide6Content5"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide6Content6"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide6Content7"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide6Content8"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide6Content9"); ?></li>
				</ul>
				<p><?php echo $langs->trans("PresentationSlide6Content10"); ?></p>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-6.jpg'?>" alt="06" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-7">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2>
					<?php echo $langs->trans("PresentationSlide7Content1"); ?> <a href="http://www.inrs.fr/media.html?refINRS=ED%20840" target="_blank"><?php echo $langs->trans("PresentationSlide7Content2"); ?></a>
				</h2>
				<p><?php echo $langs->trans("PresentationSlide7Content3"); ?></p>
				<ul>
					<li><?php echo $langs->trans("PresentationSlide7Content4"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide7Content5"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide7Content6"); ?></li>
				</ul>
				<p><?php echo $langs->trans("PresentationSlide7Content7"); ?></p>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-7.jpg'?>" alt="07" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-8">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide8Content1"); ?></h2>
				<ul>
					<li><?php echo $langs->trans("PresentationSlide8Content2"); ?></li>
					<li><?php echo $langs->trans("PresentationSlide8Content3"); ?></li>
				</ul>
				<p><a href="http://www.inrs.fr/media.html?refINRS=outil10" target="_blank"><?php echo $langs->trans("PresentationSlide8Content4"); ?></a></p>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-8.jpg'?>" alt="08" />
			</div>
		</div>
	</div>

	<div class="slide-element bloc-9">
		<div class="wpeo-gridlayout grid-gap-0 padding grid-2">
			<div class="content">
				<h2><?php echo $langs->trans("PresentationSlide9Content1"); ?></h2>
				<p><?php echo $langs->trans("PresentationSlide9Content2"); ?></p>
				<ul>
					<li><a href="https://fr.libreoffice.org/" target="_blank"><?php echo $langs->trans("PresentationSlide9Content3"); ?></a> <?php echo $langs->trans("PresentationSlide9Content4"); ?></li>
					<li><a href="https://www.service-public.fr/professionnels-entreprises/vosdroits/F23106" target="_blank"><?php echo $langs->trans("PresentationSlide9Content5"); ?></a></li>
				</ul>
			</div>
			<div>
				<img src="<?php echo DOL_URL_ROOT . '/custom/digiriskdolibarr/img/install/slide-9.jpg'?>" alt="09" />
			</div>
		</div>
	</div>
</div>

<?php
// End of page
llxFooter();
$db->close();
