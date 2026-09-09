<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/digiriskelement/digiriskelement_organization_tree.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Shared fragment for the GP/UT organization tree: dialogs, JS config and notices.
 *          Included by both the left navigation panel (digirisk_header) and the standalone
 *          organization page. Contains no <script> (handled by js/modules/digiriskorganization.js)
 *          and no tree markup (rendered by display_recurse_tree / the page wrapper).
 *
 * Requires the following variables to be defined by the calling context:
 * - DoliDB         $db
 * - Conf           $conf
 * - Translate      $langs
 * - User           $user
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}
?>

<span id="digirisk-organization-config"
      data-saving="<?php echo dol_escape_htmltag($langs->trans('SavingInProgress')); ?>"
      data-saved="<?php echo dol_escape_htmltag($langs->trans('Saved')); ?>"
      data-error-saving="<?php echo dol_escape_htmltag($langs->trans('ErrorSaving')); ?>"
      data-new-groupment="<?php echo dol_escape_htmltag($langs->trans('NewGroupment')); ?>"
      data-new-workunit="<?php echo dol_escape_htmltag($langs->trans('NewWorkUnit')); ?>"
      data-token="<?php echo newToken(); ?>"></span>

<!-- Dialog natif d'ajout rapide GP/UT via form POST standard -->
<dialog id="quick-add-dialog">
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?action=quickCreateElement&token=' . newToken(); ?>">
        <h3 id="quick-add-dialog-title" class="modal-title"></h3>
        <input type="hidden" name="element_type" id="quick-add-type-input" />
        <input type="hidden" name="fk_parent" id="quick-add-fk-parent-input" />
        <input type="hidden" name="mainmenu" value="<?php echo dol_escape_htmltag(GETPOST('mainmenu', 'alpha')); ?>" />
        <input type="hidden" name="leftmenu" value="<?php echo dol_escape_htmltag(GETPOST('leftmenu', 'alpha')); ?>" />
        <table class="border centpercent">
            <tr>
                <td class="fieldrequired"><?php echo $langs->trans('ParentElement'); ?></td>
                <td><span id="quick-add-parent-info" class="parent-info"></span></td>
            </tr>
            <tr>
                <td class="fieldrequired"><?php echo $langs->trans('Label'); ?></td>
                <td><input type="text" name="label" id="quick-add-label-input" class="flat minwidth300" required /></td>
            </tr>
            <tr>
                <td><?php echo $langs->trans('Description'); ?></td>
                <td><textarea name="description" id="quick-add-description-input" class="flat minwidth300" rows="3"></textarea></td>
            </tr>
            <tr>
                <td><?php echo $langs->trans('ShowInSelectOnPublicTicketInterface'); ?></td>
                <td><input type="checkbox" name="show_in_selector" id="quick-add-selector-input" value="1" checked /></td>
            </tr>
        </table>
        <div class="modal-actions">
            <button type="submit" class="button"><?php echo $langs->trans('Create'); ?></button>
            <button type="button" class="button quick-add-cancel"><?php echo $langs->trans('Cancel'); ?></button>
        </div>
    </form>
</dialog>

<!-- Dialog natif de confirmation de suppression -->
<dialog id="confirm-delete-dialog">
    <h3 class="modal-title"><?php echo dol_string_nohtmltag($langs->trans('Delete')); ?></h3>
    <p><?php echo dol_string_nohtmltag($langs->trans('ConfirmDeleteObject')); ?></p>
    <p><strong id="confirm-delete-ref"></strong></p>
    <div class="modal-actions">
        <button type="button" class="button btn-danger" id="confirm-delete-yes"
                data-token="<?php echo newToken(); ?>"
                data-mainmenu="<?php echo dol_escape_htmltag(GETPOST('mainmenu', 'alpha')); ?>"
                data-leftmenu="<?php echo dol_escape_htmltag(GETPOST('leftmenu', 'alpha')); ?>"><?php echo dol_string_nohtmltag($langs->trans('Confirm')); ?></button>
        <button type="button" class="button confirm-delete-cancel"><?php echo dol_string_nohtmltag($langs->trans('Cancel')); ?></button>
    </div>
</dialog>

<div class="messageErrorOrganizationSaved notice hidden">
    <div class="wpeo-notice notice-error organization-saved-error-notice">
        <div class="notice-content">
            <div class="notice-title"><?php echo $langs->trans('OrganizationNotSaved'); ?></div>
            <div class="notice-subtitle">
                <span class="text"></span>
            </div>
        </div>
        <div class="notice-close"><i class="fas fa-times"></i></div>
    </div>
</div>
