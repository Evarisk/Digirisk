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
 * \file    core/tpl/frontend/safetytalk_talk_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Creation and edition form of a safety talk. The theme is picked in tiles, as the category of the
 *          public ticket form, and gives its title to the talk; the date is free, each talk has its own.
 *          Photos, videos and files go through the media block of Saturne, sent as soon as picked.
 *          Expects: $db, $langs, $object (talk, empty for a new one), $project (cycle), $prefill, $maxUploadKb,
 *          $mediaContext, $mediaPrefix.
 */

global $db, $langs;

$isEdit = ($object->id > 0);
// Everyone the talk is diffused to: the users of the cycle and its people without account
$people = digiriskSafetyTalkGetPeopleNames($db, $project, $langs);

$topbarBackUrl   = $isEdit ? $_SERVER['PHP_SELF'] . '?id=' . $object->id : $cycleUrl;
$topbarBackLabel = $isEdit ? $object->label : $project->title;
$topbarActions   = [];

$workflowIcons = digiriskMobileWorkflowIcons();
$stepsCreation = [
    ['title' => $langs->trans('SafetyTalkStepCreated'), 'status' => $langs->transnoentities($isEdit ? 'MobileStepDone' : 'MobileStepInProgress'), 'date' => dol_print_date($isEdit ? $object->date_c : dol_now(), 'day', 'tzuserrel'), 'done' => $isEdit, 'current' => !$isEdit, 'viewBox' => $workflowIcons['created']['viewBox'], 'svg' => $workflowIcons['created']['svg']],
    ['title' => $langs->trans('SafetyTalkStepSpread'), 'status' => $langs->transnoentities('SafetyTalkStepPeople', count($people)), 'date' => '', 'done' => $isEdit, 'current' => false, 'viewBox' => $workflowIcons['user']['viewBox'], 'svg' => $workflowIcons['user']['svg']],
    ['title' => $langs->trans('SafetyTalkStepHeld'), 'status' => $langs->transnoentities('MobileStepTodo'), 'date' => '', 'done' => false, 'current' => false, 'viewBox' => $workflowIcons['firewatch']['viewBox'], 'svg' => $workflowIcons['firewatch']['svg']],
    ['title' => $langs->trans('SafetyTalkStepSigned'), 'status' => $langs->transnoentities('MobileStepTodo'), 'date' => '', 'done' => false, 'current' => false, 'viewBox' => $workflowIcons['lock']['viewBox'], 'svg' => $workflowIcons['lock']['svg']],
];
?>
<div class="pwa-container digirisk-mobile digirisk-safetytalk">
    <?php require __DIR__ . '/safetytalk_topbar.tpl.php'; ?>

    <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-mobile-form digirisk-safetytalk-form digirisk-safetytalk-talk-form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($isEdit) { ?>
        <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
        <?php } else { ?>
        <input type="hidden" name="project_id" value="<?php print (int) $project->id; ?>">
        <?php } ?>
        <input type="hidden" name="theme" class="digirisk-safetytalk-theme-input" value="<?php print dol_escape_htmltag($prefill['theme']); ?>">

        <div class="digirisk-mobile-form-errors hidden"></div>

        <div class="digirisk-mobile-card">
            <?php print digiriskMobileRenderWorkflow($stepsCreation, $isEdit ? dol_escape_htmltag($object->ref) : '', true); ?>
            <div class="digirisk-safetytalk-form__cycle"><i class="fas fa-comments"></i> <?php print dol_escape_htmltag($project->ref . ' - ' . $project->title); ?></div>
        </div>

        <!-- Theme of the talk: a Digirisk danger category, picked in tiles like the category of a public ticket -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-th-large"></i> <?php print $langs->trans('SafetyTalkChooseTheme'); ?></div>
            <div class="digirisk-safetytalk-themes">
                <button type="button" class="digirisk-safetytalk-theme<?php print ($prefill['theme'] === '') ? ' active' : ''; ?>" data-theme="" data-name="">
                    <span class="digirisk-safetytalk-theme__picto"><i class="fas fa-comments"></i></span>
                    <span class="digirisk-safetytalk-theme__label"><?php print $langs->trans('SafetyTalkThemeNone'); ?></span>
                </button>
                <?php foreach (Risk::getDangerCategories() as $dangerCategory) { ?>
                <button type="button" class="digirisk-safetytalk-theme<?php print ($prefill['theme'] !== '' && (int) $prefill['theme'] === (int) $dangerCategory['position']) ? ' active' : ''; ?>" data-theme="<?php print (int) $dangerCategory['position']; ?>" data-name="<?php print dol_escape_htmltag($dangerCategory['name']); ?>">
                    <span class="digirisk-safetytalk-theme__picto"><img src="<?php print dol_escape_htmltag(dol_buildpath('/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png', 1)); ?>" alt="" loading="lazy"></span>
                    <span class="digirisk-safetytalk-theme__label"><?php print dol_escape_htmltag($dangerCategory['name']); ?></span>
                </button>
                <?php } ?>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkThemeHelp'); ?></div>
        </div>

        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-comment-dots"></i> <?php print $langs->trans($isEdit ? 'SafetyTalkEditTalk' : 'SafetyTalkNewTalk'); ?></div>

            <div class="digirisk-mobile-field">
                <label for="safetytalk-label"><?php print $langs->trans('SafetyTalkTitle'); ?> <span class="digirisk-mobile-required">*</span></label>
                <input type="text" id="safetytalk-label" name="label" class="digirisk-safetytalk-label" required maxlength="255" value="<?php print dol_escape_htmltag($prefill['label']); ?>">
            </div>

            <div class="digirisk-mobile-field">
                <label for="safetytalk-date"><?php print $langs->trans('SafetyTalkDate'); ?> <span class="digirisk-mobile-required">*</span></label>
                <input type="datetime-local" id="safetytalk-date" name="date_start" required value="<?php print dol_escape_htmltag($prefill['date_start']); ?>">
                <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkDateHelp'); ?></div>
            </div>

            <div class="digirisk-mobile-field">
                <label for="safetytalk-description"><?php print $langs->trans('SafetyTalkContent'); ?></label>
                <textarea id="safetytalk-description" name="description" rows="4"><?php print dol_escape_htmltag(dol_string_nohtmltag($prefill['description'], 0)); ?></textarea>
            </div>
        </div>

        <!-- Files of the talk, shown in its spread: the media block of Saturne sends each one as soon as picked
             (kept aside until the talk is saved, on the creation form) -->
        <div class="digirisk-mobile-card digirisk-safetytalk-files">
            <div class="digirisk-mobile-card__title"><i class="fas fa-photo-video"></i> <?php print $langs->trans('SafetyTalkFiles'); ?></div>
            <?php require __DIR__ . '/safetytalk_media_block.tpl.php'; ?>

            <div class="digirisk-mobile-row digirisk-safetytalk-files__link">
                <div class="digirisk-mobile-field">
                    <label for="safetytalk-link-url"><?php print $langs->trans('SafetyTalkLinkUrl'); ?></label>
                    <input type="url" id="safetytalk-link-url" name="link_url" placeholder="https://">
                </div>
                <div class="digirisk-mobile-field">
                    <label for="safetytalk-link-label"><?php print $langs->trans('SafetyTalkLinkLabel'); ?></label>
                    <input type="text" id="safetytalk-link-label" name="link_label">
                </div>
            </div>
        </div>

        <div class="digirisk-mobile-card digirisk-safetytalk-form__people">
            <div class="digirisk-mobile-card__title"><i class="fas fa-users"></i> <?php print $langs->trans('SafetyTalkCardParticipants', count($people)); ?></div>
            <?php require __DIR__ . '/safetytalk_people_chips.tpl.php'; ?>
            <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkParticipantsHelp'); ?></div>
        </div>

        <button type="submit" class="digirisk-mobile-submit digirisk-safetytalk-submit">
            <i class="fas fa-paper-plane"></i> <?php print $langs->trans('SafetyTalkTalkSave'); ?>
        </button>
    </form>
</div>
<?php
// Photo editor of the media block: it binds to this modal when the page loads
print saturne_photo_editor_modal();
