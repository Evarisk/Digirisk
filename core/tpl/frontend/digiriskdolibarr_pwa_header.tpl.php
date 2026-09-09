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
    <a href="<?php print DOL_URL_ROOT; ?>/custom/digiriskdolibarr/digiriskdolibarrindex.php" class="digirisk-pwa-header__brand" style="text-decoration: none;">
        <?php if (!empty($logoFile)) {
            $logoUrl = DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode($logoFile);
            print '<img class="digirisk-pwa-header__logo" src="' . $logoUrl . '" alt="">';
        } ?>
        <span class="digirisk-pwa-header__title">
            <?php 
            print dol_escape_htmltag($mysoc->name); 
            if (!empty($pwaHeaderTitle) && $pwaHeaderTitle !== $langs->trans('PwaNavHome') && $pwaHeaderTitle !== 'Accueil') {
                print ' - ' . dol_escape_htmltag($pwaHeaderTitle);
            }
            ?>
        </span>
    </a>
    <a href="<?php print DOL_URL_ROOT; ?>/user/card.php?id=<?php print $user->id; ?>" class="digirisk-pwa-header__user" style="text-decoration: none;">
        <?php
        // Build the URL through Form::showphoto(): a user photo lives in <user id>/photos/, so a
        // hand-made viewimage.php link missing that sub-directory always renders as a broken
        // image. It also falls back to a generic icon when the file is absent from the disk.
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        $pwaHeaderForm = new Form($db);
        print $pwaHeaderForm->showphoto('userphoto', $user, 0, 0, 0, 'digirisk-pwa-header__avatar', 'small', 0);
        ?>
        <span class="digirisk-pwa-header__username"><?php print dol_escape_htmltag($user->getFullName($langs)); ?></span>
    </a>
</div>
