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
 * \file    core/tpl/frontend/firepermit_mobile_success_firewatch.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Surveillance after the hot work on the mobile screen of a signed fire permit: where the
 *          rounds stand, and the way to the screen where they are walked.
 *          Expects: $langs, $object, $fpFireWatch (digiriskFirePermitRoundsSummary()), $fpFireWatchStarted,
 *                   $fpFireWatchUrl (screen of the rounds, empty once the permit is archived).
 */

global $langs;

$fpFireWatchBadges = [
    'working'  => 'FireWatchStateWorking',
    'late'     => 'FireWatchRoundStateLate',
    'due'      => 'FireWatchRoundStateDue',
    'upcoming' => 'FireWatchRoundStateUpcoming',
    'done'     => 'FireWatchStateDone',
];

// The button says what is expected next on site
if (!$fpFireWatchStarted) {
    $fpFireWatchAction = ['fa-flag-checkered', $langs->trans('FireWatchDeclareWorkEnd')];
} elseif (in_array($fpFireWatch['state'], ['due', 'late'], true)) {
    $fpFireWatchAction = ['fa-clipboard-check', $langs->trans('FireWatchRecordRound', $fpFireWatch['nextRound']->position)];
} else {
    $fpFireWatchAction = ['fa-walking', $langs->trans('MobileFireWatchOpen')];
}
?>
<div class="digirisk-mobile-card digirisk-mobile-extsign digirisk-mobile-firewatch">
    <div class="digirisk-mobile-extsign__title digirisk-mobile-extsign__title--split">
        <div><i class="fas fa-walking"></i> <?php print $langs->trans('FireWatchTitle'); ?></div>
        <span class="digirisk-firewatch__badge digirisk-firewatch__badge--<?php print $fpFireWatch['state']; ?>"><?php print $langs->trans($fpFireWatchBadges[$fpFireWatch['state']]); ?></span>
    </div>

    <?php if (!$fpFireWatchStarted) { ?>
        <div class="digirisk-mobile-firewatch__text"><?php print $langs->trans('FireWatchIntro', implode(', ', array_map('digiriskFirePermitFormatDelay', digiriskFirePermitGetRoundDelays()))); ?></div>
    <?php } else { ?>
        <ul class="digirisk-mobile-firewatch__facts">
            <li><i class="fas fa-flag-checkered"></i> <span><?php print $langs->trans('FireWatchWorkEnd'); ?> :</span> <strong><?php print dol_print_date($object->date_work_end, 'dayhour', 'tzuserrel'); ?></strong></li>
            <li><i class="fas fa-user-shield"></i> <span><?php print $langs->trans('FireWatchName'); ?> :</span> <strong><?php print dol_escape_htmltag($object->firewatch_name); ?></strong></li>
            <li><i class="fas fa-tasks"></i> <?php print $langs->trans('FireWatchRoundsDone', $fpFireWatch['doneCount'], $fpFireWatch['total']); ?></li>
            <?php if ($fpFireWatch['nextRound'] !== null) { ?>
                <li><i class="far fa-clock"></i> <?php print $langs->trans('FireWatchRoundNumber', $fpFireWatch['nextRound']->position); ?> &middot; <strong><?php print dol_print_date($fpFireWatch['nextRound']->date_planned, 'hour', 'tzuserrel'); ?></strong></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <?php if (dol_strlen($fpFireWatchUrl)) { ?>
        <a class="digirisk-mobile-success__button digirisk-mobile-success__button--primary" href="<?php print dol_escape_htmltag($fpFireWatchUrl); ?>">
            <i class="fas <?php print $fpFireWatchAction[0]; ?>"></i> <?php print $fpFireWatchAction[1]; ?>
        </a>
    <?php } ?>
</div>
