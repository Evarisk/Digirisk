<?php
/* Copyright (C) 2021-2026 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/firepermit/firepermit_rounds_public_list.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Screen of the safety watcher listing the fire permits to watch, most urgent first.
 *
 * Expects from public/firepermit/firepermit_rounds.php and view/frontend/pwa_firepermit_rounds.php:
 *   $langs
 *   $roundsPublic bool  True on the public interface, which has no application header to carry the title
 *   $listRows     array of ['url', 'ref', 'label', 'society', 'state', 'nextRound' (FirePermitRound|null), 'dateEnd', 'dateWorkEnd']
 */

$listStateBadges = [
    'late'     => 'FireWatchRoundStateLate',
    'due'      => 'FireWatchRoundStateDue',
    'upcoming' => 'FireWatchRoundStateUpcoming',
    'working'  => 'FireWatchStateWorking',
    'done'     => 'FireWatchStateDone',
];
?>
<div class="digirisk-firewatch" data-label-in="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchIn', '%s')); ?>" data-label-ago="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchAgo', '%s')); ?>">
    <?php if ($roundsPublic) { ?>
        <div class="digirisk-firewatch__header">
            <i class="fas fa-fire-alt"></i>
            <div>
                <div class="digirisk-firewatch__title"><?php print $langs->trans('FireWatchPublicTitle'); ?></div>
                <div class="digirisk-firewatch__subtitle"><?php print $langs->trans('FireWatchPublicSubtitle'); ?></div>
            </div>
        </div>
    <?php } else { ?>
        <div class="digirisk-firewatch__lead"><?php print $langs->trans('FireWatchPublicSubtitle'); ?></div>
    <?php } ?>

    <?php if (empty($listRows)) { ?>
        <div class="digirisk-firewatch__empty">
            <i class="fas fa-check-circle"></i>
            <div><?php print $langs->trans('FireWatchNoPermit'); ?></div>
        </div>
    <?php } else { ?>
        <div class="digirisk-firewatch__permits">
            <?php foreach ($listRows as $listRow) { ?>
                <a class="digirisk-firewatch__permit digirisk-firewatch__permit--<?php print $listRow['state']; ?>" href="<?php print dol_escape_htmltag($listRow['url']); ?>">
                    <div class="digirisk-firewatch__permit-head">
                        <span class="digirisk-firewatch__ref"><?php print dol_escape_htmltag($listRow['ref']); ?></span>
                        <span class="digirisk-firewatch__badge digirisk-firewatch__badge--<?php print $listRow['state']; ?>"><?php print $langs->trans($listStateBadges[$listRow['state']]); ?></span>
                    </div>
                    <div class="digirisk-firewatch__permit-title"><?php print dol_escape_htmltag($listRow['label']); ?></div>
                    <?php if (dol_strlen($listRow['society'])) { ?>
                        <div class="digirisk-firewatch__line"><i class="fas fa-building"></i> <?php print dol_escape_htmltag($listRow['society']); ?></div>
                    <?php } ?>
                    <div class="digirisk-firewatch__line">
                        <?php if ($listRow['state'] === 'working') { ?>
                            <i class="fas fa-hard-hat"></i> <?php print $langs->trans('FireWatchWorkInProgress'); ?>
                            <?php if (!empty($listRow['dateEnd'])) { ?>
                                &middot; <?php print $langs->trans('FireWatchPermitValidUntil'); ?> <time data-firewatch-time="<?php print (int) $listRow['dateEnd']; ?>" data-firewatch-format="day"><?php print dol_print_date($listRow['dateEnd'], 'day', 'tzuserrel'); ?></time>
                            <?php } ?>
                        <?php } elseif ($listRow['state'] === 'done') { ?>
                            <i class="fas fa-check"></i> <?php print $langs->trans('FireWatchAllRoundsDone'); ?>
                        <?php } else { ?>
                            <i class="fas fa-walking"></i>
                            <?php print $langs->trans('FireWatchRoundNumber', $listRow['nextRound']->position); ?> &middot;
                            <time data-firewatch-time="<?php print (int) $listRow['nextRound']->date_planned; ?>"><?php print dol_print_date($listRow['nextRound']->date_planned, 'hour', 'tzuserrel'); ?></time>
                            <span class="digirisk-firewatch__countdown" data-firewatch-countdown="<?php print (int) $listRow['nextRound']->date_planned; ?>"></span>
                        <?php } ?>
                    </div>
                </a>
            <?php } ?>
        </div>
    <?php } ?>
</div>
