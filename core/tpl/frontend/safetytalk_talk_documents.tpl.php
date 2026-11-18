<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/frontend/safetytalk_talk_documents.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Content of a safety talk on its screen: the files and links its spread shows, the media block of
 *          Saturne that sends new ones as soon as they are picked, and the link form.
 *          Expects: $langs, $object (talk), $documents, $permissiontoadd, $maxUploadKb, $mediaContext, $mediaPrefix.
 */

global $langs;
?>
<div class="digirisk-mobile-card digirisk-safetytalk-documents" id="safetytalk-documents">
    <div class="digirisk-mobile-extsign__title"><i class="fas fa-photo-video"></i> <?php print $langs->trans('SafetyTalkDocumentsTitle'); ?></div>

    <?php require __DIR__ . '/safetytalk_talk_documents_list.tpl.php'; ?>

    <?php if ($permissiontoadd) { ?>
    <div class="digirisk-safetytalk-documents__add">
        <?php require __DIR__ . '/safetytalk_media_block.tpl.php'; ?>
    </div>

    <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-link-form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="add_link">
        <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
        <div class="digirisk-mobile-row">
            <div class="digirisk-mobile-field">
                <label for="safetytalk-new-link-url"><?php print $langs->trans('SafetyTalkLinkUrl'); ?></label>
                <input type="url" id="safetytalk-new-link-url" name="link_url" placeholder="https://">
            </div>
            <div class="digirisk-mobile-field">
                <label for="safetytalk-new-link-label"><?php print $langs->trans('SafetyTalkLinkLabel'); ?></label>
                <input type="text" id="safetytalk-new-link-label" name="link_label">
            </div>
        </div>
        <button type="submit" class="digirisk-safetytalk-button digirisk-safetytalk-button--wide"><i class="fas fa-link"></i> <?php print $langs->trans('SafetyTalkLinkAdd'); ?></button>
    </form>
    <?php } ?>
</div>
<?php
if ($permissiontoadd) {
    // Photo editor of the media block: it binds to this modal when the page loads
    print saturne_photo_editor_modal();
}
