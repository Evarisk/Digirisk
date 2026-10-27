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
 * \file    core/tpl/digiriskdocuments/digiriskdolibarr_preventionplan_document_content.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Contenu du PDF du plan de prevention, sur sa page de configuration : une ligne par partie
 *          du document, avec le texte qu'elle imprime et les images ajoutees a sa fin.
 *          Expects: $langs, $form, $permissiontowrite, $documentParts (partie => ['title',
 *          'content' (text|emergency|imagesonly|plan), 'images', et pour un texte 'field', 'value']),
 *          $emergencyNumbers (ressource => ['label', 'field', 'id', 'phone']).
 */

global $langs;
?>
<div class="digirisk-document-content" id="document-content">
    <?php print load_fiche_titre($langs->trans('PreventionPlanDocumentContent'), '', ''); ?>
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" name="document_content_form">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="setDocumentContent">
        <table class="noborder centpercent editmode digirisk-document-content__table">
            <tr class="liste_titre">
                <td class="digirisk-document-content__part-head"><?php print $langs->trans('PreventionPlanDocumentPart'); ?></td>
                <td><?php print $langs->trans('PreventionPlanDocumentPartText'); ?></td>
                <td class="digirisk-document-content__images-head"><?php print $langs->trans('PreventionPlanDocumentPartImages'); ?></td>
            </tr>
            <?php foreach ($documentParts as $documentPart) : ?>
                <tr class="oddeven">
                    <td class="digirisk-document-content__part">
                        <?php if ($documentPart['content'] == 'text') : ?>
                            <label for="<?php print $documentPart['field']; ?>"><?php print $documentPart['title']; ?></label>
                        <?php else : ?>
                            <?php print $documentPart['title']; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($documentPart['content'] == 'text') : ?>
                            <?php
                            $doleditor = new DolEditor($documentPart['field'], $documentPart['value'], '', 200, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '100%');
                            $doleditor->Create();
                            ?>
                        <?php elseif ($documentPart['content'] == 'emergency') : ?>
                            <div class="digirisk-document-content__emergency">
                                <?php foreach ($emergencyNumbers as $emergencyNumber) : ?>
                                    <label for="<?php print $emergencyNumber['field']; ?>"><?php print $langs->trans($emergencyNumber['label']); ?></label>
                                    <div class="digirisk-document-content__emergency-value">
                                        <?php print $form->select_company($emergencyNumber['id'] ?: '', $emergencyNumber['field'], '', 'SelectThirdParty', 1, 0, [], 0, 'minwidth200'); ?>
                                        <span class="digirisk-document-content__emergency-phone opacitymedium"><?php print dol_strlen($emergencyNumber['phone']) ? dol_escape_htmltag($emergencyNumber['phone']) : ($emergencyNumber['id'] > 0 ? $langs->trans('PreventionPlanEmergencyNoPhone') : ''); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($documentPart['content'] == 'imagesonly') : ?>
                            <span class="opacitymedium"><?php print $langs->trans('PreventionPlanDocumentPartImagesOnly'); ?></span>
                        <?php else : ?>
                            <span class="opacitymedium"><?php print $langs->trans('PreventionPlanDocumentPartFromPlan'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php print $documentPart['images']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="center paddingtop">
            <input type="submit" class="button button-save" name="save" value="<?php print dol_escape_htmltag($langs->trans('Save')); ?>"<?php print $permissiontowrite ? '' : ' disabled'; ?>>
        </div>
    </form>
</div>
