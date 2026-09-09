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

    </div>

    <?php if (!empty($successCustomFactsHtml)) {
        print $successCustomFactsHtml;
    } elseif (!empty($successFacts)) { ?>
    <div class="digirisk-mobile-card digirisk-mobile-success-facts">
        <div class="digirisk-mobile-extsign__title" style="margin-bottom: 15px;"><i class="fas fa-chart-line"></i> Avancement</div>
        <ul class="digirisk-mobile-success__facts" style="margin-bottom: 0;">
            <?php foreach ($successFacts as $successFact) {
                $icon = 'fa-check-circle';
                $color = '';
                $text = $successFact;
                if (is_array($successFact)) {
                    $text = $successFact['text'];
                    if (!empty($successFact['status']) && $successFact['status'] === 'pending') {
                        $icon = 'fa-times-circle';
                        $color = 'color: #d32f2f;';
                    }
                    if (!empty($successFact['icon'])) {
                        $icon = $successFact['icon'];
                    }
                    if (!empty($successFact['color'])) {
                        $color = 'color: ' . $successFact['color'] . ';';
                    }
                }
            ?>
            <li><i class="fas <?php print $icon; ?>" style="<?php print $color; ?>"></i><span><?php print dol_escape_htmltag($text); ?></span></li>
            <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <?php if (!empty($successEuBlockHtml)) {
        print $successEuBlockHtml;
    } ?>

    <?php
    // Bloc propre a l'objet cree (signature de l'entreprise exterieure pour un plan de prevention),
    // insere ici pour que l'ecran commun reste identique d'un objet a l'autre
    if (!empty($successExtraBlockFile) && file_exists($successExtraBlockFile)) {
        require $successExtraBlockFile;
    }
    ?>

    <?php if (dol_strlen($successShareUrl)) { ?>
    <?php
    $shareIsDisabled = isset($successShareEnabled) && $successShareEnabled === false;
    $shareOpacityStyle = $shareIsDisabled ? 'opacity: 0.3; pointer-events: none;' : '';
    ?>
    <div class="digirisk-mobile-card digirisk-mobile-share">
        <div class="digirisk-mobile-share__title"><i class="fas fa-paper-plane" style="margin-right: 8px;"></i><?php print $langs->trans('MobileSpreadShareTitle'); ?></div>
        <div class="digirisk-mobile-share__text"><?php print $langs->trans('MobileSpreadShareText'); ?></div>

        <?php if (dol_strlen($successQrCode)) { ?>
        <div class="digirisk-mobile-share__qr" style="<?php print $shareOpacityStyle; ?>">
            <div class="digirisk-mobile-share__qr-frame"><?php print $successQrCode; ?></div>
        </div>
        <?php } ?>

        <div class="digirisk-mobile-share__copy copy-signatureurl-container" style="<?php print $shareOpacityStyle; ?>">
            <div class="digirisk-mobile-share__link">
                <a href="<?php print $successShareUrl; ?>" target="_blank"><?php print dol_escape_htmltag($successShareUrl); ?></a>
                <i class="fas fa-clipboard copy-signatureurl" data-signature-url="<?php print dol_escape_htmltag($successShareUrl); ?>" title="<?php print dol_escape_htmltag($langs->trans('ClickToCopyToClipboard')); ?>"></i>
            </div>
            <span class="copied-to-clipboard" style="display: none;"><?php print $langs->trans('CopiedToClipboard'); ?></span>
        </div>

        <?php if ($shareIsDisabled && !empty($successShareDisabledText)) { ?>
        <div style="margin-top: 15px; padding: 12px; background: #fff3cd; color: #856404; border-radius: 6px; text-align: center; font-size: 0.9em; font-weight: 500;">
            <i class="fas fa-exclamation-triangle"></i> <?php print dol_escape_htmltag($successShareDisabledText); ?>
        </div>
        <?php } else { ?>
        <a class="digirisk-mobile-success__button digirisk-mobile-success__button--primary" href="<?php print $successShareUrl; ?>" target="_blank">
            <i class="fas fa-external-link-alt"></i> <?php print $langs->trans('MobileSpreadOpen'); ?>
        </a>
        <?php } ?>
    </div>
    <?php } ?>

    <?php
    $diffusionModDisabled = !isModEnabled('doliletter');
    if ($diffusionModDisabled) {
    ?>
    <div style="text-align: center; color: #d32f2f; margin-bottom: 5px; font-size: 0.9em; padding: 0 15px;">
        <i class="fas fa-exclamation-triangle"></i> Module DoliLetter manquant pour la diffusion - Contacter votre administrateur
    </div>
    <?php } ?>
    <div class="digirisk-mobile-success__actions">
        <a class="digirisk-mobile-success__button" href="<?php print $successViewUrl; ?>"><?php print dol_escape_htmltag($successViewLabel); ?></a>
        <a class="digirisk-mobile-success__button" href="<?php print $successAgainUrl; ?>"><?php print dol_escape_htmltag($successAgainLabel); ?></a>
        <a class="digirisk-mobile-success__button" href="<?php print $diffusionModDisabled ? '#' : $successShareUrl; ?>" <?php print $diffusionModDisabled ? 'style="opacity: 0.5; pointer-events: none;"' : 'target="_blank"'; ?>>
            Diffusion
        </a>
    </div>
</div>
