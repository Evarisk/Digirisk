<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    core/tpl/frontend/preventionplan_mobile_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Creation form screen of the mobile prevention plan interface.
 *          Expects: $conf, $form, $langs, $mysoc, $user, $savedSignature, $uploadToken.
 */

global $conf, $langs, $mysoc, $user;

$hasSignature     = digiriskIsValidSignature($savedSignature);
$sirenLookupUrl   = dol_buildpath('/custom/digiriskdolibarr/core/ajax/mobile_siren_lookup.php', 1);
$saveSignatureUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/save_user_signature.php', 1);

// Danger categories (risks) and signalisation pictos (protections), indexed by position:
// used both to render the already selected items and to fill the picker modals
$dangerCategories = Risk::getDangerCategories();
$dangerMap        = [];
foreach ($dangerCategories as $dangerCategoryItem) {
    $dangerMap[$dangerCategoryItem['position']] = $dangerCategoryItem;
}

$signalisationFile       = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
$signalisationCategories = file_exists($signalisationFile) ? (json_decode(file_get_contents($signalisationFile), true) ?: []) : [];
$protectionMap           = [];
foreach ($signalisationCategories as $signalisationItem) {
    $protectionMap[$signalisationItem['position']] = $signalisationItem;
}
?>
<div class="pwa-container digirisk-mobile">
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" class="digirisk-mobile-form digirisk-mobile-form--preventionplan"
          data-has-signature="<?php print ($hasSignature || !empty($isEdit)) ? '1' : '0'; ?>"
          data-siren-lookup-url="<?php print dol_escape_htmltag($sirenLookupUrl); ?>"
          data-save-signature-url="<?php print dol_escape_htmltag($saveSignatureUrl); ?>"
          data-empty-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEmptySignature')); ?>"
          data-need-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorNoSignature')); ?>"
          data-invalid-siren-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidSiren')); ?>"
          data-company-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyFound')); ?>"
          data-company-not-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyNotFound')); ?>"
          data-end-before-start-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndBeforeStart')); ?>"
          data-max-span-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorMaxOneYear')); ?>"
          data-max-span-days="365"
          data-dates-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorDatesRequired')); ?>"
          data-date-start-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorStartRequired')); ?>"
          data-date-end-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndRequired')); ?>"
          data-risk-comment-label="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>"
          data-mandatory-label="<?php print dol_escape_htmltag($langs->trans('MobilePPMandatory')); ?>"
          data-risk-start-index="<?php print count($prefill['risks']); ?>"
          data-delete-risk-label="<?php print dol_escape_htmltag($langs->trans('MobilePPDeleteRiskConfirm')); ?>"
          data-cert-start-index="<?php print count($prefill['certifications']); ?>">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="add_mobile">
        <?php if (!empty($isEdit)) { ?><input type="hidden" name="id" value="<?php print (int) $object->id; ?>"><?php } ?>
        <input type="hidden" name="ext_society_id" class="digirisk-mobile-ext-society-id" value="<?php print (int) $prefill["ext_society_id"]; ?>">
        <input type="hidden" name="resp_contact_id" class="digirisk-mobile-resp-contact-id" value="<?php print (int) $prefill["resp_contact_id"]; ?>">

        <div class="digirisk-mobile-form-errors hidden"></div>

        <!-- Card 1: interior company, auto-signed by the connected responsible -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-building"></i> <?php print $langs->trans('MobilePPInteriorCompany'); ?></div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Company'); ?></label>
                    <div class="digirisk-mobile-static"><?php print dol_escape_htmltag($mysoc->name); ?></div>
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('MobilePPResponsibleAutoSign'); ?></label>
                    <div class="digirisk-mobile-static"><?php print dol_escape_htmltag($user->getFullName($langs)); ?></div>
                </div>
            </div>

            <div class="digirisk-mobile-signature">
                <div class="digirisk-mobile-signature-saved <?php print $hasSignature ? '' : 'hidden'; ?>">
                    <span class="digirisk-mobile-signature-badge"><i class="fas fa-check-circle"></i> <?php print $langs->trans('MobilePPSignatureSaved'); ?></span>
                    <img class="digirisk-mobile-signature-preview" src="<?php print $hasSignature ? dol_escape_htmltag($savedSignature) : ''; ?>" alt="">
                </div>
                <div class="digirisk-mobile-signature-draw <?php print $hasSignature ? 'hidden' : ''; ?>">
                    <div class="digirisk-mobile-signature-hint"><?php print $langs->trans('MobilePPDrawSignatureHint'); ?></div>
                    <canvas class="digirisk-mobile-signature-canvas"></canvas>
                    <div class="digirisk-mobile-signature-actions">
                        <button type="button" class="digirisk-mobile-signature-clear wpeo-button button-grey"><?php print $langs->trans('MobilePPClearSignature'); ?></button>
                        <button type="button" class="digirisk-mobile-signature-save wpeo-button button-blue"><?php print $langs->trans('MobilePPSaveSignature'); ?></button>
                    </div>
                </div>
                <div class="digirisk-mobile-signature-status"></div>
            </div>
        </div>

        <!-- Card 2: exterior company, asked to sign by email. Three ways to fill it in: pick it in
             the third party list, resolve it by SIREN, or type everything for a company to create -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-industry"></i> <?php print $langs->trans('MobilePPExteriorCompany'); ?></div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('MobilePPChooseExistingCompany'); ?></label>
                <?php print $form->select_company($prefill['ext_society_id'], 'ext_society_picker', '', '&nbsp;', 0, 0, [], 0, 'digirisk-mobile-society-select maxwidth500'); ?>
            </div>
            <div class="digirisk-mobile-separator"><span><?php print $langs->trans('MobilePPOrFillManually'); ?></span></div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('MobileSirenOrSiret'); ?></label>
                <div class="digirisk-mobile-siren-row">
                    <input type="text" name="siren" class="digirisk-mobile-siren-input" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="<?php print dol_escape_htmltag($langs->trans('MobileSirenOrSiretPlaceholder')); ?>" value="<?php print dol_escape_htmltag($prefill["siren"]); ?>">
                    <button type="button" class="digirisk-mobile-siren-search wpeo-button button-blue" aria-label="<?php print dol_escape_htmltag($langs->trans('Search')); ?>"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="digirisk-mobile-siren-result"></div>

            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('CompanyName'); ?> *</label>
                    <input type="text" name="ext_society_name" class="digirisk-mobile-ext-society-name" value="<?php print dol_escape_htmltag($prefill["ext_society_name"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Email'); ?></label>
                    <input type="email" name="ext_society_email" class="digirisk-mobile-ext-society-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_email"]); ?>">
                </div>
            </div>

            <div class="digirisk-mobile-subtitle"><?php print $langs->trans('MobilePPResponsibleToSign'); ?></div>

            <div class="digirisk-mobile-field digirisk-mobile-contact-picker hidden">
                <label><?php print $langs->trans('MobilePPChooseExistingContact'); ?></label>
                <select class="digirisk-mobile-contact-select">
                    <option value=""><?php print $langs->trans('MobilePPNewContact'); ?></option>
                </select>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Lastname'); ?> *</label>
                    <input type="text" name="resp_lastname" class="digirisk-mobile-resp-lastname" value="<?php print dol_escape_htmltag($prefill["resp_lastname"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Firstname'); ?></label>
                    <input type="text" name="resp_firstname" class="digirisk-mobile-resp-firstname" value="<?php print dol_escape_htmltag($prefill["resp_firstname"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Email'); ?> *</label>
                    <input type="email" name="resp_email" class="digirisk-mobile-resp-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_email"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Phone'); ?></label>
                    <input type="tel" name="resp_phone" class="digirisk-mobile-resp-phone" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_phone"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobilePPEmailForSignatureHelp'); ?></div>
        </div>

        <!-- Card 3: intervention period, capped to one year -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-calendar-alt"></i> <?php print $langs->trans('MobilePPPeriod'); ?></div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateStart'); ?> *</label>
                    <input type="date" name="date_start" class="digirisk-mobile-date-start" value="<?php print dol_escape_htmltag($prefill["date_start"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateEnd'); ?> *</label>
                    <input type="date" name="date_end" class="digirisk-mobile-date-end" value="<?php print dol_escape_htmltag($prefill["date_end"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobilePPMaxOneYearHint'); ?></div>
            <div class="digirisk-mobile-date-error hidden"></div>
        </div>

        <!-- Prior formalities: CSSCT intervention then joint prior inspection, in that order,
             like the classic prevention plan card (native cssct_intervention / prior_visit_* fields) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-clipboard-check"></i> <?php print $langs->trans('MobilePPPriorFormalities'); ?></div>
            <label class="digirisk-mobile-check">
                <input type="checkbox" name="cssct_intervention" value="1"<?php print !empty($prefill['cssct_intervention']) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('CSSCTIntervention'); ?></span>
            </label>
            <label class="digirisk-mobile-check">
                <input type="checkbox" name="prior_visit_bool" class="digirisk-mobile-prior-visit-toggle" value="1"<?php print !empty($prefill['prior_visit_bool']) ? ' checked' : ''; ?>>
                <span><?php print $langs->trans('MobilePPPriorVisitDone'); ?></span>
            </label>
            <div class="digirisk-mobile-prior-visit-detail <?php print !empty($prefill['prior_visit_bool']) ? '' : 'hidden'; ?>">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('PriorVisitDate'); ?></label>
                    <input type="date" name="prior_visit_date" value="<?php print dol_escape_htmltag($prefill['prior_visit_date']); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('PriorVisitText'); ?></label>
                    <textarea name="prior_visit_text" rows="2" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPPriorVisitTextPlaceholder')); ?>"><?php print dol_escape_htmltag($prefill['prior_visit_text']); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Tags of the plan (native preventionplan category type) -->
        <?php if (isModEnabled('categorie')) { ?>
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-tags"></i> <?php print $langs->trans('Categories'); ?></div>
            <?php print $form->multiselectarray('categories', $form->select_all_categories('preventionplan', '', 'parent', 64, 0, 1), $prefill['categories'], '', 0, 'digirisk-mobile-tags-select maxwidth500'); ?>
        </div>
        <?php } ?>

        <!-- Card 4: risks — one self-contained block per danger category (description, photos, protections) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('MobilePPRisks'); ?></div>
            <div class="digirisk-mobile-risk-list digirisk-mobile-risk-list--blocks">
                <?php
                // Edit mode: render the already selected risks with the very same fragment the JS clones
                foreach ($prefill['risks'] as $prefillRiskIndex => $prefillRisk) {
                    if (!isset($dangerMap[$prefillRisk['category']])) {
                        continue;
                    }
                    $prefillRiskCat = $dangerMap[$prefillRisk['category']];

                    $blockIndex       = (int) $prefillRiskIndex;
                    $blockPosition    = $prefillRisk['category'];
                    $blockName        = $prefillRiskCat['name'];
                    $blockThumbnail   = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $prefillRiskCat['thumbnail_name'] . '.png';
                    $blockDescription = $prefillRisk['description'];
                    $blockCompanyEu   = $prefillRisk['company_eu'];
                    $blockCompanyEe   = $prefillRisk['company_ee'];

                    // Edit mode: bring the photos already attached to the risk into the temporary
                    // upload directory, so the media block manages them like the new ones
                    digiriskMobileSeedRiskPhotos($object->element, $object->ref, (int) $prefillRisk['category'], $uploadToken, $blockIndex);
                    $blockUploadSubDir = digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);

                    $blockProtections = [];
                    foreach ($prefillRisk['protections'] as $prefillProtection) {
                        if (!isset($protectionMap[$prefillProtection['position']])) {
                            continue;
                        }
                        $blockProtections[] = [
                            'position'  => $prefillProtection['position'],
                            'thumbnail' => DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $protectionMap[$prefillProtection['position']]['name_thumbnail'],
                            'name'      => $protectionMap[$prefillProtection['position']]['name'],
                            'comment'   => $prefillProtection['comment'],
                        ];
                    }

                    include __DIR__ . '/preventionplan_mobile_risk_block.tpl.php';
                }
                ?>
            </div>
            <div class="digirisk-mobile-risk-empty <?php print empty($prefill['risks']) ? '' : 'hidden'; ?>"><?php print $langs->trans('MobilePPNoRiskYet'); ?></div>
            <button type="button" class="digirisk-mobile-risk-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddRisk'); ?></button>
        </div>

        <!-- Card 6: required certifications (CACES, permits...) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-id-badge"></i> <?php print $langs->trans('MobilePPCertifications'); ?></div>
            <div class="digirisk-mobile-cert-picker-row">
                <select id="digirisk_cert_picker" class="digirisk-mobile-cert-picker">
                    <option value=""><?php print $langs->trans('Select'); ?></option>
                    <?php foreach (digiriskGetCertificationOptions() as $certCode => $certLabel) { ?>
                        <option value="<?php print dol_escape_htmltag($certCode); ?>"><?php print dol_escape_htmltag($certLabel); ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="digirisk-mobile-cert-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddCertification'); ?></button>
            </div>
            <?php print ajax_combobox('digirisk_cert_picker'); ?>
            <div class="digirisk-mobile-cert-list">
                <?php
                // Edit mode: render the already selected certifications exactly like the JS does
                $prefillCertOptions = digiriskGetCertificationOptions(false);
                foreach ($prefill['certifications'] as $prefillCertIndex => $prefillCert) {
                    $prefillCertLabel = $prefillCertOptions[$prefillCert['code']] ?? $prefillCert['code'];
                ?>
                <div class="digirisk-mobile-cert-item" data-code="<?php print dol_escape_htmltag($prefillCert['code']); ?>">
                    <span class="digirisk-mobile-cert-item-label"><?php print dol_escape_htmltag($prefillCertLabel); ?></span>
                    <input type="hidden" name="cert_code[<?php print (int) $prefillCertIndex; ?>]" value="<?php print dol_escape_htmltag($prefillCert['code']); ?>">
                    <label class="digirisk-mobile-cert-item-mandatory">
                        <input type="checkbox" name="cert_mandatory[<?php print (int) $prefillCertIndex; ?>]" value="1"<?php print !empty($prefillCert['mandatory']) ? ' checked' : ''; ?>>
                        <span><?php print $langs->trans('MobilePPMandatory'); ?></span>
                    </label>
                    <button type="button" class="digirisk-mobile-cert-item-delete"><i class="fas fa-trash"></i></button>
                </div>
                <?php } ?>
            </div>
        </div>

        <button type="submit" class="digirisk-mobile-submit wpeo-button button-blue no-load">
            <i class="fas <?php print !empty($isEdit) ? 'fa-save' : 'fa-plus-circle'; ?>"></i>
            <?php print !empty($isEdit) ? $langs->trans('Save') : $langs->trans('MobilePPSubmit'); ?>
        </button>

        <!-- Risk picker modal -->
        <div class="digirisk-mobile-risk-modal hidden">
            <div class="digirisk-mobile-risk-modal__overlay"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPRiskModalTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-risk-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-risk-modal__grid">
                    <?php
                    if (!empty($dangerCategories)) {
                        foreach ($dangerCategories as $dangerCategory) {
                            $thumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $dangerCategory['thumbnail_name'] . '.png';
                            ?>
                            <div class="digirisk-mobile-risk-option" data-position="<?php print dol_escape_htmltag($dangerCategory['position']); ?>" data-name="<?php print dol_escape_htmltag($dangerCategory['name']); ?>" data-thumbnail="<?php print dol_escape_htmltag($thumb); ?>">
                                <img src="<?php print $thumb; ?>" alt="">
                                <span><?php print dol_escape_htmltag($dangerCategory['name']); ?></span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Protection picker modal (signalisation OBLIGATION / EPI pictos) -->
        <div class="digirisk-mobile-protection-modal hidden">
            <div class="digirisk-mobile-protection-modal__overlay"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPProtectionModalTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-protection-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-risk-modal__grid">
                    <?php
                    foreach ($signalisationCategories as $signalisationCategory) {
                        if (strpos($signalisationCategory['name_thumbnail'], 'OBLIGATION/') !== 0) {
                            continue; // keep only the protection (mandatory EPI) pictos
                        }
                        $thumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $signalisationCategory['name_thumbnail'];
                        ?>
                        <div class="digirisk-mobile-protection-option" data-position="<?php print dol_escape_htmltag($signalisationCategory['position']); ?>" data-name="<?php print dol_escape_htmltag($signalisationCategory['name']); ?>" data-thumbnail="<?php print dol_escape_htmltag($thumb); ?>">
                            <img src="<?php print $thumb; ?>" alt="">
                            <span><?php print dol_escape_htmltag($signalisationCategory['name']); ?></span>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Delete confirmation: a risk block carries its photos and its protections, losing it by
             mistake on a phone is easy and there is no undo before the form is submitted -->
        <div class="digirisk-mobile-confirm-modal hidden">
            <div class="digirisk-mobile-confirm-modal__overlay digirisk-mobile-confirm-cancel"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobilePPDeleteRiskTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-confirm-cancel"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-confirm-modal__body"></div>
                <div class="digirisk-mobile-confirm-modal__actions">
                    <button type="button" class="digirisk-mobile-confirm-cancel wpeo-button button-grey"><?php print $langs->trans('Cancel'); ?></button>
                    <button type="button" class="digirisk-mobile-confirm-delete wpeo-button button-red"><i class="fas fa-trash"></i> <?php print $langs->trans('Delete'); ?></button>
                </div>
            </div>
        </div>

        <?php
        /*
         * Fragments the JavaScript clones when the user adds a risk or a protection.
         * Rendered with the very same templates as the server-side rows, so both stay in sync;
         * only the row indexes are placeholders the JS replaces.
         */
        ?>
        <template class="digirisk-mobile-risk-block-template"><?php
            $blockIndex        = '__RISKINDEX__';
            $blockPosition     = '';
            $blockName         = '';
            $blockThumbnail    = '';
            $blockDescription  = '';
            // A risk added on site concerns both companies until the user says otherwise
            $blockCompanyEu    = 1;
            $blockCompanyEe    = 1;
            $blockUploadSubDir = digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);
            $blockProtections  = [];
            include __DIR__ . '/preventionplan_mobile_risk_block.tpl.php';

            // The media block creates its upload directory as it renders: drop the one the
            // placeholder index just created, the real ones are created block by block
            dol_delete_dir_recursive($conf->digiriskdolibarr->dir_output . '/' . $blockUploadSubDir);
        ?></template>
        <template class="digirisk-mobile-protection-row-template"><?php
            $rowRiskIndex = '__RISKINDEX__';
            $rowIndex     = '__INDEX__';
            $rowPosition  = '';
            $rowThumbnail = '';
            $rowName      = '';
            $rowComment   = '';
            include __DIR__ . '/preventionplan_mobile_protection_row.tpl.php';
        ?></template>
    </form>
</div>
<?php
// Photo editor the Saturne media block opens on every shot (crop, rotate, annotate)
$langs->load('medias@saturne');
include dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');
