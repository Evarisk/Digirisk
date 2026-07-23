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
 * \file    core/tpl/frontend/digiriskdolibarr_pwa_home.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Home screen of the PWA: status tiles and creation shortcuts.
 *          Expects: $langs, $user, $homeTiles, $homeActions.
 */

global $langs, $user;
?>
<div class="pwa-container digirisk-pwa">
    <div class="digirisk-pwa-hello"><?php print $langs->trans('PwaHello', $user->firstname ?: $user->lastname); ?></div>

    <?php if (!empty($homeTiles)) { ?>
    <div class="digirisk-pwa-tiles">
        <?php foreach ($homeTiles as $homeTile) { ?>
        <a class="digirisk-pwa-tile" href="<?php print dol_escape_htmltag($homeTile['url']); ?>">
            <i class="fas <?php print dol_escape_htmltag($homeTile['icon']); ?>"></i>
            <span class="digirisk-pwa-tile__count"><?php print (int) $homeTile['count']; ?></span>
            <span class="digirisk-pwa-tile__label"><?php print dol_escape_htmltag($homeTile['label']); ?></span>
        </a>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if (!empty($homeActions)) { ?>
    <div class="digirisk-pwa-actions">
        <?php foreach ($homeActions as $homeAction) { ?>
        <a class="digirisk-pwa-action" href="<?php print dol_escape_htmltag($homeAction['url']); ?>">
            <i class="fas <?php print dol_escape_htmltag($homeAction['icon']); ?>"></i>
            <span><?php print dol_escape_htmltag($homeAction['label']); ?></span>
        </a>
        <?php } ?>
    </div>
    <?php } ?>
</div>
