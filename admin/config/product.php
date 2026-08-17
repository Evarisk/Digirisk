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

if ($action == 'delete_svg') {
	$chap = GETPOST('chap', 'alpha');
	$iconsDir = $conf->digiriskdolibarr->dir_output . '/icons';
	$destName = 'digirisk_' . strtolower($chap) . '_icon.svg';
	if (file_exists($iconsDir . '/' . $destName)) {
		unlink($iconsDir . '/' . $destName);
	}
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}

if (($action == 'update' && ! GETPOST("cancel", 'alpha'))) {
	// No separate risks save block needed anymore, it will be handled by the generic chapters loop

	// Ensure icons directory exists
	$iconsDir = $conf->digiriskdolibarr->dir_output . '/icons';
	if (!is_dir($iconsDir)) {
		dol_mkdir($iconsDir);
	}

	// Chapter configurations
	$chapters = ['RISKS', 'IDENTIFICATION', 'SECURITY', 'USERMANUAL', 'QUALIFICATION', 'HYGIENE', 'MAINTENANCE'];
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

print load_fiche_titre('<i class="fas fa-cube"></i> ' . $langs->trans("ProductFIChaptersManagement"), '', '');
print '<hr>';

print '<script>
function resetChap(key, label, icon, color) {
	$("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_LABEL\']").val("").trigger("input");
	$("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_ICON\']").val(icon).trigger("input");
	$("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_COLOR\']").val(color);
}

$(document).ready(function() {
	$("input[name^=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_\']").on("input", function() {
		var name = $(this).attr("name");
		if (name.endsWith("_LABEL") || name.endsWith("_ICON")) {
			var key = name.replace("DIGIRISKDOLIBARR_PRODUCT_DEFAULT_", "").replace("_LABEL", "").replace("_ICON", "");
			
			var iconVal = $("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_ICON\']").val();
			
			// Auto-correct FA6 to FA5
			if (iconVal.indexOf("fa-solid ") !== -1) {
				iconVal = iconVal.replace("fa-solid ", "fas ");
				$("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_ICON\']").val(iconVal);
			}
			if (iconVal.indexOf("fa-regular ") !== -1) {
				iconVal = iconVal.replace("fa-regular ", "far ");
				$("input[name=\'DIGIRISKDOLIBARR_PRODUCT_DEFAULT_" + key + "_ICON\']").val(iconVal);
			}
			
			if (iconVal === "") iconVal = $("#preview_icon_" + key).data("default");
			
			$("#preview_icon_" + key).attr("class", iconVal);
			
			// Visual effect
			$("#preview_container_" + key).css({"color": "#28a745", "transition": "none"});
			setTimeout(function() {
				$("#preview_container_" + key).css({"color": "", "transition": "color 0.5s ease"});
			}, 100);
		}
	});
});
</script>';

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

// Configuration for FI Chapters
print load_fiche_titre('<i class="fas fa-file-pdf"></i> ' . $langs->trans("ProductFIChaptersManagement", "Gestion des chapitres de la Fiche d'Instruction (FI)"), '', '');
print '<hr>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th class="liste_titre">Chapitre (Aperçu Web)</th>';
print '<th class="liste_titre">Icône FA (Web)</th>';
print '<th class="liste_titre">Couleur (Web & PDF)</th>';
print '<th class="liste_titre">Image SVG (PDF)</th>';
print '</tr>';

$chaptersConfig = [
	'RISKS'          => ['name' => 'Risques & Protections', 'icon' => 'fas fa-exclamation-triangle', 'color' => '#d32f2f'],
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
	$svgPath = $hasSvg ? $iconsDir . '/' . $svgName : DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/img/icons/' . $svgName;

	$svgDisplay = '';
	if (file_exists($svgPath)) {
		$svgContent = file_get_contents($svgPath);
		$svgContent = preg_replace('/<svg([^>]*)>/i', '<svg$1 fill="' . dol_escape_htmltag($colorVal) . '" width="32" height="32">', $svgContent);
		$svgContent = preg_replace('/fill="[^"]*"/i', 'fill="' . dol_escape_htmltag($colorVal) . '"', $svgContent);
		$svgDisplay = '<div style="width:32px; height:32px; float:left; margin-right: 8px;">' . $svgContent . '</div>';
	}

	print '<tr class="oddeven">';
	print '<td style="vertical-align: top;">';
	print '<a href="#" onclick="resetChap(\'' . $key . '\', \'' . dol_escape_js($default['name']) . '\', \'' . dol_escape_js($default['icon']) . '\', \'' . dol_escape_js($default['color']) . '\'); return false;" class="text-muted" title="Rétablir les valeurs standard" style="margin-right:8px;"><i class="fas fa-undo"></i></a>';
	
	$displayLabel = !empty($labelVal) ? $labelVal : $default['name'];
	$displayIcon  = !empty($iconVal) ? $iconVal : $default['icon'];
	print '<strong id="preview_container_' . $key . '"><i id="preview_icon_' . $key . '" class="' . dol_escape_htmltag($displayIcon) . '" data-default="' . dol_escape_htmltag($default['icon']) . '" style="margin-right:6px;"></i>';
	print '<input type="text" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_LABEL" id="preview_label_' . $key . '" value="' . dol_escape_htmltag($displayLabel) . '" placeholder="' . dol_escape_htmltag($default['name']) . '" style="border:none; background:transparent; font-weight:bold; width:220px; outline:none; border-bottom:1px dashed #ccc; color:inherit;" onfocus="this.style.borderBottom=\'1px solid #666\'" onblur="this.style.borderBottom=\'1px dashed #ccc\'" data-default="' . dol_escape_htmltag($default['name']) . '">';
	print '</strong></td>';
	print '<td style="vertical-align: top;"><input type="text" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_ICON" value="' . dol_escape_htmltag($iconVal) . '" class="minwidth100"></td>';
	print '<td style="vertical-align: top;"><input type="color" name="DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_COLOR" value="' . dol_escape_htmltag($colorVal) . '"></td>';
	print '<td style="vertical-align: top;">';
	print $svgDisplay;
	print '<input type="file" name="file_' . $key . '" accept=".svg">';
	if ($hasSvg) {
		print '<br><small class="text-success"><i class="fas fa-check"></i> ' . $langs->trans("CustomSvgUploaded") . '</small>';
		print ' &nbsp; <a href="' . $_SERVER["PHP_SELF"] . '?action=delete_svg&chap=' . $key . '&token=' . newToken() . '" class="text-danger" title="Rétablir le SVG standard"><i class="fas fa-undo"></i></a>';
	} else {
		print '<br><small class="text-muted">' . $langs->trans("DefaultSvgUsed") . '</small>';
	}
	print '</td>';
	print '</tr>';
	print '<tr class="oddeven"><td colspan="5">';
	print '<label style="margin-bottom: 5px; display: inline-block;">' . $langs->trans("DefaultContent") . ' (' . $default['name'] . ')</label>';
	print ' &nbsp; <a href="#" onclick="$(\'#editor_' . $key . '\').toggle(); return false;" title="Modifier"><i class="fas fa-pencil-alt"></i></a>';
	print '<div id="editor_' . $key . '" style="display:none; margin-top: 10px;">';
	$doleditor = new DolEditor('DIGIRISKDOLIBARR_PRODUCT_DEFAULT_' . $key . '_DESC', $descVal, '', 100, 'dolibarr_details', 'In', false, true, true, ROWS_3, '100%');
	$doleditor->Create();
	print '</div>';
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
