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
 * \file    core/tpl/frontend/safetytalk_talk_success.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Screen of a safety talk once diffused, on the success screen shared with the prevention plan:
 *          its progress, the QR code of its spread, then the people of the spread and its content.
 *          Expects: $db, $langs, $object (talk), $project (cycle), $cycleUrl, $permissiontoadd, $maxUploadKb,
 *          $mediaContext, $mediaPrefix.
 */

global $db, $langs;

$signatories = digiriskSafetyTalkGetSignatories($db, (int) $object->id);
$signedCount = count(array_filter($signatories, 'digiriskSafetyTalkHasSigned'));
$period      = digiriskSafetyTalkGetPeriod($object);
$theme       = digiriskSafetyTalkGetTheme((string) ($object->array_options['options_' . DIGIRISKDOLIBARR_SAFETYTALK_THEME_FIELD] ?? ''));
$documents   = digiriskSafetyTalkGetDocuments($object);

$lastSignatureDate = 0;
foreach ($signatories as $signatory) {
    if (digiriskSafetyTalkHasSigned($signatory)) {
        $lastSignatureDate = max($lastSignatureDate, (int) $signatory->signature_date);
    }
}
$allSigned = !empty($signatories) && $signedCount == count($signatories);

// Progress strip: the same steps as the form, now with what really happened
$workflowIcons = digiriskMobileWorkflowIcons();
$steps         = [
    ['title' => $langs->trans('SafetyTalkStepCreated'), 'status' => $langs->transnoentities('MobileStepDone'), 'date' => dol_print_date($object->date_c, 'day', 'tzuserrel'), 'done' => true, 'viewBox' => $workflowIcons['created']['viewBox'], 'svg' => $workflowIcons['created']['svg']],
    ['title' => $langs->trans('SafetyTalkStepSpread'), 'status' => $langs->transnoentities('SafetyTalkStepPeople', count($signatories)), 'date' => '', 'done' => !empty($signatories), 'viewBox' => $workflowIcons['user']['viewBox'], 'svg' => $workflowIcons['user']['svg']],
    ['title' => $langs->trans('SafetyTalkStepHeld'), 'status' => $langs->transnoentities($period == 'done' ? 'MobileStepDone' : ($period == 'today' ? 'MobileStepInProgress' : 'MobileStepTodo')), 'date' => !empty($object->date_start) ? dol_print_date($object->date_start, 'day', 'tzuserrel') : '', 'done' => ($period == 'done'), 'current' => ($period == 'today'), 'viewBox' => $workflowIcons['firewatch']['viewBox'], 'svg' => $workflowIcons['firewatch']['svg']],
    ['title' => $langs->trans('SafetyTalkStepSigned'), 'status' => $langs->transnoentities('SafetyTalkSignedCount', $signedCount, count($signatories)), 'date' => $allSigned ? dol_print_date($lastSignatureDate, 'day', 'tzuserrel') : '', 'done' => $allSigned, 'viewBox' => $workflowIcons['lock']['viewBox'], 'svg' => $workflowIcons['lock']['svg']],
];

$successSaved      = GETPOST('saved', 'aZ09');
$successBannerText = '';
if ($successSaved === 'created') {
    $successBannerText = dol_escape_htmltag($langs->transnoentities('SafetyTalkTalkCreated', $object->ref, count($signatories)));
} elseif ($successSaved === 'updated') {
    $successBannerText = dol_escape_htmltag($langs->transnoentities('SafetyTalkTalkUpdated', $object->ref));
}

$successTitle   = '';
$successRef     = $object->ref;
$successRefHtml = '';
$successLabel   = $object->label;

// When and on what: the two facts people look for before joining the talk
ob_start(); ?>
<div class="digirisk-safetytalk-facts">
    <div class="digirisk-safetytalk-facts__item">
        <span class="digirisk-safetytalk-facts__icon">
            <?php if ($theme !== null) { ?>
            <img src="<?php print dol_escape_htmltag($theme['src']); ?>" alt="">
            <?php } else { ?>
            <i class="fas fa-comments"></i>
            <?php } ?>
        </span>
        <span>
            <span class="digirisk-safetytalk-facts__label"><?php print $langs->trans('SafetyTalkTheme'); ?></span>
            <span class="digirisk-safetytalk-facts__value"><?php print dol_escape_htmltag($theme !== null ? $theme['name'] : $langs->transnoentities('SafetyTalkThemeNone')); ?></span>
        </span>
    </div>
    <div class="digirisk-safetytalk-facts__item">
        <span class="digirisk-safetytalk-facts__icon"><i class="fas fa-calendar-alt"></i></span>
        <span>
            <span class="digirisk-safetytalk-facts__label"><?php print $langs->trans('SafetyTalkDate'); ?></span>
            <span class="digirisk-safetytalk-facts__value digirisk-safetytalk-facts__value--accent"><?php print !empty($object->date_start) ? dol_print_date($object->date_start, 'dayhour', 'tzuserrel') : '-'; ?></span>
        </span>
    </div>
</div>
<?php if (dol_strlen(dol_string_nohtmltag((string) $object->description))) { ?>
<div class="digirisk-safetytalk-cycle__description"><?php print dol_escape_htmltag(dol_string_nohtmltag((string) $object->description, 0), 0, 1); ?></div>
<?php }
$successExtraInfoHtml = digiriskMobileRenderWorkflow($steps, dol_escape_htmltag($project->ref)) . ob_get_clean();

$successCustomFactsHtml = '';

// Spread of the talk: QR code to hand round on site, and its link
$successShareUrl = digiriskSafetyTalkGetSpreadUrl((int) $object->id);

$successExtraBlockFile = [__DIR__ . '/safetytalk_talk_signatories.tpl.php', __DIR__ . '/safetytalk_talk_documents.tpl.php'];

// The way back and the actions sit on top of the screen, in sight: none at the bottom
$successTopBlockFile = __DIR__ . '/safetytalk_topbar.tpl.php';
$successViewUrl      = '';
$successAgainUrl     = '';

$topbarBackUrl   = $cycleUrl;
$topbarBackLabel = $project->title;
$topbarActions   = [];
if ($permissiontoadd) {
    $topbarActions[] = ['label' => $langs->transnoentities('Modify'), 'icon' => 'fa-pen', 'url' => $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit'];
    // Removal while nobody has signed: the signatures are the proof of the attendance
    if ($signedCount == 0) {
        $topbarActions[] = ['label' => $langs->transnoentities('SafetyTalkDeleteTalk'), 'icon' => 'fa-trash-alt', 'post' => ['action' => 'delete', 'id' => $object->id], 'confirm' => $langs->transnoentities('SafetyTalkDeleteTalkConfirm', $object->ref)];
    }
    $topbarActions[] = ['label' => $langs->transnoentities('SafetyTalkNewTalk'), 'icon' => 'fa-plus', 'url' => $_SERVER['PHP_SELF'] . '?project_id=' . $project->id . '&action=create', 'primary' => true];
}

require __DIR__ . '/digiriskdolibarr_mobile_success.tpl.php';
