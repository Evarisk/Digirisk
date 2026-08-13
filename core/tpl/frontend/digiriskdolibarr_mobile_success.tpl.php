<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/digiriskdolibarr_mobile_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Success screen shared by the mobile quick-creation interfaces.
 *          Reads as a receipt: the reference is the anchor, then what already happened, then the
 *          QR code to hand round on site so the people involved can join the spread.
 *          Expects: $langs, $successTitle, $successRef, $successLabel, $successFacts, $successShareUrl,
 *                   $successViewUrl, $successViewLabel, $successAgainUrl, $successAgainLabel.
 */

global $langs;

$successQrCode = dol_strlen($successShareUrl) ? digiriskGetQrCodeSvg($successShareUrl) : '';
?>
<div class="pwa-container digirisk-mobile">
    <div class="digirisk-mobile-card digirisk-mobile-success">
        <?php if (!empty($successTitle)) { ?>
        <div class="digirisk-mobile-success__eyebrow">
            <i class="fas fa-check"></i> <?php print dol_escape_htmltag($successTitle); ?>
        </div>
        <?php } ?>
        <div class="digirisk-mobile-success__ref"><?php print !empty($successRefHtml) ? $successRefHtml : dol_escape_htmltag($successRef); ?></div>
        <?php if (dol_strlen($successLabel)) { ?>
        <div class="digirisk-mobile-success__label"><?php print dol_escape_htmltag($successLabel); ?></div>
        <?php } ?>

        <?php if (!empty($successExtraInfoHtml)) { ?>
        <div class="digirisk-mobile-success__extrainfo" style="margin-top: 10px; font-size: 0.9em; color: #555;">
            <?php print $successExtraInfoHtml; ?>
        </div>
        <?php } ?>

        <?php if (!empty($successFacts)) { ?>
        <ul class="digirisk-mobile-success__facts">
            <?php foreach ($successFacts as $successFact) { ?>
            <li><i class="fas fa-check-circle"></i><span><?php print dol_escape_htmltag($successFact); ?></span></li>
            <?php } ?>
        </ul>
        <?php } ?>
    </div>

    <?php if (dol_strlen($successShareUrl)) { ?>
    <div class="digirisk-mobile-card digirisk-mobile-share">
        <div class="digirisk-mobile-share__title"><?php print $langs->trans('MobileSpreadShareTitle'); ?></div>
        <div class="digirisk-mobile-share__text"><?php print $langs->trans('MobileSpreadShareText'); ?></div>

        <?php if (dol_strlen($successQrCode)) { ?>
        <div class="digirisk-mobile-share__qr">
            <div class="digirisk-mobile-share__qr-frame"><?php print $successQrCode; ?></div>
        </div>
        <?php } ?>

        <div class="digirisk-mobile-share__copy copy-signatureurl-container">
            <div class="digirisk-mobile-share__link">
                <a href="<?php print $successShareUrl; ?>" target="_blank"><?php print dol_escape_htmltag($successShareUrl); ?></a>
                <i class="fas fa-clipboard copy-signatureurl" data-signature-url="<?php print dol_escape_htmltag($successShareUrl); ?>" title="<?php print dol_escape_htmltag($langs->trans('ClickToCopyToClipboard')); ?>"></i>
            </div>
            <span class="copied-to-clipboard" style="display: none;"><?php print $langs->trans('CopiedToClipboard'); ?></span>
        </div>

        <a class="digirisk-mobile-success__button digirisk-mobile-success__button--primary" href="<?php print $successShareUrl; ?>" target="_blank">
            <i class="fas fa-external-link-alt"></i> <?php print $langs->trans('MobileSpreadOpen'); ?>
        </a>
    </div>
    <?php } ?>

    <?php
    // Bloc propre a l'objet cree (signature de l'entreprise exterieure pour un plan de prevention),
    // insere ici pour que l'ecran commun reste identique d'un objet a l'autre
    if (!empty($successExtraBlockFile) && file_exists($successExtraBlockFile)) {
        require $successExtraBlockFile;
    }
    ?>

    <div class="digirisk-mobile-success__actions">
        <a class="digirisk-mobile-success__button" href="<?php print $successViewUrl; ?>"><?php print dol_escape_htmltag($successViewLabel); ?></a>
        <a class="digirisk-mobile-success__button" href="<?php print $successAgainUrl; ?>"><?php print dol_escape_htmltag($successAgainLabel); ?></a>
    </div>
</div>
