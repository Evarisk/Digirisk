<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskdolibarr_entity_transfer_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Entity export and import block of the Tools page
 *
 * Expects $permissiontotransferentity, $canChooseEntity, $entityExports, $entityList,
 * $siblingModules and $defaultModules prepared by view/digirisktools.php
 */

global $conf, $langs;

print load_fiche_titre($langs->trans('EntityTransfer'), '', '');

if (empty($permissiontotransferentity)) {
    print '<div class="wpeo-notice notice-info"><div class="notice-content"><div class="notice-subtitle"><strong>'
        . $langs->trans('EntityTransferAdminOnly') . '</strong></div></div></div>';
    return;
}
?>

<div class="wpeo-notice notice-info">
	<div class="notice-content">
		<div class="notice-subtitle"><strong><?php print $langs->trans('EntityTransferDescription'); ?></strong></div>
	</div>
</div>

<?php
print '<form name="entityExport" id="entityExport" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="exportEntity">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans('EntityExport') . '</td>';
print '<td>';
print $langs->trans('EntityExportDescription') . '<br>';

print '<br>' . $langs->trans('EntityToExport') . ' ';

if ($canChooseEntity && !empty($entityList)) {
    print '<select name="exportEntity" class="flat minwidth200">';
    foreach ($entityList as $entityId => $entityLabel) {
        print '<option value="' . $entityId . '"' . ($entityId == $conf->entity ? ' selected' : '') . '>' . $entityId . ' - ' . dol_escape_htmltag($entityLabel) . '</option>';
    }
    print '</select>';

    print '<br>' . $langs->trans('ExtraEntities') . ' ';
    print '<input type="text" name="extraEntities" class="flat maxwidth100" placeholder="1">';
    print ' <span class="opacitymedium">' . $langs->trans('ExtraEntitiesDescription') . '</span>';
} else {
    // Not a super administrator: the export is nailed to the entity of the session
    print '<strong>' . $conf->entity . ' - ' . dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_NOM')) . '</strong>';
    print ' <span class="opacitymedium">' . $langs->trans('EntityToExportCurrentOnly') . '</span>';
}

print '<br><br>' . $langs->trans('EntityExportScope') . ' ';
print '<select name="exportScope" class="flat minwidth200">';
print '<option value="digirisk" selected>' . $langs->trans('EntityExportScopeDigirisk') . '</option>';
print '<option value="core">' . $langs->trans('EntityExportScopeCore') . '</option>';
print '<option value="full">' . $langs->trans('EntityExportScopeFull') . '</option>';
print '</select>';

if (!empty($siblingModules)) {
    print '<br><br>' . $langs->trans('EntityExportModules') . '<br>';
    print '<span class="opacitymedium">' . $langs->trans('EntityExportModulesDescription') . '</span><br>';

    foreach ($siblingModules as $siblingModule) {
        $checked = (in_array($siblingModule, $defaultModules, true) ? ' checked' : '');
        print '<label class="marginrightonly"><input type="checkbox" name="exportModules[]" value="' . dol_escape_htmltag($siblingModule) . '"' . $checked . '> ' . dol_escape_htmltag($siblingModule) . '</label>';
    }
}

print '<br><br>';
print '<label><input type="checkbox" name="withFiles" value="1" checked> ' . $langs->trans('EntityExportWithFiles') . '</label><br>';
print '<label><input type="checkbox" name="withDictionaries" value="1"> ' . $langs->trans('EntityExportWithDictionaries') . '</label><br>';
print '<label><input type="checkbox" name="withPurge" value="1"> ' . $langs->trans('EntityExportWithPurge') . '</label>';
print '</td>';

print '<td class="center">';
print '<input type="submit" class="button reposition" name="entityExportSubmit" value="' . $langs->trans('Export') . '">';
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

print load_fiche_titre($langs->trans('EntityExportArchives'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('File') . '</td>';
print '<td class="center">' . $langs->trans('Size') . '</td>';
print '<td class="center">' . $langs->trans('Date') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

if (empty($entityExports)) {
    print '<tr class="oddeven"><td colspan="4">' . $langs->trans('NoEntityExportYet') . '</td></tr>';
} else {
    foreach ($entityExports as $entityExport) {
        // Served by the page itself: the archives live outside every directory document.php exposes,
        // and the download action is behind the same administrator check as the export
        $downloadUrl = $_SERVER['PHP_SELF'] . '?action=downloadEntityExport&token=' . newToken() . '&exportFile=' . urlencode($entityExport['name']);

        print '<tr class="oddeven">';
        print '<td><a href="' . $downloadUrl . '">' . img_mime($entityExport['name']) . ' ' . dol_escape_htmltag($entityExport['name']) . '</a></td>';
        print '<td class="center">' . dol_print_size($entityExport['size']) . '</td>';
        print '<td class="center">' . dol_print_date($entityExport['date'], 'dayhour') . '</td>';
        print '<td class="center">';
        print '<form name="deleteEntityExport" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="deleteEntityExport">';
        print '<input type="hidden" name="exportFile" value="' . dol_escape_htmltag($entityExport['name']) . '">';
        print '<input type="submit" class="button reposition button-delete" value="' . $langs->trans('Delete') . '">';
        print '</form>';
        print '</td>';
        print '</tr>';
    }
}

print '</table>';

print load_fiche_titre($langs->trans('EntityImport'), '', '');
?>

<div class="wpeo-notice notice-warning">
	<div class="notice-content">
		<div class="notice-subtitle"><strong><?php print $langs->trans('EntityImportWarning'); ?></strong></div>
	</div>
</div>

<?php
print '<form name="entityImport" id="entityImport" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="importEntity">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td>' . $langs->trans('Description') . '</td>';
print '<td class="center">' . $langs->trans('Action') . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans('EntityImport') . '</td>';
print '<td>';
print $langs->trans('EntityImportDescription');
print '<br><br>';
print '<label><input type="checkbox" name="importWithFiles" value="1" checked> ' . $langs->trans('EntityImportWithFiles') . '</label><br>';
print '<label><input type="checkbox" name="importPurge" value="1"> ' . $langs->trans('EntityImportPurge') . '</label>';
print '</td>';

print '<td class="center">';
print '<input class="flat" type="file" name="entityImportFile[]" accept=".zip,.sql">';
print '<input type="submit" class="button reposition" name="entityImportSubmit" value="' . $langs->trans('Upload') . '">';
print '</td>';
print '</tr>';
print '</table>';
print '</form>';
