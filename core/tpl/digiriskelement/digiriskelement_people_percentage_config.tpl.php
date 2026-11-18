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
 * \file    core/tpl/digiriskelement/digiriskelement_people_percentage_config.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Modal of the GP/UT setup where the share of the company workforce present in each
 *          GP/UT is entered for all of them at once.
 *
 * Rendered by admin/config/digiriskelement.php (inherits its scope). Expects:
 * - Translate         $langs
 * - DigiriskElement[] $visibleElements           GP/UT of the current entity, parents before children
 * - array             $postedPeoplePercentages   Values posted by a save that failed, shown back
 * - int               $nbEmployees               Workforce of the company
 * - bool              $permissionToEditElements  The user can change the GP/UT
 * - bool              $openPeoplePercentageModal The modal is shown when the page loads
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print 'Error, template page cannot be called as URL';
    exit;
}

// Elements come parents first, so the depth of the parent is always known
$elementDepths = [];
foreach ($visibleElements as $elementId => $visibleElement) {
    $elementDepths[$elementId] = isset($elementDepths[$visibleElement->fk_parent]) ? $elementDepths[$visibleElement->fk_parent] + 1 : 0;
}
?>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="update_people_percentage">

    <div class="wpeo-modal modal-close-only-with-button<?php echo $openPeoplePercentageModal ? ' modal-active' : ''; ?>" id="people_percentage_modal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $langs->trans('PeoplePercentageByElement'); ?></h2>
                <div class="modal-close"><i class="fas fa-times"></i></div>
            </div>

            <div class="modal-content">
                <table class="noborder centpercent">
                    <tr class="liste_titre">
                        <td><?php echo $langs->trans('PeoplePercentageElement'); ?></td>
                        <td><?php echo $langs->trans('PeoplePercentage'); ?></td>
                        <td><?php echo $langs->trans('PeoplePercentageNbPeopleColumn'); ?></td>
                    </tr>

                    <?php if (empty($visibleElements)) : ?>
                        <tr class="oddeven"><td colspan="3"><span class="opacitymedium"><?php echo $langs->trans('NoRecordFound'); ?></span></td></tr>
                    <?php endif; ?>

                    <?php foreach ($visibleElements as $elementId => $visibleElement) :
                        $depth            = $elementDepths[$elementId];
                        $peoplePercentage = array_key_exists($elementId, $postedPeoplePercentages) ? $postedPeoplePercentages[$elementId] : (string) $visibleElement->people_percentage;
                        $nbPeople         = $visibleElement->getNbPeople($nbEmployees); ?>
                        <tr class="oddeven">
                            <td>
                                <label for="people_percentage_<?php echo $elementId; ?>">
                                    <?php echo str_repeat('&#160;', $depth * 4) . ($depth > 0 ? '&#x21B3; ' : '') . $visibleElement->getNomUrl(1, '', 0, '', -1, 1); ?>
                                </label>
                            </td>
                            <td class="nowraponall">
                                <?php if ($permissionToEditElements) : ?>
                                    <input type="number" class="maxwidth75" id="people_percentage_<?php echo $elementId; ?>" name="people_percentage[<?php echo $elementId; ?>]" min="0" max="100" step="any" value="<?php echo dol_escape_htmltag($peoplePercentage); ?>"> %
                                <?php elseif ($peoplePercentage !== '') : ?>
                                    <?php echo price($peoplePercentage, 0, $langs, 1, 0); ?> %
                                <?php endif; ?>
                            </td>
                            <td class="nowraponall">
                                <?php if ($nbPeople !== null && $nbEmployees > 0) : ?>
                                    <?php echo $langs->trans('PeoplePercentageNbPeople', $nbPeople, $nbEmployees); ?>
                                <?php elseif ($nbPeople !== null) : ?>
                                    <span class="opacitymedium"><?php echo $langs->trans('PeoplePercentageWorkforceMissing'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <?php if ($permissionToEditElements && !empty($visibleElements)) : ?>
                <div class="modal-footer">
                    <input type="submit" class="button" name="save" value="<?php echo $langs->trans('Save'); ?>">
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>
