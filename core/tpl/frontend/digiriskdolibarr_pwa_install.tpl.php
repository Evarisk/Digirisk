<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/digiriskdolibarr_pwa_install.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   "Install the application" banner of the PWA, and the help sheet shown when the browser
 *          cannot install it from a button (iPhone, Firefox). Rendered hidden: pwa-install.js
 *          reveals it unless the application already runs installed.
 *          Expects: $langs.
 */

global $langs;
?>
<div class="digirisk-pwa-install">
    <img class="digirisk-pwa-install__icon" src="<?php print dol_buildpath('/custom/digiriskdolibarr/img/digiriskdolibarr_color_192.png', 1); ?>" alt="">
    <div class="digirisk-pwa-install__text">
        <span class="digirisk-pwa-install__title"><?php print $langs->trans('PwaInstallTitle'); ?></span>
        <span class="digirisk-pwa-install__description"><?php print $langs->trans('PwaInstallDescription'); ?></span>
    </div>
    <button type="button" class="digirisk-pwa-install__button" data-action="pwa-install"><?php print $langs->trans('PwaInstallButton'); ?></button>
    <button type="button" class="digirisk-pwa-install__dismiss" data-action="pwa-install-dismiss" aria-label="<?php print dol_escape_htmltag($langs->trans('PwaInstallLater')); ?>">
        <i class="fas fa-times"></i>
    </button>
</div>

<div class="digirisk-pwa-install-overlay" data-action="pwa-install-help-close"></div>
<div class="digirisk-pwa-install-help" role="dialog" aria-modal="true" aria-labelledby="digirisk-pwa-install-help-title" data-platform="other">
    <div class="digirisk-pwa-install-help__header">
        <span id="digirisk-pwa-install-help-title" class="digirisk-pwa-install-help__title"><?php print $langs->trans('PwaInstallTitle'); ?></span>
        <button type="button" class="digirisk-pwa-install-help__close" data-action="pwa-install-help-close" aria-label="<?php print dol_escape_htmltag($langs->trans('Close')); ?>">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <ol class="digirisk-pwa-install-help__steps digirisk-pwa-install-help__steps--ios">
        <li><i class="fas fa-share-square"></i><span><?php print $langs->trans('PwaInstallIosStep1'); ?></span></li>
        <li><i class="far fa-plus-square"></i><span><?php print $langs->trans('PwaInstallIosStep2'); ?></span></li>
        <li><i class="fas fa-check"></i><span><?php print $langs->trans('PwaInstallIosStep3'); ?></span></li>
    </ol>

    <ol class="digirisk-pwa-install-help__steps digirisk-pwa-install-help__steps--other">
        <li><i class="fas fa-ellipsis-v"></i><span><?php print $langs->trans('PwaInstallOtherStep1'); ?></span></li>
        <li><i class="fas fa-download"></i><span><?php print $langs->trans('PwaInstallOtherStep2'); ?></span></li>
        <li><i class="fas fa-check"></i><span><?php print $langs->trans('PwaInstallOtherStep3'); ?></span></li>
    </ol>

    <p class="digirisk-pwa-install-help__note"><?php print $langs->trans('PwaInstallAlreadyInstalled'); ?></p>
</div>
