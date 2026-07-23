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
 * \file    core/tpl/frontend/firepermit_mobile_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Success screen of the mobile fire permit interface.
 *          Expects: $langs, $object (fetched fire permit).
 */

global $langs;

$cardUrl = dol_buildpath('/custom/digiriskdolibarr/view/firepermit/firepermit_card.php', 1) . '?id=' . $object->id;
?>
<div class="pwa-container digirisk-mobile">
    <div class="digirisk-mobile-card digirisk-mobile-success">
        <i class="fas fa-check-circle digirisk-mobile-success__icon"></i>
        <div class="digirisk-mobile-success__title"><?php print $langs->trans('MobileFPSuccessTitle'); ?></div>
        <div class="digirisk-mobile-success__ref"><?php print dol_escape_htmltag($object->ref); ?></div>
        <div class="digirisk-mobile-success__text"><?php print $langs->trans('MobilePPSuccessText'); ?></div>
        <div class="digirisk-mobile-success__actions">
            <a class="wpeo-button button-blue" href="<?php print $cardUrl; ?>"><i class="fas fa-eye"></i> <?php print $langs->trans('MobileFPViewPermit'); ?></a>
            <a class="wpeo-button button-grey" href="<?php print $_SERVER['PHP_SELF']; ?>"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobileFPCreateAnother'); ?></a>
        </div>
    </div>
</div>
