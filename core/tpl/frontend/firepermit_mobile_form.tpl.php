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
 * \file    core/tpl/frontend/firepermit_mobile_form.tpl.php
 * \ingroup digiriskdolibarr
 * \brief   Creation form screen of the mobile fire permit interface.
 *          Expects: $conf, $db, $form, $langs, $mysoc, $user, $savedSignature, $uploadToken,
 *                   $prefill, $isEdit, $object, $preventionplan, $digiriskelement.
 */

global $conf, $db, $langs, $mysoc, $user;

$hasSignature            = digiriskIsValidSignature($savedSignature);
$sirenLookupUrl          = dol_buildpath('/custom/digiriskdolibarr/core/ajax/mobile_siren_lookup.php', 1);
$saveSignatureUrl        = dol_buildpath('/custom/digiriskdolibarr/core/ajax/save_user_signature.php', 1);
$preventionPlanLookupUrl = dol_buildpath('/custom/digiriskdolibarr/core/ajax/mobile_preventionplan_lookup.php', 1);

// Exclude the elements sitting in the trash from the location picker, like the fire permit card does.
$deletedElements = $digiriskelement->getMultiEntityTrashList();
if (empty($deletedElements)) {
    $deletedElements = [0];
}

// Types of work (fire permit danger categories) and signalisation pictos (protections), indexed by
// position: used both to render the already selected items and to fill the picker modals
$risk         = new Risk($db);
$workTypes    = $risk->getFirePermitDangerCategories();
$workTypes    = is_array($workTypes) ? $workTypes : [];
$workTypeMap  = [];
foreach ($workTypes as $workTypeItem) {
    $workTypeMap[$workTypeItem['position']] = $workTypeItem;
}

$signalisationFile       = DOL_DOCUMENT_ROOT . '/custom/digiriskdolibarr/js/json/signalisationCategories.json';
$signalisationCategories = file_exists($signalisationFile) ? (json_decode(file_get_contents($signalisationFile), true) ?: []) : [];
$protectionMap           = [];
foreach ($signalisationCategories as $signalisationItem) {
    $protectionMap[$signalisationItem['position']] = $signalisationItem;
}

// Opening hours of the company, offered as a starting point for the work schedules
$companyHours = [];
foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $companyHoursDay) {
    $companyHours[$companyHoursDay] = getDolGlobalString('MAIN_INFO_OPENINGHOURS_' . strtoupper($companyHoursDay));
}
?>
<div class="pwa-container digirisk-mobile">
    <form method="POST" action="<?php print $_SERVER['PHP_SELF']; ?>" class="digirisk-mobile-form digirisk-mobile-form--firepermit"
          data-has-signature="<?php print ($hasSignature || !empty($isEdit)) ? '1' : '0'; ?>"
          data-siren-lookup-url="<?php print dol_escape_htmltag($sirenLookupUrl); ?>"
          data-save-signature-url="<?php print dol_escape_htmltag($saveSignatureUrl); ?>"
          data-preventionplan-lookup-url="<?php print dol_escape_htmltag($preventionPlanLookupUrl); ?>"
          data-empty-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEmptySignature')); ?>"
          data-need-signature-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorNoSignature')); ?>"
          data-invalid-siren-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidSiren')); ?>"
          data-company-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyFound')); ?>"
          data-company-not-found-label="<?php print dol_escape_htmltag($langs->trans('MobilePPCompanyNotFound')); ?>"
          data-end-before-start-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndBeforeStart')); ?>"
          data-max-span-label="<?php print dol_escape_htmltag($langs->trans('MobileFPErrorMaxOneMonth')); ?>"
          data-max-span-days="<?php print DIGIRISK_MOBILE_FIREPERMIT_MAX_SPAN_DAYS; ?>"
          data-dates-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorDatesRequired')); ?>"
          data-date-start-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorStartRequired')); ?>"
          data-date-end-required-label="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorEndRequired')); ?>"
          data-risk-comment-label="<?php print dol_escape_htmltag($langs->trans('MobilePPRiskComment')); ?>"
          data-mandatory-label="<?php print dol_escape_htmltag($langs->trans('MobilePPMandatory')); ?>"
          data-risk-start-index="<?php print count($prefill['risks']); ?>"
<?php // trans() efface un %s auquel rien n'est passe : on lui donne le marqueur lui-meme, la
              // substitution du nom se faisant cote JavaScript au moment de la suppression ?>
          data-delete-risk-label="<?php print dol_escape_htmltag($langs->trans('MobileFPDeleteWorkTypeConfirm', '%s')); ?>"
          data-cert-start-index="<?php print count($prefill['certifications']); ?>">
        <input type="hidden" name="token" value="<?php print newToken(); ?>">
        <input type="hidden" name="action" value="add_mobile">
        <?php if (!empty($isEdit)) { ?><input type="hidden" name="id" value="<?php print (int) $object->id; ?>"><?php } ?>
        <input type="hidden" name="ext_society_id" class="digirisk-mobile-ext-society-id" value="<?php print (int) $prefill["ext_society_id"]; ?>">
        <input type="hidden" name="resp_contact_id" class="digirisk-mobile-resp-contact-id" value="<?php print (int) $prefill["resp_contact_id"]; ?>">

        <div class="digirisk-mobile-form-errors hidden"></div>

        <!-- Card 1: what the job is, where and when it takes place, capped to one month -->
        <div class="digirisk-mobile-card">
            <?php
            // Progress strip: where the permit stands, from its creation to its archiving.
            // Nothing is signed nor locked yet at this point, only the step being played is green.
            $workflowIcons = digiriskMobileWorkflowIcons();

            $stepsCreation = [
                [
                    'title'   => $langs->trans('MobileFPStepCreated'),
                    'status'  => !empty($isEdit) ? $langs->transnoentities('MobileStepDone') : $langs->transnoentities('MobileStepInProgress'),
                    'date'    => dol_print_date(dol_now(), 'day'),
                    'done'    => !empty($isEdit),
                    'current' => empty($isEdit),
                    'viewBox' => $workflowIcons['created']['viewBox'],
                    'svg'     => $workflowIcons['created']['svg'],
                ],
                [
                    'title'   => $langs->trans('MobileStepUserCompanyResponsible'),
                    'status'  => $langs->transnoentities('MobileStepTodo'),
                    'date'    => '',
                    'done'    => false,
                    'current' => !empty($isEdit),
                    'viewBox' => $workflowIcons['user']['viewBox'],
                    'svg'     => $workflowIcons['user']['svg'],
                ],
                [
                    'title'   => $langs->trans('MobileStepExteriorCompanyResponsible'),
                    'status'  => $langs->transnoentities('MobileStepTodo'),
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => $workflowIcons['company']['viewBox'],
                    'svg'     => $workflowIcons['company']['svg'],
                ],
                [
                    'title'   => $langs->trans('MobileStepLock'),
                    'status'  => $langs->transnoentities('MobileStepTodo'),
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => $workflowIcons['lock']['viewBox'],
                    'svg'     => $workflowIcons['lock']['svg'],
                ],
                [
                    'title'   => $langs->trans('MobileStepArchive'),
                    'status'  => $langs->transnoentities('MobileStepTodo'),
                    'date'    => '',
                    'done'    => false,
                    'current' => false,
                    'viewBox' => $workflowIcons['archive']['viewBox'],
                    'svg'     => $workflowIcons['archive']['svg'],
                ],
            ];

            print digiriskMobileRenderWorkflow($stepsCreation, (!empty($isEdit) && $object->ref) ? $object->getNomUrl(1) : '', true);
            ?>

            <div class="digirisk-mobile-field digirisk-mobile-field--spaced">
                <label><?php print $langs->trans('MobileFPMotif'); ?> *</label>
                <input type="text" name="label" class="digirisk-mobile-label" required placeholder="<?php print dol_escape_htmltag($langs->trans('MobileFPMotifPlaceholder')); ?>" value="<?php print dol_escape_htmltag($prefill['label']); ?>">
            </div>

            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('Location'); ?></label>
                <?php print $digiriskelement->selectDigiriskElementList($prefill['fk_element'], 'fk_element', ['customsql' => ' t.rowid NOT IN (' . implode(',', $deletedElements) . ')'], 0, 0, [], 0, 0, 'digirisk-mobile-element-select', 0, false, 1); ?>
            </div>

            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateStart'); ?> *</label>
                    <input type="datetime-local" name="date_start" class="digirisk-mobile-date-start" value="<?php print dol_escape_htmltag($prefill["date_start"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('DateEnd'); ?> *</label>
                    <input type="datetime-local" name="date_end" class="digirisk-mobile-date-end" value="<?php print dol_escape_htmltag($prefill["date_end"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobileFPMaxOneMonthHint'); ?></div>
            <div class="digirisk-mobile-date-error hidden"></div>

            <!-- Work schedules, stored as the Saturne schedules of the permit -->
            <div class="digirisk-mobile-field digirisk-mobile-schedules">
                <div class="digirisk-mobile-schedules__head">
                    <label><?php print $langs->trans('MobileFPSchedules'); ?></label>
                    <div class="digirisk-mobile-schedules__actions">
                        <a href="<?php print dol_buildpath('/admin/openinghours.php', 1); ?>" target="_blank"><i class="fas fa-cog"></i> <?php print $langs->trans('MobilePPConfigHours'); ?></a>
                        <button type="button" class="digirisk-mobile-schedules-copy" title="<?php print dol_escape_htmltag($langs->trans('MobilePPCopyCompanyHours')); ?>" data-company-hours="<?php print dol_escape_htmltag(json_encode($companyHours)); ?>"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div class="digirisk-mobile-schedules__table">
                    <table class="digirisk-mobile-table">
                        <thead>
                            <tr>
                                <th></th>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $dayLabel) { ?>
                                    <th><?php print dol_escape_htmltag(dol_substr($langs->transnoentities($dayLabel), 0, 3)); ?></th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="digirisk-mobile-table__head"><?php print $langs->trans('Morning'); ?></td>
                                <?php foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) { ?>
                                    <td><input type="text" name="schedule_<?php print $day; ?>_am" value="<?php print dol_escape_htmltag($prefill['schedule_' . $day . '_am']); ?>"></td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <td class="digirisk-mobile-table__head"><?php print $langs->trans('Afternoon'); ?></td>
                                <?php foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) { ?>
                                    <td><input type="text" name="schedule_<?php print $day; ?>_pm" value="<?php print dol_escape_htmltag($prefill['schedule_' . $day . '_pm']); ?>"></td>
                                <?php } ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card 2: interior company, auto-signed by the connected responsible -->
        <div class="digirisk-mobile-card digirisk-mobile-extsign digirisk-mobile-extsign--form">
            <div class="digirisk-mobile-extsign__title digirisk-mobile-extsign__title--split">
                <div class="digirisk-mobile-extsign__heading"><i class="fas fa-user-tie"></i> <?php print $langs->trans('FirePermitUserCompany'); ?></div>

                <div class="digirisk-mobile-signature-saved <?php print $hasSignature ? '' : 'hidden'; ?>">
                    <div class="digirisk-mobile-signature-badge">
                        <span><i class="fas fa-check-circle"></i> <?php print $langs->trans('MobilePPSignatureSaved'); ?></span>
                        <button type="button" class="digirisk-mobile-signature-show" aria-label="<?php print dol_escape_htmltag($langs->trans('MobilePPSignatureSaved')); ?>"><i class="fas fa-signature"></i></button>
                    </div>
                </div>
            </div>

            <div class="digirisk-mobile-extsign__who">
                <div class="digirisk-mobile-extsign__line">
                    <div><span><?php print $langs->trans('ThirdParty'); ?> :</span> <strong><?php print dol_escape_htmltag($mysoc->name); ?></strong></div>
                    <div><span><?php print $langs->transcountry('ProfId1Short', $mysoc->country_code); ?> :</span> <?php print dol_escape_htmltag($mysoc->idprof1); ?></div>
                </div>
                <div class="digirisk-mobile-extsign__line">
                    <div><span><?php print $langs->trans('MobileResponsibleShort'); ?></span> <strong><?php print dol_escape_htmltag($user->getFullName($langs)); ?></strong></div>
                    <div><span><?php print $langs->trans('PhoneShort'); ?> :</span> <?php print dol_escape_htmltag($user->office_phone); ?></div>
                </div>
                <div class="digirisk-mobile-extsign__line">
                    <div><span><?php print $langs->trans('Email'); ?> :</span> <?php print dol_escape_htmltag($user->email); ?></div>
                    <div><span><?php print $langs->trans('PostOrFunction'); ?> :</span> <?php print dol_escape_htmltag($user->job ?? ''); ?></div>
                </div>
            </div>

            <div class="digirisk-mobile-signature">
                <div class="digirisk-mobile-signature-draw <?php print $hasSignature ? 'hidden' : ''; ?>">
                    <div class="digirisk-mobile-signature-hint"><i class="fas fa-pen-nib"></i> <?php print $langs->trans('MobilePPDrawSignatureHint'); ?></div>
                    <canvas class="digirisk-mobile-signature-canvas"></canvas>
                    <div class="digirisk-mobile-signature-actions">
                        <button type="button" class="digirisk-mobile-signature-clear wpeo-button button-grey"><?php print $langs->trans('MobilePPClearSignature'); ?></button>
                        <button type="button" class="digirisk-mobile-signature-save wpeo-button button-blue"><?php print $langs->trans('MobilePPSaveSignature'); ?></button>
                    </div>
                </div>
                <div class="digirisk-mobile-signature-status"></div>
            </div>

            <!-- Saved signature, shown on demand so it can be checked before it is reused -->
            <div class="wpeo-modal" id="modal-signature-preview">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2 class="modal-title"><?php print $langs->trans('MobilePPSignatureSaved'); ?></h2>
                        <div class="modal-close digirisk-mobile-signature-preview-close"><i class="fas fa-times"></i></div>
                    </div>
                    <div class="modal-content center">
                        <img class="digirisk-mobile-signature-preview-img" src="<?php print $hasSignature ? dol_escape_htmltag($savedSignature) : ''; ?>" alt="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="wpeo-button button-grey digirisk-mobile-signature-preview-close"><?php print $langs->trans('Close'); ?></button>
                        <button type="button" class="wpeo-button button-blue digirisk-mobile-signature-edit"><i class="fas fa-edit"></i> <?php print $langs->trans('Modify'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: parent prevention plan (mandatory) — picking it pre-fills the company and the period -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-project-diagram"></i> <?php print $langs->trans('PreventionPlanLinked'); ?></div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('PreventionPlan'); ?> *</label>
                <?php print $preventionplan->select_preventionplan_list($prefill['fk_preventionplan'], 'fk_preventionplan', [], '1', 0, [], 0, 0, 'digirisk-mobile-preventionplan-select'); ?>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobileFPPreventionPlanHelp'); ?></div>
        </div>

        <!-- Card 4: exterior company, asked to sign by email. Three ways to fill it in: pick it in
             the third party list, resolve it by SIREN, or type everything for a company to create -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-industry"></i> <?php print $langs->trans('MobilePPExteriorCompany'); ?></div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('MobilePPChooseExistingCompany'); ?></label>
                <div class="digirisk-mobile-picker-row">
                    <?php print $form->select_company($prefill['ext_society_id'], 'ext_society_picker', '', '&nbsp;', 0, 0, [], 0, 'digirisk-mobile-society-select maxwidth500'); ?>
                    <a href="<?php print dol_buildpath('/societe/card.php', 1) . '?action=create'; ?>" target="_blank" class="wpeo-button button-blue" title="<?php print dol_escape_htmltag($langs->trans('NewThirdParty')); ?>"><i class="fas fa-plus"></i></a>
                </div>
            </div>
            <div class="digirisk-mobile-separator"><span><?php print $langs->trans('MobilePPOrFillManually'); ?></span></div>

            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('CompanyName'); ?> *</label>
                    <input type="text" name="ext_society_name" class="digirisk-mobile-ext-society-name" value="<?php print dol_escape_htmltag($prefill["ext_society_name"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('MobileSirenOrSiret'); ?> *</label>
                    <input type="text" name="siren" class="digirisk-mobile-siren-input" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="<?php print dol_escape_htmltag($langs->trans('MobileSirenOrSiretPlaceholder')); ?>" value="<?php print dol_escape_htmltag($prefill["siren"]); ?>" pattern="[\d\s]{9,20}" title="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidSiren')); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-siren-result"></div>

            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('Email'); ?></label>
                <input type="email" name="ext_society_email" class="digirisk-mobile-ext-society-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_email"]); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}">
            </div>
            <div class="digirisk-mobile-field">
                <label><?php print $langs->trans('Address'); ?></label>
                <textarea name="ext_society_address" class="digirisk-mobile-ext-society-address" rows="2"><?php print dol_escape_htmltag($prefill["ext_society_address"]); ?></textarea>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Zip'); ?></label>
                    <input type="text" name="ext_society_zip" class="digirisk-mobile-ext-society-zip" inputmode="numeric" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_zip"]); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Town'); ?></label>
                    <input type="text" name="ext_society_town" class="digirisk-mobile-ext-society-town" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["ext_society_town"]); ?>">
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
                    <label><?php print $langs->trans('Firstname'); ?> *</label>
                    <input type="text" name="resp_firstname" class="digirisk-mobile-resp-firstname" value="<?php print dol_escape_htmltag($prefill["resp_firstname"]); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-row">
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Email'); ?> *</label>
                    <input type="email" name="resp_email" class="digirisk-mobile-resp-email" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_email"]); ?>" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidEmail')); ?>">
                </div>
                <div class="digirisk-mobile-field">
                    <label><?php print $langs->trans('Phone'); ?></label>
                    <input type="tel" name="resp_phone" class="digirisk-mobile-resp-phone" autocomplete="off" value="<?php print dol_escape_htmltag($prefill["resp_phone"]); ?>" pattern="^(\+?\d{1,3}[\-.\s]?)?(\(?\d{1,4}\)?[\-.\s]?)?[\d\-.\s]{5,15}$" title="<?php print dol_escape_htmltag($langs->trans('MobilePPErrorInvalidPhone')); ?>">
                </div>
            </div>
            <div class="digirisk-mobile-help"><?php print $langs->trans('MobilePPEmailForSignatureHelp'); ?></div>
        </div>

        <?php
        // Tags of the permit (native firepermit category type). Tant qu'aucune categorie n'est
        // declaree, le multiselect s'affichait vide sans rien dire : on annonce l'absence et on
        // donne le lien pour en creer une plutot que de laisser chercher.
        if (isModEnabled('categorie')) {
            $permitTagOptions = $form->select_all_categories('digiriskfirepermit', '', 'parent', 64, 0, 1);
            $permitTagOptions = is_array($permitTagOptions) ? $permitTagOptions : [];
        ?>
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-tags"></i> <?php print $langs->trans('Categories'); ?></div>
            <?php if (!empty($permitTagOptions)) {
                print $form->multiselectarray('categories', $permitTagOptions, $prefill['categories'], '', 0, 'digirisk-mobile-tags-select minwidth500 width100p');
            } else { ?>
            <div class="digirisk-mobile-empty">
                <i class="fas fa-info-circle"></i>
                <span><?php print $langs->trans('MobileFPNoTagAvailable'); ?></span>
            </div>
            <a class="digirisk-mobile-empty__action" href="<?php print DOL_URL_ROOT . '/categories/card.php?action=create&type=digiriskfirepermit'; ?>" target="_blank">
                <i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobilePPCreateTag'); ?>
            </a>
            <?php } ?>
        </div>
        <?php } ?>

        <!-- Card 5: types of work — one self-contained block each (description, equipment, photos, protections) -->
        <div class="digirisk-mobile-card">
            <div class="digirisk-mobile-card__title"><i class="fas fa-fire-alt"></i> <?php print $langs->trans('MobileFPWorkTypes'); ?></div>
            <div class="digirisk-mobile-risk-list digirisk-mobile-risk-list--blocks">
                <?php
                // Edit mode: render the already selected types of work with the very same fragment the JS clones
                foreach ($prefill['risks'] as $prefillRiskIndex => $prefillRisk) {
                    if (!isset($workTypeMap[$prefillRisk['category']])) {
                        continue;
                    }
                    $prefillWorkType = $workTypeMap[$prefillRisk['category']];

                    $blockIndex       = (int) $prefillRiskIndex;
                    $blockPosition    = $prefillRisk['category'];
                    $blockName        = $prefillWorkType['name'];
                    $blockThumbnail   = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/typeDeTravaux/' . $prefillWorkType['thumbnail_name'] . '.png';
                    $blockDescription = $prefillRisk['description'];
                    $blockEquipment   = $prefillRisk['used_equipment'];

                    // Edit mode: bring the photos already attached to the type of work into the
                    // temporary upload directory, so the media block manages them like the new ones
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

                    include __DIR__ . '/firepermit_mobile_worktype_block.tpl.php';
                }
                ?>
            </div>
            <div class="digirisk-mobile-risk-empty <?php print empty($prefill['risks']) ? '' : 'hidden'; ?>"><?php print $langs->trans('MobileFPNoWorkTypeYet'); ?></div>
            <button type="button" class="digirisk-mobile-risk-add wpeo-button button-blue"><i class="fas fa-plus-circle"></i> <?php print $langs->trans('MobileFPAddWorkType'); ?></button>
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
            <?php print !empty($isEdit) ? $langs->trans('Save') : $langs->trans('MobileFPSubmit'); ?>
        </button>

        <!-- Type of work picker modal -->
        <div class="digirisk-mobile-risk-modal hidden">
            <div class="digirisk-mobile-risk-modal__overlay"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobileFPWorkTypeModalTitle'); ?></span>
                    <button type="button" class="digirisk-mobile-risk-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="digirisk-mobile-risk-modal__grid">
                    <?php
                    foreach ($workTypes as $workType) {
                        $thumb = DOL_URL_ROOT . '/custom/digiriskdolibarr/img/typeDeTravaux/' . $workType['thumbnail_name'] . '.png';
                        ?>
                        <div class="digirisk-mobile-risk-option" data-position="<?php print dol_escape_htmltag($workType['position']); ?>" data-name="<?php print dol_escape_htmltag($workType['name']); ?>" data-thumbnail="<?php print dol_escape_htmltag($thumb); ?>">
                            <img src="<?php print $thumb; ?>" alt="">
                            <span><?php print dol_escape_htmltag($workType['name']); ?></span>
                        </div>
                        <?php
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

        <!-- Delete confirmation: a block carries its photos and its protections, losing it by
             mistake on a phone is easy and there is no undo before the form is submitted -->
        <div class="digirisk-mobile-confirm-modal hidden">
            <div class="digirisk-mobile-confirm-modal__overlay digirisk-mobile-confirm-cancel"></div>
            <div class="digirisk-mobile-risk-modal__dialog">
                <div class="digirisk-mobile-risk-modal__header">
                    <span><?php print $langs->trans('MobileFPDeleteWorkTypeTitle'); ?></span>
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
         * Fragments the JavaScript clones when the user adds a type of work or a protection.
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
            $blockEquipment    = '';
            $blockUploadSubDir = digiriskMobileRiskUploadSubDir($uploadToken, $blockIndex);
            $blockProtections  = [];
            include __DIR__ . '/firepermit_mobile_worktype_block.tpl.php';

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
            include __DIR__ . '/digiriskdolibarr_mobile_protection_row.tpl.php';
        ?></template>
    </form>
</div>
<?php
// Photo editor the Saturne media block opens on every shot (crop, rotate, annotate)
$langs->load('medias@saturne');
include dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');
