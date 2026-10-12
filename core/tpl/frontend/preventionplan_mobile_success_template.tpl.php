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
 * \file    core/tpl/frontend/preventionplan_mobile_success_template.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   « Enregistrer comme trame » sur l'ecran de suivi d'un plan de prevention : son contenu
 *          reutilisable devient le point de depart du prochain plan. Action secondaire, repliee en
 *          un lien sous les boutons ; details/summary la deplie sans JS.
 *          Expects: $langs, $object (fetched prevention plan).
 */

global $langs;
?>
<details class="digirisk-mobile-template-save">
    <summary class="digirisk-mobile-template-save__toggle"><i class="fas fa-copy"></i> <?php print $langs->trans('MobilePPTemplateSave'); ?></summary>
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" class="digirisk-mobile-template-save__form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="saveTemplate">
        <input type="hidden" name="plan_id" value="<?php print (int) $object->id; ?>">
        <div class="digirisk-mobile-template-save__help"><?php print $langs->trans('MobilePPTemplateSaveHelp'); ?></div>
        <div class="digirisk-mobile-template-save__row digirisk-mobile-field">
            <input type="text" name="template_label" maxlength="255" required value="<?php print dol_escape_htmltag($object->label); ?>" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPTemplateName')); ?>" aria-label="<?php print dol_escape_htmltag($langs->trans('MobilePPTemplateName')); ?>">
            <button type="submit" class="wpeo-button button-blue digirisk-mobile-template-save__submit"><?php print $langs->trans('Save'); ?></button>
        </div>
    </form>
</details>
