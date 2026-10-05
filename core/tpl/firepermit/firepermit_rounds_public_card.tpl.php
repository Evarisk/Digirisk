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
 * \file    core/tpl/firepermit/firepermit_rounds_public_card.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Screen of the safety watcher for one fire permit: end of the hot work, then the rounds,
 *          the earliest one due being recorded from the form at the bottom.
 *
 * Expects from public/firepermit/firepermit_rounds.php and view/frontend/pwa_firepermit_rounds.php:
 *   $langs, $now
 *   $roundsCanRecord   bool            False when the reader may only follow the surveillance
 *   $roundsDefaultName string          Name of the watcher filled in the forms, empty to let them type it
 *   $object          FirePermit (locked)
 *   $societyName     string
 *   $workLocation    string
 *   $workTypes       array of ['name', 'description']
 *   $rounds          FirePermitRound[]
 *   $roundToRecord   FirePermitRound|null The round the form records
 *   $roundItemLevels array                Level of each round item (comment, photo, geoloc, signature)
 *   $nextReloadAt    int                  When the next round opens, 0 when none is waiting
 *   $errors          string[]
 *   $saved           string               'workend' or 'round' after a successful post
 *   $listUrl         string
 *   $permitUrl       callable             Builds the URL of a permit
 */

$roundStateLabels = [
    'done'     => 'FireWatchRoundStateDone',
    'due'      => 'FireWatchRoundStateDue',
    'late'     => 'FireWatchRoundStateLate',
    'upcoming' => 'FireWatchRoundStateUpcoming',
];

$requiredMark = function (string $item) use ($roundItemLevels) {
    return $roundItemLevels[$item] === DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED ? ' <span class="digirisk-firewatch__required">*</span>' : '';
};

$doneCount = 0;
foreach ($rounds as $round) {
    if ((int) $round->status === FirePermitRound::STATUS_DONE) {
        $doneCount++;
    }
}
?>
<div class="digirisk-firewatch"
     data-label-in="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchIn', '%s')); ?>"
     data-label-ago="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchAgo', '%s')); ?>"
     data-reload-at="<?php print (int) $nextReloadAt; ?>"
     data-now="<?php print (int) $now; ?>">

    <a class="digirisk-firewatch__back" href="<?php print dol_escape_htmltag($listUrl); ?>"><i class="fas fa-chevron-left"></i> <?php print $langs->trans('FireWatchBackToList'); ?></a>

    <?php if ($saved === 'workend') { ?>
        <div class="digirisk-firewatch__banner digirisk-firewatch__banner--success"><i class="fas fa-check-circle"></i> <?php print $langs->trans('FireWatchWorkEndDeclared'); ?></div>
    <?php } elseif ($saved === 'round') { ?>
        <div class="digirisk-firewatch__banner digirisk-firewatch__banner--success"><i class="fas fa-check-circle"></i> <?php print $langs->trans('FireWatchRoundSaved'); ?></div>
    <?php } ?>

    <?php if (!empty($errors)) { ?>
        <div class="digirisk-firewatch__banner digirisk-firewatch__banner--error">
            <ul>
                <?php foreach ($errors as $errorMessage) { ?>
                    <li><?php print dol_escape_htmltag($errorMessage); ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <div class="digirisk-firewatch__card">
        <div class="digirisk-firewatch__permit-head">
            <span class="digirisk-firewatch__ref"><?php print dol_escape_htmltag($object->ref); ?></span>
        </div>
        <div class="digirisk-firewatch__permit-title"><?php print dol_escape_htmltag($object->label); ?></div>
        <?php if (dol_strlen($societyName)) { ?>
            <div class="digirisk-firewatch__line"><i class="fas fa-industry"></i> <?php print dol_escape_htmltag($societyName); ?></div>
        <?php } ?>
        <?php if (dol_strlen($workLocation)) { ?>
            <div class="digirisk-firewatch__line"><i class="fas fa-map-marker-alt"></i> <?php print dol_escape_htmltag($workLocation); ?></div>
        <?php } ?>
        <?php foreach ($workTypes as $workType) { ?>
            <div class="digirisk-firewatch__line">
                <i class="fas fa-fire"></i> <?php print dol_escape_htmltag($workType['name']); ?>
                <?php if (dol_strlen($workType['description'])) { ?>
                    <span class="digirisk-firewatch__muted">&mdash; <?php print dol_escape_htmltag(dol_string_nohtmltag($workType['description'])); ?></span>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <?php if (empty($object->date_work_end)) { ?>
        <div class="digirisk-firewatch__card">
            <div class="digirisk-firewatch__section-title"><i class="fas fa-hard-hat"></i> <?php print $langs->trans('FireWatchWorkInProgress'); ?></div>
            <p class="digirisk-firewatch__muted"><?php print $langs->trans('FireWatchIntro', implode(', ', array_map('digiriskFirePermitFormatDelay', digiriskFirePermitGetRoundDelays()))); ?></p>

            <?php if ($roundsCanRecord) { ?>
            <form method="POST" action="<?php print dol_escape_htmltag($permitUrl($object->id)); ?>" class="digirisk-firewatch__form">
                <input type="hidden" name="token" value="<?php print newToken(); ?>">
                <input type="hidden" name="action" value="declareWorkEnd">

                <label class="digirisk-firewatch__label" for="firewatch_name"><?php print $langs->trans('FireWatchName'); ?> <span class="digirisk-firewatch__required">*</span></label>
                <input type="text" class="digirisk-firewatch__input" name="firewatch_name" id="firewatch_name" maxlength="255" autocomplete="name" value="<?php print dol_escape_htmltag(GETPOSTISSET('firewatch_name') ? GETPOST('firewatch_name', 'alphanohtml') : $roundsDefaultName); ?>" required>

                <label class="digirisk-firewatch__label" for="ended_ago"><?php print $langs->trans('FireWatchWorkEnd'); ?></label>
                <select class="digirisk-firewatch__input" name="ended_ago" id="ended_ago">
                    <option value="0"><?php print $langs->trans('FireWatchEndedNow'); ?></option>
                    <?php foreach ([15, 30, 45, 60] as $endedAgo) { ?>
                        <option value="<?php print $endedAgo; ?>"><?php print $langs->trans('FireWatchEndedAgo', digiriskFirePermitFormatDelay($endedAgo)); ?></option>
                    <?php } ?>
                </select>

                <button type="submit" class="digirisk-firewatch__submit"><i class="fas fa-flag-checkered"></i> <?php print $langs->trans('FireWatchDeclareWorkEnd'); ?></button>
            </form>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="digirisk-firewatch__card">
            <div class="digirisk-firewatch__section-title"><i class="fas fa-walking"></i> <?php print $langs->trans('FireWatchTitle'); ?></div>
            <div class="digirisk-firewatch__line"><i class="fas fa-flag-checkered"></i> <?php print $langs->trans('FireWatchWorkEnd'); ?> :
                <time data-firewatch-time="<?php print (int) $object->date_work_end; ?>"><?php print dol_print_date($object->date_work_end, 'dayhour', 'tzuserrel'); ?></time>
            </div>
            <div class="digirisk-firewatch__line"><i class="fas fa-user-shield"></i> <?php print $langs->trans('FireWatchName'); ?> : <?php print dol_escape_htmltag($object->firewatch_name); ?></div>
            <div class="digirisk-firewatch__line"><i class="fas fa-tasks"></i> <?php print $langs->trans('FireWatchRoundsDone', $doneCount, count($rounds)); ?></div>

            <ol class="digirisk-firewatch__rounds">
                <?php foreach ($rounds as $round) {
                    $roundState = $round->getState($now); ?>
                    <li class="digirisk-firewatch__round digirisk-firewatch__round--<?php print $roundState; ?>">
                        <div class="digirisk-firewatch__round-head">
                            <span class="digirisk-firewatch__round-name">
                                <?php print $langs->trans('FireWatchRoundNumber', $round->position); ?>
                                <span class="digirisk-firewatch__muted">+<?php print digiriskFirePermitFormatDelay((int) $round->delay_minutes); ?></span>
                            </span>
                            <span class="digirisk-firewatch__badge digirisk-firewatch__badge--<?php print $roundState; ?>"><?php print $langs->trans($roundStateLabels[$roundState]); ?></span>
                        </div>
                        <?php if ($roundState === 'done') { ?>
                            <div class="digirisk-firewatch__line">
                                <i class="fas fa-check"></i>
                                <time data-firewatch-time="<?php print (int) $round->date_done; ?>"><?php print dol_print_date($round->date_done, 'hour', 'tzuserrel'); ?></time>
                                &middot; <?php print dol_escape_htmltag($round->watcher_name); ?>
                            </div>
                            <?php if (dol_strlen($round->description)) { ?>
                                <div class="digirisk-firewatch__muted"><?php print dol_nl2br(dol_escape_htmltag($round->description)); ?></div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="digirisk-firewatch__line">
                                <i class="far fa-clock"></i>
                                <time data-firewatch-time="<?php print (int) $round->date_planned; ?>"><?php print dol_print_date($round->date_planned, 'hour', 'tzuserrel'); ?></time>
                                <span class="digirisk-firewatch__countdown" data-firewatch-countdown="<?php print (int) $round->date_planned; ?>"></span>
                            </div>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ol>

            <?php if ($doneCount === count($rounds)) { ?>
                <div class="digirisk-firewatch__banner digirisk-firewatch__banner--success"><i class="fas fa-shield-alt"></i> <?php print $langs->trans('FireWatchAllRoundsDoneHelp'); ?></div>
            <?php } elseif ($roundToRecord === null) { ?>
                <div class="digirisk-firewatch__banner"><i class="far fa-clock"></i> <?php print $langs->trans('FireWatchNextRoundLater'); ?></div>
            <?php } ?>
        </div>

        <?php if ($roundToRecord !== null && $roundsCanRecord) { ?>
            <!-- No file in the post: the photos are sent by the media block as soon as they are taken -->
            <form method="POST" action="<?php print dol_escape_htmltag($permitUrl($object->id)); ?>" class="digirisk-firewatch__card digirisk-firewatch__round-form"
                  data-signature-level="<?php print $roundItemLevels['signature']; ?>"
                  data-geoloc-level="<?php print $roundItemLevels['geoloc']; ?>"
                  data-photo-level="<?php print $roundItemLevels['photo']; ?>"
                  data-error-signature="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchErrorSignatureRequired')); ?>"
                  data-error-geoloc="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchErrorGeolocRequired')); ?>"
                  data-error-photo="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchErrorPhotoRequired')); ?>">
                <input type="hidden" name="token" value="<?php print newToken(); ?>">
                <input type="hidden" name="action" value="recordRound">
                <input type="hidden" name="round_id" value="<?php print (int) $roundToRecord->id; ?>">

                <div class="digirisk-firewatch__section-title"><i class="fas fa-clipboard-check"></i> <?php print $langs->trans('FireWatchRecordRound', $roundToRecord->position); ?></div>

                <label class="digirisk-firewatch__label" for="watcher_name"><?php print $langs->trans('FireWatchName'); ?> <span class="digirisk-firewatch__required">*</span></label>
                <input type="text" class="digirisk-firewatch__input" name="watcher_name" id="watcher_name" maxlength="255" autocomplete="name" required
                       value="<?php print dol_escape_htmltag(GETPOSTISSET('watcher_name') ? GETPOST('watcher_name', 'alphanohtml') : ($roundsDefaultName ?: $object->firewatch_name)); ?>">

                <?php if ($roundItemLevels['comment'] !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) { ?>
                    <label class="digirisk-firewatch__label" for="comment"><?php print $langs->trans('FireWatchRoundComment') . $requiredMark('comment'); ?></label>
                    <textarea class="digirisk-firewatch__input" name="comment" id="comment" rows="3" placeholder="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchRoundCommentPlaceholder')); ?>"<?php print $roundItemLevels['comment'] === DIGIRISK_FIREPERMIT_ROUND_ITEM_REQUIRED ? ' required' : ''; ?>><?php print dol_escape_htmltag(GETPOST('comment', 'alphanohtml')); ?></textarea>
                <?php } ?>

                <?php if ($roundItemLevels['photo'] !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) { ?>
                    <div class="digirisk-firewatch__label"><?php print $langs->trans('Photos') . $requiredMark('photo'); ?></div>
                    <div class="digirisk-firewatch__medias">
                        <?php print digiriskFirePermitRoundMediaBlock($roundToRecord); ?>
                    </div>
                <?php } ?>

                <?php if ($roundItemLevels['geoloc'] !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) { ?>
                    <div class="digirisk-firewatch__label"><?php print $langs->trans('FireWatchGeoloc') . $requiredMark('geoloc'); ?></div>
                    <div class="digirisk-firewatch__geoloc"
                         data-label-pending="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchGeolocPending')); ?>"
                         data-label-done="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchGeolocDone', '%s')); ?>"
                         data-label-error="<?php print dol_escape_htmltag($langs->transnoentities('FireWatchGeolocError')); ?>">
                        <span class="digirisk-firewatch__geoloc-status"><i class="fas fa-satellite-dish"></i> <?php print $langs->trans('FireWatchGeolocPending'); ?></span>
                        <button type="button" class="digirisk-firewatch__geoloc-retry"><i class="fas fa-redo"></i> <?php print $langs->trans('FireWatchGeolocRetry'); ?></button>
                        <input type="hidden" name="latitude" value="">
                        <input type="hidden" name="longitude" value="">
                    </div>
                <?php } ?>

                <?php if ($roundItemLevels['signature'] !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) { ?>
                    <div class="digirisk-firewatch__label"><?php print $langs->trans('Signature') . $requiredMark('signature'); ?></div>
                    <!-- Saturne signature pad: window.saturne.signature draws on .canvas-signature, .signature-erase clears it -->
                    <div class="signature-element digirisk-firewatch__signature">
                        <canvas class="canvas-container editable canvas-signature"></canvas>
                        <button type="button" class="signature-erase wpeo-button button-square-40 button-rounded button-grey" title="<?php print dol_escape_htmltag($langs->trans('FireWatchClearSignature')); ?>"><i class="fas fa-eraser"></i></button>
                    </div>
                    <input type="hidden" name="signature" value="">
                <?php } ?>

                <div class="digirisk-firewatch__form-error hidden"></div>

                <button type="submit" class="digirisk-firewatch__submit"><i class="fas fa-check"></i> <?php print $langs->trans('FireWatchValidateRound', $roundToRecord->position); ?></button>
            </form>

            <?php if ($roundItemLevels['photo'] !== DIGIRISK_FIREPERMIT_ROUND_ITEM_DISABLED) {
                // Editor the media block opens on each photo taken, before sending it
                include dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');
            } ?>
        <?php } ?>
    <?php } ?>
</div>
