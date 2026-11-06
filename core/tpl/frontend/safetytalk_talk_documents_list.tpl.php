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
 * \file    core/tpl/frontend/safetytalk_talk_documents_list.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Files and links the spread of a safety talk shows, with how each one is shown. Sent back too after
 *          each change of the media block, which puts it in place.
 *          Expects: $langs, $object (talk), $documents, $permissiontoadd.
 */

global $langs;

$documentModes = [
    DOLILETTER_SPREAD_DOCUMENT_READ     => 'SafetyTalkDocumentRead',
    DOLILETTER_SPREAD_DOCUMENT_DOWNLOAD => 'SafetyTalkDocumentDownload',
    DOLILETTER_SPREAD_DOCUMENT_HIDDEN   => 'SafetyTalkDocumentHidden',
];
?>
<div class="digirisk-safetytalk-documents__content" id="safetytalk-documents-list">
    <?php if (empty($documents)) { ?>
    <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkNoDocument'); ?></div>
    <?php } else { ?>
    <ul class="digirisk-safetytalk-documents__list">
        <?php foreach ($documents as $document) {
            $mimeType = $document['type'] == 'file' ? dol_mimetype($document['name']) : '';
            $isImage  = $document['type'] == 'file' && image_format_supported($document['name']) >= 0 && dol_strlen($document['url']);
            if ($document['type'] == 'link') {
                $icon = 'fas fa-link';
            } elseif (strpos($mimeType, 'video/') === 0) {
                $icon = 'fas fa-film';
            } elseif (strpos($mimeType, 'audio/') === 0) {
                $icon = 'fas fa-volume-up';
            } else {
                $icon = doliletter_spread_get_file_icon(dol_strtolower(pathinfo($document['name'], PATHINFO_EXTENSION)));
            } ?>
        <li class="digirisk-safetytalk-documents__item">
            <a class="digirisk-safetytalk-documents__preview" href="<?php print dol_escape_htmltag(dol_strlen($document['url']) ? $document['url'] . ($document['type'] == 'file' ? '&attachment=0' : '') : '#'); ?>" target="_blank">
                <?php if ($isImage) { ?>
                <img src="<?php print dol_escape_htmltag($document['url'] . '&attachment=0'); ?>" alt="" loading="lazy">
                <?php } else { ?>
                <i class="<?php print dol_escape_htmltag($icon); ?>"></i>
                <?php } ?>
            </a>
            <span class="digirisk-safetytalk-documents__body">
                <span class="digirisk-safetytalk-documents__name"><?php print dol_escape_htmltag($document['name']); ?></span>
                <span class="digirisk-safetytalk-pill digirisk-safetytalk-pill--<?php print dol_escape_htmltag($document['mode']); ?>"><?php print $langs->trans($document['type'] == 'link' ? 'SafetyTalkDocumentLink' : ($documentModes[$document['mode']] ?? 'SafetyTalkDocumentHidden')); ?></span>
            </span>
            <?php if ($permissiontoadd) { ?>
            <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-confirm" data-confirm="<?php print dol_escape_htmltag($langs->transnoentities('SafetyTalkDeleteDocumentConfirm', $document['name'])); ?>">
                <input type="hidden" name="token" value="<?php print newToken(); ?>">
                <input type="hidden" name="action" value="delete_document">
                <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
                <input type="hidden" name="document_type" value="<?php print dol_escape_htmltag($document['type']); ?>">
                <input type="hidden" name="document_id" value="<?php print (int) $document['id']; ?>">
                <button type="submit" class="digirisk-safetytalk-documents__delete" aria-label="<?php print dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-trash-alt"></i></button>
            </form>
            <?php } ?>
        </li>
        <?php } ?>
    </ul>
    <?php } ?>
</div>
