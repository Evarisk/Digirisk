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
 * \file    core/tpl/frontend/safetytalk_topbar.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Bar on top of the safety talk screens, kept in sight while scrolling: the way back on the left,
 *          the actions of the screen on the right, instead of buttons lost under a long list.
 *          Expects: $topbarBackUrl, $topbarBackLabel, $topbarActions (each ['label', 'icon', 'url'] for a link,
 *          optional 'primary', 'target'; or ['label', 'icon', 'post' => [field => value], 'confirm'] for an action
 *          sent with its token).
 */
?>
<div class="digirisk-safetytalk-topbar">
    <a class="digirisk-safetytalk-topbar__back" href="<?php print dol_escape_htmltag($topbarBackUrl); ?>">
        <i class="fas fa-chevron-left"></i>
        <span><?php print dol_escape_htmltag($topbarBackLabel); ?></span>
    </a>
    <?php if (!empty($topbarActions)) { ?>
    <div class="digirisk-safetytalk-topbar__actions">
        <?php foreach ($topbarActions as $topbarAction) {
            $topbarActionClass = 'digirisk-safetytalk-topbar__action' . (!empty($topbarAction['primary']) ? ' digirisk-safetytalk-topbar__action--primary' : '') . (!empty($topbarAction['post']) ? ' digirisk-safetytalk-topbar__action--danger' : '');
            if (!empty($topbarAction['post'])) { ?>
        <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-confirm" data-confirm="<?php print dol_escape_htmltag($topbarAction['confirm'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php print newToken(); ?>">
            <?php foreach ($topbarAction['post'] as $topbarField => $topbarValue) { ?>
            <input type="hidden" name="<?php print dol_escape_htmltag($topbarField); ?>" value="<?php print dol_escape_htmltag($topbarValue); ?>">
            <?php } ?>
            <button type="submit" class="<?php print $topbarActionClass; ?>" title="<?php print dol_escape_htmltag($topbarAction['label']); ?>" aria-label="<?php print dol_escape_htmltag($topbarAction['label']); ?>">
                <i class="fas <?php print dol_escape_htmltag($topbarAction['icon']); ?>"></i>
            </button>
        </form>
            <?php } else { ?>
        <a class="<?php print $topbarActionClass; ?>" href="<?php print dol_escape_htmltag($topbarAction['url']); ?>"<?php print !empty($topbarAction['target']) ? ' target="' . dol_escape_htmltag($topbarAction['target']) . '"' : ''; ?> title="<?php print dol_escape_htmltag($topbarAction['label']); ?>">
            <i class="fas <?php print dol_escape_htmltag($topbarAction['icon']); ?>"></i>
            <span><?php print dol_escape_htmltag($topbarAction['label']); ?></span>
        </a>
            <?php }
        } ?>
    </div>
    <?php } ?>
</div>
