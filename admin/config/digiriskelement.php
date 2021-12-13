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
 * \file    admin/digiriskelement.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr digiriskelement page.
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

global $langs, $user, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../../lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * View
 */

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_.C3.89l.C3.A9ment_Digirisk';
$title    = $langs->trans("Arborescence");

$morejs   = array("/digiriskdolibarr/js/digiriskdolibarr.js.php");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', $morejs, $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'digiriskdolibarr32px@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'digiriskelement', '', -1, "digiriskdolibarr@digiriskdolibarr");

$types = array(
	'groupment' => 'Groupment',
	'workunit' => 'WorkUnit'
);

$pictos = array(
	'groupment' => '<span class="ref" style="font-size: 10px; color: #fff; text-transform: uppercase; font-weight: 600; display: inline-block; background: #263C5C; padding: 0.2em 0.4em; line-height: 10px !important">GP</span> ',
	'workunit' => '<span class="ref" style="background: #0d8aff;  font-size: 10px; color: #fff; text-transform: uppercase; font-weight: 600; display: inline-block;; padding: 0.2em 0.4em; line-height: 10px !important">WU</span> '
);

/*
 *  Numbering module
 */
foreach ($types as $type => $typeWithCase) {

	print load_fiche_titre( $pictos[$type] . $langs->trans($typeWithCase . 'Management'), '', '');
	print '<hr>';
	print load_fiche_titre($langs->trans("Digirisk" . $typeWithCase . "NumberingModule"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="nowrap">'.$langs->trans("Example").'</td>';
	print '<td class="center">'.$langs->trans("Status").'</td>';
	print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>';

	clearstatcache();

	$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskelement/".$type."/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false ) {
				if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
					$filebis = $file;

					$classname = preg_replace('/\.php$/', '', $file);
					$classname = preg_replace('/\-.*$/', '', $classname);

					if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
						// Charging the numbering class
						require_once $dir.$filebis;

						$module = new $classname($db);

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td>';
							print $langs->trans($module->name);
							print "</td><td>";
							print $module->info();
							print '</td>';

							// Show example of numbering module
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
							elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
							else print $tmp;
							print '</td>';

							print '<td class="center">';
							$typeConf = 'DIGIRISKDOLIBARR_' . strtoupper($type) . '_ADDON';

							if ($conf->global->$typeConf == $file || $conf->global->$typeConf.'.php' == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							}
							else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							// Example for listing risks action
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($object_document);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValue").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
										$nextval = $langs->trans($nextval);
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							if ($conf->global->$typeConf.'.php' == $file) { // If module is the one used, we show existing errors
								if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
							}
							print '</td>';
							print "</tr>";
						}
					}
				}
			}
			closedir($handle);
		}
	}

	print '</table>';
	print '<hr>';

}

/*
 *  Deleted Elements
 */

print load_fiche_titre('<i class="fas fa-trash"></i> ' . $langs->trans('DeletedElements'), '', '');
print '<hr>';

print load_fiche_titre($langs->trans("DeletedDigiriskElement"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('DeletedDigiriskElement');
print "</td><td>";
print $langs->trans('ShowDeletedDigiriskElement');
print '</td>';

print '<td class="center">';
print ajax_constantonoff('DIGIRISKDOLIBARR_SHOW_HIDDEN_DIGIRISKELEMENT');
print '</td>';
print '</tr>';

print '</table>';
// Page end

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

