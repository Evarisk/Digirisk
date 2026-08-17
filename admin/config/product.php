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
 * \file    admin/config/product.php
 * \ingroup digiriskdolibarr
 * \brief   Digiriskdolibarr product setup page.
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
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formprojet.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

require_once __DIR__ . '/../../lib/digiriskdolibarr.lib.php';

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

$error = 0;

if (($action == 'update' && ! GETPOST("cancel", 'alpha'))) {
	$risksIcon = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON', 'alpha');
	$risksColor = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_SVG_COLOR', 'alpha');
	$risksDesc = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS', 'restricthtml');

	dolibarr_set_const($db, "DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON", $risksIcon, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_SVG_COLOR", $risksColor, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS", $risksDesc, 'chaine', 0, '', $conf->entity);

	// Ensure icons directory exists
	$iconsDir = $conf->digiriskdolibarr->dir_output . '/icons';
	if (!is_dir($iconsDir)) {
		dol_mkdir($iconsDir);
	}

	// Chapter configurations
	$chapters = ['IDENTIFICATION', 'SECURITY', 'USERMANUAL', 'QUALIFICATION', 'HYGIENE', 'MAINTENANCE'];
	foreach ($chapters as $chap) {
		$label = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_LABEL', 'alpha');
		$icon  = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_ICON', 'alpha');
		$color = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_COLOR', 'alpha');
		$desc  = GETPOST('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_DESC', 'restricthtml');
		
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_LABEL', $label, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_ICON', $icon, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_COLOR', $color, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $chap . '_DESC', $desc, 'chaine', 0, '', $conf->entity);
		
		// Handle SVG upload
		$fileKey = 'file_' . $chap;
		if (!empty($_FILES[$fileKey]['name']) && $_FILES[$fileKey]['error'] == 0) {
			$ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
			if (strtolower($ext) == 'svg') {
				$destName = 'digirisk_' . strtolower($chap) . '_icon.svg';
				dol_move_uploaded_file($_FILES[$fileKey]['tmp_name'], $iconsDir . '/' . $destName, 1);
			}
		}
	}

	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}

// Actions set_mod, update_mask
require_once __DIR__ . '/../../../saturne/core/tpl/actions/admin_conf_actions.tpl.php';

/*
 * View
 */

$form = new Form($db);

$title    = $langs->trans("ModuleSetup", $moduleName);
$helpUrl = 'FR:Module_Digirisk';

saturne_header(0,'', $title, $helpUrl);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

// Configuration header
$head = digiriskdolibarr_admin_prepare_head();
print dol_get_fiche_head($head, 'product', $title, -1, "digiriskdolibarr_color@digiriskdolibarr");

print load_fiche_titre('<i class="fas fa-cube"></i> ' . $langs->trans("ProductManagement"), '', '');
print '<hr>';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

// Default Risk Icon
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON">' . $langs->trans("DefaultRiskIcon", "Icone des risques par défaut") . '</label><br><small>ex: fas fa-exclamation-triangle</small></td><td>';
print '<input type="text" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON" value="' . dol_escape_htmltag($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_ICON ?? 'fas fa-exclamation-triangle') . '" class="minwidth200">';
print '</td></tr>';

// Default Risk Color
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_SVG_COLOR">' . $langs->trans("DefaultRiskColor", "Couleur des risques par défaut") . '</label><br><small>ex: #D32F2F</small></td><td>';
print '<input type="color" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_SVG_COLOR" value="' . dol_escape_htmltag($conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS_SVG_COLOR ?? '#D32F2F') . '">';
print '</td></tr>';

// Default Risk Description
print '<tr class="oddeven"><td><label for="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS">' . $langs->trans("DefaultRiskDescription", "Description des risques par défaut") . '</label></td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS', $conf->global->DIGIRISKDOLIBARR_PRODUCT_DEFAULT_RISKS ?? '', '', 150, 'dolibarr_details', 'In', false, true, true, ROWS_4, '100%');
$doleditor->Create();
print '</td></tr>';
print '</table>';

// Configuration for FI Chapters
print '<br>';
print load_fiche_titre('<i class="fas fa-file-pdf"></i> ' . $langs->trans("ProductFIChaptersManagement", "Gestion des chapitres de la Fiche d'Instruction (FI)"), '', '');
print '<hr>';

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Chapter") . '</td>';
print '<td>' . $langs->trans("Label") . '</td>';
print '<td>' . $langs->trans("Icon") . '</td>';
print '<td>' . $langs->trans("Color") . '</td>';
print '<td>' . $langs->trans("SVG Image") . ' (.svg)</td>';
print '</tr>';

$chaptersConfig = [
	'IDENTIFICATION' => ['name' => 'Identification & Caractéristiques', 'icon' => 'fas fa-tag', 'color' => '#c07500'],
	'SECURITY'       => ['name' => 'Sécurité & Protections', 'icon' => 'fas fa-shield-alt', 'color' => '#b72020'],
	'USERMANUAL'     => ['name' => 'Mode d\'emploi simplifié', 'icon' => 'fas fa-book', 'color' => '#1a7a3c'],
	'QUALIFICATION'  => ['name' => 'Qualification & Habilitation', 'icon' => 'fas fa-graduation-cap', 'color' => '#1a5fa8'],
	'HYGIENE'        => ['name' => 'Hygiène & Nettoyage', 'icon' => 'fas fa-broom', 'color' => '#0e7e7e'],
	'MAINTENANCE'    => ['name' => 'Maintenance & Contrôles', 'icon' => 'fas fa-wrench', 'color' => '#8b4000']
];

$iconsDir = $conf->digiriskdolibarr->dir_output . '/icons';

foreach ($chaptersConfig as $key => $default) {
	$labelVal = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_LABEL'} ?? '';
	$iconVal  = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_ICON'} ?? $default['icon'];
	$colorVal = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_COLOR'} ?? $default['color'];
	$descVal  = $conf->global->{'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_DESC'} ?? '';

	$svgName = 'digirisk_' . strtolower($key) . '_icon.svg';
	$hasSvg = file_exists($iconsDir . '/' . $svgName);

	print '<tr class="oddeven">';
	print '<td style="vertical-align: top;"><strong>' . $default['name'] . '</strong></td>';
	print '<td style="vertical-align: top;"><input type="text" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_LABEL" value="' . dol_escape_htmltag($labelVal) . '" placeholder="' . dol_escape_htmltag($default['name']) . '" class="minwidth200"></td>';
	print '<td style="vertical-align: top;"><input type="text" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_ICON" value="' . dol_escape_htmltag($iconVal) . '" class="minwidth100"></td>';
	print '<td style="vertical-align: top;"><input type="color" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_COLOR" value="' . dol_escape_htmltag($colorVal) . '"></td>';
	print '<td style="vertical-align: top;">';
	print '<input type="file" name="file_' . $key . '" accept=".svg">';
	if ($hasSvg) {
		print '<br><small class="text-success"><i class="fas fa-check"></i> ' . $langs->trans("CustomSvgUploaded") . '</small>';
	}
	print '</td>';
	print '</tr>';
	print '<tr class="oddeven"><td colspan="5">';
	print '<label style="margin-bottom: 5px; display: inline-block;">' . $langs->trans("DefaultContent") . ' (' . $default['name'] . ')</label>';
	$doleditor = new DolEditor('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_DESC', $descVal, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_3, '100%');
	$doleditor->Create();
	print '</td></tr>';
}

print '</table>';

print '<div class="center" style="margin-top: 15px;">';
print '<input type="submit" class="button button-save" name="save" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

print dol_get_fiche_end();
llxFooter();
$db->close();
