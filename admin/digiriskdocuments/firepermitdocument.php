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
 * \file    admin/firepermitdocument.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr firepermitdocument page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../../lib/digiriskdolibarr.lib.php';
require_once '../../class/digiriskdocuments.class.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$type          = 'firepermitdocument';
$error         = 0;

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstfirepermitdocument = GETPOST('maskconstfirepermitdocument', 'alpha');
	$maskfirepermitdocument      = GETPOST('maskfirepermitdocument', 'alpha');

	if ($maskconstfirepermitdocument) $res = dolibarr_set_const($db, $maskconstfirepermitdocument, $maskfirepermitdocument, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Activate a model
if ($action == 'set') {
	$label = GETPOST('label', 'alpha');

	if ( $value == 'firepermitdocument_odt' ) {
		$description = "DIGIRISKDOLIBARR_".strtoupper($type)."_ADDON_ODT_PATH";
	} elseif ( $value == 'firepermitdocument_custom_odt' ) {
		$description = "DIGIRISKDOLIBARR_".strtoupper($type)."_CUSTOM_ADDON_ODT_PATH";
	}

	addDocumentModel($value, $type, $label, $description);
} elseif ($action == 'del') {
	delDocumentModel($value, $type);
}

// Set default model
if ($action == 'setdoc') {
	$constforval = "DIGIRISKDOLIBARR_".strtoupper($type)."_DEFAULT_MODEL";
	$label       = '';

	if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity))
	{
		$conf->global->$constforval = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);

	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label);
	}
} elseif ($action == 'setmod') {
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper($type)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$form            = new Form($db);
$object_document = new DigiriskDocuments($db);

$help_url = 'FR:Module_DigiriskDolibarr#L.27onglet_Document_Digirisk';
$title    = $langs->trans("YourDocuments"). ' - '. $langs->trans("FirePermitDocument");
$morecss  = array("/digiriskdolibarr/css/digiriskdolibarr.css");

llxHeader('', $title, $help_url, '', '', '', '', $morecss);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($title, $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
print dol_get_fiche_head($head, 'digiriskdocuments', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminDigiriskDocumentsPrepareHead();
print dol_get_fiche_head($head, 'firepermitdocument', '', -1, "digiriskdolibarr@digiriskdolibarr");

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskFirePermitDocumentNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '</tr>';

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/".$type."/");
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
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON.'.php' == $file) {
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
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_ADDON.'.php' == $file) { // If module is the one used, we show existing errors
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

/*
*  Documents models for Listing Risks Action
*/

print load_fiche_titre($langs->trans("DigiriskTemplateDocumentFirePermit"), '', '');

// Defini tableau def des modeles
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$type."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center">'.$langs->trans("Status")."</td>";
print '<td class="center">'.$langs->trans("Default")."</td>";
print '<td class="center">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center">'.$langs->trans("Preview").'</td>';
print "</tr>";

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/".$type."/");
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			$filelist[] = $file;
		}
		closedir($handle);
		arsort($filelist);

		foreach ($filelist as $file) {
			if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/firepermitdocument/i', $file) && preg_match('/odt/i', $file)) {
				if (file_exists($dir.'/'.$file)) {
					$name = substr($file, 4, dol_strlen($file) - 16);
					$classname = substr($file, 0, dol_strlen($file) - 12);

					require_once $dir.'/'.$file;
					$module = new $classname($db);

					$modulequalified = 1;
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

					if ($modulequalified) {
						print '<tr class="oddeven"><td>';
						print (empty($module->name) ? $name : $module->name);
						print "</td><td>";
						if (method_exists($module, 'info')) print $module->info($langs);
						else print $module->description;
						print '</td>';

						// Active
						if (in_array($name, $def)) {
							print '<td class="center">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
							print img_picto($langs->trans("Enabled"), 'switch_on');
							print '</a>';
							print "</td>";
						}
						else
						{
							print '<td class="center">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							print "</td>";
						}

						// Default
						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_FIREPERMITDOCUMENT_DEFAULT_MODEL == $name) {
							print img_picto($langs->trans("Default"), 'on');
						}
						else {
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
						}
						print '</td>';

						// Info
						$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
						$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
						$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
						$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
						$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, -1, 0);
						print '</td>';

						// Preview
						print '<td class="center">';
						if ($module->type == 'pdf') {
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'intervention').'</a>';
						}
						else {
							print img_object($langs->trans("PreviewNotAvailable"), 'generic');
						}
						print '</td>';
						print '</tr>';
					}
				}
			}
		}
	}
}

print '</table>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
