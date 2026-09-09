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
 * \file    core/tpl/frontend/digiriskdolibarr_mobile_protection_row.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   One protection (EPI) row inside a risk (or type of work) block of the mobile interfaces.
 *          Rendered server-side for the already selected protections, and once inside a <template>
 *          whose index placeholders the JavaScript replaces when a protection is added.
 *          A protection carried by a risk is always mandatory, hence no option to say otherwise.
 *          Expects: $langs, $rowRiskIndex, $rowIndex, $rowPosition, $rowThumbnail, $rowName, $rowComment.
 */

global $langs;
?>
<div class="digirisk-mobile-protection-item" data-position="<?php print dol_escape_htmltag($rowPosition); ?>">
    <img class="digirisk-mobile-protection-item-photo" src="<?php print dol_escape_htmltag($rowThumbnail); ?>" alt="" title="<?php print dol_escape_htmltag($rowName); ?>">
    <input type="hidden" name="risk_protection_position[<?php print $rowRiskIndex; ?>][<?php print $rowIndex; ?>]" value="<?php print dol_escape_htmltag($rowPosition); ?>">
    <input type="text" name="risk_protection_comment[<?php print $rowRiskIndex; ?>][<?php print $rowIndex; ?>]" class="digirisk-mobile-protection-item-comment" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>" value="<?php print dol_escape_htmltag($rowComment); ?>">
    <button type="button" class="digirisk-mobile-protection-item-delete"><i class="fas fa-trash"></i></button>
</div>
