<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 SuperAdmin
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
 * \file    digiriskdolibarr/admin/setup.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr setup page.
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/custom/digiriskdolibarr/class/digiriskelement.class.php";
require_once '../lib/digiriskdolibarr.lib.php';

// Translations
$langs->loadLangs(array("admin", "digiriskdolibarr@digiriskdolibarr"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');

$arrayofparameters = array(
	'DIGIRISKDOLIBARR_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1),
	'DIGIRISKDOLIBARR_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1)
);

$error = 0;
$setupnotempty = 0;


/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'updateMask')
{
	$maskconstdigiriskelement = GETPOST('maskconstdigiriskelement', 'alpha');
	$maskdigiriskelement = GETPOST('maskdigiriskelement', 'alpha');

	if ($maskconstdigiriskelement) $res = dolibarr_set_const($db, $maskconstdigiriskelement, $maskdigiriskelement, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen')
{
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
		$file = dol_buildpath($reldir."core/modules/digiriskdolibarr/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file))
		{
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
elseif ($action == 'set')
{
	$type = 'workunit';

	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del')
{
	$tmpobjectkey = preg_replace('/(_odt)/', '', $value);
	$type = 'workunit';

	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$constforval = "DIGIRISKDOLIBARR_" . strtoupper($tmpobjectkey).'_ADDON_ODT';
		if ($conf->global->$constforval == "$value") dolibarr_del_const($db, $constforval, $conf->entity);
	}
}

// Set default model
elseif ($action == 'setdoc')
{

	$tmpobjectkey = preg_replace('/(_odt)/', '', $value);
	$constforval = "DIGIRISKDOLIBARR_WORKUNIT_DEFAULT_MODEL";
	$type = 'workunit';
	$label = '';
	if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity))
	{
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
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
	// TODO Check if numbering module chosen can be activated
	// by calling method canBeActivated
	$tmpobjectkey = 'workunit';
	$constforval = 'DIGIRISKDOLIBARR_'.strtoupper($tmpobjectkey)."_ADDON";
	dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
}



/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$help_url = 'FR:Module_DigiriskDolibarr';
$page_name = "DigiriskdolibarrSetup";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_digiriskdolibarr@digiriskdolibarr');

// Configuration header
$head = digiriskdolibarrAdminPrepareHead();
dol_fiche_head($head, 'workunit', '', -1, "digiriskdolibarr@digiriskdolibarr");



/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("DigiriskWorkUnitNumberingModule"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."custom/digiriskdolibarr/core/modules/digiriskdolibarr/");
	// A CHANGER PAR DIGIRISK DOLIBARR
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

					// preg_match('/^workunit/', $file)
					// For compatibility
					if (!is_file($dir.$filebis))
					{
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_digiriskdolibarr_".$file;
					}

					// Check if there is a filter on country
					preg_match('/\-(.*)_(.*)$/', $classname, $reg);
					if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;

					$classname = preg_replace('/\-.*$/', '', $classname);

					if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php' && preg_match('/workunit/i', $classname))
					{
						// Charging the numbering class
						require_once $dir.$filebis;

						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled())
						{
							print '<tr class="oddeven"><td width="100">';
							echo preg_replace('/\-.*$/', '', preg_replace('/mod_digiriskdolibarr_/', '', preg_replace('/\.php$/', '', $file)));
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

							if ($conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON == $file || $conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON.'.php' == $file)
							{
								print img_picto($langs->trans("Activated"), 'switch_on');
							}
							else
							{
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/', '', $file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$workunit = new DigiriskElement($db);
							$workunit->initAsSpecimen();

							// Example for standard invoice
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$workunit->type = 0;
							$nextval = $module->getNextValue($workunit);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValueForInvoices").': ';
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

							if ($conf->global->DIGIRISKDOLIBARR_WORKUNIT_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
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
}
/*
 *  Documents models for Work Unit
 */

print load_fiche_titre($langs->trans("DigiriskTemplateDocumentWorkUnit"), '', '');

// Defini tableau def des modeles
$type = 'workunit';
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

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."custom/digiriskdolibarr/core/modules/digiriskdolibarr/doc");

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
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file) && preg_match('/workunit/i', $file) && preg_match('/odt/i', $file))
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
							if ($conf->global->DIGIRISKDOLIBARR_WORKUNIT_DEFAULT_MODEL == "$name")
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
							$htmltooltip .= '<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);
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
}
print '</table>';

// Page end
dol_fiche_end();

llxFooter();
$db->close();
