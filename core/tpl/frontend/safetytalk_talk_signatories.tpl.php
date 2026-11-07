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
 * \file    core/tpl/frontend/safetytalk_talk_signatories.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   People of the spread of a safety talk on its screen: who has signed and when, the page of each one
 *          to hand over, and the email that sends it to the ones still to sign.
 *          Expects: $langs, $object (talk), $signatories, $signedCount, $permissiontoadd.
 */

global $langs;

$pendingCount = count($signatories) - $signedCount;
?>
<div class="digirisk-mobile-card digirisk-safetytalk-signatories" id="safetytalk-signatories">
    <div class="digirisk-mobile-extsign__title digirisk-mobile-extsign__title--split">
        <span><i class="fas fa-users"></i> <?php print $langs->trans('SafetyTalkSignatoriesTitle'); ?></span>
        <span class="digirisk-mobile-extsign__signed<?php print $pendingCount > 0 ? ' digirisk-mobile-extsign__signed--not' : ''; ?>"><?php print $langs->trans('SafetyTalkSignedCount', $signedCount, count($signatories)); ?></span>
    </div>

    <?php if (empty($signatories)) { ?>
    <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkNoSignatory'); ?></div>
    <?php } else { ?>
    <ul class="digirisk-safetytalk-signatories__list">
        <?php foreach ($signatories as $signatory) {
            $hasSigned = digiriskSafetyTalkHasSigned($signatory); ?>
        <li class="digirisk-safetytalk-signatories__item">
            <span class="digirisk-safetytalk-signatories__state digirisk-safetytalk-signatories__state--<?php print $hasSigned ? 'signed' : 'pending'; ?>">
                <i class="fas <?php print $hasSigned ? 'fa-check' : 'fa-hourglass-half'; ?>"></i>
            </span>
            <span class="digirisk-safetytalk-signatories__who">
                <span class="digirisk-safetytalk-signatories__name"><?php print dol_escape_htmltag(doliletter_spread_get_signatory_name($signatory)); ?></span>
                <span class="digirisk-safetytalk-signatories__status">
                    <?php print $hasSigned
                        ? $langs->trans('SafetyTalkSigned', dol_print_date($signatory->signature_date, 'dayhour', 'tzuserrel'))
                        : $langs->trans('SafetyTalkNotSigned') . (!empty($signatory->last_email_sent_date) ? ' - ' . $langs->trans('SafetyTalkMailSentOn', dol_print_date($signatory->last_email_sent_date, 'dayhour', 'tzuserrel')) : ''); ?>
                </span>
            </span>
            <?php if (!$hasSigned) {
                $signatoryName = doliletter_spread_get_signatory_name($signatory); ?>
            <a class="digirisk-safetytalk-signatories__open" href="<?php print dol_escape_htmltag(digiriskSafetyTalkGetSpreadUrl((int) $object->id, $signatory)); ?>" target="_blank" title="<?php print dol_escape_htmltag($langs->trans('SafetyTalkOpenOwnPage')); ?>"><i class="fas fa-pen-nib"></i></a>
                <?php if ($permissiontoadd) { ?>
            <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-confirm" data-confirm="<?php print dol_escape_htmltag($langs->transnoentities('SafetyTalkRemovePersonConfirm', $signatoryName)); ?>">
                <input type="hidden" name="token" value="<?php print newToken(); ?>">
                <input type="hidden" name="action" value="remove_person">
                <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
                <input type="hidden" name="signatory_id" value="<?php print (int) $signatory->id; ?>">
                <input type="hidden" name="person_name" value="<?php print dol_escape_htmltag($signatoryName); ?>">
                <button type="submit" class="digirisk-safetytalk-signatories__remove" title="<?php print dol_escape_htmltag($langs->trans('SafetyTalkRemovePerson')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('SafetyTalkRemovePerson')); ?>"><i class="fas fa-user-minus"></i></button>
            </form>
                <?php } ?>
            <?php } ?>
        </li>
        <?php } ?>
    </ul>
    <?php } ?>

    <?php if ($permissiontoadd) { ?>
    <!-- Someone present that day, without Dolibarr account: added to this talk alone -->
    <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-person-form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="add_person">
        <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
        <div class="digirisk-safetytalk-person-form__title"><i class="fas fa-user-plus"></i> <?php print $langs->trans('SafetyTalkAddPersonTitle'); ?></div>
        <div class="digirisk-safetytalk-guest">
            <input type="text" name="person_firstname" class="noborderfocus" placeholder="<?php print dol_escape_htmltag($langs->trans('Firstname')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Firstname')); ?>">
            <input type="text" name="person_lastname" class="noborderfocus" placeholder="<?php print dol_escape_htmltag($langs->trans('Lastname')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Lastname')); ?>">
            <input type="email" name="person_email" class="noborderfocus digirisk-safetytalk-email" placeholder="<?php print dol_escape_htmltag($langs->trans('SafetyTalkGuestEmail')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Email')); ?>">
            <button type="submit" class="digirisk-safetytalk-guest-submit" aria-label="<?php print dol_escape_htmltag($langs->trans('Add')); ?>"><i class="fas fa-plus"></i></button>
            <span class="digirisk-safetytalk-guest__hint hidden"><?php print $langs->trans('SafetyTalkEmailInvalid'); ?></span>
        </div>
        <div class="digirisk-mobile-help"><?php print $langs->trans('SafetyTalkAddPersonHelp'); ?></div>
    </form>
    <?php } ?>

    <?php if ($permissiontoadd && $pendingCount > 0) { ?>
    <form method="POST" action="<?php print dol_escape_htmltag($_SERVER['PHP_SELF']); ?>" class="digirisk-safetytalk-signatories__send">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="send_links">
        <input type="hidden" name="id" value="<?php print (int) $object->id; ?>">
        <button type="submit" class="digirisk-safetytalk-button digirisk-safetytalk-button--wide"><i class="fas fa-envelope"></i> <?php print $langs->trans('SafetyTalkSendLinks'); ?></button>
    </form>
    <?php } ?>
</div>
