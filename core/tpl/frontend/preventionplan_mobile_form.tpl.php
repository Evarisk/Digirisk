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
 *          Expects: $conf, $langs, $mysoc, $user, $savedSignature.
 */

global $conf, $langs, $mysoc, $user;

$hasSignature     = digiriskIsValidSignature($savedSignature);
$sirenLookupUrl   = dol_buildpath('/custom/digiriskdolibarr/core/ajax/preventionplan_siren_lookup.php', 1);
$saveSignatureUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/save_user_signature.php', 1);
?>
<div class="pwa-container preventionplan-mobile">
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" class="digirisk-mobile-form"
          data-has-signature="<?php print ($hasSignature || !empty($isEdit)) ? '1' : '0'; ?>"
          data-siren-lookup-url="<?php print dol_escape_htmltag($sirenLookupUrl); ?>"
          data-save-signature-url="<?php print dol_escape_htmltag($saveSignatureUrl); ?>"
          data-empty-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEmptySignature')); ?>"
          data-need-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorNoSignature')); ?>"
          data-invalid-siren-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidSiren')); ?>"
          data-company-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyFound')); ?>"
          data-company-not-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyNotFound')); ?>"
          data-end-before-start-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndBeforeStart')); ?>"
          data-max-one-year-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorMaxOneYear')); ?>"
          data-dates-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorDatesRequired')); ?>"
          data-date-start-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorStartRequired')); ?>"
          data-date-end-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndRequired')); ?>"
          data-risk-comment-label="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>"
          data-mandatory-label="<?php print dol_escape_htmltag($langs->trans('MobilePPMandatory')); ?>"
          data-protection-start-index="<?php print count($prefill['protections']); ?>"
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

        <!-- Card 2: exterior company resolved by SIREN, asked to sign by email -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-industry"></i> <?php print $langs->trans('MobilePPExteriorCompany'); ?></div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('Siren'); ?></label>
                <div class="digirisk-mobile-siren-row">
                    <input type="text" name="siren" class="digirisk-mobile-siren-input" inputmode="numeric" autocomplete="off" maxlength="14" value="<?php print dol_escape_htmltag($prefill["siren"]); ?>">
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

        <!-- Card 4: risks (danger categories) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-exclamation-triangle"></i> <?php print $langs->trans('MobilePPRisks'); ?></div>
            <button type="button" class="digirisk-mobile-risk-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddRisk'); ?></button>
            <div class="digirisk-mobile-risk-list">
                <?php
                // Edit mode: render the already selected risks exactly like the JS does
                $dangerMap = [];
                foreach (Risk::getDangerCategories() as $dangerCategoryItem) {
                    $dangerMap[$dangerCategoryItem['position']] = $dangerCategoryItem;
                }
                foreach ($prefill['risks'] as $prefillRisk) {
                    if (!isset($dangerMap[$prefillRisk['category']])) {
                        continue;
                    }
                    $prefillRiskCat = $dangerMap[$prefillRisk['category']];
                    $prefillThumb   = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/categorieDangers/' . $prefillRiskCat['thumbnail_name'] . '.png';
                ?>
                <div class="digirisk-mobile-risk-item" data-position="<?php print dol_escape_htmltag($prefillRisk['category']); ?>">
                    <img class="digirisk-mobile-risk-item-photo" src="<?php print $prefillThumb; ?>" alt="" title="<?php print dol_escape_htmltag($prefillRiskCat['name']); ?>">
                    <input type="hidden" name="risk_category[]" value="<?php print dol_escape_htmltag($prefillRisk['category']); ?>">
                    <input type="text" name="risk_comment[]" class="digirisk-mobile-risk-item-comment" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>" value="<?php print dol_escape_htmltag($prefillRisk['description']); ?>">
                    <button type="button" class="digirisk-mobile-risk-item-delete"><i class="fas fa-trash"></i></button>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Card 5: protections (EPI / mandatory equipment) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-hard-hat"></i> <?php print $langs->trans('MobilePPProtections'); ?></div>
            <button type="button" class="digirisk-mobile-protection-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPAddProtection'); ?></button>
            <div class="digirisk-mobile-protection-list">
                <?php
                // Edit mode: render the already selected protections exactly like the JS does
                $signalisationFile     = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
                $prefillProtectionMap  = [];
                if (file_exists($signalisationFile)) {
                    foreach ((json_decode(file_get_contents($signalisationFile), true) ?: []) as $signalisationItem) {
                        $prefillProtectionMap[$signalisationItem['position']] = $signalisationItem;
                    }
                }
                foreach ($prefill['protections'] as $prefillProtectionIndex => $prefillProtection) {
                    if (!isset($prefillProtectionMap[$prefillProtection['position']])) {
                        continue;
                    }
                    $prefillProtectionCat = $prefillProtectionMap[$prefillProtection['position']];
                ?>
                <div class="digirisk-mobile-protection-item" data-position="<?php print dol_escape_htmltag($prefillProtection['position']); ?>">
                    <img class="digirisk-mobile-protection-item-photo" src="<?php print DOL_URL_ROOT . '/custom/digiriskdolibarr/img/' . $prefillProtectionCat['name_thumbnail']; ?>" alt="" title="<?php print dol_escape_htmltag($prefillProtectionCat['name']); ?>">
                    <input type="hidden" name="protection_position[<?php print (int) $prefillProtectionIndex; ?>]" value="<?php print dol_escape_htmltag($prefillProtection['position']); ?>">
                    <input type="text" name="protection_comment[<?php print (int) $prefillProtectionIndex; ?>]" class="digirisk-mobile-protection-item-comment" placeholder="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>" value="<?php print dol_escape_htmltag($prefillProtection['comment']); ?>">
                    <label class="digirisk-mobile-protection-item-mandatory">
                        <input type="checkbox" name="protection_mandatory[<?php print (int) $prefillProtectionIndex; ?>]" value="1"<?php print !empty($prefillProtection['mandatory']) ? ' checked' : ''; ?>>
                        <span><?php print $langs->trans('MobilePPMandatory'); ?></span>
                    </label>
                    <button type="button" class="digirisk-mobile-protection-item-delete"><i class="fas fa-trash"></i></button>
                </div>
                <?php } ?>
            </div>
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
                $prefillCertOptions = digiriskGetCertificationOptions();
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
                    $dangerCategories = Risk::getDangerCategories();
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
                    $signalisationFile       = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
                    $signalisationCategories = file_exists($signalisationFile) ? json_decode(file_get_contents($signalisationFile), true) : [];
                    if (is_array($signalisationCategories)) {
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
                    }
                    ?>
                </div>
            </div>
        </div>
    </form>
</div>
