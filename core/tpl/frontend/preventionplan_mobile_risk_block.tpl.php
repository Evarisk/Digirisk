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
 * \file    core/tpl/frontend/preventionplan_mobile_risk_block.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   One risk block of the mobile interface: the danger category, its description, its photos
 *          and the protections (EPI) that apply to it.
 *          Rendered server-side for the already selected risks, and once inside a <template> whose
 *          index placeholders the JavaScript replaces when a risk is added.
 *          Expects: $langs, $blockIndex, $blockPosition, $blockName, $blockThumbnail,
 *                   $blockDescription, $blockCompanyEu, $blockCompanyEe, $blockUploadSubDir,
 *                   $blockProtections.
 */

global $langs;
?>
<div class="digirisk-mobile-risk-block" data-position="<?php print dol_escape_htmltag($blockPosition); ?>" data-index="<?php print $blockIndex; ?>" data-protection-index="<?php print count($blockProtections); ?>">
    <div class="digirisk-mobile-risk-block__header">
        <img class="digirisk-mobile-risk-block__picto" src="<?php print dol_escape_htmltag($blockThumbnail); ?>" alt="">
        <span class="digirisk-mobile-risk-block__name"><?php print dol_escape_htmltag($blockName); ?></span>
        <input type="hidden" class="digirisk-mobile-risk-block__category" name="risk_category[<?php print $blockIndex; ?>]" value="<?php print dol_escape_htmltag($blockPosition); ?>">

        <!-- Which company the risk concerns: user company (EU), exterior company (EE), or both -->
        <div class="digirisk-mobile-risk-block__companies">
            <label class="digirisk-mobile-risk-company" title="<?php print dol_escape_htmltag($langs->trans('MobilePPUserCompany')); ?>">
                <input type="checkbox" name="risk_company_eu[<?php print $blockIndex; ?>]" value="1"<?php print !empty($blockCompanyEu) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('MobilePPUserCompanyShort'); ?></span>
            </label>
            <label class="digirisk-mobile-risk-company" title="<?php print dol_escape_htmltag($langs->trans('MobilePPExteriorCompany')); ?>">
                <input type="checkbox" name="risk_company_ee[<?php print $blockIndex; ?>]" value="1"<?php print !empty($blockCompanyEe) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('MobilePPExteriorCompanyShort'); ?></span>
            </label>
        </div>

        <button type="button" class="digirisk-mobile-risk-block__delete" aria-label="<?php print dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-trash"></i></button>
    </div>

    <!-- Description and photos side by side, wrapping onto two lines on the narrowest screens -->
    <div class="digirisk-mobile-risk-block__main">
        <textarea name="risk_comment[<?php print $blockIndex; ?>]" class="digirisk-mobile-risk-block__description" rows="2" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskDescriptionPlaceholder')); ?>"><?php print dol_escape_htmltag($blockDescription); ?></textarea>
        <div class="digirisk-mobile-risk-block__photos">
            <?php
            // Saturne media block: shoots or picks the photos and uploads them right away into the
            // temporary directory of this risk block, so nothing is lost if the form is reloaded
            print saturne_render_media_block('digiriskdolibarr', $blockUploadSubDir, 'risk-' . $blockIndex, 'digiriskdolibarr,preventionplan,write', ['show_photo' => true, 'show_audio' => false, 'show_file' => false]);
            ?>
        </div>
    </div>

    <div class="digirisk-mobile-risk-block__section">
        <div class="digirisk-mobile-risk-block__label"><?php print $langs->trans('MobilePPProtections'); ?></div>
        <div class="digirisk-mobile-protection-list">
            <?php
            foreach ($blockProtections as $blockProtectionIndex => $blockProtection) {
                $rowRiskIndex = $blockIndex;
                $rowIndex     = $blockProtectionIndex;
                $rowPosition  = $blockProtection['position'];
                $rowThumbnail = $blockProtection['thumbnail'];
                $rowName      = $blockProtection['name'];
                $rowComment   = $blockProtection['comment'];
                include __DIR__ . '/preventionplan_mobile_protection_row.tpl.php';
            }
            ?>
        </div>
        <button type="button" class="digirisk-mobile-protection-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddProtection'); ?></button>
    </div>
</div>
