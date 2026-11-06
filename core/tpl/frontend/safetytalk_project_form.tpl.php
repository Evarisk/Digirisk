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
 * \file    core/tpl/frontend/safetytalk_project_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Creation and edition form of a cycle of safety talks: its name and the people assigned to it.
 *          Expects: $form, $langs, $object (cycle, empty for a new one), $prefill (title, description, participants).
 */

global $langs;

$isEdit = ($object->id > 0);

$topbarBackUrl   = $isEdit ? $_SERVER['PHP_SELF'] . '?id=' . $object->id : dol_buildpath('/digiriskdolibarr/view/frontend/pwa_safetytalk_list.php', 1);
$topbarBackLabel = $isEdit ? $object->title : $langs->transnoentities('PwaNavSafetyTalks');
$topbarActions   = [];
?>
<div class="pwa-container digirisk-mobile digirisk-safetytalk">
    <?php require __DIR__ . '/safetytalk_topbar.tpl.php'; ?>

    <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-mobile-form digirisk-safetytalk-form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($isEdit) { ?><input type="hidden" name="id" value="<?php print (int) $object->id; ?>"><?php } ?>

        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title">
                <i class="fas fa-comments"></i> <?php print $langs->trans($isEdit ? 'SafetyTalkEditCycle' : 'SafetyTalkNewCycle'); ?>
                <?php if ($isEdit) { ?><span class="digirisk-safetytalk-ref"><?php print dol_escape_htmltag($object->ref); ?></span><?php } ?>
            </div>

            <div class="digirisk-mobile-field">
                <label for="safetytalk-cycle-title"><?php print $langs->trans('SafetyTalkCycleName'); ?> <span class="digirisk-mobile-required">*</span></label>
                <input type="text" id="safetytalk-cycle-title" name="title" required maxlength="255"
                       placeholder="<?php print dol_escape_htmltag($langs->transnoentities('SafetyTalkCycleNamePlaceholder', dol_print_date(dol_now(), '%Y'))); ?>"
                       value="<?php print dol_escape_htmltag($prefill['title']); ?>">
            </div>

            <!-- The people of the cycle, one row each, added or taken out on the spot. A row of a Dolibarr user
                 (cycles made before) keeps its link to the account, its name and email come from it -->
            <div class="digirisk-mobile-field digirisk-safetytalk-guests">
                <label><?php print $langs->trans('SafetyTalkParticipants'); ?></label>
                <div class="digirisk-safetytalk-guests__list">
                    <?php foreach ($prefill['people'] as $guest) {
                        require __DIR__ . '/safetytalk_guest_row.tpl.php';
                    } ?>
                </div>
                <template class="digirisk-safetytalk-guest-template">
                    <?php $guest = ['firstname' => '', 'lastname' => '', 'email' => '', 'user_id' => 0];
                    require __DIR__ . '/safetytalk_guest_row.tpl.php'; ?>
                </template>
                <button type="button" class="digirisk-safetytalk-button digirisk-safetytalk-guest-add"><i class="fas fa-user-plus"></i> <?php print $langs->trans('SafetyTalkGuestAdd'); ?></button>
                <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkParticipantsHelp'); ?></div>
            </div>

            <div class="digirisk-mobile-field">
                <label for="safetytalk-cycle-description"><?php print $langs->trans('SafetyTalkCycleDescription'); ?></label>
                <textarea id="safetytalk-cycle-description" name="description" rows="3"><?php print dol_escape_htmltag(dol_string_nohtmltag($prefill['description'], 0)); ?></textarea>
            </div>
        </div>

        <button type="submit" class="digirisk-mobile-submit digirisk-safetytalk-submit">
            <i class="fas fa-save"></i> <?php print $langs->trans('SafetyTalkCycleSave'); ?>
        </button>
    </form>
</div>
