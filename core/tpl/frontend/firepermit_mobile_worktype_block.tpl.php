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
 * \file    core/tpl/frontend/firepermit_mobile_worktype_block.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   One type of work block of the mobile fire permit interface: the fire permit danger
 *          category, its description, the equipment used, its photos and the protections (EPI)
 *          that apply to it.
 *          Rendered server-side for the already selected types of work, and once inside a <template>
 *          whose index placeholders the JavaScript replaces when one is added.
 *          Expects: $langs, $blockIndex, $blockPosition, $blockName, $blockThumbnail,
 *                   $blockDescription, $blockEquipment, $blockUploadSubDir, $blockProtections.
 */

global $langs;
?>
<div class="digirisk-mobile-risk-block" data-position="<?php print dol_escape_htmltag($blockPosition); ?>" data-index="<?php print $blockIndex; ?>" data-protection-index="<?php print count($blockProtections); ?>">
    <div class="digirisk-mobile-risk-block__header">
        <img class="digirisk-mobile-risk-block__picto" src="<?php print dol_escape_htmltag($blockThumbnail); ?>" alt="">
        <span class="digirisk-mobile-risk-block__name"><?php print dol_escape_htmltag($blockName); ?></span>
        <input type="hidden" class="digirisk-mobile-risk-block__category" name="risk_category[<?php print $blockIndex; ?>]" value="<?php print dol_escape_htmltag($blockPosition); ?>">

        <button type="button" class="digirisk-mobile-risk-block__delete" aria-label="<?php print dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-trash"></i></button>
    </div>

    <!-- Description and photos side by side, wrapping onto two lines on the narrowest screens -->
    <div class="digirisk-mobile-risk-block__main">
        <textarea name="risk_comment[<?php print $blockIndex; ?>]" class="digirisk-mobile-risk-block__description" rows="2" placeholder="<?php print dol_escape_htmltag($langs->trans('MobileFPWorkTypeDescriptionPlaceholder')); ?>"><?php print dol_escape_htmltag($blockDescription); ?></textarea>
        <div class="digirisk-mobile-risk-block__photos">
            <?php
            // Saturne media block: shoots or picks the photos and uploads them right away into the
            // temporary directory of this block, so nothing is lost if the form is reloaded
            print saturne_render_media_block('digiriskdolibarr', $blockUploadSubDir, 'risk-' . $blockIndex, 'digiriskdolibarr,firepermit,write', ['show_photo' => true, 'show_audio' => false, 'show_file' => false]);
            ?>
        </div>
        <!-- The equipment used is what makes the job a hot work: it belongs to the type of work -->
        <input type="text" name="risk_equipment[<?php print $blockIndex; ?>]" class="digirisk-mobile-risk-block__equipment" placeholder="<?php print dol_escape_htmltag($langs->trans('UsedEquipment')); ?>" value="<?php print dol_escape_htmltag($blockEquipment); ?>">
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
                include __DIR__ . '/digiriskdolibarr_mobile_protection_row.tpl.php';
            }
            ?>
        </div>
        <button type="button" class="digirisk-mobile-protection-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddProtection'); ?></button>
    </div>
</div>
