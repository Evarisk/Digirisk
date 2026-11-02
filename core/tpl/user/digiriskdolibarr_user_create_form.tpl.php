<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file    core/tpl/user/digiriskdolibarr_user_create_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Quick user creation form shown under the user list.
 *          The email is filled from the first name and the last name by js/modules/user.js,
 *          which listens to the fields of .digirisk-users.
 *
 * Rendered by view/digiriskusers.php (inherits its scope). Expects:
 *   $conf, $langs, $form
 *   $backtopage  string  URL to go back to once the user is created
 *   $group       int     Group selected by default (Digirisk readers group)
 */

if (empty($conf) || empty($langs) || empty($form)) {
	print 'Error, missing parameters';
	exit;
}
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="createuser" id="addUser" class="digirisk-user-create digirisk-users">
	<input type="hidden" name="token" value="<?php echo newToken(); ?>">
	<input type="hidden" name="action" value="add">
	<input type="hidden" name="backtopage" value="<?php echo dol_escape_htmltag($backtopage); ?>">
	<?php if (!empty($ldap_sid)) : ?>
		<input type="hidden" name="ldap_sid" value="<?php echo dol_escape_htmltag($ldap_sid); ?>">
	<?php endif; ?>
	<input type="hidden" name="entity" value="<?php echo $conf->entity; ?>">
	<input type="hidden" class="input-domain-mail" name="societyname" value="<?php echo dol_escape_htmltag(preg_replace('/ /', '', getDolGlobalString('MAIN_INFO_SOCIETE_NOM')) . '.fr'); ?>">

	<div class="digirisk-user-create__header">
		<span class="digirisk-user-create__icon"><i class="fas fa-user-plus"></i></span>
		<div>
			<div class="digirisk-user-create__title"><?php echo $langs->trans('NewUser'); ?></div>
			<div class="digirisk-user-create__hint"><?php echo $langs->trans('UserCreateEmailHint'); ?></div>
		</div>
	</div>

	<div class="digirisk-user-create__fields">
		<div class="digirisk-user-create__field">
			<label for="lastname"><?php echo $langs->trans('LastName'); ?> <span class="digirisk-user-create__required">*</span></label>
			<input type="text" id="lastname" name="lastname" value="<?php echo dol_escape_htmltag(GETPOST('lastname')); ?>" autocomplete="off" required>
		</div>
		<div class="digirisk-user-create__field">
			<label for="firstname"><?php echo $langs->trans('FirstName'); ?></label>
			<input type="text" id="firstname" name="firstname" value="<?php echo dol_escape_htmltag(GETPOST('firstname')); ?>" autocomplete="off">
		</div>
		<div class="digirisk-user-create__field digirisk-user-create__field--email">
			<label for="email"><?php echo $langs->trans('Email'); ?> <span class="digirisk-user-create__required">*</span></label>
			<input type="email" id="email" class="email" name="email" value="<?php echo dol_escape_htmltag(GETPOST('email')); ?>" autocomplete="off" required>
		</div>
		<div class="digirisk-user-create__field">
			<label for="job"><?php echo $langs->trans('PostOrFunction'); ?></label>
			<input type="text" id="job" name="job" value="<?php echo dol_escape_htmltag(GETPOST('job')); ?>" autocomplete="off">
		</div>
		<div class="digirisk-user-create__field">
			<label for="groupid"><?php echo $langs->trans('UserGroup'); ?></label>
			<?php echo $form->select_dolgroups($group, 'groupid', 1, '', 0, '', array(), (string) $conf->entity, false, 'widthcentpercent'); ?>
		</div>
		<div class="digirisk-user-create__actions">
			<button type="submit" class="wpeo-button digirisk-user-create__submit" name="createuseraction">
				<i class="fas fa-plus button-icon"></i>
				<span><?php echo $langs->trans('CreateUser'); ?></span>
			</button>
		</div>
	</div>
</form>
