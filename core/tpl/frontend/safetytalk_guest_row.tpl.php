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
 * \file    core/tpl/frontend/safetytalk_guest_row.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   One person in the form of a cycle of safety talks: firstname, lastname, email to receive the link
 *          to sign. Also the template the "add a person" button copies. The row of a Dolibarr user keeps the
 *          id of the account and shows its name and email as they are in Dolibarr.
 *          Expects: $langs, $guest (firstname, lastname, email, user_id).
 */

global $langs;

$isUserRow = !empty($guest['user_id']);
$readonly  = $isUserRow ? ' readonly' : '';
?>
<div class="digirisk-safetytalk-guest<?php print $isUserRow ? ' digirisk-safetytalk-guest--user' : ''; ?>">
    <input type="hidden" name="guest_user[]" value="<?php print (int) ($guest['user_id'] ?? 0); ?>">
    <input type="text" name="guest_firstname[]" class="noborderfocus" value="<?php print dol_escape_htmltag($guest['firstname']); ?>" placeholder="<?php print dol_escape_htmltag($langs->trans('Firstname')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Firstname')); ?>"<?php print $readonly; ?>>
    <input type="text" name="guest_lastname[]" class="noborderfocus" value="<?php print dol_escape_htmltag($guest['lastname']); ?>" placeholder="<?php print dol_escape_htmltag($langs->trans('Lastname')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Lastname')); ?>"<?php print $readonly; ?>>
    <input type="email" name="guest_email[]" value="<?php print dol_escape_htmltag($guest['email']); ?>" placeholder="<?php print dol_escape_htmltag($langs->trans('SafetyTalkGuestEmail')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('Email')); ?>"<?php print $isUserRow ? ' class="noborderfocus"' . $readonly : ' class="noborderfocus digirisk-safetytalk-email"'; ?>>
    <button type="button" class="digirisk-safetytalk-guest-remove" aria-label="<?php print dol_escape_htmltag($langs->trans('Delete')); ?>"><i class="fas fa-times"></i></button>
    <?php if (!$isUserRow) { ?>
    <!-- Shown by the script while the email typed is not one the server takes -->
    <span class="digirisk-safetytalk-guest__hint hidden"><?php print $langs->trans('SafetyTalkEmailInvalid'); ?></span>
    <?php } ?>
</div>
