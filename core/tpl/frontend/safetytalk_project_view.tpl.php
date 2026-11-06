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
 * \file    core/tpl/frontend/safetytalk_project_view.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Screen of a cycle of safety talks: who takes part in it, then its talks in the order they are held,
 *          each one with its date, its theme and where its signatures stand.
 *          Expects: $db, $langs, $object (cycle), $talks, $people (digiriskSafetyTalkGetPeopleNames()), $permissiontoadd.
 */

global $db, $langs;

$talkCardUrl = dol_buildpath('/digiriskdolibarr/view/safetytalk/safetytalk_card.php', 1);

$savedBanner = '';
if (GETPOST('saved', 'aZ09') === 'created') {
    $savedBanner = $langs->trans('SafetyTalkCycleCreated', $object->ref);
} elseif (GETPOST('saved', 'aZ09') === 'updated') {
    $savedBanner = $langs->trans('SafetyTalkCycleUpdated', $object->ref);
} elseif (GETPOST('saved', 'aZ09') === 'talk_deleted') {
    $savedBanner = $langs->trans('SafetyTalkTalkDeleted', GETPOST('ref', 'alphanohtml'));
}

$periodLabels = [
    'upcoming' => 'SafetyTalkPeriodUpcoming',
    'today'    => 'SafetyTalkPeriodToday',
    'done'     => 'SafetyTalkPeriodDone',
    'undated'  => 'SafetyTalkPeriodUndated',
];

// Way back and actions on top, in sight whatever the length of the list of talks
$topbarBackUrl   = dol_buildpath('/digiriskdolibarr/view/frontend/pwa_safetytalk_list.php', 1);
$topbarBackLabel = $langs->transnoentities('PwaNavSafetyTalks');
$topbarActions   = [];
if ($permissiontoadd) {
    $topbarActions[] = ['label' => $langs->transnoentities('Modify'), 'icon' => 'fa-pen', 'url' => $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit'];
}
$topbarActions[] = ['label' => $langs->transnoentities('SafetyTalkPlanningPdf'), 'icon' => 'fa-file-pdf', 'url' => $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=planning&token=' . newToken(), 'target' => '_blank'];
if ($permissiontoadd) {
    $topbarActions[] = ['label' => $langs->transnoentities('SafetyTalkNewTalk'), 'icon' => 'fa-plus', 'url' => $talkCardUrl . '?project_id=' . $object->id . '&action=create', 'primary' => true];
}
?>
<div class="pwa-container digirisk-mobile digirisk-safetytalk">
    <?php require __DIR__ . '/safetytalk_topbar.tpl.php'; ?>

    <?php if (dol_strlen($savedBanner)) { ?>
    <div class="digirisk-mobile-success-banner" role="status">
        <i class="fas fa-check-circle"></i>
        <span><?php print dol_escape_htmltag($savedBanner); ?></span>
    </div>
    <?php } ?>

    <?php if (!digiriskSafetyTalkSpreadAvailable()) { ?>
    <div class="digirisk-safetytalk-warning"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('SafetyTalkSpreadMissing'); ?></div>
    <?php } ?>

    <div class="digirisk-mobile-card digirisk-safetytalk-cycle">
        <div class="digirisk-safetytalk-cycle__head">
            <span class="digirisk-safetytalk-ref"><?php print $object->getNomUrl(1, '', 0, '', '-', 0, 0, 'nocss'); ?></span>
            <?php print digiriskSafetyTalkCycleStatusHtml($object); ?>
        </div>
        <div class="digirisk-safetytalk-cycle__title"><?php print dol_escape_htmltag($object->title); ?></div>
        <?php if (dol_strlen(dol_string_nohtmltag((string) $object->description))) { ?>
        <div class="digirisk-safetytalk-cycle__description"><?php print dol_escape_htmltag(dol_string_nohtmltag((string) $object->description, 0), 0, 1); ?></div>
        <?php } ?>

        <div class="digirisk-safetytalk-cycle__label"><i class="fas fa-users"></i> <?php print $langs->trans('SafetyTalkCardParticipants', count($people)); ?></div>
        <?php require __DIR__ . '/safetytalk_people_chips.tpl.php'; ?>
    </div>

    <div class="digirisk-safetytalk-section">
        <span class="digirisk-safetytalk-section__title"><i class="fas fa-comments"></i> <?php print $langs->trans('SafetyTalks'); ?></span>
    </div>

    <?php if (empty($talks)) { ?>
    <div class="digirisk-pwa-empty">
        <i class="fas fa-comments"></i>
        <div><?php print $langs->trans('SafetyTalkNoTalk'); ?></div>
    </div>
    <?php } else { ?>
    <div class="digirisk-safetytalk-timeline">
        <?php foreach ($talks as $talk) {
            $theme       = digiriskSafetyTalkGetTheme((string) ($talk->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] ?? ''));
            $period      = digiriskSafetyTalkGetPeriod($talk);
            $signatories = digiriskSafetyTalkGetSignatories($db, (int) $talk->id);
            $signedCount = count(array_filter($signatories, 'digiriskSafetyTalkHasSigned'));
            $fileCount   = count(digiriskSafetyTalkGetDocuments($talk)); ?>
        <a class="digirisk-safetytalk-talk digirisk-safetytalk-talk--<?php print $period; ?>" href="<?php print dol_escape_htmltag($talkCardUrl . '?id=' . $talk->id); ?>">
            <span class="digirisk-safetytalk-talk__picto">
                <?php if ($theme !== null) { ?>
                <img src="<?php print dol_escape_htmltag($theme['src']); ?>" alt="<?php print dol_escape_htmltag($theme['name']); ?>" loading="lazy">
                <?php } else { ?>
                <i class="fas fa-comments"></i>
                <?php } ?>
            </span>
            <span class="digirisk-safetytalk-talk__body">
                <span class="digirisk-safetytalk-talk__date">
                    <i class="fas fa-calendar-alt"></i> <?php print !empty($talk->date_start) ? dol_print_date($talk->date_start, 'dayhour', 'tzuserrel') : '-'; ?>
                    <span class="digirisk-safetytalk-pill digirisk-safetytalk-pill--<?php print $period; ?>"><?php print $langs->trans($periodLabels[$period]); ?></span>
                </span>
                <span class="digirisk-safetytalk-talk__title"><?php print dol_escape_htmltag($talk->label); ?></span>
                <span class="digirisk-safetytalk-talk__meta">
                    <span class="<?php print ($signatories && $signedCount == count($signatories)) ? 'digirisk-safetytalk-talk__signed' : ''; ?>"><i class="fas fa-signature"></i> <?php print $langs->trans('SafetyTalkSignedCount', $signedCount, count($signatories)); ?></span>
                    <span><i class="fas fa-photo-video"></i> <?php print $fileCount; ?></span>
                </span>
            </span>
            <i class="fas fa-chevron-right digirisk-safetytalk-talk__chevron"></i>
        </a>
        <?php } ?>
    </div>
    <?php } ?>

</div>
