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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/ticket/ticket_category_preview.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Preview of the public interface a ticket category configuration makes (#5711). Read-only: the
 *          fields, the description, the signature and the texts are drawn by js/modules/ticket_category_preview.js
 *          from the settings of the page, saved or not.
 *
 * Variables expected from view/ticket/category_config.php:
 *   $langs
 *   $categoryPreview array See digiriskdolibarr_ticket_category_preview()
 */

if (empty($langs) || !isset($categoryPreview)) {
    print 'Error: missing variables for ticket_category_preview.tpl.php';
    return;
}
?>

<aside class="ticket-category-preview"
       data-inherited-description="<?php print $categoryPreview['inherited']['show_description'] ? 1 : 0; ?>"
       data-inherited-signatory="<?php print $categoryPreview['inherited']['use_signatory'] ? 1 : 0; ?>"
       data-external-link-text="<?php print dol_escape_htmltag($langs->transnoentities('TicketPublicInterfacePreviewExternalLink', '%s')); ?>">
    <div class="ticket-category-preview__head">
        <span class="ticket-category-preview__title"><?php print img_picto('', 'fa-eye', 'class="paddingrightonly"') . $langs->transnoentities('TicketPublicInterfacePreview'); ?></span>
        <?php if ($categoryPreview['offered']) : ?>
            <a class="ticket-category-preview__open" href="<?php print dol_escape_htmltag($categoryPreview['url']); ?>" target="_blank" rel="noopener"><?php print img_picto('', 'fa-external-link-alt', 'class="paddingrightonly"') . $langs->transnoentities('TicketPublicInterfacePreviewOpen'); ?></a>
        <?php endif; ?>
    </div>

    <?php if (!$categoryPreview['offered']) : ?>
        <div class="ticket-category-preview__notice"><?php print $langs->transnoentities('TicketPublicInterfacePreviewNotOffered'); ?></div>
    <?php else : ?>
        <div class="ticket-category-preview__help"><?php print $langs->transnoentities('TicketPublicInterfacePreviewHelp'); ?></div>

        <div class="ticket-category-preview__screen">
            <div class="ticket-category-preview__screen-title"><?php print img_picto('', 'digiriskdolibarr_color@digiriskdolibarr', 'class="ticket-category-preview__logo"') . $langs->transnoentities('CreateTicket'); ?></div>

            <?php foreach ($categoryPreview['levels'] as $levelIndex => $level) : ?>
                <div class="ticket-category-preview__level-title">
                    <?php print dol_escape_htmltag($level['title']) . ($level['required'] ? '<span class="ticket-category-preview__required"> *</span>' : ''); ?>
                </div>
                <div class="ticket-category-preview__categories<?php print $levelIndex > 0 ? ' ticket-category-preview__categories--sub' : ''; ?>">
                    <?php foreach ($level['categories'] as $levelCategory) : ?>
                        <span class="ticket-category-preview__category<?php print $levelCategory['selected'] ? ' ticket-category-preview__category--selected' : ''; ?>"<?php print ($levelIndex == 0 && $levelCategory['color']) ? ' style="--ticket-category-color: #' . $levelCategory['color'] . '"' : ''; ?>>
                            <?php print dol_escape_htmltag($levelCategory['label']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="ticket-category-preview__notice ticket-category-preview__external" hidden></div>

            <div class="ticket-category-preview__form">
                <div class="ticket-category-preview__description" hidden>
                    <span class="ticket-category-preview__label"><?php print $langs->transnoentities('Description'); ?></span>
                    <?php if (dol_strlen($categoryPreview['description'])) : ?>
                        <div class="ticket-category-preview__description-text"><?php print dol_htmlentitiesbr($categoryPreview['description']); ?></div>
                    <?php else : ?>
                        <div class="ticket-category-preview__empty"><?php print $langs->transnoentities('TicketPublicInterfacePreviewNoDescription'); ?></div>
                    <?php endif; ?>
                </div>

                <div class="ticket-category-preview__fields" data-message-label="<?php print dol_escape_htmltag($langs->transnoentities('Message')); ?>"></div>

                <?php if (getDolGlobalInt('DIGIRISKDOLIBARR_USE_CAPTCHA')) : ?>
                    <div class="ticket-category-preview__field">
                        <span class="ticket-category-preview__label"><?php print $langs->transnoentities('SecurityCode'); ?><span class="ticket-category-preview__required"> *</span></span>
                        <span class="ticket-category-preview__input"></span>
                    </div>
                <?php endif; ?>

                <div class="ticket-category-preview__signature" hidden>
                    <div class="ticket-category-preview__validate">
                        <span class="ticket-category-preview__checkbox"></span>
                        <div class="ticket-category-preview__validate-text"></div>
                    </div>
                    <div class="ticket-category-preview__signature-pad"><?php print img_picto('', 'fa-signature', 'class="paddingrightonly"') . $langs->transnoentities('Signature'); ?></div>
                </div>

                <div class="ticket-category-preview__send">
                    <span class="ticket-category-preview__send-button"><?php print img_picto('', 'fa-paper-plane', 'class="paddingrightonly"') . $langs->transnoentities('Send'); ?></span>
                </div>
            </div>
        </div>

        <div class="ticket-category-preview__after">
            <div class="ticket-category-preview__after-title"><?php print $langs->transnoentities('TicketPublicInterfacePreviewAfterSending'); ?></div>
            <div class="ticket-category-preview__success"></div>
        </div>

        <?php // What the categories above set, used while this one sets nothing of its own ?>
        <template class="ticket-category-preview__inherited-validate-text"><?php print $categoryPreview['inherited']['validate_text']; ?></template>
        <template class="ticket-category-preview__inherited-success-message"><?php print $categoryPreview['inherited']['success_message']; ?></template>
    <?php endif; ?>
</aside>
