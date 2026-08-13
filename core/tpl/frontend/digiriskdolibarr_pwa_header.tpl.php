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
 * \file    core/tpl/frontend/digiriskdolibarr_pwa_header.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Fixed top header for the DigiriskDolibarr mobile interface.
 *          Expects $pwaHeaderTitle to be optionally defined by the parent script.
 */

global $conf, $db, $langs, $mysoc, $user;

if (empty($mysoc)) {
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    $mysoc = new Societe($db);
    $mysoc->setMysoc($conf);
}

$logoFile = '';
if (!empty($mysoc->logo_squarred_mini)) {
    $logoFile = 'logos/thumbs/' . $mysoc->logo_squarred_mini;
} elseif (!empty($mysoc->logo_squarred_small)) {
    $logoFile = 'logos/thumbs/' . $mysoc->logo_squarred_small;
} elseif (!empty($mysoc->logo_squarred)) {
    $logoFile = 'logos/thumbs/' . $mysoc->logo_squarred;
} elseif (!empty($mysoc->logo)) {
    $logoFile = 'logos/thumbs/' . $mysoc->logo;
}
?>
<div id="id-top" class="digirisk-pwa-header">
    <div class="digirisk-pwa-header__brand">
        <?php if (!empty($logoFile)) {
            $logoUrl = DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($logoFile);
            print '<img class="digirisk-pwa-header__logo" src="' . $logoUrl . '" alt="">';
        } ?>
        <span class="digirisk-pwa-header__title"><?php print dol_escape_htmltag(!empty($pwaHeaderTitle) ? $pwaHeaderTitle : ''); ?></span>
    </div>
    <a href="<?php print DOL_URL_ROOT; ?>/user/card.php?id=<?php print $user->id; ?>" class="digirisk-pwa-header__user" style="text-decoration: none;">
        <?php if (!empty($user->photo)) {
            $userPhotoUrl = DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=userphoto&file=' . urlencode($user->photo);
            print '<img src="' . $userPhotoUrl . '" alt="" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">';
        } else {
            print '<i class="fas fa-user-circle"></i>';
        } ?>
        <span class="digirisk-pwa-header__username"><?php print dol_escape_htmltag($user->getFullName($langs)); ?></span>
    </a>
</div>
