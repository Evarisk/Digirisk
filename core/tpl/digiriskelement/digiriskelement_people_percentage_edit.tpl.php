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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/tpl/digiriskelement/digiriskelement_people_percentage_edit.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Row of the create and edit forms of a GP/UT where the share of the company workforce
 *          present in the element is entered.
 *
 * Requires the following variables to be defined by the calling context:
 * - Form       $form
 * - Translate  $langs
 * - string     $peoplePercentage   Value of the input
 * - int        $nbEmployees        Workforce of the company
 * - string     $workforceConfigUrl Configuration page of the workforce, empty when the user cannot open it
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}
?>

<tr>
    <td><label for="people_percentage"><?php echo $form->textwithpicto($langs->trans('PeoplePercentage'), $langs->trans('PeoplePercentageHelp')); ?></label></td>
    <td>
        <input type="number" class="maxwidth75" id="people_percentage" name="people_percentage" min="0" max="100" step="any" value="<?php echo dol_escape_htmltag($peoplePercentage); ?>"> %
        <span class="opacitymedium">
            <?php echo $nbEmployees > 0 ? $langs->trans('PeoplePercentageOfWorkforce', $nbEmployees) : $langs->trans('PeoplePercentageNoWorkforce'); ?>
        </span>
        <?php if (!empty($workforceConfigUrl)) : ?>
            <a href="<?php echo $workforceConfigUrl; ?>" class="paddingleft" title="<?php echo dol_escape_htmltag($langs->trans('NbPeopleInCompany')); ?>"><i class="fas fa-cog"></i></a>
        <?php endif; ?>
    </td>
</tr>
