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
 * \file    core/tpl/firepermit/firepermit_rounds_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   "Surveillance after the work" block of the fire permit card: end of the hot work, safety
 *          watcher and the rounds planned after it.
 *
 * Expects from the card:
 *   $object             FirePermit
 *   $form               Form
 *   $permissiontoadd    bool
 *   $fireWatchRounds    FirePermitRound[] (empty until the end of the work is declared)
 *   $fireWatchPhotos    array             Photos of each round, keyed by round id
 *   $fireWatchDelays    int[]             Delays the rounds will be planned at
 *   $fireWatchNow       int               Timestamp the states are computed for
 *   $fireWatchPublicUrl string            Link to the public interface of the rounds, empty when closed
 *   $fireWatchQrCode    string            Inline SVG QR code of that link
 */

global $langs;

$fireWatchStateBadges = [
    'done'     => ['status4', 'FireWatchRoundStateDone'],
    'due'      => ['status1', 'FireWatchRoundStateDue'],
    'late'     => ['status8', 'FireWatchRoundStateLate'],
    'upcoming' => ['status0', 'FireWatchRoundStateUpcoming'],
];

$fireWatchDoneCount = 0;
foreach ($fireWatchRounds as $fireWatchRound) {
    if ((int) $fireWatchRound->status === FirePermitRound::STATUS_DONE) {
        $fireWatchDoneCount++;
    }
}

$fireWatchDelayLabels = array_map('digiriskFirePermitFormatDelay', $fireWatchDelays);
?>
<div class="firepermit-firewatch" id="firewatch">
    <?php print load_fiche_titre('<i class="fas fa-walking pictofixedwidth"></i>' . $langs->trans('FireWatchTitle'), '', ''); ?>

    <?php if (empty($object->date_work_end)) { ?>
        <div class="firepermit-firewatch__intro opacitymedium">
            <?php print $langs->trans('FireWatchIntro', implode(', ', $fireWatchDelayLabels)); ?>
        </div>

        <?php if ($object->status == FirePermit::STATUS_LOCKED && $permissiontoadd) { ?>
            <form method="POST" action="<?php print $_SERVER['PHP_SELF'] . '?id=' . $object->id; ?>#firewatch" class="firepermit-firewatch__form">
                <input type="hidden" name="token" value="<?php print newToken(); ?>">
                <input type="hidden" name="action" value="declareWorkEnd">
                <table class="border centpercent tableforfield">
                    <tr>
                        <td class="titlefield fieldrequired"><?php print $langs->trans('FireWatchWorkEnd'); ?></td>
                        <td><?php print $form->selectDate($fireWatchNow, 'work_end', 1, 1, 0, '', 1, 1, 0, '', '', '', '', 1, '', '', 'tzuserrel'); ?></td>
                    </tr>
                    <tr>
                        <td class="titlefield fieldrequired"><label for="firewatch_name"><?php print $langs->trans('FireWatchName'); ?></label></td>
                        <td>
                            <input type="text" class="flat minwidth300" name="firewatch_name" id="firewatch_name" maxlength="255" value="<?php print dol_escape_htmltag(GETPOST('firewatch_name', 'alphanohtml')); ?>" required>
                            <div class="opacitymedium small"><?php print $langs->trans('FireWatchNameHelp'); ?></div>
                        </td>
                    </tr>
                </table>
                <div class="center firepermit-firewatch__actions">
                    <input type="submit" class="button" value="<?php print dol_escape_htmltag($langs->trans('FireWatchDeclareWorkEnd')); ?>">
                </div>
            </form>
        <?php } else { ?>
            <div class="info"><?php print $langs->trans('FireWatchPermitMustBeLocked'); ?></div>
        <?php } ?>
    <?php } else { ?>
        <table class="border centpercent tableforfield firepermit-firewatch__summary">
            <tr>
                <td class="titlefield"><?php print $langs->trans('FireWatchWorkEnd'); ?></td>
                <td><?php print dol_print_date($object->date_work_end, 'dayhour', 'tzuserrel'); ?></td>
            </tr>
            <tr>
                <td class="titlefield"><?php print $langs->trans('FireWatchName'); ?></td>
                <td><?php print dol_escape_htmltag($object->firewatch_name); ?></td>
            </tr>
            <tr>
                <td class="titlefield"><?php print $langs->trans('FireWatchProgress'); ?></td>
                <td>
                    <?php print $langs->trans('FireWatchRoundsDone', $fireWatchDoneCount, count($fireWatchRounds)); ?>
                    <?php if ($fireWatchDoneCount < count($fireWatchRounds)) { ?>
                        <span class="opacitymedium"> &mdash; <?php print $langs->trans('FireWatchArchiveBlocked'); ?></span>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <div class="div-table-responsive-no-min">
            <table class="noborder centpercent firepermit-firewatch__rounds">
                <tr class="liste_titre">
                    <td><?php print $langs->trans('FireWatchRound'); ?></td>
                    <td><?php print $langs->trans('FireWatchRoundPlanned'); ?></td>
                    <td class="center"><?php print $langs->trans('Status'); ?></td>
                    <td><?php print $langs->trans('FireWatchRoundDone'); ?></td>
                    <td><?php print $langs->trans('FireWatchRoundBy'); ?></td>
                    <td><?php print $langs->trans('Comment'); ?></td>
                    <td class="center"><?php print $langs->trans('Photos'); ?></td>
                    <td class="center"><?php print $langs->trans('FireWatchGeoloc'); ?></td>
                    <td class="center"><?php print $langs->trans('Signature'); ?></td>
                </tr>
                <?php foreach ($fireWatchRounds as $fireWatchRound) {
                    list($badgeType, $badgeLabel) = $fireWatchStateBadges[$fireWatchRound->getState($fireWatchNow)]; ?>
                    <tr class="oddeven">
                        <td class="nowraponall">
                            <?php print $langs->trans('FireWatchRoundNumber', $fireWatchRound->position); ?>
                            <span class="opacitymedium">(+<?php print digiriskFirePermitFormatDelay((int) $fireWatchRound->delay_minutes); ?>)</span>
                        </td>
                        <td class="nowraponall"><?php print dol_print_date($fireWatchRound->date_planned, 'dayhour', 'tzuserrel'); ?></td>
                        <td class="center"><?php print dolGetStatus($langs->trans($badgeLabel), $langs->trans($badgeLabel), '', $badgeType, 5); ?></td>
                        <td class="nowraponall"><?php print !empty($fireWatchRound->date_done) ? dol_print_date($fireWatchRound->date_done, 'dayhour', 'tzuserrel') : ''; ?></td>
                        <td><?php print dol_escape_htmltag($fireWatchRound->watcher_name); ?></td>
                        <td class="tdoverflowmax300"><?php print dol_nl2br(dol_escape_htmltag($fireWatchRound->description)); ?></td>
                        <td class="center firepermit-firewatch__photos">
                            <?php foreach ($fireWatchPhotos[$fireWatchRound->id] ?? [] as $fireWatchPhoto) { ?>
                                <a href="<?php print dol_escape_htmltag($fireWatchPhoto['url'] . '&attachment=0'); ?>" target="_blank" rel="noopener">
                                    <img class="firepermit-firewatch__photo" src="<?php print dol_escape_htmltag($fireWatchPhoto['url']); ?>" alt="">
                                </a>
                            <?php } ?>
                        </td>
                        <td class="center">
                            <?php if ($fireWatchRound->latitude !== null && $fireWatchRound->longitude !== null) {
                                $mapUrl = 'https://www.openstreetmap.org/?mlat=' . urlencode((string) $fireWatchRound->latitude) . '&mlon=' . urlencode((string) $fireWatchRound->longitude) . '#map=18/' . urlencode((string) $fireWatchRound->latitude) . '/' . urlencode((string) $fireWatchRound->longitude); ?>
                                <a href="<?php print dol_escape_htmltag($mapUrl); ?>" target="_blank" rel="noopener noreferrer" title="<?php print dol_escape_htmltag($fireWatchRound->latitude . ', ' . $fireWatchRound->longitude); ?>"><i class="fas fa-map-marker-alt"></i></a>
                            <?php } ?>
                        </td>
                        <td class="center">
                            <?php $fireWatchSignature = ((int) $fireWatchRound->status === FirePermitRound::STATUS_DONE) ? $fireWatchRound->getSignature() : '';
                            if (digiriskIsValidSignature($fireWatchSignature)) { ?>
                                <img class="firepermit-firewatch__signature" src="<?php print dol_escape_htmltag($fireWatchSignature); ?>" alt="<?php print dol_escape_htmltag($langs->trans('Signature')); ?>">
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

    <?php if (dol_strlen($fireWatchPublicUrl)) { ?>
        <div class="firepermit-firewatch__public">
            <div class="firepermit-firewatch__qr"><?php print $fireWatchQrCode; ?></div>
            <div>
                <div><strong><?php print $langs->trans('FireWatchPublicLink'); ?></strong></div>
                <div class="opacitymedium small"><?php print $langs->trans('FireWatchPublicLinkHelp'); ?></div>
                <div class="firepermit-firewatch__url">
                    <a href="<?php print dol_escape_htmltag($fireWatchPublicUrl); ?>" target="_blank" rel="noopener"><?php print dol_escape_htmltag($fireWatchPublicUrl); ?></a>
                    <?php print showValueWithClipboardCPButton($fireWatchPublicUrl, 0, 'none'); ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
