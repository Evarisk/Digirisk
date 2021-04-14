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
 * \file    digiriskdolibarr/admin/preventionplan.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr preventionplan page.
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/custom/digiriskdolibarr/lib/digiriskdolibarr.lib.php');
dol_include_once('/custom/digiriskdolibarr/class/digiriskdocuments.class.php');

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

$type          = 'preventionplan';
$error         = 0;
$setupnotempty = 0;

/*
 * Actions
 */
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
	$maskconstpreventionplan = GETPOST('maskconstpreventionplan', 'alpha');
	$maskpreventionplan      = GETPOST('maskpreventionplan', 'alpha');

	if ($maskconstpreventionplan) $res = dolibarr_set_const($db, $maskconstpreventionplan, $maskpreventionplan, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Activate a model
elseif ($action == 'set')
{
	$label = GETPOST('label', 'alpha');

	if ( $value == 'preventionplan_odt' ) {
		$description = "DIGIRISKDOLIBARR_".strtoupper($type)."_ADDON_ODT_PATH";
	} elseif ( $value == 'preventionplan_custom_odt' ) {
		$description = "DIGIRISKDOLIBARR_".strtoupper($type)."_CUSTOM_ADDON_ODT_PATH";
	}

	addDocumentModel($value, $type, $label, $description);
} elseif ($action == 'del')
{
	delDocumentModel($value, $type);
}

// Set default model
elseif ($action == 'setdoc')
{
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
} elseif ($action == 'setmod')
{
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper($type)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$form            = new Form($db);
$object_document = new DigiriskDocuments($db);

$help_url  = 'FR:Module_DigiriskDolibarr';
$page_name = "DigiriskdolibarrSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'digiriskdocuments', '', -1, "digiriskdolibarr@digiriskdolibarr");
$head = digiriskdolibarrAdminDigiriskDocumentsPrepareHead();
dol_fiche_head($head, 'preventionplan', '', -1, "digiriskdolibarr@digiriskdolibarr");

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskPreventionPlanNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/".$type."/");
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle)) !== false )
		{
			if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
			{
				$filebis = $file;

				$classname = preg_replace('/\.php$/', '', $file);
				$classname = preg_replace('/\-.*$/', '', $classname);

				if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php')
				{
					// Charging the numbering class
					require_once $dir.$filebis;

					$module = new $classname($db);

					if ($module->isEnabled())
					{
						print '<tr class="oddeven"><td width="100">';
						print $langs->trans($module->name);
						print "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
						elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON.'.php' == $file)
						{
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else
						{
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
						if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
						{
							if (!empty($module->error)) dol_htmloutput_mesg($module->error, '', 'error', 1);
						}
						print '</td>';
						print "</tr>\n";
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

print load_fiche_titre($langs->trans("DigiriskTemplateDocumentPreventionPlan"), '', '');

// Defini tableau def des modeles
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$type."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
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
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$dir = dol_buildpath("/custom/digiriskdolibarr/core/modules/digiriskdolibarr/digiriskdocuments/".$type."/");
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle)) !== false)
		{
			$filelist[] = $file;
		}
		closedir($handle);
		arsort($filelist);

		foreach ($filelist as $file)
		{
			if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/preventionplan/i', $file) && preg_match('/odt/i', $file))
			{
				if (file_exists($dir.'/'.$file))
				{
					$name = substr($file, 4, dol_strlen($file) - 16);
					$classname = substr($file, 0, dol_strlen($file) - 12);

					require_once $dir.'/'.$file;
					$module = new $classname($db);

					$modulequalified = 1;
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

					if ($modulequalified)
					{
						print '<tr class="oddeven"><td width="100">';
						print (empty($module->name) ? $name : $module->name);
						print "</td><td>\n";
						if (method_exists($module, 'info')) print $module->info($langs);
						else print $module->description;
						print '</td>';

						// Active
						if (in_array($name, $def))
						{
							print '<td class="center">'."\n";
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
							print img_picto($langs->trans("Enabled"), 'switch_on');
							print '</a>';
							print "</td>";
						}
						else
						{
							print '<td class="center">'."\n";
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							print "</td>";
						}

						// Default
						print '<td class="center">';
						if ($conf->global->DIGIRISKDOLIBARR_PREVENTIONPLAN_DEFAULT_MODEL == $name)
						{
							print img_picto($langs->trans("Default"), 'on');
						}
						else
						{
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
						if ($module->type == 'pdf')
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'intervention').'</a>';
						}
						else
						{
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
dol_fiche_end();

llxFooter();
$db->close();
